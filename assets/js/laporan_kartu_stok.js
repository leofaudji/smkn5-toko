function initLaporanKartuStokPage() {
    const itemSelect = document.getElementById('ks-item-id');
    const startDateInput = document.getElementById('ks-tanggal-mulai');
    const endDateInput = document.getElementById('ks-tanggal-akhir');
    const form = document.getElementById('kartu-stok-form');
    const contentDiv = document.getElementById('report-ks-content');
    const summaryDiv = document.getElementById('report-ks-summary');
    const headerDiv = document.getElementById('report-ks-header');
    const pdfButton = document.getElementById('export-kartu-stok-pdf');

    // Set default date range to current month
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    startDateInput.value = firstDay.toISOString().split('T')[0];
    endDateInput.value = today.toISOString().split('T')[0];

    // Load items into select dropdown
    async function loadItems() {
        try {
            // Fetch all items for the dropdown. limit=-1 is not standard, so we use a very large number.
            // The action is 'list' as per stok_handler.php
            const params = new URLSearchParams({
                action: 'list',
                limit: 99999
            });
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
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        if (!itemId) {
            showToast('Silakan pilih barang terlebih dahulu.', 'warning');
            return;
        }

        contentDiv.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div><p>Memuat laporan...</p></div>';
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
                contentDiv.innerHTML = `<div class="alert alert-danger">${result.message || 'Gagal memuat laporan.'}</div>`;
            }
        } catch (error) {
            console.error('Error fetching stock card report:', error);
            contentDiv.innerHTML = `<div class="alert alert-danger">Terjadi kesalahan saat mengambil data.</div>`;
        }
    });

    function renderReport(data) {
        // Update header
        document.getElementById('ks-item-name').textContent = data.item_info.nama_barang;
        document.getElementById('ks-period').textContent = `${formatDate(startDateInput.value)} - ${formatDate(endDateInput.value)}`;
        headerDiv.style.display = 'block';

        // Update summary
        document.getElementById('ks-summary-awal').textContent = formatNumber(data.summary.saldo_awal || 0);
        document.getElementById('ks-summary-masuk').textContent = `+${formatNumber(data.summary.total_masuk || 0)}`;
        document.getElementById('ks-summary-keluar').textContent = `-${formatNumber(data.summary.total_keluar || 0)}`;
        document.getElementById('ks-summary-akhir').textContent = formatNumber(data.summary.saldo_akhir || 0);
        summaryDiv.style.display = 'block';

        // Render table
        let tableHtml = `
            <table class="table table-sm table-hover table-bordered">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th class="text-end">Masuk</th>
                        <th class="text-end">Keluar</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4"><strong>Saldo Awal</strong></td>
                        <td class="text-end"><strong>${formatNumber(data.summary.saldo_awal || 0)}</strong></td>
                    </tr>
        `;

        if (data.transactions.length > 0) {
            data.transactions.forEach(trx => {
                tableHtml += `
                    <tr>
                        <td>${formatDate(trx.tanggal)}</td>
                        <td>${trx.keterangan}</td>
                        <td class="text-end text-success">${trx.masuk > 0 ? `+${formatNumber(trx.masuk || 0)}` : ''}</td>
                        <td class="text-end text-danger">${trx.keluar > 0 ? `-${formatNumber(trx.keluar || 0)}` : ''}</td>
                        <td class="text-end">${formatNumber(trx.saldo || 0)}</td>
                    </tr>
                `;
            });
        } else {
            tableHtml += '<tr><td colspan="5" class="text-center text-muted">Tidak ada transaksi pada periode ini.</td></tr>';
        }

        tableHtml += `
                </tbody>
            </table>
        `;
        contentDiv.innerHTML = tableHtml;
        pdfButton.style.display = 'inline-block';
    }

    loadItems();
}