# Catatan Perubahan Aplikasi

Seluruh pembaruan dan perbaikan pada aplikasi akan dicatat di sini untuk memudahkan Anda mengetahui fitur terbaru.

## [1.7.0] - 2026-04-10
### FITUR BARU
- **Tampilan Jurnal Lebih Manis**: Daftar jurnal kini dikelompokkan per transaksi, sehingga data yang tadinya berantakan sekarang jadi lebih rapi dan enak dilihat.
- **Tombol Pintas Pencarian**: Menambahkan tombol sekali klik untuk melihat data "Hari Ini" atau "Bulan Ini" agar Anda tidak perlu repot memilih tanggal secara manual.
- **Ikon Kategori Transaksi**: Kini ada ikon warna-warni yang memudahkan Anda membedakan mana transaksi Penjualan, Kas masuk/keluar, dan Jurnal Umum hanya dalam sekali lihat.

### PENINGKATAN
- **Urutan Transaksi Terbaru**: Data yang baru saja Anda masukkan atau ubah sekarang otomatis muncul di paling atas agar tidak perlu dicari-cari lagi.
- **Informasi Waktu Lebih Detail**: Kolom tanggal sekarang menampilkan jam dan menit yang akurat untuk memudahkan Anda melacak kapan tepatnya sebuah transaksi dilakukan atau dibatalkan.
- **Navigasi Lebih Nyaman**: Judul tabel sekarang tetap terlihat di atas saat disekroll ke bawah, sehingga Anda tidak akan bingung membaca kolom data yang panjang.

### PERBAIKAN
- **Pencatatan Pembatalan Penjualan**: Memperbaiki masalah di mana jurnal pembatalan (void) terkadang tidak muncul di laporan. Sekarang, semua pembatalan akan tercatat 100% akurat.
- **Perataan Data**: Memperbaiki posisi angka dan nama akun yang sebelumnya agak berantakan, sekarang semuanya sudah tegak lurus dan rapi sesuai kolomnya.
- **Pembersihan Filter**: Tombol Reset baru memudahkan Anda mengembalikan semua pencarian ke kondisi awal dengan satu klik saja.

## [1.6.0] - 2026-04-09
### FITUR BARU
- **Redesign Leaderboard Member**: Mengubah tampilan Papan Peringkat menjadi desain yang lebih simpel, bersih, dan elegan profesional.
- **Drill-down Riwayat Transaksi**: Nama anggota kini dapat diklik untuk memunculkan modal riwayat yang menampilkan tab "Riwayat Belanja" dan "Wajib Belanja".
- **Rincian Item Belanja**: Menambahkan kemampuan untuk melihat daftar barang yang dibeli per transaksi secara langsung di dalam modal riwayat anggota.
- **Filter Canggih Konsinyasi**: Menambahkan fitur pencarian (Nama/SKU), filter Pemasok, dan filter Status Stok pada menu Kelola Barang Konsinyasi.

### PENINGKATAN
- **UX Modal Dinamis**: Memperbarui sistem modal agar selalu muncul di posisi yang proporsional terhadap layar (viewport), memudahkan navigasi pada daftar data yang panjang.
- **Optimasi API Leaderboard & Konsinyasi**: Meningkatkan kecepatan penarikan data riwayat dan daftar barang melalui optimasi query database.

## [1.5.0] - 2026-04-04
### FITUR BARU
- **Riwayat Transaksi WB Lengkap**: Menambahkan kolom Metode Pembayaran (Tunai, QRIS, Transfer, Saldo WB) pada modal riwayat transaksi di Laporan WB Tahunan untuk transparansi data yang lebih baik.

### PERBAIKAN
- **Sinkronisasi Nilai Persediaan**: Memperbaiki gap data antara tabel produk dan kartu stok dengan mewajibkan pencatatan log (kartu stok) saat Tambah Barang Baru dan Penyesuaian Stok (Opname).
- **Akurasi Laporan Stok**: Menjalankan skrip rekonsiliasi otomatis untuk menyelaraskan riwayat stok lama dengan saldo fisik saat ini, memastikan laporan historis dan real-time selaras 100%.
- **Navigasi Laporan (NaN Fix)**: Memperbaiki bug tampilan pagination "NaN sampai NaN" pada Laporan Penjualan per Item dengan mensinkronkan variabel metadata antara API dan Frontend.
- **Optimasi Kasir (Qty)**: Memperbaiki bug "Qty 2" pada modul penjualan menggunakan sistem *Throttling* 300ms dan *Event Delegation* untuk stabilitas scan barcode yang lebih baik.
- **Stok Konsinyasi Akurat**: Memperbaiki sinkronisasi stok barang konsinyasi agar otomatis berkurang secara real-time saat terjual, baik di menu Penjualan maupun Manajemen Konsinyasi.
- **Stabilitas Edit Transaksi**: Menangani error `ArgumentCountError` dan typo pada kolom database (`price` -> `harga_jual`) saat melakukan edit transaksi penjualan.
- **Validasi Pembayaran**: Memastikan data metode pembayaran dan akun tujuan tersimpan dengan benar di database saat pembaruan transaksi (Edit).
- **Pembersihan Sistem**: Membersihkan ruang kerja dari puluhan file skrip sementara, debug, dan migrasi untuk menjaga performa dan kerapian kode aplikasi.

