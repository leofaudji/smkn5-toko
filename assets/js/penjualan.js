function initPenjualanPage() {
    const penjualanModal = new bootstrap.Modal(document.getElementById('penjualanModal'));
    const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
    const searchProdukInput = document.getElementById('search-produk');
    const cartItemsContainer = document.getElementById('cart-items');
    const searchInput = document.getElementById('search-input');
    const tableBody = document.getElementById('penjualanTable').querySelector('tbody');
    const paginationContainer = document.getElementById('pagination');
    const paginationInfo = document.getElementById('pagination-info');

    let cart = [];
    let currentPage = 1;
    const limit = 10;
    let searchTimeout;

    // --- Tambahan: Inject UI Metode Pembayaran ---
    const bayarInput = document.getElementById('bayar');
    if (bayarInput && !document.getElementById('payment_method')) {
        const container = bayarInput.closest('.mb-3') || bayarInput.parentElement;
        const paymentHtml = `
            <div class="mb-3">
                <label for="payment_method" class="form-label"><i class="bi bi-credit-card me-1"></i>Metode Pembayaran</label>
                <select class="form-select" id="payment_method">
                    <option value="cash">Tunai</option>
                    <option value="transfer">Transfer Bank</option>
                    <option value="qris">QRIS</option>
                </select>
            </div>
            <div class="mb-3" id="account-select-container" style="display:none;">
                <label for="payment_account_id" class="form-label"><i class="bi bi-bank me-1"></i>Akun Tujuan <span class="text-danger">*</span></label>
                <select class="form-select" id="payment_account_id">
                    <option value="">-- Pilih Akun Bank --</option>
                </select>
            </div>
        `;
        container.insertAdjacentHTML('beforebegin', paymentHtml);

        // Load daftar akun kas/bank
        fetch(`${basePath}/api/settings?action=get_cash_accounts`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    const accSelect = document.getElementById('payment_account_id');
                    res.data.forEach(acc => accSelect.add(new Option(acc.nama_akun, acc.id)));
                }
            });

        // Event listener ganti metode
        document.getElementById('payment_method').addEventListener('change', (e) => {
            const isNonCash = e.target.value !== 'cash';
            document.getElementById('account-select-container').style.display = isNonCash ? 'block' : 'none';
            const accSelect = document.getElementById('payment_account_id');
            accSelect.required = isNonCash;
            if (!isNonCash) accSelect.value = '';
            updateSummary(); // Update summary untuk auto-fill nominal jika non-tunai
        });

        // Tambahkan tombol Uang Pas
        const uangPasBtn = document.createElement('button');
        uangPasBtn.type = 'button';
        uangPasBtn.className = 'btn btn-outline-secondary w-100 mt-2';
        uangPasBtn.id = 'btn-uang-pas';
        uangPasBtn.innerHTML = '<i class="bi bi-cash-stack"></i> Uang Pas';
        
        bayarInput.parentNode.insertBefore(uangPasBtn, bayarInput.nextSibling);

        uangPasBtn.addEventListener('click', () => {
            // Hitung total belanja saat ini
            const subtotal = cart.reduce((sum, item) => sum + (parseFloat(item.harga_jual) * parseInt(item.qty)), 0);
            const itemDiscounts = cart.reduce((sum, item) => sum + (parseFloat(item.discount) || 0), 0);
            const totalDiscountInput = parseFloat(document.getElementById('discount_total').value) || 0;
            const total = subtotal - (itemDiscounts + totalDiscountInput);
            
            // Set nilai input bayar sesuai total
            bayarInput.value = total;
            
            // Update perhitungan kembalian
            updateSummary();
        });
    }
    // ---------------------------------------------

    // --- Tambahan: Sticky Header & Scroll untuk Tabel Keranjang ---
    if (!document.getElementById('penjualan-pos-styles')) {
        const style = document.createElement('style');
        style.id = 'penjualan-pos-styles';
        style.textContent = `
            .pos-cart-scroll {
                max-height: 400px; /* Tinggi maksimal area scroll */
                overflow-y: auto;
                border: 1px solid #dee2e6;
                border-radius: 0.25rem;
            }
            .pos-cart-scroll table { margin-bottom: 0; }
            .pos-cart-scroll thead th {
                position: sticky;
                top: 0;
                background-color: #f8f9fa; /* Warna background header agar tidak transparan */
                z-index: 5;
                box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            }
        `;
        document.head.appendChild(style);
    }

    if (cartItemsContainer) {
        const table = cartItemsContainer.closest('table');
        // Bungkus tabel dengan div scroll jika belum
        if (table && !table.parentElement.classList.contains('pos-cart-scroll')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'pos-cart-scroll';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    }
    // -------------------------------------------------------------

    // Fungsi utilitas
    const formatRupiah = (angka) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    };

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
                        <div>Tgl: ${detail.tanggal_penjualan}</div>
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
                renderPagination(result.total, result.page, result.limit);
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
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="me-2 text-primary"><i class="bi bi-receipt"></i></div>
                            <div>
                                <span class="fw-bold ${textDecoration}">${item.nomor_referensi}</span>
                                ${isVoid ? '<span class="badge bg-danger ms-1" style="font-size: 0.65rem;">BATAL</span>' : ''}
                            </div>
                        </div>
                    </td>
                    <td><i class="bi bi-calendar-event me-1 text-muted"></i> <span class="${textDecoration}">${new Date(item.tanggal_penjualan).toLocaleString('id-ID')}</span></td>
                    <td><i class="bi bi-person me-1 text-muted"></i> <span class="${textDecoration}">${item.customer_name}</span></td>
                    <td class="text-end">
                        <span class="fw-bold ${isVoid ? 'text-muted' : 'text-success'} ${textDecoration}">${formatRupiah(item.total)}</span>
                    </td>
                    <td><i class="bi bi-person-badge me-1 text-muted"></i> <span class="${textDecoration}">${item.username}</span></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-info btn-detail" data-id="${item.id}" title="Lihat Detail">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-void" data-id="${item.id}" title="Batalkan Transaksi" ${isVoid ? 'disabled' : ''}>
                            <i class="bi bi-x-circle"></i>
                        </button>
                        </div>
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', row);
        });
    };

    const renderPagination = (total, page, limit) => {
        const totalPages = Math.ceil(total / limit);
        paginationContainer.innerHTML = '';
        
        if (totalPages <= 1) {
            paginationInfo.textContent = `Menampilkan ${total} dari ${total} data.`;
            return;
        }

        // Info
        const start = (page - 1) * limit + 1;
        const end = Math.min(page * limit, total);
        paginationInfo.textContent = `Menampilkan ${start}-${end} dari ${total} data.`;

        // Tombol
        // Prev
        paginationContainer.innerHTML += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page - 1}">Prev</a></li>`;
        // Pages
        for (let i = 1; i <= totalPages; i++) {
            paginationContainer.innerHTML += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        // Next
        paginationContainer.innerHTML += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page + 1}">Next</a></li>`;
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
                <tr data-index="${index}" class="align-middle">
                    <td>
                        <div class="fw-bold text-dark">${item.nama_barang}</div>
                        <small class="text-muted">${item.kode_barang || ''}</small>
                    </td>
                    <td class="text-end">${formatRupiah(item.harga_jual)}</td>
                    <td style="width: 140px;">
                        <div class="input-group input-group-sm">
                            <button class="btn btn-outline-secondary btn-qty-dec" type="button"><i class="bi bi-dash"></i></button>
                            <input type="number" class="form-control text-center qty-input" value="${item.qty}" min="1" max="${item.stok}">
                            <button class="btn btn-outline-secondary btn-qty-inc" type="button"><i class="bi bi-plus"></i></button>
                        </div>
                    </td>
                    <td style="width: 130px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control text-end discount-input" value="${item.discount || 0}" min="0" placeholder="0">
                        </div>
                    </td>
                    <td class="subtotal text-end fw-bold text-primary">${formatRupiah(subtotal)}</td>
                    <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm remove-item-btn" title="Hapus"><i class="bi bi-trash"></i></button></td>
                </tr>
            `;
            cartItemsContainer.insertAdjacentHTML('beforeend', row);
        });
        updateSummary();
    };

    const updateSummary = () => {
        const subtotal = cart.reduce((sum, item) => sum + (item.harga_jual * item.qty), 0);
        const itemDiscounts = cart.reduce((sum, item) => sum + (parseFloat(item.discount) || 0), 0);
        const totalDiscountInput = parseFloat(document.getElementById('discount_total').value) || 0;
        const totalDiscount = itemDiscounts + totalDiscountInput;
        const total = subtotal - totalDiscount;
        
        // Auto-fill bayar jika metode pembayaran bukan tunai (Transfer/QRIS)
        const paymentMethod = document.getElementById('payment_method');
        const bayarInput = document.getElementById('bayar');
        if (paymentMethod && bayarInput && paymentMethod.value !== 'cash') {
            bayarInput.value = total;
        }

        const bayar = parseFloat(document.getElementById('bayar').value) || 0;
        const kembali = bayar - total;

        document.getElementById('subtotal').value = formatRupiah(subtotal);
        document.getElementById('total').value = formatRupiah(total);
        document.getElementById('kembali').value = formatRupiah(kembali >= 0 ? kembali : 0);
    };

    // Event Listeners
    document.getElementById('btn-tambah-penjualan').addEventListener('click', () => {
        document.getElementById('form-penjualan').reset();
        document.getElementById('tanggal').valueAsDate = new Date();
        cart = [];
        updateSummary();
        penjualanModal.show();
    });

    searchInput.addEventListener('keyup', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadPenjualan(1, e.target.value);
        }, 300);
    });

    paginationContainer.addEventListener('click', (e) => {
        e.preventDefault();
        if (e.target.tagName === 'A') {
            const page = parseInt(e.target.dataset.page);
            if (page && page !== currentPage) {
                loadPenjualan(page, searchInput.value);
            }
        }
    });

    searchProdukInput.addEventListener('keyup', async (e) => {
        const term = e.target.value;
        const suggestionsContainer = document.getElementById('product-suggestions');
        if (term.length < 2) {
            suggestionsContainer.innerHTML = '';
            return;
        }

        const response = await fetch(`${basePath}/api/penjualan?action=search_produk&term=${term}`);
        const products = await response.json();
        
        suggestionsContainer.innerHTML = '';
        if (products.length > 0) {
            const list = products.map(p => 
                `<a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-product='${JSON.stringify(p)}'>
                    <div>
                        <div class="fw-bold"><i class="bi bi-box-seam me-2 text-primary"></i>${p.nama_barang}</div>
                        <small class="text-muted"><i class="bi bi-upc-scan me-1"></i>${p.sku || p.kode_barang || '-'} | ${formatRupiah(p.harga_jual)}</small>
                    </div>
                    <span class="badge bg-${p.stok > 10 ? 'success' : 'warning'} rounded-pill">Stok: ${p.stok}</span>
                </a>`
            ).join('');
            suggestionsContainer.innerHTML = `<div class="list-group position-absolute w-100" style="z-index: 1056;">${list}</div>`;
        }
    });

    document.getElementById('product-suggestions').addEventListener('click', (e) => {
        e.preventDefault();
        if (e.target.classList.contains('list-group-item')) {
            const product = JSON.parse(e.target.dataset.product);
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

        const formData = {
            tanggal: document.getElementById('tanggal').value,
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
                penjualanModal.hide();
                
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
                    document.getElementById('tanggal').valueAsDate = new Date();
                    cart = [];
                    renderCart(); // Bersihkan tampilan keranjang
                    
                    penjualanModal.show(); // Buka kembali modal
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
                    <tr>
                        <td>${item.deskripsi_item}</td>
                        <td>${item.quantity}</td>
                        <td>${formatRupiah(item.price)}</td>
                        <td class="text-end">${formatRupiah(item.subtotal)}</td>
                    </tr>
                `).join('');

                const detailContent = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="text-muted small">No. Faktur</div>
                            <div class="fw-bold text-primary"><i class="bi bi-receipt me-1"></i>${detail.nomor_referensi}</div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="text-muted small">Tanggal</div>
                            <div class="fw-bold"><i class="bi bi-calendar3 me-1"></i>${new Date(detail.tanggal_penjualan).toLocaleString('id-ID')}</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Pelanggan</div>
                            <div class="fw-bold"><i class="bi bi-person me-1"></i>${detail.customer_name}</div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="text-muted small">Kasir</div>
                            <div class="fw-bold"><i class="bi bi-person-badge me-1"></i>${detail.created_by_username}</div>
                        </div>
                    </div>
                    
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Barang</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>${itemsHtml}</tbody>
                        </table>
                    </div>
                    
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Total Tagihan</span>
                                <span class="fw-bold fs-5 text-primary">${formatRupiah(detail.total)}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1 text-muted">
                                <span>Bayar</span>
                                <span>${formatRupiah(detail.bayar)}</span>
                            </div>
                            <div class="border-top my-2"></div>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Kembali</span>
                                <span class="fw-bold text-success">${formatRupiah(detail.kembali)}</span>
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

                detailModal.show();
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
                const url = `${basePath}/api/pdf?report=struk-penjualan&id=${id}`;
                window.open(url, '_blank');
            }
        }
    });

    // Muat data saat halaman pertama kali dibuka
    loadPenjualan();
}