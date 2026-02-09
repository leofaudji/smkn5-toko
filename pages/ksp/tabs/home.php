<div id="tab-home" class="tab-content pb-28 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div id="home-header" class="px-6 pt-4 pb-6 bg-gray-50 sticky top-0 z-30 transition-all duration-300">
        <div class="flex justify-between items-center">
            <div>
                <p id="greeting-text" class="text-xs text-gray-500 font-medium flex items-center gap-1">Selamat Datang,</p>
                <h1 class="text-lg font-bold text-gray-800 tracking-tight truncate max-w-[200px]" id="member-name">Anggota</h1>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="togglePrivacyMode()" id="btn-privacy-mode" class="w-10 h-10 rounded-full bg-white text-gray-400 flex items-center justify-center shadow-sm border border-gray-100 hover:bg-gray-50 transition">
                    <i class="bi bi-eye"></i>
                </button>
                <button onclick="switchTab('profile')" class="w-12 h-12 rounded-full p-0.5 border-2 border-blue-200">
                    <img src="https://ui-avatars.com/api/?name=Member&background=random" id="home-avatar" class="w-full h-full rounded-full object-cover" alt="Profile">
                </button>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="px-6 space-y-6">
        <!-- Level & Points Card -->
        <div class="relative bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-5 shadow-lg shadow-emerald-300/50 overflow-hidden text-white">
            <!-- Shimmer Effect -->
            <div class="absolute inset-0 z-0 pointer-events-none">
                <div class="h-full w-1/2 bg-gradient-to-r from-transparent via-white/10 to-transparent animate-shimmer"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <div class="flex items-center gap-1.5 mb-1">
                            <p class="text-xs text-emerald-100 font-medium">Level Keanggotaan</p>
                            <button onclick="openLevelInfoModal()" class="text-emerald-200 hover:text-white transition"><i class="bi bi-info-circle"></i></button>
                        </div>
                        <div id="header-level-badge" class="inline-block"></div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-emerald-100 font-medium mb-1">Poin Reward</p>
                        <p class="text-2xl font-bold" id="home-points">0</p>
                    </div>
                </div>

                <!-- Progress Bar to Next Level (Dummy Logic for Visual) -->
                <div class="space-y-1">
                    <div class="flex justify-between text-[10px] text-emerald-100 font-medium">
                        <span>Progress Level</span>
                        <span id="progress-level-percent">0%</span>
                    </div>
                    <div class="w-full bg-black/20 rounded-full h-2">
                        <div id="progress-level-bar" class="bg-yellow-400 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-gray-800 text-sm mb-4 flex items-center gap-2">
                <i class="bi bi-grid-fill text-indigo-500"></i> Menu Cepat
            </h3>
            <div class="grid grid-cols-4 gap-2 text-center">
                <button onclick="openWithdrawalModal()" class="group flex flex-col items-center gap-1.5 transition active:scale-95">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 text-blue-600 flex items-center justify-center text-xl shadow-sm border border-blue-200 group-hover:from-blue-600 group-hover:to-blue-700 group-hover:text-white transition-all duration-300"><i class="bi bi-box-arrow-up"></i></div>
                    <span class="text-[10px] font-semibold text-gray-600 group-hover:text-blue-600 transition-colors">Tarik</span>
                </button>
                <button onclick="openTransferModal()" class="group flex flex-col items-center gap-1.5 transition active:scale-95">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-cyan-50 to-cyan-100 text-cyan-600 flex items-center justify-center text-xl shadow-sm border border-cyan-200 group-hover:from-cyan-600 group-hover:to-cyan-700 group-hover:text-white transition-all duration-300"><i class="bi bi-send"></i></div>
                    <span class="text-[10px] font-semibold text-gray-600 group-hover:text-cyan-600 transition-colors">Transfer</span>
                </button>
                <button onclick="switchTab('belanja')" class="group flex flex-col items-center gap-1.5 transition active:scale-95">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-rose-50 to-rose-100 text-rose-600 flex items-center justify-center text-xl shadow-sm border border-rose-200 group-hover:from-rose-600 group-hover:to-rose-700 group-hover:text-white transition-all duration-300"><i class="bi bi-shop"></i></div>
                    <span class="text-[10px] font-semibold text-gray-600 group-hover:text-rose-600 transition-colors">Belanja</span>
                </button>
                <button onclick="openPaymentScanner()" class="group flex flex-col items-center gap-1.5 transition active:scale-95">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-50 to-purple-100 text-purple-600 flex items-center justify-center text-xl shadow-sm border border-purple-200 group-hover:from-purple-600 group-hover:to-purple-700 group-hover:text-white transition-all duration-300"><i class="bi bi-qr-code-scan"></i></div>
                    <span class="text-[10px] font-semibold text-gray-600 group-hover:text-purple-600 transition-colors">Bayar</span>
                </button>
            </div>
        </div>

        <!-- Financial Pulse Widget (New Idea) -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
            <div class="flex justify-between items-center mb-3 relative z-10">
                <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                    <i class="bi bi-activity text-rose-500"></i> Dompet Bulan Ini
                </h3>
                <span class="text-[10px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full"><?= date('F Y') ?></span>
            </div>
            <div class="grid grid-cols-2 gap-4 relative z-10">
                <div class="bg-green-50 p-3 rounded-xl border border-green-100">
                    <p class="text-[10px] text-green-600 font-medium mb-0.5 text-center">Ditabung</p>
                    <p class="text-sm font-bold text-green-700 text-center" id="pulse-saved">Rp 0</p>
                </div>
                <div class="bg-orange-50 p-3 rounded-xl border border-orange-100">
                    <p class="text-[10px] text-orange-600 font-medium mb-0.5 text-center">Dibelanjakan</p>
                    <p class="text-sm font-bold text-orange-700 text-center" id="pulse-spent">Rp 0</p>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div id="dashboard-notifications" class="space-y-3 hidden"></div>

        <!-- Asset Summary -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                    <i class="bi bi-pie-chart-fill text-green-600"></i> Ringkasan Aset
                </h3>
                <button onclick="switchTab('simpanan')" class="text-xs text-blue-600 font-medium">Detail</button>
            </div>
            <div class="grid grid-cols-2 gap-4 divide-x divide-gray-100">
                <div class="text-center cursor-pointer" onclick="switchTab('simpanan')">
                    <p class="text-xs text-gray-500 mb-1">Total Simpanan</p>
                    <p id="total-simpanan" class="text-lg font-bold text-blue-600">
                        <span class="h-5 w-24 bg-gray-200 rounded animate-pulse inline-block"></span>
                    </p>
                </div>
                <div class="text-center cursor-pointer" onclick="switchTab('pinjaman')">
                    <p class="text-xs text-gray-500 mb-1">Kewajiban Pinjaman</p>
                    <p id="sisa-pinjaman" class="text-lg font-bold text-red-500">
                        <span class="h-5 w-24 bg-gray-200 rounded animate-pulse inline-block"></span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Savings Growth Chart -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-gray-800 mb-3 text-sm flex items-center gap-2">
                <i class="bi bi-graph-up-arrow text-blue-600"></i> Pertumbuhan Simpanan
            </h3>
            <div class="h-40">
                <canvas id="savingsChart"></canvas>
            </div>
        </div> 

        <!-- News/Info -->
        <div id="news-container" class="hidden">
            <h3 class="font-bold text-gray-800 mb-3 text-sm flex items-center gap-2">
                <i class="bi bi-megaphone-fill text-orange-500"></i> Info Koperasi
            </h3>
            <div id="news-list" class="flex gap-3 overflow-x-auto hide-scrollbar pb-2"></div>
        </div>

        <!-- Financial Health (Compact) -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="relative w-16 h-16 shrink-0">
                <canvas id="healthScoreChart"></canvas>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span id="health-score-text" class="text-sm font-bold text-gray-800">0</span>
                </div>
            </div>
            <div>
                <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                    <i class="bi bi-heart-pulse-fill text-rose-500"></i> Kesehatan Finansial
                </h3>
                <p id="health-score-rating" class="text-xs text-gray-500 mb-1">Menganalisis...</p>
                <div id="smart-insights-list" class="text-[10px] text-gray-400 line-clamp-1">
                    Cek detail untuk saran keuangan.
                </div>
            </div>
        </div>

        <!-- Recent History -->
        <div>
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                    <i class="bi bi-clock-history text-blue-600"></i> Aktivitas Terakhir
                </h3>
                <button onclick="switchTab('simpanan')" class="text-xs text-blue-600 font-medium hover:underline">Lihat Semua</button>
            </div>
            <div id="recent-history" class="space-y-3">
                <!-- Skeleton -->
                <?php for($i=0; $i<2; $i++): ?>
                <div class="bg-white p-3 rounded-lg shadow-sm flex justify-between items-center border border-gray-100 animate-pulse">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gray-200"></div>
                        <div>
                            <div class="h-4 w-24 bg-gray-200 rounded mb-1.5"></div>
                            <div class="h-3 w-16 bg-gray-200 rounded"></div>
                        </div>
                    </div>
                    <div class="h-5 w-20 bg-gray-200 rounded"></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Info Level -->
