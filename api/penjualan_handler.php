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
    case 'search_member':
        search_member($db);
        break;
    case 'void':
        void_penjualan($db);
        break;
    case 'update':
        update_penjualan($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
        break;
}

function get_all_penjualan($db)
{
    try {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $searchTerm = $_GET['search'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $offset = ($page - 1) * $limit;

        $sql = "SELECT p.id, p.nomor_referensi, p.tanggal_penjualan, p.customer_name, p.total, p.status, u.username 
                FROM penjualan p
                LEFT JOIN users u ON p.created_by = u.id";
        $countSql = "SELECT COUNT(p.id) as total FROM penjualan p LEFT JOIN users u ON p.created_by = u.id";

        $whereClauses = [];
        $params = [];
        $types = "";

        if (!empty($searchTerm)) {
            $whereClauses[] = "(p.nomor_referensi LIKE ? OR p.customer_name LIKE ? OR u.username LIKE ?)";
            $search = "%{$searchTerm}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= "sss";
        }

        if (!empty($startDate)) {
            $whereClauses[] = "DATE(p.tanggal_penjualan) >= ?";
            $params[] = $startDate;
            $types .= "s";
        }

        if (!empty($endDate)) {
            $whereClauses[] = "DATE(p.tanggal_penjualan) <= ?";
            $params[] = $endDate;
            $types .= "s";
        }

        if (!empty($whereClauses)) {
            $where = " WHERE " . implode(" AND ", $whereClauses);
            $sql .= $where;
            $countSql .= $where;
        }

        $sql .= " ORDER BY p.tanggal_penjualan DESC, p.id DESC LIMIT ? OFFSET ?";

        $stmt = $db->prepare($sql);
        $countStmt = $db->prepare($countSql);

        // Bind parameters for count query
        if (!empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }

        // Add limit and offset for main query
        $mainParams = array_merge($params, [$limit, $offset]);
        $mainTypes = $types . "ii";
        $stmt->bind_param($mainTypes, ...$mainParams);

        $stmt->execute();
        $data = stmt_fetch_all($stmt);
        $stmt->close();

        $countStmt->execute();
        $countResult = stmt_fetch_assoc($countStmt);
        $total = $countResult['total'];
        $countStmt->close();

        echo json_encode([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'limit' => $limit
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil data: ' . $e->getMessage()]);
    }
}

function store_penjualan($db)
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['tanggal']) || empty($data['items']) || !is_array($data['items'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
        return;
    }

    // Extract variables early (Pindahkan definisi variabel ke atas agar bisa divalidasi)
    $tanggal = $data['tanggal'];
    // Jika tanggal hanya YYYY-MM-DD (10 karakter), tambahkan jam real-time transaksi
    if (strlen($tanggal) === 10) {
        $tanggal .= ' ' . date('H:i:s');
    }
    $customer_name = $data['customer_name'] ?? 'Umum';
    $subtotal = $data['subtotal'];
    $discount = $data['discount'];
    $total = $data['total'];
    $bayar = (float) ($data['bayar'] ?? 0);
    $bayar_wb = (float) ($data['bayar_wb'] ?? 0);
    $anggota_id = !empty($data['anggota_id']) ? (int) $data['anggota_id'] : null;
    $kembali = $data['kembali'];
    $keterangan = isset($data['catatan']) ? trim($data['catatan']) : '';
    $user_id = 1; // ID Pemilik Data (Toko)
    $logged_in_user_id = (int) $_SESSION['user_id']; // ID User yang login (Actor)
    $payment_method = $data['payment_method'] ?? 'cash';
    $payment_account_id = $data['payment_account_id'] ?? null;
    $is_hutang = ($payment_method === 'hutang');

    $db->begin_transaction();

    try {
        // Validasi Saldo WB jika digunakan
        if ($bayar_wb > 0) {
            if (!$anggota_id)
                throw new Exception("Anggota harus dipilih untuk menggunakan Saldo WB.");
            $stmt_cek = $db->prepare("SELECT saldo_wajib_belanja FROM anggota WHERE id = ?");
            $stmt_cek->bind_param('i', $anggota_id);
            $stmt_cek->execute();
            $stmt_cek_res = stmt_fetch_assoc($stmt_cek);
            $saldo_wb = $stmt_cek_res ? $stmt_cek_res['saldo_wajib_belanja'] : 0;
            if ($saldo_wb < $bayar_wb)
                throw new Exception("Saldo Wajib Belanja tidak mencukupi.");
            $stmt_cek->close();
        }

        // Validasi Hutang
        if ($is_hutang && empty($anggota_id)) {
            throw new Exception("Untuk pembayaran Hutang, Anggota wajib dipilih.");
        }

        // 1. Generate Nomor Faktur (lebih robust untuk mencegah duplikat)
        $tanggal_transaksi = $tanggal;
        $date_for_prefix = date('Ymd', strtotime($tanggal_transaksi));
        $prefix = "INV/{$date_for_prefix}/";

        // Cari nomor referensi terakhir untuk hari transaksi untuk menentukan urutan berikutnya
        $stmt_last_ref = $db->prepare("SELECT nomor_referensi FROM penjualan WHERE nomor_referensi LIKE ? ORDER BY nomor_referensi DESC LIMIT 1");
        $like_prefix = $prefix . '%';
        $stmt_last_ref->bind_param('s', $like_prefix);
        $stmt_last_ref->execute();
        $last_ref_result = stmt_fetch_assoc($stmt_last_ref);
        $stmt_last_ref->close();

        $sequence = 1;
        if ($last_ref_result) {
            $last_sequence = (int) substr($last_ref_result['nomor_referensi'], -4);
            $sequence = $last_sequence + 1;
        }
        $nomor_referensi = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);

        // 2. Insert ke tabel 'penjualan'
        // Tambahkan info metode pembayaran ke keterangan jika bukan tunai
        // (Opsional: jika tabel penjualan punya kolom payment_method, simpan di sana)
        if ($payment_method !== 'cash' && $payment_method !== 'hutang') {
            $keterangan = trim("[" . strtoupper($payment_method) . "] " . $keterangan);
        }

        if (empty($keterangan)) {
            $keterangan = "Penjualan " . $nomor_referensi;
        }

        $stmt = $db->prepare(
            "INSERT INTO penjualan (user_id, customer_id, nomor_referensi, tanggal_penjualan, customer_name, subtotal, discount, total, bayar, kembali, keterangan, created_by, payment_method) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iisssdddsssis', $user_id, $anggota_id, $nomor_referensi, $tanggal, $customer_name, $subtotal, $discount, $total, $bayar, $kembali, $keterangan, $logged_in_user_id, $payment_method);
        $stmt->execute();
        $penjualanId = $stmt->insert_id;
        $stmt->close();

        // 3. Insert ke tabel 'penjualan_detail' dan update stok
        $detailStmt = $db->prepare(
            "INSERT INTO penjualan_details (penjualan_id, item_id, deskripsi_item, price, quantity, discount, subtotal, item_type) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $updateStokStmt = $db->prepare("UPDATE items SET stok = stok - ? WHERE id = ? AND user_id = ?");
        $kartuStokStmt = $db->prepare("INSERT INTO kartu_stok (tanggal, item_id, debit, kredit, keterangan, ref_id, source, user_id) VALUES (?, ?, 0, ?, ?, ?, 'penjualan', ?)");

        // Akun-akun konsinyasi (ambil sekali saja)
        $consignment_settings = [
            'kas_acc_id' => get_setting('consignment_cash_account', null, $db),
            'revenue_acc_id' => get_setting('consignment_revenue_account', null, $db),
            'cogs_acc_id' => get_setting('consignment_cogs_account', null, $db),
            'payable_acc_id' => get_setting('consignment_payable_account', null, $db),
            'inventory_acc_id' => get_setting('consignment_inventory_account', null, $db)
        ];

        // Define default account IDs for normal items
        $default_revenue_acc_id = get_setting('default_sales_revenue_account_id', null, $db);
        $default_cogs_acc_id = get_setting('default_cogs_account_id', null, $db);
        $default_inventory_acc_id = get_setting('default_inventory_account_id', null, $db);

        $revenue_totals = [];
        $normal_cogs_totals = [];
        $normal_inventory_totals = [];
        $consignment_journal_entries = []; // Untuk entri HPP/Inventory konsinyasi individual

        foreach ($data['items'] as $item) {
            $item_type = $item['item_type'] ?? 'normal';

            if ($item_type === 'normal') {
                // 1. Logika Barang Normal
                $stokCheckStmt = $db->prepare("SELECT stok, harga_beli, revenue_account_id, inventory_account_id, cogs_account_id FROM items WHERE id = ? FOR UPDATE");
                $stokCheckStmt->bind_param('i', $item['id']);
                $stokCheckStmt->execute();
                $item_db = stmt_fetch_assoc($stokCheckStmt);
                $stokCheckStmt->close();

                if (!$item_db)
                    throw new Exception("Barang '{$item['nama']}' tidak ditemukan.");
                if ($item_db['stok'] < $item['qty'])
                    throw new Exception("Stok untuk '{$item['nama']}' tidak mencukupi.");

                // Update Stok & Kartu Stok
                $updateStokStmt->bind_param('iii', $item['qty'], $item['id'], $user_id);
                $updateStokStmt->execute();
                $ksKeterangan = "Penjualan #{$nomor_referensi}";
                $kartuStokStmt->bind_param('siisii', $tanggal, $item['id'], $item['qty'], $ksKeterangan, $penjualanId, $user_id);
                $kartuStokStmt->execute();

                // Group Accounting Totals
                $rev_acc = $item_db['revenue_account_id'] ?? $default_revenue_acc_id;
                $inv_acc = $item_db['inventory_account_id'] ?? $default_inventory_acc_id;
                $cogs_acc = $item_db['cogs_account_id'] ?? $default_cogs_acc_id;

                if (!$rev_acc || !$inv_acc || !$cogs_acc)
                    throw new Exception("Akun default akuntansi belum lengkap.");

                if (!isset($revenue_totals[$rev_acc]))
                    $revenue_totals[$rev_acc] = 0;
                $revenue_totals[$rev_acc] += $item['subtotal'];

                $hpp_val = $item['qty'] * (float) $item_db['harga_beli'];
                if (!isset($normal_cogs_totals[$cogs_acc]))
                    $normal_cogs_totals[$cogs_acc] = 0;
                $normal_cogs_totals[$cogs_acc] += $hpp_val;

                if (!isset($normal_inventory_totals[$inv_acc]))
                    $normal_inventory_totals[$inv_acc] = 0;
                $normal_inventory_totals[$inv_acc] += $hpp_val;

            } else {
                // 2. Logika Barang Konsinyasi
                $stmt_cons = $db->prepare("SELECT ci.*, s.nama_pemasok FROM consignment_items ci JOIN suppliers s ON ci.supplier_id = s.id WHERE ci.id = ?");
                $stmt_cons->bind_param('i', $item['id']);
                $stmt_cons->execute();
                $item_cons = stmt_fetch_assoc($stmt_cons);
                $stmt_cons->close();

                if (!$item_cons)
                    throw new Exception("Barang konsinyasi '{$item['nama']}' tidak ditemukan.");

                foreach ($consignment_settings as $key => $val) {
                    if (empty($val))
                        throw new Exception("Pengaturan akun konsinyasi ($key) belum diatur.");
                }

                // NEW LOGIC: Separate Commission from Purchase Price
                $total_harga_beli = $item['qty'] * (float) $item_cons['harga_beli'];
                $komisi = $item['subtotal'] - $total_harga_beli;

                // Group Commission Revenue
                $cons_rev_acc = $consignment_settings['revenue_acc_id'];
                if (!isset($revenue_totals[$cons_rev_acc]))
                    $revenue_totals[$cons_rev_acc] = 0;
                $revenue_totals[$cons_rev_acc] += $komisi;

                // Individual Entries for Payable (Utang Titipan)
                $consignment_journal_entries[] = [
                    'consignment_item_id' => $item['id'],
                    'qty' => $item['qty'],
                    'payable_acc_id' => $consignment_settings['payable_acc_id'],
                    'amount' => $total_harga_beli,
                    'desc' => "Penjualan Konsinyasi: {$item['qty']} x {$item['nama']} ({$item_cons['nama_pemasok']})"
                ];
            }

            // Simpan Detail Transaksi
            $detailStmt->bind_param('iisidids', $penjualanId, $item['id'], $item['nama'], $item['harga'], $item['qty'], $item['discount'], $item['subtotal'], $item_type);
            $detailStmt->execute();
        }
        $detailStmt->close();
        $updateStokStmt->close();
        $kartuStokStmt->close();

        // Integrasi Keuangan
        $stmt_gl = $db->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, consignment_item_id, qty, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'penjualan', ?, ?, ?)");
        $zero = 0.00;
        $null_val = null;

        // A. Handle WB Payment
        if ($bayar_wb > 0) {
            $stmt_upd_wb = $db->prepare("UPDATE anggota SET saldo_wajib_belanja = saldo_wajib_belanja - ? WHERE id = ?");
            $stmt_upd_wb->bind_param('di', $bayar_wb, $anggota_id);
            $stmt_upd_wb->execute();
            $stmt_upd_wb->close();

            $ket_wb = "Pembayaran Belanja #$nomor_referensi";
            $stmt_log_wb = $db->prepare("INSERT INTO transaksi_wajib_belanja (user_id, anggota_id, tanggal, jenis, jumlah, metode_pembayaran, keterangan, nomor_referensi, created_by) VALUES (?, ?, ?, 'belanja', ?, 'potong_saldo', ?, ?, ?)");
            $stmt_log_wb->bind_param('iisdssi', $user_id, $anggota_id, $tanggal, $bayar_wb, $ket_wb, $nomor_referensi, $logged_in_user_id);
            $stmt_log_wb->execute();
            $stmt_log_wb->close();

            $akun_utang_wb = get_setting('wajib_belanja_liability_account_id', null, $db);
            if ($akun_utang_wb) {
                $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $ket_wb, $nomor_referensi, $akun_utang_wb, $bayar_wb, $zero, $penjualanId, $null_val, $null_val, $logged_in_user_id);
                $stmt_gl->execute();
            }
        }

        // B. Debit Kas / Piutang
        $debit_acc_id = $is_hutang ? get_setting('default_sales_cash_account_id', null, $db) : ($payment_account_id ?: get_setting('default_sales_cash_account_id', null, $db));
        $cash_portion = $is_hutang ? $bayar : max(0, $total - $bayar_wb);
        $piutang_portion = $is_hutang ? max(0, $total - $bayar - $bayar_wb) : 0;

        if ($cash_portion > 0) {
            $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $keterangan, $nomor_referensi, $debit_acc_id, $cash_portion, $zero, $penjualanId, $null_val, $null_val, $logged_in_user_id);
            $stmt_gl->execute();
        }
        if ($piutang_portion > 0) {
            $piutang_acc_id = get_setting('sales_receivable_account_id', null, $db);
            $ket_piutang = "Piutang Anggota: " . $customer_name;
            $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $ket_piutang, $nomor_referensi, $piutang_acc_id, $piutang_portion, $zero, $penjualanId, $null_val, $null_val, $logged_in_user_id);
            $stmt_gl->execute();
        }

        // B2. Debit Potongan Penjualan (Jika ada diskon global)
        if ($discount > 0) {
            $discount_acc_id = get_setting('sales_discount_account_id', null, $db);
            if ($discount_acc_id) {
                $ket_discount = "Potongan Penjualan #" . $nomor_referensi;
                $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $ket_discount, $nomor_referensi, $discount_acc_id, $discount, $zero, $penjualanId, $null_val, $null_val, $logged_in_user_id);
                $stmt_gl->execute();
            }
        }

        // C. Credit Revenue (Aggregated)
        foreach ($revenue_totals as $acc_id => $amount) {
            $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $keterangan, $nomor_referensi, $acc_id, $zero, $amount, $penjualanId, $null_val, $null_val, $logged_in_user_id);
            $stmt_gl->execute();
        }

        // D. Jurnal Stok - Normal (Aggregated)
        $hpp_ket = "HPP Penjualan #{$nomor_referensi}";
        foreach ($normal_cogs_totals as $acc_id => $amount) {
            $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $hpp_ket, $nomor_referensi, $acc_id, $amount, $zero, $penjualanId, $null_val, $null_val, $logged_in_user_id);
            $stmt_gl->execute();
        }
        foreach ($normal_inventory_totals as $acc_id => $amount) {
            $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $hpp_ket, $nomor_referensi, $acc_id, $zero, $amount, $penjualanId, $null_val, $null_val, $logged_in_user_id);
            $stmt_gl->execute();
        }

        // E. Jurnal Stok - Konsinyasi (Individual per Item)
        foreach ($consignment_journal_entries as $ent) {
            // (Cr) Utang Konsinyasi (Utang Titipan)
            $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $ent['desc'], $nomor_referensi, $ent['payable_acc_id'], $zero, $ent['amount'], $penjualanId, $ent['consignment_item_id'], $ent['qty'], $logged_in_user_id);
            $stmt_gl->execute();
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

