document.addEventListener('DOMContentLoaded', function() {
    const reportForm = document.getElementById('report-form');
    const previewContainer = document.getElementById('preview-container');
    const previewTableContainer = document.getElementById('preview-table-container');
    const printPdfBtn = document.getElementById('print-pdf-btn');
    const previewBtn = document.getElementById('preview-btn');

    if (reportForm) {
        reportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            loadPreview();
        });
    }

    async function loadPreview() {
        const tanggal = document.getElementById('tanggal').value;
        if (!tanggal) {
            showToast('Harap pilih tanggal terlebih dahulu.', 'error');
            return;
        }

        const originalBtnHtml = previewBtn.innerHTML;
        previewBtn.disabled = true;
        previewBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Memuat...`;
        previewContainer.style.display = 'block';
        previewTableContainer.innerHTML = `<div class="text-center p-5"><div class="spinner-border"></div></div>`;

        try {
            const response = await fetch(`${basePath}/api/neraca-saldo?tanggal=${tanggal}`);
            const result = await response.json();

            if (result.status !== 'success') {
                throw new Error(result.message);
            }

            renderPreviewTable(result.data, result.totals);

        } catch (error) {
            previewTableContainer.innerHTML = `<div class="alert alert-danger">Gagal memuat preview: ${error.message}</div>`;
        } finally {
            previewBtn.disabled = false;
            previewBtn.innerHTML = originalBtnHtml;
        }
    }

    function renderPreviewTable(data, totals) {
        let tableHtml = `
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Kode Akun</th>
                        <th>Nama Akun</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.forEach(row => {
            tableHtml += `
                <tr>
                    <td>${row.kode_akun}</td>
                    <td>${row.nama_akun}</td>
                    <td class="text-end">${row.debit > 0 ? formatCurrencyAccounting(row.debit) : ''}</td>
                    <td class="text-end">${row.kredit > 0 ? formatCurrencyAccounting(row.kredit) : ''}</td>
                </tr>
            `;
        });

        tableHtml += `
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2" class="text-center">TOTAL</td>
                        <td class="text-end">${formatCurrencyAccounting(totals.debit)}</td>
                        <td class="text-end">${formatCurrencyAccounting(totals.kredit)}</td>
                    </tr>
                </tfoot>
            </table>
        `;
        previewTableContainer.innerHTML = tableHtml;
    }

    if (printPdfBtn) {
        printPdfBtn.addEventListener('click', function() {
            const tanggal = document.getElementById('tanggal').value;
            const reportUrl = `${basePath}/api/pdf?report=trial-balance&tanggal=${tanggal}`;
            window.open(reportUrl, '_blank');
        });
    }
});