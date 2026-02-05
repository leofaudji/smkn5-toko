<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Jika sudah login sebagai anggota, redirect ke dashboard anggota
if (isset($_SESSION['member_loggedin']) && $_SESSION['member_loggedin'] === true) {
    header('Location: ' . base_url('/member/dashboard'));
    exit;
}

$app_logo_path = get_setting('app_logo');
$logo_src = !empty($app_logo_path) ? base_url($app_logo_path) : base_url('assets/img/logo.png');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login Anggota - <?= get_setting('app_name', 'Koperasi') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');
        body { 
            background-color: #f8fafc; 
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .mobile-container { 
            max-width: 480px; 
            margin: 0 auto; 
            min-height: 100vh; 
            background: white; 
            position: relative; 
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="mobile-container flex flex-col justify-center px-8 py-10">
        
        <!-- Header / Logo Area -->
        <div class="text-center mb-10">
            <div class="inline-block p-4 rounded-3xl bg-blue-50 mb-6 relative overflow-hidden group">
                <div class="absolute inset-0 bg-blue-100 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <img src="<?= $logo_src ?>" class="w-16 h-16 object-contain relative z-10" alt="Logo" onerror="this.src='https://ui-avatars.com/api/?name=Koperasi&background=2563eb&color=fff'">
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Selamat Datang</h1>
            <p class="text-gray-500 text-sm leading-relaxed">Masuk ke portal anggota <br><span class="font-semibold text-blue-600"><?= get_setting('app_name', 'Koperasi') ?></span></p>
        </div>

        <!-- Login Form -->
        <form id="login-form" class="space-y-6">
            <div class="space-y-1">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Nomor Anggota</label>
                <div class="relative group">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors">
                        <i class="bi bi-person-badge text-lg"></i>
                    </span>
                    <input type="text" name="nomor_anggota" autocomplete="username" 
                        class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm font-medium text-gray-800 placeholder-gray-400" 
                        placeholder="Contoh: ANG-001" required>
                </div>
            </div>
            
            <div class="space-y-1">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Password</label>
                <div class="relative group">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors">
                        <i class="bi bi-shield-lock text-lg"></i>
                    </span>
                    <input type="password" name="password" id="password" autocomplete="current-password" 
                        class="w-full pl-12 pr-12 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm font-medium text-gray-800 placeholder-gray-400" 
                        placeholder="Masukkan password" required>
                    <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 focus:outline-none">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 rounded-2xl shadow-lg shadow-blue-200 active:scale-[0.98] transition-all duration-200 flex justify-center items-center gap-2 text-sm">
                    <span>Masuk Sekarang</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </form>

        <div id="alert-message" class="mt-6 hidden"></div>

        <div class="mt-auto pt-10 text-center">
            <p class="text-xs text-gray-400">
                &copy; <?= date('Y') ?> <?= get_setting('app_name', 'Koperasi') ?>. <br>All rights reserved.
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalContent = btn.innerHTML;
            const alertBox = document.getElementById('alert-message');
            
            btn.disabled = true;
            btn.innerHTML = '<div class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>';
            alertBox.classList.add('hidden');

            try {
                const formData = new FormData(this);
                const response = await fetch('<?= base_url('/api/member/login') ?>', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    // Success animation or redirect
                    btn.innerHTML = '<i class="bi bi-check-lg text-xl"></i>';
                    btn.classList.remove('from-blue-600', 'to-indigo-600');
                    btn.classList.add('bg-green-500');
                    
                    setTimeout(() => {
                        window.location.href = '<?= base_url('/member/dashboard') ?>';
                    }, 500);
                } else {
                    alertBox.innerHTML = `
                        <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-2xl text-sm flex items-start gap-3">
                            <i class="bi bi-exclamation-circle-fill mt-0.5"></i>
                            <div>${result.message}</div>
                        </div>
                    `;
                    alertBox.classList.remove('hidden');
                    
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            } catch (error) {
                console.error(error);
                alertBox.innerHTML = `
                    <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-2xl text-sm flex items-start gap-3">
                        <i class="bi bi-wifi-off mt-0.5"></i>
                        <div>Terjadi kesalahan jaringan. Periksa koneksi Anda.</div>
                    </div>
                `;
                alertBox.classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        });
    </script>
</body>
</html>