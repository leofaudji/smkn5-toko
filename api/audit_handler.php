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
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) return 0;

    return ($res['saldo_normal'] === 'Debit') 
        ? (float)$res['total_debit'] - (float)$res['total_kredit']
        : (float)$res['total_kredit'] - (float)$res['total_debit'];
}

try {
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
    $sub_rcv = $conn->query("SELECT SUM(total - bayar) as total FROM penjualan WHERE user_id = $user_id AND payment_method = 'hutang' AND status = 'completed'")->fetch_assoc()['total'] ?? 0;
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

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
