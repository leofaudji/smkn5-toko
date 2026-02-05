// File: assets/js/ksp/member/core.js

window.isPrivacyMode = localStorage.getItem('privacy_mode') === 'true';

document.addEventListener('DOMContentLoaded', () => {
    const cardContainer = document.getElementById('member-card-container');
    if (cardContainer) {
        cardContainer.addEventListener('mousemove', handleCardGlare);
        cardContainer.addEventListener('mouseleave', resetCardGlare);
    }

    // Header Scroll Effect
    const handleHeaderScroll = () => {
        const header = document.getElementById('home-header');
        if (header) {
            if (window.scrollY > 10) {
                header.classList.replace('bg-gray-50', 'bg-white');
                header.classList.add('shadow-sm');
            } else {
                header.classList.replace('bg-white', 'bg-gray-50');
                header.classList.remove('shadow-sm');
            }
        }
    };
    window.addEventListener('scroll', handleHeaderScroll);
    handleHeaderScroll(); // Initial check
    
    updateGreeting();
    updatePrivacyUI();
});

function updateGreeting() {
    const hour = new Date().getHours();
    const greetingEl = document.getElementById('greeting-text');
    if (!greetingEl) return;

    let text = 'Selamat Pagi,';
    let icon = '<i class="bi bi-sunrise text-yellow-500"></i>';

    if (hour >= 10 && hour < 15) { text = 'Selamat Siang,'; icon = '<i class="bi bi-sun text-orange-500"></i>'; }
    else if (hour >= 15 && hour < 18) { text = 'Selamat Sore,'; icon = '<i class="bi bi-sunset text-orange-400"></i>'; }
    else if (hour >= 18) { text = 'Selamat Malam,'; icon = '<i class="bi bi-moon-stars text-indigo-500"></i>'; }

    greetingEl.innerHTML = `${icon} ${text}`;
}

function handleCardGlare(e) {
    const card = e.currentTarget;
    const rect = card.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    // Hitung posisi mouse dalam persen
    const xPercent = (x / rect.width) * 100;
    const yPercent = (y / rect.height) * 100;

    const glares = document.querySelectorAll('.card-glare');
    glares.forEach(glare => {
        glare.style.background = `radial-gradient(circle at ${xPercent}% ${yPercent}%, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0) 80%)`;
        glare.style.opacity = '1';
    });
}

function resetCardGlare() {
    document.querySelectorAll('.card-glare').forEach(glare => glare.style.opacity = '0');
}

function formatRupiah(val) {
    if (window.isPrivacyMode) return 'Rp ••••••';
    const num = parseFloat(val);
    if (isNaN(num)) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num);
}

