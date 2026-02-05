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

switch ($action) {
    case 'get_anggota':
        $sql = "SELECT id, nama_lengkap, nomor_anggota FROM anggota WHERE status = 'aktif' ORDER BY nama_lengkap ASC";
        $result = $db->query($sql);
        echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
        break;

    case 'get_report':
        $anggota_id = $_GET['anggota_id'] ?? 0;
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-d');

        if (empty($anggota_id)) {
            echo json_encode(['success' => false, 'message' => 'Anggota harus dipilih']);
            exit;
        }

        // 1. Hitung Saldo Awal (Transaksi sebelum start_date)
        // Saldo Simpanan (Liabilitas) = Kredit - Debit
        $stmt_awal = $db->prepare("SELECT SUM(kredit - debit) as saldo_awal FROM ksp_transaksi_simpanan WHERE anggota_id = ? AND tanggal < ?");
        $stmt_awal->bind_param("is", $anggota_id, $start_date);
        $stmt_awal->execute();
        $res_awal = $stmt_awal->get_result()->fetch_assoc();
        $saldo_awal = $res_awal['saldo_awal'] ?? 0;

        // 2. Ambil Transaksi Periode Ini
        $sql = "SELECT t.*, j.nama as jenis_simpanan 
                FROM ksp_transaksi_simpanan t
                JOIN ksp_jenis_simpanan j ON t.jenis_simpanan_id = j.id
                WHERE t.anggota_id = ? AND t.tanggal BETWEEN ? AND ?
                ORDER BY t.tanggal ASC, t.id ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param("iss", $anggota_id, $start_date, $end_date);
        $stmt->execute();
        $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 3. Hitung Running Balance
        $data = [];
        $current_balance = $saldo_awal;

        foreach ($transactions as $trx) {
            $debit = (float)$trx['debit'];
            $kredit = (float)$trx['kredit'];
            $current_balance += ($kredit - $debit);

            $data[] = [
                'tanggal' => $trx['tanggal'],
                'nomor_referensi' => $trx['nomor_referensi'],
                'jenis_simpanan' => $trx['jenis_simpanan'],
                'keterangan' => $trx['keterangan'],
                'debit' => $debit,
                'kredit' => $kredit,
                'saldo' => $current_balance
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'saldo_awal' => (float)$saldo_awal,
                'transactions' => $data,
                'saldo_akhir' => $current_balance
            ]
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}