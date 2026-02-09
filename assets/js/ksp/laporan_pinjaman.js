function initLaporanPinjamanPage() {
    const tableBody = document.getElementById('laporan-table-body');
    const totalSisaPokokEl = document.getElementById('total-sisa-pokok');
    const btnFilter = document.getElementById('btn-filter');
    const searchInput = document.getElementById('search-anggota');
    const filterKolektibilitas = document.getElementById('filter-kolektibilitas');
    const btnExportPdf = document.getElementById('btn-export-pdf');
    const btnExportCsv = document.getElementById('btn-export-csv');

    if (!tableBody) return;

    loadData();

    btnFilter.addEventListener('click', loadData);
    
    // Allow enter key on search input
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') loadData();
    });

    async function loadData() {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Memuat data...</td></tr>';
        
        const search = searchInput.value;
        const kolektibilitas = filterKolektibilitas.value;
        
        try {
            const response = await fetch(`${basePath}/api/ksp/laporan-pinjaman?action=list&search=${encodeURIComponent(search)}&kolektibilitas=${encodeURIComponent(kolektibilitas)}`);
            const result = await response.json();
            
            if (result.success) {
                renderTable(result.data);
            } else {
                tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-red-500">${result.message}</td></tr>`;
            }
        } catch (error) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-red-500">Terjadi kesalahan jaringan</td></tr>';
        }
    }

    function renderTable(data) {
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-gray-500">Tidak ada data ditemukan.</td></tr>';
            totalSisaPokokEl.textContent = formatRupiah(0);
            return;
        }

        let totalSisa = 0;
        
        tableBody.innerHTML = data.map(item => {
            totalSisa += parseFloat(item.sisa_pokok);
            
            let badgeColor = 'bg-green-100 text-green-800';
            if (item.kolektibilitas === 'Macet') badgeColor = 'bg-red-100 text-red-800';
            else if (item.kolektibilitas === 'Diragukan') badgeColor = 'bg-orange-100 text-orange-800';
            else if (item.kolektibilitas === 'Kurang Lancar') badgeColor = 'bg-yellow-100 text-yellow-800';
            else if (item.kolektibilitas === 'Dalam Perhatian Khusus') badgeColor = 'bg-blue-100 text-blue-800';

            return `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">${item.nama_lengkap}</div>
                        <div class="text-xs text-gray-500">${item.nomor_anggota}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${item.nomor_pinjaman}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">${formatRupiah(item.jumlah_pinjaman)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-white">${formatRupiah(item.sisa_pokok)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-300">${item.hari_terlambat} Hari</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${badgeColor}">
                            ${item.kolektibilitas}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');

        totalSisaPokokEl.textContent = formatRupiah(totalSisa);
    }

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(angka);
    }

    // Export Handlers (Placeholder)
    btnExportPdf.addEventListener('click', () => {
        // Implementasi export PDF
        alert('Fitur Export PDF akan segera hadir.');
    });

    btnExportCsv.addEventListener('click', () => {
        // Implementasi export CSV
        alert('Fitur Export CSV akan segera hadir.');
    });
}