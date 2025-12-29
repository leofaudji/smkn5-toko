<div class="flex flex-col items-center justify-center min-h-[60vh] px-4 text-center">
    <div class="p-6 mb-6 bg-red-50 rounded-full dark:bg-red-900/20">
        <i class="bi bi-shield-lock-fill text-6xl text-red-500 dark:text-red-400"></i>
    </div>
    <h1 class="mb-2 text-4xl font-bold text-gray-900 dark:text-white">Akses Ditolak</h1>
    <p class="max-w-md mb-8 text-lg text-gray-600 dark:text-gray-400">
        Maaf, Anda tidak memiliki izin untuk mengakses halaman ini. Silakan hubungi administrator jika Anda merasa ini adalah kesalahan.
    </p>
    <div class="flex gap-4">
        <a href="<?= base_url('/dashboard') ?>" class="inline-flex items-center px-6 py-3 text-base font-medium text-white transition-colors border border-transparent rounded-md shadow-sm bg-primary hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
            <i class="bi bi-speedometer2 mr-2"></i> Dashboard
        </a>
        <button onclick="history.back()" class="inline-flex items-center px-6 py-3 text-base font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-md shadow-sm dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
            <i class="bi bi-arrow-left mr-2"></i> Kembali
        </button>
    </div>
</div>