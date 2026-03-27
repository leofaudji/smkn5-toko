<?php
// Debugging script for production 500 error
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'includes/bootstrap.php';
    $db = Database::getInstance()->getConnection();
    
    echo "<h1>Production Debug Info</h1>";
    
    // Check helper functions
    echo "<h3>1. Helper Functions Check:</h3>";
    echo "stmt_fetch_assoc exists: " . (function_exists('stmt_fetch_assoc') ? 'YES' : 'NO') . "<br>";
    echo "stmt_fetch_all exists: " . (function_exists('stmt_fetch_all') ? 'YES' : 'NO') . "<br>";
    
    // Check Table Schema
    echo "<h3>2. Table 'anggota' Schema Check:</h3>";
    $result = $db->query("SHOW COLUMNS FROM anggota");
    if ($result) {
        echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "Error checking columns: " . $db->error;
    }
    
    // Test the Get All Query
    echo "<h3>3. Test Get All Query:</h3>";
    $user_id = 1;
    $sql = "SELECT id, nomor_anggota, nama_lengkap, nik, no_telepon, status, tanggal_daftar FROM anggota WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        echo "Prepare failed: " . $db->error . "<br>";
    } else {
        echo "Prepare successful.<br>";
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
             echo "Execute successful.<br>";
        } else {
             echo "Execute failed: " . $stmt->error . "<br>";
        }
    }

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage();
}
?>
