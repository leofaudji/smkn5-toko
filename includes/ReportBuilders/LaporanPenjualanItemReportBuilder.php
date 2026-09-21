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

class LaporanPenjualanItemReportBuilder implements ReportBuilderInterface
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
        $start_date = $this->params['start_date'] ?? date('Y-m-01');
        $end_date = $this->params['end_date'] ?? date('Y-m-d');
        $item_type = $this->params['item_type'] ?? 'all';
        $movement_filter = $this->params['movement_filter'] ?? 'with_activity';
        $search = trim($this->params['search'] ?? '');
        $sort_by = $this->params['sort_by'] ?? 'total_margin';
        $sort_order = strtolower($this->params['sort_order'] ?? 'desc');
        $user_id = (int)($this->params['user_id'] ?? 1);

        $this->pdf->SetTitle('Laporan Penjualan & Margin Stok per Item');
        $this->pdf->report_title = 'LAPORAN PENJUALAN & MARGIN STOK PER ITEM';
        $this->pdf->report_period = 'Periode: ' . date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date));
        $this->pdf->SetMargins(15, 12, 15);
        $this->pdf->SetAutoPageBreak(true, 15);
        $this->pdf->AddPage('L', 'A4'); // Format Landscape A4

        $data = $this->fetchData($user_id, $start_date, $end_date, $item_type, $movement_filter, $search, $sort_by, $sort_order);
        $this->render($data, $start_date, $end_date);

        $this->pdf->signature_date = $end_date;
        $this->pdf->RenderSignatureBlock();
    }

    private function fetchData(int $user_id, string $start_date, string $end_date, string $item_type, string $movement_filter, string $search, string $sort_by, string $sort_order): array
    {
        // 1. Sales per item
        $sales_sql = "
            SELECT
                pd.item_id,
                pd.item_type,
                MAX(pd.deskripsi_item) as deskripsi_item,
                SUM(pd.quantity) as qty_terjual,
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
        $stmt_sales = $this->conn->prepare($sales_sql);
        $stmt_sales->bind_param('iss', $user_id, $start_date, $end_date);
        $stmt_sales->execute();
        $sales_rows = stmt_fetch_all($stmt_sales);
        $stmt_sales->close();

        $sales_by_key = [];
        foreach ($sales_rows as $sr) {
            $sales_by_key[$sr['item_type'] . '_' . $sr['item_id']] = $sr;
        }

        $items_map = [];

        // 2. Normal Items
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
            $stmt_ks = $this->conn->prepare($ks_sql);
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

                $items_map[$key] = [
                    'id' => (int)$item['id'],
                    'item_type' => 'normal',
                    'type_label' => 'Toko',
                    'sku' => $item['sku'] ?? '',
                    'nama_barang' => $item['nama_barang'],
                    'stok_awal' => $stok_awal,
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                    'stok_akhir' => $stok_akhir,
                    'qty_terjual' => $qty_terjual,
                    'total_penjualan' => $total_penjualan,
                    'total_hpp' => $total_hpp,
                    'total_margin' => $total_margin,
                    'margin_pct' => $margin_pct
                ];
            }
        }

        // 3. Consignment Items
        if ($item_type === 'all' || $item_type === 'consignment') {
            $cons_payable_acc = null;
            $res_cfg = $this->conn->query("SELECT setting_value FROM settings WHERE setting_key = 'consignment_payable_account' LIMIT 1");
            if ($res_cfg && $r_cfg = $res_cfg->fetch_assoc()) {
                $cons_payable_acc = $r_cfg['setting_value'];
            }

            $ci_sql = "
                SELECT 
                    ci.id,
                    ci.nama_barang,
                    ci.sku,
                    (
                        ci.stok_awal 
                        + COALESCE((SELECT SUM(qty) FROM consignment_restocks WHERE consignment_item_id = ci.id AND tanggal < ?), 0)
                        - COALESCE((SELECT SUM(IF(debit > 0, -qty, qty)) FROM general_ledger WHERE consignment_item_id = ci.id AND ref_type IN ('jurnal', 'penjualan') AND account_id = ? AND DATE(tanggal) < ?), 0)
                    ) as stok_awal,
                    COALESCE((SELECT SUM(qty) FROM consignment_restocks WHERE consignment_item_id = ci.id AND tanggal BETWEEN ? AND ?), 0) as masuk,
                    COALESCE((SELECT SUM(IF(debit > 0, -qty, qty)) FROM general_ledger WHERE consignment_item_id = ci.id AND ref_type IN ('jurnal', 'penjualan') AND account_id = ? AND DATE(tanggal) BETWEEN ? AND ?), 0) as keluar
                FROM consignment_items ci
                WHERE ci.user_id = ?
            ";
            $stmt_ci = $this->conn->prepare($ci_sql);
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

                $items_map[$key] = [
                    'id' => (int)$item['id'],
                    'item_type' => 'consignment',
                    'type_label' => 'Konsinyasi',
                    'sku' => $item['sku'] ?? '',
                    'nama_barang' => $item['nama_barang'],
                    'stok_awal' => $stok_awal,
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                    'stok_akhir' => $stok_akhir,
                    'qty_terjual' => $qty_terjual,
                    'total_penjualan' => $total_penjualan,
                    'total_hpp' => $total_hpp,
                    'total_margin' => $total_margin,
                    'margin_pct' => $margin_pct
                ];
            }
        }

        // Fallback for items with sales that were deleted from master
        foreach ($sales_by_key as $key => $sale) {
            if (!isset($items_map[$key])) {
                $total_penjualan = (float)$sale['total_neto'];
                $total_hpp = (float)$sale['total_hpp'];
                $total_margin = (float)$sale['total_margin'];
                $qty_terjual = (int)$sale['qty_terjual'];
                $margin_pct = ($total_penjualan > 0) ? ($total_margin / $total_penjualan * 100) : 0.0;

                $items_map[$key] = [
                    'id' => (int)$sale['item_id'],
                    'item_type' => $sale['item_type'],
                    'type_label' => ($sale['item_type'] === 'consignment' ? 'Konsinyasi' : 'Toko'),
                    'sku' => '-',
                    'nama_barang' => !empty($sale['deskripsi_item']) ? $sale['deskripsi_item'] : ('Item #' . $sale['item_id'] . ' (Non-Master)'),
                    'stok_awal' => 0,
                    'masuk' => 0,
                    'keluar' => $qty_terjual,
                    'stok_akhir' => 0,
                    'qty_terjual' => $qty_terjual,
                    'total_penjualan' => $total_penjualan,
                    'total_hpp' => $total_hpp,
                    'total_margin' => $total_margin,
                    'margin_pct' => $margin_pct
                ];
            }
        }

        // 4. Filtering
        $filtered = [];
        foreach ($items_map as $item) {
            if (!empty($search)) {
                $s = mb_strtolower($search);
                if (strpos(mb_strtolower($item['nama_barang']), $s) === false && strpos(mb_strtolower($item['sku']), $s) === false) {
                    continue;
                }
            }

            if ($movement_filter === 'with_sales') {
                if ($item['qty_terjual'] <= 0) continue;
            } elseif ($movement_filter === 'with_activity') {
                if ($item['qty_terjual'] <= 0 && $item['masuk'] <= 0 && $item['keluar'] <= 0 && $item['stok_awal'] == 0 && $item['stok_akhir'] == 0) {
                    continue;
                }
            }

            $filtered[] = $item;
        }

        // 5. Sorting
        usort($filtered, function ($a, $b) use ($sort_by, $sort_order) {
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

        return $filtered;
    }

    private function renderTableHeader(array $w): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 7.5);
        $this->pdf->SetFillColor(230, 236, 248);
        $this->pdf->SetDrawColor(180, 195, 220);
        $this->pdf->Cell($w[0], 7.5, 'No', 1, 0, 'C', true);
        $this->pdf->Cell($w[1], 7.5, 'SKU', 1, 0, 'C', true);
        $this->pdf->Cell($w[2], 7.5, 'Nama Barang', 1, 0, 'L', true);
        $this->pdf->Cell($w[3], 7.5, 'Tipe', 1, 0, 'C', true);
        $this->pdf->Cell($w[4], 7.5, 'Awal', 1, 0, 'R', true);
        $this->pdf->Cell($w[5], 7.5, 'Masuk', 1, 0, 'R', true);
        $this->pdf->Cell($w[6], 7.5, 'Keluar', 1, 0, 'R', true);
        $this->pdf->Cell($w[7], 7.5, 'Sisa', 1, 0, 'R', true);
        $this->pdf->Cell($w[8], 7.5, 'Terjual', 1, 0, 'R', true);
        $this->pdf->Cell($w[9], 7.5, 'Penjualan (Net)', 1, 0, 'R', true);
        $this->pdf->Cell($w[10], 7.5, 'HPP (Modal)', 1, 0, 'R', true);
        $this->pdf->Cell($w[11], 7.5, 'Margin (Laba)', 1, 0, 'R', true);
        $this->pdf->Cell($w[12], 7.5, 'Margin %', 1, 1, 'R', true);
        $this->pdf->SetDrawColor(210, 215, 225);
    }

    private function render(array $data, string $start_date, string $end_date): void
    {
        // Hitung akumulasi total terlebih dahulu untuk KPI cards di atas halaman
        $tot_masuk = 0;
        $tot_keluar = 0;
        $tot_terjual = 0;
        $tot_penjualan = 0;
        $tot_hpp = 0;
        $tot_margin = 0;

        foreach ($data as $row) {
            $tot_masuk += (int)$row['masuk'];
            $tot_keluar += (int)$row['keluar'];
            $tot_terjual += (int)$row['qty_terjual'];
            $tot_penjualan += (float)$row['total_penjualan'];
            $tot_hpp += (float)$row['total_hpp'];
            $tot_margin += (float)$row['total_margin'];
        }
        $tot_pct = ($tot_penjualan > 0) ? ($tot_margin / $tot_penjualan * 100) : 0;

        // Render 4 Kartu KPI Ringkasan Eksekutif di Halaman 1
        $curY = $this->pdf->GetY();
        $this->pdf->SetFillColor(245, 248, 253);
        $this->pdf->SetDrawColor(210, 222, 240);
        $this->pdf->Rect(15, $curY, 64, 13, 'DF');
        $this->pdf->Rect(82, $curY, 64, 13, 'DF');
        $this->pdf->Rect(149, $curY, 64, 13, 'DF');
        $this->pdf->Rect(216, $curY, 66, 13, 'DF');

        $this->pdf->SetXY(15, $curY + 1.5);
        $this->pdf->SetFont('Helvetica', '', 7);
        $this->pdf->SetTextColor(80, 80, 80);
        $this->pdf->Cell(64, 3.5, 'PENJUALAN BERSIH (NET)', 0, 0, 'C');
        $this->pdf->SetXY(82, $curY + 1.5);
        $this->pdf->Cell(64, 3.5, 'TOTAL HPP (MODAL)', 0, 0, 'C');
        $this->pdf->SetXY(149, $curY + 1.5);
        $this->pdf->Cell(64, 3.5, 'TOTAL MARGIN KEUNTUNGAN', 0, 0, 'C');
        $this->pdf->SetXY(216, $curY + 1.5);
        $this->pdf->Cell(66, 3.5, 'TOTAL BARANG TERJUAL', 0, 1, 'C');

        $this->pdf->SetXY(15, $curY + 5.5);
        $this->pdf->SetFont('Helvetica', 'B', 8.5);
        $this->pdf->SetTextColor(25, 60, 150);
        $this->pdf->Cell(64, 5.5, format_currency_pdf($tot_penjualan), 0, 0, 'C');

        $this->pdf->SetXY(82, $curY + 5.5);
        $this->pdf->SetTextColor(90, 90, 90);
        $this->pdf->Cell(64, 5.5, format_currency_pdf($tot_hpp), 0, 0, 'C');

        $this->pdf->SetXY(149, $curY + 5.5);
        $profitColor = $tot_margin < 0 ? [200, 30, 30] : [16, 125, 50];
        $this->pdf->SetTextColor($profitColor[0], $profitColor[1], $profitColor[2]);
        $this->pdf->Cell(64, 5.5, format_currency_pdf($tot_margin) . ' (' . number_format($tot_pct, 1) . '%)', 0, 0, 'C');

        $this->pdf->SetXY(216, $curY + 5.5);
        $this->pdf->SetTextColor(30, 30, 30);
        $this->pdf->Cell(66, 5.5, number_format($tot_terjual) . ' pcs', 0, 1, 'C');

        $this->pdf->SetY($curY + 17);
        $this->pdf->SetTextColor(0, 0, 0);

        // Lebar Total Kolom = 267mm
        // Margin kiri 15mm + 267mm tabel + Margin kanan 15mm = 297mm pas di tengah Landscape A4
        // No(8), SKU(22), Nama(60), Tipe(15), Awl(13), Msk(13), Klr(13), Sisa(13), Trjl(13), Penjualan(28), HPP(27), Margin(27), %(15)
        $w = [8, 22, 60, 15, 13, 13, 13, 13, 13, 28, 27, 27, 15];

        $this->renderTableHeader($w);
        $this->pdf->SetFont('Helvetica', '', 7);

        foreach ($data as $idx => $row) {
            // Cek batas bawah halaman (Landscape A4 tinggi 210mm, batas aman 180mm)
            if ($this->pdf->GetY() > 180) {
                $this->pdf->AddPage('L', 'A4');
                $this->renderTableHeader($w);
                $this->pdf->SetFont('Helvetica', '', 7);
            }

            $fill = ($idx % 2 === 1);
            $this->pdf->SetFillColor(248, 250, 253);

            $this->pdf->Cell($w[0], 5.8, $idx + 1, 1, 0, 'C', $fill);
            $this->pdf->Cell($w[1], 5.8, substr($row['sku'] ?? '-', 0, 14), 1, 0, 'L', $fill);
            $this->pdf->Cell($w[2], 5.8, ' ' . substr($row['nama_barang'], 0, 38), 1, 0, 'L', $fill);
            $this->pdf->Cell($w[3], 5.8, $row['type_label'], 1, 0, 'C', $fill);
            $this->pdf->Cell($w[4], 5.8, number_format($row['stok_awal']), 1, 0, 'R', $fill);
            $this->pdf->Cell($w[5], 5.8, number_format($row['masuk']), 1, 0, 'R', $fill);
            $this->pdf->Cell($w[6], 5.8, number_format($row['keluar']), 1, 0, 'R', $fill);
            $this->pdf->Cell($w[7], 5.8, number_format($row['stok_akhir']), 1, 0, 'R', $fill);
            $this->pdf->Cell($w[8], 5.8, number_format($row['qty_terjual']), 1, 0, 'R', $fill);
            $this->pdf->Cell($w[9], 5.8, format_currency_pdf($row['total_penjualan']), 1, 0, 'R', $fill);
            $this->pdf->Cell($w[10], 5.8, format_currency_pdf($row['total_hpp']), 1, 0, 'R', $fill);

            $profitColor = (float)$row['total_margin'] < 0 ? [200, 30, 30] : [0, 0, 0];
            $this->pdf->SetTextColor($profitColor[0], $profitColor[1], $profitColor[2]);
            $this->pdf->Cell($w[11], 5.8, format_currency_pdf($row['total_margin']), 1, 0, 'R', $fill);
            $this->pdf->SetTextColor(0, 0, 0);

            $this->pdf->Cell($w[12], 5.8, number_format($row['margin_pct'], 1) . '%', 1, 1, 'R', $fill);
        }

        // Baris Total Akumulasi Periode
        if ($this->pdf->GetY() > 175) {
            $this->pdf->AddPage('L', 'A4');
            $this->renderTableHeader($w);
        }

        $this->pdf->SetFont('Helvetica', 'B', 7.5);
        $this->pdf->SetFillColor(235, 240, 250);
        $this->pdf->SetDrawColor(180, 195, 220);
        $this->pdf->Cell($w[0] + $w[1] + $w[2] + $w[3] + $w[4], 7, 'TOTAL PERIODE:', 1, 0, 'R', true);
        $this->pdf->Cell($w[5], 7, number_format($tot_masuk), 1, 0, 'R', true);
        $this->pdf->Cell($w[6], 7, number_format($tot_keluar), 1, 0, 'R', true);
        $this->pdf->Cell($w[7], 7, '-', 1, 0, 'C', true);
        $this->pdf->Cell($w[8], 7, number_format($tot_terjual), 1, 0, 'R', true);
        $this->pdf->Cell($w[9], 7, format_currency_pdf($tot_penjualan), 1, 0, 'R', true);
        $this->pdf->Cell($w[10], 7, format_currency_pdf($tot_hpp), 1, 0, 'R', true);
        $this->pdf->Cell($w[11], 7, format_currency_pdf($tot_margin), 1, 0, 'R', true);
        $this->pdf->Cell($w[12], 7, number_format($tot_pct, 1) . '%', 1, 1, 'R', true);
        $this->pdf->SetDrawColor(0, 0, 0);
    }
}