function void_penjualan($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $user_id = 1; // ID Pemilik Data (Toko)
    $logged_in_user_id = (int) $_SESSION['user_id'];

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
        $penjualan = stmt_fetch_assoc($stmt);
        $stmt->close();

        if (!$penjualan)
            throw new Exception("Transaksi tidak ditemukan.");
        if ($penjualan['status'] === 'void')
            throw new Exception("Transaksi sudah pernah dibatalkan.");

        // Cek periode lock
        check_period_lock($penjualan['tanggal_penjualan'], $db);

        // 2. Ambil detail item penjualan
        $stmt_details = $db->prepare("SELECT * FROM penjualan_details WHERE penjualan_id = ?");
        $stmt_details->bind_param('i', $id);
        $stmt_details->execute();
        $details = stmt_fetch_all($stmt_details);
        $stmt_details->close();

        // 3. Kembalikan stok barang (Hanya untuk tipe NORMAL)
        $stmt_update_stok = $db->prepare("UPDATE items SET stok = stok + ? WHERE id = ? AND user_id = ?");
        $stmt_ks_void = $db->prepare("INSERT INTO kartu_stok (tanggal, item_id, debit, kredit, keterangan, ref_id, source, user_id) VALUES (NOW(), ?, ?, 0, ?, ?, 'void_penjualan', ?)");

        foreach ($details as $item) {
            $item_type = $item['item_type'] ?? 'normal';
            if ($item_type === 'normal') {
                $stmt_update_stok->bind_param('iii', $item['quantity'], $item['item_id'], $user_id);
                $stmt_update_stok->execute();

                // Catat ke Kartu Stok (Barang Masuk Kembali / Reversal)
                $ksKeterangan = "Batal Penjualan #{$penjualan['nomor_referensi']}";
                $stmt_ks_void->bind_param('iisii', $item['item_id'], $item['quantity'], $ksKeterangan, $id, $user_id);
                $stmt_ks_void->execute();
            }
        }
        $stmt_update_stok->close();
        $stmt_ks_void->close();

        // 4. Buat Jurnal Pembalik (Reversal Entry) di General Ledger
        // Ambil data dari jurnal asli untuk dibalik
        $stmt_original_gl = $db->prepare("SELECT * FROM general_ledger WHERE ref_id = ? AND ref_type = 'penjualan'");
        $stmt_original_gl->bind_param('i', $id);
        $stmt_original_gl->execute();
        $original_entries = stmt_fetch_all($stmt_original_gl);
        $stmt_original_gl->close();

        if (empty($original_entries)) {
            throw new Exception("Jurnal akuntansi asli untuk transaksi ini tidak ditemukan.");
        }

        $stmt_gl_reverse = $db->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, consignment_item_id, qty, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'penjualan', ?, ?, ?)");

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

            $stmt_gl_reverse->bind_param('isssiddiiii', $user_id, $reversal_date, $entry_keterangan, $penjualan['nomor_referensi'], $entry['account_id'], $new_debit, $new_kredit, $id, $entry['consignment_item_id'], $entry['qty'], $logged_in_user_id);
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

