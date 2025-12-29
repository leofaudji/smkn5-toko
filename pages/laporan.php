<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('laporan_keuangan', 'menu');
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-bar-chart-line-fill"></i> Laporan Keuangan</h1>
</div>

<div class="mb-4 border-b border-gray-200 dark:border-gray-700">
    <div class="-mb-px flex space-x-4" aria-label="Tabs" role="tablist" id="laporanTab">
        <button type="button" class="laporan-tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" id="neraca-tab" data-target="#neraca-pane" role="tab">Neraca</button>
        <button type="button" class="laporan-tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" id="laba-rugi-tab" data-target="#laba-rugi-pane" role="tab">Laba Rugi</button>
        <button type="button" class="laporan-tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" id="arus-kas-tab" data-target="#arus-kas-pane" role="tab">Arus Kas</button>
    </div>
</div>

<div id="laporanTabContent">
    <!-- Tab Neraca -->
    <div class="laporan-tab-pane" id="neraca-pane" role="tabpanel">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap justify-between items-center gap-4" id="neraca-header">
                <div class="flex items-center">
                    Laporan Posisi Keuangan (Neraca)
                    <span id="neraca-balance-status-badge" class="ml-2"></span>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label for="neraca-tanggal" class="text-sm font-medium text-gray-700 dark:text-gray-300">Per Tanggal:</label>
                        <input type="date" id="neraca-tanggal" class="report-filter block w-full sm:w-auto rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>
                    <div class="flex items-center">
                        <input class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary report-filter" type="checkbox" role="switch" id="neraca-include-closing" data-param="include_closing" checked>
                        <label class="ml-2 text-sm text-gray-600 dark:text-gray-300" for="neraca-include-closing">Sertakan JP</label>
                    </div>
                    <div class="relative inline-block text-left">
                        <button type="button" class="inline-flex justify-center w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-3 py-2 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600" data-dropdown-toggle="neraca-export-dropdown">
                            <i class="bi bi-download mr-2"></i> Export
                        </button>
                        <div id="neraca-export-dropdown" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 hidden z-10">
                            <div class="py-1" role="none">
                                <a href="#" id="export-neraca-pdf" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600"><i class="bi bi-file-earmark-pdf-fill text-red-500 mr-3"></i>Cetak PDF</a>
                                <a href="#" id="export-neraca-csv" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600"><i class="bi bi-file-earmark-spreadsheet-fill text-green-500 mr-3"></i>Export CSV</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6" id="neraca-content">
                <div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>
            </div>
        </div>
    </div>

    <!-- Tab Laba Rugi -->
    <div class="laporan-tab-pane hidden" id="laba-rugi-pane" role="tabpanel">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white">Laporan Laba Rugi</h5>
                <div class="relative inline-block text-left">
                    <button type="button" class="inline-flex justify-center w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-3 py-2 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600" data-dropdown-toggle="lr-export-dropdown">
                        <i class="bi bi-download mr-2"></i> Export
                    </button>
                    <div id="lr-export-dropdown" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 hidden z-10">
                        <div class="py-1" role="none">
                            <a href="#" id="export-lr-pdf" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600"><i class="bi bi-file-earmark-pdf-fill text-red-500 mr-3"></i>Cetak PDF</a>
                            <a href="#" id="export-lr-csv" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600"><i class="bi bi-file-earmark-spreadsheet-fill text-green-500 mr-3"></i>Export CSV</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end mb-4">
                    <div class="md:col-span-3">
                        <label for="laba-rugi-tanggal-mulai" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                        <input type="date" id="laba-rugi-tanggal-mulai" class="report-filter mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>
                    <div class="md:col-span-3">
                        <label for="laba-rugi-tanggal-akhir" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                        <input type="date" id="laba-rugi-tanggal-akhir" class="report-filter mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>
                    <div class="md:col-span-3">
                        <label for="lr-compare-mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bandingkan Dengan</label>
                        <select id="lr-compare-mode" class="report-filter mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                            <option value="none">Tidak Ada Perbandingan</option>
                            <option value="previous_period">Periode Sebelumnya</option>
                            <option value="previous_year_month">Bulan yang Sama Tahun Lalu</option>
                            <option value="custom">Periode Kustom</option>
                        </select>
                    </div>
                    <div class="md:col-span-3 flex items-end space-x-4 pb-1">
                        <div class="flex items-center">
                            <input class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary report-filter" type="checkbox" role="switch" id="lr-common-size-switch">
                            <label class="ml-2 text-sm text-gray-600 dark:text-gray-300" for="lr-common-size-switch">Analisis Vertikal</label>
                        </div>
                        <div class="flex items-center">
                            <input class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary report-filter" type="checkbox" role="switch" id="lr-include-closing" data-param="include_closing" checked>
                            <label class="ml-2 text-sm text-gray-600 dark:text-gray-300" for="lr-include-closing" title="Sertakan Jurnal Penutup">Sertakan JP</label>
                        </div>
                    </div>
                </div>
                <!-- Container untuk filter periode kustom, awalnya tersembunyi -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-2 hidden" id="lr-period-2">
                    <div class="md:col-span-3 md:col-start-7">
                        <label for="laba-rugi-tanggal-mulai-2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dari Tanggal (Pembanding)</label>
                        <input type="date" id="laba-rugi-tanggal-mulai-2" class="report-filter mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>
                    <div class="md:col-span-3">
                        <label for="laba-rugi-tanggal-akhir-2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal (Pembanding)</label>
                        <input type="date" id="laba-rugi-tanggal-akhir-2" class="report-filter mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>
                </div>
                <div class="mt-4" id="laba-rugi-content">
                    <div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Arus Kas -->
    <div class="laporan-tab-pane hidden" id="arus-kas-pane" role="tabpanel">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap justify-between items-center gap-4">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white">Laporan Arus Kas</h5>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label for="arus-kas-tanggal-mulai" class="text-sm font-medium text-gray-700 dark:text-gray-300">Dari:</label>
                        <input type="date" id="arus-kas-tanggal-mulai" class="report-filter block w-full sm:w-auto rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="arus-kas-tanggal-akhir" class="text-sm font-medium text-gray-700 dark:text-gray-300">Sampai:</label>
                        <input type="date" id="arus-kas-tanggal-akhir" class="report-filter block w-full sm:w-auto rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>
                    <div class="flex items-center">
                        <input class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary report-filter" type="checkbox" role="switch" id="ak-include-closing" data-param="include_closing" checked>
                        <label class="ml-2 text-sm text-gray-600 dark:text-gray-300" for="ak-include-closing">Sertakan JP</label>
                    </div>
                    <div class="relative inline-block text-left">
                        <button type="button" class="inline-flex justify-center w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-3 py-2 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600" data-dropdown-toggle="ak-export-dropdown">
                            <i class="bi bi-download mr-2"></i> Export
                        </button>
                        <div id="ak-export-dropdown" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 hidden z-10">
                            <div class="py-1" role="none">
                                <a href="#" id="export-ak-pdf" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600"><i class="bi bi-file-earmark-pdf-fill text-red-500 mr-3"></i>Cetak PDF</a>
                                <a href="#" id="export-ak-csv" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600"><i class="bi bi-file-earmark-spreadsheet-fill text-green-500 mr-3"></i>Export CSV</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6" id="arus-kas-content">
                <div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Tooltip Element -->
<div id="custom-tooltip" class="hidden absolute z-20 p-3 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-sm" role="tooltip">
    <div id="custom-tooltip-content"></div>
    <div class="tooltip-arrow" data-popper-arrow></div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>