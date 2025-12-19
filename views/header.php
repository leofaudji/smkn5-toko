<?php
// Ambil pengaturan aplikasi dari database untuk digunakan di seluruh UI
$app_settings = [];
$settings_conn = Database::getInstance()->getConnection();
$settings_result = $settings_conn->query("SELECT setting_key, setting_value FROM settings");
if ($settings_result) {
    while ($row = $settings_result->fetch_assoc()) {
        $app_settings[$row['setting_key']] = $row['setting_value'];
    }
}
$app_name = htmlspecialchars($app_settings['app_name'] ?? 'Aplikasi RT');
$notification_interval = (int)($app_settings['notification_interval'] ?? 15000);
$log_cleanup_days = (int)($app_settings['log_cleanup_interval_days'] ?? 180);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $app_name ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php $v=date("Ymd"); ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css?v='.$v) ?>">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
        }
        /* Membuat semua header tabel menjadi uppercase dan sedikit lebih gelap */
        .table thead th {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            background-color: #e9ecef !important; /* Warna abu-abu terang */
            /* !important digunakan untuk memastikan gaya ini menimpa gaya default dari sb-admin-2.css */
        }
        /* CSS untuk menu collapsible */
        .sidebar-nav .nav-link[data-bs-toggle="collapse"]::after {
            content: '\f282'; /* Bootstrap Icon chevron-down */
            font-family: 'bootstrap-icons';
            font-weight: bold;
            display: inline-block;
            margin-left: auto;
            transition: transform 0.2s ease-in-out;
        }
        .sidebar-nav .nav-link[data-bs-toggle="collapse"]:not(.collapsed)::after {
            transform: rotate(-180deg);
        }
        .nav-submenu {
            position: relative; /* Diperlukan untuk pseudo-element */
            list-style: none; /* Menghilangkan bullet points */
            padding: 0;
            margin: 0;
            background-color: #ffffff; /* Latar belakang submenu diubah menjadi putih */
        }
        .nav-submenu .nav-item {
            position: relative; /* Diperlukan untuk memposisikan garis horizontal */
        }
        .nav-submenu .nav-link {
            padding: 0.5rem 1rem 0.5rem 3.5rem; /* Indentasi teks submenu */
            font-size: 0.9rem; /* Ukuran font lebih kecil untuk submenu */
            border-left: 3px solid transparent; /* Garis penanda aktif di kiri */
        }
        /* Format Baru: Garis hierarki yang rapi dan modern */
        .nav-submenu .nav-item::before {
            content: '';
            position: absolute;
            left: 1.6rem; /* Posisi garis vertikal, sejajar dengan ikon induk */
            top: 0;
            height: 50%; /* Tinggi garis vertikal (setengah dari item) */
            width: 1.2rem; /* Panjang garis horizontal */
            border-left: 1px solid #dee2e6; /* Garis vertikal */
            border-bottom: 1px solid #dee2e6; /* Garis horizontal */
        }
        .nav-submenu .nav-link:hover {
            background-color: #e9ecef; /* Warna hover yang sama dengan menu utama */
        }

        /* Gaya untuk link submenu yang aktif */
        .nav-submenu .nav-link.active {
            color: var(--bs-primary);
            font-weight: bold;
            border-left-color: var(--bs-primary); /* Menandai link aktif dengan warna tema */
        }
        /* Gaya untuk menu induk yang aktif karena anaknya aktif */
        .sidebar .sidebar-nav .nav-link.active[data-bs-toggle="collapse"] {
            background-color: transparent; /* Hapus warna latar primer */
            color: #595d62ff; /* Kembalikan warna teks ke default */
            font-weight: 600; /* Beri sedikit penebalan untuk menandakan aktif */
        }
        .sidebar .sidebar-nav .nav-link.active[data-bs-toggle="collapse"] i {
            color: var(--bs-primary); /* Ubah warna ikon menjadi warna primer sebagai penanda */
        }

        /* TEMA BARU UNTUK SIDEBAR */
        .sidebar {
            background-color: #f8f9fa; /* Warna terang untuk sidebar */
            border-right: 1px solid #dee2e6;
            color: #212529;
        }
        .sidebar .navbar-brand span {
            color: #212529; /* Warna teks brand */
        }
        .sidebar .sidebar-header {
            color: #6c757d; /* Warna header grup menu */
            font-weight: 700;
        }
        .sidebar .sidebar-nav .nav-link {
            color: #343a40; /* Warna teks link */
            display: flex;
            align-items: center;
        }
        .sidebar .sidebar-nav .nav-link i {
            color: #495057; /* Warna ikon */
        }
        .sidebar .sidebar-nav .nav-link:hover {
            background-color: #e9ecef; /* Warna latar saat hover */
            color: #000;
        }
        .sidebar .sidebar-nav .nav-link.active {
            background-color: var(--bs-primary); /* Menggunakan warna tema utama */
            color: #fff;
            font-weight: 500;
        }
        .sidebar .sidebar-nav .nav-link.active i {
            color: #fff;
        }
        /* Pastikan area collapse tidak memiliki padding/margin yang mengganggu */
        .sidebar .collapse { margin: 0; padding: 0; }

    </style>
    <!-- Favicon  -->
    <link rel="icon" href="assets/favicon.png" />
    <script>
        const userRole = '<?= $_SESSION['role'] ?? 'warga' ?>';
        const username = '<?= $_SESSION['username'] ?? '' ?>';
        const basePath = '<?= BASE_PATH ?>';
        const notificationInterval = <?= $notification_interval ?>;
        const logCleanupDays = <?= $log_cleanup_days ?>;
    </script>
