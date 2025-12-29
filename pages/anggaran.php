<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('anggaran', 'menu');
?>

<div class="flex flex-wrap md:flex-nowrap justify-between items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white"><i class="bi bi-bullseye mr-2"></i> Anggaran vs. Realisasi</h1>
    <div class="flex items-center gap-2">
        <div class="relative" data-controller="dropdown">
            <button onclick="toggleDropdown(this)" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                <i class="bi bi-download"></i> Export
            </button>
            <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-10">
                <div class="py-1">
                    <a href="#" id="export-anggaran-pdf" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="bi bi-file-earmark-pdf-fill text-red-500 mr-3"></i>Cetak PDF</a>
                    <a href="#" id="export-anggaran-csv" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"><i class="bi bi-file-earmark-spreadsheet-fill text-green-500 mr-3"></i>Export CSV</a>
                </div>
            </div>
        </div>
        <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none" id="manage-anggaran-btn">
            <i class="bi bi-pencil-square mr-2"></i> Kelola Anggaran
        </button>
    </div>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg mb-6">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-4">
                <label for="anggaran-bulan-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bulan</label>
                <select id="anggaran-bulan-filter" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"></select>
            </div>
            <div class="md:col-span-4">
                <label for="anggaran-tahun-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tahun</label>
                <select id="anggaran-tahun-filter" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"></select>
            </div>
            <div class="md:col-span-2 flex items-center">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="anggaran-compare-switch" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">Bandingkan</span>
                </label>
            </div>
            <div class="md:col-span-2">
                <button class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none" id="anggaran-tampilkan-btn"><i class="bi bi-search mr-2"></i> Tampilkan</button>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <h6 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Total Anggaran</h6>
        <h4 class="text-2xl font-bold text-gray-900 dark:text-white" id="summary-total-anggaran"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div></h4>
    </div>
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <h6 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Total Realisasi</h6>
        <h4 class="text-2xl font-bold text-gray-900 dark:text-white" id="summary-total-realisasi"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div></h4>
    </div>
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <h6 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Sisa Anggaran</h6>
        <h4 class="text-2xl font-bold text-gray-900 dark:text-white" id="summary-sisa-anggaran"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div></h4>
    </div>
</div>

<!-- Trend Chart -->
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg mb-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h5 class="text-lg font-semibold text-gray-900 dark:text-white">Grafik Tren Anggaran vs. Realisasi Bulanan</h5>
    </div>
    <div class="p-6">
        <canvas id="anggaran-trend-chart"></canvas>
    </div>
</div>

<!-- Chart -->
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg mb-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h5 class="text-lg font-semibold text-gray-900 dark:text-white">Grafik Perbandingan Anggaran vs. Realisasi</h5>
    </div>
    <div class="p-6">
        <canvas id="anggaran-chart"></canvas>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead id="anggaran-report-table-header" class="bg-gray-50 dark:bg-gray-700"></thead>
                <tbody id="anggaran-report-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal untuk Manajemen Anggaran -->
<div id="anggaranModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="anggaranModalLabel" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('anggaranModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="anggaranModalLabel">Kelola Anggaran Tahunan (<span id="modal-tahun-label"></span>)</h5>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('anggaranModal')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4 text-sm">
                    Masukkan total anggaran untuk <strong>satu tahun</strong>. Sistem akan membaginya secara otomatis menjadi anggaran bulanan.
                </div>
                <form id="anggaran-management-form">
                    <div id="anggaran-management-container" class="space-y-3">
                        <!-- Konten dimuat oleh JS -->
                    </div>
                </form>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-dark focus:outline-none sm:ml-3 sm:w-auto sm:text-sm" id="save-anggaran-btn">Simpan Anggaran</button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('anggaranModal')">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>