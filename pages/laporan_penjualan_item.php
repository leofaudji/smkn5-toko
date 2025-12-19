<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-graph-up"></i> Laporan Penjualan per Item</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-outline-danger" id="export-penjualan-item-pdf">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </button>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <form id="report-penjualan-item-form" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="penjualan-item-tanggal-mulai" class="form-label">Dari Tanggal</label>
                <input type="date" id="penjualan-item-tanggal-mulai" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="penjualan-item-tanggal-akhir" class="form-label">Sampai Tanggal</label>
                <input type="date" id="penjualan-item-tanggal-akhir" class="form-control">
            </div>
            <div class="col-md-4">
                <label for="penjualan-item-sort" class="form-label">Urutkan Berdasarkan</label>
                <select id="penjualan-item-sort" class="form-select">
                    <option value="total_terjual" selected>Jumlah Terjual (Terlaris)</option>
                    <option value="total_penjualan">Total Penjualan (Rupiah)</option>
                    <option value="total_profit">Total Profit</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100" id="penjualan-item-tampilkan-btn">
                    <i class="bi bi-search"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header" id="report-penjualan-item-header">
        <h5 class="mb-0">Produk Terlaris</h5>
    </div>
    <div class="card-body">
        <div id="report-penjualan-item-content" class="table-responsive">
            <p class="text-center text-muted">Silakan pilih rentang tanggal dan klik "Tampilkan".</p>
        </div>
        <nav class="mt-3">
            <ul class="pagination justify-content-center" id="penjualan-item-report-pagination">
                <!-- Pagination dimuat oleh JS -->
            </ul>
        </nav>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>