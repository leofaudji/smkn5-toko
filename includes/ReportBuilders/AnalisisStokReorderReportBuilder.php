<?php
require_once __DIR__ . '/ReportBuilderInterface.php';

if (!function_exists('format_currency_pdf')) {
    function format_currency_pdf($number) {
        if ($number === null || $number === '') return '-';
        $num = (float)$number;
        if ($num < 0) {
            return '(' . number_format(abs($num), 0, ',', '.') . ')';
        }
        return number_format($num, 0, ',', '.');
    }
}

class AnalisisStokReorderReportBuilder implements ReportBuilderInterface
{
    private $pdf;
    private $conn;
    private $params;

    public function __construct(PDF $pdf, mysqli $conn, array $params)
    {
        $this->pdf = $pdf;
        $this->conn = $conn;
        $this->params = $params;
    }

    public function build(): void
    {
        $period_preset = $this->params['period_preset'] ?? '30';
        if (!empty($this->params['start_date']) && !empty($this->params['end_date'])) {
            $start_date = date('Y-m-d', strtotime($this->params['start_date']));
            $end_date = date('Y-m-d', strtotime($this->params['end_date']));
        } else {
            $days_back = in_array($period_preset, ['7', '30', '60', '90', '180']) ? (int)$period_preset : 30;
            $start_date = date('Y-m-d', strtotime('-' . ($days_back - 1) . ' days'));
            $end_date = date('Y-m-d');
        }

        $buffer_days = isset($this->params['buffer_days']) ? max(1, min(180, (int)$this->params['buffer_days'])) : 14;
        $category_id = isset($this->params['category_id']) && $this->params['category_id'] !== 'all' ? (int)$this->params['category_id'] : null;
        $tab_filter = $this->params['tab'] ?? 'all';
        $user_id = (int)($this->params['user_id'] ?? 1);

        $total_days = max(1, (int)round((strtotime($end_date) - strtotime($start_date)) / 86400) + 1);

        $this->pdf->SetTitle('Analisis ABC & Prediksi Reorder Stok');
        $this->pdf->report_title = 'ANALISIS FAST-MOVING, SLOW-MOVING, DEAD STOCK & ESTIMASI REORDER';
        $this->pdf->report_period = "Periode Observasi: " . date('d M Y', strtotime($start_date)) . " s/d " . date('d M Y', strtotime($end_date)) . " ($total_days Hari) | Buffer Reorder: $buffer_days Hari";
        $this->pdf->SetMargins(15, 12, 15);
        $this->pdf->SetAutoPageBreak(true, 15);
        $this->pdf->AddPage('L', 'A4'); // Format Landscape A4

        $data = $this->fetchData($user_id, $start_date, $end_date, $buffer_days, $category_id, $tab_filter, $total_days);
        $this->render($data, $buffer_days);

        $this->pdf->signature_date = $end_date;
        $this->pdf->RenderSignatureBlock();
    }

