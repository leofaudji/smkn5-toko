/**
 * Buku Panduan - Logika Rendering Mermaid
 */

function initBukuPanduanPage() {
    if (typeof mermaid === 'undefined') {
        loadMermaid();
    } else {
        renderMermaidDiagrams();
    }
}

async function loadMermaid() {
    try {
        // Karena Mermaid 10+ menggunakan ESM, kita harus mengimpor secara dinamis
        const module = await import('https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs');
        window.mermaid = module.default;
        
        mermaid.initialize({ 
            startOnLoad: false,
            securityLevel: 'loose',
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'default'
        });
        
        renderMermaidDiagrams();
    } catch (error) {
        console.error('Gagal memuat Mermaid.js:', error);
    }
}

function renderMermaidDiagrams() {
    if (window.mermaid) {
        // Gunakan timeout kecil untuk memastikan DOM benar-benar siap
        setTimeout(async () => {
            try {
                await mermaid.run({
                    nodes: document.querySelectorAll('.mermaid'),
                });
            } catch (err) {
                console.error('Mermaid render error:', err);
            }
        }, 100);
    }
}

/**
 * Toggle Accordion on Handbook Page
 */
function toggleAccordion(button) {
    const item = button.closest('[data-controller="accordion-item"]');
    const content = item.querySelector(':scope > div:last-child'); // Finding the collapse content
    const icon = button.querySelector('.bi-chevron-down');

    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        if (icon) icon.classList.add('rotate-180');
    } else {
        content.classList.add('hidden');
        if (icon) icon.classList.remove('rotate-180');
    }
}

// Tambahkan listener untuk perubahan tema jika diperlukan agar diagram menyesuaikan
// (Opsional, karena saat ini refresh UI biasanya terjadi saat ganti tema di SPA)
