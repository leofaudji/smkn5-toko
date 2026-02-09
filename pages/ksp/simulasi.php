<?php
// Pastikan akses via index.php
if (!defined('PROJECT_ROOT')) exit('No direct script access allowed');

$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Simulasi Kredit</h1>
        <nav class="text-sm text-gray-500 dark:text-gray-400">
            <ol class="list-none p-0 inline-flex">
                <li class="flex items-center">
                    <a href="<?= base_url('/dashboard') ?>" class="hover:text-primary-600">Dashboard</a>
                    <svg class="fill-current w-3 h-3 mx-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"/></svg>
                </li>
                <li>Simulasi Kredit</li>
            </ol>
        </nav>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Section -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                    <i class="bi bi-calculator text-xl text-primary-600 mr-2"></i>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Kalkulator</h2>
                </div>
                
                <form id="form-simulasi">
                    <div class="mb-4">
                        <label for="jumlah_pinjaman" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jumlah Pinjaman (Rp)</label>
                        <input type="text" id="jumlah_pinjaman" class="money-input w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 p-2 border" placeholder="0" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="tenor_bulan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jangka Waktu (Bulan)</label>
                        <input type="number" id="tenor_bulan" min="1" max="60" value="12" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 p-2 border" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="bunga_per_tahun" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bunga per Tahun (%)</label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="number" id="bunga_per_tahun" step="0.01" value="12" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 p-2 border pr-10" required>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">%</span>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Metode perhitungan: Flat (Tetap)</p>
                    </div>
                    
                    <div class="flex flex-col gap-2 mt-6">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out flex items-center justify-center gap-2">
                            <i class="bi bi-calculator-fill"></i> Hitung Angsuran
                        </button>
                        <button type="button" id="btn-reset" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out">
                            Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Result Section -->
        <div class="lg:col-span-2">
            <div id="result-card" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 hidden">
                <div class="flex items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                    <i class="bi bi-table text-xl text-primary-600 mr-2"></i>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Hasil Simulasi</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800 text-center">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Angsuran per Bulan</div>
                        <div class="text-xl font-bold text-blue-700 dark:text-blue-400 mt-1" id="res-angsuran-bulan">Rp 0</div>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-100 dark:border-red-800 text-center">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Total Bunga</div>
                        <div class="text-xl font-bold text-red-700 dark:text-red-400 mt-1" id="res-total-bunga">Rp 0</div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-100 dark:border-green-800 text-center">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Total Pembayaran</div>
                        <div class="text-xl font-bold text-green-700 dark:text-green-400 mt-1" id="res-total-bayar">Rp 0</div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bulan Ke</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pokok</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bunga</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Angsuran</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sisa Pinjaman</th>
                            </tr>
                        </thead>
                        <tbody id="table-schedule-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- Rows generated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="empty-state" class="bg-white dark:bg-gray-800 shadow rounded-lg p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                    <i class="bi bi-calculator text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Belum ada simulasi</h3>
                <p class="mt-1 text-gray-500 dark:text-gray-400">Silakan isi formulir di sebelah kiri untuk melihat hasil simulasi.</p>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>