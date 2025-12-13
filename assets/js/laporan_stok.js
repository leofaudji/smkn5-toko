function initLaporanStokPage() {
    const form = document.getElementById('report-stok-form');
    const reportContent = document.getElementById('report-stok-content');
    const reportSummary = document.getElementById('report-stok-summary');
    const reportHeader = document.getElementById('report-stok-header');
    const startDateInput = document.getElementById('stok-tanggal-mulai');
    const endDateInput = document.getElementById('stok-tanggal-akhir');

    if (!form) return;

    // Set tanggal default ke bulan ini
    const now = new Date();
    startDateInput.value = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
    endDateInput.value = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        loadReport();
    });

    async function loadReport() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        if (!startDate || !endDate) {
            showToast('Harap pilih rentang tanggal.', 'error');
            return;
        }

        reportContent.innerHTML = `<div class="text-center p-5"><div class="spinner-border"></div></div>`;
        reportSummary.innerHTML = '';
        reportHeader.textContent = `Laporan Stok Periode ${formatDate(startDate)} s/d ${formatDate(endDate)}`;

        try {
            const params = new URLSearchParams({ start_date: startDate, end_date: endDate });
            const response = await fetch(`${basePath}/api/laporan_stok?${params.toString()}`);
            const result = await response.json();

            if (result.status !== 'success') {
                throw new Error(result.message);
            }

            renderReportTable(result.data);
            renderReportSummary(result.summary);

        } catch (error) {
            reportContent.innerHTML = `<div class="alert alert-danger">Gagal memuat laporan: ${error.message}</div>`;
        }
    }

    function renderReportTable(data) {
        let tableHtml = `
            <table class="table table-sm table-bordered table-hover table-sticky-header">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%;">No.</th>
                        <th>SKU</th>
                        <th>Nama Barang</th>
                        <th class="text-end">Stok Awal</th>
                        <th class="text-end">Masuk</th>
                        <th class="text-end">Keluar</th>
                        <th class="text-end">Stok Akhir</th>
                        <th class="text-end">Harga Beli</th>
                        <th class="text-end">Nilai Persediaan</th>
                    </tr>
                </thead>
                <tbody>
        `;

        if (data.length > 0) {
            data.forEach((item, index) => {
                tableHtml += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.sku || '-'}</td>
                        <td>${item.nama_barang}</td>
                        <td class="text-end">${item.stok_awal}</td>
                        <td class="text-end text-success">${item.masuk > 0 ? `+${item.masuk}` : '0'}</td>
                        <td class="text-end text-danger">${item.keluar > 0 ? `-${item.keluar}` : '0'}</td>
                        <td class="text-end fw-bold">${item.stok_akhir}</td>
                        <td class="text-end">${formatCurrencyAccounting(item.harga_beli)}</td>
                        <td class="text-end fw-bold">${formatCurrencyAccounting(item.nilai_persediaan)}</td>
                    </tr>
                `;
            });
        } else {
            tableHtml += `<tr><td colspan="9" class="text-center text-muted">Tidak ada data untuk periode ini.</td></tr>`;
        }

        tableHtml += `</tbody></table>`;
        reportContent.innerHTML = tableHtml;
    }

    function renderReportSummary(summary) {
        reportSummary.innerHTML = `
            <div class="row justify-content-end">
                <div class="col-md-4">
                    <dl class="row">
                        <dt class="col-sm-7">Total Nilai Persediaan Akhir</dt>
                        <dd class="col-sm-5 text-end fw-bold fs-5">${formatCurrencyAccounting(summary.total_nilai_persediaan)}</dd>
                    </dl>
                </div>
            </div>
        `;
    }
}