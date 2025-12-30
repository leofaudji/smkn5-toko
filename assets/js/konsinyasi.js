function initKonsinyasiPage() {
    // --- Element Selectors ---
    const supplierTableBody = document.getElementById('suppliers-table-body');
    const itemTableBody = document.getElementById('items-table-body');
    const saleForm = document.getElementById('consignment-sale-form');
    const reportLink = document.getElementById('view-consignment-report-link');
    const debtSummaryReportLink = document.getElementById('view-debt-summary-report-link');
    const printDebtSummaryBtn = document.getElementById('print-debt-summary-btn');
    const filterSisaUtangBtn = document.getElementById('filter-sisa-utang-btn');
    const addSupplierBtn = document.getElementById('add-supplier-btn');
    const addItemBtn = document.getElementById('add-item-btn');

    if (!supplierTableBody || !itemTableBody) return;

    const commonOptions = { dateFormat: "d-m-Y", allowInput: true };
    flatpickr("#report-start-date", commonOptions);
    flatpickr("#report-end-date", commonOptions);
    flatpickr("#sisa-utang-start-date", commonOptions);
    flatpickr("#sisa-utang-end-date", commonOptions);
    const csTanggalPicker = flatpickr("#cs-tanggal", { ...commonOptions, defaultDate: "today" });
    const cpTanggalPicker = flatpickr("#cp-tanggal", { ...commonOptions, defaultDate: "today" });
    const terimaTanggalPicker = flatpickr("#tanggal_terima", { ...commonOptions, defaultDate: "today" });

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    // --- Report Modal Logic ---
    const reportStartDateEl = document.getElementById('report-start-date');
    const reportEndDateEl = document.getElementById('report-end-date');
    const filterReportBtn = document.getElementById('filter-report-btn');
    const printReportBtn = document.getElementById('print-report-btn');

    async function loadConsignmentReport() {
        const startDate = reportStartDateEl.value.split('-').reverse().join('-');
        const endDate = reportEndDateEl.value.split('-').reverse().join('-');
        if (!startDate || !endDate) {
            showToast('Harap pilih tanggal mulai dan akhir.', 'error');
            return;
        }

        const reportBody = document.getElementById('consignment-report-body');
        reportBody.innerHTML = '<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>';
        
        const params = new URLSearchParams({ action: 'get_sales_report', start_date: startDate, end_date: endDate });
        const response = await fetch(`${basePath}/api/konsinyasi?${params.toString()}`);
        const result = await response.json();

        if (result.status === 'success') {
            let html = '<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"><thead class="bg-gray-50 dark:bg-gray-700"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pemasok</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Barang</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Terjual</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga Beli</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Utang</th></tr></thead><tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">';
            let totalUtangKeseluruhan = 0;
            if (result.data.length > 0) {
                result.data.forEach(row => {
                    totalUtangKeseluruhan += parseFloat(row.total_utang);
                    html += `<tr class="text-sm"><td class="px-6 py-4">${row.nama_pemasok}</td><td class="px-6 py-4">${row.nama_barang}</td><td class="px-6 py-4 text-right">${row.total_terjual}</td><td class="px-6 py-4 text-right">${currencyFormatter.format(row.harga_beli)}</td><td class="px-6 py-4 text-right">${currencyFormatter.format(row.total_utang)}</td></tr>`;
                });
            } else {
                html += '<tr><td colspan="5" class="text-center text-muted">Tidak ada penjualan pada periode ini.</td></tr>';
            }
            html += `</tbody><tfoot><tr class="table-light fw-bold"><td colspan="4" class="text-end">Total Utang Konsinyasi</td><td class="text-end">${currencyFormatter.format(totalUtangKeseluruhan)}</td></tr></tfoot></table>`;
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
        const response = await fetch(`${basePath}/api/konsinyasi?action=list_items`);
        const result = await response.json();
        itemTableBody.innerHTML = '';
        if (result.status === 'success' && result.data.length > 0) {
            result.data.forEach(i => {
                itemTableBody.innerHTML += `<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 text-sm"><td class="px-6 py-4">${i.nama_barang}</td><td class="px-6 py-4">${i.nama_pemasok}</td><td class="px-6 py-4 text-right">${currencyFormatter.format(i.harga_jual)}</td><td class="px-6 py-4 text-right">${currencyFormatter.format(i.harga_beli)}</td><td class="px-6 py-4 text-right">${i.stok_saat_ini} / ${i.stok_awal}</td><td class="px-6 py-4 text-right"><div class="flex justify-end gap-4"><button class="text-blue-600 hover:text-blue-900 edit-item-btn" data-id="${i.id}"><i class="bi bi-pencil-fill"></i></button> <button class="text-red-600 hover:text-red-900 delete-item-btn" data-id="${i.id}"><i class="bi bi-trash-fill"></i></button></div></td></tr>`;
            });
        } else {
            itemTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-gray-500">Belum ada barang konsinyasi.</td></tr>';
        }
    }

    async function loadItemsForSale() {
        const select = document.getElementById('cs-item-id');
        select.innerHTML = '<option>Memuat...</option>';
        const response = await fetch(`${basePath}/api/konsinyasi?action=list_items`);
        const result = await response.json();
        select.innerHTML = '<option value="">-- Pilih Barang --</option>';
        if (result.status === 'success') {
            result.data.forEach(i => {
                if (i.stok_saat_ini > 0) {
                    select.add(new Option(`${i.nama_barang} (Stok: ${i.stok_saat_ini})`, i.id));
                }
            });
        }
    }

    async function loadSuppliersForPayment() {
        const select = document.getElementById('cp-supplier-id');
        select.innerHTML = '<option>Memuat...</option>';
        const response = await fetch(`${basePath}/api/konsinyasi?action=list_suppliers`);
        const result = await response.json();
        select.innerHTML = '<option value="">-- Pilih Pemasok --</option>';
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
        const response = await fetch(`${basePath}/api/konsinyasi`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
        if (result.status === 'success') { closeModal('itemModal'); loadItems(); loadItemsForSale(); }
    });

    saleForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Ambil detail untuk pesan konfirmasi
        const itemSelect = document.getElementById('cs-item-id');
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        const itemName = selectedOption ? selectedOption.text.split(' (Stok:')[0] : 'barang';
        const qty = document.getElementById('cs-qty').value;

        // Tampilkan dialog konfirmasi
        if (!confirm(`Anda yakin ingin menjual ${qty} x ${itemName}?`)) {
            return; // Hentikan proses jika pengguna menekan "Batal"
        }

        const formData = new FormData();
        formData.append('action', 'sell_item');
        formData.append('item_id', document.getElementById('cs-item-id').value);
        formData.append('qty', document.getElementById('cs-qty').value);
        formData.append('tanggal', document.getElementById('cs-tanggal').value.split('-').reverse().join('-'));
        
        const response = await fetch(`${basePath}/api/konsinyasi`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
        if (result.status === 'success') {
            saleForm.reset();
            csTanggalPicker.setDate(new Date());
            loadItemsForSale();
        }
    });

    reportLink.addEventListener('click', async (e) => {
        e.preventDefault();
        document.getElementById('consignment-report-body').innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center">Silakan atur filter tanggal dan klik "Tampilkan" untuk melihat laporan.</p>';
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
            end_date: reportEndDateEl.value.split('-').reverse().join('-') 
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
                document.getElementById('nama_barang').value = item.nama_barang;
                document.getElementById('harga_jual').value = item.harga_jual;
                document.getElementById('harga_beli').value = item.harga_beli;
                document.getElementById('stok_awal').value = item.stok_awal;
                terimaTanggalPicker.setDate(item.tanggal_terima, true, "Y-m-d");
                openModal('itemModal');
            } catch (error) { showToast(`Gagal memuat data barang: ${error.message}`, 'error'); }
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
            if (targetId === 'barang-pane') loadItems();
            else if (targetId === 'pembayaran-pane') { loadSuppliersForPayment(); loadCashAccountsForPayment(); loadPaymentHistory(); cpTanggalPicker.setDate(new Date()); }
        }
        tabButtons.forEach(button => button.addEventListener('click', () => switchTab(button.dataset.target.substring(1))));
        switchTab('pemasok-pane'); // Initial active tab
    }

    // --- Initial Load ---
    loadSuppliers();
    loadItems();
    loadItemsForSale();
    csTanggalPicker.setDate(new Date());
    setupTabs();
}