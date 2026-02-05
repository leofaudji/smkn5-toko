// d:\laragon\www\smkn5-toko\assets\js\ksp\member\belanja.js

let cart = [];
let currentCategory = '';
let isWishlistView = false;
let searchTimeout = null;

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-item-input');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchItems(e.target.value);
            }, 500);
        });
    }

    const checkoutBtn = document.getElementById('btn-checkout');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', handleCheckout);
    }

    // Fix DOM warnings for checkout password
    const checkoutPassword = document.getElementById('checkout-password');
    if (checkoutPassword) {
        checkoutPassword.setAttribute('autocomplete', 'current-password');
        checkoutPassword.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleCheckout();
            }
        });
    }
    
    loadStoreCategories();
});

async function loadStoreCategories() {
    const container = document.getElementById('store-categories');
    if(!container) return;
    
    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=get_item_categories`);
        const json = await res.json();
        
        if(json.success) {
            // Fix: Tambahkan kembali tombol wishlist dan separator karena innerHTML akan menimpa konten sebelumnya
            let html = `
                <button onclick="toggleWishlistView()" id="btn-wishlist-toggle" class="shrink-0 w-8 h-8 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-white hover:bg-white/30 transition shadow-sm border border-white/20"><i class="bi bi-heart"></i></button>
                <div class="w-px h-6 bg-white/20 mx-1 shrink-0"></div>
            `;

            html += `<button onclick="selectCategory('')" class="category-btn active whitespace-nowrap px-4 py-1.5 rounded-full text-xs font-medium transition-colors bg-white text-rose-600 border border-white/20 shadow-sm" data-id="">Semua</button>`;
            
            html += json.data.map(c => `
                <button onclick="selectCategory(${c.id})" class="category-btn whitespace-nowrap px-4 py-1.5 rounded-full text-xs font-medium transition-colors bg-white/20 text-white border border-white/20 hover:bg-white/30" data-id="${c.id}">${c.nama_kategori}</button>
            `).join('');
            
            container.innerHTML = html;
            updateWishlistButtonState();
        }
    } catch(e) { console.error(e); }
}

window.toggleWishlistView = function() {
    isWishlistView = !isWishlistView;
    
    updateWishlistButtonState();

    if (isWishlistView) {
        loadWishlistItems();
    } else {
        const query = document.getElementById('search-item-input').value;
        searchItems(query);
    }
}

window.selectCategory = function(id) {
    currentCategory = id;
    isWishlistView = false; // Reset wishlist view when category selected
    
    // Update wishlist button style based on content
    updateWishlistButtonState();
    
    // Update UI Styles
    document.querySelectorAll('.category-btn').forEach(btn => {
        if(btn.dataset.id == id) {
            btn.className = 'category-btn active whitespace-nowrap px-4 py-1.5 rounded-full text-xs font-medium transition-colors bg-white text-rose-600 border border-white/20 shadow-sm';
        } else {
            btn.className = 'category-btn whitespace-nowrap px-4 py-1.5 rounded-full text-xs font-medium transition-colors bg-white/20 text-white border border-white/20 hover:bg-white/30';
        }
    });
    
    const query = document.getElementById('search-item-input').value;
    searchItems(query);
}

async function updateWishlistButtonState() {
    const btn = document.getElementById('btn-wishlist-toggle');
    if (!btn) return;

    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=get_wishlist`);
        const json = await res.json();
        const count = (json.success && json.data) ? json.data.length : 0;
        
        let baseClass = 'shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition shadow-sm border relative ';
        let contentHtml = '';

        if (isWishlistView || count > 0) {
            baseClass += 'bg-rose-500 text-white border-rose-400';
            contentHtml = '<i class="bi bi-heart-fill"></i>';
        } else {
            baseClass += 'bg-white/20 backdrop-blur-md text-white hover:bg-white/30 border-white/20';
            contentHtml = '<i class="bi bi-heart"></i>';
        }
        
        btn.className = baseClass;

        if (count > 0) {
            contentHtml += `<span class="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-white border border-rose-100 text-[10px] font-bold text-rose-600 shadow-sm">${count}</span>`;
        }
        
        btn.innerHTML = contentHtml;
    } catch (e) { console.error(e); }
}

