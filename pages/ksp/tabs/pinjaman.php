<div id="tab-pinjaman" class="tab-content hidden pb-28 bg-gray-50 min-h-screen">
    <!-- Header Section -->
    <div class="relative bg-gradient-to-br from-violet-600 to-fuchsia-600 pb-12 pt-4 px-6 rounded-b-[2.5rem] shadow-xl overflow-hidden">
        <!-- Decorative Elements -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-20 -mt-20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-40 h-40 bg-white/10 rounded-full -ml-10 -mb-10 blur-2xl"></div>
        
        <div class="relative z-10">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-white/90 font-medium text-sm tracking-wide">Pinjaman Saya</h2>
                <div class="bg-white/20 backdrop-blur-md p-2 rounded-full shadow-lg">
                    <i class="bi bi-cash-coin text-white text-lg"></i>
                </div>
            </div>
            
            <div class="text-center mb-8">
                <p class="text-purple-100 text-xs font-medium mb-2 uppercase tracking-wider">Sisa Pokok Pinjaman</p>
                <h1 class="text-4xl font-bold text-white tracking-tight drop-shadow-sm" id="header-sisa-pinjaman">
                    Rp 0
                </h1>
            </div>
            
            <!-- Quick Actions -->
            <div class="grid grid-cols-2 gap-4 max-w-[200px] mx-auto">
                <button onclick="openLoanApplicationModal()" class="group flex flex-col items-center gap-2">
                    <div class="w-12 h-12 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl flex items-center justify-center group-active:scale-95 transition shadow-lg">
                        <i class="bi bi-plus-lg text-white text-xl"></i>
                    </div>
                    <span class="text-xs text-purple-50 font-medium">Ajukan</span>
                </button>
                <button onclick="loadPinjamanList()" class="group flex flex-col items-center gap-2">
                    <div class="w-12 h-12 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl flex items-center justify-center group-active:scale-95 transition shadow-lg">
                        <i class="bi bi-arrow-clockwise text-white text-xl"></i>
                    </div>
                    <span class="text-xs text-purple-50 font-medium">Refresh</span>
                </button>
            </div>
        </div>
    </div> 
    
    <!-- List Container -->
    <div class="px-5 -mt-6 relative z-20 space-y-4 pb-4" id="pinjaman-list">
        <!-- Skeleton Loading -->
        <?php for($i=0; $i<2; $i++): ?>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm animate-pulse">
            <div class="flex justify-between mb-4">
                <div class="h-4 w-24 bg-gray-200 rounded"></div>
                <div class="h-4 w-16 bg-gray-200 rounded"></div>
            </div>
            <div class="h-6 w-32 bg-gray-200 rounded mb-2"></div>
            <div class="h-2 w-full bg-gray-100 rounded-full mt-4"></div>
        </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Modal Ajukan Pinjaman -->
<div id="modal-ajukan-pinjaman" class="fixed inset-0 z-[70] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-ajukan-pinjaman').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-t-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:rounded-2xl">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Formulir Pengajuan Pinjaman</h3>
                <form id="form-ajukan-pinjaman" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Pinjaman</label>
                        <select name="jenis_pinjaman_id" id="loan-type-select" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                            <option value="">Memuat...</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rp)</label>
                            <input type="number" name="jumlah" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="0" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tenor (Bulan)</label>
                            <input type="number" name="tenor" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="12" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keperluan / Keterangan</label>
                        <textarea name="keterangan" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Contoh: Renovasi rumah"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition">Kirim Pengajuan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Simulasi Pelunasan -->
<div id="modal-simulasi-pelunasan" class="fixed inset-0 z-[80] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-simulasi-pelunasan').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full">
            <div class="bg-white p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Simulasi Pelunasan</h3>
                    <button onclick="document.getElementById('modal-simulasi-pelunasan').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-green-50 p-4 rounded-xl border border-green-100 text-center">
                        <p class="text-xs text-green-600 mb-1 font-medium uppercase tracking-wide">Estimasi Hemat Bunga</p>
                        <h2 class="text-2xl font-extrabold text-green-700 tracking-tight" id="sim-hemat">Rp 0</h2>
                    </div>

                    <div class="space-y-3 text-sm bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Sisa Pokok</span>
                            <span class="font-semibold text-gray-800" id="sim-sisa-pokok">Rp 0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Sisa Bunga Berjalan</span>
                            <span class="font-semibold text-gray-800" id="sim-sisa-bunga">Rp 0</span>
                        </div>
                        <div class="border-t border-dashed border-gray-200 my-2"></div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-800 font-bold">Total Pelunasan</span>
                            <span class="text-xl font-bold text-gray-900" id="sim-total-bayar">Rp 0</span>
                        </div>
                    </div>

                    <p class="text-[10px] text-gray-400 italic mt-2 text-center leading-relaxed">
                        *Perhitungan ini adalah estimasi jika pelunasan dilakukan hari ini. Hubungi admin untuk proses pelunasan.
                    </p>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6">
                <button type="button" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-3 bg-gray-900 text-base font-medium text-white hover:bg-gray-800 sm:text-sm transition-colors" onclick="document.getElementById('modal-simulasi-pelunasan').classList.add('hidden')">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Pinjaman -->
<div id="modal-detail-pinjaman" class="fixed inset-0 z-[60] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-detail-pinjaman').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-t-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:rounded-2xl">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h3 class="text-lg font-bold text-gray-900">Detail Angsuran</h3>
                    <button onclick="document.getElementById('modal-detail-pinjaman').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>
                <div id="detail-pinjaman-content">
                    <!-- Content loaded via JS -->
                    <div class="text-center py-8">
                        <span class="animate-spin inline-block w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full"></span>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="document.getElementById('modal-detail-pinjaman').classList.add('hidden')">Tutup</button>
            </div>
        </div>
    </div>
</div>