<div id="modal-level-info" class="fixed inset-0 z-[90] hidden" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-level-info').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full relative">
            <div class="bg-white p-6">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-lg font-bold text-gray-900">Info Level & Poin</h3>
                    <button onclick="document.getElementById('modal-level-info').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-amber-50 rounded-xl border border-amber-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold shadow-sm"><i class="bi bi-award"></i></div>
                            <div>
                                <p class="font-bold text-gray-800 text-sm">Bronze</p>
                                <p class="text-[10px] text-gray-500">Level Awal</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-gray-600 bg-white px-2 py-1 rounded border border-gray-100">0 - 499</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold shadow-sm"><i class="bi bi-award-fill"></i></div>
                            <div>
                                <p class="font-bold text-gray-800 text-sm">Silver</p>
                                <p class="text-[10px] text-gray-500">Member Aktif</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-gray-600 bg-white px-2 py-1 rounded border border-gray-100">500 - 1499</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-xl border border-yellow-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center font-bold shadow-sm"><i class="bi bi-trophy-fill"></i></div>
                            <div>
                                <p class="font-bold text-gray-800 text-sm">Gold</p>
                                <p class="text-[10px] text-gray-500">Member Setia</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-gray-600 bg-white px-2 py-1 rounded border border-gray-100">1500 - 2999</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-xl border border-blue-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold shadow-sm"><i class="bi bi-gem"></i></div>
                            <div>
                                <p class="font-bold text-gray-800 text-sm">Platinum</p>
                                <p class="text-[10px] text-gray-500">Member VIP</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-gray-600 bg-white px-2 py-1 rounded border border-gray-100">3000+</span>
                    </div>
                </div>
                <p class="text-[10px] text-gray-400 mt-5 text-center leading-relaxed">Kumpulkan poin dengan menabung rutin, membayar angsuran tepat waktu, dan berbelanja di toko koperasi.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Scan & Bayar QR -->
