<?php
header('Content-Type: application/json');

// Muat komponen inti aplikasi
require_once __DIR__ . '/../includes/bootstrap.php';

$db = Database::getInstance()->getConnection();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_all':
        get_all_penjualan($db);
        break;
    case 'store':
        store_penjualan($db);
        break;
    case 'get_detail':
        get_penjualan_detail($db);
        break;
    case 'search_produk':
        search_produk($db);
        break;
    case 'void':
        void_penjualan($db);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
        break;
}

function get_all_penjualan($db) {
    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $searchTerm = $_GET['search'] ?? '';
        $offset = ($page - 1) * $limit;

        $sql = "SELECT p.id, p.nomor_referensi, p.tanggal_penjualan, p.customer_name, p.total, p.status, u.username 
                FROM penjualan p
                LEFT JOIN users u ON p.created_by = u.id";
        $countSql = "SELECT COUNT(p.id) as total FROM penjualan p LEFT JOIN users u ON p.created_by = u.id";

        if (!empty($searchTerm)) {
            $sql .= " WHERE p.nomor_referensi LIKE ? OR u.username LIKE ?";
            $countSql .= " WHERE p.nomor_referensi LIKE ? OR u.username LIKE ?";
        }

        $sql .= " ORDER BY p.tanggal_penjualan DESC, p.id DESC LIMIT ? OFFSET ?";
        
        $stmt = $db->prepare($sql);
        $countStmt = $db->prepare($countSql);

        if (!empty($searchTerm)) {
            $search = "%{$searchTerm}%";
            $stmt->bind_param('ssii', $search, $search, $limit, $offset);
            $countStmt->bind_param('ss', $search, $search);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $countStmt->execute();
        $countResult = $countStmt->get_result()->fetch_assoc();
        $total = $countResult['total'];
        $countStmt->close();

        echo json_encode([
            'success' => true,
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil data: ' . $e->getMessage()]);
    }
}

function store_penjualan($db) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['tanggal']) || empty($data['items']) || !is_array($data['items'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
        return;
    }

    $db->begin_transaction();

    try {
        // 1. Generate Nomor Faktur (lebih robust untuk mencegah duplikat)
        $tanggal_transaksi = $data['tanggal'];
        $date_for_prefix = date('Ymd', strtotime($tanggal_transaksi));
        $prefix = "INV/{$date_for_prefix}/";

        // Cari nomor referensi terakhir untuk hari transaksi untuk menentukan urutan berikutnya
        $stmt_last_ref = $db->prepare("SELECT nomor_referensi FROM penjualan WHERE nomor_referensi LIKE ? ORDER BY nomor_referensi DESC LIMIT 1");
        $like_prefix = $prefix . '%';
        $stmt_last_ref->bind_param('s', $like_prefix);
        $stmt_last_ref->execute();
        $last_ref_result = $stmt_last_ref->get_result()->fetch_assoc();
        $stmt_last_ref->close();

        $sequence = 1;
        if ($last_ref_result) {
            $last_sequence = (int)substr($last_ref_result['nomor_referensi'], -4);
            $sequence = $last_sequence + 1;
        }
        $nomor_referensi = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);

        // 2. Insert ke tabel 'penjualan'
        $tanggal = $data['tanggal'];
        $customer_name = $data['customer_name'] ?? 'Umum'; // Ambil customer_name
        $subtotal = $data['subtotal'];
        $discount = $data['discount'];
        $total = $data['total'];
        $bayar = $data['bayar'];
        $kembali = $data['kembali'];
        $keterangan = isset($data['catatan']) ? trim($data['catatan']) : '';
        $user_id = 1; // ID Pemilik Data (Toko)
        $logged_in_user_id = (int)$_SESSION['user_id']; // ID User yang login (Actor)

        // Tambahkan info metode pembayaran ke keterangan jika bukan tunai
        // (Opsional: jika tabel penjualan punya kolom payment_method, simpan di sana)
        $payment_method = $data['payment_method'] ?? 'cash';
        $payment_account_id = $data['payment_account_id'] ?? null;
        if ($payment_method !== 'cash') {
            $keterangan = trim("[" . strtoupper($payment_method) . "] " . $keterangan);
        }

        if (empty($keterangan)) {
            $keterangan = "Penjualan " . $nomor_referensi;
        }

        $stmt = $db->prepare(
            "INSERT INTO penjualan (user_id, nomor_referensi, tanggal_penjualan, customer_name, subtotal, discount, total, bayar, kembali, keterangan, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('isssdddsssi', $user_id, $nomor_referensi, $tanggal, $customer_name, $subtotal, $discount, $total, $bayar, $kembali, $keterangan, $logged_in_user_id);
        $stmt->execute();
        $penjualanId = $stmt->insert_id;
        $stmt->close();

        // 3. Insert ke tabel 'penjualan_detail' dan update stok
        $detailStmt = $db->prepare(
            "INSERT INTO penjualan_details (penjualan_id, item_id, deskripsi_item, price, quantity, discount, subtotal) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $updateStokStmt = $db->prepare("UPDATE items SET stok = stok - ? WHERE id = ? AND user_id = ?");
        $kartuStokStmt = $db->prepare("INSERT INTO kartu_stok (tanggal, item_id, debit, kredit, keterangan, ref_id, source, user_id) VALUES (?, ?, 0, ?, ?, ?, 'penjualan', ?)");
 
        foreach ($data['items'] as $item) {
            // Cek stok sebelum mengurangi
            $stokCheckStmt = $db->prepare("SELECT stok FROM items WHERE id = ? FOR UPDATE");
            $stokCheckStmt->bind_param('i', $item['id']);
            $stokCheckStmt->execute();
            $currentStok = $stokCheckStmt->get_result()->fetch_assoc()['stok'];
            $stokCheckStmt->close();

            if ($currentStok < $item['qty']) {
                throw new Exception("Stok untuk barang '{$item['nama']}' tidak mencukupi.");
            }
 
            $detailStmt->bind_param('iisidid', $penjualanId, $item['id'], $item['nama'], $item['harga'], $item['qty'], $item['discount'], $item['subtotal']);
            $detailStmt->execute();

            $updateStokStmt->bind_param('iii', $item['qty'], $item['id'], $user_id);
            $updateStokStmt->execute();

            // Catat ke Kartu Stok (Barang Keluar)
            $ksKeterangan = "Penjualan #{$nomor_referensi}";
            $kartuStokStmt->bind_param('siisii', $tanggal, $item['id'], $item['qty'], $ksKeterangan, $penjualanId, $user_id);
            $kartuStokStmt->execute();
        }
        $detailStmt->close();
        $updateStokStmt->close();
        $kartuStokStmt->close();

        // 4. Log aktivitas
        $keterangan_log = "Membuat transaksi penjualan baru #{$nomor_referensi} dengan total " . number_format($total);
        log_activity($_SESSION['username'], 'Buat Penjualan', $keterangan_log);

        // 5. Integrasi Akuntansi ke General Ledger
        // Tentukan akun debit (Kas/Bank)
        $debit_acc_id = null;
        if ($payment_method !== 'cash' && !empty($payment_account_id)) {
            $debit_acc_id = $payment_account_id; // Gunakan akun pilihan user (Bank/QRIS)
        } else {
            $debit_acc_id = get_setting('default_sales_cash_account_id', null, $db); // Gunakan default tunai
        }

        if (!$debit_acc_id) {
            throw new Exception("Akun penerimaan pembayaran (Kas/Bank) belum ditentukan.");
        }

        $stmt_gl = $db->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'penjualan', ?)");
        if (!$stmt_gl) {
            throw new Exception("Gagal prepare statement GL: " . $db->error);
        }
        $zero = 0.00;

        // Jurnal 1: (Dr) Kas, (Cr) Pendapatan Penjualan
        // Kita akan mengelompokkan pendapatan berdasarkan akun yang di-set di tiap barang
        $revenue_totals = [];
        $cogs_totals = [];
        $inventory_totals = [];

        $item_details_stmt = $db->prepare("SELECT harga_beli, revenue_account_id, cogs_account_id, inventory_account_id FROM items WHERE id = ?");
        foreach ($data['items'] as $item) {
            $item_details_stmt->bind_param('i', $item['id']);
            $item_details_stmt->execute();
            $item_acc_details = $item_details_stmt->get_result()->fetch_assoc();

            $null_var = null; // Variabel untuk dilewatkan sebagai referensi
            $revenue_acc = $item_acc_details['revenue_account_id'] ?? get_setting('default_sales_revenue_account_id', $null_var, $db);
            $cogs_acc = $item_acc_details['cogs_account_id'] ?? get_setting('default_cogs_account_id', $null_var, $db);
            $inv_acc = $item_acc_details['inventory_account_id'] ?? get_setting('default_inventory_account_id', $null_var, $db);

            if (!$revenue_acc || !$cogs_acc || !$inv_acc) {
                throw new Exception("Akun default untuk Pendapatan/HPP/Persediaan belum diatur di Pengaturan > Akuntansi.");
            }

            if (!isset($revenue_totals[$revenue_acc])) $revenue_totals[$revenue_acc] = 0;
            $revenue_totals[$revenue_acc] += $item['subtotal'];
            
            $hpp_amount = $item['qty'] * (float)$item_acc_details['harga_beli'];
            if (!isset($cogs_totals[$cogs_acc])) $cogs_totals[$cogs_acc] = 0;
            $cogs_totals[$cogs_acc] += $hpp_amount;

            if (!isset($inventory_totals[$inv_acc])) $inventory_totals[$inv_acc] = 0;
            $inventory_totals[$inv_acc] += $hpp_amount;
        }
        $item_details_stmt->close();

        // (Dr) Kas
        $stmt_gl->bind_param('isssiddii', $user_id, $tanggal, $keterangan, $nomor_referensi, $debit_acc_id, $total, $zero, $penjualanId, $logged_in_user_id);
        if (!$stmt_gl->execute()) {
            throw new Exception("Gagal insert GL (Kas): " . $stmt_gl->error);
        }
        // (Cr) Pendapatan (bisa lebih dari satu akun)
        foreach ($revenue_totals as $acc_id => $sub_total) {
            $stmt_gl->bind_param('isssiddii', $user_id, $tanggal, $keterangan, $nomor_referensi, $acc_id, $zero, $sub_total, $penjualanId, $logged_in_user_id);
            if (!$stmt_gl->execute()) {
                throw new Exception("Gagal insert GL (Pendapatan): " . $stmt_gl->error);
            }
        }

        // Jurnal 2: (Dr) HPP, (Cr) Persediaan
        $hpp_keterangan = "HPP untuk {$nomor_referensi}";
        
        // (Dr) Beban Pokok Penjualan (HPP)
        foreach ($cogs_totals as $acc_id => $amount) {
            if ($amount > 0) {
                $stmt_gl->bind_param('isssiddii', $user_id, $tanggal, $hpp_keterangan, $nomor_referensi, $acc_id, $amount, $zero, $penjualanId, $logged_in_user_id);
                if (!$stmt_gl->execute()) {
                    throw new Exception("Gagal insert GL (HPP): " . $stmt_gl->error);
                }
            }
        }

        // (Cr) Persediaan Barang
        foreach ($inventory_totals as $acc_id => $amount) {
            if ($amount > 0) {
                $stmt_gl->bind_param('isssiddii', $user_id, $tanggal, $hpp_keterangan, $nomor_referensi, $acc_id, $zero, $amount, $penjualanId, $logged_in_user_id);
                if (!$stmt_gl->execute()) {
                    throw new Exception("Gagal insert GL (Persediaan): " . $stmt_gl->error);
                }
            }
        }
        
        $stmt_gl->close();

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Transaksi penjualan berhasil disimpan.', 'id' => $penjualanId]);

    } catch (Exception $e) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function void_penjualan($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $user_id = 1; // ID Pemilik Data (Toko)
    $logged_in_user_id = (int)$_SESSION['user_id'];

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID transaksi tidak valid.']);
        return;
    }

    $db->begin_transaction();

    try {
        // 1. Ambil data transaksi yang akan dibatalkan
        $stmt = $db->prepare("SELECT * FROM penjualan WHERE id = ? AND user_id = ? FOR UPDATE");
        $stmt->bind_param('ii', $id, $user_id);
        $stmt->execute();
        $penjualan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$penjualan) throw new Exception("Transaksi tidak ditemukan.");
        if ($penjualan['status'] === 'void') throw new Exception("Transaksi sudah pernah dibatalkan.");

        // Cek periode lock
        check_period_lock($penjualan['tanggal_penjualan'], $db);

        // 2. Ambil detail item penjualan
        $stmt_details = $db->prepare("SELECT * FROM penjualan_details WHERE penjualan_id = ?");
        $stmt_details->bind_param('i', $id);
        $stmt_details->execute();
        $details = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_details->close();

        // 3. Kembalikan stok barang
        $stmt_update_stok = $db->prepare("UPDATE items SET stok = stok + ? WHERE id = ? AND user_id = ?");
        $stmt_ks_void = $db->prepare("INSERT INTO kartu_stok (tanggal, item_id, debit, kredit, keterangan, ref_id, source, user_id) VALUES (NOW(), ?, ?, 0, ?, ?, 'void_penjualan', ?)");

        foreach ($details as $item) {
            $stmt_update_stok->bind_param('iii', $item['quantity'], $item['item_id'], $user_id);
            $stmt_update_stok->execute();

            // Catat ke Kartu Stok (Barang Masuk Kembali / Reversal)
            $ksKeterangan = "Batal Penjualan #{$penjualan['nomor_referensi']}";
            $stmt_ks_void->bind_param('iisii', $item['item_id'], $item['quantity'], $ksKeterangan, $id, $user_id);
            $stmt_ks_void->execute();
        }
        $stmt_update_stok->close();
        $stmt_ks_void->close();

        // 4. Buat Jurnal Pembalik (Reversal Entry) di General Ledger
        // Ambil data dari jurnal asli untuk dibalik
        $stmt_original_gl = $db->prepare("SELECT * FROM general_ledger WHERE ref_id = ? AND ref_type = 'penjualan'");
        $stmt_original_gl->bind_param('i', $id);
        $stmt_original_gl->execute();
        $original_entries = $stmt_original_gl->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_original_gl->close();

        if (empty($original_entries)) {
            throw new Exception("Jurnal akuntansi asli untuk transaksi ini tidak ditemukan.");
        }

        $stmt_gl_reverse = $db->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'penjualan', ?)");
        
        $reversal_date = date('Y-m-d'); // Tanggal pembatalan adalah hari ini
        $reversal_keterangan = "PEMBATALAN: " . $penjualan['keterangan'];
        if (empty(trim($reversal_keterangan))) {
            $reversal_keterangan = "Pembatalan transaksi " . $penjualan['nomor_referensi'];
        }

        foreach ($original_entries as $entry) {
            // Balik posisi debit dan kredit
            $new_debit = $entry['kredit'];
            $new_kredit = $entry['debit'];
            
            // Untuk keterangan HPP, tambahkan prefix pembatalan juga
            $entry_keterangan = (strpos($entry['keterangan'], 'HPP') !== false) 
                ? "PEMBATALAN: " . $entry['keterangan'] 
                : $reversal_keterangan;

            $stmt_gl_reverse->bind_param('isssiddii', $user_id, $reversal_date, $entry_keterangan, $penjualan['nomor_referensi'], $entry['account_id'], $new_debit, $new_kredit, $id, $logged_in_user_id);
            $stmt_gl_reverse->execute();
        }
        $stmt_gl_reverse->close();

        // 5. Update status transaksi menjadi 'void'
        $stmt_void = $db->prepare("UPDATE penjualan SET status = 'void', total = 0, bayar = 0, kembali = 0 WHERE id = ?");
        $stmt_void->bind_param('i', $id);
        $stmt_void->execute();
        $stmt_void->close();

        // 6. Log aktivitas
        log_activity($_SESSION['username'], 'Batal Penjualan', "Membatalkan transaksi penjualan #{$penjualan['nomor_referensi']}");

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Transaksi berhasil dibatalkan.']);

    } catch (Exception $e) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function get_penjualan_detail($db) {
    try {
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
            return;
        }

        $stmt = $db->prepare("SELECT p.*, u.username as created_by_username FROM penjualan p JOIN users u ON p.created_by = u.id WHERE p.id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $penjualan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($penjualan) {
            $stmt = $db->prepare(
                "SELECT pd.*, i.nama_barang, i.harga_jual
                 FROM penjualan_details pd
                 JOIN items i ON pd.item_id = i.id
                 WHERE pd.penjualan_id = ?"
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $penjualan['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            echo json_encode(['success' => true, 'data' => $penjualan]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan.']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function search_produk($db) {
    $user_id = 1; // ID Pemilik Data (Toko)
    $term = $_GET['term'] ?? '';
    $search = "%{$term}%";
    $stmt = $db->prepare("SELECT id, sku, nama_barang, harga_jual, stok FROM items WHERE user_id = ? AND (nama_barang LIKE ? OR sku LIKE ?) AND stok > 0 LIMIT 10");
    $stmt->bind_param('iss', $user_id, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    echo json_encode($result);
}