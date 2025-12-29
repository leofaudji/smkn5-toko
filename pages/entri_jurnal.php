<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
 
// Security check
check_permission('entri_jurnal', 'menu');
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 id="page-title" class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-journal-plus"></i> Entri Jurnal</h1>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h5 class="text-lg font-medium text-gray-900 dark:text-white">Buat Jurnal Umum (Majemuk)</h5>
    </div>
    <div class="p-6">
        <form id="entri-jurnal-form">
            <input type="hidden" name="id" id="jurnal-id">
            <input type="hidden" name="action" id="jurnal-action" value="add">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4">
                <div class="md:col-span-4">
                    <label for="jurnal-tanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                    <input type="date" id="jurnal-tanggal" name="tanggal" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" required>
                </div>
                <div class="md:col-span-8">
                    <label for="jurnal-keterangan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                    <input type="text" id="jurnal-keterangan" name="keterangan" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" placeholder="Deskripsi jurnal..." required>
                </div>
            </div>

            <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-md mb-4">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-5/12">Akun</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Debit</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kredit</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-16">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="jurnal-lines-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- Baris jurnal akan ditambahkan di sini oleh JS -->
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-700 font-bold text-sm text-gray-700 dark:text-gray-300">
                        <tr>
                            <td class="px-4 py-2 text-right">Total</td>
                            <td class="px-4 py-2 text-right" id="total-jurnal-debit">Rp 0</td>
                            <td class="px-4 py-2 text-right" id="total-jurnal-kredit">Rp 0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <button type="button" class="inline-flex items-center px-3 py-1.5 border border-dashed border-gray-400 text-sm font-medium rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none" id="add-jurnal-line-btn"><i class="bi bi-plus-lg mr-2"></i> Tambah Baris</button>
            <hr class="my-6 border-gray-200 dark:border-gray-700">
            <div class="flex justify-end gap-3">
                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="save-as-recurring-btn">
                    <i class="bi bi-arrow-repeat mr-2"></i> Jadikan Berulang...
                </button>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="save-jurnal-entry-btn"><i class="bi bi-save-fill mr-2"></i> Simpan Entri Jurnal</button>
            </div>
        </form>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>