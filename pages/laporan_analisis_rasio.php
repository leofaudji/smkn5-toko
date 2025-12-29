<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
check_permission('analisis_rasio', 'menu');
?>

<div class="flex flex-wrap md:flex-nowrap justify-between items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white"><i class="bi bi-pie-chart-fill mr-2"></i> Analisis Rasio Keuangan</h1>
    <div class="relative" data-controller="dropdown">
        <button onclick="toggleDropdown(this)" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="bi bi-download mr-2"></i>
            Export
            <i class="bi bi-chevron-down ml-2 -mr-1"></i>
        </button>
        <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-10">
            <div class="py-1">
                <a href="#" id="export-ra-pdf" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="bi bi-file-earmark-pdf-fill text-red-500 mr-3"></i>Cetak PDF
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg mb-6">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-5">
                <label for="ra-tanggal-akhir" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Analisis per Tanggal</label>
                <input type="date" id="ra-tanggal-akhir" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm">
            </div>
            <div class="md:col-span-5">
                <label for="ra-tanggal-pembanding" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bandingkan dengan Tanggal (Opsional)</label>
                <input type="date" id="ra-tanggal-pembanding" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm">
            </div>
            <div class="md:col-span-2">
                <button class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" id="ra-tampilkan-btn">
                    <i class="bi bi-search mr-2"></i> Analisis
                </button>
            </div>
        </div>
    </div>
</div>

<div id="ratio-analysis-content">
    <div class="bg-blue-100 border-t-4 border-blue-500 rounded-b text-blue-900 px-4 py-3 shadow-md text-center" role="alert">
        Silakan pilih tanggal analisis, lalu klik "Analisis".
    </div>
</div>

<!-- Template untuk card rasio -->
<template id="ratio-card-template">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md flex flex-col h-full border border-gray-200 dark:border-gray-700">
        <div class="p-6 flex-grow">
            <h5 class="flex justify-between items-start mb-2">
                <span class="ratio-name font-semibold text-gray-800 dark:text-white"></span>
                <i class="bi bi-info-circle-fill text-gray-400 dark:text-gray-500 cursor-pointer" title=""></i>
            </h5>
            <h2 class="ratio-value text-3xl font-bold text-gray-900 dark:text-white"></h2>
            <p class="ratio-comparison mt-2 text-sm text-gray-500 dark:text-gray-400 flex items-center"></p>
            <p class="ratio-formula mt-4 text-xs italic text-gray-500 dark:text-gray-400"></p>
        </div>
        <div class="ratio-interpretation bg-gray-50 dark:bg-gray-700/50 px-6 py-4 text-sm rounded-b-lg">
        </div>
    </div>
</template>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>