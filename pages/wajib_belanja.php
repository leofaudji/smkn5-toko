<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('wajib_belanja', 'menu');
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
        <i class="bi bi-wallet2"></i> Setor Wajib Belanja (WB)
    </h1>
    <div class="flex gap-2">
        <button id="wb-import-btn" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none">
            <i class="bi bi-file-earmark-arrow-up mr-2"></i> Impor CSV
        </button>
        <button id="wb-tambah-btn" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-md shadow-sm hover:bg-primary-600 focus:outline-none">
            <i class="bi bi-plus-lg mr-2"></i> Tambah Setoran
        </button>
    </div>
</div>

<!-- Form Modal -->
<div id="wb-form-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('wb-form-modal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <form id="wb-form">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4" id="wb-modal-title">Setor Wajib Belanja Kolektif</h3>
                    
                    <!-- Header Form -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="wb-tanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                            <input type="date" id="wb-tanggal" name="tanggal" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 sm:text-sm" required>
                        </div>
                        <div>
                            <label for="wb-metode-pembayaran" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Metode Pembayaran</label>
                            <select id="wb-metode-pembayaran" name="metode_pembayaran" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 sm:text-sm" required>
                                <option value="tunai">Tunai</option>
                                <option value="transfer">Transfer</option>
                            </select>
                        </div>
                        <div>
                            <label for="wb-akun-kas-id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Setor Ke Akun</label>
                            <select id="wb-akun-kas-id" name="akun_kas_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 sm:text-sm" required></select>
                        </div>
                    </div>

                    <!-- Tabel Input Kolektif -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden mb-4">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-1/2">Anggota</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-1/4">Jumlah (Rp)</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-1/4">Keterangan</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-10"><i class="bi bi-trash"></i></th>
                                </tr>
                            </thead>
                            <tbody id="wb-items-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Baris item akan ditambahkan di sini oleh JS -->
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <td colspan="4" class="px-4 py-2">
                                        <div class="flex justify-between items-center">
                                            <div class="space-x-2">
                                                <button type="button" id="wb-add-row-btn" class="text-sm text-primary hover:text-primary-600 font-medium"><i class="bi bi-plus-lg"></i> Tambah Baris</button>
                                                <span class="text-gray-300">|</span>
                                                <button type="button" id="wb-load-all-btn" class="text-sm text-green-600 hover:text-green-700 font-medium"><i class="bi bi-people"></i> Load Semua Anggota</button>
                                            </div>
                                            <div class="font-bold text-gray-900 dark:text-white">
                                                Total: <span id="wb-total-display">Rp 0</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" id="wb-form-submit-btn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('wb-form-modal')">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal (Single Transaction) -->
<div id="wb-edit-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('wb-edit-modal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="wb-edit-form">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Edit Transaksi Wajib Belanja</h3>
                    <input type="hidden" id="edit-wb-id" name="id">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Anggota</label>
                            <input type="text" id="edit-wb-anggota-display" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" readonly>
                        </div>
                        <div>
                            <label for="edit-wb-tanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                            <input type="date" id="edit-wb-tanggal" name="tanggal" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" required>
                        </div>
                        <div>
                            <label for="edit-wb-jumlah" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah</label>
                            <input type="number" id="edit-wb-jumlah" name="jumlah" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" required>
                        </div>
                        <div>
                            <label for="edit-wb-metode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Metode Pembayaran</label>
                            <select id="edit-wb-metode" name="metode_pembayaran" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" required>
                                <option value="tunai">Tunai</option>
                                <option value="transfer">Transfer</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit-wb-keterangan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                            <textarea id="edit-wb-keterangan" name="keterangan" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Simpan Perubahan</button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('wb-edit-modal')">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Table -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Anggota</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jumlah</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Metode</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="wb-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Data will be loaded by JS -->
                </tbody>
            </table>
        </div>
        <div id="wb-loading" class="text-center p-5" style="display: none;">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Memuat data...</p>
        </div>
        <div class="flex justify-between items-center mt-4">
            <div id="wb-pagination-info" class="text-sm text-gray-700 dark:text-gray-300"></div>
            <div id="wb-pagination"></div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="wb-import-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('wb-import-modal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="wb-import-form">
                <input type="hidden" name="action" value="import_csv">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h5 class="text-lg font-medium text-gray-900 dark:text-white">Impor Setoran Wajib Belanja (CSV)</h5>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('wb-import-modal')"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-md">
                        <p class="text-xs text-blue-700 dark:text-blue-300">
                            <strong>Format Kolom CSV:</strong><br>
                            <code>no, noanggota, nama, totalbayar, totalbelanja</code>
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Transaksi</label>
                            <input type="date" name="tanggal" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm sm:text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Metode</label>
                            <select name="metode_pembayaran" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm sm:text-sm" required>
                                <option value="tunai">Tunai</option>
                                <option value="transfer">Transfer</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Setor Ke Akun</label>
                        <select id="import-wb-akun-kas-id" name="akun_kas_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm sm:text-sm" required></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih File CSV</label>
                        <input type="file" name="csv_file" accept=".csv" class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-dark" required>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-dark focus:outline-none sm:ml-3 sm:w-auto sm:text-sm" id="wb-process-import-btn">Mulai Impor</button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('wb-import-modal')">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>
