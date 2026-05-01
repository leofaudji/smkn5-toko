<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$user_id = 1; // ID Pemilik Data (Toko)

try {
    $cache_key = "report:growth:inventory:{$user_id}:{$tahun}";
    check_redis_cache($cache_key);

    $conn = Database::getInstance()->getConnection();
    $data_bulanan = [];
    $nilai_bulan_sebelumnya = 0;

    for ($bulan = 1; $bulan <= 12; $bulan++) {
        $tanggal_akhir_bulan = date("Y-m-t", strtotime("$tahun-$bulan-01"));

        $stmt = $conn->prepare("
            SELECT 
                SUM(
                    (
                        (SELECT COALESCE(SUM(pd.quantity), 0) FROM pembelian_details pd JOIN pembelian p ON pd.pembelian_id = p.id WHERE pd.item_id = i.id AND p.tanggal_pembelian <= ?) +
                        (SELECT COALESCE(SUM(sa.selisih_kuantitas), 0) FROM stock_adjustments sa WHERE sa.item_id = i.id AND sa.tanggal <= ?)
                    ) * i.harga_beli
                ) as total_nilai
            FROM items i
            WHERE i.user_id = ?
        ");
        $stmt->bind_param('ssi', $tanggal_akhir_bulan, $tanggal_akhir_bulan, $user_id);
        $stmt->execute();
        $hasil = stmt_fetch_assoc($stmt);
        $stmt->close();

        $nilai_bulan_ini = (float)($hasil['total_nilai'] ?? 0);
        $selisih = ($bulan > 1 && $nilai_bulan_sebelumnya != 0) ? $nilai_bulan_ini - $nilai_bulan_sebelumnya : 0;

        $data_bulanan[] = [
            'bulan' => $bulan,
            'nama_bulan' => date("F", mktime(0, 0, 0, $bulan, 10)),
            'nilai_persediaan' => $nilai_bulan_ini,
            'selisih' => $selisih,
        ];

        if ($nilai_bulan_ini > 0) {
            $nilai_bulan_sebelumnya = $nilai_bulan_ini;
        }
    }

    send_json_response($data_bulanan, $cache_key, 3600); // Cache 1 jam

} catch (Exception $e) {
    send_error_response($e->getMessage(), 500);
}
?>