function initSaldoAwalNeracaPage() {
    const gridBody = document.getElementById('jurnal-grid-body');
    const saveBtn = document.getElementById('save-jurnal-btn');
    const form = document.getElementById('jurnal-form');

    if (!gridBody || !saveBtn || !form) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    function calculateTotals() {
        let totalDebit = 0;
        let totalCredit = 0;
        gridBody.querySelectorAll('tr').forEach(row => {
            totalDebit += parseFloat(row.querySelector('.debit-input').value) || 0;
            totalCredit += parseFloat(row.querySelector('.credit-input').value) || 0;
        });

        document.getElementById('total-debit').textContent = currencyFormatter.format(totalDebit);
        document.getElementById('total-kredit').textContent = currencyFormatter.format(totalCredit);

        const totalDebitEl = document.getElementById('total-debit');
        const totalKreditEl = document.getElementById('total-kredit');

        if (Math.abs(totalDebit - totalCredit) < 0.01 && totalDebit > 0) {
            totalDebitEl.classList.add('text-green-600', 'dark:text-green-400');
            totalKreditEl.classList.add('text-green-600', 'dark:text-green-400');
            totalDebitEl.classList.remove('text-red-600', 'dark:text-red-400');
            totalKreditEl.classList.remove('text-red-600', 'dark:text-red-400');
        } else {
            totalDebitEl.classList.remove('text-green-600', 'dark:text-green-400');
            totalKreditEl.classList.remove('text-green-600', 'dark:text-green-400');
            if (totalDebit !== totalCredit) {
                totalDebitEl.classList.add('text-red-600', 'dark:text-red-400');
                totalKreditEl.classList.add('text-red-600', 'dark:text-red-400');
            } else {
                totalDebitEl.classList.remove('text-red-600', 'dark:text-red-400');
                totalKreditEl.classList.remove('text-red-600', 'dark:text-red-400');
            }
        } 
    }

    async function renderGrid() {
        gridBody.innerHTML = '<tr><td colspan="4" class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>';
        try {
            const response = await fetch(`${basePath}/api/saldo-awal-neraca`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);
            
            gridBody.innerHTML = '';
            result.data.forEach((acc, index) => {
                const saldo = parseFloat(acc.saldo_awal);
                const debitValue = acc.saldo_normal === 'Debit' && saldo > 0 ? saldo : 0;
                const creditValue = acc.saldo_normal === 'Kredit' && saldo > 0 ? saldo : 0;

                const row = `
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                            <input type="hidden" name="entries[${index}][account_id]" value="${acc.id}">
                            ${acc.kode_akun}
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">${acc.nama_akun}</td>
                        <td class="px-4 py-2"><input type="number" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm text-right debit-input" name="entries[${index}][debit]" value="${debitValue}" step="any"></td>
                        <td class="px-4 py-2"><input type="number" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm text-right credit-input" name="entries[${index}][credit]" value="${creditValue}" step="any"></td>
                    </tr>
                `;
                gridBody.insertAdjacentHTML('beforeend', row);
            });
            calculateTotals();
        } catch (error) {
            gridBody.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 py-4">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    gridBody.addEventListener('input', (e) => {
        if (e.target.matches('.debit-input, .credit-input')) {
            calculateTotals();
        }
    });

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;

        try {
            const response = await fetch(`${basePath}/api/saldo-awal-neraca`, { method: 'POST', body: formData });
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