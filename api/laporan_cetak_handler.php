<?php
// Pastikan sesi dimulai di awal, karena file ini dipanggil di tab baru
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/bootstrap.php';
require_once PROJECT_ROOT . '/includes/PDF.php';
// Muat semua kelas builder
require_once PROJECT_ROOT . '/includes/ReportBuilders/ReportBuilderInterface.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/NeracaReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/LabaRugiReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/ArusKasReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/LaporanHarianReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/BukuBesarReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/DaftarJurnalReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/LaporanLabaDitahanReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/AnggaranReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/PertumbuhanLabaReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/AnalisisRasioReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/BukuPanduanReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/RekonsiliasiReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/KonsinyasiReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/TrialBalanceReportBuilder.php'; // Tambahkan ini
require_once PROJECT_ROOT . '/includes/ReportBuilders/KonsinyasiSisaUtangReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/AsetTetapReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/StrukPenjualanReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/LaporanPenjualanItemReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/LaporanPenjualanReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/DetailPinjamanReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/StrukAngsuranReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/StrukSimpananReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/LaporanNominatifSimpananReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/LaporanNominatifPinjamanReportBuilder.php';

$conn = Database::getInstance()->getConnection();

// --- Parameter Handling (Robustly handle GET, POST, and JSON POST) ---
$params = $_GET; // Start with GET params as a base

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_params = $_POST;
    // If $_POST is empty, it might be a JSON request body
    if (empty($post_params)) {
        $json_body = json_decode(file_get_contents('php://input'), true);
        if (is_array($json_body)) {
            $post_params = $json_body;
        }
    }
    // Merge POST params, which will overwrite any GET params with the same name
    $params = array_merge($params, $post_params);
}

$report_type = $params['report'] ?? '';
// --- End Parameter Handling ---

// Ambil nama perumahan dari settings untuk header PDF
global $housing_name;
$housing_name = get_setting('app_name', 'Aplikasi Keuangan');

function format_currency_pdf($number) {
    // Handle non-numeric values gracefully to prevent errors
    if (!is_numeric($number)) {
        return 'Rp 0';
    }
    $is_negative = $number < 0;
    $formatted_number = 'Rp ' . number_format(abs($number), 0, ',', '.');
    return $is_negative ? '(' . $formatted_number . ')' : $formatted_number;
}

$pdf = new PDF();
$pdf->AliasNbPages(); // Penting untuk mengetahui total halaman

$params['user_id'] = 1; // ID Pemilik Data (Toko)

// Peta dari tipe laporan ke kelas builder-nya
$builder_map = [
    'neraca' => NeracaReportBuilder::class,
    'laba-rugi' => LabaRugiReportBuilder::class,
    'arus-kas' => ArusKasReportBuilder::class,
    'laporan-harian' => LaporanHarianReportBuilder::class,
    'buku-besar' => BukuBesarReportBuilder::class,
    'daftar-jurnal' => DaftarJurnalReportBuilder::class,
    'laporan-laba-ditahan' => LaporanLabaDitahanReportBuilder::class,
    'anggaran' => AnggaranReportBuilder::class,
    'laporan-pertumbuhan-laba' => PertumbuhanLabaReportBuilder::class,
    'struk-penjualan' => StrukPenjualanReportBuilder::class,
    'analisis-rasio' => AnalisisRasioReportBuilder::class,
    'laporan-penjualan-item' => LaporanPenjualanItemReportBuilder::class,
    'laporan-penjualan' => LaporanPenjualanReportBuilder::class,
    'buku-panduan' => BukuPanduanReportBuilder::class,
    'rekonsiliasi' => RekonsiliasiReportBuilder::class,
    'konsinyasi' => KonsinyasiReportBuilder::class,
    'trial-balance' => TrialBalanceReportBuilder::class, // Tambahkan ini
    'konsinyasi-sisa-utang' => KonsinyasiSisaUtangReportBuilder::class,
    'aset-tetap' => AsetTetapReportBuilder::class,
    'detail_pinjaman' => DetailPinjamanReportBuilder::class,
    'struk_angsuran' => StrukAngsuranReportBuilder::class,
    'struk_simpanan' => StrukSimpananReportBuilder::class,
    'nominatif_simpanan' => LaporanNominatifSimpananReportBuilder::class,
    'nominatif_pinjaman' => LaporanNominatifPinjamanReportBuilder::class,
];

try {
    if (isset($builder_map[$report_type])) {
        $builder_class = $builder_map[$report_type];
        
        // Buat instance builder dengan dependensi yang diperlukan
        $builder = new $builder_class($pdf, $conn, $params);
        
        // Jalankan proses build
        $builder->build();

    } else {
        // Jika tipe laporan tidak dikenal
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 10, 'Error: Tipe Laporan Tidak Dikenal', 0, 1, 'C');
    }
} catch (Exception $e) {
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(0, 10, 'Terjadi Kesalahan', 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->MultiCell(0, 6, 'Error: ' . $e->getMessage());
}

$pdf->Output('I', str_replace(' ', '_', $pdf->report_title) . '.pdf');
?>