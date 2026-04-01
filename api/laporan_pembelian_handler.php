<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$user_id = 1; // ID Pemilik Data (Toko)

try {
    $limit = (int)($_GET['limit'] ?? 15);
    $page = (int)($_GET['page'] ?? 1);
    $offset = ($page - 1) * $limit;

    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $supplier_id = $_GET['supplier_id'] ?? '';
    $search = $_GET['search'] ?? '';

    $where_clauses = ['p.user_id = ?'];
    $params = ['i', $user_id];

    if (!empty($start_date)) {
        $where_clauses[] = 'DATE(p.tanggal_pembelian) >= ?';
        $params[0] .= 's';
        $params[] = $start_date;
    }
    if (!empty($end_date)) {
        $where_clauses[] = 'DATE(p.tanggal_pembelian) <= ?';
        $params[0] .= 's';
        $params[] = $end_date;
    }
    if (!empty($supplier_id)) {
        $where_clauses[] = 'p.supplier_id = ?';
        $params[0] .= 'i';
        $params[] = $supplier_id;
    }
    if (!empty($search)) {
        $where_clauses[] = '(s.nama_pemasok LIKE ? OR p.keterangan LIKE ? OR p.nomor_referensi LIKE ?)';
        $params[0] .= 'sss';
        $searchTerm = '%' . $search . '%';
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
    }

    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

    // 1. Get summary data
    $summary_where_sql = 'WHERE ' . implode(' AND ', $where_clauses) . " AND p.status != 'void'";
    $summary_stmt = $conn->prepare("
        SELECT 
            SUM(p.total) as total_pembelian,
            SUM(CASE WHEN p.payment_method = 'cash' THEN p.total ELSE 0 END) as total_tunai,
            SUM(CASE WHEN p.payment_method = 'credit' THEN p.total ELSE 0 END) as total_kredit
        FROM pembelian p
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        $summary_where_sql
    ");
    $bind_params_summary = [&$params[0]];
    for ($i = 1; $i < count($params); $i++) { $bind_params_summary[] = &$params[$i]; }
    call_user_func_array([$summary_stmt, 'bind_param'], $bind_params_summary);
    $summary_stmt->execute();
    $summary_data = stmt_fetch_assoc($summary_stmt);
    $summary_stmt->close();

    // 2. Get total count for pagination
    $total_stmt = $conn->prepare("SELECT COUNT(p.id) as total FROM pembelian p LEFT JOIN suppliers s ON p.supplier_id = s.id $where_sql");
    $bind_params_total = [&$params[0]];
    for ($i = 1; $i < count($params); $i++) { $bind_params_total[] = &$params[$i]; }
    call_user_func_array([$total_stmt, 'bind_param'], $bind_params_total);
    $total_stmt->execute();
    $total_records = stmt_fetch_assoc($total_stmt)['total'];
    $total_stmt->close();

    // 3. Get data for the current page
    $query = "
        SELECT 
            p.*, 
            s.nama_pemasok 
        FROM pembelian p 
        LEFT JOIN suppliers s ON p.supplier_id = s.id 
        $where_sql 
        ORDER BY p.tanggal_pembelian DESC, p.id DESC 
        LIMIT ? OFFSET ?
    ";
    $params[0] .= 'ii';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $conn->prepare($query);
    $bind_params_main = [&$params[0]];
    for ($i = 1; $i < count($params); $i++) { $bind_params_main[] = &$params[$i]; }
    call_user_func_array([$stmt, 'bind_param'], $bind_params_main);
    $stmt->execute();
    $data = stmt_fetch_all($stmt);
    $stmt->close();

    $from = $total_records > 0 ? $offset + 1 : 0;
    $to = min($offset + $limit, $total_records);

    $pagination = [
        'page' => $page, 
        'total_pages' => ceil($total_records / $limit), 
        'total_records' => $total_records, 
        'limit' => $limit,
        'from' => $from,
        'to' => $to,
        'summary' => [
            'total' => (float)($summary_data['total_pembelian'] ?? 0),
            'cash' => (float)($summary_data['total_tunai'] ?? 0),
            'credit' => (float)($summary_data['total_kredit'] ?? 0)
        ]
    ];
    echo json_encode(['status' => 'success', 'data' => $data, 'pagination' => $pagination]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
