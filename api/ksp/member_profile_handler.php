<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isset($_SESSION['member_loggedin']) || $_SESSION['member_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$member_id = $_SESSION['member_id'];
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'Semua kolom wajib diisi.']);
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Konfirmasi password tidak cocok.']);
        exit;
    }

    $stmt = $db->prepare("SELECT password FROM anggota WHERE id = ?");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($current_password, $user['password'])) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_update = $db->prepare("UPDATE anggota SET password = ? WHERE id = ?");
        $stmt_update->bind_param("si", $hashed_password, $member_id);
        $stmt_update->execute();
        echo json_encode(['success' => true, 'message' => 'Password berhasil diubah.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Password saat ini salah.']);
    }
}