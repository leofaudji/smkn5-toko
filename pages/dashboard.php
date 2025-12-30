<?php
// Cek apakah ini permintaan dari SPA via AJAX
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';

// Hanya muat header jika ini bukan permintaan SPA
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

?>

                <!-- Dashboard Header & Filters -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="bi bi-speedometer2"></i> Overview
                    </h2>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="open-customize-modal-btn" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium transition">
                            <i class="bi bi-grid-3x3-gap-fill mr-1"></i> Kustomisasi
                        </button>
                        <div class="flex items-center gap-2">
                            <select id="dashboard-bulan-filter" class="form-select block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                                <!-- Options populated by JS -->
                            </select>
                            <select id="dashboard-tahun-filter" class="form-select block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                                <!-- Options populated by JS -->
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Statistic Cards (Dynamic) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Pendapatan -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Pendapatan</p>
                                <h3 class="text-2xl font-bold text-gray-800 mt-2" id="stat-income">Rp 0</h3>
                            </div>
                            <div class="p-2 bg-green-50 rounded-lg text-green-600">
                                <i class="bi bi-arrow-up-right text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <!-- Pengeluaran -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Pengeluaran</p>
                                <h3 class="text-2xl font-bold text-gray-800 mt-2" id="stat-expense">Rp 0</h3>
                            </div>
                            <div class="p-2 bg-red-50 rounded-lg text-red-600">
                                <i class="bi bi-arrow-down-right text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <!-- Laba Bersih -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Laba Bersih</p>
                                <h3 class="text-2xl font-bold text-gray-800 mt-2" id="stat-profit">Rp 0</h3>
                            </div>
                            <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                                <i class="bi bi-wallet2 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <!-- Saldo Kas -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Saldo Kas</p>
                                <h3 class="text-2xl font-bold text-gray-800 mt-2" id="stat-cash">Rp 0</h3>
                            </div>
                            <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                                <i class="bi bi-cash-stack text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi Cepat (Grid Cards) -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
                    <a href="<?= base_url('/transaksi') ?>" class="block bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow text-center group">
                        <div class="w-12 h-12 mx-auto bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-3 group-hover:bg-green-600 group-hover:text-white transition-colors">
                            <i class="bi bi-plus-circle-fill text-2xl"></i>
                        </div>
                        <h6 class="font-semibold text-gray-700">Tambah Transaksi</h6>
                    </a>
                    
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="<?= base_url('/entri-jurnal') ?>" class="block bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow text-center group">
                        <div class="w-12 h-12 mx-auto bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mb-3 group-hover:bg-yellow-500 group-hover:text-white transition-colors">
                            <i class="bi bi-journal-plus text-2xl"></i>
                        </div>
                        <h6 class="font-semibold text-gray-700">Buat Jurnal Umum</h6>
                    </a>
                    <?php endif; ?>

                    <a href="<?= base_url('/konsinyasi') ?>" class="block bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow text-center group">
                        <div class="w-12 h-12 mx-auto bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-3 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <i class="bi bi-box-seam text-2xl"></i>
                        </div>
                        <h6 class="font-semibold text-gray-700">Konsinyasi</h6>
                    </a>

                    <a href="<?= base_url('/laporan') ?>" class="block bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow text-center group">
                        <div class="w-12 h-12 mx-auto bg-cyan-100 text-cyan-600 rounded-full flex items-center justify-center mb-3 group-hover:bg-cyan-600 group-hover:text-white transition-colors">
                            <i class="bi bi-bar-chart-line-fill text-2xl"></i>
                        </div>
                        <h6 class="font-semibold text-gray-700">Lihat Laporan</h6>
                    </a>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="<?= base_url('/coa') ?>" class="block bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow text-center group">
                        <div class="w-12 h-12 mx-auto bg-gray-100 text-gray-600 rounded-full flex items-center justify-center mb-3 group-hover:bg-gray-600 group-hover:text-white transition-colors">
                            <i class="bi bi-journal-bookmark-fill text-2xl"></i>
                        </div>
                        <h6 class="font-semibold text-gray-700">Bagan Akun (COA)</h6>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Grafik Tren Arus Kas (Lebar 2 kolom) --> 
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 border border-gray-100" id="widget-profit_loss_trend">
                        <h5 class="font-bold text-gray-800 mb-4">Tren Arus Kas (Tahun Ini)</h5>
                        <div class="relative h-72 w-full">
                            <canvas id="dashboard-trend-chart"></canvas>
                        </div>
                    </div>
                    <!-- Grafik Kategori Pengeluaran (Lebar 1 kolom) --> 
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100" id="widget-expense_category">
                        <h5 class="font-bold text-gray-800 mb-4">Kategori Pengeluaran</h5>
                        <div class="relative h-72 w-full flex justify-center">
                            <canvas id="dashboard-expense-chart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Profit Growth & Balance Status -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Grafik Pertumbuhan Laba (Lebar 2 kolom) -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 border border-gray-100" id="widget-profit_growth">
                        <h5 class="font-bold text-gray-800 mb-4">Pertumbuhan Laba (Tahun Ini)</h5>
                        <div class="relative h-72 w-full">
                            <canvas id="dashboard-profit-growth-chart"></canvas>
                        </div>
                    </div>
                    <!-- Status Keseimbangan Neraca -->
                    <div class="lg:col-span-1 bg-white rounded-xl shadow-sm p-6 border border-gray-100" id="widget-balance_status">
                        <h5 class="font-bold text-gray-800 mb-4">Status Keseimbangan Neraca</h5>
                        <div id="balance-status-content" class="flex items-center justify-center text-center h-full min-h-[150px]">
                            <!-- Content will be populated by JS -->
                        </div>
                    </div>
                </div>

                <!-- Row 3: Inventory Growth -->
                <div class="grid grid-cols-1 gap-6 mb-8" id="widget-inventory_growth">
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h5 class="font-bold text-gray-800 mb-4">Pertumbuhan Nilai Persediaan</h5>
                        <div class="relative h-72 w-full">
                            <canvas id="dashboard-inventory-growth-chart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions (Full Width) -->
                <div class="grid grid-cols-1 gap-6 mb-8">
                    <!-- Tabel Transaksi Terbaru (lebar 2 kolom) -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100" id="widget-recent_transactions">
                        <h5 class="font-bold text-gray-800 mb-4">Transaksi Terbaru</h5>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <tbody id="dashboard-recent-transactions">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            

<!-- Modal Kustomisasi Dashboard -->
<div id="customizeDashboardModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" data-modal-close="customizeDashboardModal"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Kustomisasi Widget Dashboard
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 mb-4">
                                Pilih widget yang ingin Anda tampilkan di halaman Dashboard.
                            </p>
                            <form id="dashboard-widgets-form" class="space-y-2">
                                <!-- Checkbox akan diisi oleh JavaScript -->
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="save-dashboard-widgets-btn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Simpan Perubahan
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" data-modal-close="customizeDashboardModal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// Hanya muat footer jika ini bukan permintaan SPA
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>