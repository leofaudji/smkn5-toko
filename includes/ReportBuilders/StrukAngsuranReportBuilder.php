<?php
require_once __DIR__ . '/ReportBuilderInterface.php';

class StrukAngsuranReportBuilder implements ReportBuilderInterface
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
        $ref = $this->params['payment_ref'] ?? '';
        if (empty($ref)) {
            throw new Exception("Referensi pembayaran tidak valid.");
        }

        // Ambil data pembayaran dari GL dan Anggota
        // Kita ambil total debit pada akun kas untuk referensi ini sebagai total bayar
        $sql = "SELECT 
                    gl.tanggal, 
                    gl.nomor_referensi,
                    SUM(gl.debit) as total_bayar,
                    p.nomor_pinjaman,
                    a.nama_lengkap,
                    a.nomor_anggota
                FROM general_ledger gl
                JOIN ksp_angsuran ang ON gl.ref_id = ang.id
                JOIN ksp_pinjaman p ON ang.pinjaman_id = p.id
                JOIN anggota a ON p.anggota_id = a.id
                JOIN accounts acc ON gl.account_id = acc.id
                WHERE gl.nomor_referensi = ? 
                  AND gl.ref_type = 'transaksi' 
                  AND acc.is_kas = 1
                GROUP BY gl.nomor_referensi";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $ref);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        if (!$data) {
            throw new Exception("Data pembayaran tidak ditemukan.");
        }

        $this->pdf->report_title = 'Struk Pembayaran';
        // Ukuran kertas 58mm, tinggi menyesuaikan (misal 100mm atau lebih)
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
        $this->pdf->Cell(0, 4, 'Bukti Pembayaran Angsuran', 0, 1, 'C');
        $this->pdf->Ln(2);

        $this->pdf->SetFont('Helvetica', '', 8);
        
        // Info Transaksi
        $this->pdf->Cell(15, 4, 'No. Ref', 0, 0);
        $this->pdf->Cell(2, 4, ':', 0, 0);
        $this->pdf->MultiCell(0, 4, $data['nomor_referensi'], 0, 'L');
        
        $this->pdf->Cell(15, 4, 'Tanggal', 0, 0);
        $this->pdf->Cell(2, 4, ':', 0, 0);
        $this->pdf->Cell(0, 4, date('d/m/Y', strtotime($data['tanggal'])), 0, 1);

        $this->pdf->Cell(15, 4, 'Anggota', 0, 0);
        $this->pdf->Cell(2, 4, ':', 0, 0);
        $this->pdf->MultiCell(0, 4, $data['nama_lengkap'], 0, 'L');

        $this->pdf->Cell(15, 4, 'No. Pinj', 0, 0);
        $this->pdf->Cell(2, 4, ':', 0, 0);
        $this->pdf->Cell(0, 4, $data['nomor_pinjaman'], 0, 1);

        $this->pdf->Ln(2);
        $this->pdf->Cell(0, 0, '', 1, 1); // Garis
        $this->pdf->Ln(2);

        // Detail Pembayaran
        $this->pdf->SetFont('Helvetica', 'B', 10);
        $this->pdf->Cell(20, 6, 'Total Bayar', 0, 0);
        $this->pdf->Cell(0, 6, 'Rp ' . number_format($data['total_bayar'], 0, ',', '.'), 0, 1, 'R');

        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Ln(2);
        $this->pdf->Cell(0, 0, '', 1, 1); // Garis
        $this->pdf->Ln(4);

        $this->pdf->Cell(0, 4, 'Terima Kasih', 0, 1, 'C');
        $this->pdf->Cell(0, 4, 'Simpan struk ini sebagai bukti sah.', 0, 1, 'C');
    }
}