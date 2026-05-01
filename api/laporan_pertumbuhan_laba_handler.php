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
$user_id = 1; // ID Pemilik Data (Toko)

$tahun = (int)($_GET['tahun'] ?? date('Y'));
$view_mode = $_GET['view_mode'] ?? 'monthly';
$compare = isset($_GET['compare']) && $_GET['compare'] === 'true';

try {
    $cache_key = "report:growth:profit:{$user_id}:{$tahun}:{$view_mode}:" . ($compare ? '1' : '0');
    check_redis_cache($cache_key);

    $repo = new LaporanRepository($conn);
    $result = $repo->getPertumbuhanLabaData($user_id, $tahun, $view_mode, $compare);

    $report_data = [];
    $laba_bersih_sebelumnya = 0;

    foreach ($result as $row) {
        $laba_bersih = (float)$row['laba_bersih'];

        $pertumbuhan = 0;
        if ($laba_bersih_sebelumnya != 0) {
            $pertumbuhan = (($laba_bersih - $laba_bersih_sebelumnya) / abs($laba_bersih_sebelumnya)) * 100;
        } elseif ($laba_bersih != 0) {
            $pertumbuhan = 100.0;
        }

        $pertumbuhan_yoy = 0;
        if ($row['laba_bersih_lalu'] != 0) {
            $pertumbuhan_yoy = (($laba_bersih - (float)$row['laba_bersih_lalu']) / abs((float)$row['laba_bersih_lalu'])) * 100;
        }

        $row['pertumbuhan'] = $pertumbuhan;
        $row['pertumbuhan_yoy'] = $pertumbuhan_yoy;
        $report_data[] = $row;

        $laba_bersih_sebelumnya = $laba_bersih;
    }

    send_json_response($report_data, $cache_key, 300);
} catch (Exception $e) {
    send_error_response($e->getMessage(), 500);
}
?>