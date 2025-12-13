<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-clipboard-data"></i> Laporan Stok</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-outline-success me-2" id="export-stok-csv">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
        </button>
        <button type="button" class="btn btn-outline-danger" id="export-stok-pdf">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </button>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <form id="report-stok-form" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="stok-tanggal-mulai" class="form-label">Dari Tanggal</label>
                <input type="date" id="stok-tanggal-mulai" class="form-control">
            </div>
            <div class="col-md-4">
                <label for="stok-tanggal-akhir" class="form-label">Sampai Tanggal</label>
                <input type="date" id="stok-tanggal-akhir" class="form-control">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100" id="stok-tampilkan-btn">
                    <i class="bi bi-search"></i> Tampilkan Laporan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 id="report-stok-header" class="mb-0">Hasil Laporan</h5>
    </div>
    <div class="card-body">
        <div id="report-stok-content" class="table-responsive" style="max-height: 65vh;">
            <p class="text-center text-muted">Silakan pilih rentang tanggal dan klik "Tampilkan Laporan".</p>
        </div>
        <div id="report-stok-summary" class="mt-3 pt-3 border-top">
            <!-- Summary dimuat oleh JS -->
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>