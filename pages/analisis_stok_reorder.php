<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
check_permission('analisis_stok_reorder', 'menu');
?>
<style>
@media print { @page { size: landscape; margin: 8mm; } .no-print { display: none !important; } }
.abc-badge-a  { background:#dcfce7; color:#166534; border:1px solid #86efac; }
.abc-badge-b  { background:#fef3c7; color:#92400e; border:1px solid #fcd34d; }
.abc-badge-c  { background:#e0f2fe; color:#0c4a6e; border:1px solid #7dd3fc; }
.abc-badge-dead { background:#f3f4f6; color:#374151; border:1px solid #d1d5db; }
.dark .abc-badge-a  { background:#14532d; color:#86efac; border-color:#166534; }
.dark .abc-badge-b  { background:#451a03; color:#fcd34d; border-color:#92400e; }
.dark .abc-badge-c  { background:#0c4a6e; color:#7dd3fc; border-color:#0369a1; }
.dark .abc-badge-dead { background:#1f2937; color:#9ca3af; border-color:#374151; }
</style>
<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-4 border-b border-gray-200 dark:border-gray-700">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2.5">
            <i class="bi bi-graph-up-arrow text-primary"></i> Analisis ABC &amp; Reorder Stok
        </h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Klasifikasi Fast-Moving / Slow-Moving / Dead Stock (Pareto), estimasi habis stok, dan rekomendasi reorder berdasarkan rata-rata penjualan harian.
        </p>
    </div>
    <div class="flex items-center gap-2 mt-3 md:mt-0 flex-wrap no-print">
        <button type="button" class="inline-flex items-center px-3 py-2 border border-green-300 dark:border-green-600 shadow-sm text-sm font-medium rounded-lg text-green-700 dark:text-green-300 bg-white dark:bg-gray-800 hover:bg-green-50 dark:hover:bg-gray-700 transition" id="export-analisis-stok-csv">
            <i class="bi bi-file-earmark-spreadsheet-fill text-green-600 dark:text-green-400 mr-2"></i> Export Excel/CSV
        </button>
        <button type="button" class="inline-flex items-center px-3 py-2 border border-red-300 dark:border-red-600 shadow-sm text-sm font-medium rounded-lg text-red-700 dark:text-red-300 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-gray-700 transition" id="export-analisis-stok-pdf">
            <i class="bi bi-file-earmark-pdf-fill text-red-600 dark:text-red-400 mr-2"></i> Export PDF (Landscape)
        </button>
    </div>
</div>
<div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl mb-6 border border-gray-100 dark:border-gray-700 no-print">
    <div class="p-5">
        <form id="report-analisis-stok-form">
            <div class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700 text-xs flex-wrap">
                <span class="font-semibold text-gray-600 dark:text-gray-400 mr-1"><i class="bi bi-calendar3"></i> Periode Analisis:</span>
                <button type="button" class="stok-preset-btn px-2.5 py-1 rounded-md bg-primary-50 dark:bg-primary-900/30 text-primary hover:bg-primary-100 font-medium transition" data-days="30">30 Hari</button>
                <button type="button" class="stok-preset-btn px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 font-medium transition" data-days="60">60 Hari</button>
                <button type="button" class="stok-preset-btn px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 font-medium transition" data-days="90">90 Hari</button>
                <button type="button" id="btn-stok-custom" class="px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 font-medium transition">Kustom</button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
                <div class="lg:col-span-3 hidden" id="stok-date-start-wrap">
                    <label for="analisis-stok-tanggal-mulai" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Dari Tanggal</label>
                    <input type="date" id="analisis-stok-tanggal-mulai" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                </div>
                <div class="lg:col-span-3 hidden" id="stok-date-end-wrap">
                    <label for="analisis-stok-tanggal-akhir" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Sampai Tanggal</label>
                    <input type="date" id="analisis-stok-tanggal-akhir" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                </div>
                <div class="lg:col-span-2">
                    <label for="analisis-stok-category" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Kategori</label>
                    <select id="analisis-stok-category" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                        <option value="all">Semua Kategori</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label for="analisis-stok-buffer" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Buffer Reorder (Hari)</label>
                    <select id="analisis-stok-buffer" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                        <option value="7">7 Hari</option>
                        <option value="14" selected>14 Hari</option>
                        <option value="30">30 Hari</option>
                        <option value="45">45 Hari</option>
                        <option value="60">60 Hari</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label for="analisis-stok-sort" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Urutan</label>
                    <select id="analisis-stok-sort" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                        <option value="total_neto">Penjualan Neto</option>
                        <option value="run_out_days">Sisa Hari Habis</option>
                        <option value="suggested_reorder_qty">Qty Reorder</option>
                        <option value="stok">Stok Saat Ini</option>
                        <option value="nama_barang">Nama Barang</option>
                    </select>
                </div>
                <div class="lg:col-span-1">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Arah</label>
                    <select id="analisis-stok-order" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                        <option value="desc" selected>Z-A</option>
                        <option value="asc">A-Z</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition" id="analisis-stok-tampilkan-btn">
                        <i class="bi bi-search mr-2"></i> Analisis
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<div id="analisis-stok-summary" class="mb-6"></div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">
            <h6 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <i class="bi bi-pie-chart-fill text-primary"></i> Komposisi Modal Persediaan (ABC)
            </h6>
            <span class="text-xs text-gray-400">Nilai Stok per Kelas</span>
        </div>
        <div class="relative h-64 flex items-center justify-center">
            <canvas id="chart-stok-capital-donut"></canvas>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">
            <h6 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-amber-500"></i> SKU Paling Mendesak Reorder
            </h6>
            <span class="text-xs text-gray-400">Top 8 Prioritas</span>
        </div>
        <div class="relative h-64">
            <canvas id="chart-stok-urgent-bar"></canvas>
        </div>
    </div>
</div>
<div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden mb-8">
    <div class="px-6 pt-4 border-b border-gray-200 dark:border-gray-700 no-print">
        <div class="flex items-center gap-1 overflow-x-auto pb-0" id="stok-tab-bar">
            <button class="stok-tab-btn px-3 py-2.5 text-xs font-semibold border-b-2 border-primary text-primary whitespace-nowrap transition" data-tab="all"><i class="bi bi-list-ul mr-1"></i> Semua <span class="ml-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded text-xs" id="tab-count-all">-</span></button>
            <button class="stok-tab-btn px-3 py-2.5 text-xs font-semibold border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 whitespace-nowrap transition" data-tab="critical"><i class="bi bi-exclamation-circle-fill text-red-500 mr-1"></i> Kritis &amp; Habis <span class="ml-1 bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 px-1.5 py-0.5 rounded text-xs" id="tab-count-critical">-</span></button>
            <button class="stok-tab-btn px-3 py-2.5 text-xs font-semibold border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 whitespace-nowrap transition" data-tab="reorder"><i class="bi bi-cart-plus-fill text-amber-500 mr-1"></i> Perlu Reorder <span class="ml-1 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 px-1.5 py-0.5 rounded text-xs" id="tab-count-reorder">-</span></button>
            <button class="stok-tab-btn px-3 py-2.5 text-xs font-semibold border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 whitespace-nowrap transition" data-tab="fast_moving"><i class="bi bi-lightning-fill text-emerald-500 mr-1"></i> Fast Moving (A)</button>
            <button class="stok-tab-btn px-3 py-2.5 text-xs font-semibold border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 whitespace-nowrap transition" data-tab="slow_moving"><i class="bi bi-hourglass-split text-yellow-500 mr-1"></i> Slow Moving (B)</button>
            <button class="stok-tab-btn px-3 py-2.5 text-xs font-semibold border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 whitespace-nowrap transition" data-tab="dead_stock"><i class="bi bi-archive-fill text-gray-500 mr-1"></i> Dead Stock</button>
            <button class="stok-tab-btn px-3 py-2.5 text-xs font-semibold border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 whitespace-nowrap transition" data-tab="overstocked"><i class="bi bi-boxes text-purple-500 mr-1"></i> Overstock</button>
        </div>
    </div>
    <div class="px-6 py-3 flex items-center justify-between flex-wrap gap-3 border-b border-gray-100 dark:border-gray-700">
        <div class="text-xs text-gray-500 dark:text-gray-400" id="stok-table-info">Pilih periode dan klik Analisis</div>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-gray-400"><i class="bi bi-search text-xs"></i></span>
            <input type="text" id="analisis-stok-search" class="pl-8 py-1.5 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary w-48 sm:w-64" placeholder="Cari nama/SKU/barcode...">
        </div>
    </div>
    <div class="p-4">
        <div id="analisis-stok-content" class="overflow-x-auto min-h-[250px]">
            <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-200 p-6 rounded-lg text-center font-medium">
                <i class="bi bi-info-circle text-lg mr-2"></i> Pilih periode analisis dan klik "Analisis" untuk memulai.
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    function loadAndInit() {
        if (typeof initAnalisisStokReorderPage === 'function') {
            initAnalisisStokReorderPage();
        } else {
            var scriptUrl = '<?= base_url("assets/js/analisis_stok_reorder.js?v=" . (file_exists(PROJECT_ROOT . "/assets/js/analisis_stok_reorder.js") ? filemtime(PROJECT_ROOT . "/assets/js/analisis_stok_reorder.js") : time())) ?>';
            var script = document.createElement('script');
            script.src = scriptUrl;
            script.onload = function() {
                if (typeof initAnalisisStokReorderPage === 'function') initAnalisisStokReorderPage();
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
