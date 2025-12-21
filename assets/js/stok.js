// =================================================================
// == FUNGSI UNTUK HALAMAN BARANG & STOK
// =================================================================

function initStokPage() {
    // Event listener untuk filter
    let debounceTimer;
    const filterHandler = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadItemsList(1), 300);
    };
    document.getElementById('search-item')?.addEventListener('input', filterHandler);
    document.getElementById('filter-stok')?.addEventListener('change', filterHandler);
    document.getElementById('filter-category')?.addEventListener('change', filterHandler);

    // Event listener untuk tombol "Tambah Barang"
    document.getElementById('add-item-btn')?.addEventListener('click', () => {
        const form = document.getElementById('item-form');
        form.reset();
        form.classList.remove('was-validated');

        document.getElementById('itemModalLabel').textContent = 'Tambah Barang Baru';
        document.getElementById('item-id').value = '';
        document.getElementById('item-action').value = 'save';
        
        const stokInput = document.getElementById('stok');
        stokInput.disabled = false;
        stokInput.value = '0';
        
        const stokHelpText = document.getElementById('stok-help-text');
        if (stokHelpText) {
            stokHelpText.textContent = 'Masukkan jumlah stok awal. Untuk mengubah stok selanjutnya, gunakan fitur "Penyesuaian Stok" atau transaksi Pembelian.';
        }

        // Muat data yang diperlukan untuk modal
        loadAccountsForItemModal();
        loadCategoriesForItemModal();

        openModal('itemModal');
    });

    // Event listener untuk tombol simpan
    document.getElementById('save-item-btn')?.addEventListener('click', saveItem);

    // Event listener untuk tombol upload excel
    document.getElementById('upload-excel-btn')?.addEventListener('click', uploadExcel);

    // Event delegation untuk tombol edit & hapus
    document.getElementById('items-table-body')?.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-item-btn');
        if (editBtn) {
            handleEditItem(editBtn.dataset.id);
        }

        const deleteBtn = e.target.closest('.delete-item-btn');
        if (deleteBtn) {
            handleDeleteItem(deleteBtn.dataset.id, deleteBtn.dataset.nama);
        }

        // Tambahkan ini untuk menangani tombol penyesuaian
        const adjustmentBtn = e.target.closest('.adjustment-btn');
        if (adjustmentBtn) {
            handleAdjustment(adjustmentBtn);
        }
    });

    // Event listener untuk tombol simpan penyesuaian
    document.getElementById('save-adjustment-btn')?.addEventListener('click', saveAdjustment);

    // Muat akun untuk modal import saat dibuka
    document.querySelector('[onclick="openModal(\'importModal\')"]')?.addEventListener('click', () => {
        loadAdjustmentAccounts('import_adj_account_id');
    });

     // Muat data awal untuk daftar barang
    loadItemsList();
    loadCategoriesForFilter(); // Panggil fungsi untuk memuat kategori
}

