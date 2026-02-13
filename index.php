<?php  
// Aplikasi RT - Front Controller

// Mulai sesi di setiap permintaan. Ini harus dilakukan sebelum output apa pun.
session_start();  

// Muat komponen inti
require_once 'includes/bootstrap.php';

// --- Auto Login from "Remember Me" Cookie ---
// Jalankan ini setelah bootstrap (untuk fungsi) tetapi sebelum router (untuk otentikasi)
if (empty($_SESSION['loggedin']) && isset($_COOKIE['remember_me'])) {
    list($selector, $validator) = explode(':', $_COOKIE['remember_me'], 2);

    if (!empty($selector) && !empty($validator)) {
        // Fungsi attempt_login_with_cookie() didefinisikan di dalam bootstrap.php
        attempt_login_with_cookie($selector, $validator);
    }
}
// --- End Auto Login ---

require_once 'includes/Router.php';

// Router membutuhkan base path yang sudah didefinisikan di bootstrap.php
$router = new Router(BASE_PATH);

// --- Definisikan Rute (Routes) ---

// Rute untuk tamu (hanya bisa diakses jika belum login)
$router->get('/login', 'login.php', ['guest']);
$router->post('/login', 'actions/auth.php'); // Handler untuk proses login
$router->get('/forgot', 'pages/forgot_password.php', ['guest']);
$router->post('/actions/forgot_password_action.php', 'actions/forgot_password_action.php', ['guest']);
$router->get('/reset-password', 'pages/reset_password.php', ['guest']);
$router->post('/reset-password', 'actions/reset_password_action.php', ['guest']);

// Rute Portal Anggota
$router->get('/member/login', 'member_login.php');
$router->get('/member/dashboard', 'pages/ksp/member_dashboard.php');
$router->get('/member/logout', function() { session_destroy(); header('Location: '.BASE_PATH.'/member/login'); });

// Rute Otomatis untuk /member
$router->get('/member', function() {
    if (isset($_SESSION['member_loggedin']) && $_SESSION['member_loggedin'] === true) {
        header('Location: ' . base_url('/member/dashboard'));
    } else {
        header('Location: ' . base_url('/member/login'));
    }
    exit;
});

// Rute yang memerlukan otentikasi
$router->get('/', function() {
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        header('Location: ' . base_url('/dashboard'));
    } else {
        header('Location: ' . base_url('/login'));
    }
    exit;
});
$router->get('/dashboard', 'pages/dashboard.php', ['auth']);
$router->get('/buku-panduan', 'pages/buku_panduan.php', ['auth']);
$router->get('/logout', 'logout.php');
$router->get('/my-profile/change-password', 'pages/my_profile.php', ['auth']);

