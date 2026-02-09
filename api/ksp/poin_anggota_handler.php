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

try {
    switch ($action) {
        case 'list_members':
            $search = $_GET['search'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $limit = 10;
            $offset = ($page - 1) * $limit;

            $where = "WHERE status = 'aktif'";
            $params = [];
            $types = "";

            if (!empty($search)) {
                $where .= " AND (nama_lengkap LIKE ? OR nomor_anggota LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "ss";
            }

            // Count total
            $stmtCount = $db->prepare("SELECT COUNT(*) as total FROM anggota $where");
            if (!empty($params)) $stmtCount->bind_param($types, ...$params);
            $stmtCount->execute();
            $total = $stmtCount->get_result()->fetch_assoc()['total'];

            // Get data
            $sql = "SELECT id, nomor_anggota, nama_lengkap, gamification_points 
                    FROM anggota $where 
                    ORDER BY gamification_points DESC, nama_lengkap ASC 
                    LIMIT ? OFFSET ?";
            $stmt = $db->prepare($sql);
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Calculate rank based on offset
            foreach ($data as $index => &$row) {
                $row['rank'] = $offset + $index + 1;
            }

            echo json_encode([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            break;

        case 'get_history':
            $memberId = (int)($_GET['member_id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM ksp_gamification_log WHERE anggota_id = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->bind_param("i", $memberId);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'adjust_points':
            $data = json_decode(file_get_contents('php://input'), true);
            $memberId = (int)($data['member_id'] ?? 0);
            $actionType = $data['action_type'] ?? 'manual_reward';
            $points = (int)($data['points'] ?? 0);
            $description = $data['description'] ?? 'Penyesuaian Manual';

            if ($memberId <= 0 || $points <= 0) {
                throw new Exception("Data tidak valid.");
            }

            // If penalty, make points negative for the log logic (though addGamificationPoints handles addition, we might need to adjust logic or pass negative)
            // The helper function addGamificationPoints adds points. If we want to deduct, we pass negative.
            if ($actionType === 'manual_penalty') {
                $points = -$points;
            }

            addGamificationPoints($memberId, $actionType, $points, $description, null);
            
            echo json_encode(['success' => true, 'message' => 'Poin berhasil disesuaikan.']);
            break;
            
        case 'get_all_members_simple':
             $result = $db->query("SELECT id, nama_lengkap, nomor_anggota FROM anggota WHERE status='aktif' ORDER BY nama_lengkap ASC");
             echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
             break;

        default:
            throw new Exception("Aksi tidak valid.");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}