function initSimpananPage() {
    const tableBody = document.getElementById('simpanan-table-body');
    const searchInput = document.getElementById('search-simpanan');
    const modal = document.getElementById('modal-simpanan');
    const form = document.getElementById('form-simpanan');
    const btnAdd = document.getElementById('btn-add-simpanan');
    const btnCancel = document.getElementById('btn-cancel-modal');
    
    // Load Data Awal
    loadData();
    loadSummary();
    loadDropdowns();

    btnAdd.addEventListener('click', () => {
        form.reset();
        modal.classList.remove('hidden');
    });

    btnCancel.addEventListener('click', () => {
        modal.classList.add('hidden');
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        fetch(`${basePath}/api/ksp/simpanan?action=store`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                modal.classList.add('hidden');
                loadData();
                loadSummary();
                
                Swal.fire({
                    title: 'Berhasil!',
                    text: res.message,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-printer"></i> Cetak Struk',
                    cancelButtonText: 'Tutup',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed && res.transaksi_id) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `${basePath}/api/pdf`;
                        form.target = '_blank';
                        
                        const inputReport = document.createElement('input');
                        inputReport.type = 'hidden';
                        inputReport.name = 'report';
                        inputReport.value = 'struk_simpanan';
                        form.appendChild(inputReport);

                        const inputId = document.createElement('input');
                        inputId.type = 'hidden';
                        inputId.name = 'id';
                        inputId.value = res.transaksi_id;
                        form.appendChild(inputId);

                        document.body.appendChild(form);
                        form.submit();
                        document.body.removeChild(form);
                    }
                });
            } else {
                showNotification('Gagal: ' + res.message, 'error');
            }
        });
    });

    function loadData() {
        fetch(`${basePath}/api/ksp/simpanan?action=get_transaksi`)
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    renderTable(res.data);
                }
            });
    }

    function loadSummary() {
        fetch(`${basePath}/api/ksp/simpanan?action=get_summary`)
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    const d = res.data;
                    document.getElementById('summary-saldo').textContent = formatRupiah(d.total_saldo);
                    document.getElementById('summary-setor').textContent = formatRupiah(d.total_setor_hari_ini);
                    document.getElementById('summary-tarik').textContent = formatRupiah(d.total_tarik_hari_ini);
                }
            });
    }

    function renderTable(data) {
        tableBody.innerHTML = data.map(item => {
            const isSetor = item.jenis_transaksi === 'setor';
            const badgeClass = isSetor ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            const icon = isSetor ? '<i class="bi bi-arrow-down-circle mr-1"></i>' : '<i class="bi bi-arrow-up-circle mr-1"></i>';
            
            return `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${formatDate(item.tanggal)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full bg-gray-100 dark:bg-gray-600 flex items-center justify-center mr-3 text-gray-500">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">${item.nama_lengkap}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">${item.nomor_referensi}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${item.jenis_simpanan}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${badgeClass}">
                            ${icon} ${item.jenis_transaksi.toUpperCase()}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold ${isSetor ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">
                        ${formatRupiah(item.jumlah)}
                    </td>
                </tr>
            `;
        }).join('');
    }

    function loadDropdowns() {
        // Load Anggota
        fetch(`${basePath}/api/ksp/simpanan?action=get_anggota_list`)
            .then(res => res.json())
            .then(res => {
                const select = document.getElementById('anggota_id');
                select.innerHTML = '<option value="">Pilih Anggota...</option>' + 
                    res.data.map(a => `<option value="${a.id}">${a.nama_lengkap} (${a.nomor_anggota})</option>`).join('');
            });
        
        // Load Kategori Transaksi (Jenis Transaksi)
        fetch(`${basePath}/api/ksp/pengaturan?action=list_kategori_transaksi`).then(r=>r.json()).then(res => {
            const select = document.getElementById('jenis_transaksi');
            select.innerHTML = res.data.map(k => `<option value="${k.id}">${k.nama}</option>`).join('');
        });

        // Load Jenis Simpanan
        fetch(`${basePath}/api/ksp/simpanan?action=get_jenis_simpanan`)
            .then(res => res.json())
            .then(res => {
                const select = document.getElementById('jenis_simpanan_id');
                select.innerHTML = res.data.map(j => `<option value="${j.id}">${j.nama}</option>`).join('');
            });

        // Load Akun Kas
        fetch(`${basePath}/api/ksp/simpanan?action=get_kas_accounts`)
            .then(res => res.json())
            .then(res => {
                const select = document.getElementById('akun_kas_id');
                select.innerHTML = res.data.map(k => `<option value="${k.id}">${k.kode_akun} - ${k.nama_akun}</option>`).join('');
            });
    }

    function formatRupiah(val) {
        if (isNaN(val)) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
    }
    
    function formatDate(dateString) {
        if (!dateString) return '-';
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('id-ID', options);
    }

    function showNotification(message, type = 'success') {
        const container = document.getElementById('notification-container');
        const notifId = 'notif-' + Date.now();

        const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
        const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-x-circle-fill';

        const notification = `
            <div id="${notifId}" class="transform transition-all duration-300 translate-x-full opacity-0">
                <div class="max-w-sm w-full ${bgColor} text-white shadow-lg rounded-lg pointer-events-auto flex ring-1 ring-black ring-opacity-5">
                    <div class="p-4 flex items-center">
                        <i class="bi ${icon} text-2xl"></i>
                        <p class="ml-3 font-medium">${message}</p>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', notification);
        
        const notifElement = document.getElementById(notifId);
        setTimeout(() => {
            notifElement.classList.remove('translate-x-full', 'opacity-0');
            notifElement.classList.add('translate-x-0', 'opacity-100');
        }, 10);

        setTimeout(() => {
            notifElement.remove();
        }, 5000);
    }
}
