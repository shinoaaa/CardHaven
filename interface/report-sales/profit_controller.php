<?php
// Endpoint JSON untuk tab Profit di halaman Report (khusus Owner).
//  - action=summary : ringkasan bulanan revenue / modal (COGS) / gross profit
//                     + pengeluaran restock & buyback, untuk 1 tahun (0 = semua).
//
// Definisi angka mengikuti konvensi report lain agar konsisten:
//  - Penjualan dihitung hanya status_penjualan = 6 (sama dengan udf_LaporanSales)
//  - Restock  dihitung hanya status_restok    = 4 (Paid, sama dengan udf_LaporanPembelian)
//  - Buyback  dihitung hanya status_pembelian = 8 (sama dengan udf_LaporanBuyback)
// Query ditulis inline (parameterized) di sini — bukan UDF — supaya ikut ter-
// version-control di git dan tidak hilang kalau database di-restore.
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/CardHaven/connection.php';
require_once __DIR__ . '/../../auth/session.php';

ob_start();

try {
    if (!isset($conn) || $conn === false) throw new Exception('Invalid database connection.');

    // Profit hanya boleh dilihat Owner. Role dari session, bukan dari URL.
    auth_api_require_role([ROLE_OWNER]);

    $action = $_GET['action'] ?? '';

    if ($action === 'summary') {
        $tahun = (int)($_GET['tahun'] ?? date('Y'));

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

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'tahun'  => $tahun,
            'years'  => $years,
            'months' => array_values($months),
            'total'  => $tot
        ]);
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
