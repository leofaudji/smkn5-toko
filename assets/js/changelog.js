/**
 * Changelog Page — Collapsible Version Feed
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
        renderChangelog(data, container);
    } catch (error) {
        console.error('Error fetching changelog:', error);
        container.innerHTML = `
            <div style="
                padding: 1.25rem 1.5rem;
                border-radius: 14px;
                background: #fff1f2;
                border: 1px solid #fecdd3;
                color: #e11d48;
                font-size: 13px;
                font-weight: 600;
            ">
                <i class="bi bi-exclamation-triangle-fill" style="margin-right:6px"></i>
                Gagal memuat data: ${error.message}
            </div>`;
    }
}

function renderChangelog(data, container) {
    if (!data || data.length === 0) {
        container.innerHTML = `
            <div style="text-align:center;padding:4rem 0;color:#94a3b8;font-size:15px;font-weight:500">
                <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.75rem;opacity:.4"></i>
                Belum ada catatan perubahan.
            </div>`;
        return;
    }

    // Keep the timeline line, rebuild the rest
    const timelineLine = container.querySelector('.cl-timeline-line');
    container.innerHTML = '';
    if (timelineLine) container.appendChild(timelineLine);

    data.forEach((item, index) => {
        const isLatest = index === 0;
        const el = buildVersionEl(item, index, isLatest);
        container.appendChild(el);

        // Open latest by default after a short delay for animation
        if (isLatest) {
            requestAnimationFrame(() => {
                setTimeout(() => el.classList.add('is-open'), 60);
            });
        }
    });
}

function buildVersionEl(item, index, isLatest) {
    const wrapper = document.createElement('div');
    wrapper.className = 'cl-version';
    wrapper.dataset.version = item.version;
    wrapper.style.animationDelay = `${index * 80}ms`;

    // Sanitize version string for use as id
    const vId = `cl-v-${item.version.replace(/\./g, '-')}`;

    wrapper.innerHTML = `
        <div class="cl-version-dot"></div>
        <div class="cl-card">

            <!-- Clickable Header -->
            <div class="cl-version-header" role="button" aria-expanded="${isLatest}" aria-controls="${vId}" tabindex="0">
                <div class="cl-version-header-left">
                    <div class="cl-version-tag">
                        <span class="cl-version-number">v${escHtml(item.version)}</span>
                        ${isLatest ? `
                            <span class="cl-latest-pill">
                                <span class="cl-latest-pulse"></span>
                                Latest
                            </span>` : ''}
                    </div>
                    <div class="cl-version-date">
                        <i class="bi bi-calendar3"></i>
                        ${escHtml(item.date)}
                    </div>
                </div>
                <div class="cl-toggle-btn" aria-hidden="true">
                    <i class="bi bi-chevron-down"></i>
                </div>
            </div>

            <!-- Collapsible Body -->
            <div class="cl-version-body" id="${vId}" role="region">
                <div class="cl-version-body-inner">
                    <div class="cl-version-body-content">
                        ${buildCategories(item.categories)}
                    </div>
                </div>
            </div>

        </div>
    `;

    // Click handler
    const header = wrapper.querySelector('.cl-version-header');
    header.addEventListener('click', () => toggleVersion(wrapper));
    header.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleVersion(wrapper);
        }
    });

    return wrapper;
}

function toggleVersion(wrapper) {
    const isOpen = wrapper.classList.contains('is-open');
    wrapper.classList.toggle('is-open', !isOpen);
    const header = wrapper.querySelector('.cl-version-header');
    if (header) header.setAttribute('aria-expanded', String(!isOpen));
}

function buildCategories(categories) {
    if (!categories || typeof categories !== 'object') return '';

    const ORDER = ['KEAMANAN', 'FITUR BARU', 'TAMBAH', 'ADD', 'INFRASTRUKTUR', 'PENINGKATAN', 'IMPROVE', 'PERBAIKAN', 'FIX'];
    const ICONS = {
        'KEAMANAN':     'bi-shield-lock-fill',
        'FITUR BARU':   'bi-stars',
        'TAMBAH':       'bi-plus-circle-fill',
        'ADD':          'bi-plus-circle-fill',
        'INFRASTRUKTUR':'bi-cpu-fill',
        'PENINGKATAN':  'bi-graph-up-arrow',
        'IMPROVE':      'bi-graph-up-arrow',
        'PERBAIKAN':    'bi-tools',
        'FIX':          'bi-tools',
    };

    const sorted = Object.keys(categories).sort((a, b) => {
        const ia = ORDER.indexOf(a) === -1 ? 99 : ORDER.indexOf(a);
        const ib = ORDER.indexOf(b) === -1 ? 99 : ORDER.indexOf(b);
        return ia - ib;
    });

    return sorted.map(cat => {
        const items = categories[cat];
        const icon  = ICONS[cat] || 'bi-info-circle-fill';
        const cls   = catBadgeClass(cat);

        const logHtml = items.map(text => `
            <div class="cl-log-item">
                <div class="cl-log-bullet"></div>
                <p class="cl-log-text">${parseMarkdown(text)}</p>
            </div>`).join('');

        return `
            <div class="cl-category">
                <span class="cl-cat-badge ${cls}">
                    <i class="bi ${icon}"></i>
                    ${escHtml(cat)}
                </span>
                <div class="cl-log-list">${logHtml}</div>
            </div>`;
    }).join('');
}

function catBadgeClass(cat) {
    const c = cat.toLowerCase();
    if (c.includes('aman') || c.includes('security'))          return 'cl-cat-keamanan';
    if (c.includes('fitur') || c.includes('tambah') || c === 'add') return 'cl-cat-fitur';
    if (c.includes('infra'))                                    return 'cl-cat-infrastruktur';
    if (c.includes('peningkatan') || c === 'improve')          return 'cl-cat-peningkatan';
    if (c.includes('perbaikan') || c === 'fix')                return 'cl-cat-perbaikan';
    return 'cl-cat-default';
}

function parseMarkdown(text) {
    if (!text) return '';
    return escHtml(text)
        .replace(/\*\*(.*?)\*\*/g, '<strong style="font-weight:700;color:var(--cl-text-primary)">$1</strong>')
        .replace(/\*(.*?)\*/g,     '<em>$1</em>')
        .replace(/`(.*?)`/g,       '<code style="font-family:monospace;font-size:12px;padding:1px 5px;border-radius:4px;background:var(--cl-divider)">$1</code>');
}

function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
