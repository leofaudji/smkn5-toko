<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

// Include the accounting helper functions
require_once __DIR__ . '/../includes/accounting_helper.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$user_id = 1; // ID Pemilik Data (Toko)
$logged_in_user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'list') {
            $limit = (int)($_GET['limit'] ?? 15);
            $page = (int)($_GET['page'] ?? 1);
            $offset = ($page - 1) * $limit;
            $search = $_GET['search'] ?? '';
            $stok_filter = $_GET['stok_filter'] ?? '';
            $category_filter = $_GET['category_filter'] ?? '';

            $where_clauses = ['i.user_id = ?']; // Perbaikan di sini: tambahkan alias tabel 'i'
            $params = ['i', $user_id];

            if (!empty($search)) {
                $where_clauses[] = '(nama_barang LIKE ? OR sku LIKE ?)';
                $params[0] .= 'ss';
                $searchTerm = '%' . $search . '%';
                array_push($params, $searchTerm, $searchTerm);
            }
            if ($stok_filter === 'ready') {
                $where_clauses[] = 'stok > 0';
            } elseif ($stok_filter === 'empty') {
                $where_clauses[] = 'stok <= 0';
            }
            if (!empty($category_filter)) {
                $where_clauses[] = 'i.category_id = ?';
                $params[0] .= 'i';
                $params[] = $category_filter;
            }

            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

            $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM items i $where_sql");
            $bind_params_total = [&$params[0]];
            for ($i = 1; $i < count($params); $i++) { $bind_params_total[] = &$params[$i]; }
            call_user_func_array([$total_stmt, 'bind_param'], $bind_params_total);
            $total_stmt->execute();
            $total_records = $total_stmt->get_result()->fetch_assoc()['total'];
            $total_stmt->close();

            $query = "
                SELECT i.*, ic.nama_kategori
                FROM items i
                LEFT JOIN item_categories ic ON i.category_id = ic.id
                $where_sql ORDER BY i.nama_barang ASC, i.id ASC LIMIT ? OFFSET ?
            ";
            $params[0] .= 'ii';
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $conn->prepare($query);
            $bind_params_main = [&$params[0]];
            for ($i = 1; $i < count($params); $i++) { $bind_params_main[] = &$params[$i]; }
            call_user_func_array([$stmt, 'bind_param'], $bind_params_main);
            $stmt->execute();
            $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $pagination = ['current_page' => $page, 'total_pages' => ceil($total_records / $limit), 'total_records' => $total_records];
            echo json_encode(['status' => 'success', 'data' => $items, 'pagination' => $pagination]);
        
        } elseif ($action === 'get_accounts') {
            $stmt = $conn->prepare("SELECT id, kode_akun, nama_akun, tipe_akun FROM accounts WHERE user_id = ? ORDER BY kode_akun ASC");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $all_accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $accounts = [
                'aset' => array_values(array_filter($all_accounts, fn($acc) => $acc['tipe_akun'] == 'Aset')),
                'beban' => array_values(array_filter($all_accounts, fn($acc) => $acc['tipe_akun'] == 'Beban')),
                'pendapatan' => array_values(array_filter($all_accounts, fn($acc) => $acc['tipe_akun'] == 'Pendapatan')),
            ];
            echo json_encode(['status' => 'success', 'data' => $accounts]);
        
        } elseif ($action === 'get_categories') {
            $stmt = $conn->prepare("SELECT id, nama_kategori FROM item_categories WHERE user_id = ? ORDER BY nama_kategori ASC");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            echo json_encode(['status' => 'success', 'data' => $categories]);
        } elseif ($action === 'get_adjustment_accounts') {
            $stmt = $conn->prepare("SELECT id, kode_akun, nama_akun FROM accounts WHERE user_id = ? AND tipe_akun IN ('Beban', 'Ekuitas', 'Pendapatan') ORDER BY kode_akun ASC");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            echo json_encode(['status' => 'success', 'data' => $accounts]);

        } elseif ($action === 'get_adjustment_history') {
            $item_id = (int)($_GET['item_id'] ?? 0);
            if (empty($item_id)) throw new Exception("Item ID tidak valid.");

            $stmt = $conn->prepare("SELECT sa.*, u.username FROM stock_adjustments sa LEFT JOIN users u ON sa.user_id = u.id WHERE sa.item_id = ? ORDER BY sa.tanggal DESC, sa.created_at DESC");
            $stmt->bind_param('i', $item_id);
            $stmt->execute();
            $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            echo json_encode(['status' => 'success', 'data' => $history]);
        
        } elseif ($action === 'get_kartu_stok') {
            $item_id = (int)($_GET['item_id'] ?? 0);
            $start_date = $_GET['start_date'] ?? '';
            $end_date = $_GET['end_date'] ?? '';
            
            if (empty($item_id) || empty($start_date) || empty($end_date)) {
                throw new Exception("Parameter tidak lengkap: item_id, start_date, dan end_date diperlukan.");
            }

            // 1. Get Item Info
            $stmt = $conn->prepare("SELECT id, nama_barang, sku FROM items WHERE id = ? AND user_id = ?");
            $stmt->bind_param('ii', $item_id, $user_id);
            $stmt->execute();
            $item_info = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$item_info) throw new Exception("Barang tidak ditemukan.");

            // 2. Calculate Saldo Awal (stock at the beginning of start_date)
            $stmt = $conn->prepare("
                SELECT COALESCE(SUM(debit - kredit), 0) as saldo
                FROM kartu_stok 
                WHERE item_id = ? AND tanggal < ?
            ");
            $stmt->bind_param("is", $item_id, $start_date);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $saldo_awal = (int)$result['saldo'];
            $stmt->close();

            // 3. Get all transactions within the date range
            $stmt = $conn->prepare("
                SELECT 
                    id, tanggal, keterangan, 
                    debit, 
                    kredit 
                FROM kartu_stok 
                WHERE item_id = ? AND tanggal BETWEEN ? AND ?
                ORDER BY tanggal ASC, id ASC
            ");
            $stmt->bind_param("iss", $item_id, $start_date, $end_date);
            $stmt->execute();
            $transactions_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // 4. Process transactions and calculate running balance
            $transactions = [];
            $saldo_berjalan = $saldo_awal;
            $total_debit = 0;
            $total_kredit = 0;

            foreach ($transactions_raw as $trx) {
                $saldo_berjalan += $trx['debit'] - $trx['kredit'];
                $total_debit += $trx['debit'];
                $total_kredit += $trx['kredit'];
                $transactions[] = [
                    'tanggal' => $trx['tanggal'],
                    'keterangan' => $trx['keterangan'],
                    'debit' => (int)$trx['debit'],
                    'kredit' => (int)$trx['kredit'],
                    'masuk' => (int)$trx['debit'], // Alias untuk kompatibilitas frontend
                    'keluar' => (int)$trx['kredit'], // Alias untuk kompatibilitas frontend
                    'saldo' => $saldo_berjalan
                ];
            }

            $saldo_akhir = $saldo_awal + $total_debit - $total_kredit;

            $response_data = [
                'item_info' => $item_info,
                'summary' => [
                    'saldo_awal' => $saldo_awal,
                    'total_debit' => $total_debit,
                    'total_kredit' => $total_kredit,
                    'total_masuk' => $total_debit, // Alias untuk kompatibilitas frontend
                    'total_keluar' => $total_kredit, // Alias untuk kompatibilitas frontend
                    'saldo_akhir' => $saldo_akhir
                ],
                'transactions' => $transactions
            ];

            echo json_encode(['status' => 'success', 'data' => $response_data]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get action from JSON body or form-data
        $is_json_request = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
        $data = $is_json_request ? json_decode(file_get_contents('php://input'), true) : $_POST;
        $action = $data['action'] ?? '';

        if ($action === 'save' || $action === 'update') { // Handle both add and update
            $nama_barang = trim($_POST['nama_barang']);
            $sku = trim($_POST['sku']) ?: null;
            $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $harga_beli = (float)$_POST['harga_beli'];
            $harga_jual = (float)$_POST['harga_jual'];
            $inventory_account_id = !empty($_POST['inventory_account_id']) ? (int)$_POST['inventory_account_id'] : null;
            $cogs_account_id = !empty($_POST['cogs_account_id']) ? (int)$_POST['cogs_account_id'] : null;
            $revenue_account_id = !empty($_POST['sales_account_id']) ? (int)$_POST['sales_account_id'] : null;

            if (empty($nama_barang) || $harga_beli < 0 || $harga_jual < 0) {
                throw new Exception("Data barang tidak lengkap atau tidak valid."); 
            }

            if ($action === 'update') { // Update
                $id = (int)($_POST['item-id'] ?? 0);
                // Saat update, jangan ubah stok. Stok diubah melalui penyesuaian/pembelian.
                $stmt = $conn->prepare("UPDATE items SET nama_barang=?, sku=?, category_id=?, harga_beli=?, harga_jual=?, inventory_account_id=?, cogs_account_id=?, revenue_account_id=? WHERE id=? AND user_id=?");
                $stmt->bind_param('ssiddiiiii', $nama_barang, $sku, $category_id, $harga_beli, $harga_jual, $inventory_account_id, $cogs_account_id, $revenue_account_id, $id, $user_id);
            } else { // Add
                // Saat add, ambil stok dari form.
                $stok = (int)($_POST['stok'] ?? 0);
                if ($stok < 0) {
                    throw new Exception("Stok awal tidak boleh negatif.");
                }
                $stmt = $conn->prepare("INSERT INTO items (user_id, nama_barang, sku, category_id, harga_beli, harga_jual, stok, inventory_account_id, cogs_account_id, revenue_account_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('issiddiiii', $user_id, $nama_barang, $sku, $category_id, $harga_beli, $harga_jual, $stok, $inventory_account_id, $cogs_account_id, $revenue_account_id);
            }
            $stmt->execute();
            $message = ($action === 'update') ? 'Data barang berhasil diperbarui.' : 'Data barang berhasil ditambahkan.';
            $stmt->close();
            echo json_encode(['status' => 'success', 'message' => $message]);
        
        } elseif ($action === 'get_single') {
            $id = (int)($_POST['id'] ?? 0); // This is correct for get_single
            $stmt = $conn->prepare("SELECT * FROM items WHERE id = ? AND user_id = ?");
            $stmt->bind_param('ii', $id, $user_id);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$item) throw new Exception("Barang tidak ditemukan.");
            echo json_encode(['status' => 'success', 'data' => $item]);
        
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0); // This is correct for delete
            // TODO: Cek keterkaitan dengan transaksi pembelian/penjualan sebelum hapus
            $stmt = $conn->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
            $stmt->bind_param('ii', $id, $user_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['status' => 'success', 'message' => 'Barang berhasil dihapus.']);
        
        } elseif ($action === 'adjust_stock') {
            // This part uses JSON, so we need to read from the raw input
            $data = json_decode(file_get_contents('php://input'), true);

            // Validasi input
            $itemId = $data['item_id'] ?? 0;
            $stokFisik = (int)($data['stok_fisik'] ?? 0);
            $tanggal = $data['tanggal'] ?? '';
            $adjAccountId = $data['adj_account_id'] ?? 0;
            $keterangan = $data['keterangan'] ?? '';
            $userId = $_SESSION['user_id'] ?? null;

            if (empty($itemId) || empty($tanggal) || empty($adjAccountId) || empty($keterangan) || $stokFisik < 0) {
                throw new Exception("Semua field wajib diisi dan stok fisik tidak boleh negatif.");
            }

            $conn->begin_transaction();

            try {
                // 1. Ambil data barang saat ini
                $stmt = $conn->prepare("SELECT stok, harga_beli, inventory_account_id FROM items WHERE id = ? FOR UPDATE");
                $stmt->bind_param("i", $itemId);
                $stmt->execute();
                $item = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$item) throw new Exception("Barang tidak ditemukan.");

                $stokSebelum = (int)$item['stok'];
                $hargaBeli = (float)$item['harga_beli'];
                $inventoryAccountId = $item['inventory_account_id'] ?: get_setting('default_inventory_account_id');

                if (empty($inventoryAccountId)) {
                    throw new Exception("Akun persediaan untuk barang ini belum diatur. Silakan atur di halaman edit barang atau di Pengaturan > Akuntansi.");
                }

                // 2. Hitung selisih
                $selisihKuantitas = $stokFisik - $stokSebelum;
                $selisihNilai = $selisihKuantitas * $hargaBeli;

                if ($selisihKuantitas == 0) {
                    $conn->rollback(); // Batalkan transaksi jika tidak ada perubahan
                    throw new Exception("Tidak ada perubahan stok. Stok fisik sama dengan stok tercatat.");
                }

                // 3. Buat Jurnal Penyesuaian
                $keteranganJurnal = "Penyesuaian Stok: " . $keterangan;
                // Argumen ke-3 adalah user_id pemilik data (selalu 1), argumen ke-4 adalah user yang login
                $journalId = create_journal_entry($tanggal, $keteranganJurnal, 1, $userId);
                $nomorReferensi = "ADJ-" . $journalId;

                $zero_val = 0.0;

                if ($selisihNilai < 0) { // Pengurangan Stok
                    add_journal_line($journalId, $adjAccountId, abs($selisihNilai), $zero_val);
                    update_general_ledger($conn, $user_id, $adjAccountId, $tanggal, abs($selisihNilai), $zero_val, $keteranganJurnal, $nomorReferensi, $journalId);

                    add_journal_line($journalId, $inventoryAccountId, $zero_val, abs($selisihNilai));
                    update_general_ledger($conn, $user_id, $inventoryAccountId, $tanggal, $zero_val, abs($selisihNilai), $keteranganJurnal, $nomorReferensi, $journalId);
                } else { // Penambahan Stok
                    add_journal_line($journalId, $inventoryAccountId, $selisihNilai, $zero_val);
                    update_general_ledger($conn, $user_id, $inventoryAccountId, $tanggal, $selisihNilai, $zero_val, $keteranganJurnal, $nomorReferensi, $journalId);

                    add_journal_line($journalId, $adjAccountId, $zero_val, $selisihNilai);
                    update_general_ledger($conn, $user_id, $adjAccountId, $tanggal, $zero_val, $selisihNilai, $keteranganJurnal, $nomorReferensi, $journalId);
                }

                // 4. Update stok di tabel items
                $stmt = $conn->prepare("UPDATE items SET stok = ? WHERE id = ?");
                $stmt->bind_param("ii", $stokFisik, $itemId);
                $stmt->execute();

                // 5. Catat ke tabel history penyesuaian
                $stmt = $conn->prepare("INSERT INTO stock_adjustments (item_id, user_id, journal_id, tanggal, stok_sebelum, stok_setelah, selisih_kuantitas, selisih_nilai, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisiisds", $itemId, $userId, $journalId, $tanggal, $stokSebelum, $stokFisik, $selisihKuantitas, $selisihNilai, $keterangan);
                $stmt->execute();

                // 6. Catat ke kartu stok
                $stmt_ks = $conn->prepare("INSERT INTO kartu_stok (tanggal, item_id, debit, kredit, keterangan, ref_id, source, user_id) VALUES (?, ?, ?, ?, ?, ?, 'adjustment', ?)");
                $debit = $selisihKuantitas > 0 ? $selisihKuantitas : 0;
                $kredit = $selisihKuantitas < 0 ? abs($selisihKuantitas) : 0;
                $stmt_ks->bind_param('siiisii', $tanggal, $itemId, $debit, $kredit, $keteranganJurnal, $journalId, $userId);
                $stmt_ks->execute();
                $stmt_ks->close();

                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Penyesuaian stok berhasil disimpan.']);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e; // Re-throw untuk ditangkap oleh handler utama
            }
        } elseif ($action === 'batch_adjust_stock') {
            // This part uses JSON, so we need to read from the raw input
            $data = json_decode(file_get_contents('php://input'), true);

            // Validasi input umum
            $tanggal = $data['tanggal'] ?? '';
            $adjAccountId = $data['adj_account_id'] ?? 0;
            $keterangan = $data['keterangan'] ?? '';
            $itemsToAdjust = $data['items'] ?? [];
            $userId = $_SESSION['user_id'] ?? null;

            if (empty($tanggal) || empty($adjAccountId) || empty($keterangan) || !is_array($itemsToAdjust) || empty($itemsToAdjust)) {
                throw new Exception("Data tidak lengkap. Pastikan tanggal, akun penyesuaian, keterangan, dan daftar barang telah diisi.");
            }

            $conn->begin_transaction();

            try {
                // 1. Buat satu Jurnal Induk untuk seluruh proses stok opname
                $keteranganJurnal = "Stok Opname Batch: " . $keterangan;
                // Argumen ke-3 adalah user_id pemilik data (selalu 1), argumen ke-4 adalah user yang login
                $journalId = create_journal_entry($tanggal, $keteranganJurnal, 1, $userId);
                $nomorReferensi = "SO-" . $journalId;

                $itemStmt = $conn->prepare("SELECT stok, harga_beli, inventory_account_id FROM items WHERE id = ? AND user_id = ? FOR UPDATE");
                $updateStmt = $conn->prepare("UPDATE items SET stok = ? WHERE id = ?");
                $historyStmt = $conn->prepare("INSERT INTO stock_adjustments (item_id, user_id, journal_id, tanggal, stok_sebelum, stok_setelah, selisih_kuantitas, selisih_nilai, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $kartuStokStmt = $conn->prepare("INSERT INTO kartu_stok (tanggal, item_id, debit, kredit, keterangan, ref_id, source, user_id) VALUES (?, ?, ?, ?, ?, ?, 'adjustment', ?)");

                // Variabel untuk menampung rekapitulasi total untuk general ledger
                $ledgerTotals = [];

                foreach ($itemsToAdjust as $itemData) {
                    $itemId = (int)($itemData['item_id'] ?? 0);
                    $stokFisik = (int)($itemData['stok_fisik'] ?? 0);

                    if ($itemId <= 0 || $stokFisik < 0) continue; // Lewati data tidak valid

                    // 2. Ambil data barang saat ini
                    $itemStmt->bind_param("ii", $itemId, $userId);
                    $itemStmt->execute();
                    $item = $itemStmt->get_result()->fetch_assoc();

                    if (!$item) continue; // Lewati jika barang tidak ditemukan atau bukan milik user

                    $stokSebelum = (int)$item['stok'];
                    $selisihKuantitas = $stokFisik - $stokSebelum;

                    if ($selisihKuantitas == 0) continue; // Tidak ada perubahan, lanjut ke item berikutnya

                    $hargaBeli = (float)$item['harga_beli'];
                    $selisihNilai = $selisihKuantitas * $hargaBeli;
                    $inventoryAccountId = $item['inventory_account_id'] ?: get_setting('default_inventory_account_id');

                    if (empty($inventoryAccountId)) throw new Exception("Akun persediaan untuk salah satu barang belum diatur.");

                    $zero_val = 0.0;

                    // 3. Tambahkan baris ke Jurnal Induk
                    if ($selisihNilai < 0) { // Pengurangan Stok
                        // Detail tetap dicatat di journal_lines
                        add_journal_line($journalId, $adjAccountId, abs($selisihNilai), $zero_val); // Debit Akun Beban/Penyeimbang
                        add_journal_line($journalId, $inventoryAccountId, $zero_val, abs($selisihNilai)); // Kredit Akun Persediaan

                        // Akumulasi nilai untuk rekap general_ledger
                        $ledgerTotals[$adjAccountId]['debit'] = ($ledgerTotals[$adjAccountId]['debit'] ?? 0) + abs($selisihNilai);
                        $ledgerTotals[$inventoryAccountId]['credit'] = ($ledgerTotals[$inventoryAccountId]['credit'] ?? 0) + abs($selisihNilai);

                    } else { // Penambahan Stok
                        // Detail tetap dicatat di journal_lines
                        add_journal_line($journalId, $inventoryAccountId, $selisihNilai, $zero_val); // Debit Akun Persediaan
                        add_journal_line($journalId, $adjAccountId, $zero_val, $selisihNilai); // Kredit Akun Modal/Penyeimbang

                        // Akumulasi nilai untuk rekap general_ledger
                        $ledgerTotals[$inventoryAccountId]['debit'] = ($ledgerTotals[$inventoryAccountId]['debit'] ?? 0) + $selisihNilai;
                        $ledgerTotals[$adjAccountId]['credit'] = ($ledgerTotals[$adjAccountId]['credit'] ?? 0) + $selisihNilai;
                    }

                    // 4. Update stok & catat history
                    $updateStmt->bind_param("ii", $stokFisik, $itemId);
                    $updateStmt->execute();
                    $historyStmt->bind_param("iiisiisds", $itemId, $userId, $journalId, $tanggal, $stokSebelum, $stokFisik, $selisihKuantitas, $selisihNilai, $keterangan);
                    $historyStmt->execute();

                    // 5. Catat ke kartu stok
                    $debit = $selisihKuantitas > 0 ? $selisihKuantitas : 0;
                    $kredit = $selisihKuantitas < 0 ? abs($selisihKuantitas) : 0;
                    $keterangan_ks = "Stok Opname Batch: " . $keterangan;
                    $kartuStokStmt->bind_param('siiisii', $tanggal, $itemId, $debit, $kredit, $keterangan_ks, $journalId, $userId);
                    $kartuStokStmt->execute();
                }

                // 5. Setelah loop selesai, insert rekapitulasi total ke general_ledger
                foreach ($ledgerTotals as $accountId => $totals) {
                    $debit = $totals['debit'] ?? 0;
                    $credit = $totals['credit'] ?? 0;
                    // Pastikan debit dan kredit tidak terisi bersamaan dalam satu baris GL.
                    // Jika sebuah akun memiliki total debit dan kredit (jarang terjadi dalam skenario ini, tapi untuk keamanan),
                    // buat dua baris GL terpisah. Namun, untuk stok opname, satu akun hanya akan didebit atau dikredit.
                    if ($debit > 0 || $credit > 0) {
                        update_general_ledger($conn, $user_id, $accountId, $tanggal, $debit, $credit, $keteranganJurnal, $nomorReferensi, $journalId);
                    }
                }

                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Stok opname batch berhasil disimpan.']);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e; // Re-throw untuk ditangkap oleh handler utama
            }
        } elseif ($action === 'import') {
            // Untuk import, kita tidak menggunakan JSON body, jadi tidak perlu $data
            // Validasi file upload
            if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Gagal mengunggah file atau tidak ada file yang dipilih.");
            }

            $file_path = $_FILES['excel_file']['tmp_name'];
            
            // Validasi tipe file
            $file_mime_type = mime_content_type($file_path);
            if ($file_mime_type !== 'text/plain' && $file_mime_type !== 'text/csv') {
                throw new Exception("Format file tidak valid. Harap unggah file .csv");
            }

            $rows = [];
            if (($handle = fopen($file_path, "r")) !== FALSE) {
                // Lewati baris header
                fgetcsv($handle); 
                
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $rows[] = $data;
                }
                fclose($handle);
            }

            if (empty($rows)) {
                throw new Exception("File Excel kosong atau tidak memiliki data.");
            }

            $adjAccountId = (int)($_POST['adj_account_id'] ?? 0);
            if (empty($adjAccountId)) {
                throw new Exception("Akun Penyeimbang Saldo Awal wajib dipilih.");
            }
            // Tambahan: Validasi bahwa akun persediaan default sudah diatur
            $defaultInventoryAccountId = get_setting('default_inventory_account_id', null, $conn);
            if (empty($defaultInventoryAccountId)) {
                throw new Exception("Akun Persediaan Default belum diatur. Silakan atur di Pengaturan > Akuntansi sebelum melakukan impor.");
            } 

            $tanggal_import = date('Y-m-d'); // Gunakan tanggal hari ini untuk penyesuaian

            $conn->begin_transaction();
            $stmt_select = $conn->prepare("SELECT id FROM items WHERE id = ? AND user_id = ?");
            $stmt_update = $conn->prepare("UPDATE items SET harga_beli=?, harga_jual=?, stok=? WHERE id=?");
            $stmt_insert = $conn->prepare("INSERT INTO items (user_id, nama_barang, sku, category_id, harga_beli, harga_jual, stok) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_select_cat = $conn->prepare("SELECT id FROM item_categories WHERE nama_kategori = ? AND user_id = ?");
            $stmt_insert_cat = $conn->prepare("INSERT INTO item_categories (user_id, nama_kategori) VALUES (?, ?)");

            // 1. Buat satu Jurnal Induk untuk seluruh proses impor
            $keterangan_impor = "Penyesuaian Saldo Awal dari Impor CSV";
            // Argumen ke-3 adalah user_id pemilik data (selalu 1), argumen ke-4 adalah user yang login
            $journalId = create_journal_entry($tanggal_import, $keterangan_impor, 1, $logged_in_user_id);
            $nomorReferensi = "IMP-" . $journalId;

            $processed = 0;
            $errors = [];

            // Siapkan statement untuk kartu stok
            $stmt_ks = $conn->prepare("INSERT INTO kartu_stok (tanggal, item_id, debit, kredit, keterangan, ref_id, source, user_id) VALUES (?, ?, ?, ?, ?, ?, 'import', ?)");

            $ledgerTotals = []; // Array untuk rekapitulasi ke General Ledger

            foreach ($rows as $index => $row) {

                $nama_barang = trim($row[0] ?? ''); 
                $item_id_csv = (int)trim($row[1] ?? 0); // Ambil ID Barang dari kolom B
                $kategori_nama = trim($row[1] ?? ''); // Ambil Nama Kategori dari kolom C
                // Kolom kategori ($row[1]) diabaikan sesuai instruksi
                $stok_raw = $row[9] ?? '0'; // stok setelah stok opname
                $harga_beli_raw = $row[4] ?? '0'; // Sesuai instruksi di modal: D: beli
                $harga_jual_raw = $row[5] ?? '0'; // Sesuai instruksi di modal: E: jual

                if (empty($nama_barang)) continue; // Lewati baris kosong

                // Fungsi untuk membersihkan dan mengkonversi nilai numerik dari format CSV
                $stok = (int)preg_replace('/[^\d-]/', '', $stok_raw);
                $harga_beli = (float)str_replace(',', '.', preg_replace('/[^\d,-]/', '', $harga_beli_raw));
                $harga_jual = (float)str_replace(',', '.', preg_replace('/[^\d,-]/', '', $harga_jual_raw));

                if ($harga_beli < 0 || $harga_jual < 0 || $stok < 0) {
                    // Baris ini akan dilewati jika nilai numerik tidak valid setelah pembersihan
                    // Anda bisa menambahkan logging atau pesan error yang lebih spesifik jika diperlukan
                    continue;
                }

                // --- Logika Kategori ---
                $category_id = null;
                if (!empty($kategori_nama)) {
                    // Cek apakah kategori sudah ada
                    $stmt_select_cat->bind_param('si', $kategori_nama, $user_id);
                    $stmt_select_cat->execute();
                    $existing_cat = $stmt_select_cat->get_result()->fetch_assoc();

                    if ($existing_cat) {
                        $category_id = $existing_cat['id'];
                    } else {
                        // Jika belum ada, buat kategori baru
                        $stmt_insert_cat->bind_param('is', $user_id, $kategori_nama);
                        $stmt_insert_cat->execute();
                        $category_id = $conn->insert_id;
                    }
                }
                // --- Akhir Logika Kategori ---

                $item_id = null;
                $stok_sebelum = 0;

                if ($item_id_csv > 0) { // Jika ID ada di CSV, ini adalah proses UPDATE
                    $stmt_select->bind_param('ii', $item_id_csv, $user_id);
                    $stmt_select->execute();
                    $existing_item = $stmt_select->get_result()->fetch_assoc();

                    if (!$existing_item) {
                        // Jika ID dari CSV tidak ditemukan di DB, lewati dan catat error
                        $errors[] = "Baris " . ($index + 2) . ": Barang dengan ID '{$item_id_csv}' tidak ditemukan. Baris ini dilewati.";
                        continue;
                    }

                    $item_id = $item_id_csv;

                    // Saat update, kita tidak langsung set stok, tapi akan dihitung via adjustment
                    $stmt_get_stok = $conn->prepare("SELECT stok, harga_beli FROM items WHERE id = ?");
                    $stmt_get_stok->bind_param('i', $item_id);
                    $stmt_get_stok->execute();
                    $stok_sebelum = (int)$stmt_get_stok->get_result()->fetch_assoc()['stok'];
                    $stmt_get_stok->close();

                } else { // Insert
                    // Insert dengan stok 0, karena akan di-adjust setelahnya
                    $initial_stock = 0;
                    $stmt_insert->bind_param('issiddi', $user_id, $nama_barang, $sku, $category_id, $harga_beli, $harga_jual, $initial_stock);
                    $stmt_insert->execute();
                    $item_id = $conn->insert_id;
                    $stok_sebelum = 0;
                }

                // Lakukan proses penyesuaian stok untuk item ini
                $selisih_kuantitas = $stok - $stok_sebelum;
                $selisih_nilai = $selisih_kuantitas * $harga_beli;

                if ($selisih_kuantitas != 0) {
                    // 2. Tambahkan baris ke Jurnal Induk (tanpa membuat jurnal baru setiap kali)
                    $inventoryAccountId = $defaultInventoryAccountId;
                    $zero_val = 0.0;

                    if ($selisih_nilai > 0) { // Penambahan stok
                        //add_journal_line($journalId, $inventoryAccountId, $selisih_nilai, $zero_val);
                        //add_journal_line($journalId, $adjAccountId, $zero_val, $selisih_nilai);
                        $ledgerTotals[$inventoryAccountId]['debit'] = ($ledgerTotals[$inventoryAccountId]['debit'] ?? 0) + $selisih_nilai;
                        $ledgerTotals[$adjAccountId]['credit'] = ($ledgerTotals[$adjAccountId]['credit'] ?? 0) + $selisih_nilai;
                    } else { // Pengurangan stok
                        //add_journal_line($journalId, $adjAccountId, abs($selisih_nilai), $zero_val);
                        //add_journal_line($journalId, $inventoryAccountId, $zero_val, abs($selisih_nilai));
                        $ledgerTotals[$adjAccountId]['debit'] = ($ledgerTotals[$adjAccountId]['debit'] ?? 0) + abs($selisih_nilai);
                        $ledgerTotals[$inventoryAccountId]['credit'] = ($ledgerTotals[$inventoryAccountId]['credit'] ?? 0) + abs($selisih_nilai);
                    }

                    // Update stok di tabel items
                    $stmt_update->bind_param('ddii', $harga_beli, $harga_jual, $stok, $item_id);
                    $stmt_update->execute();

                    // Catat ke kartu stok sebagai saldo awal/penyesuaian
                    $debit = $selisih_kuantitas > 0 ? $selisih_kuantitas : 0;
                    $kredit = $selisih_kuantitas < 0 ? abs($selisih_kuantitas) : 0;
                    $stmt_ks->bind_param('siiisii', $tanggal_import, $item_id, $debit, $kredit, $keterangan_impor, $journalId, $logged_in_user_id);
                    $stmt_ks->execute();
                } 

                $processed++;
            }

            $conn->commit();

            // Jika ada error saat validasi ID, tampilkan di pesan sukses
            $error_message = !empty($errors) ? " Peringatan: " . implode(" ", $errors) : "";
            $final_message = "Berhasil memproses {$processed} baris data barang." . $error_message;


            // 3. Setelah loop, insert rekapitulasi total ke general_ledger
            foreach ($ledgerTotals as $accountId => $totals) {
                $debit = $totals['debit'] ?? 0;
                $credit = $totals['credit'] ?? 0;
                if ($debit > 0 || $credit > 0) {
                    //update_general_ledger($conn, $user_id, $accountId, $tanggal_import, $debit, $credit, $keterangan_impor, $nomorReferensi, $journalId);
                }
            }
            echo json_encode(['status' => 'success', 'message' => $final_message]);
        }
    } else {
        // Jika method tidak dikenali
        throw new Exception("Metode request tidak valid.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>