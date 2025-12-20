function initLaporanPage() {
    const neracaTanggalInput = document.getElementById('neraca-tanggal');
    const neracaContent = document.getElementById('neraca-content');
    const labaRugiTab = document.getElementById('laba-rugi-tab');
    const labaRugiContent = document.getElementById('laba-rugi-content');
    const labaRugiTglMulai = document.getElementById('laba-rugi-tanggal-mulai');
    const labaRugiTglAkhir = document.getElementById('laba-rugi-tanggal-akhir');
    const lrCompareModeSelect = document.getElementById('lr-compare-mode');
    const lrPeriod2Container = document.getElementById('lr-period-2');
    const labaRugiTglMulai2 = document.getElementById('laba-rugi-tanggal-mulai-2');
    const lrCommonSizeSwitch = document.getElementById('lr-common-size-switch');
    const labaRugiTglAkhir2 = document.getElementById('laba-rugi-tanggal-akhir-2');
    const arusKasTab = document.getElementById('arus-kas-tab');
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


    const storageKey = 'laporan_filters';

    if (!neracaTanggalInput || !neracaContent) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    function saveFilters() {
        const filtersToSave = {
            neraca_tanggal: neracaTanggalInput.value,
            lr_start: labaRugiTglMulai.value,
            lr_end: labaRugiTglAkhir.value,
            ak_start: arusKasTglMulai.value,
            ak_end: arusKasTglAkhir.value,
        };
        localStorage.setItem(storageKey, JSON.stringify(filtersToSave));
    }

    function loadAndSetFilters() {
        const savedFilters = JSON.parse(localStorage.getItem(storageKey)) || {};
        const now = new Date();
        const today = now.toISOString().split('T')[0];
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];

        neracaTanggalInput.value = savedFilters.neraca_tanggal || today;

        labaRugiTglMulai.value = savedFilters.lr_start || firstDay;
        labaRugiTglAkhir.value = savedFilters.lr_end || lastDay;
        
        // Set default comparison period to previous month
        const prevMonthDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const firstDayPrevMonth = new Date(prevMonthDate.getFullYear(), prevMonthDate.getMonth(), 1).toISOString().split('T')[0];
        const lastDayPrevMonth = new Date(prevMonthDate.getFullYear(), prevMonthDate.getMonth() + 1, 0).toISOString().split('T')[0];
        labaRugiTglMulai2.value = savedFilters.lr_start2 || firstDayPrevMonth;
        labaRugiTglAkhir2.value = savedFilters.lr_end2 || lastDayPrevMonth;

        arusKasTglMulai.value = savedFilters.ak_start || firstDay;
        arusKasTglAkhir.value = savedFilters.ak_end || lastDay;
    }

    function renderNeraca(data) {
        neracaContent.innerHTML = '';

        const renderRows = (items, level = 0) => {
            let html = '';
            items.forEach(item => {
                const isParent = item.children && item.children.length > 0;
                const padding = level * 20;
                const fw = isParent ? 'fw-bold' : '';
                
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
                    <tr>
                        <td style="padding-left: ${padding}px;" class="${fw}">${item.nama_akun}</td>
                        <td class="text-end ${fw}">${formatCurrencyAccounting(saldoToShow)}</td>
                    </tr>
                `;
                if (isParent) {
                    html += renderRows(item.children, level + 1);
                }
            });
            return html;
        };

        const buildHierarchy = (list, parentId = null) => list
            .filter(item => item.parent_id == parentId)
            .map(item => ({ ...item, children: buildHierarchy(list, item.id) }));

        // Perbaiki fungsi calculateTotal untuk menjumlahkan semua item dalam data, bukan hanya root.
        const calculateTotal = (data) => data.reduce((acc, item) => acc + parseFloat(item.saldo_akhir), 0);

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
        const balanceStatusClass = isBalanced ? 'table-success' : 'table-danger';
        const balanceStatusText = isBalanced ? 'BALANCE' : 'TIDAK BALANCE';
        const balanceBadge = document.getElementById('neraca-balance-status-badge');
        if (balanceBadge) {
            balanceBadge.innerHTML = `<span class="badge ${isBalanced ? 'bg-success' : 'bg-danger'}">${balanceStatusText}</span>`;
        }

        const neracaHtml = `
            <div class="row">
                <div class="col-md-6">
                    <h5>Aset</h5>
                    <table class="table table-sm"><tbody>${renderRows(asetData)}</tbody></table><br>
                </div>
                <div class="col-md-6">
                    <h5>Liabilitas</h5>
                    <table class="table table-sm"><tbody>${renderRows(liabilitasData)}</tbody></table>
                    <table class="table"><tbody><tr class="table-light"><td class="fw-bold">TOTAL LIABILITAS</td><td class="text-end fw-bold">${formatCurrencyAccounting(totalLiabilitas)}</td></tr></tbody></table><br>

                    <h5 class="mt-4">Ekuitas</h5>
                    <table class="table table-sm"><tbody>${renderRows(ekuitasData)}</tbody></table>
                    <table class="table"><tbody><tr class="table-light"><td class="fw-bold">TOTAL EKUITAS</td><td class="text-end fw-bold">${formatCurrencyAccounting(totalEkuitas)}</td></tr></tbody></table><br>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-6">
                    <table class="table"><tbody><tr class="${balanceStatusClass}"><td class="fw-bold">TOTAL ASET</td><td class="text-end fw-bold">${formatCurrencyAccounting(totalAset)}</td></tr></tbody></table>
                </div>
                <div class="col-md-6">
                    <table class="table"><tbody><tr class="${balanceStatusClass}"><td class="fw-bold">TOTAL LIABILITAS + EKUITAS</td><td class="text-end fw-bold">${formatCurrencyAccounting(totalLiabilitasEkuitas)}</td></tr></tbody></table>
                </div>
            </div>
        `;
        neracaContent.innerHTML = neracaHtml;
    }

    async function loadNeraca() {
        const tanggal = neracaTanggalInput.value;
        neracaContent.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';
        
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
            neracaContent.innerHTML = `<div class="alert alert-danger">Gagal memuat laporan: ${error.message}</div>`;
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
            const change = ((currentVal - prevVal) / Math.abs(prevVal)) * 100;
            const color = change >= 0 ? 'text-success' : 'text-danger';
            const icon = change >= 0 ? '<i class="bi bi-arrow-up"></i>' : '<i class="bi bi-arrow-down"></i>';
            return `<span class="${color}">${icon} ${Math.abs(change).toFixed(1)}%</span>`;
        };

        const renderRows = (tipe) => {
            let html = '';
            const colCount = 2 + (isComparison ? 1 : 0) + (isCommonSize ? (isComparison ? 2 : 1) : 0);
            const accountsOfType = Array.from(allAccounts.values()).filter(acc => acc.tipe_akun === tipe);
            if (accountsOfType.length === 0) return `<tr><td colspan="${colCount}" class="text-muted">Tidak ada data.</td></tr>`;

            accountsOfType.forEach(acc => {
                const currentData = findAccountTotal(current, acc.id);
                html += `<tr><td>${acc.nama_akun}</td><td class="text-end">${formatCurrencyAccounting(currentData.total)}</td>`;
                if (isCommonSize) {
                    html += `<td class="text-end text-muted small">${currentData.percentage.toFixed(2)}%</td>`;
                }
                if (isComparison) {
                    const prevData = findAccountTotal(previous, acc.id);
                    html += `<td class="text-end">${formatCurrencyAccounting(prevData.total)}</td>`;
                    if (isCommonSize) html += `<td class="text-end text-muted small">${prevData.percentage.toFixed(2)}%</td>`;
                    html += `<td class="text-end small">${calculateChange(currentData.total, prevData.total)}</td>`;
                }
                html += `</tr>`;
            });
            return html;
        };

        const labaRugiHtml = `
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Keterangan</th>
                        <th class="text-end">Periode Saat Ini</th>
                        ${isCommonSize ? '<th class="text-end">%</th>' : ''}
                        ${isComparison ? '<th class="text-end">Periode Pembanding</th>' : ''}
                        ${isComparison && isCommonSize ? '<th class="text-end">%</th>' : ''}
                        ${isComparison ? '<th class="text-end">Perubahan</th>' : ''}
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-light"><td colspan="${2 + (isComparison ? 1 : 0) + (isCommonSize ? (isComparison ? 2 : 1) : 0)}" class="fw-bold">Pendapatan</td></tr>
                    ${renderRows('Pendapatan')}
                    <tr class="table-light">
                        <td class="fw-bold">TOTAL PENDAPATAN</td>
                        <td class="text-end fw-bold">${formatCurrencyAccounting(current.summary.total_pendapatan)}</td>
                        ${isCommonSize ? '<td class="text-end fw-bold text-muted small">100.00%</td>' : ''}
                        ${isComparison ? `<td class="text-end fw-bold">${formatCurrencyAccounting(previous.summary.total_pendapatan)}</td>` : ''}
                        ${isComparison && isCommonSize ? '<td class="text-end fw-bold text-muted small">100.00%</td>' : ''}
                        ${isComparison ? `<td class="text-end small">${calculateChange(current.summary.total_pendapatan, previous.summary.total_pendapatan)}</td>` : ''}
                    </tr>
                    
                    <tr class="table-light"><td colspan="${2 + (isComparison ? 1 : 0) + (isCommonSize ? (isComparison ? 2 : 1) : 0)}" class="fw-bold pt-4">Beban</td></tr>
                    ${renderRows('Beban')}
                    <tr class="table-light">
                        <td class="fw-bold">TOTAL BEBAN</td>
                        <td class="text-end fw-bold">${formatCurrencyAccounting(current.summary.total_beban)}</td>
                        ${isCommonSize ? `<td class="text-end fw-bold text-muted small">${(current.summary.total_beban_percentage || 0).toFixed(2)}%</td>` : ''}
                        ${isComparison ? `<td class="text-end fw-bold">${formatCurrencyAccounting(previous.summary.total_beban)}</td>` : ''}
                        ${isComparison && isCommonSize ? `<td class="text-end fw-bold text-muted small">${(previous.summary.total_beban_percentage || 0).toFixed(2)}%</td>` : ''}
                        ${isComparison ? `<td class="text-end small">${calculateChange(current.summary.total_beban, previous.summary.total_beban)}</td>` : ''}
                    </tr>
                </tbody>
                <tfoot class="table-group-divider">
                    <tr class="${current.summary.laba_bersih >= 0 ? 'table-success' : 'table-danger'}">
                        <td class="fw-bold fs-5">LABA (RUGI) BERSIH</td>
                        <td class="text-end fw-bold fs-5">${formatCurrencyAccounting(current.summary.laba_bersih)}</td>
                        ${isCommonSize ? `<td class="text-end fw-bold fs-5 text-muted small">${(current.summary.laba_bersih_percentage || 0).toFixed(2)}%</td>` : ''}
                        ${isComparison ? `<td class="text-end fw-bold fs-5">${formatCurrencyAccounting(previous.summary.laba_bersih)}</td>` : ''}
                        ${isComparison && isCommonSize ? `<td class="text-end fw-bold fs-5 text-muted small">${(previous.summary.laba_bersih_percentage || 0).toFixed(2)}%</td>` : ''}
                        ${isComparison ? `<td class="text-end small">${calculateChange(current.summary.laba_bersih, previous.summary.laba_bersih)}</td>` : ''}
                    </tr>
                </tfoot>
            </table>
        `;
        labaRugiContent.innerHTML = labaRugiHtml;
    }

    async function loadLabaRugi() {
        const params = new URLSearchParams({
            start: labaRugiTglMulai.value,
            end: labaRugiTglAkhir.value
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
                start2 = labaRugiTglMulai2.value;
                end2 = labaRugiTglAkhir2.value;
            } else {
                const mainStartDate = new Date(labaRugiTglMulai.value);
                const mainEndDate = new Date(labaRugiTglAkhir.value);

                if (compareMode === 'previous_period') {
                    const duration = mainEndDate.getTime() - mainStartDate.getTime();
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

        labaRugiContent.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';
        try {
            const response = await fetch(`${basePath}/api/laporan_laba_rugi_handler.php?${params.toString()}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);
            renderLabaRugi(result.data);
        } catch (error) {
            labaRugiContent.innerHTML = `<div class="alert alert-danger">Gagal memuat laporan: ${error.message}</div>`;
        }
    }

    function renderArusKas(data) {
        arusKasContent.innerHTML = '';
        const { arus_kas_operasi, arus_kas_investasi, arus_kas_pendanaan, kenaikan_penurunan_kas, saldo_kas_awal, saldo_kas_akhir_terhitung } = data;

        const renderSection = (title, amount) => `
            <tr>
                <td>${title}</td>
                <td class="text-end">${formatCurrencyAccounting(amount)}</td>
            </tr>
        `;
        
        const createTooltipContent = (details) => {
            // 'details' adalah objek, bukan array. Kita cek dengan Object.keys.
            if (!details || Object.keys(details).length === 0) return 'Tidak ada rincian.';
            let content = '<ul class="list-unstyled mb-0">';
            // Gunakan Object.entries untuk iterasi pada objek
            for (const [akun, jumlah] of Object.entries(details)) {
                content += `<li class="d-flex justify-content-between"><span>${akun}</span> <span class="fw-bold">${formatCurrencyAccounting(jumlah)}</span></li>`;
            }
            content += '</ul>';
            return content;
        };

        const arusKasHtml = `
            <table class="table table-sm">
                <tbody>
                    <tr class="table-light"><td colspan="2" class="fw-bold">Arus Kas dari Aktivitas Operasi
                        <i class="bi bi-info-circle-fill ms-2 text-primary" data-bs-toggle="tooltip" data-bs-html="true" data-details='${JSON.stringify(arus_kas_operasi.details)}'></i>
                    </td></tr>
                    ${renderSection('Total Arus Kas Operasi', arus_kas_operasi.total)}
                    
                    <tr class="table-light"><td colspan="2" class="fw-bold mt-3">Arus Kas dari Aktivitas Investasi
                        <i class="bi bi-info-circle-fill ms-2 text-primary" data-bs-toggle="tooltip" data-bs-html="true" data-details='${JSON.stringify(arus_kas_investasi.details)}'></i>
                    </td></tr>
                    ${renderSection('Total Arus Kas Investasi', arus_kas_investasi.total)}

                    <tr class="table-light"><td colspan="2" class="fw-bold mt-3">Arus Kas dari Aktivitas Pendanaan
                        <i class="bi bi-info-circle-fill ms-2 text-primary" data-bs-toggle="tooltip" data-bs-html="true" data-details='${JSON.stringify(arus_kas_pendanaan.details)}'></i>
                    </td></tr>
                    ${renderSection('Total Arus Kas Pendanaan', arus_kas_pendanaan.total)}
                </tbody>
                <tfoot class="table-group-divider">
                    <tr class="fw-bold">
                        <td>Kenaikan (Penurunan) Bersih Kas</td>
                        <td class="text-end">${formatCurrencyAccounting(kenaikan_penurunan_kas)}</td>
                    </tr>
                    <tr>
                        <td>Saldo Kas pada Awal Periode</td>
                        <td class="text-end">${formatCurrencyAccounting(saldo_kas_awal)}</td>
                    </tr>
                    <tr class="fw-bold table-success">
                        <td>Saldo Kas pada Akhir Periode</td>
                        <td class="text-end">${formatCurrencyAccounting(saldo_kas_akhir_terhitung)}</td>
                    </tr>
                </tbody>
            </table>
        `;
        arusKasContent.innerHTML = arusKasHtml;

        // Initialize tooltips
        const tooltipTriggerList = arusKasContent.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(tooltipTriggerEl => {
            const tooltip = new bootstrap.Tooltip(tooltipTriggerEl, {
                title: 'Memuat rincian...' // Placeholder title
            });
            tooltipTriggerEl.addEventListener('show.bs.tooltip', function () {
                const details = JSON.parse(this.dataset.details || '{}');
                tooltip.setContent({ '.tooltip-inner': createTooltipContent(details) });
            });
        });
    }

    async function loadArusKas() {
        const startDate = arusKasTglMulai.value;
        const endDate = arusKasTglAkhir.value;
        arusKasContent.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';

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
            arusKasContent.innerHTML = `<div class="alert alert-danger">Gagal memuat laporan: ${error.message}</div>`;
        }
    }

    // Fungsi untuk memanggil load dan save
    const handleNeracaChange = () => { saveFilters(); loadNeraca(); };
    const handleLabaRugiChange = () => { saveFilters(); loadLabaRugi(); };
    const handleArusKasChange = () => { saveFilters(); loadArusKas(); };

    neracaTanggalInput.addEventListener('change', handleNeracaChange);
    neracaIncludeClosing.addEventListener('change', handleNeracaChange);
    labaRugiTab?.addEventListener('shown.bs.tab', loadLabaRugi);
    labaRugiTglMulai.addEventListener('change', handleLabaRugiChange);
    labaRugiTglAkhir.addEventListener('change', handleLabaRugiChange);
    labaRugiTglMulai2.addEventListener('change', handleLabaRugiChange);
    labaRugiTglAkhir2.addEventListener('change', handleLabaRugiChange);    
    lrCommonSizeSwitch.addEventListener('change', handleLabaRugiChange);
    lrCompareModeSelect.addEventListener('change', () => {        
        lrPeriod2Container.classList.toggle('d-none', lrCompareModeSelect.value !== 'custom');
        handleLabaRugiChange();
    });
    lrIncludeClosing.addEventListener('change', handleLabaRugiChange);
    arusKasTab?.addEventListener('shown.bs.tab', loadArusKas);
    arusKasTglMulai.addEventListener('change', handleArusKasChange);
    arusKasTglAkhir.addEventListener('change', handleArusKasChange);
    akIncludeClosing.addEventListener('change', handleArusKasChange);

    // --- Event Listeners untuk Export ---

    // Event listener untuk tombol PDF (sekarang menggunakan FPDF handler)
    exportNeracaPdfBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';
        const params = { report: 'neraca', tanggal: neracaTanggalInput.value };
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
        const params = { report: 'laba-rugi', start: labaRugiTglMulai.value, end: labaRugiTglAkhir.value, compare_mode: lrCompareModeSelect.value };
        if (lrCompareModeSelect.value !== 'none') {
            params.compare = 'true';
            params.start2 = labaRugiTglMulai2.value;
            params.end2 = labaRugiTglAkhir2.value;
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
        const params = { report: 'arus-kas', start: arusKasTglMulai.value, end: arusKasTglAkhir.value };
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
            window.open(`${basePath}/api/csv?report=neraca&format=csv&tanggal=${neracaTanggalInput.value}`, '_blank');
    });
    exportLrCsvBtn?.addEventListener('click', (e) => {
        e.preventDefault();
            const params = new URLSearchParams({ report: 'laba-rugi', format: 'csv', start: labaRugiTglMulai.value, end: labaRugiTglAkhir.value });
            if (lrCompareModeSelect.value !== 'none') {
                params.append('compare', 'true');
                params.append('start2', labaRugiTglMulai2.value);
                params.append('end2', labaRugiTglAkhir2.value);
            }
            window.open(`${basePath}/api/csv?${params.toString()}`, '_blank');
    });
    exportAkCsvBtn?.addEventListener('click', (e) => {
        e.preventDefault();
            window.open(`${basePath}/api/csv?report=arus-kas&format=csv&start=${arusKasTglMulai.value}&end=${arusKasTglAkhir.value}`, '_blank');
    });

    // Initial Load
    loadAndSetFilters();
    loadNeraca();
}