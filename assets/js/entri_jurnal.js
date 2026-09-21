function initEntriJurnalPage() {
    const form = document.getElementById('entri-jurnal-form');
    const linesBody = document.getElementById('jurnal-lines-body');
    const addLineBtn = document.getElementById('add-jurnal-line-btn');
    const saveAsRecurringBtn = document.getElementById('save-as-recurring-btn');
    const saveBtn = document.getElementById('save-jurnal-entry-btn');
    const saveBtnText = document.getElementById('save-btn-text');
    const saveBtnIcon = document.getElementById('save-btn-icon');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');
    const alertCancelEditBtn = document.getElementById('alert-cancel-edit-btn');
    const headerCancelEditBtn = document.getElementById('header-cancel-edit-btn');
    const editModeAlert = document.getElementById('edit-mode-alert');
    const editAlertTitle = document.getElementById('edit-alert-title');
    const editBadge = document.getElementById('edit-badge');
    const formCardTitle = document.getElementById('form-card-title');
    const formCard = document.getElementById('entri-jurnal-card');

    // Tab navigation elements
    const tabEntriBtn = document.getElementById('tab-entri-btn');
    const tabRiwayatBtn = document.getElementById('tab-riwayat-btn');
    const paneEntri = document.getElementById('pane-entri');
    const paneRiwayat = document.getElementById('pane-riwayat');
    const riwayatCountBadge = document.getElementById('riwayat-count-badge');

    // Recent journals elements
    const recentTableBody = document.getElementById('recent-jurnal-table-body');
    const searchRecentInput = document.getElementById('search-recent-jurnal');
    const filterRecentMulai = document.getElementById('filter-recent-mulai');
    const filterRecentAkhir = document.getElementById('filter-recent-akhir');
    const filterRecentLimit = document.getElementById('filter-recent-limit');
    const btnRecentReset = document.getElementById('btn-recent-reset');
    const refreshRecentBtn = document.getElementById('refresh-recent-jurnals-btn');
    const recentPaginationContainer = document.getElementById('recent-jurnal-pagination');
    const recentPaginationInfo = document.getElementById('recent-jurnal-pagination-info');

    if (!form) return;

    // Tab Switching Function
    function switchTab(targetTab) {
        if (!paneEntri || !paneRiwayat || !tabEntriBtn || !tabRiwayatBtn) return;

        if (targetTab === 'riwayat') {
            paneEntri.classList.add('hidden');
            paneRiwayat.classList.remove('hidden');

            tabEntriBtn.classList.remove('border-primary', 'text-primary', 'font-semibold');
            tabEntriBtn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300', 'dark:text-gray-400', 'dark:hover:text-gray-200', 'font-medium');
            tabEntriBtn.setAttribute('aria-selected', 'false');

            tabRiwayatBtn.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300', 'dark:text-gray-400', 'dark:hover:text-gray-200', 'font-medium');
            tabRiwayatBtn.classList.add('border-primary', 'text-primary', 'font-semibold');
            tabRiwayatBtn.setAttribute('aria-selected', 'true');

            // Segarkan riwayat jurnal
            loadRecentJournals(1);
        } else {
            paneRiwayat.classList.add('hidden');
            paneEntri.classList.remove('hidden');

            tabRiwayatBtn.classList.remove('border-primary', 'text-primary', 'font-semibold');
            tabRiwayatBtn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300', 'dark:text-gray-400', 'dark:hover:text-gray-200', 'font-medium');
            tabRiwayatBtn.setAttribute('aria-selected', 'false');

            tabEntriBtn.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300', 'dark:text-gray-400', 'dark:hover:text-gray-200', 'font-medium');
            tabEntriBtn.classList.add('border-primary', 'text-primary', 'font-semibold');
            tabEntriBtn.setAttribute('aria-selected', 'true');
        }
    }

    if (tabEntriBtn) {
        tabEntriBtn.addEventListener('click', (e) => {
            e.preventDefault();
            switchTab('entri');
        });
    }

    if (tabRiwayatBtn) {
        tabRiwayatBtn.addEventListener('click', (e) => {
            e.preventDefault();
            switchTab('riwayat');
        });
    }

    let allAccounts = [];
    let recentStartDatePicker = null;
    let recentEndDatePicker = null;
    let searchDebounceTimeout = null;

    // Inisialisasi Flatpickr Tanggal Entri
    const tanggalPicker = flatpickr("#jurnal-tanggal", { dateFormat: "d-m-Y", allowInput: true });

    // Inisialisasi Flatpickr Filter Riwayat jika ada elemennya
    if (filterRecentMulai && filterRecentAkhir) {
        const filterOptions = { dateFormat: "d-m-Y", allowInput: true };
        recentStartDatePicker = flatpickr(filterRecentMulai, filterOptions);
        recentEndDatePicker = flatpickr(filterRecentAkhir, filterOptions);
    }

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

    function addJurnalLine(accountId = '', debit = 0, kredit = 0) {
        const index = Date.now() + Math.random().toString(36).substr(2, 5);
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors';
        const select = createAccountSelect(accountId);
        select.name = `lines[${index}][account_id]`;
        select.required = true;

        const debitVal = parseFloat(debit) || 0;
        const kreditVal = parseFloat(kredit) || 0;

        tr.innerHTML = `
            <td class="px-4 py-2"></td>
            <td class="px-4 py-2">
                <input type="number" name="lines[${index}][debit]" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm text-right debit-input" value="${debitVal}" step="any" min="0">
            </td>
            <td class="px-4 py-2">
                <input type="number" name="lines[${index}][kredit]" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm text-right kredit-input" value="${kreditVal}" step="any" min="0">
            </td>
            <td class="px-4 py-2 text-center">
                <button type="button" class="inline-flex items-center p-1.5 border border-transparent rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 remove-line-btn transition-colors" title="Hapus Baris">
                    <i class="bi bi-trash-fill text-xs"></i>
                </button>
            </td>
        `;
        tr.querySelector('td').appendChild(select);
        linesBody.appendChild(tr);
        calculateTotals();
    }

    function calculateTotals() {
        let totalDebit = 0;
        let totalKredit = 0;
        linesBody.querySelectorAll('tr').forEach(row => {
            const debitInput = row.querySelector('.debit-input');
            const kreditInput = row.querySelector('.kredit-input');
            if (debitInput) totalDebit += parseFloat(debitInput.value) || 0;
            if (kreditInput) totalKredit += parseFloat(kreditInput.value) || 0;
        });

        const totalDebitEl = document.getElementById('total-jurnal-debit');
        const totalKreditEl = document.getElementById('total-jurnal-kredit');
        const balanceStatusEl = document.getElementById('jurnal-balance-status');

        if (totalDebitEl) totalDebitEl.textContent = currencyFormatter.format(totalDebit);
        if (totalKreditEl) totalKreditEl.textContent = currencyFormatter.format(totalKredit);

        const diff = Math.abs(totalDebit - totalKredit);
        const isBalanced = diff < 0.01 && totalDebit > 0;

        if (isBalanced) {
            if (totalDebitEl) {
                totalDebitEl.classList.add('text-green-600', 'dark:text-green-400');
                totalDebitEl.classList.remove('text-red-600', 'dark:text-red-400');
            }
            if (totalKreditEl) {
                totalKreditEl.classList.add('text-green-600', 'dark:text-green-400');
                totalKreditEl.classList.remove('text-red-600', 'dark:text-red-400');
            }
            if (balanceStatusEl) {
                balanceStatusEl.innerHTML = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 border border-green-300 dark:border-green-700" title="Jurnal seimbang"><i class="bi bi-check2-circle mr-1"></i> Balance</span>`;
            }
        } else {
            if (totalDebitEl) {
                totalDebitEl.classList.remove('text-green-600', 'dark:text-green-400');
                if (totalDebit > 0 && totalDebit !== totalKredit) totalDebitEl.classList.add('text-red-600', 'dark:text-red-400');
            }
            if (totalKreditEl) {
                totalKreditEl.classList.remove('text-green-600', 'dark:text-green-400');
                if (totalKredit > 0 && totalDebit !== totalKredit) totalKreditEl.classList.add('text-red-600', 'dark:text-red-400');
            }
            if (balanceStatusEl) {
                if (totalDebit === 0 && totalKredit === 0) {
                    balanceStatusEl.innerHTML = '';
                } else {
                    balanceStatusEl.innerHTML = `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 border border-red-300 dark:border-red-700" title="Selisih ${currencyFormatter.format(diff)}"><i class="bi bi-exclamation-triangle mr-1"></i> Selisih: ${currencyFormatter.format(diff)}</span>`;
                }
            }
        }
    }

    addLineBtn.addEventListener('click', () => addJurnalLine());

    linesBody.addEventListener('click', e => {
        if (e.target.closest('.remove-line-btn')) {
            if (linesBody.children.length <= 2) {
                showToast('Jurnal minimal harus memiliki 2 baris (satu debit dan satu kredit).', 'warning');
                return;
            }
            e.target.closest('tr').remove();
            calculateTotals();
        }
    });

    linesBody.addEventListener('input', e => {
        if (e.target.matches('.debit-input, .kredit-input')) {
            calculateTotals();
        }
    });

    // Reset Form ke kondisi awal (Buat Jurnal Baru)
    function resetFormToNew() {
        document.getElementById('jurnal-id').value = '';
        document.getElementById('jurnal-action').value = 'add';
        document.getElementById('jurnal-keterangan').value = '';
        
        if (formCardTitle) formCardTitle.textContent = 'Buat Jurnal Umum (Majemuk)';
        if (editBadge) editBadge.classList.add('hidden');
        if (editModeAlert) editModeAlert.classList.add('hidden');
        if (cancelEditBtn) cancelEditBtn.classList.add('hidden');
        if (headerCancelEditBtn) headerCancelEditBtn.classList.add('hidden');

        if (saveBtn) {
            saveBtn.classList.remove('bg-amber-600', 'hover:bg-amber-700');
            saveBtn.classList.add('bg-primary', 'hover:bg-primary-600');
        }
        if (saveBtnText) saveBtnText.textContent = 'Simpan Entri Jurnal';
        if (saveBtnIcon) {
            saveBtnIcon.className = 'bi bi-save-fill mr-2';
        }

        tanggalPicker.setDate(new Date(), true);

        // Reset baris jurnal
        linesBody.innerHTML = '';
        addJurnalLine();
        addJurnalLine();
        calculateTotals();

        // Hapus query edit_id dari URL bila ada
        if (window.location.search.includes('edit_id')) {
            const cleanUrl = `${window.location.pathname}`;
            window.history.replaceState({}, '', cleanUrl);
        }
    }

    // Load data jurnal untuk diedit
    async function loadJournalForEdit(id, ref = '') {
        try {
            // Pastikan berpindah ke Tab Form Entri Jurnal terlebih dahulu
            switchTab('entri');

            const displayRef = ref || `JRN-${id}`;
            showToast(`Memuat data jurnal ${displayRef}...`, 'info');
            const response = await fetch(`${basePath}/api/entri-jurnal?action=get_single&id=${id}&ref=${encodeURIComponent(ref)}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const { header, details } = result.data;
            const refNumber = header.nomor_referensi || displayRef;

            // Update form fields
            document.getElementById('jurnal-id').value = header.id;
            document.getElementById('jurnal-action').value = 'update';
            tanggalPicker.setDate(header.tanggal, true, "Y-m-d");
            document.getElementById('jurnal-keterangan').value = header.keterangan;

            // Update UI indikator edit
            if (formCardTitle) formCardTitle.textContent = `Edit Entri Jurnal (${refNumber})`;
            if (editBadge) editBadge.classList.remove('hidden');
            if (editModeAlert) {
                editAlertTitle.textContent = `Sedang Mengedit Jurnal: ${refNumber} - "${header.keterangan}"`;
                editModeAlert.classList.remove('hidden');
            }
            if (cancelEditBtn) cancelEditBtn.classList.remove('hidden');
            if (headerCancelEditBtn) headerCancelEditBtn.classList.remove('hidden');

            if (saveBtn) {
                saveBtn.classList.remove('bg-primary', 'hover:bg-primary-600');
                saveBtn.classList.add('bg-amber-600', 'hover:bg-amber-700');
            }
            if (saveBtnText) saveBtnText.textContent = 'Perbarui Entri Jurnal';
            if (saveBtnIcon) {
                saveBtnIcon.className = 'bi bi-check2-circle mr-2';
            }

            // Render baris rincian akun
            linesBody.innerHTML = '';
            details.forEach(line => {
                addJurnalLine(line.account_id, line.debit, line.kredit);
            });
            calculateTotals();

            // Highlight & smooth scroll ke formulir
            if (formCard) {
                formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                formCard.classList.add('ring-2', 'ring-amber-500', 'dark:ring-amber-400');
                setTimeout(() => {
                    formCard.classList.remove('ring-2', 'ring-amber-500', 'dark:ring-amber-400');
                }, 2000);
            }

            // Update URL tanpa reload
            window.history.replaceState({ path: window.location.pathname }, '', `${basePath}/entri-jurnal?edit_id=${id}${ref ? '&edit_ref=' + encodeURIComponent(ref) : ''}`);
            showToast(`Data jurnal ${refNumber} siap diedit.`, 'success');
        } catch (error) {
            showToast(`Gagal memuat data jurnal untuk diedit: ${error.message}`, 'error');
        }
    }

    // Pasang handler untuk tombol batal edit
    if (cancelEditBtn) cancelEditBtn.addEventListener('click', resetFormToNew);
    if (alertCancelEditBtn) alertCancelEditBtn.addEventListener('click', resetFormToNew);
    if (headerCancelEditBtn) headerCancelEditBtn.addEventListener('click', resetFormToNew);

    // Submit form (Tambah atau Edit)
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const action = document.getElementById('jurnal-action').value || 'add';
        const formData = new FormData(form);

        // Ambil tanggal dari flatpickr dan format untuk DB
        const selectedDate = tanggalPicker.selectedDates[0];
        if (selectedDate) {
            const year = selectedDate.getFullYear();
            const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
            const day = String(selectedDate.getDate()).padStart(2, '0');
            formData.set('tanggal', `${year}-${month}-${day}`);
        }

        // Validasi seimbang di client
        let totalDebit = 0, totalKredit = 0, validLines = 0;
        linesBody.querySelectorAll('tr').forEach(row => {
            const select = row.querySelector('select');
            const debit = parseFloat(row.querySelector('.debit-input').value) || 0;
            const kredit = parseFloat(row.querySelector('.kredit-input').value) || 0;
            if (select && select.value && (debit > 0 || kredit > 0)) {
                validLines++;
                totalDebit += debit;
                totalKredit += kredit;
            }
        });

        if (validLines < 2) {
            showToast('Jurnal minimal harus memiliki dua baris dengan akun dan nominal terisi.', 'error');
            return;
        }

        if (Math.abs(totalDebit - totalKredit) > 0.01) {
            showToast(`Jurnal tidak seimbang! Total Debit: ${currencyFormatter.format(totalDebit)}, Total Kredit: ${currencyFormatter.format(totalKredit)}`, 'error');
            return;
        }

        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;

        try {
            const response = await fetch(`${basePath}/api/entri-jurnal`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                // Reset form ke mode tambah baru
                resetFormToNew();
                // Segarkan tabel riwayat jurnal terbaru
                loadRecentJournals(1);
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan saat menyimpan.', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
        }
    });

    // Save as recurring template
    saveAsRecurringBtn.addEventListener('click', () => {
        const keterangan = document.getElementById('jurnal-keterangan').value;
        if (!keterangan) {
            showToast('Keterangan jurnal wajib diisi sebelum membuat template.', 'error');
            return;
        }

        const lines = [];
        linesBody.querySelectorAll('tr').forEach(row => {
            const select = row.querySelector('select');
            const debit = parseFloat(row.querySelector('.debit-input').value) || 0;
            const kredit = parseFloat(row.querySelector('.kredit-input').value) || 0;
            if (select && select.value && (debit > 0 || kredit > 0)) {
                lines.push({ account_id: select.value, debit, kredit });
            }
        });

        if (lines.length < 2) {
            showToast('Template harus memiliki minimal 2 baris jurnal yang valid.', 'error');
            return;
        }

        const templateData = { keterangan, lines };
        openRecurringModal('jurnal', templateData);
    });

    // =========================================================================
    // DAFTAR RIWAYAT JURNAL TERBARU (INFINITE SCROLL)
    // =========================================================================
    let recentJournalsAbortController = null;
    let currentRecentPage = 1;
    let totalRecentPages = 1;
    let isRecentLoading = false;
    let hasMoreRecent = true;

    function renderRecentJournalRows(data) {
        if (!data || data.length === 0) return;

        // Kelompokkan data per transaksi berdasarkan 'ref' (No. Referensi)
        const transactions = [];
        data.forEach(line => {
            let tx = transactions.find(t => t.ref === line.ref);
            if (!tx) {
                tx = {
                    ref: line.ref,
                    tanggal: line.tanggal,
                    keterangan: line.keterangan,
                    source: line.source,
                    entry_id: line.entry_id,
                    created_at: line.created_at,
                    created_by_name: line.created_by_name,
                    updated_by_name: line.updated_by_name,
                    lines: []
                };
                transactions.push(tx);
            }
            tx.lines.push(line);
        });

        transactions.forEach(tx => {
            const dateObj = new Date(tx.tanggal);
            const formattedDate = !isNaN(dateObj.getTime()) 
                ? dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
                : tx.tanggal;
            const formattedTime = !isNaN(dateObj.getTime())
                ? dateObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                : '';

            let totalDebit = 0;
            tx.lines.forEach(l => { totalDebit += parseFloat(l.debit) || 0; });

            const isSO = tx.ref && tx.ref.startsWith('SO-');
            const badgeClass = isSO
                ? 'bg-teal-100 text-teal-800 dark:bg-teal-900/60 dark:text-teal-300 border-teal-300 dark:border-teal-700'
                : 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border-amber-300 dark:border-amber-700';

            const headerRow = `
                <tr class="bg-gray-50/80 dark:bg-gray-700/50 border-t-2 border-gray-200 dark:border-gray-600">
                    <td class="px-4 py-3 align-middle">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-2 py-0.5 rounded text-xs font-bold font-mono uppercase tracking-wider ${badgeClass}">${tx.ref}</span>
                            ${isSO ? '<span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-teal-50 text-teal-700 dark:bg-teal-950/50 dark:text-teal-300 border border-teal-200 dark:border-teal-800">Stok Opname</span>' : ''}
                        </div>
                        <div class="text-xs font-medium text-gray-900 dark:text-white mt-1 line-clamp-2" title="${tx.keterangan || '-'}">
                            ${tx.keterangan || '-'}
                        </div>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 align-middle">
                        <div class="font-medium text-gray-800 dark:text-gray-200">${formattedDate}</div>
                        <div class="text-[11px] opacity-75">${formattedTime}</div>
                        <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">Oleh: ${tx.created_by_name || 'sistem'}</div>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300 align-middle">
                        <span class="font-semibold text-gray-800 dark:text-gray-200">${tx.lines.length} Akun</span>
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-xs font-bold text-gray-900 dark:text-white align-middle" colspan="2">
                        <span class="text-[11px] text-gray-400 mr-1 font-normal">Total:</span> ${currencyFormatter.format(totalDebit)}
                    </td>
                    <td class="px-4 py-3 text-center align-middle">
                        <div class="flex items-center justify-center gap-1">
                            <button type="button" class="p-1.5 text-amber-600 hover:bg-amber-100 dark:hover:bg-amber-900/40 rounded-lg transition-colors edit-recent-jurnal-btn" data-id="${tx.entry_id}" data-ref="${tx.ref}" title="Edit Jurnal Ini">
                                <i class="bi bi-pencil-square text-base"></i>
                            </button>
                            <button type="button" class="p-1.5 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-lg transition-colors delete-recent-jurnal-btn" data-id="${tx.entry_id}" data-keterangan="${tx.keterangan || ''}" data-ref="${tx.ref}" title="Hapus Jurnal Ini">
                                <i class="bi bi-trash3-fill text-base"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            recentTableBody.insertAdjacentHTML('beforeend', headerRow);

            // Sub-rows rincian baris akun
            tx.lines.forEach(line => {
                const isDebit = parseFloat(line.debit) > 0;
                const row = `
                    <tr class="hover:bg-gray-50/40 dark:hover:bg-gray-700/20 transition-colors text-xs border-none">
                        <td class="py-1.5 px-4" colspan="2"></td>
                        <td class="px-4 py-1.5">
                            <div class="flex items-center gap-2 ${isDebit ? 'text-gray-900 dark:text-white font-medium pl-2' : 'text-gray-600 dark:text-gray-400 pl-6 italic'}">
                                <i class="bi bi-arrow-return-right opacity-30 text-xs"></i>
                                <span class="truncate max-w-[280px]" title="${line.nama_akun || '-'}">${line.nama_akun || '-'}</span>
                            </div>
                        </td>
                        <td class="px-4 py-1.5 text-right font-mono ${isDebit ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-400'}">
                            ${parseFloat(line.debit) > 0 ? currencyFormatter.format(line.debit) : '-'}
                        </td>
                        <td class="px-4 py-1.5 text-right font-mono ${parseFloat(line.kredit) > 0 ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-400'}">
                            ${parseFloat(line.kredit) > 0 ? currencyFormatter.format(line.kredit) : '-'}
                        </td>
                        <td></td>
                    </tr>
                `;
                recentTableBody.insertAdjacentHTML('beforeend', row);
            });
        });
    }

    async function loadRecentJournals(page = 1, append = false) {
        if (!recentTableBody) return;
        if (isRecentLoading || (!hasMoreRecent && append)) return;
        isRecentLoading = true;

        const loaderEl = document.getElementById('recent-infinite-scroll-loader');

        if (append) {
            if (loaderEl) loaderEl.classList.remove('hidden');
        } else {
            currentRecentPage = 1;
            hasMoreRecent = true;
            if (recentJournalsAbortController) {
                recentJournalsAbortController.abort();
            }
            recentJournalsAbortController = new AbortController();
            recentTableBody.innerHTML = `<tr><td colspan="6" class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div><span class="text-xs text-gray-500 mt-2 block">Memuat riwayat entri jurnal...</span></td></tr>`;
        }

        currentRecentPage = page;
        const limit = filterRecentLimit ? filterRecentLimit.value : 15;
        const search = searchRecentInput ? searchRecentInput.value.trim() : '';
        const startDate = filterRecentMulai && filterRecentMulai.value ? filterRecentMulai.value.split('-').reverse().join('-') : '';
        const endDate = filterRecentAkhir && filterRecentAkhir.value ? filterRecentAkhir.value.split('-').reverse().join('-') : '';

        const params = new URLSearchParams({
            ref_prefix: 'JRN-,SO-', // Tampilkan jurnal umum manual (JRN-) dan jurnal penyesuaian stok opname (SO-)
            page,
            limit,
            search,
            start_date: startDate,
            end_date: endDate,
            sort_by: 'tanggal'
        });

        try {
            const response = await fetch(`${basePath}/api/entri-jurnal?${params.toString()}`, {
                signal: recentJournalsAbortController ? recentJournalsAbortController.signal : undefined
            });
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            if (!append) {
                recentTableBody.innerHTML = '';
            }

            if (result.data && result.data.length > 0) {
                renderRecentJournalRows(result.data);
            } else if (!append) {
                recentTableBody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400"><i class="bi bi-journal-x text-3xl mb-2 block opacity-30"></i>Belum ada data entri jurnal manual yang cocok dengan filter.</td></tr>`;
            }

            if (result.pagination) {
                totalRecentPages = result.pagination.total_pages || 1;
                hasMoreRecent = currentRecentPage < totalRecentPages;

                const totalEntries = result.pagination.total_entries !== undefined ? result.pagination.total_entries : result.pagination.total_records;
                const end = Math.min(currentRecentPage * (result.pagination.limit || 15), totalEntries);

                if (recentPaginationInfo) {
                    recentPaginationInfo.textContent = totalEntries === 0
                        ? 'Tidak ada entri jurnal ditemukan.'
                        : `Menampilkan ${end} dari ${totalEntries} entri jurnal manual (${result.pagination.total_records} baris ledger).`;
                }

                if (riwayatCountBadge) {
                    riwayatCountBadge.textContent = Number(totalEntries || 0).toLocaleString('id-ID');
                }
            }
        } catch (error) {
            if (error.name === 'AbortError') return;
            if (!append) {
                recentTableBody.innerHTML = `<tr><td colspan="6" class="text-center py-6 text-red-500"><i class="bi bi-exclamation-triangle mr-2"></i>Gagal memuat data: ${error.message}</td></tr>`;
            } else {
                showToast('Gagal memuat data tambahan: ' + error.message, 'danger');
            }
        } finally {
            isRecentLoading = false;
            if (loaderEl) loaderEl.classList.add('hidden');
        }
    }

    // Setup Infinite Scroll Observer
    function setupRecentInfiniteScroll() {
        const sentinel = document.getElementById('recent-infinite-scroll-sentinel');
        const container = document.getElementById('recentJurnalTableContainer');
        if (!sentinel) return;

        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && hasMoreRecent && !isRecentLoading) {
                loadRecentJournals(currentRecentPage + 1, true);
            }
        }, {
            root: container,
            threshold: 0.1
        });

        observer.observe(sentinel);
    }

    // Event delegation untuk Edit dan Hapus di tabel riwayat
    if (recentTableBody) {
        recentTableBody.addEventListener('click', async (e) => {
            // Edit Jurnal
            const editBtn = e.target.closest('.edit-recent-jurnal-btn');
            if (editBtn) {
                const id = editBtn.dataset.id;
                const ref = editBtn.dataset.ref || '';
                if (id || ref) loadJournalForEdit(id, ref);
                return;
            }

            // Hapus Jurnal
            const deleteBtn = e.target.closest('.delete-recent-jurnal-btn');
            if (deleteBtn) {
                const { id, keterangan, ref } = deleteBtn.dataset;
                if (confirm(`Yakin ingin menghapus entri jurnal ${ref} ("${keterangan}")? Tindakan ini akan menghapus jurnal dari General Ledger dan tidak dapat dibatalkan.`)) {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('id', id);
                        const response = await fetch(`${basePath}/api/entri-jurnal`, { method: 'POST', body: formData });
                        const result = await response.json();
                        showToast(result.message, result.status === 'success' ? 'success' : 'error');
                        if (result.status === 'success') {
                            // Jika yang dihapus sedang dibuka di form edit, reset form ke new
                            const currentEditId = document.getElementById('jurnal-id').value;
                            if (currentEditId == id) {
                                resetFormToNew();
                            }
                            loadRecentJournals(1);
                        }
                    } catch (err) {
                        showToast('Gagal menghapus entri jurnal.', 'error');
                    }
                }
            }
        });
    }

    // Filter Listeners
    if (searchRecentInput) {
        searchRecentInput.addEventListener('input', () => {
            clearTimeout(searchDebounceTimeout);
            searchDebounceTimeout = setTimeout(() => {
                loadRecentJournals(1);
            }, 350);
        });
    }

    if (filterRecentMulai) {
        filterRecentMulai.addEventListener('change', () => loadRecentJournals(1));
    }
    if (filterRecentAkhir) {
        filterRecentAkhir.addEventListener('change', () => loadRecentJournals(1));
    }
    if (filterRecentLimit) {
        filterRecentLimit.addEventListener('change', () => loadRecentJournals(1));
    }
    if (refreshRecentBtn) {
        refreshRecentBtn.addEventListener('click', () => {
            loadRecentJournals(1);
            showToast('Data riwayat diperbarui.', 'info');
        });
    }
    if (btnRecentReset) {
        btnRecentReset.addEventListener('click', () => {
            if (searchRecentInput) searchRecentInput.value = '';
            if (recentStartDatePicker) recentStartDatePicker.clear();
            if (recentEndDatePicker) recentEndDatePicker.clear();
            if (filterRecentLimit) filterRecentLimit.value = '15';
            loadRecentJournals(1, false);
        });
    }

    // Inisialisasi infinite scroll observer
    setupRecentInfiniteScroll();

    // Initial setup
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit_id');
    const editRef = urlParams.get('edit_ref');

    if (window.location.hash === '#riwayat' && !editId) {
        switchTab('riwayat');
    } else {
        switchTab('entri');
        // Muat riwayat awal untuk menghitung badge tab
        loadRecentJournals(1, false);
    }

    fetchAccounts().then(() => {
        if (document.getElementById('jurnal-tanggal')) {
            if (editId) {
                loadJournalForEdit(editId, editRef);
            } else {
                tanggalPicker.setDate(new Date(), true);
                addJurnalLine();
                addJurnalLine();
            }
        }
    });
}