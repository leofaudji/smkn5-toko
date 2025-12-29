<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('transaksi_kas', 'menu');
?>

<div class="flex flex-col sm:flex-row items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2 mb-4 sm:mb-0"><i class="bi bi-arrow-down-up"></i> Transaksi</h1>
    <div class="flex items-center space-x-2">
        <button type="button" class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-md font-semibold text-sm text-white shadow-sm hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="add-transaksi-btn">
            <i class="bi bi-plus-circle-fill mr-2"></i> Tambah Transaksi
        </button>
    </div>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-1">
                <input type="text" id="search-transaksi" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="Cari keterangan...">
            </div>
            <div>
                <select id="filter-akun-kas" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    <option value="">Semua Akun Kas/Bank</option>
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
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Akun</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. Ref</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jumlah</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Info</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dari/Ke Akun Kas</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="transaksi-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Data akan dimuat oleh JavaScript -->
                </tbody>
            </table>
        </div>
        <div class="flex justify-between items-center mt-4">
            <div id="transaksi-pagination-info" class="text-sm text-gray-700 dark:text-gray-300"></div>
            <div id="transaksi-pagination">
                <!-- Pagination akan dimuat oleh JS -->
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Tambah/Edit Transaksi -->
<div id="transaksiModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="transaksiModalLabel" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('transaksiModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="transaksiModalLabel">Tambah Transaksi Baru</h5>
                <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeModal('transaksiModal')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="p-6">
                <form id="transaksi-form" novalidate class="space-y-4">
                    <input type="hidden" name="id" id="transaksi-id">
                    <input type="hidden" name="action" id="transaksi-action" value="add">
                    <input type="hidden" name="jenis" id="jenis" required>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Transaksi</label>
                            <div id="jenis-btn-group" class="mt-1 grid grid-cols-3 gap-1 rounded-lg bg-gray-200 dark:bg-gray-700 p-1">
                                <button type="button" class="jenis-btn px-3 py-2 text-sm font-medium rounded-md" data-value="pengeluaran"><i class="bi bi-arrow-down-circle-fill mr-2"></i>Pengeluaran</button>
                                <button type="button" class="jenis-btn px-3 py-2 text-sm font-medium rounded-md" data-value="pemasukan"><i class="bi bi-arrow-up-circle-fill mr-2"></i>Pemasukan</button>
                                <button type="button" class="jenis-btn px-3 py-2 text-sm font-medium rounded-md" data-value="transfer"><i class="bi bi-arrow-left-right mr-2"></i>Transfer</button>
                            </div>
                        </div>
                        <div>
                            <label for="tanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                            <input type="date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="tanggal" name="tanggal" required>
                        </div>
                    </div>

                    <div>
                        <label for="jumlah" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah (Rp)</label>
                        <input type="number" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="jumlah" name="jumlah" placeholder="cth: 50000" required>
                    </div>

                    <div>
                        <label for="nomor_referensi" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor Referensi (Opsional)</label>
                        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="nomor_referensi" name="nomor_referensi" placeholder="Kosongkan untuk nomor otomatis">
                    </div>

                    <!-- Dynamic Fields -->
                    <div id="pemasukan-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label for="kas_account_id_pemasukan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Masuk ke Akun Kas</label><select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="kas_account_id_pemasukan" name="kas_account_id_pemasukan"></select></div>
                        <div><label for="account_id_pemasukan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dari Akun Pendapatan</label><select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="account_id_pemasukan" name="account_id_pemasukan"></select></div>
                    </div>
                    <div id="pengeluaran-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label for="kas_account_id_pengeluaran" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Keluar dari Akun Kas</label><select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="kas_account_id_pengeluaran" name="kas_account_id_pengeluaran"></select></div>
                        <div><label for="account_id_pengeluaran" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Untuk Akun Beban</label><select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="account_id_pengeluaran" name="account_id_pengeluaran"></select></div>
                    </div>
                    <div id="transfer-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
                        <div><label for="kas_account_id_transfer" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dari Akun Kas</label><select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="kas_account_id_transfer" name="kas_account_id_transfer"></select></div>
                        <div><label for="kas_tujuan_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ke Akun Kas</label><select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="kas_tujuan_account_id" name="kas_tujuan_account_id"></select></div>
                    </div>

                    <div>
                        <label for="keterangan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan (bisa lebih dari 1 baris)</label>
                        <textarea class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="keterangan" name="keterangan" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm" id="save-transaksi-btn">Simpan Transaksi</button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('transaksiModal')">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Detail Jurnal -->
<div id="jurnalDetailModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="jurnalDetailModalLabel" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('jurnalDetailModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="jurnalDetailModalLabel"><i class="bi bi-journal-text"></i> Detail Jurnal Transaksi</h5>
                <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeModal('jurnalDetailModal')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="p-6" id="jurnal-detail-body">
                <div class="text-center p-4"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('jurnalDetailModal')">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>