function initUsersPage() {
    const tableBody = document.getElementById('users-table-body');
    const form = document.getElementById('user-form');
    const saveBtn = document.getElementById('save-user-btn');
    const addUserBtn = document.getElementById('add-user-btn');

    if (!tableBody) return;

    async function loadUsers() {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-10"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>';
        try {
            const response = await fetch(`${basePath}/api/users`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            tableBody.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach(user => {
                    const roleBadge = user.role === 'admin' 
                        ? `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-800/20 dark:text-red-200">Admin</span>`
                        : `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800/20 dark:text-blue-200">User</span>`;

                    const row = `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${user.username}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${user.nama_lengkap || '-'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">${roleBadge}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${new Date(user.created_at).toLocaleDateString('id-ID', { dateStyle: 'long' })}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-4">
                                    <button class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 edit-btn" data-id="${user.id}" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                                    <button class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 delete-btn" data-id="${user.id}" data-username="${user.username}" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                                </div>
                            </td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-gray-500">Tidak ada pengguna ditemukan.</td></tr>';
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-red-500">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;

        try {
            const response = await fetch(`${basePath}/api/users`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                closeModal('userModal');
                loadUsers();
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
        }
    });

    addUserBtn.addEventListener('click', () => {
        document.getElementById('userModalLabel').textContent = 'Tambah Pengguna Baru';
        form.reset();
        document.getElementById('user-id').value = '';
        document.getElementById('user-action').value = 'add';
        document.getElementById('password').setAttribute('placeholder', 'Wajib diisi untuk pengguna baru');
        document.getElementById('password-help').textContent = '';
        openModal('userModal');
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
                document.getElementById('password-help').textContent = 'Kosongkan jika tidak ingin mengubah password.';
                openModal('userModal');
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

    loadUsers();
}
