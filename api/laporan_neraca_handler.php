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
    $neraca_accounts = $repo->getNeracaDataWithProfitLoss($user_id, $per_tanggal, $include_closing);

    echo json_encode(['status' => 'success', 'data' => array_values($neraca_accounts)]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}