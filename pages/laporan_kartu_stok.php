<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('kartu_stok', 'menu');
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-card-list"></i> Laporan Kartu Stok</h1>
    <div class="flex mb-2 md:mb-0">
        <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none" id="export-kartu-stok-pdf" style="display: none;">
            <i class="bi bi-file-earmark-pdf-fill text-red-600 mr-2"></i> PDF
        </button>
    </div>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
    <div class="p-6">
        <form id="kartu-stok-form">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-4">
                    <label for="ks-item-id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Barang</label>
                    <select id="ks-item-id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" required>
                        <option value="">Memuat barang...</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label for="ks-tanggal-mulai" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                    <input type="date" id="ks-tanggal-mulai" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                </div>
                <div class="md:col-span-3">
                    <label for="ks-tanggal-akhir" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                    <input type="date" id="ks-tanggal-akhir" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="ks-tampilkan-btn">
                        <i class="bi bi-search mr-2"></i> Tampilkan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700" id="report-ks-header" style="display: none;">
        <h5 class="text-lg font-medium text-gray-900 dark:text-white">Kartu Stok: <span id="ks-item-name"></span></h5>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Periode: <span id="ks-period"></span></p>
    </div>
    <div class="p-6">
        <div id="report-ks-summary" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 text-center" style="display: none;">
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg"><div class="text-sm text-gray-500 dark:text-gray-400">Saldo Awal</div><div id="ks-summary-awal" class="text-xl font-bold text-gray-900 dark:text-white">0</div></div>
            <div class="p-4 bg-green-50 dark:bg-green-900/30 rounded-lg"><div class="text-sm text-green-600 dark:text-green-400">Total Masuk</div><div id="ks-summary-masuk" class="text-xl font-bold text-green-700 dark:text-green-300">0</div></div>
            <div class="p-4 bg-red-50 dark:bg-red-900/30 rounded-lg"><div class="text-sm text-red-600 dark:text-red-400">Total Keluar</div><div id="ks-summary-keluar" class="text-xl font-bold text-red-700 dark:text-red-300">0</div></div>
            <div class="p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg"><div class="text-sm text-blue-600 dark:text-blue-400">Saldo Akhir</div><div id="ks-summary-akhir" class="text-xl font-bold text-blue-700 dark:text-blue-300">0</div></div>
        </div>
        <div id="report-ks-content" class="overflow-x-auto">
            <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-200 p-4 rounded-md text-center">
                Silakan pilih barang dan rentang tanggal, lalu klik "Tampilkan".
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>