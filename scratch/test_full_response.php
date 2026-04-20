<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$conn = Database::getInstance()->getConnection();
$payable_acc_id = get_setting('consignment_payable_account', null, $conn);
$user_id = 1;

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

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'data' => $data,
    'debug' => [
        'account_id' => $payable_acc_id,
        'count' => count($data)
    ]
], JSON_PRETTY_PRINT);
