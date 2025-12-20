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

    // Set default dates to today
    const today = new Date().toISOString().split('T')[0];
    tglMulai.value = today;
    tglAkhir.value = today;

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
        const startDate = tglMulai.value;
        const endDate = tglAkhir.value;

        if (!accountId || !startDate || !endDate) {
            showToast('Harap pilih akun dan rentang tanggal.', 'error');
            return;
        }

        const originalBtnHtml = tampilkanBtn.innerHTML;
        tampilkanBtn.disabled = true;
        tampilkanBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Memuat...`;
        reportContent.innerHTML = `<div class="text-center p-5"><div class="spinner-border"></div></div>`;

        try {
            const params = new URLSearchParams({ account_id: accountId, start_date: startDate, end_date: endDate });
            const response = await fetch(`${basePath}/api/buku-besar-data?${params.toString()}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const { account_info, saldo_awal, transactions } = result.data;
            reportHeader.textContent = `Buku Besar: ${account_info.nama_akun}`;

            let tableHtml = `
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Tanggal</th><th>Keterangan</th><th class="text-end">Debit</th><th class="text-end">Kredit</th><th class="text-end">Saldo</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4"><strong>Saldo Awal</strong></td>
                            <td class="text-end"><strong>${currencyFormatter.format(saldo_awal)}</strong></td>
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
                    <tr>
                        <td>${new Date(tx.tanggal).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'})}</td>
                        <td>${tx.keterangan}</td>
                        <td class="text-end">${debit > 0 ? currencyFormatter.format(debit) : '-'}</td>
                        <td class="text-end">${kredit > 0 ? currencyFormatter.format(kredit) : '-'}</td>
                        <td class="text-end">${currencyFormatter.format(saldoBerjalan)}</td>
                    </tr>
                `;
            });

            tableHtml += `</tbody><tfoot><tr class="table-light"><td colspan="4" class="text-end fw-bold">Saldo Akhir</td><td class="text-end fw-bold">${currencyFormatter.format(saldoBerjalan)}</td></tr></tfoot></table>`;
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
        if (!akunFilter.value) { showToast('Pilih akun terlebih dahulu.', 'error'); return; }
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';
        const params = { report: 'buku-besar', account_id: akunFilter.value, start_date: tglMulai.value, end_date: tglAkhir.value };
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
        const url = `${basePath}/api/csv?report=buku-besar&account_id=${akunFilter.value}&start_date=${tglMulai.value}&end_date=${tglAkhir.value}`;
        window.open(url, '_blank');
    });

    loadAccounts();
}