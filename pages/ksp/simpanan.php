<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check (pastikan permission ini ada di DB atau gunakan permission umum dulu)
// check_permission('menu.view.simpanan'); 
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
        <i class="bi bi-piggy-bank-fill"></i> Simpanan Anggota
    </h1>
    <button id="btn-add-simpanan" class="inline-flex items-center gap-2 px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
        <i class="bi bi-plus-circle-fill"></i>
        <span>Transaksi Baru</span>
    </button>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-blue-50 dark:bg-blue-900/50 border border-blue-200 dark:border-blue-800 p-4 rounded-lg flex items-center">
        <div class="bg-blue-100 dark:bg-blue-800 p-3 rounded-full mr-4">
            <i class="bi bi-wallet2 text-2xl text-blue-600 dark:text-blue-300"></i>
        </div>
        <div>
            <p class="text-sm text-blue-600 dark:text-blue-300 font-medium">Total Saldo Simpanan</p>
            <p id="summary-saldo" class="text-2xl font-bold text-blue-800 dark:text-blue-100">Rp 0</p>
        </div>
    </div>
    <div class="bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 p-4 rounded-lg flex items-center">
        <div class="bg-green-100 dark:bg-green-800 p-3 rounded-full mr-4">
            <i class="bi bi-arrow-down-circle-fill text-2xl text-green-600 dark:text-green-300"></i>
        </div>
        <div>
            <p class="text-sm text-green-600 dark:text-green-300 font-medium">Setoran Hari Ini</p>
            <p id="summary-setor" class="text-2xl font-bold text-green-800 dark:text-green-100">Rp 0</p>
        </div>
    </div>
    <div class="bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 p-4 rounded-lg flex items-center">
        <div class="bg-red-100 dark:bg-red-800 p-3 rounded-full mr-4">
            <i class="bi bi-arrow-up-circle-fill text-2xl text-red-600 dark:text-red-300"></i>
        </div>
        <div>
            <p class="text-sm text-red-600 dark:text-red-300 font-medium">Penarikan Hari Ini</p>
            <p id="summary-tarik" class="text-2xl font-bold text-red-800 dark:text-red-100">Rp 0</p>
        </div>
    </div>
</div>

<!-- Filter & Search -->
<div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-lg mb-6 p-4">
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="bi bi-search text-gray-400"></i>
        </div>
        <input type="text" id="search-simpanan" class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 dark:text-white focus:ring-primary focus:border-primary sm:text-sm" placeholder="Cari Nama Anggota atau No Referensi...">
    </div>
</div>

<!-- Table -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Anggota</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jenis Simpanan</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipe</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jumlah</th>
            </tr>
        </thead>
        <tbody id="simpanan-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
    </table>
</div>

<!-- Modal Form -->
<div id="modal-simpanan" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="form-simpanan">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-primary-100 dark:bg-primary-900 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="bi bi-piggy-bank text-xl text-primary-600 dark:text-primary-300"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Transaksi Simpanan Baru</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Anggota</label>
                                    <select id="anggota_id" name="anggota_id" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" required>
                                        <option value="">Pilih Anggota...</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Transaksi</label>
                                        <select name="jenis_transaksi" id="jenis_transaksi" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required></select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                                        <input type="date" name="tanggal" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Simpanan</label>
                                    <select id="jenis_simpanan_id" name="jenis_simpanan_id" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required></select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Kas (Sumber/Tujuan)</label>
                                    <select id="akun_kas_id" name="akun_kas_id" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required></select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah (Rp)</label>
                                    <input type="number" name="jumlah" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                                    <textarea name="keterangan" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                    <button type="button" id="btn-cancel-modal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Notification Container -->
<div id="notification-container" class="fixed bottom-0 right-0 p-6 space-y-3 z-[100]"></div>

<?php if (!$is_spa_request) { require_once PROJECT_ROOT . '/views/footer.php'; } ?>
