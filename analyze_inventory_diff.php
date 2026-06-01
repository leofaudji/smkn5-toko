<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once __DIR__ . '/includes/bootstrap.php';
$db = Database::getInstance()->getConnection();

$user_id = 1;

echo "=== MENGHITUNG NOMINAL TRANSAKSI DI KARTU STOK PER SOURCE ===\n";

$sources = ['import', '', 'void_penjualan', 'sync', 'pembelian', 'penjualan'];

foreach ($sources as $source) {
    // Cari total kuantitas dan nominal (qty * harga_beli)
    $res = $db->query("
        SELECT 
            COUNT(*) as total_transaksi,
            SUM(ks.debit) as qty_debit,
            SUM(ks.kredit) as qty_kredit,
            SUM(ks.debit * i.harga_beli) as nominal_debit,
            SUM(ks.kredit * i.harga_beli) as nominal_kredit
        FROM kartu_stok ks
        JOIN items i ON ks.item_id = i.id
        WHERE ks.source = '$source' AND ks.user_id = $user_id
    ")->fetch_assoc();
    
    echo "Source: '" . ($source === '' ? '[BLANK]' : $source) . "'\n";
    echo "- Total Transaksi: {$res['total_transaksi']}\n";
    echo "- Qty Debit: " . ($res['qty_debit'] ?? 0) . " (Nominal: Rp " . number_format($res['nominal_debit'], 2, ',', '.') . ")\n";
    echo "- Qty Kredit: " . ($res['qty_kredit'] ?? 0) . " (Nominal: Rp " . number_format($res['nominal_kredit'], 2, ',', '.') . ")\n\n";
}

echo "\n=== CONTOH TRANSAKSI KARTU STOK DENGAN SOURCE [BLANK] ATAU SYNC ===\n";
$res_samples = $db->query("
    SELECT ks.id, ks.tanggal, ks.item_id, i.nama_barang, ks.debit, ks.kredit, ks.keterangan, ks.source
    FROM kartu_stok ks
    JOIN items i ON ks.item_id = i.id
    WHERE ks.source IN ('', 'sync') AND ks.user_id = $user_id
    LIMIT 10
");
while ($row = $res_samples->fetch_assoc()) {
    print_r($row);
}
