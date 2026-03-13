<?php
require_once __DIR__ . '/includes/bootstrap.php';
$conn = Database::getInstance()->getConnection();
header('Content-Type: text/plain');

try {
    // 1. Check if item_type exists in penjualan_details
    $res = $conn->query("SHOW COLUMNS FROM penjualan_details LIKE 'item_type'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE penjualan_details ADD COLUMN item_type ENUM('normal', 'consignment') DEFAULT 'normal' AFTER subtotal");
        echo "Column 'item_type' added to 'penjualan_details'.\n";
    } else {
        echo "Column 'item_type' already exists in 'penjualan_details'.\n";
    }

    // 2. Check if payment_method exists in penjualan
    $res = $conn->query("SHOW COLUMNS FROM penjualan LIKE 'payment_method'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE penjualan ADD COLUMN payment_method VARCHAR(50) DEFAULT 'cash' AFTER keterangan");
        echo "Column 'payment_method' added to 'penjualan'.\n";
    } else {
        echo "Column 'payment_method' already exists in 'penjualan'.\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
?>
