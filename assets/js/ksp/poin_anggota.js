function initPoinAnggotaPage() {
    const tableBody = document.getElementById('points-table-body');
    const searchInput = document.getElementById('search-member-points');
    const historyContent = document.getElementById('history-content');
    const historyTitle = document.getElementById('history-title');
    const modal = document.getElementById('modal-adjustment');
    const form = document.getElementById('form-adjustment');
    
    let currentPage = 1;
    let searchTimeout;

    if (!tableBody) return;

    loadMembers();

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            loadMembers();
        }, 500);
    });

    window.openAdjustmentModal = function() {
        form.reset();
        // Load members for dropdown
        fetch(`${basePath}/api/ksp/poin-anggota?action=get_all_members_simple`)
            .then(r => r.json())
            .then(res => {
                const select = document.getElementById('adjust-member-id');
                select.innerHTML = res.data.map(m => `<option value="${m.id}">${m.nama_lengkap} (${m.nomor_anggota})</option>`).join('');
                modal.classList.remove('hidden');
            });
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(`${basePath}/api/ksp/poin-anggota?action=adjust_points`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                modal.classList.add('hidden');
                loadMembers();
                // If the adjusted member is currently viewed in history, refresh history
                const currentHistoryMemberId = historyContent.dataset.memberId;
                if (currentHistoryMemberId == data.member_id) {
                    viewHistory(data.member_id, document.querySelector(`option[value="${data.member_id}"]`).text.split(' (')[0]);
                }
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan', 'error');
        }
    });

    async function loadMembers() {
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4">Memuat...</td></tr>';
        const search = searchInput.value;
        
        try {
            const response = await fetch(`${basePath}/api/ksp/poin-anggota?action=list_members&page=${currentPage}&search=${search}`);
            const result = await response.json();
            
            if (result.success) {
                renderTable(result.data);
                renderPagination(result.pagination);
            } else {
                tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-red-500">Gagal memuat data</td></tr>';
            }
        } catch (error) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-red-500">Error</td></tr>';
        }
    }

    function renderTable(data) {
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-gray-500">Tidak ada data anggota.</td></tr>';
            return;
        }

        tableBody.innerHTML = data.map(item => {
            let rankIcon = '';
            if (item.rank === 1) rankIcon = '🥇';
            else if (item.rank === 2) rankIcon = '🥈';
            else if (item.rank === 3) rankIcon = '🥉';

            return `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer" onclick="viewHistory(${item.id}, '${item.nama_lengkap}')">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-bold">
                    ${rankIcon} ${item.rank}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">${item.nama_lengkap}</div>
                    <div class="text-xs text-gray-500">${item.nomor_anggota}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-primary">
                    ${new Intl.NumberFormat('id-ID').format(item.gamification_points)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                    <button class="text-blue-600 hover:text-blue-900"><i class="bi bi-clock-history"></i></button>
                </td>
            </tr>
            `;
        }).join('');
    }

    window.viewHistory = async function(memberId, memberName) {
        historyTitle.textContent = `Riwayat: ${memberName}`;
        historyContent.innerHTML = '<div class="text-center py-4"><span class="animate-spin inline-block w-6 h-6 border-2 border-primary border-t-transparent rounded-full"></span></div>';
        historyContent.dataset.memberId = memberId;

        try {
            const response = await fetch(`${basePath}/api/ksp/poin-anggota?action=get_history&member_id=${memberId}`);
            const result = await response.json();
            
            if (result.success && result.data.length > 0) {
                historyContent.innerHTML = result.data.map(log => {
                    const isPositive = log.points_awarded > 0;
                    const colorClass = isPositive ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50';
                    const icon = isPositive ? 'bi-arrow-up' : 'bi-arrow-down';
                    
                    return `
                    <div class="flex items-start p-3 rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-700">
                        <div class="flex-shrink-0 mr-3">
                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full ${colorClass}">
                                <i class="bi ${icon}"></i>
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                ${log.keterangan || log.action_type}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                ${new Date(log.created_at).toLocaleString('id-ID')}
                            </p>
                        </div>
                        <div class="inline-flex items-center text-sm font-bold ${isPositive ? 'text-green-600' : 'text-red-600'}">
                            ${isPositive ? '+' : ''}${log.points_awarded}
                        </div>
                    </div>
                    `;
                }).join('');
            } else {
                historyContent.innerHTML = '<p class="text-center text-gray-500 text-sm py-4">Belum ada riwayat poin.</p>';
            }
        } catch (error) {
            historyContent.innerHTML = '<p class="text-center text-red-500 text-sm py-4">Gagal memuat riwayat.</p>';
        }
    };

    function renderPagination(pagination) {
        const container = document.getElementById('pagination-container');
        if (pagination.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = `<span class="text-sm text-gray-700 mr-4 self-center">Hal ${pagination.page} dari ${pagination.total_pages}</span>`;
        
        if (pagination.page > 1) {
            html += `<button onclick="changePage(${pagination.page - 1})" class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 mr-2">Prev</button>`;
        }
        if (pagination.page < pagination.total_pages) {
            html += `<button onclick="changePage(${pagination.page + 1})" class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Next</button>`;
        }
        
        container.innerHTML = html;
    }

    window.changePage = function(page) {
        currentPage = page;
        loadMembers();
    };
}