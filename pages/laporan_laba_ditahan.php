<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('perubahan_laba', 'menu');
?>

<div class="flex justify-between flex-wrap md:flex-nowrap items-center pt-3 pb-2 mb-3 border-b border-gray-200">
    <h1 class="text-2xl font-semibold text-gray-800"><i class="bi bi-graph-up mr-2"></i> Laporan Perubahan Laba Ditahan</h1>
    <div class="flex space-x-2 mb-2 md:mb-0">
        <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="export-re-pdf">
            <i class="bi bi-file-earmark-pdf-fill text-red-600 mr-2"></i> PDF
        </button>
        <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="export-re-csv">
            <i class="bi bi-file-earmark-spreadsheet-fill text-green-600 mr-2"></i> CSV
        </button>
    </div>
</div>

<!-- Filter -->
<div class="bg-white shadow rounded-lg mb-6">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
            <div>
                <label for="re-tanggal-mulai" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                <input type="date" id="re-tanggal-mulai" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
            </div>
            <div>
                <label for="re-tanggal-akhir" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                <input type="date" id="re-tanggal-akhir" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
            </div>
            <div>
                <button class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="re-tampilkan-btn">
                    <i class="bi bi-search mr-2"></i> Tampilkan Laporan
                </button>
            </div>
        </div>
    </div>
</div>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 font-semibold text-gray-800" id="re-report-header">
        Laporan Perubahan Laba Ditahan
    </div>
    <div class="p-6">
        <div class="overflow-x-auto" id="re-report-content">
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 text-blue-700 text-center">
                Silakan pilih rentang tanggal, lalu klik "Tampilkan Laporan".
            </div>
        </div>
    </div>
</div>


<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>