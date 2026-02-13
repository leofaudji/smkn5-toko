<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('laporan_wb_tahunan', 'menu');
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
        <i class="bi bi-calendar3"></i> Rekap Wajib Belanja Tahunan
    </h1>
    <div class="flex items-center gap-2">
        <button onclick="window.print()" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none">
            <i class="bi bi-printer mr-2"></i> Cetak
        </button>
    </div>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6 print:hidden">
    <div class="p-6">
        <div class="flex items-end gap-4">
            <div class="w-48">
                <label for="laporan-wb-tahun" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Tahun</label>
                <select id="laporan-wb-tahun" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    <!-- Opsi tahun akan diisi JS -->
                </select>
            </div>
            <div class="flex items-center pb-2">
                <input id="filter-tunggakan" type="checkbox" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary dark:bg-gray-700 dark:border-gray-600">
                <label for="filter-tunggakan" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Hanya yang Menunggak</label>
            </div>
            <div>
                <button id="btn-tampilkan-laporan" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="bi bi-search mr-2"></i> Tampilkan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Laporan -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="laporan-title">Laporan Wajib Belanja Tahun <?= date('Y') ?></h5>
        <div class="text-xs space-x-3 flex items-center">
            <span class="flex items-center"><span class="w-2 h-2 rounded-full bg-green-500 mr-1"></span> Lunas/Aman</span>
            <span class="flex items-center"><span class="w-2 h-2 rounded-full bg-yellow-500 mr-1"></span> Kurang Bayar</span>
            <span class="flex items-center"><span class="w-2 h-2 rounded-full bg-red-500 mr-1"></span> Menunggak</span>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-3 py-3 text-left font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider sticky left-0 bg-gray-50 dark:bg-gray-700 z-10">Anggota</th>
                    <?php 
                    $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                    foreach ($bulan as $b) {
                        echo '<th scope="col" class="px-2 py-3 text-right font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">' . $b . '</th>';
                    }
                    ?>
                    <th scope="col" class="px-3 py-3 text-right font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider font-bold">Total</th>
                    <th scope="col" class="px-3 py-3 text-right font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wider font-bold">Belanja</th>
                    <th scope="col" class="px-3 py-3 text-right font-medium text-red-600 dark:text-red-400 uppercase tracking-wider font-bold">Tunggakan</th>
                    <th scope="col" class="px-3 py-3 text-right font-medium text-green-600 dark:text-green-400 uppercase tracking-wider font-bold">Sisa Saldo</th>
                </tr>
            </thead>
            <tbody id="laporan-wb-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <!-- Data akan dimuat di sini -->
                <tr><td colspan="17" class="text-center p-4">Silakan pilih tahun dan klik Tampilkan.</td></tr>
            </tbody>
            <tfoot id="laporan-wb-footer" class="bg-gray-100 dark:bg-gray-900 font-semibold text-gray-700 dark:text-gray-300">
                <!-- Total footer akan dimuat di sini -->
            </tfoot>
        </table>
    </div>
    <div id="laporan-loading" class="text-center p-5 hidden">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Memuat data...</p>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>
