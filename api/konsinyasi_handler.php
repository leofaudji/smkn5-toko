<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$user_id = 1; // Semua user mengakses data yang sama
$logged_in_user_id = $_SESSION['user_id']; // Untuk logging

try {
    $action = $_REQUEST['action'] ?? '';

    // --- SUPPLIER ACTIONS ---
    if ($action === 'list_suppliers') {
        $result = $conn->query("SELECT * FROM suppliers WHERE user_id = $user_id ORDER BY nama_pemasok ASC");
        echo json_encode(['status' => 'success', 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($action === 'list_payments') {
        $payable_acc_id = get_setting('consignment_payable_account', null, $conn);
        if (empty($payable_acc_id)) {
            echo json_encode(['status' => 'success', 'data' => [], 'debug' => 'Account ID not set']);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT gl.tanggal, gl.keterangan, gl.debit as jumlah, s.nama_pemasok
            FROM general_ledger gl
            LEFT JOIN suppliers s ON (
                SUBSTRING_INDEX(SUBSTRING_INDEX(gl.keterangan, 'ke ', -1), ' -', 1) = s.nama_pemasok
                OR gl.keterangan LIKE CONCAT('%ke ', s.nama_pemasok, '%')
            )
            WHERE gl.account_id = ?
              AND gl.debit > 0
            ORDER BY gl.tanggal DESC, gl.id DESC
        ");
        $stmt->bind_param('i', $payable_acc_id);
        $stmt->execute();
        $data = stmt_fetch_all($stmt);
        $stmt->close();

        echo json_encode([
            'status' => 'success',
            'data' => $data,
            'debug' => [
                'user_id' => $user_id,
                'account_id' => $payable_acc_id,
                'count' => count($data)
            ]
        ]);
        exit;
    } elseif ($action === 'save_supplier') {
        $id = (int) ($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_pemasok'] ?? '');
        $kontak = trim($_POST['kontak'] ?? '');
        if (empty($nama))
            throw new Exception("Nama pemasok wajib diisi.");

        if ($id > 0) { // Update
            $stmt = $conn->prepare("UPDATE suppliers SET nama_pemasok = ?, kontak = ?, updated_by = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param('ssiii', $nama, $kontak, $logged_in_user_id, $id, $user_id);
        } else { // Add
            $stmt = $conn->prepare("INSERT INTO suppliers (user_id, nama_pemasok, kontak, created_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('issi', $user_id, $nama, $kontak, $logged_in_user_id);
        }
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status' => 'success', 'message' => 'Pemasok berhasil disimpan.']);
    } elseif ($action === 'delete_supplier') {
        $id = (int) ($_POST['id'] ?? 0);
        // Cek keterkaitan sebelum hapus
        $res = $conn->query("SELECT COUNT(*) as count FROM consignment_items WHERE supplier_id = $id");
        if ($res->fetch_assoc()['count'] > 0)
            throw new Exception("Tidak dapat menghapus pemasok karena masih memiliki barang konsinyasi.");

        $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $id, $user_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status' => 'success', 'message' => 'Pemasok berhasil dihapus.']);
    }

    // --- ITEM ACTIONS ---
    elseif ($action === 'list_items') {
        $search = trim($_GET['search'] ?? '');
        $supplier_id = (int) ($_GET['supplier_id'] ?? 0);
        $stock_status = $_GET['stock_status'] ?? 'all'; // all, available, out_of_stock

        $where = ["ci.user_id = $user_id"];
        if (!empty($search)) {
            $search_safe = $conn->real_escape_string($search);
            $where[] = "(ci.nama_barang LIKE '%$search_safe%' OR ci.sku LIKE '%$search_safe%')";
        }
        if ($supplier_id > 0) {
            $where[] = "ci.supplier_id = $supplier_id";
        }

        $where_sql = implode(' AND ', $where);

        $sql = "
            SELECT * FROM (
                SELECT 
                    ci.*,
                    s.nama_pemasok,
                    (
                        ci.stok_awal 
                        + COALESCE((SELECT SUM(qty) FROM consignment_restocks WHERE consignment_item_id = ci.id), 0)
                        - COALESCE((SELECT SUM(IF(debit > 0, -qty, qty)) FROM general_ledger WHERE consignment_item_id = ci.id AND ref_type IN ('jurnal', 'penjualan') AND account_id = (SELECT setting_value FROM settings WHERE setting_key = 'consignment_payable_account')), 0)
                    ) as stok_saat_ini,
                    COALESCE((SELECT SUM(qty) FROM consignment_restocks WHERE consignment_item_id = ci.id), 0) as total_restock
                FROM consignment_items ci
                JOIN suppliers s ON ci.supplier_id = s.id
                WHERE $where_sql
            ) as sub
        ";

        if ($stock_status === 'out_of_stock') {
            $sql .= " WHERE stok_saat_ini <= 0";
        } elseif ($stock_status === 'available') {
            $sql .= " WHERE stok_saat_ini > 0";
        }

        $sql .= " ORDER BY nama_barang ASC";

        $result = $conn->query($sql);
        echo json_encode(['status' => 'success', 'data' => $result->fetch_all(MYSQLI_ASSOC)]);

    } elseif ($action === 'add_restock') {
        $item_id = (int) $_POST['item_id'];
        $qty = (int) $_POST['qty'];
        $tanggal = $_POST['tanggal'];
        $keterangan = trim($_POST['keterangan'] ?? '');

        if ($item_id <= 0 || $qty <= 0 || empty($tanggal)) {
            throw new Exception("Data penambahan stok tidak valid.");
        }

        $stmt = $conn->prepare("INSERT INTO consignment_restocks (user_id, consignment_item_id, qty, tanggal, keterangan, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iiissi', $user_id, $item_id, $qty, $tanggal, $keterangan, $logged_in_user_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Stok berhasil ditambahkan.']);
        } else {
            throw new Exception("Gagal menyimpan data stok: " . $stmt->error);
        }
        $stmt->close();
    } elseif ($action === 'save_item') {
        $id = (int) ($_POST['id'] ?? 0);
        $supplier_id = (int) $_POST['supplier_id'];
        $nama_barang = trim($_POST['nama_barang']);
        $harga_jual = (float) $_POST['harga_jual'];
        $harga_beli = (float) $_POST['harga_beli'];
        $stok_awal = (int) $_POST['stok_awal'];
        $tanggal_terima = $_POST['tanggal_terima'];

        if (empty($nama_barang) || $harga_jual <= 0 || $harga_beli <= 0 || $stok_awal < 0) {
            throw new Exception("Data barang tidak lengkap atau tidak valid.");
        }

        $conn->begin_transaction();

        if ($id > 0) { // Update
            $stmt = $conn->prepare("UPDATE consignment_items SET supplier_id=?, sku=?, barcode=?, nama_barang=?, harga_jual=?, harga_beli=?, stok_awal=?, tanggal_terima=?, updated_by=? WHERE id=? AND user_id=?");
            $sku = trim($_POST['sku'] ?? '');
            $barcode = trim($_POST['barcode'] ?? '');
            $stmt->bind_param('isssddisiii', $supplier_id, $sku, $barcode, $nama_barang, $harga_jual, $harga_beli, $stok_awal, $tanggal_terima, $logged_in_user_id, $id, $user_id);
            $stmt->execute();
            $stmt->close();
        } else { // Add
            $stmt = $conn->prepare("INSERT INTO consignment_items (user_id, supplier_id, sku, barcode, nama_barang, harga_jual, harga_beli, stok_awal, tanggal_terima, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $sku = trim($_POST['sku'] ?? '');
            $barcode = trim($_POST['barcode'] ?? '');
            $stmt->bind_param('iisssddisi', $user_id, $supplier_id, $sku, $barcode, $nama_barang, $harga_jual, $harga_beli, $stok_awal, $tanggal_terima, $logged_in_user_id);
            $stmt->execute();
            $item_id = $conn->insert_id;
            $stmt->close();

        }
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Barang konsinyasi berhasil disimpan.']);
    } elseif ($action === 'delete_item') {
        $id = (int) ($_POST['id'] ?? 0);
        // Cek keterkaitan sebelum hapus
        $res = $conn->query("SELECT COUNT(*) as count FROM general_ledger WHERE consignment_item_id = $id");
        if ($res->fetch_assoc()['count'] > 0)
            throw new Exception("Tidak dapat menghapus barang karena sudah ada riwayat penjualan.");

        $stmt = $conn->prepare("DELETE FROM consignment_items WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $id, $user_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status' => 'success', 'message' => 'Barang berhasil dihapus.']);
    } elseif ($action === 'get_single_item') {
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $conn->prepare("SELECT * FROM consignment_items WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $id, $user_id);
        $stmt->execute();
        $item = stmt_fetch_assoc($stmt);
        $stmt->close();
        if (!$item)
            throw new Exception("Barang tidak ditemukan.");
        echo json_encode(['status' => 'success', 'data' => $item]);
    }

    // --- SALE ACTION ---
    elseif ($action === 'sell_item') {
        $item_id = (int) $_POST['item_id'];
        $qty = (int) $_POST['qty'];
        $tanggal = $_POST['tanggal']; // Ini akan diambil dari form, bukan dari session
        $created_by = $_SESSION['user_id'];

        if ($item_id <= 0 || $qty <= 0 || empty($tanggal)) {
            throw new Exception("Data penjualan tidak valid.");
        }

        // Ambil detail barang dan akun-akun terkait
        $stmt_item = $conn->prepare("
            SELECT ci.*, s.nama_pemasok,
                   (SELECT setting_value FROM settings WHERE setting_key = 'consignment_cash_account') as kas_acc_id,
                   (SELECT setting_value FROM settings WHERE setting_key = 'consignment_revenue_account') as revenue_acc_id,
                   (SELECT setting_value FROM settings WHERE setting_key = 'consignment_cogs_account') as cogs_acc_id,
                   (SELECT setting_value FROM settings WHERE setting_key = 'consignment_payable_account') as payable_acc_id,
                   (SELECT setting_value FROM settings WHERE setting_key = 'consignment_inventory_account') as inventory_acc_id
            FROM consignment_items ci
            JOIN suppliers s ON ci.supplier_id = s.id
            WHERE ci.id = ? AND ci.user_id = ?
        ");
        $stmt_item->bind_param('ii', $item_id, $user_id);
        $stmt_item->execute();
        $item = stmt_fetch_assoc($stmt_item);
        $stmt_item->close();

        if (!$item)
            throw new Exception("Barang tidak ditemukan.");
        if (empty($item['kas_acc_id']) || empty($item['revenue_acc_id']) || empty($item['cogs_acc_id']) || empty($item['payable_acc_id']) || empty($item['inventory_acc_id'])) {
            throw new Exception("Akun untuk konsinyasi belum diatur di Pengaturan. Silakan hubungi admin.");
        }

        $total_penjualan = $qty * (float) $item['harga_jual'];
        $total_modal = $qty * (float) $item['harga_beli'];
        $keterangan = "Penjualan konsinyasi: $qty x {$item['nama_barang']} ({$item['nama_pemasok']})";

        // --- Logika Nomor Referensi Otomatis untuk Penjualan Konsinyasi ---
        $prefix = 'CSL'; // Consignment Sale
        $date_parts = explode('-', $tanggal);
        $year = $date_parts[0];
        $month = $date_parts[1];

        $stmt_ref = $conn->prepare(
            "SELECT nomor_referensi FROM general_ledger 
             WHERE user_id = ? AND YEAR(tanggal) = ? AND MONTH(tanggal) = ? AND nomor_referensi LIKE ? 
             ORDER BY id DESC LIMIT 1"
        );
        $like_prefix = $prefix . '%';
        $stmt_ref->bind_param('iiss', $user_id, $year, $month, $like_prefix);
        $stmt_ref->execute();
        $last_ref = stmt_fetch_assoc($stmt_ref);
        $stmt_ref->close();

        $sequence = 1;
        if ($last_ref && !empty($last_ref['nomor_referensi'])) {
            $parts = explode('/', $last_ref['nomor_referensi']);
            $sequence = (int) end($parts) + 1;
        }
        $nomor_referensi = sprintf('%s/%s/%s/%03d', $prefix, $year, $month, $sequence);
        // --- Akhir Logika ---

        $conn->begin_transaction();

        $zero = 0.00;
        $komisi = $total_penjualan - $total_modal;

        // Buat 3 entri di General Ledger (Balanced Journal)
        // 1. (Dr) Kas/Kas Toko - Total Harga Jual
        // 2. (Cr) Utang Konsinyasi (Utang Titipan) - Total Harga Beli
        // 3. (Cr) Pendapatan Konsinyasi (Pendapatan Komisi) - Selisih (Komisi)

        $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_type, ref_id, consignment_item_id, qty, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'jurnal', 0, ?, ?, ?)");

        // 1. (Dr) Kas
        $stmt_gl->bind_param('isssiddiii', $user_id, $tanggal, $keterangan, $nomor_referensi, $item['kas_acc_id'], $total_penjualan, $zero, $item_id, $qty, $created_by);
        $stmt_gl->execute();

        // 2. (Cr) Utang Konsinyasi (Utang Titipan)
        $stmt_gl->bind_param('isssiddiii', $user_id, $tanggal, $keterangan, $nomor_referensi, $item['payable_acc_id'], $zero, $total_modal, $item_id, $qty, $created_by);
        $stmt_gl->execute();

        // 3. (Cr) Pendapatan Konsinyasi (Pendapatan Komisi)
        $stmt_gl->bind_param('isssiddiii', $user_id, $tanggal, $keterangan, $nomor_referensi, $item['revenue_acc_id'], $zero, $komisi, $item_id, $qty, $created_by);
        $stmt_gl->execute();

        $stmt_gl->close();
        $conn->commit();

        echo json_encode(['status' => 'success', 'message' => "Penjualan {$item['nama_barang']} berhasil dicatat."]);
    }

    // --- PAYMENT ACTION ---
    elseif ($action === 'pay_debt') {
        $supplier_id = (int) $_POST['supplier_id'];
        $tanggal = $_POST['tanggal'];
        $jumlah = (float) $_POST['jumlah'];
        $kas_account_id = (int) $_POST['kas_account_id'];
        $keterangan = trim($_POST['keterangan']);
        $created_by = $_SESSION['user_id'];

        if ($supplier_id <= 0 || empty($tanggal) || $jumlah <= 0 || $kas_account_id <= 0) {
            throw new Exception("Data pembayaran tidak lengkap atau tidak valid.");
        }

        $payable_acc_id = get_setting('consignment_payable_account', null, $conn);
        if (empty($payable_acc_id)) {
            throw new Exception("Akun Utang Konsinyasi belum diatur di Pengaturan.");
        }

        $stmt_supplier = $conn->prepare("SELECT nama_pemasok FROM suppliers WHERE id = ?");
        $stmt_supplier->bind_param('i', $supplier_id);
        $stmt_supplier->execute();
        $supplier_name = stmt_fetch_assoc($stmt_supplier)['nama_pemasok'] ?? 'N/A';
        $stmt_supplier->close();

        $conn->begin_transaction();

        $keterangan_jurnal = "Pembayaran utang konsinyasi ke {$supplier_name}";
        if (!empty($keterangan)) {
            $keterangan_jurnal .= " - " . $keterangan;
        }
        $nomor_referensi = "CPY-" . date('YmdHis'); // Consignment Payment
        $zero = 0.00;

        $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_type, ref_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'jurnal', 0, ?)");

        // (Dr) Utang Konsinyasi
        $stmt_gl->bind_param('isssiddi', $user_id, $tanggal, $keterangan_jurnal, $nomor_referensi, $payable_acc_id, $jumlah, $zero, $created_by);
        $stmt_gl->execute();
        // (Cr) Kas/Bank
        $stmt_gl->bind_param('isssiddi', $user_id, $tanggal, $keterangan_jurnal, $nomor_referensi, $kas_account_id, $zero, $jumlah, $created_by);
        $stmt_gl->execute();

        $stmt_gl->close();
        $conn->commit();

        echo json_encode(['status' => 'success', 'message' => 'Pembayaran utang konsinyasi berhasil dicatat.']);

    } elseif ($action === 'list_sales') {
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-t');
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        // 1. Hitung TOTAL records untuk paging
        $stmt_total = $conn->prepare("
            SELECT COUNT(*) as total
            FROM general_ledger gl
            WHERE gl.user_id = ?
              AND gl.account_id = (SELECT setting_value FROM settings WHERE setting_key = 'consignment_payable_account')
              AND gl.kredit > 0
              AND gl.ref_type IN ('jurnal', 'penjualan')
              AND gl.tanggal BETWEEN ? AND ?
        ");
        $stmt_total->bind_param('iss', $user_id, $start_date, $end_date);
        $stmt_total->execute();
        $total_records = (int) stmt_fetch_assoc($stmt_total)['total'];
        $stmt_total->close();

        // 2. Ambil DATA untuk halaman saat ini
        $stmt = $conn->prepare("
            SELECT 
                gl.id,
                gl.tanggal, 
                gl.keterangan, 
                gl.nomor_referensi,
                gl.qty,
                ci.nama_barang,
                ci.harga_jual,
                (gl.qty * ci.harga_jual) as total_jual
            FROM general_ledger gl
            JOIN consignment_items ci ON gl.consignment_item_id = ci.id
            WHERE gl.user_id = ?
              AND gl.account_id = (SELECT setting_value FROM settings WHERE setting_key = 'consignment_payable_account')
              AND gl.kredit > 0
              AND gl.ref_type IN ('jurnal', 'penjualan')
              AND gl.tanggal BETWEEN ? AND ?
            ORDER BY gl.tanggal DESC, gl.id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param('issii', $user_id, $start_date, $end_date, $limit, $offset);
        $stmt->execute();
        $data = stmt_fetch_all($stmt);
        $stmt->close();

        echo json_encode([
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'limit' => $limit,
                'total_records' => $total_records,
                'total_pages' => ceil($total_records / $limit)
            ]
        ]);
    }

    // --- REPORT ACTION ---
    elseif ($action === 'get_sales_report') {
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-t');
        $supplier_id = !empty($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : null;
        $status = $_GET['status'] ?? 'Semua';

        $where = "WHERE gl.user_id = ? AND gl.tanggal BETWEEN ? AND ? AND gl.ref_type IN ('jurnal', 'penjualan') AND gl.consignment_item_id IS NOT NULL AND gl.account_id = (SELECT setting_value FROM settings WHERE setting_key = 'consignment_payable_account')";
        $params = [$user_id, $start_date, $end_date];
        $types = "iss";

        if ($supplier_id) {
            $where .= " AND ci.supplier_id = ?";
            $params[] = $supplier_id;
            $types .= "i";
        }

        $query = "
            SELECT 
                s.nama_pemasok,
                ci.nama_barang,
                SUM(IF(gl.debit > 0, -gl.qty, gl.qty)) as total_terjual, 
                ci.harga_beli, 
                (SUM(IF(gl.debit > 0, -gl.qty, gl.qty)) * ci.harga_beli) as total_utang,
                IFNULL(curr_stat.total_hutang_pemasok, 0) as total_hutang_pemasok,
                IFNULL(curr_stat.total_bayar_pemasok, 0) as total_bayar_pemasok
            FROM general_ledger gl
            JOIN consignment_items ci ON gl.consignment_item_id = ci.id
            JOIN suppliers s ON ci.supplier_id = s.id
            LEFT JOIN (
                SELECT 
                    s2.id as sid,
                    (SELECT SUM(gl3.kredit) FROM general_ledger gl3 JOIN consignment_items ci3 ON gl3.consignment_item_id = ci3.id WHERE ci3.supplier_id = s2.id AND gl3.account_id = (SELECT setting_value FROM settings WHERE setting_key = 'consignment_payable_account') AND gl3.ref_type IN ('jurnal', 'penjualan')) as total_hutang_pemasok,
                    (SELECT SUM(gl4.debit) FROM general_ledger gl4 WHERE gl4.account_id = (SELECT setting_value FROM settings WHERE setting_key = 'consignment_payable_account') AND gl4.debit > 0 AND SUBSTRING_INDEX(SUBSTRING_INDEX(gl4.keterangan, 'ke ', -1), ' -', 1) = s2.nama_pemasok) as total_bayar_pemasok
                FROM suppliers s2
            ) curr_stat ON s.id = curr_stat.sid
            $where
            GROUP BY s.id, s.nama_pemasok, ci.nama_barang, ci.harga_beli, curr_stat.total_hutang_pemasok, curr_stat.total_bayar_pemasok
            HAVING total_terjual > 0
        ";

        if ($status === 'Lunas') {
            $query .= " AND total_hutang_pemasok <= total_bayar_pemasok AND total_hutang_pemasok > 0";
        } elseif ($status === 'Belum Lunas') {
            $query .= " AND (total_hutang_pemasok > total_bayar_pemasok OR total_hutang_pemasok IS NULL OR total_hutang_pemasok = 0)";
        }

        $query .= " ORDER BY s.nama_pemasok, ci.nama_barang";

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $report = stmt_fetch_all($stmt);
        $stmt->close();

        echo json_encode(['status' => 'success', 'data' => $report]);
    } elseif ($action === 'import_items_csv') {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File tidak terunggah dengan benar.");
        }

        $file = $_FILES['csv_file']['tmp_name'];
        if (($handle = fopen($file, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ","); // Skip header

            $success = 0;
            $skipped = 0;
            $errors = [];
            $line = 1;

            $conn->begin_transaction();

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $line++;
                if (count($data) < 6) {
                    $skipped++;
                    continue;
                }

                // no, namasupplier, namabarang, hargabeli, hargajual, sku
                $nama_supplier = trim($data[1]);
                $nama_barang = trim($data[2]);
                $harga_beli = (float) str_replace(',', '.', $data[3]);
                $harga_jual = (float) str_replace(',', '.', $data[4]);
                $sku = trim($data[5]);

                if (empty($nama_barang) || empty($nama_supplier)) {
                    $errors[] = "Baris $line: Nama barang atau supplier kosong.";
                    continue;
                }

                // 1. Resolve Supplier
                $stmt_sup = $conn->prepare("SELECT id FROM suppliers WHERE LOWER(nama_pemasok) = LOWER(?) AND user_id = ?");
                $stmt_sup->bind_param("si", $nama_supplier, $user_id);
                $stmt_sup->execute();
                $sup_res = stmt_fetch_assoc($stmt_sup);
                $stmt_sup->close();

                if ($sup_res) {
                    $supplier_id = $sup_res['id'];
                } else {
                    $stmt_ins_sup = $conn->prepare("INSERT INTO suppliers (user_id, nama_pemasok, created_by) VALUES (?, ?, ?)");
                    $stmt_ins_sup->bind_param("isi", $user_id, $nama_supplier, $logged_in_user_id);
                    $stmt_ins_sup->execute();
                    $supplier_id = $conn->insert_id;
                    $stmt_ins_sup->close();
                }

                // 2. Check if item exists by SKU or Name
                $stmt_check = $conn->prepare("SELECT id FROM consignment_items WHERE (sku = ? AND sku != '') OR (nama_barang = ? AND supplier_id = ?) AND user_id = ?");
                $stmt_check->bind_param("ssii", $sku, $nama_barang, $supplier_id, $user_id);
                $stmt_check->execute();
                $exists = stmt_fetch_assoc($stmt_check);
                $stmt_check->close();

                if ($exists) {
                    // Update existing
                    $stmt_upd = $conn->prepare("UPDATE consignment_items SET nama_barang=?, harga_jual=?, harga_beli=?, supplier_id=?, updated_by=? WHERE id=?");
                    $stmt_upd->bind_param("sddiii", $nama_barang, $harga_jual, $harga_beli, $supplier_id, $logged_in_user_id, $exists['id']);
                    $stmt_upd->execute();
                    $stmt_upd->close();
                } else {
                    // Insert new
                    $tanggal_terima = date('Y-m-d');
                    $stok_awal = 0;
                    $stmt_ins = $conn->prepare("INSERT INTO consignment_items (user_id, supplier_id, sku, nama_barang, harga_jual, harga_beli, stok_awal, tanggal_terima, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_ins->bind_param("iisssddsi", $user_id, $supplier_id, $sku, $nama_barang, $harga_jual, $harga_beli, $stok_awal, $tanggal_terima, $logged_in_user_id);
                    $stmt_ins->execute();
                    $stmt_ins->close();
                }
                $success++;
            }
            fclose($handle);
            $conn->commit();

            echo json_encode([
                'status' => 'success',
                'message' => "Impor selesai. $success berhasil, $skipped dilewati.",
                'errors' => $errors
            ]);
        } else {
            throw new Exception("Gagal membuka file CSV.");
        }
    } elseif ($action === 'delete_restock') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0)
            throw new Exception("ID restock tidak valid.");

        $stmt = $conn->prepare("DELETE FROM consignment_restocks WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $id, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Data restock berhasil dihapus.']);
        } else {
            throw new Exception("Gagal menghapus data restock.");
        }
        $stmt->close();
    } elseif ($action === 'list_mutations') {
        $supplier_id = !empty($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : null;
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;

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
                SELECT 
                    ci.tanggal_terima as tanggal, 
                    ci.nama_barang, 
                    s.nama_pemasok, 
                    'Stok Awal' as tipe, 
                    ci.stok_awal as qty, 
                    'Penerimaan awal saat pendaftaran barang' as keterangan,
                    ci.id as item_id,
                    0 as mutation_id
                FROM consignment_items ci
                JOIN suppliers s ON ci.supplier_id = s.id
                $where_ci
                
                UNION ALL
                
                SELECT 
                    cr.tanggal, 
                    ci.nama_barang, 
                    s.nama_pemasok, 
                    'Restock' as tipe, 
                    cr.qty, 
                    cr.keterangan,
                    ci.id as item_id,
                    cr.id as mutation_id
                FROM consignment_restocks cr
                JOIN consignment_items ci ON cr.consignment_item_id = ci.id
                JOIN suppliers s ON ci.supplier_id = s.id
                $where_cr
                
                UNION ALL
                
                SELECT 
                    gl.tanggal,
                    ci.nama_barang,
                    s.nama_pemasok,
                    'Terjual' as tipe,
                    SUM(gl.qty) as qty,
                    'Total penjualan harian' as keterangan,
                    ci.id as item_id,
                    0 as mutation_id
                FROM general_ledger gl
                JOIN consignment_items ci ON gl.consignment_item_id = ci.id
                JOIN suppliers s ON ci.supplier_id = s.id
                $where_gl
                GROUP BY gl.tanggal, ci.id
            ) as combined_mutations
            ORDER BY tanggal DESC, nama_barang ASC
        ";

        // Count total results for pagination
        $count_query = "SELECT COUNT(*) as total FROM ($query) as total_count";
        $stmt_count = $conn->prepare($count_query);
        $final_params = array_merge($params_ci, $params_cr, $params_gl);
        $final_types = $types_ci . $types_cr . $types_gl;
        if (!empty($final_params)) {
            $stmt_count->bind_param($final_types, ...$final_params);
        }
        $stmt_count->execute();
        $total_records = (int) stmt_fetch_assoc($stmt_count)['total'];
        $stmt_count->close();

        // Add LIMIT and OFFSET for pagination
        $query .= " LIMIT ? OFFSET ?";
        $final_params[] = $limit;
        $final_params[] = $offset;
        $final_types .= "ii";

        $stmt = $conn->prepare($query);
        if (!empty($final_params)) {
            $stmt->bind_param($final_types, ...$final_params);
        }
        $stmt->execute();
        $mutations = stmt_fetch_all($stmt);
        $stmt->close();

        echo json_encode([
            'status' => 'success',
            'data' => $mutations,
            'pagination' => [
                'current_page' => $page,
                'limit' => $limit,
                'total_records' => $total_records,
                'total_pages' => ceil($total_records / $limit)
            ]
        ]);
    } elseif ($action === 'get_debt_summary_report') {
        $payable_acc_id = get_setting('consignment_payable_account', null, $conn);
        $cogs_acc_id = get_setting('consignment_cogs_account', null, $conn);

        if (empty($payable_acc_id) || empty($cogs_acc_id)) {
            throw new Exception("Akun Utang/HPP Konsinyasi belum diatur di Pengaturan.");
        }

        $start_date = $_GET['start_date'] ?? '1970-01-01';
        $end_date = $_GET['end_date'] ?? date('Y-m-d');

        // 1. Get Total Account Balance (matched with Trial Balance/Audit Saldo logic)
        $stmt_total = $conn->prepare("
            SELECT 
                a.saldo_awal,
                COALESCE(SUM(gl.debit), 0) as total_debit,
                COALESCE(SUM(gl.kredit), 0) as total_kredit
            FROM accounts a
            LEFT JOIN general_ledger gl ON a.id = gl.account_id AND gl.tanggal <= ?
            WHERE a.id = ? AND a.user_id = ?
            GROUP BY a.id, a.saldo_awal
        ");
        $stmt_total->bind_param('sii', $end_date, $payable_acc_id, $user_id);
        $stmt_total->execute();
        $total_res = stmt_fetch_assoc($stmt_total);
        $total_audit_balance = ($total_res ? $total_res['saldo_awal'] + $total_res['total_kredit'] - $total_res['total_debit'] : 0);
        $stmt_total->close();

        // 2. Get breakdown per supplier
        $stmt = $conn->prepare("
            SELECT 
                s.id,
                s.nama_pemasok,
                COALESCE(utang.total_utang, 0) as total_utang,
                COALESCE(bayar.total_bayar, 0) as total_bayar,
                (COALESCE(utang.total_utang, 0) - COALESCE(bayar.total_bayar, 0)) as sisa_utang
            FROM 
                suppliers s
            LEFT JOIN (
                -- Subquery: Utang dari barang terjual (Kredit) dikurangi pembatalan (Debit linked to item)
                SELECT 
                    ci.supplier_id,
                    SUM(gl.kredit - gl.debit) as total_utang
                FROM general_ledger gl
                JOIN consignment_items ci ON gl.consignment_item_id = ci.id
                WHERE gl.user_id = ?
                  AND gl.account_id = ? AND gl.tanggal <= ?
                  AND gl.ref_type IN ('jurnal', 'penjualan', 'penyesuaian')
                GROUP BY ci.supplier_id
            ) utang ON s.id = utang.supplier_id
            LEFT JOIN (
                -- Subquery: Total pembayaran (Debit) yang terurai per supplier via keterangan
                SELECT 
                    s_inner.id as supplier_id,
                    SUM(gl.debit) as total_bayar
                FROM general_ledger gl
                JOIN suppliers s_inner ON (
                    SUBSTRING_INDEX(SUBSTRING_INDEX(gl.keterangan, 'ke ', -1), ' -', 1) = s_inner.nama_pemasok
                    OR gl.keterangan LIKE CONCAT('%Pelunasan Konsinyasi ke ', s_inner.nama_pemasok, '%')
                )
                WHERE gl.user_id = ?
                  AND gl.account_id = ? AND gl.tanggal <= ?
                  AND gl.debit > 0
                GROUP BY s_inner.id
            ) bayar ON s.id = bayar.supplier_id
            WHERE s.user_id = ?
            ORDER BY s.nama_pemasok
        ");
        $stmt->bind_param('ississi', $user_id, $payable_acc_id, $end_date, $user_id, $payable_acc_id, $end_date, $user_id);
        $stmt->execute();
        $report_data = stmt_fetch_all($stmt);
        $stmt->close();

        // 3. Calculate "Lain-lain" (Saldo Awal or Orphan Entries)
        $total_linked_sisa = 0;
        foreach ($report_data as $row) {
            $total_linked_sisa += $row['sisa_utang'];
        }

        $diff = $total_audit_balance - $total_linked_sisa;
        if (abs($diff) > 0.01) {
            $report_data[] = [
                'id' => null,
                'nama_pemasok' => 'Saldo Awal / Penyesuaian Manual',
                'total_utang' => ($diff > 0 ? abs($diff) : 0),
                'total_bayar' => ($diff < 0 ? abs($diff) : 0),
                'sisa_utang' => $diff
            ];
        }

        echo json_encode(['status' => 'success', 'data' => $report_data, 'meta' => ['total_balance_audit' => $total_audit_balance]]);
    }

} catch (Exception $e) {
    if (isset($conn) && method_exists($conn, 'in_transaction') && $conn->in_transaction())
        $conn->rollback();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>