<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Pastikan hanya admin yang bisa menjalankan (opsional, bisa dihapus jika dijalankan via CLI atau lokal)
if (php_sapi_name() !== 'cli' && (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin')) {
    die('Akses ditolak. Silakan login sebagai admin.');
}

$conn = Database::getInstance()->getConnection();
$user_id = 1; // ID Toko/Unit
$created_by = 1; // ID Admin

echo "Mulai seeding data KSP...\n";

// --- 1. Seed Anggota (20 Data) ---
echo "Seeding Anggota...\n";
$anggota_ids = [];
$names = [
    'Budi Santoso', 'Siti Aminah', 'Agus Setiawan', 'Dewi Ratnasari', 'Eko Prasetyo',
    'Rina Wati', 'Joko Susilo', 'Sri Wahyuni', 'Hendra Gunawan', 'Yuli Astuti',
    'Dedi Kurniawan', 'Nia Daniati', 'Rudi Hartono', 'Lilis Suryani', 'Iwan Fals',
    'Maya Sari', 'Tono Sudirjo', 'Wulan Guritno', 'Bambang Pamungkas', 'Susi Susanti'
];

$stmt_anggota = $conn->prepare("INSERT INTO anggota (user_id, nomor_anggota, nama_lengkap, alamat, no_telepon, email, tanggal_daftar, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', ?)");

foreach ($names as $i => $name) {
    $seq = $i + 1;
    $nomor = 'AGT-' . date('Ym') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    $alamat = "Jl. Merdeka No. " . $seq;
    $telp = "0812" . str_pad($seq, 8, '0', STR_PAD_RIGHT);
    $email = strtolower(str_replace(' ', '.', $name)) . "@example.com";
    $tgl = date('Y-m-d', strtotime("-".rand(1, 12)." months"));
    
    // Cek duplikat email/nomor (simple check)
    $check = $conn->query("SELECT id FROM anggota WHERE nomor_anggota = '$nomor'");
    if ($check->num_rows > 0) {
        $anggota_ids[] = $check->fetch_assoc()['id'];
        continue;
    }

    $stmt_anggota->bind_param("issssssi", $user_id, $nomor, $name, $alamat, $telp, $email, $tgl, $created_by);
    $stmt_anggota->execute();
    $anggota_ids[] = $conn->insert_id;
}
$stmt_anggota->close();

// --- 2. Seed Simpanan (20 Transaksi) ---
echo "Seeding Simpanan...\n";
// Ambil ID jenis simpanan & kategori
$jenis_simpanan = $conn->query("SELECT id FROM ksp_jenis_simpanan")->fetch_all(MYSQLI_ASSOC);
$kategori_setor = $conn->query("SELECT id FROM ksp_kategori_transaksi WHERE tipe_aksi = 'setor' LIMIT 1")->fetch_assoc()['id'] ?? 1;
$akun_kas = $conn->query("SELECT id FROM accounts WHERE is_kas = 1 LIMIT 1")->fetch_assoc()['id'] ?? 103;

if (empty($jenis_simpanan)) die("Error: Jenis Simpanan belum ada. Jalankan setup awal dulu.\n");

$stmt_simpanan = $conn->prepare("INSERT INTO ksp_transaksi_simpanan (user_id, anggota_id, jenis_simpanan_id, tanggal, jenis_transaksi, debit, kredit, jumlah, keterangan, akun_kas_id, nomor_referensi, created_by) VALUES (?, ?, ?, ?, 'setor', 0, ?, ?, 'Setoran Awal Dummy', ?, ?, ?)");

for ($i = 0; $i < 20; $i++) {
    $agt_id = $anggota_ids[$i % count($anggota_ids)];
    $js_id = $jenis_simpanan[array_rand($jenis_simpanan)]['id'];
    $jumlah = rand(50000, 1000000);
    $tgl = date('Y-m-d', strtotime("-".rand(1, 30)." days"));
    $ref = "SIM-DUMMY-" . ($i+1);

    $stmt_simpanan->bind_param("iiisddisi", $user_id, $agt_id, $js_id, $tgl, $jumlah, $jumlah, $akun_kas, $ref, $created_by);
    $stmt_simpanan->execute();
}
$stmt_simpanan->close();

// --- 3. Seed Pinjaman (20 Data) ---
echo "Seeding Pinjaman...\n";
// Ambil ID jenis pinjaman
$jenis_pinjaman = $conn->query("SELECT id, bunga_per_tahun FROM ksp_jenis_pinjaman LIMIT 1")->fetch_assoc();
if (!$jenis_pinjaman) die("Error: Jenis Pinjaman belum ada.\n");

$jp_id = $jenis_pinjaman['id'];
$bunga_persen = $jenis_pinjaman['bunga_per_tahun'];

$stmt_pinjaman = $conn->prepare("INSERT INTO ksp_pinjaman (user_id, nomor_pinjaman, anggota_id, jenis_pinjaman_id, jumlah_pinjaman, bunga_per_tahun, tenor_bulan, tanggal_pengajuan, tanggal_pencairan, status, keterangan, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif', 'Pinjaman Dummy', ?)");
$stmt_angsuran = $conn->prepare("INSERT INTO ksp_angsuran (pinjaman_id, angsuran_ke, tanggal_jatuh_tempo, pokok, bunga, total_angsuran, status) VALUES (?, ?, ?, ?, ?, ?, ?)");

for ($i = 0; $i < 20; $i++) {
    $agt_id = $anggota_ids[$i % count($anggota_ids)];
    $nomor_pinj = "PINJ-DUMMY-" . str_pad($i+1, 4, '0', STR_PAD_LEFT);
    $jumlah = rand(1, 10) * 1000000; // 1jt - 10jt
    $tenor = [6, 12, 24][array_rand([6, 12, 24])];
    $tgl_aju = date('Y-m-d', strtotime("-".rand(1, 6)." months"));
    $tgl_cair = date('Y-m-d', strtotime($tgl_aju . " +2 days"));

    $stmt_pinjaman->bind_param("isiididssi", $user_id, $nomor_pinj, $agt_id, $jp_id, $jumlah, $bunga_persen, $tenor, $tgl_aju, $tgl_cair, $created_by);
    $stmt_pinjaman->execute();
    $pinjaman_id = $conn->insert_id;

    // Generate Angsuran
    $pokok_bulanan = $jumlah / $tenor;
    $bunga_bulanan = ($jumlah * ($bunga_persen / 100)) / 12;
    $total_bulanan = $pokok_bulanan + $bunga_bulanan;
    
    $tgl_tempo = new DateTime($tgl_cair);
    $tgl_tempo->modify('+1 month');

    for ($j = 1; $j <= $tenor; $j++) {
        $status_angsuran = 'belum_bayar';
        // Randomly set some installments as paid if due date is passed
        if ($tgl_tempo < new DateTime() && rand(0, 1)) {
            // Logic bayar angsuran dummy (update tabel angsuran saja, skip jurnal untuk simplifikasi seed)
            // Di real app, gunakan handler pay_installment
            $status_angsuran = 'lunas';
        }

        $jatuh_tempo_str = $tgl_tempo->format('Y-m-d');
        $stmt_angsuran->bind_param("iisddds", $pinjaman_id, $j, $jatuh_tempo_str, $pokok_bulanan, $bunga_bulanan, $total_bulanan, $status_angsuran);
        $stmt_angsuran->execute();
        
        // Jika lunas, update kolom terbayar
        if ($status_angsuran === 'lunas') {
            $angsuran_id = $conn->insert_id;
            $tgl_bayar = $jatuh_tempo_str; // Asumsi bayar tepat waktu
            $conn->query("UPDATE ksp_angsuran SET pokok_terbayar = $pokok_bulanan, bunga_terbayar = $bunga_bulanan, tanggal_bayar = '$tgl_bayar' WHERE id = $angsuran_id");
        }

        $tgl_tempo->modify('+1 month');
    }
}
$stmt_pinjaman->close();
$stmt_angsuran->close();

echo "Selesai! Data dummy berhasil dibuat.\n";
echo "Silakan hapus file ini setelah digunakan.\n";
?>