// --- Rute Utama Aplikasi Keuangan ---
$router->get('/transaksi', 'pages/transaksi.php', ['auth']);
$router->get('/pembelian', 'pages/pembelian.php', ['auth']);
$router->get('/wajib-belanja', 'pages/wajib_belanja.php', ['auth']);
$router->get('/penjualan', 'pages/penjualan.php', ['auth']); // Rute baru untuk halaman penjualan
$router->get('/stok', 'pages/stok.php', ['auth']);
$router->get('/stok-opname', 'pages/stok_opname.php', ['auth']);
$router->get('/daftar-jurnal', 'pages/daftar_jurnal.php', ['auth']);
$router->get('/konsinyasi', 'pages/konsinyasi.php', ['auth']);
$router->get('/transaksi-berulang', 'pages/transaksi_berulang.php', ['auth']);
$router->get('/rekonsiliasi-bank', 'pages/rekonsiliasi_bank.php', ['auth']);
$router->get('/histori-rekonsiliasi', 'pages/histori_rekonsiliasi.php', ['auth']);
$router->get('/aset-tetap', 'pages/aset_tetap.php', ['auth']);
$router->get('/entri-jurnal', 'pages/entri_jurnal.php', ['auth']);
$router->get('/coa', 'pages/coa.php', ['auth']);
$router->get('/saldo-awal', 'pages/saldo_awal.php', ['auth']);
$router->get('/laporan', 'pages/laporan.php', ['auth']); 
$router->get('/laporan-stok', 'pages/laporan_stok.php', ['auth']);
$router->get('/laporan-penjualan', 'pages/laporan_penjualan.php', ['auth']);
$router->get('/laporan-penjualan-item', 'pages/laporan_penjualan_item.php', ['auth']);
$router->get('/laporan-kartu-stok', 'pages/laporan_kartu_stok.php', ['auth']);
$router->get('/laporan-wb-tahunan', 'pages/laporan_wb_tahunan.php', ['auth']);
$router->get('/laporan-kesehatan-bank', 'pages/laporan_kesehatan_bank.php', ['auth']);
$router->get('/laporan-persediaan', 'pages/laporan_persediaan.php', ['auth']);
$router->get('/laporan-pertumbuhan-persediaan', 'pages/laporan_pertumbuhan_persediaan.php', ['auth']);
$router->get('/anggaran', 'pages/anggaran.php', ['auth']);
$router->get('/neraca-saldo', 'pages/neraca_saldo.php', ['auth']);
$router->get('/tutup-buku', 'pages/tutup_buku.php', ['auth', 'admin']);
$router->get('/laporan-laba-ditahan', 'pages/laporan_laba_ditahan.php', ['auth']);
$router->get('/laporan-pertumbuhan-laba', 'pages/laporan_pertumbuhan_laba.php', ['auth']);
$router->get('/analisis-rasio', 'pages/laporan_analisis_rasio.php', ['auth']); // Nama file halaman sudah benar
$router->get('/activity-log', 'pages/activity_log.php', ['auth', 'admin']);
$router->get('/laporan-harian', 'pages/laporan_harian.php', ['auth']);
$router->get('/buku-besar', 'pages/buku_besar.php', ['auth']);
$router->get('/settings', 'pages/settings.php', ['auth']);
$router->get('/users', 'pages/users.php', ['auth', 'admin']); // Halaman manajemen pengguna
$router->get('/roles', 'pages/roles.php', ['auth', 'admin']);
$router->post('/roles', 'pages/roles.php', ['auth', 'admin']);
$router->get('/ksp/anggota', 'pages/ksp/anggota.php', ['auth']);
$router->get('/ksp/penarikan', 'pages/ksp/penarikan.php', ['auth']);
$router->get('/ksp/simpanan', 'pages/ksp/simpanan.php', ['auth']);
$router->get('/ksp/menu', 'pages/ksp/menu.php', ['auth']); // Menu Utama KSP
$router->get('/ksp/laporan-simpanan', 'pages/ksp/laporan_simpanan.php', ['auth']);
$router->get('/ksp/statistik', 'pages/ksp/statistik.php', ['auth']);
$router->get('/ksp/pinjaman', 'pages/ksp/pinjaman.php', ['auth']);
$router->get('/ksp/generate-qr', 'pages/ksp/generate_qr.php', ['auth']);
$router->get('/ksp/simulasi', 'pages/ksp/simulasi.php', ['auth']);
$router->get('/ksp/pengumuman', 'pages/ksp/pengumuman.php', ['auth']);
$router->get('/ksp/target-tabungan', 'pages/ksp/target_tabungan.php', ['auth']);
$router->get('/ksp/wishlist', 'pages/ksp/wishlist.php', ['auth']);
$router->get('/ksp/laporan-pinjaman', 'pages/ksp/laporan_pinjaman.php', ['auth']);
$router->get('/ksp/poin-anggota', 'pages/ksp/poin_anggota.php', ['auth']);
$router->get('/ksp/laporan-nominatif', 'pages/ksp/laporan_nominatif.php', ['auth']); // Rute baru
$router->get('/ksp/pengaturan', 'pages/ksp/pengaturan.php', ['auth']); // Rute baru

// --- Rute API (Untuk proses data via AJAX) ---
// Rute ini akan dipanggil oleh JavaScript untuk mendapatkan, menambah, mengubah, dan menghapus data tanpa reload halaman.
$router->get('/api/dashboard', 'api/dashboard_handler.php', ['auth']); // Mengambil data untuk dashboard

// API untuk Transaksi
$router->get('/api/transaksi', 'api/transaksi_handler.php', ['auth']);
$router->post('/api/transaksi', 'api/transaksi_handler.php', ['auth']);

