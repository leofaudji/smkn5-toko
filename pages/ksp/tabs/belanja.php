<div id="tab-belanja" class="tab-content hidden pb-28 bg-gray-50 min-h-screen">
    <!-- Header Section -->
    <div class="relative bg-gradient-to-br from-rose-500 to-orange-400 pb-24 pt-4 px-6 rounded-b-[2.5rem] shadow-xl overflow-hidden">
        <!-- Decorative Elements -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-20 -mt-20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-40 h-40 bg-white/10 rounded-full -ml-10 -mb-10 blur-2xl"></div>
        
        <div class="relative z-10">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <button onclick="switchTab('home')" class="w-10 h-10 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-white hover:bg-white/30 transition shadow-sm">
                        <i class="bi bi-arrow-left text-lg"></i>
                    </button>
                    <h2 class="text-white font-bold text-lg tracking-wide">Toko Koperasi</h2>
                </div>
                <div class="bg-white/20 backdrop-blur-md p-2 rounded-full shadow-lg">
                    <i class="bi bi-shop text-white text-lg"></i>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="flex gap-2">
                <div class="relative group flex-1">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="bi bi-search text-white/70 text-lg group-focus-within:text-white transition-colors"></i>
                    </div>
                    <input type="text" id="search-item-input" 
                        class="w-full pl-12 pr-4 py-3.5 bg-white/20 backdrop-blur-md border border-white/30 rounded-2xl text-white placeholder-white/70 focus:outline-none focus:bg-white/30 focus:border-white/50 transition-all shadow-sm" 
                        placeholder="Cari barang (min. 2 huruf)...">
                </div>
                <button onclick="startBarcodeScanner()" class="shrink-0 w-[54px] h-[54px] rounded-2xl bg-white/20 backdrop-blur-md border border-white/30 flex items-center justify-center text-white hover:bg-white/30 transition shadow-sm active:scale-95">
                    <i class="bi bi-qr-code-scan text-xl"></i>
                </button>
            </div>

            <!-- Categories -->
            <div class="mt-6 flex gap-2 overflow-x-auto hide-scrollbar pb-2 items-center" id="store-categories">
                <button onclick="toggleWishlistView()" id="btn-wishlist-toggle" class="shrink-0 w-8 h-8 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-white hover:bg-white/30 transition shadow-sm border border-white/20"><i class="bi bi-heart"></i></button>
                <div class="w-px h-6 bg-white/20 mx-1 shrink-0"></div>
                <!-- Categories loaded via JS -->
            </div>
        </div>
    </div> 

    <!-- Content Container -->
    <div class="px-5 -mt-12 relative z-20 space-y-6">
        
        <!-- Item List -->
        <div id="item-list-container" class="grid grid-cols-2 gap-4">
            <!-- Empty State / Initial State -->
            <div class="col-span-2 bg-white rounded-2xl p-8 text-center shadow-sm border border-gray-100">
                <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="bi bi-basket text-3xl"></i>
                </div>
                <h3 class="text-gray-800 font-bold mb-1">Mulai Belanja</h3>
                <p class="text-gray-500 text-xs">Ketik nama barang di kolom pencarian di atas.</p>
            </div>
        </div>

        <!-- Riwayat Belanja Section -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-800 text-lg">Riwayat Belanja</h3>
                <button onclick="loadShoppingHistory()" class="text-xs text-rose-600 font-medium hover:underline">Perbarui</button>
            </div>
            <div id="shopping-history-list" class="space-y-3">
                <!-- History items loaded via JS -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Scan Barcode -->
<div id="modal-scan-barcode" class="fixed inset-0 z-[80] hidden" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-80 transition-opacity" aria-hidden="true" onclick="stopBarcodeScanner()"></div>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full relative">
            <div class="bg-white p-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Scan Barcode</h3>
                    <button onclick="stopBarcodeScanner()" class="text-gray-400 hover:text-gray-500 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>
                <div id="reader" class="w-full rounded-lg overflow-hidden bg-black"></div>
                <p class="text-center text-xs text-gray-500 mt-4">Arahkan kamera ke barcode barang.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Keranjang Belanja -->
<div id="modal-keranjang" class="fixed inset-0 z-[70] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-keranjang').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-t-[2rem] text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Keranjang Belanja</h3>
                    <button onclick="document.getElementById('modal-keranjang').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>
                
                <div id="cart-items-list" class="space-y-3 max-h-60 overflow-y-auto mb-4 pr-2">
                    <!-- Cart items injected here -->
                </div>
                
                <div class="border-t border-gray-200 pt-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span id="cart-subtotal" class="font-medium text-gray-800">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold">
                        <span>Total</span>
                        <span id="cart-total" class="text-rose-600">Rp 0</span>
                    </div>
                    
                    <form id="form-checkout-auth" onsubmit="event.preventDefault();">
                        <input type="text" name="username" autocomplete="username" class="hidden" value="<?= $_SESSION['member_no'] ?? '' ?>">
                        <div class="p-4 bg-yellow-50 border border-yellow-100 rounded-xl mt-4">
                            <div class="flex gap-3 mb-2">
                                <i class="bi bi-shield-lock text-yellow-600 text-xl"></i>
                                <div>
                                    <label class="block text-xs font-bold text-yellow-800 uppercase tracking-wide mb-1">Konfirmasi Password</label>
                                    <p class="text-[10px] text-yellow-700 mb-2">Pembayaran dipotong dari Simpanan Sukarela.</p>
                                </div>
                            </div>
                            <input type="password" id="checkout-password" name="password" autocomplete="current-password" class="w-full px-4 py-2.5 border border-yellow-200 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 text-sm bg-white" placeholder="Masukkan password Anda" required>
                        </div>
                    </form>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-4 sm:px-6">
                <button type="button" id="btn-checkout" class="w-full bg-gradient-to-r from-rose-600 to-orange-500 text-white py-3.5 rounded-xl font-bold shadow-lg shadow-rose-200 hover:shadow-rose-300 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    Bayar Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Belanja -->
<div id="modal-detail-belanja" class="fixed inset-0 z-[60] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-detail-belanja').classList.add('hidden')"></div>
        <div class="inline-block align-bottom bg-white rounded-t-[2rem] text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h3 class="text-lg font-bold text-gray-900">Detail Belanja</h3>
                    <button onclick="document.getElementById('modal-detail-belanja').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>
                <div id="detail-belanja-content">
                    <div class="text-center py-8">
                        <span class="animate-spin inline-block w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full"></span>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-3 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="document.getElementById('modal-detail-belanja').classList.add('hidden')">Tutup</button>
            </div>
        </div>
    </div>
</div>