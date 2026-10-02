# Dokumentasi & Spesifikasi Produk (PRD) - Sistem Koperasi & POS Toko SMKN 5

Selamat datang di pusat dokumentasi teknis dan spesifikasi produk aplikasi **Koperasi & Toko SMKN 5**.  
Dokumentasi ini disusun secara modular berdasarkan domain fungsional sistem agar memudahkan pemeliharaan, audit, dan penambahan fitur berkelanjutan.

> 🔒 **Keamanan Direktori:**  
> Folder `docs/` ini telah diproteksi dari akses web langsung via HTTP/HTTPS menggunakan konfigurasi Web Server Apache di [docs/.htaccess](file:///d:/laragon/www/smkn5-toko/docs/.htaccess) dan root [.htaccess](file:///d:/laragon/www/smkn5-toko/.htaccess).

> 📌 **Aturan Pemeliharaan (Mandatory):**  
> Setiap kali ada penambahan, perubahan, atau penghapusan fitur, pengembang / AI Assistant **WAJIB** memperbarui file PRD terkait di folder ini. Lihat pedoman lengkap di [docs/AGENTS.md](file:///d:/laragon/www/smkn5-toko/docs/AGENTS.md).

---

## Daftar Isi PRD Modular (Functional Grouping)

| No | Modul / Dokumen | Deskripsi Singkat | File Tautan |
|:---|:---|:---|:---|
| **00** | **Arsitektur & Standar Sistem** | Arsitektur teknis, stack teknologi, struktur direktori, konvensi DB, sekuriti, dan integrasi akuntansi. | [00-arsitektur-dan-standar-sistem.md](file:///d:/laragon/www/smkn5-toko/docs/prd/00-arsitektur-dan-standar-sistem.md) |
| **01** | **Autentikasi & Hak Akses (RBAC)** | Login, remember me rotation, dynamic roles & permissions, users management, reset password, dan activity audit log. | [01-autentikasi-dan-manajemen-pengguna.md](file:///d:/laragon/www/smkn5-toko/docs/prd/01-autentikasi-dan-manajemen-pengguna.md) |
| **02** | **Master Data & Pengaturan** | Bagan Akun (COA), Saldo Awal Neraca/Laba Rugi, Supplier, Kategori, Anggaran / Budgeting, Pengaturan Toko & Printer Struk. | [02-master-data-dan-pengaturan.md](file:///d:/laragon/www/smkn5-toko/docs/prd/02-master-data-dan-pengaturan.md) |
| **03** | **POS Penjualan & Kasir** | Transaksi kasir, barcode scanner, multi-pembayaran (Tunai, QRIS, Transfer, Saldo WB, Piutang Anggota), cetak struk thermal, void transaksi, auto-jurnal perpetual. | [03-modul-pos-penjualan-dan-kasir.md](file:///d:/laragon/www/smkn5-toko/docs/prd/03-modul-pos-penjualan-dan-kasir.md) |
| **04** | **Pembelian & Pengadaan Stok** | Input pengadaan barang supplier, tracking hutang dagang, kalkulasi harga modal/HPP, riwayat pembelian, ekspor PDF & CSV. | [04-modul-pembelian-dan-pengadaan.md](file:///d:/laragon/www/smkn5-toko/docs/prd/04-modul-pembelian-dan-pengadaan.md) |
| **05** | **Manajemen Konsinyasi** | Pengelolaan barang titipan pihak ketiga, penetapan harga titip & jual, pemotongan stok otomatis saat kasir menjual, sistem komisi laba toko, pelunasan berkala per supplier. | [05-modul-konsinyasi.md](file:///d:/laragon/www/smkn5-toko/docs/prd/05-modul-konsinyasi.md) |
| **06** | **Stok, Inventaris & Aset Tetap** | Master barang & SKU, mutasi kartu stok perpetual, stok opname multi-user real-time (sesi draft & finalisasi), analisis ABC & Reorder Point, laporan nilai & pertumbuhan persediaan, aset tetap & depresiasi otomatis. | [06-modul-stok-dan-inventaris.md](file:///d:/laragon/www/smkn5-toko/docs/prd/06-modul-stok-dan-inventaris.md) |
| **07** | **Wajib Belanja (WB) Anggota** | Setoran top-up simpanan belanja anggota, saldo WB, penggunaan saat belanja di POS, auto-detect piutang jika belanja melebihi saldo, rekapitulasi WB tahunan, leaderboard belanja. | [07-modul-wajib-belanja-wb.md](file:///d:/laragon/www/smkn5-toko/docs/prd/07-modul-wajib-belanja-wb.md) |
| **08** | **Koperasi Simpan Pinjam (KSP)** | Simpanan (pokok, wajib, sukarela), penarikan & persetujuan pengurus, pinjaman online/offline, angsuran/cicilan, simulasi kredit, agunan, gamifikasi poin, wishlist barang, pengumuman, rasio kesehatan KSP. | [08-modul-simpan-pinjam-ksp.md](file:///d:/laragon/www/smkn5-toko/docs/prd/08-modul-simpan-pinjam-ksp.md) |
| **09** | **Portal Anggota PWA (Mobile)** | Web app mobile-first untuk anggota koperasi, PWA offline capability, cek saldo simpanan & WB, rincian riwayat belanja kasir per item, pengajuan pinjaman/penarikan mandiri, push notification OneSignal. | [09-portal-anggota-pwa.md](file:///d:/laragon/www/smkn5-toko/docs/prd/09-portal-anggota-pwa.md) |
| **10** | **Akuntansi, Jurnal & Buku Besar** | Double-entry journal engine, denormalisasi General Ledger cepat, buku besar interaktif drill-down, neraca saldo & lajur, entri jurnal manual/penyesuaian, proses tutup buku periode. | [10-modul-akuntansi-dan-buku-besar.md](file:///d:/laragon/www/smkn5-toko/docs/prd/10-modul-akuntansi-dan-buku-besar.md) |
| **11** | **Laporan Keuangan & Analitik** | Laporan Harian Kasir, Laba Rugi komersial/koperasi, Neraca Keuangan, Arus Kas langsung/tidak langsung, Laba Ditahan, Analisis Rasio Keuangan, Laporan Margin per SKU & per Kategori, Audit Saldo Silang fisik vs GL. | [11-modul-laporan-keuangan-dan-analitik.md](file:///d:/laragon/www/smkn5-toko/docs/prd/11-modul-laporan-keuangan-dan-analitik.md) |
| **12** | **Tools, Rekonsiliasi & Pemeliharaan** | Transaksi berulang (recurring templates & runner), Rekonsiliasi Bank rekening koran, Audit Transaksi perbedaan saldo, Global Search, Backup/Restore Database, Changelog sistem. | [12-tools-rekonsiliasi-dan-pemeliharaan.md](file:///d:/laragon/www/smkn5-toko/docs/prd/12-tools-rekonsiliasi-dan-pemeliharaan.md) |

---

## Ringkasan Ekosistem Aplikasi

Aplikasi Toko & Koperasi SMKN 5 adalah sistem terintegrasi yang menggabungkan:
1. **Sistem Kasir Ritel (POS):** Cepat, mendukung scan barcode, multi-pembayaran, dan pencatatan HPP perpetual secara otomatis.
2. **Sistem Koperasi Anggota & Wajib Belanja:** Pengelolaan anggota, kewajiban belanja, simpanan, dan pinjaman.
3. **Core Akuntansi Standar Koperasi/SAK ETAP:** Jurnal otomatis di setiap mutasi kas, stok, piutang, dan hutang; menghasilkan Neraca, Laba Rugi, dan Arus Kas real-time.
4. **Portal Mandiri Anggota (PWA):** Akses transparan bagi anggota melalui perangkat mobile/smartphone.
