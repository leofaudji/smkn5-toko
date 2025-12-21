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

    // Fungsi untuk membuka/menutup modal kustomisasi (non-bootstrap)
    const openCustomizeModal = () => { populateCustomizeModal(); customizeModalEl.classList.remove('hidden'); };
    const closeCustomizeModal = () => { customizeModalEl.classList.add('hidden'); };

    // Definisi semua widget yang tersedia
    const allWidgets = {
        summary_cards: { name: 'Kartu Ringkasan (Saldo, Pemasukan, dll.)', default: true },
        balance_status: { name: 'Status Keseimbangan Neraca', default: true },
        profit_loss_trend: { name: 'Grafik Tren Laba/Rugi', default: true },
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

            // --- Render Grafik Tren Arus Kas ---
            const trendCtx = document.getElementById('dashboard-trend-chart');
            if (trendCtx) {
                if (trendChartInstance) trendChartInstance.destroy();
                
                // Gunakan data dari API atau dummy jika belum tersedia
                const trendLabels = data.trend?.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                const incomeData = data.trend?.income || [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
                const expenseData = data.trend?.expense || [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

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
                
                const catLabels = data.expense_categories?.labels || ['Belum ada data'];
                const catData = data.expense_categories?.data || [1];
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