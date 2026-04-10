function initPelunasanKonsinyasiPage() {
    const balanceTableBody = document.getElementById('supplier-balance-table-body');
    const paySupplierSelect = document.getElementById('pay-supplier-id');
    const payKasAccountSelect = document.getElementById('pay-kas-account');
    const payForm = document.getElementById('payment-form');
    const paymentHistoryList = document.getElementById('payment-history-list');
    
    const filterDateMulai = document.getElementById('filter-date-mulai');
    const filterDateAkhir = document.getElementById('filter-date-akhir');
    const searchSupplierInput = document.getElementById('search-supplier');

    if (!balanceTableBody) return;

    let originalData = [];
    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    // Initialize Flatpickr
    flatpickr(filterDateMulai, { dateFormat: "Y-m-d", defaultDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1) });
    flatpickr(filterDateAkhir, { dateFormat: "Y-m-d", defaultDate: "today" });
    flatpickr("#pay-tanggal", { dateFormat: "Y-m-d", defaultDate: "today" });

    function updateStats(data, meta) {
        let totals = { utang: 0, bayar: 0, sisa: 0 };
        data.forEach(row => {
            totals.utang += parseFloat(row.total_utang);
            totals.bayar += parseFloat(row.total_bayar);
            totals.sisa += parseFloat(row.sisa_utang);
        });

        const statUtang = document.getElementById('stat-total-utang');
        const statBayar = document.getElementById('stat-total-bayar');
        const statSisa = document.getElementById('stat-sisa-utang');
        const syncOk = document.getElementById('stat-sync-ok');
        const syncWarning = document.getElementById('stat-sync-warning');

        if (statUtang) statUtang.textContent = currencyFormatter.format(totals.utang);
        if (statBayar) statBayar.textContent = currencyFormatter.format(totals.bayar);
        if (statSisa) statSisa.textContent = currencyFormatter.format(totals.sisa);

        // Discrepancy check with Audit Saldo (returned in meta)
        if (meta && meta.total_balance_audit !== undefined) {
            const diff = Math.abs(parseFloat(meta.total_balance_audit) - totals.sisa);
            if (diff > 1) { // 1 Rupiah threshold for floating point
                syncOk.classList.add('hidden');
                syncWarning.classList.remove('hidden');
            } else {
                syncOk.classList.remove('hidden');
                syncWarning.classList.add('hidden');
            }
        }
    }

    function renderTable(data) {
        balanceTableBody.innerHTML = '';
        if (data.length === 0) {
            balanceTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-12 text-gray-500"><i class="bi bi-inbox text-4xl block mb-2 opacity-20"></i> Tidak ada data utang ditemukan.</td></tr>';
            return;
        }

        data.forEach(row => {
            const utang = parseFloat(row.total_utang);
            const bayar = parseFloat(row.total_bayar);
            const sisa = parseFloat(row.sisa_utang);
            const progress = utang > 0 ? Math.min(100, (bayar / utang) * 100) : 0;
            
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors group animate-fade-in';
            tr.innerHTML = `
                <td class="px-6 py-4">
                    <div class="font-bold text-gray-900 dark:text-white">${row.nama_pemasok}</div>
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1 mt-2 max-w-[100px]">
                        <div class="bg-primary h-1 rounded-full transition-all duration-500" style="width: ${progress}%"></div>
                    </div>
                </td>
                <td class="px-6 py-4 text-right text-sm text-gray-600 dark:text-gray-400 font-medium">${currencyFormatter.format(utang)}</td>
                <td class="px-6 py-4 text-right text-sm text-green-600 font-bold">${currencyFormatter.format(bayar)}</td>
                <td class="px-6 py-4 text-right">
                    <span class="text-sm font-black ${sisa > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400'}">${currencyFormatter.format(sisa)}</span>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <button class="p-2 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition-all pay-btn" 
                                data-id="${row.id}" data-nama="${row.nama_pemasok}" data-sisa="${sisa}" title="Bayar">
                            <i class="bi bi-cash-stack"></i>
                        </button>
                    </div>
                </td>
            `;
            balanceTableBody.appendChild(tr);
        });

        // Update Foot
        const balanceTableFoot = document.getElementById('supplier-balance-table-foot');
        if (balanceTableFoot) {
            let totals = { utang: 0, bayar: 0, sisa: 0 };
            data.forEach(row => {
                totals.utang += parseFloat(row.total_utang);
                totals.bayar += parseFloat(row.total_bayar);
                totals.sisa += parseFloat(row.sisa_utang);
            });
            balanceTableFoot.innerHTML = `
                <tr class="bg-gray-50/50 dark:bg-gray-700/50">
                    <td class="px-6 py-4 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest text-left">Ringkasan Total</td>
                    <td class="px-6 py-4 text-right font-bold text-gray-700 dark:text-gray-300 border-l border-gray-100 dark:border-gray-600">${currencyFormatter.format(totals.utang)}</td>
                    <td class="px-6 py-4 text-right font-bold text-green-600 border-l border-gray-100 dark:border-gray-600">${currencyFormatter.format(totals.bayar)}</td>
                    <td class="px-6 py-4 text-right font-bold text-red-600 border-x border-gray-100 dark:border-gray-600">${currencyFormatter.format(totals.sisa)}</td>
                    <td></td>
                </tr>
            `;
        }
    }

    async function loadData() {
        const start = filterDateMulai.value;
        const end = filterDateAkhir.value;
        
        try {
            const [debtRes, supplierRes, coaRes, historyRes] = await Promise.all([
                fetch(`${basePath}/api/konsinyasi?action=get_debt_summary_report&start_date=${start}&end_date=${end}`),
                fetch(`${basePath}/api/konsinyasi?action=list_suppliers`),
                fetch(`${basePath}/api/coa`),
                fetch(`${basePath}/api/konsinyasi?action=list_payments`)
            ]);

            const debtResult = await debtRes.json();
            const supplierResult = await supplierRes.json();
            const coaResult = await coaRes.json();
            const historyResult = await historyRes.json();

            originalData = debtResult.data || [];
            updateStats(originalData, debtResult.meta);
            applyFilters();

            // 2. Populate Supplier Select
            paySupplierSelect.innerHTML = '<option value="">-- Pilih Pemasok --</option>';
            supplierResult.data.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.nama_pemasok;
                paySupplierSelect.appendChild(opt);
            });

            // 3. Populate Kas Account
            payKasAccountSelect.innerHTML = '<option value="">-- Pilih Sumber Dana --</option>';
            const accounts = Array.isArray(coaResult) ? coaResult : (coaResult.data || []);
            const cashAccounts = accounts.filter(a => a.kode_akun.startsWith('1') || a.is_cash == 1);
            
            (cashAccounts.length > 0 ? cashAccounts : accounts).forEach(a => {
                const opt = document.createElement('option');
                opt.value = a.id;
                opt.textContent = `${a.kode_akun} - ${a.nama_akun}`;
                payKasAccountSelect.appendChild(opt);
            });

            // 4. Render History
            paymentHistoryList.innerHTML = '';
            if (!historyResult.data || historyResult.data.length === 0) {
                paymentHistoryList.innerHTML = '<div class="p-8 text-center text-gray-400 text-xs italic">Belum ada histori</div>';
            } else {
                historyResult.data.slice(0, 10).forEach(h => {
                    const item = document.createElement('div');
                    item.className = 'p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group';
                    item.innerHTML = `
                        <div class="flex justify-between items-start mb-1">
                            <span class="font-bold text-gray-800 dark:text-gray-200">${h.nama_pemasok}</span>
                            <span class="text-green-600 font-bold">${currencyFormatter.format(h.jumlah)}</span>
                        </div>
                        <div class="flex justify-between items-center text-[10px] text-gray-500 uppercase tracking-tighter">
                            <div class="flex items-center gap-1">
                                <i class="bi bi-calendar-event"></i>
                                <span>${new Date(h.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}</span>
                            </div>
                            <span class="truncate ml-4 max-w-[120px] italic border-b border-gray-100 dark:border-gray-700">${h.keterangan || 'Tanpa catatan'}</span>
                        </div>
                    `;
                    paymentHistoryList.appendChild(item);
                });
            }

        } catch (error) {
            showToast('Gagal memuat data: ' + error.message, 'error');
        }
    }

    // Search Filtering
    function applyFilters() {
        const term = searchSupplierInput.value.toLowerCase();
        const onlyDebt = document.getElementById('filter-only-debt').checked;
        
        let filtered = originalData.filter(row => 
            row.nama_pemasok.toLowerCase().includes(term)
        );

        if (onlyDebt) {
            filtered = filtered.filter(row => parseFloat(row.sisa_utang) > 0);
        }

        renderTable(filtered);
    }

    searchSupplierInput.addEventListener('input', applyFilters);
    document.getElementById('filter-only-debt').addEventListener('change', applyFilters);

    // Event delegation for pay button
    balanceTableBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.pay-btn');
        if (btn) {
            const { id, nama, sisa } = btn.dataset;
            if (id === 'null' || !id) {
                // For "Saldo Awal / Penyesuaian Manual" row, we might not have a direct ID in the select
                paySupplierSelect.value = "";
                showToast("Pilih pemasok secara manual untuk penyesuaian.", "info");
            } else {
                paySupplierSelect.value = id;
            }
            document.getElementById('pay-jumlah').value = Math.max(0, parseInt(sisa));
            document.getElementById('pay-jumlah').focus();
            
            // Add a small bounce animation to the form
            payForm.parentElement.classList.add('ring-2', 'ring-primary', 'shadow-lg');
            setTimeout(() => {
                payForm.parentElement.classList.remove('ring-2', 'ring-primary', 'shadow-lg');
            }, 1000);
        }
    });

    payForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Confirmation before processing
        const amount = document.getElementById('pay-jumlah').value;
        const supplierName = paySupplierSelect.options[paySupplierSelect.selectedIndex].text;
        
        const confirm = await Swal.fire({
            title: 'Konfirmasi Pelunasan',
            text: `Apakah Anda yakin ingin melakukan pelunasan senilai ${currencyFormatter.format(amount)} ke ${supplierName}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Proses Sekarang',
            cancelButtonText: 'Batal',
            confirmButtonColor: 'var(--color-primary)'
        });

        if (!confirm.isConfirmed) return;

        const formData = new FormData(payForm);
        formData.append('action', 'pay_debt');
        
        try {
            const response = await fetch(`${basePath}/api/konsinyasi`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                payForm.reset();
                flatpickr("#pay-tanggal", { dateFormat: "Y-m-d", defaultDate: "today" });
                loadData();
            }
        } catch (error) {
            showToast('Gagal memproses pembayaran.', 'error');
        }
    });

    [filterDateMulai, filterDateAkhir].forEach(el => el.addEventListener('change', loadData));

    loadData();
}
