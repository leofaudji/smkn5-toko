<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('pertumbuhan_persediaan', 'menu');
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-graph-up-arrow"></i> Laporan Pertumbuhan Nilai Persediaan</h1>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-3">
                <label for="lpp-tahun-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Tahun</label>
                <select id="lpp-tahun-filter" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"></select>
            </div>
            <div class="md:col-span-2">
                <button id="lpp-tampilkan-btn" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="bi bi-search mr-2"></i> Tampilkan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Grafik -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h5 class="text-lg font-medium text-gray-900 dark:text-white">Grafik Nilai Persediaan Bulanan</h5>
    </div>
    <div class="p-6">
        <canvas id="lpp-chart"></canvas>
    </div>
</div>

<!-- Tabel Detail -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h5 class="text-lg font-medium text-gray-900 dark:text-white">Detail Pertumbuhan</h5>
    </div>
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/2">Bulan</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/4">Nilai Persediaan (Hasil Opname)</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Perubahan (Selisih)</th>
                    </tr>
                </thead>
                <tbody id="lpp-report-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Data akan dimuat oleh JavaScript -->
                </tbody>
            </table>
        </div>
        <div id="lpp-loading" class="text-center p-5" style="display: none;">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Memuat data...</p>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    // Kita akan membuat file JS terpisah untuk halaman ini
    // Pastikan file ini dimuat di footer
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>
