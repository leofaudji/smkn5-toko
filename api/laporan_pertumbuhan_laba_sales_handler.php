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

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    $cache_key = "report:growth:sales:{$user_id}:{$start_date}:{$end_date}";
    check_redis_cache($cache_key);

    $repo = new LaporanRepository($conn);
    $report_data = $repo->getDailySalesProfitGrowth($user_id, $start_date, $end_date);

    send_json_response([
        'data' => $report_data,
        'period' => [
            'start' => $start_date,
            'end' => $end_date
        ]
    ], $cache_key, 300);
} catch (Exception $e) {
    send_error_response($e->getMessage(), 500);
}
?>
