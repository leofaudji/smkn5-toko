// =================================================================
// == FUNGSI UNTUK HALAMAN PEMBELIAN
// =================================================================

// Fungsi utama yang dipanggil saat halaman pembelian dimuat
function initPembelianPage() {
    // Inisialisasi Flatpickr
    const tglPembelianPicker = flatpickr("#tanggal_pembelian", { dateFormat: "d-m-Y", allowInput: true, defaultDate: "today" });
    const jatuhTempoPicker = flatpickr("#jatuh_tempo", { dateFormat: "d-m-Y", allowInput: true, placeholder: "DD-MM-YYYY" });

    // Muat data awal untuk form (pemasok, akun, dll)
    loadPembelianFormData();

    // Tambahkan event listener untuk tombol "Tambah Pembelian"
    document.getElementById('add-pembelian-btn')?.addEventListener('click', () => {
        resetPembelianForm();
        openModal('pembelianModal'); // Buka modal setelah form direset
    });

    // Tambahkan event listener untuk tombol "Tambah Baris" di dalam modal
    document.getElementById('add-pembelian-line-btn')?.addEventListener('click', () => {
        addPembelianLine();
    });

    // Event listener untuk mengubah tampilan berdasarkan metode pembayaran
    const paymentMethodSelect = document.getElementById('payment_method');
    const kasAccountContainer = document.getElementById('kas-account-container');
    const kasAccountSelect = document.getElementById('kas_account_id');

    paymentMethodSelect?.addEventListener('change', (e) => {
        const isCash = e.target.value === 'cash';
        kasAccountContainer.classList.toggle('hidden', !isCash); // Gunakan class 'hidden'
        kasAccountSelect.required = isCash;
    });


    // Event listener untuk menghapus baris (delegasi event)
    document.getElementById('pembelian-lines-body')?.addEventListener('click', (e) => {
        if (e.target && e.target.closest('.remove-pembelian-line-btn')) {
            e.target.closest('tr').remove();
        }
    });

    // Event listener untuk tombol simpan
    document.getElementById('save-pembelian-btn')?.addEventListener('click', savePembelian);

    // Muat daftar pembelian yang sudah ada
    loadPembelianList();

    // Event listener untuk filter
    let debounceTimer;
    const filterHandler = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadPembelianList(1), 300);
    };

    document.getElementById('search-pembelian')?.addEventListener('input', filterHandler);
    ['filter-supplier', 'filter-bulan', 'filter-tahun'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', filterHandler);
    });

    // Event delegation untuk tombol edit & hapus pada daftar pembelian
    document.getElementById('pembelian-table-body')?.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-pembelian-btn');
        if (editBtn) {
            handleEditPembelian(editBtn.dataset.id);
            return; // Hentikan eksekusi setelah menangani edit
        }

        const deleteBtn = e.target.closest('.delete-pembelian-btn');
        if (deleteBtn) {
            handleDeletePembelian(deleteBtn.dataset.id);
        }
    });
}

// Fungsi untuk mereset form ke keadaan awal
function resetPembelianForm() {
    const form = document.getElementById('pembelian-form');
    form.reset();
    document.getElementById('pembelian-id').value = '';
    document.getElementById('pembelian-action').value = 'add';
    document.getElementById('pembelianModalLabel').textContent = 'Tambah Pembelian Baru';
    document.getElementById('pembelian-lines-body').innerHTML = '';
    // Tambahkan satu baris kosong secara default
    addPembelianLine();
    // Set tanggal hari ini
    const tglPicker = document.getElementById('tanggal_pembelian')._flatpickr;
    if (tglPicker) tglPicker.setDate(new Date(), true);
    // Reset payment method display
    document.getElementById('kas-account-container').classList.add('hidden');
    document.getElementById('kas_account_id').required = false;
}

