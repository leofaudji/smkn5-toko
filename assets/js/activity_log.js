function initActivityLogPage() {
    const tableBody = document.getElementById('activity-log-table-body');
    const searchInput = document.getElementById('search-log');
    const startDateFilter = document.getElementById('filter-log-mulai');
    const endDateFilter = document.getElementById('filter-log-akhir');
    const limitSelect = document.getElementById('filter-log-limit');
    const paginationContainer = document.getElementById('activity-log-pagination');

    if (!tableBody) return;

    // Set default dates
    endDateFilter.valueAsDate = new Date();
    const sevenDaysAgo = new Date();
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
    startDateFilter.valueAsDate = sevenDaysAgo;

    async function loadLogs(page = 1) {
        const params = new URLSearchParams({
            page,
            limit: limitSelect.value,
            search: searchInput.value,
            start_date: startDateFilter.value,
            end_date: endDateFilter.value,
        });

        tableBody.innerHTML = `<tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>`;
        try {
            const response = await fetch(`${basePath}/api/activity-log?${params.toString()}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            tableBody.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach(log => {
                    const row = `
                        <tr>
                            <td><small>${new Date(log.timestamp).toLocaleString('id-ID')}</small></td>
                            <td>${log.username}</td>
                            <td><span class="badge bg-info text-dark">${log.action}</span></td>
                            <td>${log.details}</td>
                            <td>${log.ip_address}</td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Tidak ada log aktivitas ditemukan.</td></tr>';
            }
            renderPagination(paginationContainer, result.pagination, loadLogs);
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    let debounceTimer;
    const filterHandler = () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => loadLogs(1), 300); };
    [searchInput, startDateFilter, endDateFilter, limitSelect].forEach(el => el.addEventListener('change', filterHandler));
    searchInput.addEventListener('input', filterHandler);

    loadLogs();
}
