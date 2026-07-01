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

// 2. TIMPA FUNGSI getFilteredAndSortedData DENGAN YANG BARU INI
function getFilteredAndSortedData($conn, $tahun, $bulan, $search, $sortBy, $sortOrder) {
    $sqlData = "SELECT * FROM dbo.udf_LaporanBuyback(?, ?)";
    $stmtData = sqlsrv_query($conn, $sqlData, [$tahun, $bulan]);
    $data = [];

    while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
        // Validasi Bulan & Tahun
        $rowYear = ($row['tanggal_pembelian'] instanceof DateTime) ? (int)$row['tanggal_pembelian']->format('Y') : 0;
        $rowMonth = ($row['tanggal_pembelian'] instanceof DateTime) ? (int)$row['tanggal_pembelian']->format('n') : 0;

        if ($tahun !== 0 && $rowYear !== $tahun) continue;
        if ($bulan !== 0 && $rowMonth !== $bulan) continue;

        // Pencarian Teks Terintegrasi
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

    // Engine Sorting Ganda (Date vs Price)
    usort($data, function($a, $b) use ($sortBy, $sortOrder) {
        if ($sortBy === 'DATE') {
            $t1 = ($a['tanggal_pembelian'] instanceof DateTime) ? $a['tanggal_pembelian']->getTimestamp() : 0;
            $t2 = ($b['tanggal_pembelian'] instanceof DateTime) ? $b['tanggal_pembelian']->getTimestamp() : 0;
            return $sortOrder === 'DESC' ? $t2 - $t1 : $t1 - $t2;
        } else { // PRICE
            $p1 = (float)$a['total_harga'];
            $p2 = (float)$b['total_harga'];
            
            if ($p1 == $p2) return 0;
            if ($sortOrder === 'DESC') {
                return ($p1 < $p2) ? 1 : -1;
            } else {
                return ($p1 < $p2) ? -1 : 1;
            }
        }
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
    case 'get_detail':
        header('Content-Type: application/json');
        try {
            $id = (int)($_GET['id'] ?? 0);
            
            // Eksekusi UDF yang baru saja dibuat
            $sql = "SELECT * FROM dbo.udf_GetDetailKartuBuyback(?)";
            $stmt = sqlsrv_query($conn, $sql, [$id]);
            
            // Validasi jika query ke UDF gagal
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                echo json_encode([
                    "status" => "error", 
                    "message" => "Terjadi kesalahan pada Database.", 
                    "debug" => $errors // Ini akan membantu melacak penyebab pasti error
                ]);
                exit;
            }
            
            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }
            
            echo json_encode(["status" => "success", "data" => $data]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Gagal memuat detail: " . $e->getMessage()]);
        }
        break;
    case 'export_excel':
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Buyback_Report.xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        $data = getFilteredAndSortedData($conn, $tahun, $bulan, $search, $sortBy, $sortOrder);
        
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
        $tcpdf_path = __DIR__ . '/../../TCPDF-main/tcpdf.php';
        
        if (file_exists($tcpdf_path)) {
            require_once($tcpdf_path);
        } else {
            die("Error: File TCPDF tidak ditemukan di " . $tcpdf_path);
        }

        if (ob_get_length()) ob_end_clean();
        $data = getFilteredAndSortedData($conn, $tahun, $bulan, $search, $sortBy, $sortOrder);
        
        // Inisialisasi TCPDF Landscape (L)
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Pengaturan Dokumen
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Buyback Report');
        $pdf->setPrintHeader(false); // Header kustom nonaktif
        
        // --- PERBAIKAN MULTI-PAGE: Aktifkan Footer & Penomoran Halaman ---
        $pdf->setPrintFooter(true); 
        $pdf->setFooterFont(Array('helvetica', '', 9));
        $pdf->SetFooterMargin(10);
        
        // Atur Margin dan Auto Page Break agar tidak menabrak batas kertas
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        // Header Judul Laporan di dalam PDF
        $html = '<h2 style="text-align:center; color:#0F3891; margin-bottom:0;">Buyback Transaction Report</h2>';
        $html .= '<p style="text-align:center; font-size:9px; color:#666;">Generated on: ' . date('d-m-Y H:i') . '</p><br/>';
        
        // --- PERBAIKAN STRUKTUR TABEL: Menggunakan THEAD agar otomatis berulang di halaman baru ---
        $html .= '<table border="1" cellpadding="6" style="border-collapse:collapse; border:1px solid #7491ca; width:100%;">
                    <thead>
                        <tr style="background-color:#0F3891; color:#ffffff; font-weight:bold; text-align:center;">
                            <th width="5%">No</th>
                            <th width="12%">Date</th>
                            <th width="18%">Customer</th>
                            <th width="40%">Cards Purchased</th>
                            <th width="10%">Items</th>
                            <th width="15%">Paid (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        $no = 1;
        $totalQty = 0;      
        $totalNominal = 0;
        
        foreach ($data as $row) {
            $tgl = ($row['tanggal_pembelian'] instanceof DateTime) ? $row['tanggal_pembelian']->format('d-m-Y') : '-';
            $totalQty += (int)$row['total_barang'];
            $totalNominal += (float)$row['total_harga'];
            
            // Warna baris selang-seling biar mudah dibaca (zebra style) sesuai global.css
            $bgColor = ($no % 2 == 0) ? '#dee8fc' : '#ffffff';
            
            // --- PERBAIKAN NOBR: Mencegah satu baris terpotong split antar halaman ---
            $html .= '<tr bgcolor="'.$bgColor.'" nobr="true">
                        <td width="5%" align="center">'.$no++.'</td>
                        <td width="12%" align="center" style="white-space:nowrap;">'.$tgl.'</td>
                        <td width="18%"><b>'.htmlspecialchars($row['nama_customer']).'</b></td>
                        <td width="40%">'.htmlspecialchars($row['daftar_kartu']).'</td>
                        <td width="10%" align="right">'.$row['total_barang'].' Pcs</td>
                        <td width="15%" align="right">Rp'.number_format($row['total_harga'], 0, ',', '.').'</td>
                    </tr>';
        }
        
        // Baris Grand Total di dalam TBODY
        $html .= '<tr style="background-color:#f2f2f2; font-weight:bold;" nobr="true">
                    <td align="center" colspan="4" align="right">GRAND TOTAL</td>
                    <td align="right">'.number_format($totalQty, 0, ',', '.').' Pcs</td>
                    <td align="right">Rp'.number_format($totalNominal, 0, ',', '.').'</td>
                  </tr>';
                  
        $html .= '</tbody></table>';

        // Penamaan file menggunakan tanggal dinamis
        $tanggalSekarang = date('d-m-Y'); 
        $namaFile = 'Laporan_Buyback_' . $tanggalSekarang . '.pdf';
        
        // Render HTML ke PDF
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Output mode 'I' (View inline tanpa otomatis download)
        $pdf->Output($namaFile, 'I');
        break;
}
?>