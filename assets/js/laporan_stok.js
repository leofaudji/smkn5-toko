function initLaporanStokPage() {
    const form = document.getElementById('report-stok-form');
    const reportContent = document.getElementById('report-stok-content');
    const reportSummary = document.getElementById('report-stok-summary');
    const reportHeader = document.getElementById('report-stok-header');
    const startDateInput = document.getElementById('stok-tanggal-mulai');
    const endDateInput = document.getElementById('stok-tanggal-akhir');

    if (!form) return;

    const commonOptions = { dateFormat: "d-m-Y", allowInput: true };
    const startDatePicker = flatpickr(startDateInput, commonOptions);
    const endDatePicker = flatpickr(endDateInput, commonOptions);

    // Set tanggal default ke bulan ini
    const now = new Date();
    startDatePicker.setDate(new Date(now.getFullYear(), now.getMonth(), 1), true);
    endDatePicker.setDate(new Date(now.getFullYear(), now.getMonth() + 1, 0), true);

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        loadReport();
    });

    async function loadReport() {
        const startDate = startDateInput.value.split('-').reverse().join('-');
        const endDate = endDateInput.value.split('-').reverse().join('-');

        if (!startDate || !endDate) {
            showToast('Harap pilih rentang tanggal.', 'error');
            return;
        }

        reportContent.innerHTML = `<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>`;
        reportSummary.innerHTML = '';
        reportHeader.textContent = `Laporan Stok Periode ${startDateInput.value} s/d ${endDateInput.value}`;

        try {
            const params = new URLSearchParams({ start_date: startDate, end_date: endDate });
            const response = await fetch(`${basePath}/api/laporan_stok?${params.toString()}`);
            const result = await response.json();

            if (result.status !== 'success') { 
                throw new Error(result.message);
            }

            renderReportTable(result.data);
            renderReportSummary(result.summary);

        } catch (error) {
            reportContent.innerHTML = `<div class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-200 p-4 rounded-md text-center">Gagal memuat laporan: ${error.message}</div>`;
        }
    }

    function renderReportTable(data) {
        let tableHtml = `
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12">No.</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">SKU</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Barang</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stok Awal</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Masuk</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keluar</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stok Akhir</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga Beli</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nilai Persediaan</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        `;

        if (data.length > 0) {
            data.forEach((item, index) => {
                tableHtml += `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">${index + 1}</td>
                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">${item.sku || '-'}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">${item.nama_barang}</td>
                        <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">${item.stok_awal}</td>
                        <td class="px-4 py-2 text-sm text-right text-green-600 dark:text-green-400">${item.masuk > 0 ? `+${item.masuk}` : '0'}</td>
                        <td class="px-4 py-2 text-sm text-right text-red-600 dark:text-red-400">${item.keluar > 0 ? `-${item.keluar}` : '0'}</td>
                        <td class="px-4 py-2 text-sm text-right font-bold text-gray-900 dark:text-white">${item.stok_akhir}</td>
                        <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">${formatCurrencyAccounting(item.harga_beli)}</td>
                        <td class="px-4 py-2 text-sm text-right font-bold text-gray-900 dark:text-white">${formatCurrencyAccounting(item.nilai_persediaan)}</td>
                    </tr>
                `;
            });
        } else {
            tableHtml += `<tr><td colspan="9" class="text-center text-gray-500 py-4">Tidak ada data untuk periode ini.</td></tr>`;
        }

        tableHtml += `</tbody></table>`;
        reportContent.innerHTML = tableHtml;
    }

    function renderReportSummary(summary) {
        reportSummary.innerHTML = `
            <div class="flex justify-end">
                <div class="w-full md:w-1/3">
                    <div class="flex justify-between items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-base font-medium text-gray-900 dark:text-white">Total Nilai Persediaan Akhir</span>
                        <span class="text-xl font-bold text-gray-900 dark:text-white">${formatCurrencyAccounting(summary.total_nilai_persediaan)}</span>
                    </div>
                </div>
            </div>
        `;
    }
}