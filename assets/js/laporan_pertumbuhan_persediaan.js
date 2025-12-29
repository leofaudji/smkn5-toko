function initLaporanPertumbuhanPersediaanPage() {
    const yearFilter = document.getElementById('lpp-tahun-filter');
    const showBtn = document.getElementById('lpp-tampilkan-btn');
    const tableBody = document.getElementById('lpp-report-table-body');
    const chartCanvas = document.getElementById('lpp-chart');
    const loadingIndicator = document.getElementById('lpp-loading');

    let inventoryChart = null;

    function formatRupiah(number) {
        const isNegative = number < 0;
        const formatted = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(Math.abs(number));

        return isNegative ? `<span class="text-danger">(${formatted})</span>` : formatted;
    }

    function setupFilters() {
        const currentYear = new Date().getFullYear();
        for (let i = 0; i < 5; i++) {
            const year = currentYear - i;
            yearFilter.add(new Option(year, year));
        }
        yearFilter.value = currentYear;
    }

    async function loadReport() {
        const selectedYear = yearFilter.value;
        loadingIndicator.style.display = 'block';
        tableBody.innerHTML = '';

        try {
            const response = await fetch(`${basePath}/api/pertumbuhan_persediaan?tahun=${selectedYear}`);
            const result = await response.json();

            if (result.status !== 'success') {
                throw new Error(result.message);
            }

            renderTable(result.data);
            renderChart(result.data);

        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="3" class="px-6 py-4 text-center text-red-600 dark:text-red-400">Gagal memuat laporan: ${error.message}</td></tr>`;
        } finally {
            loadingIndicator.style.display = 'none';
        }
    }

    function renderTable(data) {
        data.forEach(row => {
            let selisihClass = row.selisih > 0 ? 'text-green-600 dark:text-green-400' : (row.selisih < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white');
            let selisihIcon = row.selisih > 0 ? '<i class="bi bi-arrow-up"></i>' : (row.selisih < 0 ? '<i class="bi bi-arrow-down"></i>' : '');

            // Jika nilai persediaan 0, anggap tidak ada data opname
            if (parseFloat(row.nilai_persediaan) === 0) {
                tableBody.innerHTML += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${row.nama_bulan}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500 dark:text-gray-400 italic" colspan="2">Tidak ada data stok opname</td>
                    </tr>
                `;
                return; // Lanjut ke bulan berikutnya
            }

            tableBody.innerHTML += `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${row.nama_bulan}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-white">${formatRupiah(row.nilai_persediaan)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right ${selisihClass}">${selisihIcon} ${formatRupiah(row.selisih)}</td>
                </tr>
            `;
        });
    }

    function renderChart(data) {
        if (inventoryChart) {
            inventoryChart.destroy();
        }
        const labels = data.map(row => row.nama_bulan.substring(0, 3));
        const values = data.map(row => row.nilai_persediaan);

        inventoryChart = new Chart(chartCanvas, {
            type: 'line', // Mengubah tipe grafik dari 'bar' menjadi 'line'
            data: {
                labels: labels,
                datasets: [{
                    label: 'Nilai Persediaan (Hasil Opname)',
                    data: values,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)', // Warna area di bawah garis
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2, // Ketebalan garis
                    fill: true, // Mengisi area di bawah garis
                    tension: 0.3 // Membuat garis lebih halus (tidak kaku)
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            // Format label sumbu Y menjadi format Rupiah
                            callback: function(value) {
                                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', notation: 'compact' }).format(value);
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: { label: (context) => formatRupiah(context.parsed.y) }
                    }
                }
            }
        });
    }

    showBtn.addEventListener('click', loadReport);
    setupFilters();
    loadReport(); // Muat laporan untuk tahun default saat halaman dibuka
}
