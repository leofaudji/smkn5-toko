function initTransaksiBerulangPage() {
    const tableBody = document.getElementById('recurring-table-body');
    if (!tableBody) return;

    async function loadTemplates() {
        tableBody.innerHTML = `<tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>`;
        try {
            const response = await fetch(`${basePath}/api/recurring?action=list_templates`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            tableBody.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach(t => {
                    const statusBadge = t.is_active == 1 ? `<span class="badge bg-success">Aktif</span>` : `<span class="badge bg-secondary">Non-Aktif</span>`;
                    const toggleText = t.is_active == 1 ? 'Non-aktifkan' : 'Aktifkan';
                    const row = `
                        <tr>
                            <td>${t.name}</td>
                            <td>Setiap ${t.frequency_interval} ${t.frequency_unit}</td>
                            <td>${new Date(t.next_run_date).toLocaleDateString('id-ID', {dateStyle: 'long'})}</td>
                            <td>${statusBadge}</td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-info edit-recurring-btn" data-id="${t.id}"><i class="bi bi-pencil-fill"></i></button>
                                    <button class="btn btn-sm btn-secondary toggle-status-btn" data-id="${t.id}" data-active="${t.is_active}" title="${toggleText}"><i class="bi bi-power"></i></button>
                                    <button class="btn btn-sm btn-danger delete-recurring-btn" data-id="${t.id}"><i class="bi bi-trash-fill"></i></button>
                                </div>
                            </td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Belum ada template yang dibuat.</td></tr>`;
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Gagal memuat data: ${error.message}</td></tr>`;
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
