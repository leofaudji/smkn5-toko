/**
 * Logika untuk halaman Saldo Awal Terpadu.
 */
function initSaldoAwalPage() {
    const gridBody = document.getElementById('saldo-awal-grid-body');
    const totalDebitEl = document.getElementById('total-debit');
    const totalKreditEl = document.getElementById('total-kredit');
    const totalSelisihEl = document.getElementById('total-selisih');
    const selisihRow = document.getElementById('selisih-row');
    const saveBtn = document.getElementById('save-saldo-awal-btn');

    // Hentikan jika elemen tidak ditemukan (berarti bukan di halaman saldo awal)
    if (!gridBody) return;

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2 }).format(value);
    };

    const calculateTotals = () => {
        let totalDebit = 0;
        let totalKredit = 0;

        gridBody.querySelectorAll('tr').forEach(row => {
            const debitInput = row.querySelector('.debit-input');
            const kreditInput = row.querySelector('.kredit-input');
            if (debitInput) totalDebit += parseFloat(debitInput.value) || 0;
            if (kreditInput) totalKredit += parseFloat(kreditInput.value) || 0;
        });

        totalDebitEl.textContent = formatCurrency(totalDebit);
        totalKreditEl.textContent = formatCurrency(totalKredit);

        const selisih = totalDebit - totalKredit;

        if (Math.abs(selisih) < 0.01) {
            selisihRow.classList.remove('bg-red-100', 'dark:bg-red-900/30', 'text-red-700', 'dark:text-red-200');
            selisihRow.classList.add('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-200');
            totalSelisihEl.textContent = 'Seimbang (Rp 0)';
        } else {
            selisihRow.classList.remove('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-200');
            selisihRow.classList.add('bg-red-100', 'dark:bg-red-900/30', 'text-red-700', 'dark:text-red-200');
            totalSelisihEl.textContent = formatCurrency(selisih);
        }
    };

    const renderGrid = (accounts) => {
        gridBody.innerHTML = '';
        if (!accounts || accounts.length === 0) {
            gridBody.innerHTML = '<tr><td colspan="5" class="text-center p-5 text-gray-500">Tidak ada akun ditemukan.</td></tr>';
            return;
        }

        accounts.forEach(item => {
            const debitValue = parseFloat(item.debit) > 0 ? parseFloat(item.debit) : '';
            const kreditValue = parseFloat(item.kredit) > 0 ? parseFloat(item.kredit) : '';

            const row = document.createElement('tr');
            row.dataset.id = item.id;
            row.innerHTML = `
                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.kode_akun}</td>
                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.nama_akun}</td>
                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.tipe_akun}</td>
                <td class="px-4 py-2">
                    <input type="number" step="0.01" value="${debitValue}"
                           class="debit-input w-full text-right rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                           placeholder="0">
                </td>
                <td class="px-4 py-2">
                    <input type="number" step="0.01" value="${kreditValue}"
                           class="kredit-input w-full text-right rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                           placeholder="0">
                </td>
            `;
            gridBody.appendChild(row);
        });

        // Event delegation untuk input
        gridBody.addEventListener('input', (e) => {
            if (e.target.classList.contains('debit-input') || e.target.classList.contains('kredit-input')) {
                calculateTotals();
            }
        });

        calculateTotals();
    };

    const loadData = async () => {
        try {
            const response = await fetch(`${basePath}/api/saldo-awal`);
            const result = await response.json();
            if (result.status === 'success') {
                renderGrid(result.data);
            } else {
                showToast(result.message || 'Gagal memuat data.', 'error');
            }
        } catch (error) {
            console.error(error);
            showToast('Terjadi kesalahan jaringan.', 'error');
        }
    };

    saveBtn.addEventListener('click', async () => {
        const entries = [];
        gridBody.querySelectorAll('tr').forEach(row => {
            const accountId = row.dataset.id;
            const debit = parseFloat(row.querySelector('.debit-input').value) || 0;
            const kredit = parseFloat(row.querySelector('.kredit-input').value) || 0;

            if (debit > 0 || kredit > 0) {
                entries.push({
                    account_id: accountId,
                    debit: debit,
                    kredit: kredit
                });
            }
        });

        if (entries.length === 0) {
            showToast('Belum ada saldo yang diisi.', 'warning');
            return;
        }

        // Cek keseimbangan di sisi klien
        let totalD = entries.reduce((sum, item) => sum + item.debit, 0);
        let totalK = entries.reduce((sum, item) => sum + item.kredit, 0);
        if (Math.abs(totalD - totalK) > 0.01) {
             Swal.fire({
                title: 'Jurnal Tidak Seimbang',
                text: `Total Debit (Rp ${formatCurrency(totalD)}) tidak sama dengan Total Kredit (Rp ${formatCurrency(totalK)}). Selisih: ${formatCurrency(Math.abs(totalD - totalK))}`,
                icon: 'error'
            });
            return;
        }

        const formData = new FormData();
        entries.forEach((entry, index) => {
            formData.append(`entries[${index}][account_id]`, entry.account_id);
            formData.append(`entries[${index}][debit]`, entry.debit);
            formData.append(`entries[${index}][kredit]`, entry.kredit);
        });

        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white inline-block mr-2"></div> Menyimpan...`;

        try {
            const response = await fetch(`${basePath}/api/saldo-awal`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.status === 'success') {
                showToast(result.message, 'success');
                loadData(); // Muat ulang data untuk memastikan sinkronisasi
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan saat menyimpan.', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
        }
    });

    loadData();
}