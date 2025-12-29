<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('tutup_buku', 'menu');
?>

<div class="flex flex-wrap md:flex-nowrap justify-between items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white"><i class="bi bi-archive-fill mr-2"></i> Tutup Buku Periodik</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <div class="lg:col-span-7">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">Proses Tutup Buku</h5>
            </div>
            <div class="p-6">
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p class="font-bold">PERHATIAN!</p>
                    <p>Proses tutup buku akan membuat Jurnal Penutup untuk menolkan saldo akun Pendapatan dan Beban, lalu memindahkannya ke Laba Ditahan. Proses ini sebaiknya dilakukan di akhir periode (misal: 31 Desember) dan tidak dapat dibatalkan dengan mudah.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label for="closing-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Tutup Buku</label>
                        <input type="date" id="closing-date" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm">
                    </div>
                    <div class="md:col-span-1">
                        <button id="process-closing-btn" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="bi bi-lock-fill mr-2"></i> Proses Tutup Buku
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="lg:col-span-5">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="bi bi-clock-history mr-2"></i> Histori Jurnal Penutup</h5>
            </div>
            <div class="p-6">
                <div id="closing-history-container" class="space-y-3">
                    <div class="text-center p-4"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary mx-auto"></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>