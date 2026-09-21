<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('margin_kategori', 'menu');
?>

<style>
@media print {
    @page {
        size: landscape;
        margin: 8mm;
    }
    .no-print {
        display: none !important;
    }
}
</style>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-4 border-b border-gray-200 dark:border-gray-700">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2.5">
            <i class="bi bi-pie-chart-fill text-primary"></i> Laporan Kontribusi & Margin per Kategori
        </h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Analisis kinerja penjualan, modal HPP, margin keuntungan, dan ketebalan margin (%) per kategori barang yang tersinkronisasi 100% dengan Laporan Penjualan Toko.
        </p>
    </div>
    <div class="flex items-center gap-2 mt-3 md:mt-0 flex-wrap no-print">
        <button type="button" class="inline-flex items-center px-3 py-2 border border-green-300 dark:border-green-600 shadow-sm text-sm font-medium rounded-lg text-green-700 dark:text-green-300 bg-white dark:bg-gray-800 hover:bg-green-50 dark:hover:bg-gray-700 transition" id="export-margin-kategori-csv">
            <i class="bi bi-file-earmark-spreadsheet-fill text-green-600 dark:text-green-400 mr-2"></i> Export Excel/CSV
        </button>
        <button type="button" class="inline-flex items-center px-3 py-2 border border-red-300 dark:border-red-600 shadow-sm text-sm font-medium rounded-lg text-red-700 dark:text-red-300 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-gray-700 transition" id="export-margin-kategori-pdf">
            <i class="bi bi-file-earmark-pdf-fill text-red-600 dark:text-red-400 mr-2"></i> Export PDF (Landscape)
        </button>
        <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition" id="print-margin-kategori-btn">
            <i class="bi bi-printer-fill text-gray-600 dark:text-gray-400 mr-2"></i> Cetak
        </button>
    </div>
</div>

<!-- Filter Box -->
<div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl mb-6 border border-gray-100 dark:border-gray-700 no-print">
    <div class="p-5">
        <form id="report-margin-kategori-form">
            <!-- Preset Buttons -->
            <div class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700 text-xs flex-wrap">
                <span class="font-semibold text-gray-600 dark:text-gray-400 mr-1"><i class="bi bi-calendar3"></i> Periode Cepat:</span>
                <button type="button" class="px-2.5 py-1 rounded-md bg-primary-50 dark:bg-primary-900/30 text-primary hover:bg-primary-100 font-medium transition" id="btn-kategori-bulan-ini">Bulan Ini</button>
                <button type="button" class="px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 font-medium transition" id="btn-kategori-bulan-lalu">Bulan Lalu</button>
                <button type="button" class="px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 font-medium transition" id="btn-kategori-tahun-ini">Tahun Ini</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
                <div class="lg:col-span-3">
                    <label for="margin-kategori-tanggal-mulai" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Dari Tanggal</label>
                    <input type="date" id="margin-kategori-tanggal-mulai" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                </div>
                <div class="lg:col-span-3">
                    <label for="margin-kategori-tanggal-akhir" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Sampai Tanggal</label>
                    <input type="date" id="margin-kategori-tanggal-akhir" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                </div>
                <div class="lg:col-span-3">
                    <label for="margin-kategori-sort" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Urutan Data</label>
                    <select id="margin-kategori-sort" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                        <option value="total_neto" selected>Penjualan Bersih (Neto)</option>
                        <option value="total_margin">Total Margin Keuntungan</option>
                        <option value="margin_pct">Ketebalan Margin (%)</option>
                        <option value="kontribusi_sales_pct">Pangsa Omset (%)</option>
                        <option value="kontribusi_margin_pct">Pangsa Margin (%)</option>
                        <option value="total_qty">Jumlah Qty Terjual</option>
                        <option value="total_sku">Ragam Produk (SKU)</option>
                        <option value="kategori_nama">Nama Kategori (A - Z)</option>
                    </select>
                </div>
                <div class="lg:col-span-1">
                    <label for="margin-kategori-order" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Arah</label>
                    <select id="margin-kategori-order" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                        <option value="desc" selected>Z-A</option>
                        <option value="asc">A-Z</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition" id="margin-kategori-tampilkan-btn">
                        <i class="bi bi-search mr-2"></i> Tampilkan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Executive KPI Summary Cards Container -->
