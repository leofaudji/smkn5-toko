<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="flex justify-between flex-wrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
        <i class="bi bi-gear-fill"></i> Pengaturan KSP
    </h1>
</div>

<div class="mb-4 border-b border-gray-200 dark:border-gray-700">
    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="settingsTabs" role="tablist">
        <li class="mr-2" role="presentation">
            <button class="inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 border-primary text-primary" id="jenis-simpanan-tab" data-tabs-target="#jenis-simpanan" type="button" role="tab" aria-controls="jenis-simpanan" aria-selected="true">Jenis Simpanan</button>
        </li>
        <li class="mr-2" role="presentation">
            <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" id="kategori-transaksi-tab" data-tabs-target="#kategori-transaksi" type="button" role="tab" aria-controls="kategori-transaksi" aria-selected="false">Kategori Transaksi</button>
        </li>
        <li class="mr-2" role="presentation">
            <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" id="jenis-pinjaman-tab" data-tabs-target="#jenis-pinjaman" type="button" role="tab" aria-controls="jenis-pinjaman" aria-selected="false">Jenis Pinjaman</button>
        </li>
        <li class="mr-2" role="presentation">
            <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" id="tipe-agunan-tab" data-tabs-target="#tipe-agunan" type="button" role="tab" aria-controls="tipe-agunan" aria-selected="false">Tipe Agunan</button>
        </li>
    </ul>
</div>

<div id="settingsTabContent">
    <!-- Tab Jenis Simpanan -->
    <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800" id="jenis-simpanan" role="tabpanel" aria-labelledby="jenis-simpanan-tab">
        <div class="flex justify-end mb-4">
            <button onclick="openModalJenisSimpanan()" class="px-4 py-2 bg-primary text-white rounded-md text-sm hover:bg-primary-600">Tambah Jenis Simpanan</button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900 rounded-lg shadow">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Akun COA</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tipe</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nominal Default</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody id="table-jenis-simpanan" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>
        </div>
    </div>

    <!-- Tab Kategori Transaksi -->
    <div class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800" id="kategori-transaksi" role="tabpanel" aria-labelledby="kategori-transaksi-tab">
        <div class="flex justify-end mb-4">
            <button onclick="openModalKategoriTransaksi()" class="px-4 py-2 bg-primary text-white rounded-md text-sm hover:bg-primary-600">Tambah Kategori</button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900 rounded-lg shadow">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama Kategori</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tipe Aksi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Posisi (D/K)</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody id="table-kategori-transaksi" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>
        </div>
    </div>

    <!-- Tab Jenis Pinjaman -->
    <div class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800" id="jenis-pinjaman" role="tabpanel" aria-labelledby="jenis-pinjaman-tab">
        <div class="flex justify-end mb-4">
            <button onclick="openModalJenisPinjaman()" class="px-4 py-2 bg-primary text-white rounded-md text-sm hover:bg-primary-600">Tambah Jenis Pinjaman</button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900 rounded-lg shadow">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Bunga/Tahun</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Akun Piutang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Akun Pendapatan</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody id="table-jenis-pinjaman" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>
        </div>
    </div>

    <!-- Tab Tipe Agunan -->
    <div class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800" id="tipe-agunan" role="tabpanel" aria-labelledby="tipe-agunan-tab">
        <div class="flex justify-end mb-4">
            <button onclick="openModalTipeAgunan()" class="px-4 py-2 bg-primary text-white rounded-md text-sm hover:bg-primary-600">Tambah Tipe Agunan</button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900 rounded-lg shadow">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama Tipe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Konfigurasi Field</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody id="table-tipe-agunan" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Jenis Simpanan -->
<div id="modal-jenis-simpanan" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="form-jenis-simpanan">
                <input type="hidden" name="id" id="js-id">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Jenis Simpanan</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Simpanan</label>
                            <input type="text" name="nama" id="js-nama" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun COA (Liabilitas/Ekuitas)</label>
                            <select name="akun_id" id="js-akun-id" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required></select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipe</label>
                            <select name="tipe" id="js-tipe" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm">
                                <option value="pokok">Pokok</option>
                                <option value="wajib">Wajib</option>
                                <option value="sukarela">Sukarela</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nominal Default (Rp)</label>
                            <input type="number" name="nominal_default" id="js-nominal" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" value="0">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                    <button type="button" onclick="document.getElementById('modal-jenis-simpanan').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Kategori Transaksi -->
<div id="modal-kategori-transaksi" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="form-kategori-transaksi">
                <input type="hidden" name="id" id="kt-id">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Kategori Transaksi</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Kategori</label>
                            <input type="text" name="nama" id="kt-nama" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" placeholder="Contoh: Setoran Awal, Penarikan THR" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipe Aksi</label>
                            <select name="tipe_aksi" id="kt-tipe" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm">
                                <option value="setor">Setor (Uang Masuk)</option>
                                <option value="tarik">Tarik (Uang Keluar)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Posisi Akun Simpanan</label>
                            <select name="posisi" id="kt-posisi" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm">
                                <option value="kredit">Kredit (Menambah Simpanan)</option>
                                <option value="debit">Debit (Mengurangi Simpanan)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                    <button type="button" onclick="document.getElementById('modal-kategori-transaksi').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Jenis Pinjaman -->
<div id="modal-jenis-pinjaman" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="form-jenis-pinjaman">
                <input type="hidden" name="id" id="jp-id">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Jenis Pinjaman</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Pinjaman</label>
                            <input type="text" name="nama" id="jp-nama" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bunga per Tahun (%)</label>
                            <input type="number" name="bunga_per_tahun" id="jp-bunga" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Piutang (Aset)</label>
                            <select name="akun_piutang_id" id="jp-akun-piutang" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required></select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Pendapatan Bunga</label>
                            <select name="akun_pendapatan_bunga_id" id="jp-akun-bunga" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required></select>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                    <button type="button" onclick="document.getElementById('modal-jenis-pinjaman').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tipe Agunan -->
<div id="modal-tipe-agunan" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="form-tipe-agunan">
                <input type="hidden" name="id" id="ta-id">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Tipe Agunan</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Tipe Agunan</label>
                            <input type="text" name="nama" id="ta-nama" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Konfigurasi Field (JSON)</label>
                            <textarea name="config" id="ta-config" rows="5" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm sm:text-sm font-mono text-xs" placeholder='[{"label":"Nomor Polisi","name":"nopol","type":"text"}]'></textarea>
                            <p class="text-xs text-gray-500 mt-1">Format JSON array objek dengan key: label, name, type.</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                    <button type="button" onclick="document.getElementById('modal-tipe-agunan').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!$is_spa_request) { require_once PROJECT_ROOT . '/views/footer.php'; } ?>