function initTransaksiPage() {
    const tableBody = document.getElementById('transaksi-table-body');
    const modalEl = document.getElementById('transaksiModal');
    const jurnalDetailModalEl = document.getElementById('jurnalDetailModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    const form = document.getElementById('transaksi-form');

    // Cek jika URL memiliki hash '#add', buka modal secara otomatis
    if (window.location.hash === '#add') {
        // Gunakan timeout kecil untuk memastikan modal siap
        setTimeout(() => document.getElementById('add-transaksi-btn')?.click(), 100);
    }

    const saveBtn = document.getElementById('save-transaksi-btn');
    const jenisBtnGroup = document.getElementById('jenis-btn-group');

    // Filter elements
    const searchInput = document.getElementById('search-transaksi');
    const akunKasFilter = document.getElementById('filter-akun-kas');
    const bulanFilter = document.getElementById('filter-bulan');
    const tahunFilter = document.getElementById('filter-tahun');
    const limitSelect = document.getElementById('filter-limit');
    const paginationContainer = document.getElementById('transaksi-pagination');

    if (!tableBody) return;
    let periodLockDate = null;

    // Cek jika URL memiliki hash untuk memfilter transaksi tertentu
    if (window.location.hash && window.location.hash.startsWith('#tx-')) {
        const txId = window.location.hash.substring(4); // Hapus '#tx-'
        searchInput.value = txId;
        // Hapus hash dari URL agar tidak mengganggu navigasi selanjutnya
        history.replaceState(null, '', window.location.pathname + window.location.search);
    }

    // Load saved limit from localStorage
    const savedLimit = localStorage.getItem('transaksi_limit');
    if (savedLimit) limitSelect.value = savedLimit;

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
            limit: limitSelect.value,
            search: searchInput.value,
            bulan: bulanFilter.value,
            tahun: tahunFilter.value, 
            akun_kas: akunKasFilter.value,
        });

        tableBody.innerHTML = `<tr><td colspan="7" class="text-center p-5"><div class="spinner-border"></div></td></tr>`;
        
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
                        akunUtama = `<span class="badge bg-success">Pemasukan</span> ${tx.nama_akun_utama}`;
                        akunKas = `Ke: ${tx.nama_akun_kas}`;
                        jumlahDisplay = `<span class="text-success fw-bold">+ ${jumlahFormatted}</span>`;
                    } else if (tx.jenis === 'pengeluaran') {
                        akunUtama = `<span class="badge bg-danger">Pengeluaran</span> ${tx.nama_akun_utama}`;
                        akunKas = `Dari: ${tx.nama_akun_kas}`;
                        jumlahDisplay = `<span class="text-danger fw-bold">- ${jumlahFormatted}</span>`;
                    } else { // transfer
                        akunUtama = `<span class="badge bg-info">Transfer</span>`;
                        akunKas = `Dari: ${tx.nama_akun_kas}<br>Ke: ${tx.nama_akun_tujuan}`;
                        jumlahDisplay = `<span class="text-info fw-bold">${jumlahFormatted}</span>`;
                    }

                    // Info Audit (Created/Updated)
                    const createdAt = new Date(tx.created_at);
                    const updatedAt = new Date(tx.updated_at);
                    const createdBy = tx.created_by_name || 'sistem';
                    const updatedBy = tx.updated_by_name || 'sistem';
                    
                    let auditInfo = `Dibuat: ${createdBy} pada ${createdAt.toLocaleString('id-ID')}`;
                    let auditIcon = '<i class="bi bi-info-circle"></i>';

                    if (updatedBy && updatedAt.getTime() > createdAt.getTime() + 1000) { // Cek jika ada update signifikan
                        auditInfo += `\nDiperbarui: ${updatedBy} pada ${updatedAt.toLocaleString('id-ID')}`;
                        auditIcon = '<i class="bi bi-info-circle-fill text-primary"></i>';
                    }

                    // Cek apakah transaksi terkunci
                    const isLocked = periodLockDate && new Date(tx.tanggal) <= periodLockDate;
                    const disabledAttr = isLocked ? 'disabled title="Periode terkunci"' : '';
                    const deleteBtnHtml = `<button class="btn btn-sm btn-danger delete-btn" data-id="${tx.id}" data-keterangan="${tx.keterangan}" title="Hapus" ${disabledAttr}><i class="bi bi-trash-fill"></i></button>`;
                    const editBtnHtml = `<button class="btn btn-sm btn-warning edit-btn" data-id="${tx.id}" title="Edit" ${disabledAttr}><i class="bi bi-pencil-fill"></i></button>`;

                    const row = `
                        <tr id="tx-${tx.id}">
                            <td>${new Date(tx.tanggal).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'})}</td>
                            <td>${akunUtama}</td>
                            <td><small class="text-muted">${tx.nomor_referensi || '-'}</small></td>
                            <td>${tx.keterangan.replace(/\n/g, '<br>')}</td>
                            <td class="text-end">${jumlahDisplay}</td>
                            <td><span data-bs-toggle="tooltip" data-bs-placement="top" title="${auditInfo}">${auditIcon}</span></td>
                            <td><small>${akunKas}</small></td>
                            <td class="text-end">
                                ${deleteBtnHtml}
                                ${editBtnHtml}
                                <button class="btn btn-sm btn-secondary view-journal-btn" data-id="${tx.id}" title="Lihat Jurnal"><i class="bi bi-journal-text"></i></button>                                
                            </td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="9" class="text-center">Tidak ada transaksi ditemukan.</td></tr>';
            }
            renderPagination(paginationContainer, result.pagination, loadTransaksi);
            // Inisialisasi ulang tooltip setelah data baru dimuat
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="9" class="text-center text-danger">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    function toggleFormFields() {
        const jenis = document.getElementById('jenis').value;
        document.getElementById('pemasukan-fields').style.display = jenis === 'pemasukan' ? 'flex' : 'none';
        document.getElementById('pengeluaran-fields').style.display = jenis === 'pengeluaran' ? 'flex' : 'none';
        document.getElementById('transfer-fields').style.display = jenis === 'transfer' ? 'flex' : 'none';
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
            buttons.forEach(btn => {
                btn.classList.remove('active', 'btn-danger', 'btn-success', 'btn-info');
                btn.classList.add(`btn-outline-${btn.dataset.value === 'pengeluaran' ? 'danger' : (btn.dataset.value === 'pemasukan' ? 'success' : 'info')}`);
            });
            button.classList.add('active', `btn-${button.dataset.value === 'pengeluaran' ? 'danger' : (button.dataset.value === 'pemasukan' ? 'success' : 'info')}`);
            toggleFormFields();
        });
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
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            showToast('Harap isi semua field yang wajib.', 'error');
            return;
        }
        form.classList.remove('was-validated');

        const formData = new FormData(form);
        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;

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
                    form.classList.remove('was-validated');
                    document.getElementById('tanggal').valueAsDate = new Date();
                    // Kembalikan ke jenis transaksi default (misal: pengeluaran)
                    jenisBtnGroup.querySelector('button[data-value="pengeluaran"]').click();
                } else {
                    // Untuk 'update', tutup modal seperti biasa
                    modal.hide();
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
                form.classList.remove('was-validated');
                document.getElementById('transaksi-id').value = tx.id;
                document.getElementById('transaksi-action').value = 'update';
                jenisBtnGroup.querySelector(`button[data-value="${tx.jenis}"]`).click(); // Simulate click to set value and style
                document.getElementById('tanggal').value = tx.tanggal;
                document.getElementById('jumlah').value = tx.jumlah;
                document.getElementById('nomor_referensi').value = tx.nomor_referensi;
                document.getElementById('keterangan').value = tx.keterangan;
                toggleFormFields(); // Update visible fields based on 'jenis'
                
                // Set selected values for dropdowns
                if (tx.jenis === 'pemasukan') { document.getElementById('kas_account_id_pemasukan').value = tx.kas_account_id; document.getElementById('account_id_pemasukan').value = tx.account_id; } 
                else if (tx.jenis === 'pengeluaran') { document.getElementById('kas_account_id_pengeluaran').value = tx.kas_account_id; document.getElementById('account_id_pengeluaran').value = tx.account_id; } 
                else if (tx.jenis === 'transfer') { document.getElementById('kas_account_id_transfer').value = tx.kas_account_id; document.getElementById('kas_tujuan_account_id').value = tx.kas_tujuan_account_id; }
                modal.show();
            } catch (error) { showToast(`Gagal memuat data transaksi: ${error.message}`, 'error'); }
        }

        if (viewJournalBtn) {
            const id = viewJournalBtn.dataset.id;
            const jurnalModal = bootstrap.Modal.getInstance(jurnalDetailModalEl) || new bootstrap.Modal(jurnalDetailModalEl);
            const modalBody = document.getElementById('jurnal-detail-body');
            modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border"></div></div>';
            jurnalModal.show();

            try {
                const response = await fetch(`${basePath}/api/transaksi?action=get_journal_entry&id=${id}`);
                const result = await response.json();
                if (result.status !== 'success') throw new Error(result.message);

                const { transaksi, jurnal } = result.data;
                let tableHtml = `
                    <p><strong>Tanggal:</strong> ${new Date(transaksi.tanggal).toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'})}</p>
                    <p><strong>No. Referensi:</strong> ${transaksi.nomor_referensi || '-'}</p>
                    <p><strong>Keterangan:</strong> ${transaksi.keterangan}</p>
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Akun</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                jurnal.forEach(entry => {
                    tableHtml += `
                        <tr>
                            <td>${entry.akun}</td>
                            <td class="text-end">${entry.debit > 0 ? currencyFormatter.format(entry.debit) : '-'}</td>
                            <td class="text-end">${entry.kredit > 0 ? currencyFormatter.format(entry.kredit) : '-'}</td>
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

    modalEl.addEventListener('show.bs.modal', (e) => {
        const button = e.relatedTarget;
        if (button && button.dataset.action === 'add') { 
            // Ambil pengaturan default dari API
            fetch(`${basePath}/api/settings`).then(res => res.json()).then(result => {
                const settings = result.data || {};
                document.getElementById('transaksiModalLabel').textContent = 'Tambah Transaksi Baru';
                form.reset();
                form.classList.remove('was-validated');
                document.getElementById('transaksi-id').value = '';
                document.getElementById('transaksi-action').value = 'add';
                document.getElementById('tanggal').valueAsDate = new Date();
                
                // Set default to 'pengeluaran' by simulating a click
                jenisBtnGroup.querySelector('button[data-value="pengeluaran"]').click();

                // Set default cash accounts
                if (settings.default_cash_in) document.getElementById('kas_account_id_pemasukan').value = settings.default_cash_in;
                if (settings.default_cash_out) document.getElementById('kas_account_id_pengeluaran').value = settings.default_cash_out;
                if (settings.default_cash_out) document.getElementById('kas_account_id_transfer').value = settings.default_cash_out;
            });
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
        localStorage.setItem('transaksi_limit', limitSelect.value); // Save limit on change
    };

    [searchInput, akunKasFilter, bulanFilter, tahunFilter, limitSelect].forEach(el => {
        el.addEventListener('change', combinedFilterHandler);
    });
    searchInput.addEventListener('input', combinedFilterHandler);

    // --- Initial Load ---
    setupFilters();
    loadAccountsForForm().then(() => {
        loadTransaksi();
    });
}