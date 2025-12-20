function initDaftarJurnalPage() {
    const tableBody = document.getElementById('daftar-jurnal-table-body');
    const searchInput = document.getElementById('search-jurnal');
    const startDateFilter = document.getElementById('filter-jurnal-mulai');
    const endDateFilter = document.getElementById('filter-jurnal-akhir');
    const limitSelect = document.getElementById('filter-jurnal-limit');
    const paginationContainer = document.getElementById('daftar-jurnal-pagination');
    const exportPdfBtn = document.getElementById('export-dj-pdf');
    const exportCsvBtn = document.getElementById('export-dj-csv');
    const viewModalEl = document.getElementById('viewJurnalModal');

    if (!tableBody) return;
    let periodLockDate = null;

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    async function loadJurnal(page = 1) {
        const params = new URLSearchParams({
            page,
            limit: limitSelect.value,
            search: searchInput.value,
            start_date: startDateFilter.value,
            end_date: endDateFilter.value,
        });

        tableBody.innerHTML = `<tr><td colspan="8" class="text-center p-5"><div class="spinner-border"></div></td></tr>`;
        try {
            const [jurnalRes, settingsRes] = await Promise.all([
                fetch(`${basePath}/api/entri-jurnal?${params.toString()}`),
                fetch(`${basePath}/api/settings`)
            ]);
            const result = await jurnalRes.json();
            const settingsResult = await settingsRes.json();

            if (settingsResult.status === 'success' && settingsResult.data.period_lock_date) {
                periodLockDate = new Date(settingsResult.data.period_lock_date);
            }
            if (result.status !== 'success') throw new Error(result.message);

            tableBody.innerHTML = '';
            if (result.data.length > 0) {
                let lastRef = null;
                result.data.forEach((line, index) => {
                    const isFirstRowOfGroup = line.ref !== lastRef;
                    const borderTopClass = isFirstRowOfGroup && index > 0 ? 'border-top-heavy' : '';

                    // Info Audit (Created/Updated)
                    const createdAt = new Date(line.created_at);
                    const updatedAt = new Date(line.updated_at);
                    const createdBy = line.created_by_name || 'sistem';
                    const updatedBy = line.updated_by_name || 'sistem';
                    
                    let auditInfo = `Dibuat: ${createdBy} pada ${createdAt.toLocaleString('id-ID')}`;
                    let auditIcon = '<i class="bi bi-info-circle"></i>';

                    if (updatedBy && updatedAt.getTime() > createdAt.getTime() + 1000) { // Cek jika ada update signifikan
                        auditInfo += `\nDiperbarui: ${updatedBy} pada ${updatedAt.toLocaleString('id-ID')}`;
                        auditIcon = '<i class="bi bi-info-circle-fill text-primary"></i>';
                    }

                    let editBtn, deleteBtn;
                    if (line.source === 'jurnal') {
                        editBtn = `<a href="${basePath}/entri-jurnal?edit_id=${line.entry_id}" class="btn btn-sm btn-warning edit-jurnal-btn" title="Edit"><i class="bi bi-pencil-fill"></i></a>`;
                        deleteBtn = `<button class="btn btn-sm btn-danger delete-jurnal-btn" data-id="${line.entry_id}" data-keterangan="${line.keterangan}" title="Hapus"><i class="bi bi-trash-fill"></i></button>`;
                    } else { // transaksi, hanya bisa dihapus dari sini, edit di halaman transaksi
                        editBtn = `<a href="${basePath}/transaksi#tx-${line.entry_id}" class="btn btn-sm btn-secondary" title="Lihat & Edit di Halaman Transaksi"><i class="bi bi-box-arrow-up-right"></i></a>`;
                        deleteBtn = `<button class="btn btn-sm btn-danger delete-transaksi-btn" data-id="${line.entry_id}" data-keterangan="${line.keterangan}" title="Hapus Transaksi"><i class="bi bi-trash-fill"></i></button>`;
                    }

                    const row = `
                        <tr class="${borderTopClass}">
                            <td>${isFirstRowOfGroup ? line.ref : ''}</td>
                            <td>${isFirstRowOfGroup ? new Date(line.tanggal).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'}) : ''}</td>
                            <td>${isFirstRowOfGroup ? line.keterangan : ''}</td>
                            <td class="${line.debit > 0 ? '' : 'ps-4'}">${line.nama_akun || '-'}</td>
                            <td class="text-end">${line.debit > 0 ? currencyFormatter.format(line.debit) : ''}</td>
                            <td class="text-end">${line.kredit > 0 ? currencyFormatter.format(line.kredit) : ''}</td>
                            <td>${isFirstRowOfGroup ? `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${auditInfo}">${auditIcon}</span>` : ''}</td>
                            <td class="text-end align-middle">
                                ${isFirstRowOfGroup ? `
                                    <div class="btn-group">
                                        ${editBtn}
                                        ${deleteBtn}
                                    </div>
                                ` : ''}
                            </td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                    lastRef = line.ref;
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center">Tidak ada entri jurnal ditemukan.</td></tr>';
            }
            renderPagination(paginationContainer, result.pagination, loadJurnal);
            // Inisialisasi ulang tooltip setelah data baru dimuat
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    tableBody.addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.delete-jurnal-btn');
        if (deleteBtn) {
            const { id, keterangan } = deleteBtn.dataset;
            if (confirm(`Yakin ingin menghapus entri jurnal "${keterangan}" (ID: JRN-${String(id).padStart(5, '0')})? Aksi ini tidak dapat dibatalkan.`)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                const response = await fetch(`${basePath}/api/entri-jurnal`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') loadJurnal(1);
            }
        }

        const editTransaksiBtn = e.target.closest('.edit-transaksi-btn');
        if (editTransaksiBtn) {
            const id = editTransaksiBtn.dataset.id;
            // Navigasi ke halaman transaksi dan buka modal edit
            navigate(`${basePath}/transaksi#edit-${id}`);
        }

        const deleteTransaksiBtn = e.target.closest('.delete-transaksi-btn');
        if (deleteTransaksiBtn) {
            const { id, keterangan } = deleteTransaksiBtn.dataset;
            if (confirm(`Yakin ingin menghapus transaksi "${keterangan}"? Aksi ini juga akan menghapus entri jurnal terkait.`)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                try {
                    const response = await fetch(`${basePath}/api/transaksi`, { method: 'POST', body: formData });
                    const result = await response.json();
                    showToast(result.message, result.status === 'success' ? 'success' : 'error');
                    if (result.status === 'success') loadJurnal(1);
                } catch (error) {
                    showToast('Gagal menghapus transaksi.', 'error');
                }
            }
        }
    });

    // --- Export Listeners ---
    exportPdfBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';
        const params = { report: 'daftar-jurnal', search: searchInput.value, start_date: startDateFilter.value, end_date: endDateFilter.value };
        for (const key in params) {
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = key;
            hiddenField.value = params[key];
            form.appendChild(hiddenField);
        }
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    exportCsvBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const params = new URLSearchParams({ report: 'daftar-jurnal', format: 'csv', search: searchInput.value, start_date: startDateFilter.value, end_date: endDateFilter.value });
        const url = `${basePath}/api/csv?${params.toString()}`;
        window.open(url, '_blank');
    });

    let debounceTimer;
    const combinedFilterHandler = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadJurnal(1), 300);
        // Simpan semua filter ke localStorage
        localStorage.setItem('daftar_jurnal_limit', limitSelect.value);
        localStorage.setItem('daftar_jurnal_start_date', startDateFilter.value);
        localStorage.setItem('daftar_jurnal_end_date', endDateFilter.value);
    };
    [searchInput, startDateFilter, endDateFilter, limitSelect].forEach(el => el.addEventListener('change', combinedFilterHandler));
    searchInput.addEventListener('input', combinedFilterHandler);

    // Muat filter yang tersimpan dari localStorage sebelum memuat data awal
    const savedLimit = localStorage.getItem('daftar_jurnal_limit');
    if (savedLimit) {
        limitSelect.value = savedLimit;
    }

    const savedStartDate = localStorage.getItem('daftar_jurnal_start_date');
    const savedEndDate = localStorage.getItem('daftar_jurnal_end_date');

    if (savedStartDate && savedEndDate) {
        startDateFilter.value = savedStartDate;
        endDateFilter.value = savedEndDate;
    } else {
        // Atur tanggal default ke bulan ini jika tidak ada yang tersimpan
        const now = new Date();
        startDateFilter.value = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
        endDateFilter.value = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
    }

    // Initial load
    loadJurnal();
}