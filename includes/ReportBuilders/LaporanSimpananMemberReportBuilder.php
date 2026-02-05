<?php
require_once __DIR__ . '/ReportBuilderInterface.php';

class LaporanSimpananMemberReportBuilder implements ReportBuilderInterface
{
    private $pdf;
    private $conn;
    private $params;

    public function __construct($pdf, $conn, $params)
    {
        $this->pdf = $pdf;
        $this->conn = $conn;
        $this->params = $params;
    }

    public function build(): void
    {
        $anggota_id = $this->params['anggota_id'] ?? 0;
        $start_date = $this->params['start_date'] ?? date('Y-m-01');
        $end_date = $this->params['end_date'] ?? date('Y-m-d');

        // Ambil Data Anggota
        $stmt = $this->conn->prepare("SELECT * FROM anggota WHERE id = ?");
        $stmt->bind_param("i", $anggota_id);
        $stmt->execute();
        $anggota = $stmt->get_result()->fetch_assoc();

        if (!$anggota) {
            die("Anggota tidak ditemukan");
        }

        // Setup PDF
        $this->pdf->SetTitle('Laporan Simpanan Anggota');
        $this->pdf->report_title = 'Laporan Simpanan Anggota';
        $this->pdf->report_period = date('d/m/Y', strtotime($start_date)) . ' s/d ' . date('d/m/Y', strtotime($end_date));
        $this->pdf->AddPage();

        // Info Anggota
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(30, 6, 'Nama', 0, 0);
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(100, 6, ': ' . $anggota['nama_lengkap'], 0, 1);
        
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(30, 6, 'No. Anggota', 0, 0);
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(100, 6, ': ' . $anggota['nomor_anggota'], 0, 1);
        $this->pdf->Ln(5);

        // Header Tabel
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(25, 8, 'Tanggal', 1, 0, 'C', true);
        $this->pdf->Cell(30, 8, 'No. Ref', 1, 0, 'C', true);
        $this->pdf->Cell(55, 8, 'Keterangan', 1, 0, 'C', true);
        $this->pdf->Cell(25, 8, 'Debit', 1, 0, 'R', true);
        $this->pdf->Cell(25, 8, 'Kredit', 1, 0, 'R', true);
        $this->pdf->Cell(30, 8, 'Saldo', 1, 1, 'R', true);

        // Hitung Saldo Awal
        $stmt_awal = $this->conn->prepare("SELECT SUM(kredit - debit) as saldo_awal FROM ksp_transaksi_simpanan WHERE anggota_id = ? AND tanggal < ?");
        $stmt_awal->bind_param("is", $anggota_id, $start_date);
        $stmt_awal->execute();
        $saldo_awal = $stmt_awal->get_result()->fetch_assoc()['saldo_awal'] ?? 0;

        // Baris Saldo Awal
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(110, 8, 'Saldo Awal', 1, 0, 'R');
        $this->pdf->Cell(25, 8, '-', 1, 0, 'R');
        $this->pdf->Cell(25, 8, '-', 1, 0, 'R');
        $this->pdf->Cell(30, 8, number_format($saldo_awal, 0, ',', '.'), 1, 1, 'R');

        // Ambil Transaksi
        $sql = "SELECT t.*, j.nama as jenis_simpanan 
                FROM ksp_transaksi_simpanan t
                JOIN ksp_jenis_simpanan j ON t.jenis_simpanan_id = j.id
                WHERE t.anggota_id = ? AND t.tanggal BETWEEN ? AND ?
                ORDER BY t.tanggal ASC, t.id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $anggota_id, $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $this->pdf->SetFont('Arial', '', 9);
        $current_balance = $saldo_awal;
        $total_debit = 0;
        $total_kredit = 0;

        while ($row = $result->fetch_assoc()) {
            $debit = (float)$row['debit'];
            $kredit = (float)$row['kredit'];
            $current_balance += ($kredit - $debit);
            $total_debit += $debit;
            $total_kredit += $kredit;

            $this->pdf->Cell(25, 7, date('d/m/Y', strtotime($row['tanggal'])), 1, 0, 'C');
            $this->pdf->Cell(30, 7, $row['nomor_referensi'], 1, 0, 'C');
            
            // Keterangan (Jenis + Ket)
            $ket = $row['jenis_simpanan'];
            if (!empty($row['keterangan'])) $ket .= ' - ' . $row['keterangan'];
            // Truncate text if too long
            if (strlen($ket) > 30) $ket = substr($ket, 0, 27) . '...';
            
            $this->pdf->Cell(55, 7, $ket, 1, 0, 'L');
            $this->pdf->Cell(25, 7, $debit > 0 ? number_format($debit, 0, ',', '.') : '-', 1, 0, 'R');
            $this->pdf->Cell(25, 7, $kredit > 0 ? number_format($kredit, 0, ',', '.') : '-', 1, 0, 'R');
            $this->pdf->Cell(30, 7, number_format($current_balance, 0, ',', '.'), 1, 1, 'R');
        }

        // Total
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(110, 8, 'Total Mutasi', 1, 0, 'R');
        $this->pdf->Cell(25, 8, number_format($total_debit, 0, ',', '.'), 1, 0, 'R');
        $this->pdf->Cell(25, 8, number_format($total_kredit, 0, ',', '.'), 1, 0, 'R');
        $this->pdf->Cell(30, 8, number_format($current_balance, 0, ',', '.'), 1, 1, 'R');

        // Tanda Tangan
        $this->pdf->signature_date = date('Y-m-d');
        $this->pdf->RenderSignatureBlock();
    }
}