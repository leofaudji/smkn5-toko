<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php'; // Assuming this file exists and sets up the page
}
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
        <i class="bi bi-wallet2 text-primary"></i> Pinjaman Anggota
    </h1>
    <button id="btn-add-pinjaman" class="inline-flex items-center gap-2 px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
        <i class="bi bi-plus-circle-fill"></i>
        <span>Ajukan Pinjaman</span>
    </button>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-blue-50 dark:bg-blue-900/50 border border-blue-200 dark:border-blue-800 p-4 rounded-lg flex items-center">
        <div class="bg-blue-100 dark:bg-blue-800 p-3 rounded-full mr-4">
            <i class="bi bi-hourglass-split text-2xl text-blue-500"></i>
        </div>
        <div>
            <p class="text-sm text-blue-600 dark:text-blue-300">Pinjaman Aktif</p>
            <p id="summary-aktif" class="text-2xl font-bold text-blue-800 dark:text-blue-100">0</p>
        </div>
    </div>
    <div class="bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 p-4 rounded-lg flex items-center">
        <div class="bg-green-100 dark:bg-green-800 p-3 rounded-full mr-4">
            <i class="bi bi-cash-stack text-2xl text-green-500"></i>
        </div>
        <div>
            <p class="text-sm text-green-600 dark:text-green-300">Sisa Bakidebet Aktif</p>
            <p id="summary-plafon" class="text-2xl font-bold text-green-800 dark:text-green-100">Rp 0</p>
        </div>
    </div>
    <div class="bg-yellow-50 dark:bg-yellow-900/50 border border-yellow-200 dark:border-yellow-800 p-4 rounded-lg flex items-center">
        <div class="bg-yellow-100 dark:bg-yellow-800 p-3 rounded-full mr-4">
            <i class="bi bi-clock-history text-2xl text-yellow-500"></i>
        </div>
        <div>
            <p class="text-sm text-yellow-600 dark:text-yellow-300">Menunggu Persetujuan</p>
            <p id="summary-pending" class="text-2xl font-bold text-yellow-800 dark:text-yellow-100">0</p>
        </div>
    </div>
</div>

<!-- Filter and Search Controls -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-4 p-4 bg-white dark:bg-gray-800/50 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="flex-1 min-w-[250px]">
        <label for="search-pinjaman" class="sr-only">Cari Pinjaman</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="bi bi-search text-gray-400"></i>
            </div>
            <input type="text" id="search-pinjaman" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white dark:bg-gray-700 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm" placeholder="Cari No. Pinjaman / Nama Anggota...">
        </div>
    </div>
    <div class="flex items-center gap-4">
        <div>
            <label for="filter-status" class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Status:</label>
            <select id="filter-status" class="w-40 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600">
                <option value="all">Semua</option>
                <option value="pending">Pending</option>
                <option value="aktif">Aktif</option>
                <option value="lunas">Lunas</option>
                <option value="ditolak">Ditolak</option>
            </select>
        </div>
    </div>
</div>

<!-- List Pinjaman -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden mb-6">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr id="pinjaman-table-header">
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600" onclick="handleSort('tanggal_pengajuan')" data-sort-by="tanggal_pengajuan">No. Pinjaman <i class="bi bi-arrow-down-up text-gray-400 ml-1"></i></th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600" onclick="handleSort('nama_lengkap')" data-sort-by="nama_lengkap">Anggota <i class="bi bi-arrow-down-up text-gray-400 ml-1"></i></th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jenis</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600" onclick="handleSort('jumlah_pinjaman')" data-sort-by="jumlah_pinjaman">Pokok <i class="bi bi-arrow-down-up text-gray-400 ml-1"></i></th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sisa Bakidebet</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tenor</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
            </tr>
        </thead>
        <tbody id="pinjaman-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
    </table>
    <!-- Pagination Controls -->
    <div id="pagination-controls" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
    </div>
</div>

