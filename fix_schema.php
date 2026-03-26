<?php
require_once 'includes/bootstrap.php';

header('Content-Type: text/plain');

echo "--- Schema Fix Utility ---\n";

try {
    $conn = Database::getInstance()->getConnection();
    
    echo "Checking for missing columns in 'users' table...\n";
    
    $result = $conn->query("DESCRIBE users");
    $columns = $result->fetch_all(MYSQLI_ASSOC);
    $column_names = array_column($columns, 'Field');
    
    $missing = [];
    if (!in_array('remember_selector', $column_names)) $missing[] = 'remember_selector';
    if (!in_array('remember_validator_hash', $column_names)) $missing[] = 'remember_validator_hash';
    
    if (empty($missing)) {
        echo "No missing columns found in 'users' table.\n";
    } else {
        echo "Missing columns: " . implode(', ', $missing) . "\n";
        echo "Attempting to fix schema...\n";
        
        foreach ($missing as $col) {
            $conn->query("ALTER TABLE users ADD COLUMN $col VARCHAR(255) DEFAULT NULL AFTER role_id");
            echo "Added column '$col' to 'users' table.\n";
        }
        echo "Schema fix COMPLETED.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
