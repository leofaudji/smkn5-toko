function initLaporanLabaDitahanPage() {
    const tglMulai = document.getElementById('re-tanggal-mulai');
    const tglAkhir = document.getElementById('re-tanggal-akhir');
    const tampilkanBtn = document.getElementById('re-tampilkan-btn');
    const reportContent = document.getElementById('re-report-content');
    const reportHeader = document.getElementById('re-report-header');
    const exportPdfBtn = document.getElementById('export-re-pdf');
    const exportCsvBtn = document.getElementById('export-re-csv');

    if (!tampilkanBtn) return;

    const commonOptions = { dateFormat: "d-m-Y", allowInput: true };
    const mulaiPicker = flatpickr(tglMulai, commonOptions);
    const akhirPicker = flatpickr(tglAkhir, commonOptions);

    // Set default dates to current year
    const now = new Date();
    mulaiPicker.setDate(new Date(now.getFullYear(), 0, 1), true);
    akhirPicker.setDate(new Date(now.getFullYear(), 11, 31), true);

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2 });

    async function loadReport() {
        const startDate = tglMulai.value.split('-').reverse().join('-');
        const endDate = tglAkhir.value.split('-').reverse().join('-');

        if (!startDate || !endDate) {
            showToast('Harap pilih rentang tanggal.', 'error');
            return;
        }

        const originalBtnHtml = tampilkanBtn.innerHTML;
        tampilkanBtn.disabled = true;
        tampilkanBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memuat...`;
        reportContent.innerHTML = `<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>`;

        try {
            const params = new URLSearchParams({ start_date: startDate, end_date: endDate });
            const response = await fetch(`${basePath}/api/laporan-laba-ditahan?${params.toString()}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const { account_info, saldo_awal, transactions } = result.data;
            reportHeader.textContent = `Laporan Perubahan Laba Ditahan: ${account_info.nama_akun}`;

            let tableHtml = `
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Debit</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kredit</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Saldo</th></tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">Saldo Awal per ${new Date(startDate).toLocaleDateString('id-ID')}</td>
                            <td class="px-6 py-4 text-sm font-bold text-right text-gray-900 dark:text-white">${currencyFormatter.format(saldo_awal)}</td>
                        </tr>
            `;

            let saldoBerjalan = parseFloat(saldo_awal);
            transactions.forEach(tx => {
                const debit = parseFloat(tx.debit);
                const kredit = parseFloat(tx.kredit);
                saldoBerjalan += kredit - debit; // Saldo normal Ekuitas adalah Kredit
                
                tableHtml += `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${new Date(tx.tanggal).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'})}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">${tx.keterangan}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">${debit > 0 ? currencyFormatter.format(debit) : '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">${kredit > 0 ? currencyFormatter.format(kredit) : '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">${currencyFormatter.format(saldoBerjalan)}</td>
                    </tr>
                `;
            });

            tableHtml += `</tbody><tfoot class="bg-gray-50 dark:bg-gray-700"><tr><td colspan="4" class="px-6 py-4 text-sm font-bold text-right text-gray-900 dark:text-white">Saldo Akhir per ${new Date(endDate).toLocaleDateString('id-ID')}</td><td class="px-6 py-4 text-sm font-bold text-right text-gray-900 dark:text-white">${currencyFormatter.format(saldoBerjalan)}</td></tr></tfoot></table>`;
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
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';
        const params = { 
            report: 'laporan-laba-ditahan', 
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
        const url = `${basePath}/api/csv?report=laporan-laba-ditahan&format=csv&start_date=${tglMulai.value.split('-').reverse().join('-')}&end_date=${tglAkhir.value.split('-').reverse().join('-')}`;
        window.open(url, '_blank');
    });

    loadReport(); // Initial load
}
