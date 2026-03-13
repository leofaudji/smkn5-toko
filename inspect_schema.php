<?php
require_once __DIR__ . '/includes/bootstrap.php';
$conn = Database::getInstance()->getConnection();

function show_columns($table) {
    global $conn;
    echo "\nColumns for $table:\n";
    $res = $conn->query("SHOW COLUMNS FROM $table");
    while ($row = $res->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
}

show_columns('items');
show_columns('penjualan');
show_columns('anggota');
show_columns('general_ledger');
show_columns('consignment_items');
