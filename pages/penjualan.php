<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('penjualan', 'menu');
?>
<div class="flex flex-col sm:flex-row items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-4 sm:mb-0">Transaksi Penjualan</h1>
    <button class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-md font-semibold text-sm text-white shadow-sm hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition ease-in-out duration-150" id="btn-tambah-penjualan">
        <i class="bi bi-plus-lg mr-2"></i> Buat Transaksi Baru
    </button>
</div>

<div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h6 class="text-lg font-bold text-primary">Daftar Transaksi Penjualan</h6>
    </div>
    <div class="p-6">
        <div class="mb-4">
            <div class="w-full md:w-1/3">
                <input type="text" id="search-input" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" placeholder="Cari No. Faktur atau Nama Customer...">
            </div>
        </div>
        <div class="overflow-auto max-h-[65vh] border border-gray-200 dark:border-gray-700 rounded-md">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="penjualanTable">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. Faktur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kasir</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <!-- Data akan dimuat oleh JavaScript -->
                </tbody>
            </table>
        </div>
        <div class="flex justify-between items-center mt-4">
            <div id="pagination-info" class="text-sm text-gray-700 dark:text-gray-300"></div>
            <div id="pagination"></div>
        </div>
    </div>
</div>

<!-- Modal Transaksi Penjualan -->
<div id="penjualanModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="penjualanModalLabel" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('penjualanModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="penjualanModalLabel">Transaksi Penjualan Baru</h5>
                <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeModal('penjualanModal')">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="p-6">
                <form id="form-penjualan">
                    <!-- Form Header -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="tanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                            <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" id="tanggal" name="tanggal" required placeholder="DD-MM-YYYY">
                        </div>
                        <div>
                            <label for="customer_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Customer</label>
                            <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" id="customer_name" name="customer_name" placeholder="Umum">
                        </div>
                        <div>
                            <label for="kasir" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kasir</label>
                            <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 bg-gray-100 cursor-not-allowed shadow-sm" id="kasir" name="kasir" value="<?= htmlspecialchars($_SESSION['username']) ?>" readonly>
                        </div>
                    </div>

                    <!-- Pencarian Barang -->
                    <div class="mb-4 relative">
                        <label for="search-produk" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cari Barang (Kode atau Nama)</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="bi bi-search text-gray-400"></i>
                            </div>
                            <input type="text" class="block w-full pl-10 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" id="search-produk" placeholder="Ketik untuk mencari barang...">
                        </div>
                         <div id="product-suggestions" class="absolute z-10 w-full bg-white dark:bg-gray-700 shadow-lg rounded-md mt-1 max-h-60 overflow-y-auto"></div>
                    </div>

                    <!-- Tabel Item -->
                    <div class="overflow-y-auto max-h-72 mb-4 border border-gray-200 dark:border-gray-700 rounded-md">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="cart-table">
                            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Barang</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-32">Harga</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-24">Qty</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-32">Diskon</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-40">Subtotal</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-16">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="cart-items" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Item yang dipilih akan ditambahkan di sini -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Ringkasan Total -->
                    <div class="flex justify-end mt-6">
                        <div class="w-full md:w-1/2 lg:w-5/12 space-y-4">
                            <!-- Kalkulasi Total -->
                            <div class="space-y-2 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Subtotal</span>
                                    <span id="subtotal" class="text-sm font-medium text-gray-900 dark:text-white">Rp 0</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <label for="discount_total" class="text-sm text-gray-600 dark:text-gray-400">Diskon Global</label>
                                    <input type="number" id="discount_total" value="0" min="0" class="w-32 text-right rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm py-1">
                                </div>
                                <div class="flex justify-between items-center border-t border-gray-200 dark:border-gray-600 pt-2 mt-2">
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">Total</span>
                                    <span id="total" class="text-lg font-bold text-red-600">Rp 0</span>
                                </div>
                            </div>

                            <!-- Pembayaran -->
                            <div class="space-y-2 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <label for="payment_method" class="text-sm text-gray-600 dark:text-gray-400">Metode Bayar</label>
                                    <select id="payment_method" class="w-32 text-right rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm py-1">
                                        <option value="cash">Tunai</option>
                                        <option value="transfer">Transfer Bank</option>
                                        <option value="qris">QRIS</option>
                                    </select>
                                </div>
                                <div id="account-select-container" class="hidden flex justify-between items-center">
                                    <label for="payment_account_id" class="text-sm text-gray-600 dark:text-gray-400">Akun Tujuan <span class="text-red-500">*</span></label>
                                    <select id="payment_account_id" class="w-48 text-right rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm py-1">
                                        <option value="">-- Pilih Akun Bank --</option>
                                    </select>
                                </div>
                                <div class="flex justify-between items-center">
                                    <label for="bayar" class="text-sm text-gray-600 dark:text-gray-400">Jumlah Bayar</label>
                                    <input type="number" id="bayar" min="0" class="w-32 text-right rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm py-1">
                                </div>
                                <div class="flex justify-between items-center border-t border-gray-200 dark:border-gray-600 pt-2 mt-2">
                                    <span class="font-medium text-gray-900 dark:text-white">Kembali</span>
                                    <span id="kembali" class="font-medium text-gray-900 dark:text-white">Rp 0</span>
                                </div>
                                <button type="button" id="btn-uang-pas" class="w-full mt-2 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                    <i class="bi bi-cash-stack"></i> Uang Pas
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Catatan -->
                    <div class="mt-4">
                        <label for="catatan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Catatan (Opsional)</label>
                        <textarea class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" id="catatan" name="catatan" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm" id="btn-simpan-penjualan">
                    <i class="bi bi-save mr-2"></i> Simpan Transaksi
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal('penjualanModal')">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Penjualan -->
<div id="detailModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="detailModalLabel" role="dialog" aria-modal="true">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('detailModal')"></div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
      <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h5 class="text-lg font-medium text-gray-900 dark:text-white" id="detailModalLabel">Detail Transaksi</h5>
        <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeModal('detailModal')">
            <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="p-6" id="detailModalBody">
        <!-- Konten detail akan dimuat di sini -->
      </div>
      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
        <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm" id="btn-cetak-struk">
            <i class="bi bi-printer mr-2"></i> Cetak Struk
        </button>
        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal('detailModal')">
            Tutup
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