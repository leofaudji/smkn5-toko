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
    if (!generalSettingsContainer) return;

    async function loadSettings() {
        try {
            const response = await fetch(`${basePath}/api/settings`);
            const result = await response.json();

            if (result.status === 'success') {
                const settings = result.data;
                generalSettingsContainer.innerHTML = `
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="app_name" class="form-label">Nama Aplikasi</label>
                                <input type="text" class="form-control" id="app_name" name="app_name" value="${settings.app_name || ''}">
                            </div>
                            <div class="mb-3">
                                <label for="app_logo" class="form-label">Logo Aplikasi (PNG/JPG, maks 1MB)</label>
                                <input class="form-control" type="file" id="app_logo" name="app_logo" accept="image/png, image/jpeg">
                            </div>
                            <hr>
                            <h5 class="mb-3">Pengaturan Warna Halaman Login</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="login_bg_color" class="form-label">Warna Latar Samping</label>
                                    <input type="color" class="form-control form-control-color" id="login_bg_color" name="login_bg_color" value="${settings.login_bg_color || '#075E54'}" title="Pilih warna">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="login_btn_color" class="form-label">Warna Tombol Login</label>
                                    <input type="color" class="form-control form-control-color" id="login_btn_color" name="login_btn_color" value="${settings.login_btn_color || '#25D366'}" title="Pilih warna">
                                </div>
                            </div>

                            <hr>
                            <h5 class="mb-3">Pengaturan Header Laporan PDF</h5>
                            <div class="mb-3">
                                <label for="pdf_header_line1" class="form-label">Header Baris 1</label>
                                <input type="text" class="form-control" id="pdf_header_line1" name="pdf_header_line1" value="${settings.pdf_header_line1 || ''}" placeholder="cth: NAMA PENGURUS">
                            </div>
                            <div class="mb-3">
                                <label for="pdf_header_line2" class="form-label">Header Baris 2 (Nama Perusahaan)</label>
                                <input type="text" class="form-control" id="pdf_header_line2" name="pdf_header_line2" value="${settings.pdf_header_line2 || ''}" placeholder="cth: NAMA PERUSAHAAN ANDA">
                            </div>
                            <div class="mb-3">
                                <label for="pdf_header_line3" class="form-label">Header Baris 3 (Alamat)</label>
                                <input type="text" class="form-control" id="pdf_header_line3" name="pdf_header_line3" value="${settings.pdf_header_line3 || ''}" placeholder="cth: Alamat Sekretariat RT Anda">
                            </div>
                            <hr>
                            <h5 class="mb-3">Pengaturan Tanda Tangan Laporan</h5>
                             <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="signature_ketua_name" class="form-label">Nama Penanda Tangan 1 (Kanan)</label>
                                    <input type="text" class="form-control" id="signature_ketua_name" name="signature_ketua_name" value="${settings.signature_ketua_name || ''}" placeholder="cth: John Doe">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="signature_bendahara_name" class="form-label">Nama Penanda Tangan 2 (Kiri)</label>
                                    <input type="text" class="form-control" id="signature_bendahara_name" name="signature_bendahara_name" value="${settings.signature_bendahara_name || ''}" placeholder="cth: Jane Doe">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="app_city" class="form-label">Kota Laporan</label>
                                    <input type="text" class="form-control" id="app_city" name="app_city" value="${settings.app_city || ''}" placeholder="cth: Jakarta">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="stamp_image" class="form-label">Gambar Stempel (PNG Transparan)</label>
                                    <input class="form-control" type="file" id="stamp_image" name="stamp_image" accept="image/png">
                                    ${settings.stamp_image_exists ? `<div class="form-text">Stempel saat ini: <a href="${basePath}/${settings.stamp_image}" target="_blank">Lihat</a></div>` : ''}
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="signature_image" class="form-label">Gambar Tanda Tangan (PNG Transparan)</label>
                                    <input class="form-control" type="file" id="signature_image" name="signature_image" accept="image/png">
                                    ${settings.signature_image_exists ? `<div class="form-text">Tanda tangan saat ini: <a href="${basePath}/${settings.signature_image}" target="_blank">Lihat</a></div>` : ''}
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="letterhead_image" class="form-label">Gambar Kop Surat (PNG/JPG)</label>
                                    <input class="form-control" type="file" id="letterhead_image" name="letterhead_image" accept="image/png, image/jpeg">
                                    ${settings.letterhead_image_exists ? `<div class="form-text">Kop surat saat ini: <a href="${basePath}/${settings.letterhead_image}" target="_blank">Lihat</a></div>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <label class="form-label">Preview Logo</label>
                            <img id="logo-preview" src="${settings.app_logo ? basePath + '/' + settings.app_logo + '?t=' + new Date().getTime() : 'https://via.placeholder.com/150x50?text=Logo'}" class="img-thumbnail" alt="Logo Preview" style="max-height: 80px;">
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
            generalSettingsContainer.innerHTML = `<div class="alert alert-danger">Gagal memuat pengaturan: ${error.message}</div>`;
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
                <h5 class="mb-3">Nomor Referensi Otomatis</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="ref_pemasukan_prefix" class="form-label">Prefix Pemasukan</label>
                        <input type="text" class="form-control" id="ref_pemasukan_prefix" name="ref_pemasukan_prefix" value="${settings.ref_pemasukan_prefix || 'INV'}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="ref_pengeluaran_prefix" class="form-label">Prefix Pengeluaran</label>
                        <input type="text" class="form-control" id="ref_pengeluaran_prefix" name="ref_pengeluaran_prefix" value="${settings.ref_pengeluaran_prefix || 'EXP'}">
                    </div>
                </div>
                <hr>
                <h5 class="mb-3">Akun Kas Default</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="default_cash_in" class="form-label">Akun Kas Default untuk Pemasukan</label>
                        <select class="form-select" id="default_cash_in" name="default_cash_in">${cashOptions}</select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="default_cash_out" class="form-label">Akun Kas Default untuk Pengeluaran</label>
                        <select class="form-select" id="default_cash_out" name="default_cash_out">${cashOptions}</select>
                    </div>
                </div>
            `;
            // Set selected values
            if (settings.default_cash_in) document.getElementById('default_cash_in').value = settings.default_cash_in;
            if (settings.default_cash_out) document.getElementById('default_cash_out').value = settings.default_cash_out;

        } catch (error) {
            trxSettingsContainer.innerHTML = `<div class="alert alert-danger">Gagal memuat pengaturan transaksi: ${error.message}</div>`;
        }
    }

    async function loadArusKasSettings() {
        if (!cfMappingContainer) return;
        try {
            const response = await fetch(`${basePath}/api/settings?action=get_cf_accounts`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            let formHtml = '<div class="row">';
            result.data.forEach(acc => {
                formHtml += `
                    <div class="col-md-6 mb-3">
                        <label for="cf_mapping_${acc.id}" class="form-label small">${acc.kode_akun} - ${acc.nama_akun}</label>
                        <select class="form-select form-select-sm" id="cf_mapping_${acc.id}" name="cf_mapping[${acc.id}]">
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
            cfMappingContainer.innerHTML = `<div class="alert alert-danger">Gagal memuat pemetaan akun: ${error.message}</div>`;
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
                <div class="mb-3">
                    <label for="consignment_cash_account" class="form-label">Akun Kas (Penerimaan Penjualan)</label>
                    <select class="form-select" id="consignment_cash_account" name="consignment_cash_account">${createOptions(kas)}</select>
                </div>
                <div class="mb-3">
                    <label for="consignment_revenue_account" class="form-label">Akun Pendapatan Konsinyasi</label>
                    <select class="form-select" id="consignment_revenue_account" name="consignment_revenue_account">${createOptions(pendapatan)}</select>
                </div>
                <div class="mb-3">
                    <label for="consignment_cogs_account" class="form-label">Akun HPP Konsinyasi</label>
                    <select class="form-select" id="consignment_cogs_account" name="consignment_cogs_account">${createOptions(beban)}</select>
                </div>
                <div class="mb-3">
                    <label for="consignment_payable_account" class="form-label">Akun Utang Konsinyasi</label>
                    <select class="form-select" id="consignment_payable_account" name="consignment_payable_account">${createOptions(liabilitas)}</select>
                </div>
                <div class="mb-3">
                    <label for="consignment_inventory_account" class="form-label">Akun Persediaan Konsinyasi (Aset)</label>
                    <select class="form-select" id="consignment_inventory_account" name="consignment_inventory_account">${createOptions(persediaan)}</select>
                </div>
            `;

            // Set selected values
            if (settings.consignment_cash_account) document.getElementById('consignment_cash_account').value = settings.consignment_cash_account;
            if (settings.consignment_revenue_account) document.getElementById('consignment_revenue_account').value = settings.consignment_revenue_account;
            if (settings.consignment_cogs_account) document.getElementById('consignment_cogs_account').value = settings.consignment_cogs_account;
            if (settings.consignment_payable_account) document.getElementById('consignment_payable_account').value = settings.consignment_payable_account;
            if (settings.consignment_inventory_account) document.getElementById('consignment_inventory_account').value = settings.consignment_inventory_account;
        } catch (error) {
            konsinyasiSettingsContainer.innerHTML = `<div class="alert alert-danger">Gagal memuat pengaturan konsinyasi: ${error.message}</div>`;
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
                <div class="mb-3">
                    <label for="retained_earnings_account_id" class="form-label">Akun Laba Ditahan (Retained Earnings)</label>
                    <select class="form-select" id="retained_earnings_account_id" name="retained_earnings_account_id" required>
                        <option value="">-- Pilih Akun Ekuitas --</option>
                        ${equityOptions}
                    </select>
                    <div class="form-text">Akun ini digunakan untuk menyimpan laba bersih pada saat proses tutup buku.</div>
                </div>
                <hr>
                <h6 class="text-muted">Default Penjualan</h6>
                <div class="mb-3">
                    <label for="default_sales_cash_account_id" class="form-label">Akun Kas/Bank Default untuk Penjualan</label>
                    <select class="form-select" id="default_sales_cash_account_id" name="default_sales_cash_account_id">
                        <option value="">-- Pilih Akun Kas/Bank --</option>
                        ${cashOptions}
                    </select>
                    <div class="form-text">Pilih akun kas/bank yang akan menerima uang dari transaksi penjualan.</div>
                </div>
                <div class="mb-3">
                    <label for="default_sales_revenue_account_id" class="form-label">Akun Pendapatan Default untuk Penjualan</label>
                    <select class="form-select" id="default_sales_revenue_account_id" name="default_sales_revenue_account_id">
                        <option value="">-- Pilih Akun Pendapatan --</option>
                        ${revenueOptions}
                    </select>
                    <div class="form-text">Akun pendapatan yang digunakan jika tidak ada akun spesifik yang diatur pada barang.</div>
                </div>
                <hr>
                <h6 class="text-muted">Default Persediaan</h6>
                <div class="mb-3">
                    <label for="default_inventory_account_id" class="form-label">Akun Persediaan Default</label>
                    <select class="form-select" id="default_inventory_account_id" name="default_inventory_account_id">
                        <option value="">-- Pilih Akun Aset --</option>
                        ${inventoryOptions}
                    </select>
                    <div class="form-text">Akun persediaan yang digunakan jika tidak ada akun spesifik yang diatur pada barang.</div>
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

        } catch (error) {
            accountingSettingsContainer.innerHTML = `<div class="alert alert-danger">Gagal memuat pengaturan akuntansi: ${error.message}</div>`;
        }
    }

    saveGeneralSettingsBtn.addEventListener('click', async () => {
        const formData = new FormData(generalSettingsForm);
        const originalBtnHtml = saveGeneralSettingsBtn.innerHTML;
        saveGeneralSettingsBtn.disabled = true;
        saveGeneralSettingsBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...`;

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
            saveTrxSettingsBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;

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
            saveCfSettingsBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;

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
            saveKonsinyasiSettingsBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;

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
            saveAccountingSettingsBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;

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

    loadSettings();
    loadTransaksiSettings();
    loadArusKasSettings();
    loadKonsinyasiSettings();
    loadAccountingSettings();
}