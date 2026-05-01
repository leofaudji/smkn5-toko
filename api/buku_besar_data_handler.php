<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/BukuBesarDataTrait.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

class BukuBesarDataHandler {
    use BukuBesarDataTrait;
}

try {
    $conn = Database::getInstance()->getConnection();
    $user_id = 1; // Semua user mengakses data yang sama
    $redis = RedisManager::getInstance();
    $account_id = (int)($_GET['account_id'] ?? 0);
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');

    // ── Logika Caching Redis ───────────────────────────────────────
    $cache_key = "report:bukubesar:{$user_id}:{$account_id}:{$start_date}:{$end_date}";
    check_redis_cache($cache_key);

    $handler = new BukuBesarDataHandler();
    $data = $handler->fetchBukuBesarData($conn, $user_id, $account_id, $start_date, $end_date);

    send_json_response($data, $cache_key, 300);

} catch (Exception $e) {
    header('Content-Type: application/json; charset=UTF-8');
    if (ob_get_length()) ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    die();
}