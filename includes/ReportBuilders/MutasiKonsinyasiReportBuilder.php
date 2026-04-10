<?php
require_once __DIR__ . '/ReportBuilderInterface.php';

class MutasiKonsinyasiReportBuilder implements ReportBuilderInterface
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
        $end_date = $this->params['end_date'] ?? date('Y-m-d');

        $this->pdf->SetTitle('Laporan Mutasi Stok Konsinyasi');
        $this->pdf->report_title = 'Laporan Mutasi Stok Konsinyasi';
        $this->pdf->report_period = 'Periode: ' . date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date));
        $this->pdf->AddPage('P');

        $data = $this->fetchData($user_id, $start_date, $end_date);
        $this->render($data);
    }

    private function fetchData(int $user_id, string $start_date, string $end_date): array
    {
        $supplier_id = !empty($this->params['supplier_id']) ? (int)$this->params['supplier_id'] : null;

        $where_ci = "WHERE ci.user_id = ?";
        $where_cr = "WHERE cr.user_id = ?";
        $where_gl = "WHERE gl.user_id = ? AND gl.account_id = (SELECT setting_value FROM settings WHERE setting_key = 'consignment_payable_account') AND gl.kredit > 0 AND gl.ref_type IN ('jurnal', 'penjualan')";
        
        $params_ci = [$user_id];
        $params_cr = [$user_id];
        $params_gl = [$user_id];
        
        $types_ci = "i";
        $types_cr = "i";
        $types_gl = "i";

        if ($supplier_id) {
            $where_ci .= " AND ci.supplier_id = ?";
            $where_cr .= " AND ci.supplier_id = ?";
            $where_gl .= " AND ci.supplier_id = ?";
            $params_ci[] = $supplier_id;
            $params_cr[] = $supplier_id;
            $params_gl[] = $supplier_id;
            $types_ci .= "i";
            $types_cr .= "i";
            $types_gl .= "i";
        }

        if ($start_date) {
            $where_ci .= " AND ci.tanggal_terima >= ?";
            $where_cr .= " AND cr.tanggal >= ?";
            $where_gl .= " AND gl.tanggal >= ?";
            $params_ci[] = $start_date;
            $params_cr[] = $start_date;
            $params_gl[] = $start_date;
            $types_ci .= "s";
            $types_cr .= "s";
            $types_gl .= "s";
        }

        if ($end_date) {
            $where_ci .= " AND ci.tanggal_terima <= ?";
            $where_cr .= " AND cr.tanggal <= ?";
            $where_gl .= " AND gl.tanggal <= ?";
            $params_ci[] = $end_date;
            $params_cr[] = $end_date;
            $params_gl[] = $end_date;
            $types_ci .= "s";
            $types_cr .= "s";
            $types_gl .= "s";
        }

        $query = "
            SELECT * FROM (
                SELECT 
                    ci.tanggal_terima as tanggal, 
                    ci.nama_barang, 
                    s.nama_pemasok, 
                    'Stok Awal' as tipe, 
                    ci.stok_awal as qty, 
                    'Penerimaan awal saat pendaftaran barang' as keterangan
                FROM consignment_items ci
                JOIN suppliers s ON ci.supplier_id = s.id
                $where_ci
                
                UNION ALL
                
                SELECT 
                    cr.tanggal, 
                    ci.nama_barang, 
                    s.nama_pemasok, 
                    'Restock' as tipe, 
                    cr.qty, 
                    cr.keterangan
                FROM consignment_restocks cr
                JOIN consignment_items ci ON cr.consignment_item_id = ci.id
                JOIN suppliers s ON ci.supplier_id = s.id
                $where_cr

                UNION ALL

                SELECT 
                    gl.tanggal,
                    ci.nama_barang,
                    s.nama_pemasok,
                    'Terjual' as tipe,
                    SUM(gl.qty) as qty,
                    'Total penjualan harian' as keterangan
                FROM general_ledger gl
                JOIN consignment_items ci ON gl.consignment_item_id = ci.id
                JOIN suppliers s ON ci.supplier_id = s.id
                $where_gl
                GROUP BY gl.tanggal, ci.id
            ) as combined_mutations
            ORDER BY tanggal DESC, nama_barang ASC
        ";

        $final_params = array_merge($params_ci, $params_cr, $params_gl);
        $final_types = $types_ci . $types_cr . $types_gl;

        $stmt = $this->conn->prepare($query);
        if (!empty($final_params)) {
            $stmt->bind_param($final_types, ...$final_params);
        }
        $stmt->execute();
        return stmt_fetch_all($stmt);
    }

    private function render(array $data): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(25, 8, 'Tanggal', 1, 0, 'C', true);
        $this->pdf->Cell(50, 8, 'Nama Barang', 1, 0, 'C', true);
        $this->pdf->Cell(45, 8, 'Pemasok', 1, 0, 'C', true);
        $this->pdf->Cell(20, 8, 'Tipe', 1, 0, 'C', true);
        $this->pdf->Cell(15, 8, 'Qty', 1, 0, 'C', true);
        $this->pdf->Cell(35, 8, 'Keterangan', 1, 1, 'C', true);

        $this->pdf->SetFont('Helvetica', '', 8);
        if (empty($data)) {
            $this->pdf->Cell(190, 8, 'Tidak ada data mutasi untuk periode ini.', 1, 1, 'C');
        } else {
            $widths = [25, 50, 45, 20, 15, 35];
            $aligns = ['C', 'L', 'L', 'C', 'C', 'L'];
            
            foreach ($data as $row) {
                $date = date('d/m/Y', strtotime($row['tanggal']));
                $qty = number_format($row['qty'], 0);
                
                $this->pdf->Row($widths, [
                    $date,
                    $row['nama_barang'],
                    $row['nama_pemasok'],
                    $row['tipe'],
                    $qty,
                    $row['keterangan'] ?: '-'
                ], $aligns);
            }
        }

        $this->pdf->signature_date = $this->params['end_date'] ?? date('Y-m-d');
        $this->pdf->RenderSignatureBlock();
    }
}
