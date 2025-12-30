function initRekonsiliasiBankPage() {
    const akunFilter = document.getElementById('recon-akun-filter');
    const tanggalAkhirInput = document.getElementById('recon-tanggal-akhir');
    const saldoRekeningInput = document.getElementById('recon-saldo-rekening');
    const tampilkanBtn = document.getElementById('recon-tampilkan-btn');
    const reconciliationContent = document.getElementById('reconciliation-content');
    const appTransactionsBody = document.getElementById('app-transactions-body');
    const checkAllApp = document.getElementById('check-all-app');
    const saveBtn = document.getElementById('save-reconciliation-btn');

    if (!akunFilter) return;

    const tanggalAkhirPicker = flatpickr(tanggalAkhirInput, { dateFormat: "d-m-Y", allowInput: true });

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2 });
    let saldoBukuAwal = 0;

    async function loadCashAccounts() {
        try {
            const response = await fetch(`${basePath}/api/rekonsiliasi-bank?action=get_cash_accounts`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);
            akunFilter.innerHTML = '<option value="">-- Pilih Akun --</option>';
            result.data.forEach(acc => akunFilter.add(new Option(acc.nama_akun, acc.id)));
        } catch (error) {
            showToast(`Gagal memuat akun kas: ${error.message}`, 'error');
        }
    }

    function updateSummary() {
        const saldoBank = parseFloat(saldoRekeningInput.value) || 0;
        let clearedDebit = 0;
        let clearedKredit = 0;
        let unclearedDebit = 0;
        let unclearedKredit = 0;

        appTransactionsBody.querySelectorAll('tr').forEach(row => {
            const debit = parseFloat(row.dataset.debit) || 0;
            const kredit = parseFloat(row.dataset.kredit) || 0;
            const checkbox = row.querySelector('.recon-check');

            if (checkbox && checkbox.checked) {
                clearedDebit += debit;
                clearedKredit += kredit;
            } else {
                unclearedDebit += debit;
                unclearedKredit += kredit;
            }
        });

        const saldoBukuAkhir = saldoBukuAwal + (clearedDebit - clearedKredit) + (unclearedDebit - unclearedKredit);
        const saldoBukuDisesuaikan = saldoBukuAwal + (clearedDebit - clearedKredit);
        const selisih = saldoBukuDisesuaikan - saldoBank;

        document.getElementById('summary-saldo-buku').textContent = currencyFormatter.format(saldoBukuAkhir);
        document.getElementById('summary-saldo-bank').textContent = currencyFormatter.format(saldoBank);
        document.getElementById('summary-cleared').textContent = currencyFormatter.format(clearedDebit - clearedKredit);
        document.getElementById('summary-selisih').textContent = currencyFormatter.format(selisih);

        // Aktifkan tombol simpan jika selisihnya nol (atau sangat kecil)
        saveBtn.disabled = Math.abs(selisih) > 0.01;
    }

    async function startReconciliation() {
        const accountId = akunFilter.value;
        const endDate = tanggalAkhirInput.value.split('-').reverse().join('-');

        if (!accountId || !endDate || !saldoRekeningInput.value) {
            showToast('Harap pilih akun, tanggal akhir, dan isi saldo rekening koran.', 'error');
            return;
        }

        const originalBtnHtml = tampilkanBtn.innerHTML;
        tampilkanBtn.disabled = true;
        tampilkanBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memuat...`;

        reconciliationContent.classList.remove('hidden');
        appTransactionsBody.innerHTML = `<tr><td colspan="5" class="text-center py-10"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>`;

        try {
            const response = await fetch(`${basePath}/api/rekonsiliasi-bank?action=get_transactions&account_id=${accountId}&end_date=${endDate}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            saldoBukuAwal = result.saldo_buku_awal;
            appTransactionsBody.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach(tx => {
                    const row = `
                        <tr data-debit="${tx.debit}" data-kredit="${tx.kredit}" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4"><input class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-offset-0 focus:ring-primary focus:ring-opacity-50 recon-check" type="checkbox" value="${tx.id}"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${new Date(tx.tanggal).toLocaleDateString('id-ID')}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">${tx.keterangan}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 dark:text-green-400">${tx.debit > 0 ? currencyFormatter.format(tx.debit) : '-'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 dark:text-red-400">${tx.kredit > 0 ? currencyFormatter.format(tx.kredit) : '-'}</td>
                        </tr>
                    `;
                    appTransactionsBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                appTransactionsBody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-gray-500">Tidak ada transaksi untuk direkonsiliasi.</td></tr>`;
            }
            updateSummary();

        } catch (error) {
            showToast(`Gagal memuat transaksi: ${error.message}`, 'error');
            appTransactionsBody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-red-500">${error.message}</td></tr>`;
        } finally {
            tampilkanBtn.disabled = false;
            tampilkanBtn.innerHTML = `<i class="bi bi-search mr-2"></i> Mulai`;
        }
    }

    tampilkanBtn.addEventListener('click', startReconciliation);
    saldoRekeningInput.addEventListener('input', updateSummary);

    appTransactionsBody.addEventListener('change', e => {
        if (e.target.classList.contains('recon-check')) {
            updateSummary();
        }
    });

    checkAllApp.addEventListener('change', () => {
        appTransactionsBody.querySelectorAll('.recon-check').forEach(chk => {
            chk.checked = checkAllApp.checked;
        });
        updateSummary();
    });

    saveBtn.addEventListener('click', async () => {
        const clearedIds = Array.from(appTransactionsBody.querySelectorAll('.recon-check:checked')).map(chk => chk.value);
        if (clearedIds.length === 0) {
            showToast('Tidak ada transaksi yang dipilih untuk direkonsiliasi.', 'info');
            return;
        }

        if (!confirm(`Anda yakin ingin menyimpan rekonsiliasi untuk ${clearedIds.length} transaksi?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'save');
        formData.append('account_id', akunFilter.value);
        formData.append('reconciliation_date', tanggalAkhirInput.value.split('-').reverse().join('-'));
        clearedIds.forEach(id => formData.append('cleared_ids[]', id));

        const response = await fetch(`${basePath}/api/rekonsiliasi-bank`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status);
        if (result.status === 'success') {
            reconciliationContent.classList.add('hidden');
            appTransactionsBody.innerHTML = '';
        }
    });

    // Initial Load
    tanggalAkhirPicker.setDate(new Date(), true);
    loadCashAccounts();
}