<div id="modal-payment-scanner" class="fixed inset-0 z-[80] hidden" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-80 transition-opacity" aria-hidden="true" onclick="stopPaymentScanner()"></div>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full relative">
            
            <!-- Scanning View -->
            <div id="payment-scanner-view">
                <div class="bg-white p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Scan untuk Bayar</h3>
                        <button onclick="stopPaymentScanner()" class="text-gray-400 hover:text-gray-500 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>
                    <div id="payment-reader" class="w-full rounded-lg overflow-hidden bg-black aspect-square"></div>
                    <p class="text-center text-xs text-gray-500 mt-4">Arahkan kamera ke QR Code pembayaran.</p>
                </div>
            </div>

            <!-- Confirmation View -->
            <div id="payment-confirmation-view" class="hidden">
                 <div class="bg-white px-6 pt-6 pb-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-900">Konfirmasi Pembayaran</h3>
                        <button onclick="stopPaymentScanner()" class="text-gray-400 hover:text-gray-500">
                            <i class="bi bi-x-lg text-xl"></i>
                        </button>
                    </div>
                    <div class="text-center mb-6">
                        <p class="text-sm text-gray-500">Anda akan membayar kepada</p>
                        <p id="payment-merchant-name" class="text-xl font-bold text-gray-800 mb-2">Nama Merchant</p>
                        <p class="text-4xl font-bold text-blue-600" id="payment-amount">Rp 0</p>
                    </div>
                    <form id="form-confirm-payment" class="space-y-4">
                        <input type="hidden" id="payment-data-input" name="qr_data">
                        <input type="text" name="username" autocomplete="username" class="hidden" value="<?= $_SESSION['member_no'] ?? '' ?>">
                        <div class="p-4 bg-yellow-50 border border-yellow-100 rounded-xl">
                            <div class="flex gap-3 mb-2">
                                <i class="bi bi-shield-lock text-yellow-600 text-xl"></i>
                                <div>
                                    <label class="block text-xs font-bold text-yellow-800 uppercase tracking-wide mb-1">Konfirmasi Password</label>
                                    <p class="text-[10px] text-yellow-700 mb-2">Pembayaran dipotong dari Simpanan Sukarela.</p>
                                </div>
                            </div>
                            <input type="password" id="payment-password" name="password" autocomplete="current-password" class="w-full px-4 py-2.5 border border-yellow-200 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 text-sm bg-white" placeholder="Masukkan password Anda" required>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white py-3.5 rounded-xl font-bold shadow-lg shadow-blue-200 hover:shadow-blue-300 active:scale-[0.98] transition-all text-sm mt-2">Konfirmasi & Bayar</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>