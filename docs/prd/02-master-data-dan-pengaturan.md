# PRD 02: Modul Master Data, Bagan Akun (COA) & Pengaturan Sistem

| Atribut Dokumen | Informasi |
|:---|:---|
| **Modul** | Master Data, Bagan Akun (COA), Saldo Awal & Pengaturan Sistem |
| **Versi Dokumen** | 2.1.0 |
| **Tanggal Efektif** | 2026-10-02 |
| **Status** | Active / Production Standard |

---

## 1. Ringkasan Eksekutif & Tujuan

Modul ini adalah pondasi konfigurasi bisnis dari aplikasi Toko & Koperasi SMKN 5. Seluruh transaksi kasir, pembelian, simpan pinjam, dan persediaan bergantung pada konfigurasi hierarki akun (Chart of Accounts / COA), penetapan saldo awal buku, pemetaan akun otomatis (*default account mapping*), serta parameter struk kasir dan profil toko.

---

## 2. Fitur Utama

1. **Bagan Akun (Chart of Accounts / COA):**
   - Struktur hierarkis parent-child (Akun Induk dan Sub-Akun).
   - Klasifikasi 5 kategori utama: Aset, Liabilitas, Ekuitas, Pendapatan, dan Beban.
   - Penandaan Akun Kas/Bank (`is_kas = 1`) yang menentukan rekening kas yang muncul pada kasir POS dan transaksi kas.
   - Penandaan Kategori Arus Kas (`cash_flow_category`: Operasi, Investasi, Pendanaan).
2. **Saldo Awal Neraca (Opening Balance):**
   - Input saldo per tanggal *cut-off* sistem baru.
   - Validasi keseimbangan (balance check): Total Debit **wajib sama** dengan Total Kredit sebelum saldo dapat difinalisasi.
3. **Anggaran (Budgeting):**
   - Perencanaan plafon anggaran per akun beban per bulan/tahun.
   - Komparasi realisasi pengeluaran terhadap pagu anggaran untuk *early warning* over-budget.
4. **Pengaturan Sistem & Pemetaan Akun (Default Account Mapping):**
   - Identitas Koperasi: Nama, Alamat, Nomor Telepon, Logo, dan Catatan Kaki (*Receipt Footer*).
   - Prefix Nomor Transaksi otomatis: No Struk (`TRX-`), Pembelian (`PO-`), Jurnal (`JU-`), Kas Masuk/Keluar (`KM-`/`KK-`).
   - Pemetaan Akun Otomatis:
     - Akun Kas Utama
     - Akun Piutang Anggota
     - Akun Persediaan Barang Dagang
     - Akun Pendapatan Penjualan
     - Akun Harga Pokok Penjualan (HPP)
     - Akun Hutang Usaha / Supplier
     - Akun Hutang Wajib Belanja (WB)
     - Akun Pendapatan Selisih Opname & Beban Selisih Opname
     - Akun Pendapatan Komisi Konsinyasi
5. **Backup & Restore Database:**
   - Pencadangan satu klik menghasilkan file SQL terkompresi.
   - Mekanisme pemulihan data aman.

---

## 3. Spesifikasi Rute & Endpoint API

| Method | URL Path | Handler File | Hak Akses | Deskripsi |
|:---|:---|:---|:---|:---|
| `GET` | `/coa` | `pages/coa.php` | `auth` | Halaman kelola bagan akun |
| `GET` | `/api/coa` | `api/coa_handler.php` | `auth` | Mengambil daftar akun hierarki (JSON) |
| `POST` | `/api/coa` | `api/coa_handler.php` | `admin` | Tambah / Ubah / Hapus akun |
| `GET` | `/saldo-awal` | `pages/saldo_awal.php` | `auth` | Halaman input saldo awal |
| `GET` | `/api/saldo-awal` | `api/saldo_awal_handler.php` | `auth` | Ambil data saldo awal akun |
| `POST` | `/api/saldo-awal` | `api/saldo_awal_handler.php` | `admin` | Simpan saldo awal (dengan validasi balance) |
| `GET` | `/anggaran` | `pages/anggaran.php` | `auth` | Tampilan anggaran vs realisasi |
| `GET` | `/api/anggaran` | `api/anggaran_handler.php` | `auth` | Data JSON pagu anggaran & realisasi |
| `POST` | `/api/anggaran` | `api/anggaran_handler.php` | `admin` | Simpan / perbarui pagu anggaran akun |
| `GET` | `/settings` | `pages/settings.php` | `admin` | Halaman pengaturan toko & pemetaan akun |
| `GET` | `/api/settings` | `api/settings_handler.php` | `auth` | Mengambil konfigurasi setting publik |
| `POST` | `/api/settings` | `api/settings_handler.php` | `admin` | Simpan pengaturan identitas & mapping akun |
| `POST` | `/api/backup-restore` | `api/backup_restore.php` | `admin` | Eksekusi dump database / upload restore |

---

## 4. Struktur Basis Data (Data Model)

### 4.1. Tabel `accounts` (COA)
```sql
CREATE TABLE `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `kode_akun` varchar(20) NOT NULL,
  `nama_akun` varchar(100) NOT NULL,
  `tipe_akun` enum('Aset','Liabilitas','Ekuitas','Pendapatan','Beban') NOT NULL,
  `saldo_normal` enum('Debit','Kredit') NOT NULL,
  `cash_flow_category` enum('Operasi','Investasi','Pendanaan') DEFAULT NULL,
  `is_kas` tinyint(1) NOT NULL DEFAULT 0,
  `saldo_awal` decimal(15,2) NOT NULL DEFAULT 0.00,  
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_kode_akun` (`user_id`,`kode_akun`),
  KEY `parent_id` (`parent_id`),
  FOREIGN KEY (`parent_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4.2. Tabel `settings`
```sql
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_setting_key` (`user_id`,`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4.3. Tabel `anggaran`
```sql
CREATE TABLE `anggaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `tahun` int(4) NOT NULL,
  `bulan` int(2) NOT NULL,
  `jumlah_anggaran` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_account_period` (`account_id`,`tahun`,`bulan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5. Validasi & Integritas Bisnis

1. **Perlindungan Akun Berkait (Integrity Constraint):** Akun COA yang telah memiliki riwayat transaksi di `jurnal_details`, `general_ledger`, atau terdaftar sebagai akun pemetaan sistem tidak dapat dihapus.
2. **Keseimbangan Saldo Awal:** Saat menyimpan Saldo Awal, sistem melakukan verifikasi:
   $$\sum \text{Debit Saldo Awal} - \sum \text{Kredit Saldo Awal} = 0$$
   Jika selisih tidak nol, sistem menampilkan peringatan dan mencegah penyimpanan.
3. **Sentralisasi Struk Kasir:** Perubahan nama toko, alamat, dan pesan footer pada menu Pengaturan langsung berefleksi secara real-time pada template struk cetak [api/laporan_cetak_handler.php](file:///d:/laragon/www/smkn5-toko/api/laporan_cetak_handler.php) tanpa perlu compile ulang.
