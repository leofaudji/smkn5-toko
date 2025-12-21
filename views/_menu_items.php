<?php
// This file is included by header.php and contains the sidebar menu structure.

function render_menu_item($url, $icon, $text) {
    echo '<a href="' . base_url($url) . '" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 group">
            <i class="' . $icon . ' mr-4 text-lg text-gray-500 dark:text-gray-400 group-hover:text-primary transition-colors"></i>
            <span>' . $text . '</span>
          </a>';
}

function render_collapsible_menu($id, $icon, $text, $items) {
    $items_html = '';
    $total = count($items);
    foreach ($items as $index => $item) {
        $is_last = ($index === $total - 1);
        // Garis vertikal: jika item terakhir, tingginya setengah (h-1/2) untuk membentuk sudut L
        $vertical_line_height = $is_last ? 'h-1/2' : 'h-full';
        
        $items_html .= '
        <div class="relative">
            <!-- Garis Vertikal -->
            <div class="absolute left-6 top-0 ' . $vertical_line_height . ' w-px bg-gray-300 dark:bg-gray-600"></div>
            <!-- Garis Horizontal -->
            <div class="absolute left-6 top-1/2 w-5 h-px bg-gray-300 dark:bg-gray-600"></div>
            
            <a href="' . base_url($item['url']) . '" class="flex items-center ml-11 px-3 py-2 text-sm font-normal rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-primary dark:hover:text-primary-400 transition-colors">
                ' . $item['text'] . '
            </a>
        </div>';
    }

    echo '<div data-controller="collapse">
            <button onclick="toggleCollapse(this)" class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 group">
                <span class="flex items-center">
                    <i class="' . $icon . ' mr-4 text-lg text-gray-500 dark:text-gray-400 group-hover:text-primary transition-colors"></i>
                    <span>' . $text . '</span>
                </span>
                <i class="bi bi-chevron-down transform transition-transform duration-200"></i>
            </button>
            <div class="collapse-content hidden">
                ' . $items_html . '
            </div>
          </div>';
}

?>

<!-- Menu Items -->
<?php render_menu_item('/dashboard', 'bi bi-speedometer2', 'Dashboard'); ?>
<?php render_menu_item('/buku-panduan', 'bi bi-question-circle-fill', 'Buku Panduan'); ?>

<!-- Grup Menu -->
<div class="pt-4 pb-2 px-4">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Aktivitas Utama</p>
</div>
<?php render_collapsible_menu('transaksi-menu', 'bi bi-pencil-square', 'Transaksi', [
    ['url' => '/penjualan', 'text' => 'Penjualan'],
    ['url' => '/pembelian', 'text' => 'Pembelian'],
    ['url' => '/transaksi', 'text' => 'Transaksi Kas'],
    ['url' => '/entri-jurnal', 'text' => 'Entri Jurnal'],
]); ?>

<?php render_collapsible_menu('akuntansi-menu', 'bi bi-calculator', 'Akuntansi', [
    ['url' => '/coa', 'text' => 'Bagan Akun (COA)'],
    ['url' => '/saldo-awal-neraca', 'text' => 'Saldo Awal Neraca'],
    ['url' => '/saldo-awal-lr', 'text' => 'Saldo Awal L/R'],
    ['url' => '/anggaran', 'text' => 'Anggaran'],
    ['url' => '/daftar-jurnal', 'text' => 'Daftar Jurnal'],
    ['url' => '/buku-besar', 'text' => 'Buku Besar'],
]); ?>

<?php render_collapsible_menu('stok-menu', 'bi bi-box-seam', 'Stok & Inventaris', [
    ['url' => '/stok', 'text' => 'Barang & Stok'],
    ['url' => '/stok-opname', 'text' => 'Stok Opname'],
    ['url' => '/laporan-stok', 'text' => 'Laporan Stok'],
    ['url' => '/laporan-kartu-stok', 'text' => 'Kartu Stok'],
    ['url' => '/laporan-persediaan', 'text' => 'Nilai Persediaan'],
    ['url' => '/laporan-pertumbuhan-persediaan', 'text' => 'Pertumbuhan Persediaan'],
    ['url' => '/aset-tetap', 'text' => 'Aset Tetap'],
]); ?>

<?php render_collapsible_menu('laporan-menu', 'bi bi-bar-chart-line-fill', 'Laporan', [
    ['url' => '/laporan-harian', 'text' => 'Laporan Harian'],
    ['url' => '/laporan-penjualan-item', 'text' => 'Penjualan per Item'],
    ['url' => '/laporan-penjualan', 'text' => 'Laporan Penjualan'],
    ['url' => '/laporan', 'text' => 'Laporan Keuangan'],
    ['url' => '/neraca-saldo', 'text' => 'Neraca Saldo'],
    ['url' => '/laporan-laba-ditahan', 'text' => 'Perubahan Laba'],
    ['url' => '/laporan-pertumbuhan-laba', 'text' => 'Pertumbuhan Laba'],
    ['url' => '/analisis-rasio', 'text' => 'Analisis Rasio'],
]); ?>

<?php render_collapsible_menu('tools-menu', 'bi bi-tools', 'Alat & Proses', [
    ['url' => '/transaksi-berulang', 'text' => 'Transaksi Berulang'],
    ['url' => '/rekonsiliasi-bank', 'text' => 'Rekonsiliasi Bank'],
]); ?>

<!-- Menu Khusus Admin -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="pt-4 pb-2 px-4">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Administrasi</p>
    </div>
    <?php render_menu_item('/users', 'bi bi-people-fill', 'Users'); ?>
    <?php render_menu_item('/activity-log', 'bi bi-list-check', 'Log Aktivitas'); ?>
    <?php render_menu_item('/tutup-buku', 'bi bi-archive-fill', 'Tutup Buku'); ?>
    <?php render_menu_item('/settings', 'bi bi-gear-fill', 'Pengaturan'); ?>
<?php endif; ?>