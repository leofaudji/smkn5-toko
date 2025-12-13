<?php

/**
 * File ini berisi fungsi-fungsi pembantu terkait akuntansi.
 */

if (!function_exists('create_journal_entry')) {
    /**
     * Membuat entri jurnal baru di tabel 'journals'.
     *
     * @param string $tanggal Tanggal jurnal dalam format YYYY-MM-DD.
     * @param string $keterangan Deskripsi atau narasi untuk entri jurnal.
     * @param int $user_id ID pengguna yang melakukan aksi.
     * @return int ID dari jurnal yang baru saja dibuat.
     * @throws Exception Jika query database gagal.
     */
    function create_journal_entry(string $tanggal, string $keterangan, int $user_id): int
    {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("INSERT INTO jurnal_entries (tanggal, keterangan, user_id) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Gagal mempersiapkan statement untuk membuat jurnal: " . $conn->error);
        }
        $stmt->bind_param("ssi", $tanggal, $keterangan, $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Gagal mengeksekusi statement untuk membuat jurnal: " . $stmt->error);
        }
        $journal_id = $stmt->insert_id;
        $stmt->close();

        if ($journal_id == 0) {
            throw new Exception("Gagal mendapatkan ID untuk jurnal yang baru dibuat.");
        }

        return $journal_id;
    }
}

if (!function_exists('add_journal_line')) {
    /**
     * Menambahkan baris detail (debit/kredit) ke entri jurnal yang sudah ada.
     *
     * @param int $jurnal_entry_id ID jurnal induk dari tabel 'jurnal_entries'.
     * @param int $account_id ID akun yang terpengaruh.
     * @param float $debit Jumlah yang didebit (0 jika tidak ada).
     * @param float $kredit Jumlah yang dikredit (0 jika tidak ada).
     * @return void
     * @throws Exception Jika query database gagal.
     */
    function add_journal_line(int $jurnal_entry_id, int $account_id, float $debit, float $kredit): void
    {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("INSERT INTO jurnal_details (jurnal_entry_id, account_id, debit, kredit) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Gagal mempersiapkan statement untuk menambah baris jurnal: " . $conn->error);
        }
        $stmt->bind_param("iidd", $jurnal_entry_id, $account_id, $debit, $kredit);
        if (!$stmt->execute()) {
            throw new Exception("Gagal mengeksekusi statement untuk menambah baris jurnal: " . $stmt->error);
        }
        $stmt->close();
    }

    
}

/**
 * Memperbarui tabel rekapitulasi general_ledger (UPSERT).
 * Ini adalah bentuk denormalisasi untuk mempercepat query laporan.
 * PERHATIAN: Pastikan ada mekanisme untuk menjaga konsistensi data jika jurnal diedit/dihapus.
 *
 * @param mysqli $conn Koneksi database.
 * @param int $userId ID pengguna.
 * @param int $accountId ID akun.
 * @param string $tanggal Tanggal transaksi (Y-m-d).
 * @param string $keterangan Keterangan transaksi.
 * @param string|null $nomor_referensi Nomor referensi, bisa null.
 * @param int|null $ref_id ID referensi (misal: invoice_id), bisa null.
 * @param float $debit Jumlah debit.
 * @param float $kredit Jumlah kredit.
 * @return void
 * @throws Exception Jika query database gagal.
 */
function update_general_ledger($conn, int $userId, int $accountId, string $tanggal, float $debit, float $kredit, string $keterangan = '', ?string $nomor_referensi = null, ?int $ref_id = null): void {
    // Menggunakan ON DUPLICATE KEY UPDATE untuk operasi UPSERT yang efisien.
    // Ini membutuhkan UNIQUE KEY pada (user_id, account_id, tanggal).
    $sql = "INSERT INTO general_ledger (user_id, created_by, account_id, tanggal, keterangan, nomor_referensi, ref_id, debit, kredit)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) -- Kolom user_id dan created_by diisi oleh parameter $userId
            ON DUPLICATE KEY UPDATE
            debit = debit + VALUES(debit),
            kredit = kredit + VALUES(kredit),
            keterangan = CONCAT(keterangan, '; ', VALUES(keterangan)),
            nomor_referensi = CONCAT_WS(', ', nomor_referensi, VALUES(nomor_referensi))";
    
    // Pastikan ref_id tidak null jika kolom database tidak mengizinkannya. Gunakan 0 sebagai default.
    $ref_id_to_save = $ref_id ?? 0;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Gagal mempersiapkan statement untuk general ledger: " . $conn->error);
    }
    $stmt->bind_param('iiisssidd', $userId, $userId, $accountId, $tanggal, $keterangan, $nomor_referensi, $ref_id_to_save, $debit, $kredit);
    if (!$stmt->execute()) {
        throw new Exception("Gagal mengeksekusi statement untuk general ledger: " . $stmt->error);
    }
    $stmt->close();
}

if (!function_exists('get_setting')) {
    /**
     * Mengambil nilai dari sebuah pengaturan dari tabel 'settings'.
     *
     * @param string $setting_name Nama pengaturan yang ingin diambil.
     * @param mixed $default_value Nilai default yang dikembalikan jika pengaturan tidak ditemukan.
     * @return mixed Nilai dari pengaturan atau nilai default.
     */
    function get_setting(string $setting_name, $default_value = null)
    {
        // Asumsi user_id disimpan di session untuk pengaturan per-user
        if (!isset($_SESSION['user_id'])) {
            return $default_value;
        }
        $user_id = $_SESSION['user_id'];

        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_name = ? AND user_id = ?");
        $stmt->bind_param("si", $setting_name, $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result['setting_value'] ?? $default_value;
    }
}