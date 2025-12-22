function initLaporanPertumbuhanLabaPage() {
    const yearFilter = document.getElementById('lpl-tahun-filter');
    const tampilkanBtn = document.getElementById('lpl-tampilkan-btn');
    const chartCanvas = document.getElementById('lpl-chart');
    const tableBody = document.getElementById('lpl-report-table-body');
    const compareSwitch = document.getElementById('lpl-compare-switch');
    const viewModeGroup = document.getElementById('lpl-view-mode');
    const exportPdfBtn = document.getElementById('export-lpl-pdf');
    const exportCsvBtn = document.getElementById('export-lpl-csv');

    if (!yearFilter) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });
    const quarters = ["Triwulan 1 (Jan-Mar)", "Triwulan 2 (Apr-Jun)", "Triwulan 3 (Jul-Sep)", "Triwulan 4 (Okt-Des)"];
    const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

    function setupFilters() {
        const currentYear = new Date().getFullYear();
        for (let i = 0; i < 5; i++) {
            yearFilter.add(new Option(currentYear - i, currentYear - i));
        }
        yearFilter.value = currentYear;
    }

    async function loadReport() {
        const selectedYear = yearFilter.value;
        const viewMode = document.querySelector('input[name="view_mode"]:checked').value;
        const isComparing = compareSwitch.checked;
        
        const originalBtnHtml = tampilkanBtn.innerHTML;
        tampilkanBtn.disabled = true;
        tampilkanBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memuat...`;
        tableBody.innerHTML = `<tr><td colspan="5" class="text-center p-10"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>`;

        try {
            const params = new URLSearchParams({
                view_mode: viewMode,
                tahun: selectedYear,
                compare: isComparing
            });
            const response = await fetch(`${basePath}/api/laporan-pertumbuhan-laba?${params.toString()}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const data = result.data;

            // Render Table
            tableBody.innerHTML = '';
            const tableHeader = document.getElementById('lpl-report-table-header');
            const isCumulative = viewMode === 'cumulative';
            const isYearly = viewMode === 'yearly';
            const periodLabel = isCumulative ? 'Bulan (YTD)' : (isYearly ? 'Tahun' : (viewMode === 'monthly' ? 'Bulan' : 'Triwulan'));
            const growthLabel = isYearly ? 'YoY' : (isCumulative ? 'MoM' : (viewMode === 'monthly' ? 'MoM' : 'QoQ'));


            if (isComparing) {
                tableHeader.innerHTML = `<tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">${periodLabel}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Laba Bersih (${selectedYear})</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Laba Bersih (${selectedYear - 1})</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pertumbuhan ${growthLabel}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pertumbuhan YoY</th>
                </tr>`;
            } else {
                tableHeader.innerHTML = `<tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">${periodLabel}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Pendapatan</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Beban</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Laba (Rugi) Bersih</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pertumbuhan ${growthLabel}</th>
                </tr>`;
            }

            if (data.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-gray-500">Tidak ada data untuk periode ini.</td></tr>`;
            }

            data.forEach(row => {
                let growthHtml;
                if (row.pertumbuhan > 0) {
                    growthHtml = `<span class="text-green-600 dark:text-green-400"><i class="bi bi-arrow-up"></i> ${row.pertumbuhan.toFixed(2)}%</span>`;
                } else if (row.pertumbuhan < 0) {
                    growthHtml = `<span class="text-red-600 dark:text-red-400"><i class="bi bi-arrow-down"></i> ${Math.abs(row.pertumbuhan).toFixed(2)}%</span>`;
                } else {
                    growthHtml = `<span>-</span>`;
                }

                let tableRow;
                let periodName;
                if (viewMode === 'quarterly') {
                    periodName = quarters[row.triwulan - 1];
                } else if (viewMode === 'yearly') {
                    periodName = row.tahun;
                } else { // monthly or cumulative
                    periodName = months[row.bulan - 1];
                }
                if (isComparing) {
                    let yoyGrowthHtml;
                    if (row.pertumbuhan_yoy > 0) {
                        yoyGrowthHtml = `<span class="text-green-600 dark:text-green-400"><i class="bi bi-arrow-up"></i> ${row.pertumbuhan_yoy.toFixed(2)}%</span>`;
                    } else if (row.pertumbuhan_yoy < 0) {
                        yoyGrowthHtml = `<span class="text-red-600 dark:text-red-400"><i class="bi bi-arrow-down"></i> ${Math.abs(row.pertumbuhan_yoy).toFixed(2)}%</span>`;
                    } else {
                        yoyGrowthHtml = `<span>-</span>`;
                    }
                    tableRow = `
                        <tr class="text-sm">
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">${periodName}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right font-bold ${row.laba_bersih < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'}">${currencyFormatter.format(row.laba_bersih)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-gray-500 dark:text-gray-400">${currencyFormatter.format(row.laba_bersih_lalu)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">${growthHtml}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">${yoyGrowthHtml}</td>
                        </tr>
                    `;
                } else {
                    tableRow = `
                        <tr class="text-sm">
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">${periodName}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-gray-900 dark:text-white">${currencyFormatter.format(row.total_pendapatan)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-gray-900 dark:text-white">${currencyFormatter.format(row.total_beban)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right font-bold ${row.laba_bersih < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'}">${currencyFormatter.format(row.laba_bersih)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">${growthHtml}</td>
                        </tr>
                    `;
                }
                tableBody.insertAdjacentHTML('beforeend', tableRow);
            });

            // Render Chart
            if (window.lplProfitChart) {
                window.lplProfitChart.destroy();
            }

            let chartLabels;
            if (viewMode === 'quarterly') {
                chartLabels = ["Q1", "Q2", "Q3", "Q4"];
            } else if (viewMode === 'yearly') {
                chartLabels = data.map(d => d.tahun);
            } else { // monthly or cumulative
                chartLabels = months.map(m => m.substring(0, 3));
            }

            const isCumulativeView = viewMode === 'cumulative';

            const chartConfig = {
                type: isCumulativeView ? 'line' : 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: `Laba Bersih ${selectedYear}`,
                        data: data.map(d => d.laba_bersih),
                        // Atur warna berdasarkan tipe chart
                        backgroundColor: isCumulativeView 
                            ? 'rgba(0, 122, 255, 0.1)' 
                            : data.map(d => d.laba_bersih >= 0 ? 'rgba(25, 135, 84, 0.6)' : 'rgba(220, 53, 69, 0.6)'),
                        borderColor: isCumulativeView 
                            ? 'rgba(0, 122, 255, 1)' 
                            : data.map(d => d.laba_bersih >= 0 ? 'rgba(25, 135, 84, 1)' : 'rgba(220, 53, 69, 1)'),
                        borderWidth: isCumulativeView ? 2 : 1,
                        fill: isCumulativeView, // Aktifkan fill hanya untuk line chart
                        tension: 0.3 // Buat garis lebih halus
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += currencyFormatter.format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: value => currencyFormatter.format(value) }
                        }
                    }
                }
            };

            if (isComparing) {
                chartConfig.data.datasets.push({
                    label: `Laba Bersih ${selectedYear - 1}`,
                    data: data.map(d => d.laba_bersih_lalu),
                    backgroundColor: 'rgba(108, 117, 125, 0.5)',
                    borderColor: 'rgba(108, 117, 125, 1)',
                    borderWidth: 1,
                    type: 'line', // Tampilkan sebagai garis untuk perbandingan
                    tension: 0.1
                });
            }
            window.lplProfitChart = new Chart(chartCanvas, chartConfig);

        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-red-500 dark:text-red-400 py-10">Gagal memuat laporan: ${error.message}</td></tr>`;
        } finally {
            tampilkanBtn.disabled = false;
            tampilkanBtn.innerHTML = `<i class="bi bi-search mr-2"></i> Tampilkan`;
        }
    }

    // Cukup tambahkan listener secara langsung. SPA akan menghapusnya saat navigasi.
    tampilkanBtn.addEventListener('click', loadReport);
    viewModeGroup.addEventListener('change', loadReport);
    compareSwitch.addEventListener('change', loadReport);

    exportPdfBtn.addEventListener('click', (e) => {
        e.preventDefault();

        // 1. Ambil gambar chart sebagai base64
        const chartImage = window.lplProfitChart ? window.lplProfitChart.toBase64Image() : '';

        // 2. Buat form sementara untuk mengirim data via POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank'; // Buka di tab baru

        const params = {
            report: 'laporan-pertumbuhan-laba',
            tahun: yearFilter.value,
            view_mode: document.querySelector('input[name="view_mode"]:checked').value,
            compare: compareSwitch.checked,
            chart_image: chartImage // Kirim data gambar
        };

        for (const key in params) {
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = key;
            hiddenField.value = params[key];
            form.appendChild(hiddenField);
        }

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    exportCsvBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const params = new URLSearchParams({
            report: 'laporan-pertumbuhan-laba',
            format: 'csv',
            tahun: yearFilter.value,
            view_mode: document.querySelector('input[name="view_mode"]:checked').value,
            compare: compareSwitch.checked
        });
        window.open(`${basePath}/api/csv?${params.toString()}`, '_blank');
    });

    setupFilters();
    loadReport(); // Initial load
}
