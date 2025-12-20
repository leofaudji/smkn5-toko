function initHistoriRekonsiliasiPage() {
    const tableBody = document.getElementById('history-recon-table-body');
    if (!tableBody) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    async function loadHistory() {
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center p-5"><div class="spinner-border"></div></td></tr>`;
        try {
            const response = await fetch(`${basePath}/api/histori-rekonsiliasi`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            tableBody.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach(item => {
                    const row = `
                        <tr>
                            <td>RECON-${String(item.id).padStart(5, '0')}</td>
                            <td>${item.nama_akun}</td>
                            <td>${new Date(item.statement_date).toLocaleDateString('id-ID', { dateStyle: 'long' })}</td>
                            <td class="text-end">${currencyFormatter.format(item.statement_balance)}</td>
                            <td>${new Date(item.created_at).toLocaleString('id-ID')}</td>
                            <td class="text-end">
                                <a href="#" class="btn btn-sm btn-danger print-recon-btn" data-id="${item.id}" title="Cetak PDF">
                                    <i class="bi bi-file-earmark-pdf-fill"></i>                                </a>
                                <a href="#" class="btn btn-sm btn-warning reverse-recon-btn" data-id="${item.id}" title="Batalkan Rekonsiliasi">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Belum ada histori rekonsiliasi.</td></tr>';
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Gagal memuat histori: ${error.message}</td></tr>`;
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

