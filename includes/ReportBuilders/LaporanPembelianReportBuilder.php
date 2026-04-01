<?php

require_once __DIR__ . '/ReportBuilderInterface.php';

class LaporanPembelianReportBuilder implements ReportBuilderInterface {
    private $pdf;
    private $conn;
    private $params;

    public function __construct(PDF $pdf, mysqli $conn, array $params) {
        $this->pdf = $pdf;
        $this->conn = $conn;
        $this->params = $params;
    }

    public function build(): void {
        $user_id = $this->params['user_id'] ?? 1;
        $start_date = $this->params['start_date'] ?? '';
        $end_date = $this->params['end_date'] ?? '';
        $supplier_id = $this->params['supplier_id'] ?? '';
        $search = $this->params['search'] ?? '';

        // --- Data Fetching ---
        $where_clauses = ['p.user_id = ?'];
        $sql_params = ['i', $user_id];

        if (!empty($start_date)) {
            $where_clauses[] = 'DATE(p.tanggal_pembelian) >= ?';
            $sql_params[0] .= 's';
            $sql_params[] = $start_date;
        }
        if (!empty($end_date)) {
            $where_clauses[] = 'DATE(p.tanggal_pembelian) <= ?';
            $sql_params[0] .= 's';
            $sql_params[] = $end_date;
        }
        if (!empty($supplier_id)) {
            $where_clauses[] = 'p.supplier_id = ?';
            $sql_params[0] .= 'i';
            $sql_params[] = (int)$supplier_id;
        }
        if (!empty($search)) {
            $where_clauses[] = '(s.nama_pemasok LIKE ? OR p.keterangan LIKE ? OR p.nomor_referensi LIKE ?)';
            $sql_params[0] .= 'sss';
            $searchTerm = '%' . $search . '%';
            array_push($sql_params, $searchTerm, $searchTerm, $searchTerm);
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

        $query = "
            SELECT 
                p.nomor_referensi,
                p.tanggal_pembelian,
                s.nama_pemasok,
                p.payment_method,
                p.status,
                p.total
            FROM pembelian p
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            $where_sql
            ORDER BY p.tanggal_pembelian ASC
        ";
        
        $stmt = $this->conn->prepare($query);
        $bind_params = [&$sql_params[0]];
        for ($i = 1; $i < count($sql_params); $i++) { $bind_params[] = &$sql_params[$i]; }
        call_user_func_array([$stmt, 'bind_param'], $bind_params);
        $stmt->execute();
        $items = stmt_fetch_all($stmt);
        $stmt->close();

        // --- PDF Generation ---
        $this->pdf->report_title = 'Laporan Pembelian';
        $period_text = '';
        if (!empty($start_date) && !empty($end_date)) {
            $period_text = 'Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date));
        }
        $this->pdf->report_period = $period_text;
        
        $this->pdf->AddPage('P', 'A4');
        
        // --- Summary Table ---
        $total_pembelian = 0;
        $total_tunai = 0;
        $total_kredit = 0;
        foreach ($items as $item) {
            if ($item['status'] !== 'void') {
                $total_pembelian += (float)$item['total'];
                if ($item['payment_method'] === 'cash') $total_tunai += (float)$item['total'];
                if ($item['payment_method'] === 'credit') $total_kredit += (float)$item['total'];
            }
        }

        $this->pdf->SetFont('Helvetica', 'B', 10);
        $this->pdf->Cell(45, 6, 'Total Pembelian', 0, 0);
        $this->pdf->Cell(30, 6, ': Rp ' . number_format($total_pembelian, 0, ',', '.'), 0, 1);
        $this->pdf->SetFont('Helvetica', '', 9);
        $this->pdf->Cell(45, 6, 'Total Pembelian Tunai', 0, 0);
        $this->pdf->Cell(30, 6, ': Rp ' . number_format($total_tunai, 0, ',', '.'), 0, 1);
        $this->pdf->Cell(45, 6, 'Total Pembelian Kredit', 0, 0);
        $this->pdf->Cell(30, 6, ': Rp ' . number_format($total_kredit, 0, ',', '.'), 0, 1);
        $this->pdf->Ln(5);

        // --- Data Table ---
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->SetFillColor(240, 240, 240);
        
        $w = [25, 45, 45, 25, 20, 30]; // Widths
        $headers = ['Tanggal', 'No. Referensi', 'Pemasok', 'Metode', 'Status', 'Total'];
        
        foreach ($headers as $i => $h) {
            $this->pdf->Cell($w[$i], 8, $h, 1, 0, 'C', true);
        }
        $this->pdf->Ln();

        $this->pdf->SetFont('Helvetica', '', 8);
        foreach ($items as $item) {
            $status_text = ($item['status'] === 'paid') ? 'Lunas' : (($item['status'] === 'open') ? 'Hutang' : 'Batal');
            $method_text = ($item['payment_method'] === 'cash') ? 'Tunai' : 'Kredit';
            
            // Check for page break
            if ($this->pdf->GetY() > 270) {
                $this->pdf->AddPage();
                // Redraw headers
                $this->pdf->SetFont('Helvetica', 'B', 9);
                foreach ($headers as $i => $h) {
                    $this->pdf->Cell($w[$i], 8, $h, 1, 0, 'C', true);
                }
                $this->pdf->Ln();
                $this->pdf->SetFont('Helvetica', '', 8);
            }

            $this->pdf->Cell($w[0], 7, date('d/m/Y', strtotime($item['tanggal_pembelian'])), 1, 0, 'C');
            $this->pdf->Cell($w[1], 7, $item['nomor_referensi'], 1, 0, 'L');
            $this->pdf->Cell($w[2], 7, substr($item['nama_pemasok'] ?: '-', 0, 25), 1, 0, 'L');
            $this->pdf->Cell($w[3], 7, $method_text, 1, 0, 'C');
            $this->pdf->Cell($w[4], 7, $status_text, 1, 0, 'C');
            $this->pdf->Cell($w[5], 7, number_format($item['total'], 0, ',', '.'), 1, 1, 'R');
        }

        if (empty($items)) {
            $this->pdf->Cell(array_sum($w), 10, 'Tidak ada data ditemukan.', 1, 1, 'C');
        }

        // --- Grand Total ---
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->Cell(array_sum(array_slice($w, 0, 5)), 8, 'GRAND TOTAL', 1, 0, 'R', true);
        $this->pdf->Cell($w[5], 8, number_format($total_pembelian, 0, ',', '.'), 1, 1, 'R', true);
        
        $this->pdf->RenderSignatureBlock();
    }
}
