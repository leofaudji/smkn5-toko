<?php
$app_logo_path = get_setting('app_logo');
$logo_src = !empty($app_logo_path) ? BASE_PATH . '/' . $app_logo_path : BASE_PATH . '/assets/img/logo.png';
?>
<div id="tab-profile" class="tab-content hidden pb-28 bg-gray-50 min-h-screen">
    <!-- Top Bar -->
    <div class="px-6 pt-4 pb-4 flex justify-between items-center bg-white sticky top-0 z-30 shadow-[0_2px_15px_rgba(0,0,0,0.03)]">
        <h2 class="text-xl font-bold text-gray-800">Profil Saya</h2>
        <button onclick="openGamificationModal()" class="relative p-2 text-gray-500 hover:bg-gray-100 rounded-full transition">
            <i class="bi bi-bell"></i>
            <span class="absolute top-2 right-2 w-2 h-2 bg-rose-500 rounded-full border border-white"></span>
        </button>
    </div>

    <!-- Content Container -->
    <div class="px-5 mt-6 space-y-6">
        
        <!-- Digital Member Card (Flip Design) -->
        <style>
            .perspective-1000 { perspective: 1000px; }
            .transform-style-3d { transform-style: preserve-3d; }
            .backface-hidden { backface-visibility: hidden; }
            .rotate-y-180 { transform: rotateY(180deg); }
        </style>
        <div id="member-card-container" class="perspective-1000 w-full aspect-[1.586/1] group cursor-pointer" onclick="toggleCardFlip()">
            <div id="member-card-inner" class="relative w-full h-full transition-all duration-700 transform-style-3d rounded-2xl shadow-2xl">
                
                <!-- Front Face -->
                <div id="member-card-front" class="absolute inset-0 w-full h-full backface-hidden rounded-2xl overflow-hidden bg-gradient-to-br from-slate-800 to-slate-900 p-6 flex flex-col justify-between select-none text-white font-sans border border-white/10">
                    <!-- Glare Effect -->
                    <div class="card-glare absolute inset-0 w-full h-full z-20 pointer-events-none opacity-0 transition-opacity duration-300 mix-blend-overlay" style="background: radial-gradient(circle at 50% 50%, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0) 60%);"></div>
                    
                    <!-- Background Effects -->
                    <div class="absolute top-0 right-0 -mt-12 -mr-12 w-48 h-48 bg-white/10 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 -mb-12 -ml-12 w-48 h-48 bg-black/20 rounded-full blur-3xl"></div>
                    
                    <!-- Header -->
                    <div class="relative z-10 flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-lg tracking-tight leading-none">KOPERASI SEKOLAH</h3>
                            <p class="text-white/60 text-[10px] uppercase tracking-[0.2em] mt-1">Member Card</p>
                        </div>
                        <img src="<?= $logo_src ?>" class="h-8 w-auto object-contain opacity-90 drop-shadow-md" alt="Logo" onerror="this.style.display='none'">
                    </div>

                    <!-- Chip & Number -->
                    <div class="relative z-10 mt-2">
                        <div class="flex justify-between items-center mb-4">
                            <div class="w-11 h-8 rounded bg-gradient-to-br from-yellow-200 to-yellow-500 border border-yellow-600/30 shadow-sm flex items-center justify-center opacity-90 relative overflow-hidden">
                                <div class="absolute inset-0 border border-black/10 rounded"></div>
                                <div class="w-full h-[1px] bg-black/10"></div>
                                <div class="absolute w-[1px] h-full bg-black/10"></div>
                                <div class="absolute w-6 h-4 border border-black/10 rounded-sm"></div>
                            </div>
                            <i class="bi bi-wifi text-2xl text-white/50 rotate-90"></i>
                        </div>
                        <p class="font-mono text-xl sm:text-2xl tracking-widest drop-shadow-md" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3)" id="profile-no-display">
                            0000 0000
                        </p>
                    </div>

                    <!-- Footer -->
                    <div class="relative z-10 flex justify-between items-end mt-auto">
                        <div>
                            <p class="text-white/50 text-[9px] uppercase mb-0.5 tracking-wider">Card Holder</p>
                            <p class="font-bold text-sm sm:text-base tracking-wide truncate max-w-[180px]" id="profile-name-display">NAMA ANGGOTA</p>
                        </div>
                        <div class="flex items-end gap-3">
                            <div id="card-level-badge"></div>
                            <button onclick="event.stopPropagation(); showDigitalCard()" class="w-10 h-10 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl flex items-center justify-center hover:bg-white/20 transition active:scale-95 shadow-lg">
                                <i class="bi bi-qr-code text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Back Face -->
                <div id="member-card-back" class="absolute inset-0 w-full h-full backface-hidden rotate-y-180 rounded-2xl overflow-hidden bg-gradient-to-br from-slate-800 to-slate-900 text-white font-sans border border-white/10">
                    <!-- Glare Effect -->
                    <div class="card-glare absolute inset-0 w-full h-full z-20 pointer-events-none opacity-0 transition-opacity duration-300 mix-blend-overlay" style="background: radial-gradient(circle at 50% 50%, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0) 60%);"></div>

                    <div class="w-full h-10 bg-black/80 mt-6 relative z-10"></div>
                    <div class="px-6 mt-4 relative z-10">
                        <div class="flex gap-3 items-center">
                            <div class="flex-1 h-8 bg-white/20 rounded flex items-center px-2 relative overflow-hidden">
                                <div class="absolute inset-0 bg-white/10 opacity-20"></div>
                                <p class="text-white/50 font-serif text-xs italic ml-2">Authorized Signature</p>
                            </div>
                            <div class="w-12 h-8 bg-white text-slate-800 font-mono font-bold flex items-center justify-center rounded italic text-sm shadow-inner">
                                <span id="card-cvv">000</span>
                            </div>
                        </div>
                        <div class="mt-4 space-y-1">
                            <p class="text-[8px] text-white/60 text-justify leading-tight">
                                Kartu ini adalah bukti keanggotaan Koperasi Sekolah. Harap simpan kartu ini dengan baik. Kehilangan kartu harap segera lapor ke admin koperasi.
                            </p>
                            <div class="flex justify-between items-end pt-2">
                                <div class="text-[9px] text-white/50">
                                    <p>Member Since: <span id="card-since-back">-</span></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[9px] text-white/50">Issuer</p>
                                    <p class="text-[10px] font-bold tracking-wide">SMKN 5 TOKO</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white p-4 rounded-2xl shadow-[0_2px_15px_rgba(0,0,0,0.03)] border border-gray-100 flex items-center gap-3 cursor-pointer hover:bg-gray-50 transition" onclick="openGamificationModal()">
                <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Poin Reward</p>
                    <p class="font-bold text-gray-800" id="profile-points">0 Poin</p>
                </div>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-[0_2px_15px_rgba(0,0,0,0.03)] border border-gray-100 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center">
                    <i class="bi bi-award-fill"></i>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Level</p>
                    <div id="profile-level-badge" class="mt-0.5"></div>
                </div>
            </div>
        </div>

        <!-- Menu Groups -->
        <div class="space-y-4">
            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider ml-1">Keuangan</h4>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <button onclick="document.getElementById('modal-pengaturan-pembayaran').classList.remove('hidden')" class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition border-b border-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center"><i class="bi bi-credit-card-2-front"></i></div>
                        <div class="text-left">
                            <span class="block text-sm font-medium text-gray-700">Metode Pembayaran Utama</span>
                            <span class="block text-[10px] text-gray-400">Atur sumber dana default</span>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-gray-300 text-xs"></i>
                </button>
                <button onclick="document.getElementById('modal-tambah-target').classList.remove('hidden')" class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition border-b border-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center"><i class="bi bi-bullseye"></i></div>
                        <div class="text-left">
                            <span class="block text-sm font-medium text-gray-700">Target Tabungan</span>
                            <span class="block text-[10px] text-gray-400">Rencanakan tujuan finansial</span>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-gray-300 text-xs"></i>
                </button>
                <button onclick="openWishlistFromProfile()" class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition border-b border-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center"><i class="bi bi-heart-fill"></i></div>
                        <div class="text-left">
                            <span class="block text-sm font-medium text-gray-700">Wishlist Belanja</span>
                            <span class="block text-[10px] text-gray-400">Barang impian Anda</span>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-gray-300 text-xs"></i>
                </button>
                <button onclick="switchTab('simulasi')" class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-cyan-100 text-cyan-600 flex items-center justify-center"><i class="bi bi-calculator"></i></div>
                        <div class="text-left">
                            <span class="block text-sm font-medium text-gray-700">Kalkulator & Zakat</span>
                            <span class="block text-[10px] text-gray-400">Hitung angsuran & zakat</span>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-gray-300 text-xs"></i>
                </button>
            </div>

            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider ml-1">Akun & Keamanan</h4>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <button onclick="openQrHistoryModal()" class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition border-b border-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i class="bi bi-qr-code"></i></div>
                        <div class="text-left">
                            <span class="block text-sm font-medium text-gray-700">Riwayat Bayar QR</span>
                            <span class="block text-[10px] text-gray-400">Catatan transaksi QR</span>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-gray-300 text-xs"></i>
                </button>
                <button onclick="document.getElementById('modal-ganti-password').classList.remove('hidden')" class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition border-b border-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center"><i class="bi bi-shield-lock"></i></div>
                        <div class="text-left">
                            <span class="block text-sm font-medium text-gray-700">Ganti Password</span>
                            <span class="block text-[10px] text-gray-400">Amankan akun Anda</span>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-gray-300 text-xs"></i>
                </button>
                <button onclick="document.getElementById('modal-logout').classList.remove('hidden')" class="w-full flex items-center justify-between p-4 hover:bg-red-50 transition group text-left">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-red-100 text-red-500 flex items-center justify-center group-hover:bg-red-200 transition"><i class="bi bi-box-arrow-right"></i></div>
                        <div class="text-left">
                            <span class="block text-sm font-medium text-gray-700 group-hover:text-red-600">Keluar Aplikasi</span>
                            <span class="block text-[10px] text-gray-400 group-hover:text-red-400">Akhiri sesi saat ini</span>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-gray-300 text-xs group-hover:text-red-300"></i>
                </button>
            </div>
        </div>
        
        <!-- Target List (Hidden/Optional view) -->
        <div id="target-list" class="space-y-3 pb-4">
             <!-- Targets loaded here -->
        </div>

        <div class="text-center pb-8 pt-4">
            <p class="text-[10px] text-gray-300">Koperasi SMKN 5 • v1.0.0</p>
        </div>
    </div>
