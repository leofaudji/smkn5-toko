/**
 * Catatan Perubahan - Logika Halaman (Redesigned)
 */

function initChangelogPage() {
    fetchChangelog();
}

async function fetchChangelog() {
    const container = document.getElementById('changelog-container');
    if (!container) return;

    try {
        const response = await fetch(`${basePath}/api/changelog_handler.php`);
        if (!response.ok) throw new Error('Gagal mengambil data catatan perubahan');
        
        const data = await response.json();
        renderChangelog(data);
    } catch (error) {
        console.error('Error fetching changelog:', error);
        container.innerHTML = `<div class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-4 rounded-lg border border-red-100 dark:border-red-800">Gagal memuat data: ${error.message}</div>`;
    }
}

function toggleVersion(button) {
    const content = button.nextElementSibling;
    const icon = button.querySelector('.bi-plus-lg, .bi-dash-lg');
    const card = button.closest('.version-card');
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        content.classList.add('animate-fade-in');
        card.classList.add('ring-2', 'ring-primary/20', 'shadow-lg');
        if (icon) {
            icon.classList.replace('bi-plus-lg', 'bi-dash-lg');
        }
    } else {
        content.classList.add('hidden');
        card.classList.remove('ring-2', 'ring-primary/20', 'shadow-lg');
        if (icon) {
            icon.classList.replace('bi-dash-lg', 'bi-plus-lg');
        }
    }
}

function renderChangelog(data) {
    const container = document.getElementById('changelog-container');
    if (!container) return;

    if (!data || data.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-500 py-12">Belum ada catatan perubahan.</p>';
        return;
    }

    // Setel badge versi terbaru di header
    const latestVersion = data[0].version;
    const badge = document.getElementById('current-ver-badge');
    if (badge) badge.textContent = `v${latestVersion}`;

    let html = '';
    data.forEach((item, index) => {
        const isLatest = index === 0;
        const hiddenClass = isLatest ? '' : 'hidden';
        const iconClass = isLatest ? 'bi-dash-lg' : 'bi-plus-lg';
        const activeCardClass = isLatest ? 'ring-2 ring-primary/20 shadow-lg' : '';

        html += `
        <div class="version-card relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 transition-all duration-300 mb-4 ${activeCardClass}">
            <!-- Header Versi -->
            <button onclick="toggleVersion(this)" class="w-full flex items-center justify-between p-5 md:p-6 text-left focus:outline-none group">
                <div class="flex items-center gap-4 md:gap-6">
                    <div class="h-12 w-12 md:h-14 md:w-14 rounded-xl bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform duration-200">
                        <span class="text-primary-600 dark:text-primary-400 font-bold text-lg md:text-xl">v${item.version.split('.')[0]}</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h2 class="text-xl md:text-2xl font-extrabold text-gray-800 dark:text-white leading-tight">Versi ${item.version}</h2>
                            ${isLatest ? '<span class="px-2 py-0.5 text-[10px] font-bold bg-green-500 text-white rounded-md uppercase tracking-wider">Terbaru</span>' : ''}
                        </div>
                        <div class="flex items-center gap-4 text-xs md:text-sm text-gray-400 dark:text-gray-500">
                            <span class="flex items-center gap-1.5"><i class="bi bi-calendar3"></i> ${item.date}</span>
                            <span class="hidden md:flex items-center gap-1.5"><i class="bi bi-check2-circle"></i> Verifikasi Stabil</span>
                        </div>
                    </div>
                </div>
                <div class="h-8 w-8 rounded-full border border-gray-100 dark:border-gray-700 flex items-center justify-center text-gray-400 group-hover:text-primary transition-colors">
                    <i class="bi ${iconClass} transition-transform duration-200"></i>
                </div>
            </button>
            
            <!-- Konten Konten -->
            <div class="p-6 pt-0 border-t border-gray-50 dark:border-gray-700/50 ${hiddenClass}">
                <div class="grid grid-cols-1 gap-6 mt-6">
                    ${renderCategories(item.categories)}
                </div>
            </div>
        </div>
        `;
    });

    container.innerHTML = html;
}

/**
 * Simple Markdown Parser to support basic formatting
 * @param {string} text 
 * @returns {string}
 */
function parseMarkdown(text) {
    if (!text) return '';
    return text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/__(.*?)__/g, '<u>$1</u>');
}

function renderCategories(categories) {
    let html = '';
    
    // Urutan: FITUR BARU, TAMBAH, PENINGKATAN, PERBAIKAN
    const order = ['FITUR BARU', 'TAMBAH', 'PENINGKATAN', 'PERBAIKAN', 'HAPUS', 'ADD', 'IMPROVE', 'FIX', 'REMOVE'];
    const sortedCategories = Object.keys(categories).sort((a, b) => {
        let indexA = order.indexOf(a);
        let indexB = order.indexOf(b);
        if (indexA === -1) indexA = 99;
        if (indexB === -1) indexB = 99;
        return indexA - indexB;
    });

    sortedCategories.forEach(cat => {
        const items = categories[cat];
        let icon = 'bi-patch-check';
        let colorClass = 'text-gray-500 dark:text-gray-400';
        let bgColorClass = 'bg-gray-50 dark:bg-gray-700/30';
        
        if (cat === 'FITUR BARU' || cat === 'TAMBAH' || cat === 'ADD') {
            icon = 'bi-stars';
            colorClass = 'text-green-600 dark:text-green-400';
            bgColorClass = 'bg-green-50 dark:bg-green-900/20';
        } else if (cat === 'PERBAIKAN' || cat === 'FIX') {
            icon = 'bi-wrench-adjustable';
            colorClass = 'text-amber-600 dark:text-amber-400';
            bgColorClass = 'bg-amber-50 dark:bg-amber-900/20';
        } else if (cat === 'PENINGKATAN' || cat === 'IMPROVE') {
            icon = 'bi-rocket-takeoff';
            colorClass = 'text-blue-600 dark:text-blue-400';
            bgColorClass = 'bg-blue-50 dark:bg-blue-900/20';
        }

        html += `
        <div class="space-y-3">
            <div class="flex items-center gap-2">
                <div class="h-6 w-6 rounded-md ${bgColorClass} flex items-center justify-center ${colorClass}">
                    <i class="bi ${icon} text-xs"></i>
                </div>
                <h3 class="text-xs font-bold uppercase tracking-widest ${colorClass}">${cat}</h3>
            </div>
            <div class="pl-8 space-y-2.5">
                ${items.map(text => `
                    <div class="flex items-start gap-3 group/item">
                        <div class="mt-2 h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-600 group-hover/item:bg-primary transition-colors"></div>
                        <p class="text-sm md:text-base text-gray-600 dark:text-gray-400 leading-relaxed">${parseMarkdown(text)}</p>
                    </div>
                `).join('')}
            </div>
        </div>
        `;
    });

    return html;
}
