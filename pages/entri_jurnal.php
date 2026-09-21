<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
 
// Security check
check_permission('entri_jurnal', 'menu');
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-4 border-b border-gray-200 dark:border-gray-700">
    <h1 id="page-title" class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
        <i class="bi bi-journal-plus text-primary"></i> Entri Jurnal
    </h1>
    <div class="flex items-center gap-2">
        <a href="<?= base_url('/daftar-jurnal') ?>" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <i class="bi bi-list-ol mr-2 text-primary"></i> Semua Daftar Jurnal
        </a>
    </div>
</div>

<!-- Tab Navigation -->
<div class="mb-6 border-b border-gray-200 dark:border-gray-700">
    <div class="-mb-px flex space-x-6" aria-label="Tabs" role="tablist" id="entriJurnalTabs">
        <button type="button" class="entri-jurnal-tab-btn whitespace-nowrap py-3 px-2 border-b-2 font-semibold text-sm flex items-center gap-2 border-primary text-primary transition-all" id="tab-entri-btn" data-target="#pane-entri" role="tab" aria-selected="true">
            <i class="bi bi-pencil-square text-base"></i> Form Entri Jurnal
        </button>
        <button type="button" class="entri-jurnal-tab-btn whitespace-nowrap py-3 px-2 border-b-2 font-medium text-sm flex items-center gap-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 transition-all" id="tab-riwayat-btn" data-target="#pane-riwayat" role="tab" aria-selected="false">
            <i class="bi bi-clock-history text-base"></i> Riwayat Entri Jurnal
            <span id="riwayat-count-badge" class="ml-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">...</span>
        </button>
    </div>
</div>

