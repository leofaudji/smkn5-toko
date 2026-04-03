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
        $view_type = $this->params['view_type'] ?? 'summary'; // 'summary' or 'detail'

        $this->pdf->SetTitle('Laporan Penjualan');
        $this->pdf->report_title = ($view_type === 'detail' ? 'Laporan Rincian Penjualan' : 'Laporan Penjualan');
        $this->pdf->report_period = 'Periode: ' . date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date));
        $this->pdf->AddPage('P');

        $data = $this->fetchData($user_id, $start_date, $end_date, $search, $view_type);
        
        if ($view_type === 'detail') {
            $this->renderDetail($data);
        } else {
            $this->renderSummary($data);
        }

        $this->pdf->signature_date = $end_date;
        $this->pdf->RenderSignatureBlock();
    }

    private function fetchData(int $user_id, string $start_date, string $end_date, string $search, string $view_type): array
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
        
        if ($view_type === 'detail') {
            $query = "
                SELECT 
                    p.nomor_referensi, 
                    p.tanggal_penjualan, 
                    p.customer_name, 
                    p.status, 
                    p.payment_method,
                    u.username,
                    pd.deskripsi_item,
                    pd.quantity,
                    pd.price,
                    pd.subtotal as item_total
                FROM penjualan p 
                JOIN penjualan_details pd ON p.id = pd.penjualan_id
                LEFT JOIN users u ON p.created_by = u.id 
                $where_sql 
                ORDER BY p.tanggal_penjualan ASC, p.id ASC, pd.id ASC";
        } else {
            $query = "
                SELECT 
                    p.nomor_referensi, 
                    p.tanggal_penjualan, 
                    p.customer_name, 
                    p.total, 
                    p.payment_method,
                    p.status, 
                    u.username
                FROM penjualan p 
                LEFT JOIN users u ON p.created_by = u.id 
                $where_sql 
                ORDER BY p.tanggal_penjualan ASC, p.id ASC";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(...$params);
        $stmt->execute();
        return stmt_fetch_all($stmt);
    }

    private function getPaymentMethodName(string $method): string
    {
        $methods = [
            'cash' => 'Tunai',
            'transfer' => 'Transfer',
            'potong_saldo' => 'Sal. WB',
            'hutang' => 'Piutang'
        ];
        return $methods[$method] ?? $method;
    }

    private function renderSummary(array $data): void
    {
        // Faktur(25), Tgl(30), Cust(40), Bayar(25), Kasir(30), Total(40)
        $w = [25, 30, 40, 25, 30, 40];

        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell($w[0], 8, 'Faktur', 1, 0, 'C', true);
        $this->pdf->Cell($w[1], 8, 'Tanggal', 1, 0, 'C', true);
        $this->pdf->Cell($w[2], 8, 'Customer', 1, 0, 'C', true);
        $this->pdf->Cell($w[3], 8, 'Bayar', 1, 0, 'C', true);
        $this->pdf->Cell($w[4], 8, 'Kasir', 1, 0, 'C', true);
        $this->pdf->Cell($w[5], 8, 'Total', 1, 1, 'C', true);

        $this->pdf->SetFont('Helvetica', '', 8);
        foreach ($data as $row) {
            $statusText = ($row['status'] === 'void' ? ' (V)' : '');
            $this->pdf->Cell($w[0], 7, $row['nomor_referensi'] . $statusText, 1);
            $this->pdf->Cell($w[1], 7, date('d/m/y H:i', strtotime($row['tanggal_penjualan'])), 1, 0, 'C');
            $this->pdf->Cell($w[2], 7, substr($row['customer_name'] ?? 'Umum', 0, 25), 1);
            $this->pdf->Cell($w[3], 7, $this->getPaymentMethodName($row['payment_method']), 1, 0, 'C');
            $this->pdf->Cell($w[4], 7, substr($row['username'], 0, 15), 1);
            $this->pdf->Cell($w[5], 7, format_currency_pdf($row['total']), 1, 1, 'R');
            
            if ($this->pdf->GetY() > 260) $this->pdf->AddPage('P');
        }
        $this->renderFinalSummary();
    }

    private function renderDetail(array $data): void
    {
        // Tgl(22), Faktur(28), Barang(55), Qty(10), Harga(25), Total(25), Bayar(25)
        $w = [22, 28, 55, 10, 25, 25, 25];

        $this->pdf->SetFont('Helvetica', 'B', 7);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell($w[0], 8, 'Tanggal', 1, 0, 'C', true);
        $this->pdf->Cell($w[1], 8, 'Faktur', 1, 0, 'C', true);
        $this->pdf->Cell($w[2], 8, 'Barang', 1, 0, 'C', true);
        $this->pdf->Cell($w[3], 8, 'Qty', 1, 0, 'C', true);
        $this->pdf->Cell($w[4], 8, 'Harga', 1, 0, 'C', true);
        $this->pdf->Cell($w[5], 8, 'Total', 1, 0, 'C', true);
        $this->pdf->Cell($w[6], 8, 'Bayar', 1, 1, 'C', true);

        $this->pdf->SetFont('Helvetica', '', 7);
        foreach ($data as $row) {
            $statusText = ($row['status'] === 'void' ? '*' : '');
            $this->pdf->Cell($w[0], 7, date('d/m/y', strtotime($row['tanggal_penjualan'])), 1, 0, 'C');
            $this->pdf->Cell($w[1], 7, $row['nomor_referensi'] . $statusText, 1);
            $this->pdf->Cell($w[2], 7, ' ' . substr($row['deskripsi_item'], 0, 40), 1);
            $this->pdf->Cell($w[3], 7, $row['quantity'], 1, 0, 'C');
            $this->pdf->Cell($w[4], 7, format_currency_pdf($row['price']), 1, 0, 'R');
            $this->pdf->Cell($w[5], 7, format_currency_pdf($row['item_total']), 1, 0, 'R');
            $this->pdf->Cell($w[6], 7, $this->getPaymentMethodName($row['payment_method']), 1, 1, 'C');

            if ($this->pdf->GetY() > 260) $this->pdf->AddPage('P');
        }
        $this->renderFinalSummary();
    }

    private function renderFinalSummary(): void
    {
        $start_date = $this->params['start_date'] ?? date('Y-m-01');
        $end_date = $this->params['end_date'] ?? date('Y-m-d');
        $search = $this->params['search'] ?? '';
        $summary = $this->fetchSummaryData($this->params['user_id'], $start_date, $end_date, $search);

        // Summary Block
        $this->pdf->Ln(5);
        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->Cell(190, 8, 'RINGKASAN ESTIMASI PROFIT (TRANSAKSI LUNAS)', 0, 1, 'L');
        
        $this->pdf->SetFont('Helvetica', 'B', 7);
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->Cell(40, 6, 'Kategori', 1, 0, 'L', true);
        $this->pdf->Cell(35, 6, 'Penjualan', 1, 0, 'R', true);
        $this->pdf->Cell(35, 6, 'HPP', 1, 0, 'R', true);
        $this->pdf->Cell(35, 6, 'Profit', 1, 1, 'R', true);

        $this->pdf->SetFont('Helvetica', '', 7);
        // Barang Toko
        $this->pdf->Cell(40, 6, 'Barang Toko', 1, 0, 'L');
        $this->pdf->Cell(35, 6, format_currency_pdf($summary['shop']['sales']), 1, 0, 'R');
        $this->pdf->Cell(35, 6, format_currency_pdf($summary['shop']['hpp']), 1, 0, 'R');
        $this->pdf->Cell(35, 6, format_currency_pdf($summary['shop']['profit']), 1, 1, 'R');

        // Barang Konsinyasi
        $this->pdf->Cell(40, 6, 'Barang Konsinyasi', 1, 0, 'L');
        $this->pdf->Cell(35, 6, format_currency_pdf($summary['consignment']['sales']), 1, 0, 'R');
        $this->pdf->Cell(35, 6, format_currency_pdf($summary['consignment']['hpp']), 1, 0, 'R');
        $this->pdf->Cell(35, 6, format_currency_pdf($summary['consignment']['profit']), 1, 1, 'R');

        // Total
        $this->pdf->SetFont('Helvetica', 'B', 7);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(40, 6, 'GRAND TOTAL', 1, 0, 'L', true);
        $this->pdf->Cell(35, 6, format_currency_pdf($summary['total_penjualan']), 1, 0, 'R', true);
        $this->pdf->Cell(35, 6, format_currency_pdf($summary['total_hpp']), 1, 0, 'R', true);
        $this->pdf->Cell(35, 6, format_currency_pdf($summary['total_profit']), 1, 1, 'R', true);
    }

    private function fetchSummaryData(int $user_id, string $start_date, string $end_date, string $search): array
    {
        $where_clauses = ['p.user_id = ?', 'DATE(p.tanggal_penjualan) >= ?', 'DATE(p.tanggal_penjualan) <= ?', "p.status = 'completed'"];
        $params = ['iss', $user_id, $start_date, $end_date];

        if (!empty($search)) {
            $where_clauses[] = '(p.customer_name LIKE ? OR u.username LIKE ?)';
            $params[0] .= 'ss';
            $searchTerm = '%' . $search . '%';
            array_push($params, $searchTerm, $searchTerm);
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        $query = "
            SELECT
                pd.item_type,
                SUM(pd.subtotal) as total_penjualan,
                SUM(CASE 
                    WHEN pd.item_type = 'normal' THEN pd.quantity * i.harga_beli 
                    WHEN pd.item_type = 'consignment' THEN pd.quantity * ci.harga_beli 
                    ELSE 0 
                END) as total_hpp
            FROM penjualan_details pd
            JOIN penjualan p ON pd.penjualan_id = p.id
            LEFT JOIN items i ON pd.item_id = i.id AND pd.item_type = 'normal'
            LEFT JOIN consignment_items ci ON pd.item_id = ci.id AND pd.item_type = 'consignment'
            LEFT JOIN users u ON p.created_by = u.id
            $where_sql
            GROUP BY pd.item_type
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(...$params);
        $stmt->execute();
        $rows = stmt_fetch_all($stmt);

        $summary = [
            'total_penjualan' => 0, 'total_hpp' => 0, 'total_profit' => 0,
            'shop' => ['sales' => 0, 'hpp' => 0, 'profit' => 0],
            'consignment' => ['sales' => 0, 'hpp' => 0, 'profit' => 0]
        ];

        foreach ($rows as $row) {
            $sales = (float)$row['total_penjualan'];
            $hpp = (float)$row['total_hpp'];
            $profit = $sales - $hpp;
            $summary['total_penjualan'] += $sales;
            $summary['total_hpp'] += $hpp;
            $summary['total_profit'] += $profit;
            if ($row['item_type'] === 'normal') {
                $summary['shop'] = ['sales' => $sales, 'hpp' => $hpp, 'profit' => $profit];
            } elseif ($row['item_type'] === 'consignment') {
                $summary['consignment'] = ['sales' => $sales, 'hpp' => $hpp, 'profit' => $profit];
            }
        }
        return $summary;
    }
}