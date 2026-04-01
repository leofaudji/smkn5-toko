# Catatan Perubahan Aplikasi

Seluruh pembaruan dan perbaikan pada aplikasi akan dicatat di sini untuk memudahkan Anda mengetahui fitur terbaru.

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
