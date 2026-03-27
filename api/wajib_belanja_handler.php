<?php
require_once __DIR__ . '/../includes/bootstrap.php';

check_permission('wajib_belanja', 'menu');

header('Content-Type: application/json');

$conn = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Handle GET request for fetching data
        $action = $_GET['action'] ?? 'list';

        if ($action === 'list') {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 15;
            $offset = ($page - 1) * $limit;

            $sql = "SELECT twb.*, a.nama_lengkap as nama_anggota 
                    FROM transaksi_wajib_belanja twb
                    JOIN anggota a ON twb.anggota_id = a.id
                    ORDER BY twb.tanggal DESC, twb.id DESC
                    LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $limit, $offset);
            $stmt->execute();
            $data = stmt_fetch_all($stmt);
            $stmt->close();

            $total_sql = "SELECT COUNT(*) as total FROM transaksi_wajib_belanja";
            $total_stmt = $conn->prepare($total_sql);
            $total_stmt->execute();
            $total_records = stmt_fetch_assoc($total_stmt)['total'];
            $total_stmt->close();

            echo json_encode([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total_records / $limit),
                    'total_records' => $total_records,
                ]
            ]);

        } elseif ($action === 'init_data') {
            // Fetch initial data for the form
            $anggota_sql = "SELECT id, nama_lengkap, nomor_anggota FROM anggota WHERE status = 'aktif' ORDER BY nama_lengkap";
            $stmt_anggota = $conn->prepare($anggota_sql);
            $stmt_anggota->execute();
            $anggota = stmt_fetch_all($stmt_anggota);
            $stmt_anggota->close();

            $kas_sql = "SELECT id, nama_akun, kode_akun FROM accounts WHERE is_kas = 1 ORDER BY nama_akun";
            $stmt_kas = $conn->prepare($kas_sql);
            $stmt_kas->execute();
            $kas_accounts = stmt_fetch_all($stmt_kas);
            $stmt_kas->close();

            $nominal_default = get_setting('nominal_wajib_belanja', 50000, $conn);

            echo json_encode([
                'success' => true,
                'anggota' => $anggota,
                'kas_accounts' => $kas_accounts,
                'nominal_default' => $nominal_default
            ]);
        }

        elseif ($action === 'get_single') {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) throw new Exception("ID tidak valid.");

            $stmt = $conn->prepare("SELECT twb.*, a.nama_lengkap as nama_anggota FROM transaksi_wajib_belanja twb JOIN anggota a ON twb.anggota_id = a.id WHERE twb.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $data = stmt_fetch_assoc($stmt);
            $stmt->close();

            if (!$data) throw new Exception("Data tidak ditemukan.");
            echo json_encode(['success' => true, 'data' => $data]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle POST request for saving data
        // Ambil data dari JSON body karena JS mengirim JSON
        $input = json_decode(file_get_contents('php://input'), true);
        $data = !empty($input) ? $input : $_POST;
        $action = $data['action'] ?? 'create';

        if ($action === 'create') {
            $tanggal = $data['tanggal'] ?? '';
            $metode_pembayaran = $data['metode_pembayaran'] ?? '';
            $akun_kas_id = (int)($data['akun_kas_id'] ?? 0);
            $items = $data['items'] ?? [];
            $created_by = $_SESSION['user_id'];

            // Validation
            if (empty($tanggal) || empty($metode_pembayaran) || empty($akun_kas_id)) {
                throw new Exception("Data header (Tanggal, Metode, Akun) wajib diisi.");
            }
            if (empty($items) || !is_array($items)) {
                throw new Exception("Tidak ada data anggota yang disetor.");
            }
        
        check_period_lock($tanggal, $conn);

        $conn->begin_transaction();

        // Prepare statements
        $stmt_insert = $conn->prepare("INSERT INTO transaksi_wajib_belanja (user_id, anggota_id, tanggal, jumlah, metode_pembayaran, akun_kas_id, keterangan, nomor_referensi, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_update_anggota = $conn->prepare("UPDATE anggota SET saldo_wajib_belanja = saldo_wajib_belanja + ? WHERE id = ?");
        
        // Insert ke GL per transaksi
        $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, unit, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'transaksi_wajib_belanja', 'toko', ?)");

        $akun_hutang_wb_id = get_setting('wajib_belanja_liability_account_id', null, $conn);
        if (!$akun_hutang_wb_id) {
            throw new Exception("Akun Hutang Wajib Belanja belum diatur di Pengaturan.");
        }
        
        $total_processed = 0;
        $batch_id = date('YmdHis');

        foreach ($items as $index => $item) {
            $anggota_id = (int)($item['anggota_id'] ?? 0);
            $jumlah = (float)($item['jumlah'] ?? 0);
            $ket_row = $item['keterangan'] ?? '';

            if ($anggota_id <= 0 || $jumlah <= 0) continue;

            // Generate nomor referensi unik per item
            $nomor_referensi = 'WB-' . $batch_id . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);

            // Ambil nama anggota untuk keterangan jurnal
            $res_nama = $conn->query("SELECT nama_lengkap FROM anggota WHERE id=$anggota_id");
            $nama_anggota = $res_nama ? ($res_nama->fetch_assoc()['nama_lengkap'] ?? 'Anggota') : 'Anggota';
            
            $keterangan_transaksi = "Setoran Wajib Belanja - $nama_anggota" . (!empty($ket_row) ? " ($ket_row)" : "");

            // 1. Insert Transaksi
            $stmt_insert->bind_param('iisdsissi', $user_id, $anggota_id, $tanggal, $jumlah, $metode_pembayaran, $akun_kas_id, $ket_row, $nomor_referensi, $created_by);
            $stmt_insert->execute();
            $transaksi_wb_id = $stmt_insert->insert_id;

            // 2. Update Saldo Anggota
            $stmt_update_anggota->bind_param('di', $jumlah, $anggota_id);
            $stmt_update_anggota->execute();

            // 3. Jurnal Buku Besar (Per Item)
            $zero = 0;
            // Debit Kas
            $stmt_gl->bind_param('isssiddii', $user_id, $tanggal, $keterangan_transaksi, $nomor_referensi, $akun_kas_id, $jumlah, $zero, $transaksi_wb_id, $created_by);
            $stmt_gl->execute();
            
            // Kredit Hutang
            $stmt_gl->bind_param('isssiddii', $user_id, $tanggal, $keterangan_transaksi, $nomor_referensi, $akun_hutang_wb_id, $zero, $jumlah, $transaksi_wb_id, $created_by);
            $stmt_gl->execute();

            $total_processed++;
        }

        $conn->commit();
        
        log_activity($_SESSION['username'], 'Tambah Setoran WB', "Menambah setoran Wajib Belanja untuk $total_processed anggota.");

        echo json_encode(['success' => true, 'message' => "Berhasil menyimpan setoran untuk $total_processed anggota."]);
        
        } elseif ($action === 'update') {
            $id = (int)($data['id'] ?? 0);
            $tanggal = $data['tanggal'];
            $jumlah = (float)$data['jumlah'];
            $metode = $data['metode_pembayaran'];
            $keterangan = $data['keterangan'];

            if ($id <= 0 || empty($tanggal) || $jumlah <= 0) throw new Exception("Data tidak valid.");

            $conn->begin_transaction();

            // Ambil data lama
            $stmt = $conn->prepare("SELECT * FROM transaksi_wajib_belanja WHERE id = ? FOR UPDATE");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $old_trx = stmt_fetch_assoc($stmt);
            $stmt->close();

            if (!$old_trx) throw new Exception("Transaksi tidak ditemukan.");
            check_period_lock($old_trx['tanggal'], $conn);
            check_period_lock($tanggal, $conn);

            // 1. Revert Saldo Lama (Kurangi saldo anggota)
            $stmt_revert = $conn->prepare("UPDATE anggota SET saldo_wajib_belanja = saldo_wajib_belanja - ? WHERE id = ?");
            $stmt_revert->bind_param('di', $old_trx['jumlah'], $old_trx['anggota_id']);
            $stmt_revert->execute();
            $stmt_revert->close();

            // 2. Update Transaksi
            $stmt_upd = $conn->prepare("UPDATE transaksi_wajib_belanja SET tanggal = ?, jumlah = ?, metode_pembayaran = ?, keterangan = ? WHERE id = ?");
            $stmt_upd->bind_param('sdssi', $tanggal, $jumlah, $metode, $keterangan, $id);
            $stmt_upd->execute();
            $stmt_upd->close();

            // 3. Apply Saldo Baru (Tambah saldo anggota)
            $stmt_apply = $conn->prepare("UPDATE anggota SET saldo_wajib_belanja = saldo_wajib_belanja + ? WHERE id = ?");
            $stmt_apply->bind_param('di', $jumlah, $old_trx['anggota_id']);
            $stmt_apply->execute();
            $stmt_apply->close();

            // 4. Update Jurnal (Hapus lama, buat baru agar bersih)
            $stmt_del_gl = $conn->prepare("DELETE FROM general_ledger WHERE ref_id = ? AND ref_type = 'transaksi_wajib_belanja'");
            $stmt_del_gl->bind_param('i', $id);
            $stmt_del_gl->execute();
            $stmt_del_gl->close();

            // Buat Jurnal Baru
            $akun_hutang_wb_id = get_setting('wajib_belanja_liability_account_id', null, $conn);
            $akun_kas_id = $old_trx['akun_kas_id']; // Asumsi akun kas tidak berubah di edit form sederhana
            $keterangan_jurnal = "Setoran Wajib Belanja - " . ($conn->query("SELECT nama_lengkap FROM anggota WHERE id={$old_trx['anggota_id']}")->fetch_assoc()['nama_lengkap'] ?? 'N/A') . " ($keterangan)";
            $nomor_referensi = $old_trx['nomor_referensi']; // Gunakan nomor referensi yang sama

            $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, unit, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'transaksi_wajib_belanja', 'toko', ?)");
            $zero = 0;
            // Debit Kas
            $stmt_gl->bind_param('isssiddii', $user_id, $tanggal, $keterangan_jurnal, $nomor_referensi, $akun_kas_id, $jumlah, $zero, $id, $_SESSION['user_id']);
            $stmt_gl->execute();
            // Kredit Hutang
            $stmt_gl->bind_param('isssiddii', $user_id, $tanggal, $keterangan_jurnal, $nomor_referensi, $akun_hutang_wb_id, $zero, $jumlah, $id, $_SESSION['user_id']);
            $stmt_gl->execute();
            $stmt_gl->close();

            $conn->commit();
            log_activity($_SESSION['username'], 'Update Transaksi WB', "Update transaksi WB ID: $id");
            echo json_encode(['success' => true, 'message' => 'Transaksi berhasil diperbarui.']);

        } elseif ($action === 'delete') {
            $id = (int)($data['id'] ?? 0);
            if ($id <= 0) throw new Exception("ID tidak valid.");

            $conn->begin_transaction();

            // Ambil data transaksi lama
            $stmt = $conn->prepare("SELECT * FROM transaksi_wajib_belanja WHERE id = ? FOR UPDATE");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $old_trx = stmt_fetch_assoc($stmt);
            $stmt->close();

            if (!$old_trx) throw new Exception("Transaksi tidak ditemukan.");
            check_period_lock($old_trx['tanggal'], $conn);

            // 1. Kembalikan saldo anggota (Kurangi saldo)
            $stmt_upd = $conn->prepare("UPDATE anggota SET saldo_wajib_belanja = saldo_wajib_belanja - ? WHERE id = ?");
            $stmt_upd->bind_param('di', $old_trx['jumlah'], $old_trx['anggota_id']);
            $stmt_upd->execute();
            $stmt_upd->close();

            // 2. Hapus Jurnal (General Ledger)
            $stmt_gl = $conn->prepare("DELETE FROM general_ledger WHERE ref_id = ? AND ref_type = 'transaksi_wajib_belanja'");
            $stmt_gl->bind_param('i', $id);
            $stmt_gl->execute();
            $stmt_gl->close();

            // 3. Hapus Transaksi
            $stmt_del = $conn->prepare("DELETE FROM transaksi_wajib_belanja WHERE id = ?");
            $stmt_del->bind_param('i', $id);
            $stmt_del->execute();
            $stmt_del->close();

            $conn->commit();
            log_activity($_SESSION['username'], 'Hapus Transaksi WB', "Menghapus transaksi WB ID: $id");
            echo json_encode(['success' => true, 'message' => 'Transaksi berhasil dihapus.']);
        }
    }
} catch (Exception $e) {
    if (isset($conn) && method_exists($conn, 'in_transaction') && $conn->in_transaction()) {
        $conn->rollback();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}