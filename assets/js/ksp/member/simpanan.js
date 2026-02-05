// File: assets/js/ksp/member/simpanan.js

document.addEventListener('DOMContentLoaded', () => {
    // Initialize forms if they exist
    const withdrawalForm = document.getElementById('form-tarik-simpanan');
    if (withdrawalForm) {
        // Clone to remove existing listeners if any
        const newForm = withdrawalForm.cloneNode(true);
        withdrawalForm.parentNode.replaceChild(newForm, withdrawalForm);
        newForm.addEventListener('submit', handleWithdrawalSubmit);
    }

    const transferForm = document.getElementById('form-transfer-simpanan');
    if (transferForm) {
        const newForm = transferForm.cloneNode(true);
        transferForm.parentNode.replaceChild(newForm, transferForm);
        newForm.addEventListener('submit', handleTransferSubmit);
    }

    // Event listeners untuk filter riwayat (Jenis, Bulan, Tahun)
    ['history-filter-jenis', 'history-filter-bulan', 'history-filter-tahun'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', loadSimpananHistory);
    });
});

/**
 * Script khusus untuk Tab Simpanan di Member Dashboard
 * Menangani tampilan kartu simpanan dan riwayat transaksi
 */

async function loadSimpananHistory() {
    const gridContainer = document.getElementById('simpanan-types-grid');
    const historyContainer = document.getElementById('simpanan-history-list');
    const filterJenis = document.getElementById('history-filter-jenis');
    const filterBulan = document.getElementById('history-filter-bulan');
    const filterTahun = document.getElementById('history-filter-tahun');
    
    // Loading state untuk Grid Kartu
    if(gridContainer) {
        gridContainer.innerHTML = `
            <div class="col-span-2 bg-white p-4 rounded-2xl shadow-sm border border-gray-100 text-center py-8">
                <div class="animate-spin inline-block w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full mb-2"></div>
                <p class="text-xs text-gray-500">Memuat data simpanan...</p>
            </div>`;
    }

    // Loading state untuk Riwayat (jika belum ada isinya)
    if(historyContainer && historyContainer.children.length === 0) {
         historyContainer.innerHTML = '<div class="text-center py-4 text-gray-400 text-xs">Memuat riwayat...</div>';
    }

    try {
        // 1. Load Summary (Kartu Simpanan)
        const summaryRes = await fetch(basePath + '/api/member/dashboard?action=summary');
        const summaryJson = await summaryRes.json();

        if (summaryJson.success && summaryJson.data.simpanan_per_jenis) {
            const simpanan = summaryJson.data.simpanan_per_jenis;
            let totalSimpanan = 0;
            
            if (gridContainer) {
                if (simpanan.length > 0) {
                    gridContainer.innerHTML = simpanan.map(s => {
                        totalSimpanan += parseFloat(s.saldo);
                        let icon = 'bi-wallet2';
                        let color = 'blue';
                        
                        // Kustomisasi ikon dan warna berdasarkan tipe
                        if (s.tipe === 'pokok') { icon = 'bi-shield-lock-fill'; color = 'indigo'; }
                        else if (s.tipe === 'wajib') { icon = 'bi-calendar-check-fill'; color = 'purple'; }
                        else if (s.tipe === 'sukarela') { icon = 'bi-piggy-bank-fill'; color = 'emerald'; }

                        // Layout Kartu: 
                        // - Icon dan Nama Simpanan sejajar
                        // - Saldo rata kanan bawah dengan font tebal
                        return `
                        <div class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between h-full relative overflow-hidden group transition-all hover:shadow-md min-h-[100px] cursor-pointer" onclick="showSavingsDetail(${s.id}, '${s.nama}')">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 rounded-full bg-${color}-50 text-${color}-600 flex items-center justify-center shadow-sm shrink-0">
                                    <i class="bi ${icon} text-sm"></i>
                                </div>
                                <p class="text-xs text-gray-700 font-bold line-clamp-2 leading-tight" title="${s.nama}">${s.nama}</p>
                            </div>
                            <div>
                                <p class="text-base font-bold text-gray-800 text-right tracking-tight">${formatRupiah(s.saldo)}</p>
                            </div>
                        </div>
                        `;
                    }).join('');
                } else {
                    gridContainer.innerHTML = '<div class="col-span-2 text-center text-gray-500 py-4 text-xs">Belum ada data simpanan.</div>';
                }
            }
            
            // Update total aset simpanan di header
            const totalEl = document.getElementById('tab-simpanan-total');
            if(totalEl) totalEl.textContent = formatRupiah(totalSimpanan);

            // Populate Filter Jenis Simpanan jika masih kosong (kecuali default)
            const filterJenis = document.getElementById('history-filter-jenis');
            if (filterJenis && filterJenis.options.length <= 1) {
                simpanan.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.nama;
                    filterJenis.appendChild(opt);
                });
            }
        }

        // 2. Load History (Riwayat dengan Keterangan)
        const jenisId = filterJenis ? filterJenis.value : '';
        const bulan = filterBulan ? filterBulan.value : '';
        const tahun = filterTahun ? filterTahun.value : '';
        const historyRes = await fetch(basePath + `/api/member/dashboard?action=get_all_savings_history&jenis_id=${jenisId}&bulan=${bulan}&tahun=${tahun}`);
        const historyJson = await historyRes.json();

        if (historyJson.success && historyContainer) {
            if (historyJson.data.length > 0) {
                historyContainer.innerHTML = historyJson.data.map(item => `
                    <div class="bg-white p-3 rounded-xl shadow-sm flex justify-between items-start border border-gray-100">
                        <div class="flex items-start gap-3 overflow-hidden">
                            <div class="w-9 h-9 rounded-full ${item.jenis_transaksi === 'setor' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'} flex items-center justify-center shrink-0 mt-0.5">
                                <i class="bi ${item.jenis_transaksi === 'setor' ? 'bi-arrow-down' : 'bi-arrow-up'} text-base"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-gray-800 text-xs truncate">${item.jenis_simpanan}</p>
                                <p class="text-[10px] text-gray-400 mb-0.5">${formatDate(item.tanggal)}</p>
                                <p class="text-[10px] text-gray-500 leading-tight line-clamp-2">${item.keterangan || '-'}</p>
                            </div>
                        </div>
                        <span class="font-bold text-xs ${item.jenis_transaksi === 'setor' ? 'text-green-600' : 'text-red-600'} whitespace-nowrap ml-2">
                            ${item.jenis_transaksi === 'setor' ? '+' : '-'} ${formatRupiah(item.jumlah)}
                        </span>
                    </div>
                `).join('');
            } else {
                historyContainer.innerHTML = '<p class="text-center text-gray-400 text-xs py-4">Belum ada riwayat transaksi.</p>';
            }
        }

    } catch (error) {
        console.error('Error loading simpanan data:', error);
        if(gridContainer) gridContainer.innerHTML = '<div class="col-span-2 text-center text-red-500 py-4 text-xs">Gagal memuat data.</div>';
    }
}

