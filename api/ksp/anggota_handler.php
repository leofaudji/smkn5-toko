<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/bootstrap.php';

// Cek login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_all':
        get_all_anggota($db);
        break;
    case 'get_detail':
        get_detail_anggota($db);
        break;
    case 'store':
        store_anggota($db);
        break;
    case 'update':
        update_anggota($db);
        break;
    case 'delete':
        delete_anggota($db);
        break;
    case 'sync_from_sp':
        sync_from_sp($db);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
        break;
}

function get_all_anggota($db) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $user_id = 1; // Default user ID (Toko)

    $sql = "SELECT id, nomor_anggota, nama_lengkap, no_telepon, status, tanggal_daftar FROM anggota WHERE user_id = ?";
    $params = [$user_id];
    $types = "i";

    if (!empty($search)) {
        $sql .= " AND (nama_lengkap LIKE ? OR nomor_anggota LIKE ? OR no_telepon LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    }

    $countSql = str_replace("SELECT id, nomor_anggota, nama_lengkap, no_telepon, status, tanggal_daftar", "SELECT COUNT(*) as total", $sql);
    
    // Hitung total data
    $stmt = $db->prepare($countSql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = stmt_fetch_assoc($stmt)['total'];
    $stmt->close();

    // Ambil data
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $data = stmt_fetch_all($stmt);
    $stmt->close();

    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ]);
}

function get_detail_anggota($db) {
    $id = $_GET['id'] ?? 0;
    $user_id = 1;

    $stmt = $db->prepare("SELECT * FROM anggota WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $result = stmt_fetch_assoc($stmt);
    
    if ($result) {
        echo json_encode(['success' => true, 'data' => $result]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Anggota tidak ditemukan']);
    }
}

function store_anggota($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = 1;
    $created_by = $_SESSION['user_id'];

    if (empty($data['nama_lengkap']) || empty($data['tanggal_daftar'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nama dan Tanggal Daftar wajib diisi']);
        return;
    }

    // Generate Nomor Anggota Otomatis: AGT-YYYYMM-XXXX
    $prefix = "AGT-" . date('Ym', strtotime($data['tanggal_daftar'])) . "-";
    $stmt = $db->prepare("SELECT nomor_anggota FROM anggota WHERE nomor_anggota LIKE ? ORDER BY nomor_anggota DESC LIMIT 1");
    $searchPrefix = $prefix . "%";
    $stmt->bind_param("s", $searchPrefix);
    $stmt->execute();
    $last = stmt_fetch_assoc($stmt);
    
    $seq = 1;
    if ($last) {
        $lastSeq = (int)substr($last['nomor_anggota'], -4);
        $seq = $lastSeq + 1;
    }
    $nomor_anggota = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

    $stmt = $db->prepare("INSERT INTO anggota (user_id, nomor_anggota, nama_lengkap, alamat, no_telepon, email, tanggal_daftar, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssi", $user_id, $nomor_anggota, $data['nama_lengkap'], $data['alamat'], $data['no_telepon'], $data['email'], $data['tanggal_daftar'], $data['status'], $created_by);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Anggota berhasil ditambahkan']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan: ' . $stmt->error]);
    }
}

function update_anggota($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = 1;
    $updated_by = $_SESSION['user_id'];

    if (empty($data['id']) || empty($data['nama_lengkap'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID dan Nama wajib diisi']);
        return;
    }

    $stmt = $db->prepare("UPDATE anggota SET nama_lengkap = ?, alamat = ?, no_telepon = ?, email = ?, tanggal_daftar = ?, status = ?, updated_by = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssssssiii", $data['nama_lengkap'], $data['alamat'], $data['no_telepon'], $data['email'], $data['tanggal_daftar'], $data['status'], $updated_by, $data['id'], $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Data anggota berhasil diperbarui']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui: ' . $stmt->error]);
    }
}

function delete_anggota($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $user_id = 1;

    // Cek apakah anggota sudah memiliki transaksi (opsional, untuk keamanan data)
    // Disini kita asumsikan belum ada tabel transaksi simpan pinjam yang terhubung
    // Jika ada, lakukan pengecekan terlebih dahulu

    $stmt = $db->prepare("DELETE FROM anggota WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Anggota berhasil dihapus']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . $stmt->error]);
    }
}

/**
 * Sinkronasi data anggota dari aplikasi Simpan Pinjam (SP)
 * @param mysqli $db Koneksi database
 */
function sync_from_sp($db) {
    $apiUrl = Config::get('SP_API_URL');
    if (empty($apiUrl)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Konfigurasi SP_API_URL tidak ditemukan di .env']);
        return;
    }

    // Ambil data dari SP App (Limit besar untuk sync awal)
    $fullUrl = $apiUrl . "&limit=1000";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-SPA-REQUEST: true']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        http_response_code(500);
        $msg = "Gagal terhubung ke SP App (HTTP $httpCode)";
        if ($curlError) $msg .= " - Error: $curlError";
        echo json_encode(['success' => false, 'message' => $msg]);
        return;
    }

    $resData = json_decode($response, true);
    if (!$resData || !isset($resData['success']) || !$resData['success']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Format data dari SP App tidak valid atau gagal.']);
        return;
    }

    $externalAnggota = $resData['data'] ?? [];
    $user_id = 1; // Default
    $current_user_id = $_SESSION['user_id'];
    $successCount = 0;
    $errorCount = 0;

    foreach ($externalAnggota as $item) {
        if (empty($item['nomor_anggota'])) continue;

        // Upsert based on nomor_anggota
        $stmt = $db->prepare("SELECT id FROM anggota WHERE nomor_anggota = ? AND user_id = ?");
        $stmt->bind_param("si", $item['nomor_anggota'], $user_id);
        $stmt->execute();
        $exists = stmt_fetch_assoc($stmt);
        $stmt->close();

        if ($exists) {
            // Update
            $sql = "UPDATE anggota SET nama_lengkap = ?, no_telepon = ?, status = ?, updated_by = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("sssii", $item['nama_lengkap'], $item['no_telepon'], $item['status'], $current_user_id, $exists['id']);
        } else {
            // Insert
            $sql = "INSERT INTO anggota (user_id, nomor_anggota, nama_lengkap, no_telepon, tanggal_daftar, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("isssssi", $user_id, $item['nomor_anggota'], $item['nama_lengkap'], $item['no_telepon'], $item['tanggal_daftar'], $item['status'], $current_user_id);
        }

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errorCount++;
        }
        $stmt->close();
    }

    echo json_encode([
        'success' => true, 
        'message' => "Sinkronasi selesai. $successCount data diproses ($successCount berhasil, $errorCount gagal)."
    ]);
}