</head>
<body class="">
<div id="spa-loading-bar"></div>
<script>
    // Apply theme and sidebar state from localStorage
    (function() {
        const theme = localStorage.getItem('theme') || 'light';
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
        }

        const isSmallScreen = window.innerWidth <= 992;
        const storedState = localStorage.getItem('sidebar-collapsed');
        if (storedState === 'true' || (storedState === null && isSmallScreen)) {
            document.body.classList.add('sidebar-collapsed');
        }
    })();
</script>
<div class="sidebar">
    <a class="navbar-brand d-flex align-items-center" href="<?= base_url('/dashboard') ?>">
        <?php
        $logo_path = $app_settings['app_logo'] ?? null;
        $logo_url = $logo_path ? base_url($logo_path) : base_url('assets/img/logo.png');
        ?>
        <img src="<?= $logo_url ?>" alt="Logo" height="30" class="me-2 rounded">
        <span><?= $app_name ?></span>
    </a>
    <ul class="sidebar-nav">
        <!-- Menu Non-collapsible -->
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('/dashboard') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('/buku-panduan') ?>"><i class="bi bi-question-circle-fill"></i> Buku Panduan</a>
        </li>

        <!-- Grup Menu Transaksi Utama -->
        <li class="sidebar-header">Aktivitas Utama</li>
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#transaksi-menu" role="button" aria-expanded="false" aria-controls="transaksi-menu">
                <i class="bi bi-pencil-square"></i> Transaksi
            </a>
            <div class="collapse" id="transaksi-menu">
                <ul class="nav-submenu"> 
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/penjualan') ?>">Penjualan</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/pembelian') ?>">Pembelian</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/transaksi') ?>">Transaksi Kas</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/entri-jurnal') ?>">Entri Jurnal</a></li>
                </ul>
            </div>
        </li>

        <!-- Grup Menu Akuntansi -->
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#akuntansi-menu" role="button" aria-expanded="false" aria-controls="akuntansi-menu">
                <i class="bi bi-calculator"></i> Akuntansi
            </a>
            <div class="collapse" id="akuntansi-menu">
                <ul class="nav-submenu">
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/coa') ?>">Bagan Akun (COA)</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/saldo-awal-neraca') ?>">Saldo Awal Neraca</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/saldo-awal-lr') ?>">Saldo Awal L/R</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/anggaran') ?>">Anggaran</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/daftar-jurnal') ?>">Daftar Jurnal</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/buku-besar') ?>">Buku Besar</a></li>
                </ul>
            </div>
        </li>

        <!-- Grup Menu Stok & Inventaris -->
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#stok-menu" role="button" aria-expanded="false" aria-controls="stok-menu">
                <i class="bi bi-box-seam"></i> Stok & Inventaris
            </a>
            <div class="collapse" id="stok-menu">
                <ul class="nav-submenu">
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/stok') ?>">Barang & Stok</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/stok-opname') ?>">Stok Opname</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/laporan-stok') ?>">Laporan Stok</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/laporan-kartu-stok') ?>">Kartu Stok</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/laporan-persediaan') ?>">Nilai Persediaan</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/laporan-pertumbuhan-persediaan') ?>">Pertumbuhan Persediaan</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/aset-tetap') ?>">Aset Tetap</a></li>
                </ul>
            </div>
        </li>

        <!-- Grup Menu Laporan -->
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#laporan-menu" role="button" aria-expanded="false" aria-controls="laporan-menu">
                <i class="bi bi-bar-chart-line-fill"></i> Laporan
            </a>
            <div class="collapse" id="laporan-menu">
                <ul class="nav-submenu">
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/laporan-harian') ?>">Laporan Harian</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/laporan-penjualan-item') ?>">Laporan Penjualan per Item</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/laporan-penjualan') ?>">Laporan Penjualan</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/laporan') ?>">Laporan Keuangan</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/neraca-saldo') ?>">Neraca Saldo</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/laporan-laba-ditahan') ?>">Perubahan Laba</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/laporan-pertumbuhan-laba') ?>">Pertumbuhan Laba</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/analisis-rasio') ?>">Analisis Rasio</a></li>
                </ul>
            </div>
        </li>

        <!-- Grup Menu Alat & Proses -->
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#tools-menu" role="button" aria-expanded="false" aria-controls="tools-menu">
                <i class="bi bi-tools"></i> Alat & Proses
            </a>
            <div class="collapse" id="tools-menu">
                <ul class="nav-submenu">
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/transaksi-berulang') ?>">Transaksi Berulang</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('/rekonsiliasi-bank') ?>">Rekonsiliasi Bank</a></li>
                </ul>
            </div>
        </li>

        <!-- Menu Khusus Admin -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="sidebar-header">Administrasi</li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/users') ?>"><i class="bi bi-people-fill"></i> Users </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/activity-log') ?>"><i class="bi bi-list-check"></i> Log Aktivitas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/tutup-buku') ?>"><i class="bi bi-archive-fill"></i> Tutup Buku</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/settings') ?>"><i class="bi bi-gear-fill"></i> Pengaturan</a>
            </li>
        <?php endif; ?>
    </ul>
