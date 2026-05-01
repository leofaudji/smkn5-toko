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
$redis = RedisManager::getInstance();

$user_id = 1; // ID Pemilik Data (Toko)
$per_tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$include_closing = isset($_GET['include_closing']) && $_GET['include_closing'] === 'true';

// ── Logika Caching Redis ───────────────────────────────────────
$cache_key = "report:neraca:{$user_id}:{$per_tanggal}:" . ($include_closing ? '1' : '0');
check_redis_cache($cache_key);

try {
    // Gunakan Repository untuk konsistensi data dengan PDF
    $repo = new LaporanRepository($conn);
    $neraca_accounts = $repo->getNeracaDataWithProfitLoss($user_id, $per_tanggal, $include_closing);
    $final_data = array_values($neraca_accounts);

    send_json_response($final_data, $cache_key, 300);

} catch (Exception $e) {
    header('Content-Type: application/json; charset=UTF-8');
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    die();
}