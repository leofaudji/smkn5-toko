# PRD 05: Modul Manajemen Konsinyasi (Barang Titipan)

| Atribut Dokumen | Informasi |
|:---|:---|
| **Modul** | Manajemen Barang Konsinyasi & Pelunasan Supplier |
| **Versi Dokumen** | 2.1.0 |
| **Tanggal Efektif** | 2026-10-02 |
| **Status** | Active / Production Standard |

---

## 1. Ringkasan Eksekutif & Karakteristik Bisnis

Koperasi sekolah sering menerima titipan makanan ringan, minuman, atau kerajinan dari UMKM, wali murid, maupun siswa (konsinyor). Pada skema konsinyasi:
- **Toko Koperasi bukan pemilik barang**, melainkan perantara/agen penjualan.
- Barang titipan **tidak diakui sebagai aset persediaan toko**.
- Laba toko murni berasal dari **selisih harga jual ke pembeli dikurangi harga titip supplier (komisi titip jual)**.
- Pembayaran kepada pemasok konsinyasi hanya dilakukan untuk **barang yang telah terbukti laku terjual** melalui kasir.

---

## 2. Fitur Utama

1. **Katalog Barang Konsinyasi:**
   - Pencatatan barang titipan lengkap dengan nama pemasok, SKU/Barcode, harga titip supplier (*vendor cost*), dan harga jual toko (*retail price*).
   - Filter canggih: Berdasarkan nama produk, barcode, supplier, dan status stok (tersedia, menipis, habis).
2. **Sinkronisasi Otomatis dengan POS Kasir:**
   - Barang konsinyasi langsung dapat dicari dan di-scan di modul Penjualan POS.
   - Saat kasir menjual barang konsinyasi, stok barang titipan otomatis berkurang secara real-time.
3. **Pelunasan Supplier Konsinyasi (Settlement):**
   - Sistem merekap total unit barang yang sudah terjual tetapi belum dibayarkan kepada masing-masing pemasok.
   - Pilihan pelunasan fleksibel: Bayar sebagian atau bayar seluruh tagihan yang belum lunas.
   - Pemilihan akun kas/bank sumber pembayaran pelunasan.
   - Cetak bukti pembayaran pelunasan konsinyasi resmi sebagai tanda terima supplier.

---

## 3. Alur Kerja (Workflow) Konsinyasi

```mermaid
flowchart TD
    A[Supplier Titip Barang] --> B[Input di Menu Konsinyasi: Harga Titip & Harga Jual]
    B --> C[Stok Titipan Bertambah di Sistem]
    C --> D[Kasir Menjual Barang Konsinyasi via POS]
    D --> E[Stok Konsinyasi Terpotong]
    D --> F[Sistem Catat Hutang Konsinyasi & Komisi Toko]
    E & F --> G[Periode Pelunasan Konsinyasi (Mingguan/Bulanan)]
    G --> H[Admin Buka Menu Pelunasan Konsinyasi]
    H --> I[Pilih Supplier & Verifikasi Rekap Barang Terjual]
    I --> J[Eksekusi Pelunasan via Kas/Bank]
    J --> K[Jurnal Pelunasan Dibukukan]
    J --> L[Cetak Bukti Pembayaran Supplier]
```

---

## 4. Mekanisme Jurnal Akuntansi Konsinyasi

### 4.1. Saat Barang Diterima (Penerimaan Titipan):
Tidak ada jurnal akuntansi keuangan yang dicatat karena barang bukan milik toko (hanya dicatat pada mutasi stok barang titipan).

### 4.2. Saat Barang Terjual di Kasir:
- Harga Titip Supplier: **Rp 8.500**
- Harga Jual Toko ke Konsumen: **Rp 10.000**
- Keuntungan/Komisi Toko: **Rp 1.500**

```
Jurnal Penjualan Kasir:
(D) Kas Toko / Saldo WB Anggota           Rp 10.000
    (K) Hutang Konsinyasi (Supplier)                  Rp 8.500
    (K) Pendapatan Komisi Konsinyasi                  Rp 1.500
```

### 4.3. Saat Toko Melunasi Uang ke Supplier Konsinyasi:
Toko membayarkan akumulasi barang yang laku terjual sebesar Rp 8.500:
```
Jurnal Pelunasan Supplier:
(D) Hutang Konsinyasi (Supplier)          Rp 8.500
    (K) Kas Toko / Bank                               Rp 8.500
```

---

## 5. Spesifikasi Rute & Endpoint API

| Method | URL Path | Handler File | Hak Akses | Deskripsi |
|:---|:---|:---|:---|:---|
| `GET` | `/konsinyasi` | `pages/konsinyasi.php` | `auth` | Halaman master barang konsinyasi |
| `GET` | `/pelunasan-konsinyasi` | `pages/pelunasan_konsinyasi.php` | `auth` | Halaman pelunasan tagihan supplier |
| `GET` | `/api/konsinyasi` | `api/konsinyasi_handler.php` | `auth` | Mengambil data barang titipan, stok, dan tagihan |
| `POST` | `/api/konsinyasi` | `api/konsinyasi_handler.php` | `auth` | Tambah barang, ubah stok, proses pelunasan supplier |

### Contoh Request Pelunasan (`POST /api/konsinyasi`):
```json
{
  "action": "proses_pelunasan",
  "supplier_id": 4,
  "tanggal_bayar": "2026-10-02",
  "kas_account_id": 1,
  "total_bayar": 425000,
  "keterangan": "Pelunasan snack titipan periode akhir pekan",
  "item_ids": [12, 14, 15]
}
```

---

## 6. Struktur Basis Data (Data Model)

### 6.1. Tabel `consignment_items`
```sql
CREATE TABLE `consignment_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `kode_barang` varchar(50) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `harga_titip` decimal(15,2) NOT NULL COMMENT 'Harga dari pemasok',
  `harga_jual` decimal(15,2) NOT NULL COMMENT 'Harga jual ke konsumen',
  `stok` int(11) NOT NULL DEFAULT 0,
  `terjual_belum_lunas` int(11) NOT NULL DEFAULT 0,
  `total_terjual` int(11) NOT NULL DEFAULT 0,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`),
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 7. Validasi & Pengendalian Khusus

1. **Validasi Harga Jual vs Harga Titip:** Sistem memvalidasi bahwa harga jual ke pembeli tidak boleh lebih rendah dari harga titip supplier ($\text{Harga Jual} \ge \text{Harga Titip}$) demi mencegah kerugian operasional koperasi.
2. **Pemisahan Stok Konsinyasi vs Stok Reguler:** Barang konsinyasi ditandai dengan flag `is_konsinyasi = 1` agar tidak terhitung ke dalam Laporan Nilai Persediaan Aset Toko.
3. **Rekonsiliasi Retur Barang Tidak Laku:** Jika barang titipan kadaluarsa atau ditarik kembali oleh pemiliknya, sistem menyediakan fitur penyesuaian retur tanpa menimbulkan beban HPP atau hutang pembayaran.
