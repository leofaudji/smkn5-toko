<?php

class StrukPenjualanReportBuilder implements ReportBuilderInterface
{
    protected $pdf;
    protected $conn;
    protected $params;

    public function __construct(PDF $pdf, mysqli $conn, array $params)
    {
        $this->pdf = $pdf;
        $this->conn = $conn;
        $this->params = $params;
    }

    public function build(): void
    {
        $id = $this->params['id'] ?? 0;
        if ($id <= 0) {
            throw new Exception("ID Transaksi tidak valid.");
        }

        // Ambil data header penjualan
        $stmt_header = $this->conn->prepare("SELECT p.*, u.nama_lengkap as kasir FROM penjualan p JOIN users u ON p.created_by = u.id WHERE p.id = ?");
        $stmt_header->bind_param('i', $id);
        $stmt_header->execute();
        $header = $stmt_header->get_result()->fetch_assoc();
        $stmt_header->close();

        if (!$header) {
            throw new Exception("Transaksi tidak ditemukan.");
        }

        // Ambil data detail penjualan
        $stmt_details = $this->conn->prepare("SELECT * FROM penjualan_details WHERE penjualan_id = ?");
        $stmt_details->bind_param('i', $id);
        $stmt_details->execute();
        $details = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_details->close();

        $this->pdf->report_title = 'Struk Penjualan';
        $this->pdf->AddPage('P', [80, 150]); // Ukuran kertas thermal 80mm
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->SetMargins(5, 5, 5);

        // Header Struk
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Cell(0, 5, get_setting('app_name', 'Toko SMKN 5'), 0, 1, 'C');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(0, 4, get_setting('report_header_address', 'Alamat Toko'), 0, 1, 'C');
        $this->pdf->Cell(0, 4, get_setting('report_header_contact', 'Kontak Toko'), 0, 1, 'C');
        $this->pdf->Ln(3);

        // Info Transaksi
        $this->pdf->Cell(20, 4, 'No. Struk');
        $this->pdf->Cell(2, 4, ':');
        $this->pdf->Cell(0, 4, $header['nomor_referensi'], 0, 1);

        $this->pdf->Cell(20, 4, 'Tanggal');
        $this->pdf->Cell(2, 4, ':');
        $this->pdf->Cell(0, 4, date('d/m/Y H:i', strtotime($header['tanggal_penjualan'])), 0, 1);

        $this->pdf->Cell(20, 4, 'Kasir');
        $this->pdf->Cell(2, 4, ':');
        $this->pdf->Cell(0, 4, $header['kasir'], 0, 1);

        $this->pdf->Cell(20, 4, 'Customer');
        $this->pdf->Cell(2, 4, ':');
        $this->pdf->Cell(0, 4, $header['customer_name'], 0, 1);

        // Garis pemisah
        $this->pdf->Line($this->pdf->GetX(), $this->pdf->GetY() + 2, $this->pdf->GetX() + 70, $this->pdf->GetY() + 2);
        $this->pdf->Ln(4);

        // Detail Item
        foreach ($details as $item) {
            $this->pdf->Cell(0, 4, $item['deskripsi_item'], 0, 1);
            $this->pdf->Cell(5); // Indentasi
            $this->pdf->Cell(20, 4, $item['quantity'] . ' x ' . number_format($item['price']), 0, 0);
            $this->pdf->Cell(0, 4, number_format($item['quantity'] * $item['price']), 0, 1, 'R');
            if ($item['discount'] > 0) {
                $this->pdf->Cell(5); // Indentasi
                $this->pdf->Cell(0, 4, 'Diskon: ' . number_format($item['discount']), 0, 1, 'R');
            }
        }

        // Garis pemisah
        $this->pdf->Line($this->pdf->GetX(), $this->pdf->GetY() + 2, $this->pdf->GetX() + 70, $this->pdf->GetY() + 2);
        $this->pdf->Ln(4);

        // Ringkasan Total
        $this->pdf->Cell(35, 5, 'Subtotal', 0, 0, 'R');
        $this->pdf->Cell(0, 5, number_format($header['subtotal']), 0, 1, 'R');
        $this->pdf->Cell(35, 5, 'Diskon', 0, 0, 'R');
        $this->pdf->Cell(0, 5, number_format($header['discount']), 0, 1, 'R');
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(35, 5, 'Total Akhir', 0, 0, 'R');
        $this->pdf->Cell(0, 5, number_format($header['total']), 0, 1, 'R');
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(35, 5, 'Bayar', 0, 0, 'R');
        $this->pdf->Cell(0, 5, number_format($header['bayar']), 0, 1, 'R');
        $this->pdf->Cell(35, 5, 'Kembali', 0, 0, 'R');
        $this->pdf->Cell(0, 5, number_format($header['kembali']), 0, 1, 'R');
        $this->pdf->Ln(5);

        // Footer Struk
        $this->pdf->SetFont('Arial', 'I', 8);
        $this->pdf->Cell(0, 4, 'Terima kasih telah berbelanja!', 0, 1, 'C');
        $this->pdf->Cell(0, 4, 'Barang yang sudah dibeli tidak dapat dikembalikan.', 0, 1, 'C');
    }
}