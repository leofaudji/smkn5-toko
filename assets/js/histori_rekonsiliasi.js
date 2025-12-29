function initHistoriRekonsiliasiPage() {
    const tableBody = document.getElementById('history-recon-table-body');
    if (!tableBody) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    async function loadHistory() {
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center p-5"><div class="flex justify-center"><svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div></td></tr>`;
        try {
            const response = await fetch(`${basePath}/api/histori-rekonsiliasi`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            tableBody.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach(item => {
                    const row = `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">RECON-${String(item.id).padStart(5, '0')}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.nama_akun}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${new Date(item.statement_date).toLocaleDateString('id-ID', { dateStyle: 'long' })}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">${currencyFormatter.format(item.statement_balance)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${new Date(item.created_at).toLocaleString('id-ID')}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right space-x-2">
                                <a href="#" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 print-recon-btn" data-id="${item.id}" title="Cetak PDF">
                                    <i class="bi bi-file-earmark-pdf-fill"></i>
                                </a>
                                <a href="#" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-yellow-500 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 reverse-recon-btn" data-id="${item.id}" title="Batalkan Rekonsiliasi">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada histori rekonsiliasi.</td></tr>';
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-sm text-red-600">Gagal memuat histori: ${error.message}</td></tr>`;
        }
    }

    tableBody.addEventListener('click', async (e) => {
        const printBtn = e.target.closest('.print-recon-btn');
        if (printBtn) {
            e.preventDefault();
            const reconId = printBtn.dataset.id;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `${basePath}/api/pdf`;
            form.target = '_blank';

            const params = {
                report: 'rekonsiliasi',
                id: reconId
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
        }

        const reverseBtn = e.target.closest('.reverse-recon-btn');
        if (reverseBtn) {
            e.preventDefault();
            const reconId = reverseBtn.dataset.id;
            if (confirm(`Anda yakin ingin membatalkan rekonsiliasi RECON-${String(reconId).padStart(5, '0')}? \n\nTransaksi yang terkait akan dikembalikan ke status "belum direkonsiliasi".`)) {
                const formData = new FormData();
                formData.append('action', 'reverse');
                formData.append('id', reconId);

                try {
                    const response = await fetch(`${basePath}/api/histori-rekonsiliasi`, { method: 'POST', body: formData });
                    const result = await response.json();
                    showToast(result.message, result.status);
                    if (result.status === 'success') {
                        loadHistory(); // Muat ulang daftar histori
                    }
                } catch (error) {
                    showToast(`Terjadi kesalahan: ${error.message}`, 'error');
                }
            }
        }
    });
    loadHistory();
}
