<?php
// Cek sesi anggota manual karena ini di luar sistem auth utama admin
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['member_loggedin']) || $_SESSION['member_loggedin'] !== true) {
    header('Location: ' . BASE_PATH . '/member/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Anggota</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    <script>
        window.OneSignalDeferred = window.OneSignalDeferred || [];
        OneSignalDeferred.push(async function(OneSignal) {
            await OneSignal.init({
            appId: "67c02e7d-b082-4b21-98ee-713d53d2f7fa",
            safari_web_id: "web.onesignal.auto.12f40fc9-13d7-4ca9-8e4a-0a7d50f473bf",
            notifyButton: {
                enable: true,
            },
            });
        });
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f0f2f5; padding-bottom: 80px; } /* Padding for bottom nav */
        .mobile-container { max-width: 600px; margin: 0 auto; min-height: 100vh; background: #f0f2f5; position: relative; }
        .card { background: white; border-radius: 0.75rem; padding: 1rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05); margin-bottom: 1rem; }
        .nav-item { color: #9ca3af; transition: color 0.2s; }
        .nav-item.active { color: #1d4ed8; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .header-gradient { background-image: linear-gradient(to bottom right, #1d4ed8, #3b82f6); }
    </style>
</head>
<body>
    <div class="mobile-container">
        <!-- Header -->
        <div class="header-gradient text-white p-5 rounded-b-3xl shadow-lg relative">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <p class="text-blue-200 text-xs">Selamat Datang,</p>
                    <h2 class="text-lg font-bold" id="member-name">Loading...</h2>
                </div>
                <a href="<?= BASE_PATH ?>/member/logout" class="bg-white/20 p-2 rounded-full hover:bg-white/30 transition">
                    <i class="bi bi-box-arrow-right text-lg"></i>
                </a>
            </div>
            
            <!-- Saldo Card -->
            <div class="bg-white text-gray-800 rounded-xl p-4 shadow-md -mb-16">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-xs mb-1">Total Simpanan</p>
                        <h1 class="text-2xl font-bold text-blue-700" id="total-simpanan">Rp 0</h1>
                    </div>
                    <i class="bi bi-piggy-bank-fill text-3xl text-blue-200"></i>
                </div>
                <div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t border-gray-100">
                    <div class="text-center">
                        <p class="text-gray-500 text-[10px] font-medium">POKOK</p>
                        <p class="font-semibold text-xs text-gray-700" id="simpanan-pokok">Rp 0</p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-500 text-[10px] font-medium">WAJIB</p>
                        <p class="font-semibold text-xs text-gray-700" id="simpanan-wajib">Rp 0</p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-500 text-[10px] font-medium">SUKARELA</p>
                        <p class="font-semibold text-xs text-gray-700" id="simpanan-sukarela">Rp 0</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="px-4 pt-20" id="main-content">
            <!-- Home Tab Content -->
            <div id="tab-home" class="tab-content">
                <!-- Notifications Area -->
                <div id="dashboard-notifications" class="mb-4 space-y-2 hidden"></div>

                <!-- Quick Info Cards -->
                <div class="card">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                                <i class="bi bi-cash-coin text-xl"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Sisa Pinjaman Aktif</p>
                                <p class="font-bold text-gray-800" id="sisa-pinjaman">Rp 0</p>
                            </div>
                        </div>
                        <button onclick="switchTab('pinjaman')" class="text-blue-600 text-2xl"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>

                <!-- Savings Growth Chart -->
                <div class="card mt-4">
                    <h4 class="font-bold text-gray-700 mb-3 text-sm">Pertumbuhan Simpanan (6 Bulan)</h4>
                    <div class="relative h-48 w-full">
                        <canvas id="savingsChart"></canvas>
                    </div>
                </div>

                <h3 class="font-bold text-gray-700 mb-3 mt-6 flex items-center gap-2 text-sm">
                    <i class="bi bi-list-ul text-gray-500"></i> Riwayat Transaksi Terakhir
                </h3>
                <div class="space-y-3" id="recent-history">
                    <!-- History items will be loaded here -->
                    <div class="text-center py-6 text-gray-400 text-xs">Memuat data...</div>
                </div>
            </div>

            <!-- Simpanan Tab Content -->
            <div id="tab-simpanan" class="tab-content hidden">
                <h3 class="font-bold text-gray-800 mb-4 text-lg">Saldo Simpanan</h3>
                <div class="space-y-4" id="simpanan-summary-list"></div>
            </div>

            <!-- Pinjaman Tab Content -->
            <div id="tab-pinjaman" class="tab-content hidden">
                <h3 class="font-bold text-gray-800 mb-4 text-lg">Daftar Pinjaman</h3>
                <div class="space-y-4" id="pinjaman-list"></div>
            </div>

            <!-- Profile Tab Content -->
            <div id="tab-profile" class="tab-content hidden">
                <h3 class="font-bold text-gray-800 mb-4 text-lg">Profil Saya</h3>
                <div class="card">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center">
                            <i class="bi bi-person-fill text-4xl text-gray-500"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800" id="profile-name-display">...</h4>
                            <p class="text-sm text-gray-500" id="profile-no-display">...</p>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <h4 class="font-semibold text-gray-700 mb-3 text-sm">Ganti Password</h4>
                    <form id="change-password-form" class="space-y-3">
                        <input type="text" name="username" autocomplete="username" class="hidden" value="<?= $_SESSION['member_no'] ?? '' ?>">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Password Saat Ini</label>
                            <input type="password" name="current_password" autocomplete="current-password" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Password Baru</label>
                            <input type="password" name="new_password" autocomplete="new-password" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" required minlength="6">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" autocomplete="new-password" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-semibold hover:bg-blue-700 transition text-sm">Simpan Password</button>
                    </form>
                    <div id="password-alert" class="mt-3 hidden p-3 rounded-lg text-sm text-center"></div>
                </div>
            </div>

            <!-- Simulasi Tab Content -->
            <div id="tab-simulasi" class="tab-content hidden">
                <h3 class="font-bold text-gray-800 mb-4 text-lg">Simulasi Pinjaman</h3>
                <div class="card">
                    <form id="simulasi-form" class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Jenis Pinjaman</label>
                            <select id="simulasi_jenis" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm">
                                <option value="">Memuat...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Jumlah Pinjaman (Rp)</label>
                            <input type="number" id="simulasi_jumlah" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="Contoh: 5000000">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Tenor (Bulan)</label>
                            <input type="number" id="simulasi_tenor" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="Contoh: 12">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-semibold hover:bg-blue-700 transition text-sm">Hitung Angsuran</button>
                    </form>

                    <div id="hasil-simulasi" class="mt-6 hidden border-t border-gray-200 pt-4">
                        <h4 class="font-semibold text-gray-800 mb-3 text-sm">Estimasi Angsuran</h4>
                        <div class="bg-blue-50 p-3 rounded-lg space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Pokok:</span>
                                <span class="font-medium" id="est-pokok">Rp 0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Bunga:</span>
                                <span class="font-medium" id="est-bunga">Rp 0</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-blue-200">
                                <span class="font-bold text-blue-800">Total per Bulan:</span>
                                <span class="font-bold text-blue-800" id="est-total">Rp 0</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2 italic">* Perhitungan ini hanya estimasi. Nilai sebenarnya mungkin berbeda saat pengajuan.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Navigation -->
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-2 flex justify-around items-center max-w-[600px] mx-auto z-50 shadow-[0_-1px_4px_rgba(0,0,0,0.05)]">
            <button onclick="switchTab('home')" class="nav-item active flex flex-col items-center gap-1" id="nav-home">
                <i class="bi bi-house-door-fill text-xl"></i>
                <span class="text-[10px] font-medium">Beranda</span>
            </button>
            <button onclick="switchTab('simpanan')" class="nav-item flex flex-col items-center gap-1" id="nav-simpanan">
                <i class="bi bi-piggy-bank-fill text-xl"></i>
                <span class="text-[10px] font-medium">Simpanan</span>
            </button>
            <button onclick="switchTab('pinjaman')" class="nav-item flex flex-col items-center gap-1" id="nav-pinjaman">
                <i class="bi bi-wallet2 text-xl"></i>
                <span class="text-[10px] font-medium">Pinjaman</span>
            </button>
            <button onclick="switchTab('simulasi')" class="nav-item flex flex-col items-center gap-1" id="nav-simulasi">
                <i class="bi bi-calculator text-xl"></i>
                <span class="text-[10px] font-medium">Simulasi</span>
            </button>
            <button onclick="switchTab('profile')" class="nav-item flex flex-col items-center gap-1" id="nav-profile">
                <i class="bi bi-person-circle text-xl"></i>
                <span class="text-[10px] font-medium">Profil</span>
            </button>
        </div>

        <!-- Modal Detail Simpanan -->
        <div id="modal-detail-simpanan" class="fixed inset-0 z-[60] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-detail-simpanan').classList.add('hidden')"></div>
                <div class="inline-block align-bottom bg-white rounded-t-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:rounded-2xl">
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
    </div>

    <script>
        window.memberDashboardData = {}; // Global store for dashboard data
        const basePath = '<?= BASE_PATH ?>';

        function formatRupiah(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
        }

        function formatDate(dateString) {
            const options = { day: 'numeric', month: 'short', year: 'numeric' };
            return new Date(dateString).toLocaleDateString('id-ID', options);
        }

        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.remove('hidden');
            
            // Update nav styles
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            document.getElementById('nav-' + tabName).classList.add('active');

            // Load data based on tab
            if(tabName === 'simpanan') loadSimpananHistory();
            if(tabName === 'pinjaman') loadPinjamanList();
            if(tabName === 'simulasi') loadLoanTypes();
        }

        async function loadSummary() {
            try {
                const res = await fetch(`${basePath}/api/member/dashboard?action=summary`);
                const json = await res.json();
                window.memberDashboardData = json.data; // Store for other functions

                if(json.success) {
                    document.getElementById('member-name').textContent = json.data.nama;
                    document.getElementById('profile-name-display').textContent = json.data.nama;
                    document.getElementById('profile-no-display').textContent = '<?= $_SESSION['member_no'] ?>'.replace(/(.{4})/g, '$1 ').trim();

                    const simpananData = json.data.simpanan_per_jenis || [];
                    let totalSimpanan = 0;
                    simpananData.forEach(s => {
                        totalSimpanan += parseFloat(s.saldo);
                        const el = document.getElementById(`simpanan-${s.tipe}`);
                        if (el) el.textContent = formatRupiah(s.saldo);
                    });
                    document.getElementById('total-simpanan').textContent = formatRupiah(totalSimpanan);

                    document.getElementById('sisa-pinjaman').textContent = formatRupiah(json.data.pinjaman);

                    // Handle Notifications
                    const notifContainer = document.getElementById('dashboard-notifications');
                    if (json.data.upcoming_payments && json.data.upcoming_payments.length > 0) {
                        notifContainer.classList.remove('hidden');
                        notifContainer.innerHTML = json.data.upcoming_payments.map(p => {
                            const dueDate = new Date(p.tanggal_jatuh_tempo);
                            const today = new Date();
                            today.setHours(0,0,0,0);
                            dueDate.setHours(0,0,0,0);
                            
                            const diffTime = dueDate - today;
                            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                            
                            let msg = '';
                            let bgClass = 'bg-yellow-50 border-yellow-200 text-yellow-800';
                            let icon = 'bi-exclamation-circle';

                            if (diffDays < 0) {
                                msg = `Telat ${Math.abs(diffDays)} hari`;
                                bgClass = 'bg-red-50 border-red-200 text-red-800';
                                icon = 'bi-exclamation-triangle-fill';
                            } else if (diffDays === 0) {
                                msg = 'Jatuh tempo HARI INI';
                                bgClass = 'bg-red-50 border-red-200 text-red-800';
                            } else {
                                msg = `Jatuh tempo dalam ${diffDays} hari`;
                            }

                            return `
                                <div class="${bgClass} border p-3 rounded-lg flex items-start gap-3 shadow-sm">
                                    <i class="bi ${icon} text-xl mt-0.5"></i>
                                    <div class="flex-1">
                                        <p class="font-bold text-sm">${msg}</p>
                                        <p class="text-xs mt-1">
                                            Angsuran Ke-${p.angsuran_ke} (${p.nomor_pinjaman})<br>
                                            Tagihan: <strong>${formatRupiah(p.sisa_tagihan)}</strong>
                                        </p>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    } else {
                        notifContainer.classList.add('hidden');
                        notifContainer.innerHTML = '';
                    }
                }
            } catch(e) { console.error(e); }
        }

        async function loadRecentHistory() {
            try {
                const res = await fetch(`${basePath}/api/member/dashboard?action=get_all_savings_history`);
                const json = await res.json();
                const container = document.getElementById('recent-history');
                
                if(json.success && json.data.length > 0) {
                    container.innerHTML = json.data.slice(0, 3).map(item => `
                        <div class="bg-white p-3 rounded-lg shadow-sm flex justify-between items-center border border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full ${item.jenis_transaksi === 'setor' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'} flex items-center justify-center">
                                    <i class="bi ${item.jenis_transaksi === 'setor' ? 'bi-arrow-down' : 'bi-arrow-up'} text-lg"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800 text-sm">${item.jenis_simpanan}</p>
                                    <p class="text-xs text-gray-500">${formatDate(item.tanggal)}</p>
                                </div>
                            </div>
                            <span class="font-bold text-sm ${item.jenis_transaksi === 'setor' ? 'text-green-600' : 'text-red-600'}">
                                ${item.jenis_transaksi === 'setor' ? '+' : '-'} ${formatRupiah(item.jumlah)}
                            </span>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<p class="text-center text-gray-400 text-xs py-4">Belum ada riwayat transaksi.</p>';
                }
            } catch(e) { console.error(e); }
        }

        async function loadSimpananHistory() {
            const container = document.getElementById('simpanan-summary-list');
            container.innerHTML = '<div class="text-center py-4 text-gray-400 text-sm">Memuat...</div>';
            
            try {
                // Use data from global store
                if (window.memberDashboardData && window.memberDashboardData.simpanan_per_jenis) {
                    const data = window.memberDashboardData.simpanan_per_jenis;
                    if (data.length > 0) {
                        container.innerHTML = data.map(s => `
                            <div class="card cursor-pointer hover:bg-gray-50" onclick="showSavingsDetail(${s.id}, '${s.nama}')">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">${s.nama}</p>
                                        <p class="text-xs text-gray-500 capitalize">${s.tipe}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-lg text-blue-600">${formatRupiah(s.saldo)}</p>
                                        <span class="text-xs text-blue-500">Lihat Detail <i class="bi bi-chevron-right"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                    } else {
                        container.innerHTML = '<p class="text-center text-gray-400 py-8">Belum ada data simpanan.</p>';
                    }
                } else {
                    container.innerHTML = '<p class="text-center text-gray-400 py-8">Gagal memuat data.</p>';
                }
            } catch(e) { console.error(e); }
        }

        async function loadPinjamanList() {
            const container = document.getElementById('pinjaman-list');
            container.innerHTML = '<div class="text-center py-4"><span class="animate-spin inline-block w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full"></span></div>';

            try {
                const res = await fetch(`${basePath}/api/member/dashboard?action=list_pinjaman`);
                const json = await res.json();

                if(json.success && json.data.length > 0) {
                    container.innerHTML = json.data.map(p => `
                        <div class="card cursor-pointer" onclick="showLoanDetail(${p.id})">
                            <div class="absolute top-0 right-0 px-3 py-1 bg-${p.status === 'aktif' ? 'blue' : (p.status === 'lunas' ? 'green' : 'yellow')}-100 text-${p.status === 'aktif' ? 'blue' : (p.status === 'lunas' ? 'green' : 'yellow')}-700 text-xs font-bold rounded-bl-xl">
                                ${p.status.toUpperCase()}
                            </div>
                            <p class="text-xs text-gray-500 mb-1">No. ${p.nomor_pinjaman}</p>
                            <h4 class="font-bold text-gray-800 text-lg mb-2">${formatRupiah(p.jumlah_pinjaman)}</h4>
                            <div class="flex justify-between text-sm text-gray-600 border-t border-gray-100 pt-3 mt-2">
                                <span>Sisa Tagihan:</span>
                                <span class="font-semibold text-red-600">${formatRupiah(p.sisa_pokok)}</span>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<p class="text-center text-gray-400 py-8">Tidak ada data pinjaman.</p>';
                }
            } catch(e) { console.error(e); }
        }

        async function loadSavingsChart() {
            try {
                const res = await fetch(`${basePath}/api/member/dashboard?action=savings_growth`);
                const json = await res.json();
                
                if(json.success) {
                    const ctx = document.getElementById('savingsChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: json.labels,
                            datasets: [{
                                label: 'Total Simpanan',
                                data: json.data,
                                borderColor: '#3b82f6', // blue-500
                                backgroundColor: (context) => {
                                    const ctx = context.chart.ctx;
                                    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
                                    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
                                    gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');
                                    return gradient;
                                },
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 3,
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: '#3b82f6',
                                pointBorderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return formatRupiah(context.parsed.y);
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: false,
                                    ticks: {
                                        callback: function(value) {
                                            if (value >= 1000000) return (value / 1000000).toFixed(1) + 'jt';
                                            if (value >= 1000) return (value / 1000).toFixed(0) + 'rb';
                                            return value;
                                        },
                                        font: { size: 10 },
                                        color: '#9ca3af'
                                    },
                                    grid: { borderDash: [2, 4], color: '#f3f4f6' },
                                    border: { display: false }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 10 }, color: '#9ca3af' },
                                    border: { display: false }
                                }
                            }
                        }
                    });
                }
            } catch(e) { console.error(e); }
        }

        async function showLoanDetail(id) {
            const modal = document.getElementById('modal-detail-pinjaman');
            const content = document.getElementById('detail-pinjaman-content');
            
            modal.classList.remove('hidden');
            content.innerHTML = '<div class="text-center py-8"><span class="animate-spin inline-block w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full"></span></div>';

            try {
                const res = await fetch(`${basePath}/api/member/dashboard?action=get_loan_detail&id=${id}`);
                const json = await res.json();

                if (json.success) {
                    const p = json.data;
                    const schedule = json.schedule;
                    
                    let html = `
                        <div class="mb-4 text-sm text-gray-600">
                            <p><strong>No. Pinjaman:</strong> ${p.nomor_pinjaman}</p>
                            <p><strong>Jumlah Pokok:</strong> ${formatRupiah(p.jumlah_pinjaman)}</p>
                            <p><strong>Tenor:</strong> ${p.tenor_bulan} Bulan</p>
                            <p><strong>Status:</strong> <span class="capitalize font-semibold">${p.status}</span></p>
                        </div>
                        <h4 class="font-bold text-gray-800 mb-2 text-sm">Jadwal Pembayaran</h4>
                        <div class="space-y-2 max-h-[60vh] overflow-y-auto pr-1">
                    `;

                    html += schedule.map(s => {
                        const totalPaid = parseFloat(s.pokok_terbayar || 0) + parseFloat(s.bunga_terbayar || 0) + parseFloat(s.denda || 0);
                        let paymentInfo = '';
                        
                        if (totalPaid > 0) {
                            paymentInfo = `
                                <div class="mt-2 pt-2 border-t border-gray-200 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Dibayar:</span>
                                        <span class="font-medium text-green-600">${formatRupiah(totalPaid)}</span>
                                    </div>
                                    ${s.tanggal_bayar ? `<div class="flex justify-between mt-1"><span class="text-gray-500">Tanggal:</span> <span class="text-gray-700">${formatDate(s.tanggal_bayar)}</span></div>` : ''}
                                    ${parseFloat(s.denda) > 0 ? `<div class="flex justify-between mt-1 text-red-500"><span>Denda:</span> <span>${formatRupiah(s.denda)}</span></div>` : ''}
                                </div>
                            `;
                        }

                        return `
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-sm">
                            <div class="flex justify-between mb-1">
                                <span class="font-semibold text-gray-700">Angsuran Ke-${s.angsuran_ke}</span>
                                <span class="text-xs ${s.status === 'lunas' ? 'text-green-600 font-bold' : 'text-gray-500'}">${s.status === 'lunas' ? 'LUNAS' : formatDate(s.tanggal_jatuh_tempo)}</span>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>Tagihan: ${formatRupiah(s.total_angsuran)}</span>
                                ${s.status === 'lunas' ? `<span class="text-green-600"><i class="bi bi-check-circle-fill"></i></span>` : ''}
                            </div>
                            ${paymentInfo}
                        </div>
                    `}).join('');

                    html += '</div>';
                    content.innerHTML = html;
                } else {
                    content.innerHTML = '<p class="text-center text-red-500">Gagal memuat data.</p>';
                }
            } catch (e) { console.error(e); content.innerHTML = '<p class="text-center text-red-500">Terjadi kesalahan.</p>'; }
        }

        async function showSavingsDetail(id, name) {
            const modal = document.getElementById('modal-detail-simpanan');
            const content = document.getElementById('detail-simpanan-content');
            const title = document.getElementById('simpanan-detail-title');
            
            modal.classList.remove('hidden');
            title.textContent = `Riwayat ${name}`;
            content.innerHTML = '<div class="text-center py-8"><span class="animate-spin inline-block w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full"></span></div>';

            try {
                const res = await fetch(`${basePath}/api/member/dashboard?action=get_savings_history_by_type&id=${id}`);
                const json = await res.json();

                if (json.success && json.data.length > 0) {
                    let html = '<div class="space-y-2 max-h-[60vh] overflow-y-auto pr-1">';
                    html += json.data.map(tx => {
                        const isSetor = tx.jenis_transaksi === 'setor';
                        return `
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-sm">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-medium text-gray-700">${formatDate(tx.tanggal)}</span>
                                    <span class="font-bold ${isSetor ? 'text-green-600' : 'text-red-600'}">
                                        ${isSetor ? '+' : '-'} ${formatRupiah(tx.jumlah)}
                                    </span>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span class="truncate pr-2">${tx.keterangan || 'Transaksi'}</span>
                                    <span>Saldo: ${formatRupiah(tx.saldo)}</span>
                                </div>
                            </div>
                        `;
                    }).join('');
                    html += '</div>';
                    content.innerHTML = html;
                } else {
                    content.innerHTML = '<p class="text-center text-gray-500 py-4">Tidak ada riwayat transaksi untuk simpanan ini.</p>';
                }
            } catch (e) { console.error(e); content.innerHTML = '<p class="text-center text-red-500">Terjadi kesalahan.</p>'; }
        }

        let loanTypes = [];
        async function loadLoanTypes() {
            if (loanTypes.length > 0) return; // Load only once
            try {
                const res = await fetch(`${basePath}/api/member/dashboard?action=get_loan_types`);
                const json = await res.json();
                if (json.success) {
                    loanTypes = json.data;
                    const select = document.getElementById('simulasi_jenis');
                    select.innerHTML = loanTypes.map(t => `<option value="${t.id}" data-bunga="${t.bunga_per_tahun}">${t.nama} (${t.bunga_per_tahun}% p.a)</option>`).join('');
                }
            } catch (e) { console.error(e); }
        }

        document.getElementById('simulasi-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const jenisSelect = document.getElementById('simulasi_jenis');
            const jumlah = parseFloat(document.getElementById('simulasi_jumlah').value);
            const tenor = parseInt(document.getElementById('simulasi_tenor').value);
            const bungaPersen = parseFloat(jenisSelect.options[jenisSelect.selectedIndex].dataset.bunga);

            if (!jumlah || !tenor || isNaN(bungaPersen)) {
                alert('Mohon lengkapi semua data.');
                return;
            }

            // Perhitungan Bunga Flat
            const pokokBulanan = jumlah / tenor;
            const bungaBulanan = (jumlah * (bungaPersen / 100)) / 12;
            const totalBulanan = pokokBulanan + bungaBulanan;

            document.getElementById('est-pokok').textContent = formatRupiah(pokokBulanan);
            document.getElementById('est-bunga').textContent = formatRupiah(bungaBulanan);
            document.getElementById('est-total').textContent = formatRupiah(totalBulanan);
            
            document.getElementById('hasil-simulasi').classList.remove('hidden');
        });

        document.getElementById('change-password-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const alertBox = document.getElementById('password-alert');
            
            btn.disabled = true;
            btn.innerHTML = 'Menyimpan...';
            alertBox.classList.add('hidden');

            try {
                const formData = new FormData(this);
                const response = await fetch(`${basePath}/api/member/profile`, { method: 'POST', body: formData });
                const result = await response.json();

                alertBox.className = `mt-3 p-3 rounded-lg text-sm text-center block ${result.success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
                alertBox.textContent = result.message;
                if(result.success) this.reset();
            } catch (error) {
                alertBox.className = 'mt-3 p-3 rounded-lg text-sm text-center bg-red-100 text-red-700 block';
                alertBox.textContent = 'Terjadi kesalahan jaringan.';
            }
            btn.disabled = false;
            btn.innerHTML = 'Simpan Password';
        });

        // Init
        loadSummary();
        loadRecentHistory();
        loadSavingsChart();
    </script>
</body>
</html>