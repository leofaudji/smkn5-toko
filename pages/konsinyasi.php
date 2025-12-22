<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-box-seam"></i> Manajemen Konsinyasi</h1>
</div>

<div class="mb-4 border-b border-gray-200 dark:border-gray-700">
    <div class="-mb-px flex space-x-4" aria-label="Tabs" role="tablist" id="konsinyasiTab">
        <button type="button" class="konsinyasi-tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-target="#pemasok-pane">Kelola Pemasok</button>
        <button type="button" class="konsinyasi-tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-target="#barang-pane">Kelola Barang</button>
        <button type="button" class="konsinyasi-tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-target="#penjualan-pane">Penjualan Konsinyasi</button>
        <button type="button" class="konsinyasi-tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-target="#pembayaran-pane">Pembayaran Utang</button>
    </div>
</div>

<div id="konsinyasiTabContent">
    <!-- Tab Kelola Pemasok -->
    <div class="konsinyasi-tab-pane" id="pemasok-pane" role="tabpanel">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-end">
                <button class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none" id="add-supplier-btn"><i class="bi bi-plus-circle mr-2"></i> Tambah Pemasok</button>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Pemasok</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kontak</th><th class="relative px-6 py-3"><span class="sr-only">Aksi</span></th></tr></thead>
                        <tbody id="suppliers-table-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Kelola Barang -->
    <div class="konsinyasi-tab-pane hidden" id="barang-pane" role="tabpanel">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-end">
                <button class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none" id="add-item-btn"><i class="bi bi-plus-circle mr-2"></i> Tambah Barang</button>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Barang</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pemasok</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga Jual</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga Beli</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stok</th><th class="relative px-6 py-3"><span class="sr-only">Aksi</span></th></tr>
                        </thead>
                        <tbody id="items-table-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Penjualan -->
    <div class="konsinyasi-tab-pane hidden" id="penjualan-pane" role="tabpanel">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">Form Penjualan Barang Konsinyasi</h5>
                <a href="#" id="view-consignment-report-link" class="text-sm text-primary hover:underline">Lihat Laporan Penjualan &raquo;</a>
            </div>
            <div class="p-6">
                <form id="consignment-sale-form">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                        <div class="md:col-span-4">
                            <label for="cs-tanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Penjualan</label>
                            <input type="date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="cs-tanggal" required>
                        </div>
                        <div class="md:col-span-4">
                            <label for="cs-item-id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Barang</label>
                            <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="cs-item-id" required></select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="cs-qty" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah</label>
                            <input type="number" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="cs-qty" value="1" min="1" required>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none"><i class="bi bi-cart-plus mr-2"></i> Jual</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tab Pembayaran Utang -->
    <div class="konsinyasi-tab-pane hidden" id="pembayaran-pane" role="tabpanel">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-4">
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h5 class="text-lg font-semibold text-gray-900 dark:text-white">Form Pembayaran Utang</h5></div>
                    <div class="p-6">
                        <form id="consignment-payment-form">
                            <div class="space-y-4">
                                <div><label for="cp-tanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Bayar</label><input type="date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="cp-tanggal" required></div>
                                <div><label for="cp-supplier-id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bayar ke Pemasok</label><select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="cp-supplier-id" required></select></div>
                                <div><label for="cp-jumlah" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah Pembayaran</label><input type="number" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="cp-jumlah" required placeholder="0"></div>
                                <div><label for="cp-kas-account-id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dari Akun Kas/Bank</label><select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="cp-kas-account-id" required></select></div>
                                <div><label for="cp-keterangan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label><textarea class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="cp-keterangan" rows="2"></textarea></div>
                            </div>
                            <button type="submit" class="mt-4 w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none"><i class="bi bi-send-check-fill mr-2"></i> Catat Pembayaran</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-8">
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h5 class="text-lg font-semibold text-gray-900 dark:text-white">Riwayat Pembayaran</h5>
                        <a href="#" id="view-debt-summary-report-link" class="text-sm text-primary hover:underline">Lihat Laporan Sisa Utang &raquo;</a>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pemasok</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jumlah</th></tr></thead>
                                <tbody id="payment-history-table-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pemasok -->
