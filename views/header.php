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

// Detect App Version from CHANGELOG.md
$app_version = 'v1.0.0';
$changelog_file = PROJECT_ROOT . '/CHANGELOG.md';
if (file_exists($changelog_file)) {
    $changelog_content = file_get_contents($changelog_file);
    if (preg_match('/##\s+\[(.*?)\]/', $changelog_content, $matches)) {
        $app_version = 'v' . $matches[1];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $app_name ?></title>    
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <?php $v=date("Ymd"); ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css?v='.$v) ?>">
    <!-- Favicon  -->
    <link rel="icon" href="assets/favicon.png" />
    <script>
        // Konfigurasi Tailwind
        tailwind.config = {
            darkMode: 'class', // atau 'media'
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: 'var(--theme-color, #007aff)',
                            '50': 'var(--theme-color-50, #e6f2ff)',
                            '100': 'var(--theme-color-100, #b3d9ff)',
                            '500': 'var(--theme-color-500, #007aff)',
                            '600': 'var(--theme-color-600, #006de6)',
                        },
                    },
                    fontFamily: {
                        sans: ['-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', 'Helvetica', 'Arial', 'sans-serif', '"Apple Color Emoji"', '"Segoe UI Emoji"', '"Segoe UI Symbol"'],
                    }
                }
            }
        }

        const userRole = '<?= $_SESSION['role'] ?? 'warga' ?>';
        const username = '<?= $_SESSION['username'] ?? '' ?>';
        const currentUserId = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
        const basePath = '<?= BASE_PATH ?>';
        const notificationInterval = <?= $notification_interval ?>;
        const logCleanupDays = <?= $log_cleanup_days ?>;
        window.appSettings = <?php echo json_encode($app_settings); ?>;
        window.jsVersion = '<?= date("YmdH") ?>';
    </script>
    <style>
        /* Custom scrollbar untuk sidebar */
        .sidebar-scroll::-webkit-scrollbar { width: 6px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background-color: rgba(0,0,0,0.2); border-radius: 10px; }
        html.dark .sidebar-scroll::-webkit-scrollbar-thumb { background-color: rgba(255,255,255,0.2); }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 font-sans">
<div id="app-container" class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transform -translate-x-full lg:translate-x-0 lg:static lg:inset-0 transition-transform duration-300 ease-in-out flex flex-col">
        <!-- Sidebar Header -->
        <div class="flex items-center justify-center h-16 border-b border-gray-200 dark:border-gray-700 px-4 flex-shrink-0">
            <a href="<?= base_url('/dashboard') ?>" class="flex items-center gap-2 text-xl font-bold text-gray-800 dark:text-white truncate">
                <?php
                $logo_path = $app_settings['app_logo'] ?? null;
                $logo_url = $logo_path ? base_url($logo_path) : base_url('assets/img/logo.png');
                ?>
                <img src="<?= $logo_url ?>" alt="Logo" class="h-8 w-8 object-contain rounded">
                <div class="flex flex-col leading-tight hidden-in-collapsed">
                    <span class="text-gray-800 dark:text-white"><?= $app_name ?></span>
                    <span class="text-[10px] font-medium text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-0.5"><?= $app_version ?></span>
                </div>
            </a>
        </div>

        <!-- Sidebar Menu -->
        <nav class="flex-1 overflow-y-auto sidebar-scroll py-4 px-2 space-y-0.5">
            <?php require_once __DIR__ . '/_menu_items.php'; ?>
        </nav>
    </aside>

    <!-- Mobile Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Top Navbar -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-4 lg:px-6 flex-shrink-0 z-20">
            <!-- Left side: Toggle & Title -->
            <div class="flex items-center">
                <button onclick="toggleSidebar()" class="text-gray-500 dark:text-gray-400 focus:outline-none p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="bi bi-list text-2xl"></i>
                </button>
                <h1 id="page-title" class="text-xl font-semibold text-gray-800 dark:text-white ml-3 hidden sm:block">Dashboard</h1>
            </div>

            <!-- Right side: Clock, Search, Profile -->
            <div class="flex items-center space-x-2">
                <div id="live-clock" class="text-gray-600 dark:text-gray-400 text-sm font-semibold hidden md:block"></div>

                <!-- Profile Dropdown -->
                <div class="relative" data-controller="dropdown">
                    <button onclick="toggleDropdown(this)" class="flex items-center space-x-2 p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="bi bi-person-circle text-xl"></i>
                        <span class="hidden md:inline"><?= htmlspecialchars($_SESSION['username']) ?></span>
                        <i class="bi bi-chevron-down text-xs"></i>
                    </button>
                    <div class="dropdown-menu hidden absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                        <a href="#" id="theme-switcher" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="bi bi-moon-stars-fill me-2"></i><span id="theme-switcher-text">Mode Gelap</span>
                        </a>
                        <div class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                            <label for="theme-color-picker" class="flex items-center"><i class="bi bi-palette-fill me-2"></i>Warna Tema</label>
                            <input type="color" id="theme-color-picker" class="w-8 h-8 p-0 border-none rounded" value="#007aff" title="Pilih warna tema Anda">
                        </div>
                        <a href="<?= base_url('/my-profile/change-password') ?>" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="bi bi-key-fill me-2"></i>Ganti Password
                        </a>
                        <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                        <a href="<?= base_url('/logout') ?>" data-spa-ignore id="logout-link" class="flex items-center px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/50">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Scrollable Area -->
        <main id="main-content" class="main-content flex-1 overflow-y-auto p-4 sm:p-6">