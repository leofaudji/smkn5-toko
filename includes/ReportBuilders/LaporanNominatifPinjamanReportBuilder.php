<?php
require_once __DIR__ . '/ReportBuilderInterface.php';

class LaporanNominatifPinjamanReportBuilder implements ReportBuilderInterface
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

        $this->pdf->SetTitle('Laporan Nominatif Pinjaman');
        $this->pdf->report_title = 'LAPORAN NOMINATIF PINJAMAN (BAKIDEBET)';
        $this->pdf->report_period = 'Per Tanggal: ' . date('d F Y', strtotime($per_tanggal));
        $this->pdf->AddPage('L'); // Landscape

        $data = $this->fetchData($per_tanggal);
        $this->render($data);
    }

    private function fetchData($per_tanggal)
    {
        // Menghitung sisa pokok berdasarkan pembayaran yang terjadi <= per_tanggal
        $sql = "SELECT 
                    a.nomor_anggota, 
                    a.nama_lengkap, 
                    p.nomor_pinjaman, 
                    p.jumlah_pinjaman as plafon,
                    p.tanggal_pencairan,
                    (p.jumlah_pinjaman - COALESCE((
                        SELECT SUM(pokok_terbayar) 
                        FROM ksp_angsuran 
                        WHERE pinjaman_id = p.id AND (tanggal_bayar IS NOT NULL AND tanggal_bayar <= ?)
                    ), 0)) as sisa_pokok,
                    (SELECT COUNT(*) 
                     FROM ksp_angsuran 
                     WHERE pinjaman_id = p.id 
                       AND status = 'belum_bayar' 
                       AND tanggal_jatuh_tempo < ?) as jumlah_tunggakan,
                    ta.nama as nama_tipe_agunan,
                    pa.detail_json as agunan_detail_json
                FROM ksp_pinjaman p 
                JOIN anggota a ON p.anggota_id = a.id 
                LEFT JOIN ksp_pinjaman_agunan pa ON p.id = pa.pinjaman_id
                LEFT JOIN ksp_tipe_agunan ta ON pa.tipe_agunan_id = ta.id
                WHERE p.status IN ('aktif', 'lunas') AND p.tanggal_pencairan <= ?
                HAVING sisa_pokok > 0
                ORDER BY a.nama_lengkap ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $per_tanggal, $per_tanggal, $per_tanggal);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function render($data): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        
        // Header Tabel
        $this->pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
        $this->pdf->Cell(25, 8, 'No. Anggota', 1, 0, 'C', true);
        $this->pdf->Cell(50, 8, 'Nama Anggota', 1, 0, 'L', true);
        $this->pdf->Cell(30, 8, 'No. Pinjaman', 1, 0, 'C', true);
        $this->pdf->Cell(25, 8, 'Tgl Cair', 1, 0, 'C', true);
        $this->pdf->Cell(30, 8, 'Plafon', 1, 0, 'R', true);
        $this->pdf->Cell(30, 8, 'Bakidebet', 1, 0, 'R', true);
        $this->pdf->Cell(20, 8, 'Kolek.', 1, 1, 'C', true);
        $this->pdf->Cell(55, 8, 'Data Agunan', 1, 1, 'L', true);

        $this->pdf->SetFont('Helvetica', '', 8);
        $no = 1;
        $totalPlafon = 0;
        $totalBakidebet = 0;

        foreach ($data as $row) {
            $kolektibilitas = $row['jumlah_tunggakan'] > 0 ? 'Macet' : 'Lancar';
            
            // Format Data Agunan
            $agunan_text = '-';
            if (!empty($row['nama_tipe_agunan'])) {
                $agunan_text = $row['nama_tipe_agunan'];
                if (!empty($row['agunan_detail_json'])) {
                    $details = json_decode($row['agunan_detail_json'], true);
                    if (is_array($details)) {
                        // Ambil nilai pertama sebagai info tambahan (misal: Nopol atau No Sertifikat)
                        $first_value = reset($details);
                        if ($first_value) {
                            $agunan_text .= ' (' . $first_value . ')';
                        }
                    }
                }
            }

            $this->pdf->Cell(10, 6, $no++, 1, 0, 'C');
            $this->pdf->Cell(25, 6, $row['nomor_anggota'], 1, 0, 'C');
            // Potong nama jika terlalu panjang agar tidak merusak layout
            $this->pdf->Cell(50, 6, substr($row['nama_lengkap'], 0, 25), 1, 0, 'L');
            $this->pdf->Cell(30, 6, $row['nomor_pinjaman'], 1, 0, 'C');
            $this->pdf->Cell(25, 6, date('d/m/Y', strtotime($row['tanggal_pencairan'])), 1, 0, 'C');
            $this->pdf->Cell(30, 6, number_format($row['plafon'], 0, ',', '.'), 1, 0, 'R');
            $this->pdf->Cell(30, 6, number_format($row['sisa_pokok'], 0, ',', '.'), 1, 0, 'R');
            $this->pdf->Cell(20, 6, $kolektibilitas, 1, 1, 'C');
            $this->pdf->Cell(55, 6, substr($agunan_text, 0, 35), 1, 1, 'L');
            
            $totalPlafon += $row['plafon'];
            $totalBakidebet += $row['sisa_pokok'];
        }

        // Grand Total
        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->Cell(140, 8, 'GRAND TOTAL', 1, 0, 'R', true);
        $this->pdf->Cell(30, 8, number_format($totalPlafon, 0, ',', '.'), 1, 0, 'R', true);
        $this->pdf->Cell(30, 8, number_format($totalBakidebet, 0, ',', '.'), 1, 0, 'R', true);
        $this->pdf->Cell(75, 8, '', 1, 1, 'C', true); // Empty cell for alignment

        $this->pdf->Ln(10);
        $this->pdf->signature_date = $this->params['per_tanggal'] ?? date('Y-m-d');
        $this->pdf->RenderSignatureBlock();
    }
}
