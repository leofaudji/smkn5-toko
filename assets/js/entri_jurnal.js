function initEntriJurnalPage() {
    const form = document.getElementById('entri-jurnal-form');
    const linesBody = document.getElementById('jurnal-lines-body');
    const addLineBtn = document.getElementById('add-jurnal-line-btn');
    const saveAsRecurringBtn = document.getElementById('save-as-recurring-btn');


    if (!form) return;

    let allAccounts = [];
    // Inisialisasi Flatpickr
    const tanggalPicker = flatpickr("#jurnal-tanggal", { dateFormat: "d-m-Y", allowInput: true });

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    async function fetchAccounts() {
        try {
            const response = await fetch(`${basePath}/api/coa`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);
            allAccounts = result.data;
        } catch (error) {
            showToast(`Gagal memuat akun: ${error.message}`, 'error');
        }
    }

    function createAccountSelect(selectedValue = '') {
        const select = document.createElement('select');
        select.className = 'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm';
        select.innerHTML = '<option value="">-- Pilih Akun --</option>';
        allAccounts.forEach(acc => {
            const option = new Option(`${acc.kode_akun} - ${acc.nama_akun}`, acc.id);
            if (acc.id == selectedValue) option.selected = true;
            select.add(option);
        });
        return select;
    }

    function addJurnalLine() {
        const index = linesBody.children.length;
        const tr = document.createElement('tr');
        const select = createAccountSelect(); // No selected value for new line
        select.name = `lines[${index}][account_id]`;

        tr.innerHTML = `
            <td class="px-4 py-2"></td>
            <td class="px-4 py-2"><input type="number" name="lines[${index}][debit]" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm text-right debit-input" value="0" step="any"></td>
            <td class="px-4 py-2"><input type="number" name="lines[${index}][kredit]" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm text-right kredit-input" value="0" step="any"></td>
            <td class="px-4 py-2 text-center"><button type="button" class="inline-flex items-center p-1 border border-transparent rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 remove-line-btn"><i class="bi bi-trash-fill"></i></button></td>
        `;
        tr.querySelector('td').appendChild(select);
        linesBody.appendChild(tr);
    }

    function calculateTotals() {
        let totalDebit = 0;
        let totalKredit = 0;
        linesBody.querySelectorAll('tr').forEach(row => {
            totalDebit += parseFloat(row.querySelector('.debit-input').value) || 0;
            totalKredit += parseFloat(row.querySelector('.kredit-input').value) || 0;
        });

        const totalDebitEl = document.getElementById('total-jurnal-debit');
        const totalKreditEl = document.getElementById('total-jurnal-kredit');
        totalDebitEl.textContent = currencyFormatter.format(totalDebit);
        totalKreditEl.textContent = currencyFormatter.format(totalKredit);

        if (Math.abs(totalDebit - totalKredit) < 0.01 && totalDebit > 0) {
            totalDebitEl.classList.add('text-green-600', 'dark:text-green-400');
            totalKreditEl.classList.add('text-green-600', 'dark:text-green-400');
        } else {
            totalDebitEl.classList.remove('text-green-600', 'dark:text-green-400');
            totalKreditEl.classList.remove('text-green-600', 'dark:text-green-400');
        }
    }

    addLineBtn.addEventListener('click', addJurnalLine);

    linesBody.addEventListener('click', e => {
        if (e.target.closest('.remove-line-btn')) {
            e.target.closest('tr').remove();
            calculateTotals();
        }
    });

    linesBody.addEventListener('input', e => {
        if (e.target.matches('.debit-input, .kredit-input')) {
            calculateTotals();
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const action = document.getElementById('jurnal-action').value || 'add';
        const saveBtn = document.getElementById('save-jurnal-entry-btn');
        const formData = new FormData(form); // The action is now correctly set from the hidden input

        // Ambil tanggal dari flatpickr dan format untuk DB
        const selectedDate = tanggalPicker.selectedDates[0];
        if (selectedDate) {
            const year = selectedDate.getFullYear();
            const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
            const day = String(selectedDate.getDate()).padStart(2, '0');
            formData.set('tanggal', `${year}-${month}-${day}`);
        }

        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;
        
        try {
            const response = await fetch(`${basePath}/api/entri-jurnal`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                // Setelah add atau update berhasil, reset form ke kondisi awal untuk entri baru.
                // Navigasi ke halaman daftar jurnal dihapus sesuai permintaan.
                const newUrl = `${window.location.origin}${basePath}/entri-jurnal`;
                navigate(newUrl);
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
        }
    });

    saveAsRecurringBtn.addEventListener('click', () => {
        // Validasi form jurnal dulu
        const keterangan = document.getElementById('jurnal-keterangan').value;
        if (!keterangan) {
            showToast('Keterangan jurnal wajib diisi sebelum membuat template.', 'error');
            return;
        }

        // Kumpulkan data baris jurnal
        const lines = [];
        linesBody.querySelectorAll('tr').forEach(row => {
            const account_id = row.querySelector('select').value;
            const debit = parseFloat(row.querySelector('.debit-input').value) || 0;
            const kredit = parseFloat(row.querySelector('.kredit-input').value) || 0;
            if (account_id && (debit > 0 || kredit > 0)) {
                lines.push({ account_id, debit, kredit });
            }
        });

        if (lines.length < 2) {
            showToast('Template harus memiliki minimal 2 baris jurnal.', 'error');
            return;
        }

        const templateData = { keterangan, lines };

        // Buka modal recurring
        openRecurringModal('jurnal', templateData);
    });

    async function loadJournalForEdit(id) {
        try {
            const response = await fetch(`${basePath}/api/entri-jurnal?action=get_single&id=${id}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const { header, details } = result.data;
            document.getElementById('page-title').innerHTML = `<i class="bi bi-pencil-square"></i> Edit Entri Jurnal (ID: JRN-${String(id).padStart(5, '0')})`;
            document.getElementById('jurnal-id').value = header.id;
            document.getElementById('jurnal-action').value = 'update';
            tanggalPicker.setDate(header.tanggal, true, "Y-m-d");
            document.getElementById('jurnal-keterangan').value = header.keterangan;

            linesBody.innerHTML = '';
            details.forEach((line, index) => {
                const tr = document.createElement('tr');
                const select = createAccountSelect(line.account_id);
                select.name = `lines[${index}][account_id]`;

                tr.innerHTML = `
                    <td class="px-4 py-2"></td>
                    <td class="px-4 py-2"><input type="number" name="lines[${index}][debit]" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm text-right debit-input" value="${line.debit}" step="any"></td>
                    <td class="px-4 py-2"><input type="number" name="lines[${index}][kredit]" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm text-right kredit-input" value="${line.kredit}" step="any"></td>
                    <td class="px-4 py-2 text-center"><button type="button" class="inline-flex items-center p-1 border border-transparent rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 remove-line-btn"><i class="bi bi-trash-fill"></i></button></td>
                `;
                tr.querySelector('td').appendChild(select);
                linesBody.appendChild(tr);
            });
            calculateTotals();
        } catch (error) {
            showToast(`Gagal memuat data jurnal untuk diedit: ${error.message}`, 'error');
            linesBody.innerHTML = `<tr><td colspan="4" class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-200 p-4 text-center">${error.message}</td></tr>`;
        }
    }

    // Initial setup
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit_id');

    fetchAccounts().then(() => {
        // Pastikan elemen ada sebelum diakses
        if (document.getElementById('jurnal-tanggal')) {
            if (editId) {
                loadJournalForEdit(editId);            
            } else {
                tanggalPicker.setDate(new Date(), true);
                addJurnalLine(); addJurnalLine();
            }
        }
    });
}