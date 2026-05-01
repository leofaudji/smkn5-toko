<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('changelog', 'menu');
?>

<style>
/* ─── Changelog Page Styles ────────────────────────────────── */

.cl-page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 4rem 1.5rem 6rem;
}

/* Header */
.cl-header {
    margin-bottom: 4rem;
}

.cl-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .18em;
    text-transform: uppercase;
    background: var(--cl-accent-bg);
    color: var(--cl-accent);
    border: 1px solid var(--cl-accent-border);
    margin-bottom: 1.25rem;
}

.cl-title {
    font-size: clamp(2.4rem, 6vw, 3.8rem);
    font-weight: 900;
    line-height: 1;
    letter-spacing: -.04em;
    color: var(--cl-text-primary);
    margin: 0 0 .75rem;
}

.cl-title span {
    color: var(--cl-accent);
    font-style: italic;
}

.cl-subtitle {
    font-size: 1rem;
    color: var(--cl-text-muted);
    font-weight: 500;
    line-height: 1.7;
    max-width: 480px;
}

/* Timeline */
.cl-timeline {
    position: relative;
}

.cl-timeline-line {
    position: absolute;
    left: 10px;
    top: 6px;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, var(--cl-accent) 0%, color-mix(in srgb, var(--cl-accent) 20%, transparent) 80%, transparent 100%);
    border-radius: 2px;
}

/* Version Item */
.cl-version {
    position: relative;
    padding-left: 44px;
    margin-bottom: 2rem;
    opacity: 0;
    transform: translateY(16px);
    animation: clFadeUp .5s cubic-bezier(.16,1,.3,1) forwards;
}

.cl-version-dot {
    position: absolute;
    left: 4px;
    top: 20px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--cl-card-bg);
    border: 2px solid #cbd5e1;
    transition: all .4s cubic-bezier(.16,1,.3,1);
    z-index: 1;
}

.cl-version.is-open .cl-version-dot {
    background: var(--cl-accent);
    border-color: var(--cl-accent);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--cl-accent) 18%, transparent);
    transform: scale(1.2);
}

/* Card */
.cl-card {
    background: var(--cl-card-bg);
    border: 1px solid var(--cl-card-border);
    border-radius: 20px;
    overflow: hidden;
    transition: box-shadow .3s, border-color .3s, transform .3s;
    box-shadow: var(--cl-card-shadow);
}

.cl-version.is-open .cl-card {
    border-color: color-mix(in srgb, var(--cl-accent) 30%, transparent);
    box-shadow: 0 8px 40px color-mix(in srgb, var(--cl-accent) 10%, transparent), var(--cl-card-shadow);
}

/* Version Header (clickable) */
.cl-version-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    cursor: pointer;
    user-select: none;
    transition: background .2s;
    -webkit-tap-highlight-color: transparent;
}

.cl-version-header:hover {
    background: color-mix(in srgb, var(--cl-accent) 4%, transparent);
}

.cl-version-header-left {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.cl-version-tag {
    display: flex;
    align-items: center;
    gap: 10px;
}

.cl-version-number {
    font-size: 1.35rem;
    font-weight: 900;
    letter-spacing: -.03em;
    color: var(--cl-text-primary);
    line-height: 1;
}

.cl-latest-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 9px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .14em;
    padding: 3px 9px;
    border-radius: 999px;
    background: var(--cl-accent);
    color: #fff;
    box-shadow: 0 2px 10px color-mix(in srgb, var(--cl-accent) 35%, transparent);
}

.cl-latest-pulse {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #fff;
    animation: clPulse 1.4s ease-in-out infinite;
}

.cl-version-date {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
    color: var(--cl-text-muted);
    text-transform: uppercase;
    letter-spacing: .1em;
}

.cl-toggle-btn {
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: var(--cl-toggle-bg);
    color: var(--cl-text-muted);
    font-size: 13px;
    transition: all .4s cubic-bezier(.16,1,.3,1);
}

.cl-version.is-open .cl-toggle-btn {
    background: var(--cl-accent);
    color: #fff;
    transform: rotate(180deg);
    box-shadow: 0 4px 16px color-mix(in srgb, var(--cl-accent) 30%, transparent);
}

