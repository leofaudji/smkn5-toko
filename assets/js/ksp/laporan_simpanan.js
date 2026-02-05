function initLaporanSimpananPage() {
    const form = document.getElementById('filter-form');
    const anggotaSelect = document.getElementById('anggota_id');
    const reportResult = document.getElementById('report-result');
    const tableBody = document.getElementById('report-table-body');
    const saldoAwalDisplay = document.getElementById('saldo-awal-display');
    const btnPrint = document.getElementById('btn-print-pdf');

    // Load Anggota List
    fetch(`${basePath}/api/ksp/laporan-simpanan?action=get_anggota`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                res.data.forEach(a => {
                    const option = document.createElement('option');
                    option.value = a.id;
                    option.textContent = `${a.nama_lengkap} (${a.nomor_anggota})`;
                    anggotaSelect.appendChild(option);
                });
            }
        });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        loadReport();
    });

    btnPrint.addEventListener('click', function() {
        const anggotaId = anggotaSelect.value;
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        if (!anggotaId) {
            alert('Pilih anggota terlebih dahulu');
            return;
        }

        // Redirect ke endpoint PDF
        const url = `${basePath}/api/pdf?report_type=simpanan_member&anggota_id=${anggotaId}&start_date=${startDate}&end_date=${endDate}`;
        window.open(url, '_blank');
    });

    function loadReport() {
        const anggotaId = anggotaSelect.value;
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        if (!anggotaId) {
            alert('Pilih anggota terlebih dahulu');
            return;
        }

        reportResult.classList.remove('hidden');
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Memuat data...</td></tr>';

        fetch(`${basePath}/api/ksp/laporan-simpanan?action=get_report&anggota_id=${anggotaId}&start_date=${startDate}&end_date=${endDate}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    renderTable(res.data);
                } else {
                    alert(res.message);
                }
            });
    }

    function renderTable(data) {
        saldoAwalDisplay.textContent = `Saldo Awal: ${formatCurrency(data.saldo_awal)}`;
        
        if (data.transactions.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-gray-500">Tidak ada transaksi pada periode ini.</td></tr>';
            return;
        }

        tableBody.innerHTML = data.transactions.map(item => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${formatDate(item.tanggal)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${item.nomor_referensi}</td>
                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                    <div class="font-medium">${item.jenis_simpanan}</div>
                    <div class="text-xs">${item.keterangan || '-'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600">${item.debit > 0 ? formatCurrency(item.debit) : '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600">${item.kredit > 0 ? formatCurrency(item.kredit) : '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-white">${formatCurrency(item.saldo)}</td>
            </tr>
        `).join('');
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
    }

    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('id-ID');
    }
}