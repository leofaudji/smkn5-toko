<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$conn = Database::getInstance()->getConnection();
$user_id = 1;

// Test the mutation query logic
$query = "
    SELECT * FROM (
        SELECT 
            ci.tanggal_terima as tanggal, 
            ci.nama_barang, 
            s.nama_pemasok, 
            'Stok Awal' as tipe, 
            ci.stok_awal as qty, 
            'Penerimaan awal' as keterangan,
            ci.id as item_id,
            0 as mutation_id
        FROM consignment_items ci
        JOIN suppliers s ON ci.supplier_id = s.id
        WHERE ci.user_id = $user_id
        
        UNION ALL
        
        SELECT 
            cr.tanggal, 
            ci.nama_barang, 
            s.nama_pemasok, 
            'Restock' as tipe, 
            cr.qty, 
            cr.keterangan,
            ci.id as item_id,
            cr.id as mutation_id
        FROM consignment_restocks cr
        JOIN consignment_items ci ON cr.consignment_item_id = ci.id
        JOIN suppliers s ON ci.supplier_id = s.id
        WHERE cr.user_id = $user_id
    ) AS m 
    WHERE m.tipe = 'Restock'
    LIMIT 5
";

$result = $conn->query($query);
$data = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);
