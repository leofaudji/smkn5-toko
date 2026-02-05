<?php
require_once __DIR__ . '/ReportBuilderInterface.php';

class LaporanNominatifSimpananReportBuilder implements ReportBuilderInterface
{
    private $pdf;
    private $conn;
    private $params;

    public function __construct(PDF $pdf, mysqli $conn, array $params)
    {
        $this->pdf = $pdf;
        $this->conn = $conn;
        $this->params = $params;
    }

    public function build(): void
    {
        $per_tanggal = $this->params['per_tanggal'] ?? date('Y-m-d');

        $this->pdf->SetTitle('Laporan Nominatif Simpanan');
        $this->pdf->report_title = 'LAPORAN NOMINATIF SIMPANAN';
        $this->pdf->report_period = 'Per Tanggal: ' . date('d F Y', strtotime($per_tanggal));
        $this->pdf->AddPage();

        $data = $this->fetchData($per_tanggal);
        $this->render($data);
    }

    private function fetchData($per_tanggal)
    {
        $sql = "SELECT 
                    a.nomor_anggota, 
                    a.nama_lengkap, 
                    SUM(t.kredit - t.debit) as saldo 
                FROM anggota a 
                LEFT JOIN ksp_transaksi_simpanan t ON a.id = t.anggota_id AND t.tanggal <= ? 
                WHERE a.status = 'aktif' 
                GROUP BY a.id 
                HAVING saldo <> 0 
                ORDER BY a.nama_lengkap ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $per_tanggal);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function render($data): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->SetFillColor(230, 230, 230);
        
        // Header Tabel
        $this->pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
        $this->pdf->Cell(30, 8, 'No. Anggota', 1, 0, 'C', true);
        $this->pdf->Cell(90, 8, 'Nama Anggota', 1, 0, 'L', true);
        $this->pdf->Cell(60, 8, 'Saldo Simpanan', 1, 1, 'R', true);

        $this->pdf->SetFont('Helvetica', '', 9);
        $no = 1;
        $totalSaldo = 0;

        foreach ($data as $row) {
            $this->pdf->Cell(10, 6, $no++, 1, 0, 'C');
            $this->pdf->Cell(30, 6, $row['nomor_anggota'], 1, 0, 'C');
            $this->pdf->Cell(90, 6, $row['nama_lengkap'], 1, 0, 'L');
            $this->pdf->Cell(60, 6, 'Rp ' . number_format($row['saldo'], 0, ',', '.'), 1, 1, 'R');
            $totalSaldo += $row['saldo'];
        }

        // Grand Total
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->Cell(130, 8, 'GRAND TOTAL', 1, 0, 'R', true);
        $this->pdf->Cell(60, 8, 'Rp ' . number_format($totalSaldo, 0, ',', '.'), 1, 1, 'R', true);

        $this->pdf->Ln(10);
        $this->pdf->signature_date = $this->params['per_tanggal'] ?? date('Y-m-d');
        $this->pdf->RenderSignatureBlock();
    }
}
