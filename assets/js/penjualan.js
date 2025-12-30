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
            'shadow-lg'      // Beri bayangan agar terlihat melayang
        );
    }

    const searchProdukInput = document.getElementById('search-produk');
    const cartItemsContainer = document.getElementById('cart-items');
    const searchInput = document.getElementById('search-input');
    const tableBody = document.getElementById('penjualanTable').querySelector('tbody');
    const paginationContainer = document.getElementById('pagination');
    const paginationInfo = document.getElementById('pagination-info');
    const tanggalInput = document.getElementById('tanggal');

    let cart = [];
    let currentPage = 1;
    const limit = 10;
    let searchTimeout;

    // Fungsi utilitas
    const formatRupiah = (angka) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    };

    // Inisialisasi Flatpickr untuk input tanggal
    const tanggalPicker = flatpickr(tanggalInput, {
        dateFormat: "d-m-Y", // Format DD-MM-YYYY
        defaultDate: "today",
        allowInput: true // Memungkinkan input manual dari keyboard
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
                        <small>${item.quantity} x ${formatRupiah(item.price)}</small>
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
                        <h3 style="margin: 0;">SMKN 5 TOKO</h3>
                        <div>Jl. Contoh No. 123</div>
                    </div>
                    <div class="border-bottom mb-1" style="padding-bottom: 5px;">
                        <div>No: ${detail.nomor_referensi}</div>
                        <div>Tgl: ${formatDate(detail.tanggal_penjualan)}</div>
                        <div>Kasir: ${detail.created_by_username}</div>
                        <div>Pelanggan: ${detail.customer_name}</div>
                    </div>
                    <table class="items-table mb-1">
                        ${itemsHtml}
                    </table>
                    <div class="border-top" style="padding-top: 5px;">
                        <table style="width: 100%">
                            <tr><td>Total</td><td class="text-end fw-bold">${formatRupiah(detail.total)}</td></tr>
                            <tr><td>Bayar</td><td class="text-end">${formatRupiah(detail.bayar)}</td></tr>
                            <tr><td>Kembali</td><td class="text-end">${formatRupiah(detail.kembali)}</td></tr>
                        </table>
                    </div>
                    <div class="text-center" style="margin-top: 20px;">
                        <p>Terima Kasih<br>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</p>
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
    const loadPenjualan = async (page = 1, search = '') => {
        currentPage = page;
        try {
            const response = await fetch(`${basePath}/api/penjualan?action=get_all&page=${page}&limit=${limit}&search=${search}`);
            const result = await response.json();

            if (result.success) {
                renderTable(result.data);
                renderPagination(paginationContainer, result.pagination, loadPenjualan);
            } else {
                showToast('Gagal memuat data: ' + result.message, 'danger');
            }
        } catch (error) {
            showToast('Terjadi kesalahan: ' + error, 'danger');
        }
    };

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
                    <td class="px-6 py-4 whitespace-nowrap"><i class="bi bi-calendar-event mr-1 text-gray-400"></i> <span class="${textDecoration}">${formatDate(item.tanggal_penjualan)}</span></td>
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
                        <button class="px-2 py-1 border-t border-b border-r border-gray-300 dark:border-gray-600 text-sm font-medium rounded-r-md text-red-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 btn-void" data-id="${item.id}" title="Batalkan Transaksi" ${isVoid ? 'disabled' : ''}>
                            <i class="bi bi-x-circle"></i>
                        </button>
                        </div>
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', row);
        });
    };

    // Fungsi untuk keranjang (cart)
    const addItemToCart = (product) => {
        if (product.stok <= 0) {
            showToast('Stok produk habis.', 'warning');
            return;
        }
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
        searchProdukInput.value = '';
        document.getElementById('product-suggestions').innerHTML = '';
        searchProdukInput.focus(); // Kembalikan fokus ke pencarian agar bisa langsung scan barang berikutnya
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
    };

    const updateSummary = () => {
        const subtotal = cart.reduce((sum, item) => sum + (item.harga_jual * item.qty), 0);
        const itemDiscounts = cart.reduce((sum, item) => sum + (parseFloat(item.discount) || 0), 0);
        const totalDiscountInput = parseFloat(document.getElementById('discount_total')?.value) || 0;
        const totalDiscount = itemDiscounts + totalDiscountInput;
        const total = subtotal - totalDiscount;
        
        // Auto-fill bayar jika metode pembayaran bukan tunai (Transfer/QRIS)
        const paymentMethod = document.getElementById('payment_method');
        const bayarInput = document.getElementById('bayar');
        if (paymentMethod && bayarInput && paymentMethod.value !== 'cash') {
            bayarInput.value = total;
        }

        const bayar = parseFloat(bayarInput.value) || 0;
        const kembali = bayar - total;

        document.getElementById('subtotal').textContent = formatRupiah(subtotal);
        document.getElementById('total').textContent = formatRupiah(total);
        document.getElementById('kembali').textContent = formatRupiah(kembali >= 0 ? kembali : 0);
    };

    // Event Listeners
    document.getElementById('btn-tambah-penjualan').addEventListener('click', () => {
        document.getElementById('form-penjualan').reset();
        tanggalPicker.setDate(new Date()); // Reset tanggal ke hari ini menggunakan API Flatpickr
        cart = [];
        renderCart();
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
        const isNonCash = e.target.value !== 'cash';
        document.getElementById('account-select-container').classList.toggle('hidden', !isNonCash);
        const accSelect = document.getElementById('payment_account_id');
        accSelect.required = isNonCash;
        if (!isNonCash) accSelect.value = '';
        updateSummary(); // Update summary to auto-fill amount if non-cash
    });

    // Handle "Uang Pas" button
    document.getElementById('btn-uang-pas')?.addEventListener('click', () => {
        const totalText = document.getElementById('total').textContent;
        // Extract number from "Rp 123.456"
        const totalValue = parseFloat(totalText.replace(/[^0-9,-]+/g,"").replace(",", "."));
        
        const bayarInput = document.getElementById('bayar');
        if (bayarInput && !isNaN(totalValue)) {
            bayarInput.value = totalValue;
            updateSummary(); // Recalculate change
        }
    });

    searchInput.addEventListener('keyup', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadPenjualan(1, e.target.value);
        }, 300);
    });

    paginationContainer.addEventListener('click', (e) => {
        e.preventDefault();
        if (e.target.closest('a[data-page]')) {
            const page = parseInt(e.target.dataset.page);
            if (page && page !== currentPage) {
                loadPenjualan(page, searchInput.value);
            }
        }
    });

    searchProdukInput.addEventListener('keyup', async (e) => {
        // Jangan jalankan pencarian jika tombol navigasi ditekan
        if (['ArrowUp', 'ArrowDown', 'Enter'].includes(e.key)) {
            return;
        }

        const term = e.target.value;
        const suggestionsContainer = document.getElementById('product-suggestions');
        if (term.length < 2) {
            suggestionsContainer.innerHTML = '';
            return;
        }

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
            // Log an error if the format is unexpected or if it's an explicit error object
            console.error("API Error on product search:", result.message || "Unexpected API response format");
            return;
        }

        if (products && products.length > 0) {
            const list = products.map(p => 
                `<a href="#" class="flex justify-between items-center p-3 hover:bg-gray-100 dark:hover:bg-gray-600 border-b border-gray-200 dark:border-gray-600" data-product='${JSON.stringify(p)}'>
                    <div>
                        <div class="font-bold"><i class="bi bi-box-seam mr-2 text-primary"></i>${p.nama_barang}</div>
                        <small class="text-gray-500"><i class="bi bi-upc-scan mr-1"></i>${p.sku || p.kode_barang || '-'} | ${formatRupiah(p.harga_jual)}</small>
                    </div>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-${p.stok > 10 ? 'green' : 'yellow'}-100 text-${p.stok > 10 ? 'green' : 'yellow'}-800">Stok: ${p.stok}</span>
                </a>`
            ).join('');
            suggestionsContainer.innerHTML = list;
        }
    });

    // Tambahkan event listener untuk tombol Enter pada pencarian produk
    // dan navigasi atas/bawah
    searchProdukInput.addEventListener('keydown', (e) => {
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
                const targetSuggestion = activeSuggestion || suggestions[0];
                if (targetSuggestion) targetSuggestion.click();
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

    // --- Navigasi Keyboard (Enter) untuk UX yang lebih cepat ---
    searchProdukInput.addEventListener('keydown', (e) => {
        // Jika tekan Enter saat input kosong, pindah ke kolom bayar
        if (e.key === 'Enter' && searchProdukInput.value.trim() === '') {
            e.preventDefault();
            if (cart.length > 0) {
                document.getElementById('bayar').focus();
            } else {
                showToast('Keranjang masih kosong.', 'warning');
            }
        }
    });

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
        const subtotal = cart.reduce((sum, item) => sum + (parseFloat(item.harga_jual) * parseInt(item.qty)), 0);
        const totalHPP = cart.reduce((sum, item) => sum + (parseFloat(item.harga_beli || 0) * parseInt(item.qty)), 0);
        const itemDiscounts = cart.reduce((sum, item) => sum + (parseFloat(item.discount) || 0), 0);
        const totalDiscountInput = parseFloat(document.getElementById('discount_total').value) || 0;
        const totalDiscount = itemDiscounts + totalDiscountInput;
        const total = subtotal - totalDiscount;
        const bayar = parseFloat(document.getElementById('bayar').value) || 0;

        // Validasi tambahan untuk non-tunai
        const paymentMethod = document.getElementById('payment_method')?.value || 'cash';
        const paymentAccountId = document.getElementById('payment_account_id')?.value;

        if (paymentMethod !== 'cash' && !paymentAccountId) {
            showToast('Harap pilih Akun Tujuan untuk pembayaran non-tunai.', 'warning');
            return;
        }
        if (cart.length === 0) {
            showToast('Keranjang belanja masih kosong.', 'warning');
            return;
        }
        if (bayar < total) {
            showToast('Jumlah bayar kurang dari total.', 'warning');
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
            tanggal: formatDateForDB(tanggalPicker.selectedDates[0]),
            customer_name: document.getElementById('customer_name').value || 'Umum',
            subtotal: subtotal,
            discount: totalDiscount,
            total: total,
            total_hpp: totalHPP, // Kirim total HPP untuk jurnal (Debit HPP, Kredit Persediaan)
            bayar: bayar,
            kembali: bayar - total,
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
                subtotal: (parseFloat(item.harga_jual) * parseInt(item.qty)) - (parseFloat(item.discount) || 0)
            }))
        };

        try {
            const response = await fetch(`${basePath}/api/penjualan?action=store`, {
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
                    html: `Total: <b>${formatRupiah(total)}</b><br>Kembali: <b>${formatRupiah(bayar - total)}</b>`,
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
                        <td class="px-4 py-2">${item.deskripsi_item}</td>
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

    // Muat data saat halaman pertama kali dibuka
    loadPenjualan();
}