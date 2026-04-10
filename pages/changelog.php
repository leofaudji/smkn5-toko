<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('changelog', 'menu');
?>

<div class="max-w-4xl mx-auto px-6 py-12">
    <!-- Technical Header -->
    <div class="mb-10 pb-6 border-b border-gray-200 dark:border-gray-800">
        <h1 class="text-2xl font-mono font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <i class="bi bi-terminal text-primary"></i> 
            CHANGELOG.txt
        </h1>
        <p class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-2 uppercase tracking-widest">System Update History Log</p>
    </div>

    <!-- Feed Container -->
    <div id="changelog-container" class="font-mono space-y-10">
        <div class="flex items-center gap-3 py-10">
            <div class="h-4 w-4 border-2 border-gray-200 dark:border-gray-700 border-t-primary rounded-full animate-spin"></div>
            <p class="text-[10px] tracking-widest uppercase font-bold text-gray-400">Loading archives...</p>
        </div>
    </div>
</div>

<style>
    .changelog-entry-title {
        @apply text-sm font-bold text-gray-900 dark:text-white mb-4;
    }
    .changelog-item {
        @apply flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400 mb-1.5 leading-relaxed;
    }
    .changelog-item-bullet {
        @apply flex-shrink-0 text-primary font-bold;
    }
    .changelog-entry {
        animation: technicalIn 0.3s ease-out forwards;
        opacity: 0;
        @apply border-b border-gray-100 dark:border-gray-800 pb-6 mb-6 last:border-0;
    }
    .changelog-content {
        @apply transition-all duration-500 ease-in-out;
    }
    .changelog-entry.is-open .changelog-entry-title {
        @apply mb-4;
    }
    .changelog-entry:not(.is-open) .changelog-entry-title {
        @apply mb-0;
    }
    @keyframes technicalIn {
        from { opacity: 0; transform: translateX(-5px); }
        to { opacity: 1; transform: translateX(0); }
    }
</style>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>
