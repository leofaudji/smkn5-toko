function initLaporanKartuStokPage() {
    const itemSelect = document.getElementById('ks-item-id');
    const startDateInput = document.getElementById('ks-tanggal-mulai');
    const endDateInput = document.getElementById('ks-tanggal-akhir');
    const form = document.getElementById('kartu-stok-form');
    const contentDiv = document.getElementById('report-ks-content');
    const summaryDiv = document.getElementById('report-ks-summary');
    const headerDiv = document.getElementById('report-ks-header');
    const pdfButton = document.getElementById('export-kartu-stok-pdf');
    const tampilkanBtn = document.getElementById('ks-tampilkan-btn');

    if (!form) return;

    const commonOptions = { dateFormat: "d-m-Y", allowInput: true };
    const startDatePicker = flatpickr(startDateInput, commonOptions);
    const endDatePicker = flatpickr(endDateInput, commonOptions);

    // Set default date range to current month
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    startDatePicker.setDate(firstDay, true);
    endDatePicker.setDate(today, true);
 
    // Load items into select dropdown
    async function loadItems() {
        try {
            const params = new URLSearchParams({ action: 'list', limit: 99999 });
            const response = await fetch(`${basePath}/api/stok_handler.php?${params.toString()}`);
            const result = await response.json();
            if (result.status === 'success' && result.data) {
                itemSelect.innerHTML = '<option value="">-- Pilih Barang --</option>';
                result.data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = `${item.nama_barang} (${item.sku || 'N/A'})`;
                    itemSelect.appendChild(option);
                });
            } else {
                itemSelect.innerHTML = '<option value="">Gagal memuat barang</option>';
            }
        } catch (error) {
            console.error('Error loading items:', error);
            itemSelect.innerHTML = '<option value="">Error</option>';
        }
    }

    // Handle form submission
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const itemId = itemSelect.value;
        const startDate = startDateInput.value.split('-').reverse().join('-');
        const endDate = endDateInput.value.split('-').reverse().join('-');

        if (!itemId) {
            showToast('Silakan pilih barang terlebih dahulu.', 'warning');
            return;
        }

        const originalBtnHtml = tampilkanBtn.innerHTML;
        tampilkanBtn.disabled = true;
        tampilkanBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memuat...`;

        contentDiv.innerHTML = '<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div><p class="mt-2 text-sm text-gray-500">Memuat laporan...</p></div>';
        summaryDiv.style.display = 'none';
        headerDiv.style.display = 'none';
        pdfButton.style.display = 'none';

        try {
            const params = new URLSearchParams({
                action: 'get_kartu_stok',
                item_id: itemId,
                start_date: startDate,
                end_date: endDate
            });
            const response = await fetch(`${basePath}/api/stok_handler.php?${params.toString()}`);
            const result = await response.json();

            if (result.status === 'success') {
                renderReport(result.data);
            } else {
                contentDiv.innerHTML = `<div class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-200 p-4 rounded-md text-center">${result.message || 'Gagal memuat laporan.'}</div>`;
            }
        } catch (error) {
            console.error('Error fetching stock card report:', error);
            contentDiv.innerHTML = `<div class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-200 p-4 rounded-md text-center">Terjadi kesalahan saat mengambil data.</div>`;
        } finally {
            tampilkanBtn.disabled = false;
            tampilkanBtn.innerHTML = originalBtnHtml;
        }
    });

    function renderReport(data) {
        document.getElementById('ks-item-name').textContent = data.item_info.nama_barang;
        document.getElementById('ks-period').textContent = `${startDateInput.value} - ${endDateInput.value}`;
        headerDiv.style.display = 'block';

        document.getElementById('ks-summary-awal').textContent = formatNumber(data.summary.saldo_awal || 0);
        document.getElementById('ks-summary-masuk').textContent = `+${formatNumber(data.summary.total_masuk || 0)}`;
        document.getElementById('ks-summary-keluar').textContent = `-${formatNumber(data.summary.total_keluar || 0)}`;
        document.getElementById('ks-summary-akhir').textContent = formatNumber(data.summary.saldo_akhir || 0);
        summaryDiv.style.display = 'grid';
 
        let tableHtml = `
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Masuk</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keluar</th>
                        <th class="px-4 py-2 text-right text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Saldo</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-sm font-bold text-gray-900 dark:text-white">Saldo Awal</td>
                        <td class="px-4 py-2 text-sm font-bold text-right text-gray-900 dark:text-white">${formatNumber(data.summary.saldo_awal || 0)}</td>
                    </tr>
        `;

        if (data.transactions.length > 0) {
            data.transactions.forEach(trx => {
                tableHtml += `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">${formatDate(trx.tanggal)}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">${trx.keterangan}</td>
                        <td class="px-4 py-2 text-sm text-right text-green-600 dark:text-green-400">${trx.masuk > 0 ? `+${formatNumber(trx.masuk || 0)}` : ''}</td>
                        <td class="px-4 py-2 text-sm text-right text-red-600 dark:text-red-400">${trx.keluar > 0 ? `-${formatNumber(trx.keluar || 0)}` : ''}</td>
                        <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">${formatNumber(trx.saldo || 0)}</td>
                    </tr>
                `;
            });
        } else {
            tableHtml += '<tr><td colspan="5" class="text-center text-gray-500 py-4">Tidak ada transaksi pada periode ini.</td></tr>';
        }

        tableHtml += `</tbody></table>`;
        contentDiv.innerHTML = tableHtml;
        pdfButton.style.display = 'inline-flex';
    }

    loadItems();
}