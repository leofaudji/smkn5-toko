<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
check_permission('margin_kategori', 'menu');

$conn = Database::getInstance()->getConnection();
$user_id = 1; // ID Pemilik Data (Toko)

$action = $_GET['action'] ?? 'get_report';

// -------------------------------------------------------------------------------------------------
// 1. DRILL-DOWN: Rincian Barang per Kategori
// -------------------------------------------------------------------------------------------------
if ($action === 'get_category_items') {
    $category_name = trim($_GET['category'] ?? '');
    $start_date = !empty($_GET['start_date']) ? date('Y-m-d', strtotime($_GET['start_date'])) : date('Y-m-01');
    $end_date = !empty($_GET['end_date']) ? date('Y-m-d', strtotime($_GET['end_date'])) : date('Y-m-d');

    if (empty($category_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Parameter category dibutuhkan.']);
        exit;
    }

    try {
        if ($category_name === 'Barang Konsinyasi') {
            $sql = "
                SELECT 
                    pd.item_id,
                    'consignment' as item_type,
                    MAX(pd.deskripsi_item) as deskripsi_item,
                    MAX(ci.nama_barang) as nama_barang,
                    MAX(ci.sku) as sku,
                    SUM(pd.quantity) as qty,
                    SUM(pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) as total_neto,
                    SUM(pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(ci.harga_beli, 0))) as total_hpp,
                    SUM((pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) - (pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(ci.harga_beli, 0)))) as total_margin
                FROM penjualan_details pd
                JOIN penjualan p ON pd.penjualan_id = p.id
                LEFT JOIN consignment_items ci ON pd.item_id = ci.id AND pd.item_type = 'consignment'
                WHERE p.user_id = ? 
                  AND DATE(p.tanggal_penjualan) >= ? 
                  AND DATE(p.tanggal_penjualan) <= ?
                  AND p.status = 'completed'
                  AND pd.item_type = 'consignment'
                GROUP BY pd.item_id
                ORDER BY total_neto DESC
                LIMIT 50
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iss', $user_id, $start_date, $end_date);
        } elseif ($category_name === 'Tanpa Kategori / Umum') {
            $sql = "
                SELECT 
                    pd.item_id,
                    pd.item_type,
                    MAX(pd.deskripsi_item) as deskripsi_item,
                    MAX(i.nama_barang) as nama_barang,
                    MAX(i.sku) as sku,
                    SUM(pd.quantity) as qty,
                    SUM(pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) as total_neto,
                    SUM(pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, 0))) as total_hpp,
                    SUM((pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) - (pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, 0)))) as total_margin
                FROM penjualan_details pd
                JOIN penjualan p ON pd.penjualan_id = p.id
                LEFT JOIN items i ON pd.item_id = i.id AND pd.item_type = 'normal'
                LEFT JOIN item_categories ic ON i.category_id = ic.id
                WHERE p.user_id = ? 
                  AND DATE(p.tanggal_penjualan) >= ? 
                  AND DATE(p.tanggal_penjualan) <= ?
                  AND p.status = 'completed'
                  AND pd.item_type = 'normal'
                  AND (i.category_id IS NULL OR ic.nama_kategori IS NULL)
                GROUP BY pd.item_id
                ORDER BY total_neto DESC
                LIMIT 50
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iss', $user_id, $start_date, $end_date);
        } else {
            $sql = "
                SELECT 
                    pd.item_id,
                    pd.item_type,
                    MAX(pd.deskripsi_item) as deskripsi_item,
                    MAX(i.nama_barang) as nama_barang,
                    MAX(i.sku) as sku,
                    SUM(pd.quantity) as qty,
                    SUM(pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) as total_neto,
                    SUM(pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, 0))) as total_hpp,
                    SUM((pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) - (pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, 0)))) as total_margin
                FROM penjualan_details pd
                JOIN penjualan p ON pd.penjualan_id = p.id
                LEFT JOIN items i ON pd.item_id = i.id AND pd.item_type = 'normal'
                JOIN item_categories ic ON i.category_id = ic.id
                WHERE p.user_id = ? 
                  AND DATE(p.tanggal_penjualan) >= ? 
                  AND DATE(p.tanggal_penjualan) <= ?
                  AND p.status = 'completed'
                  AND pd.item_type = 'normal'
                  AND ic.nama_kategori = ?
                GROUP BY pd.item_id
                ORDER BY total_neto DESC
                LIMIT 50
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('isss', $user_id, $start_date, $end_date, $category_name);
        }

        $stmt->execute();
        $items_raw = stmt_fetch_all($stmt);
        $stmt->close();

        $items = [];
        foreach ($items_raw as $item) {
            $neto = (float)$item['total_neto'];
            $hpp = (float)$item['total_hpp'];
            $margin = (float)$item['total_margin'];
            $pct = $neto > 0 ? ($margin / $neto * 100) : 0.0;

            $items[] = [
                'item_id' => (int)$item['item_id'],
                'item_type' => $item['item_type'],
                'sku' => (!empty($item['sku']) && $item['sku'] !== '') ? $item['sku'] : '-',
                'nama_barang' => !empty($item['nama_barang']) ? $item['nama_barang'] : (!empty($item['deskripsi_item']) ? $item['deskripsi_item'] : 'Item #' . $item['item_id']),
                'qty' => (int)$item['qty'],
                'total_neto' => $neto,
                'total_hpp' => $hpp,
                'total_margin' => $margin,
                'margin_pct' => round($pct, 2)
            ];
        }

        echo json_encode([
            'status' => 'success',
            'category' => $category_name,
            'data' => $items
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// -------------------------------------------------------------------------------------------------
// 2. MAIN REPORT: Agregasi Kontribusi & Margin per Kategori
// -------------------------------------------------------------------------------------------------
$start_date = !empty($_GET['start_date']) ? date('Y-m-d', strtotime($_GET['start_date'])) : date('Y-m-01');
$end_date = !empty($_GET['end_date']) ? date('Y-m-d', strtotime($_GET['end_date'])) : date('Y-m-d');
$sort_by = $_GET['sort_by'] ?? 'total_neto';
$sort_order = strtolower($_GET['sort_order'] ?? 'desc');

try {
    // 1. Agregasi Penjualan Berdasarkan Kategori
    $sql = "
        SELECT 
            CASE 
                WHEN pd.item_type = 'consignment' THEN 'Barang Konsinyasi'
                WHEN ic.nama_kategori IS NOT NULL THEN ic.nama_kategori
                ELSE 'Tanpa Kategori / Umum'
            END AS kategori_nama,
            MAX(COALESCE(ic.id, 0)) as kategori_id,
            COUNT(DISTINCT pd.item_id) as total_sku,
            SUM(pd.quantity) as total_qty,
            SUM(pd.subtotal) as total_bruto,
            SUM(pd.subtotal / NULLIF(p.subtotal, 0) * p.discount) as total_diskon,
            SUM(pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) as total_neto,
            SUM(pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, ci.harga_beli, 0))) as total_hpp,
            SUM((pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) - (pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, ci.harga_beli, 0)))) as total_margin
        FROM penjualan_details pd
        JOIN penjualan p ON pd.penjualan_id = p.id
        LEFT JOIN items i ON pd.item_id = i.id AND pd.item_type = 'normal'
        LEFT JOIN item_categories ic ON i.category_id = ic.id
        LEFT JOIN consignment_items ci ON pd.item_id = ci.id AND pd.item_type = 'consignment'
        WHERE p.user_id = ? 
          AND DATE(p.tanggal_penjualan) >= ? 
          AND DATE(p.tanggal_penjualan) <= ?
          AND p.status = 'completed'
        GROUP BY kategori_nama
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $user_id, $start_date, $end_date);
    $stmt->execute();
    $rows = stmt_fetch_all($stmt);
    $stmt->close();

    // 2. Hitung Grand Total Toko
    $grand_bruto = 0.0;
    $grand_diskon = 0.0;
    $grand_neto = 0.0;
    $grand_hpp = 0.0;
    $grand_margin = 0.0;
    $grand_qty = 0;

    foreach ($rows as $r) {
        $grand_bruto += (float)$r['total_bruto'];
        $grand_diskon += (float)$r['total_diskon'];
        $grand_neto += (float)$r['total_neto'];
        $grand_hpp += (float)$r['total_hpp'];
        $grand_margin += (float)$r['total_margin'];
        $grand_qty += (int)$r['total_qty'];
    }

    $grand_margin_pct = $grand_neto > 0 ? ($grand_margin / $grand_neto * 100) : 0.0;

    // 3. Hitung Rasio Kontribusi & Margin per Kategori
    $data = [];
    foreach ($rows as $r) {
        $neto = (float)$r['total_neto'];
        $hpp = (float)$r['total_hpp'];
        $margin = (float)$r['total_margin'];
        $bruto = (float)$r['total_bruto'];
        $diskon = (float)$r['total_diskon'];
        $qty = (int)$r['total_qty'];
        $sku = (int)$r['total_sku'];

        $kontribusi_sales = $grand_neto > 0 ? ($neto / $grand_neto * 100) : 0.0;
        $kontribusi_margin = $grand_margin > 0 ? ($margin / $grand_margin * 100) : 0.0;
        $margin_pct = $neto > 0 ? ($margin / $neto * 100) : 0.0;

        $data[] = [
            'kategori_id' => (int)$r['kategori_id'],
            'kategori_nama' => $r['kategori_nama'],
            'total_sku' => $sku,
            'total_qty' => $qty,
            'total_bruto' => $bruto,
            'total_diskon' => $diskon,
            'total_neto' => $neto,
            'kontribusi_sales_pct' => round($kontribusi_sales, 2),
            'total_hpp' => $hpp,
            'total_margin' => $margin,
            'kontribusi_margin_pct' => round($kontribusi_margin, 2),
            'margin_pct' => round($margin_pct, 2)
        ];
    }

    // 4. Sorting
    usort($data, function ($a, $b) use ($sort_by, $sort_order) {
        $valA = $a[$sort_by] ?? null;
        $valB = $b[$sort_by] ?? null;

        if (is_string($valA)) {
            $cmp = strcasecmp($valA, $valB);
        } else {
            if ($valA == $valB) $cmp = 0;
            else $cmp = ($valA < $valB) ? -1 : 1;
        }

        return ($sort_order === 'asc') ? $cmp : -$cmp;
    });

    // 5. Metrik Eksekutif (Top Categories)
    $top_sales = null;
    $top_margin = null;
    $top_pct = null;

    if (!empty($data)) {
        // Sort temp by sales
        $by_sales = $data;
        usort($by_sales, fn($a, $b) => $b['total_neto'] <=> $a['total_neto']);
        $top_sales = $by_sales[0];

        // Sort temp by margin
        $by_margin = $data;
        usort($by_margin, fn($a, $b) => $b['total_margin'] <=> $a['total_margin']);
        $top_margin = $by_margin[0];

        // Sort temp by margin pct (minimum sales > 50,000 to be meaningful)
        $meaningful = array_filter($data, fn($item) => $item['total_neto'] >= 50000 && $item['total_margin'] > 0);
        if (!empty($meaningful)) {
            usort($meaningful, fn($a, $b) => $b['margin_pct'] <=> $a['margin_pct']);
            $top_pct = reset($meaningful);
        } else {
            $top_pct = $top_margin;
        }
    }

    // 6. Siapkan Dataset untuk Visualisasi Chart.js
    // Chart 1: Donut Chart Penjualan (Top 6 + Lainnya)
    $sales_sorted = $data;
    usort($sales_sorted, fn($a, $b) => $b['total_neto'] <=> $a['total_neto']);
    $top_sales_slices = array_slice($sales_sorted, 0, 6);
    $other_sales_slices = array_slice($sales_sorted, 6);

    $chart_sales_labels = [];
    $chart_sales_values = [];
    foreach ($top_sales_slices as $slice) {
        $chart_sales_labels[] = $slice['kategori_nama'];
        $chart_sales_values[] = $slice['total_neto'];
    }
    if (!empty($other_sales_slices)) {
        $other_sum = array_sum(array_column($other_sales_slices, 'total_neto'));
        if ($other_sum > 0) {
            $chart_sales_labels[] = 'Kategori Lainnya';
            $chart_sales_values[] = $other_sum;
        }
    }

    // Chart 2: Donut Chart Margin (Top 6 + Lainnya)
    $margin_sorted = $data;
    usort($margin_sorted, fn($a, $b) => $b['total_margin'] <=> $a['total_margin']);
    $top_margin_slices = array_slice($margin_sorted, 0, 6);
    $other_margin_slices = array_slice($margin_sorted, 6);

    $chart_margin_labels = [];
    $chart_margin_values = [];
    foreach ($top_margin_slices as $slice) {
        $chart_margin_labels[] = $slice['kategori_nama'];
        $chart_margin_values[] = max(0, $slice['total_margin']);
    }
    if (!empty($other_margin_slices)) {
        $other_margin_sum = array_sum(array_column($other_margin_slices, 'total_margin'));
        if ($other_margin_sum > 0) {
            $chart_margin_labels[] = 'Kategori Lainnya';
            $chart_margin_values[] = $other_margin_sum;
        }
    }

    // Chart 3: Horizontal Bar Chart (% Margin Kategori - Top 10 kategori dengan omset >= 50.000)
    $bar_candidates = array_filter($data, fn($d) => $d['total_neto'] >= 50000);
    usort($bar_candidates, fn($a, $b) => $b['margin_pct'] <=> $a['margin_pct']);
    $bar_slices = array_slice($bar_candidates, 0, 10);

    $chart_bar_labels = [];
    $chart_bar_pct = [];
    foreach ($bar_slices as $slice) {
        $chart_bar_labels[] = $slice['kategori_nama'];
        $chart_bar_pct[] = $slice['margin_pct'];
    }

    $summary = [
        'total_penjualan' => $grand_neto,
        'total_bruto' => $grand_bruto,
        'total_diskon' => $grand_diskon,
        'total_hpp' => $grand_hpp,
        'total_margin' => $grand_margin,
        'margin_pct' => round($grand_margin_pct, 2),
        'total_qty' => $grand_qty,
        'total_kategori' => count($data),
        'top_sales' => $top_sales ? [
            'kategori' => $top_sales['kategori_nama'],
            'nominal' => $top_sales['total_neto'],
            'kontribusi' => $top_sales['kontribusi_sales_pct']
        ] : null,
        'top_margin' => $top_margin ? [
            'kategori' => $top_margin['kategori_nama'],
            'nominal' => $top_margin['total_margin'],
            'kontribusi' => $top_margin['kontribusi_margin_pct']
        ] : null,
        'top_pct' => $top_pct ? [
            'kategori' => $top_pct['kategori_nama'],
            'margin_pct' => $top_pct['margin_pct'],
            'nominal_margin' => $top_pct['total_margin']
        ] : null
    ];

    echo json_encode([
        'status' => 'success',
        'summary' => $summary,
        'data' => $data,
        'charts' => [
            'sales_chart' => [
                'labels' => $chart_sales_labels,
                'values' => $chart_sales_values
            ],
            'margin_chart' => [
                'labels' => $chart_margin_labels,
                'values' => $chart_margin_values
            ],
            'bar_chart' => [
                'labels' => $chart_bar_labels,
                'values' => $chart_bar_pct
            ]
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}
