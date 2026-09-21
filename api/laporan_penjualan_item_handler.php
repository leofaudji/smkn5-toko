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
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $item_type = $_GET['item_type'] ?? 'all'; // 'all', 'normal', 'consignment'
    $movement_filter = $_GET['movement_filter'] ?? 'with_activity'; // 'with_activity', 'with_sales', 'all'
    $search = trim($_GET['search'] ?? '');
    $sort_by = $_GET['sort_by'] ?? 'total_margin';
    $sort_order = strtolower($_GET['sort_order'] ?? 'desc');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = (int)($_GET['limit'] ?? 15);

    // Validate sort column
    $allowed_sorts = [
        'total_margin', 'total_terjual', 'total_penjualan', 'total_hpp', 
        'margin_pct', 'stok_awal', 'masuk', 'keluar', 'stok_akhir', 
        'nama_barang', 'sku'
    ];
    if (!in_array($sort_by, $allowed_sorts)) {
        $sort_by = 'total_margin';
    }
    if ($sort_order !== 'asc' && $sort_order !== 'desc') {
        $sort_order = 'desc';
    }

    // ── Redis Cache Check ──────────────────────────────────────────
    $cache_param_str = "{$start_date}_{$end_date}_{$item_type}_{$movement_filter}_{$search}_{$sort_by}_{$sort_order}_{$page}_{$limit}";
    $cache_key = "report:sales_item_margin:{$user_id}:" . md5($cache_param_str);
    check_redis_cache($cache_key);

    // ── 1. Executive Summary (Identik 100% dengan Laporan Penjualan) ──
    $summary_stmt = $conn->prepare("
        SELECT
            pd.item_type,
            SUM(pd.quantity) as total_qty,
            SUM(pd.subtotal) as total_bruto,
            SUM(pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) as total_neto,
            SUM(pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, ci.harga_beli, 0))) as total_hpp
        FROM penjualan_details pd
        JOIN penjualan p ON pd.penjualan_id = p.id
        LEFT JOIN items i ON pd.item_id = i.id AND pd.item_type = 'normal'
        LEFT JOIN consignment_items ci ON pd.item_id = ci.id AND pd.item_type = 'consignment'
        WHERE p.user_id = ? AND DATE(p.tanggal_penjualan) >= ? AND DATE(p.tanggal_penjualan) <= ? AND p.status = 'completed'
        GROUP BY pd.item_type
    ");
    $summary_stmt->bind_param('iss', $user_id, $start_date, $end_date);
    $summary_stmt->execute();
    $summary_rows = stmt_fetch_all($summary_stmt);
    $summary_stmt->close();

    $summary = [
        'total_penjualan_bruto' => 0.0,
        'total_penjualan' => 0.0,
        'total_hpp' => 0.0,
        'total_profit' => 0.0,
        'total_margin_pct' => 0.0,
        'total_terjual' => 0,
        'total_masuk' => 0,
        'total_keluar' => 0,
        'shop' => ['bruto' => 0.0, 'sales' => 0.0, 'hpp' => 0.0, 'profit' => 0.0, 'margin_pct' => 0.0, 'qty' => 0],
        'consignment' => ['bruto' => 0.0, 'sales' => 0.0, 'hpp' => 0.0, 'profit' => 0.0, 'margin_pct' => 0.0, 'qty' => 0]
    ];

    foreach ($summary_rows as $row) {
        $bruto = (float)$row['total_bruto'];
        $sales = (float)$row['total_neto'];
        $hpp = (float)$row['total_hpp'];
        $qty = (int)$row['total_qty'];
        $profit = $sales - $hpp;

        $summary['total_penjualan_bruto'] += $bruto;
        $summary['total_penjualan'] += $sales;
        $summary['total_hpp'] += $hpp;
        $summary['total_profit'] += $profit;
        $summary['total_terjual'] += $qty;

        $pct = ($sales > 0) ? ($profit / $sales * 100) : 0.0;
        if ($row['item_type'] === 'normal') {
            $summary['shop'] = ['bruto' => $bruto, 'sales' => $sales, 'hpp' => $hpp, 'profit' => $profit, 'margin_pct' => $pct, 'qty' => $qty];
        } elseif ($row['item_type'] === 'consignment') {
            $summary['consignment'] = ['bruto' => $bruto, 'sales' => $sales, 'hpp' => $hpp, 'profit' => $profit, 'margin_pct' => $pct, 'qty' => $qty];
        }
    }
    $summary['total_margin_pct'] = ($summary['total_penjualan'] > 0) ? ($summary['total_profit'] / $summary['total_penjualan'] * 100) : 0.0;

    // ── 2. Sales per Item (Completed Only) ───────────────────────────
    $sales_sql = "
        SELECT
            pd.item_id,
            pd.item_type,
            MAX(pd.deskripsi_item) as deskripsi_item,
            SUM(pd.quantity) as qty_terjual,
            SUM(pd.subtotal) as total_bruto,
            SUM(pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) as total_neto,
            SUM(pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, ci.harga_beli, 0))) as total_hpp,
            SUM((pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) - (pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, ci.harga_beli, 0)))) as total_margin
        FROM penjualan_details pd
        JOIN penjualan p ON pd.penjualan_id = p.id
        LEFT JOIN items i ON pd.item_id = i.id AND pd.item_type = 'normal'
        LEFT JOIN consignment_items ci ON pd.item_id = ci.id AND pd.item_type = 'consignment'
        WHERE p.user_id = ? AND DATE(p.tanggal_penjualan) >= ? AND DATE(p.tanggal_penjualan) <= ? AND p.status = 'completed'
        GROUP BY pd.item_id, pd.item_type
    ";
    $stmt_sales = $conn->prepare($sales_sql);
    $stmt_sales->bind_param('iss', $user_id, $start_date, $end_date);
    $stmt_sales->execute();
    $sales_rows = stmt_fetch_all($stmt_sales);
    $stmt_sales->close();

    $sales_by_key = [];
    foreach ($sales_rows as $sr) {
        $sales_by_key[$sr['item_type'] . '_' . $sr['item_id']] = $sr;
    }

    $items_map = [];

    // ── 3. Normal Store Items Stock Movements ────────────────────────
    if ($item_type === 'all' || $item_type === 'normal') {
        $ks_sql = "
            SELECT 
                i.id, 
                i.nama_barang, 
                i.sku, 
                i.harga_beli,
                i.harga_jual,
                COALESCE(c.nama_kategori, 'Umum') as kategori,
                COALESCE(sa.stok_awal, 0) as stok_awal,
                COALESCE(p.masuk, 0) as masuk,
                COALESCE(p.keluar, 0) as keluar
            FROM items i
            LEFT JOIN item_categories c ON i.category_id = c.id
            LEFT JOIN (
                SELECT item_id, SUM(debit - kredit) as stok_awal
                FROM kartu_stok
                WHERE tanggal < ? AND user_id = ?
                GROUP BY item_id
            ) sa ON i.id = sa.item_id
            LEFT JOIN (
                SELECT 
                    item_id, 
                    SUM(debit) as masuk, 
                    SUM(kredit) as keluar
                FROM kartu_stok
                WHERE tanggal BETWEEN ? AND CONCAT(?, ' 23:59:59') AND user_id = ?
                GROUP BY item_id
            ) p ON i.id = p.item_id
            WHERE i.user_id = ?
        ";
        $stmt_ks = $conn->prepare($ks_sql);
        $stmt_ks->bind_param('sissii', $start_date, $user_id, $start_date, $end_date, $user_id, $user_id);
        $stmt_ks->execute();
        $normal_items = stmt_fetch_all($stmt_ks);
        $stmt_ks->close();

        foreach ($normal_items as $item) {
            $key = 'normal_' . $item['id'];
            $sale = $sales_by_key[$key] ?? null;

            $stok_awal = (int)$item['stok_awal'];
            $masuk = (int)$item['masuk'];
            $keluar = (int)$item['keluar'];
            $stok_akhir = $stok_awal + $masuk - $keluar;

            $qty_terjual = $sale ? (int)$sale['qty_terjual'] : 0;
            $total_penjualan = $sale ? (float)$sale['total_neto'] : 0.0;
            $total_hpp = $sale ? (float)$sale['total_hpp'] : 0.0;
            $total_margin = $sale ? (float)$sale['total_margin'] : 0.0;
            $margin_pct = ($total_penjualan > 0) ? ($total_margin / $total_penjualan * 100) : 0.0;
            $avg_harga_jual = ($qty_terjual > 0) ? ($total_penjualan / $qty_terjual) : (float)$item['harga_jual'];

            $items_map[$key] = [
                'id' => (int)$item['id'],
                'item_type' => 'normal',
                'type_label' => 'Toko',
                'sku' => $item['sku'] ?? '',
                'nama_barang' => $item['nama_barang'],
                'kategori' => $item['kategori'],
                'stok_awal' => $stok_awal,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'stok_akhir' => $stok_akhir,
                'qty_terjual' => $qty_terjual,
                'avg_harga_jual' => $avg_harga_jual,
                'total_penjualan' => $total_penjualan,
                'total_hpp' => $total_hpp,
                'total_margin' => $total_margin,
                'margin_pct' => $margin_pct
            ];
        }
    }

    // ── 4. Consignment Items Stock Movements ─────────────────────────
    if ($item_type === 'all' || $item_type === 'consignment') {
        $cons_payable_acc = null;
        $res_cfg = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'consignment_payable_account' LIMIT 1");
        if ($res_cfg && $r_cfg = $res_cfg->fetch_assoc()) {
            $cons_payable_acc = $r_cfg['setting_value'];
        }

        $ci_sql = "
            SELECT 
                ci.id,
                ci.nama_barang,
                ci.sku,
                ci.harga_beli,
                ci.harga_jual,
                COALESCE(s.nama_pemasok, 'Konsinyasi') as kategori,
                (
                    ci.stok_awal 
                    + COALESCE((SELECT SUM(qty) FROM consignment_restocks WHERE consignment_item_id = ci.id AND tanggal < ?), 0)
                    - COALESCE((SELECT SUM(IF(debit > 0, -qty, qty)) FROM general_ledger WHERE consignment_item_id = ci.id AND ref_type IN ('jurnal', 'penjualan') AND account_id = ? AND DATE(tanggal) < ?), 0)
                ) as stok_awal,
                COALESCE((SELECT SUM(qty) FROM consignment_restocks WHERE consignment_item_id = ci.id AND tanggal BETWEEN ? AND ?), 0) as masuk,
                COALESCE((SELECT SUM(IF(debit > 0, -qty, qty)) FROM general_ledger WHERE consignment_item_id = ci.id AND ref_type IN ('jurnal', 'penjualan') AND account_id = ? AND DATE(tanggal) BETWEEN ? AND ?), 0) as keluar
            FROM consignment_items ci
            LEFT JOIN suppliers s ON ci.supplier_id = s.id
            WHERE ci.user_id = ?
        ";
        $stmt_ci = $conn->prepare($ci_sql);
        $stmt_ci->bind_param('sississsi', $start_date, $cons_payable_acc, $start_date, $start_date, $end_date, $cons_payable_acc, $start_date, $end_date, $user_id);
        $stmt_ci->execute();
        $cons_items = stmt_fetch_all($stmt_ci);
        $stmt_ci->close();

        foreach ($cons_items as $item) {
            $key = 'consignment_' . $item['id'];
            $sale = $sales_by_key[$key] ?? null;

            $stok_awal = (int)$item['stok_awal'];
            $masuk = (int)$item['masuk'];
            $keluar = (int)$item['keluar'];
            $stok_akhir = $stok_awal + $masuk - $keluar;

            $qty_terjual = $sale ? (int)$sale['qty_terjual'] : 0;
            $total_penjualan = $sale ? (float)$sale['total_neto'] : 0.0;
            $total_hpp = $sale ? (float)$sale['total_hpp'] : 0.0;
            $total_margin = $sale ? (float)$sale['total_margin'] : 0.0;
            $margin_pct = ($total_penjualan > 0) ? ($total_margin / $total_penjualan * 100) : 0.0;
            $avg_harga_jual = ($qty_terjual > 0) ? ($total_penjualan / $qty_terjual) : (float)$item['harga_jual'];

            $items_map[$key] = [
                'id' => (int)$item['id'],
                'item_type' => 'consignment',
                'type_label' => 'Konsinyasi',
                'sku' => $item['sku'] ?? '',
                'nama_barang' => $item['nama_barang'],
                'kategori' => $item['kategori'],
                'stok_awal' => $stok_awal,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'stok_akhir' => $stok_akhir,
                'qty_terjual' => $qty_terjual,
                'avg_harga_jual' => $avg_harga_jual,
                'total_penjualan' => $total_penjualan,
                'total_hpp' => $total_hpp,
                'total_margin' => $total_margin,
                'margin_pct' => $margin_pct
            ];
        }
    }

    // ── 5. In case an item was sold but not in items table (fallback) ──
    foreach ($sales_by_key as $key => $sale) {
        if (!isset($items_map[$key])) {
            $total_penjualan = (float)$sale['total_neto'];
            $total_hpp = (float)$sale['total_hpp'];
            $total_margin = (float)$sale['total_margin'];
            $qty_terjual = (int)$sale['qty_terjual'];
            $margin_pct = ($total_penjualan > 0) ? ($total_margin / $total_penjualan * 100) : 0.0;
            $avg_harga_jual = ($qty_terjual > 0) ? ($total_penjualan / $qty_terjual) : 0.0;

            $items_map[$key] = [
                'id' => (int)$sale['item_id'],
                'item_type' => $sale['item_type'],
                'type_label' => ($sale['item_type'] === 'consignment' ? 'Konsinyasi' : 'Toko'),
                'sku' => '-',
                'nama_barang' => !empty($sale['deskripsi_item']) ? $sale['deskripsi_item'] : ('Item #' . $sale['item_id'] . ' (Non-Master)'),
                'kategori' => 'Lainnya',
                'stok_awal' => 0,
                'masuk' => 0,
                'keluar' => $qty_terjual,
                'stok_akhir' => 0,
                'qty_terjual' => $qty_terjual,
                'avg_harga_jual' => $avg_harga_jual,
                'total_penjualan' => $total_penjualan,
                'total_hpp' => $total_hpp,
                'total_margin' => $total_margin,
                'margin_pct' => $margin_pct
            ];
        }
    }

    // ── 6. Filtering ────────────────────────────────────────────────
    $filtered_items = [];
    $total_masuk_filtered = 0;
    $total_keluar_filtered = 0;

    foreach ($items_map as $item) {
        // Filter search keyword
        if (!empty($search)) {
            $s = mb_strtolower($search);
            if (strpos(mb_strtolower($item['nama_barang']), $s) === false && 
                strpos(mb_strtolower($item['sku']), $s) === false) {
                continue;
            }
        }

        // Filter movement / activity
        if ($movement_filter === 'with_sales') {
            if ($item['qty_terjual'] <= 0) continue;
        } elseif ($movement_filter === 'with_activity') {
            if ($item['qty_terjual'] <= 0 && $item['masuk'] <= 0 && $item['keluar'] <= 0 && $item['stok_awal'] == 0 && $item['stok_akhir'] == 0) {
                continue;
            }
        }

        $total_masuk_filtered += $item['masuk'];
        $total_keluar_filtered += $item['keluar'];
        $filtered_items[] = $item;
    }

    $summary['total_masuk'] = $total_masuk_filtered;
    $summary['total_keluar'] = $total_keluar_filtered;

    // ── 7. Sorting ──────────────────────────────────────────────────
    usort($filtered_items, function ($a, $b) use ($sort_by, $sort_order) {
        $valA = $a[$sort_by] ?? null;
        $valB = $b[$sort_by] ?? null;

        if (is_string($valA)) {
            $cmp = strcasecmp($valA, $valB);
        } else {
            if ($valA == $valB) {
                $cmp = 0;
            } else {
                $cmp = ($valA < $valB) ? -1 : 1;
            }
        }

        return ($sort_order === 'asc') ? $cmp : -$cmp;
    });

    // ── 8. Pagination ───────────────────────────────────────────────
    $total_records = count($filtered_items);
    if ($limit > 0) {
        $total_pages = ceil($total_records / $limit);
        $offset = ($page - 1) * $limit;
        $paginated_data = array_slice($filtered_items, $offset, $limit);
    } else {
        // Limit -1 or 0 means all records (for PDF/CSV export)
        $total_pages = 1;
        $paginated_data = $filtered_items;
    }

    $response = [
        'status' => 'success',
        'data' => $paginated_data,
        'summary' => $summary,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $total_pages,
            'total_records' => $total_records
        ]
    ];

    send_json_response($response, $cache_key, 300);

} catch (Exception $e) {
    send_error_response($e->getMessage(), 500);
}