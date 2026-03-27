<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/bootstrap.php';

// Cek login atau API Key (Dukungan parameter 'secret' atau 'api_key')
$api_key = $_GET['api_key'] ?? $_GET['secret'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
$is_authenticated = (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true);
$sync_secret = Config::get('API_IMPORT_SECRET') ?: Config::get('SP_API_KEY');
$is_valid_api_key = (!empty($api_key) && $api_key === $sync_secret);

if (!$is_authenticated && !$is_valid_api_key) {
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

function get_all_anggota($db)
{
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $user_id = 1; // Default user ID (Toko)

    $sql = "SELECT id, nomor_anggota, nama_lengkap, nik, no_telepon, status, tanggal_daftar FROM anggota WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $sql .= " AND (nama_lengkap LIKE ? OR nomor_anggota LIKE ? OR no_telepon LIKE ? OR nik LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ssss";
    }

    $countSql = str_replace("SELECT id, nomor_anggota, nama_lengkap, nik, no_telepon, status, tanggal_daftar", "SELECT COUNT(*) as total", $sql);

    // Hitung total data
    $stmt = $db->prepare($countSql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
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

function get_detail_anggota($db)
{
    $id = $_GET['id'] ?? 0;
    $user_id = 1;

    $stmt = $db->prepare("SELECT * FROM anggota WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = stmt_fetch_assoc($stmt);

    if ($result) {
        echo json_encode(['success' => true, 'data' => $result]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Anggota tidak ditemukan']);
    }
}

function store_anggota($db)
{
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
        $lastSeq = (int) substr($last['nomor_anggota'], -4);
        $seq = $lastSeq + 1;
    }
    $nomor_anggota = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

    $stmt = $db->prepare("INSERT INTO anggota (user_id, nomor_anggota, nama_lengkap, nik, alamat, no_telepon, email, tanggal_daftar, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssi", $user_id, $nomor_anggota, $data['nama_lengkap'], $data['nik'], $data['alamat'], $data['no_telepon'], $data['email'], $data['tanggal_daftar'], $data['status'], $created_by);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Anggota berhasil ditambahkan']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan: ' . $stmt->error]);
    }
}

function update_anggota($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = 1;
    $updated_by = $_SESSION['user_id'];

    if (empty($data['id']) || empty($data['nama_lengkap'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID dan Nama wajib diisi']);
        return;
    }

    $stmt = $db->prepare("UPDATE anggota SET nama_lengkap = ?, nik = ?, alamat = ?, no_telepon = ?, email = ?, tanggal_daftar = ?, status = ?, updated_by = ? WHERE id = ?");
    $stmt->bind_param("sssssssii", $data['nama_lengkap'], $data['nik'], $data['alamat'], $data['no_telepon'], $data['email'], $data['tanggal_daftar'], $data['status'], $updated_by, $data['id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Data anggota berhasil diperbarui']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui: ' . $stmt->error]);
    }
}

function delete_anggota($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $user_id = 1;

    $stmt = $db->prepare("DELETE FROM anggota WHERE id = ?");
    $stmt->bind_param("i", $id);

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
function sync_from_sp($db)
{
    $apiUrl = Config::get('SP_API_URL');
    if (empty($apiUrl)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Konfigurasi SP_API_URL tidak ditemukan di .env']);
        return;
    }

    // Ambil data dari SP App (Limit besar untuk sync awal)
    $fullUrl = $apiUrl . "&limit=1000";
    $apiKey = Config::get('API_IMPORT_SECRET') ?: Config::get('SP_API_KEY');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-SPA-REQUEST: true',
        'X-API-KEY: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        http_response_code(500);
        $msg = "Gagal terhubung ke SP App (HTTP $httpCode)";
        if ($curlError) {
            $msg .= " - Error: $curlError";
        }
        echo json_encode(['success' => false, 'message' => $msg]);
        return;
    }

    $resData = json_decode($response, true);

    // Identifikasi format data: raw (langsung array) atau wrapped (ada key success/data)
    $externalAnggota = [];
    if (is_array($resData)) {
        if (isset($resData['success'])) {
            // Format wrapped
            if ($resData['success']) {
                $externalAnggota = $resData['data'] ?? [];
            } else {
                http_response_code(500);
                $remoteMsg = $resData['message'] ?? 'Unknown error';
                echo json_encode(['success' => false, 'message' => 'SP App Error: ' . $remoteMsg]);
                return;
            }
        } else {
            // Format raw (asumsi langsung array anggota)
            $externalAnggota = $resData;
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Format data dari SP App tidak valid atau gagal decode JSON.']);
        return;
    }

    $user_id = 1; // Default
    $current_user_id = $_SESSION['user_id'];
    $successCount = 0;
    $skipCount = 0;
    $errorCount = 0;

    foreach ($externalAnggota as $item) {
        $no_anggota = $item['no_anggota'] ?? $item['nomor_anggota'] ?? '';
        if (empty($no_anggota)) {
            continue;
        }

        // Cek apakah data sudah ada berdasarkan no_anggota
        $stmt = $db->prepare("SELECT id FROM anggota WHERE nomor_anggota = ?");
        $stmt->bind_param("s", $no_anggota);
        $stmt->execute();
        $exists = stmt_fetch_assoc($stmt);
        $stmt->close();

        if ($exists) {
            $skipCount++;
            continue; // Lewati jika data sudah ada
        }

        // Mapping field dari data external ke field database Toko
        $nama_lengkap = $item['nama'] ?? $item['nama_lengkap'] ?? null;
        $no_telepon = $item['telepon'] ?? $item['no_telepon'] ?? null;
        $nik = $item['nik'] ?? null;
        $alamat = $item['alamat'] ?? null;
        $email = $item['email'] ?? null;
        $status = $item['status'] ?? 'aktif';
        $tanggal_daftar = $item['tanggal_daftar'] ?? date('Y-m-d');

        // Insert data baru
        $sql = "INSERT INTO anggota (user_id, nomor_anggota, nama_lengkap, no_telepon, nik, alamat, email, status, tanggal_daftar, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("issssssssi", $user_id, $no_anggota, $nama_lengkap, $no_telepon, $nik, $alamat, $email, $status, $tanggal_daftar, $current_user_id);

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errorCount++;
        }
        $stmt->close();
    }

    echo json_encode([
        'success' => true,
        'message' => "Sinkronasi selesai. $successCount data baru ditambahkan, $skipCount data sudah ada (lewati), $errorCount gagal."
    ]);
}
