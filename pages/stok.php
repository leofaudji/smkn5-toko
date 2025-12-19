<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-boxes"></i> Manajemen Barang & Stok</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-outline-success me-2" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-file-earmark-spreadsheet-fill"></i> Import dari Excel
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itemModal" data-action="add">
            <i class="bi bi-plus-circle-fill"></i> Tambah Barang Baru
        </button> 
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-4">
                <input type="text" id="search-item" class="form-control" placeholder="Cari nama barang atau SKU...">
            </div>
            <div class="col-md-3">
                <select id="filter-category" class="form-select">
                    <option value="">Semua Kategori</option>
                    <!-- Opsi kategori dimuat oleh JS -->
                </select>
            </div>
            <div class="col-md-3">
                <select id="filter-stok" class="form-select">
                    <option value="">Semua Stok</option>
                    <option value="ready">Stok Tersedia</option>
                    <option value="empty">Stok Habis</option>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filter-limit" class="form-select" title="Item per halaman">
                    <option value="15">15</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
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
                        <th>Nama Barang</th>
                        <th>SKU</th>
                        <th>Kategori</th>
                        <th class="text-end">Harga Beli</th>
                        <th class="text-end">Harga Jual</th>
                        <th class="text-end">Stok</th>
                        <th class="text-end">Nilai Persediaan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="items-table-body">
                    <!-- Data dimuat oleh JS -->
                </tbody>
            </table>
        </div>
        <nav>
            <ul class="pagination justify-content-center" id="items-pagination">
                <!-- Pagination dimuat oleh JS -->
            </ul>
        </nav>
    </div>
</div>

<!-- Modal untuk Tambah/Edit Barang -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="itemModalLabel">Tambah Barang Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> 
      </div>
      <div class="modal-body">
        <form id="item-form" novalidate>
            <input type="hidden" name="item-id" id="item-id"> <!-- This name is now correct -->
            <input type="hidden" name="action" id="item-action" value="save">
            
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label for="nama_barang" class="form-label">Nama Barang</label>
                    <input type="text" class="form-control" id="nama_barang" name="nama_barang" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="sku" class="form-label">SKU (Kode Barang)</label>
                    <input type="text" class="form-control" id="sku" name="sku">
                </div>
                <div class="col-md-12 mb-3">
                    <label for="category_id" class="form-label">Kategori</label>
                    <select class="form-select" id="category_id" name="category_id"></select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="harga_beli" class="form-label">Harga Beli (Modal)</label>
                    <input type="number" class="form-control" id="harga_beli" name="harga_beli" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="harga_jual" class="form-label">Harga Jual</label>
                    <input type="number" class="form-control" id="harga_jual" name="harga_jual" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="stok" class="form-label">Stok Saat Ini</label>
                <input type="number" class="form-control" id="stok" name="stok" required>
                <div id="stok-help-text" class="form-text">Masukkan jumlah stok awal. Untuk mengubah stok selanjutnya, gunakan fitur "Penyesuaian Stok" atau transaksi Pembelian.</div>
            </div>

            <hr>
            <p class="text-muted">Pemetaan Akun (Opsional)</p>
            <div class="mb-3"><label for="inventory_account_id" class="form-label">Akun Persediaan (Aset)</label><select class="form-select" id="inventory_account_id" name="inventory_account_id"></select></div>
            <div class="mb-3"><label for="cogs_account_id" class="form-label">Akun Harga Pokok Penjualan (Beban)</label><select class="form-select" id="cogs_account_id" name="cogs_account_id"></select></div>
            <div class="mb-3"><label for="sales_account_id" class="form-label">Akun Pendapatan Penjualan</label><select class="form-select" id="sales_account_id" name="sales_account_id"></select></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" id="save-item-btn">Simpan Barang</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal untuk Penyesuaian Stok -->
<div class="modal fade" id="adjustmentModal" tabindex="-1" aria-labelledby="adjustmentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="adjustmentModalLabel">Penyesuaian Stok</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="adjustment-form" novalidate>
            <input type="hidden" name="item_id" id="adj-item-id">
            <input type="hidden" name="action" value="adjust_stock">
            
            <div class="mb-3">
                <label class="form-label">Nama Barang</label>
                <input type="text" class="form-control" id="adj-nama-barang" readonly>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Stok Tercatat</label>
                    <input type="text" class="form-control" id="adj-stok-tercatat" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="adj-stok-fisik" class="form-label">Stok Fisik Sebenarnya</label>
                    <input type="number" class="form-control" id="adj-stok-fisik" name="stok_fisik" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="adj-tanggal" class="form-label">Tanggal Penyesuaian</label>
                <input type="date" class="form-control" id="adj-tanggal" name="tanggal" required>
            </div>

            <div class="mb-3"><label for="adj_account_id" class="form-label">Akun Penyeimbang</label><select class="form-select" id="adj_account_id" name="adj_account_id" required></select><div class="form-text">Pilih akun untuk mencatat selisih nilai persediaan (cth: Beban Persediaan Rusak, atau Modal).</div></div>
            <div class="mb-3"><label for="adj-keterangan" class="form-label">Alasan Penyesuaian</label><textarea class="form-control" id="adj-keterangan" name="keterangan" rows="2" required placeholder="cth: Hasil stok opname 31 Des 2023"></textarea></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-adjustment-btn">Simpan Penyesuaian</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal untuk Import Excel -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importModalLabel">Import Barang dari CSV</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="import-form" enctype="multipart/form-data">
            <div class="alert alert-info small">
                <p class="mb-1">Simpan file Excel Anda sebagai file <strong>CSV (Comma-separated values)</strong>. Pastikan urutan kolomnya sebagai berikut (baris pertama/header akan dilewati):</p>
                <ol class="mb-1">
                    <li><strong>Kolom A: Nama Barang</strong> (Wajib)</li>
                    <li><strong>Kolom B: ID Barang</strong> (Opsional. Isi untuk memperbarui barang yang ada, kosongkan untuk membuat barang baru)</li>
                    <li><strong>Kolom C: Kategori</strong> (Opsional. Jika kategori belum ada, akan dibuat otomatis)</li>
                    <li><strong>Kolom D: SKU</strong> (Opsional)</li>
                    <li><strong>Kolom E: Harga Beli</strong> (Wajib, angka saja, cth: <code>15000.50</code>)</li>
                    <li><strong>Kolom F: Harga Jual</strong> (Wajib, angka saja, cth: <code>20000.00</code>)</li>
                    <li>... (kolom lain diabaikan) ...</li>
                    <li><strong>Kolom J: Stok Fisik</strong> (Wajib, angka bulat, cth: <code>100</code>)</li>
                </ol>
            </div>
            <div class="mb-3">
                <label for="excel-file" class="form-label">Pilih File CSV (.csv)</label>
                <input class="form-control" type="file" id="excel-file" name="excel_file" accept=".csv" required>
            </div>
            <div class="mb-3">
                <label for="import_adj_account_id" class="form-label">Akun Penyeimbang Saldo Awal</label>
                <select class="form-select" id="import_adj_account_id" name="adj_account_id" required></select>
                <div class="form-text">Pilih akun untuk menyeimbangkan nilai persediaan awal (cth: Modal Awal).</div>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" id="upload-excel-btn">
            <i class="bi bi-upload"></i> Unggah dan Proses
        </button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>