function formatDate(dateString) {
    const options = { day: 'numeric', month: 'short', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
}

function toggleCardFlip() {
    const inner = document.getElementById('member-card-inner');
    if (inner) {
        inner.classList.toggle('rotate-y-180');
    }
}

function togglePrivacyMode() {
    window.isPrivacyMode = !window.isPrivacyMode;
    localStorage.setItem('privacy_mode', window.isPrivacyMode);
    
    updatePrivacyUI();
    
    // Refresh data pada tab yang aktif agar format rupiah diperbarui
    loadSummary();
    if (!document.getElementById('tab-simpanan').classList.contains('hidden')) loadSimpananHistory();
    if (!document.getElementById('tab-pinjaman').classList.contains('hidden')) loadPinjamanList();
    if (!document.getElementById('tab-belanja').classList.contains('hidden')) loadShoppingHistory();
}

function updatePrivacyUI() {
    const btn = document.getElementById('btn-privacy-mode');
    if (btn) {
        const icon = btn.querySelector('i');
        if (window.isPrivacyMode) {
            icon.className = 'bi bi-eye-slash-fill';
            btn.classList.replace('text-gray-400', 'text-blue-600');
            btn.classList.replace('bg-white', 'bg-blue-50');
        } else {
            icon.className = 'bi bi-eye';
            btn.classList.replace('text-blue-600', 'text-gray-400');
            btn.classList.replace('bg-blue-50', 'bg-white');
        }
    }
}

function getBadgeHtml(level) {
    const levels = {
        'Bronze': { text: 'Bronze', class: 'bg-amber-700/80 text-white', icon: 'bi-award' },
        'Silver': { text: 'Silver', class: 'bg-slate-400 text-white', icon: 'bi-award-fill' },
        'Gold': { text: 'Gold', class: 'bg-yellow-400 text-yellow-900', icon: 'bi-trophy-fill' },
        'Platinum': { text: 'Platinum', class: 'bg-gradient-to-r from-sky-200 to-blue-300 text-blue-900', icon: 'bi-gem' }
    };
    const config = levels[level] || levels['Bronze'];
    return `<span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full ${config.class} flex items-center gap-1"><i class="bi ${config.icon}"></i> ${config.text}</span>`;
}

function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    // Show selected tab
    const tab = document.getElementById('tab-' + tabName);
    if (tab) {
        tab.classList.remove('hidden');
    }
    
    // Update nav styles
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    const navItem = document.getElementById('nav-' + tabName);
    if (navItem) {
        navItem.classList.add('active');
    }

    // Reset scroll position to top for better UX
    window.scrollTo(0, 0);

    // Load data based on tab
    if(tabName === 'simpanan') loadSimpananHistory();
    if(tabName === 'pinjaman') loadPinjamanList();
    if(tabName === 'simulasi') loadLoanTypes();
    if(tabName === 'belanja') {
        document.getElementById('search-item-input').focus();
        loadShoppingHistory();
    }
}

