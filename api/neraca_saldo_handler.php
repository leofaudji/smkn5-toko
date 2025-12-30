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
            COALESCE(SUM(gl.debit), 0) as total_debit,
            COALESCE(SUM(gl.kredit), 0) as total_kredit
        FROM accounts a
        LEFT JOIN general_ledger gl ON a.id = gl.account_id AND gl.tanggal <= ?
        WHERE a.user_id = ?
        GROUP BY a.id, a.kode_akun, a.nama_akun, a.saldo_normal
        ORDER BY a.kode_akun ASC
    ");
    $stmt->bind_param('si', $tanggal, $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $data = [];
    $totalDebit = 0;
    $totalKredit = 0;

    foreach ($result as $row) {
        // Karena saldo awal sudah menjadi bagian dari general_ledger, kita hanya perlu menghitung selisih total debit dan kredit.
        $saldo_akhir = ($row['saldo_normal'] === 'Debit')
            ? (float)$row['total_debit'] - (float)$row['total_kredit']
            : (float)$row['total_kredit'] - (float)$row['total_debit'];

        if (abs($saldo_akhir) > 0.001) { // Tampilkan akun dengan saldo tidak nol
            $debit = 0;
            $kredit = 0;
            // Jika saldo akhir positif, letakkan di sisi saldo normalnya.
            // Jika negatif (saldo kontra), letakkan di sisi berlawanan.
            if ($saldo_akhir > 0) {
                if ($row['saldo_normal'] === 'Debit') {
                    $debit = $saldo_akhir;
                } else {
                    $kredit = $saldo_akhir;
                }
            } else {
                if ($row['saldo_normal'] === 'Debit') {
                    $kredit = abs($saldo_akhir);
                } else {
                    $debit = abs($saldo_akhir);
                }
            }
            $totalDebit += $debit;
            $totalKredit += $kredit;
            $data[] = ['kode_akun' => $row['kode_akun'], 'nama_akun' => $row['nama_akun'], 'debit' => $debit, 'kredit' => $kredit];
        }
    }

    echo json_encode(['status' => 'success', 'data' => $data, 'totals' => ['debit' => $totalDebit, 'kredit' => $totalKredit]]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>