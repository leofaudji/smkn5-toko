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

if ($redis->isAvailable()) {
    $cached_data = $redis->get($cache_key);
    if ($cached_data) {
        header('Content-Type: application/json; charset=UTF-8');
        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'success', 'data' => $cached_data, 'cached' => true], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        die();
    }
}

try {
    // Gunakan Repository untuk konsistensi data dengan PDF
    $repo = new LaporanRepository($conn);
    $neraca_accounts = $repo->getNeracaDataWithProfitLoss($user_id, $per_tanggal, $include_closing);
    $final_data = array_values($neraca_accounts);

    // Simpan ke cache selama 5 menit (300 detik)
    if ($redis->isAvailable()) {
        $redis->set($cache_key, $final_data, 300);
    }

    header('Content-Type: application/json; charset=UTF-8');
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'success', 'data' => $final_data, 'cached' => false], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    die();

} catch (Exception $e) {
    header('Content-Type: application/json; charset=UTF-8');
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    die();
}