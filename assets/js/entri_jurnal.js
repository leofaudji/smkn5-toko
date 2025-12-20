function initEntriJurnalPage() {
    const form = document.getElementById('entri-jurnal-form');
    const linesBody = document.getElementById('jurnal-lines-body');
    const addLineBtn = document.getElementById('add-jurnal-line-btn');
    const saveAsRecurringBtn = document.getElementById('save-as-recurring-btn');


    if (!form) return;

    let allAccounts = [];
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
        select.className = 'form-select form-select-sm';
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
            <td></td>
            <td><input type="number" name="lines[${index}][debit]" class="form-control form-control-sm text-end debit-input" value="0" step="any"></td>
            <td><input type="number" name="lines[${index}][kredit]" class="form-control form-control-sm text-end kredit-input" value="0" step="any"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-line-btn"><i class="bi bi-trash-fill"></i></button></td>
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
            totalDebitEl.classList.add('text-success');
            totalKreditEl.classList.add('text-success');
        } else {
            totalDebitEl.classList.remove('text-success');
            totalKreditEl.classList.remove('text-success');
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
        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;
        
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
            document.querySelector('.h2').innerHTML = `<i class="bi bi-pencil-square"></i> Edit Entri Jurnal (ID: JRN-${String(id).padStart(5, '0')})`;
            document.getElementById('jurnal-id').value = header.id;
            document.getElementById('jurnal-action').value = 'update';
            document.getElementById('jurnal-tanggal').value = header.tanggal;
            document.getElementById('jurnal-keterangan').value = header.keterangan;

            linesBody.innerHTML = '';
            details.forEach((line, index) => {
                const tr = document.createElement('tr');
                const select = createAccountSelect(line.account_id);
                select.name = `lines[${index}][account_id]`;

                tr.innerHTML = `
                    <td></td>
                    <td><input type="number" name="lines[${index}][debit]" class="form-control form-control-sm text-end debit-input" value="${line.debit}" step="any"></td>
                    <td><input type="number" name="lines[${index}][kredit]" class="form-control form-control-sm text-end kredit-input" value="${line.kredit}" step="any"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-line-btn"><i class="bi bi-trash-fill"></i></button></td>
                `;
                tr.querySelector('td').appendChild(select);
                linesBody.appendChild(tr);
            });
            calculateTotals();
        } catch (error) {
            showToast(`Gagal memuat data jurnal untuk diedit: ${error.message}`, 'error');
            linesBody.innerHTML = `<tr><td colspan="4" class="alert alert-danger">${error.message}</td></tr>`;
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
                document.getElementById('jurnal-tanggal').valueAsDate = new Date();
                addJurnalLine(); addJurnalLine();
            }
        }
    });
}