<div id="report-margin-kategori-summary" class="mb-6">
    <!-- Populated dynamically via JS -->
</div>

<!-- Interactive Analytics Visualizations (Charts) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Donut Chart 1: Kontribusi Penjualan -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">
            <h6 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <i class="bi bi-pie-chart text-blue-600"></i> Pangsa Omset Penjualan
            </h6>
            <span class="text-xs text-gray-400">Top Kategori</span>
        </div>
        <div class="relative h-64 flex items-center justify-center">
            <canvas id="chart-sales-donut"></canvas>
        </div>
    </div>

    <!-- Donut Chart 2: Kontribusi Margin -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">
            <h6 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <i class="bi bi-pie-chart-fill text-emerald-600"></i> Pangsa Margin Keuntungan
            </h6>
            <span class="text-xs text-gray-400">Kontribusi Laba</span>
        </div>
        <div class="relative h-64 flex items-center justify-center">
            <canvas id="chart-margin-donut"></canvas>
        </div>
    </div>

    <!-- Bar Chart: Ketebalan Margin % -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">
            <h6 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <i class="bi bi-bar-chart-line-fill text-amber-500"></i> Ketebalan Margin (%)
            </h6>
            <span class="text-xs text-gray-400">Margin Tertinggi</span>
        </div>
        <div class="relative h-64">
            <canvas id="chart-margin-bar"></canvas>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <h5 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="bi bi-grid-3x3-gap-fill text-primary"></i> Matriks Kontribusi & Margin per Kategori
            </h5>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300">
                <i class="bi bi-check-circle-fill mr-1"></i> Sinkron Laporan Penjualan
            </span>
        </div>
        <div class="flex items-center gap-2">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-gray-400">
                    <i class="bi bi-filter"></i>
                </span>
                <input type="text" id="filter-kategori-search" class="pl-8 py-1.5 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary w-48 sm:w-64" placeholder="Filter nama kategori...">
            </div>
        </div>
    </div>

    <div class="p-6">
        <div id="report-margin-kategori-content" class="overflow-x-auto min-h-[250px]">
            <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-200 p-6 rounded-lg text-center font-medium">
                <i class="bi bi-info-circle text-lg mr-2"></i> Silakan pilih rentang tanggal atau klik "Tampilkan".
            </div>
        </div>
    </div>
</div>

<!-- Drill-down Modal: Rincian Produk per Kategori -->
<div id="modal-category-items" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" id="modal-category-backdrop"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-200 dark:border-gray-700">
            <!-- Modal Header -->
            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2" id="modal-category-title">
                        <i class="bi bi-box-seam text-primary"></i> Produk dalam Kategori
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" id="modal-category-subtitle">
                        Daftar produk terlaris dan rincian margin dalam periode yang dipilih.
                    </p>
                </div>
                <button type="button" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition" id="modal-category-close">
                    <i class="bi bi-x-lg text-lg"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6 max-h-[65vh] overflow-y-auto" id="modal-category-body">
                <div class="flex justify-center items-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-3 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                <button type="button" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition" id="modal-category-close-btn">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Self-initializing Script Loader (Guarantees execution on SPA and Full Reload) -->
<script>
(function() {
    function loadAndInit() {
        if (typeof initLaporanMarginKategoriPage === 'function') {
            initLaporanMarginKategoriPage();
        } else {
            const scriptUrl = '<?= base_url("assets/js/laporan_margin_kategori.js?v=" . (file_exists(PROJECT_ROOT . "/assets/js/laporan_margin_kategori.js") ? filemtime(PROJECT_ROOT . "/assets/js/laporan_margin_kategori.js") : time())) ?>';
            const script = document.createElement('script');
            script.src = scriptUrl;
            script.onload = function() {
                if (typeof initLaporanMarginKategoriPage === 'function') {
                    initLaporanMarginKategoriPage();
                }
            };
            document.head.appendChild(script);
        }
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(loadAndInit, 100);
    } else {
        document.addEventListener('DOMContentLoaded', loadAndInit);
    }
})();
</script>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>
