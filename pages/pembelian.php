<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('pembelian', 'menu');
?>

<div class="flex flex-col sm:flex-row items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2 mb-4 sm:mb-0"><i class="bi bi-cart-fill"></i> Pembelian</h1>
    <div class="flex items-center space-x-2">
        <button type="button" class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-md font-semibold text-sm text-white shadow-sm hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="add-pembelian-btn">
            <i class="bi bi-plus-circle-fill mr-2"></i> Tambah Pembelian
        </button>
    </div>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-1">
                <input type="text" id="search-pembelian" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="Cari pemasok/keterangan...">
            </div>
            <div>
                <select id="filter-supplier" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    <option value="">Semua Pemasok</option>
                    <!-- Opsi dimuat oleh JS -->
                </select>
            </div>
            <div>
                <select id="filter-bulan" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    <!-- Opsi dimuat oleh JS -->
                </select>
            </div>
            <div>
                <select id="filter-tahun" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    <!-- Opsi dimuat oleh JS -->
                </select>
            </div>
        </div>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="p-6">
        <div class="overflow-auto max-h-[65vh] border border-gray-200 dark:border-gray-700 rounded-md">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pemasok</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jatuh Tempo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="pembelian-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <!-- Data akan dimuat oleh JavaScript -->
                </tbody>
            </table>
        </div>
        <div class="flex justify-between items-center mt-4">
            <div id="pembelian-pagination-info" class="text-sm text-gray-700 dark:text-gray-300"></div>
            <div id="pembelian-pagination">
                <!-- Pagination akan dimuat oleh JS -->
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Tambah/Edit Pembelian -->
<div id="pembelianModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="pembelianModalLabel" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('pembelianModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="pembelianModalLabel">Tambah Pembelian Baru</h5>
                <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeModal('pembelianModal')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <form id="pembelian-form" novalidate class="space-y-4">
                    <input type="hidden" name="id" id="pembelian-id">
                    <input type="hidden" name="action" id="pembelian-action" value="add">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="supplier_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pemasok</label>
                            <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="supplier_id" name="supplier_id" required></select>
                        </div>
                        <div>
                            <label for="tanggal_pembelian" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Pembelian</label>
                            <input type="date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="tanggal_pembelian" name="tanggal_pembelian" required>
                        </div>
                    </div>

                    <div>
                        <label for="keterangan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                        <textarea class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="keterangan" name="keterangan" rows="2" required></textarea>
                    </div>

                    <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-md">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-2/5">Nama Barang</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/6">Qty</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/4">Harga Satuan</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/4">Subtotal</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="pembelian-lines-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Baris detail pembelian akan ditambahkan di sini oleh JS -->
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="inline-flex items-center px-3 py-1.5 border border-dashed border-gray-400 text-sm font-medium rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700" id="add-pembelian-line-btn"><i class="bi bi-plus-lg mr-2"></i> Tambah Baris</button>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="jatuh_tempo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Jatuh Tempo (Opsional)</label>
                                <input type="date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="jatuh_tempo" name="jatuh_tempo">
                            </div>
                            <div>
                                <label for="payment_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Metode Pembayaran</label>
                                <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="payment_method" name="payment_method">
                                    <option value="credit">Kredit (Bayar Nanti)</option>
                                    <option value="cash">Tunai/Langsung</option>
                                </select>
                            </div>
                            <div class="hidden" id="kas-account-container">
                                <label for="kas_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Kas/Bank Pembayaran</label>
                                <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="kas_account_id" name="kas_account_id"></select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm" id="save-pembelian-btn">Simpan Pembelian</button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('pembelianModal')">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>