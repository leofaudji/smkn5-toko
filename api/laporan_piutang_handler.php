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
                SUM(p.bayar + p.bayar_wb) as total_bayar,
                SUM(p.total - p.bayar - p.bayar_wb) as sisa_hutang
            FROM penjualan p
            LEFT JOIN anggota a ON p.customer_id = a.id
            WHERE p.payment_method = 'hutang' 
              AND p.status = 'completed'
            GROUP BY p.customer_id, p.customer_name, a.nomor_anggota
            HAVING sisa_hutang > 0
            ORDER BY p.customer_name ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = stmt_fetch_all($stmt);
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $result]);

    } elseif ($action === 'get_detail') {
        // Ambil detail faktur yang belum lunas untuk customer tertentu
        $customer_id = (int) ($_GET['customer_id'] ?? 0);
        if (!$customer_id)
            throw new Exception("ID Anggota tidak valid.");

        // Ambil Saldo WB Anggota
        $sql_bal = "SELECT saldo_wajib_belanja FROM anggota WHERE id = ?";
        $stmt_bal = $conn->prepare($sql_bal);
        $stmt_bal->bind_param('i', $customer_id);
        $stmt_bal->execute();
        $anggota = stmt_fetch_assoc($stmt_bal);
        $stmt_bal->close();

        $sql = "SELECT id, nomor_referensi, tanggal_penjualan, total, bayar, bayar_wb, (total - bayar - bayar_wb) as sisa 
                FROM penjualan 
                WHERE customer_id = ? AND payment_method = 'hutang' AND (total - bayar - bayar_wb) > 0 AND status = 'completed'
                ORDER BY tanggal_penjualan ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $result = stmt_fetch_all($stmt);
        $stmt->close();

        echo json_encode([
            'success' => true, 
            'data' => $result, 
            'saldo_wb' => (float)($anggota['saldo_wajib_belanja'] ?? 0)
        ]);

    } elseif ($action === 'pay') {
        // Proses pembayaran piutang
        $input = json_decode(file_get_contents('php://input'), true);
        $customer_id = (int) ($input['customer_id'] ?? 0);
        $amount = (float) ($input['amount'] ?? 0);
        $account_id = (int) ($input['account_id'] ?? 0);
        $method = $input['method'] ?? 'cash'; // 'cash' or 'wb'
        $date = $input['date'] ?? date('Y-m-d');
        $note = $input['note'] ?? 'Pembayaran Piutang';
        $created_by = $_SESSION['user_id'];

        if (!$customer_id || $amount <= 0 || ($method === 'cash' && !$account_id)) {
            throw new Exception("Data pembayaran tidak lengkap.");
        }

        $conn->begin_transaction();

        // 1. Validasi Saldo jika menggunakan WB
        if ($method === 'wb') {
            $stmt_bal = $conn->prepare("SELECT saldo_wajib_belanja, nama_lengkap FROM anggota WHERE id = ?");
            $stmt_bal->bind_param('i', $customer_id);
            $stmt_bal->execute();
            $anggota = stmt_fetch_assoc($stmt_bal);
            $stmt_bal->close();

            if (!$anggota) throw new Exception("Data anggota tidak ditemukan.");
            if ($anggota['saldo_wajib_belanja'] < $amount) {
                throw new Exception("Saldo Wajib Belanja tidak mencukupi (Tersedia: " . number_format($anggota['saldo_wajib_belanja'], 0, ',', '.') . ")");
            }

            // Kurangi Saldo WB di database Anggota
            $stmt_upd_bal = $conn->prepare("UPDATE anggota SET saldo_wajib_belanja = saldo_wajib_belanja - ? WHERE id = ?");
            $stmt_upd_bal->bind_param('di', $amount, $customer_id);
            $stmt_upd_bal->execute();
            $stmt_upd_bal->close();
        }

        // Ambil faktur yang belum lunas (FIFO)
        $sql = "SELECT id, nomor_referensi, total, bayar, bayar_wb FROM penjualan 
                WHERE customer_id = ? AND payment_method = 'hutang' AND (total - bayar - bayar_wb) > 0 AND status = 'completed'
                ORDER BY tanggal_penjualan ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $invoices = stmt_fetch_all($stmt);
        $stmt->close();

        $receivable_acc_id = get_setting('sales_receivable_account_id', null, $conn);
        if (!$receivable_acc_id)
            throw new Exception("Akun Piutang belum diatur.");

        $wb_liability_acc_id = null;
        if ($method === 'wb') {
            $wb_liability_acc_id = get_setting('wajib_belanja_liability_account_id', null, $conn);
            if (!$wb_liability_acc_id) throw new Exception("Akun Kewajiban Wajib Belanja belum diatur.");
        }

        $remaining_payment = $amount;
        $stmt_upd_cash = $conn->prepare("UPDATE penjualan SET bayar = bayar + ? WHERE id = ?");
        $stmt_upd_wb = $conn->prepare("UPDATE penjualan SET bayar_wb = bayar_wb + ? WHERE id = ?");
        $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'penjualan', ?)");

        // Generate Nomor Referensi Pembayaran Unik
        $payment_ref = ($method === 'wb' ? "PAY-WB-" : "PAY-RCV-") . date('Ymd') . "-" . rand(1000, 9999);

        foreach ($invoices as $inv) {
            if ($remaining_payment <= 0)
                break;

            $sisa_tagihan = $inv['total'] - $inv['bayar'] - $inv['bayar_wb'];
            $bayar_ini = min($remaining_payment, $sisa_tagihan);
            $zero = 0;

            // 1. Update Penjualan
            if ($method === 'wb') {
                $stmt_upd_wb->bind_param('di', $bayar_ini, $inv['id']);
                $stmt_upd_wb->execute();

                // Log Transaksi WB
                $ket_wb = "Bayar Hutang #" . $inv['nomor_referensi'] . " ($note)";
                $stmt_log_wb = $conn->prepare("INSERT INTO transaksi_wajib_belanja (user_id, anggota_id, tanggal, jenis, jumlah, metode_pembayaran, keterangan, nomor_referensi, created_by) VALUES (?, ?, ?, 'belanja', ?, 'potong_saldo', ?, ?, ?)");
                $stmt_log_wb->bind_param('iisdssi', $user_id, $customer_id, $date, $bayar_ini, $ket_wb, $payment_ref, $created_by);
                $stmt_log_wb->execute();
                $stmt_log_wb->close();
            } else {
                $stmt_upd_cash->bind_param('di', $bayar_ini, $inv['id']);
                $stmt_upd_cash->execute();
            }

            // 2. Jurnal
            $ket_jurnal = ($method === 'wb' ? "Pelunasan Hutang via WB " : "Pelunasan Piutang ") . $inv['nomor_referensi'] . " ($note)";
            $debit_account = ($method === 'wb' ? $wb_liability_acc_id : $account_id);
            
            // Debit (WB Liability berkurang atau Kas bertambah)
            $stmt_gl->bind_param('isssiddii', $user_id, $date, $ket_jurnal, $payment_ref, $debit_account, $bayar_ini, $zero, $inv['id'], $created_by);
            $stmt_gl->execute();
            // Kredit Piutang (Piutang Berkurang)
            $stmt_gl->bind_param('isssiddii', $user_id, $date, $ket_jurnal, $payment_ref, $receivable_acc_id, $zero, $bayar_ini, $inv['id'], $created_by);
            $stmt_gl->execute();

            $remaining_payment -= $bayar_ini;
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Pembayaran ' . ($method === 'wb' ? 'via WB ' : '') . 'berhasil diproses.']);

    } elseif ($action === 'import_piutang') {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File tidak terunggah dengan benar.");
        }

        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $created_by = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

        $receivable_acc_id = get_setting('sales_receivable_account_id', null, $conn);
        $retained_earnings_acc_id = get_setting('retained_earnings_account_id', null, $conn);

        if (!$receivable_acc_id || !$retained_earnings_acc_id) {
            throw new Exception("Akun Piutang atau Akun Laba Ditahan belum diatur di Pengaturan.");
        }

        $file = $_FILES['csv_file']['tmp_name'];
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Detect delimiter
            $firstLine = fgets($handle);
            $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
            rewind($handle);
            
            fgetcsv($handle, 1000, $delimiter); // Skip header

            $success = 0;
            $skipped = 0;
            $errors = [];
            $line = 1;

            $batch_id = date('YmdHis');
            $conn->begin_transaction();

            // Prepare statements
            $stmt_penjualan = $conn->prepare("INSERT INTO penjualan (user_id, customer_id, nomor_referensi, tanggal_penjualan, customer_name, subtotal, discount, total, bayar, kembali, keterangan, created_by, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, 0, ?, 0, 0, ?, ?, 'hutang', 'completed')");
            $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'penjualan', ?)");
            $stmt_lookup = $conn->prepare("SELECT id, nama_lengkap FROM anggota WHERE id = ?");

            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                $line++;
                $col_count = count($row);
                if ($col_count < 3) {
                    $errors[] = "Baris $line: Kolom kurang dari 3 (Ditemukan: $col_count). Pastikan delimiter benar.";
                    $skipped++;
                    continue;
                }

                // no (id), noanggota, [nama], jumlah
                $member_id = (int)trim($row[0]);
                
                // If 3 columns: amount is at index 2. If 4 or 5 columns: amount is at index 3.
                $jumlah_idx = ($col_count === 3) ? 2 : 3;
                $jumlah = (float) str_replace(',', '.', $row[$jumlah_idx]);

                if ($member_id <= 0 || $jumlah <= 0) {
                    $errors[] = "Baris $line: ID Anggota ($member_id) atau Jumlah ($jumlah) tidak valid.";
                    $skipped++;
                    continue;
                }

                // Lookup Anggota
                $stmt_lookup->bind_param("i", $member_id);
                $stmt_lookup->execute();
                $anggota = stmt_fetch_assoc($stmt_lookup);
                
                if (!$anggota) {
                    $errors[] = "Baris $line: Anggota dengan ID $member_id tidak ditemukan.";
                    continue;
                }

                $customer_id = $anggota['id'];
                $customer_name = $anggota['nama_lengkap'];
                $nomor_referensi = 'IMP-RCV-' . $batch_id . '-' . str_pad($line, 4, '0', STR_PAD_LEFT);
                $keterangan = "Impor Saldo Piutang - $customer_name";

                // 1. Insert Penjualan
                $stmt_penjualan->bind_param('iisssddsi', $user_id, $customer_id, $nomor_referensi, $tanggal, $customer_name, $jumlah, $jumlah, $keterangan, $created_by);
                if (!$stmt_penjualan->execute()) {
                    $errors[] = "Baris $line: Gagal simpan ke database (Penjualan): " . $stmt_penjualan->error;
                    continue;
                }
                $penjualan_id = $stmt_penjualan->insert_id;

                // 2. Jurnal: Debit Piutang, Kredit Laba Ditahan
                $zero = 0;
                // Debit Piutang
                //$stmt_gl->bind_param('isssiddii', $user_id, $tanggal, $keterangan, $nomor_referensi, $receivable_acc_id, $jumlah, $zero, $penjualan_id, $created_by);
                //$stmt_gl->execute();
                // Kredit Laba Ditahan
                //$stmt_gl->bind_param('isssiddii', $user_id, $tanggal, $keterangan, $nomor_referensi, $retained_earnings_acc_id, $zero, $jumlah, $penjualan_id, $created_by);
                //$stmt_gl->execute();

                $success++;
            }

            fclose($handle);
            $conn->commit();
            echo json_encode(['success' => true, 'message' => "Berhasil mengimpor $success data piutang.", 'skipped' => $skipped, 'errors' => $errors]);
        } else {
            throw new Exception("Gagal membuka file CSV.");
        }
        exit;
    }


} catch (Exception $e) {
    if (isset($conn) && method_exists($conn, 'in_transaction') && $conn->in_transaction())
        $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>