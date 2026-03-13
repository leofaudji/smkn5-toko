<?php
require_once __DIR__ . '/includes/bootstrap.php';
$conn = Database::getInstance()->getConnection();
$result = $conn->query("SHOW COLUMNS FROM consignment_items");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
