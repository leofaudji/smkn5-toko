# PRD 03: Modul Point of Sale (POS) Penjualan & Kasir

| Atribut Dokumen | Informasi |
|:---|:---|
| **Modul** | Point of Sale (POS), Penjualan Ritel & Kasir |
| **Versi Dokumen** | 2.1.0 |
| **Tanggal Efektif** | 2026-10-02 |
| **Status** | Active / Production Standard |

---

## 1. Ringkasan Eksekutif & Tujuan

Modul POS Penjualan adalah antarmuka garda depan kasir toko sekolah untuk melayani transaksi belanja siswa, guru, staf (anggota koperasi), serta pembeli umum secara cepat, akurat, dan terintegrasi langsung dengan mutasi stok perpetual dan pembukuan jurnal akuntansi otomatis.

---

## 2. Fitur Utama POS Kasir

1. **Antarmuka Kasir Cepat & Responsif:**
   - Pencarian produk instan via barcode scanner (USB/Bluetooth) dengan proteksi *throttling* 300ms untuk mencegah duplikasi scan item (*double scan fix*).
   - Pencarian manual berdasarkan nama barang, kode SKU, atau kategori.
   - Shortcut keyboard untuk navigasi kasir cepat tanpa mouse (F2 cari barang, F8 bayar, F9 pending, dll).
2. **Dukungan Pelanggan Anggota & Umum:**
   - Pilihan pelanggan: Umum (Non-Anggota) atau Anggota Koperasi (terhubung ke tabel `anggota`).
   - Jika Anggota dipilih, sistem otomatis menampilkan sisa Saldo Wajib Belanja (WB) dan batas piutang yang dimiliki.
3. **Multi-Metode Pembayaran (Split Payment):**
   - **Tunai (Cash):** Menghitung uang diterima dan kembalian otomatis.
   - **Saldo Wajib Belanja (WB):** Memotong saldo simpanan belanja anggota langsung dari sistem.
   - **QRIS / Transfer Bank:** Memilih akun bank penerima.
   - **Kombinasi (Split Payment):** Contoh: Total belanja Rp 50.000 dibayar Saldo WB Rp 30.000 + Tunai Rp 20.000.
4. **Auto-Detect Kurang Bayar (Auto Piutang Anggota):**
   - Jika total pembayaran anggota (Tunai + Saldo WB) lebih kecil dari total belanja, sistem secara cerdas mendeteksi selisihnya dan mencatatnya sebagai **Piutang Anggota** (bukan error atau salah catat kas).
5. **Pencetakan Struk Fleksibel:**
   - Format printer thermal 58mm dan 80mm.
   - Data nama toko, alamat, kasir, rincian barang, diskon, dan pesan footer tersinkronisasi otomatis dari Pengaturan Toko.
6. **Void / Pembatalan Transaksi Kasir:**
   - Pembatalan transaksi dengan hak otorisasi supervisor/admin.
   - Menghasilkan jurnal pembalik (*reversing entry*) 100% akurat serta mengembalikan stok fisik dan log di kartu stok.

---

## 3. Alur Kerja (Workflow) Transaksi Kasir

```mermaid
flowchart TD
    A[Kasir Buka Halaman Penjualan] --> B[Pilih Pelanggan: Umum / Anggota]
    B --> C[Scan Barcode / Input Item Barang]
    C --> D[Sistem Periksa Ketersediaan Stok]
    D -- Stok Cukup --> E[Item Masuk Keranjang Belanja]
    D -- Stok Habis --> F[Peringatan Stok Tidak Cukup]
    E --> G[Tekan Tombol Pembayaran]
    G --> H{Pilih Metode Bayar}
    H -->|Tunai / QRIS / Transfer| I[Input Nominal Bayar]
    H -->|Saldo WB| J[Verifikasi Saldo WB Anggota]
    H -->|Kombinasi / Kurang Bayar| K[Sistem Alokasikan Selisih ke Piutang Anggota]
    I & J & K --> L[Simpan Transaksi (Database Transaction)]
    L --> M[Kurangi Stok Barang di Tabel items]
    L --> N[Catat Log di Tabel kartu_stok]
    L --> O[Buat Jurnal Otomatis (create_journal_entry)]
    L --> P[Cetak Struk Pembelian]
```

---

## 4. Mekanisme Jurnal Akuntansi Perpetual Otomatis

Setiap transaksi penjualan yang tersimpan berhasil secara otomatis membukukan 2 pasang jurnal akuntansi (Double-Entry Perpetual):

