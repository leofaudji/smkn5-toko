<?php
require_once __DIR__ . '/includes/bootstrap.php';

$conn = Database::getInstance()->getConnection();

try {
    echo "Starting migration: Adding 'barcode' column to 'consignment_items' table...\n";
    
    // Check if column already exists
    $check = $conn->query("SHOW COLUMNS FROM consignment_items LIKE 'barcode'");
    if ($check->num_rows === 0) {
        $sql = "ALTER TABLE consignment_items ADD COLUMN barcode VARCHAR(50) DEFAULT NULL AFTER sku";
        if ($conn->query($sql)) {
            echo "Successfully added 'barcode' column to 'consignment_items' table.\n";
        } else {
            throw new Exception("Error adding column: " . $conn->error);
        }
    } else {
        echo "Column 'barcode' already exists in 'consignment_items' table. Skipping.\n";
    }

    // Add index
    $check_index = $conn->query("SHOW INDEX FROM consignment_items WHERE Key_name = 'consignment_items_barcode_idx'");
    if ($check_index->num_rows === 0) {
        $sql_index = "CREATE INDEX consignment_items_barcode_idx ON consignment_items(barcode)";
        if ($conn->query($sql_index)) {
            echo "Successfully added index to 'barcode' column.\n";
        } else {
            echo "Warning: Could not add index: " . $conn->error . "\n";
        }
    }

    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