function get_penjualan_detail($db)
{
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
        $penjualan = stmt_fetch_assoc($stmt);
        $stmt->close();

        if ($penjualan) {
            $stmt = $db->prepare(
                "SELECT 
                    pd.*, 
                    COALESCE(i.nama_barang, ci.nama_barang, pd.deskripsi_item) as nama_barang,
                    COALESCE(i.harga_jual, ci.harga_jual, pd.price) as harga_jual,
                    COALESCE(i.harga_beli, ci.harga_beli, 0) as harga_beli
                 FROM penjualan_details pd
                 LEFT JOIN items i ON pd.item_id = i.id AND pd.item_type = 'normal'
                 LEFT JOIN consignment_items ci ON pd.item_id = ci.id AND pd.item_type = 'consignment'
                 WHERE pd.penjualan_id = ?"
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $penjualan['items'] = stmt_fetch_all($stmt);
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

function search_produk($db)
{
    try {
        $user_id = 1; // ID Pemilik Data (Toko)
        $term = $_GET['term'] ?? '';
        $search = "%{$term}%";

        // Ambil akun konsinyasi (Hutang Titipan) untuk tracking stok terjual
        $consignment_acc_id = get_setting('consignment_payable_account', null, $db);

        // Query untuk barang reguler
        $sql_normal = "
            SELECT 
                id, 
                sku COLLATE utf8mb4_general_ci as sku, 
                barcode COLLATE utf8mb4_general_ci as barcode, 
                nama_barang COLLATE utf8mb4_general_ci as nama_barang, 
                harga_jual, stok, 'normal' as item_type 
            FROM items 
            WHERE user_id = ? AND (nama_barang LIKE ? OR sku LIKE ? OR barcode LIKE ?) AND stok > 0";

        // Query untuk barang konsinyasi
        $sql_consignment = "
            SELECT 
                ci.id, 
                ci.sku COLLATE utf8mb4_general_ci as sku, 
                ci.barcode COLLATE utf8mb4_general_ci as barcode, 
                ci.nama_barang COLLATE utf8mb4_general_ci as nama_barang, 
                ci.harga_jual,
                (
                    ci.stok_awal 
                    + COALESCE(restock.qty_masuk, 0) 
                    - COALESCE(sales.qty_terjual, 0)
                ) as stok,
                'consignment' as item_type
            FROM consignment_items ci
            LEFT JOIN (
                SELECT consignment_item_id, SUM(qty) as qty_masuk 
                FROM consignment_restocks 
                GROUP BY consignment_item_id
            ) restock ON ci.id = restock.consignment_item_id
            LEFT JOIN (
                SELECT consignment_item_id, SUM(IF(debit > 0, -qty, qty)) as qty_terjual 
                FROM general_ledger 
                WHERE ref_type IN ('jurnal', 'penjualan') 
                AND account_id = ?
                GROUP BY consignment_item_id
            ) sales ON ci.id = sales.consignment_item_id
            WHERE ci.user_id = ? AND (ci.nama_barang LIKE ? OR ci.sku LIKE ? OR ci.barcode LIKE ?)
            HAVING stok > 0";

        // Gunakan wrapper SELECT * untuk kompatibilitas UNION yang lebih baik pada beberapa driver
        $query = "SELECT * FROM (
            ($sql_normal) 
            UNION 
            ($sql_consignment)
        ) AS combined_results LIMIT 15";

        $stmt = $db->prepare($query);

        // Params mapping: 
        // 1-4 for normal (i, s, s, s)
        // 5-9 for consignment (i, i, s, s, s) -> account_id, user_id, name, sku, barcode
        $stmt->bind_param(
            'isssiisss',
            $user_id,
            $search,
            $search,
            $search, // Normal parameters
            $consignment_acc_id,
            $user_id,
            $search,
            $search,
            $search // Consignment parameters
        );

        $stmt->execute();
        $result = stmt_fetch_all($stmt);
        $stmt->close();

        echo json_encode($result);
    } catch (Exception $e) {
        // Jika terjadi error, kirim pesan error dengan status 500
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mencari barang: ' . $e->getMessage()]);
    }
}

function search_member($db)
{
    $user_id = 1; // ID Pemilik Data (Toko)
    $term = $_GET['term'] ?? '';
    $search = "%{$term}%";
    $stmt = $db->prepare("SELECT id, nomor_anggota, nama_lengkap, saldo_wajib_belanja FROM anggota WHERE user_id = ? AND status='aktif' AND (nama_lengkap LIKE ? OR nomor_anggota LIKE ?) LIMIT 10");
    $stmt->bind_param('iss', $user_id, $search, $search);
    $stmt->execute();
    $result = stmt_fetch_all($stmt);
    $stmt->close();
    echo json_encode(['success' => true, 'data' => $result]);
}

function update_penjualan($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['id'] ?? 0);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID transaksi tidak valid.']);
        return;
    }

    $user_id = 1; // ID Pemilik Data (Toko)
    $logged_in_user_id = (int) $_SESSION['user_id'];

    $db->begin_transaction();

    try {
        // 1. Ambil data lama
        $stmt_old = $db->prepare("SELECT * FROM penjualan WHERE id = ? AND user_id = ? FOR UPDATE");
        $stmt_old->bind_param('ii', $id, $user_id);
        $stmt_old->execute();
        $old_penjualan = stmt_fetch_assoc($stmt_old);
        $stmt_old->close();

        if (!$old_penjualan)
            throw new Exception("Transaksi tidak ditemukan.");
        if ($old_penjualan['status'] === 'void')
            throw new Exception("Transaksi yang sudah dibatalkan tidak bisa di-edit.");

        // Cek periode lock
        if (function_exists('check_period_lock')) {
            check_period_lock($old_penjualan['tanggal_penjualan'], $db);
        }

        // 2. KEMBALIKAN STOK LAMA (Hanya Barang Normal)
        $stmt_old_details = $db->prepare("SELECT * FROM penjualan_details WHERE penjualan_id = ?");
        $stmt_old_details->bind_param('i', $id);
        $stmt_old_details->execute();
        $old_details = stmt_fetch_all($stmt_old_details);
        $stmt_old_details->close();

        $stmt_restore_stok = $db->prepare("UPDATE items SET stok = stok + ? WHERE id = ? AND user_id = ?");
        foreach ($old_details as $item) {
            if (($item['item_type'] ?? 'normal') === 'normal') {
                $stmt_restore_stok->bind_param('iii', $item['quantity'], $item['item_id'], $user_id);
                $stmt_restore_stok->execute();
            }
        }
        $stmt_restore_stok->close();

        // 3. HAPUS JURNAL LAMA (General Ledger)
        $stmt_del_gl = $db->prepare("DELETE FROM general_ledger WHERE ref_id = ? AND ref_type = 'penjualan'");
        $stmt_del_gl->bind_param('i', $id);
        $stmt_del_gl->execute();
        $stmt_del_gl->close();

        // 4. HAPUS KARTU STOK LAMA
        $stmt_del_ks = $db->prepare("DELETE FROM kartu_stok WHERE ref_id = ? AND source = 'penjualan'");
        $stmt_del_ks->bind_param('i', $id);
        $stmt_del_ks->execute();
        $stmt_del_ks->close();

        // 5. UPDATE HEADER PENJUALAN
        $tanggal = $data['tanggal'];
        // Pastikan format tanggal ke DB (tambah jam jika hanya tgl)
        if (strlen($tanggal) === 10)
            $tanggal .= ' ' . date('H:i:s');

        $customer_name = $data['customer_name'] ?? 'Umum';
        $subtotal = $data['subtotal'];
        $discount = $data['discount'];
        $total = $data['total'];
        $bayar = (float) ($data['bayar'] ?? 0);
        $kembali = $data['kembali'];
        $keterangan = isset($data['catatan']) ? trim($data['catatan']) : '';
        if (empty($keterangan))
            $keterangan = "Update Penjualan #{$old_penjualan['nomor_referensi']}";

        $payment_method = $data['payment_method'] ?? 'cash';
        $payment_account_id = !empty($data['payment_account_id']) ? (int) $data['payment_account_id'] : null;
        $anggota_id = !empty($data['anggota_id']) ? (int) $data['anggota_id'] : null;

        $stmt_upd = $db->prepare(
            "UPDATE penjualan SET 
                tanggal_penjualan = ?, customer_id = ?, customer_name = ?, 
                subtotal = ?, discount = ?, total = ?, bayar = ?, kembali = ?, 
                keterangan = ?, payment_method = ?
             WHERE id = ? AND user_id = ?"
        );
        $stmt_upd->bind_param('sisdddddssii', $tanggal, $anggota_id, $customer_name, $subtotal, $discount, $total, $bayar, $kembali, $keterangan, $payment_method, $id, $user_id);
        $stmt_upd->execute();
        $stmt_upd->close();

        // 6. HAPUS DETAIL LAMA DAN INSERT DETAIL BARU
        $stmt_del_details = $db->prepare("DELETE FROM penjualan_details WHERE penjualan_id = ?");
        $stmt_del_details->bind_param('i', $id);
        $stmt_del_details->execute();
        $stmt_del_details->close();

        // [INSERT DETAIL BARU & UPDATE STOK]
        $detailStmt = $db->prepare(
            "INSERT INTO penjualan_details (penjualan_id, item_id, deskripsi_item, price, quantity, discount, subtotal, item_type) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $updateStokStmt = $db->prepare("UPDATE items SET stok = stok - ? WHERE id = ? AND user_id = ?");
        $kartuStokStmt = $db->prepare("INSERT INTO kartu_stok (tanggal, item_id, debit, kredit, keterangan, ref_id, source, user_id) VALUES (?, ?, 0, ?, ?, ?, 'penjualan', ?)");

        // Akun-akun default
        $consignment_settings = [
            'kas_acc_id' => get_setting('consignment_cash_account', null, $db),
            'revenue_acc_id' => get_setting('consignment_revenue_account', null, $db),
            'cogs_acc_id' => get_setting('consignment_cogs_account', null, $db),
            'payable_acc_id' => get_setting('consignment_payable_account', null, $db),
            'inventory_acc_id' => get_setting('consignment_inventory_account', null, $db)
        ];
        $default_revenue_acc_id = get_setting('default_sales_revenue_account_id', null, $db);
        $default_cogs_acc_id = get_setting('default_cogs_account_id', null, $db);
        $default_inventory_acc_id = get_setting('default_inventory_account_id', null, $db);

        $revenue_totals = [];
        $normal_cogs_totals = [];
        $normal_inventory_totals = [];
        $zero = 0;

        foreach ($data['items'] as $item) {
            $item_type = $item['item_type'] ?? 'normal';
            $detailStmt->bind_param('iisdddds', $id, $item['id'], $item['nama'], $item['harga'], $item['qty'], $item['discount'], $item['subtotal'], $item_type);
            $detailStmt->execute();

            if ($item_type === 'normal') {
                $stokCheck = $db->prepare("SELECT harga_jual, revenue_account_id, inventory_account_id, cogs_account_id, harga_beli FROM items WHERE id = ?");
                $stokCheck->bind_param('i', $item['id']);
                $stokCheck->execute();
                $item_db = stmt_fetch_assoc($stokCheck);
                $stokCheck->close();

                // Update Stok & Kartu Stok
                $updateStokStmt->bind_param('iii', $item['qty'], $item['id'], $user_id);
                $updateStokStmt->execute();
                $ksKet = "Update Penjualan #{$old_penjualan['nomor_referensi']}";
                $kartuStokStmt->bind_param('siisii', $tanggal, $item['id'], $item['qty'], $ksKet, $id, $user_id);
                $kartuStokStmt->execute();

                // Accounting logic
                $rev_acc = $item_db['revenue_account_id'] ?? $default_revenue_acc_id;
                $inv_acc = $item_db['inventory_account_id'] ?? $default_inventory_acc_id;
                $cogs_acc = $item_db['cogs_account_id'] ?? $default_cogs_acc_id;

                if (!isset($revenue_totals[$rev_acc]))
                    $revenue_totals[$rev_acc] = 0;
                $revenue_totals[$rev_acc] += $item['subtotal'];

                $hpp_val = $item['qty'] * (float) $item_db['harga_beli'];
                if (!isset($normal_cogs_totals[$cogs_acc]))
                    $normal_cogs_totals[$cogs_acc] = 0;
                $normal_cogs_totals[$cogs_acc] += $hpp_val;

                if (!isset($normal_inventory_totals[$inv_acc]))
                    $normal_inventory_totals[$inv_acc] = 0;
                $normal_inventory_totals[$inv_acc] += $hpp_val;
            } else {
                // Konsinyasi logic
                $stmt_cons = $db->prepare("SELECT harga_beli FROM consignment_items WHERE id = ?");
                $stmt_cons->bind_param('i', $item['id']);
                $stmt_cons->execute();
                $item_cons = stmt_fetch_assoc($stmt_cons);
                $stmt_cons->close();

                $total_beli = $item['qty'] * (float) $item_cons['harga_beli'];
                $komisi = $item['subtotal'] - $total_beli;

                $cons_rev_acc = $consignment_settings['revenue_acc_id'];
                if (!isset($revenue_totals[$cons_rev_acc]))
                    $revenue_totals[$cons_rev_acc] = 0;
                $revenue_totals[$cons_rev_acc] += $komisi;

                // Entry Hutang Konsinyasi (Utang Titipan)
                $gl_payable_stmt = $db->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, consignment_item_id, qty, created_by) VALUES (?, ?, ?, ?, ?, 0, ?, ?, 'penjualan', ?, ?, ?)");
                $glKetP = "Utang barang konsinyasi: {$item['nama']} (#{$old_penjualan['nomor_referensi']})";
                $gl_payable_stmt->bind_param('isssiddiii', $user_id, $tanggal, $glKetP, $old_penjualan['nomor_referensi'], $consignment_settings['payable_acc_id'], $total_beli, $id, $item['id'], $item['qty'], $logged_in_user_id);
                $gl_payable_stmt->execute();
                $gl_payable_stmt->close();
            }
        }
        $detailStmt->close();
        $updateStokStmt->close();
        $kartuStokStmt->close();

        // 7. JURNAL BALANCING (Kas/Piutang)
        $gl_stmt = $db->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'penjualan', ?)");

        // DEBIT KAS/PIUTANG
        $is_hutang = ($payment_method === 'hutang');
        if ($is_hutang) {
            $debit_acc = get_setting('sales_receivable_account_id', null, $db);
        } else if ($payment_method === 'cash') {
            $debit_acc = get_setting('default_cash_in', null, $db);
        } else {
            $debit_acc = $payment_account_id;
        }

        if (empty($debit_acc))
            throw new Exception("Akun Debit (Kas/Piutang) tidak ditemukan.");

        $gl_stmt->bind_param('isssiddii', $user_id, $tanggal, $keterangan, $old_penjualan['nomor_referensi'], $debit_acc, $total, $zero, $id, $logged_in_user_id);
        $gl_stmt->execute();

        // 7b. DEBIT DISKON (Potongan Penjualan)
        if ($discount > 0) {
            $discount_acc_id = get_setting('sales_discount_account_id', null, $db);
            if ($discount_acc_id) {
                $ket_discount = "Potongan Penjualan #" . $old_penjualan['nomor_referensi'];
                $gl_stmt->bind_param('isssiddii', $user_id, $tanggal, $ket_discount, $old_penjualan['nomor_referensi'], $discount_acc_id, $discount, $zero, $id, $logged_in_user_id);
                $gl_stmt->execute();
            }
        }

        // KREDIT PENDAPATAN
        foreach ($revenue_totals as $acc_id => $amount) {
            $glKetR = "Pendapatan Penjualan #{$old_penjualan['nomor_referensi']}";
            $gl_stmt->bind_param('isssiddii', $user_id, $tanggal, $glKetR, $old_penjualan['nomor_referensi'], $acc_id, $zero, $amount, $id, $logged_in_user_id);
            $gl_stmt->execute();
        }

        // JURNAL HPP BARANG NORMAL
        foreach ($normal_cogs_totals as $acc_id => $amount) {
            if ($amount <= 0)
                continue;
            $glKetH = "Beban Pokok Penjualan #{$old_penjualan['nomor_referensi']}";
            $gl_stmt->bind_param('isssiddii', $user_id, $tanggal, $glKetH, $old_penjualan['nomor_referensi'], $acc_id, $amount, $zero, $id, $logged_in_user_id);
            $gl_stmt->execute();
        }
        foreach ($normal_inventory_totals as $acc_id => $amount) {
            if ($amount <= 0)
                continue;
            $glKetI = "Pengurangan Persediaan #{$old_penjualan['nomor_referensi']}";
            $gl_stmt->bind_param('isssiddii', $user_id, $tanggal, $glKetI, $old_penjualan['nomor_referensi'], $acc_id, $zero, $amount, $id, $logged_in_user_id);
            $gl_stmt->execute();
        }

        $gl_stmt->close();
        log_activity($_SESSION['username'], 'Update Penjualan', "Memperbarui transaksi penjualan #{$old_penjualan['nomor_referensi']}");
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Transaksi berhasil diperbarui.']);

    } catch (Exception $e) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}