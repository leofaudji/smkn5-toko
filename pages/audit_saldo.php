<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Audit Saldo</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Membandingkan saldo GL (Buku Besar) dengan nilai data operasional (Sub-Ledger)</p>
        </div>
        <button id="refresh-audit" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors">
            <i class="bi bi-arrow-clockwise mr-2"></i> Perbarui Data
        </button>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Modul / Data Operasional</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Akun GL Terkait</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nilai Sub-Ledger</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Saldo GL</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Selisih</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody id="audit-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Data will be loaded via JS -->
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
            <h3 class="text-blue-800 dark:text-blue-300 font-medium mb-2 flex items-center">
                <i class="bi bi-info-circle-fill mr-2"></i> Apa itu Audit Saldo?
            </h3>
            <p class="text-sm text-blue-700 dark:text-blue-400 leading-relaxed">
                Menu ini membantu memastikan akurasi pencatatan antara modul operasional (stok barang, piutang, simpanan) dengan pencatatan akuntansi di Buku Besar. Idealnya, nilai keduanya harus sama (Selisih = 0).
            </p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-lg border border-amber-100 dark:border-amber-800">
            <h3 class="text-amber-800 dark:text-amber-300 font-medium mb-2 flex items-center">
                <i class="bi bi-exclamation-triangle-fill mr-2"></i> Jika Ada Selisih?
            </h3>
            <p class="text-sm text-amber-700 dark:text-amber-400 leading-relaxed">
                Selisih biasanya terjadi karena adanya jurnal manual yang langsung ke akun terkait tanpa melalui modul, atau adanya penghapusan data transaksi secara tidak wajar. Lakukan pengecekan Buku Besar pada akun tersebut.
            </p>
    </div>


    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <!-- Actions Section -->
    <div class="mt-6 p-6 bg-white dark:bg-gray-800 shadow-md rounded-lg border border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Tools Perbaikan Data</h3>
        <div class="flex flex-wrap gap-4">
            <button type="button" onclick="openSyncModal('gl')" class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700 transition-colors">
                <i class="bi bi-journal-check mr-2"></i> Perbaiki GL
            </button>
            <button type="button" onclick="openSyncModal('stock')" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 transition-colors">
                <i class="bi bi-box-seam mr-2"></i> Perbaiki Kartu Stok
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<!-- Modal Pilihan Sinkronisasi GL -->
<div id="syncModalGL" class="fixed inset-0 z-[60] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" aria-hidden="true" onclick="closeSyncModal('gl')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white shadow-2xl rounded-2xl dark:bg-gray-800 sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6 border border-gray-200 dark:border-gray-700">
            <div class="sm:flex sm:items-start">
                <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-amber-100 rounded-full dark:bg-amber-900/30 sm:mx-0 sm:h-10 sm:w-10">
                    <i class="text-amber-600 bi bi-sync dark:text-amber-400"></i>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white" id="modal-title">Sinkronisasi Buku Besar</h3>
                    <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Pilih modul yang ingin dipulihkan datanya ke Buku Besar:
                    </div>
                    
                    <div class="mt-4 space-y-3">
                        <div class="flex flex-col space-y-2">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="sync_modules_gl" value="transaksi" checked class="form-checkbox h-5 w-5 text-amber-500 rounded border-gray-300">
                                <span class="text-gray-700 dark:text-gray-300">Transaksi (Kas/Bank)</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="sync_modules_gl" value="penjualan" checked class="form-checkbox h-5 w-5 text-amber-500 rounded border-gray-300">
                                <span class="text-gray-700 dark:text-gray-300">Penjualan</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="sync_modules_gl" value="pembelian" checked class="form-checkbox h-5 w-5 text-amber-500 rounded border-gray-300">
                                <span class="text-gray-700 dark:text-gray-300">Pembelian</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="sync_modules_gl" value="jurnal" checked class="form-checkbox h-5 w-5 text-amber-500 rounded border-gray-300">
                                <span class="text-gray-700 dark:text-gray-300">Entri Jurnal Umum</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-8 sm:flex sm:flex-row-reverse sm:gap-3">
                <button type="button" onclick="startSync('gl')" class="inline-flex justify-center w-full px-5 py-2.5 text-sm font-semibold text-white bg-amber-600 rounded-lg hover:bg-amber-700 focus:outline-none sm:w-auto shadow-lg shadow-amber-500/30">
                    Mulai Perbaikan
                </button>
                <button type="button" onclick="closeSyncModal('gl')" class="inline-flex justify-center w-full px-5 py-2.5 mt-3 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pilihan Sinkronisasi Kartu Stok -->
<div id="syncModalStock" class="fixed inset-0 z-[60] hidden overflow-y-auto" aria-labelledby="modal-title-stock" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" aria-hidden="true" onclick="closeSyncModal('stock')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white shadow-2xl rounded-2xl dark:bg-gray-800 sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6 border border-gray-200 dark:border-gray-700">
            <div class="sm:flex sm:items-start">
                <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-emerald-100 rounded-full dark:bg-emerald-900/30 sm:mx-0 sm:h-10 sm:w-10">
                    <i class="text-emerald-600 bi bi-box-seam dark:text-emerald-400"></i>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white" id="modal-title-stock">Sinkronisasi Kartu Stok</h3>
                    <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Pilih modul yang ingin disinkronkan kartu stoknya (menghapus duplikat dan mengisi yang kosong):
                    </div>
                    
                    <div class="mt-4 space-y-3">
                        <div class="flex flex-col space-y-2">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="sync_modules_stock" value="pembelian" checked class="form-checkbox h-5 w-5 text-emerald-500 rounded border-gray-300">
                                <span class="text-gray-700 dark:text-gray-300">Pembelian</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="sync_modules_stock" value="penjualan" checked class="form-checkbox h-5 w-5 text-emerald-500 rounded border-gray-300">
                                <span class="text-gray-700 dark:text-gray-300">Penjualan</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-8 sm:flex sm:flex-row-reverse sm:gap-3">
                <button type="button" onclick="startSync('stock')" class="inline-flex justify-center w-full px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none sm:w-auto shadow-lg shadow-emerald-500/30">
                    Mulai Sinkronisasi
                </button>
                <button type="button" onclick="closeSyncModal('stock')" class="inline-flex justify-center w-full px-5 py-2.5 mt-3 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
