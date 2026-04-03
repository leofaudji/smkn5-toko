<?php
/**
 * Script Sinkronisasi Stok (One-time)
 * Digunakan untuk menyamakan saldo di Kartu Stok dengan saldo di tabel Items
 */
require_once __DIR__ . '/../includes/bootstrap.php';

// Default values for CLI or missing session
$conn = Database::getInstance()->getConnection();
$user_id = 1;
$logged_in_user_id = (isset($_SESSION) && isset($_SESSION['user_id'])) ? $_SESSION['user_id'] : 1;
$tanggal_sync = date('Y-m-d');

try {
    $conn->begin_transaction();

    // 1. Ambil data stok real-time dari tabel items
    $items_query = "SELECT id, nama_barang, stok FROM items WHERE user_id = ?";
    $stmt_items = $conn->prepare($items_query);
    $stmt_items->bind_param('i', $user_id);
    $stmt_items->execute();
    $items = stmt_fetch_all($stmt_items);
    $stmt_items->close();

    $synced_count = 0;
    $results = [];

    foreach ($items as $item) {
        $item_id = $item['id'];
        $stok_real = (int) $item['stok'];

        // 2. Hitung saldo saat ini di kartu_stok
        $stmt_ledger = $conn->prepare("SELECT COALESCE(SUM(debit - kredit), 0) as saldo_ledger FROM kartu_stok WHERE item_id = ?");
        $stmt_ledger->bind_param('i', $item_id);
        $stmt_ledger->execute();
        $saldo_ledger = (int) stmt_fetch_assoc($stmt_ledger)['saldo_ledger'];
        $stmt_ledger->close();

        // 3. Jika ada selisih, buat entri sinkronisasi
        $selisih = $stok_real - $saldo_ledger;

        if ($selisih != 0) {
            $debit = $selisih > 0 ? $selisih : 0;
            $kredit = $selisih < 0 ? abs($selisih) : 0;
            $keterangan = "Sinkronisasi Saldo Stok Otomatis";

            $stmt_ks = $conn->prepare("INSERT INTO kartu_stok (tanggal, item_id, debit, kredit, keterangan, user_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_ks->bind_param('siiisi', $tanggal_sync, $item_id, $debit, $kredit, $keterangan, $logged_in_user_id);
            $stmt_ks->execute();
            $stmt_ks->close();

            $synced_count++;
            $results[] = [
                'nama' => $item['nama_barang'],
                'stok_real' => $stok_real,
                'saldo_ledger' => $saldo_ledger,
                'penyesuaian' => $selisih
            ];
        }
    }

    $conn->commit();

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => "Berhasil menyinkronkan {$synced_count} barang.",
        'details' => $results
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