### Contoh Kasus:
- Penjualan kepada Anggota: **Rp 100.000**
- Pembayaran: Saldo WB **Rp 40.000**, Tunai **Rp 50.000**, Sisa Kurang Bayar (Piutang) **Rp 10.000**
- Harga Pokok Penjualan (HPP / Biaya Modal Barang): **Rp 75.000**

### Jurnal yang Dihasilkan:
```
1. Pengakuan Pendapatan & Penerimaan Kas/Piutang:
   (D) Kas Toko                     Rp 50.000
   (D) Hutang Wajib Belanja         Rp 40.000
   (D) Piutang Anggota              Rp 10.000
       (K) Pendapatan Penjualan                  Rp 100.000

2. Pengakuan Beban Pokok & Pengurangan Persediaan:
   (D) Harga Pokok Penjualan (HPP)  Rp 75.000
       (K) Persediaan Barang Dagang              Rp  75.000
```

*Catatan Khusus Barang Konsinyasi:*  
Jika barang yang terjual adalah barang konsinyasi, sistem tidak mencatat HPP melainkan mencatat:
- `(D) Kas/WB` senilai harga jual toko.
- `(K) Hutang Supplier Konsinyasi` senilai harga titip supplier.
- `(K) Pendapatan Komisi Konsinyasi` senilai selisih keuntungan toko.

---

## 5. Spesifikasi Rute & Endpoint API

| Method | URL Path | Handler File | Hak Akses | Deskripsi |
|:---|:---|:---|:---|:---|
| `GET` | `/penjualan` | `pages/penjualan.php` | `auth` | Tampilan aplikasi kasir POS |
| `GET` | `/api/penjualan` | `api/penjualan_handler.php` | `auth` | Mengambil data riwayat penjualan, invoice, dan item |
| `POST` | `/api/penjualan` | `api/penjualan_handler.php` | `auth` | Menyimpan transaksi baru / void transaksi |
| `GET` | `/api/pdf?report=struk&id={id}` | `api/laporan_cetak_handler.php` | `auth` | Cetak ulang struk thermal PDF |

### Parameter Payload Transaksi (`POST /api/penjualan`):
```json
{
  "action": "create_sale",
  "tanggal": "2026-10-02",
  "anggota_id": 15,
  "metode_pembayaran": "split",
  "cash_amount": 50000,
  "wb_amount": 40000,
  "piutang_amount": 10000,
  "kas_account_id": 1,
  "items": [
    { "item_id": 102, "qty": 2, "harga": 25000, "hpp": 18000 },
    { "item_id": 205, "qty": 1, "harga": 50000, "hpp": 39000 }
  ]
}
```

---

## 6. Struktur Basis Data (Data Model)

### 6.1. Tabel `penjualan`
```sql
CREATE TABLE `penjualan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nomor_transaksi` varchar(50) NOT NULL,
  `tanggal` datetime NOT NULL,
  `anggota_id` int(11) DEFAULT NULL,
  `total_belanja` decimal(15,2) NOT NULL,
  `metode_pembayaran` varchar(30) NOT NULL,
  `nominal_tunai` decimal(15,2) NOT NULL DEFAULT 0.00,
  `nominal_wb` decimal(15,2) NOT NULL DEFAULT 0.00,
  `nominal_piutang` decimal(15,2) NOT NULL DEFAULT 0.00,
  `kas_account_id` int(11) DEFAULT NULL,
  `status` enum('selesai','dibatalkan') NOT NULL DEFAULT 'selesai',
  `jurnal_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor_transaksi` (`nomor_transaksi`),
  KEY `anggota_id` (`anggota_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 6.2. Tabel `penjualan_details`
```sql
CREATE TABLE `penjualan_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `penjualan_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `harga_beli` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `is_konsinyasi` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `penjualan_id` (`penjualan_id`),
  FOREIGN KEY (`penjualan_id`) REFERENCES `penjualan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 7. Penanganan Kesalahan & Validasi Integritas

1. **Pencegahan Stok Negatif:** Sistem menolak transaksi jika stok fisik di database tidak mencukupi untuk item dengan opsi `track_stock = 1`.
2. **ACID Transaction Guarantee:** Seluruh operasi (simpan penjualan, kurangi stok item, tulis kartu stok, potong saldo WB, catat jurnal umum, update GL) dibungkus dalam blok `$conn->begin_transaction()`. Jika ada satu langkah gagal, seluruh mutasi di-rollback seketika.
3. **Pemberian Poin Loyalitas Anggota:** Setiap pembelanjaan anggota secara otomatis dapat memicu penambahan poin gamifikasi anggota jika parameter pengaturan poin di KSP aktif.
