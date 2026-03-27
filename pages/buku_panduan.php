<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div
    class="flex justify-between flex-wrap md:flex-nowrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i
            class="bi bi-question-circle-fill"></i> Buku Panduan Aplikasi</h1>
    <div class="flex mb-2 md:mb-0">
        <a href="<?= base_url('/api/pdf?report=buku-panduan') ?>" target="_blank"
            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
            <i class="bi bi-printer-fill"></i> Cetak PDF
        </a>
    </div>
</div>

<div class="space-y-4" id="panduanAccordion">

    <!-- Panduan 0: Workflow -->
    <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm"
        data-controller="accordion-item">
        <h2 class="mb-0" id="headingZero">
            <button
                class="w-full flex items-center justify-between px-4 py-3 text-left font-bold text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                type="button" onclick="toggleAccordion(this)">
                <span class="flex items-center gap-2">
                    <i class="bi bi-diagram-3 text-slate-500"></i> Alur Kerja Aplikasi (Workflow)
                </span>
                <i class="bi bi-chevron-down transform transition-transform duration-200 rotate-180"></i>
            </button>
        </h2>
        <div id="collapseZero"
            class="p-4 border-t border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300"
            aria-labelledby="headingZero">
            <div class="max-w-4xl mx-auto">
                <p class="mb-6 text-center text-sm">Gunakan diagram di bawah ini sebagai panduan langkah demi langkah
                    dalam mengoperasikan sistem akuntansi koperasi Anda.</p>
                <div
                    class="mermaid-container overflow-x-auto bg-white dark:bg-gray-900 p-4 rounded-xl shadow-inner border border-slate-100 dark:border-slate-800">
                    <pre class="mermaid text-center" style="font-size: 1.1em;">
                    flowchart TD
                        %% Definisi Gaya (Styles)
                        classDef master fill:#f8fafc,stroke:#64748b,stroke-width:2px,color:#334155
                        classDef operational fill:#ecfdf5,stroke:#10b981,stroke-width:2px,color:#064e3b
                        classDef finance fill:#eff6ff,stroke:#3b82f6,stroke-width:2px,color:#1e3a8a
                        classDef final fill:#fff1f2,stroke:#f43f5e,stroke-width:2px,color:#881337
                        classDef report fill:#fefce8,stroke:#eab308,stroke-width:2px,color:#713f12

                        subgraph SYTEM_SETUP ["1. Persiapan Awal (Master Data)"]
                            A1{"Pengaturan Toko"}:::master
                            A2["Bagan Akun (COA)"]:::master
                            A3["Saldo Awal Neraca"]:::master
                            A4["Master Barang & Stok"]:::master
                            A1 --> A2 --> A3
                        end

                        subgraph DAILY_OPS ["2. Operasional Harian (Transaksi Toko)"]
                            direction LR
                            subgraph PROCUREMENT ["Pembelian Stok"]
                                B1("Input Pembelian"):::operational
                                B2("Update Stok In"):::operational
                                B3("Jurnal Hutang/Kas"):::operational
                                B1 --> B2 --> B3
                            end
                            subgraph POS_FLOW ["Penjualan (Kasir)"]
                                S1("Transaksi POS"):::operational
                                S2("Update Stok Out"):::operational
                                S3("Jurnal Pendapatan & HPP"):::operational
                                S1 --> S2 --> S3
                            end
                            subgraph OTH_TRANS ["Lainnya"]
                                O1("Biaya-Biaya"):::finance
                                O2("Wajib Belanja"):::finance
                                O3("Konsinyasi"):::finance
                            end
                        end

                        subgraph MONTHLY_PROCESS ["3. Proses Periodik & Penyesuaian"]
                            M1("Penyusutan Aset"):::finance
                            M2("Stock Opname"):::finance
                            M3("Rekonsiliasi Bank"):::finance
                        end

                        subgraph REPORTING ["4. Pelaporan & Analisis Laporan"]
                            R1>Buku Besar]:::report
                            R2>Neraca]:::report
                            R3>Laba Rugi]:::report
                            R4>Laporan Penjualan]:::report
                            R1 --> R2 & R3
                        end

                        subgraph CLOSING ["5. Akhir Periode"]
                            Z1([Proses Tutup Buku]):::final
                        end

                        %% Alur Utama (Main Flow)
                        A3 & A4 --> DAILY_OPS
                        B3 & S3 & O1 & O2 & O3 --> M1
                        M1 & M2 & M3 --> R1
                        R2 & R3 --> Z1

                        %% Hyperlinks
                        click A1 "<?= base_url('/settings') ?>"
                        click A2 "<?= base_url('/coa') ?>"
                        click A3 "<?= base_url('/saldo-awal') ?>"
                        click A4 "<?= base_url('/stok') ?>"
                        click B1 "<?= base_url('/pembelian') ?>"
                        click S1 "<?= base_url('/penjualan') ?>"
                        click O1 "<?= base_url('/transaksi') ?>"
                        click O2 "<?= base_url('/wajib-belanja') ?>"
                        click O3 "<?= base_url('/konsinyasi') ?>"
                        click M1 "<?= base_url('/aset-tetap') ?>"
                        click M2 "<?= base_url('/stok') ?>"
                        click M3 "<?= base_url('/rekonsiliasi-bank') ?>"
                        click R1 "<?= base_url('/buku-besar') ?>"
                        click R2 "<?= base_url('/laporan') ?>"
                        click R3 "<?= base_url('/laporan') ?>"
                        click R4 "<?= base_url('/laporan-penjualan') ?>"
                        click Z1 "<?= base_url('/tutup-buku') ?>"
                    </pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan 1: Persiapan Sistem -->
    <div class="border border-cyan-200 dark:border-cyan-900/50 rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm"
        data-controller="accordion-item">
        <h2 class="mb-0">
            <button
                class="w-full flex items-center justify-between px-4 py-3 text-left font-bold text-cyan-800 dark:text-cyan-100 bg-cyan-50 dark:bg-cyan-900/30 hover:bg-cyan-100 dark:hover:bg-cyan-900/50 transition-colors"
                type="button" onclick="toggleAccordion(this)">
                <span class="flex items-center gap-2">
                    <i class="bi bi-gear-wide-connected text-cyan-500"></i> 1. Persiapan Sistem & Master Data
                </span>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div class="hidden p-4 border-t border-cyan-100 dark:border-cyan-900/50 text-gray-700 dark:text-gray-300">
            <div class="space-y-6">
                <!-- Step 1.1 -->
                <div class="flex items-start gap-4">
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-xl bg-cyan-100 dark:bg-cyan-900/50 text-cyan-600 dark:text-cyan-400 flex items-center justify-center font-bold shadow-sm">
                        1</div>
                    <div class="flex-1">
                        <h6 class="font-bold text-cyan-900 dark:text-cyan-100 mb-1">Bagan Akun (COA)</h6>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Fondasi utama akuntansi. Siapkan daftar
                            akun (Kas, Hutang, Piutang, Pendapatan, Beban) sesuai kebutuhan koperasi.</p>
                        <div class="flex flex-wrap gap-2">
                            <a href="<?= base_url('/coa') ?>"
                                class="inline-flex items-center px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-medium rounded-lg transition-colors shadow-sm">
                                <i class="bi bi-list-ul mr-2"></i> Buka Bagan Akun
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Step 1.2 -->
                <div class="flex items-start gap-4">
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-xl bg-cyan-100 dark:bg-cyan-900/50 text-cyan-600 dark:text-cyan-400 flex items-center justify-center font-bold shadow-sm">
                        2</div>
                    <div class="flex-1">
                        <h6 class="font-bold text-cyan-900 dark:text-cyan-100 mb-1">Saldo Awal Neraca</h6>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Masukkan saldo terakhir dari pembukuan
                            manual atau sistem lama Anda.</p>
                        <div
                            class="p-3 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 rounded text-xs text-amber-800 dark:text-amber-200 mb-3">
                            <i class="bi bi-exclamation-triangle-fill mr-1"></i> <strong>Peringatan:</strong> Total
                            Debit harus SAMA dengan Total Kredit agar laporan keuangan balance.
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="<?= base_url('/saldo-awal-neraca') ?>"
                                class="inline-flex items-center px-3 py-1.5 border border-cyan-600 text-cyan-600 dark:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-900/20 text-xs font-medium rounded-lg transition-colors">
                                <i class="bi bi-plus-square mr-2"></i> Input Saldo Awal
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Step 1.3 -->
                <div class="flex items-start gap-4">
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-xl bg-cyan-100 dark:bg-cyan-900/50 text-cyan-600 dark:text-cyan-400 flex items-center justify-center font-bold shadow-sm">
                        3</div>
                    <div class="flex-1">
                        <h6 class="font-bold text-cyan-900 dark:text-cyan-100 mb-1">Mapping Akun Otomatis</h6>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Atur pemetaan akun untuk transaksi
                            otomatis seperti Penjualan, HPP, dan Persediaan.</p>
                        <div class="flex flex-wrap gap-2">
                            <a href="<?= base_url('/settings') ?>"
                                class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded-lg transition-colors shadow-sm">
                                <i class="bi bi-sliders mr-2"></i> Buka Pengaturan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan 2: Manajemen Kas & Bank -->
    <div class="border border-green-200 dark:border-green-900/50 rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm"
        data-controller="accordion-item">
        <h2 class="mb-0">
            <button
                class="w-full flex items-center justify-between px-4 py-3 text-left font-bold text-green-800 dark:text-green-100 bg-green-50 dark:bg-green-900/30 hover:bg-green-100 dark:hover:bg-green-900/50 transition-colors"
                type="button" onclick="toggleAccordion(this)">
                <span class="flex items-center gap-2">
                    <i class="bi bi-bank text-green-500"></i> 2. Operasional Kas, Bank & Rekonsiliasi
                </span>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div class="hidden p-4 border-t border-green-100 dark:border-green-900/50 text-gray-700 dark:text-gray-300">
            <div class="space-y-6">
                <div class="flex items-start gap-4">
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 flex items-center justify-center font-bold shadow-sm">
                        1</div>
                    <div class="flex-1">
                        <h6 class="font-bold text-green-900 dark:text-green-100 mb-1">Transaksi Kas Non-Dagang</h6>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Catat pengeluaran rutin (Gaji, Listrik)
                            atau penerimaan lain di luar transaksi dagang.</p>
                        <div
                            class="p-3 bg-green-50 dark:bg-green-900/20 rounded border border-green-100 dark:border-green-800 text-xs mb-3">
                            <span class="font-bold">Info Jurnal:</span><br>
                            Bayar Listrik Rp 500k: (D) Beban Listrik | (K) Kas Toko
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="<?= base_url('/transaksi') ?>"
                                class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors shadow-sm">
                                <i class="bi bi-cash-stack mr-2"></i> Buat Transaksi Kas
                            </a>
                        </div>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-xl bg-green-100 dark:bg-cyan-900/50 text-green-600 dark:text-green-400 flex items-center justify-center font-bold shadow-sm">
                        2</div>
                    <div class="flex-1">
                        <h6 class="font-bold text-green-900 dark:text-green-100 mb-1">Rekonsiliasi Bank</h6>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Lakukan pencocokan saldo bank di
                            aplikasi dengan rekening koran secara periodik.</p>
                        <div class="flex flex-wrap gap-2">
                            <a href="<?= base_url('/rekonsiliasi-bank') ?>"
                                class="inline-flex items-center px-3 py-1.5 border border-green-600 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 text-xs font-medium rounded-lg transition-colors">
                                <i class="bi bi-check-all mr-2"></i> Rekonsiliasi Bank
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan 3: Siklus Penjualan & Wajib Belanja -->
    <div class="border border-indigo-200 dark:border-indigo-900/50 rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm"
        data-controller="accordion-item">
        <h2 class="mb-0">
            <button
                class="w-full flex items-center justify-between px-4 py-3 text-left font-bold text-indigo-800 dark:text-indigo-100 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors"
                type="button" onclick="toggleAccordion(this)">
                <span class="flex items-center gap-2">
                    <i class="bi bi-cart-check text-indigo-500"></i> 3. Siklus Penjualan & Wajib Belanja (WB)
                </span>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div class="hidden p-4 border-t border-indigo-100 dark:border-indigo-900/50 text-gray-700 dark:text-gray-300">
            <div class="space-y-6">
                <section>
                    <div class="flex items-center gap-2 mb-3 text-indigo-900 dark:text-indigo-100 font-bold">
                        <i class="bi bi-1-circle-fill"></i> Jurnal Penjualan (Perpetual)
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Sistem mencatat Pendapatan sekaligus
                        mengurangi stok (HPP) secara otomatis setiap transaksi selesai.</p>

                    <div
                        class="p-4 bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500 rounded-lg text-sm mb-4 transition-all hover:shadow-md">
                        <div class="font-bold flex items-center gap-2 mb-2"><i
                                class="bi bi-lightbulb-fill text-amber-500"></i> Contoh Transaksi:</div>
                        Beras Premium terjual: Jual <strong>Rp 100.000</strong> | Modal <strong>Rp 80.000</strong>
                    </div>

                    <div
                        class="overflow-x-auto rounded-xl border border-indigo-100 dark:border-indigo-800 shadow-sm mb-4">
                        <table class="min-w-full text-xs">
                            <thead
                                class="bg-indigo-100/50 dark:bg-indigo-900/50 text-indigo-900 dark:text-indigo-200 font-bold">
                                <tr>
                                    <th class="p-3 text-left">Deskripsi Akun</th>
                                    <th class="p-3 text-right">Debit</th>
                                    <th class="p-3 text-right">Kredit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-indigo-50 dark:divide-indigo-900/30">
                                <tr>
                                    <td class="p-3">Kas Toko / Piutang Anggota</td>
                                    <td class="p-3 text-right font-bold text-green-600">100.000</td>
                                    <td class="p-3 text-right text-gray-400">-</td>
                                </tr>
                                <tr>
                                    <td class="p-3 ps-6 text-gray-500">Pendapatan Penjualan</td>
                                    <td class="p-3 text-right text-gray-400">-</td>
                                    <td class="p-3 text-right font-bold text-blue-600">100.000</td>
                                </tr>
                                <tr>
                                    <td class="p-3 bg-gray-50/50 dark:bg-gray-800/50">Harga Pokok Penjualan (HPP)</td>
                                    <td class="p-3 text-right font-bold text-red-600 bg-gray-50/50 dark:bg-gray-800/50">
                                        80.000</td>
                                    <td class="p-3 text-right text-gray-400 bg-gray-50/50 dark:bg-gray-800/50">-</td>
                                </tr>
                                <tr>
                                    <td class="p-3 ps-6 text-gray-500 bg-gray-50/50 dark:bg-gray-800/50">Persediaan
                                        Barang</td>
                                    <td class="p-3 text-right text-gray-400 bg-gray-50/50 dark:bg-gray-800/50">-</td>
                                    <td
                                        class="p-3 text-right font-bold text-amber-600 bg-gray-50/50 dark:bg-gray-800/50">
                                        80.000</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <hr class="border-indigo-100 dark:border-indigo-900/50">

                <section>
                    <div class="flex items-center gap-2 mb-3 text-indigo-900 dark:text-indigo-100 font-bold">
                        <i class="bi bi-2-circle-fill"></i> Mekanisme Wajib Belanja (WB)
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            class="group p-4 bg-white dark:bg-gray-900 rounded-xl border border-green-100 dark:border-green-900/50 shadow-sm transition-all hover:border-green-300">
                            <div
                                class="text-xs font-black text-green-600 uppercase tracking-widest mb-2 flex items-center justify-between">
                                Setoran WB (Top-up) <i class="bi bi-arrow-up-circle"></i>
                            </div>
                            <p class="text-[11px] text-gray-500 mb-2">Uang masuk, kewajiban koperasi bertambah.</p>
                            <table class="w-full text-xs font-mono">
                                <tr class="text-green-600">
                                    <td>(D) Kas</td>
                                    <td class="text-right">100k</td>
                                </tr>
                                <tr class="text-slate-500">
                                    <td>(K) Hutang WB</td>
                                    <td class="text-right">100k</td>
                                </tr>
                            </table>
                        </div>
                        <div
                            class="group p-4 bg-white dark:bg-gray-900 rounded-xl border border-red-100 dark:border-red-900/50 shadow-sm transition-all hover:border-red-300">
                            <div
                                class="text-xs font-black text-red-600 uppercase tracking-widest mb-2 flex items-center justify-between">
                                Belanja via WB <i class="bi bi-arrow-down-circle"></i>
                            </div>
                            <p class="text-[11px] text-gray-500 mb-2">Hutang WB ke anggota berkurang.</p>
                            <table class="w-full text-xs font-mono">
                                <tr class="text-red-600">
                                    <td>(D) Hutang WB</td>
                                    <td class="text-right">40k</td>
                                </tr>
                                <tr class="text-slate-500">
                                    <td>(K) Pendapatan</td>
                                    <td class="text-right">40k</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </section>

                <div class="flex flex-wrap gap-3 pt-2">
                    <a href="<?= base_url('/penjualan') ?>"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg hover:shadow-indigo-500/30">
                        <i class="bi bi-shop mr-2"></i> Menu Penjualan
                    </a>
                    <a href="<?= base_url('/wajib-belanja') ?>"
                        class="inline-flex items-center px-4 py-2 border-2 border-indigo-600 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 text-sm font-bold rounded-xl transition-all">
                        <i class="bi bi-piggy-bank mr-2"></i> Menu Wajib Belanja
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan 4: Siklus Pembelian & Stok -->
    <div class="border border-amber-200 dark:border-amber-900/50 rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm"
        data-controller="accordion-item">
        <h2 class="mb-0">
            <button
                class="w-full flex items-center justify-between px-4 py-3 text-left font-bold text-amber-800 dark:text-amber-100 bg-amber-50 dark:bg-amber-900/30 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors"
                type="button" onclick="toggleAccordion(this)">
                <span class="flex items-center gap-2">
                    <i class="bi bi-box-seam text-amber-500"></i> 4. Siklus Pembelian Stok & Persediaan
                </span>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div class="hidden p-4 border-t border-amber-100 dark:border-amber-900/50 text-gray-700 dark:text-gray-300">
            <div class="space-y-6">
                <!-- Step 4.1 -->
                <div class="flex items-start gap-4">
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold shadow-sm">
                        1</div>
                    <div class="flex-1">
                        <h6 class="font-bold text-amber-900 dark:text-amber-100 mb-1">Pencatatan Pembelian</h6>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Masukkan transaksi pembelian dari
                            supplier. Stok akan bertambah dan sistem akan mencatat hutang dagang.</p>

                        <div
                            class="p-3 bg-white dark:bg-gray-900 rounded-lg border border-amber-100 dark:border-amber-800 text-[11px] mb-3">
                            <span class="font-bold text-amber-600">Jurnal Preview:</span><br>
                            (D) Persediaan Barang Dagang <span class="float-right text-green-600 font-bold">+Rp
                                400.000</span><br>
                            (K) Utang Usaha / Kas Toko <span class="float-right text-red-500 font-bold">-Rp
                                400.000</span>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a href="<?= base_url('/pembelian') ?>"
                                class="inline-flex items-center px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium rounded-lg transition-colors shadow-sm">
                                <i class="bi bi-plus-circle mr-2"></i> Buat Pembelian
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Step 4.2 -->
                <div class="flex items-start gap-4">
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold shadow-sm">
                        2</div>
                    <div class="flex-1">
                        <h6 class="font-bold text-amber-900 dark:text-amber-100 mb-1">Kontrol Stok (Inventory)</h6>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Pantau kartu stok dan lakukan opname
                            jika terdapat selisih fisik barang.</p>
                        <div class="flex flex-wrap gap-2">
                            <a href="<?= base_url('/stok') ?>"
                                class="inline-flex items-center px-3 py-1.5 border border-amber-600 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 text-xs font-medium rounded-lg transition-colors">
                                <i class="bi bi-card-list mr-2"></i> Lihat Kartu Stok
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan 5: Barang Titipan (Konsinyasi) -->
    <div class="border border-purple-200 dark:border-purple-900/50 rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm"
        data-controller="accordion-item">
        <h2 class="mb-0">
            <button
                class="w-full flex items-center justify-between px-4 py-3 text-left font-bold text-purple-800 dark:text-purple-100 bg-purple-50 dark:bg-purple-900/30 hover:bg-purple-100 dark:hover:bg-purple-900/50 transition-colors"
                type="button" onclick="toggleAccordion(this)">
                <span class="flex items-center gap-2">
                    <i class="bi bi-tags text-purple-500"></i> 5. Barang Titipan (Konsinyasi)
                </span>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div class="hidden p-4 border-t border-purple-100 dark:border-purple-900/50 text-gray-700 dark:text-gray-300">
            <div class="space-y-6">
                <section>
                    <div class="flex items-center gap-2 mb-3 text-purple-900 dark:text-purple-100 font-bold">
                        <i class="bi bi-info-circle-fill"></i> Logika Akuntansi Konsinyasi
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Toko hanya bertindak sebagai agen.
                        Pendapatan yang diakui hanya murni dari <strong>Selisih Harga (Komisi)</strong>.</p>

                    <div
                        class="p-4 bg-purple-50 dark:bg-purple-900/20 border-l-4 border-purple-500 rounded-lg text-xs mb-4">
                        <div class="font-bold mb-1 italic">Contoh Alurnya:</div>
                        Produk snack dititip harga <strong>Rp 8.500</strong>. Toko menjual seharga <strong>Rp
                            10.000</strong>.
                        <ul class="mt-2 space-y-1">
                            <li><i class="bi bi-check2 text-purple-500"></i> Toko menerima Kas Rp 10.000 (D)</li>
                            <li><i class="bi bi-check2 text-purple-500"></i> Hutang ke Supplier Rp 8.500 (K)</li>
                            <li><i class="bi bi-check2 text-green-500 font-bold"></i> Laba Komisi Rp 1.500 (K)</li>
                        </ul>
                    </div>
                </section>

                <div class="flex flex-wrap gap-3">
                    <a href="<?= base_url('/konsinyasi') ?>"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-purple-500/30">
                        <i class="bi bi-clipboard-plus mr-2"></i> Kelola Konsinyasi
                    </a>
                    <a href="<?= base_url('/pelunasan-konsinyasi') ?>"
                        class="inline-flex items-center px-4 py-2 border-2 border-purple-600 text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 text-sm font-bold rounded-xl transition-all">
                        <i class="bi bi-wallet2 mr-2"></i> Pelunasan Supplier
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan 6: Penyesuaian & Aset Tetap -->
    <div class="border border-rose-200 dark:border-rose-900/50 rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm"
        data-controller="accordion-item">
        <h2 class="mb-0">
            <button
                class="w-full flex items-center justify-between px-4 py-3 text-left font-bold text-rose-800 dark:text-rose-100 bg-rose-50 dark:bg-rose-900/30 hover:bg-rose-100 dark:hover:bg-rose-900/50 transition-colors"
                type="button" onclick="toggleAccordion(this)">
                <span class="flex items-center gap-2">
                    <i class="bi bi-pencil-square text-rose-500"></i> 6. Jurnal Penyesuaian, Aset Tetap & Recurring
                </span>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div class="hidden p-4 border-t border-rose-100 dark:border-rose-900/50 text-gray-700 dark:text-gray-300">
            <div class="space-y-6">
                <!-- Step 6.1 -->
                <div class="flex items-start gap-4">
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold shadow-sm">
                        1</div>
                    <div class="flex-1">
                        <h6 class="font-bold text-rose-900 dark:text-rose-100 mb-1">Aset Tetap & Penyusutan</h6>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Daftarkan aset koperasi (Gedung, Mobil,
                            Alat) untuk menghitung penyusutan bulanan otomatis.</p>
                        <div class="flex flex-wrap gap-2">
                            <a href="<?= base_url('/aset-tetap') ?>"
                                class="inline-flex items-center px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-medium rounded-lg transition-colors shadow-sm">
                                <i class="bi bi-building mr-2"></i> Kelola Aset Tetap
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Step 6.2 -->
                <div class="flex items-start gap-4">
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold shadow-sm">
                        2</div>
                    <div class="flex-1">
                        <h6 class="font-bold text-rose-900 dark:text-rose-100 mb-1">Entri Jurnal Manual</h6>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Gunakan untuk koreksi saldo atau
                            transaksi yang tidak tersedia di menu otomatis.</p>
                        <div class="flex flex-wrap gap-2">
                            <a href="<?= base_url('/entri-jurnal') ?>"
                                class="inline-flex items-center px-3 py-1.5 border border-rose-600 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 text-xs font-medium rounded-lg transition-colors">
                                <i class="bi bi-journal-plus mr-2"></i> Buat Jurnal Manual
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan 7: Pelaporan & Laporan Keuangan -->
    <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm"
        data-controller="accordion-item">
        <h2 class="mb-0">
            <button
                class="w-full flex items-center justify-between px-4 py-3 text-left font-bold text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                type="button" onclick="toggleAccordion(this)">
                <span class="flex items-center gap-2">
                    <i class="bi bi-file-earmark-bar-graph text-slate-500"></i> 7. Pelaporan, Analisis & Arus Kas
                </span>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div class="hidden p-4 border-t border-slate-200 dark:border-slate-700 text-gray-700 dark:text-gray-300">
            <div class="space-y-4">
                <p class="text-sm leading-relaxed">Sistem menyediakan berbagai laporan akuntansi standar yang diperbarui
                    secara real-time:</p>
                <ul class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <li
                        class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-900 rounded border border-gray-100 dark:border-gray-800">
                        <i class="bi bi-check2-circle text-green-500"></i> Neraca Saldo & Lajur
                    </li>
                    <li
                        class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-900 rounded border border-gray-100 dark:border-gray-800">
                        <i class="bi bi-check2-circle text-green-500"></i> Laba Rugi (Komersial/Koperasi)
                    </li>
                    <li
                        class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-900 rounded border border-gray-100 dark:border-gray-800">
                        <i class="bi bi-check2-circle text-green-500"></i> Buku Besar per Akun
                    </li>
                    <li
                        class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-900 rounded border border-gray-100 dark:border-gray-800">
                        <i class="bi bi-check2-circle text-green-500"></i> Analisis Rasio Keuangan
                    </li>
                </ul>
                <div class="pt-2">
                    <a href="<?= base_url('/laporan') ?>"
                        class="inline-flex items-center px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-xs font-bold rounded-lg transition-colors shadow-md">
                        <i class="bi bi-file-earmark-text mr-2"></i> Buka Pusat Laporan
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan 8: Akhir Periode -->
    <div class="border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm"
        data-controller="accordion-item">
        <h2 class="mb-0">
            <button
                class="w-full flex items-center justify-between px-4 py-3 text-left font-bold text-gray-800 dark:text-gray-100 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                type="button" onclick="toggleAccordion(this)">
                <span class="flex items-center gap-2">
                    <i class="bi bi-calendar-check text-gray-600 dark:text-gray-400"></i> 8. Akhir Periode: Tutup Buku
                </span>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div class="hidden p-4 border-t border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300">
            <div class="space-y-4">
                <div
                    class="p-4 bg-gray-50 dark:bg-gray-900 border-l-4 border-gray-800 dark:border-gray-500 rounded text-sm text-gray-800 dark:text-gray-200">
                    <i class="bi bi-info-circle-fill mr-1"></i> <strong>Penting:</strong> Tutup buku akan memindahkan
                    Laba Tahun Berjalan ke Laba Ditahan dan mengunci transaksi di periode tersebut agar tidak bisa
                    diedit.
                </div>
                <div>
                    <a href="<?= base_url('/tutup-buku') ?>"
                        class="inline-flex items-center px-4 py-2 bg-black hover:bg-gray-900 text-white text-xs dark:bg-gray-100 dark:text-black dark:hover:bg-white font-bold rounded-lg transition-colors shadow-md">
                        <i class="bi bi-lock mr-2"></i> Buka Menu Tutup Buku
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan 9: Pengaturan & Konfigurasi -->
    <div class="border border-violet-200 dark:border-violet-900/50 rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow-sm"
        data-controller="accordion-item">
        <h2 class="mb-0">
            <button
                class="w-full flex items-center justify-between px-4 py-3 text-left font-bold text-violet-800 dark:text-violet-100 bg-violet-50 dark:bg-violet-900/30 hover:bg-violet-100 dark:hover:bg-violet-900/50 transition-colors"
                type="button" onclick="toggleAccordion(this)">
                <span class="flex items-center gap-2">
                    <i class="bi bi-sliders2-vertical text-violet-500"></i> 9. Konfigurasi Sistem (Admin)
                </span>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div class="hidden p-4 border-t border-violet-100 dark:border-violet-900/50 text-gray-700 dark:text-gray-300">
            <div class="space-y-4">
                <p class="text-sm">Gunakan menu ini untuk kustomisasi identitas koperasi dan pengaturan teknis sistem:
                </p>
                <ul class="space-y-2 text-xs">
                    <li class="flex items-center gap-2"><i class="bi bi-check2 text-violet-500"></i> Ubah Nama Koperasi
                        & Logo</li>
                    <li class="flex items-center gap-2"><i class="bi bi-check2 text-violet-500"></i> Atur Prefix Nomor
                        Bukti (Faktur, Jurnal, Kas)</li>
                    <li class="flex items-center gap-2"><i class="bi bi-check2 text-violet-500"></i> Pemetaan Akun
                        Otomatis</li>
                </ul>
                <div class="pt-2">
                    <a href="<?= base_url('/settings') ?>"
                        class="inline-flex items-center px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-lg transition-colors shadow-md">
                        <i class="bi bi-gear-fill mr-2"></i> Buka Pengaturan Sistem
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>



<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>