function initDashboardPage() {
    const bulanFilter = document.getElementById('dashboard-bulan-filter');
    const tahunFilter = document.getElementById('dashboard-tahun-filter');
    const openCustomizeBtn = document.getElementById('open-customize-modal-btn');
    const customizeModalEl = document.getElementById('customizeDashboardModal');
    const widgetsForm = document.getElementById('dashboard-widgets-form');
    const saveWidgetsBtn = document.getElementById('save-dashboard-widgets-btn');

    // Variabel untuk menyimpan instance chart agar bisa di-destroy sebelum render ulang
    let trendChartInstance = null;
    let expenseChartInstance = null;
    let profitGrowthChartInstance = null;
    let inventoryGrowthChartInstance = null;

    // Fungsi untuk membuka/menutup modal kustomisasi (non-bootstrap)
    const openCustomizeModal = () => { populateCustomizeModal(); customizeModalEl.classList.remove('hidden'); };
    const closeCustomizeModal = () => { customizeModalEl.classList.add('hidden'); };

    // Definisi semua widget yang tersedia
    const allWidgets = {
        summary_cards: { name: 'Kartu Ringkasan (Saldo, Pemasukan, dll.)', default: true },
        balance_status: { name: 'Status Keseimbangan Neraca', default: true },
        profit_loss_trend: { name: 'Grafik Tren Arus Kas', default: true },
        profit_growth: { name: 'Grafik Pertumbuhan Laba', default: true },
        inventory_growth: { name: 'Grafik Pertumbuhan Nilai Persediaan', default: true },
        expense_category: { name: 'Grafik Kategori Pengeluaran', default: true },
        recent_transactions: { name: 'Tabel Transaksi Terbaru', default: true },
    };

    // Fungsi untuk mendapatkan preferensi widget dari localStorage
    function getWidgetPreferences() {
        const saved = localStorage.getItem('dashboard_widgets');
        if (saved) {
            return JSON.parse(saved);
        }
        // Jika tidak ada, buat dari default
        const defaults = {};
        for (const key in allWidgets) {
            defaults[key] = allWidgets[key].default;
        }
        return defaults;
    }

    // Fungsi untuk mengisi form di modal kustomisasi
    function populateCustomizeModal() {
        const prefs = getWidgetPreferences();
        widgetsForm.innerHTML = '';
        for (const [key, widget] of Object.entries(allWidgets)) {
            const isChecked = prefs[key] !== false; // Default to true if undefined
            widgetsForm.innerHTML += `
                <div class="flex items-center justify-between mb-3">
                    <label for="widget-toggle-${key}" class="text-sm text-gray-700 dark:text-gray-300">${widget.name}</label>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="widget-toggle-${key}" data-widget-key="${key}" class="sr-only peer" ${isChecked ? 'checked' : ''}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                    </label>
                </div>
            `;
        }
    }

    // Event listener untuk tombol "Tambah Transaksi"
    const addTransaksiBtn = document.getElementById('dashboard-add-transaksi');
    if (addTransaksiBtn) {
        addTransaksiBtn.addEventListener('click', (e) => {
            e.preventDefault();
            navigate(addTransaksiBtn.href + '#add'); // Tambahkan hash untuk memicu modal
        });
    }
    if (!bulanFilter || !tahunFilter || !openCustomizeBtn) return;

    function setupFilters() {
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;

        // Populate years
        for (let i = 0; i < 5; i++) {
            const year = currentYear - i;
            tahunFilter.add(new Option(year, year));
        }

        // Populate months
        const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
        months.forEach((month, index) => {
            bulanFilter.add(new Option(month, index + 1));
        });

        // Set default to current month and year
        bulanFilter.value = currentMonth;
        tahunFilter.value = currentYear;
    }

    async function fetchDashboardData(bulan, tahun, preferences) {
        // Note: Logika injeksi widget Bootstrap dihapus karena tidak kompatibel dengan view Tailwind saat ini.
        // Kita akan memperbarui elemen statis yang sudah ada di dashboard.php.

        // Tampilkan/sembunyikan widget berdasarkan preferensi pengguna
        for (const key in allWidgets) {
            const widgetEl = document.getElementById(`widget-${key}`);
            if (widgetEl) {
                // Sembunyikan widget jika preferensinya adalah false.
                // Jika preferensi tidak terdefinisi (undefined), anggap sebagai true (terlihat).
                widgetEl.classList.toggle('hidden', preferences[key] === false);
            }
        }

        // Ambil data dari API
        try {
            const response = await fetch(`${basePath}/api/dashboard?bulan=${bulan}&tahun=${tahun}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const data = result.data;
            const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

            // Update elemen statistik statis (sesuai ID di dashboard.php)
            const statCash = document.getElementById('stat-cash');
            const statIncome = document.getElementById('stat-income');
            const statExpense = document.getElementById('stat-expense');
            const statProfit = document.getElementById('stat-profit');

            if (statCash) statCash.textContent = currencyFormatter.format(data.total_saldo || 0);
            if (statIncome) statIncome.textContent = currencyFormatter.format(data.pemasukan_bulan_ini || 0);
            if (statExpense) statExpense.textContent = currencyFormatter.format(data.pengeluaran_bulan_ini || 0);
            if (statProfit) statProfit.textContent = currencyFormatter.format(data.laba_rugi_bulan_ini || 0);

            // --- Render Status Keseimbangan Neraca ---
            const balanceStatusContent = document.getElementById('balance-status-content');
            if (balanceStatusContent) {
                const isBalanced = data.balance_status?.is_balanced;
                let contentHtml = '';
                if (isBalanced === true) {
                    contentHtml = `
                        <div class="text-center">
                            <div class="text-6xl text-green-500 mb-2"><i class="bi bi-check-circle-fill"></i></div>
                            <h6 class="font-bold text-lg text-green-700">Seimbang</h6>
                            <p class="text-sm text-gray-500 mt-1">Total Aset sama dengan Total Liabilitas + Ekuitas.</p>
                            <a href="${basePath}/laporan" class="text-sm text-primary hover:underline mt-3 inline-block">Lihat Laporan Neraca</a>
                        </div>
                    `;
                } else {
                    contentHtml = `
                        <div class="text-center">
                            <div class="text-6xl text-red-500 mb-2"><i class="bi bi-x-octagon-fill"></i></div>
                            <h6 class="font-bold text-lg text-red-700">Tidak Seimbang</h6>
                            <p class="text-sm text-gray-500 mt-1">Periksa kembali entri jurnal Anda.</p>
                            <a href="${basePath}/laporan" class="text-sm text-primary hover:underline mt-3 inline-block">Lihat Laporan Neraca</a>
                        </div>
                    `;
                }
                balanceStatusContent.innerHTML = contentHtml;
            }

            // --- Render Transaksi Terbaru ---
            const recentTransactionsBody = document.getElementById('dashboard-recent-transactions');
            if (recentTransactionsBody) {
                recentTransactionsBody.innerHTML = '';
                if (data.transaksi_terbaru && data.transaksi_terbaru.length > 0) {
                    data.transaksi_terbaru.forEach(tx => {
                        const row = `
                            <tr class="border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                                <td class="py-3 pr-4"><div class="font-medium text-gray-800 dark:text-gray-200">${tx.keterangan}</div><div class="text-xs text-gray-500 dark:text-gray-400">${new Date(tx.tanggal).toLocaleDateString('id-ID')}</div></td>
                                <td class="py-3 px-4 text-right"><div class="font-semibold text-gray-800 dark:text-gray-200">${currencyFormatter.format(tx.jumlah)}</div></td>
                                <td class="py-3 pl-4 text-right"><a href="${basePath}/daftar-jurnal#JRN-${tx.ref_id}" class="text-primary hover:underline text-sm">Detail</a></td>
                            </tr>
                        `;
                        recentTransactionsBody.innerHTML += row;
                    });
                } else {
                    recentTransactionsBody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-gray-500">Tidak ada transaksi terbaru.</td></tr>';
                }
            }

            // --- Render Grafik Tren Arus Kas ---
            const trendCtx = document.getElementById('dashboard-trend-chart');
            if (trendCtx) {
                if (trendChartInstance) trendChartInstance.destroy();
                
                // Gunakan data dari API atau dummy jika belum tersedia
                // Definisikan sumber data, fallback ke objek kosong jika tidak ada
                // Cek nama properti baru (tren_arus_kas) dulu, lalu fallback ke yang lama (profit_loss_trend)
                const trendDataSource = data.tren_arus_kas || data.profit_loss_trend || {};
                const trendLabels = trendDataSource.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                const incomeData = trendDataSource.pemasukan || trendDataSource.income || Array(12).fill(0);
                const expenseData = trendDataSource.pengeluaran || trendDataSource.expense || Array(12).fill(0);

                trendChartInstance = new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: trendLabels,
                        datasets: [
                            {
                                label: 'Pemasukan',
                                data: incomeData,
                                borderColor: '#10B981', // Tailwind Emerald-500
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.4,
                                fill: true
                            },
                            {
                                label: 'Pengeluaran',
                                data: expenseData,
                                borderColor: '#EF4444', // Tailwind Red-500
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                tension: 0.4,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: { legend: { position: 'top' } },
                        scales: { y: { beginAtZero: true, ticks: { callback: (val) => currencyFormatter.format(val) } } }
                    }
                });
            }

            // --- Render Grafik Kategori Pengeluaran ---
            const expenseCtx = document.getElementById('dashboard-expense-chart');
            if (expenseCtx) {
                if (expenseChartInstance) expenseChartInstance.destroy();
                
                // Sesuaikan dengan nama properti dari API: 'pengeluaran_per_kategori'
                const expenseCatSource = data.pengeluaran_per_kategori || data.expense_categories || {};
                const catLabels = expenseCatSource.labels || ['Belum ada data'];
                const catData = expenseCatSource.data || [1];
                const catColors = ['#3B82F6', '#F59E0B', '#10B981', '#EF4444', '#8B5CF6', '#6B7280']; // Tailwind colors

                expenseChartInstance = new Chart(expenseCtx, {
                    type: 'doughnut',
                    data: {
                        labels: catLabels,
                        datasets: [{ data: catData, backgroundColor: catColors, borderWidth: 0 }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
                    }
                });
            }

            // --- Render Grafik Pertumbuhan Laba ---
            const profitGrowthCtx = document.getElementById('dashboard-profit-growth-chart');
            if (profitGrowthCtx) {
                if (profitGrowthChartInstance) profitGrowthChartInstance.destroy();

                const profitGrowthDataSource = data.pertumbuhan_laba_bulanan || {};
                const profitLabels = profitGrowthDataSource.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                const profitData = profitGrowthDataSource.data || Array(12).fill(0);

                profitGrowthChartInstance = new Chart(profitGrowthCtx, {
                    type: 'line',
                    data: {
                        labels: profitLabels,
                        datasets: [
                            {
                                label: 'Laba Bersih',
                                data: profitData,
                                backgroundColor: 'rgba(0, 122, 255, 0.1)', // Warna area di bawah garis
                                borderColor: 'rgba(0, 122, 255, 1)', // Warna garis
                                borderWidth: 2,
                                fill: true, // Mengaktifkan warna area di bawah garis
                                tension: 0.4 // Membuat garis lebih halus
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { 
                            legend: { display: false } // Hide legend for single dataset
                        },
                        scales: { 
                            y: { beginAtZero: false, ticks: { callback: (val) => currencyFormatter.format(val) } } 
                        }
                    }
                });
            }

            // --- Render Grafik Pertumbuhan Nilai Persediaan ---
            const inventoryGrowthCtx = document.getElementById('dashboard-inventory-growth-chart');
            if (inventoryGrowthCtx) {
                // Fetch data khusus untuk persediaan
                const invResponse = await fetch(`${basePath}/api/pertumbuhan_persediaan.php?tahun=${tahun}`);
                const invResult = await invResponse.json();

                if (invResult.status === 'success') {
                    if (inventoryGrowthChartInstance) inventoryGrowthChartInstance.destroy();

                    const invLabels = invResult.data.map(d => d.nama_bulan);
                    const invData = invResult.data.map(d => d.nilai_persediaan);

                    inventoryGrowthChartInstance = new Chart(inventoryGrowthCtx, {
                        type: 'line',
                        data: {
                            labels: invLabels,
                            datasets: [{
                                label: 'Nilai Persediaan',
                                data: invData,
                                borderColor: '#8B5CF6', // Tailwind Violet-500
                                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { callback: (val) => currencyFormatter.format(val) } } }
                        }
                    });
                }
            }

        } catch (error) {
            showToast(`Gagal memuat data dashboard: ${error.message}`, 'error');
        }
    }

    // --- Event Listeners ---
    const filterHandler = () => {
        const prefs = getWidgetPreferences();
        fetchDashboardData(bulanFilter.value, tahunFilter.value, prefs);
    };
    bulanFilter.addEventListener('change', filterHandler);
    tahunFilter.addEventListener('change', filterHandler);

    // Ganti listener Bootstrap dengan listener klik biasa
    openCustomizeBtn.addEventListener('click', openCustomizeModal);
    document.querySelectorAll('[data-modal-close="customizeDashboardModal"]').forEach(btn => {
        btn.addEventListener('click', closeCustomizeModal);
    });

    saveWidgetsBtn.addEventListener('click', () => {
        const newPrefs = {};
        widgetsForm.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            newPrefs[checkbox.dataset.widgetKey] = checkbox.checked;
        });
        localStorage.setItem('dashboard_widgets', JSON.stringify(newPrefs));
        showToast('Preferensi dashboard berhasil disimpan.', 'success');
        closeCustomizeModal(); // Ganti dari bootstrap hide()
        // Muat ulang dashboard dengan preferensi baru
        filterHandler();
    });

    setupFilters();
    filterHandler(); // Panggil handler untuk memuat data awal
}