function initTransaksiBerulangPage() {
    const tableBody = document.getElementById('recurring-table-body');
    if (!tableBody) return;

    async function loadTemplates() {
        tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>`;
        try {
            const response = await fetch(`${basePath}/api/recurring?action=list_templates`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            tableBody.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach(t => {
                    const statusBadge = t.is_active == 1 
                        ? `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800/20 dark:text-green-200">Aktif</span>` 
                        : `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">Non-Aktif</span>`;
                    
                    const toggleText = t.is_active == 1 ? 'Non-aktifkan' : 'Aktifkan';
                    const toggleIconColor = t.is_active == 1 ? 'text-green-500' : 'text-gray-500';

                    const row = `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${t.name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">Setiap ${t.frequency_interval} ${t.frequency_unit}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${new Date(t.next_run_date).toLocaleDateString('id-ID', {dateStyle: 'long'})}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${statusBadge}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-4">
                                    <button class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 edit-recurring-btn" data-id="${t.id}" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                                    <button class="toggle-status-btn ${toggleIconColor} hover:text-gray-900 dark:hover:text-gray-300" data-id="${t.id}" data-active="${t.is_active}" title="${toggleText}"><i class="bi bi-power"></i></button>
                                    <button class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 delete-recurring-btn" data-id="${t.id}" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                                </div>
                            </td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-gray-500">Belum ada template yang dibuat.</td></tr>`;
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-red-500">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    tableBody.addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.delete-recurring-btn');
        if (deleteBtn) {
            if (confirm('Yakin ingin menghapus template ini?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', deleteBtn.dataset.id);
                const response = await fetch(`${basePath}/api/recurring`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status);
                if (result.status === 'success') loadTemplates();
            }
        }

        const toggleBtn = e.target.closest('.toggle-status-btn');
        if (toggleBtn) {
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('id', toggleBtn.dataset.id);
            formData.append('is_active', toggleBtn.dataset.active == 1 ? 0 : 1);
            const response = await fetch(`${basePath}/api/recurring`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status);
            if (result.status === 'success') loadTemplates();
        }

        const editBtn = e.target.closest('.edit-recurring-btn');
        if (editBtn) {
            const response = await fetch(`${basePath}/api/recurring?action=get_single&id=${editBtn.dataset.id}`);
            const result = await response.json();
            if (result.status === 'success') {
                openRecurringModal(result.data.template_type, JSON.parse(result.data.template_data), result.data);
            } else {
                showToast(result.message, 'error');
            }
        }
    });

    document.getElementById('add-recurring-btn').addEventListener('click', (e) => {
        e.preventDefault();
        // Arahkan pengguna untuk membuat jurnal dulu
        showToast('Silakan buat draf jurnal di halaman "Entri Jurnal", lalu klik "Jadikan Berulang".', 'info');
        navigate(`${basePath}/entri-jurnal`);
    });

    loadTemplates();
}
