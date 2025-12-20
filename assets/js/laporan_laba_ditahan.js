function initLaporanLabaDitahanPage() {
    const tglMulai = document.getElementById('re-tanggal-mulai');
    const tglAkhir = document.getElementById('re-tanggal-akhir');
    const tampilkanBtn = document.getElementById('re-tampilkan-btn');
    const reportContent = document.getElementById('re-report-content');
    const reportHeader = document.getElementById('re-report-header');
    const exportPdfBtn = document.getElementById('export-re-pdf');
    const exportCsvBtn = document.getElementById('export-re-csv');

    if (!tampilkanBtn) return;

    // Set default dates to current year
    const now = new Date();
    tglMulai.value = new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0];
    tglAkhir.value = new Date(now.getFullYear(), 11, 31).toISOString().split('T')[0];

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2 });

    async function loadReport() {
        const startDate = tglMulai.value;
        const endDate = tglAkhir.value;

        if (!startDate || !endDate) {
            showToast('Harap pilih rentang tanggal.', 'error');
            return;
        }

        const originalBtnHtml = tampilkanBtn.innerHTML;
        tampilkanBtn.disabled = true;
        tampilkanBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Memuat...`;
        reportContent.innerHTML = `<div class="text-center p-5"><div class="spinner-border"></div></div>`;

        try {
            const params = new URLSearchParams({ start_date: startDate, end_date: endDate });
            const response = await fetch(`${basePath}/api/laporan-laba-ditahan?${params.toString()}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const { account_info, saldo_awal, transactions } = result.data;
            reportHeader.textContent = `Laporan Perubahan Laba Ditahan: ${account_info.nama_akun}`;

            let tableHtml = `
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Tanggal</th><th>Keterangan</th><th class="text-end">Debit</th><th class="text-end">Kredit</th><th class="text-end">Saldo</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4"><strong>Saldo Awal per ${new Date(startDate).toLocaleDateString('id-ID')}</strong></td>
                            <td class="text-end"><strong>${currencyFormatter.format(saldo_awal)}</strong></td>
                        </tr>
            `;

            let saldoBerjalan = parseFloat(saldo_awal);
            transactions.forEach(tx => {
                const debit = parseFloat(tx.debit);
                const kredit = parseFloat(tx.kredit);
                saldoBerjalan += kredit - debit; // Saldo normal Ekuitas adalah Kredit
                
                tableHtml += `
                    <tr>
                        <td>${new Date(tx.tanggal).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'})}</td>
                        <td>${tx.keterangan}</td>
                        <td class="text-end">${debit > 0 ? currencyFormatter.format(debit) : '-'}</td>
                        <td class="text-end">${kredit > 0 ? currencyFormatter.format(kredit) : '-'}</td>
                        <td class="text-end">${currencyFormatter.format(saldoBerjalan)}</td>
                    </tr>
                `;
            });

            tableHtml += `</tbody><tfoot><tr class="table-light"><td colspan="4" class="text-end fw-bold">Saldo Akhir per ${new Date(endDate).toLocaleDateString('id-ID')}</td><td class="text-end fw-bold">${currencyFormatter.format(saldoBerjalan)}</td></tr></tfoot></table>`;
            reportContent.innerHTML = tableHtml;

        } catch (error) {
            reportContent.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
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
        const params = { report: 'laporan-laba-ditahan', start_date: tglMulai.value, end_date: tglAkhir.value };
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
        const url = `${basePath}/api/csv?report=laporan-laba-ditahan&format=csv&start_date=${tglMulai.value}&end_date=${tglAkhir.value}`;
        window.open(url, '_blank');
    });

    loadReport(); // Initial load
}
