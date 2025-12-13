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
                a.saldo_normal, a.saldo_awal,
                COALESCE(SUM(CASE WHEN gl.tanggal <= ? THEN gl.debit ELSE 0 END), 0) as total_debit_mutasi,
                COALESCE(SUM(CASE WHEN gl.tanggal <= ? THEN gl.kredit ELSE 0 END), 0) as total_kredit_mutasi
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
            $saldo_akhir = 0;
            if ($row['saldo_normal'] === 'Debit') {
                $saldo_akhir = (float)$row['saldo_awal'] + (float)$row['total_debit_mutasi'] - (float)$row['total_kredit_mutasi'];
            } else { // Kredit
                $saldo_akhir = (float)$row['saldo_awal'] + (float)$row['total_kredit_mutasi'] - (float)$row['total_debit_mutasi'];
            }

            if (abs($saldo_akhir) < 0.001) continue; // Skip akun dengan saldo nol (toleransi floating point)

            $this->pdf->Cell(25, 7, $row['kode_akun'], 1);
            $this->pdf->Cell(80, 7, $row['nama_akun'], 1);

            $debit_col = '';
            $kredit_col = '';

            if (($row['saldo_normal'] === 'Debit' && $saldo_akhir >= 0) || ($row['saldo_normal'] === 'Kredit' && $saldo_akhir < 0)) { // Saldo normal Debit atau Saldo normal Kredit tapi nilainya negatif (abnormal)
                $debit_col = format_currency_pdf(abs($saldo_akhir));
                $totalDebit += abs($saldo_akhir);
            } else { // Saldo normal Kredit atau Saldo normal Debit tapi nilainya negatif (abnormal)
                $kredit_col = format_currency_pdf(abs($saldo_akhir));
                $totalKredit += abs($saldo_akhir);
            }
            $this->pdf->Cell(40, 7, $debit_col, 1, 0, 'R');
            $this->pdf->Cell(40, 7, $kredit_col, 1, 1, 'R');
        }

        // Baris Total
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->Cell(105, 8, 'TOTAL', 1, 0, 'C', true);
        $this->pdf->Cell(40, 8, format_currency_pdf($totalDebit), 1, 0, 'R', true);
        $this->pdf->Cell(40, 8, format_currency_pdf($totalKredit), 1, 1, 'R', true);
    }
}