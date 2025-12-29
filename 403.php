<!-- d:\xampp\htdocs\smkn5-toko\403.php -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden - Akses Ditolak</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 h-screen flex flex-col items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-2xl overflow-hidden text-center p-8 transform transition-all hover:scale-105 duration-300">
        <div class="mb-6 relative">
            <div class="absolute inset-0 bg-red-100 rounded-full animate-ping opacity-75"></div>
            <div class="relative bg-red-100 rounded-full w-24 h-24 flex items-center justify-center mx-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
        </div>
        
        <h1 class="text-5xl font-bold text-gray-900 mb-2">403</h1>
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">Akses Ditolak</h2>
        
        <p class="text-gray-500 mb-8 leading-relaxed text-sm">
            Maaf, Anda tidak memiliki izin untuk mengakses file atau halaman ini.<br>
            Sistem keamanan telah memblokir permintaan Anda demi keamanan data.
        </p>
        
        <div class="space-y-3">
            <a href="/smkn5-toko/" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 shadow-md hover:shadow-lg">
                Kembali ke Dashboard
            </a>
            <button onclick="history.back()" class="block w-full bg-white hover:bg-gray-50 text-gray-700 font-semibold py-3 px-6 rounded-lg border border-gray-300 transition duration-300">
                Kembali ke Halaman Sebelumnya
            </button>
        </div>
    </div>
    <div class="mt-8 text-gray-400 text-xs">
        &copy; <?php echo date('Y'); ?> SMKN 5 Toko. Security System.
    </div>
</body>
</html>