async function saveAdjustment() {
    const form = document.getElementById('adjustment-form');
    const formData = new FormData(form);

    const confirmed = confirm(`Ini akan menyesuaikan stok barang dan membuat jurnal otomatis. Pastikan data sudah benar.`);
    if (!confirmed) return;

    const saveBtn = document.getElementById('save-adjustment-btn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;

    try {
        const response = await fetch(`${basePath}/api/stok`, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.status === 'success') {
            showToast(result.message);
            closeModal('adjustmentModal');
            loadItemsList();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Terjadi kesalahan saat menyimpan data.', 'error');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = 'Simpan Penyesuaian';
    }
}

function handleAdjustment(btn) {
    const itemId = btn.dataset.id;
    const namaBarang = btn.dataset.nama;
    const stokTercatat = btn.dataset.stok;

    const form = document.getElementById('adjustment-form');
    
    form.reset();
    
    document.getElementById('adj-item-id').value = itemId;
    document.getElementById('adj-nama-barang').value = namaBarang;
    document.getElementById('adj-stok-tercatat').value = stokTercatat;
    document.getElementById('adj-stok-fisik').value = stokTercatat;
    document.getElementById('adj-tanggal').valueAsDate = new Date();
    
    loadAdjustmentAccounts(); 

    openModal('adjustmentModal');
}

async function loadAdjustmentAccounts(selectElementId = 'adj_account_id') {
    try {
        const response = await fetch(`${basePath}/api/stok?action=get_adjustment_accounts`);
        const result = await response.json();
        if (result.status === 'success') {
            const select = document.getElementById(selectElementId);
            if (!select) return;
            select.innerHTML = '<option value="">Pilih Akun Penyeimbang...</option>';
            result.data.forEach(acc => select.innerHTML += `<option value="${acc.id}">${acc.kode_akun} - ${acc.nama_akun}</option>`);
        }
    } catch (error) {
        console.error('Error loading adjustment accounts:', error);
    };
}

async function loadItemsList(page = 1) {
    const tableBody = document.getElementById('items-table-body');
    const paginationContainer = document.getElementById('items-pagination');
    const paginationInfo = document.getElementById('items-pagination-info');
    if (!tableBody) return;

    const search = document.getElementById('search-item')?.value || '';
    const stokFilter = document.getElementById('filter-stok')?.value || '';
    const categoryFilter = document.getElementById('filter-category')?.value || '';
    
    const params = new URLSearchParams({ page, limit: 15, search, stok_filter: stokFilter, category_filter: categoryFilter });
    tableBody.innerHTML = `<tr><td colspan="8" class="text-center p-5"><div class="spinner-border"></div></td></tr>`;

    try {
        const response = await fetch(`${basePath}/api/stok?${params.toString()}`);
        const result = await response.json();
        if (result.status !== 'success') throw new Error(result.message);

        tableBody.innerHTML = '';
        if (result.data.length > 0) {
            result.data.forEach(item => {
                const nilaiStok = parseFloat(item.harga_beli) * parseInt(item.stok);
                const row = `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.nama_barang}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.sku || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">${item.nama_kategori || 'Tanpa Kategori'}</span></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">${formatCurrencyAccounting(item.harga_beli)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">${formatCurrencyAccounting(item.harga_jual)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white text-right">${item.stok}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">${formatCurrencyAccounting(nilaiStok)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <div class="inline-flex rounded-md shadow-sm">
                                <button class="px-2 py-1 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-l-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 adjustment-btn" data-id="${item.id}" data-nama="${item.nama_barang}" data-stok="${item.stok}" title="Penyesuaian Stok">
                                    <i class="bi bi-arrow-left-right"></i>
                                </button>
                                <button class="px-2 py-1 border-t border-b border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 edit-item-btn" data-id="${item.id}" title="Edit Barang">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <button class="px-2 py-1 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-r-md text-red-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 delete-item-btn" data-id="${item.id}" data-nama="${item.nama_barang}" title="Hapus Barang">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tableBody.insertAdjacentHTML('beforeend', row);
            });
        } else {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-gray-500 py-10">Tidak ada barang ditemukan.</td></tr>';
        }
        renderPagination(paginationContainer, result.pagination, loadItemsList);
        if (paginationInfo && result.pagination) {
            const { from, to, total } = result.pagination;
            paginationInfo.textContent = `Menampilkan ${from} - ${to} dari ${total} data.`;
        }
    } catch (error) {
        tableBody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Gagal memuat data: ${error.message}</td></tr>`;
    }
}

async function loadAccountsForItemModal() {
    try {
        const response = await fetch(`${basePath}/api/stok?action=get_accounts`);
        const result = await response.json();
        if (result.status !== 'success') throw new Error(result.message);

        const { aset, beban, pendapatan } = result.data;
        const createOptions = (accounts) => '<option value="">-- Opsional --</option>' + accounts.map(acc => `<option value="${acc.id}">${acc.kode_akun} - ${acc.nama_akun}</option>`).join('');

        document.getElementById('inventory_account_id').innerHTML = createOptions(aset);
        document.getElementById('cogs_account_id').innerHTML = createOptions(beban);
        document.getElementById('sales_account_id').innerHTML = createOptions(pendapatan);
    } catch (error) {
        showToast(`Gagal memuat daftar akun: ${error.message}`, 'error');
    }
}

async function loadCategoriesForItemModal() {
    const categorySelect = document.getElementById('category_id');
    if (!categorySelect) return;

    try {
        const response = await fetch(`${basePath}/api/stok?action=get_categories`);
        const result = await response.json();
        if (result.status === 'success') {
            categorySelect.innerHTML = '<option value="">-- Pilih Kategori (Opsional) --</option>';
            result.data.forEach(cat => {
                categorySelect.innerHTML += `<option value="${cat.id}">${cat.nama_kategori}</option>`;
            });
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Gagal memuat kategori untuk modal:', error);
        categorySelect.innerHTML = '<option value="">Gagal memuat kategori</option>';
    }
}

async function loadCategoriesForFilter() {
    const categoryFilterSelect = document.getElementById('filter-category');
    if (!categoryFilterSelect) return;

    try {
        const response = await fetch(`${basePath}/api/stok?action=get_categories`);
        const result = await response.json();
        if (result.status === 'success') {
            categoryFilterSelect.innerHTML = '<option value="">Semua Kategori</option>';
            result.data.forEach(cat => {
                categoryFilterSelect.innerHTML += `<option value="${cat.id}">${cat.nama_kategori}</option>`;
            });
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Gagal memuat kategori untuk filter:', error);
    }
}

async function saveItem() {
    const form = document.getElementById('item-form');

    const saveBtn = document.getElementById('save-item-btn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;

    try {
        const formData = new FormData(form);
        const response = await fetch(`${basePath}/api/stok`, { method: 'POST', body: formData });
        const result = await response.json();
        if (result.status === 'success') {
            showToast(result.message, 'success');
            closeModal('itemModal');
            loadItemsList();
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        showToast(`Gagal menyimpan: ${error.message}`, 'error');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = 'Simpan Barang';
    }
}

async function handleEditItem(id) {
    try {
        await Promise.all([loadAccountsForItemModal(), loadCategoriesForItemModal()]);

        const formData = new FormData();
        formData.append('action', 'get_single');
        formData.append('id', id);
        const response = await fetch(`${basePath}/api/stok`, { method: 'POST', body: formData });
        const result = await response.json();
        if (result.status === 'success') {
            const item = result.data;
            document.getElementById('itemModalLabel').textContent = 'Edit Barang';
            Object.keys(item).forEach(key => {
                const el = document.getElementById(key);
                if (el) el.value = item[key];
            });
            document.getElementById('item-id').value = item.id;
            document.getElementById('item-action').value = 'update';
            document.getElementById('stok').disabled = true;
            document.getElementById('stok-help-text').textContent = 'Stok tidak dapat diubah dari sini. Gunakan fitur "Penyesuaian Stok" atau "Stok Opname".';
            openModal('itemModal');
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        showToast(`Gagal memuat data barang: ${error.message}`, 'error');
    }
}

async function handleDeleteItem(id, nama) {
    if (!confirm(`Anda yakin ingin menghapus barang "${nama}"?`)) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    const response = await fetch(`${basePath}/api/stok`, { method: 'POST', body: formData });
    const result = await response.json();
    showToast(result.message, result.status);
    if (result.status === 'success') loadItemsList();
}

async function uploadExcel() {
    const form = document.getElementById('import-form');
    const fileInput = document.getElementById('excel-file');
    const uploadBtn = document.getElementById('upload-excel-btn');

    if (fileInput.files.length === 0) {
        showToast('Harap pilih file Excel terlebih dahulu.', 'error');
        return;
    }

    const originalBtnHtml = uploadBtn.innerHTML;
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Memproses...`;

    try {
        const formData = new FormData(form);
        formData.append('action', 'import');

        const response = await fetch(`${basePath}/api/stok`, {
            method: 'POST',
            body: formData,
        });

        const result = await response.json();
        showToast(result.message, result.status);

        if (result.status === 'success') {
            closeModal('importModal');
            loadItemsList(); // Muat ulang daftar barang
        }
    } catch (error) {
        showToast(`Terjadi kesalahan: ${error.message}`, 'error');
    } finally {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = originalBtnHtml;
    }
}