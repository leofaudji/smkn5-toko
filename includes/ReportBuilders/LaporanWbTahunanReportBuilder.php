<?php

require_once __DIR__ . '/ReportBuilderInterface.php';

class LaporanWbTahunanReportBuilder implements ReportBuilderInterface {
    protected $pdf;
    protected $conn;
    protected $params;

    public function __construct(PDF $pdf, mysqli $conn, array $params) {
        $this->pdf = $pdf;
        $this->conn = $conn;
        $this->params = $params;
    }

    public function build(): void {
        $tahun = $this->params['tahun'] ?? date('Y');
        $only_arrears = !empty($this->params['only_arrears']) && $this->params['only_arrears'] == 1;
        $user_id = $this->params['user_id'];

        // --- Data Fetching Logic ---
        
        // 1. Ambil semua anggota aktif
        $sql_members = "SELECT id, nomor_anggota, nama_lengkap, saldo_wajib_belanja FROM anggota WHERE user_id = ? AND status = 'aktif' ORDER BY nama_lengkap ASC";
        $stmt_members = $this->conn->prepare($sql_members);
        $stmt_members->bind_param('i', $user_id);
        $stmt_members->execute();
        $members = $stmt_members->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_members->close();

        // 2. Ambil transaksi WB (SETOR) untuk tahun yang dipilih
        $sql_trans = "SELECT anggota_id, MONTH(tanggal) as bulan, SUM(jumlah) as total_bulan 
                      FROM transaksi_wajib_belanja 
                      WHERE user_id = ? AND YEAR(tanggal) = ? AND jenis = 'setor'
                      GROUP BY anggota_id, MONTH(tanggal)";
        $stmt_trans = $this->conn->prepare($sql_trans);
        $stmt_trans->bind_param('ii', $user_id, $tahun);
        $stmt_trans->execute();
        $transactions_result = $stmt_trans->get_result();
        
        $transactions = [];
        while($row = $transactions_result->fetch_assoc()) {
            $transactions[$row['anggota_id']][$row['bulan']] = (float)$row['total_bulan'];
        }
        $stmt_trans->close();

        // 3. Ambil Total Belanja per Anggota tahun ini
        $sql_belanja = "SELECT anggota_id, SUM(jumlah) as total_belanja 
                        FROM transaksi_wajib_belanja 
                        WHERE user_id = ? AND YEAR(tanggal) = ? AND jenis = 'belanja'
                        GROUP BY anggota_id";
        $stmt_belanja = $this->conn->prepare($sql_belanja);
        $stmt_belanja->bind_param('ii', $user_id, $tahun);
        $stmt_belanja->execute();
        $res_belanja = $stmt_belanja->get_result();
        $belanja_data = [];
        while($row = $res_belanja->fetch_assoc()) $belanja_data[$row['anggota_id']] = $row['total_belanja'];
        $stmt_belanja->close();

        // Settings
        $nominal_wajib_belanja = (float)get_setting('nominal_wajib_belanja', 50000, $this->conn);
        $current_year = (int)date('Y');
        $current_month = (int)date('n');

        // Process Data
        $report_data = [];
        foreach ($members as $member) {
            $row = [
                'nama_lengkap' => $member['nama_lengkap'],
                'nomor_anggota' => $member['nomor_anggota'],
                'total_tahun' => 0,
                'total_belanja' => (float)($belanja_data[$member['id']] ?? 0),
                'saldo_akhir' => (float)$member['saldo_wajib_belanja']
            ];

            for ($m = 1; $m <= 12; $m++) {
                $amount = $transactions[$member['id']][$m] ?? 0;
                $row['bulan_' . $m] = $amount;
                $row['total_tahun'] += $amount;
            }

            // Hitung Tunggakan
            $target_months = 0;
            if ($tahun < $current_year) {
                $target_months = 12;
            } elseif ($tahun == $current_year) {
                $target_months = $current_month;
            }
            
            $target_amount = $target_months * $nominal_wajib_belanja;
            $tunggakan = max(0, $target_amount - $row['total_tahun']);
            $row['sisa_tunggakan'] = $tunggakan;

            if ($only_arrears && $tunggakan <= 0) {
                continue;
            }

            $report_data[] = $row;
        }

        // --- PDF Generation ---
        $this->pdf->report_title = 'Rekap Wajib Belanja Tahunan ' . $tahun;
        $this->pdf->SetMargins(5, 10, 5); 
        $this->pdf->AddPage('P', 'A4'); // Portrait (P)
        
        // Title
        $this->pdf->SetFont('Helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, strtoupper($this->pdf->report_title), 0, 1, 'C');
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(0, 5, 'Dicetak pada: ' . date('d-m-Y H:i'), 0, 1, 'C');
        $this->pdf->Ln(3);

        // Table Header
        $this->pdf->SetFont('Helvetica', 'B', 6); // Font 6pt agar muat 17 kolom
        $this->pdf->SetFillColor(240, 240, 240);
        
        // Lebar kolom dioptimalkan untuk Portrait (~190mm)
        $w_name = 25;
        $w_month = 9;
        $w_total = 14.25;
        
        $this->pdf->Cell($w_name, 8, 'Nama Anggota', 1, 0, 'L', true);
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        foreach ($months as $m) {
            $this->pdf->Cell($w_month, 8, $m, 1, 0, 'C', true);
        }
        $this->pdf->Cell($w_total, 8, 'Setor', 1, 0, 'R', true);
        $this->pdf->Cell($w_total, 8, 'Blj', 1, 0, 'R', true);
        $this->pdf->Cell($w_total, 8, 'Tgk', 1, 0, 'R', true);
        $this->pdf->Cell($w_total, 8, 'Saldo', 1, 1, 'R', true);

        // Table Body
        $this->pdf->SetFont('Helvetica', '', 6);
        $grand_total_setor = 0;
        $grand_total_belanja = 0;
        $grand_total_tunggakan = 0;
        $grand_total_saldo = 0;
        $month_totals = array_fill(1, 12, 0);

        foreach ($report_data as $row) {
            // Cek Page Break
            if ($this->pdf->GetY() > 250) {
                $this->pdf->AddPage('P');
                $this->pdf->SetFont('Helvetica', 'B', 6);
                $this->pdf->Cell($w_name, 8, 'Nama Anggota', 1, 0, 'L', true);
                foreach ($months as $m) $this->pdf->Cell($w_month, 8, $m, 1, 0, 'C', true);
                $this->pdf->Cell($w_total, 8, 'Setor', 1, 0, 'R', true);
                $this->pdf->Cell($w_total, 8, 'Blj', 1, 0, 'R', true);
                $this->pdf->Cell($w_total, 8, 'Tgk', 1, 0, 'R', true);
                $this->pdf->Cell($w_total, 8, 'Saldo', 1, 1, 'R', true);
                $this->pdf->SetFont('Helvetica', '', 6);
            }

            $this->pdf->Cell($w_name, 6, substr($row['nama_lengkap'], 0, 18), 1, 0, 'L');
            
            for ($m = 1; $m <= 12; $m++) {
                $val = $row['bulan_' . $m];
                $month_totals[$m] += $val;
                $txt = $val > 0 ? number_format($val, 0, ',', '.') : '-';
                $this->pdf->Cell($w_month, 6, $txt, 1, 0, 'R');
            }
            
            $this->pdf->Cell($w_total, 6, number_format($row['total_tahun'], 0, ',', '.'), 1, 0, 'R');
            $this->pdf->Cell($w_total, 6, number_format($row['total_belanja'], 0, ',', '.'), 1, 0, 'R');
            
            // Highlight tunggakan
            if ($row['sisa_tunggakan'] > 0) {
                $this->pdf->SetTextColor(255, 0, 0);
                $this->pdf->SetFont('Helvetica', 'B', 6);
            }
            $this->pdf->Cell($w_total, 6, number_format($row['sisa_tunggakan'], 0, ',', '.'), 1, 0, 'R');
            $this->pdf->SetTextColor(0);
            $this->pdf->SetFont('Helvetica', '', 6);

            $this->pdf->Cell($w_total, 6, number_format($row['saldo_akhir'], 0, ',', '.'), 1, 1, 'R');

            $grand_total_setor += $row['total_tahun'];
            $grand_total_belanja += $row['total_belanja'];
            $grand_total_tunggakan += $row['sisa_tunggakan'];
            $grand_total_saldo += $row['saldo_akhir'];
        }

        // Footer Totals
        $this->pdf->SetFont('Helvetica', 'B', 6);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell($w_name, 8, 'TOTAL', 1, 0, 'C', true);
        for ($m = 1; $m <= 12; $m++) {
            $this->pdf->Cell($w_month, 8, number_format($month_totals[$m], 0, ',', '.'), 1, 0, 'R', true);
        }
        $this->pdf->Cell($w_total, 8, number_format($grand_total_setor, 0, ',', '.'), 1, 0, 'R', true);
        $this->pdf->Cell($w_total, 8, number_format($grand_total_belanja, 0, ',', '.'), 1, 0, 'R', true);
        $this->pdf->Cell($w_total, 8, number_format($grand_total_tunggakan, 0, ',', '.'), 1, 0, 'R', true);
        $this->pdf->Cell($w_total, 8, number_format($grand_total_saldo, 0, ',', '.'), 1, 1, 'R', true);
    }
}