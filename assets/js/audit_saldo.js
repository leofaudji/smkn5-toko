window.initAuditSaldoPage = function() {
    const tableBody = document.getElementById('audit-table-body');
    const refreshBtn = document.getElementById('refresh-audit');

    if (!tableBody || !refreshBtn) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    });

    async function loadAuditData() {
        tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-10 text-center"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div><p class="mt-2 text-sm text-gray-500">Menganalisis data...</p></td></tr>`;

        try {
            const response = await fetch(`${basePath}/api/audit_handler.php`);
            const result = await response.json();

            if (result.status === 'success') {
                renderAuditTable(result.data);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-10 text-center text-red-500">Gagal memuat data audit: ${error.message}</td></tr>`;
        }
    }

    function renderAuditTable(data) {
        tableBody.innerHTML = '';
        data.forEach(item => {
            const isMatch = Math.abs(item.diff) < 1;
            const statusClass = isMatch ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            const statusText = isMatch ? 'Cocok' : 'Selisih';

            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors';
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.module}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.account}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">${currencyFormatter.format(item.sub_ledger)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">${currencyFormatter.format(item.gl_balance)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right ${isMatch ? 'text-gray-900 dark:text-white' : 'text-red-600 font-bold'}">${currencyFormatter.format(item.diff)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">${statusText}</span>
                </td>
            `;
            tableBody.appendChild(row);
        });
    }

    refreshBtn.addEventListener('click', loadAuditData);

    // Initial load
    loadAuditData();
};

