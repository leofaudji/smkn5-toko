function initPelunasanKonsinyasiPage() {
    const balanceTableBody = document.getElementById('supplier-balance-table-body');
    const paySupplierSelect = document.getElementById('pay-supplier-id');
    const payKasAccountSelect = document.getElementById('pay-kas-account');
    const payForm = document.getElementById('payment-form');
    const paymentHistoryList = document.getElementById('payment-history-list');
    
    const filterDateMulai = document.getElementById('filter-date-mulai');
    const filterDateAkhir = document.getElementById('filter-date-akhir');

    if (!balanceTableBody) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    // Initialize Flatpickr
    flatpickr(filterDateMulai, { dateFormat: "Y-m-d", defaultDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1) });
    flatpickr(filterDateAkhir, { dateFormat: "Y-m-d", defaultDate: "today" });
    flatpickr("#pay-tanggal", { dateFormat: "Y-m-d", defaultDate: "today" });

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

            // 1. Render Balances
            balanceTableBody.innerHTML = '';
            const balanceTableFoot = document.getElementById('supplier-balance-table-foot');
            let totals = { utang: 0, bayar: 0, sisa: 0 };

            if (debtResult.data.length === 0) {
                balanceTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-500">Tidak ada data utang.</td></tr>';
                if (balanceTableFoot) balanceTableFoot.innerHTML = '';
            } else {
                debtResult.data.forEach(row => {
                    const utang = parseFloat(row.total_utang);
                    const bayar = parseFloat(row.total_bayar);
                    const sisa = parseFloat(row.sisa_utang);
                    
                    totals.utang += utang;
                    totals.bayar += bayar;
                    totals.sisa += sisa;

                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-700/50';
                    tr.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${row.nama_pemasok}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">${currencyFormatter.format(utang)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">${currencyFormatter.format(bayar)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-bold">${currencyFormatter.format(sisa)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                            <button class="text-primary hover:text-primary-700 pay-btn" data-id="${row.id}" data-nama="${row.nama_pemasok}" data-sisa="${sisa}" title="Bayar">
                                <i class="bi bi-cash-coin text-xl"></i>
                            </button>
                        </td>
                    `;
                    balanceTableBody.appendChild(tr);
                });

                if (balanceTableFoot) {
                    balanceTableFoot.innerHTML = `
                        <tr>
                            <td class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">TOTAL KESELURUHAN</td>
                            <td class="px-6 py-3 text-right text-sm text-gray-900 dark:text-white">${currencyFormatter.format(totals.utang)}</td>
                            <td class="px-6 py-3 text-right text-sm text-green-600">${currencyFormatter.format(totals.bayar)}</td>
                            <td class="px-6 py-3 text-right text-sm text-red-600">${currencyFormatter.format(totals.sisa)}</td>
                            <td class="px-6 py-3"></td>
                        </tr>
                    `;
                }
            }

            // 2. Populate Supplier Select
            paySupplierSelect.innerHTML = '<option value="">Pilih Pemasok</option>';
            supplierResult.data.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.nama_pemasok;
                paySupplierSelect.appendChild(opt);
            });

            // 3. Populate Kas Account
            payKasAccountSelect.innerHTML = '<option value="">Pilih Sumber Dana</option>';
            // The COA API might return data directly or wrapped in a 'data' property
            const accounts = Array.isArray(coaResult) ? coaResult : (coaResult.data || []);
            
            // Filter for assets/cash/bank accounts (usually starting with 11) or just show all active accounts
            const cashAccounts = accounts.filter(a => a.kode_akun.startsWith('1') || a.is_cash === 1);
            
            if (cashAccounts.length === 0 && accounts.length > 0) {
                // Fallback: if filter is too strict, show all
                accounts.forEach(a => {
                    const opt = document.createElement('option');
                    opt.value = a.id;
                    opt.textContent = `${a.kode_akun} - ${a.nama_akun}`;
                    payKasAccountSelect.appendChild(opt);
                });
            } else {
                cashAccounts.forEach(a => {
                    const opt = document.createElement('option');
                    opt.value = a.id;
                    opt.textContent = `${a.kode_akun} - ${a.nama_akun}`;
                    payKasAccountSelect.appendChild(opt);
                });
            }

            // 4. Render History
            paymentHistoryList.innerHTML = '';
            if (historyResult.data.length === 0) {
                paymentHistoryList.innerHTML = '<p class="text-sm text-gray-500 text-center py-2">Belum ada riwayat bayar.</p>';
            } else {
                historyResult.data.slice(0, 5).forEach(h => {
                    const div = document.createElement('div');
                    div.className = 'p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm';
                    div.innerHTML = `
                        <div class="flex justify-between font-medium">
                            <span>${h.nama_pemasok}</span>
                            <span class="text-green-600">${currencyFormatter.format(h.jumlah)}</span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>${new Date(h.tanggal).toLocaleDateString('id-ID')}</span>
                            <span class="truncate ml-4">${h.keterangan || ''}</span>
                        </div>
                    `;
                    paymentHistoryList.appendChild(div);
                });
            }

        } catch (error) {
            showToast('Gagal memuat data: ' + error.message, 'error');
        }
    }

    // Event delegation for pay button
    balanceTableBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.pay-btn');
        if (btn) {
            const { id, nama, sisa } = btn.dataset;
            paySupplierSelect.value = id;
            document.getElementById('pay-jumlah').value = Math.max(0, parseFloat(sisa));
            document.getElementById('pay-jumlah').focus();
        }
    });

    payForm.addEventListener('submit', async (e) => {
        e.preventDefault();
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
