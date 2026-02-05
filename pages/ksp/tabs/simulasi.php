<div id="tab-simulasi" class="tab-content hidden pb-28 bg-gray-50 min-h-screen">
    <!-- Header Section -->
    <div class="relative bg-gradient-to-br from-cyan-500 to-teal-600 pb-12 pt-4 px-6 rounded-b-[2.5rem] shadow-xl overflow-hidden">
        <!-- Decorative Elements -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-20 -mt-20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-40 h-40 bg-white/10 rounded-full -ml-10 -mb-10 blur-2xl"></div>
        
        <div class="relative z-10">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-white/90 font-medium text-sm tracking-wide">Alat Bantu</h2>
                <div class="bg-white/20 backdrop-blur-md p-2 rounded-full shadow-lg">
                    <i class="bi bi-calculator text-white text-lg"></i>
                </div>
            </div>
            
            <div class="text-center mb-2">
                <h1 class="text-3xl font-bold text-white tracking-tight drop-shadow-sm">
                    Simulasi & Zakat
                </h1>
                <p class="text-cyan-100 text-xs font-medium mt-2">Rencanakan keuangan Anda dengan bijak</p>
            </div>
        </div>
    </div> 

    <!-- Content Container -->
    <div class="px-5 -mt-6 relative z-20 space-y-6">
        
        <!-- Simulasi Pinjaman Card -->
        <div class="bg-white rounded-2xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-gray-100">
            <div class="flex items-center gap-3 mb-5 border-b border-gray-50 pb-3">
                <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i class="bi bi-cash-coin text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 text-sm">Simulasi Pinjaman</h3>
                    <p class="text-[10px] text-gray-400">Hitung estimasi angsuran bulanan</p>
                </div>
            </div>

            <form id="simulasi-form" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 ml-1">Jenis Pinjaman</label>
                    <div class="relative">
                        <select id="simulasi_jenis" class="w-full pl-4 pr-10 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm appearance-none transition-all">
                            <option value="">Memuat...</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-gray-500">
                            <i class="bi bi-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 ml-1">Jumlah (Rp)</label>
                        <input type="number" id="simulasi_jumlah" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm transition-all" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 ml-1">Tenor (Bulan)</label>
                        <input type="number" id="simulasi_tenor" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm transition-all" placeholder="12">
                    </div>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-blue-500 text-white py-3.5 rounded-xl font-bold shadow-lg shadow-blue-200 hover:shadow-blue-300 active:scale-[0.98] transition-all text-sm mt-2">
                    Hitung Angsuran
                </button>
            </form>

            <div id="hasil-simulasi" class="mt-6 hidden">
                <div class="relative bg-gray-900 rounded-2xl p-5 text-white overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-10 -mt-10 blur-xl"></div>
                    
                    <h4 class="font-medium text-gray-400 text-xs mb-4 relative z-10">Estimasi Angsuran</h4>
                    
                    <div class="space-y-3 relative z-10">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Pokok</span>
                            <span class="font-medium" id="est-pokok">Rp 0</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Bunga</span>
                            <span class="font-medium text-yellow-400" id="est-bunga">Rp 0</span>
                        </div>
                        <div class="h-px bg-white/10 my-2"></div>
                        <div class="flex justify-between items-end">
                            <span class="text-gray-300 text-xs">Total per Bulan</span>
                            <span class="font-bold text-xl text-white" id="est-total">Rp 0</span>
                        </div>
                    </div>

                    <!-- Chart Canvas -->
                    <div class="mt-5 relative z-10 flex justify-center border-t border-white/10 pt-4">
                        <div class="w-40 h-40">
                            <canvas id="simulasiChart"></canvas>
                        </div>
                    </div>
                </div>
                <p class="text-[10px] text-gray-400 mt-3 text-center italic">* Nilai ini hanya estimasi. Realisasi mungkin berbeda.</p>
            </div>
        </div>

        <!-- Kalkulator Zakat Card -->
        <div class="bg-white rounded-2xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-gray-100">
            <div class="flex items-center gap-3 mb-5 border-b border-gray-50 pb-3">
                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="bi bi-calculator-fill text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 text-sm">Kalkulator Zakat Maal</h3>
                    <p class="text-[10px] text-gray-400">Hitung kewajiban zakat harta</p>
                </div>
            </div>

            <form id="zakat-form" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 ml-1">Total Harta / Simpanan (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3.5 text-gray-400 text-sm">Rp</span>
                        <input type="number" id="zakat_total" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-sm transition-all" placeholder="0">
                    </div>
                    <p class="text-[10px] text-emerald-600 mt-1.5 ml-1 flex items-center gap-1">
                        <i class="bi bi-info-circle"></i> Otomatis terisi dari total simpanan
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 ml-1">Harga Emas per Gram (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3.5 text-gray-400 text-sm">Rp</span>
                        <input type="number" id="zakat_gold_price" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-sm transition-all" value="1300000">
                    </div>
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-emerald-600 to-teal-500 text-white py-3.5 rounded-xl font-bold shadow-lg shadow-emerald-200 hover:shadow-emerald-300 active:scale-[0.98] transition-all text-sm mt-2">
                    Hitung Zakat
                </button>
            </form>

            <div id="hasil-zakat" class="mt-6 hidden">
                <div class="bg-emerald-50 rounded-2xl p-5 border border-emerald-100">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-xs text-emerald-700 font-medium">Nisab (85g Emas)</span>
                        <span class="text-sm font-bold text-emerald-800" id="zakat-nisab">Rp 0</span>
                    </div>
                    
                    <div class="bg-white rounded-xl p-4 text-center shadow-sm mb-3">
                        <p class="text-xs text-gray-500 mb-1">Status Kewajiban</p>
                        <p class="font-bold text-lg text-gray-800" id="zakat-status">-</p>
                    </div>

                    <div class="flex justify-between items-center pt-2 border-t border-emerald-200/50">
                        <span class="text-sm font-bold text-emerald-800">Zakat Wajib (2.5%)</span>
                        <span class="text-lg font-bold text-emerald-700" id="zakat-amount">Rp 0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>