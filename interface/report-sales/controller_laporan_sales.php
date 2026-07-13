<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once '../../connection.php'; 

$action = $_GET['action'] ?? '';
$role = (int)($_GET['role'] ?? 0);
if ($role !== 2 && $role !== 3) {
    die(json_encode(["status" => "error", "message" => "Unauthorized access."]));
}

$tahun = (int)($_GET['tahun'] ?? 0);
$bulan = (int)($_GET['bulan'] ?? 0);
$search = trim(strtolower($_GET['search'] ?? ''));
$sortBy = strtoupper($_GET['sort_by'] ?? 'DATE');
$sortOrder = strtoupper($_GET['sort_order'] ?? 'DESC');

function getFilteredAndSortedData($conn, $tahun, $bulan, $search, $sortBy, $sortOrder) {
    $sqlData = "SELECT * FROM dbo.udf_LaporanSales(?, ?)";
    $stmtData = sqlsrv_query($conn, $sqlData, [$tahun, $bulan]);
    $data = [];

    while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
        $rowYear = ($row['tanggal_penjualan'] instanceof DateTime) ? (int)$row['tanggal_penjualan']->format('Y') : 0;
        $rowMonth = ($row['tanggal_penjualan'] instanceof DateTime) ? (int)$row['tanggal_penjualan']->format('n') : 0;

        if ($tahun !== 0 && $rowYear !== $tahun) continue;
        if ($bulan !== 0 && $rowMonth !== $bulan) continue;

        $tglStr = ($row['tanggal_penjualan'] instanceof DateTime) ? $row['tanggal_penjualan']->format('d-m-Y') : '';
        if ($search !== '') {
            $match = false;
            if (stripos((string)$row['nama_customer'], $search) !== false) $match = true;
            if (stripos((string)$row['daftar_produk'], $search) !== false) $match = true;
            if (stripos((string)$row['nama_metode'], $search) !== false) $match = true;
            if (stripos((string)$row['total_harga'], $search) !== false) $match = true;
            if (stripos($tglStr, $search) !== false) $match = true;
            
            if (!$match) continue;
        }
        $data[] = $row;
    }

    usort($data, function($a, $b) use ($sortBy, $sortOrder) {
        if ($sortBy === 'DATE') {
            $t1 = ($a['tanggal_penjualan'] instanceof DateTime) ? $a['tanggal_penjualan']->getTimestamp() : 0;
            $t2 = ($b['tanggal_penjualan'] instanceof DateTime) ? $b['tanggal_penjualan']->getTimestamp() : 0;
            return $sortOrder === 'DESC' ? $t2 - $t1 : $t1 - $t2;
        } else {
            $p1 = (float)$a['total_harga'];
            $p2 = (float)$b['total_harga'];
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
            $sqlData = "SELECT * FROM dbo.udf_LaporanSales(?, ?)";
            $stmtData = sqlsrv_query($conn, $sqlData, [$tahun, $bulan]);
            $data = [];
            while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
                if ($row['tanggal_penjualan'] instanceof DateTime) {
                    $row['tanggal_penjualan'] = $row['tanggal_penjualan']->format('Y-m-d H:i:s');
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
            $sql = "SELECT * FROM dbo.udf_GetDetailProdukSales(?)";
            $stmt = sqlsrv_query($conn, $sql, [$id]);
            if ($stmt === false) die(json_encode(["status" => "error", "message" => "Database Error", "debug" => sqlsrv_errors()]));
            
            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $data[] = $row;
            echo json_encode(["status" => "success", "data" => $data]);
        } catch (Exception $e) { echo json_encode(["status" => "error", "message" => $e->getMessage()]); }
        break;

    case 'export_excel':
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Sales_Report.xls");
        header("Pragma: no-cache"); header("Expires: 0");
        
        $data = getFilteredAndSortedData($conn, $tahun, $bulan, $search, $sortBy, $sortOrder);
        
        echo "<table>";
        echo "<tr><td colspan='7' style='font-size:18pt;font-weight:bold;color:#0F3891;'>CardHaven</td></tr>";
        echo "<tr><td colspan='7' style='font-weight:bold;'>Sales Transaction Report</td></tr>";
        echo "<tr><td colspan='7'>Generated on: " . date('d-m-Y H:i') . "</td></tr>";
        echo "</table>";
        echo "<table border='1'>";
        echo "<tr><th>No</th><th>Date</th><th>Customer</th><th>Product List</th><th>Payment Method</th><th>Total Qty</th><th>Total Sales</th></tr>";
        
        $no = 1;
        foreach ($data as $row) {
            $tgl = ($row['tanggal_penjualan'] instanceof DateTime) ? $row['tanggal_penjualan']->format('d-m-Y') : '-';
            echo "<tr>";
            echo "<td>" . $no++ . "</td>";
            echo "<td>" . $tgl . "</td>";
            echo "<td>" . $row['nama_customer'] . "</td>";
            echo "<td>" . $row['daftar_produk'] . "</td>";
            echo "<td>" . $row['nama_metode'] . "</td>";
            echo "<td>" . $row['total_barang'] . "</td>";
            echo "<td>Rp " . number_format($row['total_harga'], 0, ',', '.') . "</td>";
            echo "</tr>";
        }
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
        $pdf->SetTitle('Sales Report');

        // --- PERBAIKAN MULAI DISINI (Menyamakan dengan Restock) ---
        $pdf->setPrintHeader(true); // Aktifkan Kop Surat
        $pdf->setPrintFooter(true); // Aktifkan Footer
        $pdf->setFooterFont(Array('helvetica', '', 9));
        
        // Margin atas diatur ke 30 agar Kop Surat tidak menabrak isi tabel
        $pdf->SetMargins(15, 30, 15); 
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 20);
        // --- AKHIR PERBAIKAN ---

        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        $html = '<h2 style="text-align:center; color:#0F3891; margin-bottom:0;">Sales Transaction Report</h2>';
        $html .= '<p style="text-align:center; font-size:9px; color:#666;">Generated on: ' . date('d-m-Y H:i') . '</p><br/>';
        
        $html .= '<table border="1" cellpadding="6" style="border-collapse:collapse; border:1px solid #7491ca; width:100%;">
                    <thead>
                        <tr style="background-color:#0F3891; color:#ffffff; font-weight:bold; text-align:center;">
                            <th width="5%">No</th>
                            <th width="12%">Date</th>
                            <th width="15%">Customer</th>
                            <th width="30%">Products</th>
                            <th width="15%">Payment Method</th>
                            <th width="8%">Quantity</th>
                            <th width="15%">Total (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        $no = 1; $totalQty = 0; $totalNominal = 0;
        foreach ($data as $row) {
            $tgl = ($row['tanggal_penjualan'] instanceof DateTime) ? $row['tanggal_penjualan']->format('d-m-Y') : '-';
            $totalQty += (int)$row['total_barang'];
            $totalNominal += (float)$row['total_harga'];
            $bgColor = ($no % 2 == 0) ? '#dee8fc' : '#ffffff';
            
            $html .= '<tr bgcolor="'.$bgColor.'" nobr="true">
                        <td width="5%" align="center">'.$no++.'</td>
                        <td width="12%" align="center" style="white-space:nowrap;">'.$tgl.'</td>
                        <td width="15%"><b>'.htmlspecialchars($row['nama_customer']).'</b></td>
                        <td width="30%">'.htmlspecialchars($row['daftar_produk']).'</td>
                        <td width="15%" align="center">'.$row['nama_metode'].'</td>
                        <td width="8%" align="right">'.$row['total_barang'].'</td>
                        <td width="15%" align="right">Rp '.number_format($row['total_harga'], 0, ',', '.').'</td>
                    </tr>';
        }
        $html .= '<tr style="background-color:#f2f2f2; font-weight:bold;" nobr="true">
                    <td colspan="5" align="right">GRAND TOTAL</td>
                    <td width="8%" align="right">'.number_format($totalQty, 0, ',', '.').'</td>
                    <td width="15%" align="right">Rp '.number_format($totalNominal, 0, ',', '.').'</td>
                </tr></tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Laporan_Sales_' . date('d-m-Y') . '.pdf', 'I');
        break;
}