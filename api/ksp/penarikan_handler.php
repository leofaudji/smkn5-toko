<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();
$action = $_REQUEST['action'] ?? '';
$user_id = 1; // ID Pemilik Data (Toko)
$logged_in_user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'list':
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? 'pending';

            $sql = "SELECT p.*, a.nama_lengkap, a.nomor_anggota, j.nama as jenis_simpanan 
                    FROM ksp_penarikan_simpanan p
                    JOIN anggota a ON p.anggota_id = a.id
                    JOIN ksp_jenis_simpanan j ON p.jenis_simpanan_id = j.id
                    WHERE 1=1";
            
            $params = [];
            $types = "";

            if (!empty($search)) {
                $sql .= " AND a.nama_lengkap LIKE ?";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $types .= "s";
            }
            if ($status !== 'all') {
                $sql .= " AND p.status = ?";
                $params[] = $status;
                $types .= "s";
            }

            $sql .= " ORDER BY p.tanggal_pengajuan DESC, p.id DESC";

            $stmt = $db->prepare($sql);
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'approve':
            $id = (int)($_POST['id'] ?? 0);
            $akun_kas_id = (int)($_POST['akun_kas_id'] ?? 0);

            if ($id <= 0 || $akun_kas_id <= 0) {
                throw new Exception("Data tidak lengkap.");
            }

            $db->begin_transaction();

            // 1. Get withdrawal data and lock the row
            $stmt = $db->prepare("SELECT * FROM ksp_penarikan_simpanan WHERE id = ? AND status = 'pending' FOR UPDATE");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $penarikan = $stmt->get_result()->fetch_assoc();
            if (!$penarikan) {
                throw new Exception("Pengajuan tidak ditemukan atau sudah diproses.");
            }

            // 2. Get savings account from savings type
            $stmt_jenis = $db->prepare("SELECT akun_id, nama FROM ksp_jenis_simpanan WHERE id = ?");
            $stmt_jenis->bind_param("i", $penarikan['jenis_simpanan_id']);
            $stmt_jenis->execute();
            $jenis_simpanan = $stmt_jenis->get_result()->fetch_assoc();
            $akun_simpanan_id = $jenis_simpanan['akun_id'];

            // 3. Create a journal entry
            $keterangan_jurnal = "Penarikan Simpanan {$jenis_simpanan['nama']} - Ref: PEN-{$penarikan['id']}";
            $stmt_jurnal = $db->prepare("INSERT INTO jurnal_entries (user_id, tanggal, keterangan, created_by) VALUES (?, ?, ?, ?)");
            $tanggal_proses = date('Y-m-d');
            $stmt_jurnal->bind_param("issi", $user_id, $tanggal_proses, $keterangan_jurnal, $logged_in_user_id);
            $stmt_jurnal->execute();
            $jurnal_id = $stmt_jurnal->insert_id;
            $nomor_referensi_jurnal = 'JRN-' . $jurnal_id;

            // 4. Create GL entries (Debit Simpanan, Kredit Kas)
            $stmt_gl = $db->prepare("INSERT INTO general_ledger (user_id, unit, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, 'ksp', ?, ?, ?, ?, ?, ?, ?, 'jurnal', ?)");
            $zero = 0.00;
            $jumlah = (float)$penarikan['jumlah'];

            // Debit Simpanan (Liability/Equity berkurang di Debit)
            $stmt_gl->bind_param("isssiddii", $user_id, $tanggal_proses, $keterangan_jurnal, $nomor_referensi_jurnal, $akun_simpanan_id, $jumlah, $zero, $jurnal_id, $logged_in_user_id);
            $stmt_gl->execute();

            // Kredit Kas (Asset berkurang di Kredit)
            $stmt_gl->bind_param("isssiddii", $user_id, $tanggal_proses, $keterangan_jurnal, $nomor_referensi_jurnal, $akun_kas_id, $zero, $jumlah, $jurnal_id, $logged_in_user_id);
            $stmt_gl->execute();

            // 5. Create a transaction record for member history
            $stmt_trx = $db->prepare("INSERT INTO ksp_transaksi_simpanan (user_id, anggota_id, jenis_simpanan_id, tanggal, jenis_transaksi, debit, kredit, jumlah, keterangan, akun_kas_id, nomor_referensi, created_by) VALUES (?, ?, ?, ?, 'tarik', ?, 0, ?, ?, ?, ?, ?)");
            $stmt_trx->bind_param("iiisddsisi", $user_id, $penarikan['anggota_id'], $penarikan['jenis_simpanan_id'], $tanggal_proses, $jumlah, $jumlah, $penarikan['keterangan'], $akun_kas_id, $nomor_referensi_jurnal, $logged_in_user_id);
            $stmt_trx->execute();

            // 6. Update withdrawal request status
            $stmt_update = $db->prepare("UPDATE ksp_penarikan_simpanan SET status = 'approved', tanggal_diproses = ?, diproses_oleh = ? WHERE id = ?");
            $stmt_update->bind_param("sii", $tanggal_proses, $logged_in_user_id, $id);
            $stmt_update->execute();

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Penarikan berhasil disetujui dan diproses.']);
            break;

        case 'reject':
            $id = (int)($_POST['id'] ?? 0);
            $catatan = trim($_POST['catatan_admin'] ?? '');

            if ($id <= 0 || empty($catatan)) {
                throw new Exception("ID dan alasan penolakan wajib diisi.");
            }

            $stmt = $db->prepare("UPDATE ksp_penarikan_simpanan SET status = 'rejected', tanggal_diproses = CURDATE(), diproses_oleh = ?, catatan_admin = ? WHERE id = ? AND status = 'pending'");
            $stmt->bind_param("isi", $logged_in_user_id, $catatan, $id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Pengajuan berhasil ditolak.']);
                } else {
                    throw new Exception("Pengajuan tidak ditemukan atau sudah diproses.");
                }
            } else {
                throw new Exception("Gagal menolak pengajuan: " . $stmt->error);
            }
            break;

        default:
            throw new Exception("Aksi tidak valid.");
    }
} catch (Exception $e) {
    if ($db->in_transaction) {
        $db->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>