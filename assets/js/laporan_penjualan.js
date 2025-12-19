function initLaporanPenjualanPage() {
    const form = document.getElementById('report-penjualan-form');
    const startDateInput = document.getElementById('penjualan-tanggal-mulai');
    const endDateInput = document.getElementById('penjualan-tanggal-akhir');
    const searchInput = document.getElementById('penjualan-search');
    const tableBody = document.getElementById('report-penjualan-content');
    const paginationContainer = document.getElementById('penjualan-report-pagination');
    const summaryContainer = document.getElementById('report-penjualan-summary');
    const exportPdfBtn = document.getElementById('export-penjualan-pdf');

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
        const search = searchInput.value;

        if (!startDate || !endDate) {
            showToast('Harap pilih rentang tanggal.', 'error');
            return;
        }

        tableBody.innerHTML = `<div class="text-center p-5"><div class="spinner-border"></div></div>`;
        summaryContainer.innerHTML = `<div class="text-center"><div class="spinner-border spinner-border-sm"></div></div>`;

        try {
            const params = new URLSearchParams({
                page,
                limit: 15,
                start_date: startDate,
                end_date: endDate,
                search: search
            });
            const response = await fetch(`${basePath}/api/laporan-penjualan?${params.toString()}`);
            const result = await response.json();

            if (result.status !== 'success') throw new Error(result.message);

            renderTable(result.data);
            renderPagination(paginationContainer, result.pagination, loadReport);
            renderSummary(result.pagination.summary);

        } catch (error) {
            tableBody.innerHTML = `<div class="alert alert-danger">Gagal memuat laporan: ${error.message}</div>`;
            summaryContainer.innerHTML = '';
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
                        <th>No. Faktur</th>
                        <th>Tanggal</th>
                        <th>Customer</th>
                        <th>Kasir</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Profit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
        `;
        data.forEach(item => {
            const statusBadge = item.status === 'void' 
                ? `<span class="badge bg-secondary">Dibatalkan</span>` 
                : `<span class="badge bg-success">Selesai</span>`;
            const profitClass = item.profit >= 0 ? 'text-success' : 'text-danger';
            const profitValue = item.status === 'void' ? formatRupiah(0) : formatRupiah(item.profit);
            tableHtml += `
                <tr>
                    <td>${item.nomor_referensi}</td>
                    <td>${new Date(item.tanggal_penjualan).toLocaleString('id-ID')}</td>
                    <td>${item.customer_name}</td>
                    <td>${item.username}</td>
                    <td class="text-end">${formatRupiah(item.total)}</td>
                    <td class="text-end fw-bold ${profitClass}">${profitValue}</td>
                    <td>${statusBadge}</td>
                </tr>
            `;
        });
        tableHtml += `</tbody></table>`;
        tableBody.innerHTML = tableHtml;
    }

    function renderSummary(summary) {
        if (!summary) {
            summaryContainer.innerHTML = '';
            return;
        }

        const profitClass = summary.total_profit >= 0 ? 'text-success' : 'text-danger';

        summaryContainer.innerHTML = `
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="card card-body"><h6 class="card-subtitle mb-2 text-muted">Total Penjualan</h6><h4 class="card-title">${formatRupiah(summary.total_penjualan)}</h4></div>
                </div>
                <div class="col-md-4">
                    <div class="card card-body"><h6 class="card-subtitle mb-2 text-muted">Total HPP</h6><h4 class="card-title">${formatRupiah(summary.total_hpp)}</h4></div>
                </div>
                <div class="col-md-4">
                    <div class="card card-body"><h6 class="card-subtitle mb-2 text-muted">Estimasi Profit</h6><h4 class="card-title ${profitClass}">${formatRupiah(summary.total_profit)}</h4></div>
                </div>
            </div>`;
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        loadReport(1);
    });

    exportPdfBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const search = searchInput.value;

        if (!startDate || !endDate) {
            showToast('Harap pilih rentang tanggal untuk export.', 'error');
            return;
        }

        const params = new URLSearchParams({
            report: 'laporan-penjualan',
            start_date: startDate,
            end_date: endDate,
            search: search
        });

        const url = `${basePath}/api/pdf?${params.toString()}`;
        window.open(url, '_blank');
    });

    loadReport();
}