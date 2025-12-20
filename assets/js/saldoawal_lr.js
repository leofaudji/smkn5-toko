function initSaldoAwalLRPage() {
    const gridBody = document.getElementById('saldo-lr-grid-body');
    const saveBtn = document.getElementById('save-saldo-lr-btn');
    const form = document.getElementById('saldo-lr-form');

    if (!gridBody || !saveBtn || !form) return;

    async function renderGrid() {
        gridBody.innerHTML = '<tr><td colspan="4" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
        try {
            const response = await fetch(`${basePath}/api/saldo-awal-lr`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);
            
            gridBody.innerHTML = '';
            result.data.forEach((acc, index) => {
                const saldo = parseFloat(acc.saldo_awal) || 0;

                const row = `
                    <tr>
                        <td>
                            <input type="hidden" name="entries[${index}][account_id]" value="${acc.id}">
                            ${acc.kode_akun}
                        </td>
                        <td>${acc.nama_akun}</td>
                        <td><span class="badge bg-${acc.tipe_akun === 'Pendapatan' ? 'success' : 'danger'}">${acc.tipe_akun}</span></td>
                        <td><input type="number" class="form-control form-control-sm text-end" name="entries[${index}][saldo]" value="${saldo}" step="any"></td>
                    </tr>
                `;
                gridBody.insertAdjacentHTML('beforeend', row);
            });
        } catch (error) {
            gridBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;

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