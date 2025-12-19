<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-receipt"></i> Laporan Penjualan</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-outline-danger" id="export-penjualan-pdf">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </button>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <form id="report-penjualan-form" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="penjualan-tanggal-mulai" class="form-label">Dari Tanggal</label>
                <input type="date" id="penjualan-tanggal-mulai" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="penjualan-tanggal-akhir" class="form-label">Sampai Tanggal</label>
                <input type="date" id="penjualan-tanggal-akhir" class="form-control">
            </div>
            <div class="col-md-4">
                <label for="penjualan-search" class="form-label">Cari Customer / Kasir</label>
                <input type="text" id="penjualan-search" class="form-control" placeholder="Ketik nama...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100" id="penjualan-tampilkan-btn">
                    <i class="bi bi-search"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header" id="report-penjualan-header">
        <h5 class="mb-0">Hasil Laporan</h5>
    </div>
    <div class="card-body">
        <div id="report-penjualan-summary" class="mb-3">
            <!-- Summary dimuat oleh JS -->
        </div>
        <div id="report-penjualan-content" class="table-responsive">
            <p class="text-center text-muted">Silakan pilih rentang tanggal dan klik "Tampilkan".</p>
        </div>
        <nav class="mt-3">
            <ul class="pagination justify-content-center" id="penjualan-report-pagination">
                <!-- Pagination dimuat oleh JS -->
            </ul>
        </nav>
    </div>
</div>

<?php
if (!$is_spa_request) {
    // Kita akan membuat file JS terpisah untuk halaman ini
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>