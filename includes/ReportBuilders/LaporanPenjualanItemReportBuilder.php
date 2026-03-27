<?php
require_once __DIR__ . '/ReportBuilderInterface.php';

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
        $sort_by = $this->params['sort_by'] ?? 'total_terjual';
        $user_id = $this->params['user_id'];

        $this->pdf->SetTitle('Laporan Penjualan per Item');
        $this->pdf->report_title = 'Laporan Penjualan per Item';
        $this->pdf->report_period = 'Periode: ' . date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date));
        $this->pdf->AddPage('P'); // Portrait

        $data = $this->fetchData($user_id, $start_date, $end_date, $sort_by);
        $this->render($data);

        $this->pdf->signature_date = $end_date;
        $this->pdf->RenderSignatureBlock();
    }

    private function fetchData(int $user_id, string $start_date, string $end_date, string $sort_by): array
    {
        $allowed_sort_columns = ['total_terjual', 'total_penjualan', 'total_profit'];
        if (!in_array($sort_by, $allowed_sort_columns)) {
            $sort_by = 'total_terjual';
        }

        $query = "
            SELECT 
                pd.item_type,
                COALESCE(i.id, ci.id) as id,
                COALESCE(i.sku, ci.sku) as sku, 
                COALESCE(i.nama_barang, ci.nama_barang) as nama_barang, 
                SUM(pd.quantity) as total_terjual, 
                SUM(pd.subtotal) as total_penjualan, 
                SUM(pd.subtotal - (pd.quantity * COALESCE(i.harga_beli, ci.harga_beli, 0))) as total_profit
            FROM penjualan_details pd
            JOIN penjualan p ON pd.penjualan_id = p.id
            LEFT JOIN items i ON pd.item_id = i.id AND pd.item_type = 'normal'
            LEFT JOIN consignment_items ci ON pd.item_id = ci.id AND pd.item_type = 'consignment'
            WHERE p.user_id = ? AND p.status = 'completed' AND DATE(p.tanggal_penjualan) BETWEEN ? AND ?
            GROUP BY pd.item_type, COALESCE(i.id, ci.id), COALESCE(i.sku, ci.sku), COALESCE(i.nama_barang, ci.nama_barang)
            ORDER BY $sort_by DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('iss', $user_id, $start_date, $end_date);
        $stmt->execute();
        return stmt_fetch_all($stmt);
    }

    private function render(array $data): void
    {
        // Lebar total portrait A4: ~190mm
        // Nama Barang(65), SKU(30), Qty(25), Total(35), Profit(35)
        $w = [65, 30, 25, 35, 35];

        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell($w[0], 8, 'Nama Barang', 1, 0, 'C', true);
        $this->pdf->Cell($w[1], 8, 'SKU', 1, 0, 'C', true);
        $this->pdf->Cell($w[2], 8, 'Jml. Terjual', 1, 0, 'C', true);
        $this->pdf->Cell($w[3], 8, 'Total Penjualan', 1, 0, 'C', true);
        $this->pdf->Cell($w[4], 8, 'Estimasi Profit', 1, 1, 'C', true);

        $this->pdf->SetFont('Helvetica', '', 8);
        foreach ($data as $row) {
            // Cek overflow halaman
            if ($this->pdf->GetY() > 250) {
                $this->pdf->AddPage('P');
                $this->pdf->SetFont('Helvetica', 'B', 8);
                $this->pdf->Cell($w[0], 8, 'Nama Barang', 1, 0, 'C', true);
                $this->pdf->Cell($w[1], 8, 'SKU', 1, 0, 'C', true);
                $this->pdf->Cell($w[2], 8, 'Jml. Terjual', 1, 0, 'C', true);
                $this->pdf->Cell($w[3], 8, 'Total Penjualan', 1, 0, 'C', true);
                $this->pdf->Cell($w[4], 8, 'Estimasi Profit', 1, 1, 'C', true);
                $this->pdf->SetFont('Helvetica', '', 8);
            }

            $currentY = $this->pdf->GetY();
            $this->pdf->Cell($w[0], 7, substr($row['nama_barang'], 0, 40), 1);
            $this->pdf->Cell($w[1], 7, $row['sku'] ?? '-', 1);
            $this->pdf->Cell($w[2], 7, number_format($row['total_terjual']), 1, 0, 'R');
            $this->pdf->Cell($w[3], 7, format_currency_pdf($row['total_penjualan']), 1, 0, 'R');

            $profitColor = (float) $row['total_profit'] < 0 ? [200, 0, 0] : [0, 0, 0];
            $this->pdf->SetTextColor($profitColor[0], $profitColor[1], $profitColor[2]);
            $this->pdf->Cell($w[4], 7, format_currency_pdf($row['total_profit']), 1, 1, 'R');
            $this->pdf->SetTextColor(0, 0, 0);
        }
    }
}

