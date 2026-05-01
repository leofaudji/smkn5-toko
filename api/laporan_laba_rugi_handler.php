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

$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-t');
$is_comparison = isset($_GET['compare']) && $_GET['compare'] === 'true';
$is_common_size = isset($_GET['common_size']) && $_GET['common_size'] === 'true';
$include_closing = isset($_GET['include_closing']) && $_GET['include_closing'] === 'true';
$start_date2 = $_GET['start2'] ?? '';
$end_date2 = $_GET['end2'] ?? '';

// ── Logika Caching Redis ───────────────────────────────────────
$cache_key = "report:labarugi:{$user_id}:{$start_date}:{$end_date}:" . ($is_comparison ? "1:{$start_date2}:{$end_date2}" : "0") . ":" . ($is_common_size ? '1' : '0') . ":" . ($include_closing ? '1' : '0');

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
    $repo = new LaporanRepository($conn);

    // Ambil data untuk periode utama
    $current_data = $repo->getLabaRugiData($user_id, $start_date, $end_date, $include_closing);

    // Fungsi untuk menghitung persentase common-size
    $calculate_percentages = function(&$data) {
        $total_pendapatan = $data['summary']['total_pendapatan'] ?? 0;
        if ($total_pendapatan != 0) {
            foreach ($data['pendapatan'] as &$item) {
                $item['percentage'] = ($item['total'] / $total_pendapatan) * 100;
            }
            foreach ($data['beban'] as &$item) {
                $item['percentage'] = ($item['total'] / $total_pendapatan) * 100;
            }
            $data['summary']['total_beban_percentage'] = ($data['summary']['total_beban'] / $total_pendapatan) * 100;
            $data['summary']['laba_bersih_percentage'] = ($data['summary']['laba_bersih'] / $total_pendapatan) * 100;
        }
    };

    $response_data = ['current' => $current_data];

    if ($is_comparison) {
        $previous_data = $repo->getLabaRugiData($user_id, $start_date2, $end_date2, $include_closing);
        $response_data['previous'] = $previous_data;
    }

    if ($is_common_size) {
        $calculate_percentages($response_data['current']);
        if ($is_comparison && isset($response_data['previous'])) $calculate_percentages($response_data['previous']);
    }

    // Simpan ke cache selama 5 menit
    if ($redis->isAvailable()) {
        $redis->set($cache_key, $response_data, 300);
    }

    header('Content-Type: application/json; charset=UTF-8');
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'success', 'data' => $response_data, 'cached' => false], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    die();

} catch (Exception $e) {
    header('Content-Type: application/json; charset=UTF-8');
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    die();
}