<?php


// Use the main bootstrap file for consistency with other API endpoints.
require_once __DIR__ . '/../includes/bootstrap.php';

// Define TMP_PATH for temporary file storage if it's not already defined.
if (!defined('TMP_PATH')) {
    define('TMP_PATH', sys_get_temp_dir());
}

// Hanya admin yang boleh mengakses
// bootstrap.php starts the session. We just need to check for login status and role.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit;
}

$action = $_REQUEST['action'] ?? null;

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'backup':
            handle_backup();
            break;
        case 'restore':
            handle_restore();
            break;
        default:
            throw new Exception('Aksi tidak valid.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function handle_backup() {
    // Get DB credentials from the Config class, which is loaded by bootstrap.php
    $db_host = Config::get('DB_SERVER');
    $db_user = Config::get('DB_USERNAME');
    $db_pass = Config::get('DB_PASSWORD');
    $db_name = Config::get('DB_NAME');

    // Path ke mysqldump. Sesuaikan jika perlu.
    // Untuk XAMPP di Windows, biasanya ada di dalam folder xampp\mysql\bin
    $mysqldump_path = 'C:\xampp\mysql\bin\mysqldump.exe'; // Ganti dengan path absolut Anda

    if (!file_exists($mysqldump_path)) {
        throw new Exception("Executable 'mysqldump' tidak ditemukan di path: $mysqldump_path. Silakan periksa konfigurasi path di backup_restore.php.");
    }

    $backup_file_name = 'backup-' . $db_name . '-' . date("Y-m-d-H-i-s") . '.sql';
    
    // Perintah untuk menjalankan mysqldump
    $command = sprintf(
        '"%s" --host=%s --user=%s --password=%s %s > %s',
        $mysqldump_path,
        escapeshellarg($db_host),
        escapeshellarg($db_user),
        escapeshellarg($db_pass),
        escapeshellarg($db_name),
        escapeshellarg(TMP_PATH . '/' . $backup_file_name)
    );

    // Jalankan perintah
    exec($command, $output, $return_var);

    if ($return_var !== 0) {
        throw new Exception("Gagal membuat file backup. Error: " . implode("\n", $output));
    }

    $file_path = TMP_PATH . '/' . $backup_file_name;

    if (file_exists($file_path)) {
        // Set header untuk download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($backup_file_name) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        
        // Baca file dan kirim ke output
        readfile($file_path);
        
        // Hapus file sementara setelah diunduh
        unlink($file_path);
        exit;
    } else {
        throw new Exception('File backup tidak dapat dibuat.');
    }
}

function handle_restore() {
    // Get DB credentials from the Config class
    $db_host = Config::get('DB_SERVER');
    $db_user = Config::get('DB_USERNAME');
    $db_pass = Config::get('DB_PASSWORD');
    $db_name = Config::get('DB_NAME');

    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Gagal mengunggah file backup. Error code: ' . ($_FILES['backup_file']['error'] ?? 'N/A'));
    }

    $file = $_FILES['backup_file'];
    $file_path = $file['tmp_name'];
    $file_name = $file['name'];

    // Validasi ekstensi file
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if ($file_ext !== 'sql') {
        throw new Exception('File tidak valid. Harap unggah file dengan ekstensi .sql');
    }

    // Path ke mysql client. Sesuaikan jika perlu.
    $mysql_path = 'C:\xampp\mysql\bin\mysql.exe'; // Ganti dengan path absolut Anda

    if (!file_exists($mysql_path)) {
        throw new Exception("Executable 'mysql' tidak ditemukan di path: $mysql_path. Silakan periksa konfigurasi path di backup_restore.php.");
    }

    // Perintah untuk menjalankan mysql client dan mengimpor database
    $command = sprintf(
        '"%s" --host=%s --user=%s --password=%s %s < %s',
        $mysql_path,
        escapeshellarg($db_host),
        escapeshellarg($db_user),
        escapeshellarg($db_pass),
        escapeshellarg($db_name),
        escapeshellarg($file_path)
    );

    // Jalankan perintah
    exec($command, $output, $return_var);

    if ($return_var !== 0) {
        // Coba berikan pesan error yang lebih informatif jika ada
        $error_message = "Gagal memulihkan database. ";
        if (!empty($output)) {
            $error_message .= "Detail: " . implode("\n", $output);
        } else {
            $error_message .= "Pastikan detail koneksi database sudah benar.";
        }
        throw new Exception($error_message);
    }

    echo json_encode(['status' => 'success', 'message' => 'Database berhasil dipulihkan.']);
}