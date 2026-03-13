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
    $search = $_GET['search'] ?? '';

    $where_clauses = ['p.user_id = ?'];
    $params = ['i', $user_id];

    if (!empty($start_date)) {
        $where_clauses[] = 'DATE(p.tanggal_penjualan) >= ?';
        $params[0] .= 's';
        $params[] = $start_date;
    }
    if (!empty($end_date)) {
        $where_clauses[] = 'DATE(p.tanggal_penjualan) <= ?';
        $params[0] .= 's';
        $params[] = $end_date;
    }
    if (!empty($search)) {
        $where_clauses[] = '(p.customer_name LIKE ? OR u.username LIKE ?)';
        $params[0] .= 'ss';
        $searchTerm = '%' . $search . '%';
        array_push($params, $searchTerm, $searchTerm);
    }

    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

    // Get summary data (total sales and profit)
    $summary_where_sql = 'WHERE ' . implode(' AND ', $where_clauses) . " AND p.status = 'completed'";
    $summary_stmt = $conn->prepare("
        SELECT
            pd.item_type,
            SUM(pd.subtotal) as total_penjualan,
            SUM(CASE 
                WHEN pd.item_type = 'normal' THEN pd.quantity * i.harga_beli 
                WHEN pd.item_type = 'consignment' THEN pd.quantity * ci.harga_beli 
                ELSE 0 
            END) as total_hpp
        FROM penjualan_details pd
        JOIN penjualan p ON pd.penjualan_id = p.id
        LEFT JOIN items i ON pd.item_id = i.id AND pd.item_type = 'normal'
        LEFT JOIN consignment_items ci ON pd.item_id = ci.id AND pd.item_type = 'consignment'
        LEFT JOIN users u ON p.created_by = u.id
        $summary_where_sql
        GROUP BY pd.item_type
    ");
    $bind_params_summary = [&$params[0]];
    for ($i = 1; $i < count($params); $i++) { $bind_params_summary[] = &$params[$i]; }
    call_user_func_array([$summary_stmt, 'bind_param'], $bind_params_summary);
    $summary_stmt->execute();
    $summary_rows = $summary_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $summary_stmt->close();

    $summary_data = [
        'total_penjualan' => 0,
        'total_hpp' => 0,
        'total_profit' => 0,
        'shop' => ['sales' => 0, 'hpp' => 0, 'profit' => 0],
        'consignment' => ['sales' => 0, 'hpp' => 0, 'profit' => 0]
    ];

    foreach ($summary_rows as $row) {
        $sales = (float)$row['total_penjualan'];
        $hpp = (float)$row['total_hpp'];
        $profit = $sales - $hpp;

        $summary_data['total_penjualan'] += $sales;
        $summary_data['total_hpp'] += $hpp;
        $summary_data['total_profit'] += $profit;

        if ($row['item_type'] === 'normal') {
            $summary_data['shop'] = ['sales' => $sales, 'hpp' => $hpp, 'profit' => $profit];
        } elseif ($row['item_type'] === 'consignment') {
            $summary_data['consignment'] = ['sales' => $sales, 'hpp' => $hpp, 'profit' => $profit];
        }
    }

    // Get total count for pagination
    $total_stmt = $conn->prepare("SELECT COUNT(p.id) as total FROM penjualan p LEFT JOIN users u ON p.created_by = u.id $where_sql");
    $bind_params_total = [&$params[0]];
    for ($i = 1; $i < count($params); $i++) { $bind_params_total[] = &$params[$i]; }
    call_user_func_array([$total_stmt, 'bind_param'], $bind_params_total);
    $total_stmt->execute();
    $total_records = $total_stmt->get_result()->fetch_assoc()['total'];
    $total_stmt->close();

    // Get data for the current page
    $query = "
        SELECT 
            p.id, 
            p.nomor_referensi, 
            p.tanggal_penjualan, 
            p.customer_name, 
            p.total, 
            p.status, 
            u.username,
            COALESCE(cogs.total_hpp, 0) as total_hpp,
            (p.total - COALESCE(cogs.total_hpp, 0)) as profit
        FROM penjualan p
        LEFT JOIN users u ON p.created_by = u.id
        LEFT JOIN (
            SELECT 
                pd.penjualan_id, 
                SUM(CASE 
                    WHEN pd.item_type = 'normal' THEN pd.quantity * i.harga_beli 
                    WHEN pd.item_type = 'consignment' THEN pd.quantity * ci.harga_beli 
                    ELSE 0 
                END) as total_hpp
            FROM penjualan_details pd
            LEFT JOIN items i ON pd.item_id = i.id AND pd.item_type = 'normal'
            LEFT JOIN consignment_items ci ON pd.item_id = ci.id AND pd.item_type = 'consignment'
            GROUP BY pd.penjualan_id
        ) as cogs ON p.id = cogs.penjualan_id
        $where_sql 
        ORDER BY p.tanggal_penjualan DESC, p.id DESC 
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
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $pagination = [
        'current_page' => $page, 
        'total_pages' => ceil($total_records / $limit), 
        'total_records' => $total_records, 
        'limit' => $limit, // Tambahkan limit ke objek pagination
        'summary' => $summary_data
    ];
    echo json_encode(['status' => 'success', 'data' => $data, 'pagination' => $pagination]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>