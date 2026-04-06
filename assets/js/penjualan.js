function initPenjualanPage() {
    // Fix: Pastikan daftar saran produk muncul di atas elemen lain.
    const suggestionsContainer = document.getElementById('product-suggestions');
    if (suggestionsContainer) {
        const parent = suggestionsContainer.parentElement;
        // Parent dari suggestions harus memiliki posisi 'relative'
        // agar 'absolute' child diposisikan relatif terhadapnya.
        if (parent) {
            parent.classList.add('relative');
        }
        // Tambahkan kelas untuk positioning, z-index, dan styling.
        suggestionsContainer.classList.add(
            'absolute',      // Atur posisi absolut
            'w-full',        // Lebar penuh sesuai parent
            'top-full',      // Posisikan dropdown tepat di bawah elemen input.
            'mt-1',          // Beri sedikit jarak dari input field (sesuai saran Anda).
            'z-50',          // Gunakan z-index tertinggi untuk memastikan saran muncul di atas semua elemen lain.
            'bg-white',      // Beri warna latar
            'dark:bg-gray-800',
            'border',        // Tambahkan border
            'border-gray-200',
            'dark:border-gray-600',
            'rounded-b-md',  // Sudut bawah yang membulat
            'shadow-lg',     // Beri bayangan agar terlihat melayang
            'hidden'         // Sembunyikan secara default agar tidak menghalangi klik (Critical Fix)
        );
    }

    const searchProdukInput = document.getElementById('search-produk');
    const cartItemsContainer = document.getElementById('cart-items');
    const searchInput = document.getElementById('search-input');
    const tableBody = document.getElementById('penjualanTable').querySelector('tbody');
    const paginationContainer = document.getElementById('pagination');
    const paginationInfo = document.getElementById('pagination-info');
    const tanggalInput = document.getElementById('tanggal');
    
    // Element untuk Anggota & WB
    const memberSearchInput = document.getElementById('member_search');
    const memberSuggestions = document.getElementById('member-suggestions');
    const wbPaymentContainer = document.getElementById('wb-payment-container');
    const bayarWbInput = document.getElementById('bayar_wb');
    const memberInfoDiv = document.getElementById('member-info');
    const anggotaIdInput = document.getElementById('anggota_id');
    let currentMemberBalance = 0;

    let cart = [];
    let currentPage = 1;
    const limit = 10;
    let searchTimeout;

    // Use global appSettings provided by header.php for receipt printing
    const appSettings = window.appSettings || {};


    // Fungsi utilitas
    const formatRupiah = (angka) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    };

    const getPaymentMethodName = (method) => {
        const methods = {
            'cash': 'Tunai/Cash',
            'transfer': 'Transfer Bank',
            'potong_saldo': 'Saldo WB',
            'hutang': 'Hutang',
            'qris': 'QRIS'
        };
        return methods[method] || method;
    };

    // Inisialisasi Flatpickr untuk input tanggal di modal (Buat Baru)
    const tanggalPicker = flatpickr(tanggalInput, {
        dateFormat: "d-m-Y", // Format DD-MM-YYYY
        defaultDate: "today",
        allowInput: true // Memungkinkan input manual dari keyboard
    });

    // Inisialisasi Flatpickr untuk Filter Daftar Transaksi (Area Daftar)
    const filterStartDate = flatpickr("#filter-start-date", {
        dateFormat: "d-m-Y",
        defaultDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1), // Awal bulan ini
    });

    const filterEndDate = flatpickr("#filter-end-date", {
        dateFormat: "d-m-Y",
        defaultDate: "today",
    });

    // Fungsi untuk mencetak struk via window.print()
    const printStrukWindow = async (id) => {
        try {
            const response = await fetch(`${basePath}/api/penjualan?action=get_detail&id=${id}`);
            const result = await response.json();
            
            if (!result.success) {
                showToast('Gagal memuat data struk.', 'error');
                return;
            }
            
            const detail = result.data;
            const itemsHtml = detail.items.map(item => `
                <tr>
                    <td style="padding: 5px 0;">
                        ${item.deskripsi_item}<br>
                        <small>${item.quantity} x ${formatRupiah(item.price)}${item.discount > 0 ? ` (-${formatRupiah(item.discount)})` : ''}</small>
                    </td>
                    <td style="text-align: right; vertical-align: bottom;">${formatRupiah(item.subtotal)}</td>
                </tr>
            `).join('');

            const width = 350;
            const height = 600;
            const left = (screen.width - width) / 2;
            const top = (screen.height - height) / 2;

            const printWindow = window.open('', 'PrintStruk', `width=${width},height=${height},top=${top},left=${left}`);
            
            if (!printWindow) {
                showToast('Pop-up terblokir. Silakan izinkan pop-up untuk situs ini.', 'warning');
                return;
            }

            const htmlContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Struk #${detail.nomor_referensi}</title>
                    <style>
                        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; margin: 0; padding: 10px; }
                        .text-center { text-align: center; }
                        .text-end { text-align: right; }
                        .fw-bold { font-weight: bold; }
                        .mb-1 { margin-bottom: 5px; }
                        .border-bottom { border-bottom: 1px dashed #000; }
                        .border-top { border-top: 1px dashed #000; }
                        table { width: 100%; border-collapse: collapse; }
                        .items-table td { vertical-align: top; }
                        @media print { @page { margin: 0; } body { margin: 5px; } }
                    </style>
                </head>
                <body>
                    <div class="text-center mb-1">
                        <h3 style="margin: 0;">${(window.appSettings?.shop_name) || 'SMKN 5 TOKO'}</h3>
                        <div>${(window.appSettings?.shop_address) || 'Jl. Contoh No. 123'}</div>
                    </div>
                    <div class="border-bottom mb-1" style="padding-bottom: 5px;">
                        <div>No: ${detail.nomor_referensi}</div>
                        <div>Tgl: ${formatDateTime(detail.tanggal_penjualan)}</div>
                        <div>Kasir: ${detail.created_by_username}</div>
                        <div>Pelanggan: ${detail.customer_name}</div>
                        <div>Metode: ${getPaymentMethodName(detail.payment_method)}</div>
                    </div>
                    <table class="items-table mb-1">
                        ${itemsHtml}
                    </table>
                    <div class="border-top" style="padding-top: 5px;">
                        <table style="width: 100%">
                            ${detail.discount > 0 ? `<tr><td>Subtotal</td><td class="text-end">${formatRupiah(detail.subtotal)}</td></tr>` : ''}
                            ${detail.discount > 0 ? `<tr><td>Diskon</td><td class="text-end">-${formatRupiah(detail.discount)}</td></tr>` : ''}
                            <tr><td>Total</td><td class="text-end fw-bold">${formatRupiah(detail.total)}</td></tr>
                            <tr><td>Bayar</td><td class="text-end">${formatRupiah(detail.bayar)}</td></tr>
                            <tr><td>Kembali</td><td class="text-end">${formatRupiah(detail.kembali)}</td></tr>
                        </table>
                    </div>
                    <div class="text-center" style="margin-top: 20px;">
                        <p>${((window.appSettings?.receipt_footer) || 'Terima Kasih<br>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan').replace(/\n/g, '<br>')}</p>
                    </div>
                    <script>
                        window.onload = function() { 
                            window.print(); 
                            window.close(); 
                        }
                    </script>
                </body>
                </html>
            `;

            printWindow.document.open();
            printWindow.document.write(htmlContent);
            printWindow.document.close();

        } catch (error) {
            console.error(error);
            showToast('Gagal mencetak struk.', 'error');
        }
    };

    // Fungsi memuat data utama
    const loadPenjualan = async (page = 1) => {
        currentPage = page;
        const search = document.getElementById('search-input')?.value || '';
        
        // Format tanggal ke YYYY-MM-DD untuk API
        const formatDateAPI = (fp) => {
            if (!fp || !fp.selectedDates[0]) return '';
            const d = fp.selectedDates[0];
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        };

        const startDate = formatDateAPI(filterStartDate);
        const endDate = formatDateAPI(filterEndDate);

        try {
            const url = `${basePath}/api/penjualan?action=get_all&page=${page}&limit=${limit}&search=${encodeURIComponent(search)}&start_date=${startDate}&end_date=${endDate}`;
            const response = await fetch(url);
            const result = await response.json();

            if (result.success) {
                renderTable(result.data);
                if (result.pagination) {
                    renderPagination(paginationContainer, result.pagination, loadPenjualan);
                    
                    // Update pagination info
                    const info = document.getElementById('pagination-info');
                    if (info) {
                        const { total_records, current_page, limit } = result.pagination;
                        const start = total_records === 0 ? 0 : (current_page - 1) * limit + 1;
                        const end = Math.min(current_page * limit, total_records);
                        info.textContent = `Menampilkan ${start} - ${end} dari ${total_records} transaksi.`;
                    }
                }
            } else {
                showToast('Gagal memuat data: ' + result.message, 'danger');
            }
        } catch (error) {
            console.error('loadPenjualan error:', error);
            showToast('Terjadi kesalahan saat memuat data.', 'danger');
        }
    };

    // Event listener untuk tombol filter
    document.getElementById('btn-filter')?.addEventListener('click', () => {
        loadPenjualan(1);
    });

    // Trigger filter saat tekan Enter di input pencarian
    document.getElementById('search-input')?.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') {
            loadPenjualan(1);
        }
    });

    const renderTable = (data) => {
        tableBody.innerHTML = '';
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted p-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Tidak ada data penjualan ditemukan.</td></tr>';
            return;
        }
        data.forEach(item => {
            const isVoid = item.status === 'void';
            const rowClass = isVoid ? 'table-secondary text-muted' : '';
            const textDecoration = isVoid ? 'text-decoration-line-through' : '';
            
            const row = `
                <tr class="${rowClass} align-middle">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="mr-2 text-primary"><i class="bi bi-receipt"></i></div>
                            <div>
                                <span class="font-bold ${textDecoration}">${item.nomor_referensi}</span>
                                ${isVoid ? '<span class="ml-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">BATAL</span>' : ''}
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap"><i class="bi bi-calendar-event mr-1 text-gray-400"></i> <span class="${textDecoration}">${formatDateTime(item.tanggal_penjualan)}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap"><i class="bi bi-person mr-1 text-gray-400"></i> <span class="${textDecoration}">${item.customer_name}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <span class="font-bold ${isVoid ? 'text-gray-500' : 'text-green-600'} ${textDecoration}">${formatRupiah(item.total)}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap"><i class="bi bi-person-badge mr-1 text-gray-400"></i> <span class="${textDecoration}">${item.username}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <div class="inline-flex rounded-md shadow-sm">
                        <button class="px-2 py-1 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-l-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 btn-detail" data-id="${item.id}" title="Lihat Detail">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="px-2 py-1 border-t border-b border-gray-300 dark:border-gray-600 text-sm font-medium text-blue-600 dark:text-blue-400 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 btn-edit" data-id="${item.id}" title="Edit Transaksi" ${isVoid ? 'disabled' : ''}>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="px-2 py-1 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-r-md text-red-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 btn-void" data-id="${item.id}" title="Batalkan Transaksi" ${isVoid ? 'disabled' : ''}>
                            <i class="bi bi-x-circle"></i>
                        </button>
                        </div>
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', row);
        });
    };

    // Pengaman untuk mencegah double trigger (Qty 2)
    let lastAddedId = null;
    let lastAddedTime = 0;

    // Fungsi untuk keranjang (cart)
    const addItemToCart = (product) => {
        if (!product) return;
        
        // Anti-double trigger: Abaikan jika ID yang sama masuk dalam < 300ms
        const now = Date.now();
        if (product.id === lastAddedId && (now - lastAddedTime) < 300) {
            return;
        }
        lastAddedId = product.id;
        lastAddedTime = now;

        if (product.stok <= 0) {
            showToast('Stok produk habis.', 'warning');
            return;
        }

        // Segera bersihkan saran untuk mencegah klik ganda/Enter ganda
        const suggestionsContainer = document.getElementById('product-suggestions');
        if (suggestionsContainer) {
            suggestionsContainer.innerHTML = '';
            suggestionsContainer.classList.add('hidden');
        }
        searchProdukInput.value = '';

        const existingItem = cart.find(item => item.id === product.id);
        if (existingItem) {
            if (existingItem.qty < existingItem.stok) {
                existingItem.qty++;
            } else {
                showToast('Stok tidak mencukupi.', 'warning');
            }
        } else {
            cart.push({ ...product, qty: 1 });
        }

        renderCart();
        searchProdukInput.focus();
    };

    const renderCart = () => {
        cartItemsContainer.innerHTML = '';
        cart.forEach((item, index) => {
            const price = parseFloat(item.harga_jual) || 0;
            const qty = parseInt(item.qty) || 0;
            const discount = parseFloat(item.discount) || 0;
            const subtotal = (price * qty) - discount;

            const row = `
                <tr data-index="${index}" class="align-middle text-sm">
                    <td class="px-4 py-2">
                        <div class="font-bold text-gray-800 dark:text-gray-200">${item.nama_barang}</div>
                        <small class="text-gray-500">${item.kode_barang || ''}</small>
                    </td>
                    <td class="px-4 py-2 text-right">${formatRupiah(item.harga_jual)}</td>
                    <td class="px-4 py-2 w-36">
                        <div class="flex items-center">
                            <button class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-l-md btn-qty-dec" type="button"><i class="bi bi-dash"></i></button>
                            <input type="number" class="w-12 text-center border-t border-b border-gray-300 dark:border-gray-600 dark:bg-gray-700 qty-input" value="${item.qty}" min="1" max="${item.stok}">
                            <button class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-r-md btn-qty-inc" type="button"><i class="bi bi-plus"></i></button>
                        </div>
                    </td>
                    <td class="px-4 py-2 w-32">
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">Rp</div>
                            <input type="number" class="w-full pl-8 text-right rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 discount-input" value="${item.discount || 0}" min="0" placeholder="0">
                        </div>
                    </td>
                    <td class="px-4 py-2 subtotal text-right font-bold text-primary">${formatRupiah(subtotal)}</td>
                    <td class="px-4 py-2 text-center"><button type="button" class="text-red-500 hover:text-red-700 remove-item-btn" title="Hapus"><i class="bi bi-trash"></i></button></td>
                </tr>
            `;
            cartItemsContainer.insertAdjacentHTML('beforeend', row);
        });
        updateSummary();
        document.getElementById('product-suggestions')?.classList.add('hidden'); // Sembunyikan saran setelah render
    };

    const updateSummary = () => {
        const subtotal = cart.reduce((sum, item) => sum + (item.harga_jual * item.qty), 0);
        const itemDiscounts = cart.reduce((sum, item) => sum + (parseFloat(item.discount) || 0), 0);
        const totalDiscountInput = parseFloat(document.getElementById('discount_total')?.value) || 0;
        const totalDiscount = itemDiscounts + totalDiscountInput;
        const total = subtotal - totalDiscount;
        
        const bayarWb = parseFloat(bayarWbInput?.value) || 0;
        
        // Auto-fill bayar jika metode pembayaran bukan tunai (Transfer/QRIS)
        const paymentMethod = document.getElementById('payment_method');
        const bayarInput = document.getElementById('bayar');
        if (paymentMethod && bayarInput && (paymentMethod.value === 'transfer' || paymentMethod.value === 'qris')) {
            bayarInput.value = Math.max(0, total - bayarWb);
        }

        const bayar = parseFloat(bayarInput.value) || 0;
        const kembali = (bayar + bayarWb) - total;

        document.getElementById('subtotal').textContent = formatRupiah(subtotal);
        document.getElementById('total').textContent = formatRupiah(total);
        document.getElementById('kembali').textContent = formatRupiah(kembali >= 0 ? kembali : 0);
    };

    // Event Listeners
    document.getElementById('btn-tambah-penjualan').addEventListener('click', () => {
        document.getElementById('form-penjualan').reset();
        document.getElementById('penjualan_id').value = '';
        document.getElementById('penjualanModalLabel').textContent = 'Transaksi Penjualan Baru';
        tanggalPicker.setDate(new Date()); // Reset tanggal ke hari ini menggunakan API Flatpickr
        
        // Reset Member Info
        anggotaIdInput.value = '';
        wbPaymentContainer.classList.add('hidden');
        memberInfoDiv.classList.add('hidden');
        currentMemberBalance = 0;
        
        cart = [];
        renderCart();
        document.getElementById('product-suggestions')?.classList.add('hidden');
        openModal('penjualanModal');
    });

    // --- Event Listeners for Payment Section ---
    // Load bank accounts for payment method
    fetch(`${basePath}/api/settings?action=get_cash_accounts`)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                const accSelect = document.getElementById('payment_account_id');
                if(accSelect) res.data.forEach(acc => accSelect.add(new Option(acc.nama_akun, acc.id)));
            }
        });

    // Handle payment method change
    document.getElementById('payment_method')?.addEventListener('change', (e) => {
        const method = e.target.value;
        const isTransferOrQris = method === 'transfer' || method === 'qris';
        
        document.getElementById('account-select-container').classList.toggle('hidden', !isTransferOrQris);
        const accSelect = document.getElementById('payment_account_id');
        accSelect.required = isTransferOrQris;
        if (!isTransferOrQris) accSelect.value = '';
        
        if (method === 'hutang') {
            document.getElementById('bayar').value = 0;
            document.getElementById('bayar').placeholder = "DP (Opsional)";
        }
        
        updateSummary(); // Update summary to auto-fill amount if non-cash
    });

    // Handle "Uang Pas" button
    document.getElementById('btn-uang-pas')?.addEventListener('click', () => {
        const paymentMethod = document.getElementById('payment_method')?.value;
        if (paymentMethod === 'hutang') {
            showToast('Tombol Uang Pas tidak tersedia untuk metode Hutang.', 'info');
            return;
        }
        const totalText = document.getElementById('total').textContent;
        const bayarWb = parseFloat(bayarWbInput?.value) || 0;
        // Extract number from "Rp 123.456"
        const totalValue = parseFloat(totalText.replace(/[^0-9,-]+/g,"").replace(",", "."));
        const sisaTagihan = Math.max(0, totalValue - bayarWb);
        
        const bayarInput = document.getElementById('bayar');
        if (bayarInput && !isNaN(totalValue)) {
            bayarInput.value = sisaTagihan;
            updateSummary(); // Recalculate change
        }
    });

    // Pagination handled by global renderPagination

    const debounce = (func, delay) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    };

    const handleProductSearch = async (e) => {
        // Jangan jalankan pencarian jika tombol navigasi ditekan
        if (['ArrowUp', 'ArrowDown', 'Enter', 'Escape'].includes(e.key)) {
            return;
        }

        const term = e.target.value;
        const suggestionsContainer = document.getElementById('product-suggestions');
        if (term.length < 2) {
            suggestionsContainer.innerHTML = '';
            return;
        }

        try {
            const response = await fetch(`${basePath}/api/penjualan?action=search_produk&term=${term}`);
            const result = await response.json();
            
            suggestionsContainer.innerHTML = '';

            let products = [];
            // Handle both plain array response and object response for success cases
            if (Array.isArray(result)) {
                products = result;
            } else if (result && result.success && Array.isArray(result.data)) {
                products = result.data;
            } else {
                console.error("API Error on product search:", result.message || "Unexpected API response format");
                return;
            }

            if (products && products.length > 0) {
                const list = products.map(p => {
                    const isConsignment = p.item_type === 'consignment';
                    const badgeColor = isConsignment ? 'purple' : (p.stok > 10 ? 'green' : 'yellow');
                    const badgeText = isConsignment ? 'Konsinyasi' : `Stok: ${p.stok}`;
                    
                    return `<a href="#" class="flex justify-between items-center p-3 hover:bg-gray-100 dark:hover:bg-gray-600 border-b border-gray-200 dark:border-gray-600" data-product='${JSON.stringify(p)}'>
                        <div>
                            <div class="font-bold">
                                <i class="bi bi-${isConsignment ? 'handbag' : 'box-seam'} mr-2 text-${isConsignment ? 'purple' : 'primary'}-500"></i>
                                ${p.nama_barang}
                            </div>
                            <small class="text-gray-500">
                                <i class="bi bi-upc-scan mr-1"></i>${p.barcode || p.sku || '-'} 
                                ${p.sku && p.barcode ? ' | SKU: ' + p.sku : ''} | ${formatRupiah(p.harga_jual)}
                            </small>
                        </div>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-${badgeColor}-100 text-${badgeColor}-800">
                            ${badgeText} ${isConsignment ? '(Sisa: ' + p.stok + ')' : ''}
                        </span>
                    </a>`;
                }).join('');
                suggestionsContainer.innerHTML = list;
                suggestionsContainer.classList.remove('hidden');
            } else {
                suggestionsContainer.innerHTML = '<div class="p-3 text-gray-500 text-center">Produk tidak ditemukan.</div>';
                suggestionsContainer.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error fetching search results:', error);
        }
    };

    // Event Delegation untuk klik pada saran produk (Critical Fix: Mencegah double trigger)
    document.getElementById('product-suggestions')?.addEventListener('click', (e) => {
        const item = e.target.closest('a');
        if (item) {
            e.preventDefault();
            e.stopPropagation();
            try {
                const p = JSON.parse(item.dataset.product);
                addItemToCart(p);
            } catch (err) {
                console.error("Gagal parse data produk:", err);
            }
        }
    });

    searchProdukInput.addEventListener('keyup', debounce(handleProductSearch, 400));

    // Tambahkan event listener untuk tombol Enter pada pencarian produk
    // dan navigasi atas/bawah
    searchProdukInput.addEventListener('keydown', async (e) => {
        const suggestionsContainer = document.getElementById('product-suggestions');
        const suggestions = Array.from(suggestionsContainer.querySelectorAll('a'));
        const activeSuggestion = suggestionsContainer.querySelector('.active-suggestion');
        let currentIndex = activeSuggestion ? suggestions.indexOf(activeSuggestion) : -1;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                if (suggestions.length === 0) return;
                if (activeSuggestion) activeSuggestion.classList.remove('active-suggestion', 'bg-blue-100', 'dark:bg-gray-700');
                currentIndex = (currentIndex + 1) % suggestions.length;
                suggestions[currentIndex].classList.add('active-suggestion', 'bg-blue-100', 'dark:bg-gray-700');
                suggestions[currentIndex].scrollIntoView({ block: 'nearest', inline: 'start' });
                break;

            case 'ArrowUp':
                e.preventDefault();
                if (suggestions.length === 0) return;
                if (activeSuggestion) activeSuggestion.classList.remove('active-suggestion', 'bg-blue-100', 'dark:bg-gray-700');
                currentIndex = (currentIndex - 1 + suggestions.length) % suggestions.length;
                suggestions[currentIndex].classList.add('active-suggestion', 'bg-blue-100', 'dark:bg-gray-700');
                suggestions[currentIndex].scrollIntoView({ block: 'nearest', inline: 'start' });
                break;

            case 'Enter':
                e.preventDefault();
                const term = e.target.value.trim();
                if (term === '') {
                    // Jika tekan Enter saat input kosong, pindah ke kolom bayar
                    if (cart.length > 0) {
                        document.getElementById('bayar').focus();
                    } else {
                        showToast('Keranjang masih kosong.', 'warning');
                    }
                    return;
                }

                // Coba cari kecocokan eksak dari hasil yang sudah ada atau trigger pencarian cepat
                // (Menggunakan variabel suggestions yang sudah dideklarasikan di awal switch)
                let targetProduct = null;

                // 1. Cek apakah ada yang di-highlight (navigasi keyboard)
                // (Menggunakan variabel activeSuggestion yang sudah dideklarasikan di awal switch)
                if (activeSuggestion) {
                    targetProduct = JSON.parse(activeSuggestion.dataset.product);
                } 
                // 2. Cek apakah input pas dengan salah satu SKU (Barcode match)
                else if (suggestions.length > 0) {
                    const exactMatch = suggestions.find(s => {
                        const p = JSON.parse(s.dataset.product);
                        return (p.barcode && p.barcode === term) || (p.sku && p.sku.toLowerCase() === term.toLowerCase());
                    });
                    if (exactMatch) {
                        targetProduct = JSON.parse(exactMatch.dataset.product);
                    } else if (suggestions.length === 1) {
                        // Jika hanya ada 1 hasil, anggap itu yang dimaksud
                        targetProduct = JSON.parse(suggestions[0].dataset.product);
                    } else {
                        // Default: Pilih yang pertama jika banyak hasil
                        targetProduct = JSON.parse(suggestions[0].dataset.product);
                    }
                }

                if (targetProduct) {
                    addItemToCart(targetProduct);
                } else if (term.length >= 2) {
                    // Jika belum ada hasil (mungkin delay network), coba fetch sekali lagi secara sinkron/cepat
                    const response = await fetch(`${basePath}/api/penjualan?action=search_produk&term=${term}`);
                    const products = await response.json();
                    if (products && products.length > 0) {
                        // Prioritaskan yang Barcode atau SKU-nya pas
                        const match = products.find(p => (p.barcode && p.barcode === term) || (p.sku && p.sku.toLowerCase() === term.toLowerCase())) || products[0];
                        addItemToCart(match);
                    } else {
                        showToast('Barang tidak ditemukan.', 'warning');
                    }
                }
                break;
        }
    });

    document.getElementById('product-suggestions').addEventListener('click', (e) => {
        e.preventDefault();
        const link = e.target.closest('a');
        if (link) {
            const product = JSON.parse(link.dataset.product);
            addItemToCart(product);
        }
    });

    cartItemsContainer.addEventListener('input', (e) => {
        if (e.target.classList.contains('qty-input')) {
            const tr = e.target.closest('tr');
            const index = tr.dataset.index;
            let newQty = parseInt(e.target.value);
            if (isNaN(newQty) || newQty < 1) newQty = 1;
            
            if (newQty > cart[index].stok) {
                newQty = cart[index].stok;
                e.target.value = newQty;
                showToast('Stok tidak mencukupi.', 'warning');
            }
            cart[index].qty = newQty;

            // Update subtotal row secara manual agar fokus input tidak hilang
            const subtotal = (cart[index].harga_jual * cart[index].qty) - (cart[index].discount || 0);
            tr.querySelector('.subtotal').textContent = formatRupiah(subtotal);

            updateSummary(); // Cukup update summary agar input tidak kehilangan fokus
        }
        if (e.target.classList.contains('discount-input')) {
            const tr = e.target.closest('tr');
            const index = tr.dataset.index;
            let newDiscount = parseFloat(e.target.value) || 0;
            const maxDiscount = cart[index].harga_jual * cart[index].qty;
            if (newDiscount > maxDiscount) {
                newDiscount = maxDiscount;
                e.target.value = newDiscount;
            }
            cart[index].discount = newDiscount;

            // Update subtotal row secara manual
            const subtotal = (cart[index].harga_jual * cart[index].qty) - (cart[index].discount || 0);
            tr.querySelector('.subtotal').textContent = formatRupiah(subtotal);

            updateSummary(); // Cukup update summary
        }
    });

    cartItemsContainer.addEventListener('click', (e) => {
        const target = e.target.closest('button');
        if (!target) return;

        const tr = target.closest('tr');
        const index = tr.dataset.index;

        if (target.classList.contains('remove-item-btn')) {
            cart.splice(index, 1);
            renderCart();
        } else if (target.classList.contains('btn-qty-inc')) {
            if (cart[index].qty < cart[index].stok) {
                cart[index].qty++;
                renderCart();
            } else {
                showToast('Stok maksimal tercapai.', 'warning');
            }
        } else if (target.classList.contains('btn-qty-dec')) {
            if (cart[index].qty > 1) {
                cart[index].qty--;
                renderCart();
            }
        }
    });

    // --- Member Search Logic ---
    memberSearchInput?.addEventListener('input', async (e) => {
        const term = e.target.value;
        if (term.length < 2) {
            memberSuggestions.classList.add('hidden');
            return;
        }

        try {
            const response = await fetch(`${basePath}/api/penjualan?action=search_member&term=${term}`);
            const result = await response.json();
            
            if (result.success && result.data.length > 0) {
                memberSuggestions.innerHTML = result.data.map(m => `
                    <div class="p-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-100 dark:border-gray-600" 
                         data-id="${m.id}" data-nama="${m.nama_lengkap}" data-saldo="${m.saldo_wajib_belanja}">
                        <div class="font-bold text-sm">${m.nama_lengkap}</div>
                        <div class="text-xs text-gray-500">No: ${m.nomor_anggota} | Saldo WB: ${formatRupiah(m.saldo_wajib_belanja)}</div>
                    </div>
                `).join('');
                memberSuggestions.classList.remove('hidden');
            } else {
                memberSuggestions.classList.add('hidden');
            }
        } catch (err) {
            console.error(err);
        }
    });

    memberSuggestions?.addEventListener('click', (e) => {
        const item = e.target.closest('div[data-id]');
        if (item) {
            const id = item.dataset.id;
            const nama = item.dataset.nama;
            const saldo = parseFloat(item.dataset.saldo);

            memberSearchInput.value = nama;
            anggotaIdInput.value = id;
            currentMemberBalance = saldo;
            
            memberInfoDiv.textContent = `Saldo Wajib Belanja: ${formatRupiah(saldo)}`;
            memberInfoDiv.classList.remove('hidden');
            
            if (saldo > 0) {
                wbPaymentContainer.classList.remove('hidden');
            } else {
                wbPaymentContainer.classList.add('hidden');
            }
            
            memberSuggestions.classList.add('hidden');
            updateSummary();
        }
    });
    
    bayarWbInput?.addEventListener('input', updateSummary);

    // --- Navigasi Keyboard (Enter) untuk UX yang lebih cepat ---
    // Diatur di dalam event listener keydown di atas untuk searchProdukInput
    // -----------------------------------------------------------

    document.getElementById('discount_total').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('bayar').focus();
        }
    });

    document.getElementById('catatan').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('bayar').focus();
        }
    });

    document.getElementById('bayar').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('btn-simpan-penjualan').click();
        }
    });
    // -----------------------------------------------------------

    document.getElementById('bayar').addEventListener('input', updateSummary);
    document.getElementById('discount_total').addEventListener('input', updateSummary);

    document.getElementById('btn-simpan-penjualan').addEventListener('click', async () => {
        const id = document.getElementById('penjualan_id').value;
        const subtotal = cart.reduce((sum, item) => sum + (parseFloat(item.harga_jual) * parseInt(item.qty)), 0);
        const totalHPP = cart.reduce((sum, item) => sum + (parseFloat(item.harga_beli || 0) * parseInt(item.qty)), 0);
        const itemDiscounts = cart.reduce((sum, item) => sum + (parseFloat(item.discount) || 0), 0);
        const totalDiscountInput = parseFloat(document.getElementById('discount_total').value) || 0;
        const totalDiscount = itemDiscounts + totalDiscountInput;
        const total = subtotal - totalDiscount;
        const bayar = parseFloat(document.getElementById('bayar').value) || 0;
        const bayarWb = parseFloat(document.getElementById('bayar_wb')?.value) || 0;

        // Validasi tambahan untuk non-tunai
        const paymentMethod = document.getElementById('payment_method')?.value || 'cash';
        const paymentAccountId = document.getElementById('payment_account_id')?.value;

        if ((paymentMethod === 'transfer' || paymentMethod === 'qris') && !paymentAccountId) {
            showToast('Harap pilih Akun Tujuan untuk pembayaran non-tunai.', 'warning');
            return;
        }
        if (cart.length === 0) {
            showToast('Keranjang belanja masih kosong.', 'warning');
            return;
        }
        if (paymentMethod !== 'hutang' && (bayar + bayarWb) < total) {
            showToast('Jumlah bayar kurang dari total.', 'warning');
            return;
        }
        
        if (bayarWb > currentMemberBalance) {
            showToast('Saldo Wajib Belanja tidak mencukupi.', 'error');
            return;
        }

        // Helper function untuk mengubah format tanggal menjadi YYYY-MM-DD yang dibutuhkan oleh database
        const formatDateForDB = (date) => {
            // Jika tidak ada tanggal yang dipilih, gunakan tanggal hari ini sebagai fallback
            if (!date) return new Date().toISOString().slice(0, 10);
            const d = new Date(date);
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };
        const formData = {
            id: id || null,
            tanggal: formatDateForDB(tanggalPicker.selectedDates[0]),
            customer_name: document.getElementById('member_search').value || 'Umum',
            anggota_id: document.getElementById('anggota_id').value || null,
            subtotal: subtotal,
            discount: totalDiscount,
            total: total,
            total_hpp: totalHPP, // Kirim total HPP untuk jurnal (Debit HPP, Kredit Persediaan)
            bayar: bayar,
            bayar_wb: bayarWb,
            kembali: (bayar + bayarWb) - total,
            catatan: document.getElementById('catatan').value || '', // Ambil nilai dari input catatan
            payment_method: paymentMethod,
            payment_account_id: paymentAccountId,
            items: cart.map(item => ({
                id: item.id,
                nama: item.nama_barang,
                harga: parseFloat(item.harga_jual),
                harga_beli: parseFloat(item.harga_beli || 0), // Penting untuk jurnal HPP agar balance
                qty: parseInt(item.qty),
                discount: parseFloat(item.discount) || 0,
                item_type: item.item_type || 'normal',
                subtotal: (parseFloat(item.harga_jual) * parseInt(item.qty)) - (parseFloat(item.discount) || 0)
            }))
        };

        try {
            const action = id ? 'update' : 'store';
            const response = await fetch(`${basePath}/api/penjualan?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const result = await response.json();
            if (result.success) {
                closeModal('penjualanModal');
                
                // Gunakan SweetAlert untuk konfirmasi sukses yang lebih modern
                Swal.fire({
                    title: 'Transaksi Berhasil!',
                    html: `Total: <b>${formatRupiah(total)}</b><br>Kembali: <b>${formatRupiah((bayar + bayarWb) - total)}</b>`,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-printer"></i> Cetak Struk',
                    cancelButtonText: 'Transaksi Baru',
                    reverseButtons: true
                }).then((res) => {
                    // Cetak struk jika user menekan tombol Cetak Struk
                    if (res.isConfirmed && result.id) {
                        printStrukWindow(result.id);
                    }

                    // Otomatis reset form dan buka kembali modal untuk transaksi berikutnya
                    document.getElementById('form-penjualan').reset();
                    tanggalPicker.setDate(new Date()); // Reset tanggal ke hari ini
                    cart = [];
                    renderCart(); // Bersihkan tampilan keranjang
                    
                    openModal('penjualanModal'); // Buka kembali modal
                    setTimeout(() => {
                        searchProdukInput.focus(); // Fokus langsung ke cari barang
                    }, 500); // Delay sedikit agar modal siap sepenuhnya
                });

                loadPenjualan(1); 
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('Terjadi kesalahan: ' + error, 'danger');
        }
    });

    tableBody.addEventListener('click', async (e) => {
        const voidBtn = e.target.closest('.btn-void');
        if (voidBtn) {
            const id = voidBtn.dataset.id;
            
            Swal.fire({
                title: 'Anda yakin?',
                text: "Transaksi ini akan dibatalkan! Stok akan dikembalikan dan jurnal pembalik akan dibuat. Aksi ini tidak dapat diurungkan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, batalkan!',
                cancelButtonText: 'Tidak'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch(`${basePath}/api/penjualan?action=void`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: id })
                        });
                        const res = await response.json();
                        // Ganti handleApiResponse dengan showToast dan reload tabel
                        if (res.success) {
                            showToast(res.message, 'success');
                            loadPenjualan(currentPage, searchInput.value); // Muat ulang halaman saat ini
                        } else {
                            showToast(res.message, 'danger');
                        }
                    } catch (error) {
                        showToast('Terjadi kesalahan: ' . error, 'danger');
                    }
                }
            });
        }
    });

    tableBody.addEventListener('click', async (e) => {
        const detailBtn = e.target.closest('.btn-detail');
        if (detailBtn) {
            const id = detailBtn.dataset.id;
            const response = await fetch(`${basePath}/api/penjualan?action=get_detail&id=${id}`);
            const result = await response.json();

            if (result.success) {
                const detail = result.data;
                const itemsHtml = detail.items.map(item => `
                    <tr class="text-sm text-gray-800 dark:text-gray-300">
                        <td class="px-4 py-2">
                           ${item.deskripsi_item}
                           ${item.discount > 0 ? `<div class="text-xs text-red-500">Potongan: -${formatRupiah(item.discount)}</div>` : ''}
                        </td>
                        <td class="px-4 py-2 text-center">${item.quantity}</td>
                        <td class="px-4 py-2 text-right">${formatRupiah(item.price)}</td>
                        <td class="px-4 py-2 text-right font-medium">${formatRupiah(item.subtotal)}</td>
                    </tr>
                `).join('');

                const detailContent = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <div class="text-gray-500 text-sm">No. Faktur</div>
                            <div class="font-bold text-primary"><i class="bi bi-receipt mr-1"></i>${detail.nomor_referensi}</div>
                        </div>
                        <div class="md:text-right">
                            <div class="text-gray-500 text-sm">Tanggal</div>
                            <div class="font-bold"><i class="bi bi-calendar3 mr-1"></i>${new Date(detail.tanggal_penjualan).toLocaleString('id-ID')}</div>
                        </div>
                        <div>
                            <div class="text-gray-500 text-sm">Pelanggan</div>
                            <div class="font-bold"><i class="bi bi-person mr-1"></i>${detail.customer_name}</div>
                        </div>
                        <div class="md:text-right">
                            <div class="text-gray-500 text-sm">Kasir</div>
                            <div class="font-bold"><i class="bi bi-person-badge mr-1"></i>${detail.created_by_username}</div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto mb-4 border border-gray-200 dark:border-gray-700 rounded-md">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Barang</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Qty</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">${itemsHtml}</tbody>
                        </table>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <div class="space-y-2">
                            ${detail.discount > 0 ? `
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                <span>Subtotal</span>
                                <span>${formatRupiah(detail.subtotal)}</span>
                            </div>
                            <div class="flex justify-between text-sm text-red-500">
                                <span>Diskon Global</span>
                                <span>-${formatRupiah(detail.discount)}</span>
                            </div>` : ''}
                            <div class="flex justify-between">
                                <span>Total Tagihan</span>
                                <span class="font-bold text-lg text-primary">${formatRupiah(detail.total)}</span>
                            </div>
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span>Bayar</span>
                                <span>${formatRupiah(detail.bayar)}</span>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-600 my-2"></div>
                            <div class="flex justify-between">
                                <span class="font-bold">Kembali</span>
                                <span class="font-bold text-green-600">${formatRupiah(detail.kembali)}</span>
                            </div>
                        </div>
                    </div>
                `;
                document.getElementById('detailModalBody').innerHTML = detailContent;

                // Set ID untuk tombol cetak struk
                const cetakBtn = document.getElementById('btn-cetak-struk');
                if (cetakBtn) {
                    cetakBtn.dataset.id = id;
                }

                openModal('detailModal');
            } else {
                showToast(result.message, 'danger');
            }
        }
    });

    // Event listener untuk tombol cetak struk di dalam modal detail
    document.getElementById('detailModal').addEventListener('click', (e) => {
        const cetakBtn = e.target.closest('#btn-cetak-struk');
        if (cetakBtn) {
            const id = cetakBtn.dataset.id;
            if (id) {
                printStrukWindow(id);
            }
        }
    });

    // Event listener untuk tombol Edit di tabel
    tableBody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.btn-edit');
        if (!editBtn) return;

        const id = editBtn.dataset.id;
        try {
            const response = await fetch(`${basePath}/api/penjualan?action=get_detail&id=${id}`);
            const result = await response.json();

            if (result.success) {
                const detail = result.data;
                
                // Set Modal State
                document.getElementById('form-penjualan').reset();
                document.getElementById('penjualan_id').value = id;
                document.getElementById('penjualanModalLabel').textContent = 'Edit Transaksi #' + detail.nomor_referensi;
                
                // Populate Headers
                tanggalPicker.setDate(detail.tanggal_penjualan);
                document.getElementById('member_search').value = detail.customer_name;
                document.getElementById('anggota_id').value = detail.customer_id;
                
                // Fallback jika payment_method korup (0 atau kosong)
                const paymentMethod = (detail.payment_method === '0' || !detail.payment_method) ? 'cash' : detail.payment_method;
                document.getElementById('payment_method').value = paymentMethod;
                
                document.getElementById('catatan').value = detail.keterangan;
                document.getElementById('discount_total').value = detail.discount;
                document.getElementById('bayar').value = detail.bayar;
                
                // Check if it's transfer/qris to show account selector
                if (paymentMethod === 'transfer' || paymentMethod === 'qris') {
                    document.getElementById('account-select-container').classList.remove('hidden');
                    // We need to ensure accounts are loaded before setting the value
                    const accountSelect = document.getElementById('payment_account_id');
                    if (accountSelect.options.length <= 1) {
                        // If not loaded, load them then set value
                        loadPaymentAccounts().then(() => {
                            accountSelect.value = detail.payment_account_id || '';
                        });
                    } else {
                        accountSelect.value = detail.payment_account_id || '';
                    }
                } else {
                    document.getElementById('account-select-container').classList.add('hidden');
                }

                // If customer is member, show member info
                if (detail.customer_id) {
                    memberInfoDiv.textContent = `Pelanggan: ${detail.customer_name}`;
                    memberInfoDiv.classList.remove('hidden');
                } else {
                    memberInfoDiv.classList.add('hidden');
                }

                // Populate Cart
                cart = detail.items.map(item => ({
                    id: item.item_id,
                    nama_barang: item.nama_barang,
                    harga_jual: item.price,
                    harga_beli: item.harga_beli, // Important for COGS calculation on update
                    qty: item.quantity,
                    discount: item.discount,
                    item_type: item.item_type || 'normal',
                    stok: 999999 // Assume plenty for edit mode, server will validate anyway
                }));

                renderCart();
                openModal('penjualanModal');
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            console.error(error);
            showToast('Gagal memuat detail transaksi.', 'danger');
        }
    });

    // Muat data saat halaman pertama kali dibuka
    loadPenjualan();
}