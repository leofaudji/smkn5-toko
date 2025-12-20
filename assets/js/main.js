// =================================================================================
// APLIKASI RT - SINGLE PAGE APPLICATION (SPA) CORE
// =================================================================================
/**
 * Displays a toast notification.
 * @param {string} message The message to display.
 * @param {string} type The type of toast: 'success', 'error', or 'info'.
 * @param {string|null} title Optional title for the toast.
 */
function showToast(message, type = 'success', title = null) {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) return;

    const toastId = 'toast-' + Date.now();
    let toastIcon, defaultTitle;

    switch (type) {
        case 'error':
            toastIcon = '<i class="bi bi-x-circle-fill text-danger me-2"></i>';
            defaultTitle = 'Error';
            break;
        case 'info':
            toastIcon = '<i class="bi bi-bell-fill text-info me-2"></i>';
            defaultTitle = 'Notifikasi Baru';
            break;
        case 'success':
        default:
            toastIcon = '<i class="bi bi-check-circle-fill text-success me-2"></i>';
            defaultTitle = 'Sukses';
            break;
    }

    const toastTitle = title || defaultTitle;

    const toastHTML = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                ${toastIcon}
                <strong class="me-auto">${toastTitle}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 8000 });
    toast.show();
    toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
}

/**
 * Formats a number into accounting-style currency string.
 * Negative numbers are shown in red and parentheses.
 * @param {number} value The number to format.
 * @returns {string} The formatted HTML string.
 */
function formatCurrencyAccounting(value) {
    const formatter = new Intl.NumberFormat('id-ID', { 
        style: 'decimal', // Use decimal to avoid currency symbol inside parentheses
        minimumFractionDigits: 0 
    });

    if (value < 0) {
        return `<span class="text-danger">(Rp ${formatter.format(Math.abs(value))})</span>`;
    } else if (value > 0) {
        return `Rp ${formatter.format(value)}`;
    } else {
        return `Rp 0`;
    }
}

/**
 * Updates the active link in the sidebar based on the current URL.
 * @param {string} path The path of the page being navigated to.
 */
function updateActiveSidebarLink(path) {
    const sidebarLinks = document.querySelectorAll('.sidebar-nav .nav-link');
    sidebarLinks.forEach(link => {
        link.classList.remove('active');
        const linkPath = new URL(link.href).pathname;
        const cleanCurrentPath = path.length > 1 ? path.replace(/\/$/, "") : path;
        const cleanLinkPath = linkPath.length > 1 ? linkPath.replace(/\/$/, "") : linkPath;
        if (cleanLinkPath === cleanCurrentPath) {
            // Tandai link yang aktif
            link.classList.add('active');

            // Cek apakah link ini ada di dalam submenu yang collapsible
            const parentCollapse = link.closest('.collapse');
            if (parentCollapse) {
                // Buka collapse-nya
                const bsCollapse = new bootstrap.Collapse(parentCollapse, {
                    toggle: false // Jangan toggle, hanya buka
                });
                bsCollapse.show();

                // Tandai juga menu induknya sebagai aktif
                const parentTrigger = document.querySelector(`a[data-bs-target="#${parentCollapse.id}"]`);
                if (parentTrigger) {
                    parentTrigger.classList.add('active');
                    parentTrigger.setAttribute('aria-expanded', 'true');
                }
            }
        }
    });
}

/**
 * Main navigation function for the SPA.
 * Fetches page content and injects it into the main content area.
 * @param {string} url The URL to navigate to.
 * @param {boolean} pushState Whether to push a new state to the browser history.
 */
