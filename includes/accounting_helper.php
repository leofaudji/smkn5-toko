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
     * @param int $data_owner_user_id ID pengguna pemilik data (biasanya 1 untuk data bersama).
     * @param int $created_by_user_id ID pengguna yang login dan melakukan aksi.
     * @return int ID dari jurnal yang baru saja dibuat.
     * @throws Exception Jika query database gagal.
     */
    function create_journal_entry(string $tanggal, string $keterangan, int $data_owner_user_id, int $created_by_user_id): int
    {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("INSERT INTO jurnal_entries (tanggal, keterangan, user_id, created_by) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Gagal mempersiapkan statement untuk membuat jurnal: " . $conn->error);
        } 
        $stmt->bind_param("ssii", $tanggal, $keterangan, $data_owner_user_id, $created_by_user_id);
        if (!$stmt->execute()) {
            // Jika eksekusi gagal, langsung lempar error dari database.
            throw new Exception("Gagal mengeksekusi statement untuk membuat jurnal: " . $stmt->error);
        }
        $journal_id = $stmt->insert_id; // Get the ID
        if ($journal_id === 0) {
            // Kasus ini seharusnya tidak terjadi jika execute() gagal, tapi sebagai pengaman.
            // Ini bisa terjadi jika tabel tidak memiliki AUTO_INCREMENT atau INSERT berhasil tapi tidak ada baris baru.
            throw new Exception("Gagal mendapatkan ID untuk jurnal yang baru dibuat.");
        }
        $stmt->close();
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
     * @param string $key Nama pengaturan yang ingin diambil.
     * @param mixed $default Nilai default yang dikembalikan jika pengaturan tidak ditemukan.
     * @param mysqli|null $conn Optional database connection.
     * @return mixed Nilai dari pengaturan atau nilai default.
     */
    function get_setting(string $key, $default = null, $conn = null)
    {
        static $settings = null;

        if ($settings === null) {
            if ($conn === null) {
                $conn = Database::getInstance()->getConnection();
            }
            $result = $conn->query("SELECT setting_key, setting_value FROM settings");
            $settings = [];
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        return $settings[$key] ?? $default;
    }
}