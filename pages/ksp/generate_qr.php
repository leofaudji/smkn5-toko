<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<!-- Header Section with Gradient -->
<div class="relative bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-lg mb-8 p-6 overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-16 -mt-16 blur-3xl"></div>
    <div class="relative z-10 flex justify-between items-center text-white">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-3">
                <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                    <i class="bi bi-qr-code-scan"></i>
                </div>
                Generator QR Pembayaran
            </h1>
            <p class="text-blue-100 mt-1 text-sm">Buat kode QR untuk pembayaran anggota dengan mudah.</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Configuration Panel -->
    <div class="lg:col-span-4 space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800">
                <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="bi bi-sliders text-blue-500"></i> Konfigurasi
                </h3>
            </div>
            <div class="p-6">
                <form id="qr-generator-form" class="space-y-5">
                    <div>
                        <label for="qr-merchant" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Nama Merchant</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="bi bi-shop"></i>
                            </span>
                            <input type="text" class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm" id="qr-merchant" value="<?= htmlspecialchars(get_setting('app_name', 'Koperasi')) ?>" required>
                        </div>
                    </div>
                    
                    <div>
                        <label for="qr-amount" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Nominal Pembayaran</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 font-bold text-sm">Rp</span>
                            <input type="number" class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm font-semibold" id="qr-amount" placeholder="0" required>
                        </div>
                    </div>

                    <div>
                        <label for="qr-ref" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Referensi (Opsional)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="bi bi-hash"></i>
                            </span>
                            <input type="text" class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm" id="qr-ref" placeholder="Contoh: INV-001">
                        </div>
                    </div>

                    <button type="submit" class="w-full flex justify-center items-center gap-2 py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold shadow-lg shadow-blue-200 transition-all active:scale-95">
                        <i class="bi bi-magic"></i> Generate QR Code
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Preview Panel -->
    <div class="lg:col-span-8">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-2xl border border-gray-100 dark:border-gray-700 h-full flex flex-col">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800 flex justify-between items-center">
                <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="bi bi-eye text-purple-500"></i> Preview
                </h3>
                <div id="qr-actions" class="hidden flex gap-2">
                    <button id="download-qr-btn" class="text-xs flex items-center gap-1 px-3 py-1.5 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600 transition">
                        <i class="bi bi-download"></i> Simpan PNG
                    </button>
                    <button id="print-qr-btn" class="text-xs flex items-center gap-1 px-3 py-1.5 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600 transition">
                        <i class="bi bi-printer"></i> Cetak
                    </button>
                </div>
            </div>
            
            <div class="flex-1 p-8 flex flex-col items-center justify-center min-h-[400px] bg-gray-50/30 dark:bg-gray-900/30">
                <!-- QR Card Container -->
                <div id="qr-card" class="bg-white p-6 rounded-3xl shadow-xl border border-gray-100 max-w-sm w-full text-center relative transition-all duration-500 transform scale-95 opacity-50 blur-sm">
                    <!-- Logo Overlay -->
                    <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center shadow-lg border-4 border-white z-10">
                        <i class="bi bi-wallet2 text-white text-xl"></i>
                    </div>

                    <div class="mt-6 mb-4">
                        <h4 class="text-lg font-bold text-gray-800" id="display-merchant">Nama Merchant</h4>
                        <p class="text-xs text-gray-500 uppercase tracking-widest mt-1">Scan to Pay</p>
                    </div>

                    <div class="bg-gray-900 p-4 rounded-2xl inline-block shadow-inner relative group">
                        <div id="qrcode" class="bg-white p-2 rounded-xl"></div>
                        <!-- Scan Me Badge -->
                        <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 translate-y-1/2 bg-white px-3 py-1 rounded-full shadow-md border border-gray-100 text-[10px] font-bold text-gray-800 whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">
                            SCAN ME
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-dashed border-gray-200">
                        <p class="text-xs text-gray-400 mb-1">Total Pembayaran</p>
                        <h2 class="text-3xl font-extrabold text-blue-600" id="display-amount">Rp 0</h2>
                        <p class="text-xs text-gray-400 mt-2 font-mono" id="display-ref">REF: -</p>
                    </div>
                </div>

                <!-- Empty State -->
                <div id="empty-state" class="text-center absolute">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                        <i class="bi bi-qr-code text-4xl"></i>
                    </div>
                    <h3 class="text-gray-500 font-medium">Belum ada QR Code</h3>
                    <p class="text-gray-400 text-sm mt-1">Isi formulir di sebelah kiri untuk membuat QR Code.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>