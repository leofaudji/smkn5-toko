<?php
require_once __DIR__ . '/includes/bootstrap.php';
$conn = Database::getInstance()->getConnection();
$res = $conn->query("DESCRIBE penjualan");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . ': ' . $row['Type'] . ' ' . $row['Null'] . PHP_EOL;
}
