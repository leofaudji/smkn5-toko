<?php

require_once __DIR__ . '/ReportBuilderInterface.php';

class LaporanStokReportBuilder implements ReportBuilderInterface {
    protected $pdf;
    protected $conn;
    protected $params;

    public function __construct(PDF $pdf, mysqli $conn, array $params) {
        $this->pdf = $pdf;
        $this->conn = $conn;
        $this->params = $params;
    }

    public function build(): void {
        $user_id = $this->params['user_id'] ?? 1;
        $start_date = $this->params['start_date'] ?? null;
        $end_date = $this->params['end_date'] ?? null;

        if (!$start_date || !$end_date) {
            throw new Exception("Rentang tanggal wajib diisi.");
        }

        // --- Data Fetching Logic (Matched with api/laporan_stok_handler.php) ---
        $query = "
            SELECT 
                i.id, 
                i.nama_barang, 
                i.sku, 
                i.harga_beli,
                COALESCE(sa.stok_awal, 0) as stok_awal,
                COALESCE(p.masuk, 0) as masuk,
                COALESCE(p.keluar, 0) as keluar
            FROM items i
            LEFT JOIN (
                SELECT item_id, SUM(debit - kredit) as stok_awal
                FROM kartu_stok
                WHERE tanggal < ? AND user_id = ?
                GROUP BY item_id
            ) sa ON i.id = sa.item_id
            LEFT JOIN (
                SELECT 
                    item_id, 
                    SUM(debit) as masuk, 
                    SUM(kredit) as keluar
                FROM kartu_stok
                WHERE tanggal BETWEEN ? AND CONCAT(?, ' 23:59:59') AND user_id = ?
                GROUP BY item_id
            ) p ON i.id = p.item_id
            WHERE i.user_id = ?
            ORDER BY i.nama_barang ASC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('sissii', $start_date, $user_id, $start_date, $end_date, $user_id, $user_id);
        $stmt->execute();
        $results = stmt_fetch_all($stmt);
        $stmt->close();

        // --- PDF Generation ---
        $this->pdf->report_title = 'Laporan Stok Barang';
        $this->pdf->SetMargins(10, 10, 10); 
        $this->pdf->AddPage('P', 'A4'); // Portrait orientation
        
        // Title
        $this->pdf->SetFont('Helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, strtoupper($this->pdf->report_title), 0, 1, 'C');
        $this->pdf->SetFont('Helvetica', '', 10);
        $this->pdf->Cell(0, 5, 'Periode: ' . date('d/m/Y', strtotime($start_date)) . ' s/d ' . date('d/m/Y', strtotime($end_date)), 0, 1, 'C');
        $this->pdf->Ln(5);

        // Table Header
        $this->pdf->SetFont('Helvetica', 'B', 7);
        $this->pdf->SetFillColor(240, 240, 240);
        
        $w_no = 7;
        $w_sku = 23;
        $w_name = 55;
        $w_awal = 12;
        $w_masuk = 12;
        $w_keluar = 12;
        $w_akhir = 12;
        $w_price = 27;
        $w_total = 30;
        
        $this->pdf->Cell($w_no, 8, 'No', 1, 0, 'C', true);
        $this->pdf->Cell($w_sku, 8, 'SKU', 1, 0, 'C', true);
        $this->pdf->Cell($w_name, 8, 'Nama Barang', 1, 0, 'L', true);
        $this->pdf->Cell($w_awal, 8, 'Awl', 1, 0, 'C', true);
        $this->pdf->Cell($w_masuk, 8, 'Msk', 1, 0, 'C', true);
        $this->pdf->Cell($w_keluar, 8, 'Klr', 1, 0, 'C', true);
        $this->pdf->Cell($w_akhir, 8, 'Akh', 1, 0, 'C', true);
        $this->pdf->Cell($w_price, 8, 'Harga', 1, 0, 'R', true);
        $this->pdf->Cell($w_total, 8, 'Total', 1, 1, 'R', true);

        // Table Body
        $this->pdf->SetFont('Helvetica', '', 7);
        $total_nilai_all = 0;
        $no = 1;

        foreach ($results as $row) {
            $stok_akhir = (int)$row['stok_awal'] + (int)$row['masuk'] - (int)$row['keluar'];
            $nilai = $stok_akhir * (float)$row['harga_beli'];
            
            $widths = [$w_no, $w_sku, $w_name, $w_awal, $w_masuk, $w_keluar, $w_akhir, $w_price, $w_total];
            $aligns = ['C', 'C', 'L', 'C', 'C', 'C', 'C', 'R', 'R'];
            
            $this->pdf->Row($widths, [
                $no++,
                $row['sku'] ?: '-',
                $row['nama_barang'],
                number_format($row['stok_awal'], 0, ',', '.'),
                number_format($row['masuk'], 0, ',', '.'),
                number_format($row['keluar'], 0, ',', '.'),
                number_format($stok_akhir, 0, ',', '.'),
                number_format($row['harga_beli'], 0, ',', '.'),
                number_format($nilai, 0, ',', '.')
            ], $aligns);

            $total_nilai_all += $nilai;
        }

        // Footer Totals
        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell($w_no + $w_sku + $w_name + $w_awal + $w_masuk + $w_keluar + $w_akhir + $w_price, 8, 'TOTAL NILAI', 1, 0, 'R', true);
        $this->pdf->Cell($w_total, 8, number_format($total_nilai_all, 0, ',', '.'), 1, 1, 'R', true);
    }
}
