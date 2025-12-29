<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('barang_stok', 'menu');
?>

<div class="flex flex-col sm:flex-row items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2 mb-4 sm:mb-0"><i class="bi bi-boxes"></i> Manajemen Barang & Stok</h1>
    <div class="flex items-center space-x-2">
        <button type="button" class="inline-flex items-center px-4 py-2 border border-green-500 rounded-md shadow-sm text-sm font-medium text-green-600 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" onclick="openModal('importModal')">
            <i class="bi bi-file-earmark-spreadsheet-fill mr-2"></i> Import dari Excel
        </button>
        <button type="button" class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-md font-semibold text-sm text-white shadow-sm hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="add-item-btn">
            <i class="bi bi-plus-circle-fill mr-2"></i> Tambah Barang Baru
        </button> 
    </div>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-1">
                <input type="text" id="search-item" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" placeholder="Cari nama barang atau SKU...">
            </div>
            <div>
                <select id="filter-category" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="">Semua Kategori</option>
                    <!-- Opsi kategori dimuat oleh JS -->
                </select>
            </div>
            <div>
                <select id="filter-stok" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="">Semua Stok</option>
                    <option value="ready">Stok Tersedia</option>
                    <option value="empty">Stok Habis</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Barang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">SKU</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kategori</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga Beli</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga Jual</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stok</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nilai Persediaan</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="items-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Data dimuat oleh JS -->
                </tbody>
            </table>
        </div>
        <div class="flex justify-between items-center mt-4">
            <div id="items-pagination-info" class="text-sm text-gray-700 dark:text-gray-300"></div>
            <div id="items-pagination">
                <!-- Pagination dimuat oleh JS -->
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Tambah/Edit Barang -->
<div id="itemModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="itemModalLabel" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('itemModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="itemModalLabel">Tambah Barang Baru</h5>
                <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeModal('itemModal')"><i class="bi bi-x-lg"></i></button> 
            </div>
            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <form id="item-form" novalidate class="space-y-4">
                    <input type="hidden" name="id" id="item-id">
                    <input type="hidden" name="action" id="item-action" value="save">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label for="nama_barang" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Barang</label>
                            <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="nama_barang" name="nama_barang" required>
                        </div>
                        <div>
                            <label for="sku" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SKU (Kode Barang)</label>
                            <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="sku" name="sku">
                        </div>
                    </div>
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="category_id" name="category_id"></select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="harga_beli" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Harga Beli (Modal)</label>
                            <input type="number" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="harga_beli" name="harga_beli" required>
                        </div>
                        <div>
                            <label for="harga_jual" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Harga Jual</label>
                            <input type="number" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="harga_jual" name="harga_jual" required>
                        </div>
                    </div>

                    <div>
                        <label for="stok" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stok Saat Ini</label>
                        <input type="number" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="stok" name="stok" required>
                        <p id="stok-help-text" class="mt-2 text-sm text-gray-500 dark:text-gray-400">Masukkan jumlah stok awal. Untuk mengubah stok selanjutnya, gunakan fitur "Penyesuaian Stok" atau transaksi Pembelian.</p>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <p class="text-gray-500 dark:text-gray-400">Pemetaan Akun (Opsional)</p>
                        <div class="space-y-4 mt-2">
                            <div><label for="inventory_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Persediaan (Aset)</label><select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="inventory_account_id" name="inventory_account_id"></select></div>
                            <div><label for="cogs_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Harga Pokok Penjualan (Beban)</label><select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="cogs_account_id" name="cogs_account_id"></select></div>
                            <div><label for="sales_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Pendapatan Penjualan</label><select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="sales_account_id" name="sales_account_id"></select></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm" id="save-item-btn">Simpan Barang</button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('itemModal')">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Penyesuaian Stok -->
<div id="adjustmentModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="adjustmentModalLabel" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('adjustmentModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="adjustmentModalLabel">Penyesuaian Stok</h5>
                <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeModal('adjustmentModal')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="p-6">
                <form id="adjustment-form" novalidate class="space-y-4">
                    <input type="hidden" name="item_id" id="adj-item-id">
                    <input type="hidden" name="action" value="adjust_stock">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Barang</label>
                        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700/50 bg-gray-100 shadow-sm" id="adj-nama-barang" readonly>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stok Tercatat</label>
                            <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700/50 bg-gray-100 shadow-sm" id="adj-stok-tercatat" readonly>
                        </div>
                        <div>
                            <label for="adj-stok-fisik" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stok Fisik Sebenarnya</label>
                            <input type="number" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="adj-stok-fisik" name="stok_fisik" required>
                        </div>
                    </div>

                    <div>
                        <label for="adj-tanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Penyesuaian</label>
                        <input type="date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="adj-tanggal" name="tanggal" required>
                    </div>

                    <div><label for="adj_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Penyeimbang</label><select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="adj_account_id" name="adj_account_id" required></select><p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Pilih akun untuk mencatat selisih nilai persediaan (cth: Beban Persediaan Rusak, atau Modal).</p></div>
                    <div><label for="adj-keterangan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alasan Penyesuaian</label><textarea class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="adj-keterangan" name="keterangan" rows="2" required placeholder="cth: Hasil stok opname 31 Des 2023"></textarea></div>
                </form>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm" id="save-adjustment-btn">Simpan Penyesuaian</button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('adjustmentModal')">Batal</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Import Excel -->
<div id="importModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="importModalLabel" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('importModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="importModalLabel">Import Barang dari CSV</h5>
                <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeModal('importModal')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="p-6">
                <form id="import-form" enctype="multipart/form-data" class="space-y-4">
                    <div class="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-400 p-4 text-sm text-blue-700 dark:text-blue-200">
                        <p class="mb-1">Simpan file Excel Anda sebagai file <strong>CSV (Comma-separated values)</strong>. Pastikan urutan kolomnya sebagai berikut (baris pertama/header akan dilewati):</p>
                        <ol class="list-decimal list-inside space-y-1">
                            <li><strong>Kolom A: Nama Barang</strong> (Wajib)</li>
                            <li><strong>Kolom B: ID Barang</strong> (Opsional. Isi untuk memperbarui barang yang ada, kosongkan untuk membuat barang baru)</li>
                            <li><strong>Kolom C: Kategori</strong> (Opsional. Jika kategori belum ada, akan dibuat otomatis)</li>
                            <li><strong>Kolom D: SKU</strong> (Opsional)</li>
                            <li><strong>Kolom E: Harga Beli</strong> (Wajib, angka saja, cth: <code>15000.50</code>)</li>
                            <li><strong>Kolom F: Harga Jual</strong> (Wajib, angka saja, cth: <code>20000.00</code>)</li>
                            <li>... (kolom lain diabaikan) ...</li>
                            <li><strong>Kolom J: Stok Fisik</strong> (Wajib, angka bulat, cth: <code>100</code>)</li>
                        </ol>
                    </div>
                    <div>
                        <label for="excel-file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih File CSV (.csv)</label>
                        <input class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" type="file" id="excel-file" name="excel_file" accept=".csv" required>
                    </div>
                    <div>
                        <label for="import_adj_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Penyeimbang Saldo Awal</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="import_adj_account_id" name="adj_account_id" required></select>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Pilih akun untuk menyeimbangkan nilai persediaan awal (cth: Modal Awal).</p>
                    </div>
                </form>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm" id="upload-excel-btn">
                    <i class="bi bi-upload mr-2"></i> Unggah dan Proses
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('importModal')">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>