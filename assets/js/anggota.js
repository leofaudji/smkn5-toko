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

    let currentPage = 1;
    let limit = 10;
    let searchTimeout;

    // Load data awal
    loadData();

    // Event Listeners
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            loadData();
        }, 500);
    });

    btnAdd.addEventListener('click', function() {
        openModal();
    });

    btnCancel.addEventListener('click', function() {
        closeModal();
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        saveData();
    });

    // Functions
    function loadData() {
        const search = searchInput.value;
        const url = `${basePath}/api/anggota_handler.php?action=get_all&page=${currentPage}&limit=${limit}&search=${encodeURIComponent(search)}`;

        tableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Memuat data...</td></tr>';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderTable(data.data);
                    renderPagination(data.total, data.page, data.limit);
                } else {
                    alert('Gagal memuat data: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                tableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Terjadi kesalahan saat memuat data.</td></tr>';
            });
    }

    function renderTable(data) {
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Tidak ada data anggota.</td></tr>';
            return;
        }

        tableBody.innerHTML = data.map(item => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.nomor_anggota}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${item.nama_lengkap}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${item.no_telepon || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${formatDate(item.tanggal_daftar)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${item.status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        ${item.status.charAt(0).toUpperCase() + item.status.slice(1)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="editAnggota(${item.id})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3"><i class="bi bi-pencil-square"></i> Edit</button>
                    <button onclick="deleteAnggota(${item.id})" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"><i class="bi bi-trash"></i> Hapus</button>
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

    window.changePage = function(page) {
        currentPage = page;
        loadData();
    };

    function openModal(id = null) {
        form.reset();
        document.getElementById('anggota-id').value = '';
        
        if (id) {
            modalTitle.textContent = 'Edit Anggota';
            fetch(`${basePath}/api/anggota_handler.php?action=get_detail&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const item = data.data;
                        document.getElementById('anggota-id').value = item.id;
                        document.getElementById('nama_lengkap').value = item.nama_lengkap;
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

        fetch(`${basePath}/api/anggota_handler.php?action=${action}`, {
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

    window.editAnggota = function(id) {
        openModal(id);
    };

    window.deleteAnggota = function(id) {
        if (confirm('Apakah Anda yakin ingin menghapus anggota ini?')) {
            fetch(`${basePath}/api/anggota_handler.php?action=delete`, {
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