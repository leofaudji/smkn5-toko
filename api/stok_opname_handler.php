<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

require_once __DIR__ . '/../includes/accounting_helper.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

check_permission('stok_opname', 'menu');

$conn              = Database::getInstance()->getConnection();
$redis             = RedisManager::getInstance();
$owner_user_id     = 1; // Pemilik data toko
$logged_in_user_id = (int) $_SESSION['user_id'];

try {
    /* ================================================================
       GET REQUESTS
    ================================================================ */
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'get_active_session';

        // ── Cek sesi aktif ─────────────────────────────────────────
        if ($action === 'get_active_session') {
            $stmt = $conn->prepare("
                SELECT s.*, u.nama_lengkap AS created_by_name,
                       a.kode_akun, a.nama_akun
                FROM   stok_opname_sessions s
                LEFT JOIN users    u ON s.created_by    = u.id
                LEFT JOIN accounts a ON s.adj_account_id = a.id
                WHERE  s.user_id = ? AND s.status = 'aktif'
                ORDER BY s.created_at DESC LIMIT 1
            ");
            $stmt->bind_param('i', $owner_user_id);
            $stmt->execute();
            $session = stmt_fetch_assoc($stmt);
            $stmt->close();
            header('Content-Type: application/json; charset=UTF-8');
            if (ob_get_length()) ob_clean();
            echo json_encode(['status' => 'success', 'data' => $session], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            die();

        // ── Daftar barang dalam sesi ────────────────────────────────
        } elseif ($action === 'get_session_items') {
            $session_id = (int) ($_GET['session_id'] ?? 0);
            $search     = $_GET['search'] ?? '';
            $filter     = $_GET['filter'] ?? ''; // 'belum' = hanya yang belum diisi

            if (!$session_id) throw new Exception("Session ID tidak valid.");

            $extra_where  = '';
            $extra_types  = '';
            $extra_params = [];

            if (!empty($search)) {
                $extra_where   .= " AND (i.nama_barang LIKE ? OR i.sku LIKE ?)";
                $extra_types   .= 'ss';
                $term           = '%' . $search . '%';
                $extra_params[] = $term;
                $extra_params[] = $term;
            }
            if ($filter === 'belum') {
                $extra_where .= " AND d.stok_fisik IS NULL";
            }

            $sql = "
                SELECT i.id, i.nama_barang, i.sku,
                       d.stok_sistem, d.stok_fisik,
                       d.dihitung_oleh, u.nama_lengkap AS petugas_nama,
                       d.dihitung_at
                FROM   items i
                LEFT JOIN stok_opname_draft_items d
                       ON d.item_id = i.id AND d.session_id = ?
                LEFT JOIN users u ON d.dihitung_oleh = u.id
                WHERE  i.user_id = ? $extra_where
                ORDER BY i.nama_barang ASC
            ";

            $stmt  = $conn->prepare($sql);
            $types = 'ii' . $extra_types;
            $bind  = [&$types, &$session_id, &$owner_user_id];
            foreach ($extra_params as &$p) { $bind[] = &$p; }
            call_user_func_array([$stmt, 'bind_param'], $bind);
            $stmt->execute();
            $items = stmt_fetch_all($stmt);
            $stmt->close();

            // Ringkasan
            $s2 = $conn->prepare("
                SELECT COUNT(i.id)                                             AS total,
                       COUNT(CASE WHEN d.stok_fisik IS NOT NULL THEN 1 END)   AS sudah_dihitung
                FROM   items i
                LEFT JOIN stok_opname_draft_items d
                       ON d.item_id = i.id AND d.session_id = ?
                WHERE  i.user_id = ?
            ");
            $s2->bind_param('ii', $session_id, $owner_user_id);
            $s2->execute();
            $summary = stmt_fetch_assoc($s2);
            $s2->close();

            header('Content-Type: application/json; charset=UTF-8');
            if (ob_get_length()) ob_clean();
            echo json_encode(['status' => 'success', 'data' => $items, 'summary' => $summary], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            die();

        // ── Polling progress ────────────────────────────────────────
        } elseif ($action === 'get_session_progress') {
            $session_id = (int) ($_GET['session_id'] ?? 0);
            if (!$session_id) throw new Exception("Session ID tidak valid.");

            $redis_key_summary = "so:progress:{$session_id}";
            $redis_key_petugas = "so:petugas:{$session_id}";

            $summary = null;
            $petugas = [];

            // Coba ambil dari Redis
            if ($redis->isAvailable()) {
                $summary = $redis->get($redis_key_summary);
                $petugas_raw = $redis->hGetAll($redis_key_petugas);
                if ($petugas_raw) {
                    foreach ($petugas_raw as $uid => $count) {
                        $petugas[] = ['id' => $uid, 'jumlah_item' => (int)$count];
                    }
                }
            }

            // Jika Redis kosong/tidak ada, ambil dari DB (Fallback & Seeding)
            if (!$summary || empty($petugas)) {
                // Progress per petugas
                $stmt = $conn->prepare("
                    SELECT u.id, u.nama_lengkap, u.username,
                           COUNT(d.id) AS jumlah_item
                    FROM   stok_opname_draft_items d
                    LEFT JOIN users u ON d.dihitung_oleh = u.id
                    WHERE  d.session_id = ?
                      AND  d.stok_fisik IS NOT NULL
                      AND  d.dihitung_oleh IS NOT NULL
                    GROUP BY u.id, u.nama_lengkap, u.username
                ");
                $stmt->bind_param('i', $session_id);
                $stmt->execute();
                $petugas = stmt_fetch_all($stmt);
                $stmt->close();

                // Total ringkasan
                $s2 = $conn->prepare("
                    SELECT COUNT(i.id)                                            AS total,
                           COUNT(CASE WHEN d.stok_fisik IS NOT NULL THEN 1 END)  AS sudah
                    FROM   items i
                    LEFT JOIN stok_opname_draft_items d
                           ON d.item_id = i.id AND d.session_id = ?
                    WHERE  i.user_id = ?
                ");
                $s2->bind_param('ii', $session_id, $owner_user_id);
                $s2->execute();
                $summary = stmt_fetch_assoc($s2);
                $s2->close();

                // Simpan ke Redis untuk request berikutnya (TTL 30 detik agar tetap sinkron)
                if ($redis->isAvailable()) {
                    $redis->set($redis_key_summary, $summary, 30);
                    foreach ($petugas as $p) {
                        $redis->hSet($redis_key_petugas, (string)$p['id'], (string)$p['jumlah_item']);
                    }
                }
            } else {
                // Jika dari Redis, kita perlu nama_lengkap (Redis hanya simpan ID & Count)
                $uids = array_column($petugas, 'id');
                if (!empty($uids)) {
                    $placeholders = implode(',', array_fill(0, count($uids), '?'));
                    $su = $conn->prepare("SELECT id, nama_lengkap, username FROM users WHERE id IN ($placeholders)");
                    $su->bind_param(str_repeat('i', count($uids)), ...$uids);
                    $su->execute();
                    $u_details = stmt_fetch_all($su);
                    $su->close();

                    $u_map = [];
                    foreach ($u_details as $ud) $u_map[$ud['id']] = $ud;
                    
                    foreach ($petugas as &$p) {
                        if (isset($u_map[$p['id']])) {
                            $p['nama_lengkap'] = $u_map[$p['id']]['nama_lengkap'];
                            $p['username']     = $u_map[$p['id']]['username'];
                        }
                    }
                }
            }

            header('Content-Type: application/json; charset=UTF-8');
            if (ob_get_length()) ob_clean();
            echo json_encode(['status' => 'success', 'petugas' => $petugas, 'summary' => $summary], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            die();

        // ── Riwayat sesi ────────────────────────────────────────────
        } elseif ($action === 'get_session_history') {
            $stmt = $conn->prepare("
                SELECT s.*, u.nama_lengkap AS created_by_name,
                       uf.nama_lengkap AS finalized_by_name
                FROM   stok_opname_sessions s
                LEFT JOIN users u  ON s.created_by   = u.id
                LEFT JOIN users uf ON s.finalized_by  = uf.id
                WHERE  s.user_id = ?
                ORDER BY s.created_at DESC LIMIT 20
            ");
            $stmt->bind_param('i', $owner_user_id);
            $stmt->execute();
            $history = stmt_fetch_all($stmt);
            $stmt->close();
            header('Content-Type: application/json; charset=UTF-8');
            if (ob_get_length()) ob_clean();
            echo json_encode(['status' => 'success', 'data' => $history], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            die();
        }

    /* ================================================================
       POST REQUESTS
    ================================================================ */
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data   = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        // ── Buat sesi baru ──────────────────────────────────────────
        if ($action === 'create_session') {
            $tanggal        = $data['tanggal']        ?? '';
            $keterangan     = trim($data['keterangan'] ?? '');
            $adj_account_id = (int) ($data['adj_account_id'] ?? 0);

            if (empty($tanggal) || empty($keterangan) || !$adj_account_id) {
                throw new Exception("Tanggal, keterangan, dan akun penyeimbang wajib diisi.");
            }

            // Cek sesi aktif
            $chk = $conn->prepare("SELECT id FROM stok_opname_sessions WHERE user_id = ? AND status = 'aktif' LIMIT 1");
            $chk->bind_param('i', $owner_user_id);
            $chk->execute();
            if (stmt_fetch_assoc($chk)) throw new Exception("Sudah ada sesi aktif. Selesaikan atau batalkan sesi tersebut dahulu.");
            $chk->close();

            $conn->begin_transaction();

            // Buat sesi
            $ins = $conn->prepare("INSERT INTO stok_opname_sessions (user_id, created_by, tanggal, keterangan, adj_account_id, status) VALUES (?, ?, ?, ?, ?, 'aktif')");
            $ins->bind_param('iissi', $owner_user_id, $logged_in_user_id, $tanggal, $keterangan, $adj_account_id);
            $ins->execute();
            $session_id = $conn->insert_id;
            $ins->close();

            // Populasi draft item (snapshot stok saat ini)
            $pop = $conn->prepare("INSERT INTO stok_opname_draft_items (session_id, item_id, stok_sistem) SELECT ?, id, stok FROM items WHERE user_id = ?");
            $pop->bind_param('ii', $session_id, $owner_user_id);
            $pop->execute();
            $item_count = $pop->affected_rows;
            $pop->close();

            $conn->commit();
            header('Content-Type: application/json; charset=UTF-8');
            if (ob_get_length()) ob_clean();
            echo json_encode(['status' => 'success', 'message' => "Sesi dibuka dengan {$item_count} barang.", 'session_id' => $session_id], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            die();

        // ── Auto-save satu item ─────────────────────────────────────
        } elseif ($action === 'save_draft_item') {
            $session_id = (int) ($data['session_id'] ?? 0);
            $item_id    = (int) ($data['item_id']    ?? 0);
            $stok_fisik = $data['stok_fisik'] ?? null;

            if (!$session_id || !$item_id) throw new Exception("Data tidak lengkap.");
            if ($stok_fisik !== null && (int)$stok_fisik < 0) throw new Exception("Stok fisik tidak boleh negatif.");

            // Pastikan sesi masih aktif
            $chk = $conn->prepare("SELECT id FROM stok_opname_sessions WHERE id = ? AND user_id = ? AND status = 'aktif'");
            $chk->bind_param('ii', $session_id, $owner_user_id);
            $chk->execute();
            if (!stmt_fetch_assoc($chk)) throw new Exception("Sesi tidak valid atau sudah selesai.");
            $chk->close();

            // Cek status sebelumnya untuk update Redis counter
            $c_old = $conn->prepare("SELECT stok_fisik, dihitung_oleh FROM stok_opname_draft_items WHERE session_id = ? AND item_id = ?");
            $c_old->bind_param('ii', $session_id, $item_id);
            $c_old->execute();
            $old_data = stmt_fetch_assoc($c_old);
            $c_old->close();

            if ($stok_fisik !== null) {
                $val = (int) $stok_fisik;
                $now = date('Y-m-d H:i:s');
                $upd = $conn->prepare("UPDATE stok_opname_draft_items SET stok_fisik = ?, dihitung_oleh = ?, dihitung_at = ? WHERE session_id = ? AND item_id = ?");
                $upd->bind_param('iisii', $val, $logged_in_user_id, $now, $session_id, $item_id);
            } else {
                // Reset ke belum dihitung
                $upd = $conn->prepare("UPDATE stok_opname_draft_items SET stok_fisik = NULL, dihitung_oleh = NULL, dihitung_at = NULL WHERE session_id = ? AND item_id = ?");
                $upd->bind_param('ii', $session_id, $item_id);
            }
            $upd->execute();
            $upd->close();

            // Update Redis (Atomic operation)
            if ($redis->isAvailable()) {
                $redis_key_summary = "so:progress:{$session_id}";
                $redis_key_petugas = "so:petugas:{$session_id}";

                $summary = $redis->get($redis_key_summary);
                
                // Jika user mengisi (dari NULL ke NOT NULL)
                if ($old_data['stok_fisik'] === null && $stok_fisik !== null) {
                    if ($summary) {
                        $summary['sudah']++;
                        $redis->set($redis_key_summary, $summary, 300);
                    }
                    $redis->getClient()->hIncrBy($redis_key_petugas, (string)$logged_in_user_id, 1);
                } 
                // Jika user mengosongkan (dari NOT NULL ke NULL)
                elseif ($old_data['stok_fisik'] !== null && $stok_fisik === null) {
                    if ($summary) {
                        $summary['sudah']--;
                        $redis->set($redis_key_summary, $summary, 300);
                    }
                    $old_petugas = $old_data['dihitung_oleh'];
                    $redis->getClient()->hIncrBy($redis_key_petugas, (string)$old_petugas, -1);
                }
            }

            header('Content-Type: application/json; charset=UTF-8');
            if (ob_get_length()) ob_clean();
            echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            die();

        // ── Finalisasi sesi ─────────────────────────────────────────
        } elseif ($action === 'finalize_session') {
            $session_id = (int) ($data['session_id'] ?? 0);
            if (!$session_id) throw new Exception("Session ID tidak valid.");

            $stmt = $conn->prepare("SELECT * FROM stok_opname_sessions WHERE id = ? AND user_id = ? AND status = 'aktif'");
            $stmt->bind_param('ii', $session_id, $owner_user_id);
            $stmt->execute();
            $session = stmt_fetch_assoc($stmt);
            $stmt->close();

            if (!$session) throw new Exception("Sesi tidak ditemukan atau sudah selesai.");
            if ((int)$session['created_by'] !== $logged_in_user_id) throw new Exception("Hanya pembuat sesi yang dapat melakukan finalisasi.");

            // Ambil item yang ada selisih
            $stmt = $conn->prepare("
                SELECT d.item_id, d.stok_sistem, d.stok_fisik,
                       i.harga_beli, i.inventory_account_id
                FROM   stok_opname_draft_items d
                JOIN   items i ON d.item_id = i.id
                WHERE  d.session_id = ?
                  AND  d.stok_fisik IS NOT NULL
                  AND  d.stok_fisik != d.stok_sistem
            ");
            $stmt->bind_param('i', $session_id);
            $stmt->execute();
            $to_adjust = stmt_fetch_all($stmt);
            $stmt->close();

            $conn->begin_transaction();
            try {
                $tanggal        = $session['tanggal'];
                $keterangan     = $session['keterangan'];
                $adj_acc_id     = (int) $session['adj_account_id'];
                $ket_jurnal     = "Stok Opname: " . $keterangan;

                if (!empty($to_adjust)) {
                    $journal_id  = create_journal_entry($tanggal, $ket_jurnal, $owner_user_id, $logged_in_user_id);
                    $nomor_ref   = "SO-" . $journal_id;

                    $upd_stok = $conn->prepare("UPDATE items SET stok = ? WHERE id = ?");
                    $ins_hist = $conn->prepare("INSERT INTO stock_adjustments (item_id, user_id, journal_id, tanggal, stok_sebelum, stok_setelah, selisih_kuantitas, selisih_nilai, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $ins_ks   = $conn->prepare("INSERT INTO kartu_stok (tanggal, item_id, debit, kredit, keterangan, ref_id, source, user_id) VALUES (?, ?, ?, ?, ?, ?, 'adjustment', ?)");

                    $ledger = [];

                    foreach ($to_adjust as $item) {
                        $item_id      = (int) $item['item_id'];
                        $stok_sblm    = (int) $item['stok_sistem'];
                        $stok_fisik   = (int) $item['stok_fisik'];
                        $selisih_qty  = $stok_fisik - $stok_sblm;
                        $harga_beli   = (float) $item['harga_beli'];
                        $selisih_val  = $selisih_qty * $harga_beli;
                        $inv_acc_id   = $item['inventory_account_id'] ?: get_setting('default_inventory_account_id');

                        if (empty($inv_acc_id)) throw new Exception("Akun persediaan item ID {$item_id} belum diatur.");

                        $zero = 0.0;
                        if ($selisih_val < 0) { // Stok berkurang
                            add_journal_line($journal_id, $adj_acc_id,   abs($selisih_val), $zero);
                            add_journal_line($journal_id, $inv_acc_id,   $zero, abs($selisih_val));
                            $ledger[$adj_acc_id]['debit']   = ($ledger[$adj_acc_id]['debit']   ?? 0) + abs($selisih_val);
                            $ledger[$inv_acc_id]['credit']  = ($ledger[$inv_acc_id]['credit']  ?? 0) + abs($selisih_val);
                        } else { // Stok bertambah
                            add_journal_line($journal_id, $inv_acc_id,   $selisih_val, $zero);
                            add_journal_line($journal_id, $adj_acc_id,   $zero, $selisih_val);
                            $ledger[$inv_acc_id]['debit']   = ($ledger[$inv_acc_id]['debit']   ?? 0) + $selisih_val;
                            $ledger[$adj_acc_id]['credit']  = ($ledger[$adj_acc_id]['credit']  ?? 0) + $selisih_val;
                        }

                        $upd_stok->bind_param('ii', $stok_fisik, $item_id);
                        $upd_stok->execute();

                        $ins_hist->bind_param('iiisiisds', $item_id, $logged_in_user_id, $journal_id, $tanggal, $stok_sblm, $stok_fisik, $selisih_qty, $selisih_val, $keterangan);
                        $ins_hist->execute();

                        $dbt = $selisih_qty > 0 ? $selisih_qty : 0;
                        $krt = $selisih_qty < 0 ? abs($selisih_qty) : 0;
                        $ins_ks->bind_param('siiisii', $tanggal, $item_id, $dbt, $krt, $ket_jurnal, $journal_id, $owner_user_id);
                        $ins_ks->execute();
                    }

                    foreach ($ledger as $acc_id => $totals) {
                        $d = $totals['debit']  ?? 0;
                        $c = $totals['credit'] ?? 0;
                        if ($d > 0 || $c > 0) {
                            update_general_ledger($conn, $owner_user_id, $acc_id, $tanggal, $d, $c, $ket_jurnal, $nomor_ref, $journal_id);
                        }
                    }
                }

                // Tandai sesi selesai
                $done = $conn->prepare("UPDATE stok_opname_sessions SET status='selesai', finalized_by=?, finalized_at=NOW() WHERE id=?");
                $done->bind_param('ii', $logged_in_user_id, $session_id);
                $done->execute();
                $done->close();

                $conn->commit();
                
                // Bersihkan Redis (Progress & Report Cache)
                if ($redis->isAvailable()) {
                    $redis->del("so:progress:{$session_id}");
                    $redis->del("so:petugas:{$session_id}");
                    $redis->flushReports();
                    $redis->flushSearchCache();
                }
                $jumlah = count($to_adjust);
                $msg = $jumlah > 0
                    ? "{$jumlah} barang berhasil disesuaikan. Jurnal #{$journal_id} telah dibuat."
                    : "Sesi diselesaikan. Tidak ada selisih stok.";
                header('Content-Type: application/json; charset=UTF-8');
                if (ob_get_length()) ob_clean();
                echo json_encode(['status' => 'success', 'message' => $msg], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
                die();
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }

        // ── Batalkan sesi ───────────────────────────────────────────
        } elseif ($action === 'cancel_session') {
            $session_id = (int) ($data['session_id'] ?? 0);
            if (!$session_id) throw new Exception("Session ID tidak valid.");

            $stmt = $conn->prepare("SELECT created_by FROM stok_opname_sessions WHERE id = ? AND user_id = ? AND status = 'aktif'");
            $stmt->bind_param('ii', $session_id, $owner_user_id);
            $stmt->execute();
            $session = stmt_fetch_assoc($stmt);
            $stmt->close();

            if (!$session) throw new Exception("Sesi tidak ditemukan.");
            if ((int)$session['created_by'] !== $logged_in_user_id) throw new Exception("Hanya pembuat sesi yang dapat membatalkan sesi ini.");

            $conn->begin_transaction();
            $del1 = $conn->prepare("DELETE FROM stok_opname_draft_items WHERE session_id = ?");
            $del1->bind_param('i', $session_id);
            $del1->execute();
            $del1->close();

            $del2 = $conn->prepare("DELETE FROM stok_opname_sessions WHERE id = ?");
            $del2->bind_param('i', $session_id);
            $del2->execute();
            $del2->close();

            // Bersihkan Redis
            if ($redis->isAvailable()) {
                $redis->del("so:progress:{$session_id}");
                $redis->del("so:petugas:{$session_id}");
            }

            $conn->commit();

            header('Content-Type: application/json; charset=UTF-8');
            if (ob_get_length()) ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Sesi berhasil dibatalkan.'], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            die();
        }
    }
} catch (Exception $e) {
    if (isset($conn) && method_exists($conn, 'in_transaction') && $conn->in_transaction()) {
        $conn->rollback();
    }
    header('Content-Type: application/json; charset=UTF-8');
    if (ob_get_length()) ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    die();
}
?>
