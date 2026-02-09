<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $kolektibilitas = $_GET['kolektibilitas'] ?? '';

    // Query dasar untuk mengambil pinjaman aktif dan menghitung hari keterlambatan
    // Hari keterlambatan dihitung dari tanggal jatuh tempo angsuran tertua yang belum dibayar
    $sql = "
        SELECT 
            p.id, 
            p.nomor_pinjaman, 
            p.jumlah_pinjaman, 
            a.nama_lengkap, 
            a.nomor_anggota,
            (p.jumlah_pinjaman - COALESCE((SELECT SUM(pokok_terbayar) FROM ksp_angsuran WHERE pinjaman_id = p.id), 0)) as sisa_pokok,
            COALESCE((
                SELECT DATEDIFF(CURRENT_DATE, MIN(tanggal_jatuh_tempo)) 
                FROM ksp_angsuran 
                WHERE pinjaman_id = p.id AND status = 'belum_bayar' AND tanggal_jatuh_tempo < CURRENT_DATE
            ), 0) as hari_terlambat
        FROM ksp_pinjaman p
        JOIN anggota a ON p.anggota_id = a.id
        WHERE p.status = 'aktif'
    ";

    if (!empty($search)) {
        $search = $db->real_escape_string($search);
        $sql .= " AND (a.nama_lengkap LIKE '%$search%' OR p.nomor_pinjaman LIKE '%$search%' OR a.nomor_anggota LIKE '%$search%')";
    }

    $sql .= " ORDER BY hari_terlambat DESC, a.nama_lengkap ASC";

    $result = $db->query($sql);
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $days = (int)$row['hari_terlambat'];
        $status = 'Lancar';
        
        if ($days > 180) $status = 'Macet';
        elseif ($days > 120) $status = 'Diragukan';
        elseif ($days > 90) $status = 'Kurang Lancar';
        elseif ($days > 0) $status = 'Dalam Perhatian Khusus';

        // Filter by collectibility if set
        if (!empty($kolektibilitas) && $status !== $kolektibilitas) {
            continue;
        }

        $row['kolektibilitas'] = $status;
        $data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data]);
}