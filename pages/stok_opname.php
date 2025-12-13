<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-clipboard-check-fill me-2"></i>Stok Opname</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Formulir Stok Opname</h6>
        </div>
        <div class="card-body">
            <form id="stockOpnameForm">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tanggal">Tanggal</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="adj_account_id">Akun Penyeimbang (Selisih)</label>
                            <select class="form-control" id="adj_account_id" name="adj_account_id" required>
                                <option value="">Memuat akun...</option>
                            </select>
                            <small class="form-text text-muted">Akun untuk mencatat selisih (misal: Beban Kerusakan Persediaan, Modal Awal).</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="keterangan">Keterangan</label>
                            <input type="text" class="form-control" id="keterangan" name="keterangan" placeholder="cth: Stok Opname Bulanan" required>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="form-group mb-0">
                            <label for="searchInput">Cari Barang</label>
                            <input type="text" id="searchInput" class="form-control" placeholder="Ketik nama barang atau SKU...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label for="stockFilter">Filter Stok</label>
                            <select id="stockFilter" class="form-control">
                                <option value="">Semua Stok</option>
                                <option value="ready">Stok Tersedia (> 0)</option>
                                <option value="empty">Stok Habis (<= 0)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 65vh;">
                    <table class="table table-bordered table-sticky-header" id="itemsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Barang</th>
                                <th>SKU</th>
                                <th class="text-end">Stok Sistem</th>
                                <th class="text-center" style="width: 150px;">Stok Fisik</th>
                                <th class="text-end">Selisih</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            <tr>
                                <td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-primary" id="saveButton">
                        <i class="bi bi-save me-2"></i>Simpan Hasil Stok Opname
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>