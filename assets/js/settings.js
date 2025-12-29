function initSettingsPage() {
    const generalSettingsContainer = document.getElementById('settings-container');
    const saveGeneralSettingsBtn = document.getElementById('save-settings-btn');
    const generalSettingsForm = document.getElementById('settings-form');
    const trxSettingsContainer = document.getElementById('transaksi-settings-container');
    const saveTrxSettingsBtn = document.getElementById('save-transaksi-settings-btn');
    const trxSettingsForm = document.getElementById('transaksi-settings-form');
    const cfMappingContainer = document.getElementById('arus-kas-mapping-container');
    const saveCfSettingsBtn = document.getElementById('save-arus-kas-settings-btn');
    const cfSettingsForm = document.getElementById('arus-kas-settings-form');
    const konsinyasiSettingsContainer = document.getElementById('konsinyasi-settings-container');
    const saveKonsinyasiSettingsBtn = document.getElementById('save-konsinyasi-settings-btn');
    const accountingSettingsContainer = document.getElementById('accounting-settings-container');
    const saveAccountingSettingsBtn = document.getElementById('save-accounting-settings-btn');
    const backupBtn = document.getElementById('backup-db-btn');
    const restoreForm = document.getElementById('restore-db-form');

    if (!generalSettingsContainer) return;

    async function loadSettings() {
        try {
            const response = await fetch(`${basePath}/api/settings`);
            const result = await response.json();

            if (result.status === 'success') {
                const settings = result.data;
                generalSettingsContainer.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                        <div class="md:col-span-8 space-y-6">
                            <div>
                                <label for="app_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Aplikasi</label>
                                <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="app_name" name="app_name" value="${settings.app_name || ''}">
                            </div>
                            <div>
                                <label for="app_logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Logo Aplikasi (PNG/JPG, maks 1MB)</label>
                                <input class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" type="file" id="app_logo" name="app_logo" accept="image/png, image/jpeg">
                            </div>
                            <hr class="border-gray-200 dark:border-gray-700">
                            <h5 class="text-lg font-medium text-gray-900 dark:text-white">Pengaturan Warna Halaman Login</h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="login_bg_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Warna Latar Samping</label>
                                    <input type="color" class="mt-1 h-10 w-full cursor-pointer rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700" id="login_bg_color" name="login_bg_color" value="${settings.login_bg_color || '#075E54'}" title="Pilih warna">
                                </div>
                                <div>
                                    <label for="login_btn_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Warna Tombol Login</label>
                                    <input type="color" class="mt-1 h-10 w-full cursor-pointer rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700" id="login_btn_color" name="login_btn_color" value="${settings.login_btn_color || '#25D366'}" title="Pilih warna">
                                </div>
                            </div>

                            <hr class="border-gray-200 dark:border-gray-700">
                            <h5 class="text-lg font-medium text-gray-900 dark:text-white">Pengaturan Header Laporan PDF</h5>
                            <div>
                                <label for="pdf_header_line1" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Header Baris 1</label>
                                <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="pdf_header_line1" name="pdf_header_line1" value="${settings.pdf_header_line1 || ''}" placeholder="cth: NAMA PENGURUS">
                            </div>
                            <div>
                                <label for="pdf_header_line2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Header Baris 2 (Nama Perusahaan)</label>
                                <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="pdf_header_line2" name="pdf_header_line2" value="${settings.pdf_header_line2 || ''}" placeholder="cth: NAMA PERUSAHAAN ANDA">
                            </div>
                            <div>
                                <label for="pdf_header_line3" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Header Baris 3 (Alamat)</label>
                                <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="pdf_header_line3" name="pdf_header_line3" value="${settings.pdf_header_line3 || ''}" placeholder="cth: Alamat Sekretariat RT Anda">
                            </div>
                            <hr class="border-gray-200 dark:border-gray-700">
                            <h5 class="text-lg font-medium text-gray-900 dark:text-white">Pengaturan Tanda Tangan Laporan</h5>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="signature_ketua_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Penanda Tangan 1 (Kanan)</label>
                                    <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="signature_ketua_name" name="signature_ketua_name" value="${settings.signature_ketua_name || ''}" placeholder="cth: John Doe">
                                </div>
                                <div>
                                    <label for="signature_bendahara_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Penanda Tangan 2 (Kiri)</label>
                                    <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="signature_bendahara_name" name="signature_bendahara_name" value="${settings.signature_bendahara_name || ''}" placeholder="cth: Jane Doe">
                                </div>
                                <div>
                                    <label for="app_city" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kota Laporan</label>
                                    <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="app_city" name="app_city" value="${settings.app_city || ''}" placeholder="cth: Jakarta">
                                </div>
                                <div>
                                    <label for="stamp_image" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gambar Stempel (PNG Transparan)</label>
                                    <input class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600" type="file" id="stamp_image" name="stamp_image" accept="image/png">
                                    ${settings.stamp_image_exists ? `<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Stempel saat ini: <a href="${basePath}/${settings.stamp_image}" target="_blank" class="text-primary hover:underline">Lihat</a></p>` : ''}
                                </div>
                                <div>
                                    <label for="signature_image" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gambar Tanda Tangan (PNG Transparan)</label>
                                    <input class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600" type="file" id="signature_image" name="signature_image" accept="image/png">
                                    ${settings.signature_image_exists ? `<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tanda tangan saat ini: <a href="${basePath}/${settings.signature_image}" target="_blank" class="text-primary hover:underline">Lihat</a></p>` : ''}
                                </div>
                                <div>
                                    <label for="letterhead_image" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gambar Kop Surat (PNG/JPG)</label>
                                    <input class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600" type="file" id="letterhead_image" name="letterhead_image" accept="image/png, image/jpeg">
                                    ${settings.letterhead_image_exists ? `<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kop surat saat ini: <a href="${basePath}/${settings.letterhead_image}" target="_blank" class="text-primary hover:underline">Lihat</a></p>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="md:col-span-4 text-center">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview Logo</label>
                            <img id="logo-preview" src="${settings.app_logo ? basePath + '/' + settings.app_logo + '?t=' + new Date().getTime() : 'https://via.placeholder.com/150x50?text=Logo'}" class="rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm mx-auto" alt="Logo Preview" style="max-height: 80px;">
                        </div>
                    </div>
                `;

                // Event listener untuk preview logo
                const logoInput = document.getElementById('app_logo');
                const logoPreview = document.getElementById('logo-preview');
                if (logoInput && logoPreview) {
                    logoInput.addEventListener('change', function() {
                        const file = this.files[0];
                        if (file) logoPreview.src = URL.createObjectURL(file);
                    });
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            generalSettingsContainer.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">Gagal memuat pengaturan: ${error.message}</div>`;
        }
    }

    async function loadTransaksiSettings() {
        if (!trxSettingsContainer) return;
        try {
            const [settingsRes, cashAccRes] = await Promise.all([
                fetch(`${basePath}/api/settings`),
                fetch(`${basePath}/api/settings?action=get_cash_accounts`)
            ]);
            const settingsResult = await settingsRes.json();
            const cashAccResult = await cashAccRes.json();

            if (settingsResult.status !== 'success' || cashAccResult.status !== 'success') {
                throw new Error(settingsResult.message || cashAccResult.message);
            }

            const settings = settingsResult.data;
            const cashAccounts = cashAccResult.data;

            let cashOptions = cashAccounts.map(acc => `<option value="${acc.id}">${acc.nama_akun}</option>`).join('');

            trxSettingsContainer.innerHTML = `
                <h5 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Nomor Referensi Otomatis</h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="ref_pemasukan_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prefix Pemasukan</label>
                        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="ref_pemasukan_prefix" name="ref_pemasukan_prefix" value="${settings.ref_pemasukan_prefix || 'INV'}">
                    </div>
                    <div>
                        <label for="ref_pengeluaran_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prefix Pengeluaran</label>
                        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="ref_pengeluaran_prefix" name="ref_pengeluaran_prefix" value="${settings.ref_pengeluaran_prefix || 'EXP'}">
                    </div>
                </div>
                <hr class="my-6 border-gray-200 dark:border-gray-700">
                <h5 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Akun Kas Default</h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="default_cash_in" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Kas Default untuk Pemasukan</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="default_cash_in" name="default_cash_in">${cashOptions}</select>
                    </div>
                    <div>
                        <label for="default_cash_out" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Kas Default untuk Pengeluaran</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="default_cash_out" name="default_cash_out">${cashOptions}</select>
                    </div>
                </div>
            `;
            // Set selected values
            if (settings.default_cash_in) document.getElementById('default_cash_in').value = settings.default_cash_in;
            if (settings.default_cash_out) document.getElementById('default_cash_out').value = settings.default_cash_out;

        } catch (error) {
            trxSettingsContainer.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">Gagal memuat pengaturan transaksi: ${error.message}</div>`;
        }
    }

    async function loadArusKasSettings() {
        if (!cfMappingContainer) return;
        try {
            const response = await fetch(`${basePath}/api/settings?action=get_cf_accounts`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            let formHtml = '<div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">';
            result.data.forEach(acc => {
                formHtml += `
                    <div>
                        <label for="cf_mapping_${acc.id}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">${acc.kode_akun} - ${acc.nama_akun}</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="cf_mapping_${acc.id}" name="cf_mapping[${acc.id}]">
                            <option value="">-- Tidak Diklasifikasikan (Operasi) --</option>
                            <option value="Operasi" ${acc.cash_flow_category === 'Operasi' ? 'selected' : ''}>Operasi</option>
                            <option value="Investasi" ${acc.cash_flow_category === 'Investasi' ? 'selected' : ''}>Investasi</option>
                            <option value="Pendanaan" ${acc.cash_flow_category === 'Pendanaan' ? 'selected' : ''}>Pendanaan</option>
                        </select>
                    </div>
                `;
            });
            formHtml += '</div>';
            cfMappingContainer.innerHTML = formHtml;

        } catch (error) {
            cfMappingContainer.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">Gagal memuat pemetaan akun: ${error.message}</div>`;
        }
    }

    async function loadKonsinyasiSettings() {
        if (!konsinyasiSettingsContainer) return;
        try {
            const [settingsRes, accountsRes] = await Promise.all([
                fetch(`${basePath}/api/settings`),
                fetch(`${basePath}/api/settings?action=get_accounts_for_consignment`)
            ]);
            const settingsResult = await settingsRes.json();
            const accountsResult = await accountsRes.json(); // This contains {kas, pendapatan, beban, liabilitas, persediaan}

            if (settingsResult.status !== 'success' || accountsResult.status !== 'success') {
                throw new Error(settingsResult.message || accountsResult.message);
            }

            const settings = settingsResult.data;
            const { kas = [], pendapatan = [], beban = [], liabilitas = [], persediaan = [] } = accountsResult.data;

            const createOptions = (accounts) => (accounts || []).map(acc => `<option value="${acc.id}">${acc.nama_akun}</option>`).join('');

            konsinyasiSettingsContainer.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <label for="consignment_cash_account" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Kas (Penerimaan Penjualan)</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="consignment_cash_account" name="consignment_cash_account">${createOptions(kas)}</select>
                    </div>
                    <div>
                        <label for="consignment_revenue_account" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Pendapatan Konsinyasi</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="consignment_revenue_account" name="consignment_revenue_account">${createOptions(pendapatan)}</select>
                    </div>
                    <div>
                        <label for="consignment_cogs_account" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun HPP Konsinyasi</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="consignment_cogs_account" name="consignment_cogs_account">${createOptions(beban)}</select>
                    </div>
                    <div>
                        <label for="consignment_payable_account" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Utang Konsinyasi</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="consignment_payable_account" name="consignment_payable_account">${createOptions(liabilitas)}</select>
                    </div>
                    <div>
                        <label for="consignment_inventory_account" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Persediaan Konsinyasi (Aset)</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="consignment_inventory_account" name="consignment_inventory_account">${createOptions(persediaan)}</select>
                    </div>
                </div>
            `;

            // Set selected values
            if (settings.consignment_cash_account) document.getElementById('consignment_cash_account').value = settings.consignment_cash_account;
            if (settings.consignment_revenue_account) document.getElementById('consignment_revenue_account').value = settings.consignment_revenue_account;
            if (settings.consignment_cogs_account) document.getElementById('consignment_cogs_account').value = settings.consignment_cogs_account;
            if (settings.consignment_payable_account) document.getElementById('consignment_payable_account').value = settings.consignment_payable_account;
            if (settings.consignment_inventory_account) document.getElementById('consignment_inventory_account').value = settings.consignment_inventory_account;
        } catch (error) {
            konsinyasiSettingsContainer.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">Gagal memuat pengaturan konsinyasi: ${error.message}</div>`;
        }
    }

    async function loadAccountingSettings() {
        if (!accountingSettingsContainer) return;
        try {
            const [settingsRes, accountsRes] = await Promise.all([
                fetch(`${basePath}/api/settings`),
                fetch(`${basePath}/api/settings?action=get_accounts_for_accounting`)
            ]);
            const settingsResult = await settingsRes.json();
            const accountsResult = await accountsRes.json();

            if (settingsResult.status !== 'success' || accountsResult.status !== 'success') {
                throw new Error(settingsResult.message || accountsResult.message);
            }

            const settings = settingsResult.data;
            const { equity: equityAccounts, cash: cashAccounts, revenue: revenueAccounts, cogs: cogsAccounts, inventory: inventoryAccounts } = accountsResult.data;

            let equityOptions = equityAccounts.map(acc => `<option value="${acc.id}">${acc.kode_akun} - ${acc.nama_akun}</option>`).join('');
            let cashOptions = cashAccounts.map(acc => `<option value="${acc.id}">${acc.kode_akun} - ${acc.nama_akun}</option>`).join('');
            let revenueOptions = revenueAccounts.map(acc => `<option value="${acc.id}">${acc.kode_akun} - ${acc.nama_akun}</option>`).join('');
            let cogsOptions = cogsAccounts.map(acc => `<option value="${acc.id}">${acc.kode_akun} - ${acc.nama_akun}</option>`).join('');
            let inventoryOptions = inventoryAccounts.map(acc => `<option value="${acc.id}">${acc.kode_akun} - ${acc.nama_akun}</option>`).join('');

            accountingSettingsContainer.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <label for="retained_earnings_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Laba Ditahan (Retained Earnings)</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="retained_earnings_account_id" name="retained_earnings_account_id" required>
                            <option value="">-- Pilih Akun Ekuitas --</option>
                            ${equityOptions}
                        </select>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Akun ini digunakan untuk menyimpan laba bersih pada saat proses tutup buku.</p>
                    </div>
                    <hr class="border-gray-200 dark:border-gray-700">
                    <h6 class="text-base font-medium text-gray-600 dark:text-gray-400">Default Penjualan</h6>
                    <div>
                        <label for="default_sales_cash_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Kas/Bank Default untuk Penjualan</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="default_sales_cash_account_id" name="default_sales_cash_account_id">
                            <option value="">-- Pilih Akun Kas/Bank --</option>
                            ${cashOptions}
                        </select>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Pilih akun kas/bank yang akan menerima uang dari transaksi penjualan.</p>
                    </div>
                    <div>
                        <label for="default_sales_revenue_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Pendapatan Default untuk Penjualan</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="default_sales_revenue_account_id" name="default_sales_revenue_account_id">
                            <option value="">-- Pilih Akun Pendapatan --</option>
                            ${revenueOptions}
                        </select>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Akun pendapatan yang digunakan jika tidak ada akun spesifik yang diatur pada barang.</p>
                    </div>
                    <hr class="border-gray-200 dark:border-gray-700">
                    <h6 class="text-base font-medium text-gray-600 dark:text-gray-400">Default Persediaan</h6>
                    <div>
                        <label for="default_inventory_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Persediaan Default</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="default_inventory_account_id" name="default_inventory_account_id">
                        <option value="">-- Pilih Akun Aset --</option>
                        ${inventoryOptions}
                    </select>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Akun persediaan yang digunakan jika tidak ada akun spesifik yang diatur pada barang.</p>
                    </div>
                    <div>
                        <label for="default_cogs_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akun HPP (COGS) Default</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary focus:ring-primary sm:text-sm" id="default_cogs_account_id" name="default_cogs_account_id">
                            <option value="">-- Pilih Akun Beban --</option>
                            ${cogsOptions}
                        </select>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Akun HPP yang digunakan jika tidak ada akun spesifik yang diatur pada barang.</p>
                    </div>
                </div>
            `;
            // Set selected value
            if (settings.retained_earnings_account_id) {
                document.getElementById('retained_earnings_account_id').value = settings.retained_earnings_account_id;
            }
            if (settings.default_sales_cash_account_id) {
                document.getElementById('default_sales_cash_account_id').value = settings.default_sales_cash_account_id;
            }
            if (settings.default_sales_revenue_account_id) {
                document.getElementById('default_sales_revenue_account_id').value = settings.default_sales_revenue_account_id;
            }
            if (settings.default_inventory_account_id) {
                document.getElementById('default_inventory_account_id').value = settings.default_inventory_account_id;
            }
            if (settings.default_cogs_account_id) {
                document.getElementById('default_cogs_account_id').value = settings.default_cogs_account_id;
            }

        } catch (error) {
            accountingSettingsContainer.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">Gagal memuat pengaturan akuntansi: ${error.message}</div>`;
        }
    }

    saveGeneralSettingsBtn.addEventListener('click', async () => {
        const formData = new FormData(generalSettingsForm);
        const originalBtnHtml = saveGeneralSettingsBtn.innerHTML;
        saveGeneralSettingsBtn.disabled = true;
        saveGeneralSettingsBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;

        try {
            const minDelay = new Promise(resolve => setTimeout(resolve, 500));
            const fetchPromise = fetch(`${basePath}/api/settings`, { method: 'POST', body: formData });

            const [response] = await Promise.all([fetchPromise, minDelay]);

            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                loadSettings(); // Reload settings
                showToast('Beberapa perubahan mungkin memerlukan refresh halaman untuk diterapkan.', 'info', 'Informasi');
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveGeneralSettingsBtn.disabled = false;
            saveGeneralSettingsBtn.innerHTML = originalBtnHtml;
        }
    });

    if (saveTrxSettingsBtn) {
        saveTrxSettingsBtn.addEventListener('click', async () => {
            const formData = new FormData(trxSettingsForm);
            const originalBtnHtml = saveTrxSettingsBtn.innerHTML;
            saveTrxSettingsBtn.disabled = true;
            saveTrxSettingsBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;

            try {
                const minDelay = new Promise(resolve => setTimeout(resolve, 500));
                const fetchPromise = fetch(`${basePath}/api/settings`, { method: 'POST', body: formData });
                const [response] = await Promise.all([fetchPromise, minDelay]);
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') {
                    loadTransaksiSettings();
                }
            } catch (error) {
                showToast('Terjadi kesalahan jaringan.', 'error');
            } finally {
                saveTrxSettingsBtn.disabled = false;
                saveTrxSettingsBtn.innerHTML = originalBtnHtml;
            }
        });
    }

    if (saveCfSettingsBtn) {
        saveCfSettingsBtn.addEventListener('click', async () => {
            const formData = new FormData(cfSettingsForm);
            const originalBtnHtml = saveCfSettingsBtn.innerHTML;
            saveCfSettingsBtn.disabled = true;
            saveCfSettingsBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;

            try {
                const minDelay = new Promise(resolve => setTimeout(resolve, 500));
                const fetchPromise = fetch(`${basePath}/api/settings`, { method: 'POST', body: formData });
                const [response] = await Promise.all([fetchPromise, minDelay]);
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') {
                    loadArusKasSettings();
                }
            } catch (error) {
                showToast('Terjadi kesalahan jaringan.', 'error');
            } finally {
                saveCfSettingsBtn.disabled = false;
                saveCfSettingsBtn.innerHTML = originalBtnHtml;
            }
        });
    }

    if (saveKonsinyasiSettingsBtn) {
        saveKonsinyasiSettingsBtn.addEventListener('click', async () => {
            const form = document.getElementById('konsinyasi-settings-form');
            const formData = new FormData(form);
            const originalBtnHtml = saveKonsinyasiSettingsBtn.innerHTML;
            saveKonsinyasiSettingsBtn.disabled = true;
            saveKonsinyasiSettingsBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;

            try {
                const response = await fetch(`${basePath}/api/settings`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
            } catch (error) {
                showToast('Terjadi kesalahan jaringan.', 'error');
            } finally {
                saveKonsinyasiSettingsBtn.disabled = false;
                saveKonsinyasiSettingsBtn.innerHTML = originalBtnHtml;
            }
        });
    }

    if (saveAccountingSettingsBtn) {
        saveAccountingSettingsBtn.addEventListener('click', async () => {
            const form = document.getElementById('accounting-settings-form');
            const formData = new FormData(form);
            const originalBtnHtml = saveAccountingSettingsBtn.innerHTML;
            saveAccountingSettingsBtn.disabled = true;
            saveAccountingSettingsBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;

            try {
                const response = await fetch(`${basePath}/api/settings`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') {
                    loadAccountingSettings();
                }
            } catch (error) {
                showToast('Terjadi kesalahan jaringan.', 'error');
            } finally {
                saveAccountingSettingsBtn.disabled = false;
                saveAccountingSettingsBtn.innerHTML = originalBtnHtml;
            }
        });
    }

    async function handleBackup(e) {
        e.preventDefault();
        const btn = e.currentTarget;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Membuat backup...`;

        try {
            const response = await fetch(`${basePath}/api/backup_restore.php?action=backup`);
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Gagal membuat backup.');
            }

            // Trigger file download
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            // Dapatkan nama file dari header Content-Disposition
            const disposition = response.headers.get('Content-Disposition');
            let filename = 'backup.sql';
            if (disposition && disposition.indexOf('attachment') !== -1) {
                const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                const matches = filenameRegex.exec(disposition);
                if (matches && matches[1]) {
                    filename = matches[1].replace(/['"]/g, '');
                }
            }
            a.download = filename;

            // Beri tahu router SPA di main.js untuk mengabaikan klik ini.
            // Ini adalah solusi yang lebih andal daripada hanya mengandalkan setTimeout.
            a.setAttribute('data-spa-ignore', 'true');

            document.body.appendChild(a);
            a.click();

            // Bersihkan elemen link dan URL blob setelah jeda singkat untuk memastikan download dimulai.
            setTimeout(() => {
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }, 150);

            showToast('Backup berhasil diunduh.', 'success');

        } catch (error) {
            showToast(error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    async function handleRestore(e) {
        e.preventDefault();
        const form = e.currentTarget;
        const fileInput = document.getElementById('backup-file');
        
        if (fileInput.files.length === 0) {
            showToast('Silakan pilih file backup terlebih dahulu.', 'error');
            return;
        }

        const confirmed = await Swal.fire({
            title: 'ANDA YAKIN?',
            html: "Aksi ini akan <strong>MENGHAPUS SEMUA DATA SAAT INI</strong> dan menggantinya dengan data dari file backup. <br><br><strong>Aksi ini tidak dapat dibatalkan.</strong>",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, pulihkan sekarang!',
            cancelButtonText: 'Batal'
        });

        if (!confirmed.isConfirmed) {
            return;
        }

        const btn = document.getElementById('restore-db-btn');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memulihkan...`;

        const formData = new FormData(form);
        formData.append('action', 'restore');

        try {
            const response = await fetch(`${basePath}/api/backup_restore.php`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                await Swal.fire({
                    title: 'Pemulihan Berhasil!',
                    text: result.message + ' Aplikasi akan dimuat ulang.',
                    icon: 'success',
                    timer: 5000,
                    timerProgressBar: true,
                    willClose: () => {
                        location.reload();
                    }
                });
                location.reload();
            } else {
                throw new Error(result.message);
            }

        } catch (error) {
            Swal.fire('Gagal!', error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            form.reset();
        }
    }

    function setupTabs() {
        const tabContainer = document.getElementById('settingsTab');
        if (!tabContainer) return;
        const tabButtons = tabContainer.querySelectorAll('.settings-tab-btn');
        const tabPanes = document.getElementById('settingsTabContent').querySelectorAll('.settings-tab-pane');

        function switchTab(targetId) {
            tabPanes.forEach(pane => pane.classList.toggle('hidden', pane.id !== targetId));
            tabButtons.forEach(button => {
                const isActive = button.dataset.target === `#${targetId}`;
                button.classList.toggle('border-primary', isActive);
                button.classList.toggle('text-primary', isActive);
                button.classList.toggle('border-transparent', !isActive);
                button.classList.toggle('text-gray-500', !isActive);
                button.classList.toggle('dark:text-gray-400', !isActive);
            });
        }

        tabButtons.forEach(button => {
            button.addEventListener('click', () => switchTab(button.dataset.target.substring(1)));
        });

        switchTab('general-settings'); // Initial active tab
    }

    if (backupBtn) {
        backupBtn.addEventListener('click', handleBackup);
    }
    if (restoreForm) {
        restoreForm.addEventListener('submit', handleRestore);
    }

    setupTabs();
    loadSettings();
    loadTransaksiSettings();
    loadArusKasSettings();
    loadKonsinyasiSettings();
    loadAccountingSettings();
}