    private function fetchData(int $user_id, string $start_date, string $end_date, int $buffer_days, ?int $category_id, string $tab_filter, int $total_days): array
    {
        // 1. Sales
        $sales_sql = "
            SELECT 
                pd.item_id,
                SUM(pd.quantity) as qty_sold,
                SUM(pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) as total_neto,
                SUM((pd.subtotal - (pd.subtotal / NULLIF(p.subtotal, 0) * p.discount)) - (pd.quantity * IF(pd.cost_price > 0, pd.cost_price, COALESCE(i.harga_beli, 0)))) as total_margin
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
        $stmt_sales = $this->conn->prepare($sales_sql);
        $stmt_sales->bind_param('iss', $user_id, $start_date, $end_date);
        $stmt_sales->execute();
        $sales_raw = stmt_fetch_all($stmt_sales);
        $stmt_sales->close();

        $sales_map = [];
        $grand_store_sales = 0.0;
        foreach ($sales_raw as $sr) {
            $sales_map[(int)$sr['item_id']] = $sr;
            $grand_store_sales += (float)$sr['total_neto'];
        }

        // 2. Items
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
        $stmt_items = $this->conn->prepare($items_sql);
        $stmt_items->bind_param($items_types, ...$items_params);
        $stmt_items->execute();
        $items_raw = stmt_fetch_all($stmt_items);
        $stmt_items->close();

        $processed = [];
        $total_inventory_val = 0.0;

        foreach ($items_raw as $it) {
            $id = (int)$it['id'];
            $s = $sales_map[$id] ?? null;

            $qty_sold = $s ? (int)$s['qty_sold'] : 0;
            $neto = $s ? (float)$s['total_neto'] : 0.0;
            $margin = $s ? (float)$s['total_margin'] : 0.0;

            $stok = (int)$it['stok'];
            $harga_beli = (float)$it['harga_beli'];
            $nilai_stok = $stok * $harga_beli;
            if ($nilai_stok > 0) $total_inventory_val += $nilai_stok;

            $ads = round($qty_sold / $total_days, 2);

            $processed[] = [
                'id' => $id,
                'nama_barang' => $it['nama_barang'],
                'sku' => (!empty($it['sku']) && $it['sku'] !== '') ? $it['sku'] : '-',
                'nama_kategori' => $it['nama_kategori'],
                'harga_beli' => $harga_beli,
                'stok' => $stok,
                'nilai_stok' => $nilai_stok,
                'qty_sold' => $qty_sold,
                'total_neto' => $neto,
                'total_margin' => $margin,
                'ads' => $ads
            ];
        }

        // Pareto sort
        usort($processed, fn($a, $b) => $b['total_neto'] <=> $a['total_neto']);
        $cumulative = 0.0;
        $dead_stock_val = 0.0;
        $dead_stock_count = 0;
        $critical_count = 0;
        $reorder_modal = 0.0;

        foreach ($processed as &$item) {
            if ($grand_store_sales > 0 && $item['total_neto'] > 0) {
                $cumulative += $item['total_neto'];
                $cum_pct = ($cumulative / $grand_store_sales) * 100;
                if ($cum_pct <= 80.01) $item['abc_class'] = 'A (Fast)';
                elseif ($cum_pct <= 95.01) $item['abc_class'] = 'B (Slow)';
                else $item['abc_class'] = 'C (Non-Mv)';
            } else {
                if ($item['stok'] > 0) {
                    $item['abc_class'] = 'Dead Stock';
                    $dead_stock_val += $item['nilai_stok'];
                    $dead_stock_count++;
                } else {
                    $item['abc_class'] = 'C (Non-Mv)';
                }
            }

            if ($item['ads'] > 0) {
                $run_out = round($item['stok'] / $item['ads'], 1);
                $item['run_out_days'] = $run_out;
                if ($item['stok'] <= 0) {
                    $item['stock_status'] = 'Habis';
                    $critical_count++;
                } elseif ($run_out <= 7) {
                    $item['stock_status'] = 'Kritis (<=7 hr)';
                    $critical_count++;
                } elseif ($run_out <= 14) {
                    $item['stock_status'] = 'Perlu Reorder';
                } elseif ($run_out <= 45) {
                    $item['stock_status'] = 'Aman';
                } else {
                    $item['stock_status'] = 'Overstock';
                }
            } else {
                $item['run_out_days'] = ($item['stok'] > 0) ? '-' : '0';
                $item['stock_status'] = ($item['stok'] > 0) ? 'Dead Stock' : 'Kosong';
            }

            $target_stock = (int)ceil($item['ads'] * $buffer_days);
            $suggested = max(0, $target_stock - $item['stok']);
            $item['target_stock'] = $target_stock;
            $item['suggested_reorder_qty'] = $suggested;
            $item['estimasi_modal_reorder'] = $suggested * $item['harga_beli'];
            if ($suggested > 0) {
                $reorder_modal += $item['estimasi_modal_reorder'];
            }
        }
        unset($item);

        // Filter tab
        if ($tab_filter === 'critical') {
            $processed = array_filter($processed, fn($x) => str_starts_with($x['stock_status'], 'Habis') || str_starts_with($x['stock_status'], 'Kritis'));
        } elseif ($tab_filter === 'reorder') {
            $processed = array_filter($processed, fn($x) => $x['suggested_reorder_qty'] > 0);
        } elseif ($tab_filter === 'fast_moving') {
            $processed = array_filter($processed, fn($x) => str_starts_with($x['abc_class'], 'A'));
        } elseif ($tab_filter === 'slow_moving') {
            $processed = array_filter($processed, fn($x) => str_starts_with($x['abc_class'], 'B'));
        } elseif ($tab_filter === 'dead_stock') {
            $processed = array_filter($processed, fn($x) => $x['abc_class'] === 'Dead Stock');
        }

        return [
            'items' => array_values($processed),
            'summary' => [
                'total_inventory_val' => $total_inventory_val,
                'dead_stock_val' => $dead_stock_val,
                'dead_stock_count' => $dead_stock_count,
                'critical_count' => $critical_count,
                'reorder_modal' => $reorder_modal,
                'grand_store_sales' => $grand_store_sales
            ]
        ];
    }

