<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check for authenticated admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $role_id_filter = isset($_GET['role_id']) ? (int)$_GET['role_id'] : null;
        $user_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if ($user_id) {
            // Get a single user for the edit modal
            $stmt = $conn->prepare("SELECT id, username, nama_lengkap, role_id FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $user]);
        } else {
            // Get a list of all users, with optional role filtering (Refactored for robustness)
            $sql = "SELECT u.id, u.username, u.nama_lengkap, u.created_at, u.role_id, r.name as role_name 
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.id";
            
            $params = [];
            $types = '';

            if ($role_id_filter) {
                $sql .= " WHERE u.role_id = ?";
                $params[] = $role_id_filter;
                $types .= 'i';
            }
            
            $sql .= " ORDER BY u.nama_lengkap ASC";
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Database prepare failed: " . $conn->error);
            }
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            echo json_encode(['success' => true, 'data' => $users]);
        }
    } elseif ($method === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'add' || $action === 'edit') {
            $id = $_POST['id'] ?? null;
            $username = trim($_POST['username']);
            $nama_lengkap = trim($_POST['nama_lengkap']);
            $password = $_POST['password'];
            $role_id = (int)$_POST['role_id'];

            if ($action === 'add') {
                if (empty($password)) throw new Exception("Password wajib diisi untuk pengguna baru.");
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, nama_lengkap, password, role_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $username, $nama_lengkap, $hashed_password, $role_id);
            } else { // edit
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, nama_lengkap = ?, password = ?, role_id = ? WHERE id = ?");
                    $stmt->bind_param("sssii", $username, $nama_lengkap, $hashed_password, $role_id, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET username = ?, nama_lengkap = ?, role_id = ? WHERE id = ?");
                    $stmt->bind_param("ssii", $username, $nama_lengkap, $role_id, $id);
                }
            }
            $stmt->execute();
            echo json_encode(['success' => true]);

        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            if ($id === 1) throw new Exception("User Admin utama tidak dapat dihapus.");
            
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Aksi tidak valid.");
        }
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>