<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('stok_opname', 'menu');
?>

<div class="flex flex-col sm:flex-row items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2 mb-4 sm:mb-0"><i class="bi bi-clipboard-check-fill"></i> Stok Opname</h1>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h5 class="text-lg font-medium text-gray-900 dark:text-white">Formulir Stok Opname</h5>
    </div>
    <div class="p-6">
        <form id="stockOpnameForm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label for="tanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                    <input type="date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="tanggal" name="tanggal" required>
                </div>
                <div>
                    <label for="adj_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Penyeimbang (Selisih)</label>
                    <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="adj_account_id" name="adj_account_id" required>
                        <option value="">Memuat akun...</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Akun untuk mencatat selisih (misal: Beban Kerusakan Persediaan, Modal Awal).</p>
                </div>
                <div>
                    <label for="keterangan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                    <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="keterangan" name="keterangan" placeholder="cth: Stok Opname Bulanan" required>
                </div>
            </div>

            <hr class="my-6 border-gray-200 dark:border-gray-700">

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4">
                <div class="md:col-span-8">
                    <label for="searchInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cari Barang</label>
                    <input type="text" id="searchInput" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="Ketik nama barang atau SKU...">
                </div>
                <div class="md:col-span-4">
                    <label for="stockFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filter Stok</label>
                    <select id="stockFilter" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                        <option value="">Semua Stok</option>
                        <option value="ready">Stok Tersedia (> 0)</option>
                        <option value="empty">Stok Habis (<= 0)</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-md mb-4" style="max-height: 65vh;">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="itemsTable">
                    <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No.</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Barang</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">SKU</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stok Sistem</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-32">Stok Fisik</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Selisih</th>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td colspan="6" class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="saveButton">
                    <i class="bi bi-save mr-2"></i>Simpan Hasil Stok Opname
                </button>
            </div>
        </form>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>