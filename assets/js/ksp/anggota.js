function initAnggotaPage() {
    const tableBody = document.getElementById('anggota-table-body');
    const searchInput = document.getElementById('search-anggota');
    const paginationInfo = document.getElementById('pagination-info');
    const paginationControls = document.getElementById('pagination-controls');
    const modal = document.getElementById('modal-anggota');
    const form = document.getElementById('form-anggota');
    const modalTitle = document.getElementById('modal-title');
    const btnAdd = document.getElementById('btn-add-anggota');
    const btnCancel = document.getElementById('btn-cancel-modal');
    const btnPrintBatch = document.getElementById('btn-print-batch');
    const selectAllCheckbox = document.getElementById('select-all-members');
    const selectedCountSpan = document.getElementById('selected-count');
    const btnSyncSP = document.getElementById('btn-sync-sp');

    let currentPage = 1;
    let limit = 10;
    let searchTimeout;
    let currentSortBy = 'created_at';
    let currentSortDir = 'DESC';

    // Load data awal
    loadData();

    // Event Listeners
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            loadData();
        }, 500);
    });

    btnAdd.addEventListener('click', function () {
        openModal();
    });

    btnCancel.addEventListener('click', function () {
        closeModal();
    });

    if (btnSyncSP) {
        btnSyncSP.addEventListener('click', function () {
            syncFromSP();
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        saveData();
    });

    // Event Delegation untuk checkbox anggota
    tableBody.addEventListener('change', function (e) {
        if (e.target.classList.contains('member-checkbox')) {
            updateBatchPrintButton();
        }
    });

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.member-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBatchPrintButton();
        });
    }

    if (btnPrintBatch) {
        btnPrintBatch.addEventListener('click', function () {
            const selectedIds = Array.from(document.querySelectorAll('.member-checkbox:checked')).map(cb => cb.value);
            if (selectedIds.length > 0) {
                // Gunakan fungsi printCard yang sudah dimodifikasi untuk menerima array ID
                // Kita modifikasi sedikit printCard agar bisa menerima array atau single ID
                printBatchCards(selectedIds);
            }
        });
    }

    // Functions
    function loadData() {
        const search = searchInput.value;
        const url = `${basePath}/api/ksp/anggota?action=get_all&page=${currentPage}&limit=${limit}&search=${encodeURIComponent(search)}&sort_by=${currentSortBy}&sort_dir=${currentSortDir}`;

        tableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Memuat data...</td></tr>';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderTable(data.data);
                    renderPagination(data.total, data.page, data.limit);
                    // Reset checkbox select all saat load data baru
                    if (selectAllCheckbox) selectAllCheckbox.checked = false;
                    updateBatchPrintButton();
                } else {
                    alert('Gagal memuat data: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                tableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-red-500">Terjadi kesalahan saat memuat data.</td></tr>';
            });
    }

    function renderTable(data) {
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Tidak ada data anggota.</td></tr>';
            return;
        }

        tableBody.innerHTML = data.map(item => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="checkbox" class="member-checkbox rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" value="${item.id}">
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.nomor_anggota}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${item.nik || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-700 dark:text-gray-200 cursor-pointer hover:text-primary hover:underline" onclick="viewPurchaseHistory(${item.id}, '${item.nama_lengkap.replace(/'/g, "\\'")}')">
                    <i class="bi bi-person-fill mr-1 text-gray-400"></i> ${item.nama_lengkap}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${item.no_telepon || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${formatDate(item.tanggal_daftar)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${item.status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        ${item.status.charAt(0).toUpperCase() + item.status.slice(1)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="viewPurchaseHistory(${item.id}, '${item.nama_lengkap.replace(/'/g, "\\'")}')" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3" title="Riwayat Belanja"><i class="bi bi-clock-history"></i></button>
                    <button onclick="printCard(${item.id})" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3" title="Cetak Kartu"><i class="bi bi-person-badge"></i></button>
                    <button onclick="editAnggota(${item.id})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3" title="Edit"><i class="bi bi-pencil-square"></i></button>
                    <button onclick="deleteAnggota(${item.id})" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" title="Hapus"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(total, page, limit) {
        const totalPages = Math.ceil(total / limit);
        const start = (page - 1) * limit + 1;
        const end = Math.min(page * limit, total);

        paginationInfo.textContent = `Menampilkan ${total === 0 ? 0 : start} sampai ${end} dari ${total} data`;

        let controls = '';
        if (page > 1) {
            controls += `<button onclick="changePage(${page - 1})" class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Sebelumnya</button>`;
        }
        if (page < totalPages) {
            controls += `<button onclick="changePage(${page + 1})" class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Selanjutnya</button>`;
        }
        paginationControls.innerHTML = controls;
    }

    window.changePage = function (page) {
        currentPage = page;
        loadData();
    };

    function openModal(id = null) {
        form.reset();
        document.getElementById('anggota-id').value = '';

        if (id) {
            modalTitle.textContent = 'Edit Anggota';
            fetch(`${basePath}/api/ksp/anggota?action=get_detail&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const item = data.data;
                        document.getElementById('anggota-id').value = item.id;
                        document.getElementById('nama_lengkap').value = item.nama_lengkap;
                        document.getElementById('nik').value = item.nik || '';
                        document.getElementById('no_telepon').value = item.no_telepon;
                        document.getElementById('email').value = item.email;
                        document.getElementById('alamat').value = item.alamat;
                        document.getElementById('tanggal_daftar').value = item.tanggal_daftar;
                        document.getElementById('status').value = item.status;
                        modal.classList.remove('hidden');
                    }
                });
        } else {
            modalTitle.textContent = 'Tambah Anggota';
            document.getElementById('tanggal_daftar').value = new Date().toISOString().split('T')[0];
            modal.classList.remove('hidden');
        }
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    function saveData() {
        const id = document.getElementById('anggota-id').value;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        const action = id ? 'update' : 'store';

        fetch(`${basePath}/api/ksp/anggota?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    alert(response.message);
                    closeModal();
                    loadData();
                } else {
                    alert('Gagal: ' + response.message);
                }
            })
            .catch(err => console.error(err));
    }

    function syncFromSP() {
        if (!confirm('Apakah Anda yakin ingin menyinkronkan data anggota dari aplikasi Simpan Pinjam? Data lokal yang memiliki nomor anggota yang sama akan diperbarui.')) {
            return;
        }

        const originalBtnHtml = btnSyncSP.innerHTML;
        btnSyncSP.disabled = true;
        btnSyncSP.innerHTML = '<i class="bi bi-arrow-repeat animate-spin mr-2"></i> Menyinkronkan...';

        fetch(`${basePath}/api/ksp/anggota?action=sync_from_sp`)
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    alert(response.message);
                    loadData();
                } else {
                    alert('Gagal sinkronasi: ' + response.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan koneksi saat sinkronasi.');
            })
            .finally(() => {
                btnSyncSP.disabled = false;
                btnSyncSP.innerHTML = originalBtnHtml;
            });
    }

    window.editAnggota = function (id) {
        openModal(id);
    };

    window.sortData = function (column) {
        if (currentSortBy === column) {
            currentSortDir = currentSortDir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            currentSortBy = column;
            currentSortDir = 'ASC';
        }

        // Update icons
        document.querySelectorAll('[id^="sort-icon-"]').forEach(icon => {
            if (icon.id === `sort-icon-${column}`) {
                icon.className = currentSortDir === 'ASC' ? 'bi bi-sort-up text-primary' : 'bi bi-sort-down text-primary';
            } else {
                icon.className = 'bi bi-arrow-down-up text-gray-300 group-hover:text-gray-500';
            }
        });

        loadData();
    };

    window.viewPurchaseHistory = function (id, name) {
        const historyModal = document.getElementById('modal-history');
        const historyTableBody = document.getElementById('history-table-body');
        const historyTitle = document.getElementById('history-modal-title');

        historyTitle.textContent = `Riwayat Pembelanjaan: ${name}`;
        historyTableBody.innerHTML = '<tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">Memuat riwayat...</td></tr>';
        historyModal.classList.remove('hidden');

        fetch(`${basePath}/api/ksp/anggota?action=get_purchase_history&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.data.length === 0) {
                        historyTableBody.innerHTML = '<tr><td colspan="3" class="px-4 py-4 text-center text-gray-500">Belum ada riwayat transaksi.</td></tr>';
                    } else {
                        historyTableBody.innerHTML = data.data.map(tx => {
                            let badges = '';
                            const types = tx.tipe_list.split(',');
                            
                            if (types.includes('belanja')) badges += '<span class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-blue-100 text-blue-800 mr-1">Toko</span>';
                            if (types.includes('wb_setor')) badges += '<span class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-green-100 text-green-800 mr-1">WB Setor</span>';
                            if (types.includes('wb_belanja')) badges += '<span class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-orange-100 text-orange-800 mr-1">WB Potong</span>';

                            const detailBtn = tx.has_sale_detail == 1 
                                ? `<button onclick="viewSaleDetail(${tx.sale_id})" class="text-primary hover:underline text-xs" title="Lihat Detail"><i class="bi bi-eye"></i> Detail</button>` 
                                : '';

                            return `
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-2">
                                    <div class="text-xs text-gray-500">${formatDate(tx.tanggal)}</div>
                                    <div class="flex items-center my-1">${badges}</div>
                                    <div class="text-[11px] font-mono text-gray-400">#${tx.nomor_referensi}</div>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">${formatRupiah(tx.jumlah)}</div>
                                    <div class="text-[10px] text-gray-400 capitalize">${tx.status}</div>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    ${detailBtn}
                                </td>
                            </tr>
                        `;}).join('');
                    }
                } else {
                    historyTableBody.innerHTML = `<tr><td colspan="3" class="px-4 py-4 text-center text-red-500">Gagal: ${data.message}</td></tr>`;
                }
            })
            .catch(err => {
                console.error(err);
                historyTableBody.innerHTML = '<tr><td colspan="5" class="px-4 py-4 text-center text-red-500">Terjadi kesalahan.</td></tr>';
            });
    };

    window.closeHistoryModal = function () {
        document.getElementById('modal-history').classList.add('hidden');
    };

    window.viewSaleDetail = function (id) {
        fetch(`${basePath}/api/penjualan?action=get_detail&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const header = data.data;
                    const items = data.data.items || [];
                    let itemsHtml = '<div class="text-left font-sans"><table class="w-full text-xs mt-3 border-collapse">';
                    itemsHtml += '<thead class="bg-gray-50"><tr><th class="border p-1">Barang</th><th class="border p-1 text-center">Qty</th><th class="border p-1 text-right">Subtotal</th></tr></thead><tbody>';
                    items.forEach(it => {
                        itemsHtml += `<tr><td class="border p-1">${it.nama_barang}</td><td class="border p-1 text-center">${it.quantity}</td><td class="border p-1 text-right">${formatRupiah(it.subtotal)}</td></tr>`;
                    });
                    itemsHtml += '</tbody></table>';
                    itemsHtml += `<div class="mt-3 text-right font-bold">Total: ${formatRupiah(header.total)}</div></div>`;

                    Swal.fire({
                        title: `Detail Transaksi: ${header.nomor_referensi}`,
                        html: itemsHtml,
                        width: '600px',
                        confirmButtonText: 'Tutup'
                    });
                }
            });
    };

    function updateBatchPrintButton() {
        const selectedCount = document.querySelectorAll('.member-checkbox:checked').length;
        if (selectedCount > 0) {
            btnPrintBatch.classList.remove('hidden');
            selectedCountSpan.textContent = selectedCount;
        } else {
            btnPrintBatch.classList.add('hidden');
        }

        // Update select all state visual
        const allCheckboxes = document.querySelectorAll('.member-checkbox');
        if (allCheckboxes.length > 0 && selectedCount === allCheckboxes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else if (selectedCount > 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
    }

    function printBatchCards(ids) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';

        const inputReport = document.createElement('input');
        inputReport.type = 'hidden';
        inputReport.name = 'report';
        inputReport.value = 'kartu_anggota';
        form.appendChild(inputReport);

        const inputIds = document.createElement('input');
        inputIds.type = 'hidden';
        inputIds.name = 'ids';
        inputIds.value = ids.join(',');
        form.appendChild(inputIds);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    window.printCard = function (id) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';

        const params = { report: 'kartu_anggota', id: id };
        for (const key in params) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = params[key];
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    };

    window.deleteAnggota = function (id) {
        if (confirm('Apakah Anda yakin ingin menghapus anggota ini?')) {
            fetch(`${basePath}/api/ksp/anggota?action=delete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        loadData();
                    } else {
                        alert('Gagal: ' + response.message);
                    }
                });
        }
    };

    function formatDate(dateString) {
        if (!dateString) return '-';
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('id-ID', options);
    }
}