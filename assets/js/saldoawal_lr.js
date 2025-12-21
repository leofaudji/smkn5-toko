function initSaldoAwalLRPage() {
    const gridBody = document.getElementById('saldo-lr-grid-body');
    const saveBtn = document.getElementById('save-saldo-lr-btn');
    const form = document.getElementById('saldo-lr-form');

    if (!gridBody || !saveBtn || !form) return;

    async function renderGrid() {
        gridBody.innerHTML = '<tr><td colspan="4" class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>';
        try {
            const response = await fetch(`${basePath}/api/saldo-awal-lr`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);
             
            gridBody.innerHTML = '';
            result.data.forEach((acc, index) => {
                const saldo = parseFloat(acc.saldo_awal) || 0;

                const row = `
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                            <input type="hidden" name="entries[${index}][account_id]" value="${acc.id}">
                            ${acc.kode_akun}
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">${acc.nama_akun}</td>
                        <td class="px-4 py-2"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium ${acc.tipe_akun === 'Pendapatan' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">${acc.tipe_akun}</span></td>
                        <td class="px-4 py-2"><input type="number" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm text-right" name="entries[${index}][saldo]" value="${saldo}" step="any"></td>
                    </tr>
                `;
                gridBody.insertAdjacentHTML('beforeend', row);
            });
        } catch (error) {
            gridBody.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 py-4">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;

        try {
            const response = await fetch(`${basePath}/api/saldo-awal-lr`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                renderGrid(); // Reload grid to confirm changes
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
        }
    });

    renderGrid();
}