<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_all':
        get_all_pinjaman($db);
        break;
    case 'get_detail':
        get_detail_pinjaman($db);
        break;
    case 'get_jenis_pinjaman':
        $res = $db->query("SELECT * FROM ksp_jenis_pinjaman");
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        break;
    case 'store':
        store_pinjaman($db);
        break;
    case 'update':
        update_pinjaman($db);
        break;
    case 'delete':
        delete_pinjaman($db);
        break;
    case 'approve':
        approve_pinjaman($db);
        break;
    case 'pay_installment':
        pay_installment($db);
        break;
    case 'get_payoff_info':
        get_payoff_info($db);
        break;
    case 'pay_off':
        pay_off($db);
        break;
    case 'get_tipe_agunan':
        try {
            // Ambil data tipe agunan dari database
            $result = $db->query("SELECT id, nama, config FROM ksp_tipe_agunan ORDER BY nama ASC");
            $data = $result->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal memuat tipe agunan: ' . $e->getMessage()]);
        }
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
        break;
}

function get_all_pinjaman($db) {
    $sql = "SELECT p.*, a.nama_lengkap, a.nomor_anggota, j.nama as jenis_pinjaman,
            (p.jumlah_pinjaman - COALESCE(SUM(ang.pokok_terbayar), 0)) as sisa_pokok
            FROM ksp_pinjaman p
            JOIN anggota a ON p.anggota_id = a.id
            JOIN ksp_jenis_pinjaman j ON p.jenis_pinjaman_id = j.id
            LEFT JOIN ksp_angsuran ang ON p.id = ang.pinjaman_id
            GROUP BY p.id
            ORDER BY p.created_at DESC";
    $result = $db->query($sql);
    echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
}

function get_detail_pinjaman($db) {
    $id = $_GET['id'] ?? 0;
    
    // Get Header
    $sql = "SELECT 
                p.*, 
                a.nama_lengkap, 
                j.nama as jenis_pinjaman,
                pa.detail_json as agunan_detail_json,
                pa.tipe_agunan_id,
                ta.nama as nama_tipe_agunan
            FROM ksp_pinjaman p 
            JOIN anggota a ON p.anggota_id = a.id 
            JOIN ksp_jenis_pinjaman j ON p.jenis_pinjaman_id = j.id
            LEFT JOIN ksp_pinjaman_agunan pa ON p.id = pa.pinjaman_id
            LEFT JOIN ksp_tipe_agunan ta ON pa.tipe_agunan_id = ta.id
            WHERE p.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $pinjaman = $stmt->get_result()->fetch_assoc();

    if (!$pinjaman) {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        return;
    }

    // Get Schedule
    $stmt_sch = $db->prepare("SELECT * FROM ksp_angsuran WHERE pinjaman_id = ? ORDER BY angsuran_ke ASC");
    $stmt_sch->bind_param("i", $id);
    $stmt_sch->execute();
    $angsuran = $stmt_sch->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'data' => $pinjaman, 'schedule' => $angsuran]);
}

