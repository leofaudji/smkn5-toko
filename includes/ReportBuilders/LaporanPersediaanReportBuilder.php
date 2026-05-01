<?php

require_once __DIR__ . '/ReportBuilderInterface.php';

class LaporanPersediaanReportBuilder implements ReportBuilderInterface {
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
        $search = $this->params['search'] ?? '';

        // --- Data Fetching Logic ---
        $where_clauses = ["user_id = ?"];
        $bind_types = "i";
        $bind_params = [$user_id];

        if (!empty($search)) {
            $where_clauses[] = "(nama_barang LIKE ? OR sku LIKE ?)";
            $bind_types .= "ss";
            $searchTerm = '%' . $search . '%';
            $bind_params[] = $searchTerm;
            $bind_params[] = $searchTerm;
        }

        $where_sql = "WHERE " . implode(" AND ", $where_clauses);
        $sql = "SELECT id, nama_barang, sku, stok, harga_beli FROM items $where_sql ORDER BY nama_barang ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($bind_types, ...$bind_params);
        $stmt->execute();
        $result = stmt_fetch_all($stmt);
        $stmt->close();

        // --- PDF Generation ---
        $this->pdf->report_title = 'Laporan Nilai Persediaan';
        $this->pdf->SetMargins(10, 10, 10); 
        $this->pdf->AddPage('P', 'A4');
        
        // Title
        $this->pdf->SetFont('Helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, strtoupper($this->pdf->report_title), 0, 1, 'C');
        $this->pdf->SetFont('Helvetica', '', 10);
        $this->pdf->Cell(0, 5, 'Dicetak pada: ' . date('d-m-Y H:i'), 0, 1, 'C');
        $this->pdf->Ln(5);

        // Table Header
        $this->pdf->SetFont('Helvetica', 'B', 10);
        $this->pdf->SetFillColor(240, 240, 240);
        
        $w_no = 10;
        $w_name = 70;
        $w_sku = 30;
        $w_stok = 20;
        $w_price = 30;
        $w_total = 30;
        
        $this->pdf->Cell($w_no, 10, 'No', 1, 0, 'C', true);
        $this->pdf->Cell($w_name, 10, 'Nama Barang', 1, 0, 'L', true);
        $this->pdf->Cell($w_sku, 10, 'SKU', 1, 0, 'C', true);
        $this->pdf->Cell($w_stok, 10, 'Stok', 1, 0, 'C', true);
        $this->pdf->Cell($w_price, 10, 'Harga Beli', 1, 0, 'R', true);
        $this->pdf->Cell($w_total, 10, 'Total Nilai', 1, 1, 'R', true);

        // Table Body
        $this->pdf->SetFont('Helvetica', '', 9);
        $grand_total_nilai = 0;
        $no = 1;

        foreach ($result as $row) {
            $nilai = (float)$row['stok'] * (float)$row['harga_beli'];
            $widths = [$w_no, $w_name, $w_sku, $w_stok, $w_price, $w_total];
            $aligns = ['C', 'L', 'C', 'C', 'R', 'R'];
            
            $this->pdf->Row($widths, [
                $no++,
                $row['nama_barang'],
                $row['sku'] ?: '-',
                number_format($row['stok'], 0, ',', '.'),
                number_format($row['harga_beli'], 0, ',', '.'),
                number_format($nilai, 0, ',', '.')
            ], $aligns);

            $grand_total_nilai += $nilai;
        }

        // Footer Totals
        $this->pdf->SetFont('Helvetica', 'B', 10);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell($w_no + $w_name + $w_sku + $w_stok + $w_price, 10, 'GRAND TOTAL NILAI PERSEDIAAN', 1, 0, 'R', true);
        $this->pdf->Cell($w_total, 10, number_format($grand_total_nilai, 0, ',', '.'), 1, 1, 'R', true);
    }
}
