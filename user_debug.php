<?php
require_once __DIR__ . '/includes/bootstrap.php';
$conn = Database::getInstance()->getConnection();
$res = $conn->query("SELECT id, username FROM users");
while($row = $res->fetch_assoc()) {
    echo $row['id'] . ': ' . $row['username'] . PHP_EOL;
}
