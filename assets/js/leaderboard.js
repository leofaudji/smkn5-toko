let currentCategory = 'shoppers';

async function loadLeaderboard() {
    const periodDays = document.getElementById('period-days').value;
    const listBody = document.getElementById('leaderboard-body');
    const filterContainer = document.getElementById('filter-container');
    const listSubtitle = document.getElementById('list-subtitle');
    const emptyState = document.getElementById('empty-state');
    
    // Update headers based on category
    const thStat1 = document.getElementById('th-stat-1');
    const thStat2 = document.getElementById('th-stat-2');
    
    if (currentCategory === 'shoppers') {
        filterContainer.style.visibility = 'visible';
        listSubtitle.textContent = 'Top Members by Spending';
        thStat1.textContent = 'Total Belanja';
        thStat2.textContent = 'Trx';
    } else {
        filterContainer.style.visibility = 'hidden';
        listSubtitle.textContent = 'Top Members by WB Punctuality';
        thStat1.textContent = 'Points';
        thStat2.textContent = 'Total Setor';
    }

    listBody.innerHTML = '<tr><td colspan="4" class="py-20 text-center text-gray-400 text-sm">Memuat data peringkat...</td></tr>';
    emptyState.classList.add('hidden');

    try {
        const action = currentCategory === 'shoppers' ? 'get_top_shoppers' : 'get_top_loyalists';
        const url = `${basePath}/api/leaderboard?action=${action}&days=${periodDays}`;
        const response = await fetch(url);
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            renderList(result.data);
        } else {
            listBody.innerHTML = '';
            emptyState.classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error fetching leaderboard:', error);
        listBody.innerHTML = '<tr><td colspan="4" class="py-20 text-center text-red-500 text-sm">Gagal memuat data.</td></tr>';
    }
}

