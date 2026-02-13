<?php
if (!defined('PROJECT_ROOT')) exit('No direct script access allowed');

$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Laporan Kesehatan KSP</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Analisis rasio keuangan utama untuk mengukur kinerja.</p>
        </div>
        <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-2 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <label for="filter-date" class="text-sm font-medium text-gray-700 dark:text-gray-300">Per Tanggal:</label>
            <input type="date" id="filter-date" class="pl-3 pr-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white focus:ring-primary focus:border-primary">
            <select id="filter-compare" class="pl-3 pr-8 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white focus:ring-primary focus:border-primary">
                <option value="mom">Vs Bulan Lalu</option>
                <option value="yoy">Vs Tahun Lalu</option>
            </select>
            <button id="btn-filter-health" class="p-2 bg-primary text-white rounded-md hover:bg-primary-600 transition-colors">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </div>

    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="healthReportTabs" role="tablist">
            <li class="mr-2" role="presentation">
                <button class="health-tab-btn inline-block p-4 border-b-2 rounded-t-lg" data-tabs-target="#rasio-panel" type="button" role="tab">Analisis Rasio</button>
            </li>
            <li class="mr-2" role="presentation">
                <button class="health-tab-btn inline-block p-4 border-b-2 rounded-t-lg" data-tabs-target="#dupont-panel" type="button" role="tab">Analisis DuPont</button>
            </li>
        </ul>
    </div>

    <div id="healthReportTabContent">
        <div id="rasio-panel" class="health-tab-pane space-y-8" role="tabpanel">
            <!-- Content will be injected here by JS -->
        </div>
        <div id="dupont-panel" class="health-tab-pane hidden" role="tabpanel">
            <!-- DuPont Analysis content will be injected here by JS -->
        </div>
    </div>

</div>

<!-- Template Kartu Rasio -->
<template id="ratio-card-template">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all hover:shadow-md hover:-translate-y-1">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-300 ratio-name">Nama Rasio</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 ratio-description">Deskripsi singkat rasio.</p>
            </div>
            <div class="p-3 rounded-lg ratio-icon-container">
                <i class="text-xl ratio-icon"></i>
            </div>
        </div>
        <div class="mt-4">
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white ratio-value">0%</h3>
            <div class="mt-2 flex items-center text-xs">
                <span class="px-2 py-0.5 rounded-full font-medium ratio-interpretation">Interpretasi</span>
                <span class="ml-2 ratio-comparison hidden"></span>
            </div>
        </div>
        <div class="mt-4 h-16 relative">
            <canvas class="ratio-sparkline"></canvas>
        </div>
    </div>
</template>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>