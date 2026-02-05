<?php
require_once __DIR__ . '/ReportBuilderInterface.php';

class StrukSimpananReportBuilder implements ReportBuilderInterface
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
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            throw new Exception("ID Transaksi tidak valid.");
        }

        $sql = "SELECT 
                    t.tanggal,
                    t.nomor_referensi,
                    t.jenis_transaksi,
                    t.jumlah,
                    t.keterangan,
                    a.nama_lengkap,
                    a.nomor_anggota,
                    js.nama as jenis_simpanan
                FROM ksp_transaksi_simpanan t
                JOIN anggota a ON t.anggota_id = a.id
                JOIN ksp_jenis_simpanan js ON t.jenis_simpanan_id = js.id
                WHERE t.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        if (!$data) {
            throw new Exception("Data transaksi tidak ditemukan.");
        }

        $this->pdf->report_title = 'Struk Simpanan';
        // Ukuran kertas 58mm, tinggi menyesuaikan
        $this->pdf->AddPage('P', [58, 120]); 
        $this->pdf->SetMargins(2, 5, 2);
        $this->pdf->SetAutoPageBreak(false);

        $this->render($data);
    }

    private function render($data): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 10);
        $this->pdf->Cell(0, 5, get_setting('app_name', 'Koperasi'), 0, 1, 'C');
        $this->pdf->SetFont('Helvetica', '', 7);
        $this->pdf->Cell(0, 4, 'Bukti Transaksi Simpanan', 0, 1, 'C');
        $this->pdf->Ln(2);

        $this->pdf->SetFont('Helvetica', '', 8);
        
        $this->pdf->Cell(15, 4, 'No. Ref', 0, 0);
        $this->pdf->Cell(2, 4, ':', 0, 0);
        $this->pdf->MultiCell(0, 4, $data['nomor_referensi'], 0, 'L');
        
        $this->pdf->Cell(15, 4, 'Tanggal', 0, 0);
        $this->pdf->Cell(2, 4, ':', 0, 0);
        $this->pdf->Cell(0, 4, date('d/m/Y', strtotime($data['tanggal'])), 0, 1);

        $this->pdf->Cell(15, 4, 'Anggota', 0, 0);
        $this->pdf->Cell(2, 4, ':', 0, 0);
        $this->pdf->MultiCell(0, 4, $data['nama_lengkap'], 0, 'L');

        $this->pdf->Cell(15, 4, 'Jenis', 0, 0);
        $this->pdf->Cell(2, 4, ':', 0, 0);
        $this->pdf->MultiCell(0, 4, $data['jenis_simpanan'], 0, 'L');

        $this->pdf->Cell(15, 4, 'Tipe', 0, 0);
        $this->pdf->Cell(2, 4, ':', 0, 0);
        $this->pdf->Cell(0, 4, ucfirst($data['jenis_transaksi']), 0, 1);

        $this->pdf->Ln(2);
        $this->pdf->Cell(0, 0, '', 1, 1); // Garis
        $this->pdf->Ln(2);

        $this->pdf->SetFont('Helvetica', 'B', 10);
        $this->pdf->Cell(20, 6, 'Jumlah', 0, 0);
        $this->pdf->Cell(0, 6, 'Rp ' . number_format($data['jumlah'], 0, ',', '.'), 0, 1, 'R');

        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Ln(2);
        $this->pdf->Cell(0, 0, '', 1, 1); // Garis
        $this->pdf->Ln(4);

        $this->pdf->Cell(0, 4, 'Terima Kasih', 0, 1, 'C');
        $this->pdf->Cell(0, 4, 'Simpan struk ini sebagai bukti sah.', 0, 1, 'C');
    }
}