<?php
// Migration script to move data from user_id 1 to the correct user_id
require_once 'includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die("Error: Anda harus login terlebih dahulu.");
}

$db = Database::getInstance()->getConnection();
$old_id = 1;
$new_id = $_SESSION['user_id'];

if ($old_id == $new_id) {
    die("Informasi: Anda sudah menggunakan User ID 1. Tidak ada data yang perlu dipindahkan.");
}

echo "<h1>Data Migration: User $old_id &rarr; User $new_id</h1>";

$tables = [
    'anggota',
    'accounts',
    'transaksi_wajib_belanja',
    'jurnal_entries',
    'jurnal_details',
    'general_ledger',
    'transaksi',
    'pembelian',
    'penjualan',
    'aset_tetap',
    'settings',
    'rekening_bank',
    'analisis_rasio',
    'anggaran',
    'histori_rekonsiliasi',
    'konsinyasi',
    'penarikan',
    'pinjaman',
    'simpanan',
    'role_menus'
];

foreach ($tables as $table) {
    echo "Processing <b>$table</b>... ";
    
    // Check if table exists
    $check = $db->query("SHOW TABLES LIKE '$table'");
    if ($check->num_rows == 0) {
        echo "<span style='color:orange'>Skipped (Table not found)</span><br>";
        continue;
    }

    // Check if user_id column exists
    $col_check = $db->query("SHOW COLUMNS FROM $table LIKE 'user_id'");
    if ($col_check->num_rows == 0) {
        echo "<span style='color:orange'>Skipped (No user_id column)</span><br>";
        continue;
    }

    // Update
    $update = $db->prepare("UPDATE $table SET user_id = ? WHERE user_id = ?");
    $update->bind_param("ii", $new_id, $old_id);
    if ($update->execute()) {
        echo "<span style='color:green'>Success! Rows updated: " . $db->affected_rows . "</span><br>";
    } else {
        echo "<span style='color:red'>Error: " . $db->error . "</span><br>";
    }
    $update->close();
}

echo "<h3>Migrasi selesai. Silakan cek kembali menu Anggota dan Wajib Belanja.</h3>";
echo "<p><a href='index.php?page=dashboard'>Kembali ke Dashboard</a></p>";
?>
