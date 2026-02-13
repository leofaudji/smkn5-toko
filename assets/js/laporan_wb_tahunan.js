function initLaporanWbTahunanPage() {
    const tahunSelect = document.getElementById('laporan-wb-tahun');
    const btnTampilkan = document.getElementById('btn-tampilkan-laporan');
    const tbody = document.getElementById('laporan-wb-body');
    const filterTunggakan = document.getElementById('filter-tunggakan');
    const tfoot = document.getElementById('laporan-wb-footer');
    const loadingEl = document.getElementById('laporan-loading');
    const titleEl = document.getElementById('laporan-title');

    // Isi dropdown tahun (5 tahun ke belakang)
    const currentYear = new Date().getFullYear();
    for (let i = 0; i < 5; i++) {
        const year = currentYear - i;
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        tahunSelect.appendChild(option);
    }

    async function fetchReport() {
        const tahun = tahunSelect.value;
        const onlyArrears = filterTunggakan.checked;
        titleEl.textContent = `Laporan Wajib Belanja Tahun ${tahun}`;
        
        tbody.innerHTML = '';
        tfoot.innerHTML = '';
        loadingEl.classList.remove('hidden');

        try {
            const response = await fetch(`${basePath}/api/laporan-wb-tahunan?tahun=${tahun}&only_arrears=${onlyArrears}`);
            const result = await response.json();

            if (result.status === 'success') {
                renderTable(result.data, result.summary, result.meta);
            } else {
                tbody.innerHTML = `<tr><td colspan="17" class="text-center p-4 text-red-500">${result.message}</td></tr>`;
            }
        } catch (error) {
            console.error(error);
            tbody.innerHTML = `<tr><td colspan="17" class="text-center p-4 text-red-500">Gagal memuat data.</td></tr>`;
        } finally {
            loadingEl.classList.add('hidden');
        }
    }

    function renderTable(data, summary, meta) {
        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="17" class="text-center p-4">Tidak ada data anggota.</td></tr>`;
            return;
        }

        // Data untuk pengecekan bulan berjalan
        const now = new Date();
        const currentMonth = now.getMonth() + 1;
        const currentYear = now.getFullYear();
        const selectedYear = parseInt(tahunSelect.value);
        
        // Ambil nominal wajib per bulan (default 50rb jika tidak ada)
        const nominalWajib = meta ? parseFloat(meta.nominal_wajib_belanja) : 50000;

        // Render Body
        tbody.innerHTML = data.map(row => {
            let cells = `<td class="px-3 py-2 whitespace-nowrap sticky left-0 bg-white dark:bg-gray-800 z-10 border-r border-gray-200 dark:border-gray-700">
                            <div class="font-medium text-gray-900 dark:text-white">${row.nama_lengkap}</div>
                            <div class="text-xs text-gray-500">${row.nomor_anggota}</div>
                         </td>`;
            
            const totalPaid = parseFloat(row.total_tahun); // Total yang sudah dibayar tahun ini
            
            for (let m = 1; m <= 12; m++) {
                const amount = row[`bulan_${m}`];
                
                // Hitung target yang seharusnya dibayar sampai bulan ke-m
                const expectedPaid = m * nominalWajib;
                
                // Cek apakah total pembayaran setahun sudah menutup target sampai bulan ini
                const isCovered = totalPaid >= expectedPaid;
                
                let display;
                
                if (amount > 0) {
                    if (isCovered) {
                        display = `<span class="text-green-600 font-medium">${formatRupiahCompact(amount)}</span>`;
                    } else {
                        // Ada bayar, tapi total kumulatif masih kurang (Tunggakan)
                        display = `<span class="text-yellow-600 font-bold" title="Total belum menutup target s/d bulan ini (Kurang)">${formatRupiahCompact(amount)} <i class="bi bi-exclamation-circle-fill text-xs"></i></span>`;
                    }
                } else if (isCovered) {
                    display = `<span class="text-green-600 font-bold text-xs bg-green-100 dark:bg-green-900/30 px-2 py-1 rounded-full" title="Tercover pembayaran bulan lain">Lunas</span>`;
                } else if ((selectedYear === currentYear && m <= currentMonth) || (selectedYear < currentYear)) {
                    // Jika belum bayar dan sudah lewat bulannya (atau bulan ini)
                    display = `<span class="text-red-600 font-bold text-xs bg-red-100 dark:bg-red-900/30 px-2 py-1 rounded-full">Belum</span>`;
                } else {
                    display = `<span class="text-gray-300">-</span>`;
                }
                
                cells += `<td class="px-2 py-2 text-right whitespace-nowrap border-r border-gray-100 dark:border-gray-700 last:border-0">${display}</td>`;
            }
            
            cells += `<td class="px-3 py-2 text-right font-bold whitespace-nowrap bg-gray-50 dark:bg-gray-900/50">${formatRupiahCompact(row.total_tahun)}</td>`;
            
            // Kolom Belanja
            cells += `<td class="px-3 py-2 text-right whitespace-nowrap text-blue-600 font-medium">${formatRupiahCompact(row.total_belanja)}</td>`;

            // Kolom Sisa Tunggakan
            const tunggakan = parseFloat(row.sisa_tunggakan) || 0;
            const tunggakanDisplay = tunggakan > 0 ? `<span class="text-red-600 font-bold">${formatRupiahCompact(tunggakan)}</span>` : `<span class="text-green-600 font-bold"><i class="bi bi-check-lg"></i></span>`;
            cells += `<td class="px-3 py-2 text-right whitespace-nowrap bg-red-50 dark:bg-red-900/20 border-l border-gray-200 dark:border-gray-700">${tunggakanDisplay}</td>`;
            
            // Kolom Sisa Saldo
            cells += `<td class="px-3 py-2 text-right whitespace-nowrap font-bold text-green-700 bg-green-50 dark:bg-green-900/20">${formatRupiahCompact(row.saldo_akhir)}</td>`;
            
            return `<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">${cells}</tr>`;
        }).join('');

        // Render Footer (Totals)
        let footerCells = `<td class="px-3 py-3 font-bold sticky left-0 bg-gray-100 dark:bg-gray-900 z-10 border-r border-gray-300 dark:border-gray-600">TOTAL</td>`;
        for (let m = 1; m <= 12; m++) {
            const totalMonth = summary.totals_per_month[m];
            footerCells += `<td class="px-2 py-3 text-right font-bold border-r border-gray-300 dark:border-gray-600">${formatRupiahCompact(totalMonth)}</td>`;
        }
        footerCells += `<td class="px-3 py-3 text-right font-bold text-primary">${formatRupiahCompact(summary.grand_total)}</td>`;
        footerCells += `<td class="px-3 py-3 text-right font-bold text-blue-600">${formatRupiahCompact(summary.grand_total_belanja)}</td>`;
        footerCells += `<td class="px-3 py-3 text-right font-bold text-red-600 border-l border-gray-300 dark:border-gray-600">${formatRupiahCompact(summary.grand_total_tunggakan)}</td>`;
        footerCells += `<td class="px-3 py-3 text-right font-bold text-green-700">${formatRupiahCompact(summary.grand_total_saldo)}</td>`;
        
        tfoot.innerHTML = `<tr>${footerCells}</tr>`;
    }

    // Helper untuk format rupiah yang lebih ringkas di tabel padat
    function formatRupiahCompact(value) {
        if (value === 0) return '0';
        return new Intl.NumberFormat('id-ID').format(value);
    }

    btnTampilkan.addEventListener('click', fetchReport);

    // Load initial data
    fetchReport();
}
