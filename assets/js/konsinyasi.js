function initKonsinyasiPage() {
    let filterTimeout;
    // --- Element Selectors ---
    const supplierTableBody = document.getElementById('suppliers-table-body');
    const itemTableBody = document.getElementById('items-table-body');
    const filterSalesBtn = document.getElementById('filter-sales-btn');
    const reportLink = document.getElementById('view-consignment-report-link');
    const debtSummaryReportLink = document.getElementById('view-debt-summary-report-link');
    const printDebtSummaryBtn = document.getElementById('print-debt-summary-btn');
    const filterSisaUtangBtn = document.getElementById('filter-sisa-utang-btn');
    const addSupplierBtn = document.getElementById('add-supplier-btn');
    const addItemBtn = document.getElementById('add-item-btn');
    const importCsvBtn = document.getElementById('import-csv-btn');
    const processImportBtn = document.getElementById('process-import-btn');

    let salesCurrentPage = 1;
    const salesLimit = 10;

    let mutasiCurrentPage = 1;
    let mutasiTotalPages = 1;
    let isMutasiLoading = false;
    let mutasiObserver = null;

    if (!supplierTableBody || !itemTableBody) return;

    const commonOptions = { dateFormat: "d-m-Y", allowInput: true };
    flatpickr("#report-start-date", commonOptions);
    flatpickr("#report-end-date", commonOptions);
    flatpickr("#sisa-utang-start-date", commonOptions);
    flatpickr("#sisa-utang-end-date", commonOptions);
    const mutasiStartPicker = flatpickr("#mutasi-start-date", commonOptions);
    const mutasiEndPicker = flatpickr("#mutasi-end-date", commonOptions);
    const csTanggalPicker = flatpickr("#sales-start-date", { ...commonOptions, defaultDate: "today" });
    const csAkhirTanggalPicker = flatpickr("#sales-end-date", { ...commonOptions, defaultDate: "today" });
    const cpTanggalPicker = flatpickr("#cp-tanggal", { ...commonOptions, defaultDate: "today" });
    const terimaTanggalPicker = flatpickr("#tanggal_terima", { ...commonOptions, defaultDate: "today" });
    const restockTanggalPicker = flatpickr("#restock-tanggal", { ...commonOptions, defaultDate: "today" });

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    // --- Report Modal Logic ---
    const reportStartDateEl = document.getElementById('report-start-date');
    const reportEndDateEl = document.getElementById('report-end-date');
    const reportSupplierIdEl = document.getElementById('report-supplier-id');
    const reportStatusEl = document.getElementById('report-status');
    const filterReportBtn = document.getElementById('filter-report-btn');
    const printReportBtn = document.getElementById('print-report-btn');

    async function loadConsignmentReport() {
        const startDate = reportStartDateEl.value.split('-').reverse().join('-');
        const endDate = reportEndDateEl.value.split('-').reverse().join('-');
        const supplierId = reportSupplierIdEl.value;
        const status = reportStatusEl.value;

        if (!startDate || !endDate) {
            showToast('Harap pilih tanggal mulai dan akhir.', 'error');
            return;
        }

        const reportBody = document.getElementById('consignment-report-body');
        reportBody.innerHTML = '<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>';
        
        const params = new URLSearchParams({ 
            action: 'get_sales_report', 
            start_date: startDate, 
            end_date: endDate,
            supplier_id: supplierId,
            status: status
        });
        const response = await fetch(`${basePath}/api/konsinyasi?${params.toString()}`);
        const result = await response.json();

        if (result.status === 'success') {
            let html = '<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"><thead class="bg-gray-50 dark:bg-gray-700"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pemasok</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Barang</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Terjual</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">H. Beli</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Utang</th><th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th></tr></thead><tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">';
            let totalUtangKeseluruhan = 0;
            if (result.data.length > 0) {
                result.data.forEach(row => {
                    totalUtangKeseluruhan += parseFloat(row.total_utang);
                    const isLunas = parseFloat(row.total_hutang_pemasok) <= parseFloat(row.total_bayar_pemasok) && parseFloat(row.total_hutang_pemasok) > 0;
                    const statusBadge = isLunas 
                        ? '<span class="px-2 py-1 text-[10px] font-bold bg-green-100 text-green-800 rounded-full">LUNAS</span>'
                        : '<span class="px-2 py-1 text-[10px] font-bold bg-red-100 text-red-800 rounded-full">BELUM LUNAS</span>';

                    html += `<tr class="text-sm"><td class="px-6 py-4">${row.nama_pemasok}</td><td class="px-6 py-4">${row.nama_barang}</td><td class="px-6 py-4 text-right">${row.total_terjual}</td><td class="px-6 py-4 text-right">${currencyFormatter.format(row.harga_beli)}</td><td class="px-6 py-4 text-right font-semibold">${currencyFormatter.format(row.total_utang)}</td><td class="px-6 py-4 text-center">${statusBadge}</td></tr>`;
                });
            } else {
                html += '<tr><td colspan="6" class="text-center py-10 text-gray-500">Tidak ada data ditemukan untuk kriteria ini.</td></tr>';
            }
            html += `</tbody><tfoot><tr class="bg-gray-50 dark:bg-gray-700/50 font-bold"><td colspan="4" class="px-6 py-3 text-right text-xs uppercase tracking-wider">Total Utang Periode Ini</td><td class="px-6 py-3 text-right">${currencyFormatter.format(totalUtangKeseluruhan)}</td><td></td></tr></tfoot></table>`;
            reportBody.innerHTML = html;
        } else {
            reportBody.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">${result.message}</div>`;
        }
    }

    // --- Load Functions ---
    async function loadSuppliers() {
        supplierTableBody.innerHTML = '<tr><td colspan="3" class="text-center py-10"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>';
        const response = await fetch(`${basePath}/api/konsinyasi?action=list_suppliers`);
        const result = await response.json();
        supplierTableBody.innerHTML = '';
        if (result.status === 'success' && result.data.length > 0) {
            result.data.forEach(s => {
                supplierTableBody.innerHTML += `<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 text-sm"><td class="px-6 py-4">${s.nama_pemasok}</td><td class="px-6 py-4">${s.kontak || '-'}</td><td class="px-6 py-4 text-right"><div class="flex justify-end gap-4"><button class="text-blue-600 hover:text-blue-900 edit-supplier-btn" data-id="${s.id}" data-nama="${s.nama_pemasok}" data-kontak="${s.kontak}"><i class="bi bi-pencil-fill"></i></button> <button class="text-red-600 hover:text-red-900 delete-supplier-btn" data-id="${s.id}"><i class="bi bi-trash-fill"></i></button></div></td></tr>`;
            });
        } else {
            supplierTableBody.innerHTML = '<tr><td colspan="3" class="text-center py-10 text-gray-500">Belum ada pemasok.</td></tr>';
        }
    }

    async function loadItems() {
        itemTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-10"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>';
        
        const search = document.getElementById('item-search-input')?.value || '';
        const supplierId = document.getElementById('item-filter-supplier')?.value || '0';
        const stockStatus = document.getElementById('item-filter-stock')?.value || 'all';

        const params = new URLSearchParams({
            action: 'list_items',
            search: search,
            supplier_id: supplierId,
            stock_status: stockStatus
        });

        const response = await fetch(`${basePath}/api/konsinyasi?${params.toString()}`);
        const result = await response.json();
        itemTableBody.innerHTML = '';
        if (result.status === 'success' && result.data.length > 0) {
            result.data.forEach(i => {
                const totalMasuk = parseInt(i.stok_awal) + parseInt(i.total_restock);
                itemTableBody.innerHTML += `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 text-sm">
                        <td class="px-6 py-4 font-mono">
                            <div>${i.sku || '-'}</div>
                            <div class="text-[10px] text-gray-400">${i.barcode || ''}</div>
                        </td>
                        <td class="px-6 py-4">${i.nama_barang}</td>
                        <td class="px-6 py-4 text-xs">${i.nama_pemasok}</td>
                        <td class="px-6 py-4 text-right">${currencyFormatter.format(i.harga_jual)}</td>
                        <td class="px-6 py-4 text-right">${currencyFormatter.format(i.harga_beli)}</td>
                        <td class="px-6 py-4 text-right">
                            <span class="font-bold ${i.stok_saat_ini <= 5 ? 'text-red-500' : ''}">${i.stok_saat_ini}</span> / ${totalMasuk}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-3">
                                <button class="text-green-600 hover:text-green-900 restock-item-btn" data-id="${i.id}" data-nama="${i.nama_barang}" title="Tambah Stok"><i class="bi bi-patch-plus-fill"></i></button>
                                <button class="text-blue-600 hover:text-blue-900 edit-item-btn" data-id="${i.id}" title="Edit Detail"><i class="bi bi-pencil-fill"></i></button> 
                                <button class="text-red-600 hover:text-red-900 delete-item-btn" data-id="${i.id}" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                            </div>
                        </td>
                    </tr>`;
            });
        } else {
            itemTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-gray-500">Belum ada barang konsinyasi.</td></tr>';
        }
    }

    async function loadSuppliersForPayment() {
        const select = document.getElementById('cp-supplier-id');
        if (!select) return;
        select.innerHTML = '<option>Memuat...</option>';
        const response = await fetch(`${basePath}/api/konsinyasi?action=list_suppliers`);
        const result = await response.json();
        select.innerHTML = '<option value="">-- Pilih Pemasok --</option>';
        if (result.status === 'success') {
            result.data.forEach(s => select.add(new Option(s.nama_pemasok, s.id)));
        }
    }

    async function loadSuppliersForFilter() {
        const select = document.getElementById('item-filter-supplier');
        if (!select) return;
        const response = await fetch(`${basePath}/api/konsinyasi?action=list_suppliers`);
        const result = await response.json();
        select.innerHTML = '<option value="0">Semua Pemasok</option>';
        if (result.status === 'success') {
            result.data.forEach(s => select.add(new Option(s.nama_pemasok, s.id)));
        }
    }

    async function loadCashAccountsForPayment() {
        const select = document.getElementById('cp-kas-account-id');
        select.innerHTML = '<option>Memuat...</option>';
        const response = await fetch(`${basePath}/api/settings?action=get_cash_accounts`);
        const result = await response.json();
        select.innerHTML = '<option value="">-- Pilih Akun Kas/Bank --</option>';
        if (result.status === 'success') {
            result.data.forEach(acc => select.add(new Option(acc.nama_akun, acc.id)));
        }
    }

    async function loadPaymentHistory() {
        const tableBody = document.getElementById('payment-history-table-body');
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-10"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>';
        const response = await fetch(`${basePath}/api/konsinyasi?action=list_payments`);
        const result = await response.json();
        tableBody.innerHTML = '';
        if (result.status === 'success' && result.data.length > 0) {
            result.data.forEach(p => {
                tableBody.innerHTML += `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 text-sm">
                        <td class="px-6 py-4">${new Date(p.tanggal).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'})}</td>
                        <td class="px-6 py-4">${p.nama_pemasok || '<i>Tidak terdeteksi</i>'}</td>
                        <td class="px-6 py-4"><small>${p.keterangan}</small></td>
                        <td class="px-6 py-4 text-right">${currencyFormatter.format(p.jumlah)}</td>
                    </tr>
                `;
            });
        } else {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Belum ada riwayat pembayaran.</td></tr>';
        }
    }

    async function loadSalesHistory(page = 1) {
        const tableBody = document.getElementById('consignment-sales-history-body');
        if (!tableBody) return;

        salesCurrentPage = page;
        const startDateInput = document.getElementById('sales-start-date');
        const endDateInput = document.getElementById('sales-end-date');
        
        const startDate = startDateInput.value.split('-').reverse().join('-');
        const endDate = endDateInput.value.split('-').reverse().join('-');

        tableBody.innerHTML = '<tr><td colspan="4" class="px-6 py-10 text-center text-gray-500"><div class="flex flex-col items-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mb-3"></div><span>Memuat riwayat...</span></div></td></tr>';
        
        try {
            const params = new URLSearchParams({ 
                action: 'list_sales', 
                start_date: startDate, 
                end_date: endDate,
                page: salesCurrentPage,
                limit: salesLimit
            });
            const response = await fetch(`${basePath}/api/konsinyasi?${params.toString()}`);
            const result = await response.json();
            if (result.status === 'success') {
                tableBody.innerHTML = '';
                if (result.data.length > 0) {
                    result.data.forEach(row => {
                        const date = new Date(row.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                        tableBody.innerHTML += `
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 text-sm">
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">${date}</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">${row.nama_barang}</div>
                                    <div class="text-[10px] text-gray-400 font-mono tracking-tighter">${row.nomor_referensi}</div>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">${row.qty}</td>
                                <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">${currencyFormatter.format(row.total_jual)}</td>
                            </tr>
                        `;
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="4" class="px-6 py-10 text-center text-gray-500 italic">Tidak ada histori penjualan untuk periode ini.</td></tr>';
                }
                renderSalesPagination(result.pagination);
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    async function loadMutations(page = 1, append = false) {
        const tableBody = document.getElementById('mutasi-table-body');
        const sentinel = document.getElementById('mutasi-sentinel');
        if (!tableBody) return;

        if (isMutasiLoading) return;
        
        mutasiCurrentPage = page;
        isMutasiLoading = true;
        if (sentinel) sentinel.classList.remove('invisible');

        const supplierId = document.getElementById('mutasi-supplier-id').value;
        const startDate = document.getElementById('mutasi-start-date').value.split('-').reverse().join('-');
        const endDate = document.getElementById('mutasi-end-date').value.split('-').reverse().join('-');

        if (!append) {
            tableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-10 text-center text-gray-500"><div class="flex flex-col items-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mb-3"></div><span>Memuat mutasi...</span></div></td></tr>';
        }

        try {
            const params = new URLSearchParams({ 
                action: 'list_mutations', 
                supplier_id: supplierId,
                start_date: startDate,
                end_date: endDate,
                page: mutasiCurrentPage,
                limit: 20
            });
            const response = await fetch(`${basePath}/api/konsinyasi?${params.toString()}`);
            const result = await response.json();
            
            if (result.status === 'success') {
                if (!append) tableBody.innerHTML = '';
                
                mutasiTotalPages = result.pagination.total_pages;

                if (result.data.length > 0) {
                    result.data.forEach(row => {
                        const date = new Date(row.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                        let badgeClass = 'bg-gray-100 text-gray-800';
                        if (row.tipe === 'Stok Awal') {
                            badgeClass = 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
                        } else if (row.tipe === 'Restock') {
                            badgeClass = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
                        } else if (row.tipe === 'Terjual') {
                            badgeClass = 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
                        }
                        
                        const tr = document.createElement('tr');
                        tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-700/50 group';
                        
                        let actionHtml = '';
                        if (row.tipe === 'Restock' && row.mutation_id) {
                            actionHtml = `
                                <button class="p-1.5 text-red-500 opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md delete-restock-btn" 
                                        data-id="${row.mutation_id}" title="Hapus Restock Salah">
                                    <i class="bi bi-trash"></i>
                                </button>
                            `;
                        }

                        tr.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400 font-mono">${date}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-between font-medium text-gray-900 dark:text-white">
                                    <span>${row.nama_barang}</span>
                                    ${actionHtml}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">${row.nama_pemasok}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide ${badgeClass}">${row.tipe}</span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">${row.qty}</td>
                            <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">${row.keterangan || '-'}</td>
                        `;
                        tableBody.appendChild(tr);
                    });
                } else {
                    if (!append) {
                        tableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-10 text-center text-gray-500 italic">Tidak ada histori mutasi untuk kriteria ini.</td></tr>';
                    }
                }
            }
        } catch (error) {
            showToast(`Gagal memuat data: ${error.message}`, 'error');
            if (!append) tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Gagal memuat data: ${error.message}</td></tr>`;
        } finally {
            isMutasiLoading = false;
            if (sentinel) sentinel.classList.add('invisible');
        }
    }

    function initMutasiInfiniteScroll() {
        const sentinel = document.getElementById('mutasi-sentinel');
        if (!sentinel) return;

        if (mutasiObserver) mutasiObserver.disconnect();

        mutasiObserver = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && !isMutasiLoading && mutasiCurrentPage < mutasiTotalPages) {
                loadMutations(mutasiCurrentPage + 1, true);
            }
        }, { threshold: 0.1 });

        mutasiObserver.observe(sentinel);
    }

    async function loadSuppliersForMutation() {
        const select = document.getElementById('mutasi-supplier-id');
        if (!select) return;
        select.innerHTML = '<option value="">-- Memuat... --</option>';
        const response = await fetch(`${basePath}/api/konsinyasi?action=list_suppliers`);
        const result = await response.json();
        select.innerHTML = '<option value="">Semua Pemasok</option>';
        if (result.status === 'success') {
            result.data.forEach(s => select.add(new Option(s.nama_pemasok, s.id)));
        }
    }

    function renderSalesPagination(pagination) {
        const info = document.getElementById('sales-pagination-info');
        const total = document.getElementById('sales-pagination-total');
        const nav = document.getElementById('sales-pagination-nav');
        if (!info || !nav) return;

        const start = (pagination.current_page - 1) * pagination.limit + 1;
        const end = Math.min(pagination.current_page * pagination.limit, pagination.total_records);
        
        info.textContent = pagination.total_records > 0 ? `${start} - ${end}` : '0 - 0';
        total.textContent = pagination.total_records;

        let html = '';
        // Prev button
        html += `<button class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50" ${pagination.current_page === 1 ? 'disabled' : ''} onclick="window.dispatchSalesPage(${pagination.current_page - 1})"><i class="bi bi-chevron-left"></i></button>`;

        // Page numbers (limited)
        let startPage = Math.max(1, pagination.current_page - 2);
        let endPage = Math.min(pagination.total_pages, startPage + 4);
        if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === pagination.current_page;
            html += `<button class="relative inline-flex items-center px-4 py-2 text-sm font-semibold ${isActive ? 'bg-primary text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 dark:text-gray-300'} " onclick="window.dispatchSalesPage(${i})">${i}</button>`;
        }

        // Next button
        html += `<button class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50" ${pagination.current_page === pagination.total_pages || pagination.total_pages === 0 ? 'disabled' : ''} onclick="window.dispatchSalesPage(${pagination.current_page + 1})"><i class="bi bi-chevron-right"></i></button>`;

        nav.innerHTML = html;
    }

    window.dispatchSalesPage = (page) => loadSalesHistory(page);

    if (filterSalesBtn) filterSalesBtn.addEventListener('click', () => loadSalesHistory(1));

    const filterMutasiBtn = document.getElementById('filter-mutasi-btn');
    if (filterMutasiBtn) filterMutasiBtn.addEventListener('click', () => loadMutations(1, false));

    const exportPdfBtn = document.getElementById('export-mutasi-pdf-btn');
    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `${basePath}/api/pdf`;
            form.target = '_blank';
            const params = { 
                report: 'mutasi-konsinyasi', 
                start_date: document.getElementById('mutasi-start-date').value.split('-').reverse().join('-'), 
                end_date: document.getElementById('mutasi-end-date').value.split('-').reverse().join('-'),
                supplier_id: document.getElementById('mutasi-supplier-id').value
            };
            for (const key in params) {
                const hiddenField = document.createElement('input'); hiddenField.type = 'hidden'; hiddenField.name = key; hiddenField.value = params[key]; form.appendChild(hiddenField);
            }
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        });
    }

    const exportCsvBtn = document.getElementById('export-mutasi-csv-btn');
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', () => {
            const params = new URLSearchParams({
                report: 'mutasi-konsinyasi',
                format: 'csv',
                start_date: document.getElementById('mutasi-start-date').value.split('-').reverse().join('-'), 
                end_date: document.getElementById('mutasi-end-date').value.split('-').reverse().join('-'),
                supplier_id: document.getElementById('mutasi-supplier-id').value
            });
            window.location.href = `${basePath}/api/csv?${params.toString()}`;
        });
    }

    document.getElementById('consignment-payment-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('action', 'pay_debt');
        formData.append('tanggal', document.getElementById('cp-tanggal').value.split('-').reverse().join('-'));
        formData.append('supplier_id', document.getElementById('cp-supplier-id').value);
        formData.append('jumlah', document.getElementById('cp-jumlah').value);
        formData.append('kas_account_id', document.getElementById('cp-kas-account-id').value);
        formData.append('keterangan', document.getElementById('cp-keterangan').value);
        const response = await fetch(`${basePath}/api/konsinyasi`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status);
        if (result.status === 'success') { e.target.reset(); cpTanggalPicker.setDate(new Date()); loadPaymentHistory(); }
    });

    document.getElementById('item-search-input')?.addEventListener('input', () => {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(loadItems, 300);
    });
    document.getElementById('item-filter-supplier')?.addEventListener('change', loadItems);
    document.getElementById('item-filter-stock')?.addEventListener('change', loadItems);

    // --- Event Listeners ---
    document.getElementById('save-supplier-btn').addEventListener('click', async () => {
        const form = document.getElementById('supplier-form');
        const formData = new FormData(form);
        formData.set('action', document.getElementById('supplier-action').value);
        const response = await fetch(`${basePath}/api/konsinyasi`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
        if (result.status === 'success') { closeModal('supplierModal'); loadSuppliers(); }
    });

    document.getElementById('save-item-btn').addEventListener('click', async () => {
        const form = document.getElementById('item-form');
        const formData = new FormData(form);
        formData.set('action', document.getElementById('item-action').value);
        
        // Ensure date is YYYY-MM-DD
        const rawDate = document.getElementById('tanggal_terima').value;
        if (rawDate && rawDate.includes('-')) {
            const formattedDate = rawDate.split('-').reverse().join('-');
            formData.set('tanggal_terima', formattedDate);
        }

        const response = await fetch(`${basePath}/api/konsinyasi`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
        if (result.status === 'success') { closeModal('itemModal'); loadItems(); }
    });

    reportLink.addEventListener('click', async (e) => {
        e.preventDefault();
        document.getElementById('consignment-report-body').innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center">Silakan atur filter tanggal dan klik "Tampilkan" untuk melihat laporan.</p>';
        
        // Load suppliers for dropdown
        reportSupplierIdEl.innerHTML = '<option value="">-- Memuat... --</option>';
        const supResponse = await fetch(`${basePath}/api/konsinyasi?action=list_suppliers`);
        const supResult = await supResponse.json();
        reportSupplierIdEl.innerHTML = '<option value="">-- Semua Pemasok --</option>';
        if (supResult.status === 'success') supResult.data.forEach(s => reportSupplierIdEl.add(new Option(s.nama_pemasok, s.id)));

        openModal('consignmentReportModal');
        const now = new Date();
        reportStartDateEl._flatpickr.setDate(new Date(now.getFullYear(), now.getMonth(), 1), true);
        reportEndDateEl._flatpickr.setDate(new Date(now.getFullYear(), now.getMonth() + 1, 0), true);
    });

    filterReportBtn.addEventListener('click', loadConsignmentReport);

    printReportBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';
        const params = { 
            report: 'konsinyasi', 
            start_date: reportStartDateEl.value.split('-').reverse().join('-'), 
            end_date: reportEndDateEl.value.split('-').reverse().join('-'),
            supplier_id: reportSupplierIdEl.value,
            status: reportStatusEl.value
        };
        for (const key in params) {
            const hiddenField = document.createElement('input'); hiddenField.type = 'hidden'; hiddenField.name = key; hiddenField.value = params[key]; form.appendChild(hiddenField);
        }
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    async function loadDebtSummaryReport() {
        const startDate = document.getElementById('sisa-utang-start-date').value.split('-').reverse().join('-');
        const endDate = document.getElementById('sisa-utang-end-date').value.split('-').reverse().join('-');
        const reportBody = document.getElementById('debt-summary-report-body');

        if (!startDate || !endDate) {
            showToast('Harap pilih tanggal mulai dan akhir.', 'error');
            return;
        }

        reportBody.innerHTML = '<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>';

        try {
            const params = new URLSearchParams({ action: 'get_debt_summary_report', start_date: startDate, end_date: endDate });
            const response = await fetch(`${basePath}/api/konsinyasi?${params.toString()}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            let html = '<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"><thead class="bg-gray-50 dark:bg-gray-700"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pemasok</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Utang</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Bayar</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sisa Utang</th></tr></thead><tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">';
            let grandTotalSisa = 0;
            result.data.forEach(row => {
                grandTotalSisa += parseFloat(row.sisa_utang);
                html += `<tr class="text-sm"><td class="px-6 py-4">${row.nama_pemasok}</td><td class="px-6 py-4 text-right">${currencyFormatter.format(row.total_utang)}</td><td class="px-6 py-4 text-right">${currencyFormatter.format(row.total_bayar)}</td><td class="px-6 py-4 text-right font-bold">${currencyFormatter.format(row.sisa_utang)}</td></tr>`;
            });
            html += `</tbody><tfoot class="bg-gray-100 dark:bg-gray-700 font-bold"><tr class="text-sm"><td colspan="3" class="px-6 py-3 text-right">Total Sisa Utang Keseluruhan</td><td class="px-6 py-3 text-right">${currencyFormatter.format(grandTotalSisa)}</td></tr></tfoot></table>`;
            reportBody.innerHTML = html;

        } catch (error) {
            reportBody.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">${error.message}</div>`;
        }
    }

    debtSummaryReportLink.addEventListener('click', (e) => {
        e.preventDefault();
        const startDateEl = document.getElementById('sisa-utang-start-date');
        const endDateEl = document.getElementById('sisa-utang-end-date');
        const now = new Date();
        startDateEl._flatpickr.setDate(new Date(now.getFullYear(), 0, 1), true); // Awal tahun
        endDateEl._flatpickr.setDate(new Date(now.getFullYear(), 11, 31), true); // Akhir tahun
    });

    filterSisaUtangBtn.addEventListener('click', loadDebtSummaryReport);

    printDebtSummaryBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';
        const params = { 
            report: 'konsinyasi-sisa-utang',
            start_date: document.getElementById('sisa-utang-start-date').value.split('-').reverse().join('-'),
            end_date: document.getElementById('sisa-utang-end-date').value.split('-').reverse().join('-')
        };
        for (const key in params) {
            const hiddenField = document.createElement('input'); hiddenField.type = 'hidden'; hiddenField.name = key; hiddenField.value = params[key]; form.appendChild(hiddenField);
        }
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    addSupplierBtn.addEventListener('click', () => {
        const form = document.getElementById('supplier-form');
        form.reset();
        document.getElementById('supplierModalLabel').textContent = 'Tambah Pemasok';
        document.getElementById('supplier-action').value = 'save_supplier';
        openModal('supplierModal');
    });

    addItemBtn.addEventListener('click', async () => {
        const form = document.getElementById('item-form');
        const supplierSelect = document.getElementById('supplier_id');
        supplierSelect.innerHTML = '<option>Memuat...</option>';
        const response = await fetch(`${basePath}/api/konsinyasi?action=list_suppliers`);
        const result = await response.json();
        supplierSelect.innerHTML = '<option value="">-- Pilih Pemasok --</option>';
        if (result.status === 'success') result.data.forEach(s => supplierSelect.add(new Option(s.nama_pemasok, s.id)));
        
        form.reset();
        document.getElementById('itemModalLabel').textContent = 'Tambah Barang Konsinyasi';
        document.getElementById('item-action').value = 'save_item';
        terimaTanggalPicker.setDate(new Date());
        openModal('itemModal');
    });

    importCsvBtn.addEventListener('click', () => {
        document.getElementById('import-csv-form').reset();
        openModal('importItemModal');
    });

    processImportBtn.addEventListener('click', async () => {
        const fileInput = document.getElementById('csv_file');
        if (!fileInput.files || fileInput.files.length === 0) {
            showToast('Harap pilih file CSV terlebih dahulu.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'import_items_csv');
        formData.append('csv_file', fileInput.files[0]);

        processImportBtn.disabled = true;
        processImportBtn.textContent = 'Memproses...';

        try {
            const response = await fetch(`${basePath}/api/konsinyasi`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                closeModal('importItemModal');
                loadItems();
            }
        } catch (error) {
            showToast('Gagal mengimpor data: ' + error.message, 'error');
        } finally {
            processImportBtn.disabled = false;
            processImportBtn.textContent = 'Mulai Impor';
        }
    });

    document.getElementById('pemasok-pane').addEventListener('click', e => {
        const editBtn = e.target.closest('.edit-supplier-btn');
        if (editBtn) {
            const form = document.getElementById('supplier-form');
            form.reset();
            document.getElementById('supplierModalLabel').textContent = 'Edit Pemasok';
            document.getElementById('supplier-action').value = 'save_supplier';
            document.getElementById('supplier-id').value = editBtn.dataset.id;
            document.getElementById('nama_pemasok').value = editBtn.dataset.nama;
            document.getElementById('kontak').value = editBtn.dataset.kontak;
            openModal('supplierModal');
        }
        const deleteBtn = e.target.closest('.delete-supplier-btn');
        if (deleteBtn) {
            if (confirm('Yakin ingin menghapus pemasok ini?')) {
                const formData = new FormData();
                formData.append('action', 'delete_supplier');
                formData.append('id', deleteBtn.dataset.id);
                fetch(`${basePath}/api/konsinyasi`, { method: 'POST', body: formData }).then(res => res.json()).then(result => {
                    showToast(result.message, result.status === 'success' ? 'success' : 'error');
                    if (result.status === 'success') loadSuppliers();
                });
            }
        }
    });

    document.getElementById('barang-pane').addEventListener('click', async e => {
        const editBtn = e.target.closest('.edit-item-btn');
        if (editBtn) {
            const id = editBtn.dataset.id;
            try {
                // Populate supplier dropdown first
                const supplierSelect = document.getElementById('supplier_id');
                supplierSelect.innerHTML = '<option>Memuat...</option>';
                const supResponse = await fetch(`${basePath}/api/konsinyasi?action=list_suppliers`);
                const supResult = await supResponse.json();
                supplierSelect.innerHTML = '<option value="">-- Pilih Pemasok --</option>';
                if (supResult.status === 'success') supResult.data.forEach(s => supplierSelect.add(new Option(s.nama_pemasok, s.id)));

                const response = await fetch(`${basePath}/api/konsinyasi?action=get_single_item&id=${id}`);
                const result = await response.json();
                if (result.status !== 'success') throw new Error(result.message);
                
                const item = result.data;
                const form = document.getElementById('item-form');
                form.reset();
                document.getElementById('itemModalLabel').textContent = 'Edit Barang Konsinyasi';
                document.getElementById('item-action').value = 'save_item';
                document.getElementById('item-id').value = item.id;
                document.getElementById('supplier_id').value = item.supplier_id;
                document.getElementById('sku').value = item.sku || '';
                document.getElementById('barcode').value = item.barcode || '';
                document.getElementById('nama_barang').value = item.nama_barang;
                document.getElementById('harga_jual').value = item.harga_jual;
                document.getElementById('harga_beli').value = item.harga_beli;
                document.getElementById('stok_awal').value = item.stok_awal;
                terimaTanggalPicker.setDate(item.tanggal_terima, true, "Y-m-d");
                openModal('itemModal');
            } catch (error) { showToast(`Gagal memuat data barang: ${error.message}`, 'error'); }
        }

        const restockBtn = e.target.closest('.restock-item-btn');
        if (restockBtn) {
            document.getElementById('restock-form').reset();
            document.getElementById('restock-item-id').value = restockBtn.dataset.id;
            document.getElementById('restock-item-name').textContent = restockBtn.dataset.nama;
            restockTanggalPicker.setDate(new Date());
            openModal('restockModal');
        }
    });

    // --- Deletion Logic for Mutations ---
    document.getElementById('mutasi-table-body').addEventListener('click', async e => {
        const deleteBtn = e.target.closest('.delete-restock-btn');
        if (deleteBtn) {
            if (!confirm('Yakin ingin menghapus catatan restock ini? Stok barang akan dikurangi secara otomatis.')) return;
            
            const id = deleteBtn.dataset.id;
            const formData = new FormData();
            formData.append('action', 'delete_restock');
            formData.append('id', id);

            try {
                const response = await fetch(`${basePath}/api/konsinyasi`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') {
                    loadMutations(1, false); // Refresh list
                    loadItems(); // Refresh items to show updated stock
                }
            } catch (error) { showToast(`Gagal menghapus data: ${error.message}`, 'error'); }
        }
    });

    document.getElementById('save-restock-btn').addEventListener('click', async () => {
        const form = document.getElementById('restock-form');
        const formData = new FormData(form);
        formData.append('action', 'add_restock');

        const rawDate = document.getElementById('restock-tanggal').value;
        if (rawDate && rawDate.includes('-')) {
            formData.set('tanggal', rawDate.split('-').reverse().join('-'));
        }

        const response = await fetch(`${basePath}/api/konsinyasi`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
        if (result.status === 'success') {
            closeModal('restockModal');
            loadItems();
        }
    });

    function setupTabs() {
        const tabContainer = document.getElementById('konsinyasiTab');
        const tabButtons = tabContainer.querySelectorAll('.konsinyasi-tab-btn');
        const tabPanes = document.getElementById('konsinyasiTabContent').querySelectorAll('.konsinyasi-tab-pane');

        function switchTab(targetId) {
            tabPanes.forEach(pane => pane.classList.toggle('hidden', pane.id !== targetId));
            tabButtons.forEach(button => {
                const isActive = button.dataset.target === `#${targetId}`;
                button.classList.toggle('border-primary', isActive);
                button.classList.toggle('text-primary', isActive);
                button.classList.toggle('border-transparent', !isActive);
                button.classList.toggle('text-gray-500', !isActive);
                button.classList.toggle('dark:text-gray-400', !isActive);
            });
            // Load content for the new active tab
            if (targetId === 'pemasok-pane') loadSuppliers();
            else if (targetId === 'barang-pane') loadItems();
            else if (targetId === 'penjualan-pane') { 
                const now = new Date();
                csTanggalPicker.setDate(new Date(now.getFullYear(), now.getMonth(), 1));
                csAkhirTanggalPicker.setDate(new Date(now.getFullYear(), now.getMonth() + 1, 0));
                loadSalesHistory(1); 
            }
            else if (targetId === 'pembayaran-pane') { loadSuppliersForPayment(); loadCashAccountsForPayment(); loadPaymentHistory(); cpTanggalPicker.setDate(new Date()); }
            else if (targetId === 'mutasi-pane') {
                loadSuppliersForMutation();
                const now = new Date();
                mutasiStartPicker.setDate(new Date(now.getFullYear(), now.getMonth(), 1));
                mutasiEndPicker.setDate(new Date());
                loadMutations(1, false);
                initMutasiInfiniteScroll();
            }
        }
        tabButtons.forEach(button => button.addEventListener('click', () => switchTab(button.dataset.target.substring(1))));
        switchTab('pemasok-pane'); // Initial active tab
    }

    // --- Initial Load ---
    loadSuppliers();
    loadItems();
    loadSuppliersForFilter();
    setupTabs();
}