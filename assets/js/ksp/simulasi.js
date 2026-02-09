function initSimulasiPage() {
    const form = document.getElementById('form-simulasi');
    if (!form) return;

    const resultCard = document.getElementById('result-card');
    const emptyState = document.getElementById('empty-state');
    const btnReset = document.getElementById('btn-reset');
    
    // Format input uang saat mengetik
    const moneyInputs = document.querySelectorAll('.money-input');
    moneyInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            // Hapus karakter non-digit
            let value = this.value.replace(/[^0-9]/g, '');
            
            if (value) {
                // Format ke rupiah
                this.value = new Intl.NumberFormat('id-ID').format(value);
            } else {
                this.value = '';
            }
        });
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Ambil nilai dan bersihkan format ribuan
        const jumlahPinjamanStr = document.getElementById('jumlah_pinjaman').value.replace(/\./g, '');
        const jumlahPinjaman = parseFloat(jumlahPinjamanStr);
        const tenor = parseInt(document.getElementById('tenor_bulan').value);
        const bungaPersen = parseFloat(document.getElementById('bunga_per_tahun').value);

        if (isNaN(jumlahPinjaman) || jumlahPinjaman <= 0) {
            alert('Mohon masukkan jumlah pinjaman yang valid.');
            return;
        }

        calculateFlat(jumlahPinjaman, tenor, bungaPersen);
    });

    btnReset.addEventListener('click', function() {
        form.reset();
        resultCard.classList.add('hidden');
        emptyState.classList.remove('hidden');
    });

    function calculateFlat(principal, months, ratePerYear) {
        // Perhitungan Bunga Flat
        const pokokBulanan = principal / months;
        const bungaBulanan = (principal * (ratePerYear / 100)) / 12;
        const angsuranBulanan = pokokBulanan + bungaBulanan;
        
        const totalBunga = bungaBulanan * months;
        const totalBayar = principal + totalBunga;

        // Update Summary Cards
        document.getElementById('res-angsuran-bulan').textContent = formatRupiah(angsuranBulanan);
        document.getElementById('res-total-bunga').textContent = formatRupiah(totalBunga);
        document.getElementById('res-total-bayar').textContent = formatRupiah(totalBayar);

        // Generate Table Rows
        const tbody = document.getElementById('table-schedule-body');
        tbody.innerHTML = '';

        let sisaPinjaman = principal;
        
        for (let i = 1; i <= months; i++) {
            sisaPinjaman -= pokokBulanan;
            
            // Koreksi pembulatan di bulan terakhir agar sisa benar-benar 0
            if (i === months || sisaPinjaman < 0) sisaPinjaman = 0;

            const tr = document.createElement('tr');
            tr.className = "hover:bg-gray-50 dark:hover:bg-gray-700";
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500 dark:text-gray-300">${i}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">${formatRupiah(pokokBulanan)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">${formatRupiah(bungaBulanan)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900 dark:text-white">${formatRupiah(angsuranBulanan)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-300">${formatRupiah(sisaPinjaman)}</td>
            `;
            tbody.appendChild(tr);
        }

        // Show Result, Hide Empty State
        resultCard.classList.remove('hidden');
        emptyState.classList.add('hidden');
    }

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(angka);
    }
}