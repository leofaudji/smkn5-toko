function initKonsinyasiPage() {
    // --- Element Selectors ---
    const supplierTableBody = document.getElementById('suppliers-table-body');
    const itemTableBody = document.getElementById('items-table-body');
    const supplierModalEl = document.getElementById('supplierModal');
    const itemModalEl = document.getElementById('itemModal');
    const saleForm = document.getElementById('consignment-sale-form');
    const reportLink = document.getElementById('view-consignment-report-link');
    const reportModalEl = document.getElementById('consignmentReportModal');
    const debtSummaryReportLink = document.getElementById('view-debt-summary-report-link');
    const printDebtSummaryBtn = document.getElementById('print-debt-summary-btn');
    const debtSummaryModalEl = document.getElementById('debtSummaryReportModal');
    const filterSisaUtangBtn = document.getElementById('filter-sisa-utang-btn');

    if (!supplierTableBody || !itemTableBody || !reportModalEl) return;

    const supplierModal = new bootstrap.Modal(supplierModalEl);
    const itemModal = new bootstrap.Modal(itemModalEl);
    const reportModal = new bootstrap.Modal(reportModalEl);

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    // --- Report Modal Logic ---
    const reportStartDateEl = document.getElementById('report-start-date');
    const reportEndDateEl = document.getElementById('report-end-date');
    const filterReportBtn = document.getElementById('filter-report-btn');
    const printReportBtn = document.getElementById('print-report-btn');

    async function loadConsignmentReport() {
        const startDate = reportStartDateEl.value;
        const endDate = reportEndDateEl.value;
        if (!startDate || !endDate) {
            showToast('Harap pilih tanggal mulai dan akhir.', 'error');
            return;
        }

        const reportBody = document.getElementById('consignment-report-body');
        reportBody.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';
        
        const params = new URLSearchParams({ action: 'get_sales_report', start_date: startDate, end_date: endDate });
        const response = await fetch(`${basePath}/api/konsinyasi?${params.toString()}`);
        const result = await response.json();

        if (result.status === 'success') {
            let html = '<table class="table table-sm table-hover"><thead><tr><th>Pemasok</th><th>Barang</th><th class="text-end">Terjual</th><th class="text-end">Harga Beli</th><th class="text-end">Total Utang</th></tr></thead><tbody>';
            let totalUtangKeseluruhan = 0;
            if (result.data.length > 0) {
                result.data.forEach(row => {
                    totalUtangKeseluruhan += parseFloat(row.total_utang);
                    html += `<tr><td>${row.nama_pemasok}</td><td>${row.nama_barang}</td><td class="text-end">${row.total_terjual}</td><td class="text-end">${currencyFormatter.format(row.harga_beli)}</td><td class="text-end">${currencyFormatter.format(row.total_utang)}</td></tr>`;
                });
            } else {
                html += '<tr><td colspan="5" class="text-center text-muted">Tidak ada penjualan pada periode ini.</td></tr>';
            }
            html += `</tbody><tfoot><tr class="table-light fw-bold"><td colspan="4" class="text-end">Total Utang Konsinyasi</td><td class="text-end">${currencyFormatter.format(totalUtangKeseluruhan)}</td></tr></tfoot></table>`;
            reportBody.innerHTML = html;
        } else {
            reportBody.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    }

    // --- Load Functions ---
    async function loadSuppliers() {
        supplierTableBody.innerHTML = '<tr><td colspan="3" class="text-center p-4"><div class="spinner-border spinner-border-sm"></div></td></tr>';
        const response = await fetch(`${basePath}/api/konsinyasi?action=list_suppliers`);
        const result = await response.json();
        supplierTableBody.innerHTML = '';
        if (result.status === 'success' && result.data.length > 0) {
            result.data.forEach(s => {
                supplierTableBody.innerHTML += `<tr><td>${s.nama_pemasok}</td><td>${s.kontak || '-'}</td><td class="text-end"><button class="btn btn-sm btn-info edit-supplier-btn" data-id="${s.id}" data-nama="${s.nama_pemasok}" data-kontak="${s.kontak}"><i class="bi bi-pencil-fill"></i></button> <button class="btn btn-sm btn-danger delete-supplier-btn" data-id="${s.id}"><i class="bi bi-trash-fill"></i></button></td></tr>`;
            });
        } else {
            supplierTableBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Belum ada pemasok.</td></tr>';
        }
    }

    async function loadItems() {
        itemTableBody.innerHTML = '<tr><td colspan="6" class="text-center p-4"><div class="spinner-border spinner-border-sm"></div></td></tr>';
        const response = await fetch(`${basePath}/api/konsinyasi?action=list_items`);
        const result = await response.json();
        itemTableBody.innerHTML = '';
        if (result.status === 'success' && result.data.length > 0) {
            result.data.forEach(i => {
                itemTableBody.innerHTML += `<tr><td>${i.nama_barang}</td><td>${i.nama_pemasok}</td><td class="text-end">${currencyFormatter.format(i.harga_jual)}</td><td class="text-end">${currencyFormatter.format(i.harga_beli)}</td><td class="text-end">${i.stok_saat_ini} / ${i.stok_awal}</td><td class="text-end"><button class="btn btn-sm btn-info edit-item-btn" data-id="${i.id}"><i class="bi bi-pencil-fill"></i></button> <button class="btn btn-sm btn-danger delete-item-btn" data-id="${i.id}"><i class="bi bi-trash-fill"></i></button></td></tr>`;
            });
        } else {
            itemTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Belum ada barang konsinyasi.</td></tr>';
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
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center p-4"><div class="spinner-border spinner-border-sm"></div></td></tr>';
        const response = await fetch(`${basePath}/api/konsinyasi?action=list_payments`);
        const result = await response.json();
        tableBody.innerHTML = '';
        if (result.status === 'success' && result.data.length > 0) {
            result.data.forEach(p => {
                tableBody.innerHTML += `
                    <tr>
                        <td>${new Date(p.tanggal).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'})}</td>
                        <td>${p.nama_pemasok || '<i>Tidak terdeteksi</i>'}</td>
                        <td><small>${p.keterangan}</small></td>
                        <td class="text-end">${currencyFormatter.format(p.jumlah)}</td>
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
        formData.append('tanggal', document.getElementById('cp-tanggal').value);
        formData.append('supplier_id', document.getElementById('cp-supplier-id').value);
        formData.append('jumlah', document.getElementById('cp-jumlah').value);
        formData.append('kas_account_id', document.getElementById('cp-kas-account-id').value);
        formData.append('keterangan', document.getElementById('cp-keterangan').value);
        const response = await fetch(`${basePath}/api/konsinyasi`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status);
        if (result.status === 'success') { e.target.reset(); document.getElementById('cp-tanggal').valueAsDate = new Date(); loadPaymentHistory(); }
    });

    // --- Event Listeners ---
    document.getElementById('save-supplier-btn').addEventListener('click', async () => {
        const form = document.getElementById('supplier-form');
        const formData = new FormData(form);
        formData.set('action', document.getElementById('supplier-action').value);
        const response = await fetch(`${basePath}/api/konsinyasi`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status);
        if (result.status === 'success') { supplierModal.hide(); loadSuppliers(); }
    });

    document.getElementById('save-item-btn').addEventListener('click', async () => {
        const form = document.getElementById('item-form');
        const formData = new FormData(form);
        formData.set('action', document.getElementById('item-action').value);
        const response = await fetch(`${basePath}/api/konsinyasi`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status);
        if (result.status === 'success') { itemModal.hide(); loadItems(); loadItemsForSale(); }
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
        formData.append('tanggal', document.getElementById('cs-tanggal').value);
        
        const response = await fetch(`${basePath}/api/konsinyasi`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status);
        if (result.status === 'success') {
            saleForm.reset();
            document.getElementById('cs-tanggal').valueAsDate = new Date();
            loadItemsForSale();
        }
    });

    reportLink.addEventListener('click', async (e) => {
        e.preventDefault();
        const reportBody = document.getElementById('consignment-report-body');
        reportBody.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';
        reportModal.show();
        const response = await fetch(`${basePath}/api/konsinyasi?action=get_sales_report`);
        const result = await response.json();
        if (result.status === 'success') {
            let html = '<table class="table table-sm"><thead><tr><th>Pemasok</th><th>Barang</th><th class="text-end">Terjual</th><th class="text-end">Harga Beli</th><th class="text-end">Total Utang</th></tr></thead><tbody>';
            let totalUtangKeseluruhan = 0;
            result.data.forEach(row => {
                totalUtangKeseluruhan += parseFloat(row.total_utang);
                html += `<tr><td>${row.nama_pemasok}</td><td>${row.nama_barang}</td><td class="text-end">${row.total_terjual}</td><td class="text-end">${currencyFormatter.format(row.harga_beli)}</td><td class="text-end">${currencyFormatter.format(row.total_utang)}</td></tr>`;
            });
            html += `</tbody><tfoot><tr class="table-light fw-bold"><td colspan="4" class="text-end">Total Utang Konsinyasi</td><td class="text-end">${currencyFormatter.format(totalUtangKeseluruhan)}</td></tr></tfoot></table>`;
            reportBody.innerHTML = html;
        } else {
            reportBody.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
        const now = new Date();
    });

    filterReportBtn.addEventListener('click', loadConsignmentReport);

    printReportBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';
        const params = { report: 'konsinyasi', start_date: reportStartDateEl.value, end_date: reportEndDateEl.value };
        for (const key in params) {
            const hiddenField = document.createElement('input'); hiddenField.type = 'hidden'; hiddenField.name = key; hiddenField.value = params[key]; form.appendChild(hiddenField);
        }
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    document.getElementById('barang-tab').addEventListener('shown.bs.tab', () => {
        loadItems();
    });

    document.getElementById('pembayaran-tab').addEventListener('shown.bs.tab', () => {
        loadSuppliersForPayment();
        loadCashAccountsForPayment();
        loadPaymentHistory();
        document.getElementById('cp-tanggal').valueAsDate = new Date();
    });

    async function loadDebtSummaryReport() {
        const startDate = document.getElementById('sisa-utang-start-date').value;
        const endDate = document.getElementById('sisa-utang-end-date').value;
        const reportBody = document.getElementById('debt-summary-report-body');

        if (!startDate || !endDate) {
            showToast('Harap pilih tanggal mulai dan akhir.', 'error');
            return;
        }

        reportBody.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';

        try {
            const params = new URLSearchParams({ action: 'get_debt_summary_report', start_date: startDate, end_date: endDate });
            const response = await fetch(`${basePath}/api/konsinyasi?${params.toString()}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            let html = '<table class="table table-sm table-hover"><thead><tr><th>Pemasok</th><th class="text-end">Total Utang</th><th class="text-end">Total Bayar</th><th class="text-end">Sisa Utang</th></tr></thead><tbody>';
            let grandTotalSisa = 0;
            result.data.forEach(row => {
                grandTotalSisa += parseFloat(row.sisa_utang);
                html += `<tr><td>${row.nama_pemasok}</td><td class="text-end">${currencyFormatter.format(row.total_utang)}</td><td class="text-end">${currencyFormatter.format(row.total_bayar)}</td><td class="text-end fw-bold">${currencyFormatter.format(row.sisa_utang)}</td></tr>`;
            });
            html += `</tbody><tfoot><tr class="table-light fw-bold"><td colspan="3" class="text-end">Total Sisa Utang Keseluruhan</td><td class="text-end">${currencyFormatter.format(grandTotalSisa)}</td></tr></tfoot></table>`;
            reportBody.innerHTML = html;

        } catch (error) {
            reportBody.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    debtSummaryModalEl.addEventListener('show.bs.modal', () => {
        const startDateEl = document.getElementById('sisa-utang-start-date');
        const endDateEl = document.getElementById('sisa-utang-end-date');
        const now = new Date();
        startDateEl.value = new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0]; // Awal tahun
        endDateEl.value = new Date(now.getFullYear(), 11, 31).toISOString().split('T')[0]; // Akhir tahun
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
            start_date: document.getElementById('sisa-utang-start-date').value,
            end_date: document.getElementById('sisa-utang-end-date').value
        };
        for (const key in params) {
            const hiddenField = document.createElement('input'); hiddenField.type = 'hidden'; hiddenField.name = key; hiddenField.value = params[key]; form.appendChild(hiddenField);
        }
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    // --- Modal & Table Delegation ---
    supplierModalEl.addEventListener('show.bs.modal', (e) => {
        const button = e.relatedTarget;
        const form = document.getElementById('supplier-form');
        form.reset();
        if (button.dataset.action === 'add') {
            document.getElementById('supplierModalLabel').textContent = 'Tambah Pemasok';
            document.getElementById('supplier-action').value = 'save_supplier';
        }
    });

    itemModalEl.addEventListener('show.bs.modal', async (e) => {
        const button = e.relatedTarget;
        const form = document.getElementById('item-form');
        // form.reset(); // Reset is handled in the specific 'add' or 'edit' logic
        // Populate supplier dropdown
        const supplierSelect = document.getElementById('supplier_id');
        supplierSelect.innerHTML = '<option>Memuat...</option>';
        const response = await fetch(`${basePath}/api/konsinyasi?action=list_suppliers`);
        const result = await response.json();
        supplierSelect.innerHTML = '<option value="">-- Pilih Pemasok --</option>';
        if (result.status === 'success') {
            result.data.forEach(s => supplierSelect.add(new Option(s.nama_pemasok, s.id)));
        }

        if (button && button.dataset.action === 'add') {
            form.reset();
            document.getElementById('itemModalLabel').textContent = 'Tambah Barang Konsinyasi';
            document.getElementById('item-action').value = 'save_item';
            document.getElementById('tanggal_terima').valueAsDate = new Date();
        }
    });

    document.getElementById('pemasok-pane').addEventListener('click', e => {
        const editBtn = e.target.closest('.edit-supplier-btn');
        if (editBtn) {
            document.getElementById('supplierModalLabel').textContent = 'Edit Pemasok';
            document.getElementById('supplier-action').value = 'save_supplier';
            document.getElementById('supplier-id').value = editBtn.dataset.id;
            document.getElementById('nama_pemasok').value = editBtn.dataset.nama;
            document.getElementById('kontak').value = editBtn.dataset.kontak;
            supplierModal.show();
        }
        const deleteBtn = e.target.closest('.delete-supplier-btn');
        if (deleteBtn) {
            if (confirm('Yakin ingin menghapus pemasok ini?')) {
                const formData = new FormData();
                formData.append('action', 'delete_supplier');
                formData.append('id', deleteBtn.dataset.id);
                fetch(`${basePath}/api/konsinyasi`, { method: 'POST', body: formData }).then(res => res.json()).then(result => {
                    showToast(result.message, result.status);
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
                const response = await fetch(`${basePath}/api/konsinyasi?action=get_single_item&id=${id}`);
                const result = await response.json();
                if (result.status !== 'success') throw new Error(result.message);
                
                const item = result.data;
                await itemModalEl.querySelector('#supplier_id').dispatchEvent(new Event('show.bs.modal')); // Trigger supplier load
                document.getElementById('itemModalLabel').textContent = 'Edit Barang Konsinyasi';
                document.getElementById('item-action').value = 'save_item';
                document.getElementById('item-id').value = item.id;
                document.getElementById('supplier_id').value = item.supplier_id;
                document.getElementById('nama_barang').value = item.nama_barang;
                document.getElementById('harga_jual').value = item.harga_jual;
                document.getElementById('harga_beli').value = item.harga_beli;
                document.getElementById('stok_awal').value = item.stok_awal;
                document.getElementById('tanggal_terima').value = item.tanggal_terima;
                itemModal.show();
            } catch (error) { showToast(`Gagal memuat data barang: ${error.message}`, 'error'); }
        }
    });

    // --- Initial Load ---
    loadSuppliers();
    loadItems();
    loadItemsForSale();
    document.getElementById('cs-tanggal').valueAsDate = new Date();
}