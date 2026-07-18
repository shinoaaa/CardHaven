<?php
// Endpoint JSON untuk kartu analitik di halaman Report:
//  - action=monthly  : total per bulan (Rp + qty) untuk chart, per jenis laporan
//  - action=top_selling : top N produk terjual (khusus tab Sales)
// Data lewat UDF (tanpa query inline).
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/CardHaven/connection.php';
require_once __DIR__ . '/../../auth/session.php';

ob_start();

try {
    if (!isset($conn) || $conn === false) throw new Exception('Invalid database connection.');

    // Halaman Report memang owner-only; izinkan role 2 & 3 (konsisten dengan controller laporan lainnya).
    // Role diambil dari session, bukan dari ?role= di URL yang bisa dipalsukan.
    auth_api_require_role([ROLE_MANAGER, ROLE_OWNER]);

    $action = $_GET['action'] ?? '';

    // ── Total bulanan (12 bulan) untuk grouped bar chart ──────────────────
    if ($action === 'monthly') {
        $type  = $_GET['type'] ?? 'sales';
        $tahun = (int)($_GET['tahun'] ?? date('Y'));
        $fnMap = [
            'sales'   => 'udf_LaporanSalesMonthly',
            'buyback' => 'udf_LaporanBuybackMonthly',
            'restok'  => 'udf_LaporanRestokMonthly',
            'event'   => 'udf_LaporanEventMonthly',
        ];
        if (!isset($fnMap[$type])) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Invalid report type.']);
            exit;
        }

        $stmt = sqlsrv_query($conn, "SELECT * FROM dbo.{$fnMap[$type]}(?) ORDER BY bulan", [$tahun]);
        if ($stmt === false) throw new Exception('Failed to load chart data.');

        // Lengkapi jadi 12 bulan (yang kosong = 0).
        $months = [];
        for ($m = 1; $m <= 12; $m++) $months[$m] = ['total_harga' => 0.0, 'total_qty' => 0];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $bln = (int)$r['bulan'];
            if ($bln >= 1 && $bln <= 12) {
                $months[$bln] = ['total_harga' => (float)$r['total_harga'], 'total_qty' => (int)$r['total_qty']];
            }
        }
        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $data[] = ['bulan' => $m, 'total_harga' => $months[$m]['total_harga'], 'total_qty' => $months[$m]['total_qty']];
        }

        ob_clean();
        echo json_encode(['status' => 'success', 'type' => $type, 'tahun' => $tahun, 'data' => $data]);
        exit;
    }

    // ── Top selling items (Sales) ─────────────────────────────────────────
    if ($action === 'top_selling') {
        $tahun = (int)($_GET['tahun'] ?? 0);
        $bulan = (int)($_GET['bulan'] ?? 0);
        $limit = max(1, (int)($_GET['limit'] ?? 3));

        $stmt = sqlsrv_query($conn, 'SELECT * FROM dbo.udf_TopSellingProduk(?, ?, ?)', [$tahun, $bulan, $limit]);
        if ($stmt === false) throw new Exception('Failed to load top selling data.');

        $rows = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = [
                'nama_produk' => $r['nama_produk'],
                'foto'        => $r['foto'],
                'tipe_produk' => $r['tipe_produk'],
                'total_qty'   => (int)$r['total_qty'],
                'total_harga' => (float)$r['total_harga'],
            ];
        }

        ob_clean();
        echo json_encode(['status' => 'success', 'data' => $rows]);
        exit;
    }

    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
    exit;

} catch (Throwable $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
