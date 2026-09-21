<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../includes/bootstrap.php';
// Muat kelas-kelas yang diperlukan karena kita menggunakan ReportBuilder
require_once PROJECT_ROOT . '/includes/PDF.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/ReportBuilderInterface.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/NeracaReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/LabaRugiReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/ArusKasReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/LaporanHarianReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/DaftarJurnalReportBuilder.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/LaporanLabaDitahanReportBuilder.php';
require_once PROJECT_ROOT . '/includes/Repositories/LaporanRepository.php';
require_once PROJECT_ROOT . '/includes/ReportBuilders/PertumbuhanLabaReportBuilder.php';

$conn = Database::getInstance()->getConnection();
$user_id = 1; // ID Pemilik Data (Toko)

$report_type = $_GET['report'] ?? '';
$format = $_GET['format'] ?? 'csv';

if ($format !== 'csv') {
    http_response_code(400);
    die('Format tidak didukung.');
}

$filename = "laporan_{$report_type}_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

try {
    switch ($report_type) {
        case 'neraca':
            $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
            if (empty($tanggal)) $tanggal = date('Y-m-d');
            $tanggal = date('Y-m-d', strtotime($tanggal)); // Normalisasi format tanggal
            $include_closing = filter_var($_GET['include_closing'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $repo = new LaporanRepository($conn);
            $data = $repo->getNeracaDataWithProfitLoss($user_id, $tanggal, $include_closing);

            fputcsv($output, ['Laporan Posisi Keuangan (Neraca)']);
            fputcsv($output, ['Per Tanggal:', date('d-m-Y', strtotime($tanggal))]);
            fputcsv($output, []); // Baris kosong

            fputcsv($output, ['Tipe Akun', 'Nama Akun', 'Saldo Akhir']);

            $asetData = array_filter($data, fn($d) => $d['tipe_akun'] === 'Aset');
            $liabilitasData = array_filter($data, fn($d) => $d['tipe_akun'] === 'Liabilitas');
            $ekuitasData = array_filter($data, fn($d) => $d['tipe_akun'] === 'Ekuitas');

            $totalAset = 0;
            foreach ($asetData as $item) {
                fputcsv($output, ['Aset', $item['nama_akun'], $item['saldo_akhir']]);
                $totalAset += $item['saldo_akhir'];
            }
            fputcsv($output, ['TOTAL ASET', '', $totalAset]);
            fputcsv($output, []);

            $totalLiabilitas = 0;
            foreach ($liabilitasData as $item) {
                fputcsv($output, ['Liabilitas', $item['nama_akun'], $item['saldo_akhir']]);
                $totalLiabilitas += $item['saldo_akhir'];
            }
            fputcsv($output, ['TOTAL LIABILITAS', '', $totalLiabilitas]);
            fputcsv($output, []);

            $totalEkuitas = 0;
            foreach ($ekuitasData as $item) {
                fputcsv($output, ['Ekuitas', $item['nama_akun'], $item['saldo_akhir']]);
                $totalEkuitas += $item['saldo_akhir'];
            }
            fputcsv($output, ['TOTAL EKUITAS', '', $totalEkuitas]);
            fputcsv($output, []);

            fputcsv($output, ['TOTAL LIABILITAS DAN EKUITAS', '', $totalLiabilitas + $totalEkuitas]);
            break;

        case 'laba-rugi':
            $start = $_GET['start'] ?? date('Y-m-01');
            $end = $_GET['end'] ?? date('Y-m-t');
            $repo = new LaporanRepository($conn);
            $data = $repo->getLabaRugiData($user_id, $start, $end);
            fputcsv($output, ['Laporan Laba Rugi']);
            fputcsv($output, ['Periode:', date('d-m-Y', strtotime($start)) . ' - ' . date('d-m-Y', strtotime($end))]);
            fputcsv($output, []);

            fputcsv($output, ['Kategori', 'Nama Akun', 'Total']);
            foreach ($data['pendapatan'] as $item) {
                fputcsv($output, ['Pendapatan', $item['nama_akun'], $item['total']]);
            }
            fputcsv($output, ['TOTAL PENDAPATAN', '', $data['summary']['total_pendapatan']]);
            fputcsv($output, []);

            foreach ($data['beban'] as $item) {
                fputcsv($output, ['Beban', $item['nama_akun'], $item['total']]);
            }
            fputcsv($output, ['TOTAL BEBAN', '', $data['summary']['total_beban']]);
            fputcsv($output, []);

            fputcsv($output, ['LABA (RUGI) BERSIH', '', $data['summary']['laba_bersih']]);
            break;

        case 'arus-kas':
            $start = $_GET['start'] ?? date('Y-m-01');
            $end = $_GET['end'] ?? date('Y-m-t');
            
            $repo = new LaporanRepository($conn);
            $raw_data = $repo->getArusKasData($user_id, $start, $end);
            
            // Proses data mentah menjadi format yang siap dirender, sama seperti di ArusKasReportBuilder
            $builder = new ArusKasReportBuilder(new PDF(), $conn, []);
            $reflection = new ReflectionClass($builder);
            $method = $reflection->getMethod('processData');
            $method->setAccessible(true);
            $data = $method->invoke($builder, $raw_data);

            fputcsv($output, ['Laporan Arus Kas']);
            fputcsv($output, ['Periode:', date('d-m-Y', strtotime($start)) . ' - ' . date('d-m-Y', strtotime($end))]);
            fputcsv($output, []);

            fputcsv($output, ['Kategori', 'Keterangan', 'Jumlah']);
            fputcsv($output, ['Arus Kas dari Aktivitas Operasi']);
            if (!empty($data['arus_kas_operasi']['details'])) {
                foreach ($data['arus_kas_operasi']['details'] as $keterangan => $jumlah) {
                    fputcsv($output, ['', $keterangan, $jumlah]);
                }
            }
            fputcsv($output, ['Total Arus Kas Operasi', '', $data['arus_kas_operasi']['total']]);
            fputcsv($output, []);

            fputcsv($output, ['Arus Kas dari Aktivitas Investasi']);
            if (!empty($data['arus_kas_investasi']['details'])) {
                foreach ($data['arus_kas_investasi']['details'] as $keterangan => $jumlah) {
                    fputcsv($output, ['', $keterangan, $jumlah]);
                }
            }
            fputcsv($output, ['Total Arus Kas Investasi', '', $data['arus_kas_investasi']['total']]);
            fputcsv($output, []);

            fputcsv($output, ['Arus Kas dari Aktivitas Pendanaan']);
            if (!empty($data['arus_kas_pendanaan']['details'])) {
                foreach ($data['arus_kas_pendanaan']['details'] as $keterangan => $jumlah) {
                    fputcsv($output, ['', $keterangan, $jumlah]);
                }
            }
            fputcsv($output, ['Total Arus Kas Pendanaan', '', $data['arus_kas_pendanaan']['total']]);
            fputcsv($output, []);

            fputcsv($output, ['Kenaikan (Penurunan) Bersih Kas', '', $data['kenaikan_penurunan_kas']]);
            fputcsv($output, ['Saldo Kas pada Awal Periode', '', $raw_data['saldo_kas_awal']]);
            fputcsv($output, ['Saldo Kas pada Akhir Periode', '', $data['saldo_kas_akhir_terhitung']]);
            break;

        case 'laporan-harian':
            $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
            $repo = new LaporanRepository($conn);
            $data = $repo->getLaporanHarianData($user_id, $tanggal);

            fputcsv($output, ['Laporan Transaksi Harian']);
            fputcsv($output, ['Tanggal:', date('d-m-Y', strtotime($tanggal))]);
            fputcsv($output, []);

            fputcsv($output, ['Saldo Awal Hari Ini', $data['saldo_awal']]);
            fputcsv($output, []);

            fputcsv($output, ['ID/Ref', 'Keterangan', 'Akun Terkait', 'Pemasukan', 'Pengeluaran']);

            if (empty($data['transaksi'])) {
                fputcsv($output, ['Tidak ada transaksi pada tanggal ini.']);
            } else {
                foreach ($data['transaksi'] as $tx) {
                    $refDisplay = $tx['ref'] ?: strtoupper($tx['source']).'-'.$tx['id'];
                    fputcsv($output, [$refDisplay, $tx['keterangan'], $tx['akun_terkait'], $tx['pemasukan'], $tx['pengeluaran']]);
                }
            }

            fputcsv($output, []);
            fputcsv($output, ['TOTAL', '', '', $data['total_pemasukan'], $data['total_pengeluaran']]);
            fputcsv($output, ['Saldo Akhir Hari Ini', '', '', '', $data['saldo_akhir']]);

            break;

        case 'buku-besar': {
            $account_id = (int)($_GET['account_id'] ?? 0);
            $start_date = $_GET['start_date'] ?? date('Y-m-01');
            $end_date = $_GET['end_date'] ?? date('Y-m-t');

            $repo = new LaporanRepository($conn);
            $data = $repo->getBukuBesarData($user_id, $account_id, $start_date, $end_date);

            fputcsv($output, ['Laporan Buku Besar']);
            fputcsv($output, ['Akun:', $data['account_info']['kode_akun'] . ' - ' . $data['account_info']['nama_akun']]);
            fputcsv($output, ['Periode:', date('d-m-Y', strtotime($start_date)) . ' s/d ' . date('d-m-Y', strtotime($end_date))]);
            fputcsv($output, []);

            fputcsv($output, ['Tanggal', 'Keterangan', 'Debit', 'Kredit', 'Saldo']);
            fputcsv($output, ['Saldo Awal', '', '', '', $data['saldo_awal']]);

            $saldoBerjalan = $data['saldo_awal'];
            $saldoNormal = $data['account_info']['saldo_normal'];

            foreach ($data['transactions'] as $tx) {
                $debit = (float)$tx['debit'];
                $kredit = (float)$tx['kredit'];
                
                if ($saldoNormal === 'Debit') {
                    $saldoBerjalan += $debit - $kredit;
                } else { // Kredit
                    $saldoBerjalan += $kredit - $debit;
                }

                fputcsv($output, [date('d-m-Y', strtotime($tx['tanggal'])), $tx['keterangan'], $debit, $kredit, $saldoBerjalan]);
            }

            fputcsv($output, []);
            fputcsv($output, ['Saldo Akhir', '', '', '', $saldoBerjalan]);

            break;
        }

        case 'daftar-jurnal': {
            $search = $_GET['search'] ?? '';
            $start_date = $_GET['start_date'] ?? '';
            $end_date = $_GET['end_date'] ?? '';

            $repo = new LaporanRepository($conn);
            $data = $repo->getDaftarJurnalData($user_id, $search, $start_date, $end_date);
            fputcsv($output, ['Daftar Entri Jurnal']);
            if (!empty($start_date) && !empty($end_date)) {
                fputcsv($output, ['Periode:', date('d-m-Y', strtotime($start_date)) . ' s/d ' . date('d-m-Y', strtotime($end_date))]);
            }
            fputcsv($output, []);
            fputcsv($output, ['No. Referensi', 'Tanggal', 'Keterangan', 'Akun', 'Debit', 'Kredit']);

            foreach ($data as $line) {
                fputcsv($output, [$line['ref'], $line['tanggal'], $line['keterangan'], $line['nama_akun'], $line['debit'], $line['kredit']]);
            }
            break;
        }

        case 'laporan-laba-ditahan': {
            $start_date = $_GET['start_date'] ?? date('Y-01-01');
            $end_date = $_GET['end_date'] ?? date('Y-m-d');

            $repo = new LaporanRepository($conn);
            $data = $repo->getLabaDitahanData($user_id, $start_date, $end_date);
            fputcsv($output, ['Laporan Perubahan Laba Ditahan']);
            fputcsv($output, ['Periode:', date('d-m-Y', strtotime($start_date)) . ' - ' . date('d-m-Y', strtotime($end_date))]);
            fputcsv($output, []);

            fputcsv($output, ['Tanggal', 'Keterangan', 'Debit', 'Kredit', 'Saldo']);
            fputcsv($output, ['Saldo Awal per ' . date('d-m-Y', strtotime($start_date)), '', '', '', $data['saldo_awal']]);

            $saldoBerjalan = (float)$data['saldo_awal'];
            foreach ($data['transactions'] as $tx) {
                $debit = (float)$tx['debit'];
                $kredit = (float)$tx['kredit'];
                $saldoBerjalan += $kredit - $debit;

                fputcsv($output, [
                    date('d-m-Y', strtotime($tx['tanggal'])),
                    $tx['keterangan'],
                    $debit,
                    $kredit,
                    $saldoBerjalan
                ]);
            }
            fputcsv($output, ['Saldo Akhir per ' . date('d-m-Y', strtotime($end_date)), '', '', '', $saldoBerjalan]);
            break;
        }

        case 'anggaran': {
            $tahun = (int)($_GET['tahun'] ?? date('Y'));
            $bulan = (int)($_GET['bulan'] ?? date('m'));
            $compare = isset($_GET['compare']) && $_GET['compare'] === 'true';
            $tahun_lalu = $tahun - 1;
            $namaBulan = DateTime::createFromFormat('!m', $bulan)->format('F');

            // Gunakan Repository untuk konsistensi data
            $repo = new LaporanRepository($conn);
            $result = $repo->getAnggaranData($user_id, $tahun, $bulan, $compare);
            $data = $result['data'];

            fputcsv($output, ['Laporan Anggaran vs Realisasi']);
            fputcsv($output, ['Periode:', $namaBulan . ' ' . $tahun]);
            if ($compare) {
                fputcsv($output, ['Mode:', 'Perbandingan dengan Tahun ' . $tahun_lalu]);
            }
            fputcsv($output, []);

            if ($compare) {
                fputcsv($output, ['Akun Beban', 'Anggaran ' . $tahun, 'Realisasi ' . $tahun, 'Realisasi ' . $tahun_lalu, 'Penggunaan (%)']);
            } else {
                fputcsv($output, ['Akun Beban', 'Anggaran Bulanan', 'Realisasi Belanja', 'Sisa Anggaran', 'Penggunaan (%)']);
            }

            foreach ($data as $row) {
                $persentase = $row['persentase'];
                if ($compare) {
                    fputcsv($output, [
                        $row['nama_akun'], $row['anggaran_bulanan'], $row['realisasi_belanja'], $row['realisasi_belanja_lalu'], number_format($persentase, 2) . '%'
                    ]);
                } else {
                    fputcsv($output, [
                        $row['nama_akun'], $row['anggaran_bulanan'], $row['realisasi_belanja'], $row['sisa_anggaran'], number_format($persentase, 2) . '%'
                    ]);
                }
            }
            break;
        }

        case 'laporan-pertumbuhan-laba': {
            // Re-use the logic from the Report Builder
            $tahun = (int)($_GET['tahun'] ?? date('Y'));
            $view_mode = $_GET['view_mode'] ?? 'monthly';
            $compare = isset($_GET['compare']) && $_GET['compare'] === 'true';

            $repo = new LaporanRepository($conn);
            $data = $repo->getPertumbuhanLabaData($user_id, $tahun, $view_mode, $compare);
            fputcsv($output, ['Laporan Pertumbuhan Laba']);
            fputcsv($output, ['Tahun:', $tahun, 'Tampilan:', ucfirst($view_mode)]);
            fputcsv($output, []);

            $period_alias = 'bulan';
            if ($view_mode === 'quarterly') {
                $period_alias = 'triwulan';
            } elseif ($view_mode === 'yearly') {
                $period_alias = 'tahun';
            }

            $headers = [ucfirst($period_alias), 'Laba Bersih ' . $tahun];
            if ($compare) $headers[] = 'Laba Bersih ' . $tahun_lalu;
            fputcsv($output, $headers);

            $months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
            $quarters = ["Q1", "Q2", "Q3", "Q4"];

            foreach ($data as $row) {
                $periodName = '';
                if ($view_mode === 'quarterly') $periodName = $quarters[$row['triwulan'] - 1];
                elseif ($view_mode === 'yearly') $periodName = $row['tahun'];
                else $periodName = $months[$row['bulan'] - 1];

                $csv_row = [$periodName, $row['laba_bersih']];
                if ($compare) $csv_row[] = $row['laba_bersih_lalu'];
                fputcsv($output, $csv_row);
            }
            break;
        }

        case 'laporan-piutang': {
            $sql = "
                SELECT 
                    p.customer_id,
                    p.customer_name,
                    a.nomor_anggota,
                    SUM(p.total) as total_kredit,
                    SUM(p.bayar) as total_bayar,
                    SUM(p.total - p.bayar) as sisa_hutang
                FROM penjualan p
                LEFT JOIN anggota a ON p.customer_id = a.id
                WHERE p.payment_method = 'hutang' 
                  AND p.status = 'completed'
                GROUP BY p.customer_id, p.customer_name, a.nomor_anggota
                HAVING sisa_hutang > 0
                ORDER BY p.customer_name ASC
            ";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = stmt_fetch_all($stmt);
            $stmt->close();

            fputcsv($output, ['Laporan Piutang Anggota']);
            fputcsv($output, ['Dicetak pada:', date('d-m-Y H:i')]);
            fputcsv($output, []);

            fputcsv($output, ['No', 'Nama Anggota', 'No. Anggota', 'Total Kredit', 'Sudah Dibayar', 'Sisa Hutang']);
            $no = 1;
            foreach ($result as $row) {
                fputcsv($output, [
                    $no++,
                    $row['customer_name'],
                    $row['nomor_anggota'] ?: '-',
                    $row['total_kredit'],
                    $row['total_bayar'],
                    $row['sisa_hutang']
                ]);
            }
            break;
        }

        case 'laporan-pembelian': {
            $start_date = $_GET['start_date'] ?? '';
            $end_date = $_GET['end_date'] ?? '';
            $supplier_id = $_GET['supplier_id'] ?? '';
            $search = $_GET['search'] ?? '';

            $where_clauses = ['p.user_id = ?'];
            $params = ['i', 1]; // user_id = 1

            if (!empty($start_date)) {
                $where_clauses[] = 'DATE(p.tanggal_pembelian) >= ?';
                $params[0] .= 's';
                $params[] = $start_date;
            }
            if (!empty($end_date)) {
                $where_clauses[] = 'DATE(p.tanggal_pembelian) <= ?';
                $params[0] .= 's';
                $params[] = $end_date;
            }
            if (!empty($supplier_id)) {
                $where_clauses[] = 'p.supplier_id = ?';
                $params[0] .= 'i';
                $params[] = (int)$supplier_id;
            }
            if (!empty($search)) {
                $where_clauses[] = '(s.nama_pemasok LIKE ? OR p.keterangan LIKE ? OR p.nomor_referensi LIKE ?)';
                $params[0] .= 'sss';
                $searchTerm = '%' . $search . '%';
                array_push($params, $searchTerm, $searchTerm, $searchTerm);
            }

            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
            $query = "
                SELECT p.*, s.nama_pemasok FROM pembelian p 
                LEFT JOIN suppliers s ON p.supplier_id = s.id 
                $where_sql ORDER BY p.tanggal_pembelian ASC
            ";
            $stmt = $conn->prepare($query);
            $bind_params = [&$params[0]];
            for ($i = 1; $i < count($params); $i++) { $bind_params[] = &$params[$i]; }
            call_user_func_array([$stmt, 'bind_param'], $bind_params);
            $stmt->execute();
            $result = stmt_fetch_all($stmt);
            $stmt->close();

            fputcsv($output, ['Laporan Pembelian']);
            fputcsv($output, ['Dicetak pada:', date('d-m-Y H:i')]);
            if (!empty($start_date) && !empty($end_date)) {
                fputcsv($output, ['Periode:', $start_date . ' s/d ' . $end_date]);
            }
            fputcsv($output, []);

            fputcsv($output, ['No', 'Tanggal', 'No. Referensi', 'Pemasok', 'Keterangan', 'Metode Pembayaran', 'Status', 'Total']);
            $no = 1;
            foreach ($result as $row) {
                fputcsv($output, [
                    $no++,
                    $row['tanggal_pembelian'],
                    $row['nomor_referensi'],
                    $row['nama_pemasok'] ?: '-',
                    $row['keterangan'],
                    $row['payment_method'],
                    $row['status'],
                    $row['total']
                ]);
            }
            break;
        }

        case 'laporan-stok': {
            $start_date = $_GET['start_date'] ?? '';
            $end_date = $_GET['end_date'] ?? '';
            $query = "
                SELECT 
                    i.nama_barang, i.sku, i.harga_beli,
                    COALESCE(sa.stok_awal, 0) as stok_awal,
                    COALESCE(p.masuk, 0) as masuk,
                    COALESCE(p.keluar, 0) as keluar
                FROM items i
                LEFT JOIN (
                    SELECT item_id, SUM(debit - kredit) as stok_awal
                    FROM kartu_stok
                    WHERE tanggal < ? AND user_id = ?
                    GROUP BY item_id
                ) sa ON i.id = sa.item_id
                LEFT JOIN (
                    SELECT item_id, SUM(debit) as masuk, SUM(kredit) as keluar
                    FROM kartu_stok
                    WHERE tanggal BETWEEN ? AND CONCAT(?, ' 23:59:59') AND user_id = ?
                    GROUP BY item_id
                ) p ON i.id = p.item_id
                WHERE i.user_id = ?
                ORDER BY i.nama_barang ASC
            ";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('sissii', $start_date, $user_id, $start_date, $end_date, $user_id, $user_id);
            $stmt->execute();
            $result = stmt_fetch_all($stmt);
            $stmt->close();

            fputcsv($output, ['Laporan Pergerakan Stok']);
            fputcsv($output, ['Periode:', $start_date . ' s/d ' . $end_date]);
            fputcsv($output, []);
            fputcsv($output, ['Nama Barang', 'SKU', 'Stok Awal', 'Masuk', 'Keluar', 'Stok Akhir', 'Harga Beli', 'Nilai Persediaan']);
            foreach ($result as $row) {
                $stok_akhir = $row['stok_awal'] + $row['masuk'] - $row['keluar'];
                fputcsv($output, [
                    $row['nama_barang'], $row['sku'], $row['stok_awal'], $row['masuk'], $row['keluar'], $stok_akhir, $row['harga_beli'], $stok_akhir * $row['harga_beli']
                ]);
            }
            break;
        }

        case 'laporan-persediaan': {
            $search = $_GET['search'] ?? '';
            $where = "WHERE user_id = ?";
            $params = [$user_id];
            $types = "i";
            if (!empty($search)) {
                $where .= " AND (nama_barang LIKE ? OR sku LIKE ?)";
                $searchTerm = '%' . $search . '%';
                array_push($params, $searchTerm, $searchTerm);
                $types .= "ss";
            }
            $query = "SELECT nama_barang, sku, stok, harga_beli FROM items $where ORDER BY nama_barang ASC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = stmt_fetch_all($stmt);
            $stmt->close();

            fputcsv($output, ['Laporan Nilai Persediaan']);
            fputcsv($output, ['Dicetak pada:', date('d-m-Y H:i')]);
            fputcsv($output, []);
            fputcsv($output, ['Nama Barang', 'SKU', 'Stok', 'Harga Beli', 'Total Nilai']);
            foreach ($result as $row) {
                fputcsv($output, [
                    $row['nama_barang'], $row['sku'], $row['stok'], $row['harga_beli'], $row['stok'] * $row['harga_beli']
                ]);
            }
            break;
        }

        case 'mutasi-konsinyasi': {
            $supplier_id = !empty($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : null;
            $start_date = $_GET['start_date'] ?? '';
            $end_date = $_GET['end_date'] ?? '';

            $where_ci = "WHERE ci.user_id = ?";
            $where_cr = "WHERE cr.user_id = ?";
            $where_gl = "WHERE gl.user_id = ? AND gl.account_id = (SELECT setting_value FROM settings WHERE setting_key = 'consignment_payable_account') AND gl.kredit > 0 AND gl.ref_type IN ('jurnal', 'penjualan')";
            
            $params_ci = [$user_id];
            $params_cr = [$user_id];
            $params_gl = [$user_id];
            $types_ci = "i";
            $types_cr = "i";
            $types_gl = "i";

            if ($supplier_id) {
                $where_ci .= " AND ci.supplier_id = ?";
                $where_cr .= " AND ci.supplier_id = ?";
                $where_gl .= " AND ci.supplier_id = ?";
                $params_ci[] = $supplier_id;
                $params_cr[] = $supplier_id;
                $params_gl[] = $supplier_id;
                $types_ci .= "i";
                $types_cr .= "i";
                $types_gl .= "i";
            }

            if ($start_date) {
                $where_ci .= " AND ci.tanggal_terima >= ?";
                $where_cr .= " AND cr.tanggal >= ?";
                $where_gl .= " AND gl.tanggal >= ?";
                $params_ci[] = $start_date;
                $params_cr[] = $start_date;
                $params_gl[] = $start_date;
                $types_ci .= "s";
                $types_cr .= "s";
                $types_gl .= "s";
            }

            if ($end_date) {
                $where_ci .= " AND ci.tanggal_terima <= ?";
                $where_cr .= " AND cr.tanggal <= ?";
                $where_gl .= " AND gl.tanggal <= ?";
                $params_ci[] = $end_date;
                $params_cr[] = $end_date;
                $params_gl[] = $end_date;
                $types_ci .= "s";
                $types_cr .= "s";
                $types_gl .= "s";
            }

            $query = "
                SELECT * FROM (
                    SELECT ci.tanggal_terima as tanggal, ci.nama_barang, s.nama_pemasok, 'Stok Awal' as tipe, ci.stok_awal as qty, 'Penerimaan awal' as keterangan FROM consignment_items ci JOIN suppliers s ON ci.supplier_id = s.id $where_ci
                    UNION ALL
                    SELECT cr.tanggal, ci.nama_barang, s.nama_pemasok, 'Restock' as tipe, cr.qty, cr.keterangan FROM consignment_restocks cr JOIN consignment_items ci ON cr.consignment_item_id = ci.id JOIN suppliers s ON ci.supplier_id = s.id $where_cr
                    UNION ALL
                    SELECT gl.tanggal, ci.nama_barang, s.nama_pemasok, 'Terjual' as tipe, SUM(gl.qty) as qty, 'Total harian' as keterangan FROM general_ledger gl JOIN consignment_items ci ON gl.consignment_item_id = ci.id JOIN suppliers s ON ci.supplier_id = s.id $where_gl GROUP BY gl.tanggal, ci.id
                ) as combined_mutations ORDER BY tanggal DESC, nama_barang ASC
            ";
            $final_params = array_merge($params_ci, $params_cr, $params_gl);
            $final_types = $types_ci . $types_cr . $types_gl;
            $stmt = $conn->prepare($query);
            if (!empty($final_params)) { $stmt->bind_param($final_types, ...$final_params); }
            $stmt->execute();
            $result = stmt_fetch_all($stmt);
            $stmt->close();

            fputcsv($output, ['Laporan Mutasi Stok Konsinyasi']);
            fputcsv($output, ['Periode:', $start_date . ' s/d ' . $end_date]);
            fputcsv($output, []);
            fputcsv($output, ['No', 'Tanggal', 'Nama Barang', 'Pemasok', 'Tipe', 'Qty', 'Keterangan']);
            $no = 1;
            foreach ($result as $row) {
                fputcsv($output, [ $no++, $row['tanggal'], $row['nama_barang'], $row['nama_pemasok'], $row['tipe'], $row['qty'], $row['keterangan'] ]);
            }
            break;
        }

        case 'laporan-penjualan-item':
        case 'penjualan-item': {
            $start_date = $_GET['start_date'] ?? date('Y-m-01');
            $end_date = $_GET['end_date'] ?? date('Y-m-d');
            $item_type = $_GET['item_type'] ?? 'all';
            $movement_filter = $_GET['movement_filter'] ?? 'with_activity';
            $search = trim($_GET['search'] ?? '');
            $sort_by = $_GET['sort_by'] ?? 'total_margin';
            $sort_order = strtolower($_GET['sort_order'] ?? 'desc');

            // 1. Sales per item
            $sales_sql = "
                SELECT
                    pd.item_id,
                    pd.item_type,
                    MAX(pd.deskripsi_item) as deskripsi_item,
                    SUM(pd.quantity) as qty_terjual,
                    SUM(pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) as total_neto,
                    SUM(pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, ci.harga_beli, 0))) as total_hpp,
                    SUM((pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) - (pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, ci.harga_beli, 0)))) as total_margin
                FROM penjualan_details pd
                JOIN penjualan p ON pd.penjualan_id = p.id
                LEFT JOIN items i ON pd.item_id = i.id AND pd.item_type = 'normal'
                LEFT JOIN consignment_items ci ON pd.item_id = ci.id AND pd.item_type = 'consignment'
                WHERE p.user_id = ? AND DATE(p.tanggal_penjualan) >= ? AND DATE(p.tanggal_penjualan) <= ? AND p.status = 'completed'
                GROUP BY pd.item_id, pd.item_type
            ";
            $stmt_sales = $conn->prepare($sales_sql);
            $stmt_sales->bind_param('iss', $user_id, $start_date, $end_date);
            $stmt_sales->execute();
            $sales_rows = stmt_fetch_all($stmt_sales);
            $stmt_sales->close();

            $sales_by_key = [];
            foreach ($sales_rows as $sr) {
                $sales_by_key[$sr['item_type'] . '_' . $sr['item_id']] = $sr;
            }

            $items_map = [];

            // 2. Normal items
            if ($item_type === 'all' || $item_type === 'normal') {
                $ks_sql = "
                    SELECT 
                        i.id, i.nama_barang, i.sku, i.harga_beli, i.harga_jual,
                        COALESCE(c.nama_kategori, 'Umum') as kategori,
                        COALESCE(sa.stok_awal, 0) as stok_awal,
                        COALESCE(p.masuk, 0) as masuk,
                        COALESCE(p.keluar, 0) as keluar
                    FROM items i
                    LEFT JOIN item_categories c ON i.category_id = c.id
                    LEFT JOIN (
                        SELECT item_id, SUM(debit - kredit) as stok_awal
                        FROM kartu_stok
                        WHERE tanggal < ? AND user_id = ?
                        GROUP BY item_id
                    ) sa ON i.id = sa.item_id
                    LEFT JOIN (
                        SELECT item_id, SUM(debit) as masuk, SUM(kredit) as keluar
                        FROM kartu_stok
                        WHERE tanggal BETWEEN ? AND CONCAT(?, ' 23:59:59') AND user_id = ?
                        GROUP BY item_id
                    ) p ON i.id = p.item_id
                    WHERE i.user_id = ?
                ";
                $stmt_ks = $conn->prepare($ks_sql);
                $stmt_ks->bind_param('sissii', $start_date, $user_id, $start_date, $end_date, $user_id, $user_id);
                $stmt_ks->execute();
                $normal_items = stmt_fetch_all($stmt_ks);
                $stmt_ks->close();

                foreach ($normal_items as $item) {
                    $key = 'normal_' . $item['id'];
                    $sale = $sales_by_key[$key] ?? null;

                    $stok_awal = (int)$item['stok_awal'];
                    $masuk = (int)$item['masuk'];
                    $keluar = (int)$item['keluar'];
                    $stok_akhir = $stok_awal + $masuk - $keluar;

                    $qty_terjual = $sale ? (int)$sale['qty_terjual'] : 0;
                    $total_penjualan = $sale ? (float)$sale['total_neto'] : 0.0;
                    $total_hpp = $sale ? (float)$sale['total_hpp'] : 0.0;
                    $total_margin = $sale ? (float)$sale['total_margin'] : 0.0;
                    $margin_pct = ($total_penjualan > 0) ? ($total_margin / $total_penjualan * 100) : 0.0;

                    $items_map[$key] = [
                        'id' => (int)$item['id'],
                        'item_type' => 'normal',
                        'type_label' => 'Toko',
                        'sku' => $item['sku'] ?? '',
                        'nama_barang' => $item['nama_barang'],
                        'kategori' => $item['kategori'],
                        'stok_awal' => $stok_awal,
                        'masuk' => $masuk,
                        'keluar' => $keluar,
                        'stok_akhir' => $stok_akhir,
                        'qty_terjual' => $qty_terjual,
                        'total_penjualan' => $total_penjualan,
                        'total_hpp' => $total_hpp,
                        'total_margin' => $total_margin,
                        'margin_pct' => $margin_pct
                    ];
                }
            }

            // 3. Consignment items
            if ($item_type === 'all' || $item_type === 'consignment') {
                $cons_payable_acc = null;
                $res_cfg = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'consignment_payable_account' LIMIT 1");
                if ($res_cfg && $r_cfg = $res_cfg->fetch_assoc()) {
                    $cons_payable_acc = $r_cfg['setting_value'];
                }

                $ci_sql = "
                    SELECT 
                        ci.id, ci.nama_barang, ci.sku,
                        COALESCE(s.nama_pemasok, 'Konsinyasi') as kategori,
                        (
                            ci.stok_awal 
                            + COALESCE((SELECT SUM(qty) FROM consignment_restocks WHERE consignment_item_id = ci.id AND tanggal < ?), 0)
                            - COALESCE((SELECT SUM(IF(debit > 0, -qty, qty)) FROM general_ledger WHERE consignment_item_id = ci.id AND ref_type IN ('jurnal', 'penjualan') AND account_id = ? AND DATE(tanggal) < ?), 0)
                        ) as stok_awal,
                        COALESCE((SELECT SUM(qty) FROM consignment_restocks WHERE consignment_item_id = ci.id AND tanggal BETWEEN ? AND ?), 0) as masuk,
                        COALESCE((SELECT SUM(IF(debit > 0, -qty, qty)) FROM general_ledger WHERE consignment_item_id = ci.id AND ref_type IN ('jurnal', 'penjualan') AND account_id = ? AND DATE(tanggal) BETWEEN ? AND ?), 0) as keluar
                    FROM consignment_items ci
                    LEFT JOIN suppliers s ON ci.supplier_id = s.id
                    WHERE ci.user_id = ?
                ";
                $stmt_ci = $conn->prepare($ci_sql);
                $stmt_ci->bind_param('sississsi', $start_date, $cons_payable_acc, $start_date, $start_date, $end_date, $cons_payable_acc, $start_date, $end_date, $user_id);
                $stmt_ci->execute();
                $cons_items = stmt_fetch_all($stmt_ci);
                $stmt_ci->close();

                foreach ($cons_items as $item) {
                    $key = 'consignment_' . $item['id'];
                    $sale = $sales_by_key[$key] ?? null;

                    $stok_awal = (int)$item['stok_awal'];
                    $masuk = (int)$item['masuk'];
                    $keluar = (int)$item['keluar'];
                    $stok_akhir = $stok_awal + $masuk - $keluar;

                    $qty_terjual = $sale ? (int)$sale['qty_terjual'] : 0;
                    $total_penjualan = $sale ? (float)$sale['total_neto'] : 0.0;
                    $total_hpp = $sale ? (float)$sale['total_hpp'] : 0.0;
                    $total_margin = $sale ? (float)$sale['total_margin'] : 0.0;
                    $margin_pct = ($total_penjualan > 0) ? ($total_margin / $total_penjualan * 100) : 0.0;

                    $items_map[$key] = [
                        'id' => (int)$item['id'],
                        'item_type' => 'consignment',
                        'type_label' => 'Konsinyasi',
                        'sku' => $item['sku'] ?? '',
                        'nama_barang' => $item['nama_barang'],
                        'kategori' => $item['kategori'],
                        'stok_awal' => $stok_awal,
                        'masuk' => $masuk,
                        'keluar' => $keluar,
                        'stok_akhir' => $stok_akhir,
                        'qty_terjual' => $qty_terjual,
                        'total_penjualan' => $total_penjualan,
                        'total_hpp' => $total_hpp,
                        'total_margin' => $total_margin,
                        'margin_pct' => $margin_pct
                    ];
                }
            }

            // Fallback for items with sales that were deleted from master
            foreach ($sales_by_key as $key => $sale) {
                if (!isset($items_map[$key])) {
                    $total_penjualan = (float)$sale['total_neto'];
                    $total_hpp = (float)$sale['total_hpp'];
                    $total_margin = (float)$sale['total_margin'];
                    $qty_terjual = (int)$sale['qty_terjual'];
                    $margin_pct = ($total_penjualan > 0) ? ($total_margin / $total_penjualan * 100) : 0.0;

                    $items_map[$key] = [
                        'id' => (int)$sale['item_id'],
                        'item_type' => $sale['item_type'],
                        'type_label' => ($sale['item_type'] === 'consignment' ? 'Konsinyasi' : 'Toko'),
                        'sku' => '-',
                        'nama_barang' => !empty($sale['deskripsi_item']) ? $sale['deskripsi_item'] : ('Item #' . $sale['item_id'] . ' (Non-Master)'),
                        'kategori' => 'Lainnya',
                        'stok_awal' => 0,
                        'masuk' => 0,
                        'keluar' => $qty_terjual,
                        'stok_akhir' => 0,
                        'qty_terjual' => $qty_terjual,
                        'total_penjualan' => $total_penjualan,
                        'total_hpp' => $total_hpp,
                        'total_margin' => $total_margin,
                        'margin_pct' => $margin_pct
                    ];
                }
            }

            // 4. Filtering
            $filtered = [];
            foreach ($items_map as $item) {
                if (!empty($search)) {
                    $s = mb_strtolower($search);
                    if (strpos(mb_strtolower($item['nama_barang']), $s) === false && strpos(mb_strtolower($item['sku']), $s) === false) {
                        continue;
                    }
                }

                if ($movement_filter === 'with_sales') {
                    if ($item['qty_terjual'] <= 0) continue;
                } elseif ($movement_filter === 'with_activity') {
                    if ($item['qty_terjual'] <= 0 && $item['masuk'] <= 0 && $item['keluar'] <= 0 && $item['stok_awal'] == 0 && $item['stok_akhir'] == 0) {
                        continue;
                    }
                }

                $filtered[] = $item;
            }

            // 5. Sorting
            usort($filtered, function ($a, $b) use ($sort_by, $sort_order) {
                $valA = $a[$sort_by] ?? null;
                $valB = $b[$sort_by] ?? null;
                if (is_string($valA)) {
                    $cmp = strcasecmp($valA, $valB);
                } else {
                    if ($valA == $valB) $cmp = 0;
                    else $cmp = ($valA < $valB) ? -1 : 1;
                }
                return ($sort_order === 'asc') ? $cmp : -$cmp;
            });

            fputcsv($output, ['Laporan Penjualan & Margin Stok per Item']);
            fputcsv($output, ['Periode:', date('d-m-Y', strtotime($start_date)) . ' s/d ' . date('d-m-Y', strtotime($end_date))]);
            fputcsv($output, ['Jenis:', strtoupper($item_type), 'Filter:', $movement_filter]);
            fputcsv($output, []);

            fputcsv($output, [
                'No', 'SKU', 'Nama Barang', 'Kategori', 'Tipe',
                'Stok Awal', 'Masuk', 'Keluar', 'Stok Akhir',
                'Qty Terjual', 'Penjualan Bersih (Rp)', 'Total HPP (Rp)', 'Total Margin (Rp)', 'Margin %'
            ]);

            $no = 1;
            $tot_masuk = 0;
            $tot_keluar = 0;
            $tot_terjual = 0;
            $tot_penjualan = 0;
            $tot_hpp = 0;
            $tot_margin = 0;

            foreach ($filtered as $row) {
                $tot_masuk += (int)$row['masuk'];
                $tot_keluar += (int)$row['keluar'];
                $tot_terjual += (int)$row['qty_terjual'];
                $tot_penjualan += (float)$row['total_penjualan'];
                $tot_hpp += (float)$row['total_hpp'];
                $tot_margin += (float)$row['total_margin'];

                fputcsv($output, [
                    $no++,
                    $row['sku'],
                    $row['nama_barang'],
                    $row['kategori'],
                    $row['type_label'],
                    $row['stok_awal'],
                    $row['masuk'],
                    $row['keluar'],
                    $row['stok_akhir'],
                    $row['qty_terjual'],
                    $row['total_penjualan'],
                    $row['total_hpp'],
                    $row['total_margin'],
                    round($row['margin_pct'], 2) . '%'
                ]);
            }

            $tot_pct = ($tot_penjualan > 0) ? ($tot_margin / $tot_penjualan * 100) : 0;
            fputcsv($output, []);
            fputcsv($output, [
                'TOTAL', '', '', '', '',
                '', $tot_masuk, $tot_keluar, '',
                $tot_terjual, $tot_penjualan, $tot_hpp, $tot_margin, round($tot_pct, 2) . '%'
            ]);
            break;
        }

        case 'laporan-margin-kategori': {
            $start_date = $_GET['start_date'] ?? date('Y-m-01');
            $end_date = $_GET['end_date'] ?? date('Y-m-d');
            $sort_by = $_GET['sort_by'] ?? 'total_neto';
            $sort_order = strtolower($_GET['sort_order'] ?? 'desc');

            $sql = "
                SELECT 
                    CASE 
                        WHEN pd.item_type = 'consignment' THEN 'Barang Konsinyasi'
                        WHEN ic.nama_kategori IS NOT NULL THEN ic.nama_kategori
                        ELSE 'Tanpa Kategori / Umum'
                    END AS kategori_nama,
                    COUNT(DISTINCT pd.item_id) as total_sku,
                    SUM(pd.quantity) as total_qty,
                    SUM(pd.subtotal) as total_bruto,
                    SUM(pd.subtotal / NULLIF(p.subtotal, 0) * p.discount) as total_diskon,
                    SUM(pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) as total_neto,
                    SUM(pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, ci.harga_beli, 0))) as total_hpp,
                    SUM((pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) - (pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, ci.harga_beli, 0)))) as total_margin
                FROM penjualan_details pd
                JOIN penjualan p ON pd.penjualan_id = p.id
                LEFT JOIN items i ON pd.item_id = i.id AND pd.item_type = 'normal'
                LEFT JOIN item_categories ic ON i.category_id = ic.id
                LEFT JOIN consignment_items ci ON pd.item_id = ci.id AND pd.item_type = 'consignment'
                WHERE p.user_id = ? 
                  AND DATE(p.tanggal_penjualan) >= ? 
                  AND DATE(p.tanggal_penjualan) <= ?
                  AND p.status = 'completed'
                GROUP BY kategori_nama
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iss', $user_id, $start_date, $end_date);
            $stmt->execute();
            $rows = stmt_fetch_all($stmt);
            $stmt->close();

            $grand_neto = 0.0;
            $grand_margin = 0.0;
            foreach ($rows as $r) {
                $grand_neto += (float)$r['total_neto'];
                $grand_margin += (float)$r['total_margin'];
            }

            $data = [];
            foreach ($rows as $r) {
                $neto = (float)$r['total_neto'];
                $hpp = (float)$r['total_hpp'];
                $margin = (float)$r['total_margin'];
                $bruto = (float)$r['total_bruto'];
                $diskon = (float)$r['total_diskon'];
                $qty = (int)$r['total_qty'];
                $sku = (int)$r['total_sku'];

                $kontribusi_sales = $grand_neto > 0 ? ($neto / $grand_neto * 100) : 0.0;
                $kontribusi_margin = $grand_margin > 0 ? ($margin / $grand_margin * 100) : 0.0;
                $margin_pct = $neto > 0 ? ($margin / $neto * 100) : 0.0;

                $data[] = [
                    'kategori_nama' => $r['kategori_nama'],
                    'total_sku' => $sku,
                    'total_qty' => $qty,
                    'total_bruto' => $bruto,
                    'total_diskon' => $diskon,
                    'total_neto' => $neto,
                    'kontribusi_sales_pct' => round($kontribusi_sales, 2),
                    'total_hpp' => $hpp,
                    'total_margin' => $margin,
                    'kontribusi_margin_pct' => round($kontribusi_margin, 2),
                    'margin_pct' => round($margin_pct, 2)
                ];
            }

            usort($data, function ($a, $b) use ($sort_by, $sort_order) {
                $valA = $a[$sort_by] ?? null;
                $valB = $b[$sort_by] ?? null;

                if (is_string($valA)) {
                    $cmp = strcasecmp($valA, $valB);
                } else {
                    if ($valA == $valB) $cmp = 0;
                    else $cmp = ($valA < $valB) ? -1 : 1;
                }
                return ($sort_order === 'asc') ? $cmp : -$cmp;
            });

            fputcsv($output, ['Laporan Kontribusi & Margin per Kategori Barang']);
            fputcsv($output, ['Periode:', date('d-m-Y', strtotime($start_date)) . ' s/d ' . date('d-m-Y', strtotime($end_date))]);
            fputcsv($output, []);

            fputcsv($output, [
                'No', 'Kategori Barang', 'Ragam SKU', 'Qty Terjual',
                'Omset Bruto (Rp)', 'Diskon Nota (Rp)', 'Penjualan Bersih (Rp)',
                '% Pangsa Omset', 'Total HPP (Rp)', 'Total Margin (Rp)',
                '% Pangsa Margin', 'Margin %'
            ]);

            $no = 1;
            $tot_sku = 0;
            $tot_qty = 0;
            $tot_bruto = 0;
            $tot_diskon = 0;
            $tot_neto = 0;
            $tot_hpp = 0;
            $tot_margin = 0;

            foreach ($data as $row) {
                $tot_sku += $row['total_sku'];
                $tot_qty += $row['total_qty'];
                $tot_bruto += $row['total_bruto'];
                $tot_diskon += $row['total_diskon'];
                $tot_neto += $row['total_neto'];
                $tot_hpp += $row['total_hpp'];
                $tot_margin += $row['total_margin'];

                fputcsv($output, [
                    $no++,
                    $row['kategori_nama'],
                    $row['total_sku'],
                    $row['total_qty'],
                    $row['total_bruto'],
                    $row['total_diskon'],
                    $row['total_neto'],
                    $row['kontribusi_sales_pct'] . '%',
                    $row['total_hpp'],
                    $row['total_margin'],
                    $row['kontribusi_margin_pct'] . '%',
                    $row['margin_pct'] . '%'
                ]);
            }

            $tot_pct = ($tot_neto > 0) ? ($tot_margin / $tot_neto * 100) : 0;
            fputcsv($output, []);
            fputcsv($output, [
                'TOTAL KESELURUHAN', '', $tot_sku, $tot_qty,
                $tot_bruto, $tot_diskon, $tot_neto, '100.0%',
                $tot_hpp, $tot_margin, '100.0%', round($tot_pct, 2) . '%'
            ]);
            break;
        }

        case 'analisis-stok-reorder': {
            $period_preset = $_GET['period_preset'] ?? '30';
            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                $start_date = date('Y-m-d', strtotime($_GET['start_date']));
                $end_date = date('Y-m-d', strtotime($_GET['end_date']));
            } else {
                $days_back = in_array($period_preset, ['7', '30', '60', '90', '180']) ? (int)$period_preset : 30;
                $start_date = date('Y-m-d', strtotime('-' . ($days_back - 1) . ' days'));
                $end_date = date('Y-m-d');
            }

            $buffer_days = isset($_GET['buffer_days']) ? max(1, min(180, (int)$_GET['buffer_days'])) : 14;
            $category_id = isset($_GET['category_id']) && $_GET['category_id'] !== 'all' ? (int)$_GET['category_id'] : null;
            $tab_filter = $_GET['tab'] ?? 'all';
            $search = trim($_GET['search'] ?? '');
            $sort_by = $_GET['sort_by'] ?? 'total_neto';
            $sort_order = strtolower($_GET['sort_order'] ?? 'desc');

            $total_days = max(1, (int)round((strtotime($end_date) - strtotime($start_date)) / 86400) + 1);

            // 1. Sales
            $sales_sql = "
                SELECT 
                    pd.item_id,
                    SUM(pd.quantity) as qty_sold,
                    SUM(pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) as total_neto,
                    SUM((pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) - (pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, 0)))) as total_margin
                FROM penjualan_details pd
                JOIN penjualan p ON pd.penjualan_id = p.id
                LEFT JOIN items i ON pd.item_id = i.id
                WHERE p.user_id = ?
                  AND DATE(p.tanggal_penjualan) >= ?
                  AND DATE(p.tanggal_penjualan) <= ?
                  AND p.status = 'completed'
                  AND pd.item_type = 'normal'
                GROUP BY pd.item_id
            ";
            $stmt_sales = $conn->prepare($sales_sql);
            $stmt_sales->bind_param('iss', $user_id, $start_date, $end_date);
            $stmt_sales->execute();
            $sales_raw = stmt_fetch_all($stmt_sales);
            $stmt_sales->close();

            $sales_map = [];
            $grand_store_sales = 0.0;
            foreach ($sales_raw as $sr) {
                $sales_map[(int)$sr['item_id']] = $sr;
                $grand_store_sales += (float)$sr['total_neto'];
            }

            // 2. Items
            $items_where = ["i.user_id = ?"];
            $items_params = [$user_id];
            $items_types = "i";

            if ($category_id !== null) {
                if ($category_id === 0) {
                    $items_where[] = "i.category_id IS NULL";
                } else {
                    $items_where[] = "i.category_id = ?";
                    $items_params[] = $category_id;
                    $items_types .= "i";
                }
            }

            $items_sql = "
                SELECT 
                    i.id,
                    i.nama_barang,
                    i.sku,
                    i.barcode,
                    i.category_id,
                    COALESCE(ic.nama_kategori, 'Tanpa Kategori') as nama_kategori,
                    i.harga_beli,
                    i.harga_jual,
                    i.stok
                FROM items i
                LEFT JOIN item_categories ic ON i.category_id = ic.id
                WHERE " . implode(' AND ', $items_where) . "
            ";
            $stmt_items = $conn->prepare($items_sql);
            $stmt_items->bind_param($items_types, ...$items_params);
            $stmt_items->execute();
            $items_raw = stmt_fetch_all($stmt_items);
            $stmt_items->close();

            $processed = [];
            foreach ($items_raw as $it) {
                $id = (int)$it['id'];
                $s = $sales_map[$id] ?? null;
                $qty_sold = $s ? (int)$s['qty_sold'] : 0;
                $neto = $s ? (float)$s['total_neto'] : 0.0;
                $margin = $s ? (float)$s['total_margin'] : 0.0;

                $stok = (int)$it['stok'];
                $harga_beli = (float)$it['harga_beli'];
                $harga_jual = (float)$it['harga_jual'];
                $nilai_stok = $stok * $harga_beli;
                $ads = round($qty_sold / $total_days, 2);

                $processed[] = [
                    'id' => $id,
                    'nama_barang' => $it['nama_barang'],
                    'sku' => (!empty($it['sku']) && $it['sku'] !== '') ? $it['sku'] : '-',
                    'barcode' => (!empty($it['barcode']) && $it['barcode'] !== '') ? $it['barcode'] : '-',
                    'nama_kategori' => $it['nama_kategori'],
                    'harga_beli' => $harga_beli,
                    'harga_jual' => $harga_jual,
                    'stok' => $stok,
                    'nilai_stok' => $nilai_stok,
                    'qty_sold' => $qty_sold,
                    'total_neto' => $neto,
                    'total_margin' => $margin,
                    'ads' => $ads
                ];
            }

            usort($processed, fn($a, $b) => $b['total_neto'] <=> $a['total_neto']);
            $cumulative_sales = 0.0;

            foreach ($processed as &$item) {
                if ($grand_store_sales > 0 && $item['total_neto'] > 0) {
                    $cumulative_sales += $item['total_neto'];
                    $cum_pct = ($cumulative_sales / $grand_store_sales) * 100;
                    if ($cum_pct <= 80.01) $item['abc_class'] = 'A (Fast-Moving)';
                    elseif ($cum_pct <= 95.01) $item['abc_class'] = 'B (Slow-Moving)';
                    else $item['abc_class'] = 'C (Non-Moving)';
                } else {
                    $item['abc_class'] = ($item['stok'] > 0) ? 'Dead Stock' : 'Non-Moving (C)';
                }

                if ($item['ads'] > 0) {
                    $run_out = round($item['stok'] / $item['ads'], 1);
                    $item['run_out_days'] = $run_out;
                    if ($item['stok'] <= 0) $item['stock_status'] = 'Habis (Stockout)';
                    elseif ($run_out <= 7) $item['stock_status'] = 'Kritis (<= 7 Hari)';
                    elseif ($run_out <= 14) $item['stock_status'] = 'Perlu Reorder (8-14 Hari)';
                    elseif ($run_out <= 45) $item['stock_status'] = 'Aman (15-45 Hari)';
                    else $item['stock_status'] = 'Overstock (> 45 Hari)';
                } else {
                    $item['run_out_days'] = ($item['stok'] > 0) ? '-' : '0';
                    $item['stock_status'] = ($item['stok'] > 0) ? 'Dead Stock' : 'Kosong & Tanpa Sales';
                }

                $target_stock = (int)ceil($item['ads'] * $buffer_days);
                $suggested = max(0, $target_stock - $item['stok']);
                $item['target_stock'] = $target_stock;
                $item['suggested_reorder_qty'] = $suggested;
                $item['estimasi_modal_reorder'] = $suggested * $item['harga_beli'];
            }
            unset($item);

            // Tab filter
            if ($tab_filter === 'critical') {
                $processed = array_filter($processed, fn($x) => str_starts_with($x['stock_status'], 'Habis') || str_starts_with($x['stock_status'], 'Kritis'));
            } elseif ($tab_filter === 'reorder') {
                $processed = array_filter($processed, fn($x) => $x['suggested_reorder_qty'] > 0);
            } elseif ($tab_filter === 'fast_moving') {
                $processed = array_filter($processed, fn($x) => str_starts_with($x['abc_class'], 'A'));
            } elseif ($tab_filter === 'slow_moving') {
                $processed = array_filter($processed, fn($x) => str_starts_with($x['abc_class'], 'B'));
            } elseif ($tab_filter === 'dead_stock') {
                $processed = array_filter($processed, fn($x) => $x['abc_class'] === 'Dead Stock');
            } elseif ($tab_filter === 'overstocked') {
                $processed = array_filter($processed, fn($x) => str_starts_with($x['stock_status'], 'Overstock'));
            }

            if (!empty($search)) {
                $search_lower = mb_strtolower($search);
                $processed = array_filter($processed, function($x) use ($search_lower) {
                    return (
                        mb_stripos($x['nama_barang'], $search_lower) !== false ||
                        mb_stripos($x['sku'], $search_lower) !== false ||
                        mb_stripos($x['nama_kategori'], $search_lower) !== false
                    );
                });
            }

            fputcsv($output, ['Analisis Fast-Moving, Slow-Moving, Dead Stock (ABC) & Estimasi Habis Stok']);
            fputcsv($output, ['Periode Observasi:', date('d-m-Y', strtotime($start_date)) . ' s/d ' . date('d-m-Y', strtotime($end_date)) . " ($total_days hari)"]);
            fputcsv($output, ['Target Buffer Reorder:', "$buffer_days hari kebutuhan stok"]);
            fputcsv($output, []);

            fputcsv($output, [
                'No', 'SKU', 'Barcode', 'Nama Barang', 'Kategori',
                'Stok Fisik', 'Harga Beli (Rp)', 'Nilai Modal Stok (Rp)',
                'Terjual (Qty)', 'Penjualan Bersih (Rp)', 'Total Margin (Rp)',
                'Laju/Hari (ADS)', 'Sisa Hari (Run-Out)', 'Klasifikasi ABC',
                'Status Urgensi', 'Target Stok', 'Saran Reorder (Qty)', 'Estimasi Modal Belanja (Rp)'
            ]);

            $no = 1;
            $sum_stok = 0;
            $sum_nilai_stok = 0;
            $sum_qty = 0;
            $sum_neto = 0;
            $sum_margin = 0;
            $sum_reorder_qty = 0;
            $sum_reorder_modal = 0;

            foreach ($processed as $row) {
                $sum_stok += $row['stok'];
                $sum_nilai_stok += $row['nilai_stok'];
                $sum_qty += $row['qty_sold'];
                $sum_neto += $row['total_neto'];
                $sum_margin += $row['total_margin'];
                $sum_reorder_qty += $row['suggested_reorder_qty'];
                $sum_reorder_modal += $row['estimasi_modal_reorder'];

                fputcsv($output, [
                    $no++,
                    $row['sku'],
                    $row['barcode'],
                    $row['nama_barang'],
                    $row['nama_kategori'],
                    $row['stok'],
                    $row['harga_beli'],
                    $row['nilai_stok'],
                    $row['qty_sold'],
                    $row['total_neto'],
                    $row['total_margin'],
                    $row['ads'],
                    $row['run_out_days'],
                    $row['abc_class'],
                    $row['stock_status'],
                    $row['target_stock'],
                    $row['suggested_reorder_qty'],
                    $row['estimasi_modal_reorder']
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, [
                'TOTAL / REKAP', '', '', '', '',
                $sum_stok, '', $sum_nilai_stok,
                $sum_qty, $sum_neto, $sum_margin,
                '', '', '', '',
                '', $sum_reorder_qty, $sum_reorder_modal
            ]);
            break;
        }

        default:
            fputcsv($output, ['Error: Tipe laporan tidak dikenal.']);
            break;
    }
} catch (Exception $e) {
    fputcsv($output, ['Error:', $e->getMessage()]);
}

fclose($output);
exit;
?>