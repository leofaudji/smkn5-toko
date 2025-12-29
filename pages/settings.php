<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('settings', 'menu');
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-gear-fill"></i> Pengaturan Aplikasi</h1>
</div>

<div class="mb-4 border-b border-gray-200 dark:border-gray-700">
    <div class="-mb-px flex space-x-4" aria-label="Tabs" role="tablist" id="settingsTab">
        <button type="button" class="settings-tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-target="#general-settings">Umum</button>
        <button type="button" class="settings-tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-target="#transaksi-settings">Transaksi</button>
        <button type="button" class="settings-tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-target="#accounting-settings">Akuntansi</button>
        <button type="button" class="settings-tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-target="#arus-kas-settings">Arus Kas</button>
        <button type="button" class="settings-tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-target="#konsinyasi-settings">Konsinyasi</button>
        <button type="button" class="settings-tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-target="#backup-restore-settings">Backup & Restore</button>
    </div>
</div>

<div id="settingsTabContent">
    <!-- Tab Pengaturan Umum -->
    <div class="settings-tab-pane" id="general-settings" role="tabpanel">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="p-6">
                <form id="settings-form" enctype="multipart/form-data">
                    <div id="settings-container">
                        <div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>
                    </div>
                    <hr class="my-6 border-gray-200 dark:border-gray-700">
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="save-settings-btn">
                        <i class="bi bi-save-fill mr-2"></i> Simpan Pengaturan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tab Pengaturan Transaksi -->
    <div class="settings-tab-pane hidden" id="transaksi-settings" role="tabpanel">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="p-6">
                <form id="transaksi-settings-form">
                    <div id="transaksi-settings-container">
                        <div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>
                    </div>
                    <hr class="my-6 border-gray-200 dark:border-gray-700">
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="save-transaksi-settings-btn"><i class="bi bi-save-fill mr-2"></i> Simpan Pengaturan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tab Pengaturan Akuntansi -->
    <div class="settings-tab-pane hidden" id="accounting-settings" role="tabpanel">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">Pengaturan Akun Penting</h5>
            </div>
            <div class="p-6">
                <form id="accounting-settings-form">
                    <div id="accounting-settings-container">
                        <div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>
                    </div>
                    <hr class="my-6 border-gray-200 dark:border-gray-700">
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="save-accounting-settings-btn"><i class="bi bi-save-fill mr-2"></i> Simpan Pengaturan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tab Pengaturan Arus Kas -->
    <div class="settings-tab-pane hidden" id="arus-kas-settings" role="tabpanel">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">Pemetaan Akun untuk Laporan Arus Kas</h5>
            </div>
            <div class="p-6">
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4 text-sm">
                    Tentukan kategori arus kas untuk setiap akun. Ini akan digunakan untuk mengelompokkan transaksi dalam Laporan Arus Kas. Akun yang tidak diklasifikasikan akan dianggap sebagai aktivitas Operasi secara default.
                </div>
                <form id="arus-kas-settings-form">
                    <div id="arus-kas-mapping-container">
                        <div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>
                    </div>
                    <hr class="my-6 border-gray-200 dark:border-gray-700">
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="save-arus-kas-settings-btn">
                        <i class="bi bi-save-fill mr-2"></i> Simpan Pengaturan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tab Pengaturan Konsinyasi -->
    <div class="settings-tab-pane hidden" id="konsinyasi-settings" role="tabpanel">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">Pemetaan Akun untuk Transaksi Konsinyasi</h5>
            </div>
            <div class="p-6">
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4 text-sm">
                    Pilih akun-akun yang akan digunakan saat mencatat penerimaan dan penjualan barang konsinyasi.
                </div>
                <form id="konsinyasi-settings-form">
                    <div id="konsinyasi-settings-container">
                        <div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>
                    </div>
                    <hr class="my-6 border-gray-200 dark:border-gray-700"><button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="save-konsinyasi-settings-btn"><i class="bi bi-save-fill mr-2"></i> Simpan Pengaturan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tab Pengaturan Backup & Restore -->
    <div class="settings-tab-pane hidden" id="backup-restore-settings" role="tabpanel">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">Backup & Restore Database</h5>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Kolom Backup -->
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                    <h6 class="text-base font-medium text-gray-900 dark:text-white mb-2 flex items-center"><i class="bi bi-download mr-2"></i>Buat Cadangan (Backup)</h6>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Buat file cadangan (.sql) dari seluruh database aplikasi. Simpan file ini di tempat yang aman.
                    </p>
                    <button id="backup-db-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="bi bi-server mr-2"></i> Buat & Unduh Backup
                    </button>
                </div>

                <!-- Kolom Restore -->
                <div class="border border-red-300 dark:border-red-700 rounded-lg p-6 bg-red-50 dark:bg-red-900/20">
                    <h6 class="text-base font-medium text-red-800 dark:text-red-200 mb-2 flex items-center"><i class="bi bi-upload mr-2"></i>Pulihkan dari Cadangan (Restore)</h6>
                    <div class="text-sm text-red-700 dark:text-red-300 mb-4">
                        <p class="font-bold">PERINGATAN: Aksi ini akan menghapus semua data saat ini dan menggantinya dengan data dari file backup. Aksi ini tidak dapat dibatalkan.</p>
                    </div>
                    <form id="restore-db-form">
                        <label for="backup-file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pilih file backup (.sql)</label>
                        <input type="file" id="backup-file" name="backup_file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" accept=".sql" required>
                        <button id="restore-db-btn" type="submit" class="mt-4 w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="bi bi-exclamation-triangle-fill mr-2"></i> Pulihkan Database
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>