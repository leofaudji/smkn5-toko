<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Transaksi Penjualan</h1>
        <button class="btn btn-primary shadow-sm" id="btn-tambah-penjualan">
            <i class="bi bi-plus-lg"></i> Buat Transaksi Baru
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Transaksi Penjualan</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" id="search-input" class="form-control" placeholder="Cari No. Faktur atau Nama Customer...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered" id="penjualanTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No. Faktur</th>
                            <th>Tanggal</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Kasir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data akan dimuat oleh JavaScript -->
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div id="pagination-info"></div>
                <ul class="pagination" id="pagination"></ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal Transaksi Penjualan -->
<div class="modal fade" id="penjualanModal" tabindex="-1" aria-labelledby="penjualanModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="penjualanModalLabel">Transaksi Penjualan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-penjualan">
                    <!-- Form Header -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                        </div>
                        <div class="col-md-4">
                            <label for="customer_name" class="form-label">Nama Customer</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Umum">
                        </div>
                        <div class="col-md-4">
                            <label for="kasir" class="form-label">Kasir</label>
                            <input type="text" class="form-control" id="kasir" name="kasir" value="<?= htmlspecialchars($_SESSION['username']) ?>" readonly>
                        </div>
                    </div>

                    <!-- Pencarian Barang -->
                    <div class="mb-3">
                        <label for="search-produk" class="form-label">Cari Barang (Kode atau Nama)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="search-produk" placeholder="Ketik untuk mencari barang...">
                        </div>
                         <div id="product-suggestions"></div>
                    </div>

                    <!-- Tabel Item -->
                    <table class="table table-sm table-bordered" id="cart-table">
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th style="width: 130px;">Harga</th>
                                <th style="width: 80px;">Qty</th>
                                <th style="width: 120px;">Diskon</th>
                                <th style="width: 150px;">Subtotal</th>
                                <th style="width: 50px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="cart-items">
                            <!-- Item yang dipilih akan ditambahkan di sini -->
                        </tbody>
                    </table>

                    <!-- Ringkasan Total -->
                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <div class="mb-2 row">
                                <label for="subtotal" class="col-sm-4 col-form-label">Subtotal</label>
                                <div class="col-sm-8"><input type="text" readonly class="form-control-plaintext" id="subtotal" value="Rp 0"></div>
                            </div>
                            <div class="mb-2 row">
                                <label for="discount_total" class="col-sm-4 col-form-label">Diskon</label>
                                <div class="col-sm-8"><input type="number" class="form-control" id="discount_total" value="0" min="0"></div>
                            </div>
                            <div class="mb-2 row">
                                <label for="total" class="col-sm-4 col-form-label fw-bold">Total Akhir</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly class="form-control-plaintext form-control-lg fw-bold text-danger" id="total" value="0">
                                </div>
                            </div>
                            <div class="mb-2 row">
                                <label for="bayar" class="col-sm-4 col-form-label">Bayar</label>
                                <div class="col-sm-8"><input type="number" class="form-control" id="bayar" min="0"></div>
                            </div>
                            <div class="mb-2 row">
                                <label for="kembali" class="col-sm-4 col-form-label">Kembali</label>
                                <div class="col-sm-8"><input type="text" readonly class="form-control-plaintext" id="kembali" value="0"></div>
                            </div>
                        </div>
                    </div>
                    <!-- Catatan -->
                    <div class="row mt-3">
                        <label for="catatan" class="form-label">Catatan (Opsional)</label>
                        <textarea class="form-control" id="catatan" name="catatan" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-simpan-penjualan"><i class="bi bi-save"></i> Simpan Transaksi</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Penjualan -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailModalLabel">Detail Transaksi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="detailModalBody">
        <!-- Konten detail akan dimuat di sini -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="btn-cetak-struk">
            <i class="bi bi-printer"></i> Cetak Struk
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>