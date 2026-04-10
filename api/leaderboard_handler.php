<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();
$user_id = 1; // Default global owner
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_top_shoppers':
            get_top_shoppers($db, $user_id);
            break;
        case 'get_top_loyalists':
            get_top_loyalists($db, $user_id);
            break;
        case 'get_member_history':
            get_member_history($db, $user_id);
            break;
        case 'get_sale_details':
            get_sale_details($db, $user_id);
            break;
        default:
            throw new Exception("Aksi tidak valid.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Mendapatkan peringkat anggota paling aktif belanja
 */
function get_top_shoppers($db, $user_id) {
    $period_days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
    
    $sql = "
        SELECT 
            a.id, a.nama_lengkap, a.nomor_anggota,
            COUNT(p.id) as total_transaksi,
            SUM(p.total) as total_belanja
        FROM anggota a
        JOIN penjualan p ON a.id = p.customer_id
        WHERE p.status = 'completed' 
          AND p.tanggal_penjualan >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY a.id, a.nama_lengkap, a.nomor_anggota
        ORDER BY total_belanja DESC, total_transaksi DESC
        LIMIT 20
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $period_days);
    $stmt->execute();
    $data = stmt_fetch_all($stmt);
    $stmt->close();

    echo json_encode(['success' => true, 'data' => $data]);
}

/**
 * Mendapatkan peringkat anggota paling disiplin bayar WB
 * Skor = jumlah bulan di mana bayar SETOR sebelum tanggal 10.
 */
function get_top_loyalists($db, $user_id) {
    // Menghitung poin: 1 poin per bulan jika ada pembayaran setor sebelum/pada tanggal 10
    $limit_day = 10;
    
    $sql = "
        SELECT 
            a.id, a.nama_lengkap, a.nomor_anggota,
            COUNT(DISTINCT CONCAT(YEAR(twb.tanggal), '-', MONTH(twb.tanggal))) as total_bulan_aktif,
            SUM(CASE WHEN DAY(twb.tanggal) <= ? THEN 1 ELSE 0 END) as on_time_points,
            SUM(twb.jumlah) as total_setoran
        FROM anggota a
        JOIN transaksi_wajib_belanja twb ON a.id = twb.anggota_id
        WHERE twb.jenis = 'setor'
        GROUP BY a.id, a.nama_lengkap, a.nomor_anggota
        HAVING on_time_points > 0
        ORDER BY on_time_points DESC, total_bulan_aktif DESC, total_setoran DESC
        LIMIT 20
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $limit_day);
    $stmt->execute();
    $data = stmt_fetch_all($stmt);
    $stmt->close();

    echo json_encode(['success' => true, 'data' => $data]);
}

/**
 * Mendapatkan detail riwayat transaksi per anggota
 */
function get_member_history($db, $user_id) {
    $member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
    if (!$member_id) throw new Exception("ID Anggota tidak valid.");

    // 1. Riwayat Belanja (Penjualan)
    $stmt_penjualan = $db->prepare("
        SELECT id, nomor_referensi, tanggal_penjualan, total, status 
        FROM penjualan 
        WHERE customer_id = ? 
        ORDER BY tanggal_penjualan DESC, id DESC 
        LIMIT 20
    ");
    $stmt_penjualan->bind_param('i', $member_id);
    $stmt_penjualan->execute();
    $penjualan = stmt_fetch_all($stmt_penjualan);
    $stmt_penjualan->close();

    // 2. Riwayat Wajib Belanja
    $stmt_wb = $db->prepare("
        SELECT nomor_referensi, tanggal, jenis, jumlah, keterangan
        FROM transaksi_wajib_belanja 
        WHERE anggota_id = ? 
        ORDER BY tanggal DESC, id DESC 
        LIMIT 20
    ");
    $stmt_wb->bind_param('i', $member_id);
    $stmt_wb->execute();
    $wb = stmt_fetch_all($stmt_wb);
    $stmt_wb->close();

    echo json_encode([
        'success' => true, 
        'data' => [
            'penjualan' => $penjualan,
            'wajib_belanja' => $wb
        ]
    ]);
}

/**
 * Mendapatkan rincian item dalam satu transaksi penjualan
 */
function get_sale_details($db, $user_id) {
    $sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;
    if (!$sale_id) throw new Exception("ID Penjualan tidak valid.");

    $sql = "
        SELECT 
            deskripsi_item as nama_barang,
            price as harga,
            quantity as qty,
            subtotal
        FROM penjualan_details
        WHERE penjualan_id = ?
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $sale_id);
    $stmt->execute();
    $data = stmt_fetch_all($stmt);
    $stmt->close();

    echo json_encode(['success' => true, 'data' => $data]);
}
?>
