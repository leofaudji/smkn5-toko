<?php
if (!isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
    require_once __DIR__ . '/../views/header.php';
}
?>

<div class="p-4 md:p-8 max-w-5xl mx-auto animate-fadeIn">
    
    <!-- Sophisticated Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Loyalty Leaderboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Apresiasi bagi anggota koperasi yang paling aktif dan disiplin.</p>
        </div>
        
        <!-- Category Toggle (Compact) -->
        <div class="inline-flex p-1 bg-gray-100 dark:bg-gray-800 rounded-lg">
            <button id="tab-shoppers" onclick="switchCategory('shoppers')" class="px-4 py-1.5 text-xs font-bold rounded-md transition-all duration-200 bg-white dark:bg-gray-700 text-primary shadow-sm">
                Belanja
            </button>
            <button id="tab-loyalists" onclick="switchCategory('loyalists')" class="px-4 py-1.5 text-xs font-bold rounded-md transition-all duration-200 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                Wajib Belanja
            </button>
        </div>
    </div>

    <!-- Filter & Summary -->
    <div id="filter-container" class="mb-4 flex items-center justify-between text-xs text-gray-400">
        <div class="flex items-center gap-2">
            <span>Urutkan Berdasarkan:</span>
            <select id="period-days" onchange="loadLeaderboard()" class="bg-transparent font-bold text-gray-600 dark:text-gray-300 border-none p-0 focus:ring-0 cursor-pointer">
                <option value="30">30 Hari Terakhir</option>
                <option value="90">3 Bulan Terakhir</option>
                <option value="365">1 Tahun Terakhir</option>
            </select>
        </div>
        <div id="list-subtitle">-</div>
    </div>

    <!-- Main Elegant List -->
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] font-bold text-gray-400 uppercase tracking-widest bg-gray-50/50 dark:bg-gray-800/50">
                        <th class="px-6 py-4 w-16 text-center">#</th>
                        <th class="px-6 py-4">Nama Anggota</th>
                        <th id="th-stat-1" class="px-6 py-4 text-right">Kontribusi</th>
                        <th id="th-stat-2" class="px-6 py-4 text-right text-gray-300 font-normal">Aktif</th>
                    </tr>
                </thead>
                <tbody id="leaderboard-body" class="divide-y divide-gray-50 dark:divide-gray-800">
                    <!-- Loaded via JS -->
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Empty State -->
    <div id="empty-state" class="hidden py-20 text-center">
        <i class="bi bi-person-dash text-4xl text-gray-200"></i>
        <p class="mt-4 text-gray-400 text-sm">Belum ada data peringkat untuk periode ini.</p>
    </div>

</div>

<!-- Member History Modal (Moved outside animate-fadeIn for correct fixed positioning) -->
<div id="history-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="relative w-full max-w-2xl max-h-[90vh] overflow-hidden bg-white dark:bg-gray-900 rounded-2xl shadow-2xl flex flex-col animate-modalIn">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <div>
                <h3 id="modal-member-name" class="text-lg font-bold text-gray-900 dark:text-white">-</h3>
                <p id="modal-member-id" class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">-</p>
            </div>
            <button onclick="closeModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:hover:bg-gray-800 text-gray-400 transition-colors">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Modal Tabs -->
        <div class="px-6 border-b border-gray-100 dark:border-gray-800 flex gap-6">
            <button onclick="switchModalTab('penjualan')" id="tab-modal-penjualan" class="py-4 text-xs font-bold border-b-2 border-primary text-primary transition-all">
                Riwayat Belanja
            </button>
            <button onclick="switchModalTab('wb')" id="tab-modal-wb" class="py-4 text-xs font-bold border-b-2 border-transparent text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-all">
                Wajib Belanja (Loyalitas)
            </button>
        </div>

        <!-- Modal Content (Scrollable) -->
        <div class="flex-1 overflow-y-auto p-6 bg-gray-50/30 dark:bg-gray-800/20">
            <div id="modal-loader" class="py-10 text-center text-xs text-gray-400 font-bold uppercase tracking-widest animate-pulse">Memuat riwayat...</div>
            
            <!-- Table Riwayat Belanja -->
            <div id="modal-content-penjualan" class="hidden">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-gray-400 font-bold uppercase tracking-tighter border-b border-gray-100 dark:border-gray-800">
                            <th class="py-2 text-left">No. Ref</th>
                            <th class="py-2 text-left">Tanggal</th>
                            <th class="py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody id="modal-body-penjualan" class="divide-y divide-gray-50 dark:divide-gray-800"></tbody>
                </table>
            </div>

            <!-- Table Riwayat WB -->
            <div id="modal-content-wb" class="hidden">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-gray-400 font-bold uppercase tracking-tighter border-b border-gray-100 dark:border-gray-800">
                            <th class="py-2 text-left">Tanggal</th>
                            <th class="py-2 text-left">Jenis</th>
                            <th class="py-2 text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody id="modal-body-wb" class="divide-y divide-gray-50 dark:divide-gray-800"></tbody>
                </table>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 text-[10px] text-gray-400 text-center">
            Menampilkan 20 aktivitas terbaru.
        </div>
    </div>
</div>

<?php
if (!isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
    require_once __DIR__ . '/../views/footer.php';
}
?>

<style>
.rank-badge-item {
    @apply inline-flex items-center justify-center w-6 h-6 rounded-full text-[10px] font-bold shadow-sm;
}
.rank-1-badge { @apply bg-yellow-100 text-yellow-700 border border-yellow-200; }
.rank-2-badge { @apply bg-gray-100 text-gray-700 border border-gray-200; }
.rank-3-badge { @apply bg-orange-100 text-orange-700 border border-orange-200; }

.row-highlight-1 { @apply bg-yellow-50/30 dark:bg-yellow-900/5; }
.row-highlight-2 { @apply bg-gray-50/30 dark:bg-gray-800/20; }
.row-highlight-3 { @apply bg-orange-50/30 dark:bg-orange-900/5; }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn { animation: fadeIn 0.4s ease-out forwards; }

@keyframes modalIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.animate-modalIn { animation: modalIn 0.2s ease-out forwards; }
</style>
