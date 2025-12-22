function initAnggaranPage() {
    const yearFilter = document.getElementById('anggaran-tahun-filter');
    const monthFilter = document.getElementById('anggaran-bulan-filter');
    const tampilkanBtn = document.getElementById('anggaran-tampilkan-btn');
    const reportTableBody = document.getElementById('anggaran-report-table-body');
    const budgetChartCanvas = document.getElementById('anggaran-chart');
    const modalEl = document.getElementById('anggaranModal');
    const modalTahunLabel = document.getElementById('modal-tahun-label');
    const managementContainer = document.getElementById('anggaran-management-container');
    const saveAnggaranBtn = document.getElementById('save-anggaran-btn');
    const exportPdfBtn = document.getElementById('export-anggaran-pdf');
    const exportCsvBtn = document.getElementById('export-anggaran-csv');
    const compareSwitch = document.getElementById('anggaran-compare-switch');
    const trendChartCanvas = document.getElementById('anggaran-trend-chart');
    const manageAnggaranBtn = document.getElementById('manage-anggaran-btn');
    
    if (!yearFilter || !reportTableBody) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    function setupFilters() {
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;

        for (let i = 0; i < 5; i++) {
            yearFilter.add(new Option(currentYear - i, currentYear - i));
        }
        const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
        months.forEach((month, index) => {
            monthFilter.add(new Option(month, index + 1));
        });

        yearFilter.value = currentYear;
        monthFilter.value = currentMonth;
    }

    async function loadTrendChart() {
        const selectedYear = yearFilter.value;
        if (!trendChartCanvas) return;

        try {
            const response = await fetch(`${basePath}/api/anggaran?action=get_trend_data&tahun=${selectedYear}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            if (window.anggaranTrendChart) {
                window.anggaranTrendChart.destroy();
            }

            const labels = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
            window.anggaranTrendChart = new Chart(trendChartCanvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Anggaran Bulanan',
                            data: result.data.anggaran_bulanan,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            fill: false,
                            tension: 0.1
                        },
                        {
                            label: 'Realisasi Bulanan',
                            data: result.data.realisasi_bulanan,
                            borderColor: 'rgba(255, 99, 132, 1)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            fill: true,
                            tension: 0.1
                        }
                    ]
                },
                options: { responsive: true, scales: { y: { beginAtZero: true } } }
            });
        } catch (error) {
            console.error("Gagal memuat data tren:", error);
        }
    }

    async function loadReport() {
        const selectedYear = yearFilter.value;
        const selectedMonth = monthFilter.value;
        const isComparing = compareSwitch.checked;

        reportTableBody.innerHTML = '<tr><td colspan="5" class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>';
        try {
            const params = new URLSearchParams({
                action: 'get_report',
                tahun: selectedYear,
                bulan: selectedMonth,
                compare: isComparing
            });
            const response = await fetch(`${basePath}/api/anggaran?${params.toString()}`);
            const result = await response.json();

            // Update Summary Cards
            if (result.status === 'success' && result.summary) {
                document.getElementById('summary-total-anggaran').textContent = currencyFormatter.format(result.summary.total_anggaran || 0);
                document.getElementById('summary-total-realisasi').textContent = currencyFormatter.format(result.summary.total_realisasi || 0);
                document.getElementById('summary-sisa-anggaran').textContent = currencyFormatter.format(result.summary.total_sisa || 0);
            }

            // Update Table Header
            const tableHeader = document.getElementById('anggaran-report-table-header');
            if (isComparing) {
                tableHeader.innerHTML = `
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Akun Beban</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Anggaran (${selectedYear})</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Realisasi (${selectedYear})</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Realisasi (${selectedYear - 1})</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/4">Penggunaan</th>
                `;
            } else {
                tableHeader.innerHTML = `
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Akun Beban</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Anggaran Bulanan</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Realisasi Belanja</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sisa Anggaran</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/4">Penggunaan</th>
                `;
            }

            reportTableBody.innerHTML = '';

            // Update Chart
            if (window.anggaranBudgetChart) {
                window.anggaranBudgetChart.destroy();
            }
            if (result.status === 'success' && result.data.length > 0) {
                const labels = result.data.map(item => item.nama_akun);
                const budgetData = result.data.map(item => item.anggaran_bulanan);
                const realizationData = result.data.map(item => item.realisasi_belanja);                
                const realizationPrevYearData = result.data.map(item => item.realisasi_belanja_lalu);

                const chartConfig = {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Anggaran',
                                data: budgetData,
                                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Realisasi',
                                data: realizationData,
                                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: { responsive: true, scales: { y: { beginAtZero: true } } }
                };

                if (isComparing) {
                    chartConfig.data.datasets.push({
                        label: `Realisasi ${selectedYear - 1}`,
                        data: realizationPrevYearData,
                        backgroundColor: 'rgba(255, 206, 86, 0.5)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 1
                    });
                }

                window.anggaranBudgetChart = new Chart(budgetChartCanvas, chartConfig);
            }

            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(item => {
                    const percentage = parseFloat(item.persentase);
                    let progressBarColor = 'bg-green-600';
                    if (percentage > 75) progressBarColor = 'bg-yellow-500';
                    if (percentage >= 100) progressBarColor = 'bg-red-600';

                    let row;
                    if (isComparing) {
                        row = `<tr class="text-sm">
                                <td class="px-6 py-4">${item.nama_akun}</td>
                                <td class="px-6 py-4 text-right">${currencyFormatter.format(item.anggaran_bulanan)}</td>
                                <td class="px-6 py-4 text-right">${currencyFormatter.format(item.realisasi_belanja)}</td>
                                <td class="px-6 py-4 text-right text-gray-500">${currencyFormatter.format(item.realisasi_belanja_lalu)}</td>
                                <td class="px-6 py-4">
                                    <div class="w-full bg-gray-200 rounded-full h-5 dark:bg-gray-700">
                                        <div class="${progressBarColor} h-5 rounded-full text-white text-xs flex items-center justify-center" style="width: ${Math.min(percentage, 100)}%">${percentage.toFixed(1)}%</div>
                                    </div>
                                </td>
                            </tr>
                        `;
                    } else {
                        row = `
                            <tr class="text-sm">
                                <td class="px-6 py-4">${item.nama_akun}</td>
                                <td class="px-6 py-4 text-right">${currencyFormatter.format(item.anggaran_bulanan)}</td>
                                <td class="px-6 py-4 text-right">${currencyFormatter.format(item.realisasi_belanja)}</td>
                                <td class="px-6 py-4 text-right font-bold ${item.sisa_anggaran < 0 ? 'text-red-600' : ''}">${currencyFormatter.format(item.sisa_anggaran)}</td>
                                <td class="px-6 py-4">
                                    <div class="w-full bg-gray-200 rounded-full h-5 dark:bg-gray-700">
                                        <div class="${progressBarColor} h-5 rounded-full text-white text-xs flex items-center justify-center" style="width: ${Math.min(percentage, 100)}%">${percentage.toFixed(1)}%</div>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }
                    reportTableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                reportTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-gray-500">Belum ada data anggaran untuk periode ini.</td></tr>';
            }
        } catch (error) {
            reportTableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Gagal memuat laporan: ${error.message}</td></tr>`;
        }
    }


    async function loadBudgetManagement() {
        const selectedYear = yearFilter.value;
        modalTahunLabel.textContent = selectedYear;
        managementContainer.innerHTML = '<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>';
        try {
            const response = await fetch(`${basePath}/api/anggaran?action=list_budget&tahun=${selectedYear}`);
            const result = await response.json();
            managementContainer.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(item => {
                    const itemHtml = `
                        <div class="flex rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400" style="width: 250px;">${item.nama_akun}</span>
                            <input type="number" class="block w-full flex-1 rounded-none rounded-r-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-primary focus:ring-primary sm:text-sm" name="budgets[${item.account_id}]" value="${item.jumlah_anggaran}" placeholder="Anggaran Tahunan">
                        </div>
                    `;
                    managementContainer.insertAdjacentHTML('beforeend', itemHtml);
                });
            } else {
                managementContainer.innerHTML = '<p class="text-gray-500 text-center">Tidak ada akun beban yang dapat dianggarkan.</p>';
            }
        } catch (error) {
            managementContainer.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">Gagal memuat data anggaran.</div>`;
        }
    }

    saveAnggaranBtn.addEventListener('click', async () => {
        const form = document.getElementById('anggaran-management-form');
        const formData = new FormData(form);
        formData.append('action', 'save_budgets');
        formData.append('tahun', yearFilter.value);

        const response = await fetch(`${basePath}/api/anggaran`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
        if (result.status === 'success') {
            closeModal('anggaranModal');
            loadReport();
        }
    });

    exportPdfBtn.addEventListener('click', (e) => {
        e.preventDefault();

        // 1. Ambil gambar dari kedua chart sebagai base64
        const trendChartImage = window.anggaranTrendChart ? window.anggaranTrendChart.toBase64Image() : '';
        const budgetChartImage = window.anggaranBudgetChart ? window.anggaranBudgetChart.toBase64Image() : '';

        // 2. Buat form sementara untuk mengirim data via POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank'; // Buka di tab baru

        const params = {
            report: 'anggaran',
            tahun: yearFilter.value,
            bulan: monthFilter.value,
            compare: compareSwitch.checked,
            trend_chart_image: trendChartImage,
            budget_chart_image: budgetChartImage
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
        const tahun = yearFilter.value;
        const bulan = monthFilter.value;
        const isComparing = compareSwitch.checked;
        const params = new URLSearchParams({ report: 'anggaran', format: 'csv', tahun, bulan, compare: isComparing });
        const url = `${basePath}/api/csv?${params.toString()}`;
        window.open(url, '_blank');
    });

    // Gabungkan listener untuk tombol dan switch
    tampilkanBtn.addEventListener('click', () => {
        loadReport();
        loadTrendChart(); // Perbarui juga grafik tren saat filter tahun berubah
    });
    compareSwitch.addEventListener('change', loadReport);

    manageAnggaranBtn.addEventListener('click', () => {
        loadBudgetManagement();
        openModal('anggaranModal');
    });

    setupFilters();
    loadReport(); // Muat laporan detail
    loadTrendChart(); // Muat grafik tren
}