</div>

<!-- Modal Riwayat Bayar QR -->
<div id="modal-qr-history" class="fixed inset-0 z-[80] hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-qr-history').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-t-[2rem] text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-6 pt-6 pb-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Riwayat Pembayaran QR</h3>
                    <button onclick="document.getElementById('modal-qr-history').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>
                <div id="qr-history-list" class="space-y-3 max-h-[60vh] overflow-y-auto pr-1">
                    <!-- Loaded via JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Logout Confirmation -->
<div id="modal-logout" class="fixed inset-0 z-[90] hidden" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-logout').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full">
            <div class="bg-white p-6 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="bi bi-box-arrow-right text-3xl text-red-600 ml-1"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Konfirmasi Keluar</h3>
                <p class="text-sm text-gray-500 mb-6">Apakah Anda yakin ingin mengakhiri sesi dan keluar dari aplikasi?</p>
                <div class="grid grid-cols-2 gap-3">
                    <button onclick="document.getElementById('modal-logout').classList.add('hidden')" class="w-full bg-gray-100 text-gray-700 py-3 rounded-xl font-bold hover:bg-gray-200 transition text-sm">
                        Batal
                    </button>
                    <a href="<?= BASE_PATH ?>/member/logout" class="w-full bg-red-600 text-white py-3 rounded-xl font-bold hover:bg-red-700 transition text-sm flex items-center justify-center shadow-lg shadow-red-200">
                        Ya, Keluar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pengaturan Pembayaran -->