// Fungsi untuk memuat data yang dibutuhkan oleh form (Pemasok & Akun)
async function loadPembelianFormData() {
    try {
        // Ambil daftar pemasok dari API konsinyasi
        const supplierRes = await fetch(basePath + '/api/konsinyasi?action=list_suppliers');
        const supplierData = await supplierRes.json();
        const supplierSelect = document.getElementById('supplier_id');
        const supplierFilter = document.getElementById('filter-supplier');

        if (supplierData.status === 'success' && supplierSelect) {
            supplierSelect.innerHTML = '<option value="">-- Pilih Pemasok (Opsional) --</option>';
            if (supplierFilter) supplierFilter.innerHTML = '<option value="">Semua Pemasok</option>';

            supplierData.data.forEach(supplier => {
                const optionHtml = `<option value="${supplier.id}">${supplier.nama_pemasok}</option>`;
                supplierSelect.innerHTML += optionHtml;
                if (supplierFilter) supplierFilter.innerHTML += optionHtml;
            });
        }

        // Ambil daftar akun kas untuk pembayaran tunai
        const cashAccRes = await fetch(basePath + '/api/settings?action=get_cash_accounts');
        const cashAccData = await cashAccRes.json();
        const kasAccountSelect = document.getElementById('kas_account_id');
        if (cashAccData.status === 'success' && kasAccountSelect) {
            kasAccountSelect.innerHTML = '<option value="">-- Pilih Akun Kas --</option>';
            cashAccData.data.forEach(acc => {
                kasAccountSelect.innerHTML += `<option value="${acc.id}">${acc.nama_akun}</option>`;
            });
        }



        // Populate filter bulan dan tahun
        const bulanFilter = document.getElementById('filter-bulan');
        const tahunFilter = document.getElementById('filter-tahun');
        if (bulanFilter && tahunFilter) {
            const now = new Date();
            const currentYear = now.getFullYear();
            const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
            bulanFilter.innerHTML = '<option value="">Semua Bulan</option>';
            months.forEach((month, index) => bulanFilter.add(new Option(month, index + 1)));
            for (let i = 0; i < 5; i++) tahunFilter.add(new Option(currentYear - i, currentYear - i));
        }

        // Ambil daftar akun dari API COA
        const coaRes = await fetch(basePath + '/api/coa');
        const coaData = await coaRes.json();
        if (coaData.status === 'success') {
            // Simpan data akun di window untuk digunakan kembali saat menambah baris
            window.pembelianAccounts = coaData.data.filter(acc => 
                ['Aset', 'Beban'].includes(acc.tipe_akun)
            );
        }

        // Ambil daftar barang untuk pembelian
        const itemsRes = await fetch(basePath + '/api/stok?limit=9999'); // Ambil semua item
        const itemsData = await itemsRes.json();
        if (itemsData.status === 'success') {
            // Simpan data barang di window untuk digunakan kembali
            window.purchaseableItems = itemsData.data;
        }
    } catch (error) {
        console.error('Gagal memuat data form pembelian:', error);
        showToast('Gagal memuat data pendukung untuk form.', 'error');
    }
}

