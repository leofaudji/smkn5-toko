function initLaporanPenjualanPage() {
    const form = document.getElementById('report-penjualan-form');
    const startDateInput = document.getElementById('penjualan-tanggal-mulai');
    const endDateInput = document.getElementById('penjualan-tanggal-akhir');
    const searchInput = document.getElementById('penjualan-search');
    const tableBody = document.getElementById('report-penjualan-content');
    const paginationContainer = document.getElementById('penjualan-report-pagination');
    const paginationInfo = document.getElementById('penjualan-pagination-info');
    const summaryContainer = document.getElementById('report-penjualan-summary');
    const exportPdfBtn = document.getElementById('export-penjualan-pdf');

    const commonOptions = { dateFormat: "d-m-Y", allowInput: true };
    const startDatePicker = flatpickr(startDateInput, commonOptions);
    const endDatePicker = flatpickr(endDateInput, commonOptions);

    // Set default dates
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    startDatePicker.setDate(firstDayOfMonth, true);
    endDatePicker.setDate(today, true);

    const formatRupiah = (angka) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    };

    async function loadReport(page = 1) {
        const startDate = startDateInput.value.split('-').reverse().join('-');
        const endDate = endDateInput.value.split('-').reverse().join('-');
        const search = searchInput.value;

        if (!startDate || !endDate) {
            showToast('Harap pilih rentang tanggal.', 'error');
            return;
        }

        tableBody.innerHTML = `<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>`;
        summaryContainer.innerHTML = `<div class="text-center"><div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary mx-auto"></div></div>`;

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

            renderTable(result.data, page);
            renderTailwindPagination(paginationContainer, result.pagination, loadReport);
            renderSummary(result.pagination.summary);
            if (paginationInfo) {
                const start = (result.pagination.page - 1) * result.pagination.limit + 1;
                const end = Math.min(result.pagination.page * result.pagination.limit, result.pagination.total_records);
                paginationInfo.textContent = `Menampilkan ${start} sampai ${end} dari ${result.pagination.total_records} data`;
            }

        } catch (error) {
            tableBody.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">Gagal memuat laporan: ${error.message}</div>`;
            summaryContainer.innerHTML = '';
        }
    }

    function renderTable(data, currentPage) {
        if (data.length === 0) {
            tableBody.innerHTML = '<div class="bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-200 p-4 rounded-md text-center">Tidak ada data penjualan pada periode ini.</div>';
            return;
        }

        let tableHtml = `
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. Faktur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kasir</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Profit</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        `;
        data.forEach(item => {
            const statusBadge = item.status === 'void' 
                ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Dibatalkan</span>` 
                : `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Selesai</span>`;
            const profitClass = item.profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
            const profitValue = item.status === 'void' ? formatRupiah(0) : formatRupiah(item.profit);
            tableHtml += `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.nomor_referensi}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${new Date(item.tanggal_penjualan).toLocaleString('id-ID')}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${item.customer_name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${item.username}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">${formatRupiah(item.total)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold ${profitClass}">${profitValue}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">${statusBadge}</td>
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

        const profitClass = summary.total_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';

        summaryContainer.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-600 text-center">
                    <h6 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Total Penjualan</h6>
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white">${formatRupiah(summary.total_penjualan)}</h4>
                </div>
                <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-600 text-center">
                    <h6 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Total HPP</h6>
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white">${formatRupiah(summary.total_hpp)}</h4>
                </div>
                <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-600 text-center">
                    <h6 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Estimasi Profit</h6>
                    <h4 class="text-xl font-bold ${profitClass}">${formatRupiah(summary.total_profit)}</h4>
                </div>
            </div>`;
    }

    function renderTailwindPagination(container, pagination, callback) {
        if (pagination.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '<nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">';
        
        // Previous
        if (pagination.page > 1) {
            html += `<button type="button" data-page="${pagination.page - 1}" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <span class="sr-only">Previous</span>
                        <i class="bi bi-chevron-left"></i>
                     </button>`;
        }

        // Pages
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (i === pagination.page) {
                html += `<button type="button" aria-current="page" class="z-10 bg-primary border-primary text-white relative inline-flex items-center px-4 py-2 border text-sm font-medium">${i}</button>`;
            } else {
                if (i === 1 || i === pagination.total_pages || (i >= pagination.page - 2 && i <= pagination.page + 2)) {
                     html += `<button type="button" data-page="${i}" class="bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">${i}</button>`;
                } else if (i === pagination.page - 3 || i === pagination.page + 3) {
                    html += `<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300">...</span>`;
                }
            }
        }

        // Next
        if (pagination.page < pagination.total_pages) {
            html += `<button type="button" data-page="${pagination.page + 1}" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <span class="sr-only">Next</span>
                        <i class="bi bi-chevron-right"></i>
                     </button>`;
        }

        html += '</nav>';
        container.innerHTML = html;

        container.querySelectorAll('button[data-page]').forEach(btn => {
            btn.addEventListener('click', () => {
                callback(parseInt(btn.dataset.page));
            });
        });
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        loadReport(1);
    });

    exportPdfBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const startDate = startDateInput.value.split('-').reverse().join('-');
        const endDate = endDateInput.value.split('-').reverse().join('-');
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