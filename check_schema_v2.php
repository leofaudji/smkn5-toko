<?php
require_once __DIR__ . '/includes/bootstrap.php';
$conn = Database::getInstance()->getConnection();
header('Content-Type: text/plain');

function checkTable($conn, $table) {
    echo "--- $table ---\n";
    $res = $conn->query("DESCRIBE `$table` ");
    while($row = $res->fetch_assoc()) {
        echo "{$row['Field']}\n";
    }
}

checkTable($conn, 'penjualan');
checkTable($conn, 'penjualan_details');
?>