async function searchItems(query) {
    if (isWishlistView) return; // Don't search if in wishlist view

    const container = document.getElementById('item-list-container');
    
    // Izinkan pencarian jika ada kategori yang dipilih ATAU query cukup panjang
    if ((!query || query.length < 2) && !currentCategory) {
        container.innerHTML = `
            <div class="col-span-2 bg-white rounded-2xl p-8 text-center shadow-sm border border-gray-100">
                <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="bi bi-basket text-3xl"></i>
                </div>
                <h3 class="text-gray-800 font-bold mb-1">Mulai Belanja</h3>
                <p class="text-gray-500 text-xs">Ketik nama barang di kolom pencarian di atas.</p>
            </div>`;
        return;
    }

    container.innerHTML = '<div class="col-span-2 text-center py-8"><span class="animate-spin inline-block w-8 h-8 border-2 border-rose-500 border-t-transparent rounded-full"></span></div>';

    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=search_store_items&q=${encodeURIComponent(query)}&category_id=${currentCategory}`);
        const json = await res.json();

        if (json.success && json.data.length > 0) {
            container.innerHTML = json.data.map(item => {
                const stok = parseInt(item.stok);
                const isHabis = stok <= 0;
                const heartIcon = item.is_wishlist ? 'bi-heart-fill text-rose-500' : 'bi-heart text-gray-400';
                
                return `
                <div class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 flex flex-col h-full relative overflow-hidden group hover:shadow-md transition-shadow">
                    <div class="absolute top-2 right-2 ${isHabis ? 'bg-red-500' : 'bg-gray-900/80'} backdrop-blur text-white text-[10px] font-bold px-2 py-0.5 rounded-full z-10">
                        ${isHabis ? 'Habis' : 'Stok: ' + stok}
                    </div>
                    
                    <button onclick="toggleWishlist(this, ${item.id})" class="absolute top-2 left-2 z-10 w-8 h-8 rounded-full bg-white/80 backdrop-blur flex items-center justify-center shadow-sm hover:bg-white transition">
                        <i class="bi ${heartIcon} text-lg transition-transform active:scale-125"></i>
                    </button>

                    <div class="h-24 bg-gray-50 rounded-xl mb-3 flex items-center justify-center text-gray-300 relative overflow-hidden">
                        <i class="bi bi-box-seam text-4xl"></i>
                        <!-- Placeholder gradient overlay -->
                        <div class="absolute inset-0 bg-gradient-to-tr from-transparent to-white/50 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </div>
                    
                    <h4 class="font-bold text-gray-800 text-sm line-clamp-2 mb-1 flex-grow leading-snug">${item.nama_barang}</h4>
                    
                    <div class="flex items-end justify-between mt-2 pt-2 border-t border-gray-50">
                        <span class="text-rose-600 font-bold text-sm">${formatRupiah(item.harga_jual)}</span>
                        <button onclick="addToCart(${item.id}, '${item.nama_barang.replace(/'/g, "\\'")}', ${item.harga_jual}, ${stok})" 
                            class="w-8 h-8 rounded-full ${isHabis ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white'} flex items-center justify-center transition-all shadow-sm active:scale-95"
                            ${isHabis ? 'disabled' : ''}>
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>
                `;
            }).join('');
        } else {
            container.innerHTML = `
                <div class="col-span-2 text-center py-10">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2 text-gray-400">
                        <i class="bi bi-search"></i>
                    </div>
                    <p class="text-gray-500 text-sm">Barang tidak ditemukan.</p>
                </div>`;
        }
    } catch (e) {
        console.error(e);
        container.innerHTML = '<p class="col-span-2 text-center text-red-500 py-8 text-sm">Gagal memuat data.</p>';
    }
}

async function loadWishlistItems() {
    const container = document.getElementById('item-list-container');
    container.innerHTML = '<div class="col-span-2 text-center py-8"><span class="animate-spin inline-block w-8 h-8 border-2 border-rose-500 border-t-transparent rounded-full"></span></div>';

    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=get_wishlist`);
        const json = await res.json();

        if (json.success && json.data.length > 0) {
            container.innerHTML = json.data.map(item => {
                const stok = parseInt(item.stok);
                const isHabis = stok <= 0;
                
                return `
                <div class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 flex flex-col h-full relative overflow-hidden group hover:shadow-md transition-shadow">
                    <div class="absolute top-2 right-2 ${isHabis ? 'bg-red-500' : 'bg-gray-900/80'} backdrop-blur text-white text-[10px] font-bold px-2 py-0.5 rounded-full z-10">
                        ${isHabis ? 'Habis' : 'Stok: ' + stok}
                    </div>
                    
                    <button onclick="toggleWishlist(this, ${item.id})" class="absolute top-2 left-2 z-10 w-8 h-8 rounded-full bg-white/80 backdrop-blur flex items-center justify-center shadow-sm hover:bg-white transition">
                        <i class="bi bi-heart-fill text-rose-500 text-lg transition-transform active:scale-125"></i>
                    </button>

                    <div class="h-24 bg-gray-50 rounded-xl mb-3 flex items-center justify-center text-gray-300 relative overflow-hidden">
                        <i class="bi bi-box-seam text-4xl"></i>
                    </div>
                    
                    <h4 class="font-bold text-gray-800 text-sm line-clamp-2 mb-1 flex-grow leading-snug">${item.nama_barang}</h4>
                    
                    <div class="flex items-end justify-between mt-2 pt-2 border-t border-gray-50">
                        <span class="text-rose-600 font-bold text-sm">${formatRupiah(item.harga_jual)}</span>
                        <button onclick="addToCart(${item.id}, '${item.nama_barang.replace(/'/g, "\\'")}', ${item.harga_jual}, ${stok})" 
                            class="w-8 h-8 rounded-full ${isHabis ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white'} flex items-center justify-center transition-all shadow-sm active:scale-95"
                            ${isHabis ? 'disabled' : ''}>
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>
                `;
            }).join('');
        } else {
            container.innerHTML = `
                <div class="col-span-2 text-center py-10">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2 text-gray-400">
                        <i class="bi bi-heart"></i>
                    </div>
                    <p class="text-gray-500 text-sm">Wishlist Anda kosong.</p>
                </div>`;
        }
    } catch (e) {
        console.error(e);
        container.innerHTML = '<p class="col-span-2 text-center text-red-500 py-8 text-sm">Gagal memuat wishlist.</p>';
    }
}