<div id="modal-pengaturan-pembayaran" class="fixed inset-0 z-[80] hidden" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-pengaturan-pembayaran').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-t-[2rem] text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-6 pt-6 pb-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Metode Pembayaran Utama</h3>
                    <button onclick="document.getElementById('modal-pengaturan-pembayaran').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>
                <form id="form-pengaturan-pembayaran" class="space-y-4">
                    <p class="text-sm text-gray-500 mb-4">Pilih sumber dana default yang akan digunakan saat melakukan pembayaran QR atau belanja di toko.</p>
                    <div id="payment-settings-options" class="space-y-2">
                        <!-- Options loaded via JS -->
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white py-3.5 rounded-xl font-bold shadow-lg shadow-indigo-200 hover:shadow-indigo-300 active:scale-[0.98] transition-all text-sm mt-4">Simpan Pengaturan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ganti Password -->
<div id="modal-ganti-password" class="fixed inset-0 z-[80] hidden" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-ganti-password').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-t-[2rem] text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-6 pt-6 pb-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Ganti Password</h3>
                    <button onclick="document.getElementById('modal-ganti-password').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>
                <form id="change-password-form" class="space-y-4">
                    <input type="text" name="username" autocomplete="username" class="hidden" value="<?= $_SESSION['member_no'] ?? '' ?>">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Password Lama</label>
                        <input type="password" name="current_password" autocomplete="current-password" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm transition-all" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Password Baru</label>
                        <input type="password" name="new_password" autocomplete="new-password" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm transition-all" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Konfirmasi Password</label>
                        <input type="password" name="confirm_password" autocomplete="new-password" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm transition-all" required>
                    </div>
                    <div id="password-alert" class="hidden"></div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-3.5 rounded-xl font-bold shadow-lg shadow-blue-200 hover:shadow-blue-300 active:scale-[0.98] transition-all text-sm mt-2">Simpan Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Target -->
