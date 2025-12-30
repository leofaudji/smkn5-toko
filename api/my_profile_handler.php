<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

try {
    // Validasi input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        throw new Exception('Semua field wajib diisi.');
    }
    if ($new_password !== $confirm_password) {
        throw new Exception('Password baru dan konfirmasi password tidak cocok.');
    }
    if (strlen($new_password) < 6) {
        throw new Exception('Password baru minimal harus 6 karakter.');
    }

    // 1. Ambil hash password saat ini dari database
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        throw new Exception('User tidak ditemukan.');
    }
    $current_password_hash = $result['password'];

    // 2. Verifikasi password saat ini
    if (!password_verify($current_password, $current_password_hash)) {
        throw new Exception('Password saat ini salah.');
    }

    // 3. Hash password baru dan update ke database
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt_update->bind_param('si', $new_password_hash, $user_id);
    $stmt_update->execute();
    $stmt_update->close();

    log_activity($username, 'Ganti Password', 'Pengguna berhasil mengganti password.');

    // --- Invalidate Session ---
    // Hapus semua variabel sesi.
    $_SESSION = [];

    // Hancurkan sesi dan cookie.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    // Hapus juga cookie "remember_me"
    setcookie('remember_me', '', time() - 42000, BASE_PATH . '/');
    // --- End Invalidate Session ---

    echo json_encode(['status' => 'success', 'message' => 'Password berhasil diperbarui. Anda akan dialihkan ke halaman login.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>