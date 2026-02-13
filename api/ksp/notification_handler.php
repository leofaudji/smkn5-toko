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
    
    // Get Pinjaman Details
    $stmt_pinjaman_det = $db->prepare("SELECT p.nomor_pinjaman, a.nama_lengkap, p.tanggal_pengajuan FROM ksp_pinjaman p JOIN anggota a ON p.anggota_id = a.id WHERE p.status = 'pending' ORDER BY p.tanggal_pengajuan DESC LIMIT 5");
    $stmt_pinjaman_det->execute();
    $details_pinjaman = $stmt_pinjaman_det->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_pinjaman_det->close();

    // Hitung Pengajuan Penarikan Pending
    $stmt_penarikan = $db->prepare("SELECT COUNT(*) as total FROM ksp_penarikan_simpanan WHERE status = 'pending'");
    $stmt_penarikan->execute();
    $pending_penarikan = $stmt_penarikan->get_result()->fetch_assoc()['total'];
    $stmt_penarikan->close();
    
    // Get Penarikan Details
    $stmt_penarikan_det = $db->prepare("SELECT p.id, a.nama_lengkap, p.jumlah, p.tanggal_pengajuan FROM ksp_penarikan_simpanan p JOIN anggota a ON p.anggota_id = a.id WHERE p.status = 'pending' ORDER BY p.tanggal_pengajuan DESC LIMIT 5");
    $stmt_penarikan_det->execute();
    $details_penarikan = $stmt_penarikan_det->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_penarikan_det->close();

    echo json_encode([
        'success' => true,
        'data' => [
            'pinjaman' => (int)$pending_pinjaman,
            'pinjaman_details' => $details_pinjaman,
            'penarikan' => (int)$pending_penarikan,
            'penarikan_details' => $details_penarikan
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}