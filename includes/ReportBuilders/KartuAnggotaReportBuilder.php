<?php
require_once __DIR__ . '/ReportBuilderInterface.php';

class KartuAnggotaReportBuilder implements ReportBuilderInterface
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
        $ids = [];
        if (!empty($this->params['ids'])) {
            if (is_array($this->params['ids'])) {
                $ids = $this->params['ids'];
            } else {
                $ids = explode(',', $this->params['ids']);
            }
        } elseif (!empty($this->params['id'])) {
            $ids[] = $this->params['id'];
        }

        if (empty($ids)) {
            $this->pdf->AddPage();
            $this->pdf->SetFont('Helvetica', 'B', 12);
            $this->pdf->Cell(0, 10, 'Tidak ada anggota yang dipilih', 0, 1, 'C');
            return;
        }

        // Card dimensions (CR-80: 85.6 x 53.98 mm)
        $width = 85.6;
        $height = 54;

        // Disable AutoPageBreak to prevent automatic footer
        $this->pdf->SetAutoPageBreak(false);
        
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id <= 0) continue;

            $stmt = $this->conn->prepare("SELECT * FROM anggota WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $member = $stmt->get_result()->fetch_assoc();

            if (!$member) continue;

            // Add Page with specific size for each card
            $this->pdf->AddPage('L', array($width, $height));

            // Background
            $this->pdf->SetFillColor(255, 255, 255);
            $this->pdf->Rect(0, 0, $width, $height, 'F');

            // Header Strip
            $this->pdf->SetFillColor(59, 130, 246); // Blue-500
            $this->pdf->Rect(0, 0, $width, 12, 'F');

            // Title
            $this->pdf->SetFont('Helvetica', 'B', 10);
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->SetXY(0, 2);
            $this->pdf->Cell($width, 5, 'KARTU ANGGOTA', 0, 1, 'C');

            $this->pdf->SetFont('Helvetica', '', 6);
            $app_name = get_setting('app_name', 'Koperasi Simpan Pinjam');
            $this->pdf->Cell($width, 3, $app_name, 0, 1, 'C');

            // Reset Text Color
            $this->pdf->SetTextColor(0, 0, 0);

            // Member Info
            $this->pdf->SetY(16);
            $this->pdf->SetFont('Helvetica', 'B', 9);
            $this->pdf->Cell(0, 5, $member['nama_lengkap'], 0, 1);

            $this->pdf->SetFont('Helvetica', '', 7);
            $this->pdf->Cell(20, 4, 'No. Anggota', 0, 0);
            $this->pdf->Cell(2, 4, ':', 0, 0);
            $this->pdf->Cell(0, 4, $member['nomor_anggota'], 0, 1);

            $this->pdf->Cell(20, 4, 'Bergabung', 0, 0);
            $this->pdf->Cell(2, 4, ':', 0, 0);
            $this->pdf->Cell(0, 4, date('d-m-Y', strtotime($member['tanggal_daftar'])), 0, 1);
        
            $this->pdf->Cell(20, 4, 'Telepon', 0, 0);
            $this->pdf->Cell(2, 4, ':', 0, 0);
            $this->pdf->Cell(0, 4, $member['no_telepon'] ?? '-', 0, 1);

            // QR Code (Right aligned)
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($member['nomor_anggota']);
            $this->pdf->Image($qrUrl, 62, 18, 20, 20, 'PNG');

            // Footer Strip
            $this->pdf->SetFillColor(240, 240, 240);
            $this->pdf->Rect(0, $height - 5, $width, 5, 'F');
            $this->pdf->SetXY(0, $height - 5);
            $this->pdf->SetFont('Helvetica', 'I', 5);
            $this->pdf->SetTextColor(100, 100, 100);
            $this->pdf->Cell($width, 5, 'Kartu ini sah selama menjadi anggota aktif.', 0, 0, 'C');
        }
        
        $this->pdf->report_title = 'Kartu_Anggota_Batch';
    }
}