// API untuk Wajib Belanja
$router->get('/api/wajib-belanja', 'api/wajib_belanja_handler.php', ['auth']);
$router->post('/api/wajib-belanja', 'api/wajib_belanja_handler.php', ['auth']);
$router->get('/api/laporan-wb-tahunan', 'api/laporan_wb_tahunan_handler.php', ['auth']);

// API untuk Pembelian
$router->get('/api/pembelian', 'api/pembelian_handler.php', ['auth']);
$router->post('/api/pembelian', 'api/pembelian_handler.php', ['auth']);

$router->get('/api/laporan-penjualan', 'api/laporan_penjualan_handler.php', ['auth']);
$router->get('/api/laporan-penjualan-item', 'api/laporan_penjualan_item_handler.php', ['auth']);
// API untuk Penjualan
$router->get('/api/penjualan', 'api/penjualan_handler.php', ['auth']);
$router->post('/api/penjualan', 'api/penjualan_handler.php', ['auth']);

// API untuk Barang & Stok
$router->get('/api/stok', 'api/stok_handler.php', ['auth']);
$router->post('/api/stok', 'api/stok_handler.php', ['auth']);

// API untuk fitur lainnya (Rekening, Kategori, Anggaran)
$router->get('/api/coa', 'api/coa_handler.php', ['auth']);
$router->post('/api/coa', 'api/coa_handler.php', ['auth']);
$router->get('/api/laporan/neraca', 'api/laporan_neraca_handler.php', ['auth']);
$router->get('/api/laporan/laba-rugi', 'api/laporan_laba_rugi_handler.php', ['auth']);
$router->get('/api/laporan-harian', 'api/laporan_harian_handler.php', ['auth']);
$router->get('/api/pertumbuhan_persediaan', 'api/pertumbuhan_persediaan.php', ['auth']);
$router->get('/api/laporan_stok', 'api/laporan_stok_handler.php', ['auth']);
$router->get('/api/laporan-kesehatan-bank', 'api/laporan_kesehatan_bank_handler.php', ['auth']);
$router->get('/api/csv', 'api/laporan_cetak_csv_handler.php', ['auth']); // Rute baru untuk cetak CSV
$router->get('/api/pdf', 'api/laporan_cetak_handler.php', ['auth']); // Rute baru untuk cetak PDF (GET)
$router->post('/api/pdf', 'api/laporan_cetak_handler.php', ['auth']); // Rute baru untuk cetak PDF (POST)
$router->get('/api/saldo-awal', 'api/saldo_awal_handler.php', ['auth']);
$router->post('/api/saldo-awal', 'api/saldo_awal_handler.php', ['auth']);
$router->get('/api/buku-besar-data', 'api/buku_besar_data_handler.php', ['auth']);
$router->get('/api/entri-jurnal', 'api/entri_jurnal_handler.php', ['auth']);
$router->get('/api/laporan/arus-kas', 'api/laporan_arus_kas_handler.php', ['auth']);
$router->post('/api/entri-jurnal', 'api/entri_jurnal_handler.php', ['auth']);

$router->get('/api/neraca-saldo', 'api/neraca_saldo_handler.php', ['auth']);
$router->get('/api/konsinyasi', 'api/konsinyasi_handler.php', ['auth']);
$router->post('/api/konsinyasi', 'api/konsinyasi_handler.php', ['auth']);

$router->get('/api/recurring', 'api/recurring_handler.php', ['auth']);
$router->post('/api/recurring', 'api/recurring_handler.php', ['auth']);

// API untuk Rekonsiliasi Bank
$router->get('/api/rekonsiliasi-bank', 'api/rekonsiliasi_bank_handler.php', ['auth']);
$router->post('/api/rekonsiliasi-bank', 'api/rekonsiliasi_bank_handler.php', ['auth']);
$router->get('/api/histori-rekonsiliasi', 'api/histori_rekonsiliasi_handler.php', ['auth']);
$router->post('/api/histori-rekonsiliasi', 'api/histori_rekonsiliasi_handler.php', ['auth']);

