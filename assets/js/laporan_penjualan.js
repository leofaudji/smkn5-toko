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
    
    // Use global appSettings provided by header.php for receipt printing
    const appSettings = window.appSettings || {};


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

    const viewTypeSelect = document.getElementById('penjualan-view-type');
    
    async function loadReport(page = 1) {
        const startDate = startDateInput.value.split('-').reverse().join('-');
        const endDate = endDateInput.value.split('-').reverse().join('-');
        const search = searchInput.value;
        const viewType = viewTypeSelect.value;

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
                search: search,
                view_type: viewType
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
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">HPP</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Profit</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        `;
        let totalAmount = 0;
        let totalHpp = 0;
        let totalProfit = 0;

        data.forEach(item => {
            const isVoid = item.status === 'void';
            const amount = isVoid ? 0 : parseFloat(item.total);
            const hpp = isVoid ? 0 : parseFloat(item.total_hpp);
            const profit = isVoid ? 0 : (item.total - item.total_hpp);

            totalAmount += amount;
            totalHpp += hpp;
            totalProfit += profit;

            const statusBadge = isVoid 
                ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Dibatalkan</span>` 
                : `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Selesai</span>`;
            
            const profitClass = profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
            const profitValue = formatRupiah(profit);

            tableHtml += `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.nomor_referensi}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${new Date(item.tanggal_penjualan).toLocaleString('id-ID')}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${item.customer_name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${item.username}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">${formatRupiah(item.total)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300 text-right">${formatRupiah(item.total_hpp)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold ${profitClass}">${profitValue}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">${statusBadge}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <button class="text-primary hover:text-primary-700 btn-reprint" data-id="${item.id}" title="Cetak Ulang Struk">
                            <i class="bi bi-printer"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        const totalProfitClass = totalProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';

        tableHtml += `
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-800/50 font-bold border-t-2 border-gray-200 dark:border-gray-700">
                <tr>
                    <td colspan="4" class="px-6 py-4 text-sm text-gray-900 dark:text-white text-right uppercase tracking-wider">Grand Total (Halaman Ini)</td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white text-right">${formatRupiah(totalAmount)}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white text-right">${formatRupiah(totalHpp)}</td>
                    <td class="px-6 py-4 text-sm text-right ${totalProfitClass}">${formatRupiah(totalProfit)}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>`;
        tableBody.innerHTML = tableHtml;
    }

    function renderSummary(summary) {
        if (!summary) {
            summaryContainer.innerHTML = '';
            return;
        }

        const profitClass = (val) => val >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';

        summaryContainer.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <!-- Total Section -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700 text-center col-span-1 md:col-span-3">
                    <h6 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Total Seluruh Transaksi</h6>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <p class="text-xs text-gray-500">Penjualan</p>
                            <p class="font-bold text-gray-900 dark:text-white">${formatRupiah(summary.total_penjualan)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">HPP</p>
                            <p class="font-bold text-gray-900 dark:text-white">${formatRupiah(summary.total_hpp)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Profit</p>
                            <p class="font-bold ${profitClass(summary.total_profit)}">${formatRupiah(summary.total_profit)}</p>
                        </div>
                    </div>
                </div>

                <!-- Shop Section -->
                <div class="bg-blue-50/50 dark:bg-blue-900/20 rounded-lg shadow p-4 border border-blue-100 dark:border-blue-900/50">
                    <h6 class="text-xs font-bold text-blue-700 dark:text-blue-300 uppercase tracking-wider mb-3 flex items-center">
                        <i class="bi bi-shop me-2"></i> Barang Toko
                    </h6>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Penjualan:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">${formatRupiah(summary.shop.sales)}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Profit:</span>
                            <span class="font-bold ${profitClass(summary.shop.profit)}">${formatRupiah(summary.shop.profit)}</span>
                        </div>
                    </div>
                </div>

                <!-- Consignment Section -->
                <div class="bg-purple-50/50 dark:bg-purple-900/20 rounded-lg shadow p-4 border border-purple-100 dark:border-purple-900/50">
                    <h6 class="text-xs font-bold text-purple-700 dark:text-purple-300 uppercase tracking-wider mb-3 flex items-center">
                        <i class="bi bi-box-seam me-2"></i> Barang Konsinyasi
                    </h6>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Penjualan:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">${formatRupiah(summary.consignment.sales)}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Profit:</span>
                            <span class="font-bold ${profitClass(summary.consignment.profit)}">${formatRupiah(summary.consignment.profit)}</span>
                        </div>
                    </div>
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
        const viewType = viewTypeSelect.value;

        if (!startDate || !endDate) {
            showToast('Harap pilih rentang tanggal untuk export.', 'error');
            return;
        }

        const params = new URLSearchParams({
            report: 'laporan-penjualan',
            start_date: startDate,
            end_date: endDate,
            search: search,
            view_type: viewType
        });

        const url = `${basePath}/api/pdf?${params.toString()}`;
        window.open(url, '_blank');
    });

    const printStrukWindow = async (penjualanId) => {
        try {
            const response = await fetch(`${basePath}/api/penjualan?action=get_detail&id=${penjualanId}`);
            const result = await response.json();

            if (!result.success) throw new Error(result.message);

            const detail = result.data;
            const items = result.data.items;

            let itemsHtml = '';
            items.forEach(item => {
                itemsHtml += `
                    <tr>
                        <td style="width: 60%;">${item.nama_barang}</td>
                        <td style="width: 10%; text-align: center;">${item.quantity}</td>
                        <td style="width: 30%; text-align: right;">${formatRupiah(item.subtotal)}</td>
                    </tr>
                `;
            });

            const width = 300;
            const height = 600;
            const left = (screen.width - width) / 2;
            const top = (screen.height - height) / 2;

            const printWindow = window.open('', 'PrintStruk', `width=${width},height=${height},top=${top},left=${left}`);
            
            if (!printWindow) {
                showToast('Pop-up terblokir. Silakan izinkan pop-up untuk situs ini.', 'warning');
                return;
            }

            const htmlContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Struk #${detail.nomor_referensi}</title>
                    <style>
                        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; margin: 0; padding: 10px; }
                        .text-center { text-align: center; }
                        .text-end { text-align: right; }
                        .fw-bold { font-weight: bold; }
                        .mb-1 { margin-bottom: 5px; }
                        .border-bottom { border-bottom: 1px dashed #000; }
                        .border-top { border-top: 1px dashed #000; }
                        table { width: 100%; border-collapse: collapse; }
                        .items-table td { vertical-align: top; }
                        @media print { @page { margin: 0; } body { margin: 5px; } }
                    </style>
                </head>
                <body>
                    <div class="text-center mb-1">
                        <h3 style="margin: 0;">${(window.appSettings?.shop_name) || 'SMKN 5 TOKO'}</h3>
                        <div>${(window.appSettings?.shop_address) || 'Jl. Contoh No. 123'}</div>
                    </div>
                    <div class="border-bottom mb-1" style="padding-bottom: 5px;">
                        <div>No: ${detail.nomor_referensi}</div>
                        <div>Tgl: ${new Date(detail.tanggal_penjualan).toLocaleString('id-ID')}</div>
                        <div>Kasir: ${detail.created_by_username}</div>
                        <div>Pelanggan: ${detail.customer_name}</div>
                    </div>
                    <table class="items-table mb-1">
                        ${itemsHtml}
                    </table>
                    <div class="border-top" style="padding-top: 5px;">
                        <table style="width: 100%">
                            <tr><td>Total</td><td class="text-end fw-bold">${formatRupiah(detail.total)}</td></tr>
                            <tr><td>Bayar</td><td class="text-end">${formatRupiah(detail.bayar)}</td></tr>
                            <tr><td>Kembali</td><td class="text-end">${formatRupiah(detail.kembali)}</td></tr>
                        </table>
                    </div>
                    <div class="text-center" style="margin-top: 20px;">
                        <p>${((window.appSettings?.receipt_footer) || 'Terima Kasih<br>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan').replace(/\n/g, '<br>')}</p>
                    </div>
                    <script>
                        window.onload = function() { 
                            window.print(); 
                            window.close(); 
                        }
                    </script>
                </body>
                </html>
            `;

            printWindow.document.open();
            printWindow.document.write(htmlContent);
            printWindow.document.close();

        } catch (error) {
            console.error(error);
            showToast('Gagal mencetak struk: ' + error.message, 'error');
        }
    };

    tableBody.addEventListener('click', (e) => {
        const reprintBtn = e.target.closest('.btn-reprint');
        if (reprintBtn) {
            printStrukWindow(reprintBtn.dataset.id);
        }
    });

    loadReport();
}