function store_pinjaman($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = 1;
    $created_by = $_SESSION['user_id'];

    $db->begin_transaction();
    try {
        // 1. Generate Nomor Pinjaman
        $prefix = "PINJ-" . date('Ymd') . "-";
        $res = $db->query("SELECT id FROM ksp_pinjaman ORDER BY id DESC LIMIT 1");
        $last = $res->fetch_assoc();
        $seq = ($last ? $last['id'] : 0) + 1;
        $nomor_pinjaman = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

        // 2. Ambil Bunga dari Jenis Pinjaman
        $stmt_jenis = $db->prepare("SELECT bunga_per_tahun FROM ksp_jenis_pinjaman WHERE id = ?");
        $stmt_jenis->bind_param("i", $data['jenis_pinjaman_id']);
        $stmt_jenis->execute();
        $jenis = $stmt_jenis->get_result()->fetch_assoc();
        $bunga_persen = $jenis['bunga_per_tahun'];

        // 3. Simpan Header Pinjaman
        $stmt = $db->prepare("INSERT INTO ksp_pinjaman (user_id, nomor_pinjaman, anggota_id, jenis_pinjaman_id, jumlah_pinjaman, bunga_per_tahun, tenor_bulan, tanggal_pengajuan, status, keterangan, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)");
        $stmt->bind_param("isiiddissi", $user_id, $nomor_pinjaman, $data['anggota_id'], $data['jenis_pinjaman_id'], $data['jumlah_pinjaman'], $bunga_persen, $data['tenor_bulan'], $data['tanggal_pengajuan'], $data['keterangan'], $created_by);
        $stmt->execute();
        $pinjaman_id = $stmt->insert_id;

        // 4. Simpan Detail Agunan (jika ada)
        if (!empty($data['tipe_agunan_id']) && !empty($data['agunan_detail']) && is_array($data['agunan_detail'])) {
            $stmt_agunan = $db->prepare("INSERT INTO ksp_pinjaman_agunan (pinjaman_id, tipe_agunan_id, detail_json) VALUES (?, ?, ?)");
            $detail_json = json_encode($data['agunan_detail']);
            $stmt_agunan->bind_param("iis", $pinjaman_id, $data['tipe_agunan_id'], $detail_json);
            $stmt_agunan->execute();
        }

        // 5. Generate Jadwal Angsuran (Metode Flat)
        $pokok_bulanan = $data['jumlah_pinjaman'] / $data['tenor_bulan'];
        $bunga_bulanan = ($data['jumlah_pinjaman'] * ($bunga_persen / 100)) / 12;
        $total_bulanan = $pokok_bulanan + $bunga_bulanan;
        
        $stmt_angsuran = $db->prepare("INSERT INTO ksp_angsuran (pinjaman_id, angsuran_ke, tanggal_jatuh_tempo, pokok, bunga, total_angsuran) VALUES (?, ?, ?, ?, ?, ?)");
        
        $tgl_mulai = new DateTime($data['tanggal_pengajuan']);
        // Angsuran pertama biasanya 1 bulan setelah cair, disini asumsi 1 bulan setelah pengajuan untuk jadwal awal
        $tgl_mulai->modify('+1 month'); 

        for ($i = 1; $i <= $data['tenor_bulan']; $i++) {
            $jatuh_tempo = $tgl_mulai->format('Y-m-d');
            $stmt_angsuran->bind_param("iisddd", $pinjaman_id, $i, $jatuh_tempo, $pokok_bulanan, $bunga_bulanan, $total_bulanan);
            $stmt_angsuran->execute();
            $tgl_mulai->modify('+1 month');
        }

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Pengajuan pinjaman berhasil disimpan']);
    } catch (Exception $e) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function get_payoff_info($db) {
    $id = $_GET['id'] ?? 0;
    
    // Hitung sisa pokok dan bunga dari angsuran yang belum lunas
    $stmt = $db->prepare("
        SELECT 
            SUM(pokok - pokok_terbayar) as sisa_pokok,
            SUM(bunga - bunga_terbayar) as sisa_bunga,
            COUNT(*) as sisa_angsuran
        FROM ksp_angsuran 
        WHERE pinjaman_id = ? AND status != 'lunas'
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    echo json_encode(['success' => true, 'data' => $result]);
}

function pay_off($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $pinjaman_id = $data['pinjaman_id'];
    $akun_kas_id = $data['akun_kas_id'];
    $tanggal_bayar = $data['tanggal_bayar'];
    $potongan_bunga = (float)str_replace(',', '.', $data['potongan_bunga'] ?? 0);
    $keterangan = $data['keterangan'] ?? 'Pelunasan Dipercepat';
    $user_id = 1; 
    $created_by = $_SESSION['user_id'];

    $db->begin_transaction();
    try {
        // 1. Cek Status Pinjaman
        $stmt = $db->prepare("SELECT * FROM ksp_pinjaman WHERE id = ?");
        $stmt->bind_param("i", $pinjaman_id);
        $stmt->execute();
        $pinjaman = $stmt->get_result()->fetch_assoc();

        if ($pinjaman['status'] !== 'aktif') {
            throw new Exception("Pinjaman tidak aktif.");
        }

        // 2. Hitung Total Tagihan Aktual
        $stmt_rem = $db->prepare("
            SELECT 
                SUM(pokok - pokok_terbayar) as sisa_pokok,
                SUM(bunga - bunga_terbayar) as sisa_bunga
            FROM ksp_angsuran 
            WHERE pinjaman_id = ? AND status != 'lunas'
        ");
        $stmt_rem->bind_param("i", $pinjaman_id);
        $stmt_rem->execute();
        $rem = $stmt_rem->get_result()->fetch_assoc();
        
        $bayar_pokok = $rem['sisa_pokok'];
        // Bunga yang dibayar adalah sisa bunga dikurangi potongan (jika ada)
        $bayar_bunga = max(0, $rem['sisa_bunga'] - $potongan_bunga);
        $total_bayar = $bayar_pokok + $bayar_bunga;

        // 3. Update Semua Angsuran Menjadi Lunas
        $stmt_installments = $db->prepare("SELECT id, bunga, bunga_terbayar FROM ksp_angsuran WHERE pinjaman_id = ? AND status != 'lunas'");
        $stmt_installments->bind_param("i", $pinjaman_id);
        $stmt_installments->execute();
        $installments = $stmt_installments->get_result()->fetch_all(MYSQLI_ASSOC);

        $sisa_potongan = $potongan_bunga;
        $stmt_upd = $db->prepare("UPDATE ksp_angsuran SET pokok_terbayar = pokok, bunga_terbayar = ?, status = 'lunas', tanggal_bayar = ? WHERE id = ?");

        foreach ($installments as $ins) {
            $bunga_sisa_item = $ins['bunga'] - $ins['bunga_terbayar'];
            $potongan_item = 0;
            
            // Alokasikan potongan bunga ke setiap angsuran
            if ($sisa_potongan > 0) {
                $potongan_item = min($sisa_potongan, $bunga_sisa_item);
                $sisa_potongan -= $potongan_item;
            }
            
            $bunga_terbayar_new = $ins['bunga_terbayar'] + ($bunga_sisa_item - $potongan_item);
            
            $stmt_upd->bind_param("dsi", $bunga_terbayar_new, $tanggal_bayar, $ins['id']);
            $stmt_upd->execute();
        }

        // 4. Update Status Pinjaman Jadi Lunas
        $db->query("UPDATE ksp_pinjaman SET status = 'lunas' WHERE id = $pinjaman_id");

        // 5. Catat Jurnal Transaksi
        $stmt_acc = $db->prepare("SELECT akun_piutang_id, akun_pendapatan_bunga_id FROM ksp_jenis_pinjaman WHERE id = ?");
        $stmt_acc->bind_param("i", $pinjaman['jenis_pinjaman_id']);
        $stmt_acc->execute();
        $acc_ids = $stmt_acc->get_result()->fetch_assoc();

        $ref = $pinjaman['nomor_pinjaman'] . "-LUNAS";
        
        // Debit Kas
        $stmt_gl = $db->prepare("INSERT INTO general_ledger (user_id, unit, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, 'ksp', ?, ?, ?, ?, ?, 0, ?, 'transaksi', ?)");
        $stmt_gl->bind_param("isssidii", $user_id, $tanggal_bayar, $keterangan, $ref, $akun_kas_id, $total_bayar, $pinjaman_id, $created_by);
        $stmt_gl->execute();

        // Kredit Piutang & Pendapatan Bunga
        $stmt_gl_cr = $db->prepare("INSERT INTO general_ledger (user_id, unit, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, 'ksp', ?, ?, ?, ?, 0, ?, ?, 'transaksi', ?)");
        if ($bayar_pokok > 0) { $stmt_gl_cr->bind_param("isssidii", $user_id, $tanggal_bayar, $keterangan, $ref, $acc_ids['akun_piutang_id'], $bayar_pokok, $pinjaman_id, $created_by); $stmt_gl_cr->execute(); }
        if ($bayar_bunga > 0) { $stmt_gl_cr->bind_param("isssidii", $user_id, $tanggal_bayar, $keterangan, $ref, $acc_ids['akun_pendapatan_bunga_id'], $bayar_bunga, $pinjaman_id, $created_by); $stmt_gl_cr->execute(); }

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Pelunasan berhasil diproses.']);
    } catch (Exception $e) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function update_pinjaman($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $updated_by = $_SESSION['user_id'];

    $db->begin_transaction();
    try {
        // Cek status pinjaman
        $stmt_check = $db->prepare("SELECT status FROM ksp_pinjaman WHERE id = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $existing = $stmt_check->get_result()->fetch_assoc();

        if (!$existing || $existing['status'] !== 'pending') {
            throw new Exception("Hanya pinjaman dengan status 'Pending' yang dapat diedit.");
        }

        // 1. Update Header Pinjaman
        // Ambil bunga baru jika jenis pinjaman berubah
        $stmt_jenis = $db->prepare("SELECT bunga_per_tahun FROM ksp_jenis_pinjaman WHERE id = ?");
        $stmt_jenis->bind_param("i", $data['jenis_pinjaman_id']);
        $stmt_jenis->execute();
        $jenis = $stmt_jenis->get_result()->fetch_assoc();
        $bunga_persen = $jenis['bunga_per_tahun'];

        $stmt = $db->prepare("UPDATE ksp_pinjaman SET anggota_id=?, jenis_pinjaman_id=?, jumlah_pinjaman=?, bunga_per_tahun=?, tenor_bulan=?, tanggal_pengajuan=?, keterangan=?, updated_by=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("iididssii", $data['anggota_id'], $data['jenis_pinjaman_id'], $data['jumlah_pinjaman'], $bunga_persen, $data['tenor_bulan'], $data['tanggal_pengajuan'], $data['keterangan'], $updated_by, $id);
        $stmt->execute();

        // 2. Update Agunan (Hapus lama, Insert baru)
        $db->query("DELETE FROM ksp_pinjaman_agunan WHERE pinjaman_id = $id");
        if (!empty($data['tipe_agunan_id']) && !empty($data['agunan_detail']) && is_array($data['agunan_detail'])) {
            $stmt_agunan = $db->prepare("INSERT INTO ksp_pinjaman_agunan (pinjaman_id, tipe_agunan_id, detail_json) VALUES (?, ?, ?)");
            $detail_json = json_encode($data['agunan_detail']);
            $stmt_agunan->bind_param("iis", $id, $data['tipe_agunan_id'], $detail_json);
            $stmt_agunan->execute();
        }

        // 3. Regenerate Jadwal Angsuran (Hapus lama, Insert baru)
        $db->query("DELETE FROM ksp_angsuran WHERE pinjaman_id = $id");
        
        $pokok_bulanan = $data['jumlah_pinjaman'] / $data['tenor_bulan'];
        $bunga_bulanan = ($data['jumlah_pinjaman'] * ($bunga_persen / 100)) / 12;
        $total_bulanan = $pokok_bulanan + $bunga_bulanan;
        
        $stmt_angsuran = $db->prepare("INSERT INTO ksp_angsuran (pinjaman_id, angsuran_ke, tanggal_jatuh_tempo, pokok, bunga, total_angsuran) VALUES (?, ?, ?, ?, ?, ?)");
        $tgl_mulai = new DateTime($data['tanggal_pengajuan']);
        $tgl_mulai->modify('+1 month'); 

        for ($i = 1; $i <= $data['tenor_bulan']; $i++) {
            $jatuh_tempo = $tgl_mulai->format('Y-m-d');
            $stmt_angsuran->bind_param("iisddd", $id, $i, $jatuh_tempo, $pokok_bulanan, $bunga_bulanan, $total_bulanan);
            $stmt_angsuran->execute();
            $tgl_mulai->modify('+1 month');
        }

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Data pinjaman berhasil diperbarui']);
    } catch (Exception $e) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function delete_pinjaman($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID Pinjaman tidak valid.']);
        return;
    }

    $db->begin_transaction();
    try {
        // Cek status pinjaman
        $stmt_check = $db->prepare("SELECT status FROM ksp_pinjaman WHERE id = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $existing = $stmt_check->get_result()->fetch_assoc();

        if (!$existing) {
            throw new Exception("Pinjaman tidak ditemukan.");
        }
        if ($existing['status'] !== 'pending') {
            throw new Exception("Hanya pinjaman dengan status 'Pending' yang dapat dihapus.");
        }

        // Hapus dari tabel utama. Data di ksp_angsuran dan ksp_pinjaman_agunan
        // akan terhapus otomatis karena foreign key diatur dengan ON DELETE CASCADE.
        $stmt_delete = $db->prepare("DELETE FROM ksp_pinjaman WHERE id = ?");
        $stmt_delete->bind_param("i", $id);
        $stmt_delete->execute();

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Data pinjaman berhasil dihapus.']);

    } catch (Exception $e) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function approve_pinjaman($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];
    $akun_kas_id = $data['akun_kas_id']; // Akun kas untuk pencairan
    $tanggal_pencairan = $data['tanggal_pencairan'];
    $user_id = 1;
    $created_by = $_SESSION['user_id'];

    $db->begin_transaction();
    try {
        // Ambil info pinjaman
        $stmt = $db->prepare("SELECT p.*, j.akun_piutang_id, a.nama_lengkap FROM ksp_pinjaman p JOIN ksp_jenis_pinjaman j ON p.jenis_pinjaman_id = j.id JOIN anggota a ON p.anggota_id = a.id WHERE p.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $pinjaman = $stmt->get_result()->fetch_assoc();

        if ($pinjaman['status'] !== 'pending') {
            throw new Exception("Pinjaman sudah diproses.");
        }

        // Update Status Pinjaman
        $stmt_upd = $db->prepare("UPDATE ksp_pinjaman SET status = 'aktif', tanggal_pencairan = ? WHERE id = ?");
        $stmt_upd->bind_param("si", $tanggal_pencairan, $id);
        $stmt_upd->execute();

        // Update Tanggal Jatuh Tempo Angsuran berdasarkan Tanggal Pencairan
        $tgl_cair = new DateTime($tanggal_pencairan);
        $tgl_cair->modify('+1 month');
        $stmt_sch = $db->prepare("UPDATE ksp_angsuran SET tanggal_jatuh_tempo = ? WHERE pinjaman_id = ? AND angsuran_ke = ?");
        for ($i = 1; $i <= $pinjaman['tenor_bulan']; $i++) {
            $new_date = $tgl_cair->format('Y-m-d');
            $stmt_sch->bind_param("sii", $new_date, $id, $i);
            $stmt_sch->execute();
            $tgl_cair->modify('+1 month');
        }

        // Jurnal Pencairan: Debit Piutang, Kredit Kas
        $ket = "Pencairan Pinjaman " . $pinjaman['nomor_pinjaman'] . " - " . $pinjaman['nama_lengkap'];
        $ref = $pinjaman['nomor_pinjaman'];
        
        // Debit Piutang
        $stmt_gl = $db->prepare("INSERT INTO general_ledger (user_id, unit, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, 'ksp', ?, ?, ?, ?, ?, 0, ?, 'transaksi', ?)");
        $stmt_gl->bind_param("isssidii", $user_id, $tanggal_pencairan, $ket, $ref, $pinjaman['akun_piutang_id'], $pinjaman['jumlah_pinjaman'], $id, $created_by);
        $stmt_gl->execute();

        // Kredit Kas
        $stmt_gl->bind_param("isssidii", $user_id, $tanggal_pencairan, $ket, $ref, $akun_kas_id, $pinjaman['jumlah_pinjaman'], $id, $created_by); // Re-bind not ideal with diff params order, better create new query string or careful bind.
        // Correct way for re-use prepared statement with different values:
        $stmt_gl_cr = $db->prepare("INSERT INTO general_ledger (user_id, unit, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, 'ksp', ?, ?, ?, ?, 0, ?, ?, 'transaksi', ?)");
        $stmt_gl_cr->bind_param("isssidii", $user_id, $tanggal_pencairan, $ket, $ref, $akun_kas_id, $pinjaman['jumlah_pinjaman'], $id, $created_by);
        $stmt_gl_cr->execute();

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Pinjaman berhasil dicairkan']);
    } catch (Exception $e) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function pay_installment($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $initial_angsuran_id = $data['angsuran_id'];
    $akun_kas_id = $data['akun_kas_id'];
    $jumlah_dibayar_total = (float)str_replace(',', '.', $data['jumlah_dibayar']);
    $tanggal_bayar = $data['tanggal_bayar'];
    $denda = (float)str_replace(',', '.', $data['denda'] ?? 0);
    $user_id = 1;
    $created_by = $_SESSION['user_id'];

    $db->begin_transaction();
    try {
        // Get pinjaman_id and other static info from the first installment
        $stmt_info = $db->prepare("SELECT p.id as pinjaman_id, p.anggota_id, p.nomor_pinjaman, m.nama_lengkap, j.akun_piutang_id, j.akun_pendapatan_bunga_id FROM ksp_angsuran a JOIN ksp_pinjaman p ON a.pinjaman_id = p.id JOIN anggota m ON p.anggota_id = m.id JOIN ksp_jenis_pinjaman j ON p.jenis_pinjaman_id = j.id WHERE a.id = ?");
        $stmt_info->bind_param("i", $initial_angsuran_id);
        $stmt_info->execute();
        $pinjaman_info = $stmt_info->get_result()->fetch_assoc();
        if (!$pinjaman_info) throw new Exception("Data pinjaman tidak ditemukan.");
        $pinjaman_id = $pinjaman_info['pinjaman_id'];
        $stmt_info->close();

        $sisa_pembayaran = $jumlah_dibayar_total;
        $total_pokok_dibayar = 0;
        $total_bunga_dibayar = 0;

        while ($sisa_pembayaran > 0) {
            // Get next unpaid installment
            $stmt_angsuran = $db->prepare("SELECT * FROM ksp_angsuran WHERE pinjaman_id = ? AND status = 'belum_bayar' ORDER BY angsuran_ke ASC LIMIT 1");
            $stmt_angsuran->bind_param("i", $pinjaman_id);
            $stmt_angsuran->execute();
            $angsuran = $stmt_angsuran->get_result()->fetch_assoc();
            $stmt_angsuran->close();

            if (!$angsuran) break; // No more installments to pay

            $current_angsuran_id = $angsuran['id'];

            // Allocate payment
            $sisa_bunga_item = round($angsuran['bunga'] - $angsuran['bunga_terbayar'], 2);
            $sisa_pokok_item = round($angsuran['pokok'] - $angsuran['pokok_terbayar'], 2);

            $bayar_bunga_item = min($sisa_pembayaran, $sisa_bunga_item);
            $sisa_pembayaran_after_bunga = round($sisa_pembayaran - $bayar_bunga_item, 2);
            $bayar_pokok_item = min($sisa_pembayaran_after_bunga, $sisa_pokok_item);

            $sisa_pembayaran = round($sisa_pembayaran_after_bunga - $bayar_pokok_item, 2);

            $total_pokok_dibayar += $bayar_pokok_item;
            $total_bunga_dibayar += $bayar_bunga_item;

            // Update installment if payment was made to it
            if ($bayar_pokok_item > 0 || $bayar_bunga_item > 0) {
                $pokok_terbayar_baru = round($angsuran['pokok_terbayar'] + $bayar_pokok_item, 2);
                $bunga_terbayar_baru = round($angsuran['bunga_terbayar'] + $bayar_bunga_item, 2);
                
                $denda_baru = $angsuran['denda'];
                if ($current_angsuran_id == $initial_angsuran_id) {
                    $denda_baru = round($angsuran['denda'] + $denda, 2);
                }

                $is_lunas = ($pokok_terbayar_baru >= $angsuran['pokok']) && ($bunga_terbayar_baru >= $angsuran['bunga']);
                $status_baru = $is_lunas ? 'lunas' : 'belum_bayar';
                $tanggal_bayar_final = $is_lunas ? $tanggal_bayar : null;

                $stmt_upd = $db->prepare("UPDATE ksp_angsuran SET pokok_terbayar = ?, bunga_terbayar = ?, denda = ?, status = ?, tanggal_bayar = ? WHERE id = ?");
                $stmt_upd->bind_param("dddssi", $pokok_terbayar_baru, $bunga_terbayar_baru, $denda_baru, $status_baru, $tanggal_bayar_final, $current_angsuran_id);
                $stmt_upd->execute();
                $stmt_upd->close();

                // Gamifikasi: Cek pembayaran tepat waktu
                if ($is_lunas) {
                    $jatuh_tempo = new DateTime($angsuran['tanggal_jatuh_tempo']);
                    $tgl_bayar = new DateTime($tanggal_bayar);
                    if ($tgl_bayar <= $jatuh_tempo) {
                        addGamificationPoints($pinjaman_info['anggota_id'], 'bayar_tepat_waktu', 25, "Bayar angsuran ke-{$angsuran['angsuran_ke']} tepat waktu", $current_angsuran_id);
                    }
                }
            }
        }

        // Journaling
        $ket = "Pembayaran Angsuran " . $pinjaman_info['nomor_pinjaman'] . " - " . $pinjaman_info['nama_lengkap'];
        $ref = $pinjaman_info['nomor_pinjaman'] . "-PAY-" . time();
        $total_masuk_kas = $jumlah_dibayar_total + $denda;

        if ($total_masuk_kas > 0) {
            // Debit Kas
            $stmt_gl = $db->prepare("INSERT INTO general_ledger (user_id, unit, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, 'ksp', ?, ?, ?, ?, ?, 0, ?, 'transaksi', ?)");
            $stmt_gl->bind_param("isssidii", $user_id, $tanggal_bayar, $ket, $ref, $akun_kas_id, $total_masuk_kas, $initial_angsuran_id, $created_by);
            $stmt_gl->execute();
            $stmt_gl->close();
        }

        $stmt_gl_cr = $db->prepare("INSERT INTO general_ledger (user_id, unit, tanggal, keterangan, nomor_referensi, account_id, debit, kredit, ref_id, ref_type, created_by) VALUES (?, 'ksp', ?, ?, ?, ?, 0, ?, ?, 'transaksi', ?)");
        if ($total_pokok_dibayar > 0) {
            $stmt_gl_cr->bind_param("isssidii", $user_id, $tanggal_bayar, $ket, $ref, $pinjaman_info['akun_piutang_id'], $total_pokok_dibayar, $initial_angsuran_id, $created_by);
            $stmt_gl_cr->execute();
        }
        if ($total_bunga_dibayar > 0) {
            $stmt_gl_cr->bind_param("isssidii", $user_id, $tanggal_bayar, $ket, $ref, $pinjaman_info['akun_pendapatan_bunga_id'], $total_bunga_dibayar, $initial_angsuran_id, $created_by);
            $stmt_gl_cr->execute();
        }
        if ($denda > 0) {
            $akun_denda_id = 403;
            $stmt_gl_cr->bind_param("isssidii", $user_id, $tanggal_bayar, $ket, $ref, $akun_denda_id, $denda, $initial_angsuran_id, $created_by);
            $stmt_gl_cr->execute();
        }
        $stmt_gl_cr->close();

        // Cek apakah semua angsuran sudah lunas, jika ya update status pinjaman
        $stmt_check = $db->prepare("SELECT COUNT(*) as sisa FROM ksp_angsuran WHERE pinjaman_id = ? AND status != 'lunas'");
        $stmt_check->bind_param("i", $pinjaman_id);
        $stmt_check->execute();
        $sisa = $stmt_check->get_result()->fetch_assoc()['sisa'];
        
        if ($sisa == 0) {
            $db->query("UPDATE ksp_pinjaman SET status = 'lunas' WHERE id = " . $pinjaman_id);
            // Gamifikasi: Tambah poin karena lunas pinjaman
            addGamificationPoints($pinjaman_info['anggota_id'], 'lunas_pinjaman', 100, "Melunasi pinjaman #{$pinjaman_info['nomor_pinjaman']}", $pinjaman_id);
        }

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil diproses.', 'payment_ref' => $ref]);
    } catch (Exception $e) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}