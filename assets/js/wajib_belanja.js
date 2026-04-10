function initWajibBelanjaPage() {
    const form = document.getElementById('wb-form');
    const editForm = document.getElementById('wb-edit-form');
    const modal = document.getElementById('wb-form-modal');
    const tableBody = document.getElementById('wb-table-body');
    const loadingEl = document.getElementById('wb-loading');
    const paginationInfo = document.getElementById('wb-pagination-info');
    const paginationContainer = document.getElementById('wb-pagination');
    const tambahBtn = document.getElementById('wb-tambah-btn');
    const itemsBody = document.getElementById('wb-items-body');
    const addRowBtn = document.getElementById('wb-add-row-btn');
    const loadAllBtn = document.getElementById('wb-load-all-btn');
    const totalDisplay = document.getElementById('wb-total-display');
    const importBtn = document.getElementById('wb-import-btn');
    const importForm = document.getElementById('wb-import-form');

    let currentPage = 1;
    let nominalDefault = 50000;
    let anggotaList = []; // Simpan daftar anggota untuk dropdown
    let isFetching = false;
    let hasMore = true;
    let filters = {
        search: '',
        start_date: '',
        end_date: ''
    };

    async function fetchWajibBelanja(page = 1, append = false) {
        if (isFetching || (!hasMore && append)) return;

        isFetching = true;
        currentPage = page;
        
        loadingEl.style.display = 'flex';
        const noMoreEl = document.getElementById('wb-no-more');
        if (noMoreEl) noMoreEl.style.display = 'none';

        if (!append) {
            tableBody.innerHTML = '';
            hasMore = true;
        }

        try {
            const params = new URLSearchParams({
                action: 'list',
                page: page,
                search: filters.search,
                start_date: filters.start_date,
                end_date: filters.end_date
            });

            const response = await fetch(`${basePath}/api/wajib-belanja?${params.toString()}`);
            const result = await response.json();

            if (result.success) {
                renderTable(result.data, append);
                hasMore = page < result.pagination.total_pages;
                
                if (!hasMore && (append || result.data.length > 0)) {
                    if (noMoreEl) noMoreEl.style.display = 'block';
                }
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Gagal memuat data.', 'error');
            console.error(error);
        } finally {
            isFetching = false;
            loadingEl.style.display = 'none';
        }
    }

    function renderTable(data, append = false) {
        if (!append && data.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-10 text-gray-500">Tidak ada data yang ditemukan.</td></tr>`;
            return;
        }

        const rowsHtml = data.map(item => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${formatDate(item.tanggal)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.nama_anggota}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right font-mono">${formatRupiah(item.jumlah)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                    <span class="px-2 py-1 rounded-full text-xs font-medium ${item.metode_pembayaran === 'tunai' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'}">
                        ${item.metode_pembayaran}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">${item.keterangan || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3 btn-edit transition-colors" data-id="${item.id}" title="Edit">
                        <i class="bi bi-pencil-square text-lg"></i>
                    </button>
                    <button class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 btn-delete transition-colors" data-id="${item.id}" title="Hapus">
                        <i class="bi bi-trash-fill text-lg"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        if (append) {
            tableBody.insertAdjacentHTML('beforeend', rowsHtml);
        } else {
            tableBody.innerHTML = rowsHtml;
        }
    }

    // Infinite Scroll Implementation
    const sentinel = document.getElementById('wb-infinite-sentinel');
    if (sentinel) {
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && hasMore && !isFetching) {
                fetchWajibBelanja(currentPage + 1, true);
            }
        }, { threshold: 0.1 });
        observer.observe(sentinel);
    }

    // Filter Logic
    const filterNama = document.getElementById('filter-nama');
    const filterDari = document.getElementById('filter-dari');
    const filterSampai = document.getElementById('filter-sampai');
    const resetFilterBtn = document.getElementById('wb-filter-reset');

    const handleFilterChange = debounce(() => {
        filters.search = filterNama.value;
        filters.start_date = filterDari.value;
        filters.end_date = filterSampai.value;
        hasMore = true;
        fetchWajibBelanja(1, false);
    }, 400);

    if (filterNama) filterNama.addEventListener('input', handleFilterChange);
    if (filterDari) filterDari.addEventListener('change', handleFilterChange);
    if (filterSampai) filterSampai.addEventListener('change', handleFilterChange);
    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', () => {
            filterNama.value = '';
            filterDari.value = '';
            filterSampai.value = '';
            filters = { search: '', start_date: '', end_date: '' };
            hasMore = true;
            fetchWajibBelanja(1, false);
        });
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    async function initForm() {
        try {
            const response = await fetch(`${basePath}/api/wajib-belanja?action=init_data`);
            const result = await response.json();

            if (result.success) {
                anggotaList = result.anggota; // Simpan ke variabel global
                const kasSelect = document.getElementById('wb-akun-kas-id');
                const importKasSelect = document.getElementById('import-wb-akun-kas-id');
                const optionsHtml = '<option value="">Pilih Akun Kas/Bank</option>' + result.kas_accounts.map(k => `<option value="${k.id}">${k.kode_akun} - ${k.nama_akun}</option>`).join('');
                kasSelect.innerHTML = optionsHtml;
                if (importKasSelect) importKasSelect.innerHTML = optionsHtml;
                
                nominalDefault = result.nominal_default;
            } else {
                showToast('Gagal memuat data form.', 'error');
            }
        } catch (error) {
            showToast('Gagal memuat data form.', 'error');
        }
    }

    function addRow(anggotaId = '', jumlah = nominalDefault) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-4 py-2">
                <select name="anggota_id[]" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm wb-row-anggota">
                    <option value="">Pilih Anggota</option>
                    ${anggotaList.map(a => `<option value="${a.id}" ${a.id == anggotaId ? 'selected' : ''}>${a.nomor_anggota} - ${a.nama_lengkap}</option>`).join('')}
                </select>
            </td>
            <td class="px-4 py-2">
                <input type="number" name="jumlah[]" value="${jumlah}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm text-right wb-row-jumlah" required>
            </td>
            <td class="px-4 py-2">
                <input type="text" name="keterangan_row[]" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" placeholder="Opsional">
            </td>
            <td class="px-4 py-2 text-center">
                <button type="button" class="text-red-600 hover:text-red-900 wb-remove-row"><i class="bi bi-trash"></i></button>
            </td>
        `;
        itemsBody.appendChild(row);
        calculateTotal();
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.wb-row-jumlah').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        totalDisplay.textContent = formatRupiah(total);
    }

    // Event Listeners untuk Tabel
    addRowBtn.addEventListener('click', () => addRow());

    loadAllBtn.addEventListener('click', () => {
        if (confirm('Apakah Anda yakin ingin memuat semua anggota aktif? Ini akan menghapus baris yang sudah ada.')) {
            itemsBody.innerHTML = '';
            anggotaList.forEach(anggota => {
                addRow(anggota.id);
            });
        }
    });

    itemsBody.addEventListener('click', (e) => {
        if (e.target.closest('.wb-remove-row')) {
            e.target.closest('tr').remove();
            calculateTotal();
        }
    });

    itemsBody.addEventListener('input', (e) => {
        if (e.target.classList.contains('wb-row-jumlah')) {
            calculateTotal();
        }
    });

    tambahBtn.addEventListener('click', () => {
        form.reset();
        itemsBody.innerHTML = ''; // Reset tabel
        document.getElementById('wb-tanggal').valueAsDate = new Date();
        
        // Tambah satu baris kosong default
        addRow();
        
        openModal('wb-form-modal');
    });

    importBtn.addEventListener('click', () => {
        importForm.reset();
        importForm.querySelector('input[type="date"]').valueAsDate = new Date();
        openModal('wb-import-modal');
    });

    importForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = importForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        const formData = new FormData(importForm);
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Memproses...';

        try {
            const response = await fetch(`${basePath}/api/wajib-belanja`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                closeModal('wb-import-modal');
                fetchWajibBelanja(1, false);
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Gagal mengimpor data.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Validasi minimal satu baris
        const rows = itemsBody.querySelectorAll('tr');
        if (rows.length === 0) {
            showToast('Harap tambahkan minimal satu anggota.', 'error');
            return;
        }

        // Validasi anggota duplikat
        const selectedMembers = new Set();
        let hasDuplicate = false;
        document.querySelectorAll('.wb-row-anggota').forEach(select => {
            if (select.value) {
                if (selectedMembers.has(select.value)) hasDuplicate = true;
                selectedMembers.add(select.value);
            }
        });

        if (hasDuplicate) {
            showToast('Terdapat anggota yang dipilih lebih dari sekali.', 'error');
            return;
        }

        const submitBtn = document.getElementById('wb-form-submit-btn');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Menyimpan...';

        try {
            // Kumpulkan data manual karena struktur array
            const formData = {
                action: 'create',
                tanggal: document.getElementById('wb-tanggal').value,
                metode_pembayaran: document.getElementById('wb-metode-pembayaran').value,
                akun_kas_id: document.getElementById('wb-akun-kas-id').value,
                items: []
            };

            rows.forEach(row => {
                const anggotaId = row.querySelector('.wb-row-anggota').value;
                const jumlah = row.querySelector('.wb-row-jumlah').value;
                const ket = row.querySelector('input[name="keterangan_row[]"]').value;
                
                if (anggotaId && jumlah > 0) {
                    formData.items.push({
                        anggota_id: anggotaId,
                        jumlah: jumlah,
                        keterangan: ket
                    });
                }
            });

            const response = await fetch(`${basePath}/api/wajib-belanja`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                closeModal('wb-form-modal');
                fetchWajibBelanja(1, false);
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan saat menyimpan.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });

    // Handle Edit & Delete Buttons
    tableBody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.btn-edit');
        const deleteBtn = e.target.closest('.btn-delete');

        if (deleteBtn) {
            const id = deleteBtn.dataset.id;
            if (confirm('Apakah Anda yakin ingin menghapus transaksi ini? Saldo anggota akan dikembalikan.')) {
                try {
                    const response = await fetch(`${basePath}/api/wajib-belanja`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', id: id })
                    });
                    const result = await response.json();
                    if (result.success) {
                        showToast(result.message, 'success');
                        fetchWajibBelanja(1, false);
                    } else {
                        showToast(result.message, 'error');
                    }
                } catch (error) {
                    showToast('Gagal menghapus data.', 'error');
                }
            }
        }

        if (editBtn) {
            const id = editBtn.dataset.id;
            try {
                const response = await fetch(`${basePath}/api/wajib-belanja?action=get_single&id=${id}`);
                const result = await response.json();
                if (result.success) {
                    const data = result.data;
                    document.getElementById('edit-wb-id').value = data.id;
                    document.getElementById('edit-wb-anggota-display').value = data.nama_anggota;
                    document.getElementById('edit-wb-tanggal').value = data.tanggal;
                    document.getElementById('edit-wb-jumlah').value = parseFloat(data.jumlah);
                    document.getElementById('edit-wb-metode').value = data.metode_pembayaran;
                    document.getElementById('edit-wb-keterangan').value = data.keterangan;
                    openModal('wb-edit-modal');
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Gagal memuat data edit.', 'error');
            }
        }
    });

    // Handle Edit Form Submit
    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = editForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Menyimpan...';

        try {
            const formData = new FormData(editForm);
            const data = Object.fromEntries(formData.entries());
            data.action = 'update';

            const response = await fetch(`${basePath}/api/wajib-belanja`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                closeModal('wb-edit-modal');
                fetchWajibBelanja(1, false);
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Gagal memperbarui data.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    // Initial load
    fetchWajibBelanja(1, false);
    initForm();
}