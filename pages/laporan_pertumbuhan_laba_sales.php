<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('pertumbuhan_laba_sales', 'menu');
?>

<div class="flex flex-wrap md:flex-nowrap justify-between items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
        <i class="bi bi-graph-up-arrow mr-2 text-primary"></i> 
        Laporan Pertumbuhan Laba Harian (Penjualan)
    </h1>
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="<?php echo base_url('/dashboard'); ?>" class="spa-link text-sm text-gray-700 hover:text-primary dark:text-gray-400 dark:hover:text-white">Dashboard</a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="bi bi-chevron-right text-gray-400 mx-1"></i>
                    <a href="<?php echo base_url('/laporan'); ?>" class="spa-link text-sm text-gray-700 hover:text-primary dark:text-gray-400 dark:hover:text-white">Laporan</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="bi bi-chevron-right text-gray-400 mx-1"></i>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Pertumbuhan Laba (Sales)</span>
                </div>
            </li>
        </ol>
    </nav>
</div>

<!-- Filter Card -->
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg mb-6 overflow-hidden border border-gray-100 dark:border-gray-700">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Mulai</label>
                <input type="date" id="start_date" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
            </div>
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Akhir</label>
                <input type="date" id="end_date" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="md:col-span-3">
                <button id="filter-btn" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all">
                    <i class="bi bi-search mr-2"></i> Tampilkan
                </button>
            </div>
            <div class="md:col-span-3">
                <button id="reset-btn" class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all">
                    <i class="bi bi-arrow-counterclockwise mr-2"></i> Reset
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Chart Card -->
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg mb-6 overflow-hidden border border-gray-100 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 flex items-center">
        <h5 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="bi bi-graph-up mr-2 text-primary"></i>Tren Pertumbuhan Laba</h5>
    </div>
    <div class="p-6">
        <div class="relative w-full h-[350px]">
            <canvas id="profitChart"></canvas>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700 transition-all duration-300 hover:shadow-2xl">
    <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between">
        <h5 class="text-lg font-bold text-gray-800 dark:text-white flex items-center">
            <span class="w-2 h-8 bg-primary rounded-full mr-3"></span>
            Ringkasan Harian
        </h5>
        <div class="text-xs text-gray-400 dark:text-gray-500 font-medium uppercase tracking-wider">
            Profit based on Sales - HPP
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full" id="reportTable">
            <thead>
                <tr class="bg-gray-50/50 dark:bg-gray-900/40">
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tanggal</th>
                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Total Penjualan</th>
                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Total HPP</th>
                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Laba (Profit)</th>
                    <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Pertumbuhan</th>
                </tr>
            </thead>
            <tbody id="reportContent" class="divide-y divide-gray-50 dark:divide-gray-700/50 bg-white dark:bg-gray-800">
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <div class="relative w-12 h-12">
                                <div class="absolute top-0 left-0 w-full h-full border-4 border-primary/20 rounded-full"></div>
                                <div class="absolute top-0 left-0 w-full h-full border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
                            </div>
                            <span class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-400">Menyiapkan laporan premium Anda...</span>
                        </div>
                    </td>
                </tr>
            </tbody>
            <tfoot class="bg-primary/5 dark:bg-primary/10 font-bold border-t-2 border-primary/10">
                <tr class="divide-x divide-transparent">
                    <td class="px-6 py-5 text-sm text-gray-900 dark:text-white uppercase tracking-wider font-extrabold">TOTAL PERIODE</td>
                    <td class="px-6 py-5 text-right text-base text-gray-900 dark:text-white" id="footer-sales">-</td>
                    <td class="px-6 py-5 text-right text-sm text-gray-400 dark:text-gray-500 font-medium" id="footer-hpp">-</td>
                    <td class="px-6 py-5 text-right text-lg text-primary dark:text-primary-light font-black" id="footer-profit">-</td>
                    <td class="px-6 py-5 text-center text-sm text-gray-900 dark:text-white">-</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>

