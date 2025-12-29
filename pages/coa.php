<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('coa', 'menu');
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-journal-bookmark-fill"></i> Bagan Akun (Chart of Accounts)</h1>
    <div class="flex mb-2 md:mb-0">
        <button type="button" class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-md font-semibold text-sm text-white shadow-sm hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="add-coa-btn">
            <i class="bi bi-plus-circle-fill mr-2"></i> Tambah Akun
        </button>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="p-6">
        <div id="coa-tree-container">
            <!-- Pohon COA akan dirender di sini oleh JavaScript -->
            <div class="text-center p-5 text-gray-500">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                <span class="sr-only">Memuat...</span>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Tambah/Edit Akun COA -->
<div id="coaModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="coaModalLabel" role="dialog" aria-modal="true">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('coaModal')"></div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
      <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="coaModalLabel">Tambah Akun Baru</h5>
        <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeModal('coaModal')">
            <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="p-6">
        <form id="coa-form" class="space-y-4">
            <input type="hidden" name="id" id="coa-id">
            <input type="hidden" name="action" id="coa-action" value="add">
            
            <div>
                <label for="parent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Induk (Parent)</label>
                <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="parent_id" name="parent_id">
                    <!-- Opsi akan dimuat oleh JS -->
                </select>
            </div>
            <div>
                <label for="kode_akun" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kode Akun</label>
                <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="kode_akun" name="kode_akun" required>
            </div>
            <div>
                <label for="nama_akun" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Akun</label>
                <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="nama_akun" name="nama_akun" required>
            </div>
            <div>
                <label for="tipe_akun" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipe Akun</label>
                <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="tipe_akun" name="tipe_akun" required>
                    <option value="Aset">Aset</option>
                    <option value="Liabilitas">Liabilitas</option>
                    <option value="Ekuitas">Ekuitas</option>
                    <option value="Pendapatan">Pendapatan</option>
                    <option value="Beban">Beban</option>
                </select>
            </div>
            <div class="flex items-center">
                <input class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" type="checkbox" id="is_kas" name="is_kas" value="1">
                <label class="ml-2 block text-sm text-gray-900 dark:text-gray-300" for="is_kas">
                    Ini adalah akun Kas/Bank (bisa menerima/mengirim uang)
                </label>
            </div>
        </form>
      </div>
      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
        <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm" id="save-coa-btn">Simpan</button>
        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal('coaModal')">Tutup</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>