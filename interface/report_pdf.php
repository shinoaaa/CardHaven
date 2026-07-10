<?php
/**
 * Template PDF laporan CardHaven.
 * Kop (header): logo + nama usaha di setiap halaman.
 * Footer: nama usaha kiri, nomor halaman "Page X of Y" kanan.
 *
 * Cara pakai (di controller laporan, SETELAH require TCPDF):
 *   require_once __DIR__ . '/../report_pdf.php';
 *   $pdf = new CardHavenPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
 *   $pdf->setPrintHeader(true);
 *   $pdf->setPrintFooter(true);
 *   $pdf->SetMargins(15, 30, 15); // top 30 memberi ruang untuk kop
 */
class CardHavenPDF extends TCPDF {

    public function Header() {
        // Logo di kiri kop. Dipakai versi JPEG (bukan PNG transparan) karena
        // TCPDF butuh ekstensi GD/Imagick untuk PNG ber-alpha channel; JPEG
        // dibaca native tanpa ekstensi apa pun, dan kop PDF berlatar putih
        // sehingga hasilnya terlihat sama.
        $logo = __DIR__ . '/../assets/image/logo.jpg';
        if (file_exists($logo)) {
            $this->Image($logo, 15, 6, 17, 0, 'JPG');
        }

        // Nama usaha + tagline di tengah
        $this->SetY(9);
        $this->SetFont('helvetica', 'B', 15);
        $this->SetTextColor(15, 56, 145);
        $this->Cell(0, 7, 'CardHaven', 0, 1, 'C');
        $this->SetFont('helvetica', '', 8.5);
        $this->SetTextColor(110, 110, 110);
        $this->Cell(0, 4, 'Trading Card Game Store', 0, 1, 'C');

        // Garis pemisah kop
        $this->SetLineStyle(['width' => 0.4, 'color' => [15, 56, 145]]);
        $this->Line(15, 25, $this->getPageWidth() - 15, 25);
    }

    public function Footer() {
        $this->SetY(-14);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        // Dua cell transparan bertumpuk: kiri nama usaha, kanan nomor halaman
        $this->SetX(15);
        $this->Cell(0, 8, 'CardHaven - printed ' . date('d-m-Y H:i'), 0, 0, 'L');
        $this->SetX(15);
        $this->Cell(0, 8, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}