window.toggleWishlist = async function(btn, itemId) {
    // Optimistic UI update
    const icon = btn.querySelector('i');
    const isAdding = icon.classList.contains('bi-heart');
    
    if (isAdding) {
        icon.classList.replace('bi-heart', 'bi-heart-fill');
        icon.classList.replace('text-gray-400', 'text-rose-500');
    } else {
        icon.classList.replace('bi-heart-fill', 'bi-heart');
        icon.classList.replace('text-rose-500', 'text-gray-400');
        
        // If in wishlist view, remove the card
        if (isWishlistView) {
            const card = btn.closest('.bg-white');
            if (card) card.remove();
            // Check if empty
            const container = document.getElementById('item-list-container');
            if (container.children.length === 0) {
                container.innerHTML = `<div class="col-span-2 text-center py-10"><p class="text-gray-500 text-sm">Wishlist Anda kosong.</p></div>`;
            }
        }
    }

    try {
        const formData = new FormData();
        formData.append('item_id', itemId);
        const res = await fetch(`${basePath}/api/member/dashboard?action=toggle_wishlist`, {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        
        if (json.success) {
            Swal.fire({
                icon: 'success',
                title: json.status === 'added' ? 'Disimpan' : 'Dihapus',
                text: json.message,
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 1500
            });
            await updateWishlistButtonState();

            // Animasi denyut (pulse) jika barang ditambahkan
            if (json.status === 'added') {
                const wBtn = document.getElementById('btn-wishlist-toggle');
                if (wBtn) {
                    wBtn.classList.add('scale-125', 'ring-4', 'ring-rose-400/50');
                    setTimeout(() => {
                        wBtn.classList.remove('scale-125', 'ring-4', 'ring-rose-400/50');
                    }, 300);
                }
            }
        }
    } catch (e) {
        console.error(e);
        // Revert UI on error
        if (isAdding) {
            icon.classList.replace('bi-heart-fill', 'bi-heart');
            icon.classList.replace('text-rose-500', 'text-gray-400');
        } else {
            icon.classList.replace('bi-heart', 'bi-heart-fill');
            icon.classList.replace('text-gray-400', 'text-rose-500');
        }
    }
}

function addToCart(id, name, price, maxStock) {
    const existing = cart.find(i => i.id === id);
    if (existing) {
        if (existing.qty < maxStock) {
            existing.qty++;
            Swal.fire({
                icon: 'success',
                title: 'Ditambahkan',
                text: `${name} (+1)`,
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 1500
            });
        } else {
            Swal.fire({ icon: 'warning', title: 'Stok Habis', text: 'Mencapai batas stok tersedia.' });
        }
    } else {
        cart.push({ id, name, price, qty: 1, max: maxStock });
        Swal.fire({
            icon: 'success',
            title: 'Masuk Keranjang',
            text: name,
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 1500
        });
    }
    updateCartUI();
}

function updateCartUI() {
    const fab = document.getElementById('cart-fab');
    const badge = document.getElementById('cart-count-badge');
    const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);

    if (totalQty > 0) {
        fab.classList.remove('hidden');
        badge.textContent = totalQty;
        // Animasi bounce kecil saat update
        fab.classList.add('scale-110');
        setTimeout(() => fab.classList.remove('scale-110'), 200);
    } else {
        fab.classList.add('hidden');
    }
}

async function openCartModal() {
    const modal = document.getElementById('modal-keranjang');
    const list = document.getElementById('cart-items-list');
    const subtotalEl = document.getElementById('cart-subtotal');
    const totalEl = document.getElementById('cart-total');
    const btnCheckout = document.getElementById('btn-checkout');
    let total = cart.reduce((sum, item) => sum + (item.qty * item.price), 0);

    // Cek saldo sebelum membuka modal
    if (total > 0) {
        const data = window.memberDashboardData;
        let saldoTersedia = 0;
        let namaSimpanan = 'Simpanan Sukarela';

        if (data && data.simpanan_per_jenis) {
            const defaultId = data.default_payment_savings_id;
            const source = data.simpanan_per_jenis.find(s => s.id == defaultId) || data.simpanan_per_jenis.find(s => s.tipe === 'sukarela');
            if (source) { saldoTersedia = source.saldo; namaSimpanan = source.nama; }
        }

        if (total > saldoTersedia) {
            Swal.fire('Saldo Mungkin Tidak Cukup', `Total belanja Anda (<b>${formatRupiah(total)}</b>) melebihi saldo ${namaSimpanan} (<b>${formatRupiah(saldoTersedia)}</b>).`, 'warning');
            // Tetap buka modal agar user bisa mengurangi item
        }
    }

    list.innerHTML = '';

    if (cart.length === 0) {
        list.innerHTML = '<p class="text-center text-gray-400 py-4 text-sm">Keranjang kosong.</p>';
        btnCheckout.disabled = true;
    } else {
        cart.forEach((item, index) => {
            const sub = item.qty * item.price;
            total += sub;
            list.innerHTML += `
                <div class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <div class="flex-1">
                        <h4 class="font-bold text-gray-800 text-sm line-clamp-1">${item.name}</h4>
                        <p class="text-xs text-gray-500">${formatRupiah(item.price)} x ${item.qty}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center bg-white rounded-lg border border-gray-200 h-8">
                            <button onclick="updateQty(${index}, -1)" class="w-8 h-full flex items-center justify-center text-gray-500 hover:bg-gray-100 rounded-l-lg transition">-</button>
                            <span class="w-8 text-center text-xs font-bold text-gray-800">${item.qty}</span>
                            <button onclick="updateQty(${index}, 1)" class="w-8 h-full flex items-center justify-center text-gray-500 hover:bg-gray-100 rounded-r-lg transition">+</button>
                        </div>
                        <button onclick="removeItem(${index})" class="text-red-400 hover:text-red-600 transition p-1">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        btnCheckout.disabled = false;
    }

    subtotalEl.textContent = formatRupiah(total);
    totalEl.textContent = formatRupiah(total);
    modal.classList.remove('hidden');
}

function updateQty(index, change) {
    const item = cart[index];
    const newQty = item.qty + change;
    
    if (newQty > 0 && newQty <= item.max) {
        item.qty = newQty;
    } else if (newQty > item.max) {
        Swal.fire({ icon: 'warning', title: 'Maksimal', text: 'Stok tidak mencukupi.' });
    }
    
    updateCartUI();
    openCartModal(); // Refresh modal content
}

function removeItem(index) {
    cart.splice(index, 1);
    updateCartUI();
    openCartModal();
}

async function handleCheckout() {
    const password = document.getElementById('checkout-password').value;
    if (!password) {
        Swal.fire('Password Diperlukan', 'Masukkan password Anda untuk konfirmasi.', 'warning');
        return;
    }

    const btn = document.getElementById('btn-checkout');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Memproses...';

    try {
        const response = await fetch(`${basePath}/api/member/dashboard?action=checkout_store`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: cart, password: password })
        });
        const result = await response.json();

        if (result.success) {
            Swal.fire('Berhasil', result.message, 'success');
            cart = [];
            updateCartUI();
            document.getElementById('modal-keranjang').classList.add('hidden');
            document.getElementById('checkout-password').value = '';
            loadShoppingHistory();
            loadSummary(); // Refresh saldo simpanan
        } else {
            Swal.fire('Gagal', result.message, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
    } finally {
        btn.disabled = false;
        btn.innerText = originalText;
    }
}

async function loadShoppingHistory() {
    const container = document.getElementById('shopping-history-list');
    if (!container) return;
    
    container.innerHTML = '<div class="text-center py-4 text-gray-400 text-xs">Memuat riwayat...</div>';

    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=get_shopping_history`);
        const json = await res.json();

        if (json.success && json.data.length > 0) {
            container.innerHTML = json.data.map(tx => `
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex justify-between items-center cursor-pointer hover:bg-gray-50 transition" onclick="showShoppingDetail(${tx.id})">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center">
                            <i class="bi bi-bag-check-fill text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-gray-800 text-sm">${formatDate(tx.tanggal_penjualan)}</p>
                            <p class="text-xs text-gray-500">${tx.item_count} Barang</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-800 text-sm">${formatRupiah(tx.total)}</p>
                        <span class="text-[10px] text-green-600 bg-green-50 px-2 py-0.5 rounded-full font-bold uppercase tracking-wide">Selesai</span>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="text-center py-8 text-gray-400 text-sm bg-white rounded-2xl border border-dashed border-gray-200">Belum ada riwayat belanja.</div>';
        }
    } catch (e) {
        console.error(e);
        container.innerHTML = '<p class="text-center text-red-500 text-xs">Gagal memuat riwayat.</p>';
    }
}

async function showShoppingDetail(id) {
    const modal = document.getElementById('modal-detail-belanja');
    const content = document.getElementById('detail-belanja-content');
    modal.classList.remove('hidden');
    content.innerHTML = '<div class="text-center py-8"><span class="animate-spin inline-block w-8 h-8 border-2 border-rose-600 border-t-transparent rounded-full"></span></div>';

    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=get_shopping_detail&id=${id}`);
        const json = await res.json();

        if (json.success) {
            const { header, items } = json.data;
            let html = `
                <div class="bg-gray-50 p-4 rounded-xl mb-4 border border-gray-100">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-500">No. Referensi</span>
                        <span class="font-mono font-bold text-gray-800">${header.nomor_referensi}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500">Tanggal</span>
                        <span class="font-medium text-gray-800">${formatDate(header.tanggal_penjualan)}</span>
                    </div>
                </div>
                <h4 class="font-bold text-gray-800 text-sm mb-3">Rincian Barang</h4>
                <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
            `;

            html += items.map(item => `
                <div class="flex justify-between items-center py-2 border-b border-gray-50 last:border-0">
                    <div>
                        <p class="text-sm font-medium text-gray-800 line-clamp-1">${item.nama_barang}</p>
                        <p class="text-xs text-gray-500">${item.quantity} x ${formatRupiah(item.price)}</p>
                    </div>
                    <p class="text-sm font-bold text-gray-800">${formatRupiah(item.subtotal)}</p>
                </div>
            `).join('');

            html += `
                </div>
                <div class="mt-4 pt-3 border-t border-gray-200 flex justify-between items-center">
                    <span class="font-bold text-gray-800">Total Bayar</span>
                    <span class="font-bold text-lg text-rose-600">${formatRupiah(header.total)}</span>
                </div>
            `;
            content.innerHTML = html;
        }
    } catch (e) {
        content.innerHTML = '<p class="text-center text-red-500 text-sm">Gagal memuat detail.</p>';
    }
}

// Expose functions to global scope
window.loadShoppingHistory = loadShoppingHistory;
window.addToCart = addToCart;
window.openCartModal = openCartModal;
window.updateQty = updateQty;
window.removeItem = removeItem;
window.showShoppingDetail = showShoppingDetail;

// --- Barcode Scanner Logic ---
let html5QrcodeScanner = null;

window.startBarcodeScanner = function() {
    const modal = document.getElementById('modal-scan-barcode');
    modal.classList.remove('hidden');

    if (html5QrcodeScanner) {
        // Scanner already running
        return;
    }

    // Delay sedikit agar modal tampil dulu baru kamera nyala
    setTimeout(() => {
        html5QrcodeScanner = new Html5Qrcode("reader");
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };
        
        html5QrcodeScanner.start({ facingMode: "environment" }, config, onScanSuccess)
        .catch(err => {
            console.error("Error starting scanner", err);
            Swal.fire('Error', 'Gagal mengakses kamera. Pastikan izin kamera diberikan.', 'error');
            stopBarcodeScanner();
        });
    }, 200);
}

function onScanSuccess(decodedText, decodedResult) {
    // Stop scanning immediately after success
    stopBarcodeScanner();
    
    const searchInput = document.getElementById('search-item-input');
    searchInput.value = decodedText;
    
    // Trigger search
    searchItems(decodedText);
    
    // Optional: Feedback visual/audio
    const audio = new Audio(`${basePath}/assets/audio/beep.mp3`); // Pastikan file ada atau hapus baris ini
    audio.play().catch(e => {}); 
}

window.stopBarcodeScanner = function() {
    const modal = document.getElementById('modal-scan-barcode');
    modal.classList.add('hidden');
    
    if (html5QrcodeScanner) {
        html5QrcodeScanner.stop().then(() => {
            html5QrcodeScanner.clear();
            html5QrcodeScanner = null;
        }).catch(err => console.error("Failed to stop scanner", err));
    }
}

window.openWishlistFromProfile = function() {
    switchTab('belanja');
    if (!isWishlistView) {
        toggleWishlistView();
    }
}
