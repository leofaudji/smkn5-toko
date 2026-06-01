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
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
                    <div>
                        <label for="cs_tanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Opname</label>
                        <input type="text" id="cs_tanggal" name="tanggal" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="dd-mm-yyyy" required>
                    </div>
                    <div>
                        <label for="cs_adj_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Akun Beban (Stok Berkurang)</label>
                        <select id="cs_adj_account_id" name="adj_account_id" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" required>
                            <option value="">Memuat akun...</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Akun Beban Selisih Stok (Loss).</p>
                    </div>
                    <div>
                        <label for="cs_income_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Akun Pendapatan (Stok Bertambah)</label>
                        <select id="cs_income_account_id" name="income_account_id" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm" required>
                            <option value="">Memuat akun...</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Akun Pendapatan Selisih Stok (Gain).</p>
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
            <button id="btnScanMode" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-primary-700 bg-primary-50 hover:bg-primary-100 border border-primary-200 transition-colors shadow-sm mr-2">
                <i class="bi bi-camera mr-2"></i> Mode Scan HP
            </button>
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

<!-- ================================================================
     MODAL SCAN HP (Quick Scan Mode)
================================================================ -->
<div id="scanModeModal" class="fixed inset-0 z-[60] hidden overflow-y-auto" aria-labelledby="scanModalLabel" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-0 sm:p-4">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-900 bg-opacity-95 transition-opacity" aria-hidden="true" id="closeScanModalOverlay"></div>

        <!-- Modal Panel -->
        <div class="relative bg-white dark:bg-gray-800 w-full h-screen sm:h-auto sm:max-w-lg sm:rounded-2xl shadow-2xl overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center">
                        <i class="bi bi-upc-scan text-primary-600 dark:text-primary-400"></i>
                    </div>
                    <h5 class="text-base font-bold text-gray-900 dark:text-white" id="scanModalLabel">Quick Scan Opname</h5>
                </div>
                <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" id="closeScanModalBtn">
                    <i class="bi bi-x-lg text-xl"></i>
                </button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-0 flex flex-col">
                <!-- Scanner Viewport -->
                <div class="relative w-full aspect-square sm:aspect-video bg-black overflow-hidden">
                    <div id="reader" class="w-full h-full"></div>
                    <div id="scanOverlay" class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center">
                        <div class="w-64 h-64 border-2 border-primary-500/50 rounded-lg relative">
                            <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-primary-500"></div>
                            <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-primary-500"></div>
                            <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-primary-500"></div>
                            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-primary-500"></div>
                            <div class="absolute inset-x-0 top-1/2 h-0.5 bg-primary-500/30 animate-pulse"></div>
                        </div>
                        <p class="mt-4 text-xs text-white bg-black/40 px-3 py-1 rounded-full backdrop-blur-sm">Arahkan kamera ke Barcode / SKU</p>
                    </div>
                </div>

                <!-- Result & Input Area -->
                <div class="p-6">
                    <!-- Loading / Initial State -->
                    <div id="scanInitialState" class="text-center py-4">
                        <i class="bi bi-camera text-4xl text-gray-300 mb-3 block"></i>
                        <p class="text-sm text-gray-500 mb-4">Menunggu scan barcode...</p>
                        
                        <div class="px-4">
                            <div class="relative group">
                                <input type="text" id="manualScanInput" placeholder="Atau ketik SKU / Barcode..." class="block w-full h-11 rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 bg-gray-50 text-sm focus:ring-primary focus:border-primary transition-all pr-10">
                                <button id="btnManualSearch" class="absolute right-2 top-1.5 w-8 h-8 flex items-center justify-center text-gray-400 group-focus-within:text-primary transition-colors">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                            <p class="mt-2 text-[10px] text-gray-400 italic">Gunakan input ini jika barcode rusak atau kamera tidak fokus.</p>
                        </div>
                    </div>

                    <!-- Scanned Result -->
                    <div id="scanResultArea" class="hidden space-y-5 animate-in fade-in slide-in-from-bottom-4 duration-300">
                        <div class="bg-primary-50 dark:bg-primary-900/10 border border-primary-100 dark:border-primary-800/40 rounded-xl p-4">
                            <div class="flex justify-between items-start mb-2">
                                <h6 class="font-bold text-gray-900 dark:text-white" id="scannedItemName">Nama Barang</h6>
                                <span class="px-2 py-0.5 bg-primary-100 dark:bg-primary-800 text-primary-700 dark:text-primary-300 rounded text-[10px] font-mono" id="scannedItemSku">SKU</span>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mt-3">
                                <div class="text-center p-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider">Sistem</p>
                                    <p class="text-lg font-bold text-gray-700 dark:text-gray-300" id="scannedItemSystem">0</p>
                                </div>
                                <div class="text-center p-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider">Fisik Sblmnya</p>
                                    <p class="text-lg font-bold text-gray-700 dark:text-gray-300" id="scannedItemPrevFisik">–</p>
                                </div>
                            </div>
                        </div>

                        <!-- Input Qty -->
                        <div>
                            <label for="scannedQty" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Jumlah Fisik yang Ditemukan:</label>
                            <div class="flex items-center gap-3">
                                <button type="button" class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xl font-bold" id="btnMinusQty">–</button>
                                <input type="number" id="scannedQty" class="flex-1 h-12 text-center text-2xl font-bold bg-white dark:bg-gray-800 border-2 border-primary-500 rounded-xl focus:ring-0" value="1" min="0">
                                <button type="button" class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xl font-bold" id="btnPlusQty">+</button>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button type="button" class="flex-1 py-3 bg-primary hover:bg-primary-600 text-white font-bold rounded-xl shadow-lg shadow-primary-500/30 transition-all active:scale-95" id="btnSaveScan">
                                <i class="bi bi-save2 mr-2"></i> Simpan & Lanjut
                            </button>
                            <button type="button" class="px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-bold rounded-xl" id="btnSkipScan">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer (Log) -->
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Riwayat Scan Terakhir:</p>
                <div id="scanLog" class="text-xs space-y-1 text-gray-500 dark:text-gray-400 max-h-20 overflow-y-auto">
                    <p class="italic">Belum ada aktivitas scan.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* CSS khusus untuk Scan Mode agar lebih native di mobile */
@media (max-width: 640px) {
    #scanModeModal .relative {
        border-radius: 0;
    }
}
#reader {
    background: black;
}
#reader video {
    object-fit: cover !important;
}
</style>


<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>