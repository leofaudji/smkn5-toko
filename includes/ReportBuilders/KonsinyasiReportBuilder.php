<?php
require_once __DIR__ . '/ReportBuilderInterface.php';

class KonsinyasiReportBuilder implements ReportBuilderInterface
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
        $user_id = $this->params['user_id'];
        $start_date = $this->params['start_date'] ?? date('Y-m-01');
        $end_date = $this->params['end_date'] ?? date('Y-m-t');

        $this->pdf->SetTitle('Laporan Utang Konsinyasi');
        $this->pdf->report_title = 'Laporan Utang Konsinyasi';
        $this->pdf->report_period = 'Periode: ' . date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date));
        $this->pdf->AddPage('P');

        $data = $this->fetchData($user_id, $start_date, $end_date);
        $this->render($data);
    }

    private function fetchData(int $user_id, string $start_date, string $end_date): array
    {
        $supplier_id = !empty($this->params['supplier_id']) ? (int)$this->params['supplier_id'] : null;
        $status = $this->params['status'] ?? 'Semua';

        $where = "WHERE gl.user_id = ? AND gl.tanggal BETWEEN ? AND ? AND gl.ref_type IN ('jurnal', 'penjualan') AND gl.consignment_item_id IS NOT NULL AND gl.account_id = (SELECT setting_value FROM settings WHERE setting_key = 'consignment_payable_account')";
        $params_val = [$user_id, $start_date, $end_date];
        $types = "iss";

        if ($supplier_id) {
            $where .= " AND ci.supplier_id = ?";
            $params_val[] = $supplier_id;
            $types .= "i";
        }

        $query = "
            SELECT 
                s.nama_pemasok,
                ci.nama_barang,
                SUM(IF(gl.debit > 0, -gl.qty, gl.qty)) as total_terjual, 
                ci.harga_beli, 
                (SUM(IF(gl.debit > 0, -gl.qty, gl.qty)) * ci.harga_beli) as total_utang,
                IFNULL(curr_stat.total_hutang_pemasok, 0) as total_hutang_pemasok,
                IFNULL(curr_stat.total_bayar_pemasok, 0) as total_bayar_pemasok
            FROM general_ledger gl
            JOIN consignment_items ci ON gl.consignment_item_id = ci.id
            JOIN suppliers s ON ci.supplier_id = s.id
            LEFT JOIN (
                SELECT 
                    s2.id as sid,
                    (SELECT SUM(gl3.kredit) FROM general_ledger gl3 JOIN consignment_items ci3 ON gl3.consignment_item_id = ci3.id WHERE ci3.supplier_id = s2.id AND gl3.account_id = (SELECT setting_value FROM settings WHERE setting_key = 'consignment_payable_account') AND gl3.ref_type IN ('jurnal', 'penjualan')) as total_hutang_pemasok,
                    (SELECT SUM(gl4.debit) FROM general_ledger gl4 WHERE gl4.account_id = (SELECT setting_value FROM settings WHERE setting_key = 'consignment_payable_account') AND gl4.debit > 0 AND SUBSTRING_INDEX(SUBSTRING_INDEX(gl4.keterangan, 'ke ', -1), ' -', 1) = s2.nama_pemasok) as total_bayar_pemasok
                FROM suppliers s2
            ) curr_stat ON s.id = curr_stat.sid
            $where
            GROUP BY s.id, s.nama_pemasok, ci.nama_barang, ci.harga_beli, curr_stat.total_hutang_pemasok, curr_stat.total_bayar_pemasok
            HAVING total_terjual > 0
        ";

        if ($status === 'Lunas') {
            $query .= " AND total_hutang_pemasok <= total_bayar_pemasok AND total_hutang_pemasok > 0";
        } elseif ($status === 'Belum Lunas') {
            $query .= " AND (total_hutang_pemasok > total_bayar_pemasok OR total_hutang_pemasok IS NULL OR total_hutang_pemasok = 0)";
        }

        $query .= " ORDER BY s.nama_pemasok, ci.nama_barang";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params_val);
        $stmt->execute();
        return stmt_fetch_all($stmt);
    }

    private function render(array $data): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(45, 8, 'Pemasok', 1, 0, 'C', true);
        $this->pdf->Cell(55, 8, 'Nama Barang', 1, 0, 'C', true);
        $this->pdf->Cell(15, 8, 'Terjual', 1, 0, 'C', true);
        $this->pdf->Cell(25, 8, 'Harga Beli', 1, 0, 'C', true);
        $this->pdf->Cell(30, 8, 'Total Utang', 1, 0, 'C', true);
        $this->pdf->Cell(25, 8, 'Status', 1, 1, 'C', true);

        $this->pdf->SetFont('Helvetica', '', 8);
        $totalUtangKeseluruhan = 0;
        foreach ($data as $row) {
            $totalUtangKeseluruhan += (float)$row['total_utang'];
            
            $isLunas = (float)$row['total_hutang_pemasok'] <= (float)$row['total_bayar_pemasok'] && (float)$row['total_hutang_pemasok'] > 0;
            $statusText = $isLunas ? 'LUNAS' : 'BLM LUNAS';

            $this->pdf->Cell(45, 6, $row['nama_pemasok'], 1, 0);
            $this->pdf->Cell(55, 6, $row['nama_barang'], 1, 0);
            $this->pdf->Cell(15, 6, $row['total_terjual'], 1, 0, 'C');
            $this->pdf->Cell(25, 6, format_currency_pdf($row['harga_beli']), 1, 0, 'R');
            $this->pdf->Cell(30, 6, format_currency_pdf($row['total_utang']), 1, 0, 'R');
            $this->pdf->Cell(25, 6, $statusText, 1, 1, 'C');
        }
        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->Cell(140, 8, 'TOTAL UTANG PERIODE INI', 1, 0, 'R', true);
        $this->pdf->Cell(30, 8, format_currency_pdf($totalUtangKeseluruhan), 1, 0, 'R', true);
        $this->pdf->Cell(25, 8, '', 1, 1, 'C', true);

        $this->pdf->signature_date = $this->params['end_date'] ?? date('Y-m-d');
        $this->pdf->RenderSignatureBlock();
    }
}