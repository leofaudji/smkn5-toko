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

function get_gl_balance($conn, $account_id, $user_id) {
    if (!$account_id) return 0;
    
    $stmt = $conn->prepare("
        SELECT 
            a.saldo_normal,
            SUM(gl.debit) as total_debit,
            SUM(gl.kredit) as total_kredit
        FROM accounts a
        LEFT JOIN general_ledger gl ON a.id = gl.account_id
        WHERE a.id = ? AND a.user_id = ?
        GROUP BY a.id
    ");
    $stmt->bind_param('ii', $account_id, $user_id);
    $stmt->execute();
    $res = stmt_fetch_assoc($stmt);
    $stmt->close();

    if (!$res) return 0;

    return ($res['saldo_normal'] === 'Debit') 
        ? (float)$res['total_debit'] - (float)$res['total_kredit']
        : (float)$res['total_kredit'] - (float)$res['total_debit'];
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $results = [];

        // 1. AUDIT PERSEDIAAN (NORMAL)
        $inv_acc_id = get_setting('default_inventory_account_id', null, $conn);
        $sub_inv = $conn->query("SELECT SUM(stok * harga_beli) as total FROM items WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
        $gl_inv = get_gl_balance($conn, $inv_acc_id, $user_id);
        $results[] = [
            'module' => 'Persediaan Barang',
            'sub_ledger' => (float)$sub_inv,
            'gl_balance' => (float)$gl_inv,
            'diff' => (float)$sub_inv - (float)$gl_inv,
            'account' => 'Persediaan'
        ];

        // 2. AUDIT PIUTANG ANGGOTA
        $rcv_acc_id = get_setting('sales_receivable_account_id', null, $conn);
        $sub_rcv = $conn->query("SELECT SUM(total - bayar - bayar_wb) as total FROM penjualan WHERE user_id = $user_id AND payment_method = 'hutang' AND status = 'completed'")->fetch_assoc()['total'] ?? 0;
        $gl_rcv = get_gl_balance($conn, $rcv_acc_id, $user_id);
        $results[] = [
            'module' => 'Piutang Anggota',
            'sub_ledger' => (float)$sub_rcv,
            'gl_balance' => (float)$gl_rcv,
            'diff' => (float)$sub_rcv - (float)$gl_rcv,
            'account' => 'Piutang'
        ];

        // 3. AUDIT WAJIB BELANJA (WB)
        $wb_acc_id = get_setting('wajib_belanja_liability_account_id', null, $conn);
        $sub_wb = $conn->query("SELECT SUM(saldo_wajib_belanja) as total FROM anggota WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
        $gl_wb = get_gl_balance($conn, $wb_acc_id, $user_id);
        $results[] = [
            'module' => 'Wajib Belanja (Anggota)',
            'sub_ledger' => (float)$sub_wb,
            'gl_balance' => (float)$gl_wb,
            'diff' => (float)$sub_wb - (float)$gl_wb,
            'account' => 'Utang WB'
        ];

        // 4. AUDIT UTANG TITIPAN (KONSINYASI)
        $cons_acc_id = get_setting('consignment_payable_account', null, $conn);
        // Sub-ledger Utang Konsinyasi = Total Utang (dari penjualan) - Total Bayar
        $sub_cons_debt = $conn->query("
            SELECT 
                (SELECT SUM(gl.kredit) FROM general_ledger gl JOIN consignment_items ci ON gl.consignment_item_id = ci.id WHERE gl.user_id = $user_id AND gl.account_id = $cons_acc_id AND gl.kredit > 0) -
                (SELECT SUM(gl.debit) FROM general_ledger gl WHERE gl.user_id = $user_id AND gl.account_id = $cons_acc_id AND gl.debit > 0) as sisa_utang
        ")->fetch_assoc()['sisa_utang'] ?? 0;
        
        $gl_cons = get_gl_balance($conn, $cons_acc_id, $user_id);
        $results[] = [
            'module' => 'Utang Titipan (Konsinyasi)',
            'sub_ledger' => (float)$sub_cons_debt,
            'gl_balance' => (float)$gl_cons,
            'diff' => (float)$sub_cons_debt - (float)$gl_cons,
            'account' => 'Utang Konsinyasi'
        ];

        echo json_encode(['status' => 'success', 'data' => $results]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        if ($action === 'repost') {
            $ref_type = $input['ref_type'];
            $ref_id = (int)$input['ref_id'];
            $logged_in_user_id = $_SESSION['user_id'];
            
            $conn->begin_transaction();
            
            // Delete old ledger entries for this reference
            $stmt_del = $conn->prepare("DELETE FROM general_ledger WHERE user_id = ? AND ref_type = ? AND ref_id = ?");
            $stmt_del->bind_param('isi', $user_id, $ref_type, $ref_id);
            $stmt_del->execute();
            $stmt_del->close();
            
            if ($ref_type === 'jurnal') {
                // Get header
                $res_h = $conn->query("SELECT * FROM jurnal_entries WHERE id = $ref_id")->fetch_assoc();
                if (!$res_h) throw new Exception("Data sumber jurnal tidak ditemukan.");
                
                // Get details and re-insert
                $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'jurnal', ?)");
                $q_d = $conn->query("SELECT * FROM jurnal_details WHERE jurnal_entry_id = $ref_id");
                while($d = $q_d->fetch_assoc()) {
                    $ref_no = 'JRN-' . $ref_id;
                    $stmt_gl->bind_param('isssiddii', $user_id, $res_h['tanggal'], $res_h['keterangan'], $ref_no, $d['account_id'], $d['debit'], $d['kredit'], $ref_id, $logged_in_user_id);
                    $stmt_gl->execute();
                }
                $stmt_gl->close();
            } elseif ($ref_type === 'pembelian') {
                $res_h = $conn->query("SELECT * FROM pembelian WHERE id = $ref_id")->fetch_assoc();
                if (!$res_h) throw new Exception("Data sumber pembelian tidak ditemukan.");
                
                $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pembelian', ?)");
                
                // Jurnal Sisi Debit (Agregasi per Akun Persediaan dari details)
                $q_d = $conn->query("SELECT account_id, SUM(subtotal) as total FROM pembelian_details WHERE pembelian_id = $ref_id GROUP BY account_id");
                $zero = 0;
                while($d = $q_d->fetch_assoc()){
                    $stmt_gl->bind_param('isssiddii', $user_id, $res_h['tanggal_pembelian'], $res_h['keterangan'], $res_h['nomor_referensi'], $d['account_id'], $d['total'], $zero, $ref_id, $logged_in_user_id);
                    $stmt_gl->execute();
                }
                
                // Jurnal Sisi Kredit (Total dari header)
                $stmt_gl->bind_param('isssiddii', $user_id, $res_h['tanggal_pembelian'], $res_h['keterangan'], $res_h['nomor_referensi'], $res_h['credit_account_id'], $zero, $res_h['total'], $ref_id, $logged_in_user_id);
                $stmt_gl->execute();
                $stmt_gl->close();
            } elseif ($ref_type === 'penjualan') {
                $res_h = $conn->query("SELECT * FROM penjualan WHERE id = $ref_id")->fetch_assoc();
                if (!$res_h) throw new Exception("Data sumber penjualan tidak ditemukan.");
                
                $nomor_referensi = $res_h['nomor_referensi'];
                $tanggal = $res_h['tanggal_penjualan'];
                $keterangan = $res_h['keterangan'];
                $total = $res_h['total'];
                $bayar = $res_h['bayar'];
                $discount = (float)$res_h['discount'];
                $anggota_id = $res_h['customer_id'];
                $payment_method = $res_h['payment_method'];
                
                $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, consignment_item_id, qty, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'penjualan', ?, ?, ?)");
                $zero = 0;
                $null_val = null;

                // Hitung ulang agregasi dari details
                $revenue_totals = []; // account_id => amount
                $hpp_totals = []; // cogs_id => [inventory_id => amount]
                $consignment_entries = [];

                $q_items = $conn->query("
                    SELECT pd.*, i.harga_beli as purchase_price_normal, i.revenue_account_id, i.inventory_account_id, i.cogs_account_id,
                           ci.harga_beli as purchase_price_cons, ci.supplier_id as cons_supplier_id
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

                while($item = $q_items->fetch_assoc()) {
                    if($item['item_type'] === 'normal') {
                        $rev_acc = $item['revenue_account_id'] ?: $def_rev;
                        $revenue_totals[$rev_acc] = ($revenue_totals[$rev_acc] ?? 0) + $item['subtotal'];
                        
                        $cogs_acc = $item['cogs_account_id'] ?: $def_cogs;
                        $inv_acc = $item['inventory_account_id'] ?: $def_inv;
                        $hpp_val = $item['quantity'] * (float)$item['purchase_price_normal'];
                        
                        if(!isset($hpp_totals[$cogs_acc])) $hpp_totals[$cogs_acc] = [];
                        $hpp_totals[$cogs_acc][$inv_acc] = ($hpp_totals[$cogs_acc][$inv_acc] ?? 0) + $hpp_val;
                    } else {
                        // Konsinyasi: Komisi masuk revenue, Harga beli masuk Utang Titipan
                        $total_beli = $item['quantity'] * (float)$item['purchase_price_cons'];
                        $komisi = $item['subtotal'] - $total_beli;
                        
                        $revenue_totals[$cons_rev] = ($revenue_totals[$cons_rev] ?? 0) + $komisi;
                        $consignment_entries[] = [
                            'account_id' => $cons_pay,
                            'amount' => $total_beli,
                            'item_id' => $item['item_id'],
                            'qty' => $item['quantity'],
                            'desc' => "Penjualan Konsinyasi: " . $item['deskripsi_item']
                        ];
                    }
                }

                // 1. Debit Kas/Piutang/WB
                // Cek apakah ada pembayaran WB (berdasarkan log transaksi WB)
                $res_wb = $conn->query("SELECT jumlah FROM transaksi_wajib_belanja WHERE nomor_referensi = '$nomor_referensi'")->fetch_assoc();
                $bayar_wb = (float)($res_wb['jumlah'] ?? 0);
                
                if ($bayar_wb > 0) {
                    $akun_wb = get_setting('wajib_belanja_liability_account_id', null, $conn);
                    if ($akun_wb) {
                        $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $keterangan, $nomor_referensi, $akun_wb, $bayar_wb, $zero, $ref_id, $null_val, $null_val, $logged_in_user_id);
                        $stmt_gl->execute();
                    }
                }

                $piutang_portion = ($payment_method === 'hutang') ? ($total - $bayar - $bayar_wb) : 0;
                $cash_portion = $total - $bayar_wb - $piutang_portion;

                if ($cash_portion > 0) {
                    $cash_acc = get_setting('default_sales_cash_account_id', null, $conn);
                    $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $keterangan, $nomor_referensi, $cash_acc, $cash_portion, $zero, $ref_id, $null_val, $null_val, $logged_in_user_id);
                    $stmt_gl->execute();
                }
                if ($piutang_portion > 0) {
                    $piutang_acc = get_setting('sales_receivable_account_id', null, $conn);
                    $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $keterangan, $nomor_referensi, $piutang_acc, $piutang_portion, $zero, $ref_id, $null_val, $null_val, $logged_in_user_id);
                    $stmt_gl->execute();
                }

                // 1b. Debit Potongan Penjualan (Jika ada diskon global)
                if ($discount > 0) {
                    $discount_acc_id = get_setting('sales_discount_account_id', null, $conn);
                    if ($discount_acc_id) {
                        $ket_discount = "Potongan Penjualan #" . $nomor_referensi;
                        $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $ket_discount, $nomor_referensi, $discount_acc_id, $discount, $zero, $ref_id, $null_val, $null_val, $logged_in_user_id);
                        $stmt_gl->execute();
                    }
                }

                // 2. Credit Revenue
                foreach ($revenue_totals as $acc => $amt) {
                    $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $keterangan, $nomor_referensi, $acc, $zero, $amt, $ref_id, $null_val, $null_val, $logged_in_user_id);
                    $stmt_gl->execute();
                }

                // 3. HPP Normal
                $hpp_desc = "HPP Penjualan #$nomor_referensi";
                foreach ($hpp_totals as $cogs_acc => $inv_list) {
                    foreach ($inv_list as $inv_acc => $amt) {
                        $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $hpp_desc, $nomor_referensi, $cogs_acc, $amt, $zero, $ref_id, $null_val, $null_val, $logged_in_user_id);
                        $stmt_gl->execute();
                        $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $hpp_desc, $nomor_referensi, $inv_acc, $zero, $amt, $ref_id, $null_val, $null_val, $logged_in_user_id);
                        $stmt_gl->execute();
                    }
                }

                // 4. Utang Konsinyasi
                foreach ($consignment_entries as $ent) {
                    $e_desc = $ent['desc'];
                    $e_acc = $ent['account_id'];
                    $e_amt = $ent['amount'];
                    $e_item = $ent['item_id'];
                    $e_qty = $ent['qty'];
                    $stmt_gl->bind_param('isssiddiiii', $user_id, $tanggal, $e_desc, $nomor_referensi, $e_acc, $zero, $e_amt, $ref_id, $e_item, $e_qty, $logged_in_user_id);
                    $stmt_gl->execute();
                }
                $stmt_gl->close();
            }

            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => "Transaksi $ref_type #$ref_id berhasil diposting ulang."]);
        }
    }
} catch (Exception $e) {
    if (isset($conn) && method_exists($conn, 'rollback') && property_exists($conn, 'connect_errno') && $conn->connect_errno === 0) {
        // Cek secara manual jika memungkinkan, atau coba saja rollback jika dirasa perlu
        @$conn->rollback();
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
