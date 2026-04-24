let profitChart = null;

window.initLaporanPertumbuhanLabaSalesPage = async function() {
    // Initial load
    loadData();

    // Event Listeners
    const filterBtn = document.getElementById('filter-btn');
    const resetBtn = document.getElementById('reset-btn');

    if (filterBtn) filterBtn.addEventListener('click', loadData);
    if (resetBtn) resetBtn.addEventListener('click', () => {
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        document.getElementById('start_date').value = thirtyDaysAgo.toISOString().split('T')[0];
        document.getElementById('end_date').value = new Date().toISOString().split('T')[0];
        loadData();
    });
};

async function loadData() {
    const startDateEl = document.getElementById('start_date');
    const endDateEl = document.getElementById('end_date');
    const contentBody = document.getElementById('reportContent');

    if (!startDateEl || !endDateEl || !contentBody) return;

    const start_date = startDateEl.value;
    const end_date = endDateEl.value;

    contentBody.innerHTML = `<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary me-2"></div>Memuat data...</td></tr>`;

    try {
        const params = new URLSearchParams({ start_date, end_date });
        const [response, settingsRes] = await Promise.all([
            fetch(`${basePath}/api/laporan-pertumbuhan-laba-sales?${params.toString()}`),
            fetch(`${basePath}/api/settings`)
        ]);
        
        const result = await response.json();
        const settingsResult = await settingsRes.json();
        const monthlyFixedCost = settingsResult.status === 'success' ? parseFloat(settingsResult.data.monthly_fixed_cost || 0) : 0;

        // Update Break-Even Info Badge
        const breakEvenInfo = document.getElementById('break-even-info');
        const targetValueEl = document.getElementById('target-value');
        if (breakEvenInfo && targetValueEl) {
            if (monthlyFixedCost > 0) {
                breakEvenInfo.classList.remove('hidden');
                targetValueEl.textContent = formatRupiah(monthlyFixedCost) + ' /bln';
            } else {
                breakEvenInfo.classList.add('hidden');
            }
        }

        if (result.status === 'success') {
            renderTable(result.data);
            renderChart(result.data, monthlyFixedCost);
        } else {
            contentBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${result.message}</td></tr>`;
        }
    } catch (error) {
        console.error('Error:', error);
        contentBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Gagal memuat data. Silakan coba lagi.</td></tr>`;
    }
}

function renderTable(data) {
    const contentBody = document.getElementById('reportContent');
    if (!contentBody) return;
    
    if (data.length === 0) {
        contentBody.innerHTML = `<tr><td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400 italic">Tidak ada data untuk periode ini.</td></tr>`;
        return;
    }

    let html = '';
    let totalSales = 0;
    let totalHpp = 0;
    let totalProfit = 0;

    data.forEach((row, index) => {
        let growthBadge = '';
        if (row.pertumbuhan > 0) {
            growthBadge = `<span class="inline-flex items-center bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900/30 dark:text-green-300">
                <i class="bi bi-arrow-up-right mr-1"></i>${row.pertumbuhan.toFixed(2)}%
            </span>`;
        } else if (row.pertumbuhan < 0) {
            growthBadge = `<span class="inline-flex items-center bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-red-900/30 dark:text-red-300">
                <i class="bi bi-arrow-down-right mr-1"></i>${row.pertumbuhan.toFixed(2)}%
            </span>`;
        } else {
            growthBadge = `<span class="inline-flex items-center bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-gray-300">
                <i class="bi bi-dash mr-1"></i>0.00%
            </span>`;
        }
        
        html += `
            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-colors duration-150">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                    ${new Date(row.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-700 dark:text-gray-300">
                    ${formatRupiah(row.total_penjualan)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-400 dark:text-gray-500">
                    ${formatRupiah(row.total_hpp)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-primary dark:text-primary-light">
                    ${formatRupiah(row.profit)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-gray-600 dark:text-gray-300">
                    ${row.margin_pct.toFixed(1)}%
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    ${growthBadge}
                </td>
            </tr>
        `;

        totalSales += row.total_penjualan;
        totalHpp += row.total_hpp;
        totalProfit += row.profit;
    });

    contentBody.innerHTML = html;

    // Update footer
    const footerSales = document.getElementById('footer-sales');
    const footerHpp = document.getElementById('footer-hpp');
    const footerProfit = document.getElementById('footer-profit');
    const footerMargin = document.getElementById('footer-margin');
    
    if (footerSales) footerSales.textContent = formatRupiah(totalSales);
    if (footerHpp) footerHpp.textContent = formatRupiah(totalHpp);
    if (footerProfit) footerProfit.textContent = formatRupiah(totalProfit);
    if (footerMargin) {
        const totalMargin = totalSales > 0 ? (totalProfit / totalSales * 100) : 0;
        footerMargin.textContent = totalMargin.toFixed(1) + '%';
    }
}

function renderChart(data, monthlyFixedCost = 0) {
    const chartEl = document.getElementById('profitChart');
    if (!chartEl) return;
    
    const ctx = chartEl.getContext('2d');
    
    if (profitChart) {
        profitChart.destroy();
    }

    const labels = data.map(row => new Date(row.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }));
    const profitData = data.map(row => row.profit);
    const salesData = data.map(row => row.total_penjualan);
    
    // Calculate daily break-even threshold (assuming 30 days)
    const dailyBreakEven = monthlyFixedCost / 30;
    const breakEvenData = data.map(() => dailyBreakEven);

    profitChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Laba Penjualan',
                    data: profitData,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointBackgroundColor: '#0d6efd',
                    pointRadius: 4
                },
                {
                    label: 'Total Omzet',
                    data: salesData,
                    borderColor: '#adb5bd',
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 0
                },
                {
                    label: 'Target Break-Even Harian',
                    data: breakEvenData,
                    borderColor: '#ffc107',
                    borderDash: [2, 2],
                    fill: false,
                    pointRadius: 0,
                    borderWidth: 2,
                    hidden: dailyBreakEven <= 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += formatRupiah(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value >= 1000000 ? (value / 1000000) + 'jt' : (value >= 1000 ? (value / 1000) + 'rb' : value);
                        }
                    }
                }
            }
        }
    });
}
