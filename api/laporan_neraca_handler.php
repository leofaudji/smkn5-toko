<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';
require_once PROJECT_ROOT . '/includes/Repositories/LaporanRepository.php';

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

    // Logika untuk menampilkan Laba (Rugi) Periode Berjalan
    $period_lock_date = get_setting('period_lock_date', null, $conn);

    // Cari tanggal tutup buku terakhir SEBELUM tanggal laporan untuk menentukan awal periode fiskal.
    $stmt_last_lock = $conn->prepare("SELECT MAX(tanggal) as last_lock FROM jurnal_entries WHERE user_id = ? AND keterangan LIKE 'Jurnal Penutup Periode%' AND tanggal < ?");
    $stmt_last_lock->bind_param('is', $user_id, $per_tanggal);
    $stmt_last_lock->execute();
    $last_lock_before_date = $stmt_last_lock->get_result()->fetch_assoc()['last_lock'];
    $stmt_last_lock->close();

    $fiscal_year_start = $last_lock_before_date
        ? date('Y-m-d', strtotime($last_lock_before_date . ' + 1 day'))
        : date('Y-01-01', strtotime($per_tanggal));

    // Jangan tampilkan "Laba Berjalan" jika kita melihat laporan PADA tanggal tutup buku DAN menyertakan jurnal penutup,
    // karena laba sudah digulung ke Laba Ditahan.
    $is_on_lock_date_with_closing = ($period_lock_date && $per_tanggal == $period_lock_date && $include_closing);

    if (!$is_on_lock_date_with_closing) {
        // Hitung laba rugi dari awal periode fiskal hingga tanggal laporan.
        // Untuk perhitungan ini, JANGAN sertakan jurnal penutup dari periode sebelumnya, karena itu tidak relevan untuk laba periode berjalan.
        $laba_rugi_data = $repo->getLabaRugiData($user_id, $fiscal_year_start, $per_tanggal, false);
        $laba_rugi_berjalan = $laba_rugi_data['summary']['laba_bersih'];

        // Hanya tampilkan jika ada laba/rugi.
        if (abs($laba_rugi_berjalan) > 0.001) {
            $neraca_accounts[] = ['id' => 'laba_rugi_virtual', 'parent_id' => null, 'kode_akun' => '3-9999', 'nama_akun' => 'Laba (Rugi) Periode Berjalan', 'tipe_akun' => 'Ekuitas', 'saldo_akhir' => $laba_rugi_berjalan];
        }
    }

    echo json_encode(['status' => 'success', 'data' => array_values($neraca_accounts)]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}