    private function render(array $data, int $buffer_days): void
    {
        $items = $data['items'];
        $summary = $data['summary'];

        // 1. Executive Summary Cards (Landscape A4: 267mm width -> 4 cards @ 63mm each)
        $this->renderSummaryCards($summary, $buffer_days);

        // 2. Table Header
        // Widths: 8 + 24 + 60 + 32 + 16 + 16 + 16 + 18 + 18 + 27 + 16 + 16 = 267mm
        $w = [8, 24, 60, 32, 16, 16, 16, 18, 18, 27, 16, 16];

        $this->pdf->SetFont('helvetica', 'B', 8);
        $this->pdf->SetFillColor(240, 243, 246);
        $this->pdf->SetTextColor(30, 41, 59);

        $this->pdf->Cell($w[0], 7, 'No', 1, 0, 'C', true);
        $this->pdf->Cell($w[1], 7, 'SKU', 1, 0, 'C', true);
        $this->pdf->Cell($w[2], 7, 'Nama Produk', 1, 0, 'L', true);
        $this->pdf->Cell($w[3], 7, 'Kategori', 1, 0, 'L', true);
        $this->pdf->Cell($w[4], 7, 'Stok', 1, 0, 'R', true);
        $this->pdf->Cell($w[5], 7, 'Terjual', 1, 0, 'R', true);
        $this->pdf->Cell($w[6], 7, 'Laju/Hr', 1, 0, 'R', true);
        $this->pdf->Cell($w[7], 7, 'Sisa Hr', 1, 0, 'C', true);
        $this->pdf->Cell($w[8], 7, 'Kelas', 1, 0, 'C', true);
        $this->pdf->Cell($w[9], 7, 'Status Stok', 1, 0, 'C', true);
        $this->pdf->Cell($w[10], 7, 'Saran', 1, 0, 'R', true);
        $this->pdf->Cell($w[11], 7, 'Modal (Rp)', 1, 1, 'R', true);

        // 3. Table Rows
        $this->pdf->SetFont('helvetica', '', 7.5);
        $fill = false;
        $no = 1;

        $tot_stok = 0;
        $tot_sold = 0;
        $tot_reorder_qty = 0;
        $tot_reorder_modal = 0;

        foreach ($items as $row) {
            // Check page break
            if ($this->pdf->GetY() > 180) {
                $this->pdf->AddPage('L', 'A4');
                // Re-draw header
                $this->pdf->SetFont('helvetica', 'B', 8);
                $this->pdf->SetFillColor(240, 243, 246);
                $this->pdf->SetTextColor(30, 41, 59);

                $this->pdf->Cell($w[0], 7, 'No', 1, 0, 'C', true);
                $this->pdf->Cell($w[1], 7, 'SKU', 1, 0, 'C', true);
                $this->pdf->Cell($w[2], 7, 'Nama Produk', 1, 0, 'L', true);
                $this->pdf->Cell($w[3], 7, 'Kategori', 1, 0, 'L', true);
                $this->pdf->Cell($w[4], 7, 'Stok', 1, 0, 'R', true);
                $this->pdf->Cell($w[5], 7, 'Terjual', 1, 0, 'R', true);
                $this->pdf->Cell($w[6], 7, 'Laju/Hr', 1, 0, 'R', true);
                $this->pdf->Cell($w[7], 7, 'Sisa Hr', 1, 0, 'C', true);
                $this->pdf->Cell($w[8], 7, 'Kelas', 1, 0, 'C', true);
                $this->pdf->Cell($w[9], 7, 'Status Stok', 1, 0, 'C', true);
                $this->pdf->Cell($w[10], 7, 'Saran', 1, 0, 'R', true);
                $this->pdf->Cell($w[11], 7, 'Modal (Rp)', 1, 1, 'R', true);

                $this->pdf->SetFont('helvetica', '', 7.5);
                $fill = false;
            }

            $tot_stok += $row['stok'];
            $tot_sold += $row['qty_sold'];
            $tot_reorder_qty += $row['suggested_reorder_qty'];
            $tot_reorder_modal += $row['estimasi_modal_reorder'];

            $this->pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
            $this->pdf->SetTextColor(30, 41, 59);

            $this->pdf->Cell($w[0], 6, $no++, 'LRB', 0, 'C', $fill);
            $this->pdf->Cell($w[1], 6, $row['sku'], 'LRB', 0, 'C', $fill);
            $this->pdf->Cell($w[2], 6, mb_strimwidth($row['nama_barang'], 0, 36, '...'), 'LRB', 0, 'L', $fill);
            $this->pdf->Cell($w[3], 6, mb_strimwidth($row['nama_kategori'], 0, 18, '...'), 'LRB', 0, 'L', $fill);
            $this->pdf->Cell($w[4], 6, number_format($row['stok']), 'LRB', 0, 'R', $fill);
            $this->pdf->Cell($w[5], 6, number_format($row['qty_sold']), 'LRB', 0, 'R', $fill);
            $this->pdf->Cell($w[6], 6, number_format($row['ads'], 2), 'LRB', 0, 'R', $fill);
            
            // Run-out days
            $run_out_txt = is_numeric($row['run_out_days']) ? number_format($row['run_out_days'], 1) . ' hr' : $row['run_out_days'];
            $this->pdf->Cell($w[7], 6, $run_out_txt, 'LRB', 0, 'C', $fill);
            $this->pdf->Cell($w[8], 6, $row['abc_class'], 'LRB', 0, 'C', $fill);
            $this->pdf->Cell($w[9], 6, $row['stock_status'], 'LRB', 0, 'C', $fill);

            // Saran reorder
            if ($row['suggested_reorder_qty'] > 0) {
                $this->pdf->SetTextColor(194, 65, 12); // Amber bold
                $this->pdf->SetFont('helvetica', 'B', 7.5);
                $this->pdf->Cell($w[10], 6, number_format($row['suggested_reorder_qty']), 'LRB', 0, 'R', $fill);
                $this->pdf->Cell($w[11], 6, format_currency_pdf($row['estimasi_modal_reorder']), 'LRB', 1, 'R', $fill);
                $this->pdf->SetFont('helvetica', '', 7.5);
                $this->pdf->SetTextColor(30, 41, 59);
            } else {
                $this->pdf->Cell($w[10], 6, '-', 'LRB', 0, 'R', $fill);
                $this->pdf->Cell($w[11], 6, '-', 'LRB', 1, 'R', $fill);
            }

            $fill = !$fill;
        }

        // Total Row
        $this->pdf->SetFont('helvetica', 'B', 8);
        $this->pdf->SetFillColor(230, 235, 242);
        $this->pdf->Cell($w[0] + $w[1] + $w[2] + $w[3], 7, 'TOTAL / REKAPITULASI (' . count($items) . ' PRODUK)', 1, 0, 'C', true);
        $this->pdf->Cell($w[4], 7, number_format($tot_stok), 1, 0, 'R', true);
        $this->pdf->Cell($w[5], 7, number_format($tot_sold), 1, 0, 'R', true);
        $this->pdf->Cell($w[6] + $w[7] + $w[8] + $w[9], 7, '', 1, 0, 'C', true);
        $this->pdf->Cell($w[10], 7, number_format($tot_reorder_qty), 1, 0, 'R', true);
        $this->pdf->Cell($w[11], 7, format_currency_pdf($tot_reorder_modal), 1, 1, 'R', true);

        $this->pdf->Ln(6);
    }

