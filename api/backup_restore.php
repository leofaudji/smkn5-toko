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
    $db_name = Config::get('DB_NAME');

    // Use the existing database connection from bootstrap
    $conn = Database::getInstance()->getConnection();

    $backup_file_name = 'backup-' . $db_name . '-' . date("Y-m-d-H-i-s") . '.sql';

    // Set headers for download
    // Note: We remove the initial 'Content-Type: application/json' for this specific action
    // because we are streaming a file.
    header_remove('Content-Type');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($backup_file_name) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // Open output stream
    $handle = fopen('php://output', 'w');

    // Write SQL header
    fwrite($handle, "-- SMKN5-Toko SQL Dump\n");
    fwrite($handle, "-- Host: " . $db_host . "\n");
    fwrite($handle, "-- Generation Time: " . date('Y-m-d H:i:s') . "\n");
    fwrite($handle, "--\n\n");
    fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
    fwrite($handle, "START TRANSACTION;\n");
    fwrite($handle, "SET time_zone = \"+00:00\";\n\n");

    // Get all tables
    $tables_result = $conn->query('SHOW TABLES');
    if (!$tables_result) {
        fclose($handle);
        // We can't throw an exception here as headers are already sent.
        // Log the error and exit.
        error_log("Gagal mendapatkan daftar tabel: " . $conn->error);
        exit;
    }

    while ($row = $tables_result->fetch_row()) {
        $table = $row[0];

        // Get CREATE TABLE statement
        $create_table_result = $conn->query('SHOW CREATE TABLE `' . $table . '`');
        $create_table_row = $create_table_result->fetch_assoc();
        fwrite($handle, "\n-- --------------------------------------------------------\n\n");
        fwrite($handle, "--\n-- Table structure for table `$table`\n--\n\n");
        fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($handle, $create_table_row['Create Table'] . ";\n\n");
        $create_table_result->free();

        // Get table data
        $data_result = $conn->query('SELECT * FROM `' . $table . '`');
        if ($data_result->num_rows > 0) {
            fwrite($handle, "--\n-- Dumping data for table `$table`\n--\n\n");
            while ($data_row = $data_result->fetch_assoc()) {
                $columns = array_keys($data_row);
                $values = array_map(function ($value) use ($conn) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return "'" . $conn->real_escape_string($value) . "'";
                }, array_values($data_row));

                fwrite($handle, 'INSERT INTO `' . $table . '` (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $values) . ");\n");
            }
            fwrite($handle, "\n");
        }
        $data_result->free();
    }
    $tables_result->free();

    // Write SQL footer
    fwrite($handle, "COMMIT;\n");

    fclose($handle);

    if (isset($_SESSION['username'])) {
        log_activity($_SESSION['username'], 'Backup Database', 'Melakukan unduhan backup database.');
    }
    exit;
}

function handle_restore() {
    set_time_limit(0); // Mencegah timeout saat restore file besar
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

    // Use the existing database connection from bootstrap
    $conn = Database::getInstance()->getConnection();

    // Disable foreign key checks to avoid issues with table order
    $conn->query('SET foreign_key_checks = 0');

    // Baca dan eksekusi file SQL baris per baris untuk menghindari error max_allowed_packet
    $handle = fopen($file_path, "r");
    if ($handle) {
        $query = '';
        while (($line = fgets($handle)) !== false) {
            $trim_line = trim($line);
            // Lewati komentar dan baris kosong
            if ($trim_line === '' || strpos($trim_line, '--') === 0 || strpos($trim_line, '/*') === 0) {
                continue;
            }
            
            $query .= $line;
            // Jika baris diakhiri dengan titik koma, eksekusi query
            if (substr(rtrim($line), -1) === ';') {
                if (!$conn->query($query)) {
                    $error_message = "Gagal memulihkan database. Error: " . $conn->error;
                    fclose($handle);
                    $conn->query('SET foreign_key_checks = 1');
                    throw new Exception($error_message);
                }
                $query = '';
            }
        }
        fclose($handle);
    } else {
        $conn->query('SET foreign_key_checks = 1');
        throw new Exception('Gagal membaca file backup.');
    }

    // Re-enable foreign key checks
    $conn->query('SET foreign_key_checks = 1');

    if (isset($_SESSION['username'])) {
        log_activity($_SESSION['username'], 'Restore Database', "Melakukan restore database dari file: {$file_name}");
    }

    echo json_encode(['status' => 'success', 'message' => 'Database berhasil dipulihkan.']);
}