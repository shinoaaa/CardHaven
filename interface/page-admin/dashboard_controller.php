<?php
// Endpoint JSON untuk Activity Dashboard (kartu statistik, grouped bar chart, recent activity).
// Semua data diambil lewat SP/UDF (tanpa query inline).
session_start();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/CardHaven/connection.php';

ob_start();

try {
    if (!isset($conn) || $conn === false) {
        throw new Exception('Invalid database connection.');
    }

    $action = $_GET['action'] ?? '';

    // ── Kartu statistik atas ──────────────────────────────────────────────
    if ($action === 'stats') {
        $stmt = sqlsrv_query($conn, '{CALL dbo.sp_GetDashboardStats}');
        if ($stmt === false) throw new Exception('Failed to load stats.');
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ?: [];
        ob_clean();
        echo json_encode([
            'status' => 'success',
            'data'   => [
                'total_sales'     => (float)($row['total_sales'] ?? 0),
                'total_orders'    => (int)($row['total_orders'] ?? 0),
                'total_customers' => (int)($row['total_customers'] ?? 0),
                'out_of_stock'    => (int)($row['out_of_stock'] ?? 0),
            ],
        ]);
        exit;
    }

    // ── Data grouped bar chart: 12 bulan × (sales, buyback, restok) ────────
    if ($action === 'chart') {
        $year = (int)($_GET['year'] ?? date('Y'));
        $stmt = sqlsrv_query($conn, 'SELECT * FROM dbo.udf_DashboardMonthlyTotals(?) ORDER BY bulan', [$year]);
        if ($stmt === false) throw new Exception('Failed to load chart data.');
        $rows = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = [
                'bulan'   => (int)$r['bulan'],
                'sales'   => (float)$r['sales_total'],
                'buyback' => (float)$r['buyback_total'],
                'restok'  => (float)$r['restok_total'],
            ];
        }
        ob_clean();
        echo json_encode(['status' => 'success', 'year' => $year, 'data' => $rows]);
        exit;
    }

    // ── 15 transaksi terbaru gabungan
    if ($action === 'recent') {
        $limit = max(1, (int)($_GET['limit'] ?? 15));
        $stmt = sqlsrv_query($conn, '{CALL dbo.sp_GetRecentActivity(?)}', [$limit]);
        if ($stmt === false) throw new Exception('Failed to load recent activity.');
        $rows = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($r['tanggal'] instanceof DateTime) $r['tanggal'] = $r['tanggal']->format('Y-m-d H:i');
            $rows[] = [
                'jenis'       => $r['jenis'],
                'ref_id'      => (int)$r['ref_id'],
                'pihak'       => $r['pihak'] ?? '-',
                'total_harga' => (float)$r['total_harga'],
                'status_code' => (int)$r['status_code'],
                'tanggal'     => $r['tanggal'],
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
