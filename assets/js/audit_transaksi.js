window.initAuditTransaksiPage = function() {
    const tableBody = document.getElementById('audit-transaksi-body');
    const loadingRow = document.getElementById('loading-row');
    const emptyState = document.getElementById('empty-state');
    
    if (!tableBody) return;

    function formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }

    function formatDate(dateStr) {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateStr).toLocaleDateString('id-ID', options);
    }

    async function fetchAuditData() {
        const startDate = document.getElementById('filter-start-date').value;
        const endDate = document.getElementById('filter-end-date').value;
        const module = document.getElementById('filter-module').value;
        const statusFilter = document.getElementById('filter-status').value;

        tableBody.innerHTML = '';
        tableBody.appendChild(loadingRow);
        emptyState.classList.add('hidden');

        try {
            const response = await fetch(`${basePath}/api/audit-transaksi?start_date=${startDate}&end_date=${endDate}&module=${module}`);
            const result = await response.json();
            
            tableBody.innerHTML = '';
            
            if (result.status === 'success') {
                let data = result.data;
                
                // Filter by status if needed
                if (statusFilter === 'valid') {
                    data = data.filter(item => item.exists_in_gl == 1);
                } else if (statusFilter === 'missing') {
                    data = data.filter(item => item.exists_in_gl == 0);
                }

                if (data.length === 0) {
                    emptyState.classList.remove('hidden');
                    return;
                }

                data.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors group';
                    
                    const statusIcon = item.exists_in_gl == 1 
                        ? `<div class="inline-flex items-center px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-full text-xs font-bold shadow-sm">
                            <i class="bi bi-check-circle-fill mr-1.5"></i> Terverifikasi
                           </div>`
                        : `<div class="inline-flex items-center px-3 py-1 bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded-full text-xs font-bold shadow-sm animate-pulse">
                            <i class="bi bi-exclamation-triangle-fill mr-1.5"></i> Jurnal Hilang
                           </div>`;

                    const actionBtn = item.exists_in_gl == 1
                        ? `<button disabled class="p-2 text-gray-300 cursor-not-allowed opacity-50"><i class="bi bi-shield-lock"></i></button>`
                        : `<button onclick="repostTransaction('${item.ref_type}', ${item.id})" class="p-2 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 rounded-xl hover:bg-primary-600 hover:text-white transition-all transform hover:scale-110 active:scale-95 shadow-sm" title="Posting Ulang Jurnal">
                            <i class="bi bi-arrow-repeat font-bold"></i>
                           </button>`;

                    tr.innerHTML = `
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-900 dark:text-gray-100">${formatDate(item.tanggal)}</span>
                                <span class="text-[11px] font-mono text-gray-500 dark:text-gray-400 mt-0.5">${item.nomor_referensi}</span>
                                <div class="mt-1.5">
                                    <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded text-[9px] font-black uppercase tracking-wider">${item.module_name}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate">${item.keterangan || '-'}</td>
                        <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white text-right">${formatCurrency(item.amount)}</td>
                        <td class="px-6 py-4 text-center">${statusIcon}</td>
                        <td class="px-6 py-4 text-right">${actionBtn}</td>
                    `;
                    tableBody.appendChild(tr);
                });
            }
        } catch (error) {
            console.error('Error:', error);
            tableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-rose-500">Gagal memuat data.</td></tr>';
        }
    }

    window.repostTransaction = function(refType, refId) {
        Swal.fire({
            title: 'Posting Ulang?',
            text: "Sistem akan membuat ulang jurnal Buku Besar untuk transaksi ini.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0ea5e9',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Posting Ulang',
            cancelButtonText: 'Batal',
            background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#000000'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`${basePath}/api/audit-transaksi`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'repost', ref_type: refType, ref_id: refId })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        if (typeof showToast === 'function') {
                            showToast(result.message, 'success');
                        } else {
                            Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message });
                        }
                        fetchAuditData();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: result.message });
                    }
                });
            }
        });
    };

    const refreshBtn = document.getElementById('refresh-audit');
    const filterBtn = document.getElementById('apply-filters');

    if (refreshBtn) refreshBtn.addEventListener('click', fetchAuditData);
    if (filterBtn) filterBtn.addEventListener('click', fetchAuditData);
    
    // Initial fetch
    fetchAuditData();
};
