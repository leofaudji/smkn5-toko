<?php
require_once 'includes/bootstrap.php';
$db = Database::getInstance()->getConnection();
$db->query("ALTER TABLE anggota ADD COLUMN IF NOT EXISTS nik VARCHAR(20) AFTER nama_lengkap");
echo "Done";
?>