function renderList(members) {
    const listBody = document.getElementById('leaderboard-body');
    listBody.innerHTML = '';

    members.forEach((member, index) => {
        const rank = index + 1;
        const row = document.createElement('tr');
        
        // Highlight top 3
        if (rank <= 3) {
            row.className = `row-highlight-${rank}`;
        }
        
        let rankContent = rank;
        if (rank <= 3) {
            const badgeClass = `rank-${rank}-badge`;
            const icon = rank === 1 ? 'bi-trophy-fill' : 'bi-award-fill';
            rankContent = `<span class="rank-badge-item ${badgeClass}"><i class="bi ${icon}"></i></span>`;
        }

        let stat1 = currentCategory === 'shoppers' 
            ? `<span class="font-bold text-gray-900 dark:text-gray-100">${formatCurrency(member.total_belanja)}</span>`
            : `<span class="font-bold text-primary">${member.on_time_points} <small class="text-[10px] font-normal text-gray-400 uppercase">Pts</small></span>`;
            
        let stat2 = currentCategory === 'shoppers'
            ? `<span class="text-gray-400 text-xs">${member.total_transaksi}</span>`
            : `<span class="text-gray-400 text-xs">${formatCurrency(member.total_setoran)}</span>`;

        const escapedName = member.nama_lengkap.replace(/'/g, "\\'");

        row.innerHTML = `
            <td class="px-6 py-4 text-center font-bold text-sm text-gray-400">${rankContent}</td>
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 font-bold text-xs mr-3">
                        ${getInitials(member.nama_lengkap)}
                    </div>
                    <div>
                        <div class="text-sm font-bold text-gray-800 dark:text-gray-100 cursor-pointer hover:text-primary hover:underline transition-all" 
                             onclick="showMemberHistory(${member.id}, '${escapedName}', '${member.nomor_anggota}')">
                             ${member.nama_lengkap}
                        </div>
                        <div class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">${member.nomor_anggota}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 text-right">${stat1}</td>
            <td class="px-6 py-4 text-right">${stat2}</td>
        `;
        listBody.appendChild(row);
    });
}

/**
 * MODAL LOGIC
 */
async function showMemberHistory(id, name, no) {
    const modal = document.getElementById('history-modal');
    const modalName = document.getElementById('modal-member-name');
    const modalId = document.getElementById('modal-member-id');
    const loader = document.getElementById('modal-loader');
    
    modalName.textContent = name;
    modalId.textContent = no;
    modal.classList.remove('hidden');
    
    loader.classList.remove('hidden');
    document.getElementById('modal-content-penjualan').classList.add('hidden');
    document.getElementById('modal-content-wb').classList.add('hidden');
    
    try {
        const response = await fetch(`${basePath}/api/leaderboard?action=get_member_history&member_id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            renderModalData(result.data);
            loader.classList.add('hidden');
            switchModalTab('penjualan');
        }
    } catch (e) {
        console.error(e);
        loader.textContent = 'Gagal memuat riwayat.';
    }
}

function renderModalData(data) {
    const bodyPenjualan = document.getElementById('modal-body-penjualan');
    const bodyWb = document.getElementById('modal-body-wb');
    
    bodyPenjualan.innerHTML = '';
    bodyWb.innerHTML = '';
    
    if (data.penjualan.length === 0) {
        bodyPenjualan.innerHTML = '<tr><td colspan="4" class="py-4 text-center text-gray-400 italic">Tidak ada riwayat belanja.</td></tr>';
    } else {
        data.penjualan.forEach(item => {
            const row = `<tr class="group cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors" onclick="toggleSaleDetails(${item.id}, this)">
                <td class="py-3 font-bold text-gray-600 dark:text-gray-300 flex items-center gap-2">
                    <i class="bi bi-chevron-right text-[10px] transition-transform group-[.is-open]:rotate-90"></i>
                    #${item.nomor_referensi}
                </td>
                <td class="py-3 font-medium text-gray-400">${item.tanggal_penjualan}</td>
                <td class="py-3 text-right font-bold text-gray-700 dark:text-gray-200">${formatCurrency(item.total)}</td>
            </tr>
            <tr id="details-${item.id}" class="hidden bg-gray-50/50 dark:bg-gray-900/30">
                <td colspan="3" class="px-6 py-4">
                    <div class="item-details-container text-[11px]">
                         <div class="loader-inner py-2 text-center text-gray-400 animate-pulse">Memuat rincian...</div>
                         <table class="w-full hidden table-items">
                            <thead>
                                <tr class="text-gray-400 font-bold border-b border-gray-100 dark:border-gray-800">
                                    <th class="py-1 text-left">Item</th>
                                    <th class="py-1 text-center">Qty</th>
                                    <th class="py-1 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800"></tbody>
                         </table>
                    </div>
                </td>
            </tr>`;
            bodyPenjualan.insertAdjacentHTML('beforeend', row);
        });
    }
    
    if (data.wajib_belanja.length === 0) {
        bodyWb.innerHTML = '<tr><td colspan="3" class="py-4 text-center text-gray-400 italic">Tidak ada riwayat WB.</td></tr>';
    } else {
        data.wajib_belanja.forEach(item => {
            const badgeClass = item.jenis === 'setor' ? 'text-green-500 bg-green-50 dark:bg-green-900/20' : 'text-blue-500 bg-blue-50 dark:bg-blue-900/20';
            const row = `<tr>
                <td class="py-3 font-medium text-gray-400">${item.tanggal}</td>
                <td class="py-3">
                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase ${badgeClass}">${item.jenis}</span>
                    <div class="text-[9px] text-gray-400 italic mt-0.5">${item.keterangan || '-'}</div>
                </td>
                <td class="py-3 text-right font-bold text-gray-700 dark:text-gray-200">${formatCurrency(item.jumlah)}</td>
            </tr>`;
            bodyWb.insertAdjacentHTML('beforeend', row);
        });
    }
}

async function toggleSaleDetails(saleId, rowEl) {
    const detailRow = document.getElementById(`details-${saleId}`);
    const isOpening = detailRow.classList.contains('hidden');
    
    // Close others
    // document.querySelectorAll('[id^="details-"]').forEach(el => el.classList.add('hidden'));
    // document.querySelectorAll('tr.group').forEach(el => el.classList.remove('is-open'));

    if (isOpening) {
        detailRow.classList.remove('hidden');
        rowEl.classList.add('is-open');
        
        const container = detailRow.querySelector('.item-details-container');
        const table = container.querySelector('.table-items');
        const loader = container.querySelector('.loader-inner');
        const tbody = table.querySelector('tbody');
        
        if (tbody.innerHTML === '') {
            try {
                const response = await fetch(`${basePath}/api/leaderboard?action=get_sale_details&sale_id=${saleId}`);
                const result = await response.json();
                
                if (result.success) {
                    result.data.forEach(item => {
                        const row = `<tr>
                            <td class="py-2 text-gray-600 dark:text-gray-400">${item.nama_barang}</td>
                            <td class="py-2 text-center text-gray-500">${item.qty}</td>
                            <td class="py-2 text-right font-bold text-gray-700 dark:text-gray-200">${formatCurrency(item.subtotal)}</td>
                        </tr>`;
                        tbody.insertAdjacentHTML('beforeend', row);
                    });
                    loader.classList.add('hidden');
                    table.classList.remove('hidden');
                }
            } catch (e) {
                loader.textContent = 'Gagal memuat item.';
            }
        } else {
            loader.classList.add('hidden');
            table.classList.remove('hidden');
        }
    } else {
        detailRow.classList.add('hidden');
        rowEl.classList.remove('is-open');
    }
}

function switchModalTab(tab) {
    const btnPenjualan = document.getElementById('tab-modal-penjualan');
    const btnWb = document.getElementById('tab-modal-wb');
    const contentPenjualan = document.getElementById('modal-content-penjualan');
    const contentWb = document.getElementById('modal-content-wb');
    
    if (tab === 'penjualan') {
        btnPenjualan.classList.add('border-primary', 'text-primary');
        btnPenjualan.classList.remove('border-transparent', 'text-gray-400');
        btnWb.classList.remove('border-primary', 'text-primary');
        btnWb.classList.add('border-transparent', 'text-gray-400');
        contentPenjualan.classList.remove('hidden');
        contentWb.classList.add('hidden');
    } else {
        btnWb.classList.add('border-primary', 'text-primary');
        btnWb.classList.remove('border-transparent', 'text-gray-400');
        btnPenjualan.classList.remove('border-primary', 'text-primary');
        btnPenjualan.classList.add('border-transparent', 'text-gray-400');
        contentWb.classList.remove('hidden');
        contentPenjualan.classList.add('hidden');
    }
}

window.closeModal = function() {
    document.getElementById('history-modal').classList.add('hidden');
};

function switchCategory(cat) {
    currentCategory = cat;
    
    const shoppersBtn = document.getElementById('tab-shoppers');
    const loyalistsBtn = document.getElementById('tab-loyalists');
    const activeClass = ['bg-white', 'dark:bg-gray-700', 'text-primary', 'shadow-sm'];
    const inactiveClass = ['text-gray-500', 'hover:text-gray-700', 'dark:hover:text-gray-300'];

    if (cat === 'shoppers') {
        shoppersBtn.classList.add(...activeClass);
        shoppersBtn.classList.remove(...inactiveClass);
        loyalistsBtn.classList.remove(...activeClass);
        loyalistsBtn.classList.add(...inactiveClass);
    } else {
        loyalistsBtn.classList.add(...activeClass);
        loyalistsBtn.classList.remove(...inactiveClass);
        shoppersBtn.classList.remove(...activeClass);
        shoppersBtn.classList.add(...inactiveClass);
    }

    loadLeaderboard();
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

function getInitials(name) {
    if (!name) return '??';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
}

// Initialization for SPA
window.initLeaderboardPage = function() {
    loadLeaderboard();
};

// Initial load for direct access
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('leaderboard-body')) {
        loadLeaderboard();
    }
});
