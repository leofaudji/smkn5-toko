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
$user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'list') {
            $limit = (int)($_GET['limit'] ?? 15);
            $page = (int)($_GET['page'] ?? 1);
            $offset = ($page - 1) * $limit;
            $search = $_GET['search'] ?? '';
            $stok_filter = $_GET['stok_filter'] ?? '';

            $where_clauses = ['user_id = ?'];
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

            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

            $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM items $where_sql");
            $bind_params_total = [&$params[0]];
            for ($i = 1; $i < count($params); $i++) { $bind_params_total[] = &$params[$i]; }
            call_user_func_array([$total_stmt, 'bind_param'], $bind_params_total);
            $total_stmt->execute();
            $total_records = $total_stmt->get_result()->fetch_assoc()['total'];
            $total_stmt->close();

            $query = "SELECT * FROM items $where_sql ORDER BY nama_barang ASC LIMIT ? OFFSET ?";
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
            $saldo_awal = 0;
            $stmt = $conn->prepare("
                SELECT 
                    (SELECT COALESCE(SUM(pd.quantity), 0) FROM pembelian_details pd JOIN pembelian p ON pd.pembelian_id = p.id WHERE pd.item_id = ? AND p.tanggal_pembelian < ?) +
                    (SELECT COALESCE(SUM(selisih_kuantitas), 0) FROM stock_adjustments WHERE item_id = ? AND tanggal < ? AND selisih_kuantitas > 0)
                    AS total_masuk_sebelum,
                    (SELECT COALESCE(SUM(ABS(selisih_kuantitas)), 0) FROM stock_adjustments WHERE item_id = ? AND tanggal < ? AND selisih_kuantitas < 0)
                    AS total_keluar_sebelum
            ");
            $stmt->bind_param("isisis", $item_id, $start_date, $item_id, $start_date, $item_id, $start_date);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $saldo_awal = $result['total_masuk_sebelum'] - $result['total_keluar_sebelum'];
            $stmt->close();

            // 3. Get all transactions within the date range
            $stmt = $conn->prepare("
                (SELECT p.tanggal_pembelian as tanggal, CONCAT('Pembelian #', p.id) as keterangan, pd.quantity as masuk, 0 as keluar FROM pembelian_details pd JOIN pembelian p ON pd.pembelian_id = p.id WHERE pd.item_id = ? AND p.tanggal_pembelian BETWEEN ? AND ?)
                UNION ALL
                (SELECT tanggal, keterangan, IF(selisih_kuantitas > 0, selisih_kuantitas, 0) as masuk, IF(selisih_kuantitas < 0, ABS(selisih_kuantitas), 0) as keluar FROM stock_adjustments WHERE item_id = ? AND tanggal BETWEEN ? AND ?)
                ORDER BY tanggal ASC, keterangan ASC
            ");
            $stmt->bind_param("isssis", $item_id, $start_date, $end_date, $item_id, $start_date, $end_date);
            $stmt->execute();
            $transactions_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // 4. Process transactions and calculate running balance
            $transactions = [];
            $saldo_berjalan = $saldo_awal;
            $total_masuk = 0;
            $total_keluar = 0;

            foreach ($transactions_raw as $trx) {
                $saldo_berjalan += $trx['masuk'] - $trx['keluar'];
                $total_masuk += $trx['masuk'];
                $total_keluar += $trx['keluar'];
                $transactions[] = [
                    'tanggal' => $trx['tanggal'],
                    'keterangan' => $trx['keterangan'],
                    'masuk' => (int)$trx['masuk'],
                    'keluar' => (int)$trx['keluar'],
                    'saldo' => $saldo_berjalan
                ];
            }

            $saldo_akhir = $saldo_awal + $total_masuk - $total_keluar;

            $response_data = [
                'item_info' => $item_info,
                'summary' => [
                    'saldo_awal' => $saldo_awal,
                    'total_masuk' => $total_masuk,
                    'total_keluar' => $total_keluar,
                    'saldo_akhir' => $saldo_akhir
                ],
                'transactions' => $transactions
            ];

            echo json_encode(['status' => 'success', 'data' => $response_data]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get action from JSON body or form-data
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? ($_POST['action'] ?? '');

        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $nama_barang = trim($_POST['nama_barang']);
            $sku = trim($_POST['sku']) ?: null;
            $harga_beli = (float)$_POST['harga_beli'];
            $harga_jual = (float)$_POST['harga_jual'];
            $stok = (int)$_POST['stok'];
            $inventory_account_id = !empty($_POST['inventory_account_id']) ? (int)$_POST['inventory_account_id'] : null;
            $cogs_account_id = !empty($_POST['cogs_account_id']) ? (int)$_POST['cogs_account_id'] : null;
            $revenue_account_id = !empty($_POST['revenue_account_id']) ? (int)$_POST['revenue_account_id'] : null;

            if (empty($nama_barang) || $harga_beli < 0 || $harga_jual < 0 || $stok < 0) {
                throw new Exception("Data barang tidak lengkap atau tidak valid.");
            }

            if ($id > 0) { // Update
                $stmt = $conn->prepare("UPDATE items SET nama_barang=?, sku=?, harga_beli=?, harga_jual=?, stok=?, inventory_account_id=?, cogs_account_id=?, revenue_account_id=? WHERE id=? AND user_id=?");
                $stmt->bind_param('ssddiiiiii', $nama_barang, $sku, $harga_beli, $harga_jual, $stok, $inventory_account_id, $cogs_account_id, $revenue_account_id, $id, $user_id);
            } else { // Add
                $stmt = $conn->prepare("INSERT INTO items (user_id, nama_barang, sku, harga_beli, harga_jual, stok, inventory_account_id, cogs_account_id, revenue_account_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('issddiiii', $user_id, $nama_barang, $sku, $harga_beli, $harga_jual, $stok, $inventory_account_id, $cogs_account_id, $revenue_account_id);
            }
            $stmt->execute();
            $stmt->close();
            echo json_encode(['status' => 'success', 'message' => 'Data barang berhasil disimpan.']);
        
        } elseif ($action === 'get_single') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("SELECT * FROM items WHERE id = ? AND user_id = ?");
            $stmt->bind_param('ii', $id, $user_id);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$item) throw new Exception("Barang tidak ditemukan.");
            echo json_encode(['status' => 'success', 'data' => $item]);
        
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            // TODO: Cek keterkaitan dengan transaksi pembelian/penjualan sebelum hapus
            $stmt = $conn->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
            $stmt->bind_param('ii', $id, $user_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['status' => 'success', 'message' => 'Barang berhasil dihapus.']);
        
        } elseif ($action === 'adjust_stock') {
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
                $inventoryAccountId = $item['inventory_account_id'] ?: get_setting('default_inventory_account');

                if (empty($inventoryAccountId)) {
                    throw new Exception("Akun persediaan untuk barang ini belum diatur. Silakan atur di halaman edit barang atau di Pengaturan > Akuntansi.");
                }

                // 2. Hitung selisih
                $selisihKuantitas = $stokFisik - $stokSebelum;
                $selisihNilai = $selisihKuantitas * $hargaBeli;

                if ($selisihKuantitas == 0) {
                    throw new Exception("Tidak ada perubahan stok. Stok fisik sama dengan stok tercatat.");
                }

                // 3. Buat Jurnal Penyesuaian
                $keteranganJurnal = "Penyesuaian Stok: " . $keterangan;
                $journalId = create_journal_entry($tanggal, $keteranganJurnal, $userId);
                $nomorReferensi = "ADJ-" . $journalId;

                $zero_val = 0.0;

                if ($selisihNilai < 0) { // Pengurangan Stok
                    add_journal_line($journalId, $adjAccountId, abs($selisihNilai), $zero_val);
                    update_general_ledger($conn, $userId, $adjAccountId, $tanggal, abs($selisihNilai), $zero_val, $keteranganJurnal, $nomorReferensi, $journalId);

                    add_journal_line($journalId, $inventoryAccountId, $zero_val, abs($selisihNilai));
                    update_general_ledger($conn, $userId, $inventoryAccountId, $tanggal, $zero_val, abs($selisihNilai), $keteranganJurnal, $nomorReferensi, $journalId);
                } else { // Penambahan Stok
                    add_journal_line($journalId, $inventoryAccountId, $selisihNilai, $zero_val);
                    update_general_ledger($conn, $userId, $inventoryAccountId, $tanggal, $selisihNilai, $zero_val, $keteranganJurnal, $nomorReferensi, $journalId);

                    add_journal_line($journalId, $adjAccountId, $zero_val, $selisihNilai);
                    update_general_ledger($conn, $userId, $adjAccountId, $tanggal, $zero_val, $selisihNilai, $keteranganJurnal, $nomorReferensi, $journalId);
                }

                // 4. Update stok di tabel items
                $stmt = $conn->prepare("UPDATE items SET stok = ? WHERE id = ?");
                $stmt->bind_param("ii", $stokFisik, $itemId);
                $stmt->execute();

                // 5. Catat ke tabel history penyesuaian
                $stmt = $conn->prepare("INSERT INTO stock_adjustments (item_id, user_id, journal_id, tanggal, stok_sebelum, stok_setelah, selisih_kuantitas, selisih_nilai, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisiisds", $itemId, $userId, $journalId, $tanggal, $stokSebelum, $stokFisik, $selisihKuantitas, $selisihNilai, $keterangan);
                $stmt->execute();

                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Penyesuaian stok berhasil disimpan.']);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e; // Re-throw untuk ditangkap oleh handler utama
            }
        } elseif ($action === 'batch_adjust_stock') {
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
                $journalId = create_journal_entry($tanggal, $keteranganJurnal, $userId);
                $nomorReferensi = "SO-" . $journalId;

                $itemStmt = $conn->prepare("SELECT stok, harga_beli, inventory_account_id FROM items WHERE id = ? AND user_id = ? FOR UPDATE");
                $updateStmt = $conn->prepare("UPDATE items SET stok = ? WHERE id = ?");
                $historyStmt = $conn->prepare("INSERT INTO stock_adjustments (item_id, user_id, journal_id, tanggal, stok_sebelum, stok_setelah, selisih_kuantitas, selisih_nilai, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

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
                    $inventoryAccountId = $item['inventory_account_id'] ?: get_setting('default_inventory_account');

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
                }

                // 5. Setelah loop selesai, insert rekapitulasi total ke general_ledger
                foreach ($ledgerTotals as $accountId => $totals) {
                    $totalDebit = $totals['debit'] ?? 0;
                    $totalCredit = $totals['credit'] ?? 0;
                    if ($totalDebit > 0 || $totalCredit > 0) {
                        update_general_ledger($conn, $userId, $accountId, $tanggal, $totalDebit, $totalCredit, $keteranganJurnal, $nomorReferensi, $journalId);
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
            $tanggal_import = date('Y-m-d'); // Gunakan tanggal hari ini untuk penyesuaian

            $conn->begin_transaction();
            $stmt_select = $conn->prepare("SELECT id FROM items WHERE nama_barang = ? AND user_id = ?");
            $stmt_update = $conn->prepare("UPDATE items SET harga_beli=?, harga_jual=?, stok=? WHERE id=?");
            $stmt_insert = $conn->prepare("INSERT INTO items (user_id, nama_barang, harga_beli, harga_jual, stok) VALUES (?, ?, ?, ?, ?)");

            $processed = 0;
            $errors = [];

            // Siapkan statement untuk penyesuaian stok
            $stmt_adj = $conn->prepare("INSERT INTO stock_adjustments (item_id, user_id, journal_id, tanggal, stok_sebelum, stok_setelah, selisih_kuantitas, selisih_nilai, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            //print_r($rows);
            foreach ($rows as $index => $row) {

                $nama_barang = trim($row[0] ?? '');
                // Kolom kategori ($row[1]) diabaikan sesuai instruksi
                $stok_raw = $row[2] ?? '0';
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

                $stmt_select->bind_param('si', $nama_barang, $user_id);
                $stmt_select->execute();
                $existing_item = $stmt_select->get_result()->fetch_assoc();

                $item_id = null;
                $stok_sebelum = 0;

                if ($existing_item) { // Update
                    $item_id = $existing_item['id'];
                    // Saat update, kita tidak langsung set stok, tapi akan dihitung via adjustment
                    $stmt_get_stok = $conn->prepare("SELECT stok FROM items WHERE id = ?");
                    $stmt_get_stok->bind_param('i', $item_id);
                    $stmt_get_stok->execute();
                    $stok_sebelum = (int)$stmt_get_stok->get_result()->fetch_assoc()['stok'];
                    $stmt_get_stok->close();

                } else { // Insert
                    // Insert dengan stok 0, karena akan di-adjust setelahnya
                    $initial_stock = 0;
                    $stmt_insert->bind_param('isddi', $user_id, $nama_barang, $harga_beli, $harga_jual, $initial_stock);
                    $stmt_insert->execute();
                    $item_id = $conn->insert_id;
                    $stok_sebelum = 0;
                }

                // Lakukan proses penyesuaian stok untuk item ini
                $selisih_kuantitas = $stok - $stok_sebelum;
                $selisih_nilai = $selisih_kuantitas * $harga_beli;
                $keterangan_adj = "Saldo Awal dari Impor CSV";

                if ($selisih_kuantitas != 0) {
                    // Buat jurnal untuk penyesuaian ini
                    $journalId = create_journal_entry($tanggal_import, $keterangan_adj . " - " . $nama_barang, $user_id);
                    $nomorReferensi = "IMP-" . $journalId;
                    
                    // Logika jurnal sama seperti stock adjustment
                    $inventoryAccountId = get_setting('default_inventory_account'); // Asumsi ada akun default
                    $zero_val = 0.0;
                    //add_journal_line($journalId, $inventoryAccountId, $selisih_nilai, $zero_val);
                    //update_general_ledger($conn, $user_id, $inventoryAccountId, $tanggal_import, $selisih_nilai, $zero_val, $keterangan_adj, $nomorReferensi, $journalId);
                    //add_journal_line($journalId, $adjAccountId, $zero_val, $selisih_nilai);
                    //update_general_ledger($conn, $user_id, $adjAccountId, $tanggal_import, $zero_val, $selisih_nilai, $keterangan_adj, $nomorReferensi, $journalId);

                    // Update stok di tabel items
                    $stmt_update->bind_param('ddii', $harga_beli, $harga_jual, $stok, $item_id);
                    $stmt_update->execute();

                    // Catat ke history adjustment
                    $stmt_adj->bind_param("iiisiisds", $item_id, $user_id, $journalId, $tanggal_import, $stok_sebelum, $stok, $selisih_kuantitas, $selisih_nilai, $keterangan_adj);
                    $stmt_adj->execute();
                }

                $processed++;
            }

            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => "Berhasil memproses {$processed} baris data barang."]);
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