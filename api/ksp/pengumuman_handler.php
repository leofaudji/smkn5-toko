<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $result = $db->query("SELECT * FROM ksp_pengumuman ORDER BY tanggal_posting DESC, created_at DESC");
        echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
        break;

    case 'get_single':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM ksp_pengumuman WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'store':
        $judul = $_POST['judul'] ?? '';
        $isi = $_POST['isi'] ?? '';
        $tanggal = $_POST['tanggal_posting'] ?? date('Y-m-d');
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        $stmt = $db->prepare("INSERT INTO ksp_pengumuman (judul, isi, tanggal_posting, is_active) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $judul, $isi, $tanggal, $is_active);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Pengumuman berhasil ditambahkan']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan pengumuman']);
        }
        break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $judul = $_POST['judul'] ?? '';
        $isi = $_POST['isi'] ?? '';
        $tanggal = $_POST['tanggal_posting'] ?? date('Y-m-d');
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        $stmt = $db->prepare("UPDATE ksp_pengumuman SET judul = ?, isi = ?, tanggal_posting = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("sssii", $judul, $isi, $tanggal, $is_active, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Pengumuman berhasil diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui pengumuman']);
        }
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM ksp_pengumuman WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Pengumuman berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus pengumuman']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}