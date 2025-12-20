function initAsetTetapPage() {
    const tableBody = document.getElementById('assets-table-body');
    const modalEl = document.getElementById('assetModal');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('asset-form');
    const saveBtn = document.getElementById('save-asset-btn');
    const disposalModalEl = document.getElementById('disposalModal');
    const disposalModal = new bootstrap.Modal(disposalModalEl);
    const postDepreciationBtn = document.getElementById('post-depreciation-btn');
    const printReportBtn = document.getElementById('print-asset-report-btn');

    if (!tableBody) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    function setupDepreciationFilters() {
        const monthSelect = document.getElementById('depreciation-month');
        const yearSelect = document.getElementById('depreciation-year');
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth(); // 0-11

        // Populate years
        for (let i = 0; i < 5; i++) {
            yearSelect.add(new Option(currentYear - i, currentYear - i));
        }

        // Populate months
        const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
        months.forEach((month, index) => {
            monthSelect.add(new Option(month, index + 1));
        });

        // Set default to current month and year
        monthSelect.value = currentMonth + 1;
        yearSelect.value = currentYear;
    }

    async function loadAssets() {
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center p-5"><div class="spinner-border"></div></td></tr>`;
        try {
            const response = await fetch(`${basePath}/api/aset_tetap?action=list`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            tableBody.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach(asset => {
                    const isDisposed = asset.status === 'Dilepas';
                    const statusBadge = isDisposed ? `<span class="badge bg-secondary">Dilepas</span>` : `<span class="badge bg-success">Aktif</span>`;
                    const actionButtons = isDisposed ? `<button class="btn btn-sm btn-secondary" disabled title="Aset sudah dilepas"><i class="bi bi-check-circle-fill"></i></button>` : `
                        <button class="btn btn-sm btn-info edit-asset-btn" data-id="${asset.id}"><i class="bi bi-pencil-fill"></i></button>
                        <button class="btn btn-sm btn-warning dispose-asset-btn" data-id="${asset.id}" data-nama="${asset.nama_aset}"><i class="bi bi-box-arrow-right"></i></button>
                        <button class="btn btn-sm btn-danger delete-asset-btn" data-id="${asset.id}"><i class="bi bi-trash-fill"></i></button>`;

                    const row = `
                        <tr class="${isDisposed ? 'table-light text-muted' : ''}">
                            <td>${asset.nama_aset} ${statusBadge}</td>
                            <td>${new Date(asset.tanggal_akuisisi).toLocaleDateString('id-ID')}</td>
                            <td class="text-end">${currencyFormatter.format(asset.harga_perolehan)}</td>
                            <td class="text-end">${currencyFormatter.format(asset.akumulasi_penyusutan)}</td>
                            <td class="text-end fw-bold">${currencyFormatter.format(asset.nilai_buku)}</td>
                            <td class="text-end">
                                ${actionButtons}
                            </td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Belum ada aset tetap yang dicatat.</td></tr>';
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    async function loadAccountsForModal() {
        try {
            const response = await fetch(`${basePath}/api/aset_tetap?action=get_accounts`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const { aset, beban, pendapatan, kas } = result.data;
            const createOptions = (accounts) => accounts.map(acc => `<option value="${acc.id}">${acc.kode_akun} - ${acc.nama_akun}</option>`).join('');

            document.getElementById('akun_aset_id').innerHTML = createOptions(aset);
            document.getElementById('akun_akumulasi_penyusutan_id').innerHTML = createOptions(aset);
            document.getElementById('akun_beban_penyusutan_id').innerHTML = createOptions(beban);
        } catch (error) {
            showToast(`Gagal memuat daftar akun: ${error.message}`, 'error');
        }
    }

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const response = await fetch(`${basePath}/api/aset_tetap`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status);
        if (result.status === 'success') {
            modal.hide();
            loadAssets();
        }
    });

    postDepreciationBtn.addEventListener('click', async () => {
        const month = document.getElementById('depreciation-month').value;
        const year = document.getElementById('depreciation-year').value;
        if (confirm(`Anda yakin ingin memposting jurnal penyusutan untuk periode ${month}/${year}?`)) {
            const formData = new FormData();
            formData.append('action', 'post_depreciation');
            formData.append('month', month);
            formData.append('year', year);
            const response = await fetch(`${basePath}/api/aset_tetap`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status);
            if (result.status === 'success') {
                loadAssets();
            }
        }
    });

    printReportBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';
        const params = { report: 'aset-tetap', per_tanggal: new Date().toISOString().split('T')[0] };
        for (const key in params) {
            const hiddenField = document.createElement('input'); hiddenField.type = 'hidden'; hiddenField.name = key; hiddenField.value = params[key]; form.appendChild(hiddenField);
        }
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    document.getElementById('save-disposal-btn').addEventListener('click', async () => {
        const form = document.getElementById('disposal-form');
        if (!form.checkValidity()) {
            showToast('Harap isi semua field yang wajib.', 'error');
            return;
        }
        if (confirm('Anda yakin ingin memproses pelepasan aset ini? Aksi ini akan membuat jurnal permanen dan tidak dapat dibatalkan.')) {
            const formData = new FormData(form);
            const response = await fetch(`${basePath}/api/aset_tetap`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status);
            if (result.status === 'success') {
                disposalModal.hide();
                loadAssets();
            }
        }
    });

    document.getElementById('harga_jual').addEventListener('input', (e) => {
        const kasContainer = document.getElementById('disposal-kas-account-container');
        if (parseFloat(e.target.value) > 0) {
            kasContainer.style.display = 'block';
            document.getElementById('kas_account_id').required = true;
        } else {
            kasContainer.style.display = 'none';
            document.getElementById('kas_account_id').required = false;
        }
    });

    disposalModalEl.addEventListener('show.bs.modal', async (e) => {
        const kasSelect = document.getElementById('kas_account_id');
        const response = await fetch(`${basePath}/api/settings?action=get_cash_accounts`);
        const result = await response.json();
        kasSelect.innerHTML = result.data.map(acc => `<option value="${acc.id}">${acc.nama_akun}</option>`).join('');
    });

    tableBody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-asset-btn');
        if (editBtn) {
            const response = await fetch(`${basePath}/api/aset_tetap?action=get_single&id=${editBtn.dataset.id}`);
            const result = await response.json();
            if (result.status === 'success') {
                const asset = result.data;
                form.reset();
                document.getElementById('assetModalLabel').textContent = 'Edit Aset Tetap';
                Object.keys(asset).forEach(key => {
                    const el = document.getElementById(key);
                    if (el) el.value = asset[key];
                });
                document.getElementById('asset-id').value = asset.id;
                modal.show();
            }
        }

        const disposeBtn = e.target.closest('.dispose-asset-btn');
        if (disposeBtn) {
            const form = document.getElementById('disposal-form');
            form.reset();
            document.getElementById('disposal-asset-id').value = disposeBtn.dataset.id;
            document.getElementById('disposal-asset-name').textContent = disposeBtn.dataset.nama;
            document.getElementById('tanggal_pelepasan').valueAsDate = new Date();
            // Sembunyikan field kas/bank secara default
            document.getElementById('disposal-kas-account-container').style.display = 'none';
            document.getElementById('kas_account_id').required = false;
            disposalModal.show();
        }
    });

    setupDepreciationFilters();
    loadAssets();
    loadAccountsForModal();
}
