<?php
require_once 'includes/bootstrap.php';

header('Content-Type: text/plain');

echo "--- Environment Check ---\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "mysqlnd enabled: " . (extension_loaded('mysqlnd') ? 'YES' : 'NO') . "\n";
echo "mysqli_fetch_all exists: " . (function_exists('mysqli_fetch_all') ? 'YES' : 'NO') . "\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "PROJECT_ROOT: " . PROJECT_ROOT . "\n";
echo "BASE_PATH: " . BASE_PATH . "\n";
echo "APP_DEBUG: " . (Config::get('APP_DEBUG') ?: 'not set') . "\n";

echo "\n--- Database Configuration ---\n";
echo "DB_SERVER: " . (Config::get('DB_SERVER') ?: 'NOT SET') . "\n";
echo "DB_USERNAME: " . (Config::get('DB_USERNAME') ?: 'NOT SET') . "\n";
echo "DB_NAME: " . (Config::get('DB_NAME') ?: 'NOT SET') . "\n";
echo "DB_PASSWORD: " . (Config::get('DB_PASSWORD') ? 'SET (hidden)' : 'NOT SET') . "\n";

echo "\n--- Database Connection Test ---\n";
try {
    $conn = Database::getInstance()->getConnection();
    echo "Connection: SUCCESS\n";
    
    echo "\n--- Table Check ---\n";
    $tables = ['users', 'roles', 'permissions', 'role_permissions', 'activity_log', 'settings'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "Table '$table': EXISTS\n";
            // Check columns for certain tables
            if ($table === 'users' || $table === 'roles' || $table === 'activity_log') {
                $cols = $conn->query("DESCRIBE `$table`")->fetch_all(MYSQLI_ASSOC);
                echo "  Columns: " . implode(', ', array_column($cols, 'Field')) . "\n";
            }
        } else {
            echo "Table '$table': MISSING\n";
        }
    }
} catch (Exception $e) {
    echo "Connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
}
