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

class LaporanMarginKategoriReportBuilder implements ReportBuilderInterface
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
        $sort_by = $this->params['sort_by'] ?? 'total_neto';
        $sort_order = strtolower($this->params['sort_order'] ?? 'desc');
        $user_id = (int)($this->params['user_id'] ?? 1);

        $this->pdf->SetTitle('Laporan Kontribusi & Margin per Kategori');
        $this->pdf->report_title = 'LAPORAN KONTRIBUSI & MARGIN PER KATEGORI BARANG';
        $this->pdf->report_period = 'Periode: ' . date('d M Y', strtotime($start_date)) . ' s/d ' . date('d M Y', strtotime($end_date));
        $this->pdf->SetMargins(15, 12, 15);
        $this->pdf->SetAutoPageBreak(true, 15);
        $this->pdf->AddPage('L', 'A4'); // Format Landscape A4

        $data = $this->fetchData($user_id, $start_date, $end_date, $sort_by, $sort_order);
        $this->render($data, $start_date, $end_date);

        $this->pdf->signature_date = $end_date;
        $this->pdf->RenderSignatureBlock();
    }

    private function fetchData(int $user_id, string $start_date, string $end_date, string $sort_by, string $sort_order): array
    {
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

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iss', $user_id, $start_date, $end_date);
        $stmt->execute();
        $rows = stmt_fetch_all($stmt);
        $stmt->close();

        // Hitung Grand Total Toko
        $grand_neto = 0.0;
        $grand_margin = 0.0;
        foreach ($rows as $r) {
            $grand_neto += (float)$r['total_neto'];
            $grand_margin += (float)$r['total_margin'];
        }

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

        return $data;
    }

    private function renderTableHeader(array $w): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 7);
        $this->pdf->SetFillColor(235, 240, 248);
        $this->pdf->SetTextColor(30, 41, 59);
        $this->pdf->SetDrawColor(200, 210, 225);

        // Header Tabel (Total Width = 267mm)
        $this->pdf->Cell($w[0], 7.5, '#', 1, 0, 'C', true);
        $this->pdf->Cell($w[1], 7.5, 'Kategori Barang', 1, 0, 'L', true);
        $this->pdf->Cell($w[2], 7.5, 'SKU', 1, 0, 'C', true);
        $this->pdf->Cell($w[3], 7.5, 'Qty', 1, 0, 'R', true);
        $this->pdf->Cell($w[4], 7.5, 'Omset Bruto', 1, 0, 'R', true);
        $this->pdf->Cell($w[5], 7.5, 'Diskon Nota', 1, 0, 'R', true);
        $this->pdf->Cell($w[6], 7.5, 'Penjualan (Net)', 1, 0, 'R', true);
        $this->pdf->Cell($w[7], 7.5, '% Omset', 1, 0, 'R', true);
        $this->pdf->Cell($w[8], 7.5, 'HPP (Modal)', 1, 0, 'R', true);
        $this->pdf->Cell($w[9], 7.5, 'Margin (Laba)', 1, 0, 'R', true);
        $this->pdf->Cell($w[10], 7.5, '% Laba', 1, 0, 'R', true);
        $this->pdf->Cell($w[11], 7.5, 'Margin %', 1, 1, 'C', true);

        $this->pdf->SetDrawColor(220, 225, 235);
    }

    private function render(array $data, string $start_date, string $end_date): void
    {
        // Hitung akumulasi grand total
        $tot_sku = 0;
        $tot_qty = 0;
        $tot_bruto = 0.0;
        $tot_diskon = 0.0;
        $tot_neto = 0.0;
        $tot_hpp = 0.0;
        $tot_margin = 0.0;

        foreach ($data as $row) {
            $tot_sku += $row['total_sku'];
            $tot_qty += $row['total_qty'];
            $tot_bruto += $row['total_bruto'];
            $tot_diskon += $row['total_diskon'];
            $tot_neto += $row['total_neto'];
            $tot_hpp += $row['total_hpp'];
            $tot_margin += $row['total_margin'];
        }
        $tot_pct = ($tot_neto > 0) ? ($tot_margin / $tot_neto * 100) : 0;

        // Render 4 Kartu KPI Ringkasan Eksekutif di Atas
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
        $this->pdf->Cell(64, 3.5, 'TOTAL HPP (MODAL TOKO)', 0, 0, 'C');
        $this->pdf->SetXY(149, $curY + 1.5);
        $this->pdf->Cell(64, 3.5, 'TOTAL MARGIN KEUNTUNGAN', 0, 0, 'C');
        $this->pdf->SetXY(216, $curY + 1.5);
        $this->pdf->Cell(66, 3.5, 'JUMLAH KATEGORI / QTY', 0, 1, 'C');

        $this->pdf->SetXY(15, $curY + 5.5);
        $this->pdf->SetFont('Helvetica', 'B', 8.5);
        $this->pdf->SetTextColor(25, 60, 150);
        $this->pdf->Cell(64, 5.5, 'Rp ' . format_currency_pdf($tot_neto), 0, 0, 'C');

        $this->pdf->SetXY(82, $curY + 5.5);
        $this->pdf->SetTextColor(90, 90, 90);
        $this->pdf->Cell(64, 5.5, 'Rp ' . format_currency_pdf($tot_hpp), 0, 0, 'C');

        $this->pdf->SetXY(149, $curY + 5.5);
        $profitColor = $tot_margin < 0 ? [200, 30, 30] : [16, 125, 50];
        $this->pdf->SetTextColor($profitColor[0], $profitColor[1], $profitColor[2]);
        $this->pdf->Cell(64, 5.5, 'Rp ' . format_currency_pdf($tot_margin) . ' (' . number_format($tot_pct, 1) . '%)', 0, 0, 'C');

        $this->pdf->SetXY(216, $curY + 5.5);
        $this->pdf->SetTextColor(30, 30, 30);
        $this->pdf->Cell(66, 5.5, count($data) . ' Kategori / ' . number_format($tot_qty) . ' pcs', 0, 1, 'C');

        $this->pdf->SetY($curY + 17);
        $this->pdf->SetTextColor(0, 0, 0);

        // Lebar Kolom Total = 267mm
        // 15mm kiri + 267mm tabel + 15mm kanan = 297mm pas di tengah Landscape A4
        $w = [8, 48, 15, 16, 26, 20, 27, 17, 26, 26, 17, 21];

        $this->renderTableHeader($w);
        $this->pdf->SetFont('Helvetica', '', 7);

        foreach ($data as $idx => $row) {
            // Cek batas bawah halaman (Landscape A4 batas aman 180mm)
            if ($this->pdf->GetY() > 180) {
                $this->pdf->AddPage('L', 'A4');
                $this->renderTableHeader($w);
                $this->pdf->SetFont('Helvetica', '', 7);
            }

            $fill = ($idx % 2 === 1);
            if ($fill) {
                $this->pdf->SetFillColor(250, 252, 255);
            } else {
                $this->pdf->SetFillColor(255, 255, 255);
            }

            $catName = (strlen($row['kategori_nama']) > 28) ? substr($row['kategori_nama'], 0, 26) . '..' : $row['kategori_nama'];

            $this->pdf->Cell($w[0], 5.8, $idx + 1, 1, 0, 'C', true);
            $this->pdf->Cell($w[1], 5.8, $catName, 1, 0, 'L', true);
            $this->pdf->Cell($w[2], 5.8, number_format($row['total_sku']), 1, 0, 'C', true);
            $this->pdf->Cell($w[3], 5.8, number_format($row['total_qty']), 1, 0, 'R', true);
            $this->pdf->Cell($w[4], 5.8, format_currency_pdf($row['total_bruto']), 1, 0, 'R', true);
            $this->pdf->Cell($w[5], 5.8, $row['total_diskon'] > 0 ? format_currency_pdf($row['total_diskon']) : '-', 1, 0, 'R', true);

            // Kolom Penjualan Net (Highlight Biru Lembut)
            $this->pdf->SetFont('Helvetica', 'B', 7);
            $this->pdf->Cell($w[6], 5.8, format_currency_pdf($row['total_neto']), 1, 0, 'R', true);
            $this->pdf->SetFont('Helvetica', '', 7);

            $this->pdf->Cell($w[7], 5.8, number_format($row['kontribusi_sales_pct'], 1) . '%', 1, 0, 'R', true);
            $this->pdf->Cell($w[8], 5.8, format_currency_pdf($row['total_hpp']), 1, 0, 'R', true);

            // Kolom Margin (Highlight Hijau Lembut)
            $this->pdf->SetFont('Helvetica', 'B', 7);
            $this->pdf->Cell($w[9], 5.8, format_currency_pdf($row['total_margin']), 1, 0, 'R', true);
            $this->pdf->SetFont('Helvetica', '', 7);

            $this->pdf->Cell($w[10], 5.8, number_format($row['kontribusi_margin_pct'], 1) . '%', 1, 0, 'R', true);
            $this->pdf->Cell($w[11], 5.8, number_format($row['margin_pct'], 1) . '%', 1, 1, 'C', true);
        }

        // Baris Grand Total Keseluruhan
        if ($this->pdf->GetY() > 180) {
            $this->pdf->AddPage('L', 'A4');
            $this->renderTableHeader($w);
        }

        $this->pdf->SetFont('Helvetica', 'B', 7.5);
        $this->pdf->SetFillColor(230, 238, 250);
        $this->pdf->SetTextColor(20, 35, 60);

        $this->pdf->Cell($w[0] + $w[1], 6.5, 'TOTAL KESELURUHAN', 1, 0, 'C', true);
        $this->pdf->Cell($w[2], 6.5, number_format($tot_sku), 1, 0, 'C', true);
        $this->pdf->Cell($w[3], 6.5, number_format($tot_qty), 1, 0, 'R', true);
        $this->pdf->Cell($w[4], 6.5, format_currency_pdf($tot_bruto), 1, 0, 'R', true);
        $this->pdf->Cell($w[5], 6.5, $tot_diskon > 0 ? format_currency_pdf($tot_diskon) : '-', 1, 0, 'R', true);
        $this->pdf->Cell($w[6], 6.5, format_currency_pdf($tot_neto), 1, 0, 'R', true);
        $this->pdf->Cell($w[7], 6.5, '100.0%', 1, 0, 'R', true);
        $this->pdf->Cell($w[8], 6.5, format_currency_pdf($tot_hpp), 1, 0, 'R', true);
        $this->pdf->Cell($w[9], 6.5, format_currency_pdf($tot_margin), 1, 0, 'R', true);
        $this->pdf->Cell($w[10], 6.5, '100.0%', 1, 0, 'R', true);
        $this->pdf->Cell($w[11], 6.5, number_format($tot_pct, 1) . '%', 1, 1, 'C', true);

        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(4);
    }
}
