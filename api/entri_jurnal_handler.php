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
$logged_in_user_id = $_SESSION['user_id']; // Untuk logging

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'get_single') {
            $id = (int) ($_GET['id'] ?? 0);
            $ref_param = trim($_GET['ref'] ?? '');
            if ($id <= 0 && empty($ref_param))
                throw new Exception("ID atau Nomor Referensi Jurnal tidak valid.");

            // 1. Ambil info transaksi dari general_ledger (mendukung JRN- dan SO-)
            $gl_info = null;
            if ($id > 0) {
                $stmt_gl_info = $conn->prepare("
                    SELECT gl.ref_id as id, gl.tanggal, gl.keterangan, gl.nomor_referensi, gl.ref_type
                    FROM general_ledger gl
                    WHERE gl.ref_id = ? AND gl.user_id = ? AND (gl.nomor_referensi LIKE 'JRN-%' OR gl.nomor_referensi LIKE 'SO-%')
                    LIMIT 1
                ");
                $stmt_gl_info->bind_param('ii', $id, $user_id);
                $stmt_gl_info->execute();
                $gl_info = stmt_fetch_assoc($stmt_gl_info);
                $stmt_gl_info->close();
            }

            if (!$gl_info && !empty($ref_param)) {
                $stmt_ref = $conn->prepare("
                    SELECT gl.ref_id as id, gl.tanggal, gl.keterangan, gl.nomor_referensi, gl.ref_type
                    FROM general_ledger gl
                    WHERE gl.nomor_referensi = ? AND gl.user_id = ? AND (gl.nomor_referensi LIKE 'JRN-%' OR gl.nomor_referensi LIKE 'SO-%')
                    LIMIT 1
                ");
                $stmt_ref->bind_param('si', $ref_param, $user_id);
                $stmt_ref->execute();
                $gl_info = stmt_fetch_assoc($stmt_ref);
                $stmt_ref->close();
            }

            if (!$gl_info) {
                throw new Exception("Hanya data yang diinput dari menu Entri Jurnal (JRN) atau Stok Opname (SO) yang dapat diedit di sini.");
            }

            $actual_id = (int) $gl_info['id'];
            $nomor_ref = $gl_info['nomor_referensi'];

            $header = [
                'id' => $actual_id,
                'tanggal' => $gl_info['tanggal'],
                'keterangan' => $gl_info['keterangan'],
                'nomor_referensi' => $nomor_ref,
                'ref_type' => $gl_info['ref_type']
            ];

            // 2. Ambil detail baris akun: coba dari jurnal_details terlebih dahulu
            $details = [];
            if ($actual_id > 0) {
                $stmt_details = $conn->prepare("
                    SELECT jd.account_id, jd.debit, jd.kredit, a.kode_akun, a.nama_akun FROM jurnal_details jd
                    JOIN accounts a ON jd.account_id = a.id
                    WHERE jd.jurnal_entry_id = ?
                    ORDER BY jd.id ASC
                ");
                $stmt_details->bind_param('i', $actual_id);
                $stmt_details->execute();
                $details = stmt_fetch_all($stmt_details);
                $stmt_details->close();
            }

            // Jika rincian di jurnal_details kosong (misal pada entri SO), ambil langsung dari general_ledger
            if (empty($details)) {
                $stmt_gl_details = $conn->prepare("
                    SELECT gl.account_id, gl.debit, gl.kredit, a.kode_akun, a.nama_akun
                    FROM general_ledger gl
                    JOIN accounts a ON gl.account_id = a.id
                    WHERE gl.nomor_referensi = ? AND gl.user_id = ?
                    ORDER BY gl.debit DESC, gl.id ASC
                ");
                $stmt_gl_details->bind_param('si', $nomor_ref, $user_id);
                $stmt_gl_details->execute();
                $details = stmt_fetch_all($stmt_gl_details);
                $stmt_gl_details->close();
            }

            echo json_encode(['status' => 'success', 'data' => ['header' => $header, 'details' => $details]]);
            exit;
        }

        // Default action: list
        $limit = (int) ($_GET['limit'] ?? 15);
        $page = (int) ($_GET['page'] ?? 1);
        $offset = ($page - 1) * $limit;

        $search = $_GET['search'] ?? '';
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        $ref_type = $_GET['ref_type'] ?? '';
        $ref_prefix = $_GET['ref_prefix'] ?? '';

        $where_clauses = ['gl.user_id = ?'];
        $params = ['i', $user_id];

        if (!empty($ref_type)) {
            $where_clauses[] = 'gl.ref_type = ?';
            $params[0] .= 's';
            $params[] = $ref_type;
        }

        if (!empty($ref_prefix)) {
            $prefixes = array_filter(array_map('trim', explode(',', $ref_prefix)));
            if (!empty($prefixes)) {
                $pfx_clauses = [];
                foreach ($prefixes as $pfx) {
                    $pfx_clauses[] = 'gl.nomor_referensi LIKE ?';
                    $params[0] .= 's';
                    $params[] = $pfx . '%';
                }
                $where_clauses[] = '(' . implode(' OR ', $pfx_clauses) . ')';
            }
        }

        if (!empty($search)) {
            $where_clauses[] = '(gl.keterangan LIKE ? OR gl.nomor_referensi LIKE ?)';
            $params[0] .= 'ss';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if (!empty($start_date)) {
            $where_clauses[] = 'gl.tanggal >= ?';
            $params[0] .= 's';
            $params[] = $start_date;
        }
        if (!empty($end_date)) {
            $where_clauses[] = 'gl.tanggal <= ?';
            $params[0] .= 's';
            $params[] = $end_date;
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

        // Get total count of LEDGER ROWS and distinct journal entries
        $count_query = "SELECT COUNT(*) as total, COUNT(DISTINCT gl.nomor_referensi) as total_entries FROM general_ledger gl $where_sql";

        $total_stmt = $conn->prepare($count_query);
        $bind_params_total = [&$params[0]];
        for ($i = 1; $i < count($params); $i++) {
            $bind_params_total[] = &$params[$i];
        }
        call_user_func_array([$total_stmt, 'bind_param'], $bind_params_total);
        $total_stmt->execute();
        $tr_res = stmt_fetch_assoc($total_stmt);
        $total_records = (int) ($tr_res ? $tr_res['total'] : 0);
        $total_entries = (int) ($tr_res ? ($tr_res['total_entries'] ?? $total_records) : 0);
        $total_stmt->close();

        // Get data
        $query = "
            SELECT
                gl.ref_type as source, 
                gl.ref_id as entry_id, 
                gl.nomor_referensi as ref,
                COALESCE(je.updated_at, t.updated_at, p.updated_at, gl.created_at) as tanggal,
                gl.keterangan,
                acc.nama_akun,
                gl.debit,
                gl.kredit,
                -- Ambil audit info dari tabel sumber
                COALESCE(creator_je.username, creator_t.username, creator_p.username, creator_gl.username, 'sistem') as created_by_name,
                COALESCE(updater_je.username, updater_t.username, updater_p.username, 'sistem') as updated_by_name,
                COALESCE(je.created_at, t.created_at, p.created_at, gl.created_at) as created_at,
                COALESCE(je.updated_at, t.updated_at, p.updated_at, gl.created_at) as latest_update
            FROM general_ledger gl
            JOIN accounts acc ON gl.account_id = acc.id
            LEFT JOIN jurnal_entries je ON gl.ref_id = je.id AND gl.ref_type = 'jurnal'
            LEFT JOIN transaksi t ON gl.ref_id = t.id AND gl.ref_type = 'transaksi'
            LEFT JOIN penjualan p ON gl.ref_id = p.id AND gl.ref_type = 'penjualan'
            LEFT JOIN users creator_je ON je.created_by = creator_je.id
            LEFT JOIN users creator_t ON t.created_by = creator_t.id
            LEFT JOIN users creator_p ON p.created_by = creator_p.id
            LEFT JOIN users creator_gl ON gl.created_by = creator_gl.id
            LEFT JOIN users updater_je ON je.updated_by = updater_je.id
            LEFT JOIN users updater_t ON t.updated_by = updater_t.id
            LEFT JOIN users updater_p ON p.updated_by = updater_p.id
            $where_sql
        ";

        // Add ORDER BY before LIMIT clause
        $sort_by = $_GET['sort_by'] ?? 'tanggal';
        if ($sort_by === 'no_ref') {
            $query .= " ORDER BY gl.nomor_referensi DESC, gl.debit DESC";
        } else {
            // Default sort requested by user: updated_at desc (data terbaru ditaruh paling atas)
            $query .= " ORDER BY latest_update DESC, gl.ref_id DESC, gl.debit DESC";
        }

        // Handle pagination only if limit is not -1 (ALL)
        if ($limit != -1) {
            $query .= " LIMIT ? OFFSET ?";
        }

        //print($query) ;

        if ($limit != -1) {
            $params[0] .= 'ii';
            $params[] = $limit;
            $params[] = $offset;
        }

        $stmt = $conn->prepare($query);
        $bind_params_main = [&$params[0]];
        for ($i = 1; $i < count($params); $i++) {
            $bind_params_main[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_params_main);

        $stmt->execute();
        $entries = stmt_fetch_all($stmt);
        $stmt->close();

        $total_pages = 0;
        if ($limit > 0) {
            $total_pages = ceil($total_records / $limit);
        }

        $pagination = [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'total_entries' => $total_entries
        ];
        echo json_encode(['status' => 'success', 'data' => $entries, 'pagination' => $pagination]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? 'add';

        if ($action === 'add') {
            $tanggal = $_POST['tanggal'] ?? '';
            $keterangan_raw = $_POST['keterangan'] ?? '';
            $lines = $_POST['lines'] ?? [];

            if (empty($tanggal) || empty($keterangan_raw) || empty($lines)) {
                throw new Exception("Tanggal, keterangan, dan minimal dua baris jurnal wajib diisi.");
            }
            $keterangan = trim($keterangan_raw);

            check_period_lock($tanggal, $conn);

            $total_debit = 0;
            $total_kredit = 0;
            foreach ($lines as $line) {
                if (empty($line['account_id'])) {
                    throw new Exception("Setiap baris jurnal harus memiliki akun yang dipilih.");
                }
                $total_debit += (float) ($line['debit'] ?? 0);
                $total_kredit += (float) ($line['kredit'] ?? 0);
            }

            if (count($lines) < 2) {
                throw new Exception("Jurnal harus memiliki minimal dua baris (satu debit dan satu kredit).");
            }
            if (abs($total_debit - $total_kredit) > 0.01) {
                throw new Exception("Jurnal tidak seimbang. Total Debit (Rp " . number_format($total_debit) . ") harus sama dengan Total Kredit (Rp " . number_format($total_kredit) . ").");
            }
            if ($total_debit === 0) {
                throw new Exception("Total jurnal tidak boleh nol.");
            }

            $conn->begin_transaction();
            // 1. Insert header to get the new ID
            $stmt_header = $conn->prepare("INSERT INTO jurnal_entries (user_id, tanggal, keterangan, created_by) VALUES (?, ?, ?, ?)"); // user_id is the data owner, created_by is the logged in user
            $stmt_header->bind_param('issi', $user_id, $tanggal, $keterangan, $logged_in_user_id);
            $stmt_header->execute();
            $jurnal_entry_id = $conn->insert_id;
            $stmt_header->close();

            $nomor_referensi_jurnal = 'JRN-' . $jurnal_entry_id;
            // 2. Insert ke tabel detail (jurnal_details)
            $stmt_detail = $conn->prepare("INSERT INTO jurnal_details (jurnal_entry_id, account_id, debit, kredit) VALUES (?, ?, ?, ?)");
            // Sinkronisasi ke General Ledger
            $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'jurnal', ?)"); // user_id is data owner, created_by is logged in user
            foreach ($lines as $line) {
                $account_id = (int) $line['account_id'];
                $debit = (float) ($line['debit'] ?? 0);
                $kredit = (float) ($line['kredit'] ?? 0);
                if ($debit > 0 || $kredit > 0) {
                    $stmt_detail->bind_param('iidd', $jurnal_entry_id, $account_id, $debit, $kredit);
                    $stmt_detail->execute();
                    $stmt_gl->bind_param('isssiddii', $user_id, $tanggal, $keterangan, $nomor_referensi_jurnal, $account_id, $debit, $kredit, $jurnal_entry_id, $logged_in_user_id);
                    $stmt_gl->execute();
                }
            }
            $stmt_detail->close();
            $stmt_gl->close();

            $conn->commit();
            log_activity($_SESSION['username'], 'Tambah Entri Jurnal', "Jurnal majemuk baru '{$keterangan}' ditambahkan.");
            echo json_encode(['status' => 'success', 'message' => 'Entri jurnal berhasil ditambahkan.']);

        } elseif ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $tanggal = $_POST['tanggal'] ?? '';
            $keterangan_raw = $_POST['keterangan'] ?? '';
            $lines = $_POST['lines'] ?? [];

            if (empty($tanggal) || empty($keterangan_raw) || empty($lines)) {
                throw new Exception("Tanggal, keterangan, dan minimal dua baris jurnal wajib diisi.");
            }
            $keterangan = trim($keterangan_raw);

            // Validasi: hanya data yang diinput dari menu Entri Jurnal (nomor_referensi JRN-...) atau Stok Opname (SO-...) yang boleh diupdate
            $stmt_chk_source = $conn->prepare("SELECT id, nomor_referensi FROM general_ledger WHERE ref_id = ? AND (nomor_referensi LIKE 'JRN-%' OR nomor_referensi LIKE 'SO-%') LIMIT 1");
            $stmt_chk_source->bind_param('i', $id);
            $stmt_chk_source->execute();
            $chk_src = stmt_fetch_assoc($stmt_chk_source);
            $stmt_chk_source->close();
            if (!$chk_src) {
                throw new Exception("Hanya data yang diinput dari menu Entri Jurnal (JRN) atau Stok Opname (SO) yang dapat diubah di sini.");
            }
            $existing_ref = $chk_src['nomor_referensi'];

            // Cek periode lock SEBELUM update
            check_period_lock($tanggal, $conn);
            // Cek juga tanggal LAMA dari jurnal yang akan diubah dan simpan created_by
            $stmt_old_date = $conn->prepare("SELECT tanggal, created_by FROM general_ledger WHERE ref_id = ? AND (nomor_referensi LIKE 'JRN-%' OR nomor_referensi LIKE 'SO-%') LIMIT 1");
            $stmt_old_date->bind_param('i', $id);
            $stmt_old_date->execute();
            $od_res = stmt_fetch_assoc($stmt_old_date);
            $old_created_by = $od_res ? $od_res['created_by'] : $logged_in_user_id;
            check_period_lock($od_res ? $od_res['tanggal'] : null, $conn);
            $stmt_old_date->close();

            $total_debit = 0;
            $total_kredit = 0;
            foreach ($lines as $line) {
                if (empty($line['account_id'])) {
                    throw new Exception("Setiap baris jurnal harus memiliki akun yang dipilih.");
                }
                $total_debit += (float) ($line['debit'] ?? 0);
                $total_kredit += (float) ($line['kredit'] ?? 0);
            }

            if (count($lines) < 2) {
                throw new Exception("Jurnal harus memiliki minimal dua baris (satu debit dan satu kredit).");
            }
            if (abs($total_debit - $total_kredit) > 0.01) {
                throw new Exception("Jurnal tidak seimbang.");
            }
            if ($total_debit === 0) {
                throw new Exception("Total jurnal tidak boleh nol.");
            }

            $conn->begin_transaction();
            if ($id <= 0)
                throw new Exception("ID Jurnal tidak valid untuk diperbarui.");

            // 1. Update header di tabel jurnal_entries (jika ada, update; jika belum ada, buat barunya)
            $stmt_chk_je = $conn->prepare("SELECT id FROM jurnal_entries WHERE id = ?");
            $stmt_chk_je->bind_param('i', $id);
            $stmt_chk_je->execute();
            $has_je = stmt_fetch_assoc($stmt_chk_je);
            $stmt_chk_je->close();

            if ($has_je) {
                $stmt_header = $conn->prepare("UPDATE jurnal_entries SET tanggal = ?, keterangan = ?, updated_by = ? WHERE id = ? AND user_id = ?");
                $stmt_header->bind_param('ssiii', $tanggal, $keterangan, $logged_in_user_id, $id, $user_id);
                $stmt_header->execute();
                $stmt_header->close();
            } else {
                $stmt_header = $conn->prepare("INSERT INTO jurnal_entries (id, user_id, tanggal, keterangan, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_header->bind_param('iissii', $id, $user_id, $tanggal, $keterangan, $old_created_by, $logged_in_user_id);
                $stmt_header->execute();
                $stmt_header->close();
            }

            // 2. Hapus detail lama
            $stmt_delete = $conn->prepare("DELETE FROM jurnal_details WHERE jurnal_entry_id = ?");
            $stmt_delete->bind_param('i', $id);
            $stmt_delete->execute();
            $stmt_delete->close();

            // Hapus juga dari General Ledger (ref_type = 'jurnal' maupun 'transaksi' dengan SO-)
            $stmt_delete_gl = $conn->prepare("DELETE FROM general_ledger WHERE ref_id = ? AND (ref_type = 'jurnal' OR (ref_type = 'transaksi' AND nomor_referensi LIKE 'SO-%')) AND user_id = ?");
            $stmt_delete_gl->bind_param('ii', $id, $user_id);
            $stmt_delete_gl->execute();
            $stmt_delete_gl->close();

            // 3. Insert detail baru (pertahankan nomor_referensi SO- atau JRN-)
            $nomor_referensi_jurnal = !empty($existing_ref) ? $existing_ref : ('JRN-' . $id);
            $stmt_detail = $conn->prepare("INSERT INTO jurnal_details (jurnal_entry_id, account_id, debit, kredit) VALUES (?, ?, ?, ?)");
            $stmt_gl = $conn->prepare("INSERT INTO general_ledger (user_id, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'jurnal', ?, ?)");
            foreach ($lines as $line) {
                $account_id = (int) $line['account_id'];
                $debit = (float) ($line['debit'] ?? 0);
                $kredit = (float) ($line['kredit'] ?? 0);
                if ($debit > 0 || $kredit > 0) {
                    $stmt_detail->bind_param('iidd', $id, $account_id, $debit, $kredit);
                    $stmt_detail->execute();
                    $stmt_gl->bind_param('isssiddiii', $user_id, $tanggal, $keterangan, $nomor_referensi_jurnal, $account_id, $debit, $kredit, $id, $old_created_by, $logged_in_user_id);
                    $stmt_gl->execute();
                }
            }
            $stmt_detail->close();
            $stmt_gl->close();
            $conn->commit();
            log_activity($_SESSION['username'], 'Update Entri Jurnal', "Jurnal majemuk ID {$id} diperbarui.");
            echo json_encode(['status' => 'success', 'message' => 'Entri jurnal berhasil diperbarui.']);

        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0)
                throw new Exception("ID Jurnal tidak valid untuk dihapus.");

            // Validasi: hanya data yang diinput dari menu Entri Jurnal (nomor_referensi JRN-...) atau Stok Opname (SO-...) yang boleh dihapus
            $stmt_chk_source = $conn->prepare("SELECT id FROM general_ledger WHERE ref_id = ? AND (nomor_referensi LIKE 'JRN-%' OR nomor_referensi LIKE 'SO-%') LIMIT 1");
            $stmt_chk_source->bind_param('i', $id);
            $stmt_chk_source->execute();
            $chk_src = stmt_fetch_assoc($stmt_chk_source);
            $stmt_chk_source->close();
            if (!$chk_src) {
                throw new Exception("Hanya data yang diinput dari menu Entri Jurnal (JRN) atau Stok Opname (SO) yang dapat dihapus di sini.");
            }

            // Cek periode lock sebelum hapus
            $stmt_old_date = $conn->prepare("SELECT tanggal FROM general_ledger WHERE ref_id = ? AND (nomor_referensi LIKE 'JRN-%' OR nomor_referensi LIKE 'SO-%') LIMIT 1");
            $stmt_old_date->bind_param('i', $id);
            $stmt_old_date->execute();
            $od_res_del = stmt_fetch_assoc($stmt_old_date);
            $old_date = $od_res_del ? $od_res_del['tanggal'] : null;
            check_period_lock($old_date, $conn);
            $stmt_old_date->close();

            $conn->begin_transaction();

            // 1. Hapus dari jurnal_details jika ada
            $stmt_details = $conn->prepare("DELETE FROM jurnal_details WHERE jurnal_entry_id = ?");
            $stmt_details->bind_param('i', $id);
            $stmt_details->execute();
            $stmt_details->close();

            // 2. Hapus dari general_ledger (ref_type = 'jurnal' maupun 'transaksi' dengan SO-)
            $stmt_gl = $conn->prepare("DELETE FROM general_ledger WHERE ref_id = ? AND (ref_type = 'jurnal' OR (ref_type = 'transaksi' AND nomor_referensi LIKE 'SO-%')) AND user_id = ?");
            $stmt_gl->bind_param('ii', $id, $user_id);
            $stmt_gl->execute();
            $stmt_gl->close();

            // 3. Hapus dari jurnal_entries jika ada
            $stmt = $conn->prepare("DELETE FROM jurnal_entries WHERE id = ? AND user_id = ?");
            $stmt->bind_param('ii', $id, $user_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            log_activity($_SESSION['username'], 'Hapus Entri Jurnal', "Jurnal majemuk ID {$id} dihapus.");
            echo json_encode(['status' => 'success', 'message' => 'Entri jurnal berhasil dihapus.']);
        }

    }
} catch (Exception $e) {
    // Check if in transaction before rolling back, compatible with older PHP versions
    if (method_exists($conn, 'in_transaction') && $conn->in_transaction()) {
        $conn->rollback();
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>