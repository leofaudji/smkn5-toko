function initLaporanPage() {
    const neracaTanggalInput = document.getElementById('neraca-tanggal');
    const neracaContent = document.getElementById('neraca-content');
    const labaRugiContent = document.getElementById('laba-rugi-content');
    const labaRugiTglMulai = document.getElementById('laba-rugi-tanggal-mulai');
    const labaRugiTglAkhir = document.getElementById('laba-rugi-tanggal-akhir');
    const lrCompareModeSelect = document.getElementById('lr-compare-mode');
    const lrPeriod2Container = document.getElementById('lr-period-2');
    const labaRugiTglMulai2 = document.getElementById('laba-rugi-tanggal-mulai-2');
    const lrCommonSizeSwitch = document.getElementById('lr-common-size-switch');
    const labaRugiTglAkhir2 = document.getElementById('laba-rugi-tanggal-akhir-2');
    const arusKasContent = document.getElementById('arus-kas-content');
    const arusKasTglMulai = document.getElementById('arus-kas-tanggal-mulai');
    const arusKasTglAkhir = document.getElementById('arus-kas-tanggal-akhir');

    const neracaIncludeClosing = document.getElementById('neraca-include-closing');
    const lrIncludeClosing = document.getElementById('lr-include-closing');
    const akIncludeClosing = document.getElementById('ak-include-closing');

    const exportNeracaPdfBtn = document.getElementById('export-neraca-pdf');
    const exportLrPdfBtn = document.getElementById('export-lr-pdf');
    const exportAkPdfBtn = document.getElementById('export-ak-pdf');
    const exportNeracaCsvBtn = document.getElementById('export-neraca-csv');
    const exportLrCsvBtn = document.getElementById('export-lr-csv');
    const exportAkCsvBtn = document.getElementById('export-ak-csv');

    const tooltipEl = document.getElementById('custom-tooltip');
    const tooltipContentEl = document.getElementById('custom-tooltip-content');


    const storageKey = 'laporan_filters';

    if (!neracaTanggalInput || !neracaContent) return;

    const commonOptions = { dateFormat: "d-m-Y", allowInput: true };
    const neracaPicker = flatpickr(neracaTanggalInput, commonOptions);
    const lrMulaiPicker = flatpickr(labaRugiTglMulai, commonOptions);
    const lrAkhirPicker = flatpickr(labaRugiTglAkhir, commonOptions);
    const lrMulai2Picker = flatpickr(labaRugiTglMulai2, commonOptions);
    const lrAkhir2Picker = flatpickr(labaRugiTglAkhir2, commonOptions);
    const akMulaiPicker = flatpickr(arusKasTglMulai, commonOptions);
    const akAkhirPicker = flatpickr(arusKasTglAkhir, commonOptions);

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    function saveFilters() {
        const filtersToSave = {
            neraca_tanggal: neracaTanggalInput.value,
            lr_start: labaRugiTglMulai.value,
            lr_end: labaRugiTglAkhir.value,
            lr_start2: labaRugiTglMulai2.value,
            lr_end2: labaRugiTglAkhir2.value,
            ak_start: arusKasTglMulai.value,
            ak_end: arusKasTglAkhir.value,
        };
        localStorage.setItem(storageKey, JSON.stringify(filtersToSave));
    }

    function loadAndSetFilters() {
        const savedFilters = JSON.parse(localStorage.getItem(storageKey)) || {};
        const now = new Date();
        const today = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);

        neracaPicker.setDate(savedFilters.neraca_tanggal || today, true);

        lrMulaiPicker.setDate(savedFilters.lr_start || firstDay, true);
        lrAkhirPicker.setDate(savedFilters.lr_end || lastDay, true);
        
        // Set default comparison period to previous month
        const prevMonthDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const firstDayPrevMonth = new Date(prevMonthDate.getFullYear(), prevMonthDate.getMonth(), 1);
        const lastDayPrevMonth = new Date(prevMonthDate.getFullYear(), prevMonthDate.getMonth() + 1, 0);
        lrMulai2Picker.setDate(savedFilters.lr_start2 || firstDayPrevMonth, true);
        lrAkhir2Picker.setDate(savedFilters.lr_end2 || lastDayPrevMonth, true);

        akMulaiPicker.setDate(savedFilters.ak_start || firstDay, true);
        akAkhirPicker.setDate(savedFilters.ak_end || lastDay, true);
    }

    function renderNeraca(data) {
        neracaContent.innerHTML = '';

        const renderRows = (items, level = 0) => {
            let html = '';
            items.forEach(item => {
                const isParent = item.children && item.children.length > 0; // This logic might be flawed if buildHierarchy isn't used
                const padding = level * 20;
                const fw = isParent ? 'font-bold' : '';
                
                // Saldo yang akan ditampilkan. Untuk akun induk, ini adalah jumlah dari saldo anak-anaknya.
                // Untuk akun anak (tanpa turunan), ini adalah saldo akhirnya sendiri.
                let saldoToShow;
                if (isParent) {
                    // Fungsi rekursif untuk menjumlahkan semua saldo akhir dari daun (leaf nodes)
                    const sumLeafNodes = (node) => {
                        if (!node.children || node.children.length === 0) return parseFloat(node.saldo_akhir);
                        return node.children.reduce((acc, child) => acc + sumLeafNodes(child), 0);
                    };
                    saldoToShow = sumLeafNodes(item);
                } else {
                    saldoToShow = parseFloat(item.saldo_akhir);
                }

                html += `
                    <tr class="text-sm">
                        <td style="padding-left: ${padding + 16}px;" class="py-2 ${fw} text-gray-800 dark:text-gray-200">${item.nama_akun}</td>
                        <td class="text-right py-2 pr-4 ${fw} text-gray-800 dark:text-gray-200">${formatCurrencyAccounting(saldoToShow)}</td>
                    </tr>
                `;
                if (isParent && item.children) {
                    html += renderRows(item.children, level + 1);
                }
            });
            return html;
        };

        const buildHierarchy = (list, parentId = null) => list
            .filter(item => item.parent_id == parentId)
            .map(item => ({ ...item, children: buildHierarchy(list, item.id) }));

        // Perbaiki fungsi calculateTotal untuk menjumlahkan semua item dalam data, bukan hanya root.
        const calculateTotal = (data) => data
            .filter(item => {
                const hasChildren = data.some(child => child.parent_id === item.id);
                return !hasChildren;
            }).reduce((acc, item) => acc + parseFloat(item.saldo_akhir), 0);

        const asetData = data.filter(d => d.tipe_akun === 'Aset');
        const liabilitasData = data.filter(d => d.tipe_akun === 'Liabilitas');
        const ekuitasData = data.filter(d => d.tipe_akun === 'Ekuitas');

        const aset = buildHierarchy(asetData);
        const liabilitas = buildHierarchy(liabilitasData);
        const ekuitas = buildHierarchy(ekuitasData);

        const totalAset = calculateTotal(asetData);
        const totalLiabilitas = calculateTotal(liabilitasData);
        const totalEkuitas = calculateTotal(ekuitasData);
        const totalLiabilitasEkuitas = totalLiabilitas + totalEkuitas;

        const isBalanced = Math.abs(totalAset - totalLiabilitasEkuitas) < 0.01;
        const balanceStatusClass = isBalanced ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30';
        const balanceStatusText = isBalanced ? 'BALANCE' : 'TIDAK BALANCE';
        const balanceBadge = document.getElementById('neraca-balance-status-badge');
        if (balanceBadge) {
            balanceBadge.innerHTML = `<span class="px-2 py-1 text-xs font-semibold rounded-full ${isBalanced ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">${balanceStatusText}</span>`;
        }

        const neracaHtml = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h5 class="text-lg font-semibold mb-2 text-gray-800 dark:text-white">Aset</h5>
                    <table class="w-full"><tbody class="divide-y divide-gray-200 dark:divide-gray-700">${renderRows(aset)}</tbody></table>
                </div>
                <div>
                    <h5 class="text-lg font-semibold mb-2 text-gray-800 dark:text-white">Liabilitas</h5>
                    <table class="w-full"><tbody class="divide-y divide-gray-200 dark:divide-gray-700">${renderRows(liabilitas)}</tbody></table>
                    <table class="w-full mt-2"><tbody><tr class="bg-gray-100 dark:bg-gray-700"><td class="font-bold p-2 text-sm">TOTAL LIABILITAS</td><td class="text-right font-bold p-2 text-sm">${formatCurrencyAccounting(totalLiabilitas)}</td></tr></tbody></table>

                    <h5 class="mt-6 text-lg font-semibold mb-2 text-gray-800 dark:text-white">Ekuitas</h5>
                    <table class="w-full"><tbody class="divide-y divide-gray-200 dark:divide-gray-700">${renderRows(ekuitas)}</tbody></table>
                    <table class="w-full mt-2"><tbody><tr class="bg-gray-100 dark:bg-gray-700"><td class="font-bold p-2 text-sm">TOTAL EKUITAS</td><td class="text-right font-bold p-2 text-sm">${formatCurrencyAccounting(totalEkuitas)}</td></tr></tbody></table>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-6">
                <div>
                    <table class="w-full"><tbody><tr class="${balanceStatusClass}"><td class="font-bold p-3 text-gray-800 dark:text-gray-100">TOTAL ASET</td><td class="text-right font-bold p-3 text-gray-800 dark:text-gray-100">${formatCurrencyAccounting(totalAset)}</td></tr></tbody></table>
                </div>
                <div>
                    <table class="w-full"><tbody><tr class="${balanceStatusClass}"><td class="font-bold p-3 text-gray-800 dark:text-gray-100">TOTAL LIABILITAS + EKUITAS</td><td class="text-right font-bold p-3 text-gray-800 dark:text-gray-100">${formatCurrencyAccounting(totalLiabilitasEkuitas)}</td></tr></tbody></table>
                </div>
            </div>
        `;
        neracaContent.innerHTML = neracaHtml;
    }

    async function loadNeraca() {
        const tanggal = neracaTanggalInput.value.split('-').reverse().join('-');
        neracaContent.innerHTML = '<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>';
        
        const params = new URLSearchParams({
            tanggal: tanggal
        });
        if (neracaIncludeClosing.checked) params.append('include_closing', 'true');

        try {
            const response = await fetch(`${basePath}/api/laporan_neraca_handler.php?${params.toString()}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);
            renderNeraca(result.data);
        } catch (error) {
            neracaContent.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">Gagal memuat laporan: ${error.message}</div>`;
        }
    }

    function renderLabaRugi(data) {
        labaRugiContent.innerHTML = '';
        const { current, previous } = data;
        const isComparison = !!previous; // Cek apakah ada data pembanding
        const isCommonSize = current.pendapatan.length > 0 && current.pendapatan[0].hasOwnProperty('percentage'); // Cek apakah ada data persentase

        // Gabungkan semua akun dari kedua periode untuk membuat template tabel
        const allAccounts = new Map();
        [...(current.pendapatan || []), ...(current.beban || []), ...(previous?.pendapatan || []), ...(previous?.beban || [])].forEach(acc => {
            if (!allAccounts.has(acc.id)) {
                allAccounts.set(acc.id, { id: acc.id, nama_akun: acc.nama_akun, tipe_akun: acc.tipe_akun });
            }
        });

        const findAccountTotal = (periodData, accountId) => {
            const acc = [...(periodData.pendapatan || []), ...(periodData.beban || [])].find(a => a.id === accountId);
            if (!acc) return { total: 0, percentage: 0 };
            return { total: acc.total, percentage: acc.percentage || 0 };
        };

        const calculateChange = (currentVal, prevVal) => {
            if (prevVal === 0) return currentVal > 0 ? '<span class="text-success">Baru</span>' : '-';
            const change = ((currentVal - prevVal) / Math.abs(prevVal)) * 100; // Avoid division by zero
            const color = change >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
            const icon = change >= 0 ? '<i class="bi bi-arrow-up"></i>' : '<i class="bi bi-arrow-down"></i>';
            return `<span class="${color}">${icon} ${Math.abs(change).toFixed(1)}%</span>`;
        };

        const renderRows = (tipe) => {
            let html = '';
            const colCount = 2 + (isComparison ? 2 : 0) + (isCommonSize ? (isComparison ? 2 : 1) : 0);
            const accountsOfType = Array.from(allAccounts.values()).filter(acc => acc.tipe_akun === tipe);
            if (accountsOfType.length === 0) return `<tr><td colspan="${colCount}" class="text-gray-500 dark:text-gray-400 px-4 py-2">Tidak ada data.</td></tr>`;

            accountsOfType.forEach(acc => {
                const currentData = findAccountTotal(current, acc.id);
                // Tambahkan URL Drill Down ke Buku Besar.
                // Gunakan tanggal dari filter Laba Rugi, konversi DD-MM-YYYY ke YYYY-MM-DD
                const startDate = labaRugiTglMulai.value.split('-').reverse().join('-');
                const endDate = labaRugiTglAkhir.value.split('-').reverse().join('-');
                const drillDownUrl = `${basePath}/buku-besar?account_id=${acc.id}&start_date=${startDate}&end_date=${endDate}`;
                html += `<tr class="text-sm"><td class="px-4 py-2">${acc.nama_akun}</td><td class="text-right px-4 py-2"><a href="${drillDownUrl}" class="drill-down-link" title="Lihat Detail Transaksi">${formatCurrencyAccounting(currentData.total)}</a></td>`;
                if (isCommonSize) {
                    html += `<td class="text-right px-4 py-2 text-gray-500 dark:text-gray-400 text-xs">${currentData.percentage.toFixed(2)}%</td>`;
                }
                if (isComparison) {
                    const prevData = findAccountTotal(previous, acc.id);
                    html += `<td class="text-right px-4 py-2">${formatCurrencyAccounting(prevData.total)}</td>`;
                    if (isCommonSize) html += `<td class="text-right px-4 py-2 text-gray-500 dark:text-gray-400 text-xs">${prevData.percentage.toFixed(2)}%</td>`;
                    html += `<td class="text-right px-4 py-2 text-xs">${calculateChange(currentData.total, prevData.total)}</td>`;
                }
                html += `</tr>`;
            });
            return html;
        };

        const headerColSpan = 2 + (isComparison ? 2 : 0) + (isCommonSize ? (isComparison ? 2 : 1) : 0);

        const labaRugiHtml = `
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Periode Saat Ini</th>
                        ${isCommonSize ? '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">%</th>' : ''}
                        ${isComparison ? '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Periode Pembanding</th>' : ''}
                        ${isComparison && isCommonSize ? '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">%</th>' : ''}
                        ${isComparison ? '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Perubahan</th>' : ''}
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="bg-gray-100 dark:bg-gray-700"><td colspan="${headerColSpan}" class="px-4 py-2 font-bold text-sm">Pendapatan</td></tr>
                    ${renderRows('Pendapatan')}
                    <tr class="bg-gray-100 dark:bg-gray-700 text-sm">
                        <td class="font-bold px-4 py-2">TOTAL PENDAPATAN</td>
                        <td class="text-right font-bold px-4 py-2">${formatCurrencyAccounting(current.summary.total_pendapatan)}</td>
                        ${isCommonSize ? '<td class="text-right font-bold px-4 py-2 text-gray-500 dark:text-gray-400 text-xs">100.00%</td>' : ''}
                        ${isComparison ? `<td class="text-right font-bold px-4 py-2">${formatCurrencyAccounting(previous.summary.total_pendapatan)}</td>` : ''}
                        ${isComparison && isCommonSize ? '<td class="text-right font-bold px-4 py-2 text-gray-500 dark:text-gray-400 text-xs">100.00%</td>' : ''}
                        ${isComparison ? `<td class="text-right text-xs px-4 py-2">${calculateChange(current.summary.total_pendapatan, previous.summary.total_pendapatan)}</td>` : ''}
                    </tr>
                    
                    <tr class="bg-gray-100 dark:bg-gray-700"><td colspan="${headerColSpan}" class="px-4 py-2 font-bold text-sm pt-4">Beban</td></tr>
                    ${renderRows('Beban')}
                    <tr class="bg-gray-100 dark:bg-gray-700 text-sm">
                        <td class="font-bold px-4 py-2">TOTAL BEBAN</td>
                        <td class="text-right font-bold px-4 py-2">${formatCurrencyAccounting(current.summary.total_beban)}</td>
                        ${isCommonSize ? `<td class="text-right font-bold px-4 py-2 text-gray-500 dark:text-gray-400 text-xs">${(current.summary.total_beban_percentage || 0).toFixed(2)}%</td>` : ''}
                        ${isComparison ? `<td class="text-right font-bold px-4 py-2">${formatCurrencyAccounting(previous.summary.total_beban)}</td>` : ''}
                        ${isComparison && isCommonSize ? `<td class="text-right font-bold px-4 py-2 text-gray-500 dark:text-gray-400 text-xs">${(previous.summary.total_beban_percentage || 0).toFixed(2)}%</td>` : ''}
                        ${isComparison ? `<td class="text-right text-xs px-4 py-2">${calculateChange(current.summary.total_beban, previous.summary.total_beban)}</td>` : ''}
                    </tr>
                </tbody>
                <tfoot class="border-t-2 border-gray-300 dark:border-gray-600">
                    <tr class="${current.summary.laba_bersih >= 0 ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'}">
                        <td class="font-bold text-lg px-4 py-3">LABA (RUGI) BERSIH</td>
                        <td class="text-right font-bold text-lg px-4 py-3">${formatCurrencyAccounting(current.summary.laba_bersih)}</td>
                        ${isCommonSize ? `<td class="text-right font-bold text-lg px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">${(current.summary.laba_bersih_percentage || 0).toFixed(2)}%</td>` : ''}
                        ${isComparison ? `<td class="text-right font-bold text-lg px-4 py-3">${formatCurrencyAccounting(previous.summary.laba_bersih)}</td>` : ''}
                        ${isComparison && isCommonSize ? `<td class="text-right font-bold text-lg px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">${(previous.summary.laba_bersih_percentage || 0).toFixed(2)}%</td>` : ''}
                        ${isComparison ? `<td class="text-right text-xs px-4 py-2">${calculateChange(current.summary.laba_bersih, previous.summary.laba_bersih)}</td>` : ''}
                    </tr>
                </tfoot>
            </table>
        `;
        labaRugiContent.innerHTML = labaRugiHtml;
    }

    async function loadLabaRugi() {
        const params = new URLSearchParams({
            start: labaRugiTglMulai.value.split('-').reverse().join('-'),
            end: labaRugiTglAkhir.value.split('-').reverse().join('-')
        });

        if (lrIncludeClosing.checked) {
            params.append('include_closing', 'true');
        }

        const isCommonSize = lrCommonSizeSwitch.checked;
        if (isCommonSize) {
            params.append('common_size', 'true');
        }

        const compareMode = lrCompareModeSelect.value;
        if (compareMode !== 'none') {
            params.append('compare', 'true');
            let start2, end2;

            if (compareMode === 'custom') {
                start2 = labaRugiTglMulai2.value.split('-').reverse().join('-');
                end2 = labaRugiTglAkhir2.value.split('-').reverse().join('-');
            } else {
                const parseDate = (dateStr) => {
                    if (!dateStr) return new Date();
                    const [day, month, year] = dateStr.split('-');
                    return new Date(`${year}-${month}-${day}`);
                };
                const mainStartDate = parseDate(labaRugiTglMulai.value);
                const mainEndDate = parseDate(labaRugiTglAkhir.value);

                if (compareMode === 'previous_period') {
                    const duration = mainEndDate.getTime() - mainStartDate.getTime(); // Duration in ms
                    const prevEndDate = new Date(mainStartDate.getTime() - (24 * 60 * 60 * 1000)); // One day before main start
                    const prevStartDate = new Date(prevEndDate.getTime() - duration);
                    start2 = prevStartDate.toISOString().split('T')[0];
                    end2 = prevEndDate.toISOString().split('T')[0];
                } else if (compareMode === 'previous_year_month') {
                    const prevStart = new Date(mainStartDate);
                    prevStart.setFullYear(prevStart.getFullYear() - 1);
                    const prevEnd = new Date(mainEndDate);
                    prevEnd.setFullYear(prevEnd.getFullYear() - 1);
                    start2 = prevStart.toISOString().split('T')[0];
                    end2 = prevEnd.toISOString().split('T')[0];
                }
            }
            params.append('start2', start2);
            params.append('end2', end2);
        }

        labaRugiContent.innerHTML = '<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>';
        try {
            const response = await fetch(`${basePath}/api/laporan_laba_rugi_handler.php?${params.toString()}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);
            renderLabaRugi(result.data);
        } catch (error) {
            labaRugiContent.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">Gagal memuat laporan: ${error.message}</div>`;
        }
    }

    function renderArusKas(data) {
        arusKasContent.innerHTML = '';
        const { arus_kas_operasi, arus_kas_investasi, arus_kas_pendanaan, kenaikan_penurunan_kas, saldo_kas_awal, saldo_kas_akhir_terhitung } = data;

        const renderSection = (title, amount) => `
            <tr class="text-sm">
                <td class="px-4 py-2">${title}</td>
                <td class="text-right px-4 py-2">${formatCurrencyAccounting(amount)}</td>
            </tr>
        `;
        
        const createTooltipContent = (details) => {
            // 'details' adalah objek, bukan array. Kita cek dengan Object.keys.
            if (!details || Object.keys(details).length === 0) return 'Tidak ada rincian.';
            let content = '<ul class="list-none mb-0 space-y-1">';
            // Gunakan Object.entries untuk iterasi pada objek
            for (const [akun, jumlah] of Object.entries(details)) {
                content += `<li class="flex justify-between gap-4"><span>${akun}</span> <span class="font-bold">${formatCurrencyAccounting(jumlah)}</span></li>`;
            }
            content += '</ul>';
            return content;
        };

        const arusKasHtml = `
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="bg-gray-100 dark:bg-gray-700"><td colspan="2" class="px-4 py-2 font-bold text-sm">Arus Kas dari Aktivitas Operasi
                        <i class="bi bi-info-circle-fill ml-2 text-primary cursor-pointer" data-tooltip-trigger data-tooltip-details='${JSON.stringify(arus_kas_operasi.details)}'></i>
                    </td></tr>
                    ${renderSection('Total Arus Kas Operasi', arus_kas_operasi.total)}
                    
                    <tr class="bg-gray-100 dark:bg-gray-700"><td colspan="2" class="px-4 py-2 font-bold text-sm mt-3">Arus Kas dari Aktivitas Investasi
                        <i class="bi bi-info-circle-fill ml-2 text-primary cursor-pointer" data-tooltip-trigger data-tooltip-details='${JSON.stringify(arus_kas_investasi.details)}'></i>
                    </td></tr>
                    ${renderSection('Total Arus Kas Investasi', arus_kas_investasi.total)}

                    <tr class="bg-gray-100 dark:bg-gray-700"><td colspan="2" class="px-4 py-2 font-bold text-sm mt-3">Arus Kas dari Aktivitas Pendanaan
                        <i class="bi bi-info-circle-fill ml-2 text-primary cursor-pointer" data-tooltip-trigger data-tooltip-details='${JSON.stringify(arus_kas_pendanaan.details)}'></i>
                    </td></tr>
                    ${renderSection('Total Arus Kas Pendanaan', arus_kas_pendanaan.total)}
                </tbody>
                <tfoot class="border-t-2 border-gray-300 dark:border-gray-600 text-sm">
                    <tr class="font-bold">
                        <td class="px-4 py-2">Kenaikan (Penurunan) Bersih Kas</td>
                        <td class="text-right px-4 py-2">${formatCurrencyAccounting(kenaikan_penurunan_kas)}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2">Saldo Kas pada Awal Periode</td>
                        <td class="text-right px-4 py-2">${formatCurrencyAccounting(saldo_kas_awal)}</td>
                    </tr>
                    <tr class="font-bold bg-green-100 dark:bg-green-900/30">
                        <td class="px-4 py-3">Saldo Kas pada Akhir Periode</td>
                        <td class="text-right px-4 py-3">${formatCurrencyAccounting(saldo_kas_akhir_terhitung)}</td>
                    </tr>
                </tbody>
            </table>
        `;
        arusKasContent.innerHTML = arusKasHtml;
    }

    async function loadArusKas() {
        const startDate = arusKasTglMulai.value.split('-').reverse().join('-');
        const endDate = arusKasTglAkhir.value.split('-').reverse().join('-');
        arusKasContent.innerHTML = '<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>';

        const params = new URLSearchParams({
            start: startDate,
            end: endDate
        });
        if (akIncludeClosing.checked) params.append('include_closing', 'true');

        try {
            const response = await fetch(`${basePath}/api/laporan_arus_kas_handler.php?${params.toString()}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);
            renderArusKas(result.data);
        } catch (error) {
            arusKasContent.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">Gagal memuat laporan: ${error.message}</div>`;
        }
    }

    // --- Custom Tab and Dropdown Logic ---
    function setupTabs() {
        const tabContainer = document.getElementById('laporanTab');
        const tabButtons = tabContainer.querySelectorAll('.laporan-tab-btn');
        const tabPanes = document.getElementById('laporanTabContent').querySelectorAll('.laporan-tab-pane');

        function switchTab(targetId) {
            tabPanes.forEach(pane => {
                pane.classList.toggle('hidden', pane.id !== targetId);
            });
            tabButtons.forEach(button => {
                const isActive = button.dataset.target === `#${targetId}`;
                button.classList.toggle('border-primary', isActive);
                button.classList.toggle('text-primary', isActive);
                button.classList.toggle('border-transparent', !isActive);
                button.classList.toggle('text-gray-500', !isActive);
                button.classList.toggle('dark:text-gray-400', !isActive);
                button.classList.toggle('hover:text-gray-700', !isActive);
                button.classList.toggle('hover:border-gray-300', !isActive);
            });

            // Load content for the new active tab
            if (targetId === 'neraca-pane') loadNeraca();
            else if (targetId === 'laba-rugi-pane') loadLabaRugi();
            else if (targetId === 'arus-kas-pane') loadArusKas();
        }

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                switchTab(button.dataset.target.substring(1));
            });
        });

        // Initial setup
        switchTab('neraca-pane');
    }

    function setupDropdowns() {
        document.querySelectorAll('[data-dropdown-toggle]').forEach(button => {
            const dropdownId = button.getAttribute('data-dropdown-toggle');
            const dropdown = document.getElementById(dropdownId);
            if (dropdown) {
                button.addEventListener('click', (event) => {
                    event.stopPropagation();
                    dropdown.classList.toggle('hidden');
                });
            }
        });
        // Close dropdowns when clicking outside
        window.addEventListener('click', (event) => {
            document.querySelectorAll('[id$="-export-dropdown"]').forEach(dropdown => {
                if (!dropdown.classList.contains('hidden') && !dropdown.previousElementSibling.contains(event.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        });
    }

    function setupTooltips() {
        document.body.addEventListener('mouseover', (e) => {
            const trigger = e.target.closest('[data-tooltip-trigger]');
            if (trigger) {
                const details = JSON.parse(trigger.dataset.tooltipDetails || '{}');
                tooltipContentEl.innerHTML = createTooltipContent(details);
                tooltipEl.classList.remove('hidden');
                
                const rect = trigger.getBoundingClientRect();
                tooltipEl.style.left = `${rect.left + window.scrollX}px`;
                tooltipEl.style.top = `${rect.bottom + window.scrollY + 5}px`;
            }
        });

        document.body.addEventListener('mouseout', (e) => {
            const trigger = e.target.closest('[data-tooltip-trigger]');
            if (trigger) {
                tooltipEl.classList.add('hidden');
            }
        });
    }

    // Fungsi untuk memanggil load dan save
    const handleNeracaChange = () => { saveFilters(); loadNeraca(); };
    const handleLabaRugiChange = () => { saveFilters(); loadLabaRugi(); };
    const handleArusKasChange = () => { saveFilters(); loadArusKas(); };

    [neracaTanggalInput, neracaIncludeClosing, labaRugiTglMulai, labaRugiTglAkhir, labaRugiTglMulai2, labaRugiTglAkhir2, lrCommonSizeSwitch, lrIncludeClosing, arusKasTglMulai, arusKasTglAkhir, akIncludeClosing].forEach(el => {
        el?.addEventListener('change', () => {
            const activeTab = document.querySelector('.laporan-tab-pane:not(.hidden)');
            if (activeTab.id === 'neraca-pane') handleNeracaChange();
            else if (activeTab.id === 'laba-rugi-pane') handleLabaRugiChange();
            else if (activeTab.id === 'arus-kas-pane') handleArusKasChange();
        });
    });

    lrCompareModeSelect.addEventListener('change', () => {        
        lrPeriod2Container.classList.toggle('hidden', lrCompareModeSelect.value !== 'custom');
        handleLabaRugiChange();
    });

    // --- Event Listeners untuk Export ---

    // Event listener untuk tombol PDF (sekarang menggunakan FPDF handler)
    exportNeracaPdfBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';
        const params = { report: 'neraca', tanggal: neracaTanggalInput.value.split('-').reverse().join('-') };
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

    exportLrPdfBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';
        const params = { 
            report: 'laba-rugi', 
            start: labaRugiTglMulai.value.split('-').reverse().join('-'), 
            end: labaRugiTglAkhir.value.split('-').reverse().join('-'), 
            compare_mode: lrCompareModeSelect.value 
        };
        const compareMode = lrCompareModeSelect.value;
        if (compareMode !== 'none') {
            params.compare = 'true';
            if (compareMode === 'custom') {
                params.start2 = labaRugiTglMulai2.value.split('-').reverse().join('-'),
                params.end2 = labaRugiTglAkhir2.value.split('-').reverse().join('-')
            }
            // Note: For other comparison modes, the backend will calculate the dates.
            // We just need to pass the main dates correctly.
            // To be safe, we can pass the calculated dates if we want frontend to be the source of truth,
            // but for now, let's assume backend handles 'previous_period' etc. based on main dates.
        }
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

    exportAkPdfBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';
        const params = { 
            report: 'arus-kas', 
            start: arusKasTglMulai.value.split('-').reverse().join('-'), 
            end: arusKasTglAkhir.value.split('-').reverse().join('-') 
        };
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

    // Event listener untuk tombol CSV (tetap sama)
    exportNeracaCsvBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            window.open(`${basePath}/api/csv?report=neraca&format=csv&tanggal=${neracaTanggalInput.value.split('-').reverse().join('-')}`, '_blank');
    });
    exportLrCsvBtn?.addEventListener('click', (e) => {
        e.preventDefault();
            const params = new URLSearchParams({ report: 'laba-rugi', format: 'csv', start: labaRugiTglMulai.value.split('-').reverse().join('-'), end: labaRugiTglAkhir.value.split('-').reverse().join('-') });
            if (lrCompareModeSelect.value !== 'none') {
                params.append('compare', 'true');
                params.append('start2', labaRugiTglMulai2.value.split('-').reverse().join('-'));
                params.append('end2', labaRugiTglAkhir2.value.split('-').reverse().join('-'));
            }
            window.open(`${basePath}/api/csv?${params.toString()}`, '_blank');
    });
    exportAkCsvBtn?.addEventListener('click', (e) => {
        e.preventDefault();
            window.open(`${basePath}/api/csv?report=arus-kas&format=csv&start=${arusKasTglMulai.value.split('-').reverse().join('-')}&end=${arusKasTglAkhir.value.split('-').reverse().join('-')}`, '_blank');
    });

    // Initial Load
    loadAndSetFilters();
    setupTabs();
    setupDropdowns();
    setupTooltips();
}