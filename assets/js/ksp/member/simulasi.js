// File: assets/js/ksp/member/simulasi.js

let loanTypes = [];
async function loadLoanTypes() {
    if (loanTypes.length > 0) return; // Load only once
    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=get_loan_types`);
        const json = await res.json();
        if (json.success) {
            loanTypes = json.data;
            const select = document.getElementById('simulasi_jenis');
            select.innerHTML = loanTypes.map(t => `<option value="${t.id}" data-bunga="${t.bunga_per_tahun}">${t.nama} (${t.bunga_per_tahun}% p.a)</option>`).join('');
        }
    } catch (e) { console.error(e); }
}

document.getElementById('simulasi-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const jenisSelect = document.getElementById('simulasi_jenis');
    const jumlah = parseFloat(document.getElementById('simulasi_jumlah').value);
    const tenor = parseInt(document.getElementById('simulasi_tenor').value);
    const bungaPersen = parseFloat(jenisSelect.options[jenisSelect.selectedIndex].dataset.bunga);

    if (!jumlah || !tenor || isNaN(bungaPersen)) {
        Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Mohon lengkapi semua data.' });
        return;
    }

    // Perhitungan Bunga Flat
    const pokokBulanan = jumlah / tenor;
    const bungaBulanan = (jumlah * (bungaPersen / 100)) / 12;
    const totalBulanan = pokokBulanan + bungaBulanan;

    document.getElementById('est-pokok').textContent = formatRupiah(pokokBulanan);
    document.getElementById('est-bunga').textContent = formatRupiah(bungaBulanan);
    document.getElementById('est-total').textContent = formatRupiah(totalBulanan);
    
    document.getElementById('hasil-simulasi').classList.remove('hidden');
});

document.getElementById('zakat-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const totalHarta = parseFloat(document.getElementById('zakat_total').value) || 0;
    const goldPrice = parseFloat(document.getElementById('zakat_gold_price').value) || 0;
    
    const nisab = 85 * goldPrice;
    const zakatAmount = totalHarta * 0.025;
    
    document.getElementById('zakat-nisab').textContent = formatRupiah(nisab);
    
    const statusEl = document.getElementById('zakat-status');
    const amountEl = document.getElementById('zakat-amount');
    
    if (totalHarta >= nisab) {
        statusEl.textContent = 'Wajib Zakat';
        statusEl.className = 'font-bold text-green-600';
        amountEl.textContent = formatRupiah(zakatAmount);
    } else {
        statusEl.textContent = 'Belum Wajib Zakat';
        statusEl.className = 'font-bold text-gray-500';
        amountEl.textContent = formatRupiah(0);
    }
    
    document.getElementById('hasil-zakat').classList.remove('hidden');
});
