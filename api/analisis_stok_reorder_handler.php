<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
check_permission('analisis_stok_reorder', 'menu');

$conn = Database::getInstance()->getConnection();
$user_id = 1; // ID Pemilik Toko

try {
    // 1. Parameter Input
    $period_preset = $_GET['period_preset'] ?? '30';
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $start_date = date('Y-m-d', strtotime($_GET['start_date']));
        $end_date = date('Y-m-d', strtotime($_GET['end_date']));
    } else {
        $days_back = in_array($period_preset, ['7', '30', '60', '90', '180']) ? (int)$period_preset : 30;
        $start_date = date('Y-m-d', strtotime('-' . ($days_back - 1) . ' days'));
        $end_date = date('Y-m-d');
    }

    $buffer_days = isset($_GET['buffer_days']) ? max(1, min(180, (int)$_GET['buffer_days'])) : 14;
    $category_id = isset($_GET['category_id']) && $_GET['category_id'] !== 'all' ? (int)$_GET['category_id'] : null;
    $tab_filter = $_GET['tab'] ?? 'all'; // all, critical, fast_moving, slow_moving, dead_stock, overstocked
    $search = trim($_GET['search'] ?? '');
    $sort_by = $_GET['sort_by'] ?? 'total_neto';
    $sort_order = strtolower($_GET['sort_order'] ?? 'desc');

    // Hitung total hari pengamatan
    $total_days = max(1, (int)round((strtotime($end_date) - strtotime($start_date)) / 86400) + 1);

    // 2. Ambil Agregasi Penjualan Barang Normal
    $sales_sql = "
        SELECT 
            pd.item_id,
            SUM(pd.quantity) as qty_sold,
            SUM(pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) as total_neto,
            SUM((pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) - (pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, 0)))) as total_margin,
            MAX(p.tanggal_penjualan) as last_sold_date
        FROM penjualan_details pd
        JOIN penjualan p ON pd.penjualan_id = p.id
        LEFT JOIN items i ON pd.item_id = i.id
        WHERE p.user_id = ?
          AND DATE(p.tanggal_penjualan) >= ?
          AND DATE(p.tanggal_penjualan) <= ?
          AND p.status = 'completed'
          AND pd.item_type = 'normal'
        GROUP BY pd.item_id
    ";
    $stmt_sales = $conn->prepare($sales_sql);
    $stmt_sales->bind_param('iss', $user_id, $start_date, $end_date);
    $stmt_sales->execute();
    $sales_raw = stmt_fetch_all($stmt_sales);
    $stmt_sales->close();

    $sales_map = [];
    $grand_store_sales = 0.0;
    $grand_store_margin = 0.0;
    foreach ($sales_raw as $sr) {
        $sales_map[(int)$sr['item_id']] = $sr;
        $grand_store_sales += (float)$sr['total_neto'];
        $grand_store_margin += (float)$sr['total_margin'];
    }

    // 3. Ambil Master Items
    $items_where = ["i.user_id = ?"];
    $items_params = [$user_id];
    $items_types = "i";

    if ($category_id !== null) {
        if ($category_id === 0) {
            $items_where[] = "i.category_id IS NULL";
        } else {
            $items_where[] = "i.category_id = ?";
            $items_params[] = $category_id;
            $items_types .= "i";
        }
    }

    $items_sql = "
        SELECT 
            i.id,
            i.nama_barang,
            i.sku,
            i.barcode,
            i.category_id,
            COALESCE(ic.nama_kategori, 'Tanpa Kategori') as nama_kategori,
            i.harga_beli,
            i.harga_jual,
            i.stok
        FROM items i
        LEFT JOIN item_categories ic ON i.category_id = ic.id
        WHERE " . implode(' AND ', $items_where) . "
        ORDER BY i.nama_barang ASC
    ";
    $stmt_items = $conn->prepare($items_sql);
    $stmt_items->bind_param($items_types, ...$items_params);
    $stmt_items->execute();
    $items_raw = stmt_fetch_all($stmt_items);
    $stmt_items->close();

    // 4. Proses Matriks Komputasi Produk
    $processed = [];
    $total_inventory_value = 0.0;

    foreach ($items_raw as $it) {
        $id = (int)$it['id'];
        $s = $sales_map[$id] ?? null;

        $qty_sold = $s ? (int)$s['qty_sold'] : 0;
        $neto = $s ? (float)$s['total_neto'] : 0.0;
        $margin = $s ? (float)$s['total_margin'] : 0.0;
        $last_sold = $s ? $s['last_sold_date'] : null;

        $stok = (int)$it['stok'];
        $harga_beli = (float)$it['harga_beli'];
        $harga_jual = (float)$it['harga_jual'];
        $nilai_stok = $stok * $harga_beli;
        if ($nilai_stok > 0) {
            $total_inventory_value += $nilai_stok;
        }

        // Laju Penjualan Harian (ADS)
        $ads = round($qty_sold / $total_days, 2);

        $processed[] = [
            'id' => $id,
            'nama_barang' => $it['nama_barang'],
            'sku' => (!empty($it['sku']) && $it['sku'] !== '') ? $it['sku'] : '-',
            'barcode' => (!empty($it['barcode']) && $it['barcode'] !== '') ? $it['barcode'] : '-',
            'category_id' => $it['category_id'] ? (int)$it['category_id'] : 0,
            'nama_kategori' => $it['nama_kategori'],
            'harga_beli' => $harga_beli,
            'harga_jual' => $harga_jual,
            'stok' => $stok,
            'nilai_stok' => $nilai_stok,
            'qty_sold' => $qty_sold,
            'total_neto' => $neto,
            'total_margin' => $margin,
            'last_sold_date' => $last_sold ? date('d/m/Y', strtotime($last_sold)) : '-',
            'ads' => $ads
        ];
    }

    // 5. Klasifikasi Pareto ABC (Berdasarkan Omset Penjualan Bersih)
    usort($processed, fn($a, $b) => $b['total_neto'] <=> $a['total_neto']);

    $cumulative_sales = 0.0;
    $dead_stock_value = 0.0;
    $dead_stock_count = 0;
    $reorder_capital_needed = 0.0;
    $critical_sku_count = 0;
    $overstocked_sku_count = 0;
    $counts = [
        'A' => 0,
        'B' => 0,
        'C' => 0,
        'Dead' => 0,
        'Critical' => 0,
        'Reorder' => 0,
        'Optimal' => 0,
        'Overstock' => 0
    ];

    foreach ($processed as &$item) {
        // Logika Pareto ABC
        if ($grand_store_sales > 0 && $item['total_neto'] > 0) {
            $cumulative_sales += $item['total_neto'];
            $cum_pct = ($cumulative_sales / $grand_store_sales) * 100;
            if ($cum_pct <= 80.01) {
                $item['abc_class'] = 'A'; // Fast-Moving
                $item['abc_label'] = 'Fast-Moving (A)';
                $counts['A']++;
            } elseif ($cum_pct <= 95.01) {
                $item['abc_class'] = 'B'; // Slow-Moving
                $item['abc_label'] = 'Slow-Moving (B)';
                $counts['B']++;
            } else {
                $item['abc_class'] = 'C'; // Non-Moving / Bottom
                $item['abc_label'] = 'Non-Moving (C)';
                $counts['C']++;
            }
        } else {
            if ($item['stok'] > 0) {
                $item['abc_class'] = 'Dead';
                $item['abc_label'] = 'Dead Stock';
                $counts['Dead']++;
                $dead_stock_count++;
                $dead_stock_value += $item['nilai_stok'];
            } else {
                $item['abc_class'] = 'C';
                $item['abc_label'] = 'Non-Moving (C)';
                $counts['C']++;
            }
        }

        // Estimasi Sisa Hari Habis Stok (Run-Out Forecast)
        if ($item['ads'] > 0) {
            $run_out = round($item['stok'] / $item['ads'], 1);
            $item['run_out_days'] = $run_out;

            if ($item['stok'] <= 0) {
                $item['stock_status'] = 'habis';
                $item['stock_status_label'] = 'Habis (Stockout)';
                $item['stock_status_badge'] = 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/40 dark:text-red-300';
                $counts['Critical']++;
                $critical_sku_count++;
            } elseif ($run_out <= 7) {
                $item['stock_status'] = 'kritis';
                $item['stock_status_label'] = 'Kritis (≤ 7 Hari)';
                $item['stock_status_badge'] = 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-900/40 dark:text-orange-300';
                $counts['Critical']++;
                $critical_sku_count++;
            } elseif ($run_out <= 14) {
                $item['stock_status'] = 'reorder';
                $item['stock_status_label'] = 'Perlu Reorder (8-14 Hari)';
                $item['stock_status_badge'] = 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/40 dark:text-amber-300';
                $counts['Reorder']++;
            } elseif ($run_out <= 45) {
                $item['stock_status'] = 'aman';
                $item['stock_status_label'] = 'Aman (15-45 Hari)';
                $item['stock_status_badge'] = 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300';
                $counts['Optimal']++;
            } else {
                $item['stock_status'] = 'overstock';
                $item['stock_status_label'] = 'Overstock (> 45 Hari)';
                $item['stock_status_badge'] = 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/40 dark:text-purple-300';
                $counts['Overstock']++;
                $overstocked_sku_count++;
            }
        } else {
            if ($item['stok'] > 0) {
                $item['run_out_days'] = null; // Tidak ada perputaran
                $item['stock_status'] = 'dead_stock';
                $item['stock_status_label'] = 'Dead Stock (0 Sales)';
                $item['stock_status_badge'] = 'bg-gray-100 text-gray-800 border-gray-300 dark:bg-gray-800 dark:text-gray-300';
            } else {
                $item['run_out_days'] = 0;
                $item['stock_status'] = 'habis_mati';
                $item['stock_status_label'] = 'Kosong & Tanpa Sales';
                $item['stock_status_badge'] = 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400';
            }
        }

        // Rekomendasi Kuantitas Reorder & Estimasi Modal
        $target_stock = (int)ceil($item['ads'] * $buffer_days);
        $suggested_reorder_qty = max(0, $target_stock - $item['stok']);
        $item['target_stock'] = $target_stock;
        $item['suggested_reorder_qty'] = $suggested_reorder_qty;
        $item['estimasi_modal_reorder'] = $suggested_reorder_qty * $item['harga_beli'];

        if ($suggested_reorder_qty > 0) {
            $reorder_capital_needed += $item['estimasi_modal_reorder'];
        }
    }
    unset($item);

    // 6. Siapkan Dataset untuk Visualisasi Chart.js
    // Chart 1: Donut Nilai Modal Persediaan Toko
    $capital_by_class = [
        'Fast-Moving (A)' => 0.0,
        'Slow-Moving (B)' => 0.0,
        'Non-Moving (C)' => 0.0,
        'Dead Stock' => 0.0,
    ];
    foreach ($processed as $p) {
        if ($p['nilai_stok'] <= 0) continue;
        if ($p['abc_class'] === 'A') $capital_by_class['Fast-Moving (A)'] += $p['nilai_stok'];
        elseif ($p['abc_class'] === 'B') $capital_by_class['Slow-Moving (B)'] += $p['nilai_stok'];
        elseif ($p['abc_class'] === 'Dead') $capital_by_class['Dead Stock'] += $p['nilai_stok'];
        else $capital_by_class['Non-Moving (C)'] += $p['nilai_stok'];
    }

    // Chart 2: Top 10 SKU Paling Mendesak Reorder (Run-out terkecil & Suggested Qty > 0)
    $urgent_candidates = array_filter($processed, fn($x) => $x['suggested_reorder_qty'] > 0 && in_array($x['stock_status'], ['habis', 'kritis', 'reorder']));
    usort($urgent_candidates, function($a, $b) {
        $ra = $a['run_out_days'] !== null ? $a['run_out_days'] : 999;
        $rb = $b['run_out_days'] !== null ? $b['run_out_days'] : 999;
        return $ra <=> $rb;
    });
    $top_urgent_slices = array_slice($urgent_candidates, 0, 8);

    $top_urgent_labels = [];
    $top_urgent_stock = [];
    $top_urgent_target = [];
    $top_urgent_reorder = [];
    foreach ($top_urgent_slices as $tu) {
        $nama_singkat = mb_strimwidth($tu['nama_barang'], 0, 20, '...');
        $top_urgent_labels[] = $nama_singkat;
        $top_urgent_stock[] = $tu['stok'];
        $top_urgent_target[] = $tu['target_stock'];
        $top_urgent_reorder[] = $tu['suggested_reorder_qty'];
    }

    // 7. Filter Tab Client-Side / Backend
    $filtered_data = $processed;
    if ($tab_filter === 'critical') {
        $filtered_data = array_filter($filtered_data, fn($x) => in_array($x['stock_status'], ['habis', 'kritis']));
    } elseif ($tab_filter === 'reorder') {
        $filtered_data = array_filter($filtered_data, fn($x) => $x['suggested_reorder_qty'] > 0);
    } elseif ($tab_filter === 'fast_moving') {
        $filtered_data = array_filter($filtered_data, fn($x) => $x['abc_class'] === 'A');
    } elseif ($tab_filter === 'slow_moving') {
        $filtered_data = array_filter($filtered_data, fn($x) => $x['abc_class'] === 'B');
    } elseif ($tab_filter === 'dead_stock') {
        $filtered_data = array_filter($filtered_data, fn($x) => $x['abc_class'] === 'Dead');
    } elseif ($tab_filter === 'overstocked') {
        $filtered_data = array_filter($filtered_data, fn($x) => $x['stock_status'] === 'overstock');
    }

    // Filter Search
    if (!empty($search)) {
        $search_lower = mb_strtolower($search);
        $filtered_data = array_filter($filtered_data, function($x) use ($search_lower) {
            return (
                mb_stripos($x['nama_barang'], $search_lower) !== false ||
                mb_stripos($x['sku'], $search_lower) !== false ||
                mb_stripos($x['barcode'], $search_lower) !== false ||
                mb_stripos($x['nama_kategori'], $search_lower) !== false
            );
        });
    }

    // 8. Sorting
    usort($filtered_data, function($a, $b) use ($sort_by, $sort_order) {
        $valA = $a[$sort_by] ?? null;
        $valB = $b[$sort_by] ?? null;

        if ($valA === null && $valB !== null) return 1;
        if ($valB === null && $valA !== null) return -1;
        if ($valA === null && $valB === null) return 0;

        if (is_string($valA)) {
            $cmp = strcasecmp($valA, $valB);
        } else {
            if ($valA == $valB) $cmp = 0;
            else $cmp = ($valA < $valB) ? -1 : 1;
        }
        return ($sort_order === 'asc') ? $cmp : -$cmp;
    });

    // Reset numeric keys
    $filtered_data = array_values($filtered_data);

    // 9. Ambil Daftar Kategori untuk Filter UI
    $cats_res = $conn->query("SELECT id, nama_kategori FROM item_categories ORDER BY nama_kategori ASC");
    $categories = $cats_res ? $cats_res->fetch_all(MYSQLI_ASSOC) : [];

    // 10. Respon JSON
    echo json_encode([
        'status' => 'success',
        'meta' => [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'period_days' => $total_days,
            'buffer_days' => $buffer_days,
            'total_items_analyzed' => count($processed),
            'filtered_count' => count($filtered_data)
        ],
        'summary' => [
            'total_store_sales' => $grand_store_sales,
            'total_store_margin' => $grand_store_margin,
            'total_inventory_value' => $total_inventory_value,
            'dead_stock_value' => $dead_stock_value,
            'dead_stock_sku_count' => $dead_stock_count,
            'critical_sku_count' => $critical_sku_count,
            'reorder_capital_needed' => $reorder_capital_needed,
            'overstocked_sku_count' => $overstocked_sku_count,
            'counts' => $counts
        ],
        'charts' => [
            'capital_composition' => [
                'labels' => array_keys($capital_by_class),
                'values' => array_values($capital_by_class)
            ],
            'top_urgent_reorder' => [
                'labels' => $top_urgent_labels,
                'stok' => $top_urgent_stock,
                'target' => $top_urgent_target,
                'reorder' => $top_urgent_reorder
            ]
        ],
        'categories' => $categories,
        'data' => $filtered_data
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
