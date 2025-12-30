function initBukuBesarPage() {
    const akunFilter = document.getElementById('bb-akun-filter');
    const tglMulai = document.getElementById('bb-tanggal-mulai');
    const tglAkhir = document.getElementById('bb-tanggal-akhir');
    const tampilkanBtn = document.getElementById('bb-tampilkan-btn');
    const reportContent = document.getElementById('bb-report-content');
    const reportHeader = document.getElementById('bb-report-header');
    const exportPdfBtn = document.getElementById('export-bb-pdf');
    const exportCsvBtn = document.getElementById('export-bb-csv');

    if (!akunFilter) return;

    const commonOptions = { dateFormat: "d-m-Y", allowInput: true };
    const mulaiPicker = flatpickr(tglMulai, commonOptions);
    const akhirPicker = flatpickr(tglAkhir, commonOptions);

    // Set default dates to current month
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    mulaiPicker.setDate(firstDay, true);
    akhirPicker.setDate(lastDay, true);

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2 });

    async function loadAccounts() {
        try {
            const response = await fetch(`${basePath}/api/coa`); // Use the existing coa handler
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            akunFilter.innerHTML = '<option value="">-- Pilih Akun --</option>';
            result.data.forEach(acc => {
                akunFilter.add(new Option(`${acc.kode_akun} - ${acc.nama_akun}`, acc.id));
            });
        } catch (error) {
            akunFilter.innerHTML = `<option value="">Gagal memuat akun</option>`;
            showToast(error.message, 'error');
        }
    }

    async function loadReport() {
        const accountId = akunFilter.value;
        const startDate = tglMulai.value.split('-').reverse().join('-');
        const endDate = tglAkhir.value.split('-').reverse().join('-');

        if (!accountId || !startDate || !endDate) {
            showToast('Harap pilih akun dan rentang tanggal.', 'error');
            return;
        }

        const originalBtnHtml = tampilkanBtn.innerHTML;
        tampilkanBtn.disabled = true;
        tampilkanBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memuat...`;
        reportContent.innerHTML = `<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>`;

        try {
            const params = new URLSearchParams({ account_id: accountId, start_date: startDate, end_date: endDate });
            const response = await fetch(`${basePath}/api/buku-besar-data?${params.toString()}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const { account_info, saldo_awal, transactions } = result.data;
            reportHeader.textContent = `Buku Besar: ${account_info.nama_akun}`;

            let tableHtml = `
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Debit</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kredit</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Saldo</th></tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-sm font-bold text-gray-900 dark:text-white">Saldo Awal</td>
                            <td class="px-4 py-2 text-sm font-bold text-right text-gray-900 dark:text-white">${currencyFormatter.format(saldo_awal)}</td>
                        </tr>
            `;

            let saldoBerjalan = parseFloat(saldo_awal);
            const saldoNormal = account_info.saldo_normal;

            transactions.forEach(tx => {
                const debit = parseFloat(tx.debit);
                const kredit = parseFloat(tx.kredit);
                if (saldoNormal === 'Debit') {
                    saldoBerjalan += debit - kredit;
                } else { // Kredit
                    saldoBerjalan += kredit - debit;
                }
                tableHtml += `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">${formatDate(tx.tanggal)}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">${tx.keterangan}</td>
                        <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">${debit > 0 ? currencyFormatter.format(debit) : '-'}</td>
                        <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">${kredit > 0 ? currencyFormatter.format(kredit) : '-'}</td>
                        <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">${currencyFormatter.format(saldoBerjalan)}</td>
                    </tr>
                `;
            });

            tableHtml += `</tbody><tfoot class="bg-gray-50 dark:bg-gray-700"><tr><td colspan="4" class="px-4 py-2 text-sm font-bold text-right text-gray-900 dark:text-white">Saldo Akhir</td><td class="px-4 py-2 text-sm font-bold text-right text-gray-900 dark:text-white">${currencyFormatter.format(saldoBerjalan)}</td></tr></tfoot></table>`;
            reportContent.innerHTML = tableHtml;

        } catch (error) {
            reportContent.innerHTML = `<div class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-200 p-4 rounded-md text-center">${error.message}</div>`;
        } finally {
            tampilkanBtn.disabled = false;
            tampilkanBtn.innerHTML = originalBtnHtml;
        }
    }

    tampilkanBtn.addEventListener('click', loadReport);

    exportPdfBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        if (!akunFilter.value) { showToast('Pilih akun terlebih dahulu.', 'error'); return; }
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';
        const params = { 
            report: 'buku-besar', 
            account_id: akunFilter.value, 
            start_date: tglMulai.value.split('-').reverse().join('-'), 
            end_date: tglAkhir.value.split('-').reverse().join('-') 
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
        if (!akunFilter.value) { showToast('Pilih akun terlebih dahulu.', 'error'); return; }
        const url = `${basePath}/api/csv?report=buku-besar&account_id=${akunFilter.value}&start_date=${tglMulai.value.split('-').reverse().join('-')}&end_date=${tglAkhir.value.split('-').reverse().join('-')}`;
        window.open(url, '_blank');
    });

    loadAccounts().then(() => {
        // Check for URL parameters after accounts are loaded, e.g., from a drill-down link.
        const urlParams = new URLSearchParams(window.location.search);
        const accountIdFromUrl = urlParams.get('account_id');
        const startDateFromUrl = urlParams.get('start_date');
        const endDateFromUrl = urlParams.get('end_date');

        let shouldLoadReport = false;

        if (accountIdFromUrl) {
            akunFilter.value = accountIdFromUrl;
            shouldLoadReport = true;
        }
        if (startDateFromUrl) {
            mulaiPicker.setDate(startDateFromUrl, true, "Y-m-d");
        }
        if (endDateFromUrl) {
            akhirPicker.setDate(endDateFromUrl, true, "Y-m-d");
        }

        if (shouldLoadReport) loadReport();
    });
}