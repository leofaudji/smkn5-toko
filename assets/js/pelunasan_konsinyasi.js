/**
 * Global Tab Switcher for Pelunasan Konsinyasi
 * Defined globally so inline onclick handlers can find it.
 */
window.switchTabPK = function(target) {
    const btnPelunasan = document.getElementById('pk-btn-pelunasan');
    const btnHistory = document.getElementById('pk-btn-history');
    const contentPelunasan = document.getElementById('pk-content-pelunasan');
    const contentHistory = document.getElementById('pk-content-history');

    if (!btnPelunasan || !btnHistory || !contentPelunasan || !contentHistory) {
        console.error('Tab elements not found');
        return;
    }

    if (target === 'pelunasan') {
        btnPelunasan.classList.add('border-primary', 'text-primary', 'active-tab');
        btnPelunasan.classList.remove('border-transparent', 'text-gray-500');
        btnHistory.classList.remove('border-primary', 'text-primary', 'active-tab');
        btnHistory.classList.add('border-transparent', 'text-gray-500');
        
        contentPelunasan.style.display = 'block';
        contentHistory.style.display = 'none';
        contentPelunasan.classList.remove('hidden');
        contentHistory.classList.add('hidden');
    } else {
        btnHistory.classList.add('border-primary', 'text-primary', 'active-tab');
        btnHistory.classList.remove('border-transparent', 'text-gray-500');
        btnPelunasan.classList.remove('border-primary', 'text-primary', 'active-tab');
        btnPelunasan.classList.add('border-transparent', 'text-gray-500');
        
        contentHistory.style.display = 'block';
        contentPelunasan.style.display = 'none';
        contentHistory.classList.remove('hidden');
        contentPelunasan.classList.add('hidden');
    }
    console.log('Switched to tab:', target);
};

