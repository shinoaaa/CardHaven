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
$sort = strtoupper($_GET['sort'] ?? 'DESC');

// FUNGSI HELPER: Menyaring dan Mengurutkan Data PHP (Meniru JS)
function getFilteredAndSortedData($conn, $tahun, $bulan, $search, $sort) {
    $sqlData = "SELECT * FROM dbo.udf_LaporanBuyback(?, ?)";
    $stmtData = sqlsrv_query($conn, $sqlData, [$tahun, $bulan]);
    $data = [];

    while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
        // Bulletproof Date Validation di sisi PHP
        $rowYear = ($row['tanggal_pembelian'] instanceof DateTime) ? (int)$row['tanggal_pembelian']->format('Y') : 0;
        $rowMonth = ($row['tanggal_pembelian'] instanceof DateTime) ? (int)$row['tanggal_pembelian']->format('n') : 0;

        if ($tahun !== 0 && $rowYear !== $tahun) continue;
        if ($bulan !== 0 && $rowMonth !== $bulan) continue;

        $tglStr = ($row['tanggal_pembelian'] instanceof DateTime) ? $row['tanggal_pembelian']->format('d-m-Y') : '';
        
        if ($search !== '') {
            $match = false;
            if (stripos((string)$row['nama_customer'], $search) !== false) $match = true;
            if (stripos((string)$row['daftar_kartu'], $search) !== false) $match = true;
            if (stripos((string)$row['total_harga'], $search) !== false) $match = true;
            if (stripos((string)$row['no_resi'], $search) !== false) $match = true;
            if (stripos($tglStr, $search) !== false) $match = true;
            
            if (!$match) continue;
        }
        $data[] = $row;
    }

    usort($data, function($a, $b) use ($sort) {
        $t1 = ($a['tanggal_pembelian'] instanceof DateTime) ? $a['tanggal_pembelian']->getTimestamp() : 0;
        $t2 = ($b['tanggal_pembelian'] instanceof DateTime) ? $b['tanggal_pembelian']->getTimestamp() : 0;
        return $sort === 'DESC' ? $t2 - $t1 : $t1 - $t2;
    });

    return $data;
}

switch ($action) {
    case 'get_data':
        header('Content-Type: application/json');
        try {
            // Cukup lempar semua data mentah untuk bulan/tahun ke JS
            $sqlData = "SELECT * FROM dbo.udf_LaporanBuyback(?, ?)";
            $stmtData = sqlsrv_query($conn, $sqlData, [$tahun, $bulan]);

            $data = [];
            while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
                if ($row['tanggal_pembelian'] instanceof DateTime) {
                    $row['tanggal_pembelian'] = $row['tanggal_pembelian']->format('Y-m-d H:i:s');
                }
                $data[] = $row;
            }
            echo json_encode(["status" => "success", "data" => $data]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "System Error."]);
        }
        break;

    case 'export_excel':
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Buyback_Report.xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        $data = getFilteredAndSortedData($conn, $tahun, $bulan, $search, $sort);
        
        echo "<table border='1'>";
        echo "<tr><th>No</th><th>Tanggal</th><th>Customer</th><th>Daftar Kartu</th><th>Total Pcs</th><th>Total Harga</th></tr>";
        
        $no = 1;
        foreach ($data as $row) {
            $tgl = ($row['tanggal_pembelian'] instanceof DateTime) ? $row['tanggal_pembelian']->format('d-m-Y') : '-';
            echo "<tr>";
            echo "<td>" . $no++ . "</td>";
            echo "<td>" . $tgl . "</td>";
            echo "<td>" . $row['nama_customer'] . "</td>";
            echo "<td>" . $row['daftar_kartu'] . "</td>";
            echo "<td>" . $row['total_barang'] . "</td>";
            echo "<td>Rp " . number_format($row['total_harga'], 0, ',', '.') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        break;

    case 'export_pdf':
        require_once('/cardhaven/TCPDF-main/tcpdf.php');
        $data = getFilteredAndSortedData($conn, $tahun, $bulan, $search, $sort);
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Laporan Buyback Completed');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        $html = '<h2 style="text-align:center;">Completed Buyback Transaction Report</h2><hr><br/>';
        $html .= '<table border="1" cellpadding="5">
                    <tr style="background-color:#f2f2f2; font-weight:bold; text-align:center;">
                        <th width="5%">No</th>
                        <th width="12%">Date</th>
                        <th width="18%">Customer</th>
                        <th width="40%">Cards Purchased</th>
                        <th width="10%">Total Item</th>
                        <th width="15%">Nominal (Rp)</th>
                    </tr>';
        
        $no = 1;
        foreach ($data as $row) {
            $tgl = ($row['tanggal_pembelian'] instanceof DateTime) ? $row['tanggal_pembelian']->format('d-m-Y') : '-';
            $html .= '<tr>
                        <td align="center">'.$no++.'</td>
                        <td align="center">'.$tgl.'</td>
                        <td>'.$row['nama_customer'].'</td>
                        <td>'.$row['daftar_kartu'].'</td>
                        <td align="center">'.$row['total_barang'].'</td>
                        <td align="right">'.number_format($row['total_harga'], 0, ',', '.').'</td>
                    </tr>';
        }
        $html .= '</table>';

        $tanggalSekarang = date('Y-m-d'); 
        $namaFile = 'Laporan_Buyback_' . $tanggalSekarang . '.pdf';
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Laporan_Buyback.pdf', 'I');
        break;
}
?>