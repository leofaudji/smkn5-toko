<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-cart-fill"></i> Pembelian</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pembelianModal" data-action="add" id="add-pembelian-btn">
            <i class="bi bi-plus-circle-fill"></i> Tambah Pembelian
        </button>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3">
                <input type="text" id="search-pembelian" class="form-control" placeholder="Cari pemasok/keterangan...">
            </div>
            <div class="col-md-3">
                <select id="filter-supplier" class="form-select">
                    <option value="">Semua Pemasok</option>
                    <!-- Opsi dimuat oleh JS -->
                </select>
            </div>
            <div class="col-md-2">
                <select id="filter-bulan" class="form-select">
                    <!-- Opsi dimuat oleh JS -->
                </select>
            </div>
            <div class="col-md-2">
                <select id="filter-tahun" class="form-select">
                    <!-- Opsi dimuat oleh JS -->
                </select>
            </div>
            <div class="col-md-2">
                <select id="filter-limit" class="form-select">
                    <option value="10">10</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="-1">Semua</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pemasok</th>
                        <th>Keterangan</th>
                        <th class="text-end">Total</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody id="pembelian-table-body">
                    <!-- Data akan dimuat oleh JavaScript -->
                </tbody>
            </table>
        </div>
        <nav>
            <ul class="pagination justify-content-center" id="pembelian-pagination">
                <!-- Pagination akan dimuat oleh JS -->
            </ul>
        </nav>
    </div>
</div>

<!-- Modal untuk Tambah/Edit Pembelian -->
<div class="modal fade" id="pembelianModal" tabindex="-1" aria-labelledby="pembelianModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="pembelianModalLabel">Tambah Pembelian Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="pembelian-form" novalidate>
            <input type="hidden" name="id" id="pembelian-id">
            <input type="hidden" name="action" id="pembelian-action" value="add">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="supplier_id" class="form-label">Pemasok</label>
                    <select class="form-select" id="supplier_id" name="supplier_id" required></select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="tanggal_pembelian" class="form-label">Tanggal Pembelian</label>
                    <input type="date" class="form-control" id="tanggal_pembelian" name="tanggal_pembelian" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan</label>
                <textarea class="form-control" id="keterangan" name="keterangan" rows="2" required></textarea>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40%;">Nama Barang</th>
                            <th style="width: 15%;" class="text-end">Qty</th>
                            <th style="width: 20%;" class="text-end">Harga Satuan</th>
                            <th style="width: 20%;" class="text-end">Subtotal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="pembelian-lines-body">
                        <!-- Baris detail pembelian akan ditambahkan di sini oleh JS -->
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="add-pembelian-line-btn"><i class="bi bi-plus-lg"></i> Tambah Baris</button>

            <hr>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="jatuh_tempo" class="form-label">Tanggal Jatuh Tempo (Opsional)</label>
                    <input type="date" class="form-control" id="jatuh_tempo" name="jatuh_tempo">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="payment_method" class="form-label">Metode Pembayaran</label>
                    <select class="form-select" id="payment_method" name="payment_method">
                        <option value="credit">Kredit (Bayar Nanti)</option>
                        <option value="cash">Tunai/Langsung</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3" id="kas-account-container" style="display: none;">
                    <label for="kas_account_id" class="form-label">Akun Kas/Bank Pembayaran</label>
                    <select class="form-select" id="kas_account_id" name="kas_account_id">
                    </select>
                </div>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" id="save-pembelian-btn">Simpan Pembelian</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>