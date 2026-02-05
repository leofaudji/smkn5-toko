<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Hitung Pengajuan Pinjaman Pending
    $stmt_pinjaman = $db->prepare("SELECT COUNT(*) as total FROM ksp_pinjaman WHERE status = 'pending'");
    $stmt_pinjaman->execute();
    $pending_pinjaman = $stmt_pinjaman->get_result()->fetch_assoc()['total'];
    $stmt_pinjaman->close();

    // Hitung Pengajuan Penarikan Pending
    $stmt_penarikan = $db->prepare("SELECT COUNT(*) as total FROM ksp_penarikan_simpanan WHERE status = 'pending'");
    $stmt_penarikan->execute();
    $pending_penarikan = $stmt_penarikan->get_result()->fetch_assoc()['total'];
    $stmt_penarikan->close();

    echo json_encode([
        'success' => true,
        'data' => [
            'pinjaman' => (int)$pending_pinjaman,
            'penarikan' => (int)$pending_penarikan
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}