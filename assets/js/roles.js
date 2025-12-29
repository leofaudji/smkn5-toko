/**
 * Script untuk halaman Manajemen Role & Permission
 */
function initRolesPage() {
    const rolesPageContainer = document.getElementById('roles-page-container');
    const roleForm = document.getElementById('role-form');
    const selectAllMenus = document.getElementById('select-all-menus');

    // --- Helper Functions ---

    function openAddRoleModal() {
        if (roleForm) {
            roleForm.reset();
            document.getElementById('role-id').value = '';
            // Uncheck all
            roleForm.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = false);
            roleForm.querySelectorAll('input[name="menus[]"]').forEach(cb => cb.checked = false);
            if (selectAllMenus) selectAllMenus.checked = false;
        }
        openModal('roleModal');
    }

    function editRole(id) {
        const formData = new FormData();
        formData.append('action', 'get_role');
        formData.append('id', id);
        
        fetch(window.location.href, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                const d = res.data;
                document.getElementById('role-id').value = d.id;
                document.getElementById('role-name').value = d.name;
                document.getElementById('role-desc').value = d.description;
                
                const perms = d.permissions || [];
                document.querySelectorAll('input[name="permissions[]"]').forEach(cb => {
                    cb.checked = perms.includes(parseInt(cb.value));
                });

                const menus = d.menus || [];
                document.querySelectorAll('input[name="menus[]"]').forEach(cb => {
                    cb.checked = menus.includes(cb.value);
                });
                
                // Check select all box if all menus are checked
                const allCheckboxes = document.querySelectorAll('input[name="menus[]"]');
                const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
                if (selectAllMenus) selectAllMenus.checked = allChecked;

                openModal('roleModal');
            } else {
                showToast('Gagal mengambil data: ' + res.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Terjadi kesalahan koneksi.', 'error');
        });
    }

    function saveRole(e) {
        e.preventDefault();
        const formData = new FormData(roleForm);
        const saveBtn = document.getElementById('save-role-btn');
        const originalBtnHtml = saveBtn.innerHTML;

        saveBtn.disabled = true;
        saveBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;
        
        fetch(window.location.href, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                closeModal('roleModal');
                showToast('Role berhasil disimpan.', 'success');
                // Beri jeda agar toast terlihat sebelum reload
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast('Gagal menyimpan: ' + res.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Terjadi kesalahan koneksi.', 'error');
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
        });
    }

    function deleteRole(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Role ini akan dihapus secara permanen. Aksi ini tidak dapat dibatalkan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6e7881',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete_role');
                formData.append('id', id);
                
                fetch(window.location.href, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if(res.success) {
                        showToast('Role berhasil dihapus.', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showToast('Gagal menghapus: ' + res.message, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Terjadi kesalahan koneksi.', 'error');
                });
            }
        });
    }

    // --- Event Listeners ---

    // Form Submit
    if (roleForm) {
        roleForm.addEventListener('submit', saveRole);

        // Checkbox Delegation (Select All, Parent, Child)
        roleForm.addEventListener('change', function(e) {
            // Select All Menus
            if (e.target.id === 'select-all-menus') {
                const isChecked = e.target.checked;
                roleForm.querySelectorAll('input[name="menus[]"]').forEach(cb => cb.checked = isChecked);
            }

            // Parent Checkbox
            if (e.target.classList.contains('menu-parent-checkbox')) {
                const key = e.target.getAttribute('data-key');
                const children = roleForm.querySelectorAll(`.menu-child-checkbox[data-parent="${key}"]`);
                children.forEach(child => child.checked = e.target.checked);
            }
            
            // Child Checkbox
            if (e.target.classList.contains('menu-child-checkbox')) {
                const parentKey = e.target.getAttribute('data-parent');
                const parentCb = roleForm.querySelector(`.menu-parent-checkbox[data-key="${parentKey}"]`);
                if (e.target.checked && parentCb) {
                    parentCb.checked = true;
                }
            }
        });
    }

    // Action Buttons Delegation
    if (rolesPageContainer) {
        rolesPageContainer.addEventListener('click', function(e) {
            const target = e.target.closest('button');
            if (!target) return;

            if (target.id === 'add-role-btn') {
                openAddRoleModal();
            }

            if (target.dataset.action === 'edit-role') {
                const roleId = target.dataset.id;
                editRole(roleId);
            }

            if (target.dataset.action === 'delete-role') {
                const roleId = target.dataset.id;
                deleteRole(roleId);
            }
        });
    }
}
