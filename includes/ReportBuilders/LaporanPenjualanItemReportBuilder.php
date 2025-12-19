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
        $this->pdf->AddPage('L'); // Landscape

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
            SELECT i.sku, i.nama_barang, SUM(pd.quantity) as total_terjual, SUM(pd.subtotal) as total_penjualan, SUM(pd.subtotal - (pd.quantity * i.harga_beli)) as total_profit
            FROM penjualan_details pd
            JOIN penjualan p ON pd.penjualan_id = p.id
            JOIN items i ON pd.item_id = i.id
            WHERE p.user_id = ? AND p.status = 'completed' AND DATE(p.tanggal_penjualan) BETWEEN ? AND ?
            GROUP BY i.id, i.sku, i.nama_barang
            ORDER BY $sort_by DESC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('iss', $user_id, $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function render(array $data): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(80, 8, 'Nama Barang', 1, 0, 'C', true);
        $this->pdf->Cell(30, 8, 'SKU', 1, 0, 'C', true);
        $this->pdf->Cell(40, 8, 'Jumlah Terjual', 1, 0, 'C', true);
        $this->pdf->Cell(50, 8, 'Total Penjualan', 1, 0, 'C', true);
        $this->pdf->Cell(50, 8, 'Estimasi Profit', 1, 1, 'C', true);

        $this->pdf->SetFont('Helvetica', '', 8);
        foreach ($data as $row) {
            $this->pdf->Cell(80, 7, $row['nama_barang'], 1);
            $this->pdf->Cell(30, 7, $row['sku'] ?? '-', 1);
            $this->pdf->Cell(40, 7, number_format($row['total_terjual']), 1, 0, 'R');
            $this->pdf->Cell(50, 7, format_currency_pdf($row['total_penjualan']), 1, 0, 'R');
            $this->pdf->Cell(50, 7, format_currency_pdf($row['total_profit']), 1, 1, 'R');
        }
    }
}

