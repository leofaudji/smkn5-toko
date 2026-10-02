# PRD 00: Arsitektur & Standar Sistem

| Atribut Dokumen | Informasi |
|:---|:---|
| **Modul** | Arsitektur Sistem, Pondasi Teknis & Standar Rekayasa |
| **Versi Dokumen** | 2.1.0 |
| **Tanggal Efektif** | 2026-10-02 |
| **Status** | Active / Production Standard |

---

## 1. Ringkasan Eksekutif & Karakteristik Sistem

Aplikasi **Koperasi & Toko SMKN 5** adalah sistem enterprise berbasis web yang dirancang khusus untuk mengelola operasional ritel sekolah, koperasi simpan pinjam (KSP), pengelolaan persediaan, kewajiban belanja anggota (Wajib Belanja / WB), serta pembukuan akuntansi standar (SAK ETAP / Koperasi) dalam satu platform terpadu.

### Pilar Arsitektur Utama:
1. **Front Controller & Custom Router:**  
   Arsitektur clean-routing berbasis file [index.php](file:///d:/laragon/www/smkn5-toko/index.php) dan engine [Router.php](file:///d:/laragon/www/smkn5-toko/includes/Router.php) yang menangani HTTP Method, parameter URL (`{id}`), dan middleware chaining (`auth`, `guest`, `admin`, `log_access`).
2. **Hybrid SPA (Single Page Application) Navigation:**  
   Halaman backend memuat konten dinamis via AJAX/SPA (didukung header `HTTP_X_SPA_REQUEST`) untuk transisi halaman instan tanpa full page reload, dengan fallback traditional rendering.
3. **Core Accounting Engine (Double-Entry Bookkeeping):**  
   Setiap transaksi material (penjualan kasir, pembelian supplier, setoran WB, penerimaan konsinyasi, penyusutan aset, penyesuaian opname) otomatis mengalir ke tabel `jurnal_entries`, `jurnal_details`, dan rekap teroptimasi `general_ledger` (UPSERT).
4. **Session Management Modern & Skalabel:**  
   Dukungan hybrid session handler (File-based atau Redis Cluster/Standalone) yang dikonfigurasi melalui environment variable [includes/bootstrap.php](file:///d:/laragon/www/smkn5-toko/includes/bootstrap.php).
5. **Mobile-First Progressive Web App (PWA):**  
   Portal anggota mandiri dilengkapi dengan manifest PWA, Service Worker untuk caching asset statis, serta integrasi Push Notification OneSignal.

---

## 2. Tech Stack & Dependensi

| Layer | Teknologi | Keterangan & Versi |
|:---|:---|:---|
| **Runtime / Bahasa** | PHP 8.x Native | Pemrograman prosedural-modular terstruktur, PDO/MySQLi |
| **Web Server** | Apache 2.4+ / Laragon | Mod_rewrite diaktifkan untuk routing friendly URL |
| **Basis Data** | MySQL 8.0+ / MariaDB 10.4+ | Engine InnoDB (ACID transaction compliance) |
| **Cache & Session** | Redis 6.x+ (Opsional via .env) | Menyimpan sesi pengguna dan data cache transaksi |
| **Styling & UI** | Tailwind CSS + Bootstrap Icons | Antarmuka responsif, mode Gelap (Dark Mode), Glassmorphism |
| **PDF Reporting** | FPDF & Custom PDF Extension | Engine [includes/fpdf.php](file:///d:/laragon/www/smkn5-toko/includes/fpdf.php) & [includes/PDF.php](file:///d:/laragon/www/smkn5-toko/includes/PDF.php) untuk cetak laporan & struk |
| **Mobile Push** | OneSignal Web SDK | Notifikasi instan status pinjaman & transaksi anggota |

---

## 3. Struktur Direktori Proyek

```
smkn5-toko/
├── .env / .env.example       <- Konfigurasi environment (DB, Redis, App Name, Debug)
├── .htaccess                 <- Konfigurasi Apache, proteksi URL, blokir akses docs
├── index.php                 <- Front controller utama aplikasi
├── login.php / logout.php    <- Endpoint login & logout backoffice
├── member_login.php          <- Halaman autentikasi portal anggota mobile
├── manifest.json             <- Manifest PWA untuk portal anggota
├── service-worker.js         <- PWA service worker caching
├── actions/                  <- Script pemrosesan form autentikasi (2FA, reset pass)
├── api/                      <- Handler JSON AJAX API untuk seluruh modul
│   ├── ksp/                  <- Endpoint API khusus koperasi simpan pinjam
│   └── portal/               <- Endpoint API khusus portal mobile
├── assets/                   <- Berkas statis CSS, JS, Gambar, Vendor UI
├── config/                   <- File konfigurasi statis (menus.php, dll)
├── docs/                     <- DOKUMENTASI SISTEM (DIBLOKIR DARI AKSES WEB)
│   ├── .htaccess             <- Proteksi web 403
│   ├── AGENTS.md             <- Aturan sinkronisasi PRD
│   ├── README.md             <- Indeks dokumentasi
│   └── prd/                  <- File PRD fungsional modular (00 s/d 12)
├── includes/                 <- Kelas dan helper inti aplikasi
│   ├── Database.php          <- Singleton database connector
│   ├── Router.php            <- Dispatcher URL & middleware
│   ├── accounting_helper.php <- Generator jurnal otomatis & general ledger
│   ├── bootstrap.php         <- Bootstrapping aplikasi, CSRF, Redis, Error handler
│   ├── functions.php         <- Utility global, sanitasi, helper auth, format uang
│   ├── RateLimiter.php       <- Rate limiter request API per IP
│   └── middlewares/          <- Middlewares (Auth, Guest, Admin, LogAccess)
├── pages/                    <- View controller untuk rendering antarmuka
│   └── ksp/                  <- Halaman khusus modul simpan pinjam & anggota
└── views/                    <- Template bersama (header, footer, sidebar, error 403/404)
```

---

## 4. Standar Keamanan & Perlindungan Data

### 4.1. Centralized CSRF Protection
- Dikelola terpusat di [includes/bootstrap.php](file:///d:/laragon/www/smkn5-toko/includes/bootstrap.php).
- Setiap request selain `GET` (`POST`, `PUT`, `DELETE`) secara otomatis divalidasi token CSRF-nya via `verify_csrf_token()`.
- Whitelist pengecualian hanya berlaku untuk cron-runner tanpa sesi pengguna (contoh: `api/run_recurring.php`).

### 4.2. Rate Limiting API
- Setiap request menuju prefix rute `/api/` diperiksa oleh [includes/RateLimiter.php](file:///d:/laragon/www/smkn5-toko/includes/RateLimiter.php).
- Batas default: **60 request per menit per alamat IP**. Permintaan yang melebihi batas langsung dikembalikan dengan status HTTP `429 Too Many Requests`.

### 4.3. Remember Me Token Rotation
- Penyimpanan credential cookie aman menggunakan skema terpisah `selector:validator`.
- Di database disimpan hash SHA-256 validator: `hash('sha256', $validator)`.
- Setiap kali cookie digunakan untuk login otomatis, token dirotasi (selector dan validator baru dibuat, yang lama dihapus) untuk memitigasi serangan replay cookie.

### 4.4. Role-Based Access Control (RBAC) Dinamis
- Didukung tabel `roles`, `permissions`, `role_permissions`, dan `role_menus`.
- Superadmin memiliki akses mutlak, sedangkan role lain (Kasir, Petugas Toko, Pengurus KSP) hanya melihat menu dan endpoint yang diizinkan.

---

## 5. Standar Akuntansi & Integritas Transaksi (Double-Entry Engine)

Aplikasi menerapkan sistem pembukuan berpasangan (Double-Entry Bookkeeping). Tidak ada transaksi finansial yang dicatat sepihak.

### Aturan Saldo Normal:
| Klasifikasi Akun | Nomor Kepala Akun (COA) | Saldo Normal | Jika Bertambah | Jika Berkurang |
|:---|:---|:---|:---|:---|
| **Aset (Aktiva)** | `1-xxxx` | Debit | Debit | Kredit |
| **Liabilitas (Hutang)** | `2-xxxx` | Kredit | Kredit | Debit |
| **Ekuitas (Modal)** | `3-xxxx` | Kredit | Kredit | Debit |
| **Pendapatan** | `4-xxxx` | Kredit | Kredit | Debit |
| **Beban Pokok Penjualan (HPP)** | `5-xxxx` | Debit | Debit | Kredit |
| **Beban Operasional** | `6-xxxx` | Debit | Debit | Kredit |

### Formula Konsistensi:
$$\text{Total Debit} = \text{Total Kredit}$$
$$\text{Aset} = \text{Liabilitas} + \text{Ekuitas} + (\text{Pendapatan} - \text{Beban})$$

---

## 6. Konvensi Database & Skema

1. **Collation & Charset:** `utf8mb4_general_ci` / `utf8mb4_unicode_ci` untuk seluruh tabel.
2. **Audit Timestamp:** Semua tabel master dan transaksi wajib memiliki:
   - `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`
   - `created_by INT NULL`
   - `updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
   - `updated_by INT NULL`
3. **Mata Uang & Presisi Finansial:** Kolom moneter menggunakan tipe `DECIMAL(15,2)` untuk menghindari floating point error.
4. **Data Ownership:** Sistem mendukung multi-operator dengan single-tenant database toko di mana `user_id = 1` merujuk ke data entitas pemilik toko.
