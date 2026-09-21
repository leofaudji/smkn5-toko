function initLaporanPenjualanItemPage() {
    const form = document.getElementById('report-penjualan-item-form');
    const startDateInput = document.getElementById('penjualan-item-tanggal-mulai');
    const endDateInput = document.getElementById('penjualan-item-tanggal-akhir');
    const typeSelect = document.getElementById('penjualan-item-type');
    const movementSelect = document.getElementById('penjualan-item-movement');
    const searchInput = document.getElementById('penjualan-item-search');
    const sortSelect = document.getElementById('penjualan-item-sort');
    const limitSelect = document.getElementById('penjualan-item-limit');
    
    const summaryContainer = document.getElementById('report-penjualan-item-summary');
    const tableBody = document.getElementById('report-penjualan-item-content');
    const paginationContainer = document.getElementById('penjualan-item-report-pagination');
    const paginationInfo = document.getElementById('penjualan-item-pagination-info');
    
    const exportPdfBtn = document.getElementById('export-penjualan-item-pdf');
    const exportCsvBtn = document.getElementById('export-penjualan-item-csv');
    const printBtn = document.getElementById('print-penjualan-item-btn');

    const btnBulanIni = document.getElementById('btn-periode-bulan-ini');
    const btnBulanLalu = document.getElementById('btn-periode-bulan-lalu');
    const btnTahunIni = document.getElementById('btn-periode-tahun-ini');

    if (!form) return;

    // Date picker setup
    const commonOptions = { dateFormat: "d-m-Y", allowInput: true };
    const startDatePicker = flatpickr(startDateInput, commonOptions);
    const endDatePicker = flatpickr(endDateInput, commonOptions);

    // Default dates to current month
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    startDatePicker.setDate(firstDayOfMonth, true);
    endDatePicker.setDate(today, true);

    let currentSortBy = sortSelect ? sortSelect.value : 'total_margin';
    let currentSortOrder = 'desc';
    let currentPage = 1;

    // Formatters
    const formatRupiah = (angka) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka || 0);
    };

    const formatNumber = (angka) => {
        return new Intl.NumberFormat('id-ID').format(angka || 0);
    };

    // Quick period buttons
    if (btnBulanIni) {
        btnBulanIni.addEventListener('click', () => {
            const now = new Date();
            startDatePicker.setDate(new Date(now.getFullYear(), now.getMonth(), 1), true);
            endDatePicker.setDate(now, true);
            loadReport(1);
        });
    }

    if (btnBulanLalu) {
        btnBulanLalu.addEventListener('click', () => {
            const now = new Date();
            const firstDayLastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const lastDayLastMonth = new Date(now.getFullYear(), now.getMonth(), 0);
            startDatePicker.setDate(firstDayLastMonth, true);
            endDatePicker.setDate(lastDayLastMonth, true);
            loadReport(1);
        });
    }

    if (btnTahunIni) {
        btnTahunIni.addEventListener('click', () => {
            const now = new Date();
            startDatePicker.setDate(new Date(now.getFullYear(), 0, 1), true);
            endDatePicker.setDate(now, true);
            loadReport(1);
        });
    }

    async function loadReport(page = 1) {
        currentPage = page;
        const startDate = startDateInput.value.split('-').reverse().join('-');
        const endDate = endDateInput.value.split('-').reverse().join('-');

        if (!startDate || !endDate) {
            showToast('Harap pilih rentang tanggal.', 'error');
            return;
        }

        tableBody.innerHTML = `
            <div class="flex flex-col items-center justify-center p-12 text-gray-500 dark:text-gray-400">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary mb-3"></div>
                <p class="text-sm">Menghitung mutasi stok & rekonsiliasi margin...</p>
            </div>
        `;

        try {
            const params = new URLSearchParams({
                page: currentPage,
                limit: limitSelect ? limitSelect.value : 15,
                start_date: startDate,
                end_date: endDate,
                item_type: typeSelect ? typeSelect.value : 'all',
                movement_filter: movementSelect ? movementSelect.value : 'with_activity',
                search: searchInput ? searchInput.value : '',
                sort_by: currentSortBy,
                sort_order: currentSortOrder
            });

            const response = await fetch(`${basePath}/api/laporan-penjualan-item?${params.toString()}`);
            const result = await response.json();

            if (result.status !== 'success') {
                throw new Error(result.message || 'Gagal memuat data laporan.');
            }

            renderSummaryCards(result.summary);
            renderTable(result.data, result.pagination, result.summary);
            renderTailwindPagination(paginationContainer, result.pagination, loadReport);

            if (paginationInfo && result.pagination) {
                const p = result.pagination;
                if (p.total_records === 0) {
                    paginationInfo.textContent = 'Tidak ada data.';
                } else {
                    const start = (p.page - 1) * p.limit + 1;
                    const end = p.limit > 0 ? Math.min(p.page * p.limit, p.total_records) : p.total_records;
                    paginationInfo.textContent = `Menampilkan ${start} sampai ${end} dari total ${p.total_records} item terdaftar`;
                }
            }

        } catch (error) {
            tableBody.innerHTML = `
                <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-5 py-4 rounded-xl text-center">
                    <i class="bi bi-exclamation-triangle-fill text-xl mr-2"></i> Gagal memuat laporan: ${error.message}
                </div>
            `;
            if (summaryContainer) summaryContainer.innerHTML = '';
        }
    }

    function renderSummaryCards(summary) {
        if (!summaryContainer || !summary) return;

        const profitClass = (val) => val >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
        const profitBgClass = (val) => val >= 0 ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300';

        summaryContainer.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <!-- Card 1: Penjualan Bersih -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Penjualan Bersih (Neto)</p>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mt-1.5">${formatRupiah(summary.total_penjualan)}</h3>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl">
                            <i class="bi bi-cash-stack text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-[11px] text-gray-500 dark:text-gray-400 flex items-center justify-between">
                        <span>Bruto: ${formatRupiah(summary.total_penjualan_bruto)}</span>
                        ${(summary.total_penjualan_bruto - summary.total_penjualan) > 0 ? `<span class="text-red-500 font-medium">Disc: -${formatRupiah(summary.total_penjualan_bruto - summary.total_penjualan)}</span>` : ''}
                    </div>
                </div>

                <!-- Card 2: Total HPP -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total HPP (Modal)</p>
                            <h3 class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-1.5">${formatRupiah(summary.total_hpp)}</h3>
                        </div>
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-xl">
                            <i class="bi bi-tag-fill text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
                        <span>Beban Pokok Penjualan tercatat</span>
                    </div>
                </div>

                <!-- Card 3: Total Margin Keuntungan -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Margin (Laba Kotor)</p>
                            <h3 class="text-xl font-bold ${profitClass(summary.total_profit)} mt-1.5">${formatRupiah(summary.total_profit)}</h3>
                        </div>
                        <div class="p-3 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-xl">
                            <i class="bi bi-graph-up-arrow text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-[11px] flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-full font-bold ${profitBgClass(summary.total_profit)}">
                            ${parseFloat(summary.total_margin_pct || 0).toFixed(2)}% Margin
                        </span>
                        <span class="text-gray-400 text-[10px]">(Sama dg Lap. Penjualan)</span>
                    </div>
                </div>

                <!-- Card 4: Volume Penjualan & Mutasi -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Volume Terjual & Mutasi</p>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mt-1.5">${formatNumber(summary.total_terjual)} <span class="text-xs font-normal text-gray-500">Pcs Terjual</span></h3>
                        </div>
                        <div class="p-3 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-xl">
                            <i class="bi bi-box-arrow-right text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-[11px] text-gray-500 dark:text-gray-400 flex items-center gap-3">
                        <span class="text-green-600 font-semibold"><i class="bi bi-arrow-down-short"></i> Masuk: +${formatNumber(summary.total_masuk)}</span>
                        <span class="text-red-500 font-semibold"><i class="bi bi-arrow-up-short"></i> Keluar: -${formatNumber(summary.total_keluar)}</span>
                    </div>
                </div>
            </div>

            <!-- Breakdown Detail: Toko vs Konsinyasi -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-blue-50/40 dark:bg-blue-900/10 rounded-xl p-3.5 border border-blue-100 dark:border-blue-900/40 flex justify-between items-center text-xs">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300 font-bold"><i class="bi bi-shop"></i></span>
                        <div>
                            <span class="font-bold text-gray-800 dark:text-white">Barang Toko</span>
                            <span class="text-gray-500 ml-2">${formatNumber(summary.shop.qty)} pcs</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-gray-600 dark:text-gray-300">Penjualan: <b>${formatRupiah(summary.shop.sales)}</b></span>
                        <span class="mx-2 text-gray-300 dark:text-gray-600">|</span>
                        <span class="font-bold text-green-600 dark:text-green-400">Profit: ${formatRupiah(summary.shop.profit)} (${parseFloat(summary.shop.margin_pct || 0).toFixed(1)}%)</span>
                    </div>
                </div>

                <div class="bg-purple-50/40 dark:bg-purple-900/10 rounded-xl p-3.5 border border-purple-100 dark:border-purple-900/40 flex justify-between items-center text-xs">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-purple-100 dark:bg-purple-800 text-purple-700 dark:text-purple-300 font-bold"><i class="bi bi-box-seam"></i></span>
                        <div>
                            <span class="font-bold text-gray-800 dark:text-white">Barang Konsinyasi</span>
                            <span class="text-gray-500 ml-2">${formatNumber(summary.consignment.qty)} pcs</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-gray-600 dark:text-gray-300">Penjualan: <b>${formatRupiah(summary.consignment.sales)}</b></span>
                        <span class="mx-2 text-gray-300 dark:text-gray-600">|</span>
                        <span class="font-bold text-green-600 dark:text-green-400">Profit: ${formatRupiah(summary.consignment.profit)} (${parseFloat(summary.consignment.margin_pct || 0).toFixed(1)}%)</span>
                    </div>
                </div>
            </div>
        `;
    }

    function renderTable(data, pagination, summary) {
        if (!data || data.length === 0) {
            tableBody.innerHTML = `
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200 p-8 rounded-xl text-center">
                    <i class="bi bi-inbox text-3xl mb-2 block"></i>
                    <p class="font-medium text-sm">Tidak ada pergerakan stok atau data penjualan untuk filter dan periode ini.</p>
                </div>
            `;
            return;
        }

        const currentPage = pagination ? pagination.page : 1;
        const pageLimit = pagination ? pagination.limit : 15;
        const startIndex = pageLimit > 0 ? (currentPage - 1) * pageLimit : 0;

        let tableHtml = `
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-100 dark:border-gray-700 rounded-lg text-xs">
                <thead class="bg-gray-50 dark:bg-gray-700/80 sticky top-0 z-10 select-none">
                    <tr>
                        <th class="px-3 py-3 text-center font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider w-10">No</th>
                        <th class="px-3 py-3 text-left font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-200/50" data-sort="sku">SKU</th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-200/50 min-w-[200px]" data-sort="nama_barang">Nama Barang</th>
                        <th class="px-3 py-3 text-center font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Tipe</th>
                        <th class="px-3 py-3 text-right font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider bg-gray-100/60 dark:bg-gray-800/60 cursor-pointer hover:bg-gray-200/50" data-sort="stok_awal" title="Stok awal sebelum periode">Awal</th>
                        <th class="px-3 py-3 text-right font-bold text-green-700 dark:text-green-300 uppercase tracking-wider bg-green-50/50 dark:bg-green-900/20 cursor-pointer hover:bg-green-100/50" data-sort="masuk" title="Barang masuk dalam periode">+ Masuk</th>
                        <th class="px-3 py-3 text-right font-bold text-red-700 dark:text-red-300 uppercase tracking-wider bg-red-50/50 dark:bg-red-900/20 cursor-pointer hover:bg-red-100/50" data-sort="keluar" title="Barang keluar dalam periode">- Keluar</th>
                        <th class="px-3 py-3 text-right font-bold text-gray-900 dark:text-white uppercase tracking-wider bg-gray-100/80 dark:bg-gray-800 cursor-pointer hover:bg-gray-200/50" data-sort="stok_akhir" title="Stok akhir periode">Sisa</th>
                        <th class="px-3 py-3 text-right font-bold text-purple-700 dark:text-purple-300 uppercase tracking-wider cursor-pointer hover:bg-gray-200/50" data-sort="total_terjual" title="Total qty terjual via faktur penjualan">Terjual</th>
                        <th class="px-3 py-3 text-right font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-200/50" data-sort="total_penjualan">Penjualan (Neto)</th>
                        <th class="px-3 py-3 text-right font-bold text-amber-700 dark:text-amber-300 uppercase tracking-wider cursor-pointer hover:bg-gray-200/50" data-sort="total_hpp">Total HPP</th>
                        <th class="px-3 py-3 text-right font-bold text-primary uppercase tracking-wider cursor-pointer hover:bg-gray-200/50" data-sort="total_margin">Total Margin</th>
                        <th class="px-3 py-3 text-right font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-200/50" data-sort="margin_pct">Margin %</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
        `;

        data.forEach((item, idx) => {
            const profitVal = parseFloat(item.total_margin || 0);
            const profitColor = profitVal >= 0 ? 'text-green-600 dark:text-green-400 font-bold' : 'text-red-600 dark:text-red-400 font-bold';
            const marginPct = parseFloat(item.margin_pct || 0);
            const typeBadge = item.item_type === 'consignment' 
                ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">Konsinyasi</span>'
                : '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">Toko</span>';

            tableHtml += `
                <tr class="hover:bg-blue-50/40 dark:hover:bg-gray-700/60 transition">
                    <td class="px-3 py-2.5 text-center text-gray-400 dark:text-gray-500 whitespace-nowrap">${startIndex + idx + 1}</td>
                    <td class="px-3 py-2.5 font-mono text-gray-600 dark:text-gray-400 whitespace-nowrap">${item.sku || '-'}</td>
                    <td class="px-4 py-2.5 whitespace-normal">
                        <div class="font-semibold text-gray-900 dark:text-white">${item.nama_barang}</div>
                        <div class="text-[10px] text-gray-400">${item.kategori || ''}</div>
                    </td>
                    <td class="px-3 py-2.5 text-center whitespace-nowrap">${typeBadge}</td>
                    <td class="px-3 py-2.5 text-right font-medium text-gray-700 dark:text-gray-300 bg-gray-50/50 dark:bg-gray-800/40 whitespace-nowrap">${formatNumber(item.stok_awal)}</td>
                    <td class="px-3 py-2.5 text-right font-semibold text-green-600 dark:text-green-400 bg-green-50/30 dark:bg-green-900/10 whitespace-nowrap">${item.masuk > 0 ? `+${formatNumber(item.masuk)}` : '0'}</td>
                    <td class="px-3 py-2.5 text-right font-semibold text-red-500 dark:text-red-400 bg-red-50/30 dark:bg-red-900/10 whitespace-nowrap">${item.keluar > 0 ? `-${formatNumber(item.keluar)}` : '0'}</td>
                    <td class="px-3 py-2.5 text-right font-bold text-gray-900 dark:text-white bg-gray-100/50 dark:bg-gray-800/60 whitespace-nowrap">${formatNumber(item.stok_akhir)}</td>
                    <td class="px-3 py-2.5 text-right font-bold text-purple-700 dark:text-purple-300 whitespace-nowrap">${formatNumber(item.qty_terjual)}</td>
                    <td class="px-3 py-2.5 text-right font-semibold text-gray-900 dark:text-white whitespace-nowrap">${formatRupiah(item.total_penjualan)}</td>
                    <td class="px-3 py-2.5 text-right text-gray-600 dark:text-gray-300 whitespace-nowrap">${formatRupiah(item.total_hpp)}</td>
                    <td class="px-3 py-2.5 text-right whitespace-nowrap ${profitColor}">${formatRupiah(item.total_margin)}</td>
                    <td class="px-3 py-2.5 text-right whitespace-nowrap">
                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold ${marginPct >= 0 ? 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300'}">
                            ${marginPct.toFixed(1)}%
                        </span>
                    </td>
                </tr>
            `;
        });

        // Sticky / Static Table Footer (Grand Totals matching Laporan Penjualan)
        if (summary) {
            tableHtml += `
                </tbody>
                <tfoot class="bg-gray-100 dark:bg-gray-700/90 font-bold text-gray-900 dark:text-white border-t-2 border-gray-300 dark:border-gray-600">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right uppercase tracking-wider text-xs font-extrabold">TOTAL KESELURUHAN (PERIODE):</td>
                        <td class="px-3 py-3 text-right text-xs">-</td>
                        <td class="px-3 py-3 text-right text-xs text-green-600 dark:text-green-400">+${formatNumber(summary.total_masuk)}</td>
                        <td class="px-3 py-3 text-right text-xs text-red-600 dark:text-red-400">-${formatNumber(summary.total_keluar)}</td>
                        <td class="px-3 py-3 text-right text-xs">-</td>
                        <td class="px-3 py-3 text-right text-xs text-purple-700 dark:text-purple-300">${formatNumber(summary.total_terjual)}</td>
                        <td class="px-3 py-3 text-right text-xs text-primary">${formatRupiah(summary.total_penjualan)}</td>
                        <td class="px-3 py-3 text-right text-xs text-amber-700 dark:text-amber-300">${formatRupiah(summary.total_hpp)}</td>
                        <td class="px-3 py-3 text-right text-xs text-green-600 dark:text-green-400">${formatRupiah(summary.total_profit)}</td>
                        <td class="px-3 py-3 text-right text-xs">
                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-extrabold bg-green-200 dark:bg-green-800 text-green-900 dark:text-green-100">
                                ${parseFloat(summary.total_margin_pct || 0).toFixed(2)}%
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
            `;
        } else {
            tableHtml += `</tbody></table>`;
        }

        tableBody.innerHTML = tableHtml;

        // Header click listeners for sorting
        tableBody.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                const sortField = th.dataset.sort;
                if (currentSortBy === sortField) {
                    currentSortOrder = (currentSortOrder === 'desc') ? 'asc' : 'desc';
                } else {
                    currentSortBy = sortField;
                    currentSortOrder = 'desc';
                }
                if (sortSelect) sortSelect.value = currentSortBy;
                loadReport(1);
            });
        });
    }

    // Form submission
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        loadReport(1);
    });

    // Auto-reload on select filter changes
    [typeSelect, movementSelect, limitSelect, sortSelect].forEach(el => {
        if (el) {
            el.addEventListener('change', () => {
                if (el === sortSelect) {
                    currentSortBy = sortSelect.value;
                    currentSortOrder = 'desc';
                }
                loadReport(1);
            });
        }
    });

    // Debounced search
    let searchTimeout = null;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadReport(1);
            }, 400);
        });
    }

    // Export PDF
    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const startDate = startDateInput.value.split('-').reverse().join('-');
            const endDate = endDateInput.value.split('-').reverse().join('-');

            printPdf({
                report: 'laporan-penjualan-item',
                orientation: 'landscape',
                start_date: startDate,
                end_date: endDate,
                item_type: typeSelect ? typeSelect.value : 'all',
                movement_filter: movementSelect ? movementSelect.value : 'with_activity',
                search: searchInput ? searchInput.value : '',
                sort_by: currentSortBy,
                sort_order: currentSortOrder
            });
        });
    }

    // Export CSV / Excel
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const startDate = startDateInput.value.split('-').reverse().join('-');
            const endDate = endDateInput.value.split('-').reverse().join('-');

            const params = new URLSearchParams({
                report: 'laporan-penjualan-item',
                format: 'csv',
                start_date: startDate,
                end_date: endDate,
                item_type: typeSelect ? typeSelect.value : 'all',
                movement_filter: movementSelect ? movementSelect.value : 'with_activity',
                search: searchInput ? searchInput.value : '',
                sort_by: currentSortBy,
                sort_order: currentSortOrder
            });

            window.location.href = `${basePath}/api/laporan_cetak_csv_handler.php?${params.toString()}`;
        });
    }

    // Browser Print
    if (printBtn) {
        printBtn.addEventListener('click', (e) => {
            e.preventDefault();
            window.print();
        });
    }

    function renderTailwindPagination(container, pagination, callback) {
        if (!container) return;
        if (!pagination || pagination.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '<nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">';

        // Previous
        if (pagination.page > 1) {
            html += `<button type="button" data-page="${pagination.page - 1}" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <span class="sr-only">Previous</span>
                        <i class="bi bi-chevron-left"></i>
                     </button>`;
        }

        // Pages
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (i === pagination.page) {
                html += `<button type="button" aria-current="page" class="z-10 bg-primary border-primary text-white relative inline-flex items-center px-4 py-2 border text-sm font-medium">${i}</button>`;
            } else {
                if (i === 1 || i === pagination.total_pages || (i >= pagination.page - 2 && i <= pagination.page + 2)) {
                    html += `<button type="button" data-page="${i}" class="bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">${i}</button>`;
                } else if (i === pagination.page - 3 || i === pagination.page + 3) {
                    html += `<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300">...</span>`;
                }
            }
        }

        // Next
        if (pagination.page < pagination.total_pages) {
            html += `<button type="button" data-page="${pagination.page + 1}" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <span class="sr-only">Next</span>
                        <i class="bi bi-chevron-right"></i>
                     </button>`;
        }

        html += '</nav>';
        container.innerHTML = html;

        container.querySelectorAll('button[data-page]').forEach(btn => {
            btn.addEventListener('click', () => {
                callback(parseInt(btn.dataset.page));
            });
        });
    }

    // Initial load
    loadReport(1);
}

