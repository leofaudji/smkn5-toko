function initDaftarJurnalPage() {
    const tableBody = document.getElementById('daftar-jurnal-table-body');
    const searchInput = document.getElementById('search-jurnal');
    const startDateFilter = document.getElementById('filter-jurnal-mulai');
    const endDateFilter = document.getElementById('filter-jurnal-akhir');
    const limitSelect = document.getElementById('filter-jurnal-limit');
    const sortSelect = document.getElementById('filter-jurnal-sort');
    const paginationContainer = document.getElementById('daftar-jurnal-pagination');
    const exportPdfBtn = document.getElementById('export-dj-pdf');
    const exportCsvBtn = document.getElementById('export-dj-csv');
    const viewModalEl = document.getElementById('viewJurnalModal');

    if (!tableBody) return;
    let periodLockDate = null;

    const commonOptions = { dateFormat: "d-m-Y", allowInput: true };
    const startDatePicker = flatpickr(startDateFilter, commonOptions);
    const endDatePicker = flatpickr(endDateFilter, commonOptions);

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    async function loadJurnal(page = 1) {
        const params = new URLSearchParams({
            page,
            limit: limitSelect.value,
            sort_by: sortSelect.value,
            search: searchInput.value,
            start_date: startDateFilter.value.split('-').reverse().join('-'),
            end_date: endDateFilter.value.split('-').reverse().join('-'),
        });

        tableBody.innerHTML = `<tr><td colspan="8" class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>`;
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
                // Kelompokkan data berdasarkan 'ref' (No. Referensi)
                const transactions = [];
                result.data.forEach(line => {
                    let tx = transactions.find(t => t.ref === line.ref);
                    if (!tx) {
                        tx = { 
                            ref: line.ref, 
                            tanggal: line.tanggal, 
                            keterangan: line.keterangan, 
                            source: line.source, 
                            entry_id: line.entry_id, 
                            created_at: line.created_at, 
                            created_by_name: line.created_by_name,
                            lines: [] 
                        };
                        transactions.push(tx);
                    }
                    tx.lines.push(line);
                });

                transactions.forEach((tx, txIndex) => {
                    // Tentukan Ikon & Warna berdasarkan source
                    let icon = 'bi-journal-text', colorClass = 'text-yellow-600', typeLabel = 'Jurnal';
                    if (tx.source === 'penjualan') { icon = 'bi-cart-check-fill'; colorClass = 'text-blue-600'; typeLabel = 'Penjualan'; }
                    else if (tx.source === 'transaksi') { icon = 'bi-cash-stack'; colorClass = 'text-green-600'; typeLabel = 'Kas/Bank'; }

                    const dateObj = new Date(tx.tanggal);
                    const formattedDate = dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                    const formattedTime = dateObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

                    let editBtn, deleteBtn;
                    if (tx.source === 'jurnal') {
                        editBtn = `<a href="${basePath}/entri-jurnal?edit_id=${tx.entry_id}" class="p-1.5 text-yellow-600 hover:bg-yellow-50 rounded-lg transition-colors" title="Edit Jurnal"><i class="bi bi-pencil-square"></i></a>`;
                        deleteBtn = `<button class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg delete-jurnal-btn transition-colors" data-id="${tx.entry_id}" data-keterangan="${tx.keterangan}" title="Hapus Jurnal"><i class="bi bi-trash3-fill"></i></button>`;
                    } else if (tx.source === 'penjualan') {
                        editBtn = `<a href="${basePath}/penjualan#detail-${tx.entry_id}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Lihat Detail Penjualan"><i class="bi bi-eye-fill"></i></a>`;
                        deleteBtn = `<button class="p-1.5 text-gray-300 cursor-not-allowed" disabled><i class="bi bi-trash3-fill"></i></button>`;
                    } else {
                        editBtn = `<a href="${basePath}/transaksi#tx-${tx.entry_id}" class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Lihat di Transaksi"><i class="bi bi-box-arrow-up-right"></i></a>`;
                        deleteBtn = `<button class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg delete-transaksi-btn transition-colors" data-id="${tx.entry_id}" data-keterangan="${tx.keterangan}" title="Hapus Transaksi"><i class="bi bi-trash3-fill"></i></button>`;
                    }

                    // Header Row untuk Transaksi (6 Unit Grid)
                    const headerRow = `
                        <tr class="group-header bg-gray-50/80 dark:bg-gray-700/40 border-t-2 border-gray-200 dark:border-gray-600">
                            <td class="px-4 py-3 align-middle" colspan="2">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-xl ${colorClass.replace('text', 'bg')}/10 ${colorClass}">
                                        <i class="bi ${icon} text-lg"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-900 dark:text-white text-sm">${tx.ref}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${colorClass.replace('text', 'bg')}/10 ${colorClass}">${typeLabel}</span>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1 max-w-sm" title="${tx.keterangan || '-'}">${tx.keterangan || '-'}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 align-middle">
                                <div class="font-medium text-gray-700 dark:text-gray-300 text-xs">${formattedDate}</div>
                                <div class="text-[10px] opacity-60">${formattedTime}</div>
                            </td>
                            <td class="px-4 py-3 text-right align-middle" colspan="3">
                                <div class="flex justify-end items-center gap-2 pr-2">
                                    ${editBtn}
                                    ${deleteBtn}
                                </div>
                            </td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', headerRow);

                    // Baris-baris Akun (Sub-rows)
                    tx.lines.forEach(line => {
                        const row = `
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors border-none">
                                <td class="py-2" colspan="3"></td> <!-- Menempati kolom Transaksi (2) + Update (1) -->
                                <td class="px-4 py-2 text-sm">
                                    <div class="flex items-center gap-2 ${line.debit > 0 ? 'text-gray-900 dark:text-white font-medium pl-2' : 'text-gray-600 dark:text-gray-400 pl-8 italic'}">
                                        <i class="bi bi-arrow-return-right opacity-20"></i>
                                        <span class="truncate max-w-[250px]" title="${line.nama_akun || '-'}">${line.nama_akun || '-'}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-sm text-right font-mono ${line.debit > 0 ? 'text-gray-900 dark:text-white' : 'text-gray-400'}">
                                    ${line.debit > 0 ? currencyFormatter.format(line.debit) : '-'}
                                </td>
                                <td class="px-4 py-2 text-sm text-right font-mono ${line.kredit > 0 ? 'text-gray-900 dark:text-white' : 'text-gray-400'}">
                                    ${line.kredit > 0 ? currencyFormatter.format(line.kredit) : '-'}
                                </td>
                            </tr>
                        `;
                        tableBody.insertAdjacentHTML('beforeend', row);
                    });
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-gray-500 dark:text-gray-400">Tidak ada entri jurnal ditemukan.</td></tr>';
            }
            renderPagination(paginationContainer, result.pagination, loadJurnal);
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="8" class="text-center text-red-500 py-4">Gagal memuat data: ${error.message}</td></tr>`;
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
        const params = { 
            report: 'daftar-jurnal', 
            search: searchInput.value, 
            start_date: startDateFilter.value.split('-').reverse().join('-'), 
            end_date: endDateFilter.value.split('-').reverse().join('-') 
        };
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
        const params = new URLSearchParams({ 
            report: 'daftar-jurnal', 
            format: 'csv', 
            search: searchInput.value, 
            start_date: startDateFilter.value.split('-').reverse().join('-'), 
            end_date: endDateFilter.value.split('-').reverse().join('-') });
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
        localStorage.setItem('daftar_jurnal_sort', sortSelect.value);
    };
    [searchInput, startDateFilter, endDateFilter, limitSelect, sortSelect].forEach(el => el.addEventListener('change', combinedFilterHandler));
    searchInput.addEventListener('input', combinedFilterHandler);

    const btnToday = document.getElementById('btn-filter-today');
    const btnMonth = document.getElementById('btn-filter-month');
    const btnReset = document.getElementById('btn-filter-reset');

    btnToday?.addEventListener('click', () => {
        const today = new Date();
        startDatePicker.setDate(today, true);
        endDatePicker.setDate(today, true);
        combinedFilterHandler();
    });

    btnMonth?.addEventListener('click', () => {
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        startDatePicker.setDate(firstDay, true);
        endDatePicker.setDate(lastDay, true);
        combinedFilterHandler();
    });

    btnReset?.addEventListener('click', () => {
        searchInput.value = '';
        limitSelect.value = '15';
        sortSelect.value = 'tanggal';
        
        // Reset to default month
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        startDatePicker.setDate(firstDay, true);
        endDatePicker.setDate(lastDay, true);
        
        localStorage.clear(); // Opsional: bersihkan cache filter
        combinedFilterHandler();
    });

    // Muat filter yang tersimpan dari localStorage sebelum memuat data awal
    const savedLimit = localStorage.getItem('daftar_jurnal_limit');
    if (savedLimit) limitSelect.value = savedLimit;

    const savedStartDate = localStorage.getItem('daftar_jurnal_start_date');
    const savedEndDate = localStorage.getItem('daftar_jurnal_end_date');
    const savedSort = localStorage.getItem('daftar_jurnal_sort');

    if (savedSort) sortSelect.value = savedSort;

    if (savedStartDate && savedEndDate) {
        startDatePicker.setDate(savedStartDate, true);
        endDatePicker.setDate(savedEndDate, true);
    } else {
        // Atur tanggal default ke bulan ini jika tidak ada yang tersimpan
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        startDatePicker.setDate(firstDay, true);
        endDatePicker.setDate(lastDay, true);
    }

    // Initial load
    loadJurnal();
}