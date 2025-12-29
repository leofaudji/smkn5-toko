<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';
require_once PROJECT_ROOT . '/includes/Repositories/LaporanRepository.php';

// ================== KODE DEBUGGING SEMENTARA ==================
// Tulis isi dari session ke file log untuk diperiksa.
error_log("Isi Session di laporan_neraca_handler: " . print_r($_SESSION, true));
// =============================================================

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
// Ambil user_id dari session yang sudah login.
// Pastikan Anda menyimpan 'user_id' di dalam $_SESSION saat proses login.
$user_id = 1; // ID Pemilik Data (Toko)

$per_tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$include_closing = isset($_GET['include_closing']) && $_GET['include_closing'] === 'true';

try {
    // Gunakan Repository untuk konsistensi data dengan PDF
    $repo = new LaporanRepository($conn);
    $neraca_accounts = $repo->getNeracaData($user_id, $per_tanggal, $include_closing);

    // Logika untuk menampilkan Laba (Rugi) Periode Berjalan.
    // Ini hanya ditampilkan untuk periode yang belum ditutup.
    $period_lock_date = get_setting('period_lock_date', null, $conn);

    // Tentukan awal tahun fiskal. Jika ada tanggal tutup buku, tahun fiskal dimulai sehari setelahnya.
    // Jika tidak, dimulai dari 1 Januari tahun laporan.
    $fiscal_year_start = $period_lock_date 
        ? date('Y-m-d', strtotime($period_lock_date . ' +1 day'))
        : date('Y-01-01', strtotime($per_tanggal));

    // Hanya hitung laba berjalan jika tanggal laporan berada dalam tahun fiskal yang sedang berjalan.
    // Ini akan menampilkan laba/rugi untuk tahun baru yang belum ditutup.
    if ($per_tanggal >= $fiscal_year_start) {
        // Periode laba rugi dihitung dari awal tahun fiskal hingga tanggal neraca.
        $start_of_year = $fiscal_year_start;
        $laba_rugi_data = $repo->getLabaRugiData($user_id, $start_of_year, $per_tanggal, $include_closing);
        $laba_rugi_berjalan = $laba_rugi_data['summary']['laba_bersih'];

        // Tambahkan akun virtual untuk laba rugi berjalan ke dalam data neraca
        $neraca_accounts[] = [
            'id' => 'laba_rugi_virtual', 'parent_id' => null, 'kode_akun' => '3-9999', 'nama_akun' => 'Laba (Rugi) Periode Berjalan', 'tipe_akun' => 'Ekuitas', 'saldo_akhir' => $laba_rugi_berjalan
        ];
    }

    echo json_encode(['status' => 'success', 'data' => array_values($neraca_accounts)]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}