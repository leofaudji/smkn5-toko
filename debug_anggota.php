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
    echo "<h3>3. Test Get All Query (Anggota):</h3>";
    $user_id = $_SESSION['user_id'] ?? 1;
    echo "Session User ID: $user_id<br>";
    $sql = "SELECT id, nomor_anggota, nama_lengkap, nik, no_telepon, status, tanggal_daftar FROM anggota WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        echo "Prepare failed: " . $db->error . "<br>";
    } else {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
             $res = stmt_fetch_all($stmt);
             echo "Total rows fetched: " . count($res) . "<br>";
        } else {
             echo "Execute failed: " . $stmt->error . "<br>";
        }
    }

    // Check Wajib Belanja Tables
    echo "<h3>4. Wajib Belanja Data Check:</h3>";
    $tables = ['transaksi_wajib_belanja', 'anggota', 'accounts'];
    foreach ($tables as $t) {
        $countRes = $db->query("SELECT COUNT(*) as total FROM $t");
        if ($countRes) {
            $total = $countRes->fetch_assoc()['total'];
            echo "Table <b>$t</b> has $total records.<br>";
        } else {
            echo "Table <b>$t</b> check failed: " . $db->error . "<br>";
        }
    }
    
    // Check specific conditions for init_data
    echo "<h3>5. Init Data Conditions:</h3>";
    $active_anggota = $db->query("SELECT COUNT(*) as total FROM anggota WHERE user_id = $user_id AND status = 'aktif'")->fetch_assoc()['total'];
    $kas_accounts = $db->query("SELECT COUNT(*) as total FROM accounts WHERE user_id = $user_id AND is_kas = 1")->fetch_assoc()['total'];
    echo "Active Members for User $user_id: $active_anggota<br>";
    echo "Cash Accounts for User $user_id: $kas_accounts<br>";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage();
}
?>
