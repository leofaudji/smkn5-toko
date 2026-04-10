async function loadLaporanPiutang() {
    try {
        const response = await fetch(`${basePath}/api/laporan-piutang?action=list`);
        const result = await response.json();
        
        const tbody = document.getElementById('piutang-table-body');
        if (!tbody) return;
        tbody.innerHTML = '';
        
        if (result.success && result.data.length > 0) {
            let totalSisa = 0;
            result.data.forEach(item => {
                totalSisa += parseFloat(item.sisa_hutang);
                const row = `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.customer_name}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.nomor_anggota || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">Rp ${new Intl.NumberFormat('id-ID').format(item.total_kredit)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 dark:text-green-400">Rp ${new Intl.NumberFormat('id-ID').format(item.total_bayar)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-red-600 dark:text-red-400">Rp ${new Intl.NumberFormat('id-ID').format(item.sisa_hutang)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                            <button class="text-blue-600 hover:text-blue-900 btn-detail-piutang" data-id="${item.customer_id}" data-nama="${item.customer_name}">Detail / Bayar</button>
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', row);
            });
            document.getElementById('total-piutang').textContent = `Rp ${new Intl.NumberFormat('id-ID').format(totalSisa)}`;
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4 text-gray-500">Tidak ada data piutang.</td></tr>';
            document.getElementById('total-piutang').textContent = 'Rp 0';
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('piutang-table-body').innerHTML = '<tr><td colspan="6" class="text-center p-4 text-red-500">Gagal memuat data.</td></tr>';
    }
}

async function showDetailPiutang(customerId, customerName) {
    const modalTitle = document.getElementById('piutangDetailModalLabel');
    const detailContainer = document.getElementById('piutang-detail-list');
    const customerIdInput = document.getElementById('bayar-customer-id');
    const bayarJumlahInput = document.getElementById('bayar-jumlah');
    
    modalTitle.textContent = `Detail Piutang - ${customerName}`;
    customerIdInput.value = customerId;
    bayarJumlahInput.value = ''; // Reset input
    
    detailContainer.innerHTML = '<div class="text-center p-4"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div></div>';
    openModal('piutangDetailModal');

    try {
        // Load Akun Kas jika belum ada
        const akunSelect = document.getElementById('bayar-akun');
        if (akunSelect && akunSelect.options.length <= 1) {
            const accResponse = await fetch(`${basePath}/api/settings?action=get_cash_accounts`);
            const accResult = await accResponse.json();
            if (accResult.status === 'success') {
                accResult.data.forEach(acc => {
                    akunSelect.add(new Option(acc.nama_akun, acc.id));
                });
            }
        }

        // Load Detail Piutang
        const response = await fetch(`${basePath}/api/laporan-piutang?action=get_detail&customer_id=${customerId}`);
        const result = await response.json();

        if (result.success) {
            // Tampilkan Saldo WB
            const saldoWb = result.saldo_wb || 0;
            const displayWb = document.getElementById('display-saldo-wb');
            if(displayWb) displayWb.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(saldoWb)}`;
            
            let html = `
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">No. Ref</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Terbayar</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Sisa</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            `;
            let totalSisa = 0;
            result.data.forEach(row => {
                totalSisa += parseFloat(row.sisa);
                html += `
                    <tr class="text-sm">
                        <td class="px-4 py-2">${new Date(row.tanggal_penjualan).toLocaleDateString('id-ID')}</td>
                        <td class="px-4 py-2">${row.nomor_referensi}</td>
                        <td class="px-4 py-2 text-right">Rp ${new Intl.NumberFormat('id-ID').format(row.total)}</td>
                        <td class="px-4 py-2 text-right text-green-600">Rp ${new Intl.NumberFormat('id-ID').format(row.bayar)}</td>
                        <td class="px-4 py-2 text-right font-bold text-red-600">Rp ${new Intl.NumberFormat('id-ID').format(row.sisa)}</td>
                    </tr>
                `;
            });
            html += `</tbody><tfoot class="bg-gray-100 dark:bg-gray-700 font-bold"><tr><td colspan="4" class="px-4 py-2 text-right">Total Sisa Hutang</td><td class="px-4 py-2 text-right text-red-600">Rp ${new Intl.NumberFormat('id-ID').format(totalSisa)}</td></tr></tfoot></table>`;
            
            detailContainer.innerHTML = html;
            if(bayarJumlahInput) bayarJumlahInput.max = totalSisa; // Set max payment
        } else {
            detailContainer.innerHTML = '<p class="text-center text-red-500">Gagal memuat detail.</p>';
        }
    } catch (error) {
        console.error(error);
        detailContainer.innerHTML = '<p class="text-center text-red-500">Terjadi kesalahan.</p>';
    }
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-detail-piutang')) {
        const id = e.target.dataset.id;
        const nama = e.target.dataset.nama;
        showDetailPiutang(id, nama);
    }
});



