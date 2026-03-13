<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Audit Saldo</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Membandingkan saldo GL (Buku Besar) dengan nilai data operasional (Sub-Ledger)</p>
        </div>
        <button id="refresh-audit" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors">
            <i class="bi bi-arrow-clockwise mr-2"></i> Perbarui Data
        </button>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Modul / Data Operasional</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Akun GL Terkait</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nilai Sub-Ledger</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Saldo GL</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Selisih</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody id="audit-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Data will be loaded via JS -->
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
            <h3 class="text-blue-800 dark:text-blue-300 font-medium mb-2 flex items-center">
                <i class="bi bi-info-circle-fill mr-2"></i> Apa itu Audit Saldo?
            </h3>
            <p class="text-sm text-blue-700 dark:text-blue-400 leading-relaxed">
                Menu ini membantu memastikan akurasi pencatatan antara modul operasional (stok barang, piutang, simpanan) dengan pencatatan akuntansi di Buku Besar. Idealnya, nilai keduanya harus sama (Selisih = 0).
            </p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-lg border border-amber-100 dark:border-amber-800">
            <h3 class="text-amber-800 dark:text-amber-300 font-medium mb-2 flex items-center">
                <i class="bi bi-exclamation-triangle-fill mr-2"></i> Jika Ada Selisih?
            </h3>
            <p class="text-sm text-amber-700 dark:text-amber-400 leading-relaxed">
                Selisih biasanya terjadi karena adanya jurnal manual yang langsung ke akun terkait tanpa melalui modul, atau adanya penghapusan data transaksi secara tidak wajar. Lakukan pengecekan Buku Besar pada akun tersebut.
            </p>
        </div>
    </div>
</div>
