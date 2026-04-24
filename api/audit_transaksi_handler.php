<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$user_id = 1; // Semua data diakses oleh user_id 1
$logged_in_user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-t');
        $module = $_GET['module'] ?? 'all';

        $results = [];

        // 1. Module: Kas/Bank (Transaksi)
        if ($module === 'all' || $module === 'transaksi') {
            $sql = "SELECT t.id, t.tanggal, t.nomor_referensi, t.keterangan, t.jumlah as amount, 'Transaksi' as module_name, 'transaksi' as ref_type,
                    EXISTS (SELECT 1 FROM general_ledger gl WHERE gl.ref_id = t.id AND gl.ref_type = 'transaksi') as exists_in_gl
                    FROM transaksi t 
                    WHERE t.user_id = ? AND t.tanggal BETWEEN ? AND ?
                    ORDER BY t.tanggal DESC, t.id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iss', $user_id, $start_date, $end_date);
            $stmt->execute();
            $results = array_merge($results, stmt_fetch_all($stmt));
            $stmt->close();
        }

        // 2. Module: Pembelian
        if ($module === 'all' || $module === 'pembelian') {
            $sql = "SELECT p.id, p.tanggal_pembelian as tanggal, p.nomor_referensi, p.keterangan, p.total as amount, 'Pembelian' as module_name, 'pembelian' as ref_type,
                    EXISTS (SELECT 1 FROM general_ledger gl WHERE gl.ref_id = p.id AND gl.ref_type = 'pembelian') as exists_in_gl
                    FROM pembelian p 
                    WHERE p.user_id = ? AND p.tanggal_pembelian BETWEEN ? AND ?
                    ORDER BY p.tanggal_pembelian DESC, p.id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iss', $user_id, $start_date, $end_date);
            $stmt->execute();
            $results = array_merge($results, stmt_fetch_all($stmt));
            $stmt->close();
        }

        // 3. Module: Penjualan
        if ($module === 'all' || $module === 'penjualan') {
            $sql = "SELECT p.id, p.tanggal_penjualan as tanggal, p.nomor_referensi, p.keterangan, p.total as amount, 'Penjualan' as module_name, 'penjualan' as ref_type,
                    EXISTS (SELECT 1 FROM general_ledger gl WHERE gl.ref_id = p.id AND gl.ref_type = 'penjualan') as exists_in_gl
                    FROM penjualan p 
                    WHERE p.user_id = ? AND DATE(p.tanggal_penjualan) BETWEEN ? AND ?
                    ORDER BY p.tanggal_penjualan DESC, p.id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iss', $user_id, $start_date, $end_date);
            $stmt->execute();
            $results = array_merge($results, stmt_fetch_all($stmt));
            $stmt->close();
        }

        // 4. Module: Jurnal Umum
        if ($module === 'all' || $module === 'jurnal') {
            $sql = "SELECT j.id, j.tanggal, CONCAT('JRN-', j.id) as nomor_referensi, j.keterangan, 
                    (SELECT SUM(debit) FROM jurnal_details WHERE jurnal_entry_id = j.id) as amount,
                    'Jurnal Umum' as module_name, 'jurnal' as ref_type,
                    EXISTS (SELECT 1 FROM general_ledger gl WHERE gl.ref_id = j.id AND gl.ref_type = 'jurnal') as exists_in_gl
                    FROM jurnal_entries j 
                    WHERE j.user_id = ? AND j.tanggal BETWEEN ? AND ?
                    ORDER BY j.tanggal DESC, j.id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iss', $user_id, $start_date, $end_date);
            $stmt->execute();
            $results = array_merge($results, stmt_fetch_all($stmt));
            $stmt->close();
        }

        // Sort results by date descending
        usort($results, function($a, $b) {
            return strcmp($b['tanggal'], $a['tanggal']);
        });

        echo json_encode(['status' => 'success', 'data' => $results]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        if ($action === 'repost') {
            $ref_type = $input['ref_type'];
            $ref_id = (int) $input['ref_id'];

            require_once __DIR__ . '/audit_handler.php';
            // We use the existing logic from audit_handler.php
            // But we need to make sure the user has permissions
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                throw new Exception("Hanya admin yang dapat melakukan posting ulang.");
            }

            $conn->begin_transaction();
            // Call the function from audit_handler.php
            // Since it's not a function but a logic block in POST, we might need to duplicate it or restructure.
            // Let's duplicate the relevant part for now to ensure it works correctly here.
            
            repost_transaction($conn, $ref_type, $ref_id, $user_id, $logged_in_user_id);

            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Transaksi berhasil diposting ulang ke Buku Besar.']);
        }
    }
} catch (Exception $e) {
    if (isset($conn) && $conn->in_transaction()) {
        $conn->rollback();
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Helper function to repost a transaction to GL.
 * Duplicated logic from audit_handler.php for reliability.
 */
function repost_transaction($conn, $ref_type, $ref_id, $data_user_id, $logged_in_user_id) {
    // 1. Delete existing entries to prevent duplicates
    $stmt_del = $conn->prepare("DELETE FROM general_ledger WHERE user_id = ? AND ref_type = ? AND ref_id = ?");
    $stmt_del->bind_param('isi', $data_user_id, $ref_type, $ref_id);
    $stmt_del->execute();
    $stmt_del->close();

    $zero = 0.00;
    $null_val = null;

    if ($ref_type === 'jurnal') {
        $res_h = $conn->query("SELECT * FROM jurnal_entries WHERE id = $ref_id")->fetch_assoc();
        if (!$res_h) throw new Exception("Data jurnal tidak ditemukan.");

        $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'jurnal', ?)");
        $q_d = $conn->query("SELECT * FROM jurnal_details WHERE jurnal_entry_id = $ref_id");
        while ($d = $q_d->fetch_assoc()) {
            $ref_no = 'JRN-' . $ref_id;
            $stmt_gl->bind_param('isssiddii', $data_user_id, $res_h['tanggal'], $res_h['keterangan'], $ref_no, $d['account_id'], $d['debit'], $d['kredit'], $ref_id, $logged_in_user_id);
            $stmt_gl->execute();
        }
        $stmt_gl->close();
    } elseif ($ref_type === 'pembelian') {
        $res_h = $conn->query("SELECT * FROM pembelian WHERE id = $ref_id")->fetch_assoc();
        if (!$res_h) throw new Exception("Data pembelian tidak ditemukan.");

        $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pembelian', ?)");
        $q_d = $conn->query("SELECT account_id, SUM(subtotal) as total FROM pembelian_details WHERE pembelian_id = $ref_id GROUP BY account_id");
        while ($d = $q_d->fetch_assoc()) {
            $stmt_gl->bind_param('isssiddii', $data_user_id, $res_h['tanggal_pembelian'], $res_h['keterangan'], $res_h['nomor_referensi'], $d['account_id'], $d['total'], $zero, $ref_id, $logged_in_user_id);
            $stmt_gl->execute();
        }
        $stmt_gl->bind_param('isssiddii', $data_user_id, $res_h['tanggal_pembelian'], $res_h['keterangan'], $res_h['nomor_referensi'], $res_h['credit_account_id'], $zero, $res_h['total'], $ref_id, $logged_in_user_id);
        $stmt_gl->execute();
        $stmt_gl->close();
    } elseif ($ref_type === 'penjualan') {
        $res_h = $conn->query("SELECT * FROM penjualan WHERE id = $ref_id")->fetch_assoc();
        if (!$res_h) throw new Exception("Data penjualan tidak ditemukan.");
        if ($res_h['status'] === 'void') return;

        $nomor_referensi = $res_h['nomor_referensi'];
        $tanggal = $res_h['tanggal_penjualan'];
        $keterangan = $res_h['keterangan'];
        $total = $res_h['total'];
        $bayar = $res_h['bayar'];
        $discount = (float) $res_h['discount'];
        $payment_method = $res_h['payment_method'];

        $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, consignment_item_id, qty, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'penjualan', ?, ?, ?)");

        $revenue_totals = [];
        $hpp_totals = [];
        $consignment_entries = [];

        $q_items = $conn->query("
            SELECT pd.*, i.harga_beli as purchase_price_normal, i.revenue_account_id, i.inventory_account_id, i.cogs_account_id,
                   ci.harga_beli as purchase_price_cons
            FROM penjualan_details pd
            LEFT JOIN items i ON pd.item_id = i.id AND pd.item_type = 'normal'
            LEFT JOIN consignment_items ci ON pd.item_id = ci.id AND pd.item_type = 'consignment'
            WHERE pd.penjualan_id = $ref_id
        ");

        $def_rev = get_setting('default_sales_revenue_account_id', null, $conn);
        $def_cogs = get_setting('default_cogs_account_id', null, $conn);
        $def_inv = get_setting('default_inventory_account_id', null, $conn);
        $cons_rev = get_setting('consignment_revenue_account', null, $conn);
        $cons_pay = get_setting('consignment_payable_account', null, $conn);

        while ($item = $q_items->fetch_assoc()) {
            if ($item['item_type'] === 'normal') {
                $rev_acc = $item['revenue_account_id'] ?: $def_rev;
                $revenue_totals[$rev_acc] = ($revenue_totals[$rev_acc] ?? 0) + ($item['price'] * $item['quantity']);
                $cogs_acc = $item['cogs_account_id'] ?: $def_cogs;
                $inv_acc = $item['inventory_account_id'] ?: $def_inv;
                $hpp_val = $item['quantity'] * (float) $item['purchase_price_normal'];
                if (!isset($hpp_totals[$cogs_acc])) $hpp_totals[$cogs_acc] = [];
                $hpp_totals[$cogs_acc][$inv_acc] = ($hpp_totals[$cogs_acc][$inv_acc] ?? 0) + $hpp_val;
            } else {
                $total_beli = $item['quantity'] * (float) $item['purchase_price_cons'];
                $komisi = ($item['price'] * $item['quantity']) - $total_beli;
                $revenue_totals[$cons_rev] = ($revenue_totals[$cons_rev] ?? 0) + $komisi;
                $consignment_entries[] = ['account_id' => $cons_pay, 'amount' => $total_beli, 'item_id' => $item['item_id'], 'qty' => $item['quantity'], 'desc' => "Penjualan Konsinyasi: " . $item['deskripsi_item']];
            }
        }

        $res_wb = $conn->query("SELECT jumlah FROM transaksi_wajib_belanja WHERE nomor_referensi = '$nomor_referensi'")->fetch_assoc();
        $bayar_wb = (float) ($res_wb['jumlah'] ?? 0);

        if ($bayar_wb > 0) {
            $akun_wb = get_setting('wajib_belanja_liability_account_id', null, $conn);
            if ($akun_wb) {
                $stmt_gl->bind_param('isssiddiiii', $data_user_id, $tanggal, $keterangan, $nomor_referensi, $akun_wb, $bayar_wb, $zero, $ref_id, $null_val, $null_val, $logged_in_user_id);
                $stmt_gl->execute();
            }
        }

        $piutang_portion = ($payment_method === 'hutang') ? ($total - $bayar - $bayar_wb) : 0;
        $cash_portion = $total - $bayar_wb - $piutang_portion;

        if ($cash_portion > 0) {
            $cash_acc = get_setting('default_sales_cash_account_id', null, $conn);
            $stmt_gl->bind_param('isssiddiiii', $data_user_id, $tanggal, $keterangan, $nomor_referensi, $cash_acc, $cash_portion, $zero, $ref_id, $null_val, $null_val, $logged_in_user_id);
            $stmt_gl->execute();
        }
        if ($piutang_portion > 0) {
            $piutang_acc = get_setting('sales_receivable_account_id', null, $conn);
            $stmt_gl->bind_param('isssiddiiii', $data_user_id, $tanggal, $keterangan, $nomor_referensi, $piutang_acc, $piutang_portion, $zero, $ref_id, $null_val, $null_val, $logged_in_user_id);
            $stmt_gl->execute();
        }

        foreach ($revenue_totals as $acc => $amt) {
            $stmt_gl->bind_param('isssiddiiii', $data_user_id, $tanggal, $keterangan, $nomor_referensi, $acc, $zero, $amt, $ref_id, $null_val, $null_val, $logged_in_user_id);
            $stmt_gl->execute();
        }

        $hpp_desc = "HPP Penjualan #$nomor_referensi";
        foreach ($hpp_totals as $cogs_acc => $inv_list) {
            foreach ($inv_list as $inv_acc => $amt) {
                $stmt_gl->bind_param('isssiddiiii', $data_user_id, $tanggal, $hpp_desc, $nomor_referensi, $cogs_acc, $amt, $zero, $ref_id, $null_val, $null_val, $logged_in_user_id);
                $stmt_gl->execute();
                $stmt_gl->bind_param('isssiddiiii', $data_user_id, $tanggal, $hpp_desc, $nomor_referensi, $inv_acc, $zero, $amt, $ref_id, $null_val, $null_val, $logged_in_user_id);
                $stmt_gl->execute();
            }
        }

        foreach ($consignment_entries as $ent) {
            $stmt_gl->bind_param('isssiddiiii', $data_user_id, $tanggal, $ent['desc'], $nomor_referensi, $ent['account_id'], $zero, $ent['amount'], $ref_id, $ent['item_id'], $ent['qty'], $logged_in_user_id);
            $stmt_gl->execute();
        }
        $stmt_gl->close();
    } elseif ($ref_type === 'transaksi') {
        $res_h = $conn->query("SELECT * FROM transaksi WHERE id = $ref_id")->fetch_assoc();
        if (!$res_h) throw new Exception("Data transaksi tidak ditemukan.");

        $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'transaksi', ?)");

        if ($res_h['jenis'] === 'pemasukan') {
            $stmt_gl->bind_param('isssiddii', $data_user_id, $res_h['tanggal'], $res_h['keterangan'], $res_h['nomor_referensi'], $res_h['kas_account_id'], $res_h['jumlah'], $zero, $ref_id, $logged_in_user_id);
            $stmt_gl->execute();
            $stmt_gl->bind_param('isssiddii', $data_user_id, $res_h['tanggal'], $res_h['keterangan'], $res_h['nomor_referensi'], $res_h['account_id'], $zero, $res_h['jumlah'], $ref_id, $logged_in_user_id);
            $stmt_gl->execute();
        } elseif ($res_h['jenis'] === 'pengeluaran') {
            $stmt_gl->bind_param('isssiddii', $data_user_id, $res_h['tanggal'], $res_h['keterangan'], $res_h['nomor_referensi'], $res_h['account_id'], $res_h['jumlah'], $zero, $ref_id, $logged_in_user_id);
            $stmt_gl->execute();
            $stmt_gl->bind_param('isssiddii', $data_user_id, $res_h['tanggal'], $res_h['keterangan'], $res_h['nomor_referensi'], $res_h['kas_account_id'], $zero, $res_h['jumlah'], $ref_id, $logged_in_user_id);
            $stmt_gl->execute();
        } elseif ($res_h['jenis'] === 'transfer') {
            $stmt_gl->bind_param('isssiddii', $data_user_id, $res_h['tanggal'], $res_h['keterangan'], $res_h['nomor_referensi'], $res_h['kas_tujuan_account_id'], $res_h['jumlah'], $zero, $ref_id, $logged_in_user_id);
            $stmt_gl->execute();
            $stmt_gl->bind_param('isssiddii', $data_user_id, $res_h['tanggal'], $res_h['keterangan'], $res_h['nomor_referensi'], $res_h['kas_account_id'], $zero, $res_h['jumlah'], $ref_id, $logged_in_user_id);
            $stmt_gl->execute();
        }
        $stmt_gl->close();
    }
}
