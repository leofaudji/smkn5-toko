function initActivityLogPage() {
    const tableBody = document.getElementById('activity-log-table-body');
    const searchInput = document.getElementById('search-log');
    const startDateFilter = document.getElementById('filter-log-mulai');
    const endDateFilter = document.getElementById('filter-log-akhir');
    const limitSelect = document.getElementById('filter-log-limit');
    const paginationContainer = document.getElementById('activity-log-pagination');

    if (!tableBody) return;

    const commonOptions = { dateFormat: "d-m-Y", allowInput: true };
    const startDatePicker = flatpickr(startDateFilter, commonOptions);
    const endDatePicker = flatpickr(endDateFilter, commonOptions);

    // Set default dates
    endDatePicker.setDate(new Date(), true);
    const sevenDaysAgo = new Date();
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
    startDatePicker.setDate(sevenDaysAgo, true);

    async function loadLogs(page = 1) {
        const params = new URLSearchParams({
            page,
            limit: limitSelect.value,
            search: searchInput.value,
            start_date: startDateFilter.value.split('-').reverse().join('-'),
            end_date: endDateFilter.value.split('-').reverse().join('-'),
        });

        tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></td></tr>`;
        try {
            const response = await fetch(`${basePath}/api/activity-log?${params.toString()}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            tableBody.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach(log => {
                    const actionBadge = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800/20 dark:text-blue-200">${log.action}</span>`;
                    const row = `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${new Date(log.timestamp).toLocaleString('id-ID')}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${log.username}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">${actionBadge}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">${log.details}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${log.ip_address}</td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-gray-500">Tidak ada log aktivitas ditemukan.</td></tr>';
            }
            renderPagination(paginationContainer, result.pagination, loadLogs);
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-red-500">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    let debounceTimer;
    const filterHandler = () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => loadLogs(1), 300); };
    [searchInput, startDateFilter, endDateFilter, limitSelect].forEach(el => el.addEventListener('change', filterHandler));
    searchInput.addEventListener('input', filterHandler);

    loadLogs();
}
