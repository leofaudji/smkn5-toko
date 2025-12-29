<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('neraca_saldo', 'menu');
?>

<div class="flex flex-wrap md:flex-nowrap justify-between items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white"><i class="bi bi-funnel mr-2"></i> Laporan Neraca Saldo</h1>
</div>

<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg mb-6">
    <div class="p-6">
        <form id="report-form">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label for="tanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Per Tanggal</label>
                    <input type="date" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm" id="tanggal" name="tanggal" value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="preview-btn">
                        <i class="bi bi-search mr-2"></i> Tampilkan Preview
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Container untuk preview dan tombol cetak -->
<div id="preview-container" class="mt-4" style="display: none;">
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white">Preview Neraca Saldo</h5>
            <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" id="print-pdf-btn">
                <i class="bi bi-printer-fill mr-2"></i> Cetak PDF
            </button>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto" id="preview-table-container">
                <!-- Tabel preview akan dirender di sini oleh JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Script khusus untuk halaman ini -->
<!-- Script dimuat melalui main.js -->

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>