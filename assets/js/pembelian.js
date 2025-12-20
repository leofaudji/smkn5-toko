// =================================================================
// == FUNGSI UNTUK HALAMAN PEMBELIAN
// =================================================================

// Fungsi utama yang dipanggil saat halaman pembelian dimuat
function initPembelianPage() {
    // Muat data awal untuk form (pemasok, akun, dll)
    loadPembelianFormData();

    // Tambahkan event listener untuk tombol "Tambah Pembelian"
    document.getElementById('add-pembelian-btn')?.addEventListener('click', () => {
        resetPembelianForm();
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
        kasAccountContainer.style.display = isCash ? 'block' : 'none';
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
    ['filter-supplier', 'filter-bulan', 'filter-tahun', 'filter-limit'].forEach(id => {
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
    document.getElementById('tanggal_pembelian').valueAsDate = new Date();
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
        const itemsRes = await fetch(basePath + '/api/stok?limit=-1'); // Ambil semua item
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

    // Buat opsi untuk dropdown barang
    let itemOptions = '<option value="">-- Pilih Barang --</option>';
    if (window.purchaseableItems) {
        window.purchaseableItems.forEach(item => {
            const isSelected = data.item_id && data.item_id == item.id ? 'selected' : '';
            itemOptions += `<option value="${item.id}" data-price="${item.harga_beli}" ${isSelected}>${item.nama_barang} (${item.sku || 'No-SKU'})</option>`;
        });
    }

    newRow.innerHTML = `
        <td>
            <select class="form-select form-select-sm line-item" required>
                ${itemOptions}
            </select>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm text-end line-qty" placeholder="0" required value="${data.quantity || 1}">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm text-end line-price" placeholder="0" required value="${data.price || 0}">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm text-end line-subtotal" readonly>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm remove-pembelian-line-btn"><i class="bi bi-trash-fill"></i></button>
        </td>
    `;

    tbody.appendChild(newRow);

    // Fungsi untuk kalkulasi subtotal
    const calculateSubtotal = (row) => {
        const qty = parseFloat(row.querySelector('.line-qty').value) || 0;
        const price = parseFloat(row.querySelector('.line-price').value) || 0;
        row.querySelector('.line-subtotal').value = qty * price;
    };

    // Event listener untuk auto-fill harga dan kalkulasi
    newRow.querySelector('.line-item').addEventListener('change', (e) => {
        const selectedOption = e.target.options[e.target.selectedIndex];
        const price = selectedOption.dataset.price || 0;
        const priceInput = newRow.querySelector('.line-price');
        priceInput.value = price;
        calculateSubtotal(newRow);
    });
    newRow.querySelector('.line-qty').addEventListener('input', () => calculateSubtotal(newRow));
    newRow.querySelector('.line-price').addEventListener('input', () => calculateSubtotal(newRow));

    // Hitung subtotal awal jika ada data
    calculateSubtotal(newRow);
}

// Fungsi untuk menyimpan data pembelian
async function savePembelian() {
    const form = document.getElementById('pembelian-form');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        showToast('Harap isi semua field yang wajib diisi.', 'error');
        return;
    }

    const saveBtn = document.getElementById('save-pembelian-btn');
    const originalBtnText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...`;

    // Kumpulkan data dari form
    const formData = {
        action: document.getElementById('pembelian-action').value,
        id: document.getElementById('pembelian-id').value,
        supplier_id: document.getElementById('supplier_id').value,
        tanggal_pembelian: document.getElementById('tanggal_pembelian').value,
        keterangan: document.getElementById('keterangan').value,
        jatuh_tempo: document.getElementById('jatuh_tempo').value,
        payment_method: document.getElementById('payment_method').value,
        kas_account_id: document.getElementById('kas_account_id').value, // Tambahkan ini
        lines: []
    };

    // Kumpulkan data dari setiap baris item
    document.querySelectorAll('#pembelian-lines-body tr').forEach(row => {
        const item_id = row.querySelector('.line-item').value;
        const quantity = row.querySelector('.line-qty').value;
        const price = row.querySelector('.line-price').value;
        const subtotal = row.querySelector('.line-subtotal').value;

        // Pastikan baris tersebut valid sebelum ditambahkan
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
            const pembelianModal = bootstrap.Modal.getInstance(document.getElementById('pembelianModal'));
            if (pembelianModal) pembelianModal.hide();
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
    if (!tableBody) return;

    const limit = document.getElementById('filter-limit').value;
    const search = document.getElementById('search-pembelian').value;
    const supplierId = document.getElementById('filter-supplier').value;
    const bulan = document.getElementById('filter-bulan').value;
    const tahun = document.getElementById('filter-tahun').value;

    const params = new URLSearchParams({ page, limit, search, supplier_id: supplierId, bulan, tahun });
    tableBody.innerHTML = `<tr><td colspan="7" class="text-center p-5"><div class="spinner-border"></div></td></tr>`;

    try {
        const response = await fetch(`${basePath}/api/pembelian?${params.toString()}`);
        const result = await response.json();

        if (result.status !== 'success') throw new Error(result.message);

        tableBody.innerHTML = '';
        if (result.data.length > 0) {
            result.data.forEach(p => {
                let statusBadge;
                switch (p.status) {
                    case 'open': statusBadge = '<span class="badge bg-warning">Belum Lunas</span>'; break;
                    case 'paid': statusBadge = '<span class="badge bg-success">Lunas</span>'; break;
                    case 'void': statusBadge = '<span class="badge bg-secondary">Batal</span>'; break;
                    default: statusBadge = `<span class="badge bg-light text-dark">${p.status}</span>`;
                }

                const row = `
                    <tr>
                        <td>${new Date(p.tanggal_pembelian).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'})}</td>
                        <td>${p.nama_pemasok || '<i>- Tanpa Pemasok -</i>'}</td>
                        <td>${p.keterangan}</td>
                        <td class="text-end">${formatCurrencyAccounting(p.total)}</td>
                        <td>${p.jatuh_tempo ? new Date(p.jatuh_tempo).toLocaleDateString('id-ID') : '-'}</td>
                        <td>${statusBadge}</td>
                        <td class="text-end">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-info edit-pembelian-btn" data-id="${p.id}" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                                <button class="btn btn-sm btn-danger delete-pembelian-btn" data-id="${p.id}" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                            </div>
                        </td>
                    </tr>
                `;
                tableBody.insertAdjacentHTML('beforeend', row);
            });
        } else {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Tidak ada data pembelian ditemukan.</td></tr>';
        }
        renderPagination(paginationContainer, result.pagination, loadPembelianList);

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
        document.getElementById('tanggal_pembelian').value = header.tanggal_pembelian;
        document.getElementById('keterangan').value = header.keterangan;
        document.getElementById('jatuh_tempo').value = header.jatuh_tempo;
        document.getElementById('payment_method').value = header.payment_method;

        // Tampilkan/sembunyikan field akun kas berdasarkan metode pembayaran
        const kasAccountContainer = document.getElementById('kas-account-container');
        const kasAccountSelect = document.getElementById('kas_account_id');
        const isCash = header.payment_method === 'cash';
        kasAccountContainer.style.display = isCash ? 'block' : 'none';
        kasAccountSelect.required = isCash;
        if (isCash) kasAccountSelect.value = header.credit_account_id; // Di backend, credit_account_id diisi dengan kas_account_id saat tunai

        // Hapus baris default dan isi dengan detail dari database
        // NOTE: Ini memerlukan perubahan di backend untuk 'get_single' agar mengembalikan item_id, qty, price
        document.getElementById('pembelian-lines-body').innerHTML = '';
        if (details.length > 0) {
            details.forEach(line => {
                // Asumsikan backend mengembalikan data yang sesuai
                addPembelianLine({ item_id: line.item_id, quantity: line.quantity, price: line.price });
            });
        } else {
            addPembelianLine(); // Tambah satu baris kosong jika tidak ada detail
        }

        // Tampilkan modal
        const pembelianModal = new bootstrap.Modal(document.getElementById('pembelianModal'));
        pembelianModal.show();

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