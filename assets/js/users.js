/**
 * Inisialisasi halaman Manajemen Pengguna
 */
function initUsersPage() {
    const usersTableBody = document.getElementById('users-table-body');
    const userModal = document.getElementById('userModal');
    const userForm = document.getElementById('user-form');
    const modalLabel = document.getElementById('userModalLabel');
    const userIdInput = document.getElementById('user-id');
    const passwordHelp = document.getElementById('password-help');
    const addUserBtn = document.getElementById('add-user-btn');
    const saveUserBtn = document.getElementById('save-user-btn');

    // Pastikan elemen ada sebelum melanjutkan (penting untuk SPA)
    if (!usersTableBody || !userForm) return;

    function loadUsers() {
        const urlParams = new URLSearchParams(window.location.search);
        const roleIdFilter = urlParams.get('role_id');
        let apiUrl = `${basePath}/api/users`;
        if (roleIdFilter) {
            apiUrl += `?role_id=${roleIdFilter}`;
        }

        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    usersTableBody.innerHTML = '';
                    if (data.success && data.data.length > 0) {
                        data.data.forEach(user => {
                            const roleHtml = user.role_id
                                ? `<a href="${basePath}/users?role_id=${user.role_id}" class="text-primary hover:underline" title="Filter berdasarkan role ini">${user.role_name || 'N/A'}</a>`
                                : '<span class="text-gray-400">N/A</span>';

                            // Menggunakan class 'edit-user-btn' dan 'delete-user-btn' untuk event delegation
                            const row = `
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${user.username}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${user.nama_lengkap || ''}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">${roleHtml}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${new Date(user.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3 edit-user-btn" data-id="${user.id}">Edit</button>
                                        ${user.id != 1 ? `<button class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 delete-user-btn" data-id="${user.id}">Hapus</button>` : ''}
                                    </td>
                                </tr>
                            `;
                            usersTableBody.insertAdjacentHTML('beforeend', row);
                        });
                    } else {
                        usersTableBody.innerHTML = `<tr><td colspan="5" class="text-center p-5 text-gray-500">${data.message || 'Tidak ada data pengguna.'}</td></tr>`;
                    }
                } catch (e) {
                    console.error("Gagal mem-parsing JSON:", e);
                    console.error("Respons mentah dari server:", text);
                    usersTableBody.innerHTML = `<tr><td colspan="5" class="text-center p-5 text-red-500">Error parsing data.</td></tr>`;
                }
            })
            .catch(error => {
                console.error('Error fetching users:', error);
                usersTableBody.innerHTML = '<tr><td colspan="5" class="text-center p-5 text-red-500">Gagal memuat data. Periksa konsol browser (F12) untuk detail.</td></tr>';
            });
    }

    // Event Delegation untuk tombol Edit dan Hapus di dalam tabel
    usersTableBody.addEventListener('click', (e) => {
        if (e.target.classList.contains('edit-user-btn')) {
            const id = e.target.dataset.id;
            editUser(id);
        } else if (e.target.classList.contains('delete-user-btn')) {
            const id = e.target.dataset.id;
            deleteUser(id);
        }
    });

    function openAddUserModal() {
        userForm.reset();
        userIdInput.value = '';
        document.getElementById('user-action').value = 'add';
        modalLabel.textContent = 'Tambah Pengguna Baru';
        document.getElementById('password').setAttribute('required', 'required');
        passwordHelp.classList.add('hidden');
        openModal('userModal');
    }

    function editUser(id) {
        fetch(`${basePath}/api/users?id=${id}`)
            .then(r => r.json()).then(res => {
                if (res.success) {
                    const user = res.data;
                    userForm.reset();
                    userIdInput.value = user.id;
                    document.getElementById('user-action').value = 'edit';
                    document.getElementById('username').value = user.username;
                    document.getElementById('nama_lengkap').value = user.nama_lengkap || '';
                    document.getElementById('role_id').value = user.role_id || ''; 
                    document.getElementById('password').removeAttribute('required');
                    passwordHelp.classList.remove('hidden');
                    modalLabel.textContent = 'Edit Pengguna';
                    openModal('userModal');
                }
            })
            .catch(error => {
                console.error('Error fetching single user:', error);
                showToast('Gagal mengambil data pengguna untuk diedit.', 'error');
            });
    }

    function saveUser() {
        const formData = new FormData(userForm);
        
        // Disable button loading state
        const originalBtnHtml = saveUserBtn.innerHTML;
        saveUserBtn.disabled = true;
        saveUserBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;

        fetch(`${basePath}/api/users`, { method: 'POST', body: formData })
            .then(r => r.json()).then(res => {
                if (res.success) {
                    closeModal('userModal');
                    loadUsers();
                    showToast('Pengguna berhasil disimpan.');
                } else {
                    showToast(res.message || 'Gagal menyimpan pengguna.', 'error');
                }
            })
            .catch(error => {
                console.error('Error saving user:', error);
                showToast('Terjadi kesalahan saat menyimpan. Periksa konsol.', 'error');
            })
            .finally(() => {
                saveUserBtn.disabled = false;
                saveUserBtn.innerHTML = originalBtnHtml;
            });
    }

    function deleteUser(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Aksi ini tidak dapat dibatalkan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                fetch(`${basePath}/api/users`, { method: 'POST', body: formData })
                    .then(r => r.json()).then(res => {
                        if (res.success) {
                            loadUsers();
                            showToast('Pengguna berhasil dihapus.');
                        } else {
                            showToast(res.message || 'Gagal menghapus pengguna.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting user:', error);
                        showToast('Terjadi kesalahan saat menghapus. Periksa konsol.', 'error');
                    });
            }
        });
    }

    // Attach Event Listeners
    if (addUserBtn) addUserBtn.addEventListener('click', openAddUserModal);
    if (saveUserBtn) saveUserBtn.addEventListener('click', saveUser);

    // Load data awal
    loadUsers();
}
