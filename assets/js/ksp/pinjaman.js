function initPinjamanPage() {
    const tableBody = document.getElementById('pinjaman-table-body');
    const modal = document.getElementById('modal-pinjaman');
    const modalDetail = document.getElementById('modal-detail');
    const modalPembayaran = document.getElementById('modal-pembayaran-angsuran');
    const form = document.getElementById('form-pinjaman');
    const formPembayaran = document.getElementById('form-pembayaran');
    const modalPelunasan = document.getElementById('modal-pelunasan');
    const formPelunasan = document.getElementById('form-pelunasan');
    const btnAdd = document.getElementById('btn-add-pinjaman');
    const btnCancel = document.getElementById('btn-cancel-modal');
    const btnCloseDetail = document.getElementById('btn-close-detail');
    const btnCancelPayment = document.getElementById('btn-cancel-payment-modal');
    const btnCancelPelunasan = document.getElementById('btn-cancel-pelunasan');
    const btnPelunasan = document.getElementById('btn-pelunasan');
    const btnPrintPinjaman = document.getElementById('btn-print-pinjaman');
    const searchInput = document.getElementById('search-pinjaman');
    const statusFilter = document.getElementById('filter-status');
    
    let kasAccounts = [];
    let agunanTypes = [];
    let allPinjamanData = [];
    let currentPinjamanId = null;

    let currentPage = 1;
    const rowsPerPage = 10;
    let sortColumn = 'tanggal_pengajuan';
    let sortDirection = 'desc';

    loadData();
    loadDropdowns();

    btnAdd.addEventListener('click', () => {
        form.reset();
        document.getElementById('pinjaman_id').value = '';
        document.getElementById('modal-title').textContent = 'Formulir Pengajuan Pinjaman';
        // Reset agunan fields display
        renderAgunanFields(null);
        modal.classList.remove('hidden');
    });

    btnCancel.addEventListener('click', () => {
        modal.classList.add('hidden');
    });

    btnCloseDetail.addEventListener('click', () => {
        modalDetail.classList.add('hidden');
    });

    btnCancelPayment.addEventListener('click', () => {
        modalPembayaran.classList.add('hidden');
    });

    btnCancelPelunasan.addEventListener('click', () => {
        modalPelunasan.classList.add('hidden');
    });

    btnPrintPinjaman.addEventListener('click', () => {
        if (currentPinjamanId) {
            // Gunakan POST request via form hidden untuk URL yang lebih bersih
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `${basePath}/api/pdf`;
            form.target = '_blank';

            const inputReport = document.createElement('input');
            inputReport.type = 'hidden';
            inputReport.name = 'report';
            inputReport.value = 'detail_pinjaman';
            form.appendChild(inputReport);

            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'id';
            inputId.value = currentPinjamanId;
            form.appendChild(inputId);

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        } else {
            showNotification('Tidak ada data pinjaman untuk dicetak.', 'error');
        }
    });

    searchInput.addEventListener('input', () => {
        currentPage = 1;
        applyFiltersAndRender();
    });
    statusFilter.addEventListener('change', () => {
        currentPage = 1;
        applyFiltersAndRender();
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const data = {};
        const agunanDetail = {};

        // Pisahkan data form biasa dengan data detail agunan dinamis
        for (let [key, value] of formData.entries()) {
            if (key.startsWith('agunan_detail[')) {
                // Ambil nama field dari agunan_detail[nama_field]
                const fieldName = key.match(/\[(.*?)\]/)[1];
                agunanDetail[fieldName] = value;
            } else {
                data[key] = value;
            }
        }
        
        if (Object.keys(agunanDetail).length > 0) {
            data.agunan_detail = agunanDetail; // Kirim sebagai objek JSON
        }

        const action = data.id ? 'update' : 'store';

        fetch(`${basePath}/api/ksp/pinjaman?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                showNotification(res.message, 'success');
                modal.classList.add('hidden');
                loadData();
            } else {
                showNotification('Gagal: ' + res.message, 'error');
            }
        });
    });

    formPembayaran.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(formPembayaran);
        const data = Object.fromEntries(formData.entries());

        fetch(`${basePath}/api/ksp/pinjaman?action=pay_installment`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                modalPembayaran.classList.add('hidden');
                modalDetail.classList.add('hidden');
                loadData();
                
                Swal.fire({
                    title: 'Pembayaran Berhasil!',
                    text: res.message,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-printer"></i> Cetak Struk',
                    cancelButtonText: 'Tutup',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed && res.payment_ref) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `${basePath}/api/pdf`;
                        form.target = '_blank';
                        
                        const inputReport = document.createElement('input');
                        inputReport.type = 'hidden';
                        inputReport.name = 'report';
                        inputReport.value = 'struk_angsuran';
                        form.appendChild(inputReport);

                        const inputRef = document.createElement('input');
                        inputRef.type = 'hidden';
                        inputRef.name = 'payment_ref';
                        inputRef.value = res.payment_ref;
                        form.appendChild(inputRef);

                        document.body.appendChild(form);
                        form.submit();
                        document.body.removeChild(form);
                    }
                });
            } else {
                showNotification('Gagal: ' + res.message, 'error');
            }
        }).catch(err => {
            showNotification('Terjadi kesalahan jaringan.', 'error');
        });
    });

    // Handler Tombol Pelunasan di Modal Detail
    btnPelunasan.addEventListener('click', function() {
        if (!currentPinjamanId) return;
        
        fetch(`${basePath}/api/ksp/pinjaman?action=get_payoff_info&id=${currentPinjamanId}`)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    const data = res.data;
                    document.getElementById('pelunasan_pinjaman_id').value = currentPinjamanId;
                    document.getElementById('pelunasan-sisa-pokok').textContent = formatRupiah(data.sisa_pokok);
                    document.getElementById('pelunasan-sisa-bunga').textContent = formatRupiah(data.sisa_bunga);
                    document.getElementById('pelunasan-sisa-angsuran').textContent = data.sisa_angsuran + 'x';
                    
                    // Setup input potongan & kalkulasi total
                    const inputPotongan = document.getElementById('pelunasan_potongan');
                    inputPotongan.value = 0;
                    
                    const updateTotal = () => {
                        const potongan = parseFloat(inputPotongan.value) || 0;
                        const sisaBunga = parseFloat(data.sisa_bunga);
                        const bayarBunga = Math.max(0, sisaBunga - potongan);
                        const total = parseFloat(data.sisa_pokok) + bayarBunga;
                        document.getElementById('pelunasan-total-bayar').textContent = formatRupiah(total);
                    };
                    
                    inputPotongan.oninput = updateTotal;
                    updateTotal();

                    // Populate kas
                    const kasSelect = document.getElementById('pelunasan_akun_kas_id');
                    kasSelect.innerHTML = kasAccounts.map(k => `<option value="${k.id}">${k.nama_akun}</option>`).join('');

                    modalPelunasan.classList.remove('hidden');
                }
            });
    });

    formPelunasan.addEventListener('submit', function(e) {
        e.preventDefault();
        if(!confirm('Anda yakin ingin memproses pelunasan ini? Tindakan ini tidak dapat dibatalkan.')) return;

        const formData = new FormData(formPelunasan);
        fetch(`${basePath}/api/ksp/pinjaman?action=pay_off`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(Object.fromEntries(formData))
        }).then(r => r.json()).then(res => {
            if(res.success) { showNotification(res.message); modalPelunasan.classList.add('hidden'); modalDetail.classList.add('hidden'); loadData(); }
            else { showNotification(res.message, 'error'); }
        });
    });

    function loadData() {
        fetch(`${basePath}/api/ksp/pinjaman?action=get_all`)
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    allPinjamanData = res.data;
                    applyFiltersAndRender();
                    updateSummaryCards(allPinjamanData);
                }
            });
    }

    function updateSummaryCards(data) {
        const activeLoans = data.filter(p => p.status === 'aktif');
        const pendingLoans = data.filter(p => p.status === 'pending');

        const totalActive = activeLoans.length;
        const totalPlafon = activeLoans.reduce((sum, p) => sum + parseFloat(p.jumlah_pinjaman), 0);
        const totalSisa = activeLoans.reduce((sum, p) => sum + parseFloat(p.sisa_pokok), 0);
        const totalPending = pendingLoans.length;

        document.getElementById('summary-aktif').textContent = totalActive;
        document.getElementById('summary-plafon').innerHTML = `
            <div>${formatRupiah(totalSisa)}</div>
            <div class="text-xs text-green-600 dark:text-green-400 font-normal mt-1">Plafon: ${formatRupiah(totalPlafon)}</div>
        `;
        document.getElementById('summary-pending').textContent = totalPending;
    }

    function renderTable(data) {
        if (data.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-16 text-gray-500">
                <div class="flex flex-col items-center">
                    <i class="bi bi-inbox text-5xl text-gray-400"></i>
                    <p class="mt-3 text-lg">Tidak ada data yang cocok</p>
                    <p class="text-sm">Coba ubah kata kunci pencarian atau filter Anda.</p>
                </div>
            </td></tr>`;
            return;
        }
        tableBody.innerHTML = data.map(item => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer" onclick="showDetail(${item.id})">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">${item.nomor_pinjaman}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">${formatDate(item.tanggal_pengajuan)}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="h-10 w-10 flex-shrink-0 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            <i class="bi bi-person-fill text-gray-500"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">${item.nama_lengkap}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">${item.nomor_anggota}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">${item.jenis_pinjaman}</td>
                <td class="px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-white">${formatRupiah(item.jumlah_pinjaman)}</td>
                <td class="px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-white">${formatRupiah(item.sisa_pokok)}</td>
                <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-300">${item.tenor_bulan} Bln</td>
                <td class="px-6 py-4 text-center">
                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(item.status)}">
                        ${item.status.toUpperCase()}
                    </span>
                </td>
                <td class="px-6 py-4 text-center text-sm font-medium">
                    <button class="text-primary hover:text-primary-700 dark:hover:text-primary-400 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700" title="Detail">
                        <i class="bi bi-search"></i>
                    </button>
                    ${item.status === 'pending' ? `
                    <button onclick="event.stopPropagation(); editPinjaman(${item.id})" class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 ml-1" title="Edit">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button onclick="event.stopPropagation(); deletePinjaman(${item.id})" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 ml-1" title="Hapus">
                        <i class="bi bi-trash-fill"></i>
                    </button>` : ''}
                </td>
            </tr>
        `).join('');
    }

    window.editPinjaman = function(id) {
        fetch(`${basePath}/api/ksp/pinjaman?action=get_detail&id=${id}`)
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    const p = res.data;
                    
                    // Isi Form
                    document.getElementById('pinjaman_id').value = p.id;
                    document.getElementById('anggota_id').value = p.anggota_id;
                    document.getElementById('jenis_pinjaman_id').value = p.jenis_pinjaman_id;
                    document.getElementById('form-pinjaman').elements['jumlah_pinjaman'].value = parseFloat(p.jumlah_pinjaman);
                    document.getElementById('form-pinjaman').elements['tenor_bulan'].value = p.tenor_bulan;
                    document.getElementById('form-pinjaman').elements['tanggal_pengajuan'].value = p.tanggal_pengajuan;
                    document.getElementById('form-pinjaman').elements['keterangan'].value = p.keterangan || '';

                    // Handle Agunan
                    const tipeAgunanSelect = document.getElementById('tipe_agunan_id');
                    if (p.tipe_agunan_id) {
                        tipeAgunanSelect.value = p.tipe_agunan_id;
                        renderAgunanFields(p.tipe_agunan_id);
                        
                        // Isi detail agunan setelah field dirender
                        if (p.agunan_detail_json) {
                            try {
                                const detail = JSON.parse(p.agunan_detail_json);
                                for (const [key, value] of Object.entries(detail)) {
                                    const input = document.querySelector(`input[name="agunan_detail[${key}]"]`);
                                    if (input) input.value = value;
                                }
                            } catch (e) { console.error(e); }
                        }
                    } else {
                        tipeAgunanSelect.value = "";
                        renderAgunanFields(null);
                    }

                    document.getElementById('modal-title').textContent = 'Edit Pengajuan Pinjaman';
                    modal.classList.remove('hidden');
                }
            });
    };

    window.deletePinjaman = function(id) {
        Swal.fire({
            title: 'Anda yakin?',
            text: "Data pinjaman yang pending ini akan dihapus secara permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6e7881',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`${basePath}/api/ksp/pinjaman?action=delete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        showNotification(res.message, 'success');
                        loadData(); // Reload the table
                    } else {
                        showNotification('Gagal: ' + res.message, 'error');
                    }
                });
            }
        });
    };

    window.handleSort = function(column) {
        if (sortColumn === column) {
            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            sortColumn = column;
            sortDirection = 'asc';
        }
        currentPage = 1;
        applyFiltersAndRender();
    }

    window.changePage = function(page) {
        currentPage = page;
        applyFiltersAndRender();
    }

    function applyFiltersAndRender() {
        const searchTerm = searchInput.value.toLowerCase();
        const status = statusFilter.value;

        let filteredData = allPinjamanData.filter(item => {
            const matchesSearch = (
                item.nomor_pinjaman.toLowerCase().includes(searchTerm) ||
                item.nama_lengkap.toLowerCase().includes(searchTerm)
            );

            const matchesStatus = (
                status === 'all' || item.status === status
            );

            return matchesSearch && matchesStatus;
        });

        // --- SORTING LOGIC ---
        filteredData.sort((a, b) => {
            let valA = a[sortColumn];
            let valB = b[sortColumn];

            if (sortColumn === 'jumlah_pinjaman') {
                valA = parseFloat(valA);
                valB = parseFloat(valB);
            } else if (sortColumn === 'tanggal_pengajuan') {
                valA = new Date(valA);
                valB = new Date(valB);
            } else {
                valA = String(valA || '').toLowerCase();
                valB = String(valB || '').toLowerCase();
            }

            if (valA < valB) return sortDirection === 'asc' ? -1 : 1;
            if (valA > valB) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        // --- PAGINATION LOGIC ---
        const totalPages = Math.ceil(filteredData.length / rowsPerPage);
        
        // Correct current page if it's out of bounds after filtering
        if (currentPage > totalPages) {
            currentPage = totalPages > 0 ? totalPages : 1;
        }

        const startIndex = (currentPage - 1) * rowsPerPage;
        const paginatedData = filteredData.slice(startIndex, startIndex + rowsPerPage);

        renderTable(paginatedData);
        renderPagination(totalPages, filteredData.length, startIndex);
        updateSortIndicators();
    }

    window.showDetail = function(id) {
        currentPinjamanId = id; // Store the current ID for printing
        fetch(`${basePath}/api/ksp/pinjaman?action=get_detail&id=${id}`)
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    const p = res.data;
                    document.getElementById('det-no').textContent = p.nomor_pinjaman;
                    document.getElementById('det-nama').textContent = p.nama_lengkap;
                    document.getElementById('det-jumlah').textContent = formatRupiah(p.jumlah_pinjaman);                    
                    const statusBadge = document.getElementById('det-status');
                    statusBadge.innerHTML = `<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(p.status)}">${p.status.toUpperCase()}</span>`;

                    // Show/Hide Pelunasan Button
                    const btnPelunasan = document.getElementById('btn-pelunasan');
                    if (p.status === 'aktif') btnPelunasan.classList.remove('hidden');
                    else btnPelunasan.classList.add('hidden');

                    renderDetailAgunan(p);
                    
                    renderActionBar(p);
                    renderSchedule(res.schedule, p.status);
                    modalDetail.classList.remove('hidden');
                }
            });
    };

    function renderDetailAgunan(p) {
        const container = document.getElementById('det-agunan');
        if (!container) return;

        // Cek apakah ada data detail agunan dari tabel baru (backend harus join tabel ksp_pinjaman_agunan)
        if (p.agunan_detail_json) {
            try {
                const detail = JSON.parse(p.agunan_detail_json);
                const agunanIcon = getAgunanIcon(p.nama_tipe_agunan);
                let html = `
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
                        ${agunanIcon}
                        <div>
                            <div class="font-semibold text-sm text-gray-800 dark:text-gray-200">${p.nama_tipe_agunan || 'Detail Agunan'}</div>
                            <dl class="mt-1 grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-gray-600 dark:text-gray-400">`;
                for (const [key, value] of Object.entries(detail)) {
                    // Format key agar lebih rapi (misal: no_bpkb -> No Bpkb)
                    const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    html += `<div class="flex flex-col"><dt class="font-medium">${label}</dt><dd>${value}</dd></div>`;
                }
                html += `</dl></div></div>`;
                container.innerHTML = html;
            } catch (e) { 
                console.error("Error rendering agunan detail:", e);
                container.textContent = p.agunan || '-'; 
            }
        } else {
            container.textContent = p.agunan || '-'; // Fallback ke kolom agunan lama
        }
    }

    function updateSortIndicators() {
        document.querySelectorAll('#pinjaman-table-header th[data-sort-by]').forEach(th => {
            const icon = th.querySelector('i');
            if (th.dataset.sortBy === sortColumn) {
                th.classList.add('text-primary', 'dark:text-primary-400');
                if (icon) {
                    icon.className = sortDirection === 'asc' ? 'bi bi-sort-up ml-1' : 'bi bi-sort-down ml-1';
                }
            } else {
                th.classList.remove('text-primary', 'dark:text-primary-400');
                if (icon) {
                    icon.className = 'bi bi-arrow-down-up text-gray-400 ml-1';
                }
            }
        });
    }

    function renderPagination(totalPages, totalItems, startIndex) {
        const paginationContainer = document.getElementById('pagination-controls');
        if (!paginationContainer) return;

        const startItem = totalItems > 0 ? startIndex + 1 : 0;
        const endItem = Math.min(startIndex + rowsPerPage, totalItems);

        let summaryHTML = `
            <div class="text-sm text-gray-700 dark:text-gray-400">
                Menampilkan <span class="font-semibold">${startItem}</span> - <span class="font-semibold">${endItem}</span> dari <span class="font-semibold">${totalItems}</span> hasil
            </div>
        `;

        if (totalPages <= 1) {
            paginationContainer.innerHTML = summaryHTML;
            return;
        }
        
        let navHTML = `<nav><ul class="inline-flex items-center -space-x-px text-sm">`;

        // Previous Button
        navHTML += `
            <li>
                <button onclick="changePage(${currentPage - 1})" class="px-3 py-2 ml-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}" ${currentPage === 1 ? 'disabled' : ''}>
                    Sebelumnya
                </button>
            </li>
        `;

        // Page Numbers with Ellipsis
        const pageRange = 1;
        let pages = [];
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - pageRange && i <= currentPage + pageRange)) {
                pages.push(i);
            }
        }

        let lastPage = 0;
        for (const page of pages) {
            if (lastPage && page - lastPage > 1) {
                navHTML += `<li><span class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400">...</span></li>`;
            }
            const isActive = page === currentPage;
            navHTML += `
                <li>
                    <button onclick="changePage(${page})" class="px-3 py-2 leading-tight ${isActive ? 'text-primary-600 bg-primary-50 border-primary-300 dark:bg-gray-700 dark:text-white z-10' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400'}">
                        ${page}
                    </button>
                </li>
            `;
            lastPage = page;
        }

        // Next Button
        navHTML += `
            <li>
                <button onclick="changePage(${currentPage + 1})" class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white ${currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''}" ${currentPage === totalPages ? 'disabled' : ''}>
                    Berikutnya
                </button>
            </li>`;

        navHTML += `</ul></nav>`;
        paginationContainer.innerHTML = summaryHTML + navHTML;
    }

    function renderActionBar(pinjaman) {
        const actionBar = document.getElementById('action-bar');
        actionBar.innerHTML = '';
        actionBar.classList.add('hidden');

        if (pinjaman.status === 'pending') {
            actionBar.classList.remove('hidden');
            const kasOptions = kasAccounts.map(k => `<option value="${k.id}">${k.nama_akun}</option>`).join('');
            actionBar.innerHTML = `
                <div class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
                    <div class="flex-grow">
                        <label class="block text-xs font-medium text-yellow-800 dark:text-yellow-200">Akun Kas Pencairan</label>
                        <select id="approve-kas-id" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-600 sm:text-sm">${kasOptions}</select>
                    </div>
                    <div class="flex-grow">
                        <label class="block text-xs font-medium text-yellow-800 dark:text-yellow-200">Tanggal Cair</label>
                        <input type="date" id="approve-date" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-600 sm:text-sm" value="${new Date().toISOString().split('T')[0]}">
                    </div>
                    <button onclick="approvePinjaman(${pinjaman.id})" class="w-full sm:w-auto inline-flex items-center gap-2 justify-center px-4 py-2 bg-green-600 text-white rounded-md text-sm font-semibold hover:bg-green-700">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Cairkan Pinjaman</span>
                    </button>
                </div>`;
        }
    }

    window.approvePinjaman = function(id) {
        const kasId = document.getElementById('approve-kas-id').value;
        const date = document.getElementById('approve-date').value;
        if(!confirm('Yakin ingin mencairkan pinjaman ini?')) return;

        fetch(`${basePath}/api/ksp/pinjaman?action=approve`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, akun_kas_id: kasId, tanggal_pencairan: date })
        }).then(res => res.json()).then(res => {
            if(res.success) { showNotification(res.message, 'success'); modalDetail.classList.add('hidden'); loadData(); }
            else { showNotification(res.message, 'error'); }
        });
    };

    function renderSchedule(schedule, statusPinjaman) {
        const tbody = document.getElementById('schedule-table-body');
        tbody.innerHTML = schedule.map(s => {
            const sisaPokok = parseFloat(s.pokok) - parseFloat(s.pokok_terbayar);
            const sisaBunga = parseFloat(s.bunga) - parseFloat(s.bunga_terbayar);
            const sisaTotal = sisaPokok + sisaBunga;

            let actionBtn = '';
            if (statusPinjaman === 'aktif' && s.status !== 'lunas') {
                actionBtn = `<button onclick="openPaymentModal(${s.id}, ${s.angsuran_ke}, ${sisaPokok}, ${sisaBunga})" class="inline-flex items-center gap-1 px-2 py-1 bg-blue-500 text-white rounded text-xs font-semibold hover:bg-blue-600"><i class="bi bi-credit-card-fill"></i> Bayar</button>`;
            }
            return `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3 text-sm text-center">${s.angsuran_ke}</td>
                    <td class="px-4 py-3 text-sm">${formatDate(s.tanggal_jatuh_tempo)}</td>
                    <td class="px-4 py-3 text-sm text-right">${formatRupiah(s.pokok)}</td>
                    <td class="px-4 py-3 text-sm text-right">${formatRupiah(s.bunga)}</td>
                    <td class="px-4 py-3 text-sm text-right font-bold text-gray-800 dark:text-white">${formatRupiah(s.total_angsuran)}</td>
                    <td class="px-4 py-3 text-center text-sm">
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${s.status === 'lunas' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200'}">
                            ${s.status === 'lunas' ? 'LUNAS' : (sisaTotal < s.total_angsuran ? 'BAYAR SEBAGIAN' : 'BELUM BAYAR')}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">${actionBtn}</td>
                </tr>
            `;
        }).join('');
    }

    window.openPaymentModal = function(angsuranId, angsuranKe, sisaPokok, sisaBunga) {
        formPembayaran.reset();
        const totalTagihan = sisaPokok + sisaBunga;

        document.getElementById('payment_angsuran_id').value = angsuranId;
        document.getElementById('payment-modal-title').textContent = `Pembayaran Angsuran Ke-${angsuranKe}`;
        document.getElementById('payment-sisa-pokok').textContent = formatRupiah(sisaPokok);
        document.getElementById('payment-sisa-bunga').textContent = formatRupiah(sisaBunga);
        document.getElementById('payment-total-tagihan').textContent = formatRupiah(totalTagihan);
        document.getElementById('payment_jumlah_dibayar').value = totalTagihan;
        document.getElementById('payment_tanggal_bayar').value = new Date().toISOString().split('T')[0];

        const btnLunasi = document.getElementById('btn-lunasi');
        btnLunasi.onclick = () => {
            document.getElementById('payment_jumlah_dibayar').value = totalTagihan;
        };

        // Populate kas accounts
        const kasSelect = document.getElementById('payment_akun_kas_id');
        if (kasSelect.options.length <= 1) { // Populate only if not already populated
            kasSelect.innerHTML = kasAccounts.map(k => `<option value="${k.id}">${k.nama_akun}</option>`).join('');
        }

        modalPembayaran.classList.remove('hidden');
    }

    function loadDropdowns() {
        fetch(`${basePath}/api/ksp/simpanan?action=get_anggota_list`).then(r=>r.json()).then(res => {
            document.getElementById('anggota_id').innerHTML = '<option value="">Pilih...</option>' + res.data.map(a => `<option value="${a.id}">${a.nama_lengkap}</option>`).join('');
        });
        fetch(`${basePath}/api/ksp/pinjaman?action=get_jenis_pinjaman`).then(r=>r.json()).then(res => {
            document.getElementById('jenis_pinjaman_id').innerHTML = res.data.map(j => `<option value="${j.id}">${j.nama} (${j.bunga_per_tahun}%)</option>`).join('');
        });
        fetch(`${basePath}/api/ksp/simpanan?action=get_kas_accounts`).then(r=>r.json()).then(res => {
            kasAccounts = res.data;
        });
        
        // Fetch Tipe Agunan dan Setup Form Dinamis
        fetch(`${basePath}/api/ksp/pinjaman?action=get_tipe_agunan`).then(r=>r.json()).then(res => {
            if (!res.success) return;
            agunanTypes = res.data;
            const select = document.getElementById('tipe_agunan_id');
            if(select) {
                select.innerHTML = '<option value="">-- Tidak ada agunan --</option>' + 
                    res.data.map(t => `<option value="${t.id}">${t.nama}</option>`).join('');
                
                select.addEventListener('change', function() {
                    renderAgunanFields(this.value);
                });
            }
        });
    }

    function renderAgunanFields(typeId) {
        const container = document.getElementById('container-detail-agunan');
        if(!container) return;
        
        const type = agunanTypes.find(t => t.id == typeId);

        if(!type || !type.config || !typeId) {
            container.innerHTML = '';
            container.classList.add('hidden');
            return;
        }

        container.classList.remove('hidden');
        try {
            const config = JSON.parse(type.config);
            container.innerHTML = config.map(field => `
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">${field.label}</label>
                    <input type="${field.type}" name="agunan_detail[${field.name}]" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600" placeholder="${field.label}...">
                </div>
            `).join('');
        } catch (e) {
            console.error("Error parsing agunan config:", e);
            container.innerHTML = '<p class="text-red-500 text-xs">Error: Konfigurasi agunan tidak valid.</p>';
        }
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

    function getAgunanIcon(tipeAgunan) {
        let iconClass = 'bi-shield-check'; // default
        if (tipeAgunan) {
            if (tipeAgunan.toLowerCase().includes('kendaraan')) iconClass = 'bi-car-front-fill';
            else if (tipeAgunan.toLowerCase().includes('sertifikat')) iconClass = 'bi-file-earmark-text-fill';
            else if (tipeAgunan.toLowerCase().includes('elektronik')) iconClass = 'bi-pc-display';
        }
        return `<div class="flex-shrink-0"><i class="bi ${iconClass} text-2xl text-gray-400 dark:text-gray-500"></i></div>`;
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

    function getStatusColor(status) {
        if (status === 'aktif') return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        if (status === 'lunas') return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        if (status === 'ditolak') return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    }
}