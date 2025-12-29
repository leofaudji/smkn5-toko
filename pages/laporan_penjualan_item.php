<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('penjualan_item', 'menu');
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-graph-up"></i> Laporan Penjualan per Item</h1>
    <div class="flex mb-2 md:mb-0">
        <button type="button" class="inline-flex items-center px-3 py-2 border border-red-300 dark:border-red-600 shadow-sm text-sm font-medium rounded-md text-red-700 dark:text-red-400 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" id="export-penjualan-item-pdf">
            <i class="bi bi-file-earmark-pdf mr-2"></i> Export PDF
        </button>
    </div>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
    <div class="p-6">
        <form id="report-penjualan-item-form">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-3">
                    <label for="penjualan-item-tanggal-mulai" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                    <input type="date" id="penjualan-item-tanggal-mulai" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                </div>
                <div class="md:col-span-3">
                    <label for="penjualan-item-tanggal-akhir" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                    <input type="date" id="penjualan-item-tanggal-akhir" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                </div>
                <div class="md:col-span-4">
                    <label for="penjualan-item-sort" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Urutkan Berdasarkan</label>
                    <select id="penjualan-item-sort" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                        <option value="total_terjual" selected>Jumlah Terjual (Terlaris)</option>
                        <option value="total_penjualan">Total Penjualan (Rupiah)</option>
                        <option value="total_profit">Total Profit</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="penjualan-item-tampilkan-btn">
                        <i class="bi bi-search mr-2"></i> Tampilkan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700" id="report-penjualan-item-header">
        <h5 class="text-lg font-medium text-gray-900 dark:text-white">Produk Terlaris</h5>
    </div>
    <div class="p-6">
        <div id="report-penjualan-item-content" class="overflow-x-auto">
            <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-200 p-4 rounded-md text-center">Silakan pilih rentang tanggal dan klik "Tampilkan".</div>
        </div>
        <div class="flex justify-between items-center mt-4">
            <div id="penjualan-item-pagination-info" class="text-sm text-gray-700 dark:text-gray-300"></div>
            <div id="penjualan-item-report-pagination">
                <!-- Pagination dimuat oleh JS -->
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>