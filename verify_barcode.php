<?php
require_once __DIR__ . '/includes/bootstrap.php';
$db = Database::getInstance()->getConnection();

function test_search($term) {
    global $db;
    echo "Testing search for term: '$term'\n";
    $url = "/api/penjualan_handler.php?action=search_produk&term=" . urlencode($term);
    // Mimic the logic in search_produk since we can't easily do a real HTTP request here without a server running
    $user_id = 1;
    $search = "%{$term}%";
    
    $sql_normal = "SELECT id, sku, barcode, nama_barang FROM items WHERE user_id = ? AND (nama_barang LIKE ? OR sku LIKE ? OR barcode LIKE ?)";
    $sql_cons = "SELECT id, sku, barcode, nama_barang FROM consignment_items WHERE user_id = ? AND (nama_barang LIKE ? OR sku LIKE ? OR barcode LIKE ?)";
    
    $stmt = $db->prepare("($sql_normal) UNION ($sql_cons)");
    $stmt->bind_param('isssisss', $user_id, $search, $search, $search, $user_id, $search, $search, $search);
    $stmt->execute();
    $result = stmt_fetch_all($stmt);
    print_r($result);
}

// Add a test item with a barcode if none exists
$db->query("UPDATE items SET barcode = '8991234567890' LIMIT 1");

test_search('8991234567890');
