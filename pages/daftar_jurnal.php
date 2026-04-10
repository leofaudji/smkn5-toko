<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('daftar_jurnal', 'menu');
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-list-ol"></i> Daftar Entri Jurnal</h1>
    <div class="flex mb-2 md:mb-0 gap-2">
        <div class="flex gap-2">
            <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none" id="export-dj-pdf">
                <i class="bi bi-file-earmark-pdf-fill text-red-600 mr-2"></i> PDF
            </button>
            <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none" id="export-dj-csv">
                <i class="bi bi-file-earmark-spreadsheet-fill text-green-600 mr-2"></i> CSV
            </button>
        </div>
        <a href="<?= base_url('/entri-jurnal') ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
            <i class="bi bi-plus-circle-fill mr-2"></i> Buat Entri Jurnal Baru
        </a>
    </div>
</div>

<!-- Modern Filter Toolbar -->
<div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl mb-6 overflow-hidden">
    <div class="p-4 md:p-6">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search Section -->
            <div class="flex-1">
                <label for="search-jurnal" class="sr-only">Cari</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-primary transition-colors">
                        <i class="bi bi-search text-lg"></i>
                    </div>
                    <input type="text" id="search-jurnal" 
                        class="block w-full pl-11 pr-3 py-3 bg-gray-50 dark:bg-gray-900 border-none rounded-xl text-sm placeholder-gray-400 focus:ring-2 focus:ring-primary dark:text-white transition-all shadow-inner" 
                        placeholder="Cari No. Referensi atau Keterangan...">
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Date Range -->
                <div class="flex items-center bg-gray-50 dark:bg-gray-900 rounded-xl p-1 shadow-inner border border-transparent focus-within:border-primary/30 transition-all">
                    <div class="flex items-center pl-2 text-gray-400">
                        <i class="bi bi-calendar3"></i>
                    </div>
                    <input type="date" id="filter-jurnal-mulai" class="bg-transparent border-none text-sm focus:ring-0 dark:text-gray-200">
                    <span class="text-gray-300 dark:text-gray-600 mx-1">/</span>
                    <input type="date" id="filter-jurnal-akhir" class="bg-transparent border-none text-sm focus:ring-0 dark:text-gray-200">
                </div>

                <!-- Shortcuts -->
                <div class="flex gap-1 overflow-x-auto no-scrollbar">
                    <button type="button" id="btn-filter-today" class="whitespace-nowrap px-3 py-2 text-xs font-semibold rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                        Hari Ini
                    </button>
                    <button type="button" id="btn-filter-month" class="whitespace-nowrap px-3 py-2 text-xs font-semibold rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                        Bulan Ini
                    </button>
                    <button type="button" id="btn-filter-reset" class="whitespace-nowrap px-3 py-2 text-xs font-semibold rounded-lg bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/20 transition-all shadow-sm flex items-center gap-1">
                        <i class="bi bi-x-circle"></i> Reset
                    </button>
                </div>

                <div class="h-8 w-px bg-gray-200 dark:bg-gray-700 hidden lg:block"></div>

                <!-- Limit & Sort -->
                <div class="flex items-center gap-2">
                    <select id="filter-jurnal-limit" class="rounded-xl border-none bg-gray-50 dark:bg-gray-900 text-xs font-medium focus:ring-2 focus:ring-primary dark:text-gray-300 py-2.5 pl-3 pr-8 shadow-inner">
                        <option value="15">15 Baris</option>
                        <option value="50">50 Baris</option>
                        <option value="100">100 Baris</option>
                        <option value="-1">Semua</option>
                    </select>
                    <select id="filter-jurnal-sort" class="rounded-xl border-none bg-gray-50 dark:bg-gray-900 text-xs font-medium focus:ring-2 focus:ring-primary dark:text-gray-300 py-2.5 pl-3 pr-8 shadow-inner">
                        <option value="tanggal">Terbaru</option>
                        <option value="no_ref">Ref. No</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="p-6">
        <div class="overflow-x-auto overflow-y-auto" style="max-height: 600px;">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-widest bg-gray-50 dark:bg-gray-700" colspan="2">Transaksi & Keterangan</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-widest bg-gray-50 dark:bg-gray-700">Waktu Update</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-widest bg-gray-50 dark:bg-gray-700">Akun / Rincian</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-widest bg-gray-50 dark:bg-gray-700">Debit</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-widest bg-gray-50 dark:bg-gray-700">Kredit</th>
                    </tr>
                </thead>
                <tbody id="daftar-jurnal-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Data akan dimuat oleh JavaScript -->
                </tbody>
            </table>
        </div>
        <nav class="mt-4">
            <ul class="flex justify-center space-x-1" id="daftar-jurnal-pagination">
                <!-- Pagination akan dimuat oleh JS -->
            </ul>
        </nav>
    </div>
</div>

<!-- Modal untuk Detail Jurnal -->
<div id="viewJurnalModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="viewJurnalModalLabel" role="dialog" aria-modal="true">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('viewJurnalModal')"></div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
      <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="viewJurnalModalLabel"><i class="bi bi-journal-text"></i> Detail Entri Jurnal</h5>
        <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeModal('viewJurnalModal')">
            <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="p-6" id="view-jurnal-body">
        <!-- Konten detail jurnal akan dimuat di sini -->
      </div>
      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
        <button type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal('viewJurnalModal')">Tutup</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>