<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="flex justify-between flex-wrap md:flex-nowrap items-center pt-3 pb-2 mb-3 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white flex items-center gap-2"><i class="bi bi-question-circle-fill"></i> Buku Panduan Aplikasi</h1>
    <div class="flex mb-2 md:mb-0">
        <a href="<?= base_url('/api/pdf?report=buku-panduan') ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
            <i class="bi bi-printer-fill"></i> Cetak PDF
        </a>
    </div>
</div>

<div class="space-y-4" id="panduanAccordion">

    <!-- Panduan 0: Workflow -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800" data-controller="accordion-item">
        <h2 class="mb-0" id="headingZero">
            <button class="w-full flex items-center justify-between px-4 py-3 text-left font-medium text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none transition-colors" type="button" onclick="toggleAccordion(this)">
                <strong>Alur Kerja Aplikasi (Workflow)</strong>
                <i class="bi bi-chevron-down transform transition-transform duration-200 rotate-180"></i>
            </button>
        </h2>
        <div id="collapseZero" class="block p-4 border-t border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300" aria-labelledby="headingZero">
            <div>
                <p class="mb-4">Berikut adalah gambaran alur kerja yang direkomendasikan untuk menggunakan aplikasi ini secara efektif dari awal hingga akhir periode akuntansi.</p>
                <div class="mermaid-container overflow-x-auto">
                    <pre class="mermaid text-center" style="font-size: 1.1em;">
                    graph TD
                        %% Define Styles
                        %% Menambahkan font-size ke dalam style
                        classDef setup fill:#e0f7fa,stroke:#0097a7,stroke-width:2px,color:#004d40
                        classDef daily fill:#e8f5e9,stroke:#388e3c,stroke-width:2px,color:#1b5e20
                        classDef periodic fill:#fffde7,stroke:#fbc02d,stroke-width:2px,color:#f57f17
                        classDef report fill:#e3f2fd,stroke:#1976d2,stroke-width:2px,color:#0d47a1
                        classDef final fill:#fce4ec,stroke:#c2185b,stroke-width:2px,color:#880e4f

                        subgraph " "
                            direction LR
                            subgraph "Tahap 1: Setup Awal"
                                direction TB
                                A1{"Konfigurasi Pengaturan"}:::setup
                                A2["Siapkan Bagan Akun (COA)"]:::setup
                                A3["Isi Saldo Awal"]:::setup
                                A1 --> A2 --> A3
                            end
    
                            subgraph "Tahap 2: Operasional Harian"
                                direction TB
                                B1("Catat Transaksi Kas"):::daily
                                B2("Buat Jurnal Manual"):::daily
                                B3("Kelola Konsinyasi"):::daily
                                B4("Catat Aset Baru"):::daily
                            end
    
                            subgraph "Tahap 3: Proses Periodik (Bulanan)"
                                direction TB
                                C1("Posting Penyusutan"):::periodic
                                C2("Rekonsiliasi Bank"):::periodic
                            end
    
                            subgraph "Tahap 4: Pelaporan & Analisis"
                                direction TB
                                D1>Laporan Keuangan]:::report
                                D2>Buku Besar]:::report
                                D3>Analisis Rasio]:::report
                            end
    
                            subgraph "Tahap 5: Akhir Periode (Tahunan)"
                                direction TB
                                E1([Proses Tutup Buku]):::final
                            end
                        end

                        A --> B --> C --> D --> E

                        %% Define Clickable Links
                        click A1 "<?= base_url('/settings') ?>" "Buka Pengaturan" _blank
                        click A2 "<?= base_url('/coa') ?>" "Buka Bagan Akun" _blank
                        click A3 "<?= base_url('/saldo-awal-neraca') ?>" "Buka Saldo Awal" _blank
                        click B1 "<?= base_url('/transaksi') ?>" "Buka Transaksi" _blank
                        click B2 "<?= base_url('/entri-jurnal') ?>" "Buka Entri Jurnal" _blank
                        click B3 "<?= base_url('/konsinyasi') ?>" "Buka Konsinyasi" _blank
                        click B4 "<?= base_url('/aset-tetap') ?>" "Buka Aset Tetap" _blank
                        click C1 "<?= base_url('/aset-tetap') ?>" "Buka Aset Tetap" _blank
                        click C2 "<?= base_url('/rekonsiliasi-bank') ?>" "Buka Rekonsiliasi" _blank
                        click D1 "<?= base_url('/laporan') ?>" "Buka Laporan" _blank
                        click D2 "<?= base_url('/buku-besar') ?>" "Buka Buku Besar" _blank
                        click D3 "<?= base_url('/analisis-rasio') ?>" "Buka Analisis Rasio" _blank
                        click E1 "<?= base_url('/tutup-buku') ?>" "Buka Tutup Buku" _blank
                    </pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan 1: Pengaturan Awal -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800" data-controller="accordion-item">
        <h2 class="mb-0" id="headingOne">
            <button class="w-full flex items-center justify-between px-4 py-3 text-left font-medium text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none transition-colors" type="button" onclick="toggleAccordion(this)">
                <strong>1. Pengaturan Awal (Penting!)</strong>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div id="collapseOne" class="hidden p-4 border-t border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300" aria-labelledby="headingOne">
            <div>
                <p>Sebelum memulai pencatatan, ada dua langkah krusial yang harus dilakukan untuk memastikan data akurat.</p>
                <h5>Langkah 1.1: Menyiapkan Bagan Akun (COA)</h5>
                <ol>
                    <li>Buka menu <strong>Master Data &raquo; Bagan Akun (COA)</strong>.</li>
                    <li>Sistem sudah menyediakan akun-akun standar. Anda bisa menambah, mengubah, atau menghapus akun sesuai kebutuhan.</li>
                    <li>Saat menambah akun, pastikan Anda memilih <strong>Tipe Akun</strong> yang benar (Aset, Liabilitas, Ekuitas, Pendapatan, atau Beban).</li>
                    <li>Centang kotak <strong>"Ini adalah akun Kas/Bank"</strong> untuk akun-akun yang berfungsi sebagai tempat penyimpanan uang (Kas, Bank BCA, dll.). Akun ini akan muncul di form transaksi.</li>
                </ol>
                <hr>
                <h5>Langkah 1.2: Mengisi Saldo Awal</h5>
                <ol>
                    <li>Buka menu <strong>Master Data &raquo; Saldo Awal Neraca</strong>.</li>
                    <li>Masukkan saldo akhir dari periode sebelumnya ke dalam kolom Debit atau Kredit sesuai dengan saldo normal akun.
                        <ul>
                            <li><strong>Aset:</strong> Saldo normalnya di <strong>Debit</strong>.</li>
                            <li><strong>Liabilitas & Ekuitas:</strong> Saldo normalnya di <strong>Kredit</strong>.</li>
                        </ul>
                    </li>
                    <li>Pastikan <strong>Total Debit dan Total Kredit seimbang (BALANCE)</strong> sebelum menyimpan.</li>
                </ol>
                <div class="flex gap-2 mt-3">
                    <a href="<?= base_url('/coa') ?>" class="inline-flex items-center px-3 py-1.5 border border-primary text-xs font-medium rounded text-primary bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-2"></i>Buka Bagan Akun
                    </a>
                    <a href="<?= base_url('/saldo-awal-neraca') ?>" class="inline-flex items-center px-3 py-1.5 border border-primary text-xs font-medium rounded text-primary bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-2"></i>Buka Saldo Awal
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan 2: Transaksi Harian -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800" data-controller="accordion-item">
        <h2 class="mb-0" id="headingTwo">
            <button class="w-full flex items-center justify-between px-4 py-3 text-left font-medium text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none transition-colors" type="button" onclick="toggleAccordion(this)">
                <strong>2. Mencatat Transaksi Harian</strong>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div id="collapseTwo" class="hidden p-4 border-t border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300" aria-labelledby="headingTwo">
            <div>
                <p>Gunakan menu <strong>Transaksi</strong> untuk mencatat pemasukan dan pengeluaran kas harian yang sederhana.</p>
                <ol>
                    <li>Buka menu <strong>Kas & Bank &raquo; Transaksi</strong>.</li>
                    <li>Klik tombol <strong>"Tambah Transaksi"</strong>.</li>
                    <li>Pilih jenis transaksi: <strong>Pengeluaran</strong>, <strong>Pemasukan</strong>, atau <strong>Transfer</strong> antar akun kas.</li>
                    <li>Isi tanggal, jumlah, dan keterangan.</li>
                    <li>Pilih akun Kas/Bank yang digunakan dan akun lawan (misal: Akun Beban untuk pengeluaran, atau Akun Pendapatan untuk pemasukan).</li>
                    <li>Klik <strong>"Simpan Transaksi"</strong>. Sistem akan otomatis membuat jurnal di belakang layar.</li>
                </ol>
                <a href="<?= base_url('/transaksi#add') ?>" class="inline-flex items-center px-3 py-1.5 border border-primary text-xs font-medium rounded text-primary bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary mt-2" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Buka Form Tambah Transaksi
                </a>
            </div>
        </div>
    </div>

    <!-- Panduan 3: Entri Jurnal Manual -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800" data-controller="accordion-item">
        <h2 class="mb-0" id="headingThree">
            <button class="w-full flex items-center justify-between px-4 py-3 text-left font-medium text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none transition-colors" type="button" onclick="toggleAccordion(this)">
                <strong>3. Entri Jurnal Manual (Untuk Transaksi Kompleks)</strong>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div id="collapseThree" class="hidden p-4 border-t border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300" aria-labelledby="headingThree">
            <div>
                <p>Gunakan fitur ini untuk transaksi yang melibatkan lebih dari dua akun (jurnal majemuk) atau transaksi non-kas (misalnya: penyusutan, penyesuaian).</p>
                <ol>
                    <li>Buka menu <strong>Akuntansi &raquo; Entri Jurnal</strong>.</li>
                    <li>Isi tanggal dan keterangan jurnal.</li>
                    <li>Klik <strong>"Tambah Baris"</strong> untuk menambahkan detail jurnal.</li>
                    <li>Pilih akun dan isi kolom Debit atau Kredit.</li>
                    <li>Pastikan <strong>Total Debit dan Total Kredit seimbang</strong> sebelum menyimpan.</li>
                </ol>
                <a href="<?= base_url('/entri-jurnal') ?>" class="inline-flex items-center px-3 py-1.5 border border-primary text-xs font-medium rounded text-primary bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary mt-2" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Buka Halaman Entri Jurnal
                </a>
            </div>
        </div>
    </div>

    <!-- Panduan 4: Rekonsiliasi Bank -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800" data-controller="accordion-item">
        <h2 class="mb-0" id="headingFour">
            <button class="w-full flex items-center justify-between px-4 py-3 text-left font-medium text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none transition-colors" type="button" onclick="toggleAccordion(this)">
                <strong>4. Mencocokkan Catatan: Rekonsiliasi Bank</strong>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div id="collapseFour" class="hidden p-4 border-t border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300" aria-labelledby="headingFour">
            <div>
                <p>Rekonsiliasi bank adalah proses membandingkan catatan transaksi kas/bank di aplikasi dengan laporan rekening koran dari bank untuk memastikan keduanya cocok.</p>
                <ol>
                    <li>Buka menu <strong>Kas & Bank &raquo; Rekonsiliasi Bank</strong>.</li>
                    <li>Pilih <strong>Akun Kas/Bank</strong> yang ingin direkonsiliasi.</li>
                    <li>Masukkan <strong>Tanggal Akhir</strong> dan <strong>Saldo Akhir</strong> sesuai yang tertera di rekening koran Anda.</li>
                    <li>Klik <strong>"Mulai Rekonsiliasi"</strong>. Sistem akan menampilkan semua transaksi yang belum dicocokkan.</li>
                    <li>Beri tanda centang pada setiap transaksi di aplikasi yang juga Anda temukan di rekening koran.</li>
                    <li>Perhatikan kartu ringkasan <strong>"Selisih"</strong>. Tujuan Anda adalah membuat selisih menjadi <strong>Rp 0</strong>.</li>
                    <li>Jika selisih sudah nol, tombol <strong>"Simpan Rekonsiliasi"</strong> akan aktif. Klik untuk menyelesaikan.</li>
                </ol>
                <div class="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 p-4 mt-3 text-sm text-blue-700 dark:text-blue-200">
                    <strong>Tips:</strong> Jika ada selisih, kemungkinan ada transaksi yang belum Anda catat (misalnya biaya admin bank) atau ada kesalahan pencatatan. Anda bisa menambahkannya melalui menu Transaksi atau Entri Jurnal.
                </div>
                <a href="<?= base_url('/rekonsiliasi-bank') ?>" class="inline-flex items-center px-3 py-1.5 border border-primary text-xs font-medium rounded text-primary bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary mt-2" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Buka Halaman Rekonsiliasi Bank
                </a>
            </div>
        </div>
    </div>

    <!-- Panduan 5: Aset Tetap -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800" data-controller="accordion-item">
        <h2 class="mb-0" id="headingFive">
            <button class="w-full flex items-center justify-between px-4 py-3 text-left font-medium text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none transition-colors" type="button" onclick="toggleAccordion(this)">
                <strong>5. Mengelola Aset Tetap & Penyusutan</strong>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div id="collapseFive" class="hidden p-4 border-t border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300" aria-labelledby="headingFive">
            <div>
                <p>Fitur ini membantu Anda mencatat aset tetap (seperti peralatan, kendaraan) dan menghitung penyusutannya secara otomatis setiap bulan.</p>
                <h5>Langkah 1: Menambah Aset Baru</h5>
                <ol>
                    <li>Buka menu <strong>Kas & Bank &raquo; Aset Tetap</strong>.</li>
                    <li>Klik <strong>"Tambah Aset"</strong>.</li>
                    <li>Isi detail aset seperti Nama, Tanggal Perolehan, Harga Perolehan, dan Masa Manfaat (dalam tahun).</li>
                    <li><strong>Penting:</strong> Petakan akun-akun yang sesuai. Anda mungkin perlu membuat akun baru di Bagan Akun terlebih dahulu.
                        <ul>
                            <li><strong>Akun Aset:</strong> Akun untuk mencatat nilai aset itu sendiri (misal: 1-2100 Peralatan Kantor).</li>
                            <li><strong>Akun Akumulasi Penyusutan:</strong> Akun kontra-aset untuk menampung total penyusutan (misal: 1-2101 Akum. Peny. - Peralatan). Tipe akunnya adalah 'Aset' dengan saldo normal 'Kredit'.</li>
                            <li><strong>Akun Beban Penyusutan:</strong> Akun untuk mencatat beban penyusutan setiap bulan (misal: 6-1400 Beban Penyusutan). Tipe akunnya adalah 'Beban'.</li>
                        </ul>
                    </li>
                    <li>Klik <strong>"Simpan"</strong>.</li>
                </ol>
                <hr>
                <h5>Langkah 2: Memposting Jurnal Penyusutan</h5>
                <ol>
                    <li>Di halaman Aset Tetap, pilih Bulan dan Tahun pada bagian <strong>"Posting Penyusutan Periodik"</strong>.</li>
                    <li>Klik tombol <strong>"Posting Jurnal Penyusutan"</strong>.</li>
                    <li>Sistem akan otomatis menghitung penyusutan bulanan untuk semua aset yang aktif dan membuat jurnalnya. Jurnal yang sudah pernah dibuat untuk periode yang sama tidak akan dibuat ulang.</li>
                </ol>
                <a href="<?= base_url('/aset-tetap') ?>" class="inline-flex items-center px-3 py-1.5 border border-primary text-xs font-medium rounded text-primary bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary mt-2" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Buka Halaman Aset Tetap
                </a>
            </div>
        </div>
    </div>

    <!-- Panduan 6: Otomatisasi -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800" data-controller="accordion-item">
        <h2 class="mb-0" id="headingSix">
            <button class="w-full flex items-center justify-between px-4 py-3 text-left font-medium text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none transition-colors" type="button" onclick="toggleAccordion(this)">
                <strong>6. Otomatisasi dengan Transaksi Berulang</strong>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div id="collapseSix" class="hidden p-4 border-t border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300" aria-labelledby="headingSix">
            <div>
                <p>Fitur ini memungkinkan Anda membuat template untuk transaksi atau jurnal yang terjadi secara rutin (misal: bayar sewa, gaji) agar dibuat otomatis oleh sistem.</p>
                <ol>
                    <li>Buat draf jurnal yang ingin diotomatisasi di halaman <strong>Akuntansi &raquo; Entri Jurnal</strong>.</li>
                    <li>Setelah draf siap, jangan klik "Simpan". Klik tombol <strong>"Jadikan Berulang"</strong>.</li>
                    <li>Atur nama template, frekuensi (misal: setiap 1 bulan), dan tanggal mulai.</li>
                    <li>Klik <strong>"Simpan Template"</strong>.</li>
                    <li>Anda dapat melihat dan mengelola semua template di halaman <strong>Pengaturan & Master &raquo; Transaksi Berulang</strong>.</li>
                </ol>
                <div class="flex gap-2 mt-3">
                    <a href="<?= base_url('/entri-jurnal') ?>" class="inline-flex items-center px-3 py-1.5 border border-primary text-xs font-medium rounded text-primary bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-2"></i>Mulai dari Entri Jurnal
                    </a>
                    <a href="<?= base_url('/transaksi-berulang') ?>" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-2"></i>Lihat Halaman Template
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan 7: Laporan & Analisis -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800" data-controller="accordion-item">
        <h2 class="mb-0" id="headingSeven">
            <button class="w-full flex items-center justify-between px-4 py-3 text-left font-medium text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none transition-colors" type="button" onclick="toggleAccordion(this)">
                <strong>7. Melihat Laporan & Analisis</strong>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div id="collapseSeven" class="hidden p-4 border-t border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300" aria-labelledby="headingSeven">
            <div>
                <p>Semua hasil pencatatan Anda dapat dilihat dalam berbagai laporan di bawah menu <strong>Laporan & Analisis</strong>.</p>
                <ul>
                    <li><strong>Laporan Keuangan:</strong> Menampilkan Neraca, Laba Rugi, dan Arus Kas.</li>
                    <li><strong>Perubahan Laba:</strong> Menunjukkan detail perubahan pada akun Laba Ditahan.</li>
                    <li><strong>Laporan Harian:</strong> Ringkasan kas masuk dan keluar untuk tanggal tertentu.</li>
                    <li><strong>Pertumbuhan Laba:</strong> Grafik dan tabel untuk menganalisis tren laba dari waktu ke waktu.</li>
                    <li><strong>Analisis Rasio:</strong> Menghitung rasio keuangan penting (Profit Margin, ROE, dll) untuk mengukur kesehatan finansial.</li>
                    <li><strong>Anggaran:</strong> Membandingkan anggaran belanja dengan realisasi.</li>
                </ul>
                <a href="<?= base_url('/laporan') ?>" class="inline-flex items-center px-3 py-1.5 border border-primary text-xs font-medium rounded text-primary bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary mt-2" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Buka Halaman Laporan Utama
                </a>
            </div>
        </div>
    </div>

    <!-- Panduan 8: Tutup Buku -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800" data-controller="accordion-item">
        <h2 class="mb-0" id="headingEight">
            <button class="w-full flex items-center justify-between px-4 py-3 text-left font-medium text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none transition-colors" type="button" onclick="toggleAccordion(this)">
                <strong>8. Proses Akhir Periode: Tutup Buku (Khusus Admin)</strong>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div id="collapseEight" class="hidden p-4 border-t border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300" aria-labelledby="headingEight">
            <div>
                <p>Proses Tutup Buku adalah langkah akuntansi yang dilakukan di akhir periode (biasanya akhir tahun) untuk menolkan saldo akun-akun sementara (Pendapatan dan Beban) dan memindahkan laba atau rugi bersih ke akun Laba Ditahan (Retained Earnings).</p>
                <div class="bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-500 p-4 mb-4 text-sm text-yellow-700 dark:text-yellow-200">
                    <strong>Penting:</strong> Fitur ini hanya dapat diakses oleh <strong>Admin</strong>. Pastikan semua transaksi pada periode tersebut sudah final sebelum melakukan tutup buku.
                </div>
                <ol>
                    <li>Pastikan akun Laba Ditahan sudah diatur di menu <strong>Administrasi &raquo; Pengaturan &raquo; Akuntansi</strong>.</li>
                    <li>Buka menu <strong>Administrasi &raquo; Tutup Buku</strong>.</li>
                    <li>Pilih tanggal akhir periode yang akan ditutup (misalnya, 31 Desember 2023).</li>
                    <li>Klik tombol <strong>"Proses Tutup Buku"</strong> dan konfirmasi.</li>
                    <li>Sistem akan secara otomatis membuat Jurnal Penutup. Anda dapat melihat hasilnya di halaman <strong>Daftar Jurnal</strong>.</li>
                    <li>Setelah proses ini, semua transaksi sebelum tanggal tutup buku akan dikunci dan tidak dapat diubah atau dihapus.</li>
                </ol>
                <a href="<?= base_url('/tutup-buku') ?>" class="inline-flex items-center px-3 py-1.5 border border-primary text-xs font-medium rounded text-primary bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary mt-2" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Lihat Halaman Tutup Buku
                </a>
            </div>
        </div>
    </div>

    <!-- Panduan 9: Pengaturan Aplikasi -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800" data-controller="accordion-item">
        <h2 class="mb-0" id="headingNine">
            <button class="w-full flex items-center justify-between px-4 py-3 text-left font-medium text-gray-800 dark:text-white bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none transition-colors" type="button" onclick="toggleAccordion(this)">
                <strong>9. Konfigurasi Sistem: Pengaturan Aplikasi (Khusus Admin)</strong>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
        </h2>
        <div id="collapseNine" class="hidden p-4 border-t border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300" aria-labelledby="headingNine">
            <div>
                <p>Halaman ini adalah pusat kendali aplikasi, tempat Anda dapat menyesuaikan berbagai aspek sistem agar sesuai dengan kebutuhan Anda. Fitur ini hanya dapat diakses oleh <strong>Admin</strong>.</p>
                <h5>Area Pengaturan:</h5>
                <ul>
                    <li><strong>Umum:</strong> Mengubah nama aplikasi, logo, dan detail header laporan PDF.</li>
                    <li><strong>Transaksi:</strong> Mengatur prefix untuk nomor referensi otomatis dan memilih akun kas default.</li>
                    <li><strong>Akuntansi:</strong> Menentukan akun Laba Ditahan yang krusial untuk proses Tutup Buku.</li>
                    <li><strong>Arus Kas:</strong> Memetakan akun-akun ke dalam kategori Laporan Arus Kas (Operasi, Investasi, Pendanaan).</li>
                    <li><strong>Konsinyasi:</strong> Memetakan akun-akun yang digunakan untuk transaksi barang titipan.</li>
                </ul>
                <h5>Langkah Penggunaan:</h5>
                <ol>
                    <li>Buka menu <strong>Administrasi &raquo; Pengaturan</strong>.</li>
                    <li>Pilih tab pengaturan yang ingin Anda ubah (misalnya, "Umum").</li>
                    <li>Lakukan perubahan yang diperlukan pada form.</li>
                    <li>Klik tombol <strong>"Simpan Pengaturan"</strong> di bagian bawah setiap tab untuk menerapkan perubahan.</li>
                </ol>
                <a href="<?= base_url('/settings') ?>" class="inline-flex items-center px-3 py-1.5 border border-primary text-xs font-medium rounded text-primary bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary mt-2" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Buka Halaman Pengaturan
                </a>
            </div>
        </div>
    </div>

</div>

<!-- Mermaid.js untuk merender diagram -->
<script type="module">
    import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
    mermaid.initialize({ securityLevel: 'loose' });
</script>

<script>
    function toggleAccordion(button) {
        const item = button.closest('[data-controller="accordion-item"]');
        const content = item.querySelector('div[id^="collapse"]');
        const icon = button.querySelector('.bi-chevron-down');

        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.classList.add('rotate-180');
        } else {
            content.classList.add('hidden');
            icon.classList.remove('rotate-180');
        }
    }
</script>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>