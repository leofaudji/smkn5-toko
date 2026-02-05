<?php
// Cek sesi anggota manual karena ini di luar sistem auth utama admin
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['member_loggedin']) || $_SESSION['member_loggedin'] !== true) {
    header('Location: ' . BASE_PATH . '/member/login');
    exit;
}

// Ambil logo dari pengaturan aplikasi
$app_logo_path = get_setting('app_logo');
$logo_src = !empty($app_logo_path) ? BASE_PATH . '/' . $app_logo_path : BASE_PATH . '/assets/img/logo.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#b4c7bb"> 
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="<?= BASE_PATH ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?= BASE_PATH ?>/assets/img/logo.png">
    <title>Dashboard Anggota</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');
        
        body { 
            background-color: #f1f5f9; 
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding-bottom: 90px;
            -webkit-tap-highlight-color: transparent;
        }
        
        .mobile-container { 
            max-width: 480px; /* Lebih ramping agar pas seperti HP di desktop */
            margin: 0 auto; 
            min-height: 100vh; 
            background: #f8fafc; 
            position: relative; 
            box-shadow: 0 0 40px rgba(0,0,0,0.05);
        }

        /* Modern Wallet Card */
        .wallet-card {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            border-radius: 1.5rem;
            padding: 1.5rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 30px -5px rgba(37, 99, 235, 0.4);
            margin-bottom: 1.5rem;
        }
        
        .wallet-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .card { 
            background: white; 
            border-radius: 1rem; 
            padding: 1.25rem; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); 
            border: 1px solid #f1f5f9;
            margin-bottom: 1rem; 
        }

        .nav-item { 
            color: #94a3b8; 
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-item.active { 
            color: #2563eb; 
            font-weight: 600;
        }

        .nav-item.active::after {
            content: '';
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            background: #2563eb;
            border-radius: 50%;
        }

        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Pull to Refresh Styles */
        #ptr-indicator {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 40;
            transform: translateY(-100%); /* Sembunyikan di atas layar */
        }
        .ptr-rotate { transform: rotate(180deg); transition: transform 0.3s; }

        /* Shimmer Animation */
        @keyframes shimmer {
            0% { transform: translateX(-150%) skewX(-12deg); }
            100% { transform: translateX(400%) skewX(-12deg); }
        }
        .animate-shimmer {
            animation: shimmer 3s infinite;
        }

        /* Tab Transition Animation */
        .tab-content {
            animation: tabSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes tabSlideUp {
            0% { opacity: 0; transform: translateY(20px) scale(0.98); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Splash Screen Animation */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Splash Screen -->
    <div id="splash-screen" class="fixed inset-0 z-[100] bg-[#b4c7bb] flex flex-col items-center justify-center transition-opacity duration-700">
        <div class="mb-6 animate-float">
            <img src="<?= $logo_src ?>" class="w-24 h-24 object-contain drop-shadow-lg" alt="Logo" onerror="this.style.display='none'">
        </div>
        <h1 class="text-white text-2xl font-bold tracking-wide">Koperasi</h1>
        <p class="text-white/90 text-sm mt-1 font-medium">SMK Negeri 5</p>
        <div class="mt-12">
            <div class="w-8 h-8 border-4 border-white/20 border-t-white rounded-full animate-spin"></div>
        </div>
    </div>

    <div class="mobile-container">
        <!-- Pull to Refresh Indicator -->
        <div id="ptr-indicator">
            <div class="bg-white p-2 rounded-full shadow-md border border-gray-100 text-blue-600 flex items-center justify-center w-10 h-10">
                <i id="ptr-icon" class="bi bi-arrow-down text-xl transition-transform"></i>
                <div id="ptr-spinner" class="hidden animate-spin w-5 h-5 border-2 border-blue-600 border-t-transparent rounded-full"></div>
            </div>
        </div>

        <div id="main-content" class="transition-transform duration-200 ease-out will-change-transform">
            <?php include __DIR__ . '/tabs/home.php'; ?>
            <?php include __DIR__ . '/tabs/belanja.php'; ?>
            <?php include __DIR__ . '/tabs/simpanan.php'; ?>
            <?php include __DIR__ . '/tabs/pinjaman.php'; ?>
            <?php include __DIR__ . '/tabs/profile.php'; ?>
            <?php include __DIR__ . '/tabs/simulasi.php'; ?>
        </div>

        <!-- Bottom Navigation -->
        <div class="fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-md border-t border-gray-200 px-6 py-3 flex justify-between items-center max-w-[480px] mx-auto z-50">
            <button onclick="switchTab('home')" class="nav-item active flex flex-col items-center gap-1" id="nav-home">
                <i class="bi bi-house-door-fill text-xl mb-0.5"></i>
                <span class="text-[10px] font-medium">Home</span>
            </button>
            <button onclick="switchTab('simpanan')" class="nav-item flex flex-col items-center gap-1" id="nav-simpanan">
                <i class="bi bi-wallet2 text-xl mb-0.5"></i>
                <span class="text-[10px] font-medium">Simpanan</span>
            </button>
            <button onclick="switchTab('pinjaman')" class="nav-item flex flex-col items-center gap-1" id="nav-pinjaman">
                <i class="bi bi-cash-stack text-xl mb-0.5"></i>
                <span class="text-[10px] font-medium">Pinjaman</span>
            </button>
            <button onclick="switchTab('simulasi')" class="nav-item flex flex-col items-center gap-1" id="nav-simulasi">
                <i class="bi bi-calculator text-xl mb-0.5"></i>
                <span class="text-[10px] font-medium">Simulasi</span>
            </button>
            <button onclick="switchTab('profile')" class="nav-item flex flex-col items-center gap-1" id="nav-profile">
                <i class="bi bi-person-fill text-xl mb-0.5"></i>
                <span class="text-[10px] font-medium">Profil</span>
            </button>
        </div>

        
    </div>

    <!-- Floating Cart Button -->
    <button id="cart-fab" onclick="openCartModal()" class="fixed bottom-24 right-5 z-40 w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg flex items-center justify-center transition-transform transform hover:scale-110 hidden">
        <i class="bi bi-cart-fill text-2xl"></i>
        <span id="cart-count-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center border-2 border-white">0</span>
    </button>

    <!-- PWA Install Banner -->
    <div id="install-banner" class="fixed bottom-20 left-4 right-4 z-[60] hidden transform transition-all duration-500 translate-y-20 opacity-0 max-w-[450px] mx-auto">
        <div class="bg-white/90 backdrop-blur-md p-4 rounded-2xl shadow-2xl border border-white/20 flex items-center gap-4">
            <div class="bg-blue-600 w-12 h-12 rounded-xl flex items-center justify-center shadow-lg shadow-blue-200 shrink-0">
                <img src="<?= $logo_src ?>" class="w-8 h-8 object-contain" alt="Logo" onerror="this.style.display='none'">
            </div>
            <div class="flex-1 min-w-0">
                <h4 class="font-bold text-gray-900 text-sm truncate">Install Aplikasi</h4>
                <p class="text-xs text-gray-500 truncate">Akses lebih cepat & offline</p>
            </div>
            <div class="flex items-center gap-2">
                <button id="btn-install" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-lg shadow-blue-200 active:scale-95 transition">
                    Install
                </button>
                <button id="btn-close-install" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 bg-gray-50 rounded-full">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        window.memberDashboardData = {}; // Global store for dashboard data
        const basePath = '<?= BASE_PATH ?>';
    </script>
    <script src="<?= BASE_PATH ?>/assets/js/ksp/member/core.js"></script>
    <script src="<?= BASE_PATH ?>/assets/js/ksp/member/belanja.js"></script>
    <script src="<?= BASE_PATH ?>/assets/js/ksp/member/simpanan.js"></script>
    <script src="<?= BASE_PATH ?>/assets/js/ksp/member/pinjaman.js"></script>
    <script src="<?= BASE_PATH ?>/assets/js/ksp/member/home.js"></script>
    <script src="<?= BASE_PATH ?>/assets/js/ksp/member/simulasi.js"></script>
    <script src="<?= BASE_PATH ?>/assets/js/ksp/member/profile.js"></script>
    <script>
        // Init
        document.addEventListener('DOMContentLoaded', () => {
            // Handle Splash Screen
            const splash = document.getElementById('splash-screen');
            if (splash) {
                // Hilangkan splash screen setelah halaman dimuat (min 2 detik agar terlihat)
                setTimeout(() => {
                    splash.style.opacity = '0';
                    setTimeout(() => {
                        splash.remove();
                    }, 700); // Sesuaikan dengan duration transition CSS
                }, 2000);
            }

            loadSummary();
            loadRecentHistory();
            loadSavingsChart();
            initPullToRefresh();

            // Fix: Pindahkan semua modal ke body agar tidak terpengaruh oleh transform pada #main-content
            // Ini memperbaiki masalah modal yang muncul di atas saat halaman di-scroll
            document.querySelectorAll('[role="dialog"]').forEach(modal => {
                document.body.appendChild(modal);
            });
        });

        function initPullToRefresh() {
            let touchStartY = 0;
            let isPulling = false;
            let pullDistance = 0;
            const mainContent = document.getElementById('main-content');
            const ptrIndicator = document.getElementById('ptr-indicator');
            const ptrIcon = document.getElementById('ptr-icon');
            const ptrSpinner = document.getElementById('ptr-spinner');
            const threshold = 70; // Jarak tarik minimum untuk trigger refresh

            document.addEventListener('touchstart', (e) => {
                // Hanya aktifkan jika scroll berada di paling atas
                if (window.scrollY === 0) {
                    touchStartY = e.touches[0].clientY;
                    isPulling = true;
                    pullDistance = 0;
                    // Matikan transisi saat menarik agar responsif
                    mainContent.style.transition = 'none'; 
                    ptrIndicator.style.transition = 'none';
                }
            }, { passive: true });

            document.addEventListener('touchmove', (e) => {
                if (!isPulling) return;
                const currentY = e.touches[0].clientY;
                const diff = currentY - touchStartY;

                // Jika menarik ke bawah dan scroll di atas
                if (diff > 0 && window.scrollY === 0) {
                    if (e.cancelable) e.preventDefault(); // Mencegah scroll bawaan browser
                    
                    // Efek resistance (semakin ditarik semakin berat)
                    pullDistance = Math.pow(diff, 0.8); 
                    if(pullDistance > 150) pullDistance = 150; // Batas maksimal tarikan

                    mainContent.style.transform = `translateY(${pullDistance}px)`;
                    ptrIndicator.style.transform = `translateY(${pullDistance - 60}px)`; // -60 karena posisi awal di -60px
                    
                    // Putar ikon panah jika sudah melewati threshold
                    if (pullDistance > threshold) ptrIcon.classList.add('ptr-rotate');
                    else ptrIcon.classList.remove('ptr-rotate');
                } else {
                    isPulling = false;
                    mainContent.style.transform = '';
                    ptrIndicator.style.transform = '';
                }
            }, { passive: false });

            document.addEventListener('touchend', async () => {
                if (!isPulling) return;
                isPulling = false;
                
                // Hidupkan kembali transisi untuk efek snap back yang halus
                mainContent.style.transition = 'transform 0.3s ease-out';
                ptrIndicator.style.transition = 'transform 0.3s ease-out';

                if (pullDistance > threshold) {
                    // Trigger Refresh
                    ptrIcon.classList.add('hidden');
                    ptrSpinner.classList.remove('hidden');
                    
                    // Tahan posisi loading
                    mainContent.style.transform = 'translateY(60px)';
                    ptrIndicator.style.transform = 'translateY(0px)';
                    
                    await refreshDashboard();
                    
                    // Reset setelah selesai
                    setTimeout(() => {
                        mainContent.style.transform = '';
                        ptrIndicator.style.transform = '';
                        setTimeout(() => {
                            ptrIcon.classList.remove('hidden');
                            ptrSpinner.classList.add('hidden');
                            ptrIcon.classList.remove('ptr-rotate');
                        }, 300);
                    }, 500);
                } else {
                    // Batal tarik (kembali ke posisi awal)
                    mainContent.style.transform = '';
                    ptrIndicator.style.transform = '';
                }
                pullDistance = 0;
            });
        }

        async function refreshDashboard() {
            // Cek tab mana yang aktif untuk refresh data yang relevan
            const activeTabBtn = document.querySelector('.nav-item.active');
            const activeTab = activeTabBtn ? activeTabBtn.id.replace('nav-', '') : 'home';

            try {
                if (activeTab === 'home') {
                    await Promise.all([loadSummary(), loadRecentHistory(), loadSavingsChart()]);
                } else if (activeTab === 'simpanan') {
                    if(typeof loadSimpananHistory === 'function') await loadSimpananHistory();
                } else if (activeTab === 'pinjaman') {
                    if(typeof loadPinjamanList === 'function') await loadPinjamanList();
                } else if (activeTab === 'belanja') {
                    await loadSummary(); // Refresh saldo
                } else if (activeTab === 'profile') {
                    await loadSummary();
                }
            } catch (e) {
                console.error("Refresh failed", e);
            }
        }

        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= BASE_PATH ?>/service-worker.js')
                    .then(registration => {
                        console.log('✅ ServiceWorker berhasil didaftarkan dengan scope:', registration.scope);
                    })
                    .catch(err => {
                        console.error('❌ ServiceWorker gagal didaftarkan:', err);
                    });
            });
        }

        // PWA Install Logic
        let deferredPrompt;
        const installBanner = document.getElementById('install-banner');
        const btnInstall = document.getElementById('btn-install');
        const btnCloseInstall = document.getElementById('btn-close-install');

        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('✅ Event PWA (beforeinstallprompt) berhasil dipicu! Aplikasi siap diinstall.');
            // Prevent the mini-infobar from appearing on mobile
            e.preventDefault();
            // Stash the event so it can be triggered later.
            deferredPrompt = e;
            // Update UI notify the user they can install the PWA
            if (installBanner) {
                installBanner.classList.remove('hidden');
                // Small delay for animation
                setTimeout(() => {
                    installBanner.classList.remove('translate-y-20', 'opacity-0');
                }, 100);
            }
        });

        if (btnInstall) {
            btnInstall.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    deferredPrompt = null;
                    // Hide banner
                    if (installBanner) installBanner.classList.add('hidden');
                }
            });
        }

        if (btnCloseInstall) {
            btnCloseInstall.addEventListener('click', () => {
                if (installBanner) {
                    installBanner.classList.add('translate-y-20', 'opacity-0');
                    setTimeout(() => {
                        installBanner.classList.add('hidden');
                    }, 500);
                }
            });
        }

        window.addEventListener('appinstalled', () => {
            if (installBanner) installBanner.classList.add('hidden');
            deferredPrompt = null;
        });
    </script>
</body>
</html>