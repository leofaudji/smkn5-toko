function initUsersPage() {
    const tableBody = document.getElementById('users-table-body');
    const modalEl = document.getElementById('userModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    const form = document.getElementById('user-form');
    const saveBtn = document.getElementById('save-user-btn');

    if (!tableBody) return;

    async function loadUsers() {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
        try {
            const response = await fetch(`${basePath}/api/users`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            tableBody.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach((user, index) => {
                    const row = `
                        <tr>
                            <td>${user.username}</td>
                            <td>${user.nama_lengkap || '-'}</td>
                            <td><span class="badge bg-primary">${user.role}</span></td>
                            <td>${new Date(user.created_at).toLocaleDateString('id-ID', { dateStyle: 'long' })}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-info edit-btn" data-id="${user.id}" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="${user.id}" data-username="${user.username}" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                            </td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada pengguna ditemukan.</td></tr>';
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;

        try {
            const response = await fetch(`${basePath}/api/users`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                modal.hide();
                loadUsers();
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
        }
    });

    tableBody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const id = editBtn.dataset.id;
            const formData = new FormData();
            formData.append('action', 'get_single');
            formData.append('id', id);
            const response = await fetch(`${basePath}/api/users`, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                document.getElementById('userModalLabel').textContent = 'Edit Pengguna';
                form.reset();
                const user = result.data;
                document.getElementById('user-id').value = user.id;
                document.getElementById('user-action').value = 'update';
                document.getElementById('username').value = user.username;
                document.getElementById('nama_lengkap').value = user.nama_lengkap;
                document.getElementById('role').value = user.role;
                document.getElementById('password').setAttribute('placeholder', 'Kosongkan jika tidak diubah');
                modal.show();
            }
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const { id, username } = deleteBtn.dataset;
            if (confirm(`Yakin ingin menghapus pengguna "${username}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                const response = await fetch(`${basePath}/api/users`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') loadUsers();
            }
        }
    });

    modalEl.addEventListener('show.bs.modal', (e) => {
        const button = e.relatedTarget;
        if (button && button.dataset.action === 'add') {
            document.getElementById('userModalLabel').textContent = 'Tambah Pengguna Baru';
            form.reset();
            document.getElementById('user-id').value = '';
            document.getElementById('user-action').value = 'add';
            document.getElementById('password').setAttribute('placeholder', '');
        }
    });

    loadUsers();
}