<!-- Modal Pengajuan -->
<div id="modal-pinjaman" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <form id="form-pinjaman">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <input type="hidden" name="id" id="pinjaman_id">
                    <div class="flex items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-primary-100 dark:bg-primary-900 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="bi bi-file-earmark-plus-fill text-xl text-primary-600 dark:text-primary-300"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">Formulir Pengajuan Pinjaman</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Anggota</label>
                            <select id="anggota_id" name="anggota_id" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required></select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Pinjaman</label>
                            <select id="jenis_pinjaman_id" name="jenis_pinjaman_id" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required></select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah Pinjaman</label>
                                <input type="number" name="jumlah_pinjaman" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tenor (Bulan)</label>
                                <input type="number" name="tenor_bulan" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Pengajuan</label>
                            <input type="date" name="tanggal_pengajuan" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                            <textarea name="keterangan" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm"></textarea>
                        </div>

                        <!-- Input Agunan Dinamis -->
                        <fieldset class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <legend class="text-base font-medium text-gray-900 dark:text-white">Informasi Agunan (Jaminan)</legend>
                            <div class="mt-3 space-y-4">
                                <div>
                                    <label for="tipe_agunan_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipe Agunan</label>
                                    <select id="tipe_agunan_id" name="tipe_agunan_id" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm">
                                        <option value="">-- Tidak ada agunan --</option>
                                    </select>
                                </div>
                                <!-- Kontainer ini akan diisi otomatis oleh Javascript -->
                                <div id="container-detail-agunan" class="space-y-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-md border border-gray-200 dark:border-gray-700 hidden">
                                </div>
                            </div>
                        </fieldset>
                        </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                    <button type="button" id="btn-cancel-modal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail & Jadwal -->
<div id="modal-detail" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                <div class="flex justify-between items-start pb-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <i class="bi bi-info-circle-fill text-2xl text-primary"></i>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Detail Pinjaman</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="btn-pelunasan" class="hidden inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none"><i class="bi bi-check-all mr-1"></i> Pelunasan</button>
                        <button type="button" id="btn-print-pinjaman" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" title="Cetak PDF"><i class="bi bi-printer-fill"></i></button>
                        <button type="button" id="btn-close-detail" class="text-gray-400 hover:text-gray-500"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
                
                <!-- Info Pinjaman -->
                <dl class="grid grid-cols-1 sm:grid-cols-4 gap-x-4 gap-y-6 mb-6">
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Anggota</dt>
                        <dd id="det-nama" class="mt-1 text-sm text-gray-900 dark:text-white font-semibold">-</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">No. Pinjaman</dt>
                        <dd id="det-no" class="mt-1 text-sm text-gray-900 dark:text-white font-semibold">-</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Jumlah Pokok</dt>
                        <dd id="det-jumlah" class="mt-1 text-sm text-gray-900 dark:text-white">-</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                        <dd id="det-status" class="mt-1 text-sm text-gray-900 dark:text-white">-</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Agunan / Jaminan</dt>
                        <dd id="det-agunan" class="mt-1 text-sm text-gray-900 dark:text-white">-</dd>
                    </div>
                </dl>

                <!-- Action Bar (Approve / Pay) -->
                <div id="action-bar" class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/50 border border-yellow-200 dark:border-yellow-800 rounded-lg hidden">
                    <!-- Dynamic Content -->
                </div>

                <!-- Tabel Jadwal -->
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-3">Jadwal Angsuran</h4>
                <div class="overflow-auto max-h-72">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ke</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jatuh Tempo</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Pokok</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Bunga</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="schedule-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pembayaran Angsuran -->
