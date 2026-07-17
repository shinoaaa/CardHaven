<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once '../../connection.php';
require_once __DIR__ . '/../../auth/session.php';

$action = $_GET['action'] ?? '';

// Laporan Event: Manager (2) & Owner (3). Role diambil dari session,
// bukan dari ?role= di URL yang bisa dipalsukan.
auth_api_require_role([ROLE_MANAGER, ROLE_OWNER]);

$tahun = (int)($_GET['tahun'] ?? 0);
$bulan = (int)($_GET['bulan'] ?? 0);
$search = trim(strtolower($_GET['search'] ?? ''));
$sortBy = strtoupper($_GET['sort_by'] ?? 'DATE');
$sortOrder = strtoupper($_GET['sort_order'] ?? 'DESC');

function getFilteredAndSortedData($conn, $tahun, $bulan, $search, $sortBy, $sortOrder) {
    $sqlData = "SELECT * FROM dbo.udf_LaporanEvent(?, ?)";
    $stmtData = sqlsrv_query($conn, $sqlData, [$tahun, $bulan]);
    $data = [];

    while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
        $rowYear = ($row['tanggal_mulai'] instanceof DateTime) ? (int)$row['tanggal_mulai']->format('Y') : 0;
        $rowMonth = ($row['tanggal_mulai'] instanceof DateTime) ? (int)$row['tanggal_mulai']->format('n') : 0;

        if ($tahun !== 0 && $rowYear !== $tahun) continue;
        if ($bulan !== 0 && $rowMonth !== $bulan) continue;

        $tglStr = ($row['tanggal_mulai'] instanceof DateTime) ? $row['tanggal_mulai']->format('d-m-Y') : '';
        if ($search !== '') {
            $match = false;
            if (stripos((string)$row['nama_event'], $search) !== false) $match = true;
            if (stripos((string)$row['tipe_event'], $search) !== false) $match = true;
            if (stripos((string)$row['daftar_produk'], $search) !== false) $match = true;
            if (stripos((string)$row['total_harga'], $search) !== false) $match = true;
            if (stripos($tglStr, $search) !== false) $match = true;

            if (!$match) continue;
        }
        $data[] = $row;
    }

    usort($data, function($a, $b) use ($sortBy, $sortOrder) {
        if ($sortBy === 'DATE') {
            $t1 = ($a['tanggal_mulai'] instanceof DateTime) ? $a['tanggal_mulai']->getTimestamp() : 0;
            $t2 = ($b['tanggal_mulai'] instanceof DateTime) ? $b['tanggal_mulai']->getTimestamp() : 0;
            return $sortOrder === 'DESC' ? $t2 - $t1 : $t1 - $t2;
        } elseif ($sortBy === 'QTY') {
            $q1 = (int)$a['total_barang']; $q2 = (int)$b['total_barang'];
            if ($q1 == $q2) return 0;
            return $sortOrder === 'DESC' ? (($q1 < $q2) ? 1 : -1) : (($q1 < $q2) ? -1 : 1);
        } else { // PRICE / REVENUE
            $p1 = (float)$a['total_harga']; $p2 = (float)$b['total_harga'];
            if ($p1 == $p2) return 0;
            return $sortOrder === 'DESC' ? (($p1 < $p2) ? 1 : -1) : (($p1 < $p2) ? -1 : 1);
        }
    });

    return $data;
}

