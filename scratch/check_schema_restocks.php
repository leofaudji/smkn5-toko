<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$conn = Database::getInstance()->getConnection();
$result = $conn->query("DESCRIBE consignment_restocks");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
