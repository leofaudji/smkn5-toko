<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('penjualan_item', 'menu');
?>

<style>
@media print {
    @page {
        size: landscape;
        margin: 8mm;
    }
}
</style>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-4 border-b border-gray-200 dark:border-gray-700">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2.5">
            <i class="bi bi-box-seam-fill text-primary"></i> Laporan Penjualan & Margin Stok per Item
        </h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Analisis lengkap mutasi barang (stok awal, masuk, keluar, sisa) dan profitabilitas margin penjualan per item yang tersinkronisasi dengan Laporan Penjualan.
        </p>
    </div>
    <div class="flex items-center gap-2 mt-3 md:mt-0 flex-wrap">
        <button type="button" class="inline-flex items-center px-3 py-2 border border-green-300 dark:border-green-600 shadow-sm text-sm font-medium rounded-lg text-green-700 dark:text-green-300 bg-white dark:bg-gray-800 hover:bg-green-50 dark:hover:bg-gray-700 transition" id="export-penjualan-item-csv">
            <i class="bi bi-file-earmark-spreadsheet-fill text-green-600 dark:text-green-400 mr-2"></i> Export Excel/CSV
        </button>
        <button type="button" class="inline-flex items-center px-3 py-2 border border-red-300 dark:border-red-600 shadow-sm text-sm font-medium rounded-lg text-red-700 dark:text-red-300 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-gray-700 transition" id="export-penjualan-item-pdf">
            <i class="bi bi-file-earmark-pdf-fill text-red-600 dark:text-red-400 mr-2"></i> Export PDF
        </button>
        <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition" id="print-penjualan-item-btn">
            <i class="bi bi-printer-fill text-gray-600 dark:text-gray-400 mr-2"></i> Cetak
        </button>
    </div>
</div>

<!-- Filter Box -->
<div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl mb-6 border border-gray-100 dark:border-gray-700">
    <div class="p-5">
        <form id="report-penjualan-item-form">
            <!-- Preset Buttons -->
            <div class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700 text-xs flex-wrap">
                <span class="font-semibold text-gray-600 dark:text-gray-400 mr-1"><i class="bi bi-calendar3"></i> Periode Cepat:</span>
                <button type="button" class="px-2.5 py-1 rounded-md bg-primary-50 dark:bg-primary-900/30 text-primary hover:bg-primary-100 font-medium transition" id="btn-periode-bulan-ini">Bulan Ini</button>
                <button type="button" class="px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 font-medium transition" id="btn-periode-bulan-lalu">Bulan Lalu</button>
                <button type="button" class="px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 font-medium transition" id="btn-periode-tahun-ini">Tahun Ini</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
                <div class="lg:col-span-3">
                    <label for="penjualan-item-tanggal-mulai" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Dari Tanggal</label>
                    <input type="date" id="penjualan-item-tanggal-mulai" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                </div>
                <div class="lg:col-span-3">
                    <label for="penjualan-item-tanggal-akhir" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Sampai Tanggal</label>
                    <input type="date" id="penjualan-item-tanggal-akhir" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                </div>
                <div class="lg:col-span-2">
                    <label for="penjualan-item-type" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Jenis Barang</label>
                    <select id="penjualan-item-type" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                        <option value="all" selected>Semua Jenis</option>
                        <option value="normal">Barang Toko</option>
                        <option value="consignment">Barang Konsinyasi</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label for="penjualan-item-movement" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Filter Item</label>
                    <select id="penjualan-item-movement" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                        <option value="with_activity" selected>Ada Penjualan / Mutasi</option>
                        <option value="with_sales">Hanya yang Terjual</option>
                        <option value="all">Semua di Master Barang</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition" id="penjualan-item-tampilkan-btn">
                        <i class="bi bi-search mr-2"></i> Tampilkan
                    </button>
                </div>

                <!-- Secondary Filter Line: Search & Sort -->
                <div class="lg:col-span-6">
                    <label for="penjualan-item-search" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Cari Barang</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="penjualan-item-search" class="pl-9 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm" placeholder="Ketik SKU atau Nama Barang...">
                    </div>
                </div>
                <div class="lg:col-span-4">
                    <label for="penjualan-item-sort" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Urutan Data</label>
                    <select id="penjualan-item-sort" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                        <option value="total_margin" selected>Total Margin Keuntungan (Tertinggi)</option>
                        <option value="total_terjual">Jumlah Terjual (Terbanyak)</option>
                        <option value="total_penjualan">Penjualan Bersih (Tertinggi)</option>
                        <option value="margin_pct">Persentase Margin % (Tertinggi)</option>
                        <option value="masuk">Barang Masuk (Terbanyak)</option>
                        <option value="keluar">Barang Keluar (Terbanyak)</option>
                        <option value="stok_akhir">Stok Akhir (Terbanyak)</option>
                        <option value="nama_barang">Nama Barang (A - Z)</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label for="penjualan-item-limit" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tampilkan</label>
                    <select id="penjualan-item-limit" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                        <option value="15" selected>15 baris</option>
                        <option value="25">25 baris</option>
                        <option value="50">50 baris</option>
                        <option value="100">100 baris</option>
                        <option value="-1">Semua Data</option>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Executive Summary Cards Container -->
<div id="report-penjualan-item-summary" class="mb-6">
    <!-- Di-render dinamis oleh JavaScript -->
</div>

<!-- Main Table Card -->
<div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center flex-wrap gap-2">
        <h5 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2" id="report-penjualan-item-header">
            <i class="bi bi-table text-primary"></i> Data Penjualan & Mutasi Stok per Item
        </h5>
        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300">
                <i class="bi bi-check-circle-fill mr-1"></i> Terverifikasi Sinkron Laporan Penjualan
            </span>
        </div>
    </div>
    <div class="p-6">
        <div id="report-penjualan-item-content" class="overflow-x-auto min-h-[250px]">
            <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-200 p-6 rounded-lg text-center font-medium">
                <i class="bi bi-info-circle text-lg mr-2"></i> Silakan pilih rentang tanggal atau klik "Tampilkan".
            </div>
        </div>
        <div class="flex justify-between items-center mt-5 pt-4 border-t border-gray-100 dark:border-gray-700 flex-wrap gap-3">
            <div id="penjualan-item-pagination-info" class="text-xs text-gray-500 dark:text-gray-400 font-medium"></div>
            <div id="penjualan-item-report-pagination">
                <!-- Pagination di-render oleh JS -->
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>