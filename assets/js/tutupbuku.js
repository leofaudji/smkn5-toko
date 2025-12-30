function initTutupBukuPage() {
    const closingDateInput = document.getElementById('closing-date');
    const processBtn = document.getElementById('process-closing-btn');
    const historyContainer = document.getElementById('closing-history-container');

    if (!processBtn) return;

    const closingDatePicker = flatpickr(closingDateInput, { dateFormat: "d-m-Y", allowInput: true });

    // Set default date to end of last year
    const lastYear = new Date().getFullYear() - 1;
    closingDatePicker.setDate(`${lastYear}-12-31`, true, "Y-m-d");

    async function loadHistory() {
        historyContainer.innerHTML = '<div class="text-center p-4"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary mx-auto"></div></div>';
        try {
            const response = await fetch(`${basePath}/api/tutup-buku?action=list_history`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            historyContainer.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach(item => {
                    // Tombol batal hanya muncul untuk item paling atas (paling baru)
                    const isLatest = historyContainer.children.length === 0;
                    const batalBtn = isLatest ? `<button class="inline-flex items-center px-2 py-1 border border-yellow-400 text-xs font-medium rounded text-yellow-500 hover:bg-yellow-50 dark:hover:bg-yellow-400/10 ml-2 reverse-closing-btn" data-id="${item.id}" title="Batalkan Jurnal Penutup ini"><i class="bi bi-unlock-fill"></i></button>` : '';
                    const historyItem = `
                        <a href="${basePath}/daftar-jurnal#JRN-${item.id}" class="flex justify-between items-center p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-sm" data-journal-id="${item.id}">
                            <span class="text-gray-800 dark:text-gray-200">${item.keterangan} <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">${new Date(item.tanggal).toLocaleDateString('id-ID')}</span></span>
                            <span>${batalBtn}</span>
                        </a>
                    `;
                    historyContainer.insertAdjacentHTML('beforeend', historyItem);
                });
            } else {
                historyContainer.innerHTML = '<p class="text-center text-gray-500 dark:text-gray-400">Belum ada histori tutup buku.</p>';
            }
        } catch (error) {
            historyContainer.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">${error.message}</div>`;
        }
    }

    processBtn.addEventListener('click', async () => {
        const closingDate = closingDateInput.value.split('-').reverse().join('-');
        if (!closingDate) {
            showToast('Harap pilih tanggal tutup buku.', 'error');
            return;
        }

        if (confirm(`ANDA YAKIN? Proses ini akan membuat Jurnal Penutup untuk periode yang berakhir pada ${closingDate}. Aksi ini tidak dapat dibatalkan dengan mudah.`)) {
            const formData = new FormData();
            formData.append('action', 'process_closing');
            formData.append('closing_date', closingDate);

            const originalBtnHtml = processBtn.innerHTML;
            processBtn.disabled = true;
            processBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...`;

            const response = await fetch(`${basePath}/api/tutup-buku`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') loadHistory();
            processBtn.disabled = false;
            processBtn.innerHTML = originalBtnHtml;
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

                const originalBtnHtml = reverseBtn.innerHTML;
                reverseBtn.disabled = true;
                reverseBtn.innerHTML = `<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-yellow-500"></div>`;

                const response = await fetch(`${basePath}/api/tutup-buku`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') loadHistory();
                reverseBtn.disabled = false;
                reverseBtn.innerHTML = originalBtnHtml;
            }
        }
    });

    loadHistory();
}
