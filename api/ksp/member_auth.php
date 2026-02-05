<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$nomor_anggota = $_POST['nomor_anggota'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($nomor_anggota) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Nomor anggota dan password wajib diisi']);
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT id, nama_lengkap, password, status FROM anggota WHERE nomor_anggota = ?");
$stmt->bind_param("s", $nomor_anggota);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    if ($user['status'] !== 'aktif') {
        echo json_encode(['success' => false, 'message' => 'Akun Anda tidak aktif. Hubungi admin.']);
        exit;
    }

    $_SESSION['member_loggedin'] = true;
    $_SESSION['member_id'] = $user['id'];
    $_SESSION['member_name'] = $user['nama_lengkap'];
    $_SESSION['member_no'] = $nomor_anggota;

    echo json_encode(['success' => true, 'message' => 'Login berhasil']);
} else {
    echo json_encode(['success' => false, 'message' => 'Nomor anggota atau password salah']);
}