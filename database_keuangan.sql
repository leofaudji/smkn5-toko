SET FOREIGN_KEY_CHECKS = 0;

-- Hapus tabel lama jika ada
DROP TABLE IF EXISTS `transaksi`, `anggaran`, `users`, `settings`, `accounts`, `activity_log`,`jurnal_entries`,`jurnal_details`,`general_ledger`, `suppliers`, `consignment_items`,`recurring_templates`, `reconciliations`, `fixed_assets`, `customers`, `invoices`, `invoice_items`, `payments_received`;

SET FOREIGN_KEY_CHECKS = 1;

-- Tabel untuk pengguna aplikasi
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Master Chart of Accounts (COA)
CREATE TABLE `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `kode_akun` varchar(20) NOT NULL,
  `nama_akun` varchar(100) NOT NULL,
  `tipe_akun` enum('Aset','Liabilitas','Ekuitas','Pendapatan','Beban') NOT NULL,
  `saldo_normal` enum('Debit','Kredit') NOT NULL,
  `cash_flow_category` enum('Operasi','Investasi','Pendanaan') DEFAULT NULL,
  `is_kas` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag jika ini adalah akun kas/bank',
  `saldo_awal` decimal(15,2) NOT NULL DEFAULT 0.00,  
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_kode_akun` (`user_id`,`kode_akun`),
  KEY `parent_id` (`parent_id`),
  FOREIGN KEY (`parent_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel utama untuk semua transaksi
CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL COMMENT 'Akun yang didebit/dikredit (Pendapatan/Beban/Utang)',
  `tanggal` date NOT NULL,
  `jenis` enum('pemasukan','pengeluaran','transfer') NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `keterangan` text DEFAULT NULL,  
  `nomor_referensi` varchar(50) DEFAULT NULL COMMENT 'Nomor faktur/transaksi',
  `kas_account_id` int(11) NOT NULL COMMENT 'Akun kas/bank yang terpengaruh',
  `kas_tujuan_account_id` int(11) DEFAULT NULL COMMENT 'Untuk transfer antar akun kas',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `status` enum('aktif','dibatalkan') NOT NULL DEFAULT 'aktif',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`kas_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`kas_tujuan_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Buku Besar Umum (General Ledger) - PUSAT DATA AKUNTANSI
CREATE TABLE `general_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL COMMENT 'Quantity for consignment sales',
  `consignment_item_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `nomor_referensi` varchar(50) DEFAULT NULL,
  `account_id` int(11) NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `kredit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `ref_id` int(11) NOT NULL COMMENT 'ID dari tabel sumber (transaksi atau jurnal_entries)',
  `ref_type` enum('transaksi','jurnal') NOT NULL COMMENT 'Tabel sumber',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `account_id` (`account_id`),
  KEY `ref_id_type` (`ref_id`,`ref_type`),
  KEY `tanggal` (`tanggal`),
  KEY `consignment_item_id` (`consignment_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Pemasok (untuk konsinyasi)
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nama_pemasok` varchar(100) NOT NULL,
  `kontak` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),  
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Barang Konsinyasi
CREATE TABLE `consignment_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `harga_jual` decimal(15,2) NOT NULL,
  `harga_beli` decimal(15,2) NOT NULL COMMENT 'Harga yang harus dibayar ke pemasok',
  `stok_awal` int(11) NOT NULL DEFAULT 0,
  `tanggal_terima` date NOT NULL,  
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk header entri jurnal umum (majemuk)
CREATE TABLE `jurnal_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `status` enum('aktif','dibatalkan') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk template transaksi/jurnal berulang
CREATE TABLE `recurring_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL COMMENT 'Nama template, cth: "Sewa Kantor Bulanan"',
  `frequency_unit` enum('day','week','month','year') NOT NULL,
  `frequency_interval` int(11) NOT NULL DEFAULT 1 COMMENT 'cth: 1 bulan, 2 minggu',
  `start_date` date NOT NULL,
  `next_run_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `template_type` enum('transaksi','jurnal') NOT NULL,
  `template_data` json NOT NULL COMMENT 'Data JSON dari transaksi/jurnal yang akan dibuat',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `next_run_date` (`next_run_date`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk menyimpan header setiap event rekonsiliasi
CREATE TABLE `reconciliations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `statement_date` date NOT NULL COMMENT 'Tanggal akhir periode rekonsiliasi',
  `statement_balance` decimal(15,2) NOT NULL COMMENT 'Saldo akhir dari rekening koran',
  `cleared_balance` decimal(15,2) NOT NULL COMMENT 'Saldo buku setelah transaksi yang cocok dibersihkan',
  `difference` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk detail/baris entri jurnal umum
CREATE TABLE `jurnal_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jurnal_entry_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `kredit` decimal(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`jurnal_entry_id`) REFERENCES `jurnal_entries` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk anggaran
CREATE TABLE `anggaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `periode_tahun` smallint(4) NOT NULL,
  `jumlah_anggaran` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_account_periode` (`user_id`,`account_id`,`periode_tahun`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Aset Tetap
CREATE TABLE `fixed_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nama_aset` varchar(150) NOT NULL,
  `tanggal_akuisisi` date NOT NULL,
  `harga_perolehan` decimal(15,2) NOT NULL,
  `nilai_residu` decimal(15,2) NOT NULL DEFAULT 0.00,
  `masa_manfaat` int(11) NOT NULL COMMENT 'Dalam tahun',
  `metode_penyusutan` enum('Garis Lurus','Saldo Menurun') NOT NULL DEFAULT 'Garis Lurus',
  `akun_aset_id` int(11) NOT NULL,
  `akun_akumulasi_penyusutan_id` int(11) NOT NULL,
  `akun_beban_penyusutan_id` int(11) NOT NULL,
  `status` enum('Aktif','Dilepas') NOT NULL DEFAULT 'Aktif',
  `tanggal_pelepasan` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`akun_aset_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`akun_akumulasi_penyusutan_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`akun_beban_penyusutan_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk pengaturan
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk log aktivitas
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Data Awal
INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`) VALUES (1, 'admin', '{$default_password_hash}', 'Administrator', 'admin'), (2, 'user', '{$default_password_hash}', 'User Biasa', 'user');

-- Data Demo Bagan Akun (COA) untuk user 'admin' (user_id = 1)
-- Saldo awal neraca sudah diatur di sini.
-- CONTOH UNTUK KOPERASI TOKO SEKOLAH
INSERT INTO `accounts` (`id`, `user_id`, `parent_id`, `kode_akun`, `nama_akun`, `tipe_akun`, `saldo_normal`, `cash_flow_category`, `is_kas`, `saldo_awal`) VALUES
-- Aset
(100, 1, NULL, '1', 'Aset', 'Aset', 'Debit', NULL, 0, 0.00),
    (101, 1, 100, '1-1000', 'Aset Lancar', 'Aset', 'Debit', NULL, 0, 0.00),
        (102, 1, 101, '1-1100', 'Kas dan Setara Kas', 'Aset', 'Debit', NULL, 0, 0.00),
            (103, 1, 102, '1-1110', 'Kas di Tangan', 'Aset', 'Debit', NULL, 1, 2000000.00),
            (104, 1, 102, '1-1120', 'Kas di Bank', 'Aset', 'Debit', NULL, 1, 10000000.00),
        (105, 1, 101, '1-1200', 'Persediaan Barang Dagang', 'Aset', 'Debit', 'Operasi', 0, 15000000.00),
    (106, 1, 100, '1-2000', 'Aset Tetap', 'Aset', 'Debit', 'Investasi', 0, 0.00),
        (107, 1, 106, '1-2100', 'Peralatan Toko', 'Aset', 'Debit', 'Investasi', 0, 5000000.00),

-- Liabilitas
(200, 1, NULL, '2', 'Liabilitas', 'Liabilitas', 'Kredit', NULL, 0, 0.00),
    (201, 1, 200, '2-1000', 'Liabilitas Jangka Pendek', 'Liabilitas', 'Kredit', NULL, 0, 0.00),
        (202, 1, 201, '2-1100', 'Utang Dagang', 'Liabilitas', 'Kredit', 'Operasi', 0, 3000000.00),

-- Ekuitas
(300, 1, NULL, '3', 'Ekuitas', 'Ekuitas', 'Kredit', NULL, 0, 0.00),
    (301, 1, 300, '3-1100', 'Simpanan Pokok Anggota', 'Ekuitas', 'Kredit', 'Pendanaan', 0, 20000000.00),
    (302, 1, 300, '3-1200', 'Simpanan Sukarela Anggota', 'Ekuitas', 'Kredit', 'Pendanaan', 0, 0.00),
    (303, 1, 300, '3-2100', 'SHU Ditahan', 'Ekuitas', 'Kredit', NULL, 0, 9000000.00),

-- Pendapatan
(400, 1, NULL, '4', 'Pendapatan', 'Pendapatan', 'Kredit', NULL, 0, 0.00),
    (401, 1, 400, '4-1000', 'Pendapatan Penjualan Barang', 'Pendapatan', 'Kredit', 'Operasi', 0, 0.00),
    (402, 1, 400, '4-2000', 'Pendapatan Konsinyasi', 'Pendapatan', 'Kredit', 'Operasi', 0, 0.00),

-- Beban Pokok Penjualan (COGS)
(500, 1, NULL, '5', 'Beban Pokok Penjualan', 'Beban', 'Debit', 'Operasi', 0, 0.00),

-- Beban Operasional
(600, 1, NULL, '6', 'Beban Operasional', 'Beban', 'Debit', NULL, 0, 0.00),
    (601, 1, 600, '6-1100', 'Beban Gaji Karyawan', 'Beban', 'Debit', 'Operasi', 0, 0.00),
    (602, 1, 600, '6-1200', 'Beban Listrik & Air', 'Beban', 'Debit', 'Operasi', 0, 0.00),
    (603, 1, 600, '6-1300', 'Beban Perlengkapan Toko', 'Beban', 'Debit', 'Operasi', 0, 0.00),
    (108, 1, 106, '1-2101', 'Akum. Penyusutan - Peralatan', 'Aset', 'Kredit', NULL, 0, 0.00),
    (604, 1, 600, '6-1400', 'Beban Penyusutan - Peralatan', 'Beban', 'Debit', 'Operasi', 0, 0.00),
    (403, 1, 400, '4-9000', 'Laba Pelepasan Aset', 'Pendapatan', 'Kredit', 'Operasi', 0, 0.00),
    (605, 1, 600, '6-9000', 'Rugi Pelepasan Aset', 'Beban', 'Debit', 'Operasi', 0, 0.00);

-- Data Demo Transaksi
-- Transaksi Sederhana (Pemasukan & Pengeluaran Kas)
INSERT INTO `transaksi` (`user_id`, `tanggal`, `jenis`, `jumlah`, `keterangan`, `nomor_referensi`, `account_id`, `kas_account_id`, `kas_tujuan_account_id`) VALUES
-- JAN
(1, CONCAT(YEAR(CURDATE()), '-01-15'), 'pemasukan', 7500000.00, 'Penjualan tunai ATK dan seragam', 'INV/2024/01/001', 401, 103, NULL),
(1, CONCAT(YEAR(CURDATE()), '-01-28'), 'pengeluaran', 250000.00, 'Pembayaran listrik dan air Januari', 'BILL/2024/01/01', 602, 103, NULL),
-- FEB
(1, CONCAT(YEAR(CURDATE()), '-02-10'), 'pengeluaran', 2000000.00, 'Pembayaran sebagian utang ke Supplier Buku', 'PAY/2024/02/01', 202, 104, NULL),
(1, CONCAT(YEAR(CURDATE()), '-02-20'), 'pemasukan', 8200000.00, 'Penjualan tunai Februari', 'INV/2024/02/001', 401, 103, NULL),
-- MAR
(1, CONCAT(YEAR(CURDATE()), '-03-25'), 'pengeluaran', 1500000.00, 'Gaji karyawan toko Maret', 'PAY/2024/03/02', 601, 104, NULL),
-- APR
(1, CONCAT(YEAR(CURDATE()), '-04-18'), 'pemasukan', 9500000.00, 'Penjualan tunai April', 'INV/2024/04/001', 401, 103, NULL),
-- MEI
(1, CONCAT(YEAR(CURDATE()), '-05-05'), 'pengeluaran', 150000.00, 'Pembelian perlengkapan toko (kantong plastik, dll)', 'EXP/2024/05/01', 603, 103, NULL),
-- JUN
(1, CONCAT(YEAR(CURDATE()), '-06-25'), 'pengeluaran', 1500000.00, 'Gaji karyawan toko Juni', 'PAY/2024/06/01', 601, 104, NULL),
-- JUL (Tahun Ajaran Baru)
(1, CONCAT(YEAR(CURDATE()), '-07-15'), 'pemasukan', 25000000.00, 'Penjualan buku dan seragam tahun ajaran baru', 'INV/2024/07/001', 401, 104, NULL),
-- AGU
(1, CONCAT(YEAR(CURDATE()), '-08-10'), 'pemasukan', 11000000.00, 'Penjualan tunai Agustus', 'INV/2024/08/001', 401, 103, NULL),
-- SEP
(1, CONCAT(YEAR(CURDATE()), '-09-25'), 'pengeluaran', 1500000.00, 'Gaji karyawan toko September', 'PAY/2024/09/01', 601, 104, NULL);

-- Transaksi Majemuk (Jurnal Umum)
INSERT INTO `jurnal_entries` (`id`, `user_id`, `tanggal`, `keterangan`) VALUES
(101, 1, CONCAT(YEAR(CURDATE()), '-01-10'), 'Pembelian barang dagang (buku tulis) dari Supplier A secara kredit'),
(102, 1, CONCAT(YEAR(CURDATE()), '-01-15'), 'Pencatatan HPP atas penjualan tunai Januari'),
(103, 1, CONCAT(YEAR(CURDATE()), '-02-20'), 'Pencatatan HPP atas penjualan tunai Februari'),
(104, 1, CONCAT(YEAR(CURDATE()), '-04-05'), 'Pembelian barang dagang (seragam) dari Supplier B secara kredit'),
(105, 1, CONCAT(YEAR(CURDATE()), '-04-18'), 'Pencatatan HPP atas penjualan tunai April'),
(106, 1, CONCAT(YEAR(CURDATE()), '-07-15'), 'Pencatatan HPP atas penjualan tahun ajaran baru'),
(107, 1, CONCAT(YEAR(CURDATE()), '-08-10'), 'Pencatatan HPP atas penjualan tunai Agustus'),
(108, 1, CONCAT(YEAR(CURDATE()), '-03-01'), 'Pembelian Komputer Baru untuk Kantor');

INSERT INTO `jurnal_details` (`jurnal_entry_id`, `account_id`, `debit`, `kredit`) VALUES
-- Jurnal 101: Beli persediaan kredit
(101, 105, 5000000.00, 0.00), -- (Db) Persediaan Barang Dagang
(101, 202, 0.00, 5000000.00), -- (Cr) Utang Dagang
-- Jurnal 102: HPP Januari (asumsi 60% dari penjualan 7.5jt)
(102, 500, 4500000.00, 0.00), -- (Db) Beban Pokok Penjualan
(102, 105, 0.00, 4500000.00), -- (Cr) Persediaan Barang Dagang
-- Jurnal 103: HPP Februari (asumsi 60% dari penjualan 8.2jt)
(103, 500, 4920000.00, 0.00), -- (Db) Beban Pokok Penjualan
(103, 105, 0.00, 4920000.00), -- (Cr) Persediaan Barang Dagang
-- Jurnal 104: Beli persediaan kredit
(104, 105, 10000000.00, 0.00), -- (Db) Persediaan Barang Dagang
(104, 202, 0.00, 10000000.00), -- (Cr) Utang Dagang
-- Jurnal 105: HPP April (asumsi 60% dari penjualan 9.5jt)
(105, 500, 5700000.00, 0.00), -- (Db) Beban Pokok Penjualan
(105, 105, 0.00, 5700000.00), -- (Cr) Persediaan Barang Dagang
-- Jurnal 106: HPP Juli (asumsi 60% dari penjualan 25jt)
(106, 500, 15000000.00, 0.00), -- (Db) Beban Pokok Penjualan
(106, 105, 0.00, 15000000.00), -- (Cr) Persediaan Barang Dagang
-- Jurnal 107: HPP Agustus (asumsi 60% dari penjualan 11jt)
(107, 500, 6600000.00, 0.00), -- (Db) Beban Pokok Penjualan
(107, 105, 0.00, 6600000.00), -- (Cr) Persediaan Barang Dagang
-- Jurnal 108: Beli Aset
(108, 107, 7000000.00, 0.00), -- (Db) Peralatan Toko
(108, 104, 0.00, 7000000.00); -- (Cr) Kas di Bank

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('app_name', 'UangKu'),
('notification_interval', '60000'),
('retained_earnings_account_id', '303'),
('period_lock_date', CONCAT(YEAR(CURDATE())-1, '-12-31')),
('consignment_cash_account', '103'),
('consignment_revenue_account', '402'),
('consignment_cogs_account', '501'),
('consignment_payable_account', '203');

-- Data Demo Konsinyasi
INSERT INTO `suppliers` (`id`, `user_id`, `nama_pemasok`, `kontak`) VALUES
(1, 1, 'Penerbit Erlangga', '021-8717006'),
(2, 1, 'CV. Seragam Jaya', '08123456789');

INSERT INTO `consignment_items` (`id`, `user_id`, `supplier_id`, `nama_barang`, `harga_jual`, `harga_beli`, `stok_awal`, `tanggal_terima`) VALUES
(1, 1, 1, 'Buku Tulis Sinar Dunia 38 Lbr', 3500.00, 2500.00, 100, CONCAT(YEAR(CURDATE()), '-01-05')),
(2, 1, 2, 'Seragam SD Merah Putih', 75000.00, 60000.00, 50, CONCAT(YEAR(CURDATE()), '-01-05'));

-- Data Demo Anggaran
INSERT INTO `anggaran` (`user_id`, `account_id`, `periode_tahun`, `jumlah_anggaran`) VALUES
(1, 601, YEAR(CURDATE()), 18000000.00), -- Gaji: 1.5jt/bulan * 12
(1, 602, YEAR(CURDATE()), 3600000.00),  -- Listrik: 300rb/bulan * 12
(1, 603, YEAR(CURDATE()), 1200000.00); -- Perlengkapan: 100rb/bulan * 12

-- Data Demo Transaksi Berulang
INSERT INTO `recurring_templates` (`user_id`, `name`, `frequency_unit`, `frequency_interval`, `start_date`, `next_run_date`, `template_type`, `template_data`) VALUES
(1, 'Beban Sewa Toko Bulanan', 'month', 1, CONCAT(YEAR(CURDATE()), '-01-25'), CONCAT(YEAR(CURDATE()), '-01-25'), 'jurnal', '{"keterangan": "Pembayaran sewa toko bulanan", "lines": [{"account_id": "605", "debit": 500000, "kredit": 0}, {"account_id": "104", "debit": 0, "kredit": 500000}]}');

ALTER TABLE `general_ledger`
ADD COLUMN `is_reconciled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=Belum, 1=Sudah direkonsiliasi',
ADD COLUMN `reconciliation_date` DATE DEFAULT NULL COMMENT 'Tanggal proses rekonsiliasi dilakukan';
ALTER TABLE `general_ledger` ADD COLUMN `reconciliation_id` INT(11) DEFAULT NULL AFTER `reconciliation_date`;
ALTER TABLE `general_ledger` ADD KEY `idx_reconciliation_id` (`reconciliation_id`);

-- Tambahkan index untuk mempercepat query
CREATE INDEX `idx_reconciliation` ON `general_ledger` (`account_id`, `is_reconciled`, `tanggal`);

-- =================================================================
-- Tabel 1: pembelian (Header Transaksi Pembelian)
-- Tabel ini menyimpan informasi utama dari setiap transaksi pembelian.
-- =================================================================
CREATE TABLE `pembelian` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'FK ke tabel users, pemilik data',
  `supplier_id` int(11) DEFAULT NULL COMMENT 'FK ke tabel suppliers, pemasok barang/jasa',
  `nomor_referensi` varchar(50) DEFAULT NULL COMMENT 'Nomor unik untuk pembelian, bisa otomatis atau manual',
  `tanggal_pembelian` date NOT NULL COMMENT 'Tanggal terjadinya pembelian',
  `jatuh_tempo` date DEFAULT NULL COMMENT 'Tanggal jatuh tempo pembayaran (jika kredit)',
  `total` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total nilai pembelian',
  `keterangan` text DEFAULT NULL COMMENT 'Deskripsi umum pembelian',
  `status` enum('draft','open','paid','void') NOT NULL DEFAULT 'open' COMMENT 'Status pembelian: open (utang), paid (lunas)',
  `payment_method` enum('credit','cash') NOT NULL DEFAULT 'credit' COMMENT 'Metode pembayaran: kredit (utang) atau cash (tunai)',
  `credit_account_id` int(11) NOT NULL COMMENT 'Akun yang dikredit (Utang Usaha jika kredit, atau Akun Kas/Bank jika tunai)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'FK ke tabel users, siapa yang membuat',
  `updated_by` int(11) DEFAULT NULL COMMENT 'FK ke tabel users, siapa yang mengubah',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `credit_account_id` (`credit_account_id`),
  UNIQUE KEY `nomor_referensi_user` (`user_id`, `nomor_referensi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =================================================================
-- Tabel 2: pembelian_details (Detail Item Pembelian)
-- Tabel ini menyimpan rincian dari setiap transaksi pembelian.
-- Satu pembelian bisa memiliki banyak detail (misal: beli ATK dan bayar listrik).
-- =================================================================
CREATE TABLE `pembelian_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pembelian_id` int(11) NOT NULL COMMENT 'FK ke tabel pembelian',
  `account_id` int(11) NOT NULL COMMENT 'Akun yang didebit (Beban atau Aset)',
  `deskripsi` varchar(255) DEFAULT NULL COMMENT 'Deskripsi spesifik untuk baris ini',
  `jumlah` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Jumlah untuk baris ini',
  PRIMARY KEY (`id`),
  KEY `pembelian_id` (`pembelian_id`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =================================================================
-- Menambahkan Foreign Key Constraints
-- Ini untuk menjaga integritas data antar tabel.
-- =================================================================
ALTER TABLE `pembelian`
  ADD CONSTRAINT `pembelian_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pembelian_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pembelian_ibfk_3` FOREIGN KEY (`credit_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `pembelian_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pembelian_ibfk_5` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `pembelian_details`
  ADD CONSTRAINT `pembelian_details_ibfk_1` FOREIGN KEY (`pembelian_id`) REFERENCES `pembelian` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pembelian_details_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT;

CREATE TABLE `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga_beli` decimal(15,2) NOT NULL DEFAULT 0.00,
  `harga_jual` decimal(15,2) NOT NULL DEFAULT 0.00,
  `stok` int(11) NOT NULL DEFAULT 0,
  `inventory_account_id` int(11) DEFAULT NULL COMMENT 'Akun Persediaan (Aset)',
  `cogs_account_id` int(11) DEFAULT NULL COMMENT 'Akun HPP (Beban)',
  `revenue_account_id` int(11) DEFAULT NULL COMMENT 'Akun Pendapatan Penjualan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku_user` (`user_id`,`sku`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Tabel baru untuk kategori barang
CREATE TABLE `item_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_kategori` (`user_id`, `nama_kategori`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `items` ADD `category_id` INT(11) NULL DEFAULT NULL AFTER `sku`;
ALTER TABLE `items` ADD CONSTRAINT `items_fk_category` FOREIGN KEY (`category_id`) REFERENCES `item_categories` (`id`) ON DELETE SET NULL;

-- Hapus foreign key lama yang merujuk ke accounts
ALTER TABLE `pembelian_details` DROP FOREIGN KEY `pembelian_details_ibfk_2`;

-- Ubah struktur tabel
ALTER TABLE `pembelian_details`
ADD COLUMN `item_id` INT(11) NULL AFTER `pembelian_id`,
ADD COLUMN `quantity` INT(11) NOT NULL DEFAULT 1 AFTER `item_id`,
ADD COLUMN `price` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `quantity`,
CHANGE COLUMN `jumlah` `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Subtotal (qty * price)',
CHANGE COLUMN `deskripsi` `deskripsi` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Deskripsi tidak lagi dipakai, tapi dipertahankan untuk data lama';

-- Tambahkan foreign key baru ke tabel items
ALTER TABLE `pembelian_details`
ADD CONSTRAINT `pembelian_details_fk_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL;

  CREATE TABLE `stock_adjustments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `item_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `journal_id` int(11) DEFAULT NULL,
    `tanggal` date NOT NULL,
    `stok_sebelum` int(11) NOT NULL,
    `stok_setelah` int(11) NOT NULL,
    `selisih_kuantitas` int(11) NOT NULL,
    `selisih_nilai` decimal(15,2) NOT NULL,
    `keterangan` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `item_id` (`item_id`),
    KEY `user_id` (`user_id`),
    KEY `journal_id` (`journal_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ALTER TABLE general_ledger
-- ADD UNIQUE INDEX `idx_user_account_date` (`user_id`, `account_id`, `tanggal`);

-- Memperbaiki tabel entri jurnal (jika belum)
ALTER TABLE `jurnal_entries` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Memperbaiki tabel detail jurnal
ALTER TABLE `jurnal_details` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Memperbaiki tabel barang (items)
ALTER TABLE `items` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Memperbaiki tabel riwayat penyesuaian stok
ALTER TABLE `stock_adjustments` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Memperbaiki tabel buku besar (general ledger)
-- ALTER TABLE `general_ledger` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Pastikan kolom 'id' di tabel 'pembelian' adalah PRIMARY KEY dan AUTO_INCREMENT
-- ALTER TABLE `pembelian` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- Pastikan kolom 'id' di tabel 'pembelian_details' adalah PRIMARY KEY dan AUTO_INCREMENT
-- ALTER TABLE `pembelian_details` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- =================================================================
-- Tabel 1: penjualan (Header Transaksi Penjualan)
-- Menyeragamkan penamaan kolom dengan tabel `pembelian`.
-- =================================================================
CREATE TABLE `penjualan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'FK ke tabel users, pemilik data',
  `customer_id` int(11) DEFAULT NULL COMMENT 'FK ke tabel customers (opsional)',
  `nomor_referensi` varchar(50) NOT NULL COMMENT 'Nomor unik untuk penjualan, misal: INV/20240101/0001',
  `tanggal_penjualan` datetime NOT NULL COMMENT 'Tanggal dan waktu terjadinya penjualan',
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total sebelum diskon',
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total diskon keseluruhan',
  `total` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total nilai penjualan',
  `bayar` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Jumlah uang yang dibayarkan customer',
  `kembali` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Jumlah uang kembalian',
  `keterangan` text DEFAULT NULL COMMENT 'Catatan atau deskripsi umum penjualan',
  `status` enum('completed','void') NOT NULL DEFAULT 'completed' COMMENT 'Status transaksi: completed, void (dibatalkan)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'FK ke tabel users, siapa yang membuat',
  `updated_by` int(11) DEFAULT NULL COMMENT 'FK ke tabel users, siapa yang mengubah',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor_referensi_user` (`user_id`, `nomor_referensi`),
  KEY `user_id` (`user_id`),
  KEY `customer_id` (`customer_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =================================================================
-- Tabel 2: penjualan_details (Detail Item Penjualan)
-- Menyeragamkan penamaan kolom dengan tabel `pembelian_details`.
-- =================================================================
CREATE TABLE `penjualan_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `penjualan_id` int(11) NOT NULL COMMENT 'FK ke tabel penjualan',
  `item_id` int(11) NOT NULL COMMENT 'FK ke tabel items',
  `deskripsi_item` varchar(255) NOT NULL COMMENT 'Nama/deskripsi item saat transaksi',
  `quantity` int(11) NOT NULL COMMENT 'Jumlah item yang dijual',
  `price` decimal(15,2) NOT NULL COMMENT 'Harga jual satuan saat transaksi',
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Diskon per item',
  `subtotal` decimal(15,2) NOT NULL COMMENT 'Subtotal (quantity * price)',
  PRIMARY KEY (`id`),
  KEY `penjualan_id` (`penjualan_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =================================================================
-- Menambahkan Foreign Key Constraints
-- =================================================================
ALTER TABLE `penjualan`
  ADD CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  -- Asumsi ada tabel `customers`, jika tidak ada, baris ini bisa di-skip
  -- ADD CONSTRAINT `penjualan_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `penjualan_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `penjualan_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `penjualan_details`
  ADD CONSTRAINT `penjualan_details_ibfk_1` FOREIGN KEY (`penjualan_id`) REFERENCES `penjualan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `penjualan_details_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT;

ALTER TABLE `penjualan` ADD `customer_name` VARCHAR(255) NULL DEFAULT 'Umum' AFTER `tanggal_penjualan`;

CREATE TABLE `kartu_stok` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `tanggal` datetime NOT NULL,
  `debit` int(11) NOT NULL DEFAULT 0,
  `kredit` int(11) NOT NULL DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `ref_id` int(11) DEFAULT NULL COMMENT 'ID referensi transaksi (misal: id penjualan)',
  `source` varchar(50) DEFAULT NULL COMMENT 'Sumber transaksi (misal: penjualan, pembelian)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_kartu_stok_user` (`user_id`),
  KEY `idx_kartu_stok_item` (`item_id`),
  KEY `idx_kartu_stok_tanggal` (`tanggal`),
  CONSTRAINT `fk_kartu_stok_items` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_kartu_stok_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Sistem Role dan Permission
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,        -- Contoh: 'Admin', 'Kasir', 'Manager'
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,        -- Contoh: 'transaksi.create', 'laporan.view'
  `name` varchar(100) NOT NULL,       -- Contoh: 'Membuat Transaksi Baru'
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tambah kolom role_id
ALTER TABLE `users` ADD COLUMN `role_id` int(11) DEFAULT NULL AFTER `password`;

-- Tambah Foreign Key
ALTER TABLE `users` ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

-- 1. Buat Role
INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'Admin', 'Administrator dengan akses penuh'),
(2, 'Kasir', 'Staff yang menangani transaksi harian'),
(3, 'Manager', 'Melihat laporan dan analisis');

-- 2. Buat Permissions
INSERT INTO `permissions` (`id`, `slug`, `name`) VALUES
(1, 'transaksi.create', 'Membuat Transaksi'),
(2, 'transaksi.view', 'Melihat Riwayat Transaksi'),
(3, 'laporan.view', 'Melihat Laporan Keuangan'),
(4, 'settings.manage', 'Mengelola Pengaturan Aplikasi'),
(5, 'users.manage', 'Mengelola Pengguna');

-- 3. Hubungkan Role dengan Permission
-- Admin (ID 1) punya semua akses
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES 
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5);

-- Kasir (ID 2) hanya bisa transaksi
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES 
(2, 1), (2, 2);

-- Manager (ID 3) hanya bisa lihat laporan & transaksi
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES 
(3, 2), (3, 3);

-- Set role_id for default users to match the new system
UPDATE `users` SET `role_id` = 1 WHERE `username` = 'admin';

-- Menambahkan permission untuk setiap menu/grup menu
INSERT INTO `permissions` (`slug`, `name`, `description`) VALUES
('menu.view.transaksi', 'Lihat Menu: Transaksi', 'Memberi akses untuk melihat grup menu Transaksi'),
('menu.view.akuntansi', 'Lihat Menu: Akuntansi', 'Memberi akses untuk melihat grup menu Akuntansi'),
('menu.view.stok', 'Lihat Menu: Stok & Inventaris', 'Memberi akses untuk melihat grup menu Stok & Inventaris'),
('menu.view.laporan', 'Lihat Menu: Laporan', 'Memberi akses untuk melihat grup menu Laporan'),
('menu.view.tools', 'Lihat Menu: Alat & Proses', 'Memberi akses untuk melihat grup menu Alat & Proses'),
('menu.view.administrasi', 'Lihat Menu: Administrasi', 'Memberi akses untuk melihat grup menu Administrasi'),
('menu.view.users', 'Lihat Sub-Menu: Users', 'Memberi akses untuk melihat sub-menu Users'),
('menu.view.roles', 'Lihat Sub-Menu: Roles', 'Memberi akses untuk melihat sub-menu Manajemen Role'),
('menu.view.activity-log', 'Lihat Sub-Menu: Log Aktivitas', 'Memberi akses untuk melihat sub-menu Log Aktivitas'),
('menu.view.tutup-buku', 'Lihat Sub-Menu: Tutup Buku', 'Memberi akses untuk melihat sub-menu Tutup Buku'),
('menu.view.settings', 'Lihat Sub-Menu: Pengaturan', 'Memberi akses untuk melihat sub-menu Pengaturan');

-- Query ini mengasumsikan ID role 'Admin' adalah 1 dan ID permission baru dimulai dari 6.
-- Sesuaikan jika ID-nya berbeda.
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 6), (1, 7), (1, 8), (1, 9), (1, 10), (1, 11), (1, 12), (1, 13), (1, 14), (1, 15), (1, 16);

-- Beri role Kasir (ID 2) hak akses untuk melihat menu Transaksi agar menu muncul di sidebar
INSERT INTO `role_permissions` (`role_id`, `permission_id`) 
SELECT 2, id FROM permissions WHERE slug = 'menu.view.transaksi' AND NOT EXISTS (SELECT 1 FROM role_permissions WHERE role_id = 2 AND permission_id = (SELECT id FROM permissions WHERE slug = 'menu.view.transaksi'));

-- Hapus tabel role_menus lama
DROP TABLE IF EXISTS `role_menus`;

-- Buat tabel role_menus baru dengan menu_key (varchar)
CREATE TABLE `role_menus` (
  `role_id` int(11) NOT NULL,
  `menu_key` varchar(100) NOT NULL,
  PRIMARY KEY (`role_id`, `menu_key`),
  CONSTRAINT `fk_role_menus_role_v2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- (Opsional) Anda bisa menghapus tabel 'menus' jika sudah tidak dipakai
-- DROP TABLE IF EXISTS `menus`;

