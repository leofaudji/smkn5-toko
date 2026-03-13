<?php
require_once __DIR__ . '/includes/bootstrap.php';
$conn = Database::getInstance()->getConnection();
header('Content-Type: text/plain');

function checkTable($conn, $table) {
    echo "--- $table ---\n";
    $res = $conn->query("SHOW FULL COLUMNS FROM `$table` ");
    while($row = $res->fetch_assoc()) {
        if (in_array($row['Field'], ['sku', 'nama_barang'])) {
            echo "{$row['Field']}: {$row['Collation']}\n";
        }
    }
}

checkTable($conn, 'items');
checkTable($conn, 'consignment_items');
?>
