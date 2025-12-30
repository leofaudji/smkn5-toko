function initNeracaSaldoPage() {
    const reportForm = document.getElementById('report-form');
    const previewContainer = document.getElementById('preview-container');
    const previewTableContainer = document.getElementById('preview-table-container');
    const printPdfBtn = document.getElementById('print-pdf-btn');
    const previewBtn = document.getElementById('preview-btn');

    if (!reportForm) return;

    flatpickr("#tanggal", { dateFormat: "d-m-Y", allowInput: true, defaultDate: "today" });

    reportForm.addEventListener('submit', function(e) {
        e.preventDefault();
        loadPreview();
    });

    async function loadPreview() {
        const tanggal = document.getElementById('tanggal').value.split('-').reverse().join('-');
        if (!tanggal) {
            showToast('Harap pilih tanggal terlebih dahulu.', 'error');
            return;
        }

        previewBtn.disabled = true;
        previewBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memuat...`;
        previewContainer.style.display = 'block';
        previewTableContainer.innerHTML = `<div class="text-center p-5"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>`;

        try {
            const response = await fetch(`${basePath}/api/neraca-saldo?tanggal=${tanggal}`);
            const result = await response.json();

            if (result.status !== 'success') {
                throw new Error(result.message);
            }

            renderPreviewTable(result.data, result.totals);

        } catch (error) {
            previewTableContainer.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">Gagal memuat preview: ${error.message}</div>`;
        } finally {
            previewBtn.disabled = false;
            previewBtn.innerHTML = `<i class="bi bi-search mr-2"></i> Tampilkan Preview`;
        }
    }

    function renderPreviewTable(data, totals) {
        const formatCurrency = (value) => {
            if (value === null || value === undefined) return '';
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
        };

        let tableHtml = `
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kode Akun</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Akun</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Debit</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kredit</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        `;

        if (data.length > 0) {
            data.forEach(row => {
                tableHtml += `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${row.kode_akun}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${row.nama_akun}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">${row.debit > 0 ? formatCurrency(row.debit) : ''}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">${row.kredit > 0 ? formatCurrency(row.kredit) : ''}</td>
                    </tr>
                `;
            });
        } else {
            tableHtml += `<tr><td colspan="4" class="text-center py-10 text-gray-500">Tidak ada data untuk tanggal yang dipilih.</td></tr>`;
        }

        const isBalanced = Math.abs(totals.debit - totals.kredit) < 0.01;
        const totalClass = isBalanced ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';

        tableHtml += `
                </tbody>
                <tfoot class="bg-gray-100 dark:bg-gray-700 font-bold">
                    <tr>
                        <td colspan="2" class="px-6 py-3 text-center text-sm text-gray-900 dark:text-white uppercase">TOTAL</td>
                        <td class="px-6 py-3 text-right text-sm ${totalClass}">${formatCurrency(totals.debit)}</td>
                        <td class="px-6 py-3 text-right text-sm ${totalClass}">${formatCurrency(totals.kredit)}</td>
                    </tr>
                </tfoot>
            </table>
        `;
        previewTableContainer.innerHTML = tableHtml;
    }

    printPdfBtn.addEventListener('click', function() {
        const tanggal = document.getElementById('tanggal').value.split('-').reverse().join('-');
        const reportUrl = `${basePath}/api/pdf?report=trial-balance&tanggal=${tanggal}`;
        window.open(reportUrl, '_blank');
    });
}