// Expose fungsi ke window agar bisa dipanggil dari onclick atau script lain
window.loadSimpananHistory = loadSimpananHistory;


async function showSavingsDetail(id, name) {
    const modal = document.getElementById('modal-detail-simpanan');
    const content = document.getElementById('detail-simpanan-content');
    const title = document.getElementById('simpanan-detail-title');
    
    modal.classList.remove('hidden');
    title.textContent = `Riwayat ${name}`;
    content.innerHTML = '<div class="text-center py-8"><span class="animate-spin inline-block w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full"></span></div>';

    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=get_savings_history_by_type&id=${id}`);
        const json = await res.json();

        if (json.success && json.data.length > 0) {
            let html = '<div class="space-y-2 max-h-[60vh] overflow-y-auto pr-1">';
            html += json.data.map(tx => {
                const isSetor = tx.jenis_transaksi === 'setor';
                return `
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-sm">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-medium text-gray-700">${formatDate(tx.tanggal)}</span>
                            <span class="font-bold ${isSetor ? 'text-green-600' : 'text-red-600'}">
                                ${isSetor ? '+' : '-'} ${formatRupiah(tx.jumlah)}
                            </span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span class="truncate pr-2">${tx.keterangan || 'Transaksi'}</span>
                            <span>Saldo: ${formatRupiah(tx.saldo)}</span>
                        </div>
                    </div>
                `;
            }).join('');
            html += '</div>';
            content.innerHTML = html;
        } else {
            content.innerHTML = '<p class="text-center text-gray-500 py-4">Tidak ada riwayat transaksi untuk simpanan ini.</p>';
        }
    } catch (e) { console.error(e); content.innerHTML = '<p class="text-center text-red-500">Terjadi kesalahan.</p>'; }
}

function openWithdrawalModal() {
    const data = window.memberDashboardData;
    if (!data || !data.simpanan_per_jenis) {
        Swal.fire({ icon: 'info', title: 'Memuat Data', text: 'Data simpanan belum termuat. Coba lagi sebentar.' });
        return;
    }

    const select = document.getElementById('withdrawal-source');
    select.innerHTML = '<option value="">Pilih simpanan...</option>'; // Reset
    
    const sukarelaSavings = data.simpanan_per_jenis.filter(s => s.tipe === 'sukarela');
    
    if (sukarelaSavings.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Tidak Tersedia', text: 'Anda tidak memiliki Simpanan Sukarela yang dapat ditarik.' });
        return;
    }

    sukarelaSavings.forEach(s => {
        const option = document.createElement('option');
        option.value = s.id;
        option.textContent = `${s.nama} (${formatRupiah(s.saldo)})`;
        option.dataset.balance = s.saldo;
        select.appendChild(option);
    });

    document.getElementById('withdrawal-balance').textContent = formatRupiah(0);
    document.getElementById('form-tarik-simpanan').reset();
    document.getElementById('modal-tarik-simpanan').classList.remove('hidden');
    loadWithdrawalHistory(); // Muat riwayat saat modal dibuka
}

document.getElementById('withdrawal-source')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const balance = selectedOption.dataset.balance || 0;
    document.getElementById('withdrawal-balance').textContent = formatRupiah(balance);
});

async function loadWithdrawalHistory() {
    const container = document.getElementById('withdrawal-history-list');
    container.innerHTML = '<p class="text-center text-gray-400 text-xs">Memuat...</p>';
    
    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=get_withdrawal_history`);
        const json = await res.json();
        
        if(json.success && json.data.length > 0) {
            container.innerHTML = json.data.map(item => {
                let statusColor = 'text-yellow-600 bg-yellow-50';
                if(item.status === 'approved') statusColor = 'text-green-600 bg-green-50';
                if(item.status === 'rejected') statusColor = 'text-red-600 bg-red-50';
                
                return `
                    <div class="flex justify-between items-center p-2.5 rounded-lg border border-gray-100 bg-gray-50/50 text-xs">
                        <div>
                            <p class="font-semibold text-gray-700">${item.jenis_simpanan}</p>
                            <p class="text-gray-400 mt-0.5">${formatDate(item.tanggal_pengajuan)}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-800 mb-1">${formatRupiah(item.jumlah)}</p>
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide ${statusColor}">${item.status}</span>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = '<p class="text-center text-gray-400 text-xs py-2">Belum ada riwayat pengajuan.</p>';
        }
    } catch(e) { console.error(e); }
}

async function handleWithdrawalSubmit(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerText;
    
    btn.disabled = true;
    btn.innerText = 'Mengajukan...';

    try {
        const formData = new FormData(this);
        const response = await fetch(`${basePath}/api/member/dashboard?action=request_withdrawal`, { method: 'POST', body: formData });
        const result = await response.json();

        if(result.success) {
            Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message });
            // Reset form dan muat ulang riwayat tanpa menutup modal agar user bisa melihat status pending
            this.reset();
            loadWithdrawalHistory();
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: result.message });
        }
    } catch (error) { Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan jaringan.' }); }
    btn.disabled = false;
    btn.innerText = originalText;
}

// --- Logic Transfer ---
let transferSelect = null;
function openTransferModal() {
    const data = window.memberDashboardData;
    if (!data || !data.simpanan_per_jenis) {
        Swal.fire({ icon: 'info', title: 'Memuat Data', text: 'Data simpanan belum termuat. Coba lagi sebentar.' });
        return;
    }

    const sukarela = data.simpanan_per_jenis.find(s => s.tipe === 'sukarela');
    if (!sukarela || sukarela.saldo <= 0) {
        Swal.fire({ icon: 'warning', title: 'Saldo Tidak Cukup', text: 'Anda tidak memiliki saldo di Simpanan Sukarela untuk ditransfer.' });
        return;
    }

    document.getElementById('transfer-balance').textContent = formatRupiah(sukarela.saldo);
    document.getElementById('form-transfer-simpanan').reset();

    if (!transferSelect) {
        transferSelect = new TomSelect("#transfer-destination",{
            valueField: 'id',
            labelField: 'nama_lengkap',
            searchField: ['nama_lengkap', 'nomor_anggota'],
            create: false,
            render: {
                option: function(data, escape) {
                    return `<div>
                                <span class="font-semibold">${escape(data.nama_lengkap)}</span>
                                <span class="block text-xs text-gray-500">${escape(data.nomor_anggota)}</span>
                            </div>`;
                },
                item: function(data, escape) {
                    return `<div>${escape(data.nama_lengkap)}</div>`;
                }
            },
            load: function(query, callback) {
                if (!query.length) return callback();
                fetch(`${basePath}/api/member/dashboard?action=search_members&q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(json => {
                        callback(json.data);
                    }).catch(()=>{
                        callback();
                    });
            }
        });
    }
    transferSelect.clear();
    transferSelect.clearOptions();

    document.getElementById('modal-transfer-simpanan').classList.remove('hidden');
}

async function handleTransferSubmit(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerText;
    
    btn.disabled = true;
    btn.innerText = 'Mengirim...';

    try {
        const formData = new FormData(this);
        const response = await fetch(`${basePath}/api/member/dashboard?action=transfer_savings`, { method: 'POST', body: formData });
        const result = await response.json();

        if(result.success) {
            Swal.fire({ icon: 'success', title: 'Transfer Berhasil', text: result.message });
            document.getElementById('modal-transfer-simpanan').classList.add('hidden');
            loadSummary(); // Refresh dashboard data
        } else {
            Swal.fire({ icon: 'error', title: 'Transfer Gagal', text: result.message });
        }
    } catch (error) { Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan jaringan.' }); }
    btn.disabled = false;
    btn.innerText = originalText;
}