$router->get('/api/tutup-buku', 'api/tutup_buku_handler.php', ['auth', 'admin']);
$router->get('/api/laporan-laba-ditahan', 'api/laporan_laba_ditahan_handler.php', ['auth']);
$router->post('/api/tutup-buku', 'api/tutup_buku_handler.php', ['auth', 'admin']);
$router->get('/api/laporan-pertumbuhan-laba', 'api/laporan_pertumbuhan_laba_handler.php', ['auth']);
$router->get('/api/analisis-rasio', 'api/analisis_rasio_handler.php', ['auth']); // Nama file API sudah benar

$router->get('/api/activity-log', 'api/activity_log_handler.php', ['auth', 'admin']);
$router->get('/api/anggaran', 'api/anggaran_handler.php', ['auth']);
$router->post('/api/anggaran', 'api/anggaran_handler.php', ['auth']);
$router->get('/api/settings', 'api/settings_handler.php', ['auth']);
$router->post('/api/settings', 'api/settings_handler.php', ['auth']);
$router->get('/api/aset_tetap', 'api/aset_tetap_handler.php', ['auth']);
$router->post('/api/aset_tetap', 'api/aset_tetap_handler.php', ['auth']);
$router->get('/api/global-search', 'api/global_search_handler.php', ['auth']); // API untuk pencarian global
$router->get('/api/users', 'api/users_handler.php', ['auth', 'admin']); // API untuk manajemen pengguna
$router->post('/api/users', 'api/users_handler.php', ['auth', 'admin']);
$router->post('/api/my-profile/change-password', 'api/my_profile_handler.php', ['auth']);
$router->get('/api/ksp/anggota', 'api/ksp/anggota_handler.php', ['auth']);
$router->post('/api/ksp/anggota', 'api/ksp/anggota_handler.php', ['auth']);
$router->get('/api/ksp/penarikan', 'api/ksp/penarikan_handler.php', ['auth']);
$router->post('/api/ksp/penarikan', 'api/ksp/penarikan_handler.php', ['auth']);
$router->get('/api/ksp/simpanan', 'api/ksp/simpanan_handler.php', ['auth']);
$router->post('/api/ksp/simpanan', 'api/ksp/simpanan_handler.php', ['auth']);
$router->get('/api/ksp/laporan-simpanan', 'api/ksp/laporan_simpanan_handler.php', ['auth']);
$router->get('/api/ksp/pinjaman', 'api/ksp/pinjaman_handler.php', ['auth']);
$router->get('/api/ksp/notifications', 'api/ksp/notification_handler.php', ['auth']); // API Notifikasi Admin
$router->post('/api/ksp/pinjaman', 'api/ksp/pinjaman_handler.php', ['auth']);
$router->get('/api/ksp/pengumuman', 'api/ksp/pengumuman_handler.php', ['auth']);
$router->post('/api/ksp/pengumuman', 'api/ksp/pengumuman_handler.php', ['auth']);
$router->get('/api/ksp/target-tabungan', 'api/ksp/target_tabungan_handler.php', ['auth']);
$router->get('/api/ksp/wishlist', 'api/ksp/wishlist_handler.php', ['auth']);
$router->get('/api/ksp/statistik', 'api/ksp/statistik_handler.php', ['auth']);
$router->get('/api/ksp/laporan-pinjaman', 'api/ksp/laporan_pinjaman_handler.php', ['auth']);
$router->get('/api/ksp/poin-anggota', 'api/ksp/poin_anggota_handler.php', ['auth']);
$router->post('/api/ksp/poin-anggota', 'api/ksp/poin_anggota_handler.php', ['auth']);
$router->get('/api/ksp/pengaturan', 'api/ksp/pengaturan_handler.php', ['auth']); // API baru
$router->post('/api/ksp/pengaturan', 'api/ksp/pengaturan_handler.php', ['auth']); // API baru
$router->post('/api/member/login', 'api/ksp/member_auth.php'); // API Login Anggota
$router->get('/api/member/dashboard', 'api/ksp/member_dashboard_handler.php'); // API Dashboard Anggota
$router->post('/api/member/dashboard', 'api/ksp/member_dashboard_handler.php'); // API Dashboard Anggota (POST actions)
$router->post('/api/member/profile', 'api/ksp/member_profile_handler.php'); // API Profil Anggota


// Jalankan router
$router->dispatch();