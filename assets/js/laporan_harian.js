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

    const tanggalPicker = flatpickr(tanggalInput, { dateFormat: "d-m-Y", allowInput: true, defaultDate: "today" });

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });
    async function loadReport() {
        const tanggal = tanggalInput.value.split('-').reverse().join('-');
        if (!tanggal) {
            showToast('Harap pilih tanggal terlebih dahulu.', 'error');
            return;
        }

        const originalBtnHtml = tampilkanBtn.innerHTML;
        tampilkanBtn.disabled = true;
        tampilkanBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memuat...`;
        reportContent.innerHTML = `<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>`;
        summaryContent.innerHTML = `<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>`;
        reportHeader.textContent = `Detail Transaksi Harian untuk ${new Date(tanggal).toLocaleDateString('id-ID', { dateStyle: 'full' })}`;
        
        try {
            const response = await fetch(`${basePath}/api/laporan-harian?tanggal=${tanggal}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const { saldo_awal, transaksi, total_pemasukan, total_pengeluaran, saldo_akhir } = result.data;

            // Render Summary Card
            summaryContent.innerHTML = `
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="font-medium text-gray-500 dark:text-gray-400">Saldo Awal Hari</div>
                    <div class="text-right font-semibold text-gray-900 dark:text-white">${currencyFormatter.format(saldo_awal)}</div>

                    <div class="font-medium text-green-600 dark:text-green-400">Total Pemasukan</div>
                    <div class="text-right font-semibold text-green-600 dark:text-green-400">${currencyFormatter.format(total_pemasukan)}</div>

                    <div class="font-medium text-red-600 dark:text-red-400">Total Pengeluaran</div>
                    <div class="text-right font-semibold text-red-600 dark:text-red-400">${currencyFormatter.format(total_pengeluaran)}</div>

                    <div class="col-span-2 border-t border-gray-200 dark:border-gray-700 my-2"></div>

                    <div class="font-bold text-gray-900 dark:text-white">Saldo Akhir Hari</div>
                    <div class="text-right font-bold text-gray-900 dark:text-white">${currencyFormatter.format(saldo_akhir)}</div>
                </div>
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
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Akun Terkait</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pemasukan</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pengeluaran</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">Saldo Awal</td>
                            <td class="px-6 py-4 text-sm font-bold text-right text-gray-900 dark:text-white" colspan="2">${currencyFormatter.format(saldo_awal)}</td>
                        </tr>
            `;

            if (transaksi.length > 0) {
                transaksi.forEach(tx => {
                    const idDisplay = tx.ref || `${tx.source.toUpperCase()}-${tx.id}`;
                    const idHtml = `<a href="#" class="view-detail-btn text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" data-type="${tx.source}" data-id="${tx.id}">${idDisplay}</a>`;

                    tableHtml += `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${idHtml}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">${tx.keterangan}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">${tx.akun_terkait || '<i>N/A</i>'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 dark:text-green-400">${tx.pemasukan > 0 ? currencyFormatter.format(tx.pemasukan) : '-'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 dark:text-red-400">${tx.pengeluaran > 0 ? currencyFormatter.format(tx.pengeluaran) : '-'}</td>
                        </tr>
                    `;
                });
            } else {
                tableHtml += `<tr><td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada transaksi pada tanggal ini.</td></tr>`;
            }

            tableHtml += `
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-700">
                        <tr class="font-bold text-sm text-gray-900 dark:text-white">
                            <td colspan="3" class="px-6 py-3 text-right">Total</td>
                            <td class="px-6 py-3 text-right text-green-600 dark:text-green-400">${currencyFormatter.format(total_pemasukan)}</td>
                            <td class="px-6 py-3 text-right text-red-600 dark:text-red-400">${currencyFormatter.format(total_pengeluaran)}</td>
                        </tr>
                        <tr class="font-bold text-sm bg-blue-50 dark:bg-blue-900/20 text-gray-900 dark:text-white">
                            <td colspan="3" class="px-6 py-3 text-right">Saldo Akhir</td>
                            <td class="px-6 py-3 text-right" colspan="2">${currencyFormatter.format(saldo_akhir)}</td>
                        </tr>
                    </tfoot>
                </table>`;
            reportContent.innerHTML = tableHtml;

        } catch (error) {
            reportContent.innerHTML = `<div class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-200 p-4 rounded-md text-center">${error.message}</div>`;
            summaryContent.innerHTML = `<div class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-200 p-4 rounded-md text-center">${error.message}</div>`;
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
        const params = { report: 'laporan-harian', tanggal: tanggalInput.value.split('-').reverse().join('-') };
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
        window.open(`${basePath}/api/csv?report=laporan-harian&format=csv&tanggal=${tanggalInput.value.split('-').reverse().join('-')}`, '_blank');
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
        modalBody.innerHTML = '<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>';
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
                <div class="mb-4 text-sm text-gray-700 dark:text-gray-300">
                    <p><strong>Tanggal:</strong> ${new Date(header.tanggal).toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'})}</p>
                    ${header.nomor_referensi ? `<p><strong>No. Referensi:</strong> ${header.nomor_referensi}</p>` : ''}
                    <p><strong>Keterangan:</strong> ${header.keterangan}</p>
                </div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Akun</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Debit</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kredit</th></tr></thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            `;

            details.forEach(line => {
                const akunText = line.kode_akun ? `${line.kode_akun} - ${line.nama_akun}` : line.akun;
                tableHtml += `
                    <tr class="text-sm">
                        <td class="px-4 py-2 text-gray-900 dark:text-white">${akunText}</td>
                        <td class="px-4 py-2 text-right text-gray-900 dark:text-white">${line.debit > 0 ? currencyFormatter.format(line.debit) : '-'}</td>
                        <td class="px-4 py-2 text-right text-gray-900 dark:text-white">${line.kredit > 0 ? currencyFormatter.format(line.kredit) : '-'}</td>
                    </tr>
                `;
            });
            tableHtml += `</tbody></table>`;
            modalBody.innerHTML = tableHtml;

        } catch (error) {
            modalBody.innerHTML = `<div class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-200 p-4 rounded-md text-center">${error.message}</div>`;
        }
    });

    // Export buttons can be implemented similarly to other reports, pointing to a new handler or an updated one.

    // Initial load for today's report
    loadReport();
}