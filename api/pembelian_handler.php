<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'User ID tidak ditemukan di sesi.']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'list') {
            // Pagination and Filtering
            $limit = (int)($_GET['limit'] ?? 10);
            if ($limit === -1) {
                $limit = 1000; // Set a high number for "All"
            }
            $page = (int)($_GET['page'] ?? 1);
            $offset = ($page - 1) * $limit;

            $search = $_GET['search'] ?? '';
            $supplier_id_filter = $_GET['supplier_id'] ?? '';
            $bulan = $_GET['bulan'] ?? '';
            $tahun = $_GET['tahun'] ?? '';

            $where_clauses = ['p.user_id = ?'];
            $params = ['i', $user_id];

            if (!empty($search)) {
                $where_clauses[] = '(s.nama_pemasok LIKE ? OR p.keterangan LIKE ? OR p.id = ?)';
                $params[0] .= 'ssi';
                $searchTerm = '%' . $search . '%';
                array_push($params, $searchTerm, $searchTerm, $search);
            }
            if (!empty($supplier_id_filter)) {
                $where_clauses[] = 'p.supplier_id = ?';
                $params[0] .= 'i';
                $params[] = $supplier_id_filter;
            }
            if (!empty($bulan)) {
                $where_clauses[] = 'MONTH(p.tanggal_pembelian) = ?';
                $params[0] .= 'i';
                $params[] = $bulan;
            }
            if (!empty($tahun)) {
                $where_clauses[] = 'YEAR(p.tanggal_pembelian) = ?';
                $params[0] .= 'i';
                $params[] = $tahun;
            }

            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

            // Get total count for pagination
            $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM pembelian p LEFT JOIN suppliers s ON p.supplier_id = s.id $where_sql");
            $bind_params_total = [&$params[0]];
            for ($i = 1; $i < count($params); $i++) { $bind_params_total[] = &$params[$i]; }
            call_user_func_array([$total_stmt, 'bind_param'], $bind_params_total);
            $total_stmt->execute();
            $total_records = $total_stmt->get_result()->fetch_assoc()['total'];
            $total_stmt->close();

            // Get data for the current page
            $query = "SELECT p.*, s.nama_pemasok FROM pembelian p LEFT JOIN suppliers s ON p.supplier_id = s.id $where_sql ORDER BY p.tanggal_pembelian DESC, p.id DESC LIMIT ? OFFSET ?";
            $params[0] .= 'ii';
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $conn->prepare($query);
            $bind_params_main = [&$params[0]];
            for ($i = 1; $i < count($params); $i++) { $bind_params_main[] = &$params[$i]; }
            call_user_func_array([$stmt, 'bind_param'], $bind_params_main);
            $stmt->execute();
            $pembelian_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
 
            $pagination = ['current_page' => $page, 'total_pages' => ceil($total_records / $limit), 'total_records' => $total_records, 'limit' => $limit];
            echo json_encode(['status' => 'success', 'data' => $pembelian_list, 'pagination' => $pagination]);
        
        } elseif ($action === 'get_single') {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) throw new Exception("ID Pembelian tidak valid.");

            $stmt_header = $conn->prepare("SELECT * FROM pembelian WHERE id = ? AND user_id = ?");
            $stmt_header->bind_param('ii', $id, $user_id);
            $stmt_header->execute();
            $header = $stmt_header->get_result()->fetch_assoc();
            $stmt_header->close();
            if (!$header) throw new Exception("Pembelian tidak ditemukan.");

            $stmt_details = $conn->prepare("SELECT * FROM pembelian_details WHERE pembelian_id = ?");
            $stmt_details->bind_param('i', $id);
            $stmt_details->execute();
            $details = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_details->close();

            // Modifikasi: Ubah detail agar sesuai dengan format item
            $item_details = [];
            if (!empty($details)) {
                $detail_ids = array_column($details, 'id');
                $placeholders = implode(',', array_fill(0, count($detail_ids), '?'));
                $types = str_repeat('i', count($detail_ids));

                // Ambil item_id dari pembelian_details (perlu ditambahkan kolomnya)
                // Untuk sekarang, kita asumsikan account_id adalah inventory_account_id dan kita cari item_id nya
                $stmt_items = $conn->prepare("SELECT pd.id, pd.jumlah, i.id as item_id, (pd.jumlah / i.harga_beli) as quantity, i.harga_beli as price FROM pembelian_details pd JOIN items i ON pd.account_id = i.inventory_account_id WHERE pd.id IN ($placeholders)");
                $stmt_items->bind_param($types, ...$detail_ids);
                $stmt_items->execute();
                $item_results = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt_items->close();
                $details = $item_results; // Ganti detail lama dengan detail item
            }

            echo json_encode(['status' => 'success', 'data' => ['header' => $header, 'details' => $details]]);
        } else {
            throw new Exception("Aksi GET tidak valid.");
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        switch ($action) {
            case 'add': // Fallthrough to 'update' for shared validation
            case 'update':
                // 1. Validasi Input
                $supplier_id = !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null;
                $tanggal_pembelian = $data['tanggal_pembelian'] ?? '';
                $keterangan = trim($data['keterangan'] ?? '');
                $jatuh_tempo = !empty($data['jatuh_tempo']) ? $data['jatuh_tempo'] : null;
                $payment_method = $data['payment_method'] ?? '';
                $lines = $data['lines'] ?? [];
                $id = (int)($data['id'] ?? 0);

                if (empty($tanggal_pembelian) || empty($keterangan) || empty($payment_method) || empty($lines)) {
                    throw new Exception("Data tidak lengkap: Tanggal, Keterangan, Metode Pembayaran, dan minimal satu baris item wajib diisi.");
                }

                check_period_lock($tanggal_pembelian, $conn);
                if ($action === 'update') {
                    if ($id <= 0) throw new Exception("ID Pembelian tidak valid untuk diperbarui.");
                    // Cek juga tanggal lama sebelum diubah
                    $stmt_old_date = $conn->prepare("SELECT tanggal_pembelian FROM pembelian WHERE id = ?");
                    $stmt_old_date->bind_param('i', $id);
                    $stmt_old_date->execute();
                    check_period_lock($stmt_old_date->get_result()->fetch_assoc()['tanggal_pembelian'], $conn);
                }

                // 2. Tentukan Akun Kredit
                $credit_account_id = null;
                if ($payment_method === 'credit') {
                    // Ambil akun Utang Usaha dari pengaturan
                    $credit_account_id = (int)get_setting('purchase_payable_account_id', 0, $conn);
                    if ($credit_account_id === 0) {
                        throw new Exception("Akun Utang Usaha untuk pembelian belum diatur di Pengaturan > Akuntansi.");
                    }
                } elseif ($payment_method === 'cash') {
                    // Jika tunai, harus ada akun kas/bank yang dipilih
                    $credit_account_id = (int)($data['kas_account_id'] ?? 0);
                     if ($credit_account_id === 0) {
                        throw new Exception("Untuk pembayaran tunai, Anda harus memilih 'Akun Kas/Bank Pembayaran'.");
                    }
                } else {
                    throw new Exception("Metode pembayaran tidak valid.");
                }

                // 3. Hitung Total dan Validasi Baris
                $total_pembelian = 0;
                foreach ($lines as &$line) { // Gunakan reference (&) untuk menambahkan data ke line
                    if (empty($line['item_id']) || !isset($line['quantity']) || (float)$line['quantity'] <= 0) {
                        throw new Exception("Setiap baris harus memiliki Barang dan Kuantitas yang valid.");
                    }
                    $total_pembelian += (float)$line['subtotal'];

                    // Ambil inventory_account_id dari item
                    $stmt_item_acc = $conn->prepare("SELECT inventory_account_id FROM items WHERE id = ? AND user_id = ?");
                    $stmt_item_acc->bind_param('ii', $line['item_id'], $user_id);
                    $stmt_item_acc->execute();
                    $item_account = $stmt_item_acc->get_result()->fetch_assoc();
                    $stmt_item_acc->close();

                    if (!$item_account || empty($item_account['inventory_account_id'])) {
                        throw new Exception("Barang yang dipilih tidak memiliki pemetaan 'Akun Persediaan'. Harap atur di halaman Barang & Stok.");
                    }
                    // Tambahkan account_id ke array $line untuk digunakan nanti
                    $line['inventory_account_id'] = $item_account['inventory_account_id'];
                }
                unset($line); // Hapus reference

                if (abs($total_pembelian - array_sum(array_column($lines, 'subtotal'))) > 0.01) {
                    throw new Exception("Total pembelian tidak cocok dengan jumlah subtotal item.");
                }

                // 4. Mulai Transaksi Database
                $conn->begin_transaction();

                $pembelian_id = $id;

                if ($action === 'update') {
                    // Hapus entri GL dan detail lama
                    // Penting: Saat menghapus, kita juga harus mengembalikan (mengurangi) stok yang lama.
                    $stmt_old_details = $conn->prepare("SELECT item_id, quantity FROM pembelian_details WHERE pembelian_id = ?");
                    $stmt_old_details->bind_param('i', $id);
                    $stmt_old_details->execute();
                    $old_items = $stmt_old_details->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt_old_details->close();

                    $stmt_delete_gl = $conn->prepare("DELETE FROM general_ledger WHERE ref_id = ? AND ref_type = 'pembelian' AND user_id = ?");
                    $stmt_delete_gl->bind_param('ii', $id, $user_id);
                    $stmt_delete_gl->execute();
                    $stmt_delete_gl->close();

                    $stmt_delete_details = $conn->prepare("DELETE FROM pembelian_details WHERE pembelian_id = ?");
                    $stmt_delete_details->bind_param('i', $id);
                    $stmt_delete_details->execute();
                    $stmt_delete_details->close();

                    // Kembalikan stok lama
                    $stmt_revert_stock = $conn->prepare("UPDATE items SET stok = stok - ? WHERE id = ? AND user_id = ?");
                    foreach ($old_items as $item) {
                        $stmt_revert_stock->bind_param('iii', $item['quantity'], $item['item_id'], $user_id);
                        $stmt_revert_stock->execute();
                    }
                    $stmt_revert_stock->close();

                    // Update header
                    $stmt_pembelian = $conn->prepare(
                        "UPDATE pembelian SET supplier_id=?, tanggal_pembelian=?, jatuh_tempo=?, total=?, keterangan=?, payment_method=?, credit_account_id=?, updated_by=? WHERE id=? AND user_id=?"
                    );
                    $stmt_pembelian->bind_param('issdssiiii', $supplier_id, $tanggal_pembelian, $jatuh_tempo, $total_pembelian, $keterangan, $payment_method, $credit_account_id, $user_id, $id, $user_id);
                } else { // add
                    // Insert header
                    $stmt_pembelian = $conn->prepare(
                        "INSERT INTO pembelian (user_id, supplier_id, tanggal_pembelian, jatuh_tempo, total, keterangan, payment_method, credit_account_id, created_by) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt_pembelian->bind_param(
                        'iissdssii',
                        $user_id, $supplier_id, $tanggal_pembelian, $jatuh_tempo, $total_pembelian, $keterangan, $payment_method, $credit_account_id, $user_id
                    );
                }
                
                if (!$stmt_pembelian->execute()) {
                    $conn->rollback();
                    throw new Exception("Gagal menyimpan header pembelian: " . $stmt_pembelian->error);
                }
                $pembelian_id = $conn->insert_id;
                $stmt_pembelian->close();

                // 6. Insert ke `pembelian_details` dan `general_ledger`
                $stmt_details = $conn->prepare(
                    "INSERT INTO pembelian_details (pembelian_id, item_id, quantity, price, subtotal, account_id) VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt_gl = $conn->prepare(
                    "INSERT INTO general_ledger (user_id, tanggal, keterangan, account_id, debit, kredit, ref_id, ref_type, created_by, updated_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'pembelian', ?, ?)"
                );

                // Jurnal Sisi Debit (untuk setiap baris item)
                $stmt_update_stock = $conn->prepare("UPDATE items SET stok = stok + ? WHERE id = ? AND user_id = ?");

                foreach ($lines as $line) {
                    $item_id = (int)$line['item_id'];
                    $quantity = (int)$line['quantity'];
                    $price = (float)$line['price'];
                    $subtotal = (float)$line['subtotal'];
                    $inventory_account_id = (int)$line['inventory_account_id'];

                    // Insert ke detail
                    $stmt_details->bind_param('iiiddi', $pembelian_id, $item_id, $quantity, $price, $subtotal, $inventory_account_id);
                    $stmt_details->execute();

                    // Update stok barang
                    $stmt_update_stock->bind_param('iii', $quantity, $item_id, $user_id);
                    $stmt_update_stock->execute();

                    // Insert ke GL (Debit)
                    $zero = 0.00;
                    $stmt_gl->bind_param('issiddiii', $user_id, $tanggal_pembelian, $keterangan, $inventory_account_id, $subtotal, $zero, $pembelian_id, $user_id, $user_id);
                    $stmt_gl->execute();
                }

                // Jurnal Sisi Kredit (satu kali untuk total)
                $stmt_gl->bind_param('issiddiii', $user_id, $tanggal_pembelian, $keterangan, $credit_account_id, $zero, $total_pembelian, $pembelian_id, $user_id, $user_id);
                $stmt_gl->execute();

                $stmt_details->close();
                $stmt_update_stock->close();
                $stmt_gl->close();

                // 7. Commit Transaksi
                $conn->commit();

                $log_message = ($action === 'add') ? "Pembelian #{$pembelian_id} sejumlah {$total_pembelian} ditambahkan." : "Pembelian #{$pembelian_id} diperbarui.";
                $success_message = ($action === 'add') ? 'Pembelian berhasil disimpan.' : 'Pembelian berhasil diperbarui.';
                log_activity($_SESSION['username'], 'Simpan Pembelian', $log_message);
                echo json_encode(['status' => 'success', 'message' => $success_message, 'pembelian_id' => $pembelian_id]);
                break;
            
            case 'delete':
                $id = (int)($data['id'] ?? 0);
                if ($id <= 0) throw new Exception("ID Pembelian tidak valid.");

                // Cek periode lock sebelum hapus
                $stmt_old_date = $conn->prepare("SELECT tanggal_pembelian FROM pembelian WHERE id = ?");
                $stmt_old_date->bind_param('i', $id);
                $stmt_old_date->execute();
                check_period_lock($stmt_old_date->get_result()->fetch_assoc()['tanggal_pembelian'], $conn);

                // Ambil detail item yang akan dihapus untuk mengembalikan stok
                $stmt_old_details = $conn->prepare("SELECT item_id, quantity FROM pembelian_details WHERE pembelian_id = ?");
                $stmt_old_details->bind_param('i', $id);
                $stmt_old_details->execute();
                $items_to_revert = $stmt_old_details->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt_old_details->close();

                $conn->begin_transaction();

                // Kembalikan stok
                $stmt_revert_stock = $conn->prepare("UPDATE items SET stok = stok - ? WHERE id = ? AND user_id = ?");
                foreach ($items_to_revert as $item) {
                    $stmt_revert_stock->bind_param('iii', $item['quantity'], $item['item_id'], $user_id);
                    $stmt_revert_stock->execute();
                }
                $stmt_revert_stock->close();

                // Hapus dari GL
                $stmt_gl = $conn->prepare("DELETE FROM general_ledger WHERE ref_id = ? AND ref_type = 'pembelian' AND user_id = ?");
                $stmt_gl->bind_param('ii', $id, $user_id);
                $stmt_gl->execute();
                $stmt_gl->close();

                // Hapus dari pembelian (detail akan terhapus karena ON DELETE CASCADE)
                $stmt = $conn->prepare("DELETE FROM pembelian WHERE id = ? AND user_id = ?");
                $stmt->bind_param('ii', $id, $user_id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                log_activity($_SESSION['username'], 'Hapus Pembelian', "Pembelian ID {$id} dihapus.");
                echo json_encode(['status' => 'success', 'message' => 'Pembelian berhasil dihapus.']);
                break;

            default:
                throw new Exception("Aksi POST tidak valid.");
        }
    }
} catch (Exception $e) {
    // Jika terjadi error dan sedang dalam transaksi, batalkan
    if (isset($conn) && $conn->in_transaction) {
        $conn->rollback();
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>