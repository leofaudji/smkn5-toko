function initLaporanWbTahunanPage() {
    const tahunSelect = document.getElementById('laporan-wb-tahun');
    const btnTampilkan = document.getElementById('btn-tampilkan-laporan');
    const tbody = document.getElementById('laporan-wb-body');
    const filterTunggakan = document.getElementById('filter-tunggakan');
    const tfoot = document.getElementById('laporan-wb-footer');
    const loadingEl = document.getElementById('laporan-loading');
    const titleEl = document.getElementById('laporan-title');
    const historyBody = document.getElementById('wb-history-body');
    const btnExportPdf = document.getElementById('btn-export-pdf');
    const btnExportCsv = document.getElementById('btn-export-csv');

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
                            <div class="font-medium text-primary hover:text-primary-600 cursor-pointer member-name" data-id="${row.id}" data-name="${row.nama_lengkap}">${row.nama_lengkap}</div>
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

    async function showMemberHistory(memberId, memberName) {
        const tahun = tahunSelect.value;
        document.getElementById('wbHistoryModalLabel').textContent = `Riwayat Transaksi WB - ${memberName} (${tahun})`;
        historyBody.innerHTML = '<tr><td colspan="4" class="text-center p-4"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary mx-auto"></div></td></tr>';
        openModal('wbHistoryModal');

        try {
            const response = await fetch(`${basePath}/api/laporan-wb-tahunan?action=get_history&anggota_id=${memberId}&tahun=${tahun}`);
            const result = await response.json();

            if (result.status === 'success') {
                if (result.data.length === 0) {
                    historyBody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-gray-500">Tidak ada riwayat transaksi.</td></tr>';
                } else {
                    historyBody.innerHTML = result.data.map(item => `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${formatDate(item.tanggal)}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${item.jenis === 'setor' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'}">
                                    ${item.jenis === 'setor' ? 'Setoran' : 'Belanja'}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-right font-medium ${item.jenis === 'setor' ? 'text-green-600' : 'text-blue-600'}">
                                ${formatRupiah(item.jumlah)}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">${item.keterangan || '-'}</td>
                        </tr>
                    `).join('');
                }
            } else {
                historyBody.innerHTML = `<tr><td colspan="4" class="text-center p-4 text-red-500">${result.message}</td></tr>`;
            }
        } catch (error) {
            console.error(error);
            historyBody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-red-500">Gagal memuat data.</td></tr>';
        }
    }

    tbody.addEventListener('click', (e) => {
        if (e.target.classList.contains('member-name')) {
            const memberId = e.target.dataset.id;
            const memberName = e.target.dataset.name;
            showMemberHistory(memberId, memberName);
        }
    });

    btnTampilkan.addEventListener('click', fetchReport);

    if (btnExportPdf) {
        btnExportPdf.addEventListener('click', (e) => {
            e.preventDefault();
            const tahun = tahunSelect.value;
            const onlyArrears = filterTunggakan.checked ? 1 : 0;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `${basePath}/api/pdf`;
            form.target = '_blank';
            
            const params = { report: 'laporan-wb-tahunan', tahun: tahun, only_arrears: onlyArrears };
            for (const key in params) {
                const hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = key;
                hiddenField.value = params[key];
                form.appendChild(hiddenField);
            }
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        });
    }

    if (btnExportCsv) {
        btnExportCsv.addEventListener('click', (e) => {
            e.preventDefault();
            const tahun = tahunSelect.value;
            const onlyArrears = filterTunggakan.checked ? 1 : 0;
            const url = `${basePath}/api/csv?report=laporan-wb-tahunan&format=csv&tahun=${tahun}&only_arrears=${onlyArrears}`;
            window.open(url, '_blank');
        });
    }

    // Load initial data
    fetchReport();
}
