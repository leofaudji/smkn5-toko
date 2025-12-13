<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

try {
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;

    if (!$start_date || !$end_date) {
        throw new Exception("Rentang tanggal wajib diisi.");
    }

    // 1. Ambil semua item
    $items_query = "SELECT id, nama_barang, sku, harga_beli FROM items WHERE user_id = ?";
    $stmt_items = $conn->prepare($items_query);
    $stmt_items->bind_param('i', $user_id);
    $stmt_items->execute();
    $items_result = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();

    $report_data = [];
    $total_nilai_persediaan = 0;

    // Siapkan prepared statements di luar loop
    $stmt_stok_awal = $conn->prepare("
        SELECT 
            (SELECT COALESCE(SUM(pd.quantity), 0) FROM pembelian_details pd JOIN pembelian p ON pd.pembelian_id = p.id WHERE pd.item_id = ? AND p.tanggal_pembelian < ?) +
            (SELECT COALESCE(SUM(sa.selisih_kuantitas), 0) FROM stock_adjustments sa WHERE sa.item_id = ? AND sa.tanggal < ?)
        AS stok_awal
    ");

    $stmt_pergerakan = $conn->prepare("
        SELECT 
            (SELECT COALESCE(SUM(pd.quantity), 0) FROM pembelian_details pd JOIN pembelian p ON pd.pembelian_id = p.id WHERE pd.item_id = ? AND p.tanggal_pembelian BETWEEN ? AND ?) AS masuk,
            (SELECT COALESCE(SUM(sa.selisih_kuantitas), 0) FROM stock_adjustments sa WHERE sa.item_id = ? AND sa.tanggal BETWEEN ? AND ?) AS penyesuaian
    ");

    foreach ($items_result as $item) {
        $item_id = $item['id'];

        // 2. Hitung Stok Awal
        $stmt_stok_awal->bind_param("isis", $item_id, $start_date, $item_id, $start_date);
        $stmt_stok_awal->execute();
        $stok_awal = (int)$stmt_stok_awal->get_result()->fetch_assoc()['stok_awal'];

        // 3. Hitung Pergerakan Stok (Masuk & Keluar) dalam periode
        $stmt_pergerakan->bind_param("ississ", $item_id, $start_date, $end_date, $item_id, $start_date, $end_date);
        $stmt_pergerakan->execute();
        $pergerakan = $stmt_pergerakan->get_result()->fetch_assoc();
        
        $masuk = (int)$pergerakan['masuk'];
        $penyesuaian = (int)$pergerakan['penyesuaian'];

        // Pisahkan penyesuaian positif (masuk) dan negatif (keluar)
        if ($penyesuaian > 0) {
            $masuk += $penyesuaian;
            $keluar = 0;
        } else {
            $keluar = abs($penyesuaian);
        }

        // 4. Hitung Stok Akhir
        $stok_akhir = $stok_awal + $masuk - $keluar;
        $nilai_persediaan = $stok_akhir * (float)$item['harga_beli'];
        $total_nilai_persediaan += $nilai_persediaan;

        $report_data[] = [
            'id' => $item['id'],
            'nama_barang' => $item['nama_barang'],
            'sku' => $item['sku'],
            'stok_awal' => $stok_awal,
            'masuk' => $masuk,
            'keluar' => $keluar,
            'stok_akhir' => $stok_akhir,
            'harga_beli' => (float)$item['harga_beli'],
            'nilai_persediaan' => $nilai_persediaan,
        ];
    }

    $stmt_stok_awal->close();
    $stmt_pergerakan->close();

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