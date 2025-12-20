function initCoaPage() {
    const treeContainer = document.getElementById('coa-tree-container');
    const modalEl = document.getElementById('coaModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    const form = document.getElementById('coa-form');
    const saveBtn = document.getElementById('save-coa-btn');

    if (!treeContainer || !modalEl || !form || !saveBtn) return;

    let flatAccounts = []; // Store flat list for populating dropdown

    function buildTree(list, parentId = null) {
        const children = list.filter(item => item.parent_id == parentId);
        if (children.length === 0) return null;

        return children.map(child => ({
            ...child,
            children: buildTree(list, child.id)
        }));
    }

    function renderTree(nodes, container, level = 0) {
        const ul = document.createElement('ul');
        ul.className = `list-group ${level > 0 ? 'ms-4 mt-2' : 'list-group-flush'}`;

        nodes.forEach(node => {
            const li = document.createElement('li');
            // Gunakan 'list-group-item' untuk semua, karena Bootstrap 5 menangani border dengan baik.
            li.className = 'list-group-item'; 
            li.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-bold">${node.kode_akun}</span> - ${node.nama_akun}
                        <small class="text-muted">(${node.tipe_akun})</small>
                        ${node.is_kas == 1 ? '<span class="badge bg-success ms-2">Akun Kas</span>' : ''}
                    </div>
                    <div>
                        <button class="btn btn-sm btn-info edit-btn" data-id="${node.id}" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${node.id}" data-nama="${node.nama_akun}" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                    </div>
                </div>
            `;
            ul.appendChild(li);

            if (node.children) {
                // Render sub-akun di dalam <li> induk, bukan di dalam div baru.
                renderTree(node.children, li, level + 1);
            }
        });
        container.appendChild(ul);
    }

    function populateParentDropdown(selectedId = null) {
        const parentSelect = document.getElementById('parent_id');
        parentSelect.innerHTML = '<option value="">-- Akun Induk (Root) --</option>';
        flatAccounts.forEach(acc => {
            const option = new Option(`${acc.kode_akun} - ${acc.nama_akun}`, acc.id);
            if (acc.id == selectedId) option.selected = true;
            parentSelect.add(option);
        });
    }

    async function loadCoaData() {
        treeContainer.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';
        try {
            const response = await fetch(`${basePath}/api/coa`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            flatAccounts = result.data;
            const tree = buildTree(flatAccounts);
            treeContainer.innerHTML = '';
            if (tree) {
                renderTree(tree, treeContainer);
            } else {
                treeContainer.innerHTML = '<div class="alert alert-info">Bagan Akun masih kosong.</div>';
            }
            populateParentDropdown();
        } catch (error) {
            treeContainer.innerHTML = `<div class="alert alert-danger">Gagal memuat data: ${error.message}</div>`;
        }
    }

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;

        try {
            const response = await fetch(`${basePath}/api/coa`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                modal.hide();
                loadCoaData();
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
        }
    });

    treeContainer.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const id = editBtn.dataset.id;
            const formData = new FormData();
            formData.append('action', 'get_single');
            formData.append('id', id);
            const response = await fetch(`${basePath}/api/coa`, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                document.getElementById('coaModalLabel').textContent = 'Edit Akun';
                form.reset();
                const acc = result.data;
                document.getElementById('coa-id').value = acc.id;
                document.getElementById('coa-action').value = 'update';
                populateParentDropdown(acc.parent_id);
                document.getElementById('kode_akun').value = acc.kode_akun;
                document.getElementById('nama_akun').value = acc.nama_akun;
                document.getElementById('tipe_akun').value = acc.tipe_akun;
                document.getElementById('is_kas').checked = (acc.is_kas == 1);
                modal.show();
            }
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const { id, nama } = deleteBtn.dataset;
            if (confirm(`Yakin ingin menghapus akun "${nama}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                const response = await fetch(`${basePath}/api/coa`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') loadCoaData();
            }
        }
    });

    modalEl.addEventListener('show.bs.modal', (e) => {
        const button = e.relatedTarget;
        if (button && button.dataset.action === 'add') {
            document.getElementById('coaModalLabel').textContent = 'Tambah Akun Baru';
            form.reset();
            document.getElementById('coa-id').value = '';
            document.getElementById('coa-action').value = 'add';
            populateParentDropdown();
        }
    });

    loadCoaData();
}