## [1.4.0] - 2026-04-01
### FITUR BARU
- **Laporan Pembelian Lengkap**: Menambahkan menu Laporan Pembelian yang memungkinkan Anda melihat histori pengadaan barang lengkap dengan fitur filter tanggal, supplier, dan pencarian produk.
- **Export PDF & CSV (Laporan Pembelian)**: Kini laporan pembelian dapat diunduh dalam format PDF yang profesional atau CSV untuk diolah lebih lanjut di Excel.
- **Keamanan Export**: Memperbarui sistem export laporan PDF menggunakan metode POST yang lebih aman dan mendukung pengiriman data filter yang lebih kompleks.

### PENINGKATAN
- **Sentralisasi Pengaturan Struk**: Nama Toko, Alamat, dan Pesan Kaki (footer) pada struk penjualan kini selalu sinkron dengan data di menu Pengaturan. Perubahan di Pengaturan akan langsung terlihat pada struk tanpa perlu refresh halaman.
- **Sistem Cache Busting**: Memperbarui cara aplikasi memuat file JavaScript agar browser selalu mengambil versi terbaru. Hal ini mencegah masalah "tampilan tidak berubah" setelah aplikasi diperbarui oleh tim teknis.
- **Optimasi SPA (Single Page Application)**: Meningkatkan kecepatan perpindahan antar menu dan sinkronisasi data global antar modul aplikasi.

## [1.3.0] - 2026-03-27
### FITUR BARU
- **Sinkronasi Anggota (Sync SP)**: Kini Anda dapat mengimpor data anggota secara otomatis dari aplikasi Simpan Pinjam (SP) tanpa perlu input manual satu per satu.
- **Dukungan NIK**: Menambahkan kolom NIK (Nomor Induk Kependudukan) pada data anggota untuk identitas yang lebih akurat, lengkap dengan kolom di tabel dan formulir pendaftaran.
- **Import Cerdas (New Data Only)**: Sistem sinkronasi kini otomatis mendeteksi data yang sudah ada dan hanya akan memasukkan data baru saja. Hal ini mencegah data lokal yang sudah Anda ubah tertimpa oleh data dari SP App.
- **Pencarian NIK**: Anda sekarang dapat mencari anggota dengan mengetikkan nomor NIK pada kotak pencarian di daftar anggota.

## [1.2.0] - 2026-03-27
### FITUR BARU
- Menambahkan menu **Catatan Perubahan** ini agar Anda bisa melihat riwayat pembaruan aplikasi.
- Informasi versi aplikasi kini muncul di bawah nama aplikasi pada menu samping.
- Pembaruan sistem agar aplikasi lebih mudah dipasang di berbagai jenis server tanpa pengaturan manual yang rumit.

### PERBAIKAN
- Memperbaiki masalah "Error 500" saat masuk (login) yang terjadi di beberapa server.
- Memperbaiki kesalahan tampilan pada Laporan Neraca dan Laporan Laba Rugi sehingga data kini tampil lebih akurat.
- Memperbaiki sistem agar laporan bisa dibuka lebih cepat dan stabil tanpa kendala teknis dari server pusat.

## [1.1.5] - 2026-03-25
### FITUR BARU
- **Laporan Konsinyasi**: Penambahan sistem untuk melacak barang titipan (konsinyasi) dari pihak ketiga mulai dari stok hingga pelunasan.
- **Audit Saldo Otomatis**: Fitur untuk melakukan pemeriksaan silang antara saldo fisik dan saldo sistem secara otomatis untuk mencegah selisih data.

## [1.1.0] - 2026-03-15
### FITUR BARU
- Menambahkan efek transisi halus saat berpindah antar menu agar aplikasi terasa lebih nyaman digunakan.
- Menambahkan kotak pencarian global untuk memudahkan Anda mencari data di seluruh bagian aplikasi.

### PENINGKATAN
- Memperbaiki tampilan menu samping (sidebar) agar lebih rapi dan tulisan lebih mudah dibaca.
- Menyelaraskan tampilan "Mode Gelap" agar semua laporan tetap terlihat jelas dan profesional saat malam hari atau di ruangan gelap.

## [1.0.5] - 2026-03-05
### FITUR BARU
- **Sistem Piutang**: Penambahan fitur pemantauan piutang anggota dan laporan jatuh tempo secara otomatis.
- **Kartu Stok per Item**: Detail pergerakan keluar-masuk barang kini bisa dilacak dalam satu tampilan kartu stok.

## [1.0.0] - 2026-03-01
### FITUR BARU
- **Sistem Wajib Belanja (WB)**: Implementasi modul untuk mencatat dan memantau kewajiban belanja bagi anggota.
- **Koperasi Simpan Pinjam (KSP)**: Peluncuran fitur lengkap mulai dari simpanan, penarikan, hingga pengajuan pinjaman online bagi anggota.
- **Manajemen Stok Otomatis**: Sistem akan otomatis memotong stok barang setiap kali ada transaksi penjualan.
- **Laporan Akuntansi Lengkap**: Menampilkan Laporan Buku Besar, Neraca, dan Laba Rugi secara otomatis dari setiap transaksi.

### PENINGKATAN
- Peningkatan keamanan data dengan sistem hak akses (Role) yang lebih ketat.
- Optimasi kecepatan cetak laporan PDF untuk laporan bulanan yang besar.

## [0.9.0] - 2026-02-15
### FITUR BARU
- **Dashboard Toko**: Tampilan ringkasan penjualan, total stok, dan statistik anggota secara visual.
- **Point of Sale (Kasir)**: Fitur transaksi penjualan cepat dengan dukungan scan barcode.
- **Buku Panduan Digital**: Menambahkan menu bantuan untuk memudahkan pengguna baru mempelajari cara kerja aplikasi.
