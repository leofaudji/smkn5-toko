function initAnalisisStokReorderPage() {
    const form = document.getElementById('report-analisis-stok-form');
    if (!form) return;
    if (form.dataset.initialized === 'true') return;
    form.dataset.initialized = 'true';

    // --- Element Refs ---
    const startDateInput  = document.getElementById('analisis-stok-tanggal-mulai');
    const endDateInput    = document.getElementById('analisis-stok-tanggal-akhir');
    const categorySelect  = document.getElementById('analisis-stok-category');
    const bufferSelect    = document.getElementById('analisis-stok-buffer');
    const sortSelect      = document.getElementById('analisis-stok-sort');
    const orderSelect     = document.getElementById('analisis-stok-order');
    const searchInput     = document.getElementById('analisis-stok-search');
    const summaryContainer = document.getElementById('analisis-stok-summary');
    const tableContainer  = document.getElementById('analisis-stok-content');
    const tableInfo       = document.getElementById('stok-table-info');
    const exportCsvBtn    = document.getElementById('export-analisis-stok-csv');
    const exportPdfBtn    = document.getElementById('export-analisis-stok-pdf');
    const tabBar          = document.getElementById('stok-tab-bar');
    const customBtn       = document.getElementById('btn-stok-custom');
    const dateStartWrap   = document.getElementById('stok-date-start-wrap');
    const dateEndWrap     = document.getElementById('stok-date-end-wrap');

    // --- State ---
    let currentPeriodDays = 30;
    let customMode = false;
    let currentTab = 'all';
    let lastApiParams = {};
    let donutChart = null, barChart = null;
    let searchTimer = null;

    // --- Formatters ---
    const fmt = (n) => new Intl.NumberFormat('id-ID').format(n || 0);
    const fmtRp = (n) => 'Rp ' + fmt(Math.round(n || 0));
    const fmtPct = (n) => (parseFloat(n) || 0).toFixed(1) + '%';

    // --- Date helpers ---
    function getDateRange() {
        if (customMode && startDateInput.value && endDateInput.value) {
            return { start_date: startDateInput.value, end_date: endDateInput.value };
        }
        return { period_preset: currentPeriodDays };
    }

    // --- Preset buttons ---
    document.querySelectorAll('.stok-preset-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            customMode = false;
            currentPeriodDays = parseInt(btn.dataset.days);
            dateStartWrap.classList.add('hidden');
            dateEndWrap.classList.add('hidden');
            document.querySelectorAll('.stok-preset-btn').forEach(b => b.classList.remove('bg-primary-50', 'dark:bg-primary-900/30', 'text-primary'));
            btn.classList.add('bg-primary-50', 'dark:bg-primary-900/30', 'text-primary');
        });
    });

    if (customBtn) {
        customBtn.addEventListener('click', () => {
            customMode = true;
            dateStartWrap.classList.remove('hidden');
            dateEndWrap.classList.remove('hidden');
            document.querySelectorAll('.stok-preset-btn').forEach(b => b.classList.remove('bg-primary-50', 'dark:bg-primary-900/30', 'text-primary'));
        });
    }

    // --- Tabs ---
    if (tabBar) {
        tabBar.querySelectorAll('.stok-tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                currentTab = btn.dataset.tab;
                tabBar.querySelectorAll('.stok-tab-btn').forEach(b => {
                    b.classList.remove('border-primary', 'text-primary');
                    b.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                });
                btn.classList.add('border-primary', 'text-primary');
                btn.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                fetchData();
            });
        });
    }

    // --- Search ---
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(fetchData, 400);
        });
    }

    // --- Form submit ---
    form.addEventListener('submit', (e) => { e.preventDefault(); fetchData(); });

    // --- Main Fetch ---
    function fetchData() {
        const params = new URLSearchParams({
            ...getDateRange(),
            category_id: categorySelect ? categorySelect.value : 'all',
            buffer_days: bufferSelect ? bufferSelect.value : 14,
            tab: currentTab,
            sort_by: sortSelect ? sortSelect.value : 'total_neto',
            sort_order: orderSelect ? orderSelect.value : 'desc',
            search: searchInput ? searchInput.value.trim() : ''
        });
        lastApiParams = Object.fromEntries(params.entries());

        tableContainer.innerHTML = '<div class="flex justify-center items-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div><span class="ml-3 text-sm text-gray-500">Menganalisis data stok...</span></div>';

        fetch(`${basePath}/api/analisis-stok-reorder?` + params.toString())
            .then(r => r.json())
            .then(resp => {
                if (resp.status !== 'success') throw new Error(resp.message || 'Gagal memuat data');
                renderSummary(resp.summary, resp.meta);
                renderCharts(resp.charts);
                renderTable(resp.data, resp.meta);
                updateTabCounts(resp.summary.counts);
                populateCategories(resp.categories);
            })
            .catch(err => {
                tableContainer.innerHTML = `<div class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 p-6 rounded-lg text-center"><i class="bi bi-exclamation-triangle text-lg mr-2"></i> Gagal memuat laporan: ${err.message}</div>`;
            });
    }

    function populateCategories(cats) {
        if (!categorySelect || categorySelect.dataset.loaded === 'true') return;
        const existing = categorySelect.options[0];
        categorySelect.innerHTML = '';
        categorySelect.appendChild(existing);
        cats.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.nama_kategori;
            categorySelect.appendChild(opt);
        });
        categorySelect.dataset.loaded = 'true';
    }

    function updateTabCounts(counts) {
        const el = (id) => document.getElementById(id);
        if (el('tab-count-all')) el('tab-count-all').textContent = (counts.A || 0) + (counts.B || 0) + (counts.C || 0) + (counts.Dead || 0);
        if (el('tab-count-critical')) el('tab-count-critical').textContent = counts.Critical || 0;
        if (el('tab-count-reorder')) el('tab-count-reorder').textContent = counts.Reorder || 0;
    }

    // --- Summary Cards ---
    function renderSummary(s, meta) {
        if (!summaryContainer) return;
        const cards = [
            { icon: 'bi-cash-stack', color: 'blue', title: 'Total Penjualan Neto', val: fmtRp(s.total_store_sales), sub: `Periode ${meta.period_days} hari analisis` },
            { icon: 'bi-boxes', color: 'emerald', title: 'Nilai Persediaan Toko', val: fmtRp(s.total_inventory_value), sub: `${meta.total_items_analyzed} SKU dianalisis` },
            { icon: 'bi-archive-fill', color: 'red', title: 'Dead Stock (Modal Mengendap)', val: fmtRp(s.dead_stock_value), sub: `${s.dead_stock_sku_count} SKU tanpa penjualan` },
            { icon: 'bi-exclamation-circle-fill', color: 'orange', title: 'SKU Kritis & Habis Stok', val: fmt(s.critical_sku_count) + ' SKU', sub: 'Risiko lost sales segera' },
            { icon: 'bi-cart-plus-fill', color: 'amber', title: `Modal Reorder (${meta.buffer_days} Hari)`, val: fmtRp(s.reorder_capital_needed), sub: 'Estimasi kebutuhan restock optimal' },
            { icon: 'bi-boxes', color: 'purple', title: 'SKU Overstocked', val: fmt(s.overstocked_sku_count) + ' SKU', sub: 'Stok > 45 hari penjualan' }
        ];
        const colorMap = {
            blue: 'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800',
            emerald: 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-100 dark:border-emerald-800',
            red: 'bg-red-50 dark:bg-red-900/20 border-red-100 dark:border-red-800',
            orange: 'bg-orange-50 dark:bg-orange-900/20 border-orange-100 dark:border-orange-800',
            amber: 'bg-amber-50 dark:bg-amber-900/20 border-amber-100 dark:border-amber-800',
            purple: 'bg-purple-50 dark:bg-purple-900/20 border-purple-100 dark:border-purple-800'
        };
        const iconColorMap = {
            blue: 'text-blue-600', emerald: 'text-emerald-600', red: 'text-red-600',
            orange: 'text-orange-600', amber: 'text-amber-600', purple: 'text-purple-600'
        };
        summaryContainer.innerHTML = `<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">` +
            cards.map(c => `
                <div class="rounded-xl border p-4 ${colorMap[c.color]}">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="bi ${c.icon} text-lg ${iconColorMap[c.color]}"></i>
                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">${c.title}</span>
                    </div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white leading-tight">${c.val}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">${c.sub}</div>
                </div>`).join('') + `</div>`;
    }

    // --- Charts ---
    function renderCharts(charts) {
        // Donut: Capital composition
        const donutCanvas = document.getElementById('chart-stok-capital-donut');
        if (donutCanvas && charts.capital_composition) {
            if (donutChart) donutChart.destroy();
            donutChart = new Chart(donutCanvas, {
                type: 'doughnut',
                data: {
                    labels: charts.capital_composition.labels,
                    datasets: [{
                        data: charts.capital_composition.values,
                        backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#9ca3af'],
                        borderWidth: 2,
                        borderColor: document.documentElement.classList.contains('dark') ? '#1f2937' : '#fff'
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 }, color: document.documentElement.classList.contains('dark') ? '#d1d5db' : '#4b5563' } },
                        tooltip: { callbacks: { label: (ctx) => ' ' + ctx.label + ': ' + fmtRp(ctx.parsed) } }
                    }
                }
            });
        }

        // Bar: Urgent reorder
        const barCanvas = document.getElementById('chart-stok-urgent-bar');
        if (barCanvas && charts.top_urgent_reorder && charts.top_urgent_reorder.labels.length > 0) {
            if (barChart) barChart.destroy();
            barChart = new Chart(barCanvas, {
                type: 'bar',
                data: {
                    labels: charts.top_urgent_reorder.labels,
                    datasets: [
                        { label: 'Stok Saat Ini', data: charts.top_urgent_reorder.stok, backgroundColor: '#10b981', borderRadius: 4 },
                        { label: 'Target Stok', data: charts.top_urgent_reorder.target, backgroundColor: 'rgba(59,130,246,0.25)', borderColor: '#3b82f6', borderWidth: 1, borderRadius: 4 },
                        { label: 'Qty Reorder', data: charts.top_urgent_reorder.reorder, backgroundColor: '#f59e0b', borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 }, color: document.documentElement.classList.contains('dark') ? '#d1d5db' : '#4b5563' } } },
                    scales: {
                        x: { beginAtZero: true, ticks: { font: { size: 10 }, color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280' }, grid: { color: document.documentElement.classList.contains('dark') ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' } },
                        y: { ticks: { font: { size: 9 }, color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280' } }
                    }
                }
            });
        } else if (barCanvas && charts.top_urgent_reorder && charts.top_urgent_reorder.labels.length === 0) {
            barCanvas.parentElement.innerHTML = '<div class="flex items-center justify-center h-full text-sm text-gray-400 dark:text-gray-500"><i class="bi bi-check-circle-fill text-emerald-400 mr-2"></i> Tidak ada SKU mendesak reorder</div>';
        }
    }

    // --- ABC Badge ---
    function abcBadge(cls) {
        const map = {
            'A': ['abc-badge-a', 'Fast Moving (A)'],
            'B': ['abc-badge-b', 'Slow Moving (B)'],
            'C': ['abc-badge-c', 'Non-Moving (C)'],
            'Dead': ['abc-badge-dead', 'Dead Stock']
        };
        const [cssClass, label] = map[cls] || ['abc-badge-dead', cls];
        return `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold ${cssClass}">${label}</span>`;
    }

    // --- Run-Out Cell ---
    function runOutCell(days, statusBadge, statusLabel) {
        if (days === null || days === undefined) {
            return `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs border ${statusBadge}">${statusLabel}</span>`;
        }
        if (days == 0) {
            return `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs border bg-red-100 text-red-800 border-red-200 dark:bg-red-900/40 dark:text-red-300"><i class="bi bi-x-circle-fill mr-1"></i>Habis</span>`;
        }
        const daysNum = parseFloat(days);
        let color = daysNum <= 7 ? 'text-red-600 dark:text-red-400' : daysNum <= 14 ? 'text-amber-600 dark:text-amber-400' : daysNum <= 45 ? 'text-emerald-600 dark:text-emerald-400' : 'text-purple-600 dark:text-purple-400';
        return `<span class="font-semibold ${color}">${fmt(Math.round(daysNum))} hr</span><br><span class="text-xs text-gray-400">${statusLabel}</span>`;
    }

    // --- Render Table ---
    function renderTable(data, meta) {
        if (tableInfo) tableInfo.textContent = `Menampilkan ${data.length} dari ${meta.total_items_analyzed} SKU`;

        if (!data || data.length === 0) {
            tableContainer.innerHTML = '<div class="bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-200 p-6 rounded-lg text-center"><i class="bi bi-search text-lg mr-2"></i> Tidak ada data untuk filter ini.</div>';
            return;
        }

        let html = `<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-3 py-2.5 text-left font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">#</th>
                    <th class="px-3 py-2.5 text-left font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Nama Barang / SKU</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Kelas ABC</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Stok</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Terjual</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">ADS (Avg/Hr)</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Sisa / Status</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Neto Penjualan</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Margin</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Reorder Qty</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Est. Modal</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">`;

        data.forEach((row, idx) => {
            const marginPct = row.total_neto > 0 ? ((row.total_margin / row.total_neto) * 100).toFixed(1) : '0.0';
            const marginColor = parseFloat(marginPct) >= 15 ? 'text-emerald-600 dark:text-emerald-400' : parseFloat(marginPct) >= 5 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400';
            const rowBg = idx % 2 === 0 ? '' : 'bg-gray-50 dark:bg-gray-750';
            html += `<tr class="${rowBg} hover:bg-primary-50/50 dark:hover:bg-primary-900/10 transition">
                <td class="px-3 py-2.5 text-gray-400">${idx + 1}</td>
                <td class="px-3 py-2.5">
                    <div class="font-medium text-gray-900 dark:text-white">${row.nama_barang || '-'}</div>
                    <div class="text-gray-400 text-xs">${row.sku || ''} ${row.barcode ? '| ' + row.barcode : ''}</div>
                    <div class="text-gray-400 text-xs">${row.nama_kategori || ''}</div>
                </td>
                <td class="px-3 py-2.5 text-center">${abcBadge(row.abc_class)}</td>
                <td class="px-3 py-2.5 text-right font-semibold text-gray-800 dark:text-gray-200">${fmt(row.stok)}</td>
                <td class="px-3 py-2.5 text-right text-gray-700 dark:text-gray-300">${fmt(row.qty_sold)}</td>
                <td class="px-3 py-2.5 text-right text-gray-600 dark:text-gray-400">${parseFloat(row.ads || 0).toFixed(2)}</td>
                <td class="px-3 py-2.5 text-center leading-tight">${runOutCell(row.run_out_days, row.stock_status_badge, row.stock_status_label)}</td>
                <td class="px-3 py-2.5 text-right text-gray-800 dark:text-gray-200">${fmtRp(row.total_neto)}</td>
                <td class="px-3 py-2.5 text-right">
                    <span class="font-semibold ${marginColor}">${fmtRp(row.total_margin)}</span>
                    <br><span class="text-xs text-gray-400">${marginPct}%</span>
                </td>
                <td class="px-3 py-2.5 text-right font-semibold ${row.suggested_reorder_qty > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400'}">${fmt(row.suggested_reorder_qty)}</td>
                <td class="px-3 py-2.5 text-right ${row.estimasi_modal_reorder > 0 ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400'}">${row.estimasi_modal_reorder > 0 ? fmtRp(row.estimasi_modal_reorder) : '-'}</td>
            </tr>`;
        });

        html += '</tbody></table>';
        tableContainer.innerHTML = html;
    }

    // --- Export ---
    function buildExportUrl(type) {
        const p = new URLSearchParams({ ...lastApiParams, report: 'analisis-stok-reorder', format: type });
        if (type === 'pdf') return `${basePath}/api/pdf?` + p.toString();
        return `${basePath}/api/csv?` + p.toString();
    }

    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', () => {
            if (!Object.keys(lastApiParams).length) { alert('Tampilkan laporan terlebih dahulu.'); return; }
            window.open(`${basePath}/api/csv?` + new URLSearchParams({ ...lastApiParams, report: 'analisis-stok-reorder' }).toString(), '_blank');
        });
    }
    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', () => {
            if (!Object.keys(lastApiParams).length) { alert('Tampilkan laporan terlebih dahulu.'); return; }
            // Use POST with CSRF token to generate PDF in a new tab
            printPdf({ ...lastApiParams, report: 'analisis-stok-reorder', orientation: 'landscape' });
        });
    }

    // --- Auto-load on init ---
    fetchData();
}
