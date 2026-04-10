/**
 * Catatan Perubahan - Logika Halaman (Ultra-Minimalist Documentation Feed)
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
        container.innerHTML = `<div class="p-6 bg-red-50 dark:bg-red-900/10 text-red-600 dark:text-red-400 rounded-xl border border-red-100 dark:border-red-900/20 text-sm font-medium">Gagal memuat data: ${error.message}</div>`;
    }
}

function renderChangelog(data) {
    const container = document.getElementById('changelog-container');
    if (!container) return;

    if (!data || data.length === 0) {
        container.innerHTML = '<p class="text-left py-12 italic text-sm">No archives found.</p>';
        return;
    }

    let html = '';
    data.forEach((item, index) => {
        const isLatest = index === 0;
        html += `
        <article class="changelog-entry group ${isLatest ? 'is-open' : ''}">
            <h2 class="changelog-entry-title cursor-pointer flex items-center justify-between hover:text-primary transition-colors duration-200" onclick="this.parentElement.classList.toggle('is-open')">
                <span>
                    What's new in <span class="text-primary">v${item.version}</span> (${item.date}):
                </span>
                <i class="bi bi-chevron-down transform transition-transform duration-300 group-[.is-open]:rotate-180"></i>
            </h2>
            <div class="changelog-content overflow-hidden transition-all duration-300 group-[.is-open]:max-h-[2000px] max-h-0 pl-2 border-l border-gray-100 dark:border-gray-800 ml-1">
                ${renderSimplifiedCategories(item.categories)}
            </div>
        </article>
        `;
    });

    container.innerHTML = html;
}

function renderSimplifiedCategories(categories) {
    let html = '';
    
    // Order of importance
    const order = ['FITUR BARU', 'TAMBAH', 'PENINGKATAN', 'PERBAIKAN', 'ADD', 'IMPROVE', 'FIX'];
    const sortedCategories = Object.keys(categories).sort((a, b) => {
        let indexA = order.indexOf(a);
        let indexB = order.indexOf(b);
        if (indexA === -1) indexA = 99;
        if (indexB === -1) indexB = 99;
        return indexA - indexB;
    });

    sortedCategories.forEach(cat => {
        const items = categories[cat];
        const catLower = cat.toLowerCase();
        
        let colorClass = 'text-gray-400 dark:text-gray-500';
        if (catLower.includes('fix') || catLower.includes('perbaikan')) colorClass = 'text-rose-500';
        if (catLower.includes('add') || catLower.includes('fitur baru') || catLower.includes('tambah')) colorClass = 'text-emerald-500';
        if (catLower.includes('improve') || catLower.includes('peningkatan')) colorClass = 'text-amber-500';

        html += `
        <div class="changelog-group mb-6 last:mb-2">
            <div class="${colorClass} text-[10px] font-bold uppercase tracking-[0.2em] mb-3 flex items-center gap-2">
                <span>${catLower}</span>
                <div class="h-[1px] flex-1 bg-gray-100 dark:bg-gray-800/50"></div>
            </div>
            <div class="space-y-2">
                ${items.map(text => `
                    <div class="changelog-item">
                        <span class="changelog-item-bullet">*)</span>
                        <span class="text-gray-600 dark:text-gray-400">${parseMarkdown(text)}</span>
                    </div>
                `).join('')}
            </div>
        </div>
        `;
    });

    return html;
}

function parseMarkdown(text) {
    if (!text) return '';
    return text
        .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-gray-900 dark:text-white">$1</strong>')
        .replace(/\*(.*?)\*/g, '<em class="italic">$1</em>')
        .replace(/__(.*?)__/g, '<u class="underline">$1</u>');
}
