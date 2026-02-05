// File: assets/js/ksp/member/profile.js

document.addEventListener('DOMContentLoaded', () => {
    // Load Payment Settings Options when modal opens
    const paymentSettingsBtn = document.querySelector('[onclick*="modal-pengaturan-pembayaran"]');
    if (paymentSettingsBtn) {
        paymentSettingsBtn.addEventListener('click', loadPaymentSettingsOptions);
    }
});

async function loadPaymentSettingsOptions() {
    const container = document.getElementById('payment-settings-options');
    container.innerHTML = '<p class="text-center text-gray-400 text-xs">Memuat...</p>';

    try {
        // Gunakan data dashboard yang sudah ada jika tersedia
        let data = window.memberDashboardData;
        if (!data) {
            const res = await fetch(`${basePath}/api/member/dashboard?action=summary`);
            const json = await res.json();
            if (json.success) data = json.data;
        }

        if (data && data.simpanan_per_jenis) {
            // Tampilkan semua jenis simpanan yang memiliki saldo
            const savingsOptions = data.simpanan_per_jenis;
            const currentDefault = data.default_payment_savings_id;

            if (savingsOptions.length > 0) {
                container.innerHTML = savingsOptions.map(s => `
                    <label class="flex items-center p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition">
                        <input type="radio" name="default_savings_id" value="${s.id}" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500" ${s.id == currentDefault ? 'checked' : ''}>
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900">${s.nama}</span>
                            <span class="block text-xs text-gray-500">Saldo: ${formatRupiah(s.saldo)}</span>
                        </div>
                    </label>
                `).join('');
            } else {
                container.innerHTML = '<p class="text-center text-red-500 text-xs">Anda tidak memiliki data simpanan.</p>';
            }
        }
    } catch (e) { console.error(e); }
}

document.getElementById('form-pengaturan-pembayaran').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Menyimpan...';

    try {
        const formData = new FormData(this);
        const response = await fetch(`${basePath}/api/member/dashboard?action=update_payment_settings`, { method: 'POST', body: formData });
        const result = await response.json();

        if(result.success) {
            document.getElementById('modal-pengaturan-pembayaran').classList.add('hidden');
            loadSummary(); // Reload data to update global store
            Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message, timer: 1500, showConfirmButton: false });
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: result.message });
        }
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan jaringan.' });
    }
    btn.disabled = false;
    btn.innerText = originalText;
});

