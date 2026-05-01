<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
check_permission('stok_opname', 'menu');
?>

<!-- ================================================================
     HEADER
================================================================ -->
<div class="flex flex-col sm:flex-row items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2 mb-4 sm:mb-0">
        <i class="bi bi-clipboard-check-fill text-primary-500"></i> Stok Opname
        <span id="sessionActiveBadge" class="hidden ml-2 px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold rounded-full border border-emerald-200 dark:border-emerald-800/50 animate-pulse">
            <i class="bi bi-people-fill mr-1"></i> Multi-User Aktif
        </span>
    </h1>
    <div id="headerActions" class="flex items-center gap-2"></div>
</div>

<!-- ================================================================
     MODE A: TIDAK ADA SESI AKTIF
================================================================ -->
<div id="so-no-session" class="hidden">

    <!-- Form Buka Sesi Baru -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center">
                <i class="bi bi-plus-lg text-primary-600 dark:text-primary-400 text-sm"></i>
            </div>
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white">Buka Sesi Stok Opname Baru</h5>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                <i class="bi bi-info-circle mr-1"></i>
                Sesi bersama memungkinkan banyak petugas mengisi stok fisik secara bersamaan. Hanya <strong>pembuat sesi</strong> yang dapat melakukan finalisasi.
            </p>
            <form id="createSessionForm">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                    <div>
                        <label for="cs_tanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Opname</label>
                        <input type="text" id="cs_tanggal" name="tanggal" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="dd-mm-yyyy" required>
                    </div>
                    <div>
                        <label for="cs_adj_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Akun Penyeimbang (Selisih)</label>
                        <select id="cs_adj_account_id" name="adj_account_id" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" required>
                            <option value="">Memuat akun...</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Akun untuk mencatat selisih (Beban Kerusakan, Modal, dll).</p>
                    </div>
                    <div>
                        <label for="cs_keterangan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Keterangan</label>
                        <input type="text" id="cs_keterangan" name="keterangan" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="cth: Stok Opname Bulanan April 2026" required>
                    </div>
                </div>
                <button type="submit" id="createSessionBtn" class="inline-flex items-center px-5 py-2 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    <i class="bi bi-play-circle-fill mr-2"></i> Buka Sesi Stok Opname
                </button>
            </form>
        </div>
    </div>

    <!-- Riwayat Sesi -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h5 class="text-base font-semibold text-gray-900 dark:text-white"><i class="bi bi-clock-history mr-2 text-gray-400"></i>Riwayat Sesi</h5>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Keterangan</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Dibuat Oleh</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Diselesaikan Oleh</th>
                    </tr>
                </thead>
                <tbody id="sessionHistoryBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr><td colspan="5" class="text-center py-6 text-gray-400 dark:text-gray-500 text-sm">Memuat riwayat...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================================================================
     MODE B: ADA SESI AKTIF
================================================================ -->
<div id="so-active-session" class="hidden">

    <!-- Banner Info Sesi -->
    <div id="sessionInfoBanner" class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-lg p-4 mb-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center flex-shrink-0 mt-0.5">
                <i class="bi bi-clipboard2-check text-emerald-600 dark:text-emerald-400 text-lg"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-200" id="bannerKeterangan">Memuat info sesi...</p>
                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-0.5" id="bannerMeta"></p>
            </div>
        </div>
        <div id="supervisorActions" class="hidden flex-shrink-0 flex items-center gap-2">
            <button id="finalizeBtn" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors shadow-sm">
                <i class="bi bi-check2-all mr-2"></i> Finalisasi Sesi
            </button>
            <button id="cancelSessionBtn" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 border border-red-200 dark:border-red-800/50 transition-colors">
                <i class="bi bi-x-lg mr-1.5"></i> Batalkan
            </button>
        </div>
    </div>

    <!-- Progress Panel -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-5 p-5">
        <div class="flex items-center justify-between mb-4">
            <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                <i class="bi bi-bar-chart-line text-primary-500"></i> Progress Real-time
            </h6>
            <span class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1">
                <span id="pollingDot" class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Auto-refresh setiap 10 detik
            </span>
        </div>

        <!-- Summary Numbers -->
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <p class="text-2xl font-bold text-gray-800 dark:text-white" id="statTotal">–</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Total Barang</p>
            </div>
            <div class="text-center p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400" id="statSudah">–</p>
                <p class="text-xs text-emerald-500 dark:text-emerald-400 mt-0.5">Sudah Dihitung</p>
            </div>
            <div class="text-center p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400" id="statBelum">–</p>
                <p class="text-xs text-amber-500 dark:text-amber-400 mt-0.5">Belum Dihitung</p>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mb-4">
            <div id="progressBar" class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500" style="width:0%"></div>
        </div>

        <!-- Petugas List -->
        <div id="petugasList" class="flex flex-wrap gap-2">
            <span class="text-xs text-gray-400 dark:text-gray-500">Menunggu data petugas...</span>
        </div>
    </div>

    <!-- Filter + Search Bar -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-0 px-5 py-4 flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
            <input type="text" id="searchInput" placeholder="Cari nama barang atau SKU..." class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
        </div>
        <div class="flex items-center gap-3">
            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 cursor-pointer select-none">
                <input type="checkbox" id="filterBelum" class="rounded border-gray-300 text-primary focus:ring-primary">
                Hanya belum dihitung
            </label>
        </div>
    </div>

    <!-- Tabel Barang -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto" style="max-height: 60vh; overflow-y:auto;">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="itemsTable">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-8">No.</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama Barang</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">SKU</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Stok Sistem</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-36">Stok Fisik</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Selisih</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Oleh / Status</th>
                    </tr>
                </thead>
                <tbody id="itemsTableBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr><td colspan="7" class="text-center p-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>
                </tbody>
            </table>
        </div>
        <div class="px-4 py-2 text-xs text-gray-400 dark:text-gray-500 border-t border-gray-200 dark:border-gray-700" id="tableFooterInfo"></div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>