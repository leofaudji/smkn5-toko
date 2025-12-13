<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="bi bi-archive-fill me-2"></i>Laporan Nilai Persediaan</h5>
            <div>
                <button id="printButton" class="btn btn-secondary btn-sm"><i class="bi bi-printer me-2"></i>Cetak</button>
                <button id="exportButton" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel me-2"></i>Export ke Excel</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <input type="text" id="searchInput" class="form-control" placeholder="Cari berdasarkan Nama atau SKU...">
                </div>
                <div class="col-md-6 text-md-end">
                    <h5 class="mb-0">Total Nilai Persediaan: 
                        <span id="totalInventoryValueHeader" class="fw-bold text-success">Rp 0</span>
                    </h5>
                </div>
            </div>
            <div class="table-responsive" style="max-height: 65vh;">
                <table class="table table-striped table-bordered table-sticky-header">
                   <thead class="table-light">
                        <tr>
                            <th class="text-center">No</th>
                            <th>Nama Barang</th>
                            <th>SKU</th>
                            <th class="text-end">Stok Sistem</th>
                            <th class="text-end">Harga Beli (Rp)</th>
                            <th class="text-end">Nilai Persediaan (Rp)</th>
                        </tr>
                    </thead>
                    <tbody id="reportTableBody">
                        <!-- Data akan dimuat oleh JavaScript -->
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="5" class="text-end">Total Nilai Persediaan</td>
                            <td id="totalInventoryValue" class="text-end">Rp 0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div id="loadingIndicator" class="text-center mt-3" style="display: none;">
                <div class="spinner-border spinner-border-sm"></div> Memuat data...
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>