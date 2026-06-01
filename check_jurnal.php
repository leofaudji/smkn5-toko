<?php
$conn = new mysqli('localhost', 'root', '', 'smkn5_toko');
$res = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'default_inventory_account_id'");
$row = $res->fetch_assoc();
$inv_acc = $row['setting_value'];
echo "Default Inv Acc: $inv_acc\n";

$res = $conn->query("SELECT id, nama_akun FROM accounts WHERE id = $inv_acc OR id = 401");
while ($r = $res->fetch_assoc()) {
    echo "Akun {$r['id']}: {$r['nama_akun']}\n";
}
?>
