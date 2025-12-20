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
            totalDebitEl.classList.add('text-success');
            totalKreditEl.classList.add('text-success');
            totalDebitEl.classList.remove('text-danger');
            totalKreditEl.classList.remove('text-danger');
        } else {
            totalDebitEl.classList.remove('text-success');
            totalKreditEl.classList.remove('text-success');
            if (totalDebit !== totalCredit) {
                totalDebitEl.classList.add('text-danger');
                totalKreditEl.classList.add('text-danger');
            } else {
                totalDebitEl.classList.remove('text-danger');
                totalKreditEl.classList.remove('text-danger');
            }
        }
    }

    async function renderGrid() {
        gridBody.innerHTML = '<tr><td colspan="4" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
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
                        <td>
                            <input type="hidden" name="entries[${index}][account_id]" value="${acc.id}">
                            ${acc.kode_akun}
                        </td>
                        <td>${acc.nama_akun}</td>
                        <td><input type="number" class="form-control form-control-sm text-end debit-input" name="entries[${index}][debit]" value="${debitValue}" step="any"></td>
                        <td><input type="number" class="form-control form-control-sm text-end credit-input" name="entries[${index}][credit]" value="${creditValue}" step="any"></td>
                    </tr>
                `;
                gridBody.insertAdjacentHTML('beforeend', row);
            });
            calculateTotals();
        } catch (error) {
            gridBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Gagal memuat data: ${error.message}</td></tr>`;
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
        saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;

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