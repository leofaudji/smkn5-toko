function initPengaturanKspPage() {
    // Tab Switching Logic
    document.querySelectorAll('[data-tabs-target]').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('[role="tabpanel"]').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('[role="tab"]').forEach(el => {
                el.classList.remove('border-primary', 'text-primary');
                el.classList.add('border-transparent');
            });
            document.querySelector(tab.dataset.tabsTarget).classList.remove('hidden');
            tab.classList.remove('border-transparent');
            tab.classList.add('border-primary', 'text-primary');
        });
    });

    // Attach global functions needed for HTML onclick attributes
    window.loadJenisSimpanan = function() {
        fetch(`${basePath}/api/ksp/pengaturan?action=list_jenis_simpanan`).then(r=>r.json()).then(res => {
            const tbody = document.getElementById('table-jenis-simpanan');
            if(tbody) {
                tbody.innerHTML = res.data.map(item => `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">${item.nama}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">${item.kode_akun} - ${item.nama_akun}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 capitalize">${item.tipe}</td>
                        <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-white">${new Intl.NumberFormat('id-ID').format(item.nominal_default)}</td>
                        <td class="px-6 py-4 text-center">
                            <button onclick='editJenisSimpanan(${JSON.stringify(item)})' class="text-blue-600 hover:text-blue-900 mr-2"><i class="bi bi-pencil"></i></button>
                            <button onclick="deleteItem('jenis_simpanan', ${item.id})" class="text-red-600 hover:text-red-900"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                `).join('');
            }
        });
    };

    window.loadKategoriTransaksi = function() {
        fetch(`${basePath}/api/ksp/pengaturan?action=list_kategori_transaksi`).then(r=>r.json()).then(res => {
            const tbody = document.getElementById('table-kategori-transaksi');
            if(tbody) {
                tbody.innerHTML = res.data.map(item => `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">${item.nama}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${item.tipe_aksi === 'setor' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${item.tipe_aksi.toUpperCase()}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 capitalize">${item.posisi}</td>
                        <td class="px-6 py-4 text-center">
                            <button onclick='editKategoriTransaksi(${JSON.stringify(item)})' class="text-blue-600 hover:text-blue-900 mr-2"><i class="bi bi-pencil"></i></button>
                            <button onclick="deleteItem('kategori_transaksi', ${item.id})" class="text-red-600 hover:text-red-900"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                `).join('');
            }
        });
    };

    window.loadJenisPinjaman = function() {
        fetch(`${basePath}/api/ksp/pengaturan?action=list_jenis_pinjaman`).then(r=>r.json()).then(res => {
            const tbody = document.getElementById('table-jenis-pinjaman');
            if(tbody) {
                tbody.innerHTML = res.data.map(item => `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">${item.nama}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">${item.bunga_per_tahun}%</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">${item.kode_piutang} - ${item.akun_piutang}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">${item.kode_bunga} - ${item.akun_bunga}</td>
                        <td class="px-6 py-4 text-center">
                            <button onclick='editJenisPinjaman(${JSON.stringify(item)})' class="text-blue-600 hover:text-blue-900 mr-2"><i class="bi bi-pencil"></i></button>
                            <button onclick="deleteItem('jenis_pinjaman', ${item.id})" class="text-red-600 hover:text-red-900"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                `).join('');
            }
        });
    };

    window.loadTipeAgunan = function() {
        fetch(`${basePath}/api/ksp/pengaturan?action=list_tipe_agunan`).then(r=>r.json()).then(res => {
            const tbody = document.getElementById('table-tipe-agunan');
            if(tbody) {
                tbody.innerHTML = res.data.map(item => {
                    let configPreview = item.config;
                    if(configPreview.length > 50) configPreview = configPreview.substring(0, 50) + '...';
                    return `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">${item.nama}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono text-xs" title='${item.config}'>${configPreview}</td>
                        <td class="px-6 py-4 text-center">
                            <button onclick='editTipeAgunan(${JSON.stringify(item)})' class="text-blue-600 hover:text-blue-900 mr-2"><i class="bi bi-pencil"></i></button>
                            <button onclick="deleteItem('tipe_agunan', ${item.id})" class="text-red-600 hover:text-red-900"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                `}).join('');
            }
        });
    };

    window.openModalJenisSimpanan = function() {
        document.getElementById('form-jenis-simpanan').reset();
        document.getElementById('js-id').value = '';
        fetch(`${basePath}/api/ksp/pengaturan?action=get_accounts`).then(r=>r.json()).then(res => {
            document.getElementById('js-akun-id').innerHTML = res.data.map(a => `<option value="${a.id}">${a.kode_akun} - ${a.nama_akun}</option>`).join('');
            document.getElementById('modal-jenis-simpanan').classList.remove('hidden');
        });
    };

    window.editJenisSimpanan = function(item) {
        openModalJenisSimpanan();
        setTimeout(() => {
            document.getElementById('js-id').value = item.id;
            document.getElementById('js-nama').value = item.nama;
            document.getElementById('js-akun-id').value = item.akun_id;
            document.getElementById('js-tipe').value = item.tipe;
            document.getElementById('js-nominal').value = item.nominal_default;
        }, 200);
    };

    window.openModalKategoriTransaksi = function() {
        document.getElementById('form-kategori-transaksi').reset();
        document.getElementById('kt-id').value = '';
        document.getElementById('modal-kategori-transaksi').classList.remove('hidden');
    };

    window.editKategoriTransaksi = function(item) {
        openModalKategoriTransaksi();
        document.getElementById('kt-id').value = item.id;
        document.getElementById('kt-nama').value = item.nama;
        document.getElementById('kt-tipe').value = item.tipe_aksi;
        document.getElementById('kt-posisi').value = item.posisi;
    };

    window.openModalJenisPinjaman = function() {
        document.getElementById('form-jenis-pinjaman').reset();
        document.getElementById('jp-id').value = '';
        fetch(`${basePath}/api/ksp/pengaturan?action=get_accounts`).then(r=>r.json()).then(res => {
            const options = res.data.map(a => `<option value="${a.id}">${a.kode_akun} - ${a.nama_akun} (${a.tipe_akun})</option>`).join('');
            document.getElementById('jp-akun-piutang').innerHTML = options;
            document.getElementById('jp-akun-bunga').innerHTML = options;
            document.getElementById('modal-jenis-pinjaman').classList.remove('hidden');
        });
    };

    window.editJenisPinjaman = function(item) {
        openModalJenisPinjaman();
        setTimeout(() => {
            document.getElementById('jp-id').value = item.id;
            document.getElementById('jp-nama').value = item.nama;
            document.getElementById('jp-bunga').value = item.bunga_per_tahun;
            document.getElementById('jp-akun-piutang').value = item.akun_piutang_id;
            document.getElementById('jp-akun-bunga').value = item.akun_pendapatan_bunga_id;
        }, 200);
    };

    window.openModalTipeAgunan = function() {
        document.getElementById('form-tipe-agunan').reset();
        document.getElementById('ta-id').value = '';
        document.getElementById('modal-tipe-agunan').classList.remove('hidden');
    };

    window.editTipeAgunan = function(item) {
        openModalTipeAgunan();
        document.getElementById('ta-id').value = item.id;
        document.getElementById('ta-nama').value = item.nama;
        document.getElementById('ta-config').value = item.config;
    };

    window.deleteItem = function(type, id) {
        if(!confirm('Yakin ingin menghapus?')) return;
        fetch(`${basePath}/api/ksp/pengaturan?action=delete_${type}`, {
            method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id: id})
        }).then(r=>r.json()).then(res => {
            alert(res.message);
            if(type === 'jenis_simpanan') loadJenisSimpanan();
            else if(type === 'kategori_transaksi') loadKategoriTransaksi();
            else if(type === 'jenis_pinjaman') loadJenisPinjaman();
            else if(type === 'tipe_agunan') loadTipeAgunan();
        });
    };

    // Event listeners for forms
    ['jenis-simpanan', 'kategori-transaksi', 'jenis-pinjaman', 'tipe-agunan'].forEach(type => {
        const form = document.getElementById(`form-${type}`);
        if(form) {
            // Remove existing listeners to avoid duplicates if re-initialized (though navigate usually clears DOM)
            const newForm = form.cloneNode(true);
            form.parentNode.replaceChild(newForm, form);
            
            newForm.addEventListener('submit', function(e) {
                e.preventDefault();
                fetch(`${basePath}/api/ksp/pengaturan?action=save_${type.replace('-','_')}`, {
                    method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(Object.fromEntries(new FormData(this)))
                }).then(r=>r.json()).then(res => {
                    alert(res.message);
                    document.getElementById(`modal-${type}`).classList.add('hidden');
                    if(type === 'jenis-simpanan') loadJenisSimpanan();
                    else if(type === 'kategori-transaksi') loadKategoriTransaksi();
                    else if(type === 'jenis-pinjaman') loadJenisPinjaman();
                    else if(type === 'tipe-agunan') loadTipeAgunan();
                });
            });
        }
    });

    // --- Notification Settings Logic ---
    function initNotificationSettings() {
        const form = document.getElementById('form-notifikasi');
        if (!form) return;

        // Load initial settings
        fetch(`${basePath}/api/ksp/pengaturan?action=get_notification_settings`)
            .then(response => response.json())
            .then(json => {
                if (json.success) {
                    const appIdEl = document.getElementById('onesignal_app_id');
                    const apiKeyEl = document.getElementById('onesignal_rest_api_key');
                    if (appIdEl) appIdEl.value = json.data.onesignal_app_id || '';
                    if (apiKeyEl) apiKeyEl.value = json.data.onesignal_rest_api_key || '';

                    const dueSoonTitle = document.getElementById('notification_due_soon_title');
                    if (dueSoonTitle) dueSoonTitle.value = json.data.notification_due_soon_title || '';
                    const dueSoonBody = document.getElementById('notification_due_soon_body');
                    if (dueSoonBody) dueSoonBody.value = json.data.notification_due_soon_body || '';
                    const overdueTitle = document.getElementById('notification_overdue_title');
                    if (overdueTitle) overdueTitle.value = json.data.notification_overdue_title || '';
                    const overdueBody = document.getElementById('notification_overdue_body');
                    if (overdueBody) overdueBody.value = json.data.notification_overdue_body || '';
                }
            })
            .catch(err => console.error('Gagal memuat pengaturan notifikasi:', err));

        // Handle Test Notification
        const testBtn = document.getElementById('btn-test-notifikasi');
        if (testBtn) {
            testBtn.addEventListener('click', function() {
                this.disabled = true;
                this.innerHTML = '<span class="animate-spin inline-block w-4 h-4 border-2 border-white/50 border-t-white rounded-full"></span> Mengirim...';

                fetch(`${basePath}/api/ksp/pengaturan?action=test_notification`, { method: 'POST' })
                    .then(res => res.json())
                    .then(json => {
                        if (json.success) {
                            Swal.fire('Terkirim!', json.message, 'success');
                        } else {
                            Swal.fire('Gagal!', json.message, 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error', 'Gagal mengirim notifikasi tes. Periksa konsol untuk detail.', 'error');
                    })
                    .finally(() => {
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-send"></i> Test Notifikasi';
                    });
            });
        }

        // Handle form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = 'Menyimpan...';

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            fetch(`${basePath}/api/ksp/pengaturan?action=save_notification_settings`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(json => {
                if (json.success) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message, timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: json.message });
                }
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan jaringan.' });
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerText = originalText;
            });
        });
    }

    function initMassNotification() {
        const form = document.getElementById('form-mass-notification');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            const title = this.querySelector('[name="title"]').value;
            const body = this.querySelector('[name="body"]').value;

            if (!title || !body) {
                Swal.fire('Gagal', 'Judul dan isi pesan tidak boleh kosong.', 'error');
                return;
            }

            Swal.fire({
                title: 'Kirim Notifikasi?',
                text: "Pesan akan dikirim ke semua anggota yang berlangganan. Aksi ini tidak dapat dibatalkan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Kirim!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="animate-spin inline-block w-4 h-4 border-2 border-white/50 border-t-white rounded-full"></span> Mengirim...';

                    fetch(`${basePath}/api/ksp/pengaturan?action=send_mass_notification`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ title: title, body: body })
                    })
                    .then(res => res.json())
                    .then(json => {
                        if (json.success) {
                            Swal.fire('Terkirim!', json.message, 'success');
                            loadNotificationLogs(); // Refresh logs
                            form.reset();
                        } else {
                            Swal.fire('Gagal!', json.message, 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error', 'Gagal mengirim notifikasi. Periksa konsol untuk detail.', 'error');
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-broadcast"></i> Kirim ke Semua Anggota';
                    });
                }
            });
        });
    }

    function loadNotificationLogs() {
        const tbody = document.getElementById('table-notification-logs');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="3" class="px-4 py-4 text-center text-gray-500">Memuat riwayat...</td></tr>';

        // Get filter values
        const status = document.getElementById('log-filter-status').value;
        const startDate = document.getElementById('log-filter-start-date').value;
        const endDate = document.getElementById('log-filter-end-date').value;

        // Build query string
        const params = new URLSearchParams({
            action: 'get_notification_logs',
            status: status,
            start_date: startDate,
            end_date: endDate
        });

        fetch(`${basePath}/api/ksp/pengaturan?${params.toString()}`)
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data.length > 0) {
                    tbody.innerHTML = res.data.map(log => `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap text-xs">
                                ${new Date(log.sent_at).toLocaleString('id-ID')}
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-800 dark:text-white text-sm">${log.title}</p>
                                <p class="text-gray-500 dark:text-gray-400 text-xs line-clamp-1">${log.body}</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded-full text-xs font-bold ${log.status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">${log.status.toUpperCase()}</span>
                                ${log.status === 'failed' ? `<i class="bi bi-info-circle text-red-500 ml-1 cursor-pointer" title="${log.error_message}"></i>` : ''}
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="3" class="px-4 py-4 text-center text-gray-500">Belum ada riwayat notifikasi.</td></tr>';
                }
            });
    };

    // Initial load
    loadJenisSimpanan();
    loadKategoriTransaksi();
    loadJenisPinjaman();
    loadTipeAgunan();
    initNotificationSettings();
    initMassNotification();
    loadNotificationLogs();
}