<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';
$per_tanggal = $_GET['per_tanggal'] ?? date('Y-m-d');

try {
    switch ($action) {
        case 'get_nominatif_simpanan':
            $sql = "SELECT 
                        a.nomor_anggota, 
                        a.nama_lengkap, 
                        SUM(t.kredit - t.debit) as saldo 
                    FROM anggota a 
                    LEFT JOIN ksp_transaksi_simpanan t ON a.id = t.anggota_id AND t.tanggal <= ? 
                    WHERE a.status = 'aktif' 
                    GROUP BY a.id 
                    HAVING saldo <> 0 
                    ORDER BY a.nama_lengkap ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("s", $per_tanggal);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'get_nominatif_pinjaman':
            $sql = "SELECT 
                        a.nomor_anggota, 
                        a.nama_lengkap, 
                        p.nomor_pinjaman, 
                        p.jumlah_pinjaman as plafon,
                        p.tanggal_pencairan,
                        (p.jumlah_pinjaman - COALESCE((
                            SELECT SUM(pokok_terbayar) 
                            FROM ksp_angsuran 
                            WHERE pinjaman_id = p.id AND (tanggal_bayar IS NOT NULL AND tanggal_bayar <= ?)
                        ), 0)) as sisa_pokok
                    FROM ksp_pinjaman p 
                    JOIN anggota a ON p.anggota_id = a.id 
                    WHERE p.status IN ('aktif', 'lunas') AND p.tanggal_pencairan <= ?
                    HAVING sisa_pokok > 0
                    ORDER BY a.nama_lengkap ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $per_tanggal, $per_tanggal);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        default:
            throw new Exception("Aksi tidak valid");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
