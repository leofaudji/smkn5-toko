<?php
require_once 'includes/bootstrap.php';
$db = Database::getInstance()->getConnection();
$result = $db->query("DESCRIBE anggota");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
?>