switch ($action) {
    case 'get_data':
        header('Content-Type: application/json');
        try {
            $sqlData = "SELECT * FROM dbo.udf_LaporanEvent(?, ?)";
            $stmtData = sqlsrv_query($conn, $sqlData, [$tahun, $bulan]);
            $data = [];
            while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
                if ($row['tanggal_mulai'] instanceof DateTime) {
                    $row['tanggal_mulai'] = $row['tanggal_mulai']->format('Y-m-d H:i:s');
                }
                if ($row['tanggal_berakhir'] instanceof DateTime) {
                    $row['tanggal_berakhir'] = $row['tanggal_berakhir']->format('Y-m-d H:i:s');
                }
                $data[] = $row;
            }
            echo json_encode(["status" => "success", "data" => $data]);
        } catch (Exception $e) { echo json_encode(["status" => "error", "message" => "System Error."]); }
        break;

    case 'get_detail':
        header('Content-Type: application/json');
        try {
            $id = (int)($_GET['id'] ?? 0);
            $sql = "SELECT * FROM dbo.udf_GetDetailProdukEvent(?)";
            $stmt = sqlsrv_query($conn, $sql, [$id]);
            if ($stmt === false) die(json_encode(["status" => "error", "message" => "Database Error", "debug" => sqlsrv_errors()]));

            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $data[] = $row;
            echo json_encode(["status" => "success", "data" => $data]);
        } catch (Exception $e) { echo json_encode(["status" => "error", "message" => $e->getMessage()]); }
        break;

    case 'export_excel':
        header("Content-Type: application/vnd.ms-excel");
        $tanggalSekarang = date('d-m-Y');
        header("Content-Disposition: attachment; filename=Laporan_Event_{$tanggalSekarang}.xls");
        header("Pragma: no-cache"); header("Expires: 0");

        $data = getFilteredAndSortedData($conn, $tahun, $bulan, $search, $sortBy, $sortOrder);

        // Bagian Kop Laporan
        echo "<table style='font-family: sans-serif;'>";
        echo "<tr><td colspan='7' style='font-size:18pt; font-weight:bold; color:#0F3891; text-align:center;'>CardHaven</td></tr>";
        echo "<tr><td colspan='7' style='font-weight:bold; font-size:14pt; text-align:center;'>Event Performance Report</td></tr>";
        echo "<tr><td colspan='7' style='text-align:center; color:#666;'>Generated on: " . date('d-m-Y H:i') . "</td></tr>";
        echo "<tr><td colspan='7'></td></tr>";
        echo "</table>";
        
        // Bagian Tabel Data
        echo "<table border='1' style='font-family: sans-serif; border-collapse: collapse;'>";
        echo "<tr style='background-color:#0F3891; color:#ffffff; font-weight:bold; text-align:center;'>";
        echo "<th>No</th><th>Event Name</th><th>Period</th><th>Type</th><th>Discount</th><th>Items Sold</th><th>Revenue</th>";
        echo "</tr>";

        $no = 1;
        $totalQty = 0;
        $totalNominal = 0;
        
        foreach ($data as $row) {
            $tglMulai = ($row['tanggal_mulai'] instanceof DateTime) ? $row['tanggal_mulai']->format('d-m-Y') : '-';
            $tglAkhir = ($row['tanggal_berakhir'] instanceof DateTime) ? $row['tanggal_berakhir']->format('d-m-Y') : '-';
            $totalQty += (int)$row['total_barang'];
            $totalNominal += (float)$row['total_harga'];
            
            $bgColor = ($no % 2 == 0) ? '#dee8fc' : '#ffffff';
            
            echo "<tr style='background-color: {$bgColor};'>";
            echo "<td style='text-align:center;'>" . $no++ . "</td>";
            echo "<td><b>" . htmlspecialchars($row['nama_event']) . "</b></td>";
            echo "<td style='text-align:center;'>" . $tglMulai . " - " . $tglAkhir . "</td>";
            echo "<td style='text-align:center;'>" . ucfirst($row['tipe_event']) . "</td>";
            echo "<td style='text-align:center;'>" . number_format((float)$row['persen_diskon'], 0) . "%</td>";
            echo "<td style='text-align:center;'>" . $row['total_barang'] . " Pcs</td>";
            echo "<td style='text-align:right;'>Rp " . number_format((float)$row['total_harga'], 0, ',', '.') . "</td>";
            echo "</tr>";
        }
        
        // Bagian Grand Total
        echo "<tr style='background-color:#f2f2f2; font-weight:bold;'>";
        echo "<td colspan='5' style='text-align:right;'>GRAND TOTAL</td>";
        echo "<td style='text-align:center;'>" . number_format($totalQty, 0, ',', '.') . " Pcs</td>";
        echo "<td style='text-align:right;'>Rp " . number_format($totalNominal, 0, ',', '.') . "</td>";
        echo "</tr>";
        
        echo "</table>";
        break;

    case 'export_pdf':
        $tcpdf_path = __DIR__ . '/../../TCPDF-main/tcpdf.php';
        if (file_exists($tcpdf_path)) require_once($tcpdf_path); else die("Error: TCPDF missing.");

        if (ob_get_length()) ob_end_clean();
        $data = getFilteredAndSortedData($conn, $tahun, $bulan, $search, $sortBy, $sortOrder);

        require_once __DIR__ . '/../report_pdf.php'; // kop & footer standar CardHaven
        $pdf = new CardHavenPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Event Report');
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        $pdf->setFooterFont(Array('helvetica', '', 9));
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetAutoPageBreak(TRUE, 20);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        $html = '<h2 style="text-align:center; color:#0F3891; margin-bottom:0;">Event Performance Report</h2>';
        $html .= '<p style="text-align:center; font-size:9px; color:#666;">Generated on: ' . date('d-m-Y H:i') . '</p><br/>';

        $html .= '<table border="1" cellpadding="6" style="border-collapse:collapse; border:1px solid #7491ca; width:100%;">
                    <thead>
                        <tr style="background-color:#0F3891; color:#ffffff; font-weight:bold; text-align:center;">
                            <th width="5%">No</th>
                            <th width="23%">Event Name</th>
                            <th width="20%">Period</th>
                            <th width="12%">Type / Disc</th>
                            <th width="12%">Items Sold</th>
                            <th width="18%">Revenue (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>';

        $no = 1; $totalQty = 0; $totalNominal = 0;
        foreach ($data as $row) {
            $tglMulai = ($row['tanggal_mulai'] instanceof DateTime) ? $row['tanggal_mulai']->format('d-m-Y') : '-';
            $tglAkhir = ($row['tanggal_berakhir'] instanceof DateTime) ? $row['tanggal_berakhir']->format('d-m-Y') : '-';
            $totalQty += (int)$row['total_barang'];
            $totalNominal += (float)$row['total_harga'];
            $bgColor = ($no % 2 == 0) ? '#dee8fc' : '#ffffff';

            $html .= '<tr bgcolor="'.$bgColor.'" nobr="true">
                        <td width="5%" align="center">'.$no++.'</td>
                        <td width="23%"><b>'.htmlspecialchars($row['nama_event']).'</b></td>
                        <td width="20%" align="center" style="white-space:nowrap;">'.$tglMulai.' - '.$tglAkhir.'</td>
                        <td width="12%" align="center">'.ucfirst($row['tipe_event']).' ('.number_format((float)$row['persen_diskon'], 0).'%)</td>
                        <td width="12%" align="center">'.$row['total_barang'].' Pcs</td>
                        <td align="right">Rp '.number_format((float)$row['total_harga'], 0, ',', '.').'</td>
                    </tr>';
        }
        $html .= '<tr style="background-color:#f2f2f2; font-weight:bold;" nobr="true">
                    <td colspan="5" align="right">GRAND TOTAL</td>
                    <td align="center">'.number_format($totalQty, 0, ',', '.').'</td>
                    <td align="right">Rp '.number_format($totalNominal, 0, ',', '.').'</td>
                  </tr></tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Laporan_Event_' . date('d-m-Y') . '.pdf', 'I');
        break;
}
