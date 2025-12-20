function initLaporanHarianPage() {
    const tanggalInput = document.getElementById('lh-tanggal');
    const tampilkanBtn = document.getElementById('lh-tampilkan-btn');
    const reportContent = document.getElementById('lh-report-content');
    const reportHeader = document.getElementById('lh-report-header');
    const exportPdfBtn = document.getElementById('export-lh-pdf');
    const exportCsvBtn = document.getElementById('export-lh-csv');
    const summaryContent = document.getElementById('lh-summary-content');
    const chartCanvas = document.getElementById('lh-chart');

    if (!tanggalInput) return;
    tanggalInput.valueAsDate = new Date(); // Set default to today

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });
    async function loadReport() {
        const tanggal = tanggalInput.value;
        if (!tanggal) {
            showToast('Harap pilih tanggal terlebih dahulu.', 'error');
            return;
        }

        const originalBtnHtml = tampilkanBtn.innerHTML;
        tampilkanBtn.disabled = true;
        tampilkanBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Memuat...`;
        reportContent.innerHTML = `<div class="text-center p-5"><div class="spinner-border"></div></div>`;
        summaryContent.innerHTML = `<div class="text-center p-5"><div class="spinner-border"></div></div>`;
        reportHeader.textContent = `Detail Transaksi Harian untuk ${new Date(tanggal).toLocaleDateString('id-ID', { dateStyle: 'full' })}`;

        try {
            const response = await fetch(`${basePath}/api/laporan-harian?tanggal=${tanggal}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const { saldo_awal, transaksi, total_pemasukan, total_pengeluaran, saldo_akhir } = result.data;

            // Render Summary Card
            summaryContent.innerHTML = `
                <dl class="row">
                    <dt class="col-sm-5">Saldo Awal Hari</dt>
                    <dd class="col-sm-7 text-end">${currencyFormatter.format(saldo_awal)}</dd>

                    <dt class="col-sm-5 text-success">Total Pemasukan</dt>
                    <dd class="col-sm-7 text-end text-success">${currencyFormatter.format(total_pemasukan)}</dd>

                    <dt class="col-sm-5 text-danger">Total Pengeluaran</dt>
                    <dd class="col-sm-7 text-end text-danger">${currencyFormatter.format(total_pengeluaran)}</dd>

                    <hr class="my-2">

                    <dt class="col-sm-5 fw-bold">Saldo Akhir Hari</dt>
                    <dd class="col-sm-7 text-end fw-bold">${currencyFormatter.format(saldo_akhir)}</dd>
                </dl>
            `;

            // Render Chart
            if (window.dailyChart) {
                window.dailyChart.destroy();
            }
            window.dailyChart = new Chart(chartCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Pemasukan', 'Pengeluaran'],
                    datasets: [{
                        label: 'Jumlah',
                        data: [total_pemasukan, total_pengeluaran],
                        backgroundColor: ['rgba(25, 135, 84, 0.7)', 'rgba(220, 53, 69, 0.7)'],
                        borderColor: ['rgba(25, 135, 84, 1)', 'rgba(220, 53, 69, 1)'],
                        borderWidth: 1
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });

            let tableHtml = `
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Keterangan</th>
                            <th>Akun Terkait</th>
                            <th class="text-end">Pemasukan</th>
                            <th class="text-end">Pengeluaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="3" class="fw-bold">Saldo Awal</td>
                            <td class="text-end fw-bold" colspan="2">${currencyFormatter.format(saldo_awal)}</td>
                        </tr>
            `;

            if (transaksi.length > 0) {
                transaksi.forEach(tx => {
                    const idDisplay = tx.ref || `${tx.source.toUpperCase()}-${tx.id}`; // Gunakan ref jika ada
                    const idHtml = `<a href="#" class="view-detail-btn" data-type="${tx.source}" data-id="${tx.id}">${idDisplay}</a>`;

                    tableHtml += `
                        <tr>
                            <td><small>${idHtml}</small></td>
                            <td>${tx.keterangan}</td>
                            <td><small>${tx.akun_terkait || '<i>N/A</i>'}</small></td>
                            <td class="text-end text-success">${tx.pemasukan > 0 ? currencyFormatter.format(tx.pemasukan) : '-'}</td>
                            <td class="text-end text-danger">${tx.pengeluaran > 0 ? currencyFormatter.format(tx.pengeluaran) : '-'}</td>
                        </tr>
                    `;
                });
            } else {
                tableHtml += `<tr><td colspan="5" class="text-center text-muted">Tidak ada transaksi pada tanggal ini.</td></tr>`;
            }

            tableHtml += `
                    </tbody>
                    <tfoot class="table-group-divider">
                        <tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end text-success">${currencyFormatter.format(total_pemasukan)}</td><td class="text-end text-danger">${currencyFormatter.format(total_pengeluaran)}</td></tr>
                        <tr class="fw-bold table-primary"><td colspan="3" class="text-end">Saldo Akhir</td><td class="text-end" colspan="2">${currencyFormatter.format(saldo_akhir)}</td></tr>
                    </tfoot>
                </table>`;
            reportContent.innerHTML = tableHtml;

        } catch (error) {
            reportContent.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
            summaryContent.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
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
        const params = { report: 'laporan-harian', tanggal: tanggalInput.value };
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
        window.open(`${basePath}/api/csv?report=laporan-harian&format=csv&tanggal=${tanggalInput.value}`, '_blank');
    });

    reportContent.addEventListener('click', async (e) => {
        const viewBtn = e.target.closest('.view-detail-btn');
        if (!viewBtn) return;

        e.preventDefault();
        const { type, id } = viewBtn.dataset;

        const detailModalEl = document.getElementById('detailModal');
        const detailModal = bootstrap.Modal.getInstance(detailModalEl) || new bootstrap.Modal(detailModalEl);
        const modalBody = document.getElementById('detailModalBody');
        const modalLabel = document.getElementById('detailModalLabel');

        modalLabel.textContent = `Detail ${type === 'transaksi' ? 'Transaksi' : 'Jurnal'}`;
        modalBody.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';
        detailModal.show();

        try {
            const endpoint = type === 'transaksi' 
                ? `${basePath}/api/transaksi?action=get_journal_entry&id=${id}`
                : `${basePath}/api/entri-jurnal?action=get_single&id=${id}`;

            const response = await fetch(endpoint);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const header = type === 'transaksi' ? result.data.transaksi : result.data.header;
            const details = type === 'transaksi' ? result.data.jurnal : result.data.details;

            let tableHtml = `
                <p><strong>Tanggal:</strong> ${new Date(header.tanggal).toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'})}</p>
                ${header.nomor_referensi ? `<p><strong>No. Referensi:</strong> ${header.nomor_referensi}</p>` : ''}
                <p><strong>Keterangan:</strong> ${header.keterangan}</p>
                <table class="table table-sm table-bordered">
                    <thead class="table-light"><tr><th>Akun</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr></thead>
                    <tbody>
            `;

            details.forEach(line => {
                const akunText = line.kode_akun ? `${line.kode_akun} - ${line.nama_akun}` : line.akun;
                tableHtml += `
                    <tr>
                        <td>${akunText}</td>
                        <td class="text-end">${line.debit > 0 ? currencyFormatter.format(line.debit) : '-'}</td>
                        <td class="text-end">${line.kredit > 0 ? currencyFormatter.format(line.kredit) : '-'}</td>
                    </tr>
                `;
            });
            tableHtml += `</tbody></table>`;
            modalBody.innerHTML = tableHtml;

        } catch (error) {
            modalBody.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    });

    // Export buttons can be implemented similarly to other reports, pointing to a new handler or an updated one.

    // Initial load for today's report
    loadReport();
}