document.getElementById('bayar-method')?.addEventListener('change', function() {
    const method = this.value;
    const containerWb = document.getElementById('container-saldo-wb');
    const containerAkun = document.getElementById('container-bayar-akun');
    const akunSelect = document.getElementById('bayar-akun');

    if (method === 'wb') {
        containerWb?.classList.remove('hidden');
        containerAkun?.classList.add('hidden');
        akunSelect.removeAttribute('required');
    } else {
        containerWb?.classList.add('hidden');
        containerAkun?.classList.remove('hidden');
        akunSelect.setAttribute('required', 'required');
    }
});

document.getElementById('form-bayar-piutang')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    if(!confirm('Yakin ingin memproses pembayaran ini?')) return;

    const formData = Object.fromEntries(new FormData(this));
    
    // Validasi saldo jika menggunakan WB
    if (formData.method === 'wb') {
        const saldoWbText = document.getElementById('display-saldo-wb').textContent;
        const saldoWb = parseFloat(saldoWbText.replace(/[^0-9,-]+/g, "").replace(",", ".")) || 0;
        if (parseFloat(formData.amount) > saldoWb) {
            showToast('Saldo Wajib Belanja tidak mencukupi.', 'error');
            return;
        }
    }

    const response = await fetch(`${basePath}/api/laporan-piutang?action=pay`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(formData)
    });
    const result = await response.json();
    if(result.success) {
        showToast(result.message, 'success');
        closeModal('piutangDetailModal');
        loadLaporanPiutang();
    } else {
        showToast(result.message, 'error');
    }
});

const importBtn = document.getElementById('piutang-import-btn');
const importModal = document.getElementById('importPiutangModal');
const importForm = document.getElementById('form-import-piutang');

if (importBtn) {
    importBtn.addEventListener('click', () => {
        importForm.reset();
        document.getElementById('import-piutang-tanggal').valueAsDate = new Date();
        openModal('importPiutangModal');
    });
}

if (importForm) {
    importForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('btn-process-import-piutang');
        const originalText = submitBtn.innerHTML;
        
        const formData = new FormData(this);
        formData.append('action', 'import_piutang');

        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Memproses...';

        try {
            const response = await fetch(`${basePath}/api/laporan-piutang?action=import_piutang`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                closeModal('importPiutangModal');
                loadLaporanPiutang();
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Gagal mengimpor data.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

const pdfBtn = document.getElementById('piutang-pdf-btn');
const csvBtn = document.getElementById('piutang-csv-btn');

if (pdfBtn) {
    pdfBtn.addEventListener('click', () => {
        const params = new URLSearchParams({
            report: 'laporan-piutang'
        });
        window.open(`${basePath}/api/pdf?${params.toString()}`, '_blank');
    });
}

if (csvBtn) {
    csvBtn.addEventListener('click', () => {
        const params = new URLSearchParams({
            report: 'laporan-piutang',
            format: 'csv'
        });
        window.location.href = `${basePath}/api/csv?${params.toString()}`;
    });
}

loadLaporanPiutang();