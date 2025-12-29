<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('nilai_persediaan', 'menu');
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-archive-fill"></i> Laporan Nilai Persediaan</h1>
    <div class="flex mb-2 md:mb-0 gap-2">
        <button id="printButton" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none">
            <i class="bi bi-printer-fill text-gray-600 dark:text-gray-400 mr-2"></i> Cetak
        </button>
        <button id="exportButton" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <i class="bi bi-file-earmark-spreadsheet-fill mr-2"></i> Export Excel
        </button>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 items-center">
            <div>
                <input type="text" id="searchInput" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="Cari berdasarkan Nama atau SKU...">
            </div>
            <div class="md:text-right">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white">Total Nilai Persediaan: 
                    <span id="totalInventoryValueHeader" class="font-bold text-green-600 dark:text-green-400">Rp 0</span>
                </h5>
            </div>
        </div>
        
        <div class="overflow-x-auto" style="max-height: 65vh;">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
               <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                    <tr>
                        <th class="px-6 py-3 text-center text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12">No</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Barang</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">SKU</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stok Sistem</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga Beli (Rp)</th>
                        <th class="px-6 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nilai Persediaan (Rp)</th>
                    </tr>
                </thead>
                <tbody id="reportTableBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Data akan dimuat oleh JavaScript -->
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-700 font-bold text-sm text-gray-700 dark:text-gray-300 sticky bottom-0">
                    <tr>
                        <td colspan="5" class="px-6 py-3 text-right">Total Nilai Persediaan</td>
                        <td id="totalInventoryValue" class="px-6 py-3 text-right">Rp 0</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div id="loadingIndicator" class="text-center p-5" style="display: none;">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Memuat data...</p>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>