document.getElementById('change-password-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const alertBox = document.getElementById('password-alert');
    
    btn.disabled = true;
    btn.innerHTML = 'Menyimpan...';
    alertBox.classList.add('hidden');

    try {
        const formData = new FormData(this);
        const response = await fetch(`${basePath}/api/member/profile`, { method: 'POST', body: formData });
        const result = await response.json();

        alertBox.className = `mt-3 p-3 rounded-lg text-sm text-center block ${result.success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
        alertBox.textContent = result.message;
        if(result.success) this.reset();
    } catch (error) {
        alertBox.className = 'mt-3 p-3 rounded-lg text-sm text-center bg-red-100 text-red-700 block';
        alertBox.textContent = 'Terjadi kesalahan jaringan.';
    }
    btn.disabled = false;
    btn.innerHTML = 'Simpan Password';
});

document.getElementById('form-tambah-target').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerText;
    
    btn.disabled = true;
    btn.innerText = 'Menyimpan...';

    try {
        const formData = new FormData(this);
        const response = await fetch(`${basePath}/api/member/dashboard?action=add_target`, { method: 'POST', body: formData });
        const result = await response.json();

        if(result.success) {
            document.getElementById('modal-tambah-target').classList.add('hidden');
            this.reset();
            loadSummary(); // Reload data to show new target
            Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Target berhasil ditambahkan', timer: 1500, showConfirmButton: false });
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: result.message });
        }
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan jaringan.' });
    }
    btn.disabled = false;
    btn.innerText = originalText;
});

async function deleteTarget(id) {
    const confirmResult = await Swal.fire({
        title: 'Hapus Target?',
        text: "Apakah Anda yakin ingin menghapus target ini?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    });
    
    if (!confirmResult.isConfirmed) return;
    
    try {
        const formData = new FormData();
        formData.append('id', id);
        const response = await fetch(`${basePath}/api/member/dashboard?action=delete_target`, { method: 'POST', body: formData });
        const result = await response.json();

        if(result.success) {
            loadSummary(); // Reload data untuk memperbarui tampilan
            Swal.fire({ icon: 'success', title: 'Terhapus', text: 'Target berhasil dihapus', timer: 1500, showConfirmButton: false });
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: result.message });
        }
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan jaringan.' });
    }
}

function showDigitalCard() {
    const data = window.memberDashboardData;
    if (!data) return;

    document.getElementById('card-name').textContent = data.nama;
    document.getElementById('card-no').textContent = data.nomor_anggota;
    document.getElementById('card-since').textContent = formatDate(data.tanggal_daftar);
    
    // Generate QR Code using public API
    const qrData = `${data.nomor_anggota}|${data.nama}`;
    document.getElementById('card-qr').src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(qrData)}`;

    document.getElementById('modal-kartu-digital').classList.remove('hidden');
}

async function downloadDigitalCard() {
    const cardElement = document.getElementById('digital-card-content');
    if (!cardElement) return;

    // Tampilkan loading indicator
    Swal.fire({
        title: 'Memproses...',
        text: 'Sedang membuat gambar kartu...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const canvas = await html2canvas(cardElement, {
            scale: 3, // Kualitas tinggi (3x resolusi layar)
            useCORS: true, // Penting untuk memuat gambar eksternal (avatar/logo)
            backgroundColor: null
        });

        const link = document.createElement('a');
        link.download = `Kartu-Anggota-${document.getElementById('card-no').textContent.replace(/\s/g, '')}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
        
        Swal.close();
    } catch (error) {
        console.error('Gagal download kartu:', error);
        Swal.fire('Gagal', 'Tidak dapat mengunduh kartu saat ini.', 'error');
    }
}

// --- Logic Gamifikasi ---
const pointDescriptions = {
    'setor_sukarela': 'Setoran Simpanan Sukarela',
    'bayar_tepat_waktu': 'Bayar Angsuran Tepat Waktu',
    'lunas_pinjaman': 'Melunasi Pinjaman'
};

async function loadGamificationLog() {
    const container = document.getElementById('gamification-log-list');
    container.innerHTML = '<p class="text-center text-gray-400 text-xs">Memuat...</p>';
    
    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=get_gamification_log`);
        const json = await res.json();
        
        if(json.success && json.data.length > 0) {
            container.innerHTML = json.data.map(item => {
                const description = pointDescriptions[item.action_type] || 'Aktivitas Lain';
                return `
                    <div class="flex justify-between items-center p-2.5 rounded-lg border border-gray-100 bg-gray-50/50 text-xs">
                        <div>
                            <p class="font-semibold text-gray-700">${description}</p>
                            <p class="text-gray-400 mt-0.5">${formatDate(item.created_at)}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600 text-sm">+${item.points_awarded} Poin</p>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = '<p class="text-center text-gray-400 text-xs py-2">Belum ada riwayat poin.</p>';
        }
    } catch(e) { console.error(e); }
}

function openGamificationModal() {
    document.getElementById('modal-gamifikasi').classList.remove('hidden');
    loadGamificationLog();
}

async function loadQrHistory() {
    const container = document.getElementById('qr-history-list');
    container.innerHTML = '<p class="text-center text-gray-400 text-xs">Memuat...</p>';
    
    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=get_qr_payment_history`);
        const json = await res.json();
        
        if(json.success && json.data.length > 0) {
            container.innerHTML = json.data.map(item => {
                // Ekstrak nama merchant dari keterangan
                let merchantName = item.keterangan.replace('Pembayaran QR ke ', '').split(' (Ref:')[0];
                
                return `
                    <div class="flex justify-between items-center p-3 rounded-lg border border-gray-100 bg-gray-50/50">
                        <div>
                            <p class="font-semibold text-sm text-gray-800">${merchantName}</p>
                            <p class="text-xs text-gray-400 mt-0.5">${formatDate(item.tanggal)}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-sm text-red-600">-${formatRupiah(item.jumlah)}</p>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = '<p class="text-center text-gray-400 text-xs py-4">Belum ada riwayat pembayaran QR.</p>';
        }
    } catch(e) { 
        console.error(e);
        container.innerHTML = '<p class="text-center text-red-500 text-xs py-4">Gagal memuat riwayat.</p>';
    }
}

window.openQrHistoryModal = function() {
    document.getElementById('modal-qr-history').classList.remove('hidden');
    loadQrHistory();
}