    private function renderSummaryCards(array $s, int $buffer_days): void
    {
        $card_w = 64.5;
        $gap = 3;
        $card_h = 16;
        $start_x = 15;
        $start_y = $this->pdf->GetY();

        $cards = [
            [
                'title' => 'NILAI MODAL STOK FISIK',
                'val' => 'Rp ' . format_currency_pdf($s['total_inventory_val']),
                'sub' => 'Total modal barang toko'
            ],
            [
                'title' => 'MODAL MATI (DEAD STOCK)',
                'val' => 'Rp ' . format_currency_pdf($s['dead_stock_val']),
                'sub' => $s['dead_stock_count'] . ' SKU mengendap tanpa penjualan'
            ],
            [
                'title' => 'STOK KRITIS & HABIS (<= 7 HR)',
                'val' => $s['critical_count'] . ' SKU Produk',
                'sub' => 'Potensi kehilangan omset (lost sales)'
            ],
            [
                'title' => "ESTIMASI BELANJA REORDER ({$buffer_days} HR)",
                'val' => 'Rp ' . format_currency_pdf($s['reorder_modal']),
                'sub' => 'Kebutuhan modal restock optimal'
            ]
        ];

        for ($i = 0; $i < 4; $i++) {
            $x = $start_x + ($i * ($card_w + $gap));
            $this->pdf->SetXY($x, $start_y);

            // Card background & border
            $this->pdf->SetFillColor(248, 250, 252);
            $this->pdf->SetDrawColor(226, 232, 240);
            $this->pdf->Rect($x, $start_y, $card_w, $card_h, 'DF');

            // Title
            $this->pdf->SetXY($x + 2, $start_y + 2);
            $this->pdf->SetFont('helvetica', 'B', 6.5);
            $this->pdf->SetTextColor(100, 116, 139);
            $this->pdf->Cell($card_w - 4, 3.5, $cards[$i]['title'], 0, 1, 'L');

            // Value
            $this->pdf->SetXY($x + 2, $start_y + 6);
            $this->pdf->SetFont('helvetica', 'B', 9.5);
            $this->pdf->SetTextColor(15, 23, 42);
            $this->pdf->Cell($card_w - 4, 5, $cards[$i]['val'], 0, 1, 'L');

            // Subtitle
            $this->pdf->SetXY($x + 2, $start_y + 11.5);
            $this->pdf->SetFont('helvetica', '', 6);
            $this->pdf->SetTextColor(100, 116, 139);
            $this->pdf->Cell($card_w - 4, 3, $cards[$i]['sub'], 0, 1, 'L');
        }

        $this->pdf->SetY($start_y + $card_h + 5);
    }
}
