<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$user_id = 1; // ID Toko/Unit

try {
    $tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

    // 1. Ambil semua anggota aktif
    $sql_members = "SELECT id, nomor_anggota, nama_lengkap FROM anggota WHERE user_id = ? AND status = 'aktif' ORDER BY nama_lengkap ASC";
    $stmt_members = $conn->prepare($sql_members);
    $stmt_members->bind_param('i', $user_id);
    $stmt_members->execute();
    $members = $stmt_members->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_members->close();

    // 2. Ambil transaksi WB (SETOR) untuk tahun yang dipilih
    $sql_trans = "SELECT anggota_id, MONTH(tanggal) as bulan, SUM(jumlah) as total_bulan 
                  FROM transaksi_wajib_belanja 
                  WHERE user_id = ? AND YEAR(tanggal) = ? AND jenis = 'setor'
                  GROUP BY anggota_id, MONTH(tanggal)";
    $stmt_trans = $conn->prepare($sql_trans);
    $stmt_trans->bind_param('ii', $user_id, $tahun);
    $stmt_trans->execute();
    $transactions_result = $stmt_trans->get_result();
    
    // Mapping transaksi ke array [anggota_id][bulan] = jumlah
    $transactions = [];
    while($row = $transactions_result->fetch_assoc()) {
        $transactions[$row['anggota_id']][$row['bulan']] = (float)$row['total_bulan'];
    }
    $stmt_trans->close();

    // 3. Ambil Total Belanja per Anggota (Sepanjang Waktu atau Tahun ini? Biasanya saldo kumulatif)
    // Kita ambil total belanja tahun ini untuk laporan tahunan, tapi saldo akhir diambil dari tabel anggota
    $sql_belanja = "SELECT anggota_id, SUM(jumlah) as total_belanja 
                    FROM transaksi_wajib_belanja 
                    WHERE user_id = ? AND YEAR(tanggal) = ? AND jenis = 'belanja'
                    GROUP BY anggota_id";
    $stmt_belanja = $conn->prepare($sql_belanja);
    $stmt_belanja->bind_param('ii', $user_id, $tahun);
    $stmt_belanja->execute();
    $res_belanja = $stmt_belanja->get_result();
    $belanja_data = [];
    while($row = $res_belanja->fetch_assoc()) $belanja_data[$row['anggota_id']] = $row['total_belanja'];
    $stmt_belanja->close();

    // Ambil Saldo Akhir Real-time dari tabel anggota
    $stmt_saldo = $conn->prepare("SELECT id, saldo_wajib_belanja FROM anggota WHERE user_id = ?");
    $stmt_saldo->bind_param('i', $user_id);
    $stmt_saldo->execute();
    $res_saldo = $stmt_saldo->get_result();
    $saldo_map = [];
    while($row = $res_saldo->fetch_assoc()) $saldo_map[$row['id']] = $row['saldo_wajib_belanja'];

    // 3. Gabungkan data anggota dengan data transaksi
    $report_data = [];
    $totals_per_month = array_fill(1, 12, 0); // Untuk baris total di bawah
    $grand_total = 0;
    $grand_total_tunggakan = 0;
    $grand_total_belanja = 0;
    $grand_total_saldo = 0;

    // Ambil nominal wajib belanja dari setting
    $nominal_wajib_belanja = (float)get_setting('nominal_wajib_belanja', 50000, $conn);
    $current_year = (int)date('Y');
    $current_month = (int)date('n');

    $only_arrears = isset($_GET['only_arrears']) && $_GET['only_arrears'] === 'true';

    foreach ($members as $member) {
        $row = [
            'id' => $member['id'],
            'nomor_anggota' => $member['nomor_anggota'],
            'nama_lengkap' => $member['nama_lengkap'],
            'total_tahun' => 0,
            'total_belanja' => (float)($belanja_data[$member['id']] ?? 0),
            'saldo_akhir' => (float)($saldo_map[$member['id']] ?? 0)
        ];

        for ($m = 1; $m <= 12; $m++) {
            $amount = $transactions[$member['id']][$m] ?? 0;
            $row['bulan_' . $m] = $amount;
            $row['total_tahun'] += $amount;
        }

        // Hitung Sisa Tunggakan
        // Target bulan: Jika tahun lalu, target 12 bulan. Jika tahun ini, target sampai bulan ini.
        $target_months = 0;
        if ($tahun < $current_year) {
            $target_months = 12;
        } elseif ($tahun == $current_year) {
            $target_months = $current_month;
        }
        
        $target_amount = $target_months * $nominal_wajib_belanja;
        // Tunggakan tidak boleh negatif (jika bayar lebih, tunggakan 0)
        $tunggakan = max(0, $target_amount - $row['total_tahun']);
        $row['sisa_tunggakan'] = $tunggakan;

        // Filter: Jika hanya ingin yang menunggak dan anggota ini tidak menunggak, lewati
        if ($only_arrears && $tunggakan <= 0) {
            continue;
        }

        // Akumulasi total global (hanya untuk data yang lolos filter)
        for ($m = 1; $m <= 12; $m++) {
            $totals_per_month[$m] += $row['bulan_' . $m];
        }
        $grand_total += $row['total_tahun'];
        $grand_total_tunggakan += $tunggakan;
        $grand_total_belanja += $row['total_belanja'];
        $grand_total_saldo += $row['saldo_akhir'];

        $report_data[] = $row;
    }

    echo json_encode([
        'status' => 'success', 
        'data' => $report_data,
        'summary' => [
            'totals_per_month' => $totals_per_month,
            'grand_total' => $grand_total,
            'grand_total_tunggakan' => $grand_total_tunggakan,
            'grand_total_belanja' => $grand_total_belanja,
            'grand_total_saldo' => $grand_total_saldo
        ],
        'meta' => [
            'nominal_wajib_belanja' => $nominal_wajib_belanja
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