<div id="entriJurnalTabContent">
    <!-- PANE 1: Form Entri / Edit Jurnal -->
    <div id="pane-entri" class="entri-jurnal-tab-pane" role="tabpanel" aria-labelledby="tab-entri-btn">
        <!-- Form Card -->
        <div id="entri-jurnal-card" class="bg-white dark:bg-gray-800 shadow rounded-xl mb-8 transition-all duration-300 border border-transparent">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center flex-wrap gap-2">
                <div class="flex items-center gap-3">
                    <h5 id="form-card-title" class="text-lg font-medium text-gray-900 dark:text-white">Buat Jurnal Umum (Majemuk)</h5>
                    <span id="edit-badge" class="hidden px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-300 dark:border-amber-700 animate-pulse">Mode Edit</span>
                </div>
                <button type="button" id="header-cancel-edit-btn" class="hidden text-xs text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 flex items-center gap-1 font-medium transition-colors">
                    <i class="bi bi-x-circle"></i> Batal Edit & Buat Baru
                </button>
            </div>
            <div class="p-6">
                <!-- Edit Mode Alert Banner -->
                <div id="edit-mode-alert" class="hidden mb-6 p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-200 flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-amber-100 dark:bg-amber-900/60 rounded-lg text-amber-700 dark:text-amber-300">
                            <i class="bi bi-pencil-square text-lg"></i>
                        </div>
                        <div>
                            <h6 class="font-bold text-sm" id="edit-alert-title">Sedang Mengedit Jurnal: JRN-00000</h6>
                            <p class="text-xs text-amber-700 dark:text-amber-300/80 mt-0.5">Perubahan yang Anda simpan akan memperbarui data jurnal dan sinkronisasi di Buku Besar.</p>
                        </div>
                    </div>
                    <button type="button" id="alert-cancel-edit-btn" class="text-xs bg-amber-100 dark:bg-amber-900/60 hover:bg-amber-200 dark:hover:bg-amber-800 text-amber-800 dark:text-amber-200 px-3 py-1.5 rounded-lg font-semibold transition-colors border border-amber-300 dark:border-amber-700">
                        Batal Edit
                    </button>
                </div>

                <form id="entri-jurnal-form">
                    <input type="hidden" name="id" id="jurnal-id">
                    <input type="hidden" name="action" id="jurnal-action" value="add">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4">
                        <div class="md:col-span-4">
                            <label for="jurnal-tanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                            <input type="date" id="jurnal-tanggal" name="tanggal" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" required>
                        </div>
                        <div class="md:col-span-8">
                            <label for="jurnal-keterangan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                            <input type="text" id="jurnal-keterangan" name="keterangan" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" placeholder="Deskripsi jurnal..." required>
                        </div>
                    </div>

                    <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-md mb-4">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider w-5/12">Akun</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Debit (Rp)</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kredit (Rp)</th>
                                    <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider w-16">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="jurnal-lines-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Baris jurnal akan ditambahkan di sini oleh JS -->
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-700 font-bold text-sm text-gray-700 dark:text-gray-300">
                                <tr>
                                    <td class="px-4 py-3 text-right">Total:</td>
                                    <td class="px-4 py-3 text-right" id="total-jurnal-debit">Rp 0</td>
                                    <td class="px-4 py-3 text-right" id="total-jurnal-kredit">Rp 0</td>
                                    <td class="px-4 py-3 text-center" id="jurnal-balance-status"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <button type="button" class="inline-flex items-center px-3 py-1.5 border border-dashed border-gray-400 dark:border-gray-500 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition-colors" id="add-jurnal-line-btn">
                        <i class="bi bi-plus-lg mr-2"></i> Tambah Baris
                    </button>
                    <hr class="my-6 border-gray-200 dark:border-gray-700">
                    <div class="flex justify-end gap-3 flex-wrap items-center">
                        <button type="button" class="hidden inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition-colors" id="cancel-edit-btn">
                            <i class="bi bi-x-circle mr-2 text-red-500"></i> Batal Edit
                        </button>
                        <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors" id="save-as-recurring-btn">
                            <i class="bi bi-arrow-repeat mr-2"></i> Jadikan Berulang...
                        </button>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors" id="save-jurnal-entry-btn">
                            <i class="bi bi-save-fill mr-2" id="save-btn-icon"></i> <span id="save-btn-text">Simpan Entri Jurnal</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- PANE 2: Riwayat Entri Jurnal (Khusus Data yang Diinput dari Menu Ini) -->
    <div id="pane-riwayat" class="entri-jurnal-tab-pane hidden" role="tabpanel" aria-labelledby="tab-riwayat-btn">
        <div id="recent-journals-card" class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden mb-8 border border-gray-200 dark:border-gray-700">
            <div class="p-4 md:p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h5 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-clock-history text-primary"></i> Riwayat Entri Jurnal & Stok Opname
                    </h5>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Menampilkan data jurnal umum manual (Ref: JRN-...) dan jurnal penyesuaian stok opname (Ref: SO-...).</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="refresh-recent-jurnals-btn" class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm" title="Muat Ulang Data">
                        <i class="bi bi-arrow-clockwise mr-1.5"></i> Segarkan
                    </button>
                </div>
            </div>

            <!-- Toolbar Filter -->
            <div class="p-4 md:p-6 bg-gray-50/50 dark:bg-gray-900/40 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col lg:flex-row gap-3">
                    <!-- Search -->
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <i class="bi bi-search text-sm"></i>
                            </div>
                            <input type="text" id="search-recent-jurnal" 
                                class="block w-full pl-9 pr-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-xs placeholder-gray-400 focus:ring-primary focus:border-primary dark:text-white transition-all shadow-sm" 
                                placeholder="Cari No. Referensi atau Keterangan...">
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="flex items-center bg-white dark:bg-gray-800 rounded-lg px-2 py-1 border border-gray-300 dark:border-gray-600 shadow-sm">
                        <div class="flex items-center text-gray-400 mr-2">
                            <i class="bi bi-calendar3 text-xs"></i>
                        </div>
                        <input type="date" id="filter-recent-mulai" class="bg-transparent border-none text-xs focus:ring-0 p-1 dark:text-gray-200">
                        <span class="text-gray-300 dark:text-gray-600 mx-1">/</span>
                        <input type="date" id="filter-recent-akhir" class="bg-transparent border-none text-xs focus:ring-0 p-1 dark:text-gray-200">
                    </div>

                    <!-- Buttons & Limit -->
                    <div class="flex items-center gap-2">
                        <button type="button" id="btn-recent-reset" class="whitespace-nowrap px-3 py-2 text-xs font-semibold rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all shadow-sm flex items-center gap-1">
                            <i class="bi bi-x-circle"></i> Reset
                        </button>
                        <select id="filter-recent-limit" class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs font-medium focus:ring-primary focus:border-primary dark:text-gray-300 py-2 pl-3 pr-8 shadow-sm" title="Jumlah data per muat">
                            <option value="15" selected>15 Data</option>
                            <option value="30">30 Data</option>
                            <option value="50">50 Data</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table Container (Scrollable with Sticky Header) -->
            <div id="recentJurnalTableContainer" class="overflow-auto max-h-[65vh] border-b border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/95 sticky top-0 z-10 shadow-sm backdrop-blur-sm">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/4">No. Ref & Transaksi</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider w-44">Tanggal / Waktu</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Akun / Rincian</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider w-32">Debit</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider w-32">Kredit</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="recent-jurnal-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- Data jurnal akan dimuat oleh JS -->
                    </tbody>
                </table>
                <div id="recent-infinite-scroll-sentinel" class="h-4 w-full"></div>
            </div>

            <!-- Footer Info & Infinite Scroll Loader -->
            <div class="px-4 py-3 bg-gray-50/50 dark:bg-gray-900/30 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-3">
                <div id="recent-jurnal-pagination-info" class="text-xs text-gray-600 dark:text-gray-400 font-medium italic"></div>
                <div id="recent-infinite-scroll-loader" class="hidden">
                    <div class="flex items-center text-xs text-primary font-medium">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary mr-2"></div>
                        Memuat lebih banyak...
                    </div>
                </div>
                <div id="recent-jurnal-pagination" class="hidden"></div>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>