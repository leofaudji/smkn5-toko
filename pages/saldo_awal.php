<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('saldo_awal', 'menu'); // Ganti dengan key permission baru
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-journal-check"></i> Saldo Awal</h1>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h5 class="text-lg font-medium text-gray-900 dark:text-white">Entri Saldo Awal Perusahaan</h5>
    </div>
    <div class="p-6">
        <div class="bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-400 p-4 mb-4 text-sm text-yellow-700 dark:text-yellow-200">
            <strong>Informasi:</strong> Gunakan halaman ini untuk mengatur saldo awal semua akun pada saat pertama kali menggunakan aplikasi. Pastikan total Debit dan Kredit seimbang (Selisih = Rp 0).
        </div>

        <form id="saldo-awal-form">
            <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-md mb-4">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kode Akun</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Akun</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipe</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/5">Debit</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/5">Kredit</th>
                        </tr>
                    </thead>
                    <tbody id="saldo-awal-grid-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- Grid akan dirender di sini oleh JavaScript -->
                        <tr><td colspan="5" class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-700 font-bold text-sm text-gray-700 dark:text-gray-300">
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-right">Total</td>
                            <td class="px-4 py-2 text-right" id="total-debit">Rp 0</td>
                            <td class="px-4 py-2 text-right" id="total-kredit">Rp 0</td>
                        </tr>
                        <tr id="selisih-row" class="bg-gray-50 dark:bg-gray-900">
                            <td colspan="3" class="px-4 py-2 text-right font-bold">Selisih</td>
                            <td colspan="2" class="px-4 py-2 text-center font-bold" id="total-selisih">Rp 0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <hr class="my-6 border-gray-200 dark:border-gray-700">

            <div class="flex justify-end">
                <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="save-saldo-awal-btn">
                    <i class="bi bi-save-fill mr-2"></i> Simpan Saldo Awal
                </button>
            </div>
        </form>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>