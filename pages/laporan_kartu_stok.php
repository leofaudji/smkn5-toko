<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-card-list"></i> Laporan Kartu Stok</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-outline-danger" id="export-kartu-stok-pdf" style="display: none;">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </button>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <form id="kartu-stok-form" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="ks-item-id" class="form-label">Pilih Barang</label>
                <select id="ks-item-id" class="form-select" required>
                    <option value="">Memuat barang...</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="ks-tanggal-mulai" class="form-label">Dari Tanggal</label>
                <input type="date" id="ks-tanggal-mulai" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="ks-tanggal-akhir" class="form-label">Sampai Tanggal</label>
                <input type="date" id="ks-tanggal-akhir" class="form-control">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100" id="ks-tampilkan-btn">
                    <i class="bi bi-search"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header" id="report-ks-header" style="display: none;">
        <h5 class="mb-0">Kartu Stok: <span id="ks-item-name"></span></h5>
        <p class="mb-0 text-muted small">Periode: <span id="ks-period"></span></p>
    </div>
    <div class="card-body">
        <div id="report-ks-content" class="table-responsive">
            <p class="text-center text-muted">Silakan pilih barang dan rentang tanggal, lalu klik "Tampilkan".</p>
        </div>
        <div id="report-ks-summary" class="mt-3 pt-3 border-top" style="display: none;">
            <div class="row">
                <div class="col-md-3"><strong>Saldo Awal:</strong> <span id="ks-summary-awal">0</span></div>
                <div class="col-md-3"><strong>Total Masuk:</strong> <span id="ks-summary-masuk" class="text-success">0</span></div>
                <div class="col-md-3"><strong>Total Keluar:</strong> <span id="ks-summary-keluar" class="text-danger">0</span></div>
                <div class="col-md-3"><strong>Saldo Akhir:</strong> <span id="ks-summary-akhir" class="fw-bold">0</span></div>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>