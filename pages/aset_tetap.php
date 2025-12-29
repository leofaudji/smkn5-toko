<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('aset_tetap', 'menu');
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-building"></i> Manajemen Aset Tetap</h1>
    <div class="flex mb-2 md:mb-0 gap-2">
        <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="openModal('assetModal')" data-action="add">
            <i class="bi bi-plus-circle mr-2"></i> Tambah Aset
        </button>
        <button class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none" id="print-asset-report-btn">
            <i class="bi bi-printer-fill mr-2"></i> Cetak Laporan
        </button>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <span class="font-semibold text-gray-800 dark:text-white">Posting Penyusutan Periodik</span>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="depreciation-month" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bulan</label>
                <select id="depreciation-month" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></select>
            </div>
            <div>
                <label for="depreciation-year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tahun</label>
                <select id="depreciation-year" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></select>
            </div>
            <div>
                <button id="post-depreciation-btn" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                    <i class="bi bi-send-fill mr-2"></i> Posting Jurnal Penyusutan
                </button>
            </div>
        </div>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-800 dark:text-white">
        Daftar Aset Tetap
    </div>
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Aset</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tgl. Perolehan</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga Perolehan</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Akum. Penyusutan</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nilai Buku</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="assets-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Data akan dimuat oleh JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Aset -->
<div id="assetModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="assetModalLabel" role="dialog" aria-modal="true">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('assetModal')"></div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
      <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="assetModalLabel">Tambah Aset Tetap</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeModal('assetModal')">
                <span class="sr-only">Close</span>
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="asset-form">
            <input type="hidden" name="id" id="asset-id">
            <input type="hidden" name="action" id="asset-action" value="save">

            <div class="mb-3">
                <label for="nama_aset" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Aset</label>
                <input type="text" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" id="nama_aset" name="nama_aset" required>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-3">
                    <label for="tanggal_akuisisi" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Perolehan</label>
                    <input type="date" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" id="tanggal_akuisisi" name="tanggal_akuisisi" required>
                </div>
                <div class="mb-3">
                    <label for="harga_perolehan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Harga Perolehan</label>
                    <input type="number" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" id="harga_perolehan" name="harga_perolehan" required>
                </div>
                <div class="mb-3">
                    <label for="nilai_residu" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nilai Residu (Sisa)</label>
                    <input type="number" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" id="nilai_residu" name="nilai_residu" value="0" required>
                </div>
                <div class="mb-3">
                    <label for="masa_manfaat" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Masa Manfaat (Tahun)</label>
                    <input type="number" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" id="masa_manfaat" name="masa_manfaat" required>
                </div>
                <div class="mb-3">
                    <label for="metode_penyusutan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Metode Penyusutan</label>
                    <select class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" id="metode_penyusutan" name="metode_penyusutan">
                        <option value="Garis Lurus" selected>Garis Lurus</option>
                        <option value="Saldo Menurun">Saldo Menurun (Double Declining)</option>
                    </select>
                </div>
            </div>
            <hr>
            <p class="text-muted">Pemetaan Akun</p>
            <div class="mb-3"><label for="akun_aset_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Akun Aset</label><select class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" id="akun_aset_id" name="akun_aset_id" required></select></div>
            <div class="mb-3"><label for="akun_akumulasi_penyusutan_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Akun Akumulasi Penyusutan</label><select class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" id="akun_akumulasi_penyusutan_id" name="akun_akumulasi_penyusutan_id" required></select></div>
            <div class="mb-3"><label for="akun_beban_penyusutan_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Akun Beban Penyusutan</label><select class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" id="akun_beban_penyusutan_id" name="akun_beban_penyusutan_id" required></select></div>
        </form>
      </div>
      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
        <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm" id="save-asset-btn">Simpan</button>
        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal('assetModal')">Batal</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Pelepasan Aset -->
<div id="disposalModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="disposalModalLabel" role="dialog" aria-modal="true">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('disposalModal')"></div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
      <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="disposalModalLabel">Pelepasan/Penjualan Aset</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeModal('disposalModal')">
                <span class="sr-only">Close</span>
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="disposal-form">
            <input type="hidden" name="action" value="dispose_asset">
            <input type="hidden" name="asset_id" id="disposal-asset-id">
            <p class="mb-3 text-gray-700 dark:text-gray-300">Anda akan melepas aset: <strong id="disposal-asset-name"></strong></p>
            <div class="mb-3">
                <label for="tanggal_pelepasan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Pelepasan/Penjualan</label>
                <input type="date" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" id="tanggal_pelepasan" name="tanggal_pelepasan" required>
            </div>
            <div class="mb-3">
                <label for="harga_jual" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Harga Jual (Isi 0 jika dibuang)</label>
                <input type="number" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" id="harga_jual" name="harga_jual" value="0" required>
            </div>
            <div class="mb-3" id="disposal-kas-account-container">
                <label for="kas_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Uang Diterima di Akun Kas/Bank</label>
                <select class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" id="kas_account_id" name="kas_account_id"></select>
            </div>
        </form>
      </div>
      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
        <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm" id="save-disposal-btn">Proses Pelepasan</button>
        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal('disposalModal')">Batal</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>