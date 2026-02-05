// File: assets/js/ksp/member/pinjaman.js

document.addEventListener('DOMContentLoaded', () => {
    const loanForm = document.getElementById('form-ajukan-pinjaman');
    if (loanForm) {
        const newForm = loanForm.cloneNode(true);
        loanForm.parentNode.replaceChild(newForm, loanForm);
        newForm.addEventListener('submit', handleLoanSubmit);
    }
});

let showHistoryPinjaman = false;

function toggleHistoryPinjaman() {
    const container = document.getElementById('pinjaman-history-container');
    const btnShow = document.getElementById('btn-show-history');
    
    if (container) {
        const isExpanded = container.classList.contains('grid-rows-[1fr]');
        if (isExpanded) {
            container.classList.remove('grid-rows-[1fr]', 'mb-4');
            container.classList.add('grid-rows-[0fr]');
            if(btnShow) btnShow.classList.remove('hidden');
            showHistoryPinjaman = false;
        } else {
            container.classList.remove('grid-rows-[0fr]');
            container.classList.add('grid-rows-[1fr]', 'mb-4');
            if(btnShow) btnShow.classList.add('hidden');
            showHistoryPinjaman = true;
        }
    } else {
        showHistoryPinjaman = !showHistoryPinjaman;
        loadPinjamanList();
    }
}

