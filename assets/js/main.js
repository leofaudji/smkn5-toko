// =================================================================================
// APLIKASI KEUANGAN - SINGLE PAGE APPLICATION (SPA) CORE
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
    let toastIcon, defaultTitle, colors;

    switch (type) {
        case 'error':
            colors = {
                bg: 'bg-red-50 dark:bg-red-800/20',
                text: 'text-red-800 dark:text-red-200',
                icon: 'text-red-500',
                border: 'border-red-200 dark:border-red-700'
            };
            toastIcon = '<i class="bi bi-x-circle-fill"></i>';
            defaultTitle = 'Error';
            break;
        case 'info':
            colors = {
                bg: 'bg-blue-50 dark:bg-blue-800/20',
                text: 'text-blue-800 dark:text-blue-200',
                icon: 'text-blue-500',
                border: 'border-blue-200 dark:border-blue-700'
            };
            toastIcon = '<i class="bi bi-bell-fill"></i>';
            defaultTitle = 'Notifikasi';
            break;
        case 'success':
        default:
            colors = {
                bg: 'bg-green-50 dark:bg-green-800/20',
                text: 'text-green-800 dark:text-green-200',
                icon: 'text-green-500',
                border: 'border-green-200 dark:border-green-700'
            };
            toastIcon = '<i class="bi bi-check-circle-fill"></i>';
            defaultTitle = 'Sukses';
            break;
    }

    const toastTitle = title || defaultTitle;

    const toastHTML = `
        <div id="${toastId}" class="max-w-lg w-full ${colors.bg} ${colors.border} shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden transition-transform transform translate-x-full">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0 text-xl ${colors.icon}">
                        ${toastIcon}
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">${toastTitle}</p>
                        <p class="mt-1 text-sm ${colors.text}">${message}</p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button onclick="document.getElementById('${toastId}').remove()" class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    const toastElement = document.getElementById(toastId);
    
    // Animate in
    setTimeout(() => {
        toastElement.classList.remove('translate-x-full');
        toastElement.classList.add('translate-x-0');
    }, 100);

    // Auto-hide
    setTimeout(() => {
        if (toastElement) toastElement.remove();
    }, 8000);
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
    const sidebarLinks = document.querySelectorAll('#sidebar a');
    const cleanCurrentPath = path.length > 1 ? path.replace(/\/$/, "") : path;

    sidebarLinks.forEach(link => {
        const linkPath = new URL(link.href).pathname;
        const cleanLinkPath = linkPath.length > 1 ? linkPath.replace(/\/$/, "") : linkPath;

        // Reset all links first
        link.classList.remove('bg-primary-50', 'dark:bg-gray-700', 'text-primary', 'font-semibold');
        const parentCollapseTrigger = link.closest('[data-controller="collapse"]')?.querySelector('button');
        if (parentCollapseTrigger) {
            parentCollapseTrigger.classList.remove('text-primary', 'font-semibold');
        }

        if (cleanLinkPath === cleanCurrentPath) {
            // Style the active link
            link.classList.add('bg-primary-50', 'dark:bg-gray-700', 'text-primary', 'font-semibold');

            // Check if it's inside a collapsible menu
            const parentCollapseContent = link.closest('.collapse-content');
            if (parentCollapseContent) {
                // Show the content
                parentCollapseContent.classList.remove('hidden');
                
                // Style the trigger button
                const triggerButton = parentCollapseContent.previousElementSibling;
                if (triggerButton) {
                    triggerButton.classList.add('text-primary', 'font-semibold');
                    const icon = triggerButton.querySelector('.bi-chevron-down');
                    if (icon) icon.classList.add('rotate-180');
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
    const mainContent = document.getElementById('main-content');
    const loadingBar = document.getElementById('spa-loading-bar');
    if (!mainContent) return;

    // --- Start Loading (Not implemented in Tailwind version, can be added) ---
    if (loadingBar) {
        loadingBar.classList.remove('is-finished'); // Reset state
        loadingBar.classList.add('is-loading');
    }

    // 1. Mulai animasi fade-out
    mainContent.classList.add('is-transitioning');
    mainContent.style.opacity = '0';
    // 2. Tunggu animasi fade-out selesai (durasi harus cocok dengan CSS)
    await new Promise(resolve => setTimeout(resolve, 200));

    try {
        const response = await fetch(url, {
            headers: {
                'X-SPA-Request': 'true'
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
        const pageTitle = document.querySelector('#main-content .h2, #main-content h1')?.textContent || 'Dashboard';
        document.getElementById('page-title').textContent = pageTitle;
        updateActiveSidebarLink(new URL(url).pathname);
        
        // 4. Mulai animasi fade-in
        mainContent.style.opacity = '1';

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
        mainContent.style.opacity = '1';
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
    } else if (cleanPath === '/saldo-awal') {
        loadScript(`${basePath}/assets/js/saldoawal.js`)
            .then(() => initSaldoAwalPage())
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
    } else if (cleanPath === '/neraca-saldo') {
        loadScript(`${basePath}/assets/js/neraca_saldo.js`)
            .then(() => initNeracaSaldoPage())
            .catch(err => console.error(err));
    } else if (cleanPath === '/roles') {
        loadScript(`${basePath}/assets/js/roles.js`)
            .then(() => initRolesPage())
            .catch(err => console.error(err));
    }else if (cleanPath === '/buku-panduan') {        // Halaman ini statis dan tidak memerlukan inisialisasi JavaScript.
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

/**
 * Calculates time since a given date.
 * @param {Date} date The date to compare against.
 * @returns {string} A human-readable string like "5 menit lalu".
 */
function formatDate(dateString) {
    if (!dateString || dateString.startsWith('0000')) return '';
    try {
        const date = new Date(dateString);
        // Check if the date is valid
        if (isNaN(date.getTime())) {
            return dateString; // Return original string if invalid
        }
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
    } catch (e) {
        return dateString; // Return original string on error
    }
}

function timeSince(date) {
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
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
    // Sidebar logic is now handled by inline `onclick="toggleSidebar()"` in header.php
    // and the `toggleSidebar` function in footer.php

    // --- Theme Switcher ---
    const themeSwitcher = document.getElementById('theme-switcher');
    if (themeSwitcher) {
        const themeText = document.getElementById('theme-switcher-text');
        const htmlEl = document.documentElement;

        // Function to set the switcher state
        const setSwitcherState = (theme) => {
            if (theme === 'dark') {
                themeText.textContent = 'Mode Terang';
            } else {
                themeText.textContent = 'Mode Gelap';
            }
        };

        // Apply saved theme on load
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            htmlEl.classList.toggle('dark', savedTheme === 'dark');
            setSwitcherState(savedTheme);
        } else {
            // Set initial state based on default
            const currentTheme = htmlEl.classList.contains('dark') ? 'dark' : 'light';
            setSwitcherState(currentTheme);
        }

        themeSwitcher.addEventListener('click', (e) => {
            e.preventDefault();
            const isDark = htmlEl.classList.toggle('dark');
            const newTheme = isDark ? 'dark' : 'light';
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
        // Check if it's an internal, navigable link that doesn't open a new tab or has the 'data-spa-ignore' attribute
        if (link && link.href && link.target !== '_blank' && new URL(link.href).origin === window.location.origin && link.getAttribute('data-spa-ignore') === null) {
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

    // --- Initialize Global Components ---
    initGlobalSearch();
    initRecurringModal();
});

// --- Global Theme Color Picker Logic ---
document.addEventListener('DOMContentLoaded', function() {
    const savedColor = localStorage.getItem('theme_color');
    const colorPicker = document.getElementById('theme-color-picker');

    const applyThemeColor = (color) => {
        if (!color) return;
        document.documentElement.style.setProperty('--theme-color', color);
    };

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
function initRecurringModal() {
    const saveBtn = document.getElementById('save-recurring-template-btn');
    const form = document.getElementById('recurring-form');
    if (!saveBtn || !form) return;

    saveBtn.addEventListener('click', async () => {
        const response = await fetch(`${basePath}/api/recurring`, { method: 'POST', body: new FormData(form) });
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
        if (result.status === 'success') closeModal('recurringModal');
    });
}

function openRecurringModal(type, data, existingTemplate = null) {
    const recurringForm = document.getElementById('recurring-form');
    const startDateEl = document.getElementById('recurring-start-date');
    const endDateEl = document.getElementById('recurring-end-date');

    if (!recurringForm || !startDateEl || !endDateEl) return;

    recurringForm.reset();
    document.getElementById('recurring-template-type').value = type;
    document.getElementById('recurring-template-data').value = JSON.stringify(data);

    // Ambil instance flatpickr yang sudah diinisialisasi di footer.php
    const startDatePicker = startDateEl._flatpickr;
    const endDatePicker = endDateEl._flatpickr;

    if (existingTemplate) {
        document.getElementById('recurringModalLabel').textContent = 'Edit Jadwal Berulang';
        document.getElementById('recurring-id').value = existingTemplate.id;
        document.getElementById('recurring-name').value = existingTemplate.name;
        document.getElementById('recurring-frequency-interval').value = existingTemplate.frequency_interval;
        document.getElementById('recurring-frequency-unit').value = existingTemplate.frequency_unit;
        if (startDatePicker) startDatePicker.setDate(existingTemplate.start_date, true, "Y-m-d");
        if (endDatePicker && existingTemplate.end_date) endDatePicker.setDate(existingTemplate.end_date, true, "Y-m-d");
    } else {
        document.getElementById('recurringModalLabel').textContent = 'Atur Jadwal Berulang';
        document.getElementById('recurring-id').value = '';
        if (startDatePicker) startDatePicker.setDate(new Date(), true);
    }

    openModal('recurringModal');
}

function initGlobalSearch() {
    const searchModalEl = document.getElementById('globalSearchModal');
    if (!searchModalEl) return;

    const searchInput = document.getElementById('global-search-input');
    const resultsContainer = document.getElementById('global-search-results');
    const spinner = document.getElementById('global-search-spinner');

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
                        <a href="${basePath}${item.link}" class="search-result-item block p-3 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
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
        const link = e.target.closest('a.search-result-item');
        if (link) {
            e.preventDefault();
            const url = link.href;
            closeModal('globalSearchModal');
            // Gunakan fungsi navigate SPA untuk pindah halaman dan menangani hash
            navigate(url);
        }
    });

    // Add keyboard shortcut (Ctrl+K or Cmd+K)
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault(); // Prevent default browser action (e.g., search)
            openModal('globalSearchModal');
            setTimeout(() => searchInput.focus(), 50);
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
    if (!pagination || pagination.total_pages <= 1) {
        // Optional: show info even for single page
        const info = document.getElementById(container.id.replace('pagination', 'pagination-info'));
        if (info && pagination && pagination.total_records > 0) {
            info.textContent = `Menampilkan ${pagination.total_records} dari ${pagination.total_records} data.`;
        }
        return;
    }

    const { current_page, total_pages } = pagination;

    const createPageItem = (page, text, isDisabled = false, isActive = false) => {
        const a = document.createElement('a');
        a.href = '#';
        a.dataset.page = page;
        a.innerHTML = text;

        let baseClasses = 'flex items-center justify-center px-3 h-8 leading-tight';
        let stateClasses = '';
        if (isDisabled) {
            stateClasses = 'text-gray-500 bg-white border border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 cursor-not-allowed';
        } else if (isActive) {
            stateClasses = 'text-white bg-primary border border-primary z-10';
        } else {
            stateClasses = 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white';
        }
        a.className = `${baseClasses} ${stateClasses}`;
        return a;
    };

    const ul = document.createElement('ul');
    ul.className = 'inline-flex -space-x-px text-sm';

    const prevItem = createPageItem(current_page - 1, 'Prev', current_page === 1);
    prevItem.classList.add('rounded-l-lg');
    ul.appendChild(document.createElement('li')).appendChild(prevItem);

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
        ul.appendChild(document.createElement('li')).appendChild(createPageItem(1, '1'));
        if (startPage > 2) ul.appendChild(document.createElement('li')).appendChild(createPageItem(0, '...', true));
    }

    for (let i = startPage; i <= endPage; i++) {
        ul.appendChild(document.createElement('li')).appendChild(createPageItem(i, i, false, i === current_page));
    }

    if (endPage < total_pages) {
        if (endPage < total_pages - 1) ul.appendChild(document.createElement('li')).appendChild(createPageItem(0, '...', true));
        ul.appendChild(document.createElement('li')).appendChild(createPageItem(total_pages, total_pages));
    }

    const nextItem = createPageItem(current_page + 1, 'Next', current_page === total_pages);
    nextItem.classList.add('rounded-r-lg');
    ul.appendChild(document.createElement('li')).appendChild(nextItem);

    container.appendChild(ul);

    container.addEventListener('click', (e) => {
        e.preventDefault();
        const pageLink = e.target.closest('a[data-page]');
        if (pageLink && !pageLink.classList.contains('cursor-not-allowed')) {
            const page = parseInt(pageLink.dataset.page, 10);
            if (page && page !== current_page) {
                onPageClick(page);
            }
        }
    });
}

function formatNumber(value) {
    if (typeof value !== 'number') return value;
    return new Intl.NumberFormat('id-ID').format(value);
}