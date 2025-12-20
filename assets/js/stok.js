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
    document.getElementById('filter-limit')?.addEventListener('change', filterHandler);

    // Event listener untuk modal
    const itemModalEl = document.getElementById('itemModal');
    if (itemModalEl) {
        // Pindahkan pemanggilan loadAccountsForItemModal ke sini
        // Ini memastikan akun hanya dimuat saat modal akan ditampilkan
        itemModalEl.addEventListener('show.bs.modal', loadAccountsForItemModal);

        // Tambahkan pemanggilan untuk memuat kategori saat modal ditampilkan
        itemModalEl.addEventListener('show.bs.modal', loadCategoriesForItemModal);

        itemModalEl.addEventListener('show.bs.modal', (e) => {
            const button = e.relatedTarget;
            const form = document.getElementById('item-form');
            form.reset();
            form.classList.remove('was-validated');

            if (button && button.classList.contains('edit-item-btn')) {
                // Panggil fungsi untuk mengisi data saat tombol edit diklik
                handleEditItem(button.dataset.id);
            } else { // This handles the "add" case
                document.getElementById('itemModalLabel').textContent = 'Tambah Barang Baru';
                document.getElementById('item-id').value = '';
                document.getElementById('item-action').value = 'save';
                document.getElementById('stok').disabled = false;
                document.getElementById('stok').parentElement.querySelector('.form-text').textContent = 'Masukkan jumlah stok saat ini. Untuk mengubah stok, gunakan fitur "Penyesuaian Stok".';
            }
        });

        const importModalEl = document.getElementById('importModal');
        importModalEl.addEventListener('show.bs.modal', () => {
            // Muat akun penyesuaian untuk modal import
            loadAdjustmentAccounts('import_adj_account_id');
        });
    }

    // Event listener untuk tombol simpan
    document.getElementById('save-item-btn')?.addEventListener('click', saveItem);

    // Event listener untuk tombol upload excel
    document.getElementById('upload-excel-btn')?.addEventListener('click', uploadExcel);

    // Event delegation untuk tombol edit & hapus
    document.getElementById('items-table-body')?.addEventListener('click', (e) => {
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
     // Muat data awal untuk daftar barang
    loadItemsList();
    loadCategoriesForFilter(); // Panggil fungsi untuk memuat kategori
}

async function saveAdjustment() {
    const form = document.getElementById('adjustment-form');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        showToast('Harap isi semua field yang wajib diisi.', 'error');
        return;
    }

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    const confirmed = confirm(`Ini akan menyesuaikan stok barang dan membuat jurnal otomatis. Pastikan data sudah benar.`);
    if (!confirmed) return;

    const saveBtn = document.getElementById('save-adjustment-btn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;

    try {
        const response = await fetch(`${basePath}/api/stok`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.status === 'success') {
            showToast(result.message);
            bootstrap.Modal.getInstance(document.getElementById('adjustmentModal')).hide();
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

    const modal = new bootstrap.Modal(document.getElementById('adjustmentModal'));
    const form = document.getElementById('adjustment-form');
    
    form.reset();
    form.classList.remove('was-validated');
    
    document.getElementById('adj-item-id').value = itemId;
    document.getElementById('adj-nama-barang').value = namaBarang;
    document.getElementById('adj-stok-tercatat').value = stokTercatat;
    document.getElementById('adj-stok-fisik').value = stokTercatat;
    document.getElementById('adj-tanggal').valueAsDate = new Date();
    
    loadAdjustmentAccounts(); 

    modal.show();
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
    if (!tableBody) return;

    const limit = document.getElementById('filter-limit').value;
    const search = document.getElementById('search-item').value;
    const stokFilter = document.getElementById('filter-stok').value;
    const categoryFilter = document.getElementById('filter-category').value;
    
    const params = new URLSearchParams({ page, limit, search, stok_filter: stokFilter, category_filter: categoryFilter });
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
                    <tr>
                        <td>${item.nama_barang}</td>
                        <td>${item.sku || '-'}</td>
                        <td><span class="badge bg-secondary">${item.nama_kategori || 'Tanpa Kategori'}</span></td>
                        <td class="text-end">${formatCurrencyAccounting(item.harga_beli)}</td>
                        <td class="text-end">${formatCurrencyAccounting(item.harga_jual)}</td>
                        <td class="text-end fw-bold">${item.stok}</td>
                        <td class="text-end">${formatCurrencyAccounting(nilaiStok)}</td>
                        <td class="text-end">
                            <div class="btn-group">
                                <button class="btn btn-info btn-sm adjustment-btn" data-id="${item.id}" data-nama="${item.nama_barang}" data-stok="${item.stok}" title="Penyesuaian Stok">
                                    <i class="bi bi-arrow-left-right"></i>
                                </button>
                                <button class="btn btn-warning btn-sm edit-item-btn" data-id="${item.id}" title="Edit Barang" data-bs-toggle="modal" data-bs-target="#itemModal">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <button class="btn btn-danger btn-sm delete-item-btn" data-id="${item.id}" data-nama="${item.nama_barang}" title="Hapus Barang">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tableBody.insertAdjacentHTML('beforeend', row);
            });
        } else {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Tidak ada barang ditemukan.</td></tr>';
        }
        renderPagination(paginationContainer, result.pagination, loadItemsList);
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
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        showToast('Harap isi semua field yang wajib diisi.', 'error');
        return;
    }

    const saveBtn = document.getElementById('save-item-btn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;

    try {
        const formData = new FormData(form);
        const response = await fetch(`${basePath}/api/stok`, { method: 'POST', body: formData });
        const result = await response.json();
        if (result.status === 'success') {
            showToast(result.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('itemModal')).hide();
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
        document.getElementById('item-action').value = 'update'; // Explicitly set action to 'update' for edit mode
        document.getElementById('stok').disabled = true;
        document.getElementById('stok').parentElement.querySelector('.form-text').textContent = 'Stok tidak dapat diubah dari sini. Gunakan fitur "Penyesuaian Stok" atau "Stok Opname".';
        // Modal sudah dipicu oleh tombol, tidak perlu memanggil .show() lagi di sini.
    } else {
        showToast(`Gagal memuat data barang: ${result.message}`, 'error');
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
            bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
            loadItemsList(); // Muat ulang daftar barang
        }
    } catch (error) {
        showToast(`Terjadi kesalahan: ${error.message}`, 'error');
    } finally {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = originalBtnHtml;
    }
}