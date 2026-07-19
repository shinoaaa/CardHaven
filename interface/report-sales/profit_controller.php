<?php
// Endpoint untuk tab Profit di halaman Report (khusus Owner).
//  - action=summary      : ringkasan bulanan revenue / modal (COGS) / gross profit
//                          + pengeluaran restock & buyback, untuk 1 tahun (0 = semua). JSON.
//  - action=export_excel : unduh ringkasan bulanan sebagai .xls
//  - action=export_pdf   : unduh ringkasan bulanan sebagai .pdf (TCPDF + kop CardHaven)
//
// Definisi angka mengikuti konvensi report lain agar konsisten:
//  - Penjualan dihitung hanya status_penjualan = 6 (sama dengan udf_LaporanSales)
//  - Restock  dihitung hanya status_restok    = 4 (Paid, sama dengan udf_LaporanPembelian)
//  - Buyback  dihitung hanya status_pembelian = 8 (sama dengan udf_LaporanBuyback)
// Query ditulis inline (parameterized) di sini — bukan UDF — supaya ikut ter-
// version-control di git dan tidak hilang kalau database di-restore.
ini_set('display_errors', 0);
error_reporting(0);
require_once $_SERVER['DOCUMENT_ROOT'] . '/CardHaven/connection.php';
require_once __DIR__ . '/../../auth/session.php';

const PROFIT_MONTHS_EN = ['January','February','March','April','May','June',
                          'July','August','September','October','November','December'];

// Hitung ringkasan profit untuk 1 tahun (0 = semua tahun). Dipakai baik oleh
// action=summary (JSON) maupun export Excel/PDF supaya angkanya selalu konsisten.
function profitCompute($conn, $tahun) {
    // 12 bulan kosong sebagai kerangka
    $months = [];
    for ($m = 1; $m <= 12; $m++) {
        $months[$m] = ['bulan' => $m, 'revenue' => 0.0, 'cogs' => 0.0, 'profit' => 0.0,
                       'items_sold' => 0, 'orders' => 0,
                       'restok_spend' => 0.0, 'buyback_spend' => 0.0];
    }

    // ── Revenue + modal produk (COGS) + volume per bulan ────────────
    $sqlSales = "
        SELECT MONTH(pj.tanggal_penjualan) AS bulan,
               SUM(dp.subtotal_harga)                          AS revenue,
               SUM(ISNULL(p.harga_beli, 0) * dp.jumlah_barang) AS cogs,
               SUM(dp.jumlah_barang)                           AS items_sold,
               COUNT(DISTINCT pj.id_penjualan)                 AS orders
        FROM dbo.penjualan pj
        JOIN dbo.detail_penjualan dp ON dp.id_penjualan = pj.id_penjualan
        JOIN dbo.produk p            ON p.id_produk     = dp.id_produk
        WHERE pj.status_penjualan = 6
          AND (? = 0 OR YEAR(pj.tanggal_penjualan) = ?)
        GROUP BY MONTH(pj.tanggal_penjualan)
    ";
    $stmt = sqlsrv_query($conn, $sqlSales, [$tahun, $tahun]);
    if ($stmt === false) throw new Exception('Failed to load sales data.');
    while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $b = (int)$r['bulan'];
        if ($b >= 1 && $b <= 12) {
            $months[$b]['revenue']    = (float)$r['revenue'];
            $months[$b]['cogs']       = (float)$r['cogs'];
            $months[$b]['profit']     = (float)$r['revenue'] - (float)$r['cogs'];
            $months[$b]['items_sold'] = (int)$r['items_sold'];
            $months[$b]['orders']     = (int)$r['orders'];
        }
    }
    sqlsrv_free_stmt($stmt);

    // ── Pengeluaran restock (Paid) per bulan ────────────────────────
    $sqlRestok = "
        SELECT MONTH(tanggal_restok) AS bulan, SUM(total_harga) AS spend
        FROM dbo.restok
        WHERE status_restok = 4
          AND (? = 0 OR YEAR(tanggal_restok) = ?)
        GROUP BY MONTH(tanggal_restok)
    ";
    $stmt = sqlsrv_query($conn, $sqlRestok, [$tahun, $tahun]);
    if ($stmt === false) throw new Exception('Failed to load restock data.');
    while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $b = (int)$r['bulan'];
        if ($b >= 1 && $b <= 12) $months[$b]['restok_spend'] = (float)$r['spend'];
    }
    sqlsrv_free_stmt($stmt);

    // ── Pengeluaran buyback per bulan ───────────────────────────────
    $sqlBuyback = "
        SELECT MONTH(tanggal_pembelian) AS bulan, SUM(total_harga) AS spend
        FROM dbo.pembelian_kartu
        WHERE status_pembelian = 8
          AND (? = 0 OR YEAR(tanggal_pembelian) = ?)
        GROUP BY MONTH(tanggal_pembelian)
    ";
    $stmt = sqlsrv_query($conn, $sqlBuyback, [$tahun, $tahun]);
    if ($stmt === false) throw new Exception('Failed to load buyback data.');
    while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $b = (int)$r['bulan'];
        if ($b >= 1 && $b <= 12) $months[$b]['buyback_spend'] = (float)$r['spend'];
    }
    sqlsrv_free_stmt($stmt);

    // ── Daftar tahun yang punya data (untuk dropdown filter) ────────
    $sqlYears = "
        SELECT DISTINCT YEAR(tanggal_penjualan) AS th FROM dbo.penjualan WHERE status_penjualan = 6
        UNION SELECT DISTINCT YEAR(tanggal_restok)    FROM dbo.restok          WHERE status_restok = 4
        UNION SELECT DISTINCT YEAR(tanggal_pembelian) FROM dbo.pembelian_kartu WHERE status_pembelian = 8
        ORDER BY th DESC
    ";
    $years = [];
    $stmt = sqlsrv_query($conn, $sqlYears);
    if ($stmt !== false) {
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $years[] = (int)$r['th'];
        sqlsrv_free_stmt($stmt);
    }

    // Total setahun
    $tot = ['revenue' => 0.0, 'cogs' => 0.0, 'profit' => 0.0, 'items_sold' => 0, 'orders' => 0,
            'restok_spend' => 0.0, 'buyback_spend' => 0.0];
    foreach ($months as $m) {
        foreach ($tot as $k => $v) $tot[$k] += $m[$k];
    }

    return ['years' => $years, 'months' => array_values($months), 'total' => $tot];
}

