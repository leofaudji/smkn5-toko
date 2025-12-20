function initDashboardPage() {
    const bulanFilter = document.getElementById('dashboard-bulan-filter');
    const tahunFilter = document.getElementById('dashboard-tahun-filter');
    const customizeModalEl = document.getElementById('customizeDashboardModal');
    const widgetsForm = document.getElementById('dashboard-widgets-form');
    const saveWidgetsBtn = document.getElementById('save-dashboard-widgets-btn');

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
        for (const key in allWidgets) {
            const widget = allWidgets[key];
            const isChecked = prefs[key] !== false; // Default to true if undefined
            widgetsForm.innerHTML += `
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="widget-toggle-${key}" data-widget-key="${key}" ${isChecked ? 'checked' : ''}>
                    <label class="form-check-label" for="widget-toggle-${key}">${widget.name}</label>
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
    if (!bulanFilter || !tahunFilter) return;

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
        // Hapus container dashboard lama jika ada, untuk mencegah duplikasi
        const oldDashboardContent = document.getElementById('dashboard-content-wrapper');
        if (oldDashboardContent) {
            oldDashboardContent.remove();
        }

        // Buat HTML untuk widget berdasarkan preferensi
        let widgetsHtml = '';
        if (preferences.summary_cards) {
            widgetsHtml += `
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body">
                                <h5 class="card-title">Total Saldo Kas</h5>
                                <h2 class="fw-bold" id="total-saldo-widget"><div class="spinner-border spinner-border-sm"></div></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body">
                                <h5 class="card-title">Pemasukan Bulan Ini</h5>
                                <h2 class="fw-bold" id="pemasukan-widget"><div class="spinner-border spinner-border-sm"></div></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card text-white bg-danger h-100">
                            <div class="card-body">
                                <h5 class="card-title">Pengeluaran Bulan Ini</h5>
                                <h2 class="fw-bold" id="pengeluaran-widget"><div class="spinner-border spinner-border-sm"></div></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card text-white bg-info h-100">
                            <div class="card-body">
                                <h5 class="card-title">Laba/Rugi Bulan Ini</h5>
                                <h2 class="fw-bold" id="laba-rugi-widget"><div class="spinner-border spinner-border-sm"></div></h2>
                                <small id="laba-rugi-subtitle"></small>
                            </div>
                        </div>
                    </div>
                </div>`;
        }
        if (preferences.balance_status || preferences.profit_loss_trend) {
            widgetsHtml += `<div class="row g-3">`;
            if (preferences.balance_status) {
                widgetsHtml += `
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card h-100" id="balance-status-card">
                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                 <div id="balance-status-icon" class="fs-1"><div class="spinner-border"></div></div>
                                 <h5 class="card-title mt-2" id="balance-status-text">Memeriksa Status...</h5>
                                 <small class="text-muted">Keseimbangan Neraca</small>
                            </div>
                        </div>
                    </div>`;
            }
            if (preferences.profit_loss_trend) {
                widgetsHtml += `
                    <div class="col-lg-9 col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header"><h5 class="card-title mb-0">Tren Laba/Rugi (30 Hari Terakhir)</h5></div>
                            <div class="card-body"><canvas id="profit-loss-trend-chart"></canvas></div>
                        </div>
                    </div>`;
            }
            widgetsHtml += `</div>`;
        }
        if (preferences.expense_category || preferences.recent_transactions) {
            widgetsHtml += `<div class="row g-3">`;
            if (preferences.expense_category) {
                widgetsHtml += `
                <div class="col-lg-5 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Pengeluaran per Kategori</h5>
                        </div>
                        <div class="card-body d-flex justify-content-center align-items-center">
                            <div style="position: relative; height:250px; width:100%">
                                <canvas id="expense-category-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>`;
            }
            if (preferences.recent_transactions) {
                widgetsHtml += `
                <div class="col-lg-7 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Transaksi Terbaru</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr><th>Tanggal</th><th>Keterangan</th><th class="text-end">Jumlah</th></tr>
                                    </thead>
                                    <tbody id="recent-transactions-widget">
                                        <tr><td colspan="3" class="text-center p-4"><div class="spinner-border spinner-border-sm"></div></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>`;
            }
            widgetsHtml += `</div>`;
        }

        const newDashboardHtml = `<div id="dashboard-content-wrapper" class="mt-4">${widgetsHtml}</div>`;
        // Sisipkan setelah elemen h1 dan filter
        const borderBottom = document.querySelector('.main-content .row.g-3.mb-4');
        if (borderBottom) {
            borderBottom.insertAdjacentHTML('afterend', newDashboardHtml);
        }

        // Ambil data dari API
        try {
            const response = await fetch(`${basePath}/api/dashboard?bulan=${bulan}&tahun=${tahun}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const data = result.data;
            const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

            // Isi data hanya jika widgetnya ada
            if (preferences.summary_cards) {
                document.getElementById('total-saldo-widget').textContent = currencyFormatter.format(data.total_saldo);
                document.getElementById('pemasukan-widget').textContent = currencyFormatter.format(data.pemasukan_bulan_ini);
                document.getElementById('pengeluaran-widget').textContent = currencyFormatter.format(data.pengeluaran_bulan_ini);
            }

            // Render Balance Status
            if (preferences.balance_status) {
                const balanceCard = document.getElementById('balance-status-card');
                const balanceIcon = document.getElementById('balance-status-icon');
                const balanceText = document.getElementById('balance-status-text');
                const balanceStatus = data.balance_status;

                balanceCard.style.cursor = 'default';
                balanceCard.onclick = null;

                if (balanceStatus.is_balanced) {
                    balanceCard.classList.add('bg-success-subtle');
                    balanceIcon.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
                    balanceText.textContent = 'Balance';
                } else {
                    balanceCard.classList.add('bg-danger-subtle');
                    balanceCard.style.cursor = 'pointer';
                    balanceIcon.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
                    balanceText.textContent = 'Tidak Balance';

                    balanceCard.onclick = () => {
                        const detailModalEl = document.getElementById('detailModal');
                        const detailModal = bootstrap.Modal.getInstance(detailModalEl) || new bootstrap.Modal(detailModalEl);
                        document.getElementById('detailModalLabel').textContent = 'Detail Ketidakseimbangan Neraca';
                        const modalBody = document.getElementById('detailModalBody');
                        
                        let journalDetailsHtml = '';
                        if (balanceStatus.unbalanced_journals && balanceStatus.unbalanced_journals.length > 0) {
                            journalDetailsHtml = `
                                <h5 class="mt-4">Jurnal Tidak Seimbang Terdeteksi</h5>
                                <p class="text-muted">Berikut adalah daftar entri jurnal yang kemungkinan menjadi penyebab ketidakseimbangan. Klik pada ID Jurnal untuk memperbaikinya.</p>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID Jurnal</th><th>Tanggal</th><th>Keterangan</th><th class="text-end">Total Debit</th><th class="text-end">Total Kredit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${balanceStatus.unbalanced_journals.map(j => `
                                                <tr>
                                                    <td><a href="${basePath}/entri-jurnal?edit_id=${j.id}">JRN-${String(j.id).padStart(5, '0')}</a></td>
                                                    <td>${new Date(j.tanggal).toLocaleDateString('id-ID')}</td>
                                                    <td>${j.keterangan}</td>
                                                    <td class="text-end">${currencyFormatter.format(j.total_debit)}</td>
                                                    <td class="text-end">${currencyFormatter.format(j.total_kredit)}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            `;
                        }

                        modalBody.innerHTML = `
                            <p>Neraca Anda tidak seimbang. Berikut adalah rincian perhitungannya:</p>
                            <dl class="row">
                                <dt class="col-sm-6">Total Aset</dt><dd class="col-sm-6 text-end">${currencyFormatter.format(balanceStatus.total_aset)}</dd>
                                <dt class="col-sm-6">Total Liabilitas + Ekuitas</dt><dd class="col-sm-6 text-end">${currencyFormatter.format(balanceStatus.total_liabilitas_ekuitas)}</dd>
                                <dt class="col-sm-6 border-top pt-2">Selisih</dt><dd class="col-sm-6 text-end border-top pt-2 fw-bold text-danger">${currencyFormatter.format(balanceStatus.selisih)}</dd>
                            </dl>
                            ${journalDetailsHtml}
                        `;
                        detailModal.show();
                    };
                }
            }
            
            if (preferences.summary_cards) {
                const labaRugiWidget = document.getElementById('laba-rugi-widget');
                const labaRugiSubtitle = document.getElementById('laba-rugi-subtitle');
                labaRugiWidget.textContent = currencyFormatter.format(data.laba_rugi_bulan_ini);
                if (data.laba_rugi_bulan_ini < 0) {
                    labaRugiWidget.parentElement.parentElement.classList.replace('bg-info', 'bg-warning');
                    labaRugiSubtitle.textContent = 'Rugi';
                } else {
                    labaRugiSubtitle.textContent = 'Laba';
                }
            }

            // Render recent transactions
            if (preferences.recent_transactions) {
                const txWidget = document.getElementById('recent-transactions-widget');
                txWidget.innerHTML = '';
                if (data.transaksi_terbaru.length > 0) {
                    data.transaksi_terbaru.forEach(tx => {
                        const row = `
                            <tr>
                                <td>${new Date(tx.tanggal).toLocaleDateString('id-ID', {day:'2-digit', month:'short'})}</td>
                                <td>${tx.keterangan}</td>
                                <td class="text-end">${currencyFormatter.format(tx.jumlah)}</td>
                            </tr>
                        `;
                        txWidget.insertAdjacentHTML('beforeend', row);
                    });
                } else {
                    txWidget.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Tidak ada transaksi.</td></tr>';
                }
            }

            // Render chart
            if (preferences.expense_category && document.getElementById('expense-category-chart')) {
                const chartCtx = document.getElementById('expense-category-chart').getContext('2d');
                if (window.dashboardExpenseChart) window.dashboardExpenseChart.destroy();
                window.dashboardExpenseChart = new Chart(chartCtx, {
                    type: 'doughnut',
                    data: {
                        labels: data.pengeluaran_per_kategori.labels,
                        datasets: [{ data: data.pengeluaran_per_kategori.data }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
                });
            }

            // Render profit loss trend chart
            if (preferences.profit_loss_trend && document.getElementById('profit-loss-trend-chart')) {
                const trendChartCtx = document.getElementById('profit-loss-trend-chart').getContext('2d');
                if (window.dashboardTrendChart) window.dashboardTrendChart.destroy();
                window.dashboardTrendChart = new Chart(trendChartCtx, {
                    type: 'line',
                    data: {
                        labels: data.laba_rugi_harian.labels.map(d => new Date(d).toLocaleDateString('id-ID', {day: '2-digit', month: 'short'})),
                        datasets: [{
                            label: 'Laba / Rugi Harian',
                            data: data.laba_rugi_harian.data,
                            fill: true,
                            backgroundColor: 'rgba(0, 122, 255, 0.1)',
                            borderColor: 'rgba(0, 122, 255, 1)',
                            tension: 0.3,
                            pointBackgroundColor: 'rgba(0, 122, 255, 1)',
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: { y: { ticks: { callback: value => 'Rp ' + new Intl.NumberFormat('id-ID').format(value) } } }
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

    customizeModalEl.addEventListener('show.bs.modal', populateCustomizeModal);

    saveWidgetsBtn.addEventListener('click', () => {
        const newPrefs = {};
        widgetsForm.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            newPrefs[checkbox.dataset.widgetKey] = checkbox.checked;
        });
        localStorage.setItem('dashboard_widgets', JSON.stringify(newPrefs));
        showToast('Preferensi dashboard berhasil disimpan.', 'success');
        bootstrap.Modal.getInstance(customizeModalEl).hide();
        // Muat ulang dashboard dengan preferensi baru
        filterHandler();
    });

    setupFilters();
    filterHandler(); // Panggil handler untuk memuat data awal
}