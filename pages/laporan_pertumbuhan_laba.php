<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('pertumbuhan_laba', 'menu');
?>

<div class="flex flex-wrap md:flex-nowrap justify-between items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white"><i class="bi bi-graph-up-arrow mr-2"></i> Laporan Pertumbuhan Laba</h1>
    <div class="relative" data-controller="dropdown">
        <button onclick="toggleDropdown(this)" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="bi bi-download mr-2"></i>
            Export
            <i class="bi bi-chevron-down ml-2 -mr-1"></i>
        </button>
        <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-10">
            <div class="py-1">
                <a href="#" id="export-lpl-pdf" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="bi bi-file-earmark-pdf-fill text-red-500 mr-3"></i>Cetak PDF
                </a>
                <a href="#" id="export-lpl-csv" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="bi bi-file-earmark-spreadsheet-fill text-green-500 mr-3"></i>Export CSV
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg mb-6">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-2">
                <label for="lpl-tahun-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tahun</label>
                <select id="lpl-tahun-filter" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm"></select>
            </div>
            <div class="md:col-span-5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tampilan</label>
                <div class="flex rounded-md shadow-sm" role="group" id="lpl-view-mode">
                    <input type="radio" class="sr-only peer" name="view_mode" id="lpl-view-monthly" value="monthly" autocomplete="off">
                    <label for="lpl-view-monthly" class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 peer-checked:bg-primary peer-checked:text-white dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:peer-checked:bg-primary cursor-pointer">Bulanan</label>
                    
                    <input type="radio" class="sr-only peer" name="view_mode" id="lpl-view-quarterly" value="quarterly" autocomplete="off" checked>
                    <label for="lpl-view-quarterly" class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border-t border-b border-gray-200 hover:bg-gray-100 peer-checked:bg-primary peer-checked:text-white dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:peer-checked:bg-primary cursor-pointer">Triwulanan</label>
                    
                    <input type="radio" class="sr-only peer" name="view_mode" id="lpl-view-yearly" value="yearly" autocomplete="off">
                    <label for="lpl-view-yearly" class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border-t border-b border-l border-gray-200 hover:bg-gray-100 peer-checked:bg-primary peer-checked:text-white dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:peer-checked:bg-primary cursor-pointer">Tahunan</label>
                    
                    <input type="radio" class="sr-only peer" name="view_mode" id="lpl-view-cumulative" value="cumulative" autocomplete="off">
                    <label for="lpl-view-cumulative" class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md hover:bg-gray-100 peer-checked:bg-primary peer-checked:text-white dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:peer-checked:bg-primary cursor-pointer">Kumulatif</label>
                </div>
            </div>
            <div class="md:col-span-2 flex items-center justify-start">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="lpl-compare-switch" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">Bandingkan</span>
                </label>
            </div>
            <div class="md:col-span-3">
                <button class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="lpl-tampilkan-btn"><i class="bi bi-search mr-2"></i> Tampilkan</button>
            </div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg mb-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h5 class="text-lg font-semibold text-gray-900 dark:text-white">Grafik Pertumbuhan Laba Bersih</h5>
    </div>
    <div class="p-6">
        <canvas id="lpl-chart"></canvas>
    </div>
</div>

<!-- Data Table -->
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h5 class="text-lg font-semibold text-gray-900 dark:text-white">Detail Data Pertumbuhan Laba</h5>
    </div>
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead id="lpl-report-table-header" class="bg-gray-50 dark:bg-gray-700"></thead>
                <tbody id="lpl-report-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </button>
            </table>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>
