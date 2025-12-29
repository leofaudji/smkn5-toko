<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('laporan_harian', 'menu');
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-calendar-day"></i> Laporan Transaksi Harian</h1>
    <div class="flex mb-2 md:mb-0 gap-2">
        <button id="export-lh-pdf" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none">
            <i class="bi bi-file-earmark-pdf-fill text-red-600 mr-2"></i> Cetak PDF
        </button>
        <button id="export-lh-csv" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <i class="bi bi-file-earmark-spreadsheet-fill mr-2"></i> Export CSV
        </button>
    </div>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6 p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
        <div>
            <label for="lh-tanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pilih Tanggal</label>
            <input type="date" id="lh-tanggal" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
        </div>
        <div>
            <button class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="lh-tampilkan-btn">
                <i class="bi bi-search mr-2"></i> Tampilkan Laporan
            </button>
        </div>
    </div>
</div>

<!-- Chart and Summary Row -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="md:col-span-2">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg h-full">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-800 dark:text-white">
                Ringkasan Transaksi
            </div>
            <div class="p-6" id="lh-summary-content">
                 <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-200 p-4 rounded-md text-center">
                    Pilih tanggal untuk melihat ringkasan.
                </div>
            </div>
        </div>
    </div>
    <div>
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg h-full">
            <div class="p-4 flex justify-center items-center h-full" style="min-height: 250px;">
                <canvas id="lh-chart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-800 dark:text-white" id="lh-report-header">
        Detail Transaksi Harian
    </div>
    <div class="p-6 overflow-x-auto" id="lh-report-content">
        <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-200 p-4 rounded-md text-center">
            Silakan pilih tanggal, lalu klik "Tampilkan Laporan".
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>