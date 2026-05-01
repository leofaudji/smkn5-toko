/**
 * stok_opname.js — Multi-User Session Mode
 * Fitur: buat sesi, auto-save per item, polling progress live, finalisasi
 */
function initStokOpnamePage() {
    // ── Guard ──────────────────────────────────────────────────────
    if (!document.getElementById('so-no-session')) return;

    // ── State ──────────────────────────────────────────────────────
    let activeSession      = null;   // data sesi aktif dari server
    let pollingTimer       = null;   // setInterval handle
    let searchDebounce     = null;
    let saveTimers         = {};     // { item_id: timerHandle }
    const API_URL          = `${basePath}/api/stok-opname`;

    // ── DOM refs ───────────────────────────────────────────────────
    const noSessionEl    = document.getElementById('so-no-session');
    const activeEl       = document.getElementById('so-active-session');
    const activeBadge    = document.getElementById('sessionActiveBadge');
    const tableBody      = document.getElementById('itemsTableBody');
    const searchInput    = document.getElementById('searchInput');
    const filterBelum    = document.getElementById('filterBelum');
    const statTotal      = document.getElementById('statTotal');
    const statSudah      = document.getElementById('statSudah');
    const statBelum      = document.getElementById('statBelum');
    const progressBar    = document.getElementById('progressBar');
    const petugasList    = document.getElementById('petugasList');
    const supActions     = document.getElementById('supervisorActions');
    const tableFooter    = document.getElementById('tableFooterInfo');

    // ── Helpers ────────────────────────────────────────────────────
    const fmtDate = (d) => {
        if (!d) return '–';
        const parts = d.split('-');
        return parts.length === 3 ? `${parts[2]}-${parts[1]}-${parts[0]}` : d;
    };

    const colorSelisih = (v) => {
        if (v < 0) return 'text-red-600 dark:text-red-400';
        if (v > 0) return 'text-emerald-600 dark:text-emerald-400';
        return 'text-gray-400 dark:text-gray-500';
    };

    // ── Identifikasi user yang sedang login ──────────────────────
    // Baca dari variabel global yang di-inject header.php (window scope)
    function getCurrentUserId() {
        return (typeof window.currentUserId !== 'undefined') ? window.currentUserId : 0;
    }

    // ================================================================
    //  INIT — cek sesi aktif
    // ================================================================
    async function init() {
        await loadAdjustmentAccounts();
        await checkActiveSession();
        await loadSessionHistory();
    }

    // ── Muat akun penyeimbang ──────────────────────────────────────
    async function loadAdjustmentAccounts() {
        try {
            const res  = await fetch(`${basePath}/api/stok?action=get_adjustment_accounts`);
            const data = await res.json();
            const sel  = document.getElementById('cs_adj_account_id');
            if (data.status === 'success' && sel) {
                sel.innerHTML = '<option value="">-- Pilih Akun --</option>' +
                    data.data.map(a => `<option value="${a.id}">${a.kode_akun} - ${a.nama_akun}</option>`).join('');
            }
        } catch (e) {
            console.warn('Gagal memuat akun:', e);
        }
    }

    // ── Cek sesi aktif ────────────────────────────────────────────
    async function checkActiveSession() {
        try {
            const res  = await fetch(`${API_URL}?action=get_active_session`);
            const data = await res.json();
            if (data.status === 'success' && data.data) {
                activeSession = data.data;
                showActiveMode();
            } else {
                showNoSessionMode();
            }
        } catch (e) {
            showNoSessionMode();
        }
    }

    // ================================================================
    //  MODE SWITCHING
    // ================================================================
    function showNoSessionMode() {
        activeSession = null;
        stopPolling();
        noSessionEl.classList.remove('hidden');
        activeEl.classList.add('hidden');
        activeBadge.classList.add('hidden');
    }

    function showActiveMode() {
        noSessionEl.classList.add('hidden');
        activeEl.classList.remove('hidden');
        activeBadge.classList.remove('hidden');

        // Isi banner info sesi
        document.getElementById('bannerKeterangan').textContent = activeSession.keterangan;
        document.getElementById('bannerMeta').textContent =
            `Tanggal: ${fmtDate(activeSession.tanggal)} · Dibuat oleh: ${activeSession.created_by_name} · Akun: ${activeSession.kode_akun} - ${activeSession.nama_akun}`;

        // Tampilkan tombol supervisor jika user adalah pembuat sesi atau admin
        const _uid = window.currentUserId || 0;
        const isSupervisor = (_uid && parseInt(activeSession.created_by, 10) === _uid) || (typeof userRole !== 'undefined' && userRole === 'admin');
        if (isSupervisor) {
            supActions.classList.remove('hidden');
        } else {
            supActions.classList.add('hidden');
        }

        // Init flatpickr untuk filter (jika ada)
        if (searchInput) {
            searchInput.value = '';
        }

        loadItems();
        loadProgress();
        startPolling();
    }

    // ================================================================
    //  TABEL BARANG
    // ================================================================
    async function loadItems() {
        if (!activeSession) return;
        const search = searchInput ? searchInput.value : '';
        const filter = filterBelum && filterBelum.checked ? 'belum' : '';

        tableBody.innerHTML = `<tr><td colspan="7" class="text-center p-8">
            <div class="animate-spin rounded-full h-7 w-7 border-b-2 border-primary mx-auto"></div>
        </td></tr>`;

        try {
            const params = new URLSearchParams({
                action: 'get_session_items',
                session_id: activeSession.id,
                search, filter
            });
            const res  = await fetch(`${API_URL}?${params}`);
            const data = await res.json();

            if (data.status !== 'success') throw new Error(data.message);

            const items = data.data;
            if (!items.length) {
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-gray-400 dark:text-gray-500 text-sm">
                    <i class="bi bi-inbox text-2xl block mb-2"></i>Tidak ada barang yang sesuai filter.
                </td></tr>`;
                if (tableFooter) tableFooter.textContent = '';
                return;
            }

            tableBody.innerHTML = items.map((item, idx) => {
                const stokSistem = parseInt(item.stok_sistem ?? 0, 10);
                const stokFisik  = item.stok_fisik !== null ? parseInt(item.stok_fisik, 10) : null;
                const selisih    = stokFisik !== null ? stokFisik - stokSistem : null;
                const isDone     = stokFisik !== null;
                const rowBg      = isDone
                    ? 'bg-emerald-50/40 dark:bg-emerald-900/10'
                    : 'hover:bg-gray-50 dark:hover:bg-gray-700/30';

                const selisihHtml = selisih !== null
                    ? `<span class="font-bold ${colorSelisih(selisih)}">${selisih > 0 ? '+' : ''}${selisih}</span>`
                    : `<span class="text-gray-300 dark:text-gray-600">–</span>`;

                const byHtml = isDone
                    ? `<span class="inline-flex items-center gap-1 text-xs bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full px-2 py-0.5 font-medium">
                          <i class="bi bi-check2"></i>${escapeHtml(item.petugas_nama || 'Saya')}
                       </span>`
                    : `<span class="text-xs text-gray-300 dark:text-gray-600">–</span>`;

                return `
                <tr data-item-id="${item.id}" data-stok-sistem="${stokSistem}" class="${rowBg} transition-colors">
                    <td class="px-4 py-2 text-xs text-gray-400">${idx + 1}</td>
                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-medium">${escapeHtml(item.nama_barang)}</td>
                    <td class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">${escapeHtml(item.sku || '–')}</td>
                    <td class="px-4 py-2 text-sm text-right text-gray-700 dark:text-gray-300">${stokSistem}</td>
                    <td class="px-4 py-2">
                        <div class="relative flex items-center justify-center gap-1.5">
                            <input type="number" min="0"
                                class="block w-24 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm text-center physical-stock-input"
                                value="${stokFisik !== null ? stokFisik : ''}"
                                placeholder="${stokSistem}"
                                data-item-id="${item.id}"
                                data-stok-sistem="${stokSistem}">
                            <span class="save-status text-xs w-5 text-center" data-item-id="${item.id}"></span>
                        </div>
                    </td>
                    <td class="px-4 py-2 text-sm text-right difference-cell">${selisihHtml}</td>
                    <td class="px-4 py-2 text-center by-cell">${byHtml}</td>
                </tr>`;
            }).join('');

            // Footer info
            const s = data.summary;
            if (tableFooter) {
                tableFooter.textContent = `Menampilkan ${items.length} dari ${s.total} barang | Sudah dihitung: ${s.sudah_dihitung} | Belum: ${s.total - s.sudah_dihitung}`;
            }

        } catch (e) {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-red-500 py-6 text-sm">Gagal memuat data: ${e.message}</td></tr>`;
        }
    }

    // ── Input handler: real-time selisih + auto-save ───────────────
    tableBody.addEventListener('input', function (e) {
        if (!e.target.classList.contains('physical-stock-input')) return;
        const input      = e.target;
        const itemId     = input.dataset.itemId;
        const stokSistem = parseInt(input.dataset.stokSistem, 10);
        const stokFisik  = input.value !== '' ? parseInt(input.value, 10) : null;

        // Update selisih cell
        const row    = input.closest('tr');
        const diffEl = row.querySelector('.difference-cell');
        if (stokFisik !== null && !isNaN(stokFisik)) {
            const selisih = stokFisik - stokSistem;
            diffEl.innerHTML = `<span class="font-bold ${colorSelisih(selisih)}">${selisih > 0 ? '+' : ''}${selisih}</span>`;
        } else {
            diffEl.innerHTML = `<span class="text-gray-300 dark:text-gray-600">–</span>`;
        }

        // Auto-save dengan debounce 800ms
        const statusEl = document.querySelector(`.save-status[data-item-id="${itemId}"]`);
        if (statusEl) statusEl.innerHTML = `<i class="bi bi-three-dots text-gray-400 animate-pulse"></i>`;

        clearTimeout(saveTimers[itemId]);
        saveTimers[itemId] = setTimeout(() => saveDraftItem(itemId, stokFisik, statusEl, row), 800);
    });

    // ── Auto-save ke API ──────────────────────────────────────────
    async function saveDraftItem(itemId, stokFisik, statusEl, row) {
        if (!activeSession) return;
        if (statusEl) statusEl.innerHTML = `<svg class="animate-spin h-3.5 w-3.5 text-primary mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>`;

        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save_draft_item',
                    session_id: activeSession.id,
                    item_id: parseInt(itemId, 10),
                    stok_fisik: stokFisik
                })
            });
            const data = await res.json();
            if (data.status === 'success') {
                if (statusEl) statusEl.innerHTML = `<i class="bi bi-check2 text-emerald-500"></i>`;
                // Update row styling
                if (row && stokFisik !== null) {
                    row.classList.add('bg-emerald-50/40', 'dark:bg-emerald-900/10');
                    row.classList.remove('hover:bg-gray-50', 'dark:hover:bg-gray-700/30');
                }
            } else {
                throw new Error(data.message);
            }
        } catch (err) {
            if (statusEl) statusEl.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-red-500" title="${err.message}"></i>`;
        }
    }

    // ================================================================
    //  POLLING PROGRESS
    // ================================================================
    function startPolling() {
        stopPolling();
        pollingTimer = setInterval(loadProgress, 10000);
    }

    function stopPolling() {
        if (pollingTimer) { clearInterval(pollingTimer); pollingTimer = null; }
    }

    async function loadProgress() {
        if (!activeSession) return;
        try {
            const res  = await fetch(`${API_URL}?action=get_session_progress&session_id=${activeSession.id}`);
            const data = await res.json();
            if (data.status !== 'success') return;

            const total  = parseInt(data.summary.total, 10);
            const sudah  = parseInt(data.summary.sudah, 10);
            const belum  = total - sudah;
            const pct    = total > 0 ? Math.round((sudah / total) * 100) : 0;

            if (statTotal) statTotal.textContent = total;
            if (statSudah) statSudah.textContent = sudah;
            if (statBelum) statBelum.textContent = belum;
            if (progressBar) progressBar.style.width = pct + '%';

            // Daftar petugas
            if (petugasList) {
                if (data.petugas && data.petugas.length > 0) {
                    petugasList.innerHTML = data.petugas.map(p => `
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 rounded-full text-xs font-medium border border-primary-100 dark:border-primary-800/40">
                            <i class="bi bi-person-fill"></i>
                            ${escapeHtml(p.nama_lengkap || p.username)}
                            <span class="ml-0.5 bg-primary-100 dark:bg-primary-800/50 text-primary-800 dark:text-primary-200 rounded-full px-1.5 py-0.5 text-[10px] font-bold">${p.jumlah_item}</span>
                        </span>`).join('');
                } else {
                    petugasList.innerHTML = `<span class="text-xs text-gray-400 dark:text-gray-500 italic">Belum ada petugas yang mengisi...</span>`;
                }
            }
        } catch (e) {
            console.warn('Polling error:', e);
        }
    }

    // ================================================================
    //  RIWAYAT SESI
    // ================================================================
    async function loadSessionHistory() {
        const body = document.getElementById('sessionHistoryBody');
        if (!body) return;
        try {
            const res  = await fetch(`${API_URL}?action=get_session_history`);
            const data = await res.json();
            if (data.status !== 'success' || !data.data.length) {
                body.innerHTML = `<tr><td colspan="5" class="text-center py-6 text-gray-400 dark:text-gray-500 text-sm">Belum ada riwayat sesi.</td></tr>`;
                return;
            }
            body.innerHTML = data.data.map(s => {
                const badge = s.status === 'aktif'
                    ? `<span class="px-2 py-0.5 text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full">Aktif</span>`
                    : `<span class="px-2 py-0.5 text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full">Selesai</span>`;
                return `<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">${fmtDate(s.tanggal)}</td>
                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-medium">${escapeHtml(s.keterangan)}</td>
                    <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">${escapeHtml(s.created_by_name || '–')}</td>
                    <td class="px-4 py-2">${badge}</td>
                    <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">${s.finalized_by_name ? escapeHtml(s.finalized_by_name) : '–'}</td>
                </tr>`;
            }).join('');
        } catch (e) {
            body.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-red-500 text-sm">Gagal memuat riwayat.</td></tr>`;
        }
    }

    // ================================================================
    //  CREATE SESSION
    // ================================================================
    const createForm = document.getElementById('createSessionForm');
    if (createForm) {
        const tanggalPicker = flatpickr('#cs_tanggal', { dateFormat: 'd-m-Y', allowInput: true });
        tanggalPicker.setDate(new Date(), true);

        createForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('createSessionBtn');
            btn.disabled = true;
            btn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Membuka sesi...`;

            const rawDate = document.getElementById('cs_tanggal').value; // dd-mm-yyyy
            const [d, m, y] = rawDate.split('-');
            const isoDate = `${y}-${m}-${d}`;

            try {
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_session',
                        tanggal: isoDate,
                        adj_account_id: document.getElementById('cs_adj_account_id').value,
                        keterangan: document.getElementById('cs_keterangan').value
                    })
                });
                const data = await res.json();
                if (data.status !== 'success') throw new Error(data.message);

                showToast(data.message, 'success');
                await checkActiveSession();
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
                btn.disabled = false;
                btn.innerHTML = `<i class="bi bi-play-circle-fill mr-2"></i> Buka Sesi Stok Opname`;
            }
        });
    }

    // ================================================================
    //  FINALISASI SESI
    // ================================================================
    const finalizeBtn = document.getElementById('finalizeBtn');
    if (finalizeBtn) {
        finalizeBtn.addEventListener('click', async () => {
            if (!activeSession) return;

            // Opsi C: cek progress dulu sebelum konfirmasi
            finalizeBtn.disabled = true;
            finalizeBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Memeriksa...`;

            let total = 0, sudah = 0, belum = 0;
            try {
                const pRes  = await fetch(`${API_URL}?action=get_session_progress&session_id=${activeSession.id}`);
                const pData = await pRes.json();
                if (pData.status === 'success') {
                    total = parseInt(pData.summary.total, 10);
                    sudah = parseInt(pData.summary.sudah,  10);
                    belum = total - sudah;
                }
            } catch (e) { /* lanjut dengan konfirmasi biasa jika gagal fetch */ }

            finalizeBtn.disabled = false;
            finalizeBtn.innerHTML = `<i class="bi bi-check2-all mr-2"></i> Finalisasi Sesi`;

            // Bangun pesan konfirmasi sesuai kondisi
            const { isConfirmed } = await Swal.fire({
                title: belum > 0 ? 'Perhatian!' : 'Konfirmasi Finalisasi',
                html: belum > 0 
                    ? `<div class="text-left"><p class="mb-2"><b>${belum}</b> dari <b>${total}</b> barang BELUM dihitung.</p><p class="text-sm text-gray-500">Barang yang belum dihitung tidak akan disesuaikan (stok tetap seperti di sistem).</p><p class="mt-4">Lanjutkan finalisasi sekarang?</p></div>`
                    : `<div class="text-left"><p class="mb-2">Semua <b>${total}</b> barang sudah dihitung!</p><p class="text-sm text-gray-500">Finalisasi akan membuat jurnal penyesuaian dan memperbarui stok.</p><p class="mt-4">Yakin ingin finalisasi sesi "<b>${activeSession.keterangan}</b>"?</p></div>`,
                icon: belum > 0 ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Finalisasi!',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#10b981', // emerald-500
                cancelButtonColor: '#6b7280', // gray-500
            });

            if (!isConfirmed) return;

            // Proses finalisasi
            finalizeBtn.disabled = true;
            finalizeBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Memproses...`;

            try {
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'finalize_session', session_id: activeSession.id })
                });
                const data = await res.json();
                if (data.status !== 'success') throw new Error(data.message);

                showToast(data.message, 'success');
                stopPolling();
                setTimeout(async () => {
                    activeSession = null;
                    await loadSessionHistory();
                    showNoSessionMode();
                }, 1200);
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
                finalizeBtn.disabled = false;
                finalizeBtn.innerHTML = `<i class="bi bi-check2-all mr-2"></i> Finalisasi Sesi`;
            }
        });
    }

    // ================================================================
    //  BATALKAN SESI
    // ================================================================
    const cancelBtn = document.getElementById('cancelSessionBtn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', async () => {
            if (!activeSession) return;
            const { isConfirmed } = await Swal.fire({
                title: 'Batalkan Sesi?',
                html: `<div class="text-left"><p>Yakin ingin <b>MEMBATALKAN</b> sesi "<b>${activeSession.keterangan}</b>"?</p><p class="text-sm text-gray-500 mt-2">Semua data draft yang sudah diisi akan dihapus permanen.</p></div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Batalkan!',
                cancelButtonText: 'Kembali',
                confirmButtonColor: '#ef4444', // red-500
                cancelButtonColor: '#6b7280', // gray-500
            });

            if (!isConfirmed) return;

            try {
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'cancel_session', session_id: activeSession.id })
                });
                const data = await res.json();
                if (data.status !== 'success') throw new Error(data.message);
                showToast(data.message, 'success');
                stopPolling();
                activeSession = null;
                await loadSessionHistory();
                showNoSessionMode();
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
            }
        });
    }

    // ================================================================
    //  FILTER & SEARCH
    // ================================================================
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(loadItems, 350);
        });
    }
    if (filterBelum) {
        filterBelum.addEventListener('change', loadItems);
    }

    // ── Cleanup saat SPA navigasi (via popstate atau link click) ──
    const _origNavigate = typeof navigate === 'function' ? navigate : null;
    const _cleanupOnNavigate = () => { stopPolling(); };
    // Bungkus dengan MutationObserver: ketika #so-no-session hilang dari DOM, stop polling
    const _observer = new MutationObserver(() => {
        if (!document.getElementById('so-no-session')) {
            stopPolling();
            _observer.disconnect();
        }
    });
    const _mainContent = document.getElementById('main-content');
    if (_mainContent) _observer.observe(_mainContent, { childList: true });

    // ── escapeHtml helper ─────────────────────────────────────────
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ── Start ─────────────────────────────────────────────────────
    init();
}