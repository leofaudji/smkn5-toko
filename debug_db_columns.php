<?php
/**
 * Database Schema Diagnostic Tool
 * This script checks for missing tables and columns required for Penjualan and Consignment features.
 */
require_once __DIR__ . '/includes/bootstrap.php';

// Security check: Only allow admins to run this
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak. Hanya Admin yang dapat menjalankan tool diagnosa ini.");
}

$conn = Database::getInstance()->getConnection();

// Define schema requirements
$requirements = [
    'items' => [
        'id' => 'INT',
        'sku' => 'VARCHAR',
        'barcode' => 'VARCHAR',
        'nama_barang' => 'VARCHAR',
        'stok' => 'INT'
    ],
    'consignment_items' => [
        'id' => 'INT',
        'sku' => 'VARCHAR',
        'barcode' => 'VARCHAR',
        'nama_barang' => 'VARCHAR',
        'stok_awal' => 'INT'
    ],
    'general_ledger' => [
        'consignment_item_id' => 'INT',
        'qty' => 'INT',
        'account_id' => 'INT',
        'ref_type' => 'ENUM'
    ],
    'settings' => [
        'setting_key' => 'VARCHAR',
        'setting_value' => 'TEXT'
    ]
];

$missing_columns = [];
$missing_tables = [];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Database Diagnosis - SMKN 5 TOKO</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 900px; margin: 40px auto; padding: 20px; background: #f4f7f6; }
        .card { background: #fff; padding: 30px; border-radius: 8px; shadow: 0 4px 6px rgba(0,0,0,0.1); border: 1px solid #ddd; }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .status-ok { color: #27ae60; font-weight: bold; }
        .status-error { color: #e74c3c; font-weight: bold; }
        .table-section { margin-bottom: 30px; border: 1px solid #eee; padding: 15px; border-radius: 5px; }
        .table-name { font-size: 1.2em; font-weight: bold; color: #34495e; margin-bottom: 10px; display: block; }
        .sql-box { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; overflow-x: auto; font-family: 'Courier New', Courier, monospace; margin-top: 10px; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-warning { background: #fef9c3; border: 1px solid #fde047; color: #854d0e; }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔍 Diagnosa Database Produksi</h1>
        <p>Gunakan halaman ini untuk memeriksa apakah struktur database di server produksi sudah sesuai dengan kebutuhan aplikasi terbaru.</p>

        <?php
        foreach ($requirements as $table => $cols) {
            echo "<div class='table-section'>";
            echo "<span class='table-name'>Tabel: <code>$table</code></span>";
            
            // Check table existence
            $res = $conn->query("SHOW TABLES LIKE '$table'");
            if ($res->num_rows == 0) {
                echo "<p class='status-error'>❌ TABEL TIDAK DITEMUKAN!</p>";
                $missing_tables[] = $table;
                echo "</div>";
                continue;
            } else {
                echo "<p class='status-ok'>✅ Tabel tersedia.</p>";
            }

            // Check columns
            echo "<ul>";
            foreach ($cols as $col => $type) {
                $checkCol = $conn->query("SHOW FULL COLUMNS FROM `$table` LIKE '$col'");
                if ($checkCol->num_rows == 0) {
                    echo "<li>Kolom <code>$col</code>: <span class='status-error'>TIDAK ADA</span></li>";
                    $missing_columns[] = ['table' => $table, 'column' => $col, 'type' => $type];
                } else {
                    $cdata = $checkCol->fetch_assoc();
                    $collation = $cdata['Collation'] ?? 'N/A';
                    echo "<li>Kolom <code>$col</code>: <span class='status-ok'>OK</span> (Collation: <code>$collation</code>)</li>";
                }
            }
            echo "</ul>";
            echo "</div>";
        }
        ?>

        <hr>

        <?php if (!empty($missing_columns) || !empty($missing_tables)): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Peringatan:</strong> Ditemukan ketidaksesuaian struktur database. Silakan jalankan perintah SQL berikut di phpMyAdmin atau terminal database Anda:
            </div>

            <div class="sql-box">
                <pre><?php
                foreach ($missing_columns as $m) {
                    $after = "";
                    // Attempt to find current order
                    // Simplification: just add it
                    echo "ALTER TABLE `{$m['table']}` ADD COLUMN `{$m['column']}` " . ($m['type'] == 'INT' ? 'INT(11)' : 'VARCHAR(100)') . " DEFAULT NULL;\n";
                }
                ?></pre>
            </div>
            
            <p>Atau jalankan file migrasi yang tersedia:</p>
            <ul>
                <li><a href="migrate_barcode.php" target="_blank">migrate_barcode.php</a></li>
                <li><a href="migrate_consignment_barcode.php" target="_blank">migrate_consignment_barcode.php</a></li>
            </ul>
        <?php else: ?>
            <div class="alert alert-success">
                <strong>✨ Sempurna!</strong> Struktur database Anda sudah mutakhir dan sesuai dengan kode aplikasi terbaru.
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; text-align: center;">
            <a href="index.php" style="color: #3498db; text-decoration: none;">&larr; Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>
