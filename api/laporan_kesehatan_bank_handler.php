<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();

// Helper function (bisa dipindahkan ke file terpusat jika sudah ada)
if (!function_exists('get_ksp_summary_stats')) {
    function get_ksp_summary_stats($db, $date) {
        $stmt_sim = $db->prepare("SELECT SUM(kredit - debit) as total FROM ksp_transaksi_simpanan WHERE tanggal <= ?");
        $stmt_sim->bind_param("s", $date); $stmt_sim->execute();
        $total_simpanan = (float)($stmt_sim->get_result()->fetch_assoc()['total'] ?? 0);

        $stmt_pinj_cair = $db->prepare("SELECT SUM(jumlah_pinjaman) as total FROM ksp_pinjaman WHERE tanggal_pencairan <= ? AND status != 'ditolak'");
        $stmt_pinj_cair->bind_param("s", $date); $stmt_pinj_cair->execute();
        $total_cair = (float)($stmt_pinj_cair->get_result()->fetch_assoc()['total'] ?? 0);

        $stmt_pinj_bayar = $db->prepare("SELECT SUM(pokok_terbayar) as total FROM ksp_angsuran WHERE tanggal_bayar <= ?");
        $stmt_pinj_bayar->bind_param("s", $date); $stmt_pinj_bayar->execute();
        $total_bayar_pokok = (float)($stmt_pinj_bayar->get_result()->fetch_assoc()['total'] ?? 0);
        $total_outstanding = $total_cair - $total_bayar_pokok;

        $stmt_macet = $db->prepare("
            SELECT SUM(outstanding) as nilai_macet FROM (
                SELECT (p.jumlah_pinjaman - COALESCE((SELECT SUM(pokok_terbayar) FROM ksp_angsuran WHERE pinjaman_id = p.id AND tanggal_bayar <= ?), 0)) as outstanding
                FROM ksp_pinjaman p
                JOIN ksp_angsuran a ON p.id = a.pinjaman_id
                WHERE p.tanggal_pencairan <= ? AND p.status != 'ditolak' AND (a.tanggal_bayar IS NULL OR a.tanggal_bayar > ?) AND DATEDIFF(?, a.tanggal_jatuh_tempo) > 90
                GROUP BY p.id
            ) as sub
        ");
        $stmt_macet->bind_param("ssss", $date, $date, $date, $date); $stmt_macet->execute();
        $total_macet = (float)($stmt_macet->get_result()->fetch_assoc()['nilai_macet'] ?? 0);

        return compact('total_simpanan', 'total_outstanding', 'total_macet');
    }
}

function get_financial_data_for_date($db, $date) {
    // Laba Bersih (Pendapatan - Beban)
    $stmt_lr = $db->prepare("SELECT (SELECT SUM(kredit - debit) FROM general_ledger WHERE account_id IN (SELECT id FROM accounts WHERE tipe_akun = 'Pendapatan') AND tanggal <= ?) as total_pendapatan, (SELECT SUM(debit - kredit) FROM general_ledger WHERE account_id IN (SELECT id FROM accounts WHERE tipe_akun = 'Beban') AND tanggal <= ?) as total_beban");
    $stmt_lr->bind_param("ss", $date, $date); $stmt_lr->execute(); $lr = $stmt_lr->get_result()->fetch_assoc();
    $laba_bersih = (float)($lr['total_pendapatan'] ?? 0) - (float)($lr['total_beban'] ?? 0);

    // Total Aset & Ekuitas
    $stmt_neraca = $db->prepare("SELECT (SELECT SUM(debit - kredit) FROM general_ledger WHERE account_id IN (SELECT id FROM accounts WHERE tipe_akun = 'Aset') AND tanggal <= ?) as total_aset, (SELECT SUM(kredit - debit) FROM general_ledger WHERE account_id IN (SELECT id FROM accounts WHERE tipe_akun = 'Ekuitas') AND tanggal <= ?) as total_ekuitas");
    $stmt_neraca->bind_param("ss", $date, $date); $stmt_neraca->execute(); $neraca = $stmt_neraca->get_result()->fetch_assoc();
    $total_aset = (float)($neraca['total_aset'] ?? 0);
    $total_ekuitas = (float)($neraca['total_ekuitas'] ?? 0);

    // Data Simpan Pinjam
    $ksp_stats = get_ksp_summary_stats($db, $date);

    // Pendapatan & Beban Bunga
    $stmt_bunga = $db->prepare("SELECT (SELECT SUM(bunga_terbayar) FROM ksp_angsuran WHERE tanggal_bayar <= ?) as pendapatan_bunga, 0 as beban_bunga");
    $stmt_bunga->bind_param("s", $date); $stmt_bunga->execute(); $bunga = $stmt_bunga->get_result()->fetch_assoc();
    $pendapatan_bunga = (float)($bunga['pendapatan_bunga'] ?? 0);
    $beban_bunga = (float)($bunga['beban_bunga'] ?? 0);

    return compact('laba_bersih', 'total_aset', 'total_ekuitas', 'ksp_stats', 'pendapatan_bunga', 'beban_bunga', 'lr');
}

function calculate_ratios($data) {
    extract($data); // Extracts laba_bersih, total_aset, etc.
    $total_pinjaman = $ksp_stats['total_outstanding']; $total_simpanan = $ksp_stats['total_simpanan']; $total_macet = $ksp_stats['total_macet'];
    $total_pendapatan = $lr['total_pendapatan'] ?? 0;
    $total_beban = $lr['total_beban'] ?? 0;

    $roa = ($total_aset > 0) ? ($laba_bersih / $total_aset) * 100 : 0;
    $roe = ($total_ekuitas > 0) ? ($laba_bersih / $total_ekuitas) * 100 : 0;
    $nim = ($total_pinjaman > 0) ? (($pendapatan_bunga - $beban_bunga) / $total_pinjaman) * 100 : 0;
    $bopo = ($total_pendapatan > 0) ? ($total_beban / $total_pendapatan) * 100 : 0;
    $ldr = ($total_simpanan > 0) ? ($total_pinjaman / $total_simpanan) * 100 : 0;
    $npl = ($total_pinjaman > 0) ? ($total_macet / $total_pinjaman) * 100 : 0;
    $car_proxy = ($total_aset > 0) ? ($total_ekuitas / $total_aset) * 100 : 0;

    // DuPont Components
    $profit_margin = ($total_pendapatan > 0) ? ($laba_bersih / $total_pendapatan) : 0;
    $asset_turnover = ($total_aset > 0) ? ($total_pendapatan / $total_aset) : 0;
    $financial_leverage = ($total_ekuitas > 0) ? ($total_aset / $total_ekuitas) : 0;

    return ['rentabilitas' => ['roa' => $roa, 'roe' => $roe, 'nim' => $nim, 'bopo' => $bopo], 'likuiditas' => ['ldr' => $ldr], 'permodalan' => ['car' => $car_proxy], 'kualitas_aset' => ['npl' => $npl], 'dupont' => ['profit_margin' => $profit_margin, 'asset_turnover' => $asset_turnover, 'financial_leverage' => $financial_leverage]];
}

try {
    $date = !empty($_GET['date']) ? $_GET['date'] : date('Y-m-d');

    // --- Historical Trend Data (Last 6 months) ---
    $historical_trends = [];
    $trend_labels = [];
    for ($i = 5; $i >= 0; $i--) {
        $trend_date_obj = new DateTime($date);
        $trend_date_obj->modify("-$i months");
        $trend_date = $trend_date_obj->format('Y-m-t'); // End of month
        $trend_labels[] = $trend_date_obj->format('M Y');

        // Get data for this historical point
        $stmt_lr_hist = $db->prepare("SELECT (SELECT SUM(kredit - debit) FROM general_ledger WHERE account_id IN (SELECT id FROM accounts WHERE tipe_akun = 'Pendapatan') AND tanggal <= ?) as p, (SELECT SUM(debit - kredit) FROM general_ledger WHERE account_id IN (SELECT id FROM accounts WHERE tipe_akun = 'Beban') AND tanggal <= ?) as b");
        $stmt_lr_hist->bind_param("ss", $trend_date, $trend_date); $stmt_lr_hist->execute(); $lr_hist = $stmt_lr_hist->get_result()->fetch_assoc();
        $laba_bersih_hist = (float)($lr_hist['p'] ?? 0) - (float)($lr_hist['b'] ?? 0);

        $stmt_neraca_hist = $db->prepare("SELECT (SELECT SUM(debit - kredit) FROM general_ledger WHERE account_id IN (SELECT id FROM accounts WHERE tipe_akun = 'Aset') AND tanggal <= ?) as a, (SELECT SUM(kredit - debit) FROM general_ledger WHERE account_id IN (SELECT id FROM accounts WHERE tipe_akun = 'Ekuitas') AND tanggal <= ?) as e");
        $stmt_neraca_hist->bind_param("ss", $trend_date, $trend_date); $stmt_neraca_hist->execute(); $neraca_hist = $stmt_neraca_hist->get_result()->fetch_assoc();
        $total_aset_hist = (float)($neraca_hist['a'] ?? 0);
        $total_ekuitas_hist = (float)($neraca_hist['e'] ?? 0);

        $ksp_stats_hist = get_ksp_summary_stats($db, $trend_date);

        $stmt_bunga_hist = $db->prepare("SELECT SUM(bunga_terbayar) as pendapatan_bunga FROM ksp_angsuran WHERE tanggal_bayar <= ?");
        $stmt_bunga_hist->bind_param("s", $trend_date); $stmt_bunga_hist->execute();
        $pendapatan_bunga_hist = (float)($stmt_bunga_hist->get_result()->fetch_assoc()['pendapatan_bunga'] ?? 0);

        // Calculate ratios for this historical point
        $historical_trends['roa'][] = ($total_aset_hist > 0) ? ($laba_bersih_hist / $total_aset_hist) * 100 : 0;
        $historical_trends['roe'][] = ($total_ekuitas_hist > 0) ? ($laba_bersih_hist / $total_ekuitas_hist) * 100 : 0;
        $historical_trends['nim'][] = ($ksp_stats_hist['total_outstanding'] > 0) ? ($pendapatan_bunga_hist / $ksp_stats_hist['total_outstanding']) * 100 : 0;
        $historical_trends['bopo'][] = (($lr_hist['p'] ?? 0) > 0) ? (($lr_hist['b'] ?? 0) / ($lr_hist['p'] ?? 1)) * 100 : 0;
        $historical_trends['ldr'][] = ($ksp_stats_hist['total_simpanan'] > 0) ? ($ksp_stats_hist['total_outstanding'] / $ksp_stats_hist['total_simpanan']) * 100 : 0;
        $historical_trends['npl'][] = ($ksp_stats_hist['total_outstanding'] > 0) ? ($ksp_stats_hist['total_macet'] / $ksp_stats_hist['total_outstanding']) * 100 : 0;
        $historical_trends['car'][] = ($total_aset_hist > 0) ? ($total_ekuitas_hist / $total_aset_hist) * 100 : 0;
    }
    $historical_trends['labels'] = $trend_labels;

    // --- Get Current and Previous Data ---
    $compare_type = $_GET['compare_type'] ?? 'mom';
    $comp_date_obj = new DateTime($date);
    if ($compare_type === 'yoy') {
        $comp_date_obj->modify('-1 year');
    } else {
        $comp_date_obj->modify('-1 month');
    }
    $comp_date = $comp_date_obj->format('Y-m-d');

    $current_ratios = calculate_ratios(get_financial_data_for_date($db, $date));
    $prev_ratios = calculate_ratios(get_financial_data_for_date($db, $comp_date));

    echo json_encode([
        'success' => true,
        'data' => [
            'current' => $current_ratios,
            'previous' => $prev_ratios,
            'historical_trends' => $historical_trends
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}