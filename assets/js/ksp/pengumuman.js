function initPengumumanPage() {
    const tableBody = document.getElementById('pengumuman-table-body');
    const modal = document.getElementById('modal-pengumuman');
    const form = document.getElementById('form-pengumuman');
    const btnAdd = document.getElementById('btn-add-pengumuman');
    const btnCancel = document.getElementById('btn-cancel-modal');
    const modalTitle = document.getElementById('modal-title');

    if (!tableBody) return;

    loadData();

    btnAdd.addEventListener('click', () => {
        form.reset();
        document.getElementById('pengumuman-id').value = '';
        document.getElementById('tanggal_posting').value = new Date().toISOString().split('T')[0];
        document.getElementById('is_active').checked = true;
        modalTitle.textContent = 'Tambah Pengumuman';
        modal.classList.remove('hidden');
    });

    btnCancel.addEventListener('click', () => {
        modal.classList.add('hidden');
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const id = formData.get('id');
        const action = id ? 'update' : 'store';
        
        // Checkbox handling for FormData
        if (!document.getElementById('is_active').checked) {
            formData.set('is_active', '0');
        } else {
            formData.set('is_active', '1');
        }

        try {
            const response = await fetch(`${basePath}/api/ksp/pengumuman?action=${action}`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, 'success');
                modal.classList.add('hidden');
                loadData();
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan', 'error');
        }
    });

    async function loadData() {
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4">Memuat data...</td></tr>';
        try {
            const response = await fetch(`${basePath}/api/ksp/pengumuman?action=list`);
            const result = await response.json();
            
            if (result.success) {
                renderTable(result.data);
            } else {
                tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-red-500">Gagal memuat data</td></tr>';
            }
        } catch (error) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-red-500">Terjadi kesalahan</td></tr>';
        }
    }

    function renderTable(data) {
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-gray-500">Belum ada pengumuman.</td></tr>';
            return;
        }

        tableBody.innerHTML = data.map(item => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${formatDate(item.tanggal_posting)}</td>
                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                    <div class="font-bold">${item.judul}</div>
                    <div class="text-xs text-gray-500 truncate max-w-xs">${item.isi}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${item.is_active == 1 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                        ${item.is_active == 1 ? 'Aktif' : 'Non-Aktif'}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="editPengumuman(${item.id})" class="text-blue-600 hover:text-blue-900 mr-3"><i class="bi bi-pencil-square"></i></button>
                    <button onclick="deletePengumuman(${item.id})" class="text-red-600 hover:text-red-900"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        `).join('');
    }

    window.editPengumuman = async function(id) {
        try {
            const response = await fetch(`${basePath}/api/ksp/pengumuman?action=get_single&id=${id}`);
            const result = await response.json();
            
            if (result.success) {
                const data = result.data;
                document.getElementById('pengumuman-id').value = data.id;
                document.getElementById('judul').value = data.judul;
                document.getElementById('isi').value = data.isi;
                document.getElementById('tanggal_posting').value = data.tanggal_posting;
                document.getElementById('is_active').checked = data.is_active == 1;
                
                modalTitle.textContent = 'Edit Pengumuman';
                modal.classList.remove('hidden');
            }
        } catch (error) {
            showToast('Gagal memuat data', 'error');
        }
    };

    window.deletePengumuman = async function(id) {
        if (!confirm('Yakin ingin menghapus pengumuman ini?')) return;
        
        try {
            const formData = new FormData();
            formData.append('id', id);
            const response = await fetch(`${basePath}/api/ksp/pengumuman?action=delete`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, 'success');
                loadData();
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan', 'error');
        }
    };
}