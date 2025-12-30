function initAsetTetapPage() {
    const tableBody = document.getElementById('assets-table-body');
    const form = document.getElementById('asset-form');
    const saveBtn = document.getElementById('save-asset-btn');
    const postDepreciationBtn = document.getElementById('post-depreciation-btn');
    const printReportBtn = document.getElementById('print-asset-report-btn');

    if (!tableBody) return;

    const akuisisiPicker = flatpickr("#tanggal_akuisisi", { dateFormat: "d-m-Y", allowInput: true });
    const pelepasanPicker = flatpickr("#tanggal_pelepasan", { dateFormat: "d-m-Y", allowInput: true });

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
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>`;
        try {
            const response = await fetch(`${basePath}/api/aset_tetap?action=list`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            tableBody.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach(asset => {
                    const isDisposed = asset.status === 'Dilepas';
                    const statusBadge = isDisposed ? `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Dilepas</span>` : `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Aktif</span>`;
                    const actionButtons = isDisposed ? `<button class="text-gray-400 cursor-not-allowed" disabled title="Aset sudah dilepas"><i class="bi bi-check-circle-fill"></i></button>` : `
                        <button class="text-blue-600 hover:text-blue-900 edit-asset-btn mr-2" data-id="${asset.id}"><i class="bi bi-pencil-fill"></i></button>
                        <button class="text-yellow-600 hover:text-yellow-900 dispose-asset-btn mr-2" data-id="${asset.id}" data-nama="${asset.nama_aset}"><i class="bi bi-box-arrow-right"></i></button>
                        <button class="text-red-600 hover:text-red-900 delete-asset-btn" data-id="${asset.id}"><i class="bi bi-trash-fill"></i></button>`;

                    const row = `
                        <tr class="${isDisposed ? 'bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${asset.nama_aset} ${statusBadge}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${new Date(asset.tanggal_akuisisi).toLocaleDateString('id-ID')}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">${currencyFormatter.format(asset.harga_perolehan)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">${currencyFormatter.format(asset.akumulasi_penyusutan)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white text-right">${currencyFormatter.format(asset.nilai_buku)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                ${actionButtons}
                            </td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada aset tetap yang dicatat.</td></tr>';
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-red-500 text-sm">Gagal memuat data: ${error.message}</td></tr>`;
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
        const tglAkuisisi = akuisisiPicker.selectedDates[0];
        if (tglAkuisisi) {
            formData.set('tanggal_akuisisi', tglAkuisisi.toISOString().split('T')[0]);
        }

        const response = await fetch(`${basePath}/api/aset_tetap`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status);
        if (result.status === 'success') {
            closeModal('assetModal');
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
            const tglPelepasan = pelepasanPicker.selectedDates[0];
            if (tglPelepasan) {
                formData.set('tanggal_pelepasan', tglPelepasan.toISOString().split('T')[0]);
            }

            const response = await fetch(`${basePath}/api/aset_tetap`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status);
            if (result.status === 'success') {
                closeModal('disposalModal');
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

    // Ganti event listener Bootstrap modal dengan pemanggilan langsung saat tombol diklik atau saat modal dibuka
    // Karena kita menggunakan openModal, kita bisa memuat data saat tombol dispose diklik
    async function loadCashAccounts() {
        const kasSelect = document.getElementById('kas_account_id');
        const response = await fetch(`${basePath}/api/settings?action=get_cash_accounts`);
        const result = await response.json();
        kasSelect.innerHTML = result.data.map(acc => `<option value="${acc.id}">${acc.nama_akun}</option>`).join('');
    }

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
                    if (key === 'tanggal_akuisisi') {
                        akuisisiPicker.setDate(asset[key], true, "Y-m-d");
                    } else {
                        const el = document.getElementById(key);
                        if (el) el.value = asset[key];
                    }
                });
                document.getElementById('asset-id').value = asset.id;
                openModal('assetModal');
            }
        }

        const disposeBtn = e.target.closest('.dispose-asset-btn');
        if (disposeBtn) {
            const form = document.getElementById('disposal-form');
            form.reset();
            document.getElementById('disposal-asset-id').value = disposeBtn.dataset.id;
            document.getElementById('disposal-asset-name').textContent = disposeBtn.dataset.nama;
            pelepasanPicker.setDate(new Date(), true);
            // Sembunyikan field kas/bank secara default
            document.getElementById('disposal-kas-account-container').style.display = 'none';
            document.getElementById('kas_account_id').required = false;
            await loadCashAccounts(); // Muat akun kas sebelum membuka modal
            openModal('disposalModal');
        }
    });

    setupDepreciationFilters();
    loadAssets();
    loadAccountsForModal();
}
