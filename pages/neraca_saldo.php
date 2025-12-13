<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-funnel"></i> Laporan Neraca Saldo</h1>
</div>

<div class="card">
    <div class="card-body">
        <form id="report-form">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="tanggal" class="form-label">Per Tanggal</label>
                    <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary" id="preview-btn">
                        <i class="bi bi-search"></i> Tampilkan Preview
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Container untuk preview dan tombol cetak -->
<div id="preview-container" class="mt-4" style="display: none;">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Preview Neraca Saldo</h5>
            <button class="btn btn-success" id="print-pdf-btn">
                <i class="bi bi-printer-fill"></i> Cetak PDF
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive" id="preview-table-container">
                <!-- Tabel preview akan dirender di sini oleh JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Script khusus untuk halaman ini -->
<script src="<?= base_url('/assets/js/neraca_saldo.js') ?>"></script>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>