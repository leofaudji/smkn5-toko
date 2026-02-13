<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01', strtotime('-5 months'));
    $end_date = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
    $compare_type = $_GET['compare_type'] ?? 'mom'; // mom or yoy

    // 1. Summary Data
    $current_stats = get_ksp_summary_stats($db, $end_date);
    
    // Calculate Comparison Date
    $comp_date_obj = new DateTime($end_date);
    if ($compare_type === 'yoy') {
        $comp_date_obj->modify('-1 year');
    } else {
        $comp_date_obj->modify('-1 month');
    }
    $comp_date = $comp_date_obj->format('Y-m-d');
    
    $prev_stats = get_ksp_summary_stats($db, $comp_date);

    // Calculate Growth Percentages
    $growth_simpanan = calculate_growth($current_stats['total_simpanan'], $prev_stats['total_simpanan']);
    $growth_outstanding = calculate_growth($current_stats['total_outstanding'], $prev_stats['total_outstanding']);
    $growth_macet = calculate_growth($current_stats['total_macet'], $prev_stats['total_macet']);

    // 2. Growth Data (From Start Date to End Date)
    $labels = [];
    $data_simpanan = [];
    $data_pinjaman = [];

    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = DateInterval::createFromDateString('1 month');
    $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

    foreach ($period as $dt) {
        $month_end = $dt->format('Y-m-t');
        if ($month_end > $end_date) $month_end = $end_date;

        $labels[] = $dt->format('M Y');

        // Snapshot Simpanan per akhir bulan
        $q_sim = "SELECT SUM(kredit - debit) as total FROM ksp_transaksi_simpanan WHERE tanggal <= '$month_end'";
        $res_sim = $db->query($q_sim)->fetch_assoc();
        $data_simpanan[] = (float)($res_sim['total'] ?? 0);

        // Snapshot Pinjaman per akhir bulan (Approximation: Total Cair - Total Bayar Pokok)
        $q_pinj_cair = "SELECT SUM(jumlah_pinjaman) as total FROM ksp_pinjaman WHERE status != 'ditolak' AND tanggal_pencairan <= '$month_end'";
        $res_cair = $db->query($q_pinj_cair)->fetch_assoc();
        
        $q_pinj_bayar = "SELECT SUM(pokok_terbayar) as total FROM ksp_angsuran WHERE tanggal_bayar <= '$month_end'";
        $res_bayar = $db->query($q_pinj_bayar)->fetch_assoc();
        
        $data_pinjaman[] = (float)(($res_cair['total'] ?? 0) - ($res_bayar['total'] ?? 0));
    }

    // 3. Quality Data (Kolektibilitas) at End Date
    // Hitung berdasarkan hari terlambat angsuran tertua
    $stmt_quality = $db->prepare("
        SELECT 
            p.id,
            COALESCE(MAX(DATEDIFF(?, a.tanggal_jatuh_tempo)), 0) as max_delay
        FROM ksp_pinjaman p
        JOIN ksp_angsuran a ON p.id = a.pinjaman_id
        WHERE p.tanggal_pencairan <= ? AND p.status != 'ditolak'
        AND (a.tanggal_bayar IS NULL OR a.tanggal_bayar > ?) 
        AND a.tanggal_jatuh_tempo < ?
        GROUP BY p.id
    ");
    $stmt_quality->bind_param("ssss", $end_date, $end_date, $end_date, $end_date);
    $stmt_quality->execute();
    $res_quality = $stmt_quality->get_result();
    
    $quality_counts = ['lancar' => 0, 'dpk' => 0, 'kurang_lancar' => 0, 'diragukan' => 0, 'macet' => 0];
    
    $terlambat_count = 0;

    while ($row = $res_quality->fetch_assoc()) {
        $days = (int)$row['max_delay'];
        $terlambat_count++;
        if ($days > 180) $quality_counts['macet']++;
        elseif ($days > 120) $quality_counts['diragukan']++;
        elseif ($days > 90) $quality_counts['kurang_lancar']++;
        else $quality_counts['dpk']++;
    }

    // Hitung pinjaman yang lancar (Total Active - Terlambat)
    // Active = Disbursed <= end_date AND (Outstanding > 0)
    $stmt_total_aktif = $db->prepare("
        SELECT COUNT(*) as total FROM ksp_pinjaman p 
        WHERE p.tanggal_pencairan <= ? AND p.status != 'ditolak'
        AND (p.jumlah_pinjaman - COALESCE((SELECT SUM(pokok_terbayar) FROM ksp_angsuran WHERE pinjaman_id = p.id AND tanggal_bayar <= ?), 0)) > 0
    ");
    $stmt_total_aktif->bind_param("ss", $end_date, $end_date);
    $stmt_total_aktif->execute();
    $total_aktif = $stmt_total_aktif->get_result()->fetch_assoc()['total'];

    $quality_counts['lancar'] = $total_aktif - $terlambat_count;

    // 4. Savings Composition (Jenis Simpanan) at End Date
    $stmt_comp = $db->prepare("SELECT j.nama, SUM(t.kredit - t.debit) as total_saldo 
                 FROM ksp_transaksi_simpanan t 
                 JOIN ksp_jenis_simpanan j ON t.jenis_simpanan_id = j.id 
                 WHERE t.tanggal <= ?
                 GROUP BY t.jenis_simpanan_id");
    $stmt_comp->bind_param("s", $end_date);
    $stmt_comp->execute();
    $comp_data = $stmt_comp->get_result()->fetch_all(MYSQLI_ASSOC);

    // 5. Top 5 Savers at End Date
    $stmt_top = $db->prepare("SELECT a.nama_lengkap, a.nomor_anggota, SUM(t.kredit - t.debit) as total_saldo 
                FROM ksp_transaksi_simpanan t 
                JOIN anggota a ON t.anggota_id = a.id 
                WHERE t.tanggal <= ?
                GROUP BY t.anggota_id 
                ORDER BY total_saldo DESC 
                LIMIT 5");
    $stmt_top->bind_param("s", $end_date);
    $stmt_top->execute();
    $top_savers = $stmt_top->get_result()->fetch_all(MYSQLI_ASSOC);

    // 6. Cashflow Forecasting (Historical 6 months + Forecast 3 months)
    // Base calculation on end_date to allow backtesting via filter
    $hist_months = [];
    $hist_net_flow = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $m_time = strtotime("$end_date -$i months");
        $m_start = date('Y-m-01', $m_time);
        $m_end = date('Y-m-t', $m_time);
        $hist_months[] = date('M Y', $m_time);

        // Savings Flow: Inflow (Setor) - Outflow (Tarik)
        $q_save = "SELECT SUM(CASE WHEN jenis_transaksi = 'setor' THEN jumlah ELSE -jumlah END) as net_save 
                   FROM ksp_transaksi_simpanan WHERE tanggal BETWEEN '$m_start' AND '$m_end'";
        $net_save = (float)($db->query($q_save)->fetch_assoc()['net_save'] ?? 0);

        // Loan Flow: Inflow (Repayment) - Outflow (Disbursement)
        $q_loan_out = "SELECT SUM(jumlah_pinjaman) as total FROM ksp_pinjaman WHERE status != 'ditolak' AND tanggal_pencairan BETWEEN '$m_start' AND '$m_end'";
        $loan_out = (float)($db->query($q_loan_out)->fetch_assoc()['total'] ?? 0);

        $q_loan_in = "SELECT SUM(pokok_terbayar + bunga_terbayar + denda) as total FROM ksp_angsuran WHERE tanggal_bayar BETWEEN '$m_start' AND '$m_end'";
        $loan_in = (float)($db->query($q_loan_in)->fetch_assoc()['total'] ?? 0);

        $net_flow = $net_save + ($loan_in - $loan_out);
        $hist_net_flow[] = $net_flow;
    }

    // Simple Linear Regression
    $n = count($hist_net_flow);
    $x = range(0, $n - 1);
    $y = $hist_net_flow;
    $sum_x = array_sum($x); $sum_y = array_sum($y);
    $sum_xx = 0; $sum_xy = 0;
    for ($i = 0; $i < $n; $i++) { $sum_xx += $x[$i]**2; $sum_xy += $x[$i] * $y[$i]; }
    
    $slope = ($n * $sum_xx - $sum_x**2) != 0 ? ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_xx - $sum_x**2) : 0;
    $intercept = ($sum_y - $slope * $sum_x) / $n;

    $forecast_months = [];
    $forecast_values = [];
    for ($i = 1; $i <= 3; $i++) {
        $f_time = strtotime("$end_date +$i months");
        $forecast_months[] = date('M Y', $f_time);
        $forecast_values[] = $slope * ($n - 1 + $i) + $intercept;
    }

    // 7. Loan Portfolio by Type (Outstanding)
    $stmt_lp = $db->prepare("
        SELECT j.nama, SUM(p.jumlah_pinjaman - COALESCE((SELECT SUM(pokok_terbayar) FROM ksp_angsuran WHERE pinjaman_id = p.id AND tanggal_bayar <= ?), 0)) as total_outstanding
        FROM ksp_pinjaman p
        JOIN ksp_jenis_pinjaman j ON p.jenis_pinjaman_id = j.id
        WHERE p.tanggal_pencairan <= ? AND p.status != 'ditolak'
        GROUP BY j.id
    ");
    $stmt_lp->bind_param("ss", $end_date, $end_date);
    $stmt_lp->execute();
    $loan_portfolio = $stmt_lp->get_result()->fetch_all(MYSQLI_ASSOC);

    // 8. Member Growth (Last 6 months relative to end_date)
    $mg_start = date('Y-m-01', strtotime("$end_date -5 months"));
    $stmt_mg = $db->prepare("
        SELECT DATE_FORMAT(tanggal_daftar, '%Y-%m') as bulan, COUNT(*) as total
        FROM anggota
        WHERE tanggal_daftar BETWEEN ? AND ?
        GROUP BY bulan
        ORDER BY bulan ASC
    ");
    $stmt_mg->bind_param("ss", $mg_start, $end_date);
    $stmt_mg->execute();
    $member_growth_raw = $stmt_mg->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Format data agar bulan yang kosong tetap muncul (0)
    $member_growth = [];
    $period_mg = new DatePeriod(new DateTime($mg_start), new DateInterval('P1M'), (new DateTime($end_date))->modify('+1 day'));
    foreach ($period_mg as $dt) {
        $m = $dt->format('Y-m');
        $val = 0;
        foreach ($member_growth_raw as $row) {
            if ($row['bulan'] == $m) { $val = (int)$row['total']; break; }
        }
        $member_growth[] = ['bulan' => $dt->format('M Y'), 'total' => $val];
    }

    // 9. Top 5 Borrowers (Debitur)
    $stmt_top_borrowers = $db->prepare("
        SELECT a.nama_lengkap, a.nomor_anggota,
               SUM(p.jumlah_pinjaman - COALESCE((SELECT SUM(pokok_terbayar) FROM ksp_angsuran WHERE pinjaman_id = p.id AND tanggal_bayar <= ?), 0)) as total_outstanding
        FROM ksp_pinjaman p
        JOIN anggota a ON p.anggota_id = a.id
        WHERE p.status = 'aktif' AND p.tanggal_pencairan <= ?
        GROUP BY p.anggota_id
        HAVING total_outstanding > 0
        ORDER BY total_outstanding DESC
        LIMIT 5
    ");
    $stmt_top_borrowers->bind_param("ss", $end_date, $end_date);
    $stmt_top_borrowers->execute();
    $top_borrowers = $stmt_top_borrowers->get_result()->fetch_all(MYSQLI_ASSOC);

    // 10. Income Trend (Bunga & Denda) - Last 6 months relative to end_date
    $inc_start = date('Y-m-01', strtotime("$end_date -5 months"));
    $stmt_income = $db->prepare("
        SELECT DATE_FORMAT(tanggal_bayar, '%Y-%m') as bulan,
               SUM(bunga_terbayar) as total_bunga,
               SUM(denda) as total_denda
        FROM ksp_angsuran
        WHERE tanggal_bayar BETWEEN ? AND ?
        GROUP BY bulan
        ORDER BY bulan ASC
    ");
    $stmt_income->bind_param("ss", $inc_start, $end_date);
    $stmt_income->execute();
    $income_trend_raw = $stmt_income->get_result()->fetch_all(MYSQLI_ASSOC);

    // Format Income Data (ensure all months are present)
    $income_trend = [];
    $period_inc = new DatePeriod(new DateTime($inc_start), new DateInterval('P1M'), (new DateTime($end_date))->modify('+1 day'));
    foreach ($period_inc as $dt) {
        $m = $dt->format('Y-m');
        $bunga = 0; $denda = 0;
        foreach ($income_trend_raw as $row) {
            if ($row['bulan'] == $m) { $bunga = (float)$row['total_bunga']; $denda = (float)$row['total_denda']; break; }
        }
        $income_trend[] = ['bulan' => $dt->format('M Y'), 'bunga' => $bunga, 'denda' => $denda];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => [
                'total_simpanan' => $current_stats['total_simpanan'],
                'total_outstanding' => $current_stats['total_outstanding'],
                'total_macet' => $current_stats['total_macet'],
                'prev_total_simpanan' => $prev_stats['total_simpanan'],
                'prev_total_outstanding' => $prev_stats['total_outstanding'],
                'prev_total_macet' => $prev_stats['total_macet'],
                'growth_simpanan' => $growth_simpanan,
                'growth_outstanding' => $growth_outstanding,
                'growth_macet' => $growth_macet
            ],
            'growth' => ['labels' => $labels, 'simpanan' => $data_simpanan, 'pinjaman' => $data_pinjaman],
            'quality' => $quality_counts,
            'savings_comp' => $comp_data,
            'top_savers' => $top_savers,
            'forecast' => [
                'historical_labels' => $hist_months,
                'historical_data' => $hist_net_flow,
                'forecast_labels' => $forecast_months,
                'forecast_data' => $forecast_values
            ],
            'loan_portfolio' => $loan_portfolio,
            'member_growth' => $member_growth,
            'top_borrowers' => $top_borrowers,
            'income_trend' => $income_trend
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function get_ksp_summary_stats($db, $date) {
    // Total Simpanan (Kredit - Debit) per Date
    $stmt_sim = $db->prepare("SELECT SUM(kredit - debit) as total FROM ksp_transaksi_simpanan WHERE tanggal <= ?");
    $stmt_sim->bind_param("s", $date);
    $stmt_sim->execute();
    $total_simpanan = (float)($stmt_sim->get_result()->fetch_assoc()['total'] ?? 0);

    // Total Outstanding Pinjaman (Sisa Pokok) per Date
    $stmt_pinj_cair = $db->prepare("SELECT SUM(jumlah_pinjaman) as total FROM ksp_pinjaman WHERE tanggal_pencairan <= ? AND status != 'ditolak'");
    $stmt_pinj_cair->bind_param("s", $date);
    $stmt_pinj_cair->execute();
    $total_cair = (float)($stmt_pinj_cair->get_result()->fetch_assoc()['total'] ?? 0);

    $stmt_pinj_bayar = $db->prepare("SELECT SUM(pokok_terbayar) as total FROM ksp_angsuran WHERE tanggal_bayar <= ?");
    $stmt_pinj_bayar->bind_param("s", $date);
    $stmt_pinj_bayar->execute();
    $total_bayar_pokok = (float)($stmt_pinj_bayar->get_result()->fetch_assoc()['total'] ?? 0);

    $total_outstanding = $total_cair - $total_bayar_pokok;

    // Total Macet (NPL) - Approximation
    // Calculate outstanding balance for loans that have at least one installment overdue > 90 days
    $stmt_macet = $db->prepare("
        SELECT SUM(outstanding) as nilai_macet FROM (
            SELECT 
                (p.jumlah_pinjaman - COALESCE((SELECT SUM(pokok_terbayar) FROM ksp_angsuran WHERE pinjaman_id = p.id AND tanggal_bayar <= ?), 0)) as outstanding
            FROM ksp_pinjaman p
            JOIN ksp_angsuran a ON p.id = a.pinjaman_id
            WHERE p.tanggal_pencairan <= ? AND p.status != 'ditolak'
            AND (a.tanggal_bayar IS NULL OR a.tanggal_bayar > ?) 
            AND DATEDIFF(?, a.tanggal_jatuh_tempo) > 90
            GROUP BY p.id
        ) as sub
    ");
    $stmt_macet->bind_param("ssss", $date, $date, $date, $date);
    $stmt_macet->execute();
    $total_macet = (float)($stmt_macet->get_result()->fetch_assoc()['nilai_macet'] ?? 0);

    return compact('total_simpanan', 'total_outstanding', 'total_macet');
}

function calculate_growth($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return (($current - $previous) / $previous) * 100;
}