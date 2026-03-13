<?php
require_once __DIR__ . '/includes/bootstrap.php';
$conn = Database::getInstance()->getConnection();
header('Content-Type: text/plain');

try {
    // 1. Change table collation
    $sql1 = "ALTER TABLE consignment_items CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    if ($conn->query($sql1)) {
        echo "Table collation updated successfully.\n";
    } else {
        echo "Error updating table collation: " . $conn->error . "\n";
    }

    // 2. Double check specific columns just in case
    $sql2 = "ALTER TABLE consignment_items 
             MODIFY nama_barang VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
             MODIFY sku VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL";
    if ($conn->query($sql2)) {
        echo "Column collations updated successfully.\n";
    } else {
        echo "Error updating column collations: " . $conn->error . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
// unlink(__FILE__); // Keep for verification then delete
?>
