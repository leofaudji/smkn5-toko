<?php
require_once __DIR__ . '/includes/bootstrap.php';
$conn = Database::getInstance()->getConnection();
header('Content-Type: text/plain');

$res = $conn->query("DESCRIBE penjualan_details");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
