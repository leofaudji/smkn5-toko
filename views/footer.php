<?php
if (!isset($app_name)) {
    $app_name = htmlspecialchars(get_setting('app_name', 'Aplikasi Keuangan'));
}
?>
    </main> <!-- end #main-content -->

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4 text-center text-sm text-gray-500 dark:text-gray-400 flex-shrink-0">
        &copy; <?= date('Y') ?> <?= $app_name ?> CRUDWorks. All rights reserved.
    </footer>

</div> <!-- end main content wrapper -->
</div> <!-- end #app-container -->

<!-- Toast Container -->
<div id="toast-container" class="fixed bottom-0 right-0 p-6 space-y-2 z-[100] w-full max-w-md">
    <!-- Toasts will be appended here by JavaScript -->
</div>

<!-- Generic Detail Modal -->
<div id="detailModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('detailModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="detailModalLabel">
                    Detail Transaksi
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('detailModal')">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="p-6 max-h-[70vh] overflow-y-auto" id="detailModalBody">
                <div class="text-center p-5">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Jadwal Berulang -->
<div id="recurringModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('recurringModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="recurringModalLabel">Atur Jadwal Berulang</h3>
                <div class="mt-4">
                    <form id="recurring-form" class="space-y-4">
                        <input type="hidden" id="recurring-id" name="id">
                        <input type="hidden" id="recurring-template-type" name="template_type">
                        <input type="hidden" id="recurring-template-data" name="template_data">
                        <input type="hidden" name="action" value="save_template">

                        <div>
                            <label for="recurring-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Template</label>
                            <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 sm:text-sm" id="recurring-name" name="name" placeholder="cth: Bayar Sewa Bulanan" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jadwalkan Setiap</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="number" class="block w-full rounded-none rounded-l-md border-gray-300 dark:border-gray-600 focus:border-primary focus:ring-primary dark:bg-gray-700 sm:text-sm" id="recurring-frequency-interval" name="frequency_interval" value="1" min="1">
                                <select class="block w-full rounded-none rounded-r-md border-l-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:border-primary focus:ring-primary sm:text-sm" id="recurring-frequency-unit" name="frequency_unit">
                                    <option value="day">Hari</option>
                                    <option value="week">Minggu</option>
                                    <option value="month" selected>Bulan</option>
                                    <option value="year">Tahun</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="recurring-start-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mulai Tanggal</label>
                            <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 sm:text-sm" id="recurring-start-date" name="start_date" required placeholder="DD-MM-YYYY">
                        </div>
                        <div>
                            <label for="recurring-end-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Berakhir Tanggal (Opsional)</label>
                            <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 sm:text-sm" id="recurring-end-date" name="end_date" placeholder="DD-MM-YYYY">
                        </div>
                    </form>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="save-recurring-template-btn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-600 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Simpan Template</button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('recurringModal')">Batal</button>
            </div>
        </div>
    </div>
</div>

<!-- JS Libraries -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@3.0.1/dist/chartjs-plugin-annotation.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php $v=date("Ymd"); ?>
<!-- Main App Logic -->
<script src="<?= base_url('assets/js/main.js?v='.$v) ?>"></script>
<script>
    // Small helper scripts to replace Bootstrap JS functionality
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
        document.getElementById('sidebar-overlay').classList.toggle('hidden');
    }

    function toggleDropdown(element) {
        const menu = element.nextElementSibling;
        menu.classList.toggle('hidden');
    }

    function toggleCollapse(element) {
        const content = element.nextElementSibling;
        const icon = element.querySelector('.bi-chevron-down');
        content.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
    }

    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        document.querySelectorAll('[data-controller="dropdown"]').forEach(function(dropdown) {
            if (!dropdown.contains(event.target)) {
                dropdown.querySelector('.dropdown-menu').classList.add('hidden');
            }
        });

        // Inisialisasi Flatpickr untuk modal global yang ada di footer
        if (typeof flatpickr !== 'undefined') {
            flatpickr("#recurring-start-date", {
                dateFormat: "d-m-Y",
                allowInput: true
            });
            flatpickr("#recurring-end-date", {
                dateFormat: "d-m-Y",
                allowInput: true
            });
        }
    });
</script>
</body>
</html> 