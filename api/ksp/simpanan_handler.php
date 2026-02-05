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
    case 'get_transaksi':
        get_transaksi($db);
        break;
    case 'get_jenis_simpanan':
        get_jenis_simpanan($db);
        break;
    case 'get_anggota_list':
        get_anggota_list($db);
        break;
    case 'get_kas_accounts':
        get_kas_accounts($db);
        break;
    case 'store':
        store_transaksi($db);
        break;
    case 'get_summary':
        get_summary($db);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
        break;
}

function get_transaksi($db) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT t.*, a.nama_lengkap, j.nama as jenis_simpanan 
            FROM ksp_transaksi_simpanan t
            JOIN anggota a ON t.anggota_id = a.id
            JOIN ksp_jenis_simpanan j ON t.jenis_simpanan_id = j.id
            WHERE 1=1";
            
    $params = [];
    $types = "";

    if (!empty($search)) {
        $sql .= " AND (a.nama_lengkap LIKE ? OR t.nomor_referensi LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }

    $sql .= " ORDER BY t.tanggal DESC, t.id DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $db->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Hitung total untuk pagination (disederhanakan)
    $total = 100; // Idealnya query count terpisah

    echo json_encode(['success' => true, 'data' => $data, 'total' => $total]);
}

function get_jenis_simpanan($db) {
    $stmt = $db->query("SELECT * FROM ksp_jenis_simpanan ORDER BY id ASC");
    echo json_encode(['success' => true, 'data' => $stmt->fetch_all(MYSQLI_ASSOC)]);
}

function get_anggota_list($db) {
    $search = $_GET['q'] ?? '';
    $sql = "SELECT id, nama_lengkap, nomor_anggota FROM anggota WHERE status = 'aktif'";
    if ($search) {
        $sql .= " AND (nama_lengkap LIKE '%$search%' OR nomor_anggota LIKE '%$search%')";
    }
    $sql .= " LIMIT 10";
    $result = $db->query($sql);
    echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
}

function get_kas_accounts($db) {
    $result = $db->query("SELECT id, nama_akun, kode_akun FROM accounts WHERE is_kas = 1 ORDER BY kode_akun ASC");
    echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
}

