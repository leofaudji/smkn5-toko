function initLaporanPenjualanItemPage() {
    const form = document.getElementById('report-penjualan-item-form');
    const startDateInput = document.getElementById('penjualan-item-tanggal-mulai');
    const endDateInput = document.getElementById('penjualan-item-tanggal-akhir');
    const sortSelect = document.getElementById('penjualan-item-sort');
    const tableBody = document.getElementById('report-penjualan-item-content');
    const paginationContainer = document.getElementById('penjualan-item-report-pagination');
    const paginationInfo = document.getElementById('penjualan-item-pagination-info');
    const exportPdfBtn = document.getElementById('export-penjualan-item-pdf');

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
        const sortBy = sortSelect.value;

        if (!startDate || !endDate) {
            showToast('Harap pilih rentang tanggal.', 'error');
            return;
        }

        tableBody.innerHTML = `<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>`;

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

            renderTable(result.data, page);
            renderTailwindPagination(paginationContainer, result.pagination, loadReport);
            if (paginationInfo) {
                const start = (result.pagination.page - 1) * result.pagination.limit + 1;
                const end = Math.min(result.pagination.page * result.pagination.limit, result.pagination.total_records);
                paginationInfo.textContent = `Menampilkan ${start} sampai ${end} dari ${result.pagination.total_records} data`;
            }

        } catch (error) {
            tableBody.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">Gagal memuat laporan: ${error.message}</div>`;
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Barang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">SKU</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jumlah Terjual</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Penjualan</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estimasi Profit</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        `;
        data.forEach((item, index) => {
            const profitClass = item.total_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
            tableHtml += `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${index + 1 + (currentPage - 1) * 15}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.nama_barang}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${item.sku || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right font-bold">${item.total_terjual}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">${formatRupiah(item.total_penjualan)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold ${profitClass}">${formatRupiah(item.total_profit)}</td>
                </tr>
            `;
        });
        tableHtml += `</tbody></table>`;
        tableBody.innerHTML = tableHtml;
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
            start_date: startDateInput.value.split('-').reverse().join('-'),
            end_date: endDateInput.value.split('-').reverse().join('-'),
            sort_by: sortSelect.value
        });
        const url = `${basePath}/api/pdf?${params.toString()}`;
        window.open(url, '_blank');
    });

    loadReport();
}
