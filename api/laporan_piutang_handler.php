<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$user_id = 1;

try {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        // Query rekap sisa hutang per anggota
        $sql = "
            SELECT 
                p.customer_id,
                p.customer_name,
                a.nomor_anggota,
                SUM(p.total) as total_kredit,
                SUM(p.bayar) as total_bayar,
                SUM(p.total - p.bayar) as sisa_hutang
            FROM penjualan p
            LEFT JOIN anggota a ON p.customer_id = a.id
            WHERE p.user_id = ? 
              AND p.payment_method = 'hutang' 
              AND p.status = 'completed'
            GROUP BY p.customer_id, p.customer_name, a.nomor_anggota
            HAVING sisa_hutang > 0
            ORDER BY p.customer_name ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $result]);

    } elseif ($action === 'get_detail') {
        // Ambil detail faktur yang belum lunas untuk customer tertentu
        $customer_id = (int)($_GET['customer_id'] ?? 0);
        if (!$customer_id) throw new Exception("ID Anggota tidak valid.");

        $sql = "SELECT id, nomor_referensi, tanggal_penjualan, total, bayar, (total - bayar) as sisa 
                FROM penjualan 
                WHERE user_id = ? AND customer_id = ? AND payment_method = 'hutang' AND (total - bayar) > 0 AND status = 'completed'
                ORDER BY tanggal_penjualan ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $user_id, $customer_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $result]);

    } elseif ($action === 'pay') {
        // Proses pembayaran piutang
        $input = json_decode(file_get_contents('php://input'), true);
        $customer_id = (int)($input['customer_id'] ?? 0);
        $amount = (float)($input['amount'] ?? 0);
        $account_id = (int)($input['account_id'] ?? 0);
        $date = $input['date'] ?? date('Y-m-d');
        $note = $input['note'] ?? 'Pembayaran Piutang';
        $created_by = $_SESSION['user_id'];

        if (!$customer_id || $amount <= 0 || !$account_id) {
            throw new Exception("Data pembayaran tidak lengkap.");
        }

        $conn->begin_transaction();

        // Ambil faktur yang belum lunas (FIFO)
        $sql = "SELECT id, nomor_referensi, total, bayar FROM penjualan 
                WHERE user_id = ? AND customer_id = ? AND payment_method = 'hutang' AND (total - bayar) > 0 AND status = 'completed'
                ORDER BY tanggal_penjualan ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $user_id, $customer_id);
        $stmt->execute();
        $invoices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $receivable_acc_id = get_setting('sales_receivable_account_id', null, $conn);
        if (!$receivable_acc_id) throw new Exception("Akun Piutang belum diatur.");

        $remaining_payment = $amount;
        $stmt_upd = $conn->prepare("UPDATE penjualan SET bayar = bayar + ? WHERE id = ?");
        $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'penjualan', ?)");

        // Generate Nomor Referensi Pembayaran Unik
        // Format: PAY-RCV-{YYYYMMDD}-{Random 4 digit}
        $payment_ref = "PAY-RCV-" . date('Ymd') . "-" . rand(1000, 9999);

        foreach ($invoices as $inv) {
            if ($remaining_payment <= 0) break;

            $sisa_tagihan = $inv['total'] - $inv['bayar'];
            $bayar_ini = min($remaining_payment, $sisa_tagihan);
            $zero = 0;

            // 1. Update Penjualan
            $stmt_upd->bind_param('di', $bayar_ini, $inv['id']);
            $stmt_upd->execute();

            // 2. Jurnal: Debit Kas, Kredit Piutang
            // Catatan: Hanya mencatat aliran kas dan pengurangan piutang.
            // Pendapatan dan Persediaan sudah dicatat saat transaksi penjualan (Accrual Basis).
            $ket_jurnal = "Pelunasan Piutang " . $inv['nomor_referensi'] . " ($note)";
            // Debit Kas (Uang Masuk)
            $stmt_gl->bind_param('isssiddii', $user_id, $date, $ket_jurnal, $payment_ref, $account_id, $bayar_ini, $zero, $inv['id'], $created_by);
            $stmt_gl->execute();
            // Kredit Piutang (Piutang Berkurang)
            $stmt_gl->bind_param('isssiddii', $user_id, $date, $ket_jurnal, $payment_ref, $receivable_acc_id, $zero, $bayar_ini, $inv['id'], $created_by);
            $stmt_gl->execute();

            $remaining_payment -= $bayar_ini;
        }

        if ($remaining_payment > 0) {
            // Jika masih ada sisa uang tapi hutang sudah lunas semua, 
            // idealnya masuk ke deposit anggota atau dikembalikan.
            // Untuk saat ini kita throw error atau biarkan (tergantung kebijakan).
            // Disini kita biarkan saja, sisa uang tidak tercatat (atau bisa dianggap kembalian tunai).
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil diproses.']);
    }

} catch (Exception $e) {
    if (isset($conn) && $conn->in_transaction) $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