// Fungsi untuk menambah baris item baru di form pembelian
function addPembelianLine(data = {}) {
    const tbody = document.getElementById('pembelian-lines-body');
    if (!tbody) return;

    const newRow = document.createElement('tr');
    newRow.className = 'border-b border-gray-200 dark:border-gray-700';

    newRow.innerHTML = `
        <td class="px-4 py-2 relative">
            <input type="text" class="w-full bg-transparent border-none focus:ring-0 line-item-search" placeholder="Ketik untuk mencari barang..." required value="${data.nama_barang || ''}">
            <input type="hidden" class="line-item-id" value="${data.item_id || ''}">
            <div class="absolute top-full left-0 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-b-md shadow-lg z-50 hidden purchase-suggestions"></div>
        </td>
        <td class="px-4 py-2">
            <input type="number" class="w-24 text-right bg-transparent border-none focus:ring-0 line-qty" placeholder="0" required value="${data.quantity || 1}">
        </td>
        <td class="px-4 py-2">
            <input type="number" class="w-full text-right bg-transparent border-none focus:ring-0 line-price" placeholder="0" required value="${data.price || 0}">
        </td>
        <td class="px-4 py-2">
            <input type="number" class="w-full text-right bg-transparent border-none focus:ring-0 line-subtotal" readonly value="${(data.quantity || 1) * (data.price || 0)}">
        </td>
        <td class="px-4 py-2 text-center">
            <button type="button" class="text-red-500 hover:text-red-700 remove-pembelian-line-btn"><i class="bi bi-trash-fill"></i></button>
        </td>
    `;

    tbody.appendChild(newRow);

    const searchInput = newRow.querySelector('.line-item-search');
    const itemIdInput = newRow.querySelector('.line-item-id');
    const suggestionsContainer = newRow.querySelector('.purchase-suggestions');
    const priceInput = newRow.querySelector('.line-price');
    const qtyInput = newRow.querySelector('.line-qty');
    const subtotalInput = newRow.querySelector('.line-subtotal');

    // Fungsi untuk kalkulasi subtotal
    const calculateSubtotal = () => {
        const qty = parseFloat(qtyInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        subtotalInput.value = qty * price;
    };

    // Event listener untuk input pencarian
    searchInput.addEventListener('keyup', (e) => {
        const term = searchInput.value.toLowerCase();
        suggestionsContainer.innerHTML = '';
        if (term.length < 2) {
            suggestionsContainer.classList.add('hidden');
            return;
        }

        const filteredItems = (window.purchaseableItems || []).filter(item =>
            item.nama_barang.toLowerCase().includes(term) ||
            (item.sku && item.sku.toLowerCase().includes(term))
        ).slice(0, 10); // Batasi hasil untuk performa

        if (filteredItems.length > 0) {
            suggestionsContainer.classList.remove('hidden');
            filteredItems.forEach(item => {
                const suggestionDiv = document.createElement('div');
                suggestionDiv.className = 'p-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-200 dark:border-gray-600 last:border-b-0';
                suggestionDiv.innerHTML = `
                    <div class="font-semibold">${item.nama_barang}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">SKU: ${item.sku || '-'} | Stok: ${item.stok}</div>
                `;
                suggestionDiv.dataset.item = JSON.stringify(item);
                suggestionsContainer.appendChild(suggestionDiv);
            });
        } else {
            suggestionsContainer.innerHTML = '<div class="p-3 text-center text-sm text-gray-500">Barang tidak ditemukan.</div>';
            suggestionsContainer.classList.remove('hidden');
        }
    });

    // Event listener untuk memilih dari saran
    suggestionsContainer.addEventListener('click', (e) => {
        const suggestion = e.target.closest('[data-item]');
        if (suggestion) {
            const item = JSON.parse(suggestion.dataset.item);
            searchInput.value = item.nama_barang;
            itemIdInput.value = item.id;
            priceInput.value = item.harga_beli || 0;
            suggestionsContainer.classList.add('hidden');
            calculateSubtotal();
            qtyInput.focus(); // Pindah fokus ke qty
        }
    });

    // Sembunyikan saran jika klik di luar
    document.addEventListener('click', (e) => {
        if (!newRow.contains(e.target)) {
            suggestionsContainer.classList.add('hidden');
        }
    });

    qtyInput.addEventListener('input', calculateSubtotal);
    priceInput.addEventListener('input', calculateSubtotal);
}

// Fungsi untuk menyimpan data pembelian
async function savePembelian() {
    const form = document.getElementById('pembelian-form');
    // Simple validation
    if (!form.checkValidity()) {
        showToast('Harap isi semua field yang wajib diisi.', 'error');
        return;
    }

    const saveBtn = document.getElementById('save-pembelian-btn');
    const originalBtnText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;

    const formatDateForDB = (date) => {
        if (!date) return null;
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    // Kumpulkan data dari form
    const formData = {
        action: document.getElementById('pembelian-action').value,
        id: document.getElementById('pembelian-id').value,
        supplier_id: document.getElementById('supplier_id').value,
        tanggal_pembelian: formatDateForDB(document.getElementById('tanggal_pembelian')._flatpickr.selectedDates[0]),
        keterangan: document.getElementById('keterangan').value,
        jatuh_tempo: document.getElementById('jatuh_tempo').value ? formatDateForDB(document.getElementById('jatuh_tempo')._flatpickr.selectedDates[0]) : null,
        payment_method: document.getElementById('payment_method').value,
        kas_account_id: document.getElementById('kas_account_id').value, // Tambahkan ini
        lines: []
    };

    // Kumpulkan data dari setiap baris item
    document.querySelectorAll('#pembelian-lines-body tr').forEach(row => {
        const item_id = row.querySelector('.line-item-id').value;
        const quantity = row.querySelector('.line-qty').value;
        const price = row.querySelector('.line-price').value;

        // Pastikan baris tersebut valid (memiliki ID barang) sebelum ditambahkan
        if (item_id && quantity > 0 && price >= 0) {
            formData.lines.push({ item_id, quantity, price });
        }
    });

    if (formData.lines.length === 0) {
        showToast('Pembelian harus memiliki minimal satu item/baris yang valid.', 'error');
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalBtnText;
        return;
    }
    
    // Kirim data ke API
    try {
        const response = await fetch(basePath + '/api/pembelian', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.status === 'success') {
            showToast(result.message, 'success');
            closeModal('pembelianModal');
            // Panggil fungsi untuk memuat ulang daftar pembelian
            loadPembelianList(); 
        } else {
            showToast(result.message || 'Terjadi kesalahan yang tidak diketahui.', 'error');
        }
    } catch (error) {
        console.error('Error saat menyimpan pembelian:', error);
        showToast('Gagal terhubung ke server.', 'error');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalBtnText;
    }
}

// Fungsi untuk memuat dan menampilkan daftar pembelian
async function loadPembelianList(page = 1) {
    const tableBody = document.getElementById('pembelian-table-body');
    const paginationContainer = document.getElementById('pembelian-pagination');
    const paginationInfo = document.getElementById('pembelian-pagination-info');
    if (!tableBody) return;

    const limit = 10; // Hardcode limit for now
    const search = document.getElementById('search-pembelian').value;
    const supplierId = document.getElementById('filter-supplier').value;
    const bulan = document.getElementById('filter-bulan').value;
    const tahun = document.getElementById('filter-tahun').value;

    const params = new URLSearchParams({ page, limit, search, supplier_id: supplierId, bulan, tahun });
    tableBody.innerHTML = `<tr><td colspan="7" class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>`;

    try {
        const response = await fetch(`${basePath}/api/pembelian?${params.toString()}`);
        const result = await response.json();

        if (result.status !== 'success') throw new Error(result.message);

        tableBody.innerHTML = '';
        if (result.data.length > 0) {
            result.data.forEach(p => {
                let statusBadge;
                switch (p.status) {
                    case 'open': statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Belum Lunas</span>'; break;
                    case 'paid': statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Lunas</span>'; break;
                    case 'void': statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Batal</span>'; break;
                    default: statusBadge = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">${p.status}</span>`;
                }

                const row = `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${new Date(p.tanggal_pembelian).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'})}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${p.nama_pemasok || '<i>- Tanpa Pemasok -</i>'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${p.keterangan}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">${formatCurrencyAccounting(p.total)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${p.jatuh_tempo ? new Date(p.jatuh_tempo).toLocaleDateString('id-ID') : '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${statusBadge}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <div class="inline-flex rounded-md shadow-sm">
                                <button class="px-2 py-1 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-l-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 edit-pembelian-btn" data-id="${p.id}" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                                <button class="px-2 py-1 border-t border-b border-r border-gray-300 dark:border-gray-600 text-sm font-medium rounded-r-md text-red-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 delete-pembelian-btn" data-id="${p.id}" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                            </div>
                        </td>
                    </tr>
                `;
                tableBody.insertAdjacentHTML('beforeend', row);
            });
        } else {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-gray-500 py-10">Tidak ada data pembelian ditemukan.</td></tr>';
        }
        renderPagination(paginationContainer, result.pagination, loadPembelianList);
        if (paginationInfo && result.pagination) {
            const { from, to, total } = result.pagination;
            paginationInfo.textContent = `Menampilkan ${from} - ${to} dari ${total} data.`;
        }

    } catch (error) {
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Gagal memuat data: ${error.message}</td></tr>`;
    }
}

// Fungsi untuk menangani klik tombol edit
async function handleEditPembelian(id) {
    try {
        const response = await fetch(`${basePath}/api/pembelian?action=get_single&id=${id}`);
        const result = await response.json();
        if (result.status !== 'success') throw new Error(result.message);

        const { header, details } = result.data;

        // Reset form dan isi dengan data
        resetPembelianForm();
        document.getElementById('pembelianModalLabel').textContent = 'Edit Pembelian';
        document.getElementById('pembelian-id').value = header.id;
        document.getElementById('pembelian-action').value = 'update';

        document.getElementById('supplier_id').value = header.supplier_id;
        // Set tanggal menggunakan flatpickr API
        const tglPembelianPicker = document.getElementById('tanggal_pembelian')._flatpickr;
        if (tglPembelianPicker) tglPembelianPicker.setDate(header.tanggal_pembelian, true, "Y-m-d");

        document.getElementById('keterangan').value = header.keterangan;
        
        const jatuhTempoPicker = document.getElementById('jatuh_tempo')._flatpickr;
        if (jatuhTempoPicker && header.jatuh_tempo) jatuhTempoPicker.setDate(header.jatuh_tempo, true, "Y-m-d");

        document.getElementById('payment_method').value = header.payment_method;

        // Tampilkan/sembunyikan field akun kas berdasarkan metode pembayaran
        const kasAccountContainer = document.getElementById('kas-account-container');
        const kasAccountSelect = document.getElementById('kas_account_id');
        const isCash = header.payment_method === 'cash';
        kasAccountContainer.classList.toggle('hidden', !isCash);
        kasAccountSelect.required = isCash;
        if (isCash) kasAccountSelect.value = header.credit_account_id; // Di backend, credit_account_id diisi dengan kas_account_id saat tunai

        // Hapus baris default dan isi dengan detail dari database
        // NOTE: Ini memerlukan perubahan di backend untuk 'get_single' agar mengembalikan item_id, qty, price
        document.getElementById('pembelian-lines-body').innerHTML = '';
        if (details.length > 0) {
            details.forEach(line => {
                // Cari nama barang dari list global untuk ditampilkan di input
                const item = (window.purchaseableItems || []).find(p => p.id == line.item_id);
                const nama_barang = line.nama_barang || (item ? item.nama_barang : '');

                addPembelianLine({ 
                    item_id: line.item_id, 
                    nama_barang: nama_barang, // Tambahkan nama barang
                    quantity: line.quantity, 
                    price: line.price 
                });
            });
        } else {
            addPembelianLine(); // Tambah satu baris kosong jika tidak ada detail
        }

        // Tampilkan modal
        openModal('pembelianModal');

    } catch (error) {
        showToast(`Gagal memuat data pembelian: ${error.message}`, 'error');
    }
}

// Fungsi untuk menangani klik tombol hapus
async function handleDeletePembelian(id) {
    if (!confirm('Anda yakin ingin menghapus data pembelian ini? Aksi ini tidak dapat dibatalkan dan akan menghapus jurnal terkait.')) {
        return;
    }

    try {
        const response = await fetch(`${basePath}/api/pembelian`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        });

        const result = await response.json();
        if (result.status === 'success') {
            showToast(result.message, 'success');
            loadPembelianList(); // Muat ulang daftar setelah berhasil hapus
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        showToast(`Gagal menghapus data: ${error.message}`, 'error');
    }
}