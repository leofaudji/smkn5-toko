<?php
require_once __DIR__ . '/ReportBuilderInterface.php';

class DetailPinjamanReportBuilder implements ReportBuilderInterface
{
    private $pdf;
    private $conn;
    private $params;
    private $pinjaman;
    private $schedule;

    public function __construct(PDF $pdf, mysqli $conn, array $params)
    {
        $this->pdf = $pdf;
        $this->conn = $conn;
        $this->params = $params;
    }

    private function fetchData()
    {
        $id = $this->params['id'] ?? 0;
        if ($id === 0) {
            throw new Exception("ID Pinjaman tidak valid.");
        }

        // Fetch loan header
        $sql = "SELECT 
                    p.*, 
                    a.nama_lengkap, a.nomor_anggota,
                    j.nama as jenis_pinjaman,
                    pa.detail_json as agunan_detail_json,
                    ta.nama as nama_tipe_agunan
                FROM ksp_pinjaman p 
                JOIN anggota a ON p.anggota_id = a.id 
                JOIN ksp_jenis_pinjaman j ON p.jenis_pinjaman_id = j.id
                LEFT JOIN ksp_pinjaman_agunan pa ON p.id = pa.pinjaman_id
                LEFT JOIN ksp_tipe_agunan ta ON pa.tipe_agunan_id = ta.id
                WHERE p.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $this->pinjaman = $stmt->get_result()->fetch_assoc();

        if (!$this->pinjaman) {
            throw new Exception("Data pinjaman tidak ditemukan.");
        }

        // Fetch loan schedule
        $stmt_sch = $this->conn->prepare("SELECT * FROM ksp_angsuran WHERE pinjaman_id = ? ORDER BY angsuran_ke ASC");
        $stmt_sch->bind_param("i", $id);
        $stmt_sch->execute();
        $this->schedule = $stmt_sch->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function build(): void
    {
        $this->fetchData();

        $this->pdf->SetTitle('Detail Pinjaman - ' . $this->pinjaman['nomor_pinjaman']);
        $this->pdf->report_title = 'DETAIL PINJAMAN';
        $this->pdf->report_period = 'Nomor: ' . $this->pinjaman['nomor_pinjaman'];
        $this->pdf->AddPage('P');

        $this->render();
    }

    private function render(): void
    {
        // Render Loan Details
        $this->pdf->SetFont('Helvetica', 'B', 11);
        $this->pdf->Cell(0, 8, 'Informasi Pinjaman', 0, 1);
        $this->pdf->Ln(2);

        $this->renderDetailRow('Nama Anggota', $this->pinjaman['nama_lengkap'] . ' (' . $this->pinjaman['nomor_anggota'] . ')');
        $this->renderDetailRow('Jumlah Pokok', 'Rp ' . number_format($this->pinjaman['jumlah_pinjaman'], 0, ',', '.'));
        $this->renderDetailRow('Tenor', $this->pinjaman['tenor_bulan'] . ' Bulan');
        $this->renderDetailRow('Bunga', $this->pinjaman['bunga_per_tahun'] . '% per Tahun');
        $this->renderDetailRow('Tanggal Pengajuan', date('d M Y', strtotime($this->pinjaman['tanggal_pengajuan'])));
        $this->renderDetailRow('Status', strtoupper($this->pinjaman['status']));
        
        // Render Agunan
        if ($this->pinjaman['agunan_detail_json']) {
            $agunan_details = json_decode($this->pinjaman['agunan_detail_json'], true);
            $agunan_str = '';
            foreach ($agunan_details as $key => $value) {
                $label = ucwords(str_replace('_', ' ', $key));
                $agunan_str .= $label . ': ' . $value . '; ';
            }
            $this->renderDetailRow('Agunan (' . $this->pinjaman['nama_tipe_agunan'] . ')', rtrim($agunan_str, '; '));
        }
        
        $this->pdf->Ln(8);

        // Render Schedule Table
        $this->pdf->SetFont('Helvetica', 'B', 11);
        $this->pdf->Cell(0, 8, 'Jadwal Angsuran', 0, 1);
        $this->pdf->Ln(2);

        // Table Header
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(15, 7, 'Ke', 1, 0, 'C', true);
        $this->pdf->Cell(35, 7, 'Jatuh Tempo', 1, 0, 'C', true);
        $this->pdf->Cell(35, 7, 'Pokok', 1, 0, 'C', true);
        $this->pdf->Cell(35, 7, 'Bunga', 1, 0, 'C', true);
        $this->pdf->Cell(35, 7, 'Total', 1, 0, 'C', true);
        $this->pdf->Cell(35, 7, 'Status', 1, 1, 'C', true);

        // Table Body
        $this->pdf->SetFont('Helvetica', '', 9);
        foreach ($this->schedule as $row) {
            $this->pdf->Cell(15, 7, $row['angsuran_ke'], 1, 0, 'C');
            $this->pdf->Cell(35, 7, date('d M Y', strtotime($row['tanggal_jatuh_tempo'])), 1, 0, 'L');
            $this->pdf->Cell(35, 7, number_format($row['pokok'], 0, ',', '.'), 1, 0, 'R');
            $this->pdf->Cell(35, 7, number_format($row['bunga'], 0, ',', '.'), 1, 0, 'R');
            $this->pdf->Cell(35, 7, number_format($row['total_angsuran'], 0, ',', '.'), 1, 0, 'R');
            $this->pdf->Cell(35, 7, $row['status'] === 'lunas' ? 'Lunas' : 'Belum Bayar', 1, 1, 'C');
        }

        $this->pdf->Ln(10);
        $this->pdf->signature_date = date('Y-m-d');
        $this->pdf->RenderSignatureBlock();
    }

    private function renderDetailRow($label, $value)
    {
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->Cell(40, 6, $label, 0, 0, 'L');
        $this->pdf->Cell(5, 6, ':', 0, 0, 'C');
        $this->pdf->SetFont('Helvetica', '', 9);
        $this->pdf->MultiCell(0, 6, $value, 0, 'L');
    }
}