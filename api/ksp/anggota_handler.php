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
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Ambil data
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
    $result = $stmt->get_result()->fetch_assoc();
    
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
    $last = $stmt->get_result()->fetch_assoc();
    
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