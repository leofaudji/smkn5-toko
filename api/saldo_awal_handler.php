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

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Mengambil semua akun dan saldo awalnya dari jurnal yang relevan
        $keterangan_jurnal_neraca = "Jurnal Saldo Awal Neraca";
        $keterangan_jurnal_lr = "Jurnal Saldo Awal Laba Rugi (YTD)";

        // Ambil ID akun Laba Ditahan untuk dikecualikan dari tampilan
        $retained_earnings_acc_id = (int)get_setting('retained_earnings_account_id', 0, $conn);

        $stmt = $conn->prepare("
            SELECT 
                a.id, a.kode_akun, a.nama_akun, a.tipe_akun, a.saldo_normal,
                COALESCE(gl.debit, 0) as debit, 
                COALESCE(gl.kredit, 0) as kredit
            FROM accounts a
            LEFT JOIN (
                SELECT gl_inner.account_id, SUM(gl_inner.debit) as debit, SUM(gl_inner.kredit) as kredit
                FROM general_ledger gl_inner
                JOIN jurnal_entries je ON gl_inner.ref_id = je.id AND gl_inner.ref_type = 'jurnal'
                WHERE je.keterangan IN (?, ?) AND je.user_id = ?
                GROUP BY gl_inner.account_id
            ) gl ON a.id = gl.account_id
            WHERE a.user_id = ? AND a.id != ?
            ORDER BY a.kode_akun ASC
        ");
        $stmt->bind_param('ssiii', $keterangan_jurnal_neraca, $keterangan_jurnal_lr, $user_id, $user_id, $retained_earnings_acc_id);
        $stmt->execute();
        $accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['status' => 'success', 'data' => $accounts]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $entries = $_POST['entries'] ?? [];

        if (empty($entries)) {
            throw new Exception("Tidak ada data entri yang dikirim.");
        }

        $total_debit_input = 0;
        $total_kredit_input = 0;
        foreach ($entries as $entry) {
            $total_debit_input += (float)($entry['debit'] ?? 0);
            $total_kredit_input += (float)($entry['kredit'] ?? 0);
        }

        // Hitung selisih sebagai Laba/Rugi Berjalan (YTD)
        // Dalam neraca awal, Aset + Beban (Debit) = Liabilitas + Ekuitas + Pendapatan (Kredit)
        // Jadi, Laba/Rugi = Total Kredit - Total Debit
        $laba_rugi_ytd = $total_kredit_input - $total_debit_input;

        // Ambil akun Laba Ditahan untuk menampung selisih
        $retained_earnings_acc_id = (int)get_setting('retained_earnings_account_id', 0, $conn);
        if (abs($laba_rugi_ytd) > 0.01 && $retained_earnings_acc_id === 0) {
            throw new Exception("Jurnal tidak seimbang dan Akun Laba Ditahan (Retained Earnings) belum diatur di Pengaturan > Akuntansi untuk menampung selisih.");
        }

        // Tambahkan baris Laba Ditahan secara otomatis untuk menyeimbangkan jurnal
        if (abs($laba_rugi_ytd) > 0.01) {
            $entries[] = [
                'account_id' => $retained_earnings_acc_id,
                'debit' => $laba_rugi_ytd < 0 ? abs($laba_rugi_ytd) : 0,
                'kredit' => $laba_rugi_ytd > 0 ? $laba_rugi_ytd : 0,
            ];
        }

        $conn->begin_transaction();

        // --- Hapus Jurnal Saldo Awal Lama ---
        $keterangan_jurnal = "Jurnal Saldo Awal"; // Nama jurnal baru yang terpadu
        $stmt_find_old = $conn->prepare("SELECT id FROM jurnal_entries WHERE keterangan = ? AND user_id = ?");
        $stmt_find_old->bind_param('si', $keterangan_jurnal, $user_id);
        $stmt_find_old->execute();
        if ($old_journal = $stmt_find_old->get_result()->fetch_assoc()) {
            $old_journal_id = $old_journal['id'];
            $conn->query("DELETE FROM general_ledger WHERE ref_id = $old_journal_id AND ref_type = 'jurnal' AND user_id = $user_id");
            $conn->query("DELETE FROM jurnal_entries WHERE id = $old_journal_id AND user_id = $user_id");
        }
        $stmt_find_old->close();

        $opening_balance_date = date('Y-m-d', strtotime(date('Y-01-01') . ' -1 day'));
        $logged_in_user_id = $_SESSION['user_id'] ?? 0;

        // --- Proses Jurnal Saldo Awal Terpadu ---
        $stmt_header = $conn->prepare("INSERT INTO jurnal_entries (user_id, tanggal, keterangan, created_by) VALUES (?, ?, ?, ?)");
        $stmt_header->bind_param('issi', $user_id, $opening_balance_date, $keterangan_jurnal, $logged_in_user_id);
        $stmt_header->execute();
        $jurnal_id = $conn->insert_id;
        $stmt_header->close();
        $nomor_ref = 'SA-' . $jurnal_id;

        $stmt_detail = $conn->prepare("INSERT INTO jurnal_details (jurnal_entry_id, account_id, debit, kredit) VALUES (?, ?, ?, ?)");
        $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'jurnal', ?)");
        foreach ($entries as $line) {
            $stmt_detail->bind_param('iidd', $jurnal_id, $line['account_id'], $line['debit'], $line['kredit']); $stmt_detail->execute();
            $stmt_gl->bind_param('isssiddii', $user_id, $opening_balance_date, $keterangan_jurnal, $nomor_ref, $line['account_id'], $line['debit'], $line['kredit'], $jurnal_id, $logged_in_user_id); $stmt_gl->execute();
        }
        $stmt_detail->close(); $stmt_gl->close();

        $conn->query("UPDATE accounts SET saldo_awal = 0 WHERE user_id = $user_id");
        $conn->commit();

        log_activity($_SESSION['username'], 'Set Saldo Awal', 'Saldo awal perusahaan telah diatur.');
        echo json_encode(['status' => 'success', 'message' => 'Saldo awal berhasil disimpan.']);
    }
} catch (Exception $e) {
    if (isset($conn) && method_exists($conn, 'in_transaction') && $conn->in_transaction()) $conn->rollback();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>