async function navigate(url, pushState = true) {
    const mainContent = document.querySelector('.main-content');
    const loadingBar = document.getElementById('spa-loading-bar');
    if (!mainContent) return;

    // --- Start Loading ---
    if (loadingBar) {
        loadingBar.classList.remove('is-finished'); // Reset state
        loadingBar.classList.add('is-loading');
    }

    // 1. Mulai animasi fade-out
    mainContent.classList.add('is-transitioning');

    // 2. Tunggu animasi fade-out selesai (durasi harus cocok dengan CSS)
    await new Promise(resolve => setTimeout(resolve, 200));

    try {
        const response = await fetch(url, {
            headers: {
                'X-SPA-Request': 'true' // Custom header to tell the backend this is an SPA request
            }
        });

        // --- Finish Loading ---
        if (loadingBar) {
            loadingBar.classList.add('is-finished');
        }

        if (!response.ok) {
            throw new Error(`Server responded with status ${response.status}`);
        }

        const html = await response.text();

        if (pushState) {
            history.pushState({ path: url }, '', url);
        }

        // 3. Ganti konten saat tidak terlihat
        mainContent.innerHTML = html;
        updateActiveSidebarLink(new URL(url).pathname);
        
        // 4. Mulai animasi fade-in
        mainContent.classList.remove('is-transitioning');

        runPageScripts(new URL(url).pathname); // Run scripts for the new page

        // Handle hash for scrolling to a specific item
        const hash = new URL(url).hash;
        if (hash) { 
            // Use a small timeout to ensure the element is rendered by the page script
            setTimeout(() => {
                const element = document.querySelector(hash);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Add a temporary highlight effect
                    element.classList.add('highlight-item');
                    setTimeout(() => element.classList.remove('highlight-item'), 3000);
                }
            }, 300); // 300ms delay should be enough
        } 
    } catch (error) {
        console.error('Navigation error:', error);
        let errorMessage = 'Gagal memuat halaman. Silakan coba lagi.';
        if (error.message.includes('403')) {
            errorMessage = 'Akses Ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.';
        } else if (error.message.includes('404')) {
            errorMessage = 'Halaman tidak ditemukan. Halaman yang Anda cari tidak ada atau telah dipindahkan.';
        }
        mainContent.innerHTML = `<div class="alert alert-danger m-3">${errorMessage}</div>`;
        // Tampilkan juga pesan error dengan fade-in
        mainContent.classList.remove('is-transitioning');
    } finally {
        // Hide the loading bar after a short delay to let the 'finished' animation complete
        if (loadingBar) {
            setTimeout(() => {
                loadingBar.classList.remove('is-loading');
                loadingBar.classList.remove('is-finished');
            }, 500); // 500ms delay
        }
    }
}

/**
 * A client-side router to run page-specific JavaScript after content is loaded.
 * @param {string} path The current page's path.
 */
