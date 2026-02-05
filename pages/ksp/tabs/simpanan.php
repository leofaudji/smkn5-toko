<div id="tab-simpanan" class="tab-content hidden pb-28 bg-gray-50 min-h-screen">
    <!-- Header Section -->
    <div class="relative bg-gradient-to-br from-indigo-600 to-blue-500 pb-12 pt-4 px-6 rounded-b-[2.5rem] shadow-xl overflow-hidden">
        <!-- Decorative Elements -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-20 -mt-20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-40 h-40 bg-white/10 rounded-full -ml-10 -mb-10 blur-2xl"></div>
        
        <div class="relative z-10">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-white/90 font-medium text-sm tracking-wide">Tabungan Saya</h2>
                <div class="bg-white/20 backdrop-blur-md p-2 rounded-full shadow-lg">
                    <i class="bi bi-wallet2 text-white text-lg"></i>
                </div>
            </div>
            
            <div class="text-center mb-8">
                <p class="text-blue-100 text-xs font-medium mb-2 uppercase tracking-wider">Total Aset Simpanan</p>
                <h1 class="text-4xl font-bold text-white tracking-tight drop-shadow-sm" id="tab-simpanan-total">
                    Rp 0
                </h1>
            </div>
            
            <!-- Quick Actions -->
            <div class="grid grid-cols-3 gap-4 max-w-xs mx-auto">
                <button onclick="openWithdrawalModal()" class="group flex flex-col items-center gap-2">
                    <div class="w-12 h-12 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl flex items-center justify-center group-active:scale-95 transition shadow-lg">
                        <i class="bi bi-box-arrow-up text-white text-xl"></i>
                    </div>
                    <span class="text-xs text-blue-50 font-medium">Tarik</span>
                </button>
                <button onclick="openTransferModal()" class="group flex flex-col items-center gap-2">
                    <div class="w-12 h-12 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl flex items-center justify-center group-active:scale-95 transition shadow-lg">
                        <i class="bi bi-send text-white text-xl"></i>
                    </div>
                    <span class="text-xs text-blue-50 font-medium">Transfer</span>
                </button>
                <button onclick="loadSimpananHistory()" class="group flex flex-col items-center gap-2">
                    <div class="w-12 h-12 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl flex items-center justify-center group-active:scale-95 transition shadow-lg">
                        <i class="bi bi-arrow-clockwise text-white text-xl"></i>
                    </div>
                    <span class="text-xs text-blue-50 font-medium">Refresh</span>
                </button>
            </div>
        </div>
    </div> 

    <!-- Savings List -->
    <div class="px-5 -mt-6 relative z-20">
        <div class="grid grid-cols-2 gap-3" id="simpanan-types-grid">
            <!-- Content will be loaded by JS -->
            <div class="col-span-2 bg-white p-4 rounded-2xl shadow-sm border border-gray-100 text-center py-8">
                <div class="animate-spin inline-block w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full mb-2"></div>
                <p class="text-xs text-gray-500">Memuat data simpanan...</p>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="px-5 mt-6">
        <h3 class="font-bold text-gray-800 text-lg mb-3">Riwayat Transaksi</h3>
        
        <!-- Filter Grid Responsive -->
        <div class="grid grid-cols-12 gap-3 mb-4">
            <div class="col-span-12 sm:col-span-4">
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Jenis Simpanan</label>
                <div class="relative">
                    <select id="history-filter-jenis" class="w-full text-xs border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 py-2.5 pl-3 pr-8 bg-white shadow-sm text-gray-700 outline-none appearance-none font-medium transition-all cursor-pointer">
                        <option value="">Semua Jenis</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-gray-500"><i class="bi bi-chevron-down text-[10px]"></i></div>
                </div>
            </div>
            <div class="col-span-7 sm:col-span-4">
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Bulan</label>
                <div class="relative">
                    <select id="history-filter-bulan" class="w-full text-xs border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 py-2.5 pl-3 pr-8 bg-white shadow-sm text-gray-700 outline-none appearance-none font-medium transition-all cursor-pointer">
                        <option value="">Semua Bulan</option>
                        <?php
                        $months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                        foreach ($months as $index => $month) echo '<option value="' . ($index + 1) . '">' . $month . '</option>';
                        ?>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-gray-500"><i class="bi bi-chevron-down text-[10px]"></i></div>
                </div>
            </div>
            <div class="col-span-5 sm:col-span-4">
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tahun</label>
                <div class="relative">
                    <select id="history-filter-tahun" class="w-full text-xs border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 py-2.5 pl-3 pr-8 bg-white shadow-sm text-gray-700 outline-none appearance-none font-medium transition-all cursor-pointer">
                        <option value="">Semua Tahun</option>
                        <?php for ($i = 0; $i < 5; $i++) { $y = date('Y') - $i; echo '<option value="' . $y . '">' . $y . '</option>'; } ?>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-gray-500"><i class="bi bi-chevron-down text-[10px]"></i></div>
                </div>
            </div>
        </div>

        <div class="space-y-3 max-h-[50vh] overflow-y-auto pr-1" id="simpanan-history-list">
            <!-- Content will be loaded by JS -->
        </div>
    </div>