</div>
<div class="sidebar-overlay"></div>

<div class="content-wrapper">
    <nav class="top-navbar d-flex justify-content-between align-items-center">
        <!-- Left side: Sidebar Toggle -->
        <div class="d-flex align-items-center">
            <button class="btn" id="sidebar-toggle-btn" title="Toggle sidebar">
                <i class="bi bi-list fs-4"></i>
            </button>
        </div>

        <!-- Right side: Clock, Search, Notifications, Profile -->
        <div class="d-flex align-items-center">
            <div id="live-clock" class="text-muted small me-3 fw-bold d-none d-md-block">
                <!-- Clock will be inserted here by JavaScript -->
            </div>
            <!-- Global Search Button -->
            <button class="btn nav-link me-2" id="global-search-btn" data-bs-toggle="modal" data-bs-target="#globalSearchModal" title="Pencarian Global">
                <i class="bi bi-search fs-5"></i>
            </button>

            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item d-flex align-items-center" href="#" id="theme-switcher"><i class="bi bi-moon-stars-fill me-2"></i><span id="theme-switcher-text">Mode Gelap</span></a></li>
                    <li><div class="dropdown-item d-flex align-items-center justify-content-between">
                            <label for="theme-color-picker" class="d-flex align-items-center"><i class="bi bi-palette-fill me-2"></i>Warna Tema</label>
                            <input type="color" id="theme-color-picker" class="form-control form-control-color" value="#007aff" title="Pilih warna tema Anda">
                        </div>
                    </li>
                    <li><a class="dropdown-item" href="<?= base_url('/my-profile/change-password') ?>"><i class="bi bi-key-fill me-2"></i>Ganti Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= base_url('/logout') ?>" data-spa-ignore><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="main-content">

<!-- Global Search Modal -->
<div class="modal fade" id="globalSearchModal" tabindex="-1" aria-labelledby="globalSearchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="globalSearchModalLabel"><i class="bi bi-search"></i> Pencarian Global</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="input-group mb-3">
            <input type="text" class="form-control form-control-lg" id="global-search-input" placeholder="Ketik disini apa yang dicari..." autocomplete="off">
            <span class="input-group-text" id="global-search-spinner" style="display: none;"><div class="spinner-border spinner-border-sm"></div></span>
        </div>
        <div id="global-search-results">
            <p class="text-muted text-center">Masukkan kata kunci untuk memulai pencarian.</p>
        </div>
      </div>
    </div>
  </div>
</div>