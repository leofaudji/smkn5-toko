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
                    WHERE twb.user_id = ? 
                    ORDER BY twb.tanggal DESC, twb.id DESC
                    LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iii', $user_id, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $total_sql = "SELECT COUNT(*) as total FROM transaksi_wajib_belanja WHERE user_id = ?";
            $total_stmt = $conn->prepare($total_sql);
            $total_stmt->bind_param('i', $user_id);
            $total_stmt->execute();
            $total_records = $total_stmt->get_result()->fetch_assoc()['total'];
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
            $anggota_sql = "SELECT id, nama_lengkap, nomor_anggota FROM anggota WHERE user_id = ? AND status = 'aktif' ORDER BY nama_lengkap";
            $stmt_anggota = $conn->prepare($anggota_sql);
            $stmt_anggota->bind_param('i', $user_id);
            $stmt_anggota->execute();
            $anggota = $stmt_anggota->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_anggota->close();

            $kas_sql = "SELECT id, nama_akun, kode_akun FROM accounts WHERE user_id = ? AND is_kas = 1 ORDER BY nama_akun";
            $stmt_kas = $conn->prepare($kas_sql);
            $stmt_kas->bind_param('i', $user_id);
            $stmt_kas->execute();
            $kas_accounts = $stmt_kas->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_kas->close();

            $nominal_default = get_setting('nominal_wajib_belanja', 50000, $conn);

            echo json_encode([
                'success' => true,
                'anggota' => $anggota,
                'kas_accounts' => $kas_accounts,
                'nominal_default' => $nominal_default
            ]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle POST request for saving data
        // Ambil data dari JSON body karena kita mengirim struktur array kompleks
        $data = json_decode(file_get_contents('php://input'), true);
        
        $tanggal = $data['tanggal'];
        $metode_pembayaran = $data['metode_pembayaran'];
        $akun_kas_id = (int)$data['akun_kas_id'];
        $items = $data['items'] ?? [];
        $created_by = $_SESSION['user_id'];

        // Validation
        if (empty($tanggal) || empty($akun_kas_id) || empty($items)) {
            throw new Exception("Data transaksi tidak lengkap.");
        }
        
        check_period_lock($tanggal, $conn);

        $conn->begin_transaction();

        // Ambil akun hutang WB
        $akun_hutang_wb_id = get_setting('wajib_belanja_liability_account_id', null, $conn);
        if (!$akun_hutang_wb_id) {
            throw new Exception("Akun Hutang Wajib Belanja belum diatur di Pengaturan.");
        }

        // Prepare statements
        $stmt_insert = $conn->prepare("INSERT INTO transaksi_wajib_belanja (user_id, anggota_id, tanggal, jumlah, metode_pembayaran, akun_kas_id, keterangan, nomor_referensi, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_update_anggota = $conn->prepare("UPDATE anggota SET saldo_wajib_belanja = saldo_wajib_belanja + ? WHERE id = ?");

        $total_jumlah = 0;
        $total_processed = 0;
        $batch_id = date('YmdHis'); // Untuk referensi unik per batch jika perlu
        
        foreach ($items as $index => $item) {
            $anggota_id = (int)$item['anggota_id'];
            $jumlah = (float)$item['jumlah'];
            $ket_row = !empty($item['keterangan']) ? $item['keterangan'] : 'Setoran Wajib Belanja';
            
            // Generate nomor referensi unik per item
            $nomor_referensi = 'WB-' . $batch_id . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            
            // 1. Insert Transaksi
            $stmt_insert->bind_param('iisdsissi', $user_id, $anggota_id, $tanggal, $jumlah, $metode_pembayaran, $akun_kas_id, $ket_row, $nomor_referensi, $created_by);
            $stmt_insert->execute();

            // 2. Update Saldo Anggota
            $stmt_update_anggota->bind_param('di', $jumlah, $anggota_id);
            $stmt_update_anggota->execute();

            $total_jumlah += $jumlah;
            $total_processed++;
        }

        // 3. Buat Jurnal Rekap (Satu jurnal untuk satu batch)
        if ($total_jumlah > 0) {
            $keterangan_jurnal = "Setoran Wajib Belanja Kolektif ($total_processed Anggota)";
            $nomor_ref_jurnal = 'JRN-WB-' . $batch_id;
            
            // Buat Header Jurnal (jurnal_entries)
            $stmt_je = $conn->prepare("INSERT INTO jurnal_entries (user_id, tanggal, keterangan, created_by) VALUES (?, ?, ?, ?)");
            $stmt_je->bind_param('issi', $user_id, $tanggal, $keterangan_jurnal, $created_by);
            $stmt_je->execute();
            $jurnal_id = $stmt_je->insert_id;
            $stmt_je->close();

            // Insert Jurnal Details (Debit Kas & Kredit Hutang)
            $stmt_jd = $conn->prepare("INSERT INTO jurnal_details (jurnal_entry_id, account_id, debit, kredit) VALUES (?, ?, ?, ?)");
            $zero = 0;
            // Debit Kas
            $stmt_jd->bind_param('iidd', $jurnal_id, $akun_kas_id, $total_jumlah, $zero);
            $stmt_jd->execute();
            // Kredit Hutang
            $stmt_jd->bind_param('iidd', $jurnal_id, $akun_hutang_wb_id, $zero, $total_jumlah);
            $stmt_jd->execute();
            $stmt_jd->close();
            
            // Insert General Ledger (Rekap)
            // Pastikan kolom nomor_referensi terisi agar muncul di buku besar
            $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, unit, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'jurnal', 'toko', ?)");
            
            // Debit Kas
            $stmt_gl->bind_param('isssiddii', $user_id, $tanggal, $keterangan_jurnal, $nomor_ref_jurnal, $akun_kas_id, $total_jumlah, $zero, $jurnal_id, $created_by);
            $stmt_gl->execute();
            
            // Kredit Hutang
            $stmt_gl->bind_param('isssiddii', $user_id, $tanggal, $keterangan_jurnal, $nomor_ref_jurnal, $akun_hutang_wb_id, $zero, $total_jumlah, $jurnal_id, $created_by);
            $stmt_gl->execute();
            $stmt_gl->close();
        }

        $conn->commit();
        
        log_activity($_SESSION['username'], 'Tambah Setoran WB Kolektif', "Menambah setoran Wajib Belanja untuk $total_processed anggota. Total: " . number_format($total_jumlah));

        echo json_encode(['success' => true, 'message' => "Berhasil menyimpan setoran untuk $total_processed anggota."]);

    }
} catch (Exception $e) {
    if (isset($conn) && method_exists($conn, 'in_transaction') && $conn->in_transaction()) {
        $conn->rollback();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
