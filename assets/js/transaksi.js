function initTransaksiPage() {
    const tableBody = document.getElementById('transaksi-table-body');
    const form = document.getElementById('transaksi-form');
    const modalEl = document.getElementById('transaksiModal');

    // Cek jika URL memiliki hash '#add', buka modal secara otomatis
    if (window.location.hash === '#add') {
        setTimeout(() => prepareAndOpenModal(), 100);
    }

    const saveBtn = document.getElementById('save-transaksi-btn');
    const addBtn = document.getElementById('add-transaksi-btn');
    const jenisBtnGroup = document.getElementById('jenis-btn-group');

    // Filter elements
    const searchInput = document.getElementById('search-transaksi');
    const akunKasFilter = document.getElementById('filter-akun-kas');
    const bulanFilter = document.getElementById('filter-bulan');
    const tahunFilter = document.getElementById('filter-tahun');
    const paginationContainer = document.getElementById('transaksi-pagination');

    if (!tableBody) return;
    let periodLockDate = null;

    // Inisialisasi Flatpickr
    const tanggalPicker = flatpickr("#tanggal", { dateFormat: "d-m-Y", allowInput: true });

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    function setupFilters() {
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;

        for (let i = 0; i < 5; i++) {
            tahunFilter.add(new Option(currentYear - i, currentYear - i));
        }
        const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
        bulanFilter.innerHTML = '<option value="">Semua Bulan</option>';
        months.forEach((month, index) => {
            bulanFilter.add(new Option(month, index + 1));
        });

        bulanFilter.value = currentMonth;
        tahunFilter.value = currentYear;
    }

    async function loadAccountsForForm() {
        try {
            const response = await fetch(`${basePath}/api/transaksi?action=get_accounts_for_form`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const { kas, pendapatan, beban } = result.data;

            // Populate filter
            akunKasFilter.innerHTML = '<option value="">Semua Akun Kas/Bank</option>';
            kas.forEach(acc => akunKasFilter.add(new Option(acc.nama_akun, acc.id)));

            // Populate modal dropdowns
            const kasSelects = ['kas_account_id_pemasukan', 'kas_account_id_pengeluaran', 'kas_account_id_transfer', 'kas_tujuan_account_id'];
            kasSelects.forEach(id => {
                const select = document.getElementById(id);
                select.innerHTML = '';
                kas.forEach(acc => select.add(new Option(acc.nama_akun, acc.id)));
            });

            const pendapatanSelect = document.getElementById('account_id_pemasukan');
            pendapatanSelect.innerHTML = '';
            pendapatan.forEach(acc => pendapatanSelect.add(new Option(acc.nama_akun, acc.id)));

            const bebanSelect = document.getElementById('account_id_pengeluaran');
            bebanSelect.innerHTML = '';
            beban.forEach(acc => bebanSelect.add(new Option(acc.nama_akun, acc.id)));

        } catch (error) {
            showToast(`Gagal memuat daftar akun: ${error.message}`, 'error');
        }
    }

    async function loadTransaksi(page = 1) {
        const params = new URLSearchParams({
            page,
            limit: 15, // Hardcode limit
            search: searchInput.value,
            bulan: bulanFilter.value,
            tahun: tahunFilter.value, 
            akun_kas: akunKasFilter.value,
        });

        tableBody.innerHTML = `<tr><td colspan="8" class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>`;
        
        try {
            const [transaksiRes, settingsRes] = await Promise.all([
                fetch(`${basePath}/api/transaksi?${params.toString()}`),
                fetch(`${basePath}/api/settings`) // Ambil juga data settings
            ]);
            const result = await transaksiRes.json();
            const settingsResult = await settingsRes.json();

            if (result.status !== 'success') throw new Error(result.message);
            if (settingsResult.status === 'success' && settingsResult.data.period_lock_date) {
                periodLockDate = new Date(settingsResult.data.period_lock_date);
            }

            tableBody.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach(tx => {
                    let akunUtama, akunKas, jumlahDisplay;
                    const jumlahFormatted = currencyFormatter.format(tx.jumlah);
                    
                    if (tx.jenis === 'pemasukan') {
                        akunUtama = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Pemasukan</span> ${tx.nama_akun_utama}`;
                        akunKas = `Ke: ${tx.nama_akun_kas}`;
                        jumlahDisplay = `<span class="text-green-600 font-bold">+ ${jumlahFormatted}</span>`;
                    } else if (tx.jenis === 'pengeluaran') {
                        akunUtama = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Pengeluaran</span> ${tx.nama_akun_utama}`;
                        akunKas = `Dari: ${tx.nama_akun_kas}`;
                        jumlahDisplay = `<span class="text-red-600 font-bold">- ${jumlahFormatted}</span>`;
                    } else { // transfer
                        akunUtama = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Transfer</span>`;
                        akunKas = `Dari: ${tx.nama_akun_kas}<br>Ke: ${tx.nama_akun_tujuan}`;
                        jumlahDisplay = `<span class="text-blue-600 font-bold">${jumlahFormatted}</span>`;
                    }

                    // Info Audit (Created/Updated)
                    const createdAt = new Date(tx.created_at);
                    const updatedAt = new Date(tx.updated_at);
                    const createdBy = tx.created_by_name || 'sistem';
                    const updatedBy = tx.updated_by_name || 'sistem';
                    
                    let auditInfo = `Dibuat: ${createdBy} pada ${createdAt.toLocaleString('id-ID')}`;
                    let auditIcon = '<i class="bi bi-info-circle text-gray-400"></i>';

                    if (updatedBy && updatedAt.getTime() > createdAt.getTime() + 1000) { // Cek jika ada update signifikan
                        auditInfo += `\nDiperbarui: ${updatedBy} pada ${updatedAt.toLocaleString('id-ID')}`;
                        auditIcon = '<i class="bi bi-info-circle-fill text-primary"></i>';
                    }

                    // Cek apakah transaksi terkunci
                    const isLocked = periodLockDate && new Date(tx.tanggal) <= periodLockDate;
                    const disabledAttr = isLocked ? 'disabled title="Periode terkunci"' : '';
                    const disabledClasses = isLocked ? 'opacity-50 cursor-not-allowed' : '';

                    const row = `
                        <tr id="tx-${tx.id}" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 text-sm">
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">${new Date(tx.tanggal).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'})}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${akunUtama}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">${tx.nomor_referensi || '-'}</td>
                            <td class="px-6 py-4">${tx.keterangan.replace(/\n/g, '<br>')}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">${jumlahDisplay}</td>
                            <td class="px-6 py-4 whitespace-nowrap" title="${auditInfo}">${auditIcon}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">${akunKas}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="inline-flex rounded-md shadow-sm">
                                    <button class="px-2 py-1 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-l-md text-red-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 delete-btn ${disabledClasses}" data-id="${tx.id}" data-keterangan="${tx.keterangan}" title="Hapus" ${disabledAttr}><i class="bi bi-trash-fill"></i></button>
                                    <button class="px-2 py-1 border-t border-b border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 edit-btn ${disabledClasses}" data-id="${tx.id}" title="Edit" ${disabledAttr}><i class="bi bi-pencil-fill"></i></button>
                                    <button class="px-2 py-1 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-r-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 view-journal-btn" data-id="${tx.id}" title="Lihat Jurnal"><i class="bi bi-journal-text"></i></button>
                                </div>
                            </td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-gray-500 py-10">Tidak ada transaksi ditemukan.</td></tr>';
            }
            renderPagination(paginationContainer, result.pagination, loadTransaksi);
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="8" class="text-center text-red-500 py-10">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    function toggleFormFields() {
        const jenis = document.getElementById('jenis').value;
        document.getElementById('pemasukan-fields').classList.toggle('hidden', jenis !== 'pemasukan');
        document.getElementById('pengeluaran-fields').classList.toggle('hidden', jenis !== 'pengeluaran');
        document.getElementById('transfer-fields').classList.toggle('hidden', jenis !== 'transfer');
    }

    function prepareAndOpenModal() {
        fetch(`${basePath}/api/settings`).then(res => res.json()).then(result => {
            const settings = result.data || {};
            document.getElementById('transaksiModalLabel').textContent = 'Tambah Transaksi Baru';
            form.reset();
            form.classList.remove('was-validated');
            document.getElementById('transaksi-id').value = '';
            document.getElementById('transaksi-action').value = 'add';            
            tanggalPicker.setDate(new Date(), true);
            
            // Set default to 'pengeluaran' by simulating a click
            if (jenisBtnGroup) {
                const defaultBtn = jenisBtnGroup.querySelector('button[data-value="pengeluaran"]');
                if (defaultBtn) defaultBtn.click();
            }

            // Set default cash accounts
            if (settings.default_cash_in) {
                const el = document.getElementById('kas_account_id_pemasukan');
                if (el) el.value = settings.default_cash_in;
            }
            if (settings.default_cash_out) {
                const el = document.getElementById('kas_account_id_pengeluaran');
                if (el) el.value = settings.default_cash_out;
                const elTransfer = document.getElementById('kas_account_id_transfer');
                if (elTransfer) elTransfer.value = settings.default_cash_out;
            }
            openModal('transaksiModal');
        });
    }

    // --- Event Listeners ---
    if (jenisBtnGroup) {
        jenisBtnGroup.addEventListener('click', (e) => {
            const button = e.target.closest('button');
            if (!button) return;

            const selectedValue = button.dataset.value;
            document.getElementById('jenis').value = selectedValue;

            // Update button styles
            const buttons = jenisBtnGroup.querySelectorAll('button');
            buttons.forEach(btn => btn.classList.remove('bg-red-500', 'bg-green-500', 'bg-blue-500', 'text-white', 'shadow-inner'));
            
            const colorClass = selectedValue === 'pengeluaran' ? 'bg-red-500' : (selectedValue === 'pemasukan' ? 'bg-green-500' : 'bg-blue-500');
            button.classList.add(colorClass, 'text-white', 'shadow-inner');

            toggleFormFields();
        });
    }

    if (addBtn) {
        addBtn.addEventListener('click', prepareAndOpenModal);
    }

    // Menambahkan fungsionalitas 'Enter' untuk pindah field
    if (modalEl) {
        modalEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault(); // Mencegah form tersubmit

                // Dapatkan semua elemen yang bisa difokuskan dan terlihat di dalam form
                const focusableElements = Array.from(
                    form.querySelectorAll(
                        'input:not([type="hidden"]):not(:disabled), select:not(:disabled), textarea:not(:disabled)'
                    )
                ).filter(el => el.offsetParent !== null); // Filter hanya yang terlihat

                const currentIndex = focusableElements.indexOf(document.activeElement);
                const nextIndex = currentIndex + 1;

                if (nextIndex < focusableElements.length) {
                    // Pindah ke elemen berikutnya
                    focusableElements[nextIndex].focus();
                } else {
                    // Jika sudah di elemen terakhir, klik tombol simpan
                    const saveBtn = document.getElementById('save-transaksi-btn');
                    if (saveBtn) {
                        saveBtn.click();
                    }
                }
            }
        });
    }

    // Menambahkan fungsionalitas 'Enter' pada textarea keterangan untuk menyimpan
    const keteranganTextarea = document.getElementById('keterangan');
    if (keteranganTextarea) {
        keteranganTextarea.addEventListener('keydown', (e) => {
            // Jika 'Enter' ditekan tanpa 'Shift', tampilkan konfirmasi
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault(); // Mencegah membuat baris baru
                if (confirm('Simpan transaksi?')) {
                    const saveBtn = document.getElementById('save-transaksi-btn');
                    if (saveBtn) saveBtn.click();
                } else {
                    // Jika tidak, fokus kembali ke field jumlah
                    const jumlahInput = document.getElementById('jumlah');
                    if (jumlahInput) jumlahInput.focus();
                }
            }
            // Jika 'Shift + Enter' ditekan, akan tetap membuat baris baru (perilaku default)
        });
    }

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);

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
            const response = await fetch(`${basePath}/api/transaksi`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');

            if (result.status === 'success') {
                const action = formData.get('action');
                loadTransaksi(1); // Selalu refresh tabel di latar belakang

                if (action === 'add') {
                    // Untuk 'add', jangan tutup modal, tapi reset form untuk entri baru
                    form.reset();
                    tanggalPicker.setDate(new Date(), true);
                    // Kembalikan ke jenis transaksi default (misal: pengeluaran)
                    jenisBtnGroup.querySelector('button[data-value="pengeluaran"]').click();
                } else {
                    // Untuk 'update', tutup modal seperti biasa
                    closeModal('transaksiModal');
                }
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
        }
    });

    tableBody.addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const { id, keterangan } = deleteBtn.dataset;
            if (confirm(`Yakin ingin menghapus transaksi "${keterangan}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                const response = await fetch(`${basePath}/api/transaksi`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') loadTransaksi(1); // Kembali ke halaman pertama setelah menghapus
            }
        }

        const viewJournalBtn = e.target.closest('.view-journal-btn');
        const editBtn = e.target.closest('.edit-btn');

        if (editBtn) {
            const id = editBtn.dataset.id;
            try {
                // Gunakan POST untuk get_single sesuai dengan handler
                const formData = new FormData();
                formData.append('action', 'get_single');
                formData.append('id', id);
                const response = await fetch(`${basePath}/api/transaksi`, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status !== 'success') throw new Error(result.message);

                const tx = result.data;
                document.getElementById('transaksiModalLabel').textContent = 'Edit Transaksi';
                form.reset();
                document.getElementById('transaksi-id').value = tx.id;
                document.getElementById('transaksi-action').value = 'update';
                jenisBtnGroup.querySelector(`button[data-value="${tx.jenis}"]`).click(); // Simulate click to set value and style
                tanggalPicker.setDate(tx.tanggal, true, "Y-m-d");
                document.getElementById('jumlah').value = tx.jumlah;
                document.getElementById('nomor_referensi').value = tx.nomor_referensi;
                document.getElementById('keterangan').value = tx.keterangan;
                toggleFormFields(); // Update visible fields based on 'jenis'
                
                // Set selected values for dropdowns
                if (tx.jenis === 'pemasukan') { document.getElementById('kas_account_id_pemasukan').value = tx.kas_account_id; document.getElementById('account_id_pemasukan').value = tx.account_id; } 
                else if (tx.jenis === 'pengeluaran') { document.getElementById('kas_account_id_pengeluaran').value = tx.kas_account_id; document.getElementById('account_id_pengeluaran').value = tx.account_id; } 
                else if (tx.jenis === 'transfer') { document.getElementById('kas_account_id_transfer').value = tx.kas_account_id; document.getElementById('kas_tujuan_account_id').value = tx.kas_tujuan_account_id; }
                openModal('transaksiModal');
            } catch (error) { showToast(`Gagal memuat data transaksi: ${error.message}`, 'error'); }
        }

        if (viewJournalBtn) {
            const id = viewJournalBtn.dataset.id;
            const modalBody = document.getElementById('jurnal-detail-body');
            modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border"></div></div>';
            openModal('jurnalDetailModal');

            try {
                const response = await fetch(`${basePath}/api/transaksi?action=get_journal_entry&id=${id}`);
                const result = await response.json();
                if (result.status !== 'success') throw new Error(result.message);

                const { transaksi, jurnal } = result.data;
                let tableHtml = `
                    <div class="space-y-2 text-sm">
                        <p><strong>Tanggal:</strong> ${new Date(transaksi.tanggal).toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'})}</p>
                        <p><strong>No. Referensi:</strong> ${transaksi.nomor_referensi || '-'}</p>
                        <p><strong>Keterangan:</strong> ${transaksi.keterangan}</p>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 mt-4">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Akun</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Debit</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kredit</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                `;
                jurnal.forEach(entry => {
                    tableHtml += `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">${entry.akun}</td>
                            <td class="px-4 py-3 text-right">${entry.debit > 0 ? currencyFormatter.format(entry.debit) : '-'}</td>
                            <td class="px-4 py-3 text-right">${entry.kredit > 0 ? currencyFormatter.format(entry.kredit) : '-'}</td>
                        </tr>
                    `;
                });
                tableHtml += `</tbody></table>`;
                modalBody.innerHTML = tableHtml;
            } catch (error) {
                modalBody.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
            }
        }
    });

    // Fokus ke field jumlah saat modal selesai ditampilkan
    modalEl.addEventListener('shown.bs.modal', () => {
        const jumlahInput = document.getElementById('jumlah');
        if (jumlahInput) jumlahInput.focus();
    });

    let debounceTimer;
    const combinedFilterHandler = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadTransaksi(1), 300);
    };

    [searchInput, akunKasFilter, bulanFilter, tahunFilter].forEach(el => {
        el.addEventListener('change', combinedFilterHandler);
    });
    searchInput.addEventListener('input', combinedFilterHandler);

    // --- Initial Load ---
    setupFilters();
    loadAccountsForForm().then(() => {
        // Cek jika URL memiliki hash untuk memfilter transaksi tertentu
        if (window.location.hash && window.location.hash.startsWith('#tx-')) {
            const txId = window.location.hash.substring(4); // Hapus '#tx-'
            searchInput.value = txId;
            // Hapus hash dari URL agar tidak mengganggu navigasi selanjutnya
            history.replaceState(null, '', window.location.pathname + window.location.search);
        }
        loadTransaksi(1);
    });
}