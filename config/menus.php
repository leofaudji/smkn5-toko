<?php
// Konfigurasi Struktur Menu Aplikasi
// Key harus unik untuk setiap item menu

return [
    ['type' => 'item', 'key' => 'dashboard', 'label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'bi bi-speedometer2'],
    ['type' => 'item', 'key' => 'buku_panduan', 'label' => 'Buku Panduan', 'url' => '/buku-panduan', 'icon' => 'bi bi-question-circle-fill'],
    
    ['type' => 'header', 'label' => 'Aktivitas Utama'],
    
    ['type' => 'collapse', 'key' => 'transaksi', 'label' => 'Transaksi', 'icon' => 'bi bi-pencil-square', 'children' => [
        ['key' => 'penjualan', 'label' => 'Penjualan', 'url' => '/penjualan'],
        ['key' => 'pembelian', 'label' => 'Pembelian', 'url' => '/pembelian'],
        ['key' => 'transaksi_kas', 'label' => 'Transaksi Kas', 'url' => '/transaksi'],
        ['key' => 'entri_jurnal', 'label' => 'Entri Jurnal', 'url' => '/entri-jurnal'],
    ]],
    
    ['type' => 'collapse', 'key' => 'akuntansi', 'label' => 'Akuntansi', 'icon' => 'bi bi-calculator', 'children' => [
        ['key' => 'coa', 'label' => 'Bagan Akun (COA)', 'url' => '/coa'],
        ['key' => 'saldo_awal', 'label' => 'Saldo Awal', 'url' => '/saldo-awal'],
        ['key' => 'anggaran', 'label' => 'Anggaran', 'url' => '/anggaran'],
        ['key' => 'daftar_jurnal', 'label' => 'Daftar Jurnal', 'url' => '/daftar-jurnal'],
        ['key' => 'buku_besar', 'label' => 'Buku Besar', 'url' => '/buku-besar'],
    ]],

    ['type' => 'collapse', 'key' => 'stok', 'label' => 'Stok & Inventaris', 'icon' => 'bi bi-box-seam', 'children' => [
        ['key' => 'barang_stok', 'label' => 'Barang & Stok', 'url' => '/stok'],
        ['key' => 'stok_opname', 'label' => 'Stok Opname', 'url' => '/stok-opname'],
        ['key' => 'laporan_stok', 'label' => 'Laporan Stok', 'url' => '/laporan-stok'],
        ['key' => 'kartu_stok', 'label' => 'Kartu Stok', 'url' => '/laporan-kartu-stok'],
        ['key' => 'nilai_persediaan', 'label' => 'Nilai Persediaan', 'url' => '/laporan-persediaan'],
        ['key' => 'pertumbuhan_persediaan', 'label' => 'Pertumbuhan Persediaan', 'url' => '/laporan-pertumbuhan-persediaan'],
        ['key' => 'aset_tetap', 'label' => 'Aset Tetap', 'url' => '/aset-tetap'],
    ]],

    ['type' => 'collapse', 'key' => 'laporan', 'label' => 'Laporan', 'icon' => 'bi bi-bar-chart-line-fill', 'children' => [
        ['key' => 'laporan_harian', 'label' => 'Laporan Harian', 'url' => '/laporan-harian'],
        ['key' => 'penjualan_item', 'label' => 'Penjualan per Item', 'url' => '/laporan-penjualan-item'],
        ['key' => 'laporan_penjualan', 'label' => 'Laporan Penjualan', 'url' => '/laporan-penjualan'],
        ['key' => 'laporan_keuangan', 'label' => 'Laporan Keuangan', 'url' => '/laporan'],
        ['key' => 'neraca_saldo', 'label' => 'Neraca Saldo', 'url' => '/neraca-saldo'],
        ['key' => 'perubahan_laba', 'label' => 'Perubahan Laba', 'url' => '/laporan-laba-ditahan'],
        ['key' => 'pertumbuhan_laba', 'label' => 'Pertumbuhan Laba', 'url' => '/laporan-pertumbuhan-laba'],
        ['key' => 'analisis_rasio', 'label' => 'Analisis Rasio', 'url' => '/analisis-rasio'],
    ]],

    ['type' => 'collapse', 'key' => 'tools', 'label' => 'Alat & Proses', 'icon' => 'bi bi-tools', 'children' => [
        ['key' => 'transaksi_berulang', 'label' => 'Transaksi Berulang', 'url' => '/transaksi-berulang'],
        ['key' => 'rekonsiliasi_bank', 'label' => 'Rekonsiliasi Bank', 'url' => '/rekonsiliasi-bank'],
    ]],

    ['type' => 'header', 'label' => 'Administrasi'],

    ['type' => 'item', 'key' => 'users', 'label' => 'Users', 'url' => '/users', 'icon' => 'bi bi-people-fill'],
    ['type' => 'item', 'key' => 'roles', 'label' => 'Manajemen Role', 'url' => '/roles', 'icon' => 'bi bi-shield-lock-fill'],
    ['type' => 'item', 'key' => 'activity_log', 'label' => 'Log Aktivitas', 'url' => '/activity-log', 'icon' => 'bi bi-list-check'],
    ['type' => 'item', 'key' => 'tutup_buku', 'label' => 'Tutup Buku', 'url' => '/tutup-buku', 'icon' => 'bi bi-archive-fill'],
    ['type' => 'item', 'key' => 'settings', 'label' => 'Pengaturan', 'url' => '/settings', 'icon' => 'bi bi-gear-fill'],
];
