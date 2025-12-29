<?php
// bootstrap.php sudah di-require oleh index.php, jadi kita tidak perlu me-require-nya lagi.
// session_start() juga sudah dipanggil di index.php.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('/login'));
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = 'Username dan password tidak boleh kosong.';
    header('Location: ' . base_url('/login'));
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();
    // MODIFIED: Join dengan tabel roles untuk mendapatkan nama role yang sebenarnya
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.password, u.nama_lengkap, u.role_id, r.name as role_name 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id 
        WHERE u.username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        // Login berhasil
        session_regenerate_id(true); // Mencegah session fixation
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'] ?? 'user';
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'] ?? 'Pengguna';
        // MODIFIED: Gunakan role_name dari tabel roles, bukan kolom 'role' yang lama
        // Pastikan lowercase agar sesuai dengan pengecekan di menu (misal: 'kasir')
        $_SESSION['role'] = strtolower($user['role_name'] ?? 'user');

        // Handle "Remember Me"
        if (!empty($_POST['remember_me'])) {
            $selector = bin2hex(random_bytes(16));
            $validator = bin2hex(random_bytes(32));
            $validator_hash = hash('sha256', $validator);
            $expires = time() + (86400 * 30); // 30 hari

            // Simpan selector dan hash validator ke database
            $stmt_remember = $conn->prepare("UPDATE users SET remember_selector = ?, remember_validator_hash = ? WHERE id = ?");
            $stmt_remember->bind_param("ssi", $selector, $validator_hash, $user['id']);
            $stmt_remember->execute();
            $stmt_remember->close();

            // Set cookie di browser
            setcookie(
                'remember_me',
                $selector . ':' . $validator,
                $expires,
                BASE_PATH . '/', // Path cookie
                "", // Domain
                isset($_SERVER['HTTPS']), // Secure
                true // HttpOnly
            );
        }

        // Ambil role_id user
        $role_id = $user['role_id']; 

        // Query untuk mengambil semua slug permission dari role tersebut
        $stmt_perms = $conn->prepare("
            SELECT p.slug 
            FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            WHERE rp.role_id = ?
        ");
        $stmt_perms->bind_param('i', $role_id);
        $stmt_perms->execute();
        $result = $stmt_perms->get_result();

        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row['slug'];
        }
        $stmt_perms->close();
        
        // Simpan daftar slug permission ke dalam session
        $_SESSION['permissions'] = $permissions;

        // Debugging: Catat role dan permissions yang dimuat ke log error
        error_log("Login Success - User: " . $user['username']);
        error_log("Role (Session): " . $_SESSION['role']);
        error_log("Permissions loaded: " . implode(', ', $permissions));

        log_activity($user['username'], 'Login', 'Login berhasil.');

        // Redirect ke halaman dashboard yang sebenarnya
        header('Location: ' . base_url('/dashboard'));
        exit;
    } else {
        // Jika semua upaya login gagal
        $_SESSION['login_error'] = 'Username atau password salah.';
        log_activity($username, 'Login Gagal', 'Percobaan login gagal.');
        header('Location: ' . base_url('/login'));
        exit;
    }

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $_SESSION['login_error'] = 'Terjadi kesalahan pada sistem. Silakan coba lagi.';
    header('Location: ' . base_url('/login'));
    exit;
}