function initLaporanMarginKategoriPage() {
    const form = document.getElementById('report-margin-kategori-form');
    const startDateInput = document.getElementById('margin-kategori-tanggal-mulai');
    const endDateInput = document.getElementById('margin-kategori-tanggal-akhir');
    const sortSelect = document.getElementById('margin-kategori-sort');
    const orderSelect = document.getElementById('margin-kategori-order');
    const filterSearchInput = document.getElementById('filter-kategori-search');

    const summaryContainer = document.getElementById('report-margin-kategori-summary');
    const tableContainer = document.getElementById('report-margin-kategori-content');

    const exportCsvBtn = document.getElementById('export-margin-kategori-csv');
    const exportPdfBtn = document.getElementById('export-margin-kategori-pdf');
    const printBtn = document.getElementById('print-margin-kategori-btn');

    const btnBulanIni = document.getElementById('btn-kategori-bulan-ini');
    const btnBulanLalu = document.getElementById('btn-kategori-bulan-lalu');
    const btnTahunIni = document.getElementById('btn-kategori-tahun-ini');

    // Modal elements
    const modal = document.getElementById('modal-category-items');
    const modalTitle = document.getElementById('modal-category-title');
    const modalSubtitle = document.getElementById('modal-category-subtitle');
    const modalBody = document.getElementById('modal-category-body');
    const modalCloseIcon = document.getElementById('modal-category-close');
    const modalCloseBtn = document.getElementById('modal-category-close-btn');
    const modalBackdrop = document.getElementById('modal-category-backdrop');

    if (!form) return;
    if (form.dataset.initialized === 'true') return;
    form.dataset.initialized = 'true';

    // Helper to format date reliably whether DD-MM-YYYY or YYYY-MM-DD
    function getFormattedDates() {
        let s = startDateInput.value ? startDateInput.value.trim() : '';
        let e = endDateInput.value ? endDateInput.value.trim() : '';

        if (s && s.includes('-')) {
            const p = s.split('-');
            if (p[0].length === 2) s = p.reverse().join('-');
        }
        if (e && e.includes('-')) {
            const p = e.split('-');
            if (p[0].length === 2) e = p.reverse().join('-');
        }
        return { startDate: s, endDate: e };
    }

    // Date picker setup
    const commonOptions = { dateFormat: "d-m-Y", allowInput: true };
    const startDatePicker = flatpickr(startDateInput, commonOptions);
    const endDatePicker = flatpickr(endDateInput, commonOptions);

    // Default dates to current month
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    startDatePicker.setDate(firstDayOfMonth, true);
    endDatePicker.setDate(today, true);

    // Chart instances
    let salesChartInstance = null;
    let marginChartInstance = null;
    let barChartInstance = null;
    let currentRawData = [];

    // Formatters
    const formatRupiah = (angka) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka || 0);
    };

    const formatNumber = (angka) => {
        return new Intl.NumberFormat('id-ID').format(angka || 0);
    };

    // Quick period buttons
    if (btnBulanIni) {
        btnBulanIni.addEventListener('click', () => {
            const now = new Date();
            startDatePicker.setDate(new Date(now.getFullYear(), now.getMonth(), 1), true);
            endDatePicker.setDate(now, true);
            loadReport();
        });
    }

    if (btnBulanLalu) {
        btnBulanLalu.addEventListener('click', () => {
            const now = new Date();
            const firstDayLastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const lastDayLastMonth = new Date(now.getFullYear(), now.getMonth(), 0);
            startDatePicker.setDate(firstDayLastMonth, true);
            endDatePicker.setDate(lastDayLastMonth, true);
            loadReport();
        });
    }

    if (btnTahunIni) {
        btnTahunIni.addEventListener('click', () => {
            const now = new Date();
            startDatePicker.setDate(new Date(now.getFullYear(), 0, 1), true);
            endDatePicker.setDate(now, true);
            loadReport();
        });
    }

    // Modal helpers
    function openModal() {
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    }

    if (modalCloseIcon) modalCloseIcon.addEventListener('click', closeModal);
    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
    if (modalBackdrop) modalBackdrop.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // Form submit listener
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        loadReport();
    });

    if (sortSelect) sortSelect.addEventListener('change', () => loadReport());
    if (orderSelect) orderSelect.addEventListener('change', () => loadReport());

    // Instant filter table by category name
    if (filterSearchInput) {
        filterSearchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const rows = tableContainer.querySelectorAll('tbody tr.category-row');
            rows.forEach(row => {
                const catName = row.getAttribute('data-cat-name') || '';
                if (!query || catName.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Export Handlers
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', () => {
            const { startDate, endDate } = getFormattedDates();
            const sortBy = sortSelect ? sortSelect.value : 'total_neto';
            const sortOrder = orderSelect ? orderSelect.value : 'desc';

            const params = new URLSearchParams({
                report: 'laporan-margin-kategori',
                format: 'csv',
                start_date: startDate,
                end_date: endDate,
                sort_by: sortBy,
                sort_order: sortOrder
            });

            window.location.href = `${basePath}/api/laporan_cetak_csv_handler.php?${params.toString()}`;
        });
    }

    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', () => {
            const { startDate, endDate } = getFormattedDates();
            const sortBy = sortSelect ? sortSelect.value : 'total_neto';
            const sortOrder = orderSelect ? orderSelect.value : 'desc';

            const params = new URLSearchParams({
                report: 'laporan-margin-kategori',
                orientation: 'landscape',
                start_date: startDate,
                end_date: endDate,
                sort_by: sortBy,
                sort_order: sortOrder
            });

            window.open(`${basePath}/api/laporan_cetak_handler.php?${params.toString()}`, '_blank');
        });
    }

    if (printBtn) {
        printBtn.addEventListener('click', () => {
            window.print();
        });
    }

    // Main Report Fetcher
    async function loadReport() {
        const { startDate, endDate } = getFormattedDates();
        const sortBy = sortSelect ? sortSelect.value : 'total_neto';
        const sortOrder = orderSelect ? orderSelect.value : 'desc';

        if (!startDate || !endDate) {
            showToast('Harap pilih rentang tanggal.', 'error');
            return;
        }

        tableContainer.innerHTML = `
            <div class="flex flex-col items-center justify-center p-12 text-gray-500 dark:text-gray-400">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary mb-3"></div>
                <p class="text-sm font-medium">Mengkalkulasi kontribusi & margin per kategori...</p>
            </div>
        `;

        try {
            const params = new URLSearchParams({
                start_date: startDate,
                end_date: endDate,
                sort_by: sortBy,
                sort_order: sortOrder
            });

            const response = await fetch(`${basePath}/api/laporan-margin-kategori?${params.toString()}`);
            const result = await response.json();

            if (result.status !== 'success') {
                throw new Error(result.message || 'Gagal memuat data laporan.');
            }

            currentRawData = result.data;
            renderSummaryCards(result.summary);
            renderCharts(result.charts);
            renderTable(result.data, result.summary);

        } catch (error) {
            console.error(error);
            tableContainer.innerHTML = `
                <div class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-200 p-6 rounded-lg text-center font-medium">
                    <i class="bi bi-exclamation-triangle-fill text-lg mr-2"></i> ${error.message}
                </div>
            `;
        }
    }

    // 1. Executive Summary Cards
    function renderSummaryCards(summary) {
        if (!summaryContainer || !summary) return;

        const marginColor = summary.total_margin >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
        const badgeBg = summary.total_margin >= 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-300';

        summaryContainer.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Penjualan Bersih -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Penjualan Bersih (Net)</span>
                        <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <i class="bi bi-cash-stack text-lg"></i>
                        </div>
                    </div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                        ${formatRupiah(summary.total_penjualan)}
                    </div>
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center justify-between">
                        <span>Bruto: ${formatRupiah(summary.total_bruto)}</span>
                        <span class="text-red-500">Disc: ${formatRupiah(summary.total_diskon)}</span>
                    </div>
                </div>

                <!-- Card 2: Total HPP Modal -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total HPP (Modal)</span>
                        <div class="w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <i class="bi bi-wallet2 text-lg"></i>
                        </div>
                    </div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                        ${formatRupiah(summary.total_hpp)}
                    </div>
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center justify-between">
                        <span>Total Qty Terjual:</span>
                        <span class="font-semibold text-gray-700 dark:text-gray-200">${formatNumber(summary.total_qty)} pcs</span>
                    </div>
                </div>

                <!-- Card 3: Total Margin Laba Kotor -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Margin (Laba)</span>
                        <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i class="bi bi-graph-up-arrow text-lg"></i>
                        </div>
                    </div>
                    <div class="text-xl font-bold ${marginColor}">
                        ${formatRupiah(summary.total_margin)}
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Rata-rata Margin:</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border ${badgeBg}">
                            ${summary.margin_pct}%
                        </span>
                    </div>
                </div>

                <!-- Card 4: Kategori Juara -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kategori Teratas</span>
                        <div class="w-9 h-9 rounded-lg bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                            <i class="bi bi-trophy-fill text-lg"></i>
                        </div>
                    </div>
                    <div class="text-xs space-y-1">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 dark:text-gray-400">Top Omset:</span>
                            <span class="font-bold text-gray-800 dark:text-white truncate max-w-[130px]" title="${summary.top_sales?.kategori || '-'}">
                                ${summary.top_sales?.kategori || '-'}
                            </span>
                        </div>
                        <div class="text-[11px] text-right text-blue-600 dark:text-blue-400 font-semibold">
                            ${formatRupiah(summary.top_sales?.nominal)} (${summary.top_sales?.kontribusi}%)
                        </div>
                        <div class="flex justify-between items-center pt-1 border-t border-gray-100 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400">Top Laba:</span>
                            <span class="font-bold text-gray-800 dark:text-white truncate max-w-[130px]" title="${summary.top_margin?.kategori || '-'}">
                                ${summary.top_margin?.kategori || '-'}
                            </span>
                        </div>
                        <div class="text-[11px] text-right text-emerald-600 dark:text-emerald-400 font-semibold">
                            ${formatRupiah(summary.top_margin?.nominal)} (${summary.top_margin?.kontribusi}%)
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // 2. Visualizations with Chart.js
    function renderCharts(charts) {
        if (!charts) return;

        const palette = [
            '#3b82f6', // blue
            '#10b981', // emerald
            '#f59e0b', // amber
            '#8b5cf6', // violet
            '#ec4899', // pink
            '#06b6d4', // cyan
            '#64748b'  // slate / other
        ];

        // Chart 1: Donut Sales
        const salesCanvas = document.getElementById('chart-sales-donut');
        if (salesCanvas && charts.sales_chart) {
            if (salesChartInstance) salesChartInstance.destroy();
            const ctx1 = salesCanvas.getContext('2d');
            salesChartInstance = new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: charts.sales_chart.labels,
                    datasets: [{
                        data: charts.sales_chart.values,
                        backgroundColor: palette.slice(0, charts.sales_chart.labels.length),
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                font: { size: 11 },
                                padding: 10
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return ` ${context.label}: ${formatRupiah(value)} (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Chart 2: Donut Margin
        const marginCanvas = document.getElementById('chart-margin-donut');
        if (marginCanvas && charts.margin_chart) {
            if (marginChartInstance) marginChartInstance.destroy();
            const ctx2 = marginCanvas.getContext('2d');
            marginChartInstance = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: charts.margin_chart.labels,
                    datasets: [{
                        data: charts.margin_chart.values,
                        backgroundColor: palette.slice(0, charts.margin_chart.labels.length),
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                font: { size: 11 },
                                padding: 10
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return ` ${context.label}: ${formatRupiah(value)} (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Chart 3: Horizontal Bar % Margin
        const barCanvas = document.getElementById('chart-margin-bar');
        if (barCanvas && charts.bar_chart) {
            if (barChartInstance) barChartInstance.destroy();
            const ctx3 = barCanvas.getContext('2d');
            barChartInstance = new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: charts.bar_chart.labels,
                    datasets: [{
                        label: 'Margin %',
                        data: charts.bar_chart.values,
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                        barThickness: 14
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ` Ketebalan Margin: ${context.parsed.x}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(200, 200, 200, 0.15)' },
                            ticks: {
                                callback: function(value) { return value + '%'; },
                                font: { size: 10 }
                            }
                        },
                        y: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
        }
    }

    // 3. Matrix Table Render
    function renderTable(data, summary) {
        if (!tableContainer) return;

        if (!data || data.length === 0) {
            tableContainer.innerHTML = `
                <div class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 p-8 rounded-lg text-center">
                    <i class="bi bi-inbox text-3xl mb-2 block"></i>
                    Tidak ada transaksi penjualan pada rentang periode ini.
                </div>
            `;
            return;
        }

        let rowsHtml = '';
        data.forEach((row, index) => {
            const marginBadgeColor = row.margin_pct >= 25 
                ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300'
                : (row.margin_pct >= 10 
                    ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300'
                    : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300');

            rowsHtml += `
                <tr class="category-row hover:bg-gray-50/80 dark:hover:bg-gray-700/40 transition border-b border-gray-100 dark:border-gray-700/60" data-cat-name="${row.kategori_nama.toLowerCase()}">
                    <td class="px-3 py-3 text-center text-xs text-gray-400 font-medium">${index + 1}</td>
                    <td class="px-3 py-3">
                        <div class="font-bold text-gray-900 dark:text-white text-sm">
                            ${row.kategori_nama}
                        </div>
                        <div class="text-[11px] text-gray-400">
                            ${row.kategori_nama === 'Barang Konsinyasi' ? '<span class="text-purple-600 dark:text-purple-400 font-medium">Titipan Konsinyasi</span>' : 'Departemen / Kategori Toko'}
                        </div>
                    </td>
                    <td class="px-3 py-3 text-center text-xs text-gray-700 dark:text-gray-300 font-semibold">${formatNumber(row.total_sku)}</td>
                    <td class="px-3 py-3 text-right text-xs text-gray-800 dark:text-gray-200 font-medium">${formatNumber(row.total_qty)}</td>
                    <td class="px-3 py-3 text-right text-xs text-gray-500 dark:text-gray-400">${formatRupiah(row.total_bruto)}</td>
                    <td class="px-3 py-3 text-right text-xs text-red-500">${row.total_diskon > 0 ? formatRupiah(row.total_diskon) : '-'}</td>
                    <td class="px-3 py-3 text-right text-xs font-bold text-blue-700 dark:text-blue-400 bg-blue-50/30 dark:bg-blue-900/10">${formatRupiah(row.total_neto)}</td>
                    
                    <!-- % Pangsa Omset -->
                    <td class="px-3 py-3 text-right">
                        <div class="text-xs font-bold text-gray-800 dark:text-gray-200">${row.kontribusi_sales_pct}%</div>
                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 ml-auto mt-1">
                            <div class="bg-blue-600 h-1.5 rounded-full" style="width: ${Math.min(100, Math.max(2, row.kontribusi_sales_pct))}%"></div>
                        </div>
                    </td>

                    <td class="px-3 py-3 text-right text-xs text-gray-700 dark:text-gray-300">${formatRupiah(row.total_hpp)}</td>
                    <td class="px-3 py-3 text-right text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50/30 dark:bg-emerald-900/10">${formatRupiah(row.total_margin)}</td>
                    
                    <!-- % Pangsa Margin -->
                    <td class="px-3 py-3 text-right">
                        <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400">${row.kontribusi_margin_pct}%</div>
                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 ml-auto mt-1">
                            <div class="bg-emerald-500 h-1.5 rounded-full" style="width: ${Math.min(100, Math.max(2, row.kontribusi_margin_pct))}%"></div>
                        </div>
                    </td>

                    <!-- % Margin Kategori -->
                    <td class="px-3 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border ${marginBadgeColor}">
                            ${row.margin_pct}%
                        </span>
                    </td>

                    <!-- Aksi Drill-Down -->
                    <td class="px-3 py-3 text-center no-print">
                        <button type="button" class="btn-drilldown-category inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg text-primary hover:bg-primary-50 dark:hover:bg-primary-900/30 border border-primary-200 dark:border-primary-800 transition" data-cat="${encodeURIComponent(row.kategori_nama)}">
                            <i class="bi bi-search mr-1"></i> Rincian
                        </button>
                    </td>
                </tr>
            `;
        });

        tableContainer.innerHTML = `
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 font-bold border-b border-gray-200 dark:border-gray-700">
                        <th class="px-3 py-3 text-center w-10">#</th>
                        <th class="px-3 py-3 text-left">Kategori Barang</th>
                        <th class="px-3 py-3 text-center w-16">SKU</th>
                        <th class="px-3 py-3 text-right w-20">Qty</th>
                        <th class="px-3 py-3 text-right w-28">Omset Bruto</th>
                        <th class="px-3 py-3 text-right w-24">Diskon Nota</th>
                        <th class="px-3 py-3 text-right w-32 bg-blue-50/50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300">Penjualan (Net)</th>
                        <th class="px-3 py-3 text-right w-24">% Omset</th>
                        <th class="px-3 py-3 text-right w-28">HPP (Modal)</th>
                        <th class="px-3 py-3 text-right w-32 bg-emerald-50/50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300">Margin (Laba)</th>
                        <th class="px-3 py-3 text-right w-24">% Laba</th>
                        <th class="px-3 py-3 text-center w-24">Margin %</th>
                        <th class="px-3 py-3 text-center w-20 no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    ${rowsHtml}
                </tbody>
                <tfoot>
                    <tr class="bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white font-bold border-t-2 border-gray-300 dark:border-gray-600">
                        <td class="px-3 py-3.5 text-center" colspan="2">TOTAL KESELURUHAN</td>
                        <td class="px-3 py-3.5 text-center">${formatNumber(data.reduce((a, b) => a + b.total_sku, 0))}</td>
                        <td class="px-3 py-3.5 text-right">${formatNumber(summary.total_qty)}</td>
                        <td class="px-3 py-3.5 text-right">${formatRupiah(summary.total_bruto)}</td>
                        <td class="px-3 py-3.5 text-right text-red-600 dark:text-red-400">${formatRupiah(summary.total_diskon)}</td>
                        <td class="px-3 py-3.5 text-right text-blue-700 dark:text-blue-300 bg-blue-100/50 dark:bg-blue-900/40">${formatRupiah(summary.total_penjualan)}</td>
                        <td class="px-3 py-3.5 text-right">100.0%</td>
                        <td class="px-3 py-3.5 text-right">${formatRupiah(summary.total_hpp)}</td>
                        <td class="px-3 py-3.5 text-right text-emerald-700 dark:text-emerald-300 bg-emerald-100/50 dark:bg-emerald-900/40">${formatRupiah(summary.total_margin)}</td>
                        <td class="px-3 py-3.5 text-right text-emerald-600">100.0%</td>
                        <td class="px-3 py-3.5 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-300">
                                ${summary.margin_pct}%
                            </span>
                        </td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
            </table>
        `;

        // Attach drill-down click events
        tableContainer.querySelectorAll('.btn-drilldown-category').forEach(btn => {
            btn.addEventListener('click', () => {
                const category = decodeURIComponent(btn.dataset.cat);
                loadCategoryItems(category);
            });
        });
    }

    // 4. Drill-Down: Top Items per Category
    async function loadCategoryItems(categoryName) {
        if (!modal) return;

        modalTitle.innerHTML = `<i class="bi bi-box-seam text-primary"></i> Produk dalam Kategori: <span class="text-primary">${categoryName}</span>`;
        modalSubtitle.textContent = `Daftar produk terlaris dan kontribusi margin pada kategori ini.`;
        modalBody.innerHTML = `
            <div class="flex flex-col items-center justify-center py-12 text-gray-500">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mb-3"></div>
                <p class="text-xs">Memuat rincian produk kategori...</p>
            </div>
        `;
        openModal();

        const { startDate, endDate } = getFormattedDates();

        try {
            const params = new URLSearchParams({
                action: 'get_category_items',
                category: categoryName,
                start_date: startDate,
                end_date: endDate
            });

            const res = await fetch(`${basePath}/api/laporan-margin-kategori?${params.toString()}`);
            const json = await res.json();

            if (json.status !== 'success') {
                throw new Error(json.message || 'Gagal mengambil data produk.');
            }

            const items = json.data;
            if (!items || items.length === 0) {
                modalBody.innerHTML = `
                    <div class="text-center py-8 text-gray-500 text-sm">
                        Tidak ada transaksi produk pada kategori ini.
                    </div>
                `;
                return;
            }

            let itemRows = '';
            items.forEach((item, idx) => {
                const marginColor = item.total_margin >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
                itemRows += `
                    <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40 border-b border-gray-100 dark:border-gray-700">
                        <td class="px-3 py-2.5 text-center text-xs text-gray-400">${idx + 1}</td>
                        <td class="px-3 py-2.5 font-mono text-xs text-gray-600 dark:text-gray-300">${item.sku || '-'}</td>
                        <td class="px-3 py-2.5 text-xs font-semibold text-gray-900 dark:text-white">${item.nama_barang}</td>
                        <td class="px-3 py-2.5 text-right text-xs font-medium text-gray-800 dark:text-gray-200">${formatNumber(item.qty)}</td>
                        <td class="px-3 py-2.5 text-right text-xs font-bold text-blue-700 dark:text-blue-400">${formatRupiah(item.total_neto)}</td>
                        <td class="px-3 py-2.5 text-right text-xs text-gray-600 dark:text-gray-300">${formatRupiah(item.total_hpp)}</td>
                        <td class="px-3 py-2.5 text-right text-xs font-bold ${marginColor}">${formatRupiah(item.total_margin)}</td>
                        <td class="px-3 py-2.5 text-center text-xs font-semibold text-gray-700 dark:text-gray-300">${item.margin_pct}%</td>
                    </tr>
                `;
            });

            modalBody.innerHTML = `
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 font-bold">
                                <th class="px-3 py-2.5 text-center w-10">#</th>
                                <th class="px-3 py-2.5 text-left w-24">SKU</th>
                                <th class="px-3 py-2.5 text-left">Nama Barang</th>
                                <th class="px-3 py-2.5 text-right w-16">Terjual</th>
                                <th class="px-3 py-2.5 text-right w-28">Penjualan (Net)</th>
                                <th class="px-3 py-2.5 text-right w-28">HPP (Modal)</th>
                                <th class="px-3 py-2.5 text-right w-28">Margin (Laba)</th>
                                <th class="px-3 py-2.5 text-center w-20">Margin %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            ${itemRows}
                        </tbody>
                    </table>
                </div>
            `;

        } catch (err) {
            modalBody.innerHTML = `
                <div class="text-center py-6 text-red-600 text-sm">
                    <i class="bi bi-exclamation-circle text-xl mr-2"></i> ${err.message}
                </div>
            `;
        }
    }

    // Initial report load
    loadReport();
}
