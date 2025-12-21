function initCoaPage() {
    const treeContainer = document.getElementById('coa-tree-container');
    const modalEl = document.getElementById('coaModal');
    const form = document.getElementById('coa-form');
    const saveBtn = document.getElementById('save-coa-btn');
    const addBtn = document.getElementById('add-coa-btn');

    if (!treeContainer || !modalEl || !form || !saveBtn || !addBtn) return;

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
        ul.className = `list-none ${level > 0 ? 'ml-6 mt-2 border-l border-gray-200 dark:border-gray-700 pl-4' : 'space-y-2'}`;

        nodes.forEach(node => {
            const li = document.createElement('li');
            li.className = 'mb-1'; 
            li.innerHTML = `
                <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    <div>
                        <span class="font-semibold text-gray-900 dark:text-white">${node.kode_akun}</span> - <span class="text-gray-800 dark:text-gray-200">${node.nama_akun}</span>
                        <small class="text-gray-500 dark:text-gray-400 ml-1">(${node.tipe_akun})</small>
                        ${node.is_kas == 1 ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 ml-2">Akun Kas</span>' : ''}
                    </div>
                    <div class="flex gap-2">
                        <button class="text-blue-600 hover:text-blue-900 edit-btn" data-id="${node.id}" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                        <button class="text-red-600 hover:text-red-900 delete-btn" data-id="${node.id}" data-nama="${node.nama_akun}" title="Hapus"><i class="bi bi-trash-fill"></i></button>
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
        treeContainer.innerHTML = '<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>';
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
                treeContainer.innerHTML = '<div class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-200 p-4 rounded-md text-center">Bagan Akun masih kosong.</div>';
            }
            populateParentDropdown();
        } catch (error) {
            treeContainer.innerHTML = `<div class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-200 p-4 rounded-md text-center">Gagal memuat data: ${error.message}</div>`;
        }
    }

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;

        try {
            const response = await fetch(`${basePath}/api/coa`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                closeModal('coaModal');
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
                openModal('coaModal');
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

    addBtn.addEventListener('click', () => {
        document.getElementById('coaModalLabel').textContent = 'Tambah Akun Baru';
        form.reset();
        document.getElementById('coa-id').value = '';
        document.getElementById('coa-action').value = 'add';
        populateParentDropdown();
        openModal('coaModal');
    });

    loadCoaData();
}