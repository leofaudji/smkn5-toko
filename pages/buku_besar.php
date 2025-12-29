<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('buku_besar', 'menu');
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-book"></i> Buku Besar (General Ledger)</h1>
    <div class="flex mb-2 md:mb-0 gap-2">
        <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none" id="export-bb-pdf">
            <i class="bi bi-file-earmark-pdf-fill text-red-600 mr-2"></i> PDF
        </button>
        <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none" id="export-bb-csv">
            <i class="bi bi-file-earmark-spreadsheet-fill text-green-600 mr-2"></i> CSV
        </button>
    </div>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="bb-akun-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Akun</label>
                <select id="bb-akun-filter" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    <option value="">Memuat akun...</option>
                </select>
            </div>
            <div>
                <label for="bb-tanggal-mulai" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                <input type="date" id="bb-tanggal-mulai" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
            </div>
            <div>
                <label for="bb-tanggal-akhir" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                <input type="date" id="bb-tanggal-akhir" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
            </div>
            <div>
                <button class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="bb-tampilkan-btn">
                    <i class="bi bi-search mr-2"></i> Tampilkan
                </button>
            </div>
        </div>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="bb-report-header">Laporan Buku Besar</h5>
    </div>
    <div class="p-6">
        <div class="overflow-x-auto" id="bb-report-content">
            <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-200 p-4 rounded-md text-center">
                Silakan pilih akun dan rentang tanggal, lalu klik "Tampilkan".
            </div>
        </div>
    </div>
</div>


<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>