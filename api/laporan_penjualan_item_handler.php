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
    $sort_by = $_GET['sort_by'] ?? 'total_terjual';

    // Validasi kolom sort
    $allowed_sort_columns = ['total_terjual', 'total_penjualan', 'total_profit'];
    if (!in_array($sort_by, $allowed_sort_columns)) {
        $sort_by = 'total_terjual';
    }

    $where_clauses = ['p.user_id = ?', "p.status = 'completed'"];
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

    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

    $query = "
        SELECT
            COALESCE(i.id, ci.id) as id,
            COALESCE(i.sku, ci.sku) as sku,
            COALESCE(i.nama_barang, ci.nama_barang) as nama_barang,
            pd.item_type,
            SUM(pd.quantity) as total_terjual,
            SUM(pd.subtotal) as total_penjualan,
            SUM(pd.subtotal - (pd.quantity * COALESCE(i.harga_beli, ci.harga_beli, 0))) as total_profit
        FROM penjualan_details pd
        JOIN penjualan p ON pd.penjualan_id = p.id
        LEFT JOIN items i ON pd.item_id = i.id AND pd.item_type = 'normal'
        LEFT JOIN consignment_items ci ON pd.item_id = ci.id AND pd.item_type = 'consignment'
        $where_sql
        GROUP BY COALESCE(i.id, ci.id), COALESCE(i.sku, ci.sku), COALESCE(i.nama_barang, ci.nama_barang), pd.item_type
    ";

    // Get total count for pagination
    $count_query = "SELECT COUNT(*) as total FROM ($query) as subquery";
    $total_stmt = $conn->prepare($count_query);
    $total_stmt->bind_param(...$params);
    $total_stmt->execute();
    $total_records = stmt_fetch_assoc($total_stmt)['total'];
    $total_stmt->close();

    // Get data for the current page
    $query .= " ORDER BY $sort_by DESC LIMIT ? OFFSET ?";
    $params[0] .= 'ii';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $conn->prepare($query);
    $stmt->bind_param(...$params);
    $stmt->execute();
    $data = stmt_fetch_all($stmt);
    $stmt->close();

    $pagination = [
        'page' => $page, 
        'limit' => $limit,
        'total_pages' => ceil($total_records / $limit), 
        'total_records' => $total_records
    ];
    echo json_encode(['status' => 'success', 'data' => $data, 'pagination' => $pagination]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>