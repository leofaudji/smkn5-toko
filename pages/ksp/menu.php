<?php
if (!defined('PROJECT_ROOT')) exit('No direct script access allowed');

$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

$menu_items = [
    ['title' => 'Pendaftaran Anggota', 'url' => '/ksp/anggota', 'icon' => 'bi-person-plus-fill', 'color' => 'text-blue-600', 'bg' => 'bg-blue-100 dark:bg-blue-900/30'],
    ['title' => 'Statistik KSP', 'url' => '/ksp/statistik', 'icon' => 'bi-graph-up-arrow', 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100 dark:bg-indigo-900/30'],
    ['title' => 'Simpanan Anggota', 'url' => '/ksp/simpanan', 'icon' => 'bi-piggy-bank-fill', 'color' => 'text-green-600', 'bg' => 'bg-green-100 dark:bg-green-900/30'],
    ['title' => 'Persetujuan Penarikan', 'url' => '/ksp/penarikan', 'icon' => 'bi-cash-coin', 'color' => 'text-orange-600', 'bg' => 'bg-orange-100 dark:bg-orange-900/30'],
    ['title' => 'Pinjaman Anggota', 'url' => '/ksp/pinjaman', 'icon' => 'bi-credit-card-2-front-fill', 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100 dark:bg-indigo-900/30'],
    ['title' => 'Simulasi Kredit', 'url' => '/ksp/simulasi', 'icon' => 'bi-calculator-fill', 'color' => 'text-teal-600', 'bg' => 'bg-teal-100 dark:bg-teal-900/30'],
    ['title' => 'Generate QR Bayar', 'url' => '/ksp/generate-qr', 'icon' => 'bi-qr-code', 'color' => 'text-gray-700 dark:text-gray-300', 'bg' => 'bg-gray-200 dark:bg-gray-700'],
    ['title' => 'Pengumuman KSP', 'url' => '/ksp/pengumuman', 'icon' => 'bi-megaphone-fill', 'color' => 'text-red-600', 'bg' => 'bg-red-100 dark:bg-red-900/30'],
    ['title' => 'Poin & Reward', 'url' => '/ksp/poin-anggota', 'icon' => 'bi-star-fill', 'color' => 'text-yellow-500', 'bg' => 'bg-yellow-100 dark:bg-yellow-900/30'],
    ['title' => 'Laporan Simpanan', 'url' => '/ksp/laporan-simpanan', 'icon' => 'bi-journal-text', 'color' => 'text-purple-600', 'bg' => 'bg-purple-100 dark:bg-purple-900/30'],
    ['title' => 'Laporan Pinjaman', 'url' => '/ksp/laporan-pinjaman', 'icon' => 'bi-journal-medical', 'color' => 'text-pink-600', 'bg' => 'bg-pink-100 dark:bg-pink-900/30'],
    ['title' => 'Laporan Nominatif', 'url' => '/ksp/laporan-nominatif', 'icon' => 'bi-file-earmark-spreadsheet-fill', 'color' => 'text-cyan-600', 'bg' => 'bg-cyan-100 dark:bg-cyan-900/30'],
    ['title' => 'Pengaturan KSP', 'url' => '/ksp/pengaturan', 'icon' => 'bi-gear-fill', 'color' => 'text-slate-600 dark:text-slate-400', 'bg' => 'bg-slate-200 dark:bg-slate-700'],
];
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <i class="bi bi-grid-fill text-primary"></i> Menu Simpan Pinjam
            </h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Akses cepat ke seluruh fitur Koperasi Simpan Pinjam.</p>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php foreach ($menu_items as $item): ?>
            <div class="relative">
                <a href="<?= base_url($item['url']) ?>" class="group block bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md border border-gray-100 dark:border-gray-700 transition-all duration-200 p-6 text-center hover:-translate-y-1">
                    <div class="relative inline-flex items-center justify-center w-16 h-16 rounded-full mb-4 <?= $item['bg'] ?> <?= $item['color'] ?> group-hover:scale-110 transition-transform duration-200">
                        <i class="bi <?= $item['icon'] ?> text-3xl"></i>
                        <span class="ksp-menu-badge hidden absolute -top-1 -right-1 px-2 py-0.5 text-xs font-bold text-white bg-red-600 rounded-full border-2 border-white dark:border-gray-800 shadow-sm animate-badge-blink cursor-pointer hover:scale-110 transition-transform z-10" data-url="<?= $item['url'] ?>" onclick="toggleBadgeDropdown(event, this)">0</span>
                    </div>
                    <h3 class="text-gray-800 dark:text-white font-semibold text-sm md:text-base group-hover:text-primary transition-colors">
                        <?= $item['title'] ?>
                    </h3>
                </a>
                <!-- Dropdown Notification -->
                <div class="ksp-dropdown hidden absolute top-20 left-1/2 transform -translate-x-1/2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden" data-url="<?= $item['url'] ?>">
                    <div class="bg-gray-50 dark:bg-gray-700 px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-300 border-b border-gray-200 dark:border-gray-600">
                        Notifikasi Baru
                    </div>
                    <div class="ksp-dropdown-content max-h-60 overflow-y-auto"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>