<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$user_id = 1; // ID Pemilik Data (Toko)

try {
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;

    if (!$start_date || !$end_date) {
        throw new Exception("Rentang tanggal wajib diisi.");
    }

    // Single Optimized Query for all stock data
    $main_query = "
        SELECT 
            i.id, 
            i.nama_barang, 
            i.sku, 
            i.harga_beli,
            COALESCE(sa.stok_awal, 0) as stok_awal,
            COALESCE(p.masuk, 0) as masuk,
            COALESCE(p.keluar, 0) as keluar
        FROM items i
        LEFT JOIN (
            -- Stok Awal: Sum of all movements before start_date
            SELECT item_id, SUM(debit - kredit) as stok_awal
            FROM kartu_stok
            WHERE tanggal < ?
            GROUP BY item_id
        ) sa ON i.id = sa.item_id
        LEFT JOIN (
            -- Pergerakan: Sum of movements within period
            SELECT 
                item_id, 
                SUM(debit) as masuk, 
                SUM(kredit) as keluar
            FROM kartu_stok
            WHERE tanggal BETWEEN ? AND ?
            GROUP BY item_id
        ) p ON i.id = p.item_id
        WHERE i.user_id = ?
        ORDER BY i.nama_barang ASC
    ";

    $stmt = $conn->prepare($main_query);
    $stmt->bind_param('sssi', $start_date, $start_date, $end_date, $user_id);
    $stmt->execute();
    $results = stmt_fetch_all($stmt);
    $stmt->close();

    $report_data = [];
    $total_nilai_persediaan = 0;

    foreach ($results as $row) {
        $stok_awal = (int)$row['stok_awal'];
        $masuk = (int)$row['masuk'];
        $keluar = (int)$row['keluar'];
        $stok_akhir = $stok_awal + $masuk - $keluar;
        
        $nilai_persediaan = $stok_akhir * (float)$row['harga_beli'];
        $total_nilai_persediaan += $nilai_persediaan;

        $report_data[] = [
            'id' => $row['id'],
            'nama_barang' => $row['nama_barang'],
            'sku' => $row['sku'],
            'stok_awal' => $stok_awal,
            'masuk' => $masuk,
            'keluar' => $keluar,
            'stok_akhir' => $stok_akhir,
            'harga_beli' => (float)$row['harga_beli'],
            'nilai_persediaan' => $nilai_persediaan,
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $report_data,
        'summary' => ['total_nilai_persediaan' => $total_nilai_persediaan]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>