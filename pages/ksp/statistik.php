<?php
if (!defined('PROJECT_ROOT')) exit('No direct script access allowed');

$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Statistik & Kinerja KSP</h1>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Data diperbarui secara real-time
            </div>
        </div>
        <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-2 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <input type="date" id="filter-start-date" class="pl-3 pr-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white focus:ring-primary focus:border-primary">
            <span class="text-gray-500">-</span>
            <input type="date" id="filter-end-date" class="pl-3 pr-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white focus:ring-primary focus:border-primary">
            <select id="filter-compare" class="pl-3 pr-8 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white focus:ring-primary focus:border-primary">
                <option value="mom">Vs Bulan Lalu</option>
                <option value="yoy">Vs Tahun Lalu</option>
            </select>
            <button id="btn-filter-stats" class="p-2 bg-primary text-white rounded-md hover:bg-primary-600 transition-colors">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card 1: Total Aset -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Aset (Pinjaman)</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2" id="stat-aset">Rp 0</h3>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                    <i class="bi bi-wallet2 text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center">
                <span id="badge-aset" class="hidden"></span>
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">vs periode lalu</span>
            </div>
        </div>

        <!-- Card 2: Total Dana -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Dana (Simpanan)</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2" id="stat-dana">Rp 0</h3>
                </div>
                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg">
                    <i class="bi bi-piggy-bank-fill text-emerald-600 dark:text-emerald-400 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center">
                <span id="badge-dana" class="hidden"></span>
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">vs periode lalu</span>
            </div>
        </div>

        <!-- Card 3: Rasio LDR -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rasio LDR</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2" id="stat-ldr">0%</h3>
                </div>
                <div class="p-3 bg-violet-50 dark:bg-violet-900/30 rounded-lg">
                    <i class="bi bi-activity text-violet-600 dark:text-violet-400 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center">
                <span id="badge-ldr" class="hidden"></span>
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">vs periode lalu</span>
            </div>
        </div>

        <!-- Card 4: NPL -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">NPL (Kredit Macet)</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2" id="stat-npl">0%</h3>
                </div>
                <div class="p-3 bg-rose-50 dark:bg-rose-900/30 rounded-lg">
                    <i class="bi bi-exclamation-triangle-fill text-rose-600 dark:text-rose-400 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center">
                <span id="badge-npl" class="hidden"></span>
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">vs periode lalu</span>
            </div>
        </div>
    </div>

    <!-- Row 1: Financial Trends (Growth & Income) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Growth Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 h-full">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Pertumbuhan Aset</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tren Simpanan & Pinjaman</p>
                </div>
                <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg text-blue-600 dark:text-blue-400">
                    <i class="bi bi-graph-up text-xl"></i>
                </div>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="growthChart"></canvas>
            </div>
        </div>

        <!-- Income Trend -->
        <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 h-full">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Pendapatan Jasa</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Bunga & Denda</p>
                </div>
                <div class="p-2 bg-teal-50 dark:bg-teal-900/30 rounded-lg text-teal-600 dark:text-teal-400">
                    <i class="bi bi-cash-coin text-xl"></i>
                </div>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="incomeTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Row 2: Composition & Quality (3 Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <!-- Savings Composition -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Dana Simpanan</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Komposisi per Jenis</p>
                </div>
                <div class="p-2 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg text-emerald-600 dark:text-emerald-400">
                    <i class="bi bi-wallet-fill text-xl"></i>
                </div>
            </div>
            <div class="relative h-64 w-full flex justify-center">
                <canvas id="savingsCompChart"></canvas>
            </div>
        </div>

        <!-- Loan Portfolio -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Portofolio Pinjaman</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Jenis Produk</p>
                </div>
                <div class="p-2 bg-orange-50 dark:bg-orange-900/30 rounded-lg text-orange-600 dark:text-orange-400">
                    <i class="bi bi-pie-chart-fill text-xl"></i>
                </div>
            </div>
            <div class="relative h-64 w-full flex justify-center">
                <canvas id="loanPortfolioChart"></canvas>
            </div>
        </div>

        <!-- Quality Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Kualitas Kredit</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Kolektibilitas (NPL)</p>
                </div>
                <div class="p-2 bg-purple-50 dark:bg-purple-900/30 rounded-lg text-purple-600 dark:text-purple-400">
                    <i class="bi bi-shield-check text-xl"></i>
                </div>
            </div>
            <div class="relative h-64 w-full flex justify-center">
                <canvas id="qualityChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Row 3: Member Insights & Risk (3 Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <!-- Member Growth -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Anggota Baru</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tren Pertumbuhan</p>
                </div>
                <div class="p-2 bg-cyan-50 dark:bg-cyan-900/30 rounded-lg text-cyan-600 dark:text-cyan-400">
                    <i class="bi bi-people-fill text-xl"></i>
                </div>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="memberGrowthChart"></canvas>
            </div>
        </div>

        <!-- Top Savers -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Top Penabung</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saldo Tertinggi</p>
                </div>
                <div class="p-2 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg text-yellow-600 dark:text-yellow-400">
                    <i class="bi bi-trophy-fill text-xl"></i>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Anggota</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Saldo</th>
                        </tr>
                    </thead>
                    <tbody id="top-savers-body" class="divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- Generated by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Borrowers -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Top Peminjam</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Outstanding Terbesar</p>
                </div>
                <div class="p-2 bg-red-50 dark:bg-red-900/30 rounded-lg text-red-600 dark:text-red-400">
                    <i class="bi bi-person-exclamation text-xl"></i>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Anggota</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Sisa Pokok</th>
                        </tr>
                    </thead>
                    <tbody id="top-borrowers-body" class="divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- Generated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Forecast Section -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mt-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">Prediksi Arus Kas</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Proyeksi 3 Bulan Kedepan</p>
            </div>
            <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg text-indigo-600 dark:text-indigo-400">
                <i class="bi bi-graph-up-arrow text-xl"></i>
            </div>
        </div>
        <div class="relative h-72 w-full">
            <canvas id="cashflowForecastChart"></canvas>
        </div>
        <p class="text-xs text-gray-500 mt-2 italic">* Prediksi berdasarkan tren historis 6 bulan terakhir dari aktivitas Simpanan dan Pinjaman.</p>
    </div>
</div>

<!-- Load Chart.js if not already loaded -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>