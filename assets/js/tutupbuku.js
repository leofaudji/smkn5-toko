function initTutupBukuPage() {
    const closingDateInput = document.getElementById('closing-date');
    const processBtn = document.getElementById('process-closing-btn');
    const historyContainer = document.getElementById('closing-history-container');

    if (!processBtn) return;

    // Set default date to end of last year
    const lastYear = new Date().getFullYear() - 1;
    closingDateInput.value = `${lastYear}-12-31`;

    async function loadHistory() {
        historyContainer.innerHTML = '<div class="text-center p-4"><div class="spinner-border spinner-border-sm"></div></div>';
        try {
            const response = await fetch(`${basePath}/api/tutup-buku?action=list_history`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            historyContainer.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach(item => {
                    // Tombol batal hanya muncul untuk item paling atas (paling baru)
                    const isLatest = historyContainer.children.length === 0;
                    const batalBtn = isLatest ? `<button class="btn btn-sm btn-outline-warning ms-2 reverse-closing-btn" data-id="${item.id}" title="Batalkan Jurnal Penutup ini"><i class="bi bi-unlock-fill"></i></button>` : '';
                    const historyItem = `
                        <a href="${basePath}/daftar-jurnal#JRN-${item.id}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-journal-id="${item.id}">
                            <span>${item.keterangan} <span class="badge bg-secondary rounded-pill">${new Date(item.tanggal).toLocaleDateString('id-ID')}</span></span>
                            <span>${batalBtn}</span>
                        </a>
                    `;
                    historyContainer.insertAdjacentHTML('beforeend', historyItem);
                });
            } else {
                historyContainer.innerHTML = '<p class="text-center text-muted">Belum ada histori tutup buku.</p>';
            }
        } catch (error) {
            historyContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    processBtn.addEventListener('click', async () => {
        const closingDate = closingDateInput.value;
        if (!closingDate) {
            showToast('Harap pilih tanggal tutup buku.', 'error');
            return;
        }

        if (confirm(`ANDA YAKIN? Proses ini akan membuat Jurnal Penutup untuk periode yang berakhir pada ${closingDate}. Aksi ini tidak dapat dibatalkan dengan mudah.`)) {
            const formData = new FormData();
            formData.append('action', 'process_closing');
            formData.append('closing_date', closingDate);

            const response = await fetch(`${basePath}/api/tutup-buku`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') loadHistory();
        }
    });

    historyContainer.addEventListener('click', async (e) => {
        const reverseBtn = e.target.closest('.reverse-closing-btn');
        if (reverseBtn) {
            e.preventDefault(); // Mencegah navigasi dari link <a>
            e.stopPropagation(); // Mencegah event bubble up ke link <a>

            const id = reverseBtn.dataset.id;
            if (confirm(`ANDA YAKIN? \n\nMembatalkan Jurnal Penutup ini akan membuat Jurnal Pembalik dan membuka kembali periode yang terkunci. \n\nAksi ini hanya bisa dilakukan pada Jurnal Penutup yang paling baru.`)) {
                const formData = new FormData();
                formData.append('action', 'reverse_closing');
                formData.append('id', id);

                const response = await fetch(`${basePath}/api/tutup-buku`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') loadHistory();
            }
        }
    });

    loadHistory();
}

