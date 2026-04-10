<?php

require_once __DIR__ . '/ReportBuilderInterface.php';

class LaporanPiutangReportBuilder implements ReportBuilderInterface {
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

        // --- Data Fetching Logic (Matched with api/laporan_piutang_handler.php) ---
        $sql = "
            SELECT 
                p.customer_id,
                p.customer_name,
                a.nomor_anggota,
                SUM(p.total) as total_kredit,
                SUM(p.bayar) as total_bayar,
                SUM(p.total - p.bayar) as sisa_hutang
            FROM penjualan p
            LEFT JOIN anggota a ON p.customer_id = a.id
            WHERE p.payment_method = 'hutang' 
              AND p.status = 'completed'
            GROUP BY p.customer_id, p.customer_name, a.nomor_anggota
            HAVING sisa_hutang > 0
            ORDER BY p.customer_name ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = stmt_fetch_all($stmt);
        $stmt->close();

        // --- PDF Generation ---
        $this->pdf->report_title = 'Laporan Piutang Anggota';
        $this->pdf->SetMargins(10, 10, 10); 
        $this->pdf->AddPage('P', 'A4'); // Portrait (P)
        
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
        $w_name = 60;
        $w_no_anggota = 30;
        $w_amount = 30;
        
        $this->pdf->Cell($w_no, 10, 'No', 1, 0, 'C', true);
        $this->pdf->Cell($w_name, 10, 'Nama Anggota', 1, 0, 'L', true);
        $this->pdf->Cell($w_no_anggota, 10, 'No. Anggota', 1, 0, 'C', true);
        $this->pdf->Cell($w_amount, 10, 'Total Kredit', 1, 0, 'R', true);
        $this->pdf->Cell($w_amount, 10, 'Dah Bayar', 1, 0, 'R', true);
        $this->pdf->Cell($w_amount, 10, 'Sisa Piutang', 1, 1, 'R', true);

        // Table Body
        $this->pdf->SetFont('Helvetica', '', 9);
        $grand_total_kredit = 0;
        $grand_total_bayar = 0;
        $grand_total_sisa = 0;
        $no = 1;

        foreach ($result as $row) {
            $widths = [$w_no, $w_name, $w_no_anggota, $w_amount, $w_amount, $w_amount];
            $aligns = ['C', 'L', 'C', 'R', 'R', 'R'];
            
            $this->pdf->Row($widths, [
                $no++,
                $row['customer_name'],
                $row['nomor_anggota'] ?: '-',
                number_format($row['total_kredit'], 0, ',', '.'),
                number_format($row['total_bayar'], 0, ',', '.'),
                number_format($row['sisa_hutang'], 0, ',', '.')
            ], $aligns);

            $grand_total_kredit += (float)$row['total_kredit'];
            $grand_total_bayar += (float)$row['total_bayar'];
            $grand_total_sisa += (float)$row['sisa_hutang'];
        }

        // Footer Totals
        $this->pdf->SetFont('Helvetica', 'B', 10);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell($w_no + $w_name + $w_no_anggota, 10, 'GRAND TOTAL', 1, 0, 'C', true);
        $this->pdf->Cell($w_amount, 10, number_format($grand_total_kredit, 0, ',', '.'), 1, 0, 'R', true);
        $this->pdf->Cell($w_amount, 10, number_format($grand_total_bayar, 0, ',', '.'), 1, 0, 'R', true);
        $this->pdf->Cell($w_amount, 10, number_format($grand_total_sisa, 0, ',', '.'), 1, 1, 'R', true);
    }
}
