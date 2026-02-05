function initLaporanNominatifPage() {
    const form = document.getElementById('filter-form');
    const jenisLaporanSelect = document.getElementById('jenis_laporan');
    const perTanggalInput = document.getElementById('per_tanggal');
    const reportResult = document.getElementById('report-result');
    const tableHead = document.getElementById('report-table-head');
    const tableBody = document.getElementById('report-table-body');
    const tableFoot = document.getElementById('report-table-foot');
    const btnPrint = document.getElementById('btn-print-pdf');

    const formatRupiah = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        loadReport();
    });

    btnPrint.addEventListener('click', function() {
        const jenis = jenisLaporanSelect.value;
        const tanggal = perTanggalInput.value;
        const reportType = jenis === 'simpanan' ? 'nominatif_simpanan' : 'nominatif_pinjaman';

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${basePath}/api/pdf`;
        form.target = '_blank';

        const inputReport = document.createElement('input');
        inputReport.type = 'hidden';
        inputReport.name = 'report';
        inputReport.value = reportType;
        form.appendChild(inputReport);

        const inputTanggal = document.createElement('input');
        inputTanggal.type = 'hidden';
        inputTanggal.name = 'per_tanggal';
        inputTanggal.value = tanggal;
        form.appendChild(inputTanggal);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    function loadReport() {
        const jenis = jenisLaporanSelect.value;
        const tanggal = perTanggalInput.value;
        const action = jenis === 'simpanan' ? 'get_nominatif_simpanan' : 'get_nominatif_pinjaman';

        reportResult.classList.remove('hidden');
        tableBody.innerHTML = '<tr><td colspan="10" class="text-center py-4">Memuat data...</td></tr>';
        tableHead.innerHTML = '';
        tableFoot.innerHTML = '';

        fetch(`${basePath}/api/ksp/laporan_nominatif_handler.php?action=${action}&per_tanggal=${tanggal}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    if (jenis === 'simpanan') {
                        renderSimpananTable(res.data);
                    } else {
                        renderPinjamanTable(res.data);
                    }
                } else {
                    tableBody.innerHTML = `<tr><td colspan="10" class="text-center py-4 text-red-500">${res.message}</td></tr>`;
                }
            })
            .catch(err => {
                tableBody.innerHTML = `<tr><td colspan="10" class="text-center py-4 text-red-500">Terjadi kesalahan jaringan.</td></tr>`;
            });
    }

    function renderSimpananTable(data) {
        tableHead.innerHTML = `
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. Anggota</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Anggota</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Saldo Simpanan</th>
            </tr>
        `;

        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-gray-500">Tidak ada data.</td></tr>';
            return;
        }

        let total = 0;
        tableBody.innerHTML = data.map((row, index) => {
            total += parseFloat(row.saldo);
            return `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${index + 1}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${row.nomor_anggota}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${row.nama_lengkap}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white">${formatRupiah(row.saldo)}</td>
                </tr>
            `;
        }).join('');

        tableFoot.innerHTML = `
            <tr>
                <td colspan="3" class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">GRAND TOTAL</td>
                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">${formatRupiah(total)}</td>
            </tr>
        `;
    }

    function renderPinjamanTable(data) {
        tableHead.innerHTML = `
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. Anggota</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Anggota</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. Pinjaman</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tgl Cair</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Plafon</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bakidebet</th>
            </tr>
        `;

        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-gray-500">Tidak ada data.</td></tr>';
            return;
        }

        let totalPlafon = 0;
        let totalBakidebet = 0;

        tableBody.innerHTML = data.map((row, index) => {
            totalPlafon += parseFloat(row.plafon);
            totalBakidebet += parseFloat(row.sisa_pokok);
            return `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${index + 1}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${row.nomor_anggota}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${row.nama_lengkap}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${row.nomor_pinjaman}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">${new Date(row.tanggal_pencairan).toLocaleDateString('id-ID')}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500 dark:text-gray-400">${formatRupiah(row.plafon)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white">${formatRupiah(row.sisa_pokok)}</td>
                </tr>
            `;
        }).join('');

        tableFoot.innerHTML = `
            <tr>
                <td colspan="5" class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">GRAND TOTAL</td>
                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">${formatRupiah(totalPlafon)}</td>
                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">${formatRupiah(totalBakidebet)}</td>
            </tr>
        `;
    }
}