function initPelunasanKonsinyasiPage() {
    console.log('Initializing Pelunasan Konsinyasi Page...');
    
    // Selectors with new PK- prefix
    const balanceTableBody = document.getElementById('pk-supplier-table-body');
    const balanceTableFoot = document.getElementById('pk-supplier-table-foot');
    const historyTableBody = document.getElementById('pk-history-table-body');
    const paySupplierSelect = document.getElementById('pk-pay-supplier-id');
    const payKasAccountSelect = document.getElementById('pk-pay-kas-account');
    const payForm = document.getElementById('pk-payment-form');
    
    const filterDateMulai = document.getElementById('filter-date-mulai');
    const filterDateAkhir = document.getElementById('filter-date-akhir');
    const searchSupplierInput = document.getElementById('pk-search-supplier');
    const searchHistoryInput = document.getElementById('pk-search-history');
    const filterHistorySupplier = document.getElementById('pk-filter-history-supplier');

    // Debug check
    if (!balanceTableBody) {
        console.error('CRITICAL: pk-supplier-table-body not found!');
        return;
    }

    let originalData = [];
    let historyData = [];
    let apiDebugInfo = null;
    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    // Initialize Flatpickr
    if (filterDateMulai) flatpickr(filterDateMulai, { dateFormat: "Y-m-d", defaultDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1) });
    if (filterDateAkhir) flatpickr(filterDateAkhir, { dateFormat: "Y-m-d", defaultDate: "today" });
    if (document.getElementById('pk-pay-tanggal')) flatpickr("#pk-pay-tanggal", { dateFormat: "Y-m-d", defaultDate: "today" });

    function updateStats(data, meta) {
        let totals = { utang: 0, bayar: 0, sisa: 0 };
        data.forEach(row => {
            totals.utang += parseFloat(row.total_utang) || 0;
            totals.bayar += parseFloat(row.total_bayar) || 0;
            totals.sisa += parseFloat(row.sisa_utang) || 0;
        });

        const statUtang = document.getElementById('stat-total-utang');
        const statBayar = document.getElementById('stat-total-bayar');
        const statSisa = document.getElementById('stat-sisa-utang');
        
        if (statUtang) statUtang.textContent = currencyFormatter.format(totals.utang);
        if (statBayar) statBayar.textContent = currencyFormatter.format(totals.bayar);
        if (statSisa) statSisa.textContent = currencyFormatter.format(totals.sisa);
    }

    function renderTable(data) {
        if (!balanceTableBody) return;
        balanceTableBody.innerHTML = '';
        if (data.length === 0) {
            balanceTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-12 text-gray-500"><i class="bi bi-inbox text-4xl block mb-2 opacity-20"></i> Tidak ada data utang.</td></tr>';
            return;
        }

        data.forEach(row => {
            const utang = parseFloat(row.total_utang) || 0;
            const bayar = parseFloat(row.total_bayar) || 0;
            const sisa = parseFloat(row.sisa_utang) || 0;
            const progress = utang > 0 ? Math.min(100, (bayar / utang) * 100) : 0;
            
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors animate-fade-in cursor-default';
            tr.innerHTML = `
                <td class="px-6 py-4">
                    <div class="font-bold text-gray-900 dark:text-white">${row.nama_pemasok}</div>
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1 mt-2 max-w-[100px]">
                        <div class="bg-primary h-1 rounded-full" style="width: ${progress}%"></div>
                    </div>
                </td>
                <td class="px-6 py-4 text-right text-sm text-gray-600 dark:text-gray-400">${currencyFormatter.format(utang)}</td>
                <td class="px-6 py-4 text-right text-sm text-green-600 font-bold">${currencyFormatter.format(bayar)}</td>
                <td class="px-6 py-4 text-right">
                    <span class="text-sm font-black ${sisa > 0 ? 'text-red-600' : 'text-gray-400'}">${currencyFormatter.format(sisa)}</span>
                </td>
                <td class="px-6 py-4 text-center">
                    <button class="p-2 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition-all pk-pay-btn" 
                            data-id="${row.id}" data-nama="${row.nama_pemasok}" data-sisa="${sisa}">
                        <i class="bi bi-cash-stack"></i>
                    </button>
                </td>
            `;
            balanceTableBody.appendChild(tr);
        });

        // Update Foot Totals
        if (balanceTableFoot) {
            let totals = { utang: 0, bayar: 0, sisa: 0 };
            data.forEach(row => {
                totals.utang += parseFloat(row.total_utang) || 0;
                totals.bayar += parseFloat(row.total_bayar) || 0;
                totals.sisa += parseFloat(row.sisa_utang) || 0;
            });

            balanceTableFoot.innerHTML = `
                <tr class="bg-gray-50/50 dark:bg-gray-700/50 font-bold border-t-2 border-gray-200 dark:border-gray-600">
                    <td class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-widest">TOTAL RINGKASAN</td>
                    <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300 font-bold border-l border-gray-100 dark:border-gray-700">${currencyFormatter.format(totals.utang)}</td>
                    <td class="px-6 py-4 text-right text-green-600 font-bold border-l border-gray-100 dark:border-gray-700">${currencyFormatter.format(totals.bayar)}</td>
                    <td class="px-6 py-4 text-right text-red-600 font-black border-l border-gray-100 dark:border-gray-700">${currencyFormatter.format(totals.sisa)}</td>
                    <td></td>
                </tr>
            `;
        }
    }

    function renderHistoryTable(data, debugInfo = null) {
        if (!historyTableBody) return;
        historyTableBody.innerHTML = '';
        
        if (!Array.isArray(data) || data.length === 0) {
            let diag = debugInfo ? `<div class="text-[10px] mt-2 opacity-50">API status: ${debugInfo.status || 'OK'} | Count: ${debugInfo.count || 0}</div>` : '';
            historyTableBody.innerHTML = `<tr><td colspan="4" class="text-center py-20 text-gray-400">
                <i class="bi bi-clock-history text-4xl block mb-2 opacity-20"></i>
                Belum ada riwayat pembayaran.
                ${diag}
            </td></tr>`;
            return;
        }

        data.forEach(h => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50/80 transition-colors animate-fade-in';
            const amount = parseFloat(h.jumlah) || 0;
            const dateStr = h.tanggal ? new Date(h.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';

            tr.innerHTML = `
                <td class="px-6 py-4 text-sm text-gray-500">${dateStr}</td>
                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">${h.nama_pemasok || 'Pemasok Umum'}</td>
                <td class="px-6 py-4 text-sm text-gray-500 italic">${h.keterangan || '-'}</td>
                <td class="px-6 py-4 text-right font-black text-green-600">${currencyFormatter.format(amount)}</td>
            `;
            historyTableBody.appendChild(tr);
        });
    }

    async function loadData() {
        const start = filterDateMulai ? filterDateMulai.value : '';
        const end = filterDateAkhir ? filterDateAkhir.value : '';
        
        try {
            const [debtRes, supplierRes, coaRes, historyRes] = await Promise.all([
                fetch(`${basePath}/api/konsinyasi?action=get_debt_summary_report&start_date=${start}&end_date=${end}&_=${Date.now()}`),
                fetch(`${basePath}/api/konsinyasi?action=list_suppliers&_=${Date.now()}`),
                fetch(`${basePath}/api/coa?_=${Date.now()}`),
                fetch(`${basePath}/api/konsinyasi?action=list_payments&_=${Date.now()}`)
            ]);

            // Handle Debt Data
            try {
                const debtResult = await debtRes.json();
                originalData = debtResult.data || [];
                updateStats(originalData, debtResult.meta);
                applyFilters();
            } catch (e) { console.error('Error loading debt data:', e); }

            // Handle Supplier Select
            try {
                const supplierResult = await supplierRes.json();
                if (paySupplierSelect) {
                    paySupplierSelect.innerHTML = '<option value="">-- Pilih Pemasok --</option>';
                    (supplierResult.data || []).forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.id; opt.textContent = s.nama_pemasok;
                        paySupplierSelect.appendChild(opt);

                        // Also populate history filter
                        if (filterHistorySupplier) {
                            const optHistory = document.createElement('option');
                            optHistory.value = s.nama_pemasok; // Use name for history filtering
                            optHistory.textContent = s.nama_pemasok;
                            filterHistorySupplier.appendChild(optHistory);
                        }
                    });
                }
            } catch (e) { console.error('Error loading suppliers:', e); }

            // Handle COA Select
            try {
                const coaResult = await coaRes.json();
                if (payKasAccountSelect) {
                    payKasAccountSelect.innerHTML = '<option value="">-- Pilih Sumber Dana --</option>';
                    const accounts = Array.isArray(coaResult) ? coaResult : (coaResult.data || []);
                    accounts.forEach(a => {
                        const opt = document.createElement('option');
                        opt.value = a.id; opt.textContent = `${a.kode_akun} - ${a.nama_akun}`;
                        payKasAccountSelect.appendChild(opt);
                    });
                }
            } catch (e) { console.error('Error loading COA:', e); }

            // Handle History Data
            try {
                const historyResult = await historyRes.json();
                historyData = historyResult.data || [];
                apiDebugInfo = { status: historyResult.status, count: historyData.length };
                renderHistoryTable(historyData, apiDebugInfo);
            } catch (e) { console.error('Error loading history:', e); }

        } catch (error) {
            console.error('Core loading error:', error);
            showToast('Gagal memuat data.', 'error');
        }
    }

    function applyFilters() {
        const term = searchSupplierInput ? searchSupplierInput.value.toLowerCase() : '';
        const onlyDebt = document.getElementById('pk-filter-only-debt')?.checked || false;
        
        let filtered = originalData.filter(row => row.nama_pemasok.toLowerCase().includes(term));
        if (onlyDebt) filtered = filtered.filter(row => parseFloat(row.sisa_utang) > 0);
        renderTable(filtered);
    }

    function applyHistoryFilters() {
        const term = searchHistoryInput ? searchHistoryInput.value.toLowerCase() : '';
        const supplierName = filterHistorySupplier ? filterHistorySupplier.value : '';

        let filtered = historyData;

        if (supplierName) {
            filtered = filtered.filter(h => (h.nama_pemasok || '') === supplierName);
        }

        if (term) {
            filtered = filtered.filter(h => 
                (h.nama_pemasok || '').toLowerCase().includes(term) || 
                (h.keterangan || '').toLowerCase().includes(term)
            );
        }
        renderHistoryTable(filtered, apiDebugInfo);
    }

    // Event Listeners
    if (searchSupplierInput) searchSupplierInput.addEventListener('input', applyFilters);
    if (searchHistoryInput) searchHistoryInput.addEventListener('input', applyHistoryFilters);
    if (filterHistorySupplier) filterHistorySupplier.addEventListener('change', applyHistoryFilters);
    if (document.getElementById('pk-filter-only-debt')) document.getElementById('pk-filter-only-debt').addEventListener('change', applyFilters);

    balanceTableBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.pk-pay-btn');
        if (btn) {
            const { id, sisa } = btn.dataset;
            if (paySupplierSelect) paySupplierSelect.value = id || "";
            const payJumlah = document.getElementById('pk-pay-jumlah');
            if (payJumlah) { payJumlah.value = Math.max(0, parseInt(sisa)); payJumlah.focus(); }
            window.switchTabPK('pelunasan'); 
        }
    });

    if (payForm) {
        payForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(payForm);
            formData.append('action', 'pay_debt');
            
            try {
                const response = await fetch(`${basePath}/api/konsinyasi`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') {
                    payForm.reset();
                    loadData();
                }
            } catch (error) { showToast('Gagal memproses pembayaran.', 'error'); }
        });
    }

    [filterDateMulai, filterDateAkhir].forEach(el => { if (el) el.addEventListener('change', loadData); });

    // Initial Load
    loadData();
}
