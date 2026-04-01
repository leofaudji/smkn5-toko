function initLaporanPembelianPage() {
    const filterForm = document.getElementById('report-pembelian-form');
    const startDateInput = document.getElementById('pembelian-tanggal-mulai');
    const endDateInput = document.getElementById('pembelian-tanggal-akhir');
    const supplierFilter = document.getElementById('pembelian-filter-supplier');
    const searchInput = document.getElementById('pembelian-search');
    const tableBody = document.getElementById('report-pembelian-content');
    const paginationContainer = document.getElementById('pembelian-report-pagination');
    const paginationInfo = document.getElementById('pembelian-pagination-info');
    const summaryContainer = document.getElementById('report-pembelian-summary');
    const exportPdfBtn = document.getElementById('export-pembelian-pdf');
    const exportCsvBtn = document.getElementById('pembelian-csv-btn');

    // Initialize Flatpickr
    const commonOptions = { dateFormat: "d-m-Y", allowInput: true };
    const startDatePicker = flatpickr(startDateInput, commonOptions);
    const endDatePicker = flatpickr(endDateInput, commonOptions);

    // Set default dates (first day of month to today)
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    startDatePicker.setDate(firstDayOfMonth, true);
    endDatePicker.setDate(today, true);

    // Load Suppliers for filter
    const loadSuppliers = async () => {
        try {
            const response = await fetch(`${basePath}/api/konsinyasi?action=list_suppliers`);
            const result = await response.json();
            if (result.status === 'success') {
                result.data.forEach(supplier => {
                    const option = new Option(supplier.nama_pemasok, supplier.id);
                    supplierFilter.add(option);
                });
            }
        } catch (error) {
            console.error('Gagal memuat daftar pemasok:', error);
        }
    };
    loadSuppliers();

    const formatCurrency = (angka) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    };

    async function loadReport(page = 1) {
        const startDate = startDateInput.value.split('-').reverse().join('-');
        const endDate = endDateInput.value.split('-').reverse().join('-');
        const supplierId = supplierFilter.value;
        const search = searchInput.value;

        tableBody.innerHTML = `<tr><td colspan="6" class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>`;
        summaryContainer.innerHTML = `<div class="flex justify-center p-4"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div></div>`;

        try {
            const params = new URLSearchParams({
                page,
                start_date: startDate,
                end_date: endDate,
                supplier_id: supplierId,
                search: search
            });

            const response = await fetch(`${basePath}/api/laporan-pembelian?${params.toString()}`);
            const result = await response.json();

            if (result.status !== 'success') throw new Error(result.message);

            renderSummary(result.pagination.summary);
            renderTable(result.data);
            renderTailwindPagination(paginationContainer, result.pagination, loadReport);
            
            if (paginationInfo) {
                paginationInfo.textContent = `Menampilkan ${result.pagination.from} - ${result.pagination.to} dari ${result.pagination.total_records} data.`;
            }

        } catch (error) {
            console.error(error);
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center p-5 text-red-500">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    function renderSummary(summary) {
        summaryContainer.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
                    <div class="text-sm text-blue-600 dark:text-blue-400 font-medium">Total Pembelian</div>
                    <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">${formatCurrency(summary.total)}</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-100 dark:border-green-800">
                    <div class="text-sm text-green-600 dark:text-green-400 font-medium">Total Tunai (Lunas)</div>
                    <div class="text-2xl font-bold text-green-900 dark:text-green-100">${formatCurrency(summary.cash)}</div>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-100 dark:border-red-800">
                    <div class="text-sm text-red-600 dark:text-red-400 font-medium">Total Kredit (Hutang)</div>
                    <div class="text-2xl font-bold text-red-900 dark:text-red-100">${formatCurrency(summary.credit)}</div>
                </div>
            </div>
        `;
    }

    function renderTable(data) {
        tableBody.innerHTML = '';
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center p-5 text-gray-500 border-b">Tidak ada data ditemukan.</td></tr>';
            return;
        }

        data.forEach(item => {
            let statusBadge = '';
            if (item.status === 'paid') statusBadge = '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Lunas</span>';
            else if (item.status === 'open') statusBadge = '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">Hutang</span>';
            else statusBadge = '<span class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 rounded-full">Batal</span>';

            const row = `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${new Date(item.tanggal_pembelian).toLocaleDateString('id-ID')}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.nomor_referensi}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.nama_pemasok || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-capitalize">${item.payment_method === 'cash' ? 'Tunai' : 'Kredit'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">${statusBadge}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-white">${formatCurrency(item.total)}</td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', row);
        });
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

    filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        loadReport(1);
    });

    exportPdfBtn.addEventListener('click', () => {
        const startDate = startDateInput.value.split('-').reverse().join('-');
        const endDate = endDateInput.value.split('-').reverse().join('-');
        const supplierId = supplierFilter.value;
        const search = searchInput.value;

        const tempForm = document.createElement('form');
        tempForm.method = 'POST';
        tempForm.action = `${basePath}/api/pdf`;
        tempForm.target = '_blank';

        const params = {
            report: 'laporan-pembelian',
            start_date: startDate,
            end_date: endDate,
            supplier_id: supplierId,
            search: search
        };

        for (const key in params) {
            if (params.hasOwnProperty(key)) {
                const hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = key;
                hiddenField.value = params[key];
                tempForm.appendChild(hiddenField);
            }
        }

        document.body.appendChild(tempForm);
        tempForm.submit();
        document.body.removeChild(tempForm);
    });

    exportCsvBtn.addEventListener('click', () => {
        const startDate = startDateInput.value.split('-').reverse().join('-');
        const endDate = endDateInput.value.split('-').reverse().join('-');
        const supplierId = supplierFilter.value;
        const search = searchInput.value;

        const params = new URLSearchParams({
            report: 'laporan-pembelian',
            start_date: startDate,
            end_date: endDate,
            supplier_id: supplierId,
            search: search
        });
        window.location.href = `${basePath}/api/csv?${params.toString()}`;
    });

    loadReport();
}
