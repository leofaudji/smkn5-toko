function initLaporanPenjualanItemPage() {
    const form = document.getElementById('report-penjualan-item-form');
    const startDateInput = document.getElementById('penjualan-item-tanggal-mulai');
    const endDateInput = document.getElementById('penjualan-item-tanggal-akhir');
    const sortSelect = document.getElementById('penjualan-item-sort');
    const tableBody = document.getElementById('report-penjualan-item-content');
    const paginationContainer = document.getElementById('penjualan-item-report-pagination');
    const exportPdfBtn = document.getElementById('export-penjualan-item-pdf');

    // Set default dates
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    startDateInput.value = firstDayOfMonth.toISOString().split('T')[0];
    endDateInput.value = today.toISOString().split('T')[0];

    const formatRupiah = (angka) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    };

    async function loadReport(page = 1) {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const sortBy = sortSelect.value;

        if (!startDate || !endDate) {
            showToast('Harap pilih rentang tanggal.', 'error');
            return;
        }

        tableBody.innerHTML = `<div class="text-center p-5"><div class="spinner-border"></div></div>`;

        try {
            const params = new URLSearchParams({
                page,
                limit: 15,
                start_date: startDate,
                end_date: endDate,
                sort_by: sortBy
            });
            const response = await fetch(`${basePath}/api/laporan-penjualan-item?${params.toString()}`);
            const result = await response.json();

            if (result.status !== 'success') throw new Error(result.message);

            renderTable(result.data);
            renderPagination(paginationContainer, result.pagination, loadReport);

        } catch (error) {
            tableBody.innerHTML = `<div class="alert alert-danger">Gagal memuat laporan: ${error.message}</div>`;
        }
    }

    function renderTable(data) {
        if (data.length === 0) {
            tableBody.innerHTML = '<p class="text-center text-muted">Tidak ada data penjualan pada periode ini.</p>';
            return;
        }

        let tableHtml = `
            <table class="table table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nama Barang</th>
                        <th>SKU</th>
                        <th class="text-end">Jumlah Terjual</th>
                        <th class="text-end">Total Penjualan</th>
                        <th class="text-end">Estimasi Profit</th>
                    </tr>
                </thead>
                <tbody>
        `;
        data.forEach((item, index) => {
            const profitClass = item.total_profit >= 0 ? 'text-success' : 'text-danger';
            tableHtml += `
                <tr>
                    <td>${index + 1 + ((paginationContainer.querySelector('.active a')?.dataset.page || 1) - 1) * 15}</td>
                    <td>${item.nama_barang}</td>
                    <td>${item.sku || '-'}</td>
                    <td class="text-end fw-bold">${item.total_terjual}</td>
                    <td class="text-end">${formatRupiah(item.total_penjualan)}</td>
                    <td class="text-end fw-bold ${profitClass}">${formatRupiah(item.total_profit)}</td>
                </tr>
            `;
        });
        tableHtml += `</tbody></table>`;
        tableBody.innerHTML = tableHtml;
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        loadReport(1);
    });

    // Tambahkan event listener untuk setiap filter agar laporan otomatis diperbarui
    [startDateInput, endDateInput, sortSelect].forEach(element => {
        element.addEventListener('change', () => {
            loadReport(1);
        });
    });

    exportPdfBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const params = new URLSearchParams({
            report: 'laporan-penjualan-item',
            start_date: startDateInput.value,
            end_date: endDateInput.value,
            sort_by: sortSelect.value
        });
        const url = `${basePath}/api/pdf?${params.toString()}`;
        window.open(url, '_blank');
    });

    loadReport();
}
