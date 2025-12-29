<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$user_id = 1; // ID Pemilik Data (Toko)
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

try {
    // Query ini diadaptasi dari TrialBalanceReportBuilder
    $stmt = $conn->prepare("
        SELECT 
            a.kode_akun, 
            a.nama_akun,
            a.saldo_normal,
            a.saldo_awal + COALESCE(SUM(CASE WHEN gl.tanggal <= ? THEN gl.debit ELSE 0 END), 0) as total_debit,
            a.saldo_awal + COALESCE(SUM(CASE WHEN gl.tanggal <= ? THEN gl.kredit ELSE 0 END), 0) as total_kredit
        FROM accounts a
        LEFT JOIN general_ledger gl ON a.id = gl.account_id AND gl.tanggal <= ?
        WHERE a.user_id = ?
        GROUP BY a.id, a.kode_akun, a.nama_akun, a.saldo_normal, a.saldo_awal
        ORDER BY a.kode_akun ASC
    ");
    $stmt->bind_param('sssi', $tanggal, $tanggal, $tanggal, $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $data = [];
    $totalDebit = 0;
    $totalKredit = 0;

    foreach ($result as $row) {
        $saldo_akhir = ($row['saldo_normal'] === 'Debit') 
            ? (float)$row['total_debit'] - (float)$row['total_kredit'] 
            : (float)$row['total_kredit'] - (float)$row['total_debit'];

        if (abs($saldo_akhir) > 0.001) { // Tampilkan akun dengan saldo tidak nol
            $debit = 0;
            $kredit = 0;
            if ($row['saldo_normal'] === 'Debit') {
                $debit = $saldo_akhir;
                $totalDebit += $saldo_akhir;
            } else {
                $kredit = $saldo_akhir;
                $totalKredit += $saldo_akhir;
            }
            $data[] = ['kode_akun' => $row['kode_akun'], 'nama_akun' => $row['nama_akun'], 'debit' => $debit, 'kredit' => $kredit];
        }
    }

    echo json_encode(['status' => 'success', 'data' => $data, 'totals' => ['debit' => $totalDebit, 'kredit' => $totalKredit]]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>