function runPageScripts(path) {
    // Normalisasi path untuk mencocokkan rute, menghapus base path dan query string.
    const cleanPath = path.replace(basePath, '').split('?')[0].replace(/\/$/, "") || '/';

    if (cleanPath === '/dashboard') {
        loadScript(`${basePath}/assets/js/dashboard.js`)
            .then(() => initDashboardPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/transaksi') {
        loadScript(`${basePath}/assets/js/transaksi.js`)
            .then(() => initTransaksiPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/entri-jurnal') {
        loadScript(`${basePath}/assets/js/entri_jurnal.js`)
            .then(() => initEntriJurnalPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/coa') {
        loadScript(`${basePath}/assets/js/coa.js`)
            .then(() => initCoaPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/saldo-awal-neraca') {
        loadScript(`${basePath}/assets/js/saldoawal_neraca.js`)
            .then(() => initSaldoAwalNeracaPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/saldo-awal-lr') {
        loadScript(`${basePath}/assets/js/saldoawal_lr.js`)
            .then(() => initSaldoAwalLRPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/laporan') {
        loadScript(`${basePath}/assets/js/laporan.js`)
            .then(() => initLaporanPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/laporan-harian') {
        loadScript(`${basePath}/assets/js/laporan_harian.js`)
            .then(() => initLaporanHarianPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/laporan-stok') {
        loadScript(`${basePath}/assets/js/laporan_stok.js`)
            .then(() => initLaporanStokPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/buku-besar') {
        loadScript(`${basePath}/assets/js/buku_besar.js`)
            .then(() => initBukuBesarPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/settings') {
        loadScript(`${basePath}/assets/js/settings.js`)
            .then(() => initSettingsPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/my-profile/change-password') {
        loadScript(`${basePath}/assets/js/myprofile.js`)
            .then(() => initMyProfilePage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/daftar-jurnal') {
        loadScript(`${basePath}/assets/js/daftar_jurnal.js`)
            .then(() => initDaftarJurnalPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/konsinyasi') {
        loadScript(`${basePath}/assets/js/konsinyasi.js`)
            .then(() => initKonsinyasiPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/transaksi-berulang') {
        loadScript(`${basePath}/assets/js/transaksi_berulang.js`)
            .then(() => initTransaksiBerulangPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/laporan-laba-ditahan') {
        loadScript(`${basePath}/assets/js/laporan_laba_ditahan.js`)
            .then(() => initLaporanLabaDitahanPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/tutup-buku') {
        loadScript(`${basePath}/assets/js/tutupbuku.js`)
            .then(() => initTutupBukuPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/analisis-rasio') {
        loadScript(`${basePath}/assets/js/analisis_rasio.js`)
            .then(() => initAnalisisRasioPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/activity-log') {
        loadScript(`${basePath}/assets/js/activity_log.js`)
            .then(() => initActivityLogPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/anggaran') {
        loadScript(`${basePath}/assets/js/anggaran.js`)
            .then(() => initAnggaranPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/users') {
        loadScript(`${basePath}/assets/js/users.js`)
            .then(() => initUsersPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/laporan-pertumbuhan-laba') {
        loadScript(`${basePath}/assets/js/laporan_pertumbuhan_laba.js`)
            .then(() => initLaporanPertumbuhanLabaPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/histori-rekonsiliasi') {
        loadScript(`${basePath}/assets/js/histori_rekonsiliasi.js`)
            .then(() => initHistoriRekonsiliasiPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/rekonsiliasi-bank') {
        loadScript(`${basePath}/assets/js/rekonsiliasi_bank.js`)
            .then(() => initRekonsiliasiBankPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/aset-tetap') {
        loadScript(`${basePath}/assets/js/aset_tetap.js`)
            .then(() => initAsetTetapPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/pembelian') {
        loadScript(`${basePath}/assets/js/pembelian.js`)
            .then(() => initPembelianPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/stok') {
        loadScript(`${basePath}/assets/js/stok.js`)
            .then(() => initStokPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/stok-opname') {
        loadScript(`${basePath}/assets/js/stok_opname.js`)
            .then(() => initStokOpnamePage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/laporan-kartu-stok') {
        loadScript(`${basePath}/assets/js/laporan_kartu_stok.js`)
            .then(() => initLaporanKartuStokPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/laporan-persediaan') {
        loadScript(`${basePath}/assets/js/laporan_persediaan.js`)
            .then(() => initLaporanPersediaanPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/laporan-pertumbuhan-persediaan') {
        loadScript(`${basePath}/assets/js/laporan_pertumbuhan_persediaan.js`)
            .then(() => initLaporanPertumbuhanPersediaanPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/laporan-penjualan-item') {
        loadScript(`${basePath}/assets/js/laporan_penjualan_item.js`)
            .then(() => initLaporanPenjualanItemPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/laporan-penjualan') {
        loadScript(`${basePath}/assets/js/laporan_penjualan.js`)
            .then(() => initLaporanPenjualanPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/penjualan') {
        loadScript(`${basePath}/assets/js/penjualan.js`)
            .then(() => initPenjualanPage())
            .catch(err => console.error(err));
    }else if (cleanPath === '/buku-panduan') {
        // Halaman ini statis dan tidak memerlukan inisialisasi JavaScript.
        // Cukup daftarkan agar tidak error dan hentikan eksekusi.
        return; 
    }
}

/**
 * Loads a script dynamically and returns a promise that resolves when it's loaded.
 * @param {string} src The source URL of the script.
 * @returns {Promise<void>}
 */
function loadScript(src) {
    return new Promise((resolve, reject) => {
        // Check if the script is already loaded
        if (document.querySelector(`script[src="${src}"]`)) {
            resolve();
            return;
        }
        const script = document.createElement('script');
        script.src = src;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
        document.body.appendChild(script);
    });
}


// =================================================================================
// PAGE-SPECIFIC INITIALIZATION FUNCTIONS
// =================================================================================

function initKategoriPage() {
    console.log("Halaman Kategori diinisialisasi. (Belum diimplementasikan)");
}

// Deklarasikan variabel modal di luar fungsi untuk mencegah duplikasi listener
let anggaranModalInstance = null;

/**
 * Calculates time since a given date.
 * @param {Date} date The date to compare against.
 * @returns {string} A human-readable string like "5 menit lalu".
 */
function timeSince(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + " tahun lalu";
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + " bulan lalu";
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + " hari lalu";
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + " jam lalu";
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + " menit lalu";
    return "Baru saja";
}

// =================================================================================
// GLOBAL INITIALIZATION
// =================================================================================

document.addEventListener('DOMContentLoaded', function () {
    // --- Sidebar Toggle Logic ---
    const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');

    const toggleSidebar = () => {
        document.body.classList.toggle('sidebar-collapsed');
        // Save the state to localStorage
        const isCollapsed = document.body.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebar-collapsed', isCollapsed);
    };

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', toggleSidebar);
    }

    if (sidebarOverlay) {
        // Di layar kecil, klik pada overlay akan menutup sidebar
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    // --- Theme Switcher ---
    const themeSwitcher = document.getElementById('theme-switcher');
    if (themeSwitcher) {
        const themeIcon = themeSwitcher.querySelector('i');
        const themeText = document.getElementById('theme-switcher-text');

        // Function to set the switcher state
        const setSwitcherState = (theme) => {
            if (theme === 'dark') {
                themeIcon.classList.replace('bi-moon-stars-fill', 'bi-sun-fill');
                themeText.textContent = 'Mode Terang';
            } else {
                themeIcon.classList.replace('bi-sun-fill', 'bi-moon-stars-fill');
                themeText.textContent = 'Mode Gelap';
            }
        };

        // Set initial state based on what's already applied to the body
        const currentTheme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
        setSwitcherState(currentTheme);

        themeSwitcher.addEventListener('click', (e) => {
            e.preventDefault();
            const newTheme = document.body.classList.toggle('dark-mode') ? 'dark' : 'light';
            localStorage.setItem('theme', newTheme);
            setSwitcherState(newTheme);
        });
    }

    // --- Panic Button Logic ---
    const panicButton = document.getElementById('panic-button');
    if (panicButton) {
        let holdTimeout;
        const originalButtonHtml = panicButton.innerHTML;

        const startHold = (e) => {
            e.preventDefault();
            // Prevent action if button is already processing
            if (panicButton.disabled) return;

            panicButton.classList.add('is-holding');

            holdTimeout = setTimeout(async () => {
                panicButton.disabled = true;
                panicButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengirim...`;

                try {
                    const response = await fetch(`${basePath}/api/panic`, { method: 'POST' });
                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.message || 'Server error');
                    }

                    showToast(result.message, 'success');
                    panicButton.classList.remove('btn-danger');
                    panicButton.classList.add('btn-success');
                    panicButton.innerHTML = `<i class="bi bi-check-circle-fill"></i> Terkirim`;

                } catch (error) {
                    // Use error.message if available from the thrown error
                    showToast(error.message || 'Gagal mengirim sinyal darurat.', 'error');
                    panicButton.innerHTML = `<i class="bi bi-x-circle-fill"></i> Gagal`;
                } finally {
                    // Reset button to original state after a few seconds
                    setTimeout(() => {
                        panicButton.classList.remove('is-holding', 'btn-success');
                        panicButton.classList.add('btn-danger');
                        panicButton.innerHTML = originalButtonHtml;
                        panicButton.disabled = false;
                    }, 5000); // Reset after 5 seconds
                }
            }, 3000); // 3 seconds
        };

        const cancelHold = () => {
            if (panicButton.disabled) return;
            clearTimeout(holdTimeout);
            panicButton.classList.remove('is-holding');
        };

        panicButton.addEventListener('mousedown', startHold);
        panicButton.addEventListener('touchstart', startHold, { passive: false });
        panicButton.addEventListener('mouseup', cancelHold);
        panicButton.addEventListener('mouseleave', cancelHold);
        panicButton.addEventListener('touchend', cancelHold);
    }

    // --- Live Clock in Header ---
    const clockElement = document.getElementById('live-clock');
    if (clockElement) {
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        function updateLiveClock() {
            const now = new Date();
            const dayName = days[now.getDay()];
            const day = now.getDate().toString().padStart(2, '0');
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');

            clockElement.textContent = `${dayName}, ${day} ${monthName} ${year} ${hours}:${minutes}:${seconds}`;
        }

        updateLiveClock(); // Initial call
        setInterval(updateLiveClock, 1000); // Update every second
    }

    // --- SPA Navigation Listeners ---
    // Intercept clicks on internal links
    document.body.addEventListener('click', e => {
        const link = e.target.closest('a');
        // Check if it's an internal, navigable link that doesn't open a new tab, trigger a modal/dropdown, or has the 'data-spa-ignore' attribute
        if (link && link.href && link.target !== '_blank' && new URL(link.href).origin === window.location.origin && !link.getAttribute('data-bs-toggle') && link.getAttribute('data-spa-ignore') === null) {
            e.preventDefault();
            if (new URL(link.href).pathname !== window.location.pathname) {
                navigate(link.href);
            }
        }
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', e => {
        if (e.state && e.state.path) {
            navigate(e.state.path, false); // false = don't push a new state
        }
    });

    // --- Initial Page Load ---
    updateActiveSidebarLink(window.location.pathname);
    runPageScripts(window.location.pathname);
});

// --- Global Theme Color Picker Logic ---
function applyThemeColor(color) {
    if (!color) return;
    // Update CSS variables for the entire document
    document.documentElement.style.setProperty('--cf-blue', color);
    // Also update the Bootstrap variable for primary button background
    document.documentElement.style.setProperty('--bs-btn-bg', color);
    document.documentElement.style.setProperty('--bs-btn-border-color', color);
}

document.addEventListener('DOMContentLoaded', function() {
    const savedColor = localStorage.getItem('theme_color');
    const colorPicker = document.getElementById('theme-color-picker');

    if (savedColor) {
        applyThemeColor(savedColor);
        if (colorPicker) {
            colorPicker.value = savedColor;
        }
    }

    if (colorPicker) {
        colorPicker.addEventListener('input', (e) => {
            applyThemeColor(e.target.value);
            localStorage.setItem('theme_color', e.target.value);
        });
    }
});
// --- Recurring Modal Logic (Global) ---
const recurringModalEl = document.getElementById('recurringModal');
const recurringModal = recurringModalEl ? new bootstrap.Modal(recurringModalEl) : null;
const recurringForm = document.getElementById('recurring-form');

function openRecurringModal(type, data, existingTemplate = null) {
    if (!recurringModal || !recurringForm) return;

    recurringForm.reset();
    document.getElementById('recurring-template-type').value = type;
    document.getElementById('recurring-template-data').value = JSON.stringify(data);

    if (existingTemplate) {
        document.getElementById('recurringModalLabel').textContent = 'Edit Jadwal Berulang';
        document.getElementById('recurring-id').value = existingTemplate.id;
        document.getElementById('recurring-name').value = existingTemplate.name;
        document.getElementById('recurring-frequency-interval').value = existingTemplate.frequency_interval;
        document.getElementById('recurring-frequency-unit').value = existingTemplate.frequency_unit;
        document.getElementById('recurring-start-date').value = existingTemplate.start_date;
        document.getElementById('recurring-end-date').value = existingTemplate.end_date || '';
    } else {
        document.getElementById('recurringModalLabel').textContent = 'Atur Jadwal Berulang';
        document.getElementById('recurring-id').value = '';
        document.getElementById('recurring-start-date').valueAsDate = new Date();
    }

    recurringModal.show();
}

document.getElementById('save-recurring-template-btn')?.addEventListener('click', async () => {
    const response = await fetch(`${basePath}/api/recurring`, { method: 'POST', body: new FormData(recurringForm) });
    const result = await response.json();
    showToast(result.message, result.status);
    if (result.status === 'success') recurringModal.hide();
});

/**
 * Initializes the global search functionality.
 */
function initGlobalSearch() {
    const searchModalEl = document.getElementById('globalSearchModal');
    if (!searchModalEl) return;

    const searchInput = document.getElementById('global-search-input');
    const resultsContainer = document.getElementById('global-search-results');
    const spinner = document.getElementById('global-search-spinner');
    const searchModal = bootstrap.Modal.getInstance(searchModalEl) || new bootstrap.Modal(searchModalEl);

    let debounceTimer;

    const performSearch = async () => {
        const term = searchInput.value.trim();

        if (term.length < 3) {
            resultsContainer.innerHTML = '<p class="text-muted text-center">Masukkan minimal 3 karakter untuk mencari.</p>';
            spinner.style.display = 'none';
            return;
        }

        spinner.style.display = 'block';

        try {
            const response = await fetch(`${basePath}/api/global-search?term=${encodeURIComponent(term)}`);
            const result = await response.json();

            resultsContainer.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(item => {
                    const resultItem = `
                        <a href="${basePath}${item.link}" class="search-result-item" data-bs-dismiss="modal">
                            <div class="d-flex align-items-center">
                                <i class="bi ${item.icon} fs-4 me-3 text-primary"></i>
                                <div>
                                    <div class="fw-bold">${item.title}</div>
                                    <small class="text-muted">${item.subtitle}</small>
                                </div>
                                <span class="badge bg-secondary ms-auto">${item.type}</span>
                            </div>
                        </a>
                    `;
                    resultsContainer.insertAdjacentHTML('beforeend', resultItem);
                });
            } else if (result.status === 'success') {
                resultsContainer.innerHTML = `<p class="text-muted text-center">Tidak ada hasil ditemukan untuk "<strong>${term}</strong>".</p>`;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            resultsContainer.innerHTML = `<p class="text-danger text-center">Terjadi kesalahan: ${error.message}</p>`;
        } finally {
            spinner.style.display = 'none';
        }
    };

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        spinner.style.display = 'block';
        debounceTimer = setTimeout(performSearch, 500); // Debounce for 500ms
    });

    resultsContainer.addEventListener('click', (e) => {
        const link = e.target.closest('.search-result-item');
        if (link) {
            e.preventDefault();
            const url = link.href;
            // Tutup modal secara manual
            searchModal.hide();
            // Gunakan fungsi navigate SPA untuk pindah halaman dan menangani hash
            navigate(url);
        }
    });

    searchModalEl.addEventListener('shown.bs.modal', () => {
        searchInput.focus();
    });

    searchModalEl.addEventListener('hidden.bs.modal', () => {
        searchInput.value = '';
        resultsContainer.innerHTML = '<p class="text-muted text-center">Masukkan kata kunci untuk memulai pencarian.</p>';
    });

    // Add keyboard shortcut (Ctrl+K or Cmd+K)
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault(); // Prevent default browser action (e.g., search)
            searchModal.show();
        }
    });
}
/**
 * Renders pagination controls.
 * @param {HTMLElement} container The container element for the pagination.
 * @param {object|null} pagination The pagination object from the API.
 * @param {function} onPageClick The callback function to execute when a page link is clicked.
 */
function renderPagination(container, pagination, onPageClick) {
    if (!container) return;
    container.innerHTML = '';
    if (!pagination || pagination.total_pages <= 1) return;

    const { current_page, total_pages } = pagination;

    const createPageItem = (page, text, isDisabled = false, isActive = false) => {
        const li = document.createElement('li');
        li.className = `page-item ${isDisabled ? 'disabled' : ''} ${isActive ? 'active' : ''}`;
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.dataset.page = page;
        a.innerHTML = text;
        li.appendChild(a);
        return li;
    };

    container.appendChild(createPageItem(current_page - 1, 'Previous', current_page === 1));

    const maxPagesToShow = 5;
    let startPage, endPage;
    if (total_pages <= maxPagesToShow) {
        startPage = 1; endPage = total_pages;
    } else {
        const maxPagesBeforeCurrent = Math.floor(maxPagesToShow / 2);
        const maxPagesAfterCurrent = Math.ceil(maxPagesToShow / 2) - 1;
        if (current_page <= maxPagesBeforeCurrent) { startPage = 1; endPage = maxPagesToShow; } 
        else if (current_page + maxPagesAfterCurrent >= total_pages) { startPage = total_pages - maxPagesToShow + 1; endPage = total_pages; } 
        else { startPage = current_page - maxPagesBeforeCurrent; endPage = current_page + maxPagesAfterCurrent; }
    }

    if (startPage > 1) {
        container.appendChild(createPageItem(1, '1'));
        if (startPage > 2) container.appendChild(createPageItem(0, '...', true));
    }

    for (let i = startPage; i <= endPage; i++) {
        container.appendChild(createPageItem(i, i, false, i === current_page));
    }

    if (endPage < total_pages) {
        if (endPage < total_pages - 1) container.appendChild(createPageItem(0, '...', true));
        container.appendChild(createPageItem(total_pages, total_pages));
    }

    container.appendChild(createPageItem(current_page + 1, 'Next', current_page === total_pages));

    container.addEventListener('click', (e) => {
        e.preventDefault();
        const pageLink = e.target.closest('.page-link');
        if (pageLink && !pageLink.parentElement.classList.contains('disabled')) {
            const page = parseInt(pageLink.dataset.page, 10);
            if (page !== current_page) {
                onPageClick(page);
            }
        }
    });
}

// Initialize global search on every page load
initGlobalSearch();

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
}

/**
 * Formats a number with thousand separators.
 * @param {number} value The number to format.
 * @returns {string} The formatted number string.
 */
function formatNumber(value) {
    if (typeof value !== 'number') return value;
    return new Intl.NumberFormat('id-ID').format(value);
}   

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
}