<div id="modal-tambah-target" class="fixed inset-0 z-[80] hidden" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-tambah-target').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-t-[2rem] text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-6 pt-6 pb-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Tambah Target</h3>
                    <button onclick="document.getElementById('modal-tambah-target').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>
                <form id="form-tambah-target" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Nama Target</label>
                        <input type="text" name="nama_target" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-sm transition-all" placeholder="Contoh: Beli Laptop" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Nominal Target (Rp)</label>
                        <input type="number" name="nominal_target" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-sm transition-all" placeholder="0" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Target Tercapai Pada</label>
                        <input type="date" name="tanggal_target" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-sm transition-all" required>
                    </div>
                    <button type="submit" class="w-full bg-emerald-600 text-white py-3.5 rounded-xl font-bold shadow-lg shadow-emerald-200 hover:shadow-emerald-300 active:scale-[0.98] transition-all text-sm mt-2">Simpan Target</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Kartu Digital -->
<div id="modal-kartu-digital" class="fixed inset-0 z-[90] hidden" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-80 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-kartu-digital').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full relative">
            <div id="digital-card-content" class="bg-gradient-to-br from-slate-800 to-slate-900 p-6 text-white relative overflow-hidden">
                <!-- Background Pattern -->
                <div class="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full -mr-10 -mt-10 blur-2xl"></div>
                <div class="absolute bottom-0 left-0 w-32 h-32 bg-blue-500/10 rounded-full -ml-10 -mb-10 blur-xl"></div>
                
                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="font-bold text-lg tracking-wide">KARTU ANGGOTA</h3>
                            <p class="text-xs text-slate-400">Koperasi Siswa SMKN 5</p>
                        </div>
                        <img src="<?= $logo_src ?>" class="h-16 w-auto object-contain opacity-90 bg-white/10 rounded p-1 backdrop-blur-sm" alt="Logo" onerror="this.style.display='none'">
                    </div>
                    
                    <div class="flex justify-center my-6">
                        <div class="bg-white p-2 rounded-xl">
                            <img id="card-qr" src="" class="w-32 h-32" alt="QR Code">
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <h2 class="font-bold text-xl mb-1" id="card-name">Nama Anggota</h2>
                        <p class="text-sm text-slate-300 font-mono mb-4" id="card-no">NO. 00000</p>
                        <p class="text-[10px] text-slate-500">Bergabung sejak <span id="card-since">-</span></p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 text-center flex items-center justify-center gap-4">
                <button type="button" class="text-gray-500 hover:text-gray-700 text-sm font-medium" onclick="document.getElementById('modal-kartu-digital').classList.add('hidden')">Tutup</button>
                <button type="button" class="text-blue-600 hover:text-blue-700 text-sm font-bold flex items-center gap-1" onclick="downloadDigitalCard()"><i class="bi bi-download"></i> Simpan PNG</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Gamifikasi -->
<div id="modal-gamifikasi" class="fixed inset-0 z-[80] hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-gamifikasi').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-t-[2rem] text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-6 pt-6 pb-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Riwayat Poin</h3>
                    <button onclick="document.getElementById('modal-gamifikasi').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>
                <div id="gamification-log-list" class="space-y-3 max-h-[60vh] overflow-y-auto pr-1">
                    <!-- Loaded via JS -->
                </div>
            </div>
        </div>
    </div>
</div>