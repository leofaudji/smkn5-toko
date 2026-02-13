function initStatistikKspPage() {
    const statAset = document.getElementById('stat-aset');
    const startDateInput = document.getElementById('filter-start-date');
    const endDateInput = document.getElementById('filter-end-date');
    const filterBtn = document.getElementById('btn-filter-stats');
    const compareSelect = document.getElementById('filter-compare');
    
    if (!statAset) return;

    // Set default dates (6 months range)
    const now = new Date();
    const endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0); // End of current month
    const startDate = new Date(now.getFullYear(), now.getMonth() - 5, 1); // 5 months ago
    
    if (startDateInput && endDateInput) {
        // Use flatpickr if available for better UX
        if (typeof flatpickr !== 'undefined') {
            flatpickr(startDateInput, { dateFormat: "Y-m-d", defaultDate: startDate, allowInput: true });
            flatpickr(endDateInput, { dateFormat: "Y-m-d", defaultDate: endDate, allowInput: true });
        } else {
            startDateInput.valueAsDate = startDate;
            endDateInput.valueAsDate = endDate;
        }
    }

    if (filterBtn) {
        filterBtn.addEventListener('click', loadStats);
    }

    loadStats();

    async function loadStats() {
        try {
            const params = new URLSearchParams({ 
                start_date: startDateInput.value, 
                end_date: endDateInput.value,
                compare_type: compareSelect ? compareSelect.value : 'mom'
            });
            const response = await fetch(`${basePath}/api/ksp/statistik?${params.toString()}`);
            const result = await response.json();

            if (result.success) {
                updateSummary(result.data.summary);
                renderGrowthChart(result.data.growth);
                renderQualityChart(result.data.quality);
                renderSavingsCompChart(result.data.savings_comp);
                renderTopSavers(result.data.top_savers, result.data.summary.total_simpanan);
                renderLoanPortfolioChart(result.data.loan_portfolio);
                renderMemberGrowthChart(result.data.member_growth);
                renderIncomeTrendChart(result.data.income_trend);
                renderTopBorrowers(result.data.top_borrowers);
                renderForecastChart(result.data.forecast);
            } else {
                showToast(result.message || 'Gagal memuat data statistik', 'error');
            }
        } catch (error) {
            console.error('Gagal memuat statistik:', error);
            showToast('Gagal memuat data statistik', 'error');
        }
    }

    function updateSummary(data) {
        document.getElementById('stat-aset').textContent = formatRupiah(data.total_outstanding);
        document.getElementById('stat-dana').textContent = formatRupiah(data.total_simpanan);
        
        // LDR = (Total Pinjaman / Total Simpanan) * 100
        const ldr = data.total_simpanan > 0 ? (data.total_outstanding / data.total_simpanan) * 100 : 0;
        document.getElementById('stat-ldr').textContent = ldr.toFixed(2) + '%';
        
        // NPL = (Total Macet / Total Pinjaman) * 100
        const npl = data.total_outstanding > 0 ? (data.total_macet / data.total_outstanding) * 100 : 0;
        const nplEl = document.getElementById('stat-npl');
        nplEl.textContent = npl.toFixed(2) + '%';
        
        if (npl > 5) nplEl.classList.add('text-red-600');
        else nplEl.classList.add('text-green-600');

        // Update Badges (Comparison)
        updateBadge('badge-aset', data.growth_outstanding);
        updateBadge('badge-dana', data.growth_simpanan);
        
        // LDR Growth (Point difference)
        const prevLdr = data.prev_total_simpanan > 0 ? (data.prev_total_outstanding / data.prev_total_simpanan) * 100 : 0;
        const ldrGrowth = ldr - prevLdr; 
        updateBadge('badge-ldr', ldrGrowth, true);

        // NPL Growth (Point difference)
        const prevNpl = data.prev_total_outstanding > 0 ? (data.prev_total_macet / data.prev_total_outstanding) * 100 : 0;
        const nplGrowth = npl - prevNpl; 
        updateBadge('badge-npl', nplGrowth, true, true); // Inverse logic for NPL (lower is better)
    }

    function updateBadge(elementId, value, isPoint = false, inverse = false) {
        const el = document.getElementById(elementId);
        if (!el) return;

        if (value === 0 || value === null || value === undefined) {
            el.classList.add('hidden');
            return;
        }

        const isPositive = value > 0;
        // Logic: Usually positive growth is good (green), negative is bad (red).
        // Inverse: Positive growth (e.g. NPL) is bad (red), negative is good (green).
        const isGood = inverse ? !isPositive : isPositive;
        
        // Professional look: colored text/bg
        const colorClass = isGood 
            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' 
            : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
            
        const icon = isPositive ? 'bi-arrow-up' : 'bi-arrow-down';
        const suffix = isPoint ? '' : '%';
        const displayValue = Math.abs(value).toFixed(2);

        el.className = `inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${colorClass}`;
        el.innerHTML = `<i class="bi ${icon} mr-1"></i> ${displayValue}${suffix}`;
        el.classList.remove('hidden');
    }

    function renderGrowthChart(data) {
        const ctx = document.getElementById('growthChart').getContext('2d');
        
        if (window.kspGrowthChart) {
            window.kspGrowthChart.destroy();
        }
        
        window.kspGrowthChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Simpanan',
                        data: data.simpanan,
                        borderColor: '#10b981', // green-500
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Pinjaman',
                        data: data.pinjaman,
                        borderColor: '#3b82f6', // blue-500
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return (value / 1000000).toFixed(0) + 'Jt';
                            }
                        }
                    }
                }
            }
        });
    }

    function renderQualityChart(data) {
        const ctx = document.getElementById('qualityChart').getContext('2d');
        
        // Data: Lancar, DPK, Kurang Lancar, Diragukan, Macet
        const labels = ['Lancar', 'Dlm Perhatian', 'Kurang Lancar', 'Diragukan', 'Macet'];
        const colors = ['#10b981', '#3b82f6', '#f59e0b', '#f97316', '#ef4444'];
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: [
                        data.lancar, 
                        data.dpk, 
                        data.kurang_lancar, 
                        data.diragukan, 
                        data.macet
                    ],
                    backgroundColor: colors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }

    function renderSavingsCompChart(data) {
        const ctx = document.getElementById('savingsCompChart').getContext('2d');

        if (window.kspSavingsChart) {
            window.kspSavingsChart.destroy();
        }
        
        const labels = data.map(item => item.nama);
        const values = data.map(item => item.total_saldo);
        // Warna pastel yang berbeda
        const colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#10b981'];

        window.kspSavingsChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxWidth: 12, font: { size: 11 } }
                    }
                }
            }
        });
    }

    function renderTopSavers(data, totalSimpanan) {
        const tbody = document.getElementById('top-savers-body');
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="px-4 py-3 text-center text-sm text-gray-500">Belum ada data.</td></tr>';
            return;
        }

        tbody.innerHTML = data.map(item => {
            const percentage = totalSimpanan > 0 ? (item.total_saldo / totalSimpanan) * 100 : 0;
            return `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        ${item.nama_lengkap} <span class="text-xs text-gray-500 font-normal">(${item.nomor_anggota})</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700 dark:text-gray-300 font-bold">${formatRupiah(item.total_saldo)}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">${percentage.toFixed(1)}%</td>
                </tr>
            `;
        }).join('');
    }

    function renderForecastChart(data) {
        const ctx = document.getElementById('cashflowForecastChart').getContext('2d');
        
        if (window.kspForecastChart) {
            window.kspForecastChart.destroy();
        }

        const labels = [...data.historical_labels, ...data.forecast_labels];
        // Pad historical data with nulls for forecast part
        const histData = [...data.historical_data, ...Array(data.forecast_labels.length).fill(null)];
        // Pad forecast data with nulls for historical part (connect the last historical point)
        const lastHistVal = data.historical_data[data.historical_data.length - 1];
        const forecastData = Array(data.historical_labels.length - 1).fill(null);
        forecastData.push(lastHistVal); // Connect lines
        forecastData.push(...data.forecast_data);

        window.kspForecastChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Arus Kas Historis',
                        data: histData,
                        borderColor: '#6b7280', // gray-500
                        backgroundColor: 'rgba(107, 114, 128, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Prediksi',
                        data: forecastData,
                        borderColor: '#8b5cf6', // violet-500
                        borderDash: [5, 5],
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        tension: 0.3,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: false, ticks: { callback: (val) => (val / 1000000).toFixed(1) + 'Jt' } } }
            }
        });
    }

    function renderLoanPortfolioChart(data) {
        const ctx = document.getElementById('loanPortfolioChart').getContext('2d');
        if (window.kspLoanPortfolioChart) window.kspLoanPortfolioChart.destroy();

        const labels = data.map(item => item.nama);
        const values = data.map(item => item.total_outstanding);
        const colors = ['#f97316', '#f59e0b', '#eab308', '#84cc16', '#22c55e']; // Orange to Green spectrum

        window.kspLoanPortfolioChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                }
            }
        });
    }

    function renderMemberGrowthChart(data) {
        const ctx = document.getElementById('memberGrowthChart').getContext('2d');
        if (window.kspMemberGrowthChart) window.kspMemberGrowthChart.destroy();

        const labels = data.map(item => item.bulan);
        const values = data.map(item => item.total);

        window.kspMemberGrowthChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Anggota Baru',
                    data: values,
                    backgroundColor: 'rgba(6, 182, 212, 0.6)', // Cyan-500
                    borderColor: 'rgba(6, 182, 212, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    function renderIncomeTrendChart(data) {
        const ctx = document.getElementById('incomeTrendChart').getContext('2d');
        if (window.kspIncomeChart) window.kspIncomeChart.destroy();

        window.kspIncomeChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.bulan),
                datasets: [
                    {
                        label: 'Bunga',
                        data: data.map(d => d.bunga),
                        backgroundColor: '#14b8a6', // teal-500
                        stack: 'Stack 0',
                    },
                    {
                        label: 'Denda',
                        data: data.map(d => d.denda),
                        backgroundColor: '#f43f5e', // rose-500
                        stack: 'Stack 0',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + formatRupiah(c.raw) } }
                },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, ticks: { callback: (val) => (val / 1000).toFixed(0) + 'k' } }
                }
            }
        });
    }

    function renderTopBorrowers(data) {
        const tbody = document.getElementById('top-borrowers-body');
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="px-4 py-3 text-center text-sm text-gray-500">Belum ada data.</td></tr>';
            return;
        }
        tbody.innerHTML = data.map(item => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                    ${item.nama_lengkap} <div class="text-xs text-gray-500 font-normal">(${item.nomor_anggota})</div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700 dark:text-gray-300 font-bold">${formatRupiah(item.total_outstanding)}</td>
            </tr>
        `).join('');
    }
}