async function loadPinjamanList() {
    const container = document.getElementById('pinjaman-list');
    const headerTotal = document.getElementById('header-sisa-pinjaman');
    
    // Skeleton Loading
    container.innerHTML = `
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm animate-pulse">
            <div class="flex justify-between mb-4">
                <div class="h-4 w-24 bg-gray-200 rounded"></div>
                <div class="h-4 w-16 bg-gray-200 rounded"></div>
            </div>
            <div class="h-6 w-32 bg-gray-200 rounded mb-2"></div>
            <div class="h-2 w-full bg-gray-100 rounded-full mt-4"></div>
        </div>
    `;

    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=list_pinjaman&status=all`);
        const json = await res.json();

        if(json.success) {
            const data = json.data;
            
            // Update Header Total (Hitung total sisa pokok hanya yang aktif)
            const totalSisa = data.filter(p => p.status === 'aktif').reduce((sum, p) => sum + parseFloat(p.sisa_pokok), 0);
            if(headerTotal) headerTotal.textContent = formatRupiah(totalSisa);

            if (data.length === 0) {
                container.innerHTML = '<div class="text-center py-10 text-gray-400 text-sm">Belum ada data pinjaman.</div>';
                return;
            }

            const activeLoans = data.filter(p => ['pending', 'aktif'].includes(p.status));
            const historyLoans = data.filter(p => ['lunas', 'ditolak'].includes(p.status));

            let html = '';

            // Render Active Loans
            if (activeLoans.length > 0) {
                html += activeLoans.map(loan => renderLoanCard(loan)).join('');
            } else {
                 html += '<div class="text-center py-8 text-gray-400 text-sm">Tidak ada pinjaman aktif.</div>';
            }

            // Render History
            if (historyLoans.length > 0) {
                const btnDisplay = showHistoryPinjaman ? 'hidden' : '';
                const containerClass = showHistoryPinjaman ? 'grid-rows-[1fr] mb-4' : 'grid-rows-[0fr]';

                html += `
                    <button id="btn-show-history" onclick="toggleHistoryPinjaman()" class="w-full py-3 mt-2 bg-gray-100 text-gray-600 rounded-xl text-xs font-bold hover:bg-gray-200 transition flex items-center justify-center gap-2 ${btnDisplay}">
                        <i class="bi bi-clock-history"></i> Tampilkan Riwayat Lunas (${historyLoans.length})
                    </button>
                    
                    <div id="pinjaman-history-container" class="grid ${containerClass} transition-[grid-template-rows] duration-500 ease-out">
                        <div class="overflow-hidden">
                            <div class="relative py-4 flex items-center">
                                <div class="flex-grow border-t border-gray-200"></div>
                                <span class="flex-shrink-0 mx-4 text-gray-400 text-[10px] font-bold uppercase tracking-wider">Riwayat Pinjaman</span>
                                <div class="flex-grow border-t border-gray-200"></div>
                            </div>
                            ${historyLoans.map(loan => renderLoanCard(loan)).join('')}
                            <button onclick="toggleHistoryPinjaman()" class="w-full py-3 mt-4 text-gray-400 text-xs font-medium hover:text-gray-600 transition">
                                Sembunyikan Riwayat
                            </button>
                        </div>
                    </div>
                `;
            }

            container.innerHTML = html;
        }
    } catch(e) { 
        console.error(e);
        container.innerHTML = '<div class="text-center py-10 text-red-400 text-sm">Gagal memuat data.</div>';
    }
}

function renderLoanCard(loan) {
    const sisa = parseFloat(loan.sisa_pokok);
    const isLunas = loan.status === 'lunas' || sisa <= 0;
    const cardBg = isLunas ? 'bg-gray-50' : 'bg-white';
    
    let statusColor = 'bg-blue-100 text-blue-700';
    if (loan.status === 'pending') statusColor = 'bg-yellow-100 text-yellow-700';
    if (loan.status === 'lunas') statusColor = 'bg-green-100 text-green-700';
    if (loan.status === 'ditolak') statusColor = 'bg-red-100 text-red-700';

    // Logic Tampilan Angsuran Berikutnya
    let nextInstHtml = '';
    if (loan.status === 'aktif' && loan.next_installment) {
        const [date, amount] = loan.next_installment.split('|');
        nextInstHtml = `
            <div class="mt-3 pt-3 border-t border-gray-100 flex justify-between items-center bg-gray-50/50 -mx-5 px-5 -mb-5 py-3 rounded-b-2xl">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-violet-100 text-violet-600 flex items-center justify-center">
                        <i class="bi bi-calendar-event text-sm"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-500">Jatuh Tempo</p>
                        <p class="text-xs font-bold text-gray-800">${formatDate(date)}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-gray-500">Tagihan</p>
                    <p class="text-xs font-bold text-red-600">${formatRupiah(amount)}</p>
                </div>
            </div>
        `;
    } else if (loan.status === 'pending') {
            nextInstHtml = `
            <div class="mt-3 pt-3 border-t border-gray-100 text-center bg-yellow-50/50 -mx-5 px-5 -mb-5 py-2 rounded-b-2xl">
                <p class="text-xs text-yellow-700 italic"><i class="bi bi-hourglass-split"></i> Menunggu persetujuan admin</p>
            </div>
        `;
    }

    return `
    <div class="${cardBg} rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow cursor-pointer relative overflow-hidden mb-3" onclick="showLoanDetail(${loan.id})">
        <div class="flex justify-between items-start mb-3">
            <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold mb-0.5">No. Pinjaman</p>
                <h4 class="font-bold text-gray-800 text-sm">${loan.nomor_pinjaman}</h4>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide ${statusColor}">${loan.status}</span>
        </div>
        
        <div class="flex justify-between items-end mb-2">
            <div>
                <p class="text-xs text-gray-500 mb-1">Sisa Pokok</p>
                <p class="text-xl font-bold text-violet-700 tracking-tight">${formatRupiah(sisa)}</p>
            </div>
            <div class="text-right">
                <p class="text-[10px] text-gray-400">Plafon</p>
                <p class="text-xs font-medium text-gray-600">${formatRupiah(loan.jumlah_pinjaman)}</p>
            </div>
        </div>
        ${nextInstHtml}
    </div>
    `;
}

async function showLoanDetail(id) {
    const modal = document.getElementById('modal-detail-pinjaman');
    const content = document.getElementById('detail-pinjaman-content');
    
    modal.classList.remove('hidden');
    content.innerHTML = '<div class="text-center py-8"><span class="animate-spin inline-block w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full"></span></div>';

    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=get_loan_detail&id=${id}`);
        const json = await res.json();

        if (json.success) {
            const { data, schedule } = json;
            
            let scheduleHtml = '';
            if (schedule && schedule.length > 0) {
                scheduleHtml = schedule.map(sch => {
                    const isPaid = sch.status === 'lunas';
                    
                    // Cek Keterlambatan
                    const dueDate = new Date(sch.tanggal_jatuh_tempo);
                    const today = new Date();
                    today.setHours(0,0,0,0); // Reset jam agar perbandingan hanya berdasarkan tanggal
                    const isOverdue = !isPaid && dueDate < today;

                    const totalTerbayar = parseFloat(sch.pokok_terbayar || 0) + parseFloat(sch.bunga_terbayar || 0);
                    
                    let paymentInfo = '';
                    if (totalTerbayar > 0) {
                        const tglBayar = sch.tanggal_bayar ? formatDate(sch.tanggal_bayar) : '-';
                        paymentInfo = `
                            <div class="mt-1 flex items-center gap-1">
                                <i class="bi bi-check-circle-fill text-[10px] text-green-500"></i>
                                <p class="text-[10px] text-green-700">
                                    Bayar: <b>${formatRupiah(totalTerbayar)}</b> <span class="text-green-600/70">(${tglBayar})</span>
                                </p>
                            </div>
                        `;
                    }

                    const rowClass = isOverdue ? 'bg-red-50 border-red-100 rounded-lg' : 'border-gray-50 hover:bg-gray-50 rounded-lg';
                    const dateClass = isOverdue ? 'text-red-500 font-medium' : 'text-gray-400';

                    return `
                    <div class="flex justify-between items-start py-3 px-2 border-b last:border-0 transition-colors ${rowClass}">
                        <div>
                            <p class="text-xs font-bold text-gray-700">Angsuran ke-${sch.angsuran_ke}</p>
                            <p class="text-[10px] ${dateClass}">${formatDate(sch.tanggal_jatuh_tempo)}</p>
                            ${paymentInfo}
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold ${isPaid ? 'text-green-600' : (isOverdue ? 'text-red-600' : 'text-gray-800')}">${formatRupiah(sch.total_angsuran)}</p>
                            <span class="text-[10px] px-1.5 py-0.5 rounded ${isPaid ? 'bg-green-100 text-green-700' : (isOverdue ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500')}">${isPaid ? 'Lunas' : (isOverdue ? 'Telat' : 'Belum')}</span>
                        </div>
                    </div>
                    `;
                }).join('');
            }

            content.innerHTML = `
                <div class="bg-gray-50 p-4 rounded-xl mb-4">
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <p class="text-gray-500">Pokok Pinjaman</p>
                            <p class="font-bold text-gray-800">${formatRupiah(data.jumlah_pinjaman)}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Tenor</p>
                            <p class="font-bold text-gray-800">${data.tenor_bulan} Bulan</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Tanggal Cair</p>
                            <p class="font-bold text-gray-800">${formatDate(data.tanggal_pencairan || data.tanggal_pengajuan)}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Status</p>
                            <p class="font-bold uppercase ${data.status === 'aktif' ? 'text-blue-600' : 'text-gray-800'}">${data.status}</p>
                        </div>
                    </div>
                </div>
                <h4 class="font-bold text-sm text-gray-800 mb-2">Jadwal Angsuran</h4>
                <div class="max-h-60 overflow-y-auto pr-1">
                    ${scheduleHtml}
                </div>
            `;
        }
    } catch (e) {
        content.innerHTML = '<p class="text-center text-red-500">Gagal memuat detail.</p>';
    }
}

