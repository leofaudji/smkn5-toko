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

    // Initial load
    loadJenisSimpanan();
    loadKategoriTransaksi();
    loadJenisPinjaman();
    loadTipeAgunan();
}