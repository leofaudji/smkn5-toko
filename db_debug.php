<?php
require_once __DIR__ . '/includes/bootstrap.php';
$conn = Database::getInstance()->getConnection();
$res = $conn->query("DESCRIBE transaksi_wajib_belanja");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . ': ' . $row['Type'] . PHP_EOL;
}
