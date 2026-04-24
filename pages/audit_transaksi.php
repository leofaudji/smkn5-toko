<?php
// Cek apakah ini permintaan dari SPA via AJAX
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';

// Hanya muat header jika ini bukan permintaan SPA
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>
<div class="p-6">
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Audit Transaksi</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Verifikasi integritas data antara modul operasional dan Buku Besar Akuntansi.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <div class="inline-flex p-1 bg-gray-100 dark:bg-gray-800 rounded-xl shadow-inner">
                <button id="refresh-audit" class="flex items-center px-4 py-2 bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 font-semibold rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
                    <i class="bi bi-arrow-clockwise mr-2"></i> Perbarui Data
                </button>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-8 p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700">
        <div class="md:col-span-4 space-y-2">
            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider ml-1">Rentang Tanggal</label>
            <div class="flex items-center gap-2">
                <input type="date" id="filter-start-date" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-primary-500 transition-all text-sm" value="<?= date('Y-m-01') ?>">
                <span class="text-gray-400 font-medium">s/d</span>
                <input type="date" id="filter-end-date" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-primary-500 transition-all text-sm" value="<?= date('Y-m-t') ?>">
            </div>
        </div>
        <div class="md:col-span-3 space-y-2">
            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider ml-1">Modul Transaksi</label>
            <select id="filter-module" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-primary-500 transition-all text-sm">
                <option value="all">Semua Modul</option>
                <option value="transaksi">Kas & Bank</option>
                <option value="penjualan">Penjualan</option>
                <option value="pembelian">Pembelian</option>
                <option value="jurnal">Jurnal Umum</option>
            </select>
        </div>
        <div class="md:col-span-3 space-y-2">
            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider ml-1">Status Audit</label>
            <select id="filter-status" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-primary-500 transition-all text-sm">
                <option value="all">Semua Status</option>
                <option value="valid">Lolos Audit (Hijau)</option>
                <option value="missing" selected>Data Hilang (Merah)</option>
            </select>
        </div>
        <div class="md:col-span-2 flex items-end">
            <button id="apply-filters" class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl shadow-lg shadow-primary-500/30 transition-all active:scale-95">
                <i class="bi bi-funnel-fill mr-2"></i> Terapkan
            </button>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-900/50">
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">Informasi Transaksi</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">Keterangan</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700 text-right">Nilai Transaksi</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700 text-center">Status GL</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="audit-transaksi-body" class="divide-y divide-gray-50 dark:divide-gray-700">
                    <!-- Loading State -->
                    <tr id="loading-row">
                        <td colspan="7" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center space-y-4">
                                <div class="w-12 h-12 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin"></div>
                                <p class="text-gray-500 font-medium animate-pulse">Menganalisis integritas data...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Empty State (Hidden by default) -->
        <div id="empty-state" class="hidden py-24 text-center">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gray-50 dark:bg-gray-900 rounded-full mb-4">
                <i class="bi bi-clipboard-check text-4xl text-gray-300"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">Tidak ada data transaksi</h3>
            <p class="text-gray-500 mt-1 max-w-xs mx-auto">Silakan sesuaikan filter atau rentang tanggal untuk melihat data.</p>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="group p-6 bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/10 dark:to-teal-900/10 rounded-3xl border border-emerald-100 dark:border-emerald-800/30 transition-all hover:shadow-xl hover:shadow-emerald-500/10">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-emerald-500 rounded-2xl shadow-lg shadow-emerald-500/30">
                    <i class="bi bi-shield-check text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="text-emerald-900 dark:text-emerald-300 font-bold text-lg">Integritas Terjaga</h3>
                    <p class="text-emerald-700 dark:text-emerald-400/80 text-sm mt-1 leading-relaxed">
                        Ikon centang hijau <i class="bi bi-check-circle-fill mx-1"></i> menandakan transaksi telah tercatat dengan benar di Buku Besar. Data ini aman dan siap untuk pelaporan keuangan.
                    </p>
                </div>
            </div>
        </div>
        <div class="group p-6 bg-gradient-to-br from-rose-50 to-orange-50 dark:from-rose-900/10 dark:to-orange-900/10 rounded-3xl border border-rose-100 dark:border-rose-800/30 transition-all hover:shadow-xl hover:shadow-rose-500/10">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-rose-500 rounded-2xl shadow-lg shadow-rose-500/30">
                    <i class="bi bi-exclamation-octagon text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="text-rose-900 dark:text-rose-300 font-bold text-lg">Waspada Data Hilang</h3>
                    <p class="text-rose-700 dark:text-rose-400/80 text-sm mt-1 leading-relaxed">
                        Ikon peringatan merah <i class="bi bi-exclamation-triangle-fill mx-1"></i> berarti transaksi ADA di modul operasional namun jurnal akuntansinya HILANG. Gunakan tombol "Re-sync" untuk memulihkan.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts will be loaded by SPA router -->

<?php
// Hanya muat footer jika ini bukan permintaan SPA
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>