function store_transaksi($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = 1; // Default Toko/Unit ID
    $created_by = $_SESSION['user_id'];

    // Validasi
    if (empty($data['anggota_id']) || empty($data['jenis_simpanan_id']) || empty($data['jumlah']) || empty($data['akun_kas_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        return;
    }

    $db->begin_transaction();
    try {
        // 1. Generate Nomor Referensi
        $prefix = "SIM-" . date('Ymd') . "-";
        $res = $db->query("SELECT id FROM ksp_transaksi_simpanan ORDER BY id DESC LIMIT 1");
        $last = $res->fetch_assoc();
        $seq = ($last ? $last['id'] : 0) + 1;
        $nomor_referensi = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

        // Ambil detail kategori transaksi dari DB
        $kategori_id = (int)$data['jenis_transaksi'];
        $stmt_kat = $db->prepare("SELECT nama, tipe_aksi, posisi FROM ksp_kategori_transaksi WHERE id = ?");
        $stmt_kat->bind_param("i", $kategori_id);
        $stmt_kat->execute();
        $kategori = $stmt_kat->get_result()->fetch_assoc();
        $tipe_aksi = $kategori['tipe_aksi']; // 'setor' atau 'tarik'
        $posisi = $kategori['posisi']; // 'debit' atau 'kredit'

        // Tentukan Debit/Kredit untuk tabel transaksi simpanan (Memudahkan perhitungan saldo)
        $debit_transaksi = 0;
        $kredit_transaksi = 0;
        if ($posisi === 'kredit') {
            $kredit_transaksi = $data['jumlah']; // Simpanan (Kewajiban) bertambah di Kredit
        } else {
            $debit_transaksi = $data['jumlah']; // Simpanan (Kewajiban) berkurang di Debit
        }

        // Tambahkan nama kategori ke keterangan
        $keterangan_lengkap = $kategori['nama'] . ' - ' . $data['keterangan'];

        // 2. Simpan Transaksi
        $stmt = $db->prepare("INSERT INTO ksp_transaksi_simpanan (user_id, anggota_id, jenis_simpanan_id, tanggal, jenis_transaksi, debit, kredit, jumlah, keterangan, akun_kas_id, nomor_referensi, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiissdddsisi", $user_id, $data['anggota_id'], $data['jenis_simpanan_id'], $data['tanggal'], $tipe_aksi, $debit_transaksi, $kredit_transaksi, $data['jumlah'], $keterangan_lengkap, $data['akun_kas_id'], $nomor_referensi, $created_by);
        $stmt->execute();
        $transaksi_id = $stmt->insert_id;


        // 3. Integrasi Akuntansi (Jurnal Otomatis)
        // Ambil akun simpanan dari jenis simpanan
        $stmt_jenis = $db->prepare("SELECT akun_id, nama, tipe FROM ksp_jenis_simpanan WHERE id = ?");
        $stmt_jenis->bind_param("i", $data['jenis_simpanan_id']);
        $stmt_jenis->execute();
        $jenis = $stmt_jenis->get_result()->fetch_assoc();
        $akun_simpanan = $jenis['akun_id'];
        $nama_simpanan = $jenis['nama'];

        // Gamifikasi: Tambah poin jika setor ke simpanan sukarela
        if ($tipe_aksi === 'setor' && $jenis['tipe'] === 'sukarela') {
            addGamificationPoints($data['anggota_id'], 'setor_sukarela', 10, "Setoran " . $nama_simpanan, $transaksi_id);
        }

        $keterangan_jurnal = "Trans. " . ucfirst($tipe_aksi) . " " . $nama_simpanan . " (" . $kategori['nama'] . ") - Ref: " . $nomor_referensi;
        
        // Insert ke General Ledger dengan UNIT = 'ksp'
        $stmt_gl = $db->prepare("INSERT INTO general_ledger (user_id, unit, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, 'ksp', ?, ?, ?, ?, ?, ?, ?, 'transaksi', ?)");
        
        $zero = 0.00;
        $jumlah = $data['jumlah'];

        if ($tipe_aksi === 'setor') {
            // Setor: Debit Kas, Kredit Simpanan (Liabilitas/Ekuitas bertambah di Kredit)
            // Baris 1: Debit Kas
            $stmt_gl->bind_param("issssidii", $user_id, $data['tanggal'], $keterangan_jurnal, $nomor_referensi, $data['akun_kas_id'], $jumlah, $zero, $transaksi_id, $created_by);
            $stmt_gl->execute();
            // Baris 2: Kredit Simpanan
            $stmt_gl->bind_param("issssidii", $user_id, $data['tanggal'], $keterangan_jurnal, $nomor_referensi, $akun_simpanan, $zero, $jumlah, $transaksi_id, $created_by);
            $stmt_gl->execute();
        } else {
            // Tarik: Debit Simpanan (Berkurang), Kredit Kas
            // Baris 1: Debit Simpanan
            $stmt_gl->bind_param("issssidii", $user_id, $data['tanggal'], $keterangan_jurnal, $nomor_referensi, $akun_simpanan, $jumlah, $zero, $transaksi_id, $created_by);
            $stmt_gl->execute();
            // Baris 2: Kredit Kas
            $stmt_gl->bind_param("issssidii", $user_id, $data['tanggal'], $keterangan_jurnal, $nomor_referensi, $data['akun_kas_id'], $zero, $jumlah, $transaksi_id, $created_by);
            $stmt_gl->execute();
        }

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Transaksi berhasil disimpan', 'transaksi_id' => $transaksi_id]);

    } catch (Exception $e) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function get_summary($db) {
    $today = date('Y-m-d');
    
    // Total Saldo (Semua waktu) - Saldo Simpanan adalah Liabilitas (Kredit - Debit)
    $sql_saldo = "SELECT SUM(kredit - debit) as total_saldo FROM ksp_transaksi_simpanan";
    $res_saldo = $db->query($sql_saldo)->fetch_assoc();
    
    // Transaksi Hari Ini
    // Kita gunakan kolom 'jenis_transaksi' atau logika debit/kredit
    $sql_today = "SELECT 
                    SUM(CASE WHEN jenis_transaksi = 'setor' THEN jumlah ELSE 0 END) as total_setor,
                    SUM(CASE WHEN jenis_transaksi = 'tarik' THEN jumlah ELSE 0 END) as total_tarik,
                    COUNT(*) as total_transaksi
                  FROM ksp_transaksi_simpanan 
                  WHERE tanggal = '$today'";
    $res_today = $db->query($sql_today)->fetch_assoc();

    echo json_encode(['success' => true, 'data' => [
        'total_saldo' => $res_saldo['total_saldo'] ?? 0,
        'total_setor_hari_ini' => $res_today['total_setor'] ?? 0,
        'total_tarik_hari_ini' => $res_today['total_tarik'] ?? 0,
        'jumlah_transaksi_hari_ini' => $res_today['total_transaksi'] ?? 0
    ]]);
}
