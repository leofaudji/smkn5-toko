<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isset($_SESSION['member_loggedin']) || $_SESSION['member_loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$member_id = $_SESSION['member_id'];
$db = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? 'summary';
$request_method = $_SERVER['REQUEST_METHOD'];

try {
    if ($action === 'summary') {
        // Total Simpanan
        $stmt_sim = $db->prepare("
            SELECT 
                j.id,
                j.nama,
                j.tipe,
                COALESCE(SUM(t.kredit - t.debit), 0) as saldo
            FROM ksp_jenis_simpanan j
            LEFT JOIN ksp_transaksi_simpanan t ON j.id = t.jenis_simpanan_id AND t.anggota_id = ?
            WHERE j.user_id = 1
            GROUP BY j.id, j.nama, j.tipe
            ORDER BY j.id
        ");
        $stmt_sim->bind_param("i", $member_id);
        $stmt_sim->execute();
        $simpanan_per_jenis = $stmt_sim->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Pastikan saldo dikirim sebagai angka (float) bukan string
        foreach ($simpanan_per_jenis as &$item) {
            $item['saldo'] = (float)$item['saldo'];
        }
        unset($item); // Unset reference

        // Total Pinjaman (Sisa Pokok)
        $stmt_pinj = $db->prepare("
            SELECT SUM(p.jumlah_pinjaman - COALESCE(bayar.total_pokok_terbayar, 0)) as sisa_pokok
            FROM ksp_pinjaman p
            LEFT JOIN (
                SELECT pinjaman_id, SUM(pokok_terbayar) as total_pokok_terbayar
                FROM ksp_angsuran
                GROUP BY pinjaman_id
            ) bayar ON p.id = bayar.pinjaman_id
            WHERE p.anggota_id = ? AND p.status = 'aktif'
        ");
        $stmt_pinj->bind_param("i", $member_id);
        $stmt_pinj->execute();
        $sisa_pinjaman = $stmt_pinj->get_result()->fetch_assoc()['sisa_pokok'] ?? 0;

        // Cek Tagihan Jatuh Tempo (H-7)
        $stmt_due = $db->prepare("
            SELECT p.id as pinjaman_id, a.id as angsuran_id, p.nomor_pinjaman, a.angsuran_ke, a.tanggal_jatuh_tempo, a.total_angsuran,
            (a.total_angsuran - (a.pokok_terbayar + a.bunga_terbayar)) as sisa_tagihan
            FROM ksp_angsuran a 
            JOIN ksp_pinjaman p ON a.pinjaman_id = p.id 
            WHERE p.anggota_id = ? 
            AND p.status = 'aktif'
            AND a.status != 'lunas' 
            AND a.tanggal_jatuh_tempo <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY a.tanggal_jatuh_tempo ASC
        ");
        $stmt_due->bind_param("i", $member_id);
        $stmt_due->execute();
        $upcoming_payments = $stmt_due->get_result()->fetch_all(MYSQLI_ASSOC);

        // Ambil Pengumuman Aktif (Terbaru 3)
        $stmt_news = $db->prepare("SELECT judul, isi, tanggal_posting FROM ksp_pengumuman WHERE is_active = 1 ORDER BY tanggal_posting DESC LIMIT 3");
        $stmt_news->execute();
        $news = $stmt_news->get_result()->fetch_all(MYSQLI_ASSOC);

        // Ambil Target Tabungan (Dummy logic: ambil dari tabel jika ada, atau return kosong)
        // Di implementasi nyata, ini ambil dari tabel ksp_target_tabungan
        $stmt_target = $db->prepare("SELECT * FROM ksp_target_tabungan WHERE anggota_id = ? ORDER BY tanggal_target ASC");
        $stmt_target->bind_param("i", $member_id);
        $stmt_target->execute();
        $targets = $stmt_target->get_result()->fetch_all(MYSQLI_ASSOC);

        // Ambil Data Anggota Lengkap
        $stmt_m = $db->prepare("SELECT nama_lengkap, nomor_anggota, tanggal_daftar, gamification_points, default_payment_savings_id FROM anggota WHERE id = ?");
        $stmt_m->bind_param("i", $member_id);
        $stmt_m->execute();
        $member_info = $stmt_m->get_result()->fetch_assoc();

        // Gamifikasi: Tentukan Level berdasarkan poin
        $points = (int)($member_info['gamification_points'] ?? 0);
        $level = 'Bronze';
        if ($points >= 3000) {
            $level = 'Platinum';
        } elseif ($points >= 1500) {
            $level = 'Gold';
        } elseif ($points >= 500) {
            $level = 'Silver';
        }

        // --- NEW: Monthly Stats (Financial Pulse) ---
        $current_month = date('m');
        $current_year = date('Y');
        
        // 1. Tabungan Masuk Bulan Ini (Hanya Setoran)
        $stmt_month_save = $db->prepare("
            SELECT COALESCE(SUM(kredit), 0) as saved 
            FROM ksp_transaksi_simpanan 
            WHERE anggota_id = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ? AND jenis_transaksi = 'setor'
        ");
        $stmt_month_save->bind_param("iii", $member_id, $current_month, $current_year);
        $stmt_month_save->execute();
        $saved_this_month = (float)$stmt_month_save->get_result()->fetch_assoc()['saved'];

        // 2. Belanja Toko Bulan Ini (Berdasarkan referensi INV/MBR/...)
        $ref_pattern = "%/" . $member_id . "-%";
        $stmt_month_spend = $db->prepare("SELECT COALESCE(SUM(total), 0) as spent FROM penjualan WHERE nomor_referensi LIKE ? AND MONTH(tanggal_penjualan) = ? AND YEAR(tanggal_penjualan) = ?");
        $stmt_month_spend->bind_param("sii", $ref_pattern, $current_month, $current_year);
        $stmt_month_spend->execute();
        $spent_this_month = (float)$stmt_month_spend->get_result()->fetch_assoc()['spent'];


        echo json_encode([
            'success' => true,
            'data' => [
                'simpanan_per_jenis' => $simpanan_per_jenis,
                'pinjaman' => (float)$sisa_pinjaman,
                'nama' => $member_info['nama_lengkap'],
                'nomor_anggota' => $member_info['nomor_anggota'],
                'tanggal_daftar' => $member_info['tanggal_daftar'],
                'level' => $level,
                'points' => $points,
                'default_payment_savings_id' => $member_info['default_payment_savings_id'],
                'upcoming_payments' => $upcoming_payments,
                'news' => $news,
                'targets' => $targets,
                'monthly_stats' => [
                    'saved' => $saved_this_month,
                    'spent' => $spent_this_month
                ]
            ]
        ]);
    } elseif ($action === 'get_all_savings_history' || $action === 'history_simpanan') { // Added alias for backward compatibility
        $jenis_id = isset($_GET['jenis_id']) ? (int)$_GET['jenis_id'] : 0;
        $bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0;
        $tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;
        
        $sql = "SELECT t.tanggal, t.jenis_transaksi, t.jumlah, t.keterangan, j.nama as jenis_simpanan 
            FROM ksp_transaksi_simpanan t 
            JOIN ksp_jenis_simpanan j ON t.jenis_simpanan_id = j.id 
            WHERE t.anggota_id = ?";
        
        $params = [$member_id];
        $types = "i";

        if ($jenis_id > 0) {
            $sql .= " AND t.jenis_simpanan_id = ?";
            $params[] = $jenis_id;
            $types .= "i";
        }

        if ($bulan > 0) {
            $sql .= " AND MONTH(t.tanggal) = ?";
            $params[] = $bulan;
            $types .= "i";
        }

        if ($tahun > 0) {
            $sql .= " AND YEAR(t.tanggal) = ?";
            $params[] = $tahun;
            $types .= "i";
        }

        $sql .= " ORDER BY t.tanggal DESC LIMIT 50";

        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($action === 'list_pinjaman') {
        $status = $_GET['status'] ?? 'all';
        
        $sql = "SELECT p.id, p.nomor_pinjaman, p.jumlah_pinjaman, p.tanggal_pengajuan, p.tanggal_pencairan, p.status, p.tenor_bulan,
            (p.jumlah_pinjaman - COALESCE(SUM(a.pokok_terbayar), 0)) as sisa_pokok,
            (
                SELECT GROUP_CONCAT(ta.nama SEPARATOR ', ')
                FROM ksp_pinjaman_agunan pa
                JOIN ksp_tipe_agunan ta ON pa.tipe_agunan_id = ta.id
                WHERE pa.pinjaman_id = p.id
            ) as nama_agunan,
            (
                SELECT CONCAT(tanggal_jatuh_tempo, '|', total_angsuran)
                FROM ksp_angsuran 
                WHERE pinjaman_id = p.id AND status != 'lunas' 
                ORDER BY angsuran_ke ASC LIMIT 1
            ) as next_installment
            FROM ksp_pinjaman p
            LEFT JOIN ksp_angsuran a ON p.id = a.pinjaman_id
            WHERE p.anggota_id = ?";
        
        $params = [$member_id];
        $types = "i";

        if ($status !== 'all') {
            $sql .= " AND p.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        $sql .= " GROUP BY p.id ORDER BY FIELD(p.status, 'pending', 'aktif', 'lunas', 'ditolak'), p.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($action === 'get_loan_detail') {
        $pinjaman_id = $_GET['id'] ?? 0;
        
        // Get Header (Pastikan milik anggota yang login)
        $stmt = $db->prepare("SELECT * FROM ksp_pinjaman WHERE id = ? AND anggota_id = ?");
        $stmt->bind_param("ii", $pinjaman_id, $member_id);
        $stmt->execute();
        $pinjaman = $stmt->get_result()->fetch_assoc();

        if ($pinjaman) {
            // Get Schedule
            $stmt_sch = $db->prepare("SELECT * FROM ksp_angsuran WHERE pinjaman_id = ? ORDER BY angsuran_ke ASC");
            $stmt_sch->bind_param("i", $pinjaman_id);
            $stmt_sch->execute();
            $schedule = $stmt_sch->get_result()->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $pinjaman, 'schedule' => $schedule]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        }
    } elseif ($action === 'get_savings_history_by_type') {
        $jenis_id = $_GET['id'] ?? 0;

        $stmt = $db->prepare("
            SELECT tanggal, keterangan, debit, kredit, jumlah, jenis_transaksi
            FROM ksp_transaksi_simpanan 
            WHERE anggota_id = ? AND jenis_simpanan_id = ? 
            ORDER BY tanggal ASC, id ASC
        ");
        $stmt->bind_param("ii", $member_id, $jenis_id);
        $stmt->execute();
        $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $running_balance = 0;
        $history = [];
        foreach($transactions as $tx) {
            $running_balance += (float)$tx['kredit'] - (float)$tx['debit'];
            $tx['saldo'] = $running_balance;
            $history[] = $tx;
        }

        // Reverse array to show latest first
        echo json_encode(['success' => true, 'data' => array_reverse($history)]);
    } elseif ($action === 'get_loan_types') {
        $stmt = $db->prepare("SELECT id, nama, bunga_per_tahun FROM ksp_jenis_pinjaman");
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($action === 'savings_growth') {
        // Ambil data 6 bulan terakhir
        $months = [];
        $labels = [];
        $indonesian_months = [1=>'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        for ($i = 5; $i >= 0; $i--) {
            $time = strtotime("-$i months");
            $months[] = date('Y-m', $time);
            $month_idx = (int)date('n', $time);
            $labels[] = $indonesian_months[$month_idx];
        }
        
        $start_date = date('Y-m-01', strtotime("-5 months"));

        // 1. Hitung Saldo Awal sebelum periode grafik (sebelum 6 bulan lalu)
        $stmt_init = $db->prepare("SELECT SUM(kredit - debit) as total FROM ksp_transaksi_simpanan WHERE anggota_id = ? AND tanggal < ?");
        $stmt_init->bind_param("is", $member_id, $start_date);
        $stmt_init->execute();
        $current_balance = (float)($stmt_init->get_result()->fetch_assoc()['total'] ?? 0);

        // 2. Ambil perubahan saldo per bulan selama 6 bulan terakhir
        $stmt_monthly = $db->prepare("
            SELECT DATE_FORMAT(tanggal, '%Y-%m') as periode, SUM(kredit - debit) as net_change 
            FROM ksp_transaksi_simpanan 
            WHERE anggota_id = ? AND tanggal >= ? 
            GROUP BY periode 
            ORDER BY periode ASC
        ");
        $stmt_monthly->bind_param("is", $member_id, $start_date);
        $stmt_monthly->execute();
        $result = $stmt_monthly->get_result();
        
        $monthly_data = [];
        while($row = $result->fetch_assoc()) {
            $monthly_data[$row['periode']] = (float)$row['net_change'];
        }

        $chart_data = [];
        foreach ($months as $m) {
            $net = $monthly_data[$m] ?? 0;
            $current_balance += $net;
            $chart_data[] = $current_balance;
        }

        echo json_encode(['success' => true, 'labels' => $labels, 'data' => $chart_data]);
    } elseif ($action === 'get_item_categories') {
        $stmt = $db->prepare("SELECT id, nama_kategori FROM item_categories WHERE user_id = 1 ORDER BY nama_kategori ASC");
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($action === 'search_store_items') {
        $q = $_GET['q'] ?? '';
        $category_id = $_GET['category_id'] ?? '';

        // Jika tidak ada query pencarian DAN tidak ada kategori yang dipilih, return kosong
        if (strlen($q) < 2 && empty($category_id)) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }

        // Update query to include wishlist status
        $sql = "SELECT i.id, i.nama_barang, i.harga_jual, i.stok, 
                (SELECT COUNT(*) FROM ksp_wishlist w WHERE w.item_id = i.id AND w.anggota_id = ?) as is_wishlist
                FROM items i WHERE i.stok > 0";
        $params = [$member_id];
        $types = "i";

        if (!empty($category_id)) {
            $sql .= " AND i.category_id = ?";
            $params[] = $category_id;
            $types .= "i";
        }

        if (!empty($q)) {
            $sql .= " AND (i.nama_barang LIKE ? OR i.sku LIKE ?)";
            $searchTerm = "%$q%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }

        $sql .= " LIMIT 20";

        $stmt = $db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($action === 'toggle_wishlist' && $request_method === 'POST') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        if ($item_id <= 0) throw new Exception("Item ID tidak valid");

        // Cek apakah sudah ada
        $stmt_check = $db->prepare("SELECT id FROM ksp_wishlist WHERE anggota_id = ? AND item_id = ?");
        $stmt_check->bind_param("ii", $member_id, $item_id);
        $stmt_check->execute();
        $exists = $stmt_check->get_result()->fetch_assoc();

        if ($exists) {
            // Hapus
            $stmt = $db->prepare("DELETE FROM ksp_wishlist WHERE id = ?");
            $stmt->bind_param("i", $exists['id']);
            $stmt->execute();
            echo json_encode(['success' => true, 'status' => 'removed', 'message' => 'Dihapus dari wishlist']);
        } else {
            // Tambah
            $stmt = $db->prepare("INSERT INTO ksp_wishlist (anggota_id, item_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $member_id, $item_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'status' => 'added', 'message' => 'Ditambahkan ke wishlist']);
        }
    } elseif ($action === 'get_wishlist') {
        $stmt = $db->prepare("
            SELECT i.id, i.nama_barang, i.harga_jual, i.stok, 1 as is_wishlist 
            FROM ksp_wishlist w 
            JOIN items i ON w.item_id = i.id 
            WHERE w.anggota_id = ? AND i.stok > 0");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($action === 'search_members') {
        $q = $_GET['q'] ?? '';
        if (strlen($q) < 2) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }
        $searchTerm = "%$q%";
        $stmt = $db->prepare("
            SELECT id, nama_lengkap, nomor_anggota 
            FROM anggota 
            WHERE (nama_lengkap LIKE ? OR nomor_anggota LIKE ?) AND id != ? AND status = 'aktif'
            LIMIT 10
        ");
        $stmt->bind_param("ssi", $searchTerm, $searchTerm, $member_id);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($action === 'get_gamification_log') {
        $stmt = $db->prepare("
            SELECT * FROM ksp_gamification_log 
            WHERE anggota_id = ? 
            ORDER BY created_at DESC LIMIT 20
        ");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($action === 'get_withdrawal_history') {
        $stmt = $db->prepare("
            SELECT p.id, p.jumlah, p.tanggal_pengajuan, p.status, p.keterangan, j.nama as jenis_simpanan
            FROM ksp_penarikan_simpanan p
            JOIN ksp_jenis_simpanan j ON p.jenis_simpanan_id = j.id
            WHERE p.anggota_id = ?
            ORDER BY p.tanggal_pengajuan DESC, p.id DESC
            LIMIT 5
        ");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($action === 'get_qr_payment_history') {
        $stmt = $db->prepare("
            SELECT tanggal, keterangan, jumlah
            FROM ksp_transaksi_simpanan
            WHERE anggota_id = ? AND nomor_referensi LIKE 'QRPAY/%'
            ORDER BY tanggal DESC
            LIMIT 50
        ");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($action === 'update_payment_settings' && $request_method === 'POST') {
        $savings_id = isset($_POST['default_savings_id']) && $_POST['default_savings_id'] !== '' ? (int)$_POST['default_savings_id'] : null;
        
        // Validasi jika ID dikirim, pastikan itu milik user ini (validasi sederhana: pastikan ID valid di tabel jenis simpanan)
        // Di sini kita asumsikan ID yang dikirim valid dari dropdown.

        $stmt = $db->prepare("UPDATE anggota SET default_payment_savings_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $savings_id, $member_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Pengaturan pembayaran berhasil disimpan.']);
        } else {
            throw new Exception("Gagal menyimpan pengaturan: " . $stmt->error);
        }
    } elseif ($action === 'add_target' && $request_method === 'POST') {
        $nama_target = $_POST['nama_target'] ?? '';
        $nominal_target = (float)($_POST['nominal_target'] ?? 0);
        $tanggal_target = $_POST['tanggal_target'] ?? '';

        if (empty($nama_target) || $nominal_target <= 0 || empty($tanggal_target)) {
            throw new Exception("Data target tidak lengkap.");
        }

        // Pastikan tabel ksp_target_tabungan ada.
        $stmt = $db->prepare("INSERT INTO ksp_target_tabungan (anggota_id, nama_target, nominal_target, tanggal_target, nominal_terkumpul) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("isds", $member_id, $nama_target, $nominal_target, $tanggal_target);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Target berhasil ditambahkan']);
        } else {
            throw new Exception("Gagal menyimpan target: " . $stmt->error);
        }
    } elseif ($action === 'delete_target' && $request_method === 'POST') {
        $target_id = (int)($_POST['id'] ?? 0);

        if ($target_id <= 0) {
            throw new Exception("ID Target tidak valid.");
        }

        // Hapus target, pastikan milik anggota yang sedang login
        $stmt = $db->prepare("DELETE FROM ksp_target_tabungan WHERE id = ? AND anggota_id = ?");
        $stmt->bind_param("ii", $target_id, $member_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Target berhasil dihapus']);
        } else {
            throw new Exception("Gagal menghapus target: " . $stmt->error);
        }
    } elseif ($action === 'request_withdrawal' && $request_method === 'POST') {
        $jumlah = (float)($_POST['jumlah'] ?? 0);
        $jenis_simpanan_id = (int)($_POST['jenis_simpanan_id'] ?? 0);
        $keterangan = trim($_POST['keterangan'] ?? '');
        $tanggal_pengajuan = date('Y-m-d');

        if ($jumlah <= 0 || $jenis_simpanan_id <= 0) {
            throw new Exception("Jumlah dan jenis simpanan wajib diisi.");
        }

        // 1. Validasi Jenis Simpanan (hanya sukarela)
        $stmt_jenis = $db->prepare("SELECT tipe FROM ksp_jenis_simpanan WHERE id = ?");
        $stmt_jenis->bind_param("i", $jenis_simpanan_id);
        $stmt_jenis->execute();
        $jenis = $stmt_jenis->get_result()->fetch_assoc();
        if (!$jenis || $jenis['tipe'] !== 'sukarela') {
            throw new Exception("Penarikan hanya diizinkan untuk Simpanan Sukarela.");
        }

        // 2. Validasi Saldo
        $stmt_saldo = $db->prepare("SELECT COALESCE(SUM(kredit - debit), 0) as saldo FROM ksp_transaksi_simpanan WHERE anggota_id = ? AND jenis_simpanan_id = ?");
        $stmt_saldo->bind_param("ii", $member_id, $jenis_simpanan_id);
        $stmt_saldo->execute();
        $saldo = (float)$stmt_saldo->get_result()->fetch_assoc()['saldo'];

        if ($jumlah > $saldo) {
            throw new Exception("Saldo tidak mencukupi. Saldo Anda saat ini: " . number_format($saldo));
        }

        $stmt_req = $db->prepare("INSERT INTO ksp_penarikan_simpanan (anggota_id, jenis_simpanan_id, jumlah, keterangan, tanggal_pengajuan, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt_req->bind_param("iidss", $member_id, $jenis_simpanan_id, $jumlah, $keterangan, $tanggal_pengajuan);
        
        if ($stmt_req->execute()) {
            echo json_encode(['success' => true, 'message' => 'Pengajuan penarikan berhasil dikirim. Mohon tunggu persetujuan dari admin.']);
        } else {
            throw new Exception("Gagal menyimpan pengajuan: " . $stmt_req->error);
        }
    } elseif ($action === 'get_shopping_history') {
        $search_ref = "INV/MBR/%/" . $member_id . "-%";
        $stmt = $db->prepare("
            SELECT p.id, p.nomor_referensi, p.tanggal_penjualan, p.total, p.status,
                   (SELECT COUNT(*) FROM penjualan_details pd WHERE pd.penjualan_id = p.id) as item_count
            FROM penjualan p
            WHERE p.nomor_referensi LIKE ?
            ORDER BY p.tanggal_penjualan DESC
            LIMIT 20
        ");
        $stmt->bind_param("s", $search_ref);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($action === 'get_shopping_detail') {
        $penjualan_id = (int)($_GET['id'] ?? 0);
        $search_ref = "%/" . $member_id . "-%"; // Pastikan transaksi milik member ini

        $stmt = $db->prepare("
            SELECT id, nomor_referensi, tanggal_penjualan, total, status
            FROM penjualan
            WHERE id = ? AND nomor_referensi LIKE ?
        ");
        $stmt->bind_param("is", $penjualan_id, $search_ref);
        $stmt->execute();
        $header = $stmt->get_result()->fetch_assoc();

        if ($header) {
            $stmt_items = $db->prepare("
                SELECT pd.quantity, pd.price, pd.subtotal, i.nama_barang 
                FROM penjualan_details pd
                JOIN items i ON pd.item_id = i.id
                WHERE pd.penjualan_id = ?
            ");
            $stmt_items->bind_param("i", $penjualan_id);
            $stmt_items->execute();
            $items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode(['success' => true, 'data' => ['header' => $header, 'items' => $items]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan']);
        }
    } elseif ($action === 'checkout_store' && $request_method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $cart_items = $input['items'] ?? [];
        $password = $input['password'] ?? '';

        if (empty($cart_items) || empty($password)) {
            throw new Exception("Data keranjang atau password tidak lengkap.");
        }

        $db->begin_transaction();
        try {
            // 1. Validasi Password
            $stmt_pass = $db->prepare("SELECT password FROM anggota WHERE id = ?");
            $stmt_pass->bind_param("i", $member_id);
            $stmt_pass->execute();
            $sender = $stmt_pass->get_result()->fetch_assoc();
            if (!$sender || !password_verify($password, $sender['password'])) {
                throw new Exception("Password Anda salah. Transaksi dibatalkan.");
            }

            // 2. Hitung total belanja dan validasi stok
            $total_belanja = 0;
            $item_ids = array_column($cart_items, 'id');
            $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
            $stmt_items = $db->prepare("SELECT id, nama_barang, harga_jual, harga_beli, stok, inventory_account_id, cogs_account_id, revenue_account_id FROM items WHERE id IN ($placeholders) FOR UPDATE");
            $stmt_items->bind_param(str_repeat('i', count($item_ids)), ...$item_ids);
            $stmt_items->execute();
            $db_items_res = $stmt_items->get_result();
            $db_items = [];
            while ($row = $db_items_res->fetch_assoc()) {
                $db_items[$row['id']] = $row;
            }

            foreach ($cart_items as $cart_item) {
                $db_item = $db_items[$cart_item['id']] ?? null;
                if (!$db_item) throw new Exception("Barang '{$cart_item['nama_barang']}' tidak ditemukan.");
                if ($cart_item['qty'] > $db_item['stok']) throw new Exception("Stok untuk '{$db_item['nama_barang']}' tidak mencukupi.");
                $total_belanja += $cart_item['qty'] * $db_item['harga_jual'];
            }

            // 3. Validasi Saldo Simpanan Sukarela
            $stmt_sukarela = $db->prepare("SELECT id, akun_id FROM ksp_jenis_simpanan WHERE tipe = 'sukarela' AND user_id = 1 LIMIT 1");
            $stmt_sukarela->execute();
            $sukarela = $stmt_sukarela->get_result()->fetch_assoc();
            if (!$sukarela) throw new Exception("Jenis Simpanan Sukarela tidak ditemukan.");
            $sukarela_id = $sukarela['id'];
            $akun_simpanan_sukarela_id = $sukarela['akun_id'];

            $stmt_saldo = $db->prepare("SELECT COALESCE(SUM(kredit - debit), 0) as saldo FROM ksp_transaksi_simpanan WHERE anggota_id = ? AND jenis_simpanan_id = ?");
            $stmt_saldo->bind_param("ii", $member_id, $sukarela_id);
            $stmt_saldo->execute();
            $saldo = (float)$stmt_saldo->get_result()->fetch_assoc()['saldo'];
            if ($total_belanja > $saldo) {
                throw new Exception("Saldo Simpanan Sukarela tidak mencukupi. Saldo Anda: " . number_format($saldo));
            }

            // 4. Buat Transaksi Penjualan
            $tanggal = date('Y-m-d H:i:s');
            $nomor_referensi = "INV/MBR/" . date('Ymd') . "/" . $member_id . "-" . time();
            $stmt_penjualan = $db->prepare("INSERT INTO penjualan (user_id, nomor_referensi, tanggal_penjualan, customer_name, subtotal, total, bayar, keterangan, created_by) VALUES (1, ?, ?, ?, ?, ?, ?, 'Pembayaran via Simpanan Sukarela', NULL)");
            $stmt_penjualan->bind_param('ssddds', $nomor_referensi, $tanggal, $_SESSION['member_name'], $total_belanja, $total_belanja, $total_belanja);
            $stmt_penjualan->execute();
            $penjualan_id = $stmt_penjualan->insert_id;

            // 5. Buat Detail Penjualan, Update Stok, dan Kartu Stok
            $stmt_detail = $db->prepare("INSERT INTO penjualan_details (penjualan_id, item_id, deskripsi_item, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_update_stok = $db->prepare("UPDATE items SET stok = stok - ? WHERE id = ?");
            $stmt_kartu_stok = $db->prepare("INSERT INTO kartu_stok (user_id, item_id, tanggal, kredit, keterangan, ref_id, source) VALUES (1, ?, ?, ?, ?, ?, 'penjualan')");
            
            $total_hpp = 0;
            foreach ($cart_items as $cart_item) {
                $db_item = $db_items[$cart_item['id']];
                $subtotal = $cart_item['qty'] * $db_item['harga_jual'];
                $total_hpp += $cart_item['qty'] * $db_item['harga_beli'];

                $stmt_detail->bind_param('iisidi', $penjualan_id, $db_item['id'], $db_item['nama_barang'], $db_item['harga_jual'], $cart_item['qty'], $subtotal);
                $stmt_detail->execute();

                $stmt_update_stok->bind_param('ii', $cart_item['qty'], $db_item['id']);
                $stmt_update_stok->execute();

                $keterangan_ks = "Penjualan #{$nomor_referensi}";
                $stmt_kartu_stok->bind_param('isisi', $db_item['id'], $tanggal, $cart_item['qty'], $keterangan_ks, $penjualan_id);
                $stmt_kartu_stok->execute();
            }

            // 6. Buat Transaksi Penarikan Simpanan
            $keterangan_simpanan = "Belanja di toko, Ref: {$nomor_referensi}";
            
            // Ambil akun kas default untuk memenuhi constraint database, karena ini bukan transaksi kas riil
            $akun_kas_id = (int)get_setting('default_cash_out', 0, $db);
            if ($akun_kas_id === 0) {
                 $res_kas = $db->query("SELECT id FROM accounts WHERE is_kas = 1 LIMIT 1");
                 if ($row_kas = $res_kas->fetch_assoc()) {
                     $akun_kas_id = $row_kas['id'];
                 } else {
                     $akun_kas_id = 1; // Fallback jika tidak ada akun kas
                 }
            }

            $stmt_trx_simpanan = $db->prepare("INSERT INTO ksp_transaksi_simpanan (user_id, anggota_id, jenis_simpanan_id, tanggal, jenis_transaksi, debit, kredit, jumlah, keterangan, nomor_referensi, akun_kas_id, created_by) VALUES (1, ?, ?, ?, 'tarik', ?, 0, ?, ?, ?, ?, NULL)");
            $stmt_trx_simpanan->bind_param("iisddssi", $member_id, $source_savings_id, $tanggal, $total_belanja, $total_belanja, $keterangan_simpanan, $nomor_referensi, $akun_kas_id);
            $stmt_trx_simpanan->execute();

            // 7. Buat Jurnal Akuntansi
            $keterangan_jurnal = "Penjualan ke Anggota #{$_SESSION['member_no']} via Simpanan";
            $stmt_gl = $db->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (1, ?, ?, ?, ?, ?, ?, ?, 'transaksi', NULL)");
            $zero = 0.00;

            // (Dr) Simpanan Sukarela, (Cr) Pendapatan Penjualan
            // Ambil akun COA untuk jenis simpanan yang digunakan
            $stmt_akun_simpanan = $db->prepare("SELECT akun_id FROM ksp_jenis_simpanan WHERE id = ?");
            $stmt_akun_simpanan->bind_param("i", $source_savings_id);
            $stmt_akun_simpanan->execute();
            $akun_simpanan_id = $stmt_akun_simpanan->get_result()->fetch_assoc()['akun_id'];

            $stmt_gl->bind_param('sssiddi', $tanggal, $keterangan_jurnal, $nomor_referensi, $akun_simpanan_id, $total_belanja, $zero, $penjualan_id);
            $stmt_gl->execute();
            $revenue_acc_id = get_setting('default_sales_revenue_account_id', null, $db);
            $stmt_gl->bind_param('sssiddi', $tanggal, $keterangan_jurnal, $nomor_referensi, $revenue_acc_id, $zero, $total_belanja, $penjualan_id);
            $stmt_gl->execute();

            // (Dr) HPP, (Cr) Persediaan
            $keterangan_hpp = "HPP untuk penjualan #{$nomor_referensi}";
            $cogs_acc_id = get_setting('default_cogs_account_id', null, $db);
            $inventory_acc_id = get_setting('default_inventory_account_id', null, $db);
            $stmt_gl->bind_param('sssiddi', $tanggal, $keterangan_hpp, $nomor_referensi, $cogs_acc_id, $total_hpp, $zero, $penjualan_id);
            $stmt_gl->execute();
            $stmt_gl->bind_param('sssiddi', $tanggal, $keterangan_hpp, $nomor_referensi, $inventory_acc_id, $zero, $total_hpp, $penjualan_id);
            $stmt_gl->execute();

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil. Saldo simpanan Anda telah dipotong.']);

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    } elseif ($action === 'transfer_savings' && $request_method === 'POST') {
        $destination_member_id = (int)($_POST['destination_member_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $password = $_POST['password'] ?? '';
        $notes = trim($_POST['notes'] ?? '');

        // 1. Validasi Input
        if ($destination_member_id <= 0 || $amount <= 0 || empty($password)) {
            throw new Exception("Mohon lengkapi semua data transfer.");
        }
        if ($destination_member_id === $member_id) {
            throw new Exception("Anda tidak dapat mentransfer ke diri sendiri.");
        }

        $db->begin_transaction();
        try {
            // 2. Validasi Password Pengirim
            $stmt_pass = $db->prepare("SELECT password FROM anggota WHERE id = ?");
            $stmt_pass->bind_param("i", $member_id);
            $stmt_pass->execute();
            $sender = $stmt_pass->get_result()->fetch_assoc();
            if (!$sender || !password_verify($password, $sender['password'])) {
                throw new Exception("Password Anda salah. Transfer dibatalkan.");
            }

            // 3. Validasi Anggota Tujuan
            $stmt_dest = $db->prepare("SELECT id, nama_lengkap FROM anggota WHERE id = ? AND status = 'aktif'");
            $stmt_dest->bind_param("i", $destination_member_id);
            $stmt_dest->execute();
            $destination_member = $stmt_dest->get_result()->fetch_assoc();
            if (!$destination_member) {
                throw new Exception("Anggota tujuan tidak ditemukan atau tidak aktif.");
            }

            // 4. Validasi Saldo & Jenis Simpanan Sukarela
            $stmt_sukarela = $db->prepare("SELECT id FROM ksp_jenis_simpanan WHERE tipe = 'sukarela' AND user_id = 1 LIMIT 1");
            $stmt_sukarela->execute();
            $sukarela = $stmt_sukarela->get_result()->fetch_assoc();
            if (!$sukarela) {
                throw new Exception("Jenis Simpanan Sukarela tidak ditemukan. Hubungi admin.");
            }
            $sukarela_id = $sukarela['id'];

            $stmt_saldo = $db->prepare("SELECT COALESCE(SUM(kredit - debit), 0) as saldo FROM ksp_transaksi_simpanan WHERE anggota_id = ? AND jenis_simpanan_id = ?");
            $stmt_saldo->bind_param("ii", $member_id, $sukarela_id);
            $stmt_saldo->execute();
            $saldo = (float)$stmt_saldo->get_result()->fetch_assoc()['saldo'];

            if ($amount > $saldo) {
                throw new Exception("Saldo Simpanan Sukarela tidak mencukupi. Saldo Anda: " . number_format($saldo));
            }

            // 5. Proses Transaksi
            $tanggal = date('Y-m-d');
            $ref = "TRF-" . date('YmdHis') . "-" . $member_id;
            
            // Ambil akun kas default untuk memenuhi constraint database
            $akun_kas_id = (int)get_setting('default_cash_in', 0, $db);
            if ($akun_kas_id === 0) {
                 $res_kas = $db->query("SELECT id FROM accounts WHERE is_kas = 1 LIMIT 1");
                 if ($row_kas = $res_kas->fetch_assoc()) {
                     $akun_kas_id = $row_kas['id'];
                 } else {
                     $akun_kas_id = 1; // Fallback jika tidak ada akun kas
                 }
            }

            $stmt_trx = $db->prepare("INSERT INTO ksp_transaksi_simpanan (user_id, anggota_id, jenis_simpanan_id, tanggal, jenis_transaksi, debit, kredit, jumlah, keterangan, nomor_referensi, akun_kas_id, created_by) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            // Debit dari pengirim
            $keterangan_debit = "Transfer ke {$destination_member['nama_lengkap']}. " . $notes;
            $type_tarik = 'tarik';
            $zero = 0;
            $stmt_trx->bind_param("iisssddssii", $member_id, $sukarela_id, $tanggal, $type_tarik, $amount, $zero, $amount, $keterangan_debit, $ref, $akun_kas_id, $member_id);
            $stmt_trx->execute();

            // Kredit ke penerima
            $keterangan_kredit = "Transfer dari {$_SESSION['member_name']}. " . $notes;
            $type_setor = 'setor';
            $stmt_trx->bind_param("iisssddssii", $destination_member_id, $sukarela_id, $tanggal, $type_setor, $zero, $amount, $amount, $keterangan_kredit, $ref, $akun_kas_id, $member_id);
            $stmt_trx->execute();

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Transfer sebesar ' . number_format($amount) . ' ke ' . $destination_member['nama_lengkap'] . ' berhasil.']);

        } catch (Exception $e) {
            $db->rollback();
            throw $e; // Re-throw to be caught by the outer catch block
        }
    } elseif ($action === 'pay_installment' && $request_method === 'POST') {
        $angsuran_id = (int)($_POST['angsuran_id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if ($angsuran_id <= 0 || empty($password)) {
            throw new Exception("Data tidak lengkap.");
        }

        $db->begin_transaction();
        try {
            // 1. Validasi Password
            $stmt_pass = $db->prepare("SELECT password FROM anggota WHERE id = ?");
            $stmt_pass->bind_param("i", $member_id);
            $stmt_pass->execute();
            $sender = $stmt_pass->get_result()->fetch_assoc();
            if (!$sender || !password_verify($password, $sender['password'])) {
                throw new Exception("Password Anda salah.");
            }

            // 2. Tentukan Sumber Dana (Default atau Sukarela)
            // Cek settingan default user dulu
            $stmt_member = $db->prepare("SELECT default_payment_savings_id FROM anggota WHERE id = ?");
            $stmt_member->bind_param("i", $member_id);
            $stmt_member->execute();
            $member_data = $stmt_member->get_result()->fetch_assoc();
            $source_savings_id = $member_data['default_payment_savings_id'];

            // Jika tidak ada default, cari simpanan sukarela pertama sebagai fallback
            if (!$source_savings_id) {
                $stmt_sukarela = $db->prepare("SELECT id FROM ksp_jenis_simpanan WHERE tipe = 'sukarela' AND user_id = 1 LIMIT 1");
                $stmt_sukarela->execute();
                $sukarela = $stmt_sukarela->get_result()->fetch_assoc();
                if (!$sukarela) throw new Exception("Jenis Simpanan Sukarela tidak ditemukan.");
                $source_savings_id = $sukarela['id'];
            }

            // 3. Ambil Info Angsuran
            $stmt_ang = $db->prepare("
                SELECT a.*, p.anggota_id, p.nomor_pinjaman, p.jenis_pinjaman_id,
                       (a.total_angsuran - (a.pokok_terbayar + a.bunga_terbayar)) as sisa_tagihan
                FROM ksp_angsuran a
                JOIN ksp_pinjaman p ON a.pinjaman_id = p.id
                WHERE a.id = ? AND p.anggota_id = ? AND a.status != 'lunas'
            ");
            $stmt_ang->bind_param("ii", $angsuran_id, $member_id);
            $stmt_ang->execute();
            $angsuran = $stmt_ang->get_result()->fetch_assoc();

            if (!$angsuran) {
                throw new Exception("Tagihan tidak ditemukan atau sudah lunas.");
            }

            $amount = (float)$angsuran['sisa_tagihan'];

            // 4. Validasi Saldo
            $stmt_saldo = $db->prepare("SELECT COALESCE(SUM(kredit - debit), 0) as saldo FROM ksp_transaksi_simpanan WHERE anggota_id = ? AND jenis_simpanan_id = ?");
            $stmt_saldo->bind_param("ii", $member_id, $source_savings_id);
            $stmt_saldo->execute();
            $saldo = (float)$stmt_saldo->get_result()->fetch_assoc()['saldo'];

            if ($amount > $saldo) {
                throw new Exception("Saldo simpanan yang dipilih tidak mencukupi. Saldo: " . number_format($saldo));
            }

            // 5. Proses Pembayaran (Update Angsuran)
            $stmt_upd = $db->prepare("UPDATE ksp_angsuran SET pokok_terbayar = pokok, bunga_terbayar = bunga, status = 'lunas', tanggal_bayar = NOW() WHERE id = ?");
            $stmt_upd->bind_param("i", $angsuran_id);
            $stmt_upd->execute();

            // 6. Transaksi Penarikan Simpanan
            $tanggal = date('Y-m-d H:i:s');
            $nomor_referensi = "PAY/" . $angsuran['nomor_pinjaman'] . "/" . $angsuran['angsuran_ke'] . "/" . time();
            $keterangan = "Pembayaran Angsuran Ke-{$angsuran['angsuran_ke']} Pinjaman {$angsuran['nomor_pinjaman']}";
            $akun_kas_id = (int)get_setting('default_cash_out', 1, $db); 

            $stmt_trx_simpanan = $db->prepare("INSERT INTO ksp_transaksi_simpanan (user_id, anggota_id, jenis_simpanan_id, tanggal, jenis_transaksi, debit, kredit, jumlah, keterangan, nomor_referensi, akun_kas_id, created_by) VALUES (1, ?, ?, ?, 'tarik', ?, 0, ?, ?, ?, ?, NULL)");
            $stmt_trx_simpanan->bind_param("iisddssi", $member_id, $source_savings_id, $tanggal, $amount, $amount, $keterangan, $nomor_referensi, $akun_kas_id);
            $stmt_trx_simpanan->execute();

            // Cek Lunas Pinjaman
            $stmt_check = $db->prepare("SELECT COUNT(*) as sisa FROM ksp_angsuran WHERE pinjaman_id = ? AND status != 'lunas'");
            $stmt_check->bind_param("i", $angsuran['pinjaman_id']);
            $stmt_check->execute();
            if ($stmt_check->get_result()->fetch_assoc()['sisa'] == 0) {
                $db->query("UPDATE ksp_pinjaman SET status = 'lunas' WHERE id = " . $angsuran['pinjaman_id']);
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Pembayaran angsuran berhasil.']);

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    } elseif ($action === 'process_qr_payment' && $request_method === 'POST') {
        $qr_data_json = $_POST['qr_data'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($qr_data_json) || empty($password)) {
            throw new Exception("Data pembayaran atau password tidak lengkap.");
        }

        $qr_data = json_decode($qr_data_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($qr_data['amount']) || !isset($qr_data['merchant'])) {
            throw new Exception("Data QR Code tidak valid.");
        }

        $amount = (float)$qr_data['amount'];
        $merchant_name = $qr_data['merchant'];
        $merchant_ref = $qr_data['ref'] ?? null; // Optional reference from merchant

        if ($amount <= 0) {
            throw new Exception("Jumlah pembayaran tidak valid.");
        }

        $db->begin_transaction();
        try {
            // 1. Validasi Password
            $stmt_pass = $db->prepare("SELECT password FROM anggota WHERE id = ?");
            $stmt_pass->bind_param("i", $member_id);
            $stmt_pass->execute();
            $sender = $stmt_pass->get_result()->fetch_assoc();
            if (!$sender || !password_verify($password, $sender['password'])) {
                throw new Exception("Password Anda salah. Transaksi dibatalkan.");
            }

            // 2. Tentukan Sumber Dana (Default atau Sukarela)
            // Cek settingan default user dulu
            $stmt_member = $db->prepare("SELECT default_payment_savings_id FROM anggota WHERE id = ?");
            $stmt_member->bind_param("i", $member_id);
            $stmt_member->execute();
            $member_data = $stmt_member->get_result()->fetch_assoc();
            $source_savings_id = $member_data['default_payment_savings_id'];

            // Jika tidak ada default, cari simpanan sukarela pertama
            if (!$source_savings_id) {
                $stmt_sukarela = $db->prepare("SELECT id FROM ksp_jenis_simpanan WHERE tipe = 'sukarela' AND user_id = 1 LIMIT 1");
                $stmt_sukarela->execute();
                $sukarela = $stmt_sukarela->get_result()->fetch_assoc();
                if (!$sukarela) throw new Exception("Jenis Simpanan Sukarela tidak ditemukan.");
                $source_savings_id = $sukarela['id'];
            }

            // Validasi Saldo

            $stmt_saldo = $db->prepare("SELECT COALESCE(SUM(kredit - debit), 0) as saldo FROM ksp_transaksi_simpanan WHERE anggota_id = ? AND jenis_simpanan_id = ?");
            $stmt_saldo->bind_param("ii", $member_id, $source_savings_id);
            $stmt_saldo->execute();
            $saldo = (float)$stmt_saldo->get_result()->fetch_assoc()['saldo'];
            if ($amount > $saldo) {
                throw new Exception("Saldo simpanan yang dipilih tidak mencukupi. Saldo Anda: " . number_format($saldo));
            }

            // 3. Buat Transaksi Penarikan dari Simpanan Anggota
            $tanggal = date('Y-m-d H:i:s');
            $nomor_referensi = "QRPAY/" . date('Ymd') . "/" . $member_id . "-" . time();
            $keterangan_simpanan = "Pembayaran QR ke {$merchant_name}";
            if ($merchant_ref) {
                $keterangan_simpanan .= " (Ref: {$merchant_ref})";
            }
            
            $akun_kas_id = (int)get_setting('default_cash_out', 0, $db);
            if ($akun_kas_id === 0) {
                 $res_kas = $db->query("SELECT id FROM accounts WHERE is_kas = 1 LIMIT 1");
                 $akun_kas_id = $res_kas->fetch_assoc()['id'] ?? 1;
            }

            $stmt_trx_simpanan = $db->prepare("INSERT INTO ksp_transaksi_simpanan (user_id, anggota_id, jenis_simpanan_id, tanggal, jenis_transaksi, debit, kredit, jumlah, keterangan, nomor_referensi, akun_kas_id, created_by) VALUES (1, ?, ?, ?, 'tarik', ?, 0, ?, ?, ?, ?, NULL)");
            $stmt_trx_simpanan->bind_param("iisddssi", $member_id, $source_savings_id, $tanggal, $amount, $amount, $keterangan_simpanan, $nomor_referensi, $akun_kas_id);
            $stmt_trx_simpanan->execute();
            $trx_id = $stmt_trx_simpanan->insert_id;

            // 4. Buat Jurnal Akuntansi (Asumsi pembayaran QR masuk ke kas toko)
            $keterangan_jurnal = "Pembayaran QR dari Anggota #{$_SESSION['member_no']} ke {$merchant_name}";
            $akun_kas_toko_id = (int)get_setting('default_cash_in', 103, $db); // Default ke Kas di Tangan
            
            // Ambil akun COA untuk jenis simpanan yang digunakan
            $stmt_akun_simpanan = $db->prepare("SELECT akun_id FROM ksp_jenis_simpanan WHERE id = ?");
            $stmt_akun_simpanan->bind_param("i", $source_savings_id);
            $stmt_akun_simpanan->execute();
            $akun_simpanan_id = $stmt_akun_simpanan->get_result()->fetch_assoc()['akun_id'];

            create_double_entry_journal(1, $tanggal, $keterangan_jurnal, $nomor_referensi, $akun_simpanan_id, $akun_kas_toko_id, $amount, 'transaksi', $trx_id, null);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Pembayaran sebesar ' . number_format($amount) . ' ke ' . $merchant_name . ' berhasil.']);

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    } elseif ($action === 'apply_loan' && $request_method === 'POST') {
        $jenis_pinjaman_id = (int)($_POST['jenis_pinjaman_id'] ?? 0);
        $jumlah = (float)($_POST['jumlah'] ?? 0);
        $tenor = (int)($_POST['tenor'] ?? 0);
        $keterangan = trim($_POST['keterangan'] ?? '');
        $tanggal_pengajuan = date('Y-m-d');
        $user_id = 1; // ID Toko/Unit

        if ($jenis_pinjaman_id <= 0 || $jumlah <= 0 || $tenor <= 0) {
            throw new Exception("Mohon lengkapi data pengajuan (Jenis, Jumlah, Tenor).");
        }

        // 1. Ambil Info Bunga dari Jenis Pinjaman
        $stmt_jenis = $db->prepare("SELECT bunga_per_tahun FROM ksp_jenis_pinjaman WHERE id = ?");
        $stmt_jenis->bind_param("i", $jenis_pinjaman_id);
        $stmt_jenis->execute();
        $jenis_info = $stmt_jenis->get_result()->fetch_assoc();
        
        if (!$jenis_info) {
            throw new Exception("Jenis pinjaman tidak valid.");
        }
        $bunga_per_tahun = $jenis_info['bunga_per_tahun'];

        // 2. Generate Nomor Pinjaman
        $prefix = "PINJ-" . date('Ymd') . "-";
        $res = $db->query("SELECT id FROM ksp_pinjaman ORDER BY id DESC LIMIT 1");
        $last = $res->fetch_assoc();
        $seq = ($last ? $last['id'] : 0) + 1;
        $nomor_pinjaman = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

        // 3. Simpan Pengajuan (Status Pending)
        $stmt = $db->prepare("INSERT INTO ksp_pinjaman (user_id, nomor_pinjaman, anggota_id, jenis_pinjaman_id, jumlah_pinjaman, bunga_per_tahun, tenor_bulan, tanggal_pengajuan, status, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->bind_param("isiiddiss", $user_id, $nomor_pinjaman, $member_id, $jenis_pinjaman_id, $jumlah, $bunga_per_tahun, $tenor, $tanggal_pengajuan, $keterangan);
        
        if ($stmt->execute()) {
            $pinjaman_id = $stmt->insert_id;

            // 4. Generate Jadwal Angsuran (Estimasi Awal - Metode Flat)
            $pokok_bulanan = $jumlah / $tenor;
            $bunga_bulanan = ($jumlah * ($bunga_per_tahun / 100)) / 12;
            $total_bulanan = $pokok_bulanan + $bunga_bulanan;
            
            $stmt_angsuran = $db->prepare("INSERT INTO ksp_angsuran (pinjaman_id, angsuran_ke, tanggal_jatuh_tempo, pokok, bunga, total_angsuran) VALUES (?, ?, ?, ?, ?, ?)");
            
            $tgl_mulai = new DateTime($tanggal_pengajuan);
            $tgl_mulai->modify('+1 month'); 

            for ($i = 1; $i <= $tenor; $i++) {
                $jatuh_tempo = $tgl_mulai->format('Y-m-d');
                $stmt_angsuran->bind_param("iisddd", $pinjaman_id, $i, $jatuh_tempo, $pokok_bulanan, $bunga_bulanan, $total_bulanan);
                $stmt_angsuran->execute();
                $tgl_mulai->modify('+1 month');
            }

            echo json_encode(['success' => true, 'message' => 'Pengajuan pinjaman berhasil dikirim. Menunggu persetujuan admin.']);
        } else {
            throw new Exception("Gagal memproses pengajuan: " . $stmt->error);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}