<div id="modal-pembayaran-angsuran" class="fixed inset-0 z-[60] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="form-pembayaran">
                <input type="hidden" id="payment_angsuran_id" name="angsuran_id">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="bi bi-credit-card-2-front-fill text-xl text-blue-600 dark:text-blue-300"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="payment-modal-title">Pembayaran Angsuran</h3>
                            <div class="mt-4 space-y-4">
                                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md">
                                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                        <dt class="text-gray-500">Sisa Pokok:</dt>
                                        <dd id="payment-sisa-pokok" class="text-right font-semibold"></dd>
                                        <dt class="text-gray-500">Sisa Bunga:</dt>
                                        <dd id="payment-sisa-bunga" class="text-right font-semibold"></dd>
                                        <dt class="text-gray-500 font-bold">Total Tagihan:</dt>
                                        <dd id="payment-total-tagihan" class="text-right font-bold"></dd>
                                    </dl>
                                </div>
                                <div>
                                    <label for="payment_jumlah_dibayar" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah Pembayaran</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <input type="number" id="payment_jumlah_dibayar" name="jumlah_dibayar" step="0.01" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 sm:text-sm" required>
                                        <div class="absolute inset-y-0 right-0 flex items-center">
                                            <button type="button" id="btn-lunasi" class="h-full px-3 text-xs text-primary font-semibold">LUNASI</button>
                                        </div>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Pembayaran berlebih akan dialokasikan ke angsuran berikutnya.</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="payment_denda" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Denda (jika ada)</label>
                                        <input type="number" id="payment_denda" name="denda" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 sm:text-sm" value="0">
                                    </div>
                                    <div>
                                        <label for="payment_tanggal_bayar" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Bayar</label>
                                        <input type="date" id="payment_tanggal_bayar" name="tanggal_bayar" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 sm:text-sm" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                                <div>
                                    <label for="payment_akun_kas_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Setor ke Akun Kas</label>
                                    <select id="payment_akun_kas_id" name="akun_kas_id" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required></select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">Simpan Pembayaran</button>
                    <button type="button" id="btn-cancel-payment-modal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Pelunasan Dipercepat -->
<div id="modal-pelunasan" class="fixed inset-0 z-[70] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="form-pelunasan">
                <input type="hidden" id="pelunasan_pinjaman_id" name="pinjaman_id">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Pelunasan Dipercepat</h3>
                    <div class="space-y-4">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-md border border-blue-100 dark:border-blue-800">
                            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                <dt class="text-gray-600 dark:text-gray-400">Sisa Pokok:</dt>
                                <dd id="pelunasan-sisa-pokok" class="text-right font-semibold text-gray-900 dark:text-white">0</dd>
                                <dt class="text-gray-600 dark:text-gray-400">Sisa Bunga:</dt>
                                <dd id="pelunasan-sisa-bunga" class="text-right font-semibold text-gray-900 dark:text-white">0</dd>
                                <dt class="text-gray-600 dark:text-gray-400">Sisa Angsuran:</dt>
                                <dd id="pelunasan-sisa-angsuran" class="text-right font-semibold text-gray-900 dark:text-white">0x</dd>
                            </dl>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Potongan Bunga (Diskon)</label>
                            <input type="number" id="pelunasan_potongan" name="potongan_bunga" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 sm:text-sm" value="0">
                            <p class="text-xs text-gray-500 mt-1">Masukkan jumlah bunga yang dihapuskan/didiskon.</p>
                        </div>

                        <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-md border border-green-100 dark:border-green-800">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-bold text-green-800 dark:text-green-200">Total Yang Harus Dibayar:</span>
                                <span id="pelunasan-total-bayar" class="text-lg font-bold text-green-800 dark:text-green-200">Rp 0</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Pelunasan</label>
                                <input type="date" name="tanggal_bayar" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 sm:text-sm" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Kas</label>
                                <select id="pelunasan_akun_kas_id" name="akun_kas_id" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required></select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                                <input type="text" name="keterangan" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 sm:text-sm" value="Pelunasan Dipercepat">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm">Proses Pelunasan</button>
                    <button type="button" id="btn-cancel-pelunasan" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Notification Container -->
<div id="notification-container" class="fixed bottom-0 right-0 p-6 space-y-3 z-[100]">
    <!-- Notifications will be appended here -->
</div>

<?php if (!$is_spa_request) { require_once PROJECT_ROOT . '/views/footer.php'; } ?>