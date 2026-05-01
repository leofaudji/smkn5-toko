-- ============================================================
-- Migration: Stok Opname Multi-User
-- Jalankan script ini SATU KALI di database Anda
-- ============================================================

CREATE TABLE IF NOT EXISTS `stok_opname_sessions` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`         INT NOT NULL COMMENT 'Pemilik data toko (selalu 1)',
  `created_by`      INT NOT NULL COMMENT 'User yang membuat sesi',
  `tanggal`         DATE NOT NULL,
  `keterangan`      VARCHAR(255) NOT NULL,
  `adj_account_id`  INT NOT NULL COMMENT 'Akun penyeimbang selisih',
  `status`          ENUM('aktif','selesai') NOT NULL DEFAULT 'aktif',
  `finalized_by`    INT NULL,
  `finalized_at`    DATETIME NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stok_opname_draft_items` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `session_id`      INT NOT NULL,
  `item_id`         INT NOT NULL,
  `stok_sistem`     INT NOT NULL COMMENT 'Snapshot stok saat sesi dibuka',
  `stok_fisik`      INT NULL COMMENT 'Diisi petugas, NULL = belum dihitung',
  `dihitung_oleh`   INT NULL COMMENT 'user_id petugas yang mengisi',
  `dihitung_at`     DATETIME NULL,
  UNIQUE KEY `uk_session_item` (`session_id`, `item_id`),
  INDEX `idx_session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
