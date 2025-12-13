<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Laporan Pertumbuhan Nilai Persediaan</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3 align-items-end">
                <div class="col-md-3">
                    <label for="lpp-tahun-filter" class="form-label">Pilih Tahun:</label>
                    <select id="lpp-tahun-filter" class="form-select"></select>
                </div>
                <div class="col-md-3">
                    <button id="lpp-tampilkan-btn" class="btn btn-primary"><i class="bi bi-search me-2"></i>Tampilkan</button>
                </div>
            </div>

            <!-- Grafik -->
            <div class="mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Grafik Nilai Persediaan Bulanan</h5>
                        <canvas id="lpp-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tabel Detail -->
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50%;">Bulan</th>
                            <th class="text-end" style="width: 25%;">Nilai Persediaan (Hasil Opname)</th>
                            <th class="text-end">Perubahan (Selisih)</th>
                        </tr>
                    </thead>
                    <tbody id="lpp-report-table-body">
                        <!-- Data akan dimuat oleh JavaScript -->
                    </tbody>
                </table>
            </div>
            <div id="lpp-loading" class="text-center mt-3" style="display: none;">
                <div class="spinner-border spinner-border-sm"></div> Memuat data...
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    // Kita akan membuat file JS terpisah untuk halaman ini
    // Pastikan file ini dimuat di footer
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>
