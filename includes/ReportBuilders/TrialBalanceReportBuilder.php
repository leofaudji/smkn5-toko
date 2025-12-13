<?php
require_once __DIR__ . '/ReportBuilderInterface.php';

class TrialBalanceReportBuilder implements ReportBuilderInterface
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
        $tanggal = $this->params['tanggal'] ?? date('Y-m-d');
        $user_id = $this->params['user_id'];

        $this->pdf->SetTitle('Laporan Neraca Saldo');
        $this->pdf->report_title = 'Laporan Neraca Saldo (Trial Balance)';
        $this->pdf->report_period = 'Per Tanggal: ' . date('d F Y', strtotime($tanggal));
        $this->pdf->AddPage();

        $data = $this->fetchData($user_id, $tanggal);
        $this->render($data);

        $this->pdf->signature_date = $tanggal;
        $this->pdf->RenderSignatureBlock();
    }

    private function fetchData(int $user_id, string $tanggal): array
    {
        // Query ini mengambil semua akun dan menghitung saldo akhirnya.
        $stmt = $this->conn->prepare("
            SELECT 
                a.kode_akun, 
                a.nama_akun,
                a.saldo_normal,
                a.saldo_awal + COALESCE(SUM(CASE WHEN gl.tanggal <= ? THEN gl.debit ELSE 0 END), 0) as total_debit,
                a.saldo_awal + COALESCE(SUM(CASE WHEN gl.tanggal <= ? THEN gl.kredit ELSE 0 END), 0) as total_kredit
            FROM accounts a
            LEFT JOIN general_ledger gl ON a.id = gl.account_id AND gl.tanggal <= ?
            WHERE a.user_id = ?
            GROUP BY a.id, a.kode_akun, a.nama_akun, a.saldo_normal, a.saldo_awal
            ORDER BY a.kode_akun ASC
        ");
        $stmt->bind_param('sssi', $tanggal, $tanggal, $tanggal, $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function render(array $data): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(25, 8, 'Kode Akun', 1, 0, 'C', true);
        $this->pdf->Cell(80, 8, 'Nama Akun', 1, 0, 'C', true);
        $this->pdf->Cell(40, 8, 'Debit', 1, 0, 'C', true);
        $this->pdf->Cell(40, 8, 'Kredit', 1, 1, 'C', true);

        $this->pdf->SetFont('Helvetica', '', 8);
        $totalDebit = 0;
        $totalKredit = 0;

        foreach ($data as $row) {
            $saldo_akhir = ($row['saldo_normal'] === 'Debit') ? $row['total_debit'] - $row['total_kredit'] : $row['total_kredit'] - $row['total_debit'];

            if ($saldo_akhir == 0) continue; // Skip akun dengan saldo nol

            $this->pdf->Cell(25, 7, $row['kode_akun'], 1);
            $this->pdf->Cell(80, 7, $row['nama_akun'], 1);

            if ($row['saldo_normal'] === 'Debit') {
                $this->pdf->Cell(40, 7, format_currency_pdf($saldo_akhir), 1, 0, 'R');
                $this->pdf->Cell(40, 7, '', 1, 1, 'R');
                $totalDebit += $saldo_akhir;
            } else {
                $this->pdf->Cell(40, 7, '', 1, 0, 'R');
                $this->pdf->Cell(40, 7, format_currency_pdf($saldo_akhir), 1, 1, 'R');
                $totalKredit += $saldo_akhir;
            }
        }

        // Baris Total
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->Cell(105, 8, 'TOTAL', 1, 0, 'C', true);
        $this->pdf->Cell(40, 8, format_currency_pdf($totalDebit), 1, 0, 'R', true);
        $this->pdf->Cell(40, 8, format_currency_pdf($totalKredit), 1, 1, 'R', true);
    }
}