async function loadSummary() {
    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=summary`);
        const json = await res.json();
        if(json.success) {
            window.memberDashboardData = json.data;
            
            const memberNameEl = document.getElementById('member-name');
            if (memberNameEl) memberNameEl.textContent = json.data.nama;
            
            // Update Financial Pulse
            if (json.data.monthly_stats) {
                const savedEl = document.getElementById('pulse-saved');
                const spentEl = document.getElementById('pulse-spent');
                if(savedEl) savedEl.textContent = formatRupiah(json.data.monthly_stats.saved);
                if(spentEl) spentEl.textContent = formatRupiah(json.data.monthly_stats.spent);
            }

            const homeAvatarEl = document.getElementById('home-avatar');
            if (homeAvatarEl && json.data.nama) {
                homeAvatarEl.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(json.data.nama)}&background=random&size=96`;
            }

            const profileNameEl = document.getElementById('profile-name-display');
            if (profileNameEl) profileNameEl.textContent = json.data.nama;

            const profileAvatarEl = document.getElementById('profile-avatar');
            if (profileAvatarEl && json.data.nama) {
                profileAvatarEl.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(json.data.nama)}&background=random&size=128`;
            }

            const level = json.data.level || 'Bronze';
            const badgeHtml = getBadgeHtml(level);

            // Update Warna Kartu Digital (Front & Back)
            ['member-card-front', 'member-card-back'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.classList.remove('from-slate-800', 'to-slate-900', 'from-slate-400', 'to-slate-600', 'from-yellow-600', 'to-yellow-800', 'from-cyan-700', 'to-blue-900');
                    
                    if (level === 'Silver') el.classList.add('from-slate-400', 'to-slate-600');
                    else if (level === 'Gold') el.classList.add('from-yellow-600', 'to-yellow-800');
                    else if (level === 'Platinum') el.classList.add('from-cyan-700', 'to-blue-900');
                    else el.classList.add('from-slate-800', 'to-slate-900');
                }
            });

            // Cek kenaikan level
            const prevLevel = localStorage.getItem('member_level');
            const levelOrder = { 'Bronze': 1, 'Silver': 2, 'Gold': 3, 'Platinum': 4 };
            
            if (prevLevel && levelOrder[level] > levelOrder[prevLevel]) {
                Swal.fire({
                    title: 'Selamat! Anda Naik Level!',
                    text: `Anda telah mencapai level ${level}. Terus tingkatkan poin Anda!`,
                    icon: 'success',
                    confirmButtonText: 'Mantap!',
                    backdrop: `rgba(0,0,123,0.4) url("${basePath}/assets/img/confetti.gif") left top no-repeat`
                });
            }
            
            localStorage.setItem('member_level', level);
            
            const headerBadgeEl = document.getElementById('header-level-badge');
            if (headerBadgeEl) headerBadgeEl.innerHTML = badgeHtml;
            
            const profileBadgeEl = document.getElementById('profile-level-badge');
            if (profileBadgeEl) profileBadgeEl.innerHTML = badgeHtml;

            const cardBadgeEl = document.getElementById('card-level-badge');
            if (cardBadgeEl) cardBadgeEl.innerHTML = badgeHtml;

            const points = json.data.points || 0;
            const homePointsEl = document.getElementById('home-points');
            if (homePointsEl) homePointsEl.textContent = points;

            const profilePointsEl = document.getElementById('profile-points');
            if (profilePointsEl) profilePointsEl.textContent = `${points} Poin`;
            
            // Update Progress Level
            const progressTextEl = document.getElementById('progress-level-percent');
            const progressBarEl = document.getElementById('progress-level-bar');
            
            if (progressTextEl && progressBarEl) {
                let nextLevelPoints = 500; // Default next level (Silver)
                let currentLevelBase = 0;

                if (points >= 3000) {
                    nextLevelPoints = 10000; // Max level cap (example)
                    currentLevelBase = 3000;
                } else if (points >= 1500) {
                    nextLevelPoints = 3000; // Next: Platinum
                    currentLevelBase = 1500;
                } else if (points >= 500) {
                    nextLevelPoints = 1500; // Next: Gold
                    currentLevelBase = 500;
                }

                // Calculate percentage relative to current level bracket
                const pointsInLevel = points - currentLevelBase;
                const pointsNeeded = nextLevelPoints - currentLevelBase;
                const percent = Math.min(100, Math.max(0, (pointsInLevel / pointsNeeded) * 100));

                progressTextEl.textContent = `${Math.round(percent)}%`;
                progressBarEl.style.width = `${percent}%`;
            }

            document.getElementById('profile-no-display').textContent = json.data.nomor_anggota;
            
            // Update Back Info
            const sinceBack = document.getElementById('card-since-back');
            if(sinceBack) sinceBack.textContent = formatDate(json.data.tanggal_daftar);
            
            // Generate pseudo CVV based on member ID (just for visual)
            const cvvEl = document.getElementById('card-cvv');
            if(cvvEl) {
                const idStr = String(json.data.nomor_anggota).replace(/\D/g, '');
                cvvEl.textContent = idStr.substring(idStr.length - 3).padStart(3, '0');
            }

            const simpananData = json.data.simpanan_per_jenis || [];
            let totalSimpanan = 0;

            // Reset tampilan saldo ke 0 sebelum diisi ulang
            const pokokEl = document.getElementById('simpanan-pokok');
            if (pokokEl) pokokEl.textContent = formatRupiah(0);
            
            const wajibEl = document.getElementById('simpanan-wajib');
            if (wajibEl) wajibEl.textContent = formatRupiah(0);
            
            const sukarelaEl = document.getElementById('simpanan-sukarela');
            if (sukarelaEl) sukarelaEl.textContent = formatRupiah(0);

            simpananData.forEach(s => {
                totalSimpanan += parseFloat(s.saldo) || 0;
                const el = document.getElementById(`simpanan-${s.tipe}`);
                if (el) el.textContent = formatRupiah(s.saldo);
            });
            
            const totalSimpananEl = document.getElementById('total-simpanan');
            if (totalSimpananEl) totalSimpananEl.textContent = formatRupiah(totalSimpanan);

            // Pre-fill Zakat Calculator
            const zakatInput = document.getElementById('zakat_total');
            if(zakatInput) zakatInput.value = totalSimpanan;

            const sisaPinjamanEl = document.getElementById('sisa-pinjaman');
            if (sisaPinjamanEl) sisaPinjamanEl.textContent = formatRupiah(json.data.pinjaman);

            // Handle Notifications
            const notifContainer = document.getElementById('dashboard-notifications');
            if (notifContainer && json.data.upcoming_payments && json.data.upcoming_payments.length > 0) {
                notifContainer.classList.remove('hidden');
                notifContainer.innerHTML = json.data.upcoming_payments.map(p => {
                    const dueDate = new Date(p.tanggal_jatuh_tempo);
                    const today = new Date();
                    today.setHours(0,0,0,0);
                    dueDate.setHours(0,0,0,0);
                    
                    const diffTime = dueDate - today;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    let msg = '';
                    let bgClass = 'bg-yellow-50 border-yellow-200 text-yellow-800';
                    let icon = 'bi-exclamation-circle';

                    if (diffDays < 0) {
                        msg = `Telat ${Math.abs(diffDays)} hari`;
                        bgClass = 'bg-red-50 border-red-200 text-red-800';
                        icon = 'bi-exclamation-triangle-fill';
                    } else if (diffDays === 0) {
                        msg = 'Jatuh tempo HARI INI';
                        bgClass = 'bg-red-50 border-red-200 text-red-800';
                    } else {
                        msg = `Jatuh tempo dalam ${diffDays} hari`;
                    }

                    return `
                        <div class="${bgClass} border p-3 rounded-lg flex items-start gap-3 shadow-sm relative group">
                            <i class="bi ${icon} text-xl mt-0.5"></i>
                            <div class="flex-1">
                                <p class="font-bold text-sm">${msg}</p>
                                <p class="text-xs mt-1">
                                    Angsuran Ke-${p.angsuran_ke} (${p.nomor_pinjaman})<br>
                                    Tagihan: <strong>${formatRupiah(p.sisa_tagihan)}</strong>
                                </p>
                                <div class="flex gap-2 mt-2">
                                    <button onclick="showLoanDetail(${p.pinjaman_id})" class="text-xs font-bold underline hover:opacity-80">Lihat Detail</button>
                                    <button onclick="payInstallment(${p.angsuran_id}, ${p.sisa_tagihan})" class="text-xs font-bold bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700">Bayar Sekarang</button>
                                </div>
                            </div>
                            <button onclick="this.closest('.relative').remove()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 p-1">
                                <i class="bi bi-x text-lg"></i>
                            </button>
                        </div>
                    `;
                }).join('');
            } else if (notifContainer) {
                notifContainer.classList.add('hidden');
                notifContainer.innerHTML = '';
            }

            // Handle News
            const newsContainer = document.getElementById('news-container');
            const newsList = document.getElementById('news-list');
            if (newsContainer && newsList && json.data.news && json.data.news.length > 0) {
                newsContainer.classList.remove('hidden');
                newsList.innerHTML = json.data.news.map(n => `
                    <div class="min-w-[240px] bg-white p-3 rounded-xl border border-gray-100 shadow-sm">
                        <p class="font-bold text-gray-800 text-sm mb-1 truncate">${n.judul}</p>
                        <p class="text-xs text-gray-500 line-clamp-2">${n.isi}</p>
                        <p class="text-[10px] text-gray-400 mt-2 text-right">${formatDate(n.tanggal_posting)}</p>
                    </div>
                `).join('');
            }

            // Handle Targets
            const targetList = document.getElementById('target-list');
            if (targetList && json.data.targets && json.data.targets.length > 0) {
                targetList.innerHTML = json.data.targets.map(t => {
                    const percent = Math.min(100, (t.nominal_terkumpul / t.nominal_target) * 100);
                    return `
                        <div class="bg-white p-3 rounded-xl border border-gray-100 shadow-sm relative group">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-semibold text-gray-700">${t.nama_target}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500">${Math.round(percent)}%</span>
                                    <button onclick="deleteTarget(${t.id})" class="text-gray-400 hover:text-red-500 transition"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: ${percent}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>Terkumpul: ${formatRupiah(t.nominal_terkumpul)}</span>
                                <span>Target: ${formatRupiah(t.nominal_target)}</span>
                            </div>
                        </div>
                    `;
                }).join('');
            } else if (targetList) {
                targetList.innerHTML = '<p class="text-center text-gray-400 text-xs py-2">Belum ada target.</p>';
            }

            // Load financial health after summary is complete
            if (typeof loadFinancialHealth === 'function') loadFinancialHealth();
        }
        return json; // Return result for await
    } catch(e) { console.error(e); }
}

async function payInstallment(angsuranId, amount) {
    const { value: password } = await Swal.fire({
        title: 'Konfirmasi Pembayaran',
        html: `Anda akan membayar tagihan sebesar <b>${formatRupiah(amount)}</b>.<br>Saldo Simpanan Utama akan dipotong.`,
        input: 'password',
        inputLabel: 'Masukkan Password Anda',
        inputPlaceholder: 'Password',
        showCancelButton: true,
        confirmButtonText: 'Bayar',
        cancelButtonText: 'Batal',
        inputValidator: (value) => {
            if (!value) {
                return 'Password diperlukan!'
            }
        }
    });

    if (password) {
        try {
            const formData = new FormData();
            formData.append('angsuran_id', angsuranId);
            formData.append('password', password);

            const response = await fetch(`${basePath}/api/member/dashboard?action=pay_installment`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                Swal.fire('Berhasil!', result.message, 'success');
                loadSummary(); // Refresh dashboard
            } else {
                Swal.fire('Gagal!', result.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error!', 'Terjadi kesalahan jaringan.', 'error');
        }
    }
}

async function loadRecentHistory() {
    const container = document.getElementById('recent-history');
    if (container) {
        container.innerHTML = `
            <div class="bg-white p-3 rounded-lg shadow-sm flex justify-between items-center border border-gray-100 animate-pulse">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-200"></div>
                    <div>
                        <div class="h-4 w-24 bg-gray-200 rounded mb-1.5"></div>
                        <div class="h-3 w-16 bg-gray-200 rounded"></div>
                    </div>
                </div>
                <div class="h-5 w-20 bg-gray-200 rounded"></div>
            </div>
            <div class="bg-white p-3 rounded-lg shadow-sm flex justify-between items-center border border-gray-100 animate-pulse">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-200"></div>
                    <div>
                        <div class="h-4 w-24 bg-gray-200 rounded mb-1.5"></div>
                        <div class="h-3 w-16 bg-gray-200 rounded"></div>
                    </div>
                </div>
                <div class="h-5 w-20 bg-gray-200 rounded"></div>
            </div>
        `;
    }

    try {
        const res = await fetch(`${basePath}/api/member/dashboard?action=history_simpanan`);
        const json = await res.json();
        
        if(json.success && json.data.length > 0 && container) {
            container.innerHTML = json.data.slice(0, 3).map(item => `
                <div class="bg-white p-3 rounded-lg shadow-sm flex justify-between items-center border border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full ${item.jenis_transaksi === 'setor' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'} flex items-center justify-center">
                            <i class="bi ${item.jenis_transaksi === 'setor' ? 'bi-arrow-down' : 'bi-arrow-up'} text-lg"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800 text-sm">${item.jenis_simpanan}</p>
                            <p class="text-xs text-gray-500">${formatDate(item.tanggal)}</p>
                        </div>
                    </div>
                    <span class="font-bold text-sm ${item.jenis_transaksi === 'setor' ? 'text-green-600' : 'text-red-600'}">
                        ${item.jenis_transaksi === 'setor' ? '+' : '-'} ${formatRupiah(item.jumlah)}
                    </span>
                </div>
            `).join('');
        } else if (container) {
            container.innerHTML = '<p class="text-center text-gray-400 text-xs py-4">Belum ada riwayat transaksi.</p>';
        }
    } catch(e) { console.error(e); }
}