async function openLoanApplicationModal() {
    const modal = document.getElementById('modal-ajukan-pinjaman');
    const select = document.getElementById('loan-type-select');
    
    modal.classList.remove('hidden');
    select.innerHTML = '<option value="">Memuat...</option>';

    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=get_loan_types`);
        const json = await res.json();
        
        if (json.success) {
            select.innerHTML = '<option value="">Pilih Jenis Pinjaman</option>' + 
                json.data.map(t => `<option value="${t.id}">${t.nama} (Bunga: ${t.bunga_per_tahun}%/thn)</option>`).join('');
        }
    } catch (e) {
        select.innerHTML = '<option value="">Gagal memuat</option>';
    }
}

async function handleLoanSubmit(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Mengirim...';

    try {
        const formData = new FormData(this);
        const response = await fetch(`${basePath}/api/member/dashboard?action=apply_loan`, { method: 'POST', body: formData });
        const result = await response.json();

        if(result.success) {
            Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message });
            document.getElementById('modal-ajukan-pinjaman').classList.add('hidden');
            this.reset();
            loadPinjamanList();
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: result.message });
        }
    } catch (error) {
        Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
    } finally {
        btn.disabled = false;
        btn.innerText = originalText;
    }
}

// Expose to global
window.loadPinjamanList = loadPinjamanList;
window.toggleHistoryPinjaman = toggleHistoryPinjaman;
window.openLoanApplicationModal = openLoanApplicationModal;
window.showLoanDetail = showLoanDetail;