/* Collapsible body */
.cl-version-body {
    display: grid;
    grid-template-rows: 0fr;
    transition: grid-template-rows .45s cubic-bezier(.16,1,.3,1);
}

.cl-version.is-open .cl-version-body {
    grid-template-rows: 1fr;
}

.cl-version-body-inner {
    overflow: hidden;
}

.cl-version-body-content {
    border-top: 1px solid var(--cl-divider);
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Category Section */
.cl-category {
    display: flex;
    flex-direction: column;
    gap: .75rem;
}

.cl-cat-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .14em;
    border: 1px solid transparent;
    align-self: flex-start;
}

/* Category colors (vanilla — no @apply) */
.cl-cat-fitur       { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
.cl-cat-perbaikan   { background: #fff1f2; color: #e11d48; border-color: #fecdd3; }
.cl-cat-peningkatan { background: #fffbeb; color: #d97706; border-color: #fde68a; }
.cl-cat-keamanan    { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
.cl-cat-infrastruktur { background: #f5f3ff; color: #7c3aed; border-color: #ddd6fe; }
.cl-cat-default     { background: #f8fafc; color: #64748b; border-color: #e2e8f0; }

/* Dark mode overrides */
@media (prefers-color-scheme: dark) {
    .cl-cat-fitur       { background: rgb(6 78 59 / .2); color: #34d399; border-color: rgb(6 78 59 / .5); }
    .cl-cat-perbaikan   { background: rgb(136 19 55 / .2); color: #fb7185; border-color: rgb(136 19 55 / .5); }
    .cl-cat-peningkatan { background: rgb(120 53 15 / .2); color: #fbbf24; border-color: rgb(120 53 15 / .5); }
    .cl-cat-keamanan    { background: rgb(30 58 138 / .2); color: #60a5fa; border-color: rgb(30 58 138 / .5); }
    .cl-cat-infrastruktur { background: rgb(76 29 149 / .2); color: #a78bfa; border-color: rgb(76 29 149 / .5); }
    .cl-cat-default     { background: rgb(30 41 59 / .5); color: #94a3b8; border-color: rgb(51 65 85 / .8); }
}

/* For tailwind-dark class approach */
.dark .cl-cat-fitur       { background: rgb(6 78 59 / .2); color: #34d399; border-color: rgb(6 78 59 / .5); }
.dark .cl-cat-perbaikan   { background: rgb(136 19 55 / .2); color: #fb7185; border-color: rgb(136 19 55 / .5); }
.dark .cl-cat-peningkatan { background: rgb(120 53 15 / .2); color: #fbbf24; border-color: rgb(120 53 15 / .5); }
.dark .cl-cat-keamanan    { background: rgb(30 58 138 / .2); color: #60a5fa; border-color: rgb(30 58 138 / .5); }
.dark .cl-cat-infrastruktur { background: rgb(76 29 149 / .2); color: #a78bfa; border-color: rgb(76 29 149 / .5); }
.dark .cl-cat-default     { background: rgb(30 41 59 / .5); color: #94a3b8; border-color: rgb(51 65 85 / .8); }

/* Log items */
.cl-log-list {
    display: flex;
    flex-direction: column;
    gap: .5rem;
    padding-left: .25rem;
}

.cl-log-item {
    display: flex;
    align-items: flex-start;
    gap: .75rem;
}

.cl-log-bullet {
    flex-shrink: 0;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #cbd5e1;
    margin-top: 8px;
    transition: background .25s, transform .25s;
}

.cl-log-item:hover .cl-log-bullet {
    background: var(--cl-accent);
    transform: scale(1.6);
}

.cl-log-text {
    font-size: 13.5px;
    line-height: 1.65;
    font-weight: 500;
    color: var(--cl-text-secondary);
}

/* Skeleton */
.cl-skeleton {
    position: relative;
    padding-left: 44px;
    margin-bottom: 2rem;
}

.cl-skeleton-dot {
    position: absolute;
    left: 4px;
    top: 20px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #e2e8f0;
    animation: clShimmer 1.4s ease-in-out infinite;
}

.dark .cl-skeleton-dot { background: #334155; }

.cl-skeleton-card {
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid #f1f5f9;
    animation: clShimmer 1.4s ease-in-out infinite;
}

.dark .cl-skeleton-card { border-color: #1e293b; }

.cl-skeleton-head {
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.cl-skel-block {
    border-radius: 8px;
    background: #f1f5f9;
}

.dark .cl-skel-block { background: #1e293b; }

/* CSS Variables (light) */
:root {
    --cl-accent: var(--color-primary, #6366f1);
    --cl-accent-bg: color-mix(in srgb, var(--cl-accent) 10%, transparent);
    --cl-accent-border: color-mix(in srgb, var(--cl-accent) 25%, transparent);
    --cl-text-primary: #0f172a;
    --cl-text-secondary: #475569;
    --cl-text-muted: #94a3b8;
    --cl-card-bg: #ffffff;
    --cl-card-border: #f1f5f9;
    --cl-card-shadow: 0 2px 16px 0 rgb(0 0 0 / .06);
    --cl-divider: #f1f5f9;
    --cl-toggle-bg: #f8fafc;
}

/* Dark */
.dark {
    --cl-text-primary: #f8fafc;
    --cl-text-secondary: #94a3b8;
    --cl-text-muted: #64748b;
    --cl-card-bg: rgb(15 23 42 / .7);
    --cl-card-border: rgb(30 41 59 / .8);
    --cl-card-shadow: none;
    --cl-divider: rgb(30 41 59 / .8);
    --cl-toggle-bg: rgb(30 41 59 / .8);
}

/* Animations */
@keyframes clFadeUp {
    to { opacity: 1; transform: translateY(0); }
}

@keyframes clPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: .4; transform: scale(.7); }
}

@keyframes clShimmer {
    0%, 100% { opacity: .6; }
    50%       { opacity: 1; }
}
</style>

<!-- Background glow blobs -->
<div class="fixed inset-0 pointer-events-none overflow-hidden" style="z-index:-1">
    <div style="position:absolute;top:-15%;left:-10%;width:45%;height:45%;background:color-mix(in srgb, var(--cl-accent) 6%, transparent);border-radius:50%;filter:blur(100px)"></div>
    <div style="position:absolute;top:25%;right:-8%;width:35%;height:35%;background:color-mix(in srgb, #3b82f6 5%, transparent);border-radius:50%;filter:blur(90px)"></div>
    <div style="position:absolute;bottom:-10%;left:25%;width:50%;height:40%;background:color-mix(in srgb, #8b5cf6 4%, transparent);border-radius:50%;filter:blur(120px)"></div>
</div>

<div class="cl-page">

    <!-- Header -->
    <div class="cl-header">
        <div class="cl-label">
            <i class="bi bi-rocket-takeoff-fill"></i>
            System Evolution Feed
        </div>
        <h1 class="cl-title">Jejak <span>Inovasi</span></h1>
        <p class="cl-subtitle">Menelusuri setiap langkah perubahan, perbaikan, dan fitur baru yang kami hadirkan.</p>
    </div>

    <!-- Timeline -->
    <div class="cl-timeline" id="changelog-container">
        <div class="cl-timeline-line"></div>

        <!-- Skeleton loading -->
        <?php for ($i = 0; $i < 3; $i++): ?>
        <div class="cl-skeleton" style="animation-delay: <?= $i * 80 ?>ms">
            <div class="cl-skeleton-dot"></div>
            <div class="cl-skeleton-card" style="background: <?= $i === 0 ? 'var(--cl-card-bg)' : 'var(--cl-card-bg)' ?>;">
                <div class="cl-skeleton-head">
                    <div style="display:flex;flex-direction:column;gap:8px">
                        <div class="cl-skel-block" style="width:<?= 90 + $i * 20 ?>px;height:18px"></div>
                        <div class="cl-skel-block" style="width:<?= 70 + $i * 10 ?>px;height:11px;opacity:.5"></div>
                    </div>
                    <div class="cl-skel-block" style="width:34px;height:34px;border-radius:10px"></div>
                </div>
            </div>
        </div>
        <?php endfor; ?>
    </div>

</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>