function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

ob_start();

try {
    if (!isset($conn) || $conn === false) throw new Exception('Invalid database connection.');

    // Profit hanya boleh dilihat Owner. Role dari session, bukan dari URL.
    auth_api_require_role([ROLE_OWNER]);

    $action = $_GET['action'] ?? '';
    $tahun  = (int)($_GET['tahun'] ?? date('Y'));

    if ($action === 'summary') {
        $d = profitCompute($conn, $tahun);
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'tahun'  => $tahun,
            'years'  => $d['years'],
            'months' => $d['months'],
            'total'  => $d['total']
        ]);
        exit;
    }

    if ($action === 'export_excel') {
        $d = profitCompute($conn, $tahun);
        $t = $d['total'];
        $margin = $t['revenue'] > 0 ? ($t['profit'] / $t['revenue']) * 100 : 0;
        $labelTahun = $tahun === 0 ? 'All Years' : (string)$tahun;

        if (ob_get_length()) ob_clean();
        $tanggalSekarang = date('d-m-Y');
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Profit_{$tanggalSekarang}.xls");
        header("Pragma: no-cache"); header("Expires: 0");

        // Bagian Kop Laporan
        echo "<table style='font-family: sans-serif;'>";
        echo "<tr><td colspan='6' style='font-size:18pt; font-weight:bold; color:#0F3891; text-align:center;'>CardHaven</td></tr>";
        echo "<tr><td colspan='6' style='font-weight:bold; font-size:14pt; text-align:center;'>Profit Report</td></tr>";
        echo "<tr><td colspan='6' style='text-align:center; color:#666;'>Year: {$labelTahun} &bull; Generated on: " . date('d-m-Y H:i') . "</td></tr>";
        echo "<tr><td colspan='6' style='text-align:center; color:#666;'>Margin: " . number_format($margin, 1) . "% &bull; Items Sold: " . number_format($t['items_sold'], 0, ',', '.') . " Pcs &bull; Completed Orders: " . number_format($t['orders'], 0, ',', '.') . "</td></tr>";
        echo "<tr><td colspan='6'></td></tr>";
        echo "</table>";

        // Bagian Tabel Data
        echo "<table border='1' style='font-family: sans-serif; border-collapse: collapse;'>";
        echo "<tr style='background-color:#0F3891; color:#ffffff; font-weight:bold; text-align:center;'>";
        echo "<th>Month</th><th>Revenue</th><th>Product Cost</th><th>Gross Profit</th><th>Restock Spend</th><th>Buyback Spend</th>";
        echo "</tr>";

        $no = 1;
        foreach ($d['months'] as $m) {
            $bgColor = ($no % 2 == 0) ? '#dee8fc' : '#ffffff';
            $no++;
            echo "<tr style='background-color: {$bgColor};'>";
            echo "<td><b>" . PROFIT_MONTHS_EN[$m['bulan'] - 1] . "</b></td>";
            echo "<td style='text-align:right;'>" . rupiah($m['revenue']) . "</td>";
            echo "<td style='text-align:right;'>" . rupiah($m['cogs']) . "</td>";
            echo "<td style='text-align:right;'>" . rupiah($m['profit']) . "</td>";
            echo "<td style='text-align:right;'>" . rupiah($m['restok_spend']) . "</td>";
            echo "<td style='text-align:right;'>" . rupiah($m['buyback_spend']) . "</td>";
            echo "</tr>";
        }

        // Bagian Grand Total
        echo "<tr style='background-color:#f2f2f2; font-weight:bold;'>";
        echo "<td style='text-align:right;'>GRAND TOTAL</td>";
        echo "<td style='text-align:right;'>" . rupiah($t['revenue']) . "</td>";
        echo "<td style='text-align:right;'>" . rupiah($t['cogs']) . "</td>";
        echo "<td style='text-align:right;'>" . rupiah($t['profit']) . "</td>";
        echo "<td style='text-align:right;'>" . rupiah($t['restok_spend']) . "</td>";
        echo "<td style='text-align:right;'>" . rupiah($t['buyback_spend']) . "</td>";
        echo "</tr>";
        echo "</table>";
        exit;
    }

    if ($action === 'export_pdf') {
        $tcpdf_path = __DIR__ . '/../../TCPDF-main/tcpdf.php';
        if (file_exists($tcpdf_path)) {
            require_once($tcpdf_path);
        } else {
            die("Error: TCPDF file not found at " . $tcpdf_path);
        }

        $d = profitCompute($conn, $tahun);
        $t = $d['total'];
        $margin = $t['revenue'] > 0 ? ($t['profit'] / $t['revenue']) * 100 : 0;
        $labelTahun = $tahun === 0 ? 'All Years' : (string)$tahun;

        if (ob_get_length()) ob_end_clean();

        // Inisialisasi TCPDF Landscape (L) + kop & footer standar CardHaven
        require_once __DIR__ . '/../report_pdf.php';
        $pdf = new CardHavenPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Profit Report');
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        $pdf->setFooterFont(Array('helvetica', '', 9));
        $pdf->SetFooterMargin(10);
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetAutoPageBreak(TRUE, 20);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        // Header Judul Laporan di dalam PDF
        $html  = '<h2 style="text-align:center; color:#0F3891; margin-bottom:0;">Profit Report</h2>';
        $html .= '<p style="text-align:center; font-size:9px; color:#666;">Year: ' . $labelTahun . ' &bull; Generated on: ' . date('d-m-Y H:i') . '</p>';
        $html .= '<p style="text-align:center; font-size:9px; color:#666;">Margin: ' . number_format($margin, 1) . '% &bull; Items Sold: ' . number_format($t['items_sold'], 0, ',', '.') . ' Pcs &bull; Completed Orders: ' . number_format($t['orders'], 0, ',', '.') . '</p><br/>';

        // THEAD dipakai agar header tabel otomatis berulang di halaman baru
        $html .= '<table border="1" cellpadding="6" style="border-collapse:collapse; border:1px solid #7491ca; width:100%;">
                    <thead>
                        <tr style="background-color:#0F3891; color:#ffffff; font-weight:bold; text-align:center;">
                            <th width="16%">Month</th>
                            <th width="18%">Revenue</th>
                            <th width="17%">Product Cost</th>
                            <th width="17%">Gross Profit</th>
                            <th width="16%">Restock Spend</th>
                            <th width="16%">Buyback Spend</th>
                        </tr>
                    </thead>
                    <tbody>';

        $no = 1;
        foreach ($d['months'] as $m) {
            $bgColor = ($no % 2 == 0) ? '#dee8fc' : '#ffffff';
            $no++;
            $profit = $m['profit'] < 0
                ? '<font color="#e74c3c">' . rupiah($m['profit']) . '</font>'
                : rupiah($m['profit']);
            $html .= '<tr bgcolor="' . $bgColor . '" nobr="true">
                        <td width="16%"><b>' . PROFIT_MONTHS_EN[$m['bulan'] - 1] . '</b></td>
                        <td width="18%" align="right">' . rupiah($m['revenue']) . '</td>
                        <td width="17%" align="right">' . rupiah($m['cogs']) . '</td>
                        <td width="17%" align="right">' . $profit . '</td>
                        <td width="16%" align="right">' . rupiah($m['restok_spend']) . '</td>
                        <td width="16%" align="right">' . rupiah($m['buyback_spend']) . '</td>
                      </tr>';
        }

        $profitTotal = $t['profit'] < 0
            ? '<font color="#e74c3c">' . rupiah($t['profit']) . '</font>'
            : rupiah($t['profit']);
        $html .= '<tr style="background-color:#f2f2f2; font-weight:bold;" nobr="true">
                    <td align="right">GRAND TOTAL</td>
                    <td align="right">' . rupiah($t['revenue']) . '</td>
                    <td align="right">' . rupiah($t['cogs']) . '</td>
                    <td align="right">' . $profitTotal . '</td>
                    <td align="right">' . rupiah($t['restok_spend']) . '</td>
                    <td align="right">' . rupiah($t['buyback_spend']) . '</td>
                  </tr>';
        $html .= '</tbody></table>';

        // Penamaan file menggunakan tanggal dinamis (sama seperti report lain)
        $tanggalSekarang = date('d-m-Y');
        $namaFile = 'Laporan_Profit_' . $tanggalSekarang . '.pdf';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($namaFile, 'I');
        exit;
    }

    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
    exit;

} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