</div>

<!-- Modal Detail Simpanan -->
<div id="modal-detail-simpanan" class="fixed inset-0 z-[60] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-detail-simpanan').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h3 class="text-lg font-bold text-gray-900" id="simpanan-detail-title">Detail Transaksi</h3>
                    <button onclick="document.getElementById('modal-detail-simpanan').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>
                <div id="detail-simpanan-content">
                    <div class="text-center py-8">
                        <span class="animate-spin inline-block w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full"></span>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="document.getElementById('modal-detail-simpanan').classList.add('hidden')">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tarik Simpanan -->
<div id="modal-tarik-simpanan" class="fixed inset-0 z-[70] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-tarik-simpanan').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Ajukan Penarikan Simpanan</h3>
                <form id="form-tarik-simpanan" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sumber Simpanan</label>
                        <select name="jenis_simpanan_id" id="withdrawal-source" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                            <option value="">Pilih simpanan...</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Hanya simpanan sukarela yang dapat ditarik.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Penarikan (Rp)</label>
                        <input type="number" name="jumlah" id="withdrawal-amount" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="0" required>
                        <div class="text-xs text-gray-500 mt-1">Saldo tersedia: <span id="withdrawal-balance" class="font-medium">Rp 0</span></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan (Tujuan Penarikan)</label>
                        <textarea name="keterangan" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Contoh: Biaya pendidikan anak"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-xl font-bold hover:bg-green-700 transition">Ajukan Penarikan</button>
                </form>
                
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <h4 class="font-bold text-gray-800 text-sm mb-3">Riwayat Pengajuan Terakhir</h4>
                    <div id="withdrawal-history-list" class="space-y-2 max-h-48 overflow-y-auto pr-1"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Transfer Simpanan -->
<div id="modal-transfer-simpanan" class="fixed inset-0 z-[70] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-transfer-simpanan').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Transfer Simpanan Sukarela</h3>
                <form id="form-transfer-simpanan" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kirim ke Anggota</label>
                        <select id="transfer-destination" name="destination_member_id" placeholder="Cari nama atau nomor anggota..." required></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Transfer (Rp)</label>
                        <input type="number" name="amount" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="0" required>
                        <div class="text-xs text-gray-500 mt-1">Saldo Sukarela tersedia: <span id="transfer-balance" class="font-medium">Rp 0</span></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                        <input type="text" name="notes" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Contoh: Pembayaran utang">
                    </div>
                    <input type="text" name="username" autocomplete="username" class="hidden" value="<?= $_SESSION['member_no'] ?? '' ?>">
                    <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Anda</label>
                        <input type="password" name="password" autocomplete="current-password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500 text-sm" placeholder="Masukkan password untuk konfirmasi" required>
                        <p class="text-xs text-yellow-700 mt-1">Untuk keamanan, masukkan password Anda untuk melanjutkan.</p>
                    </div>
                    <button type="submit" class="w-full bg-cyan-600 text-white py-3 rounded-xl font-bold hover:bg-cyan-700 transition">Kirim Transfer</button>
                </form>
            </div>
        </div>
    </div>
</div>