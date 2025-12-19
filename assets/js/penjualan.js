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

    // Fungsi utilitas
    const formatRupiah = (angka) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
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
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Tidak ada data.</td></tr>';
            return;
        }
        data.forEach(item => {
            const row = `
                <tr>
                    <td>${item.nomor_referensi}</td>
                    <td>${item.tanggal_penjualan}</td>
                    <td>${item.customer_name}</td>
                    <td class="text-end">${formatRupiah(item.total)}</td>
                    <td>${item.username}</td>
                    <td>
                        <button class="btn btn-info btn-sm btn-detail" data-id="${item.id}" title="Lihat Detail">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-danger btn-sm btn-void" data-id="${item.id}" title="Batalkan Transaksi" ${item.status === 'void' ? 'disabled' : ''}>
                            <i class="bi bi-x-circle"></i>
                        </button>
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
    };

    const renderCart = () => {
        cartItemsContainer.innerHTML = '';
        cart.forEach((item, index) => {
            const price = parseFloat(item.harga_jual) || 0;
            const qty = parseInt(item.qty) || 0;
            const discount = parseFloat(item.discount) || 0;
            const subtotal = (price * qty) - discount;

            const row = `
                <tr data-index="${index}">
                    <td>${item.nama_barang}</td>
                    <td>${formatRupiah(item.harga_jual)}</td>
                    <td><input type="number" class="form-control form-control-sm qty-input" value="${item.qty}" min="1" max="${item.stok}"></td>
                    <td><input type="number" class="form-control form-control-sm discount-input" value="${item.discount || 0}" min="0"></td>
                    <td class="subtotal">${formatRupiah(subtotal)}</td>
                    <td><button type="button" class="btn btn-danger btn-sm remove-item-btn"><i class="bi bi-trash"></i></button></td>
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
        
        if (e.key === 'Enter' && products.length > 0) {
            addItemToCart(products[0]);
            return;
        }

        suggestionsContainer.innerHTML = '';
        if (products.length > 0) {
            const list = products.map(p => 
                `<a href="#" class="list-group-item list-group-item-action" data-product='${JSON.stringify(p)}'>
                    ${p.nama_barang} (${p.kode_barang}) - Stok: ${p.stok}
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
            const index = e.target.closest('tr').dataset.index;
            let newQty = parseInt(e.target.value);
            if (isNaN(newQty) || newQty < 1) newQty = 1;
            
            if (newQty > cart[index].stok) {
                newQty = cart[index].stok;
                e.target.value = newQty;
                showToast('Stok tidak mencukupi.', 'warning');
            }
            cart[index].qty = newQty;
            renderCart(); // Ganti updateSummary() dengan renderCart()
        }
        if (e.target.classList.contains('discount-input')) {
            const index = e.target.closest('tr').dataset.index;
            let newDiscount = parseFloat(e.target.value) || 0;
            const maxDiscount = cart[index].harga_jual * cart[index].qty;
            if (newDiscount > maxDiscount) {
                newDiscount = maxDiscount;
                e.target.value = newDiscount;
            }
            cart[index].discount = newDiscount;
            renderCart(); // Ganti updateSummary() dengan renderCart()
        }
    });

    cartItemsContainer.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.remove-item-btn');
        if (removeBtn) {
            const index = removeBtn.closest('tr').dataset.index;
            cart.splice(index, 1);
            updateSummary();
        }
    });

    document.getElementById('bayar').addEventListener('input', updateSummary);
    document.getElementById('discount_total').addEventListener('input', updateSummary);

    document.getElementById('btn-simpan-penjualan').addEventListener('click', async () => {
        const subtotal = cart.reduce((sum, item) => sum + (item.harga_jual * item.qty), 0);
        const itemDiscounts = cart.reduce((sum, item) => sum + (parseFloat(item.discount) || 0), 0);
        const totalDiscountInput = parseFloat(document.getElementById('discount_total').value) || 0;
        const totalDiscount = itemDiscounts + totalDiscountInput;
        const total = subtotal - totalDiscount;
        const bayar = parseFloat(document.getElementById('bayar').value) || 0;

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
            bayar: bayar,
            kembali: bayar - total,
            catatan: document.getElementById('catatan').value || '', // Ambil nilai dari input catatan
            items: cart.map(item => ({
                id: item.id,
                nama: item.nama_barang,
                harga: item.harga_jual,
                qty: item.qty,
                discount: parseFloat(item.discount) || 0,
                subtotal: (item.harga_jual * item.qty) - (parseFloat(item.discount) || 0)
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
                showToast(result.message, 'success');
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
                    <p><strong>No. Faktur:</strong> ${detail.nomor_referensi}</p>
                    <p><strong>Tanggal:</strong> ${detail.tanggal_penjualan}</p>
                    <p><strong>Customer:</strong> ${detail.customer_name}</p>
                    <p><strong>Kasir:</strong> ${detail.created_by_username}</p>
                    <table class="table table-sm">
                        <thead><tr><th>Barang</th><th>Qty</th><th>Harga</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody>${itemsHtml}</tbody>
                    </table>
                    <hr>
                    <div class="row">
                        <div class="col-6"></div>
                        <div class="col-6">
                            <p class="d-flex justify-content-between"><strong>Total:</strong> <span>${formatRupiah(detail.total)}</span></p>
                            <p class="d-flex justify-content-between"><strong>Bayar:</strong> <span>${formatRupiah(detail.bayar)}</span></p>
                            <p class="d-flex justify-content-between"><strong>Kembali:</strong> <span>${formatRupiah(detail.kembali)}</span></p>
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