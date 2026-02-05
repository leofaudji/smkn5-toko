<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
        <i class="bi bi-file-earmark-spreadsheet-fill"></i> Laporan Nominatif
    </h1>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6 p-4">
    <form id="filter-form" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
            <label for="jenis_laporan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jenis Laporan</label>
            <select id="jenis_laporan" name="jenis_laporan" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                <option value="simpanan">Nominatif Simpanan</option>
                <option value="pinjaman">Nominatif Pinjaman (Bakidebet)</option>
            </select>
        </div>
        <div>
            <label for="per_tanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Per Tanggal</label>
            <input type="date" id="per_tanggal" name="per_tanggal" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="md:col-span-2 flex justify-end gap-2">
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
        <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="report-title">Preview Laporan</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700" id="report-table-head"></thead>
            <tbody id="report-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-700 font-bold" id="report-table-foot"></tfoot>
        </table>
    </div>
</div>

<?php if (!$is_spa_request) { require_once PROJECT_ROOT . '/views/footer.php'; } ?>
