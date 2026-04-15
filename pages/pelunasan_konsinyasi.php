<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

check_permission('konsinyasi', 'menu'); // Reuse consignment permission
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-4">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-3">
        <div class="p-2 bg-primary/10 rounded-lg">
            <i class="bi bi-wallet2 text-primary"></i>
        </div>
        Pelunasan Konsinyasi
    </h1>
    <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-1.5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center px-3 border-r border-gray-200 dark:border-gray-700">
            <i class="bi bi-calendar3 text-gray-400 mr-2 text-sm"></i>
            <input type="date" id="filter-date-mulai" class="border-none focus:ring-0 bg-transparent text-sm p-0 w-32 dark:text-gray-300">
        </div>
        <div class="flex items-center px-3">
            <i class="bi bi-arrow-right text-gray-400 mr-2 text-xs"></i>
            <input type="date" id="filter-date-akhir" class="border-none focus:ring-0 bg-transparent text-sm p-0 w-32 dark:text-gray-300">
        </div>
    </div>
</div>

<!-- Stats Overview -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:shadow-md transition-all duration-300">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/5 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Total Kewajiban Utang</p>
        <h3 id="stat-total-utang" class="text-2xl font-bold text-gray-900 dark:text-white">Rp 0</h3>
        <div class="mt-4 flex items-center text-xs text-blue-600 font-medium">
            <i class="bi bi-info-circle mr-1"></i> Berdasarkan periode terpilih
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:shadow-md transition-all duration-300">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-green-500/5 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Total Telah Dibayar</p>
        <h3 id="stat-total-bayar" class="text-2xl font-bold text-green-600 dark:text-green-400">Rp 0</h3>
        <div class="mt-4 flex items-center text-xs text-green-600 font-medium">
            <i class="bi bi-check2-all mr-1"></i> Terhitung dari semua pelunasan
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:shadow-md transition-all duration-300">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-red-500/5 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Sisa Saldo Utang</p>
        <h3 id="stat-sisa-utang" class="text-2xl font-bold text-red-600 dark:text-red-400">Rp 0</h3>
        <div id="stat-sync-warning" class="mt-4 flex items-center text-xs text-amber-600 font-medium hidden">
            <i class="bi bi-exclamation-triangle mr-1"></i> Ada selisih penyesuaian manual
        </div>
        <div id="stat-sync-ok" class="mt-4 flex items-center text-xs text-gray-400 font-medium">
            <i class="bi bi-safe mr-1"></i> Akurat sesuai Audit Saldo
        </div>
    </div>
</div>

<!-- Tab Navigation -->
<div class="mb-6 border-b border-gray-200 dark:border-gray-700">
    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="pk-tabs-header">
        <li class="mr-2">
            <button class="inline-block p-4 border-b-2 border-primary text-primary rounded-t-lg transition-all active-tab" id="pk-btn-pelunasan" onclick="switchTabPK('pelunasan')">
                <i class="bi bi-cash-stack mr-2 text-lg"></i> Pelunasan Baru
            </button>
        </li>
        <li class="mr-2">
            <button class="inline-block p-4 border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 text-gray-500 rounded-t-lg transition-all" id="pk-btn-history" onclick="switchTabPK('history')">
                <i class="bi bi-clock-history mr-2 text-lg"></i> Riwayat Pelunasan
            </button>
        </li>
    </ul>
</div>

<div id="pk-content-pelunasan">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    <!-- Supplier Balance List -->
    <div class="lg:col-span-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Rincian Saldo Pemasok</h2>
                    <p class="text-sm text-gray-500">Daftar kewajiban pembayaran per pemasok</p>
                </div>
                <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
                    <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 px-3 py-2 rounded-xl border border-gray-100 dark:border-gray-600">
                        <input type="checkbox" id="pk-filter-only-debt" class="rounded text-primary focus:ring-primary h-4 w-4" checked>
                        <label for="pk-filter-only-debt" class="text-sm font-medium text-gray-600 dark:text-gray-300 cursor-pointer">Hanya Berhutang</label>
                    </div>
                    <div class="relative w-full md:w-64">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="bi bi-search text-sm"></i>
                        </span>
                        <input type="text" id="pk-search-supplier" class="block w-full pl-10 pr-3 py-2 border border-gray-200 dark:border-gray-600 rounded-xl leading-5 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-primary sm:text-sm" placeholder="Cari pemasok...">
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                    <thead class="bg-gray-50/50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pemasok</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Penjualan</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Terbayar</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sisa Utang</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="pk-supplier-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                        <!-- Data loaded via JS -->
                    </tbody>
                    <tfoot id="pk-supplier-table-foot" class="bg-gray-50/80 dark:bg-gray-700/80 font-bold">
                        <!-- Totals loaded via JS -->
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Pay Form -->
    <div class="lg:col-span-4 space-y-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl lg:sticky lg:top-4 border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 bg-primary/5 border-b border-primary/10">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-send-plus text-primary"></i> Form Pelunasan
                </h2>
            </div>
            <form id="pk-payment-form" class="p-6">
                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Pilih Pemasok</label>
                        <select id="pk-pay-supplier-id" name="supplier_id" class="block w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" required>
                            <option value="">-- Pilih Pemasok --</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Tgl Bayar</label>
                            <input type="text" name="tanggal" id="pk-pay-tanggal" class="block w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Jumlah (Rp)</label>
                            <input type="number" name="jumlah" id="pk-pay-jumlah" class="block w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm font-bold text-primary" placeholder="0" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Sumber Dana</label>
                        <select id="pk-pay-kas-account" name="kas_account_id" class="block w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" required></select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Catatan</label>
                        <textarea name="keterangan" class="block w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" rows="2" placeholder="Keterangan tambahan..."></textarea>
                    </div>
                    <button type="submit" class="w-full inline-flex justify-center items-center px-6 py-3 border border-transparent rounded-xl shadow-md text-sm font-bold text-white bg-primary hover:bg-primary-dark transition-all duration-300 transform hover:-translate-y-0.5 active:scale-95">
                        <i class="bi bi-check2-circle mr-2 text-lg"></i> Proses Pelunasan
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>
</div>

<!-- Tab Content Histori -->
<div id="pk-content-history" class="hidden animate-fade-in" style="display: none;">
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-list-stars text-primary"></i> 
                    Riwayat Pembayaran (UI VERIFIED)
                </h2>
                <p class="text-sm text-gray-500">Daftar semua pembayaran utang konsinyasi</p>
            </div>
            <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
                <select id="pk-filter-history-supplier" class="block w-full md:w-48 px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-xl leading-5 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm focus:ring-1 focus:ring-primary focus:outline-none">
                    <option value="">-- Semua Pemasok --</option>
                </select>
                <div class="relative w-full md:w-64">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="bi bi-search text-sm"></i>
                    </span>
                    <input type="text" id="pk-search-history" class="block w-full pl-10 pr-3 py-2 border border-gray-200 dark:border-gray-600 rounded-xl leading-5 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-primary sm:text-sm" placeholder="Cari di riwayat...">
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <thead class="bg-gray-50/50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pemasok</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Keterangan</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Jumlah</th>
                    </tr>
                </thead>
                <tbody id="pk-history-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                    <tr><td colspan="4" class="text-center py-20 text-gray-400">Memuat data histori...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>
