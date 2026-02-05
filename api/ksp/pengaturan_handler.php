<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();
$user_id = 1; // Default user ID
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        // --- JENIS SIMPANAN ---
        case 'list_jenis_simpanan':
            $stmt = $db->prepare("SELECT j.*, a.nama_akun, a.kode_akun FROM ksp_jenis_simpanan j JOIN accounts a ON j.akun_id = a.id WHERE j.user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
            break;

        case 'save_jenis_simpanan':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['nama']) || empty($data['akun_id']) || empty($data['tipe'])) throw new Exception("Data tidak lengkap");
            
            if (!empty($data['id'])) {
                $stmt = $db->prepare("UPDATE ksp_jenis_simpanan SET nama=?, akun_id=?, nominal_default=?, tipe=? WHERE id=? AND user_id=?");
                $stmt->bind_param("sidsii", $data['nama'], $data['akun_id'], $data['nominal_default'], $data['tipe'], $data['id'], $user_id);
            } else {
                $stmt = $db->prepare("INSERT INTO ksp_jenis_simpanan (user_id, nama, akun_id, nominal_default, tipe) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isids", $user_id, $data['nama'], $data['akun_id'], $data['nominal_default'], $data['tipe']);
            }
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Jenis Simpanan berhasil disimpan']);
            break;

        case 'delete_jenis_simpanan':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $db->prepare("DELETE FROM ksp_jenis_simpanan WHERE id=? AND user_id=?");
            $stmt->bind_param("ii", $data['id'], $user_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Jenis Simpanan berhasil dihapus']);
            break;

        // --- KATEGORI TRANSAKSI ---
        case 'list_kategori_transaksi':
            $stmt = $db->prepare("SELECT * FROM ksp_kategori_transaksi WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
            break;

        case 'save_kategori_transaksi':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['nama']) || empty($data['tipe_aksi'])) throw new Exception("Data tidak lengkap");

            if (!empty($data['id'])) {
                $stmt = $db->prepare("UPDATE ksp_kategori_transaksi SET nama=?, tipe_aksi=?, posisi=? WHERE id=? AND user_id=?");
                $stmt->bind_param("sssii", $data['nama'], $data['tipe_aksi'], $data['posisi'], $data['id'], $user_id);
            } else {
                $stmt = $db->prepare("INSERT INTO ksp_kategori_transaksi (user_id, nama, tipe_aksi, posisi) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $user_id, $data['nama'], $data['tipe_aksi'], $data['posisi']);
            }
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Kategori Transaksi berhasil disimpan']);
            break;

        case 'delete_kategori_transaksi':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $db->prepare("DELETE FROM ksp_kategori_transaksi WHERE id=? AND user_id=?");
            $stmt->bind_param("ii", $data['id'], $user_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Kategori Transaksi berhasil dihapus']);
            break;
            
        // --- JENIS PINJAMAN ---
        case 'list_jenis_pinjaman':
            $stmt = $db->prepare("
                SELECT jp.*, 
                       ap.nama_akun as akun_piutang, ap.kode_akun as kode_piutang,
                       ab.nama_akun as akun_bunga, ab.kode_akun as kode_bunga
                FROM ksp_jenis_pinjaman jp 
                JOIN accounts ap ON jp.akun_piutang_id = ap.id
                JOIN accounts ab ON jp.akun_pendapatan_bunga_id = ab.id
                WHERE jp.user_id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
            break;

        case 'save_jenis_pinjaman':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['nama']) || empty($data['akun_piutang_id']) || empty($data['akun_pendapatan_bunga_id'])) throw new Exception("Data tidak lengkap");
            
            $bunga = (float)$data['bunga_per_tahun'];

            if (!empty($data['id'])) {
                $stmt = $db->prepare("UPDATE ksp_jenis_pinjaman SET nama=?, bunga_per_tahun=?, akun_piutang_id=?, akun_pendapatan_bunga_id=? WHERE id=? AND user_id=?");
                $stmt->bind_param("sdiiii", $data['nama'], $bunga, $data['akun_piutang_id'], $data['akun_pendapatan_bunga_id'], $data['id'], $user_id);
            } else {
                $stmt = $db->prepare("INSERT INTO ksp_jenis_pinjaman (user_id, nama, bunga_per_tahun, akun_piutang_id, akun_pendapatan_bunga_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isdii", $user_id, $data['nama'], $bunga, $data['akun_piutang_id'], $data['akun_pendapatan_bunga_id']);
            }
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Jenis Pinjaman berhasil disimpan']);
            break;

        case 'delete_jenis_pinjaman':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $db->prepare("DELETE FROM ksp_jenis_pinjaman WHERE id=? AND user_id=?");
            $stmt->bind_param("ii", $data['id'], $user_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Jenis Pinjaman berhasil dihapus']);
            break;

        // --- TIPE AGUNAN ---
        case 'list_tipe_agunan':
            $stmt = $db->prepare("SELECT * FROM ksp_tipe_agunan");
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
            break;

        case 'save_tipe_agunan':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['nama'])) throw new Exception("Nama Tipe Agunan wajib diisi");
            
            $config = isset($data['config']) ? $data['config'] : '[]';
            // Validasi JSON sederhana
            if (json_decode($config) === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Format konfigurasi tidak valid (harus JSON)");
            }

            if (!empty($data['id'])) {
                $stmt = $db->prepare("UPDATE ksp_tipe_agunan SET nama=?, config=? WHERE id=?");
                $stmt->bind_param("ssi", $data['nama'], $config, $data['id']);
            } else {
                $stmt = $db->prepare("INSERT INTO ksp_tipe_agunan (nama, config) VALUES (?, ?)");
                $stmt->bind_param("ss", $data['nama'], $config);
            }
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Tipe Agunan berhasil disimpan']);
            break;

        case 'delete_tipe_agunan':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $db->prepare("DELETE FROM ksp_tipe_agunan WHERE id=?");
            $stmt->bind_param("i", $data['id']);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Tipe Agunan berhasil dihapus']);
            break;

        case 'get_accounts':
            // Ambil akun Liabilitas, Ekuitas, Aset, Pendapatan untuk mapping
            $stmt = $db->prepare("SELECT id, kode_akun, nama_akun, tipe_akun FROM accounts WHERE user_id = ? AND tipe_akun IN ('Liabilitas', 'Ekuitas', 'Aset', 'Pendapatan') ORDER BY kode_akun");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
            break;

        default:
            throw new Exception("Aksi tidak valid");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}