<div id="supplierModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="supplierModalLabel" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('supplierModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="supplierModalLabel"></h5><button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('supplierModal')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="p-6"><form id="supplier-form" class="space-y-4"><input type="hidden" name="id" id="supplier-id"><input type="hidden" name="action" id="supplier-action"><div><label for="nama_pemasok" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Pemasok</label><input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="nama_pemasok" name="nama_pemasok" required></div><div><label for="kontak" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kontak (No. HP/Email)</label><input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="kontak" name="kontak"></div></form></div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"><button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-dark focus:outline-none sm:ml-3 sm:w-auto sm:text-sm" id="save-supplier-btn">Simpan</button><button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('supplierModal')">Batal</button></div>
        </div>
    </div>
</div>

<!-- Modal Barang -->
<div id="itemModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="itemModalLabel" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('itemModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="itemModalLabel"></h5><button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('itemModal')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="p-6"><form id="item-form" class="space-y-4"><input type="hidden" name="id" id="item-id"><input type="hidden" name="action" id="item-action"><div><label for="supplier_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pemasok</label><select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="supplier_id" name="supplier_id" required></select></div><div><label for="nama_barang" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Barang</label><input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="nama_barang" name="nama_barang" required></div><div class="grid grid-cols-2 gap-4"><div><label for="harga_jual" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Harga Jual</label><input type="number" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="harga_jual" name="harga_jual" required></div><div><label for="harga_beli" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Harga Beli (Modal)</label><input type="number" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="harga_beli" name="harga_beli" required></div></div><div class="grid grid-cols-2 gap-4"><div><label for="stok_awal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stok Awal Diterima</label><input type="number" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="stok_awal" name="stok_awal" required></div><div><label for="tanggal_terima" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Terima</label><input type="date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="tanggal_terima" name="tanggal_terima" required></div></div></form></div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"><button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-dark focus:outline-none sm:ml-3 sm:w-auto sm:text-sm" id="save-item-btn">Simpan</button><button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('itemModal')">Batal</button></div>
        </div>
    </div>
</div>

<!-- Modal Laporan Penjualan -->
<div id="consignmentReportModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="consignmentReportModalLabel" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('consignmentReportModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="consignmentReportModalLabel"><i class="bi bi-file-earmark-bar-graph-fill mr-2"></i> Laporan Utang Konsinyasi (Berdasarkan Penjualan)</h5>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('consignmentReportModal')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end mb-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="md:col-span-3"><label for="report-start-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Mulai</label><input type="date" id="report-start-date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm sm:text-sm"></div>
                    <div class="md:col-span-3"><label for="report-end-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Akhir</label><input type="date" id="report-end-date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm sm:text-sm"></div>
                    <div class="md:col-span-6 flex gap-2">
                        <button id="filter-report-btn" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark"><i class="bi bi-filter mr-2"></i> Tampilkan</button>
                        <button id="print-report-btn" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"><i class="bi bi-printer-fill mr-2"></i> Cetak PDF</button>
                    </div>
                </div>
                <div id="consignment-report-body"><p class="text-gray-500 dark:text-gray-400 text-center">Silakan atur filter tanggal dan klik "Tampilkan" untuk melihat laporan.</p></div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"><button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('consignmentReportModal')">Tutup</button></div>
        </div>
    </div>
</div>

<!-- Modal Laporan Sisa Utang -->
<div id="debtSummaryReportModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="debtSummaryReportModalLabel" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('debtSummaryReportModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="debtSummaryReportModalLabel"><i class="bi bi-journal-check mr-2"></i> Laporan Sisa Utang per Pemasok</h5>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('debtSummaryReportModal')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end mb-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div><label for="sisa-utang-start-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Mulai</label><input type="date" id="sisa-utang-start-date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm sm:text-sm"></div>
                    <div><label for="sisa-utang-end-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Akhir</label><input type="date" id="sisa-utang-end-date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm sm:text-sm"></div>
                    <div><button id="filter-sisa-utang-btn" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark"><i class="bi bi-filter mr-2"></i> Tampilkan</button></div>
                </div>
                <div id="debt-summary-report-body"><p class="text-gray-500 dark:text-gray-400 text-center">Silakan atur filter tanggal dan klik "Tampilkan" untuk melihat laporan.</p></div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 sm:ml-3 sm:w-auto sm:text-sm" id="print-debt-summary-btn"><i class="bi bi-printer-fill mr-2"></i> Cetak PDF</button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('debtSummaryReportModal')">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>