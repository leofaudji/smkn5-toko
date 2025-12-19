<?php
require_once __DIR__ . '/ReportBuilderInterface.php';

class LaporanPenjualanReportBuilder implements ReportBuilderInterface
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
        $search = $this->params['search'] ?? '';
        $user_id = $this->params['user_id'];

        $this->pdf->SetTitle('Laporan Penjualan');
        $this->pdf->report_title = 'Laporan Rincian Penjualan';
        $this->pdf->report_period = 'Periode: ' . date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date));
        $this->pdf->AddPage('L'); // Landscape

        $data = $this->fetchData($user_id, $start_date, $end_date, $search);
        $this->render($data);

        $this->pdf->signature_date = $end_date;
        $this->pdf->RenderSignatureBlock();
    }

    private function fetchData(int $user_id, string $start_date, string $end_date, string $search): array
    {
        $where_clauses = ['p.user_id = ?', 'DATE(p.tanggal_penjualan) >= ?', 'DATE(p.tanggal_penjualan) <= ?'];
        $params = ['iss', $user_id, $start_date, $end_date];

        if (!empty($search)) {
            $where_clauses[] = '(p.customer_name LIKE ? OR u.username LIKE ?)';
            $params[0] .= 'ss';
            $searchTerm = '%' . $search . '%';
            array_push($params, $searchTerm, $searchTerm);
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        $query = "SELECT p.nomor_referensi, p.tanggal_penjualan, p.customer_name, p.total, p.status, u.username FROM penjualan p JOIN users u ON p.user_id = u.id $where_sql ORDER BY p.tanggal_penjualan ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function render(array $data): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(40, 8, 'No. Faktur', 1, 0, 'C', true);
        $this->pdf->Cell(40, 8, 'Tanggal', 1, 0, 'C', true);
        $this->pdf->Cell(60, 8, 'Customer', 1, 0, 'C', true);
        $this->pdf->Cell(40, 8, 'Kasir', 1, 0, 'C', true);
        $this->pdf->Cell(50, 8, 'Total', 1, 0, 'C', true);
        $this->pdf->Cell(30, 8, 'Status', 1, 1, 'C', true);

        $this->pdf->SetFont('Helvetica', '', 8);
        $grandTotal = 0;
        foreach ($data as $row) {
            $grandTotal += (float)$row['total'];
            $this->pdf->Cell(40, 7, $row['nomor_referensi'], 1);
            $this->pdf->Cell(40, 7, date('d-m-Y H:i', strtotime($row['tanggal_penjualan'])), 1);
            $this->pdf->Cell(60, 7, $row['customer_name'], 1);
            $this->pdf->Cell(40, 7, $row['username'], 1);
            $this->pdf->Cell(50, 7, format_currency_pdf($row['total']), 1, 0, 'R');
            $this->pdf->Cell(30, 7, ucfirst($row['status']), 1, 1, 'C');
        }
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->Cell(180, 8, 'GRAND TOTAL', 1, 0, 'R', true);
        $this->pdf->Cell(80, 8, format_currency_pdf($grandTotal), 1, 1, 'R', true);
    }
}