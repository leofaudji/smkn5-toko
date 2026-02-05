<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// check_permission('menu.view.laporan_simpanan'); // Uncomment jika permission sudah dibuat
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
        <i class="bi bi-file-earmark-text-fill"></i> Laporan Simpanan Anggota
    </h1>
</div>

<!-- Filter Section -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6 p-4">
    <form id="filter-form" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div class="md:col-span-2">
            <label for="anggota_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pilih Anggota</label>
            <select id="anggota_id" name="anggota_id" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" required>
                <option value="">-- Pilih Anggota --</option>
            </select>
        </div>
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dari Tanggal</label>
            <input type="date" id="start_date" name="start_date" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" value="<?= date('Y-m-01') ?>">
        </div>
        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sampai Tanggal</label>
            <input type="date" id="end_date" name="end_date" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="md:col-span-4 flex justify-end gap-2">
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-600 focus:outline-none">
                <i class="bi bi-search mr-2"></i> Tampilkan
            </button>
            <button type="button" id="btn-print-pdf" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                <i class="bi bi-printer-fill mr-2"></i> Cetak PDF
            </button>
        </div>
    </form>
</div>

<!-- Report Result -->
<div id="report-result" class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden hidden">
    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Rincian Transaksi</h3>
        <span id="saldo-awal-display" class="text-sm font-medium text-gray-600 dark:text-gray-300"></span>
    </div>
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. Ref</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Debit (Keluar)</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kredit (Masuk)</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Saldo</th>
            </tr>
        </thead>
        <tbody id="report-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
    </table>
</div>

<?php if (!$is_spa_request) { require_once PROJECT_ROOT . '/views/footer.php'; } ?>