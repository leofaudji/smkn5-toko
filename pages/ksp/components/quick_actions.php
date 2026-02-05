<div class="grid grid-cols-5 gap-3 mb-6">
    <button onclick="switchTab('belanja')" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center shadow-sm group-active:scale-95 transition">
            <i class="bi bi-shop text-2xl"></i>
        </div>
        <span class="text-xs font-medium text-gray-600">Belanja</span>
    </button>
    <button onclick="switchTab('simpanan')" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-sm group-active:scale-95 transition">
            <i class="bi bi-piggy-bank text-2xl"></i>
        </div>
        <span class="text-xs font-medium text-gray-600">Simpanan</span>
    </button>
    <button onclick="switchTab('pinjaman')" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center shadow-sm group-active:scale-95 transition">
            <i class="bi bi-cash-coin text-2xl"></i>
        </div>
        <span class="text-xs font-medium text-gray-600">Pinjaman</span>
    </button>
    <button onclick="switchTab('simulasi')" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center shadow-sm group-active:scale-95 transition">
            <i class="bi bi-calculator text-2xl"></i>
        </div>
        <span class="text-xs font-medium text-gray-600">Simulasi</span>
    </button>
    <button onclick="openWithdrawalModal()" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center shadow-sm group-active:scale-95 transition">
            <i class="bi bi-box-arrow-down text-2xl"></i>
        </div>
        <span class="text-xs font-medium text-gray-600">Tarik Dana</span>
    </button>
    <button onclick="openTransferModal()" class="flex flex-col items-center gap-2 group">
        <div class="w-12 h-12 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center shadow-sm group-active:scale-95 transition">
            <i class="bi bi-send-fill text-xl"></i>
        </div>
        <span class="text-xs font-medium text-gray-600">Transfer</span>
    </button>
</div>