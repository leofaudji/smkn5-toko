<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('rekonsiliasi_bank', 'menu');
?>

<div class="flex flex-wrap md:flex-nowrap justify-between items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white"><i class="bi bi-bank2 mr-2"></i> Rekonsiliasi Bank</h1>
    <div>
        <a href="<?= base_url('/histori-rekonsiliasi') ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="bi bi-clock-history mr-2"></i> Lihat Histori
        </a>
    </div>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg mb-6">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-4">
                <label for="recon-akun-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Akun Kas/Bank</label>
                <select id="recon-akun-filter" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm"></select>
            </div>
            <div class="md:col-span-3">
                <label for="recon-tanggal-akhir" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rekonsiliasi s/d Tanggal</label>
                <input type="date" id="recon-tanggal-akhir" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm">
            </div>
            <div class="md:col-span-3">
                <label for="recon-saldo-rekening" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Saldo Akhir Rekening Koran</label>
                <input type="number" id="recon-saldo-rekening" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm" placeholder="Masukkan saldo dari bank">
            </div>
            <div class="md:col-span-2">
                <button class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="recon-tampilkan-btn">
                    <i class="bi bi-search mr-2"></i> Mulai
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Konten Utama -->
<div id="reconciliation-content" class="hidden">
    <!-- Ringkasan -->
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white">Ringkasan Rekonsiliasi</h5>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white" id="summary-saldo-buku">Rp 0</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Saldo Akhir di Aplikasi</div>
                </div>
                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white" id="summary-saldo-bank">Rp 0</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Saldo Akhir di Bank</div>
                </div>
                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="summary-cleared">Rp 0</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Total Transaksi Cocok (Cleared)</div>
                </div>
                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400" id="summary-selisih">Rp 0</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Selisih</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Rekonsiliasi -->
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
        <div class="p-6">
            <div>
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Transaksi di Aplikasi</h5>
                <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg" style="max-height: 500px;">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                            <tr>
                                <th class="px-6 py-3"><input type="checkbox" id="check-all-app" class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-offset-0 focus:ring-primary focus:ring-opacity-50"></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pemasukan (Debit)</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pengeluaran (Kredit)</th>
                            </tr>
                        </thead>
                        <tbody id="app-transactions-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- Data dari API -->
                        </tbody>
                    </table>
                </div>
            </div>
            <hr class="my-6 border-gray-200 dark:border-gray-700">
            <div class="flex justify-end">
                <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed" id="save-reconciliation-btn" disabled>
                    <i class="bi bi-check-circle-fill mr-2"></i> Simpan Rekonsiliasi
                </button>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>
