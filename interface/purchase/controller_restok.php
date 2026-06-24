<?php
session_start();
require_once '../../connection.php';
header('Content-Type: application/json');

function jsonOut(bool $success, string $message = '', array $data = []): void {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ─── LIST (tabel utama, dengan pagination & filter) ───────────────────
    case 'getList':
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $limit    = 7;
        $offset   = ($page - 1) * $limit;
        $status   = $_GET['status'] ?? '';   // 'pending','approved','rejected',''
        $search   = trim($_GET['search'] ?? '');

        $where  = "WHERE r.is_deleted = 0";
        $params = [];

        if ($status !== '') {
            $where   .= " AND r.status_restok = ?";
            $params[] = (int)$status;
        }
        if ($search !== '') {
            $where   .= " AND (s.nama_suplier LIKE ? OR r.id_restok LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        // Total rows
        $sqlCount = "SELECT COUNT(*) AS total
                     FROM restok r
                     LEFT JOIN supplier s ON s.id_supplier = r.id_supplier
                     $where";
        $stmtCount = sqlsrv_query($conn, $sqlCount, $params ?: []);
        $total     = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC)['total'] ?? 0;

        // Data
        $paramsPage   = array_merge($params, [$limit, $offset]);
        $sqlData = "SELECT r.id_restok, r.tanggal_restok, r.total_barang,
                           r.total_harga, r.status_restok,
                           s.nama_suplier,
                           p.username AS created_by_name
                    FROM restok r
                    LEFT JOIN supplier s ON s.id_supplier = r.id_supplier
                    LEFT JOIN pengguna p ON p.id_pengguna = r.created_by
                    $where
                    ORDER BY r.tanggal_restok DESC
                    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

        // SQL Server OFFSET harus integer, tukar urutan
        $paramsPage = array_merge($params, [$offset, $limit]);
        $stmtData   = sqlsrv_query($conn, $sqlData, $paramsPage ?: []);

        $rows = [];
        while ($row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC)) {
            if ($row['tanggal_restok'] instanceof DateTime) {
                $row['tanggal_restok'] = $row['tanggal_restok']->format('d-m-Y');
            }
            $rows[] = $row;
        }

        jsonOut(true, '', [
            'rows'        => $rows,
            'total'       => (int)$total,
            'total_pages' => (int)ceil($total / $limit),
            'current_page'=> $page,
        ]);

    // ─── DETAIL satu PO ──────────────────────────────────────────────────
    case 'getDetail':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) jsonOut(false, 'Invalid ID.');

        // Header
        $sqlHeader = "SELECT r.id_restok, r.tanggal_restok, r.total_barang,
                             r.total_harga, r.status_restok,
                             s.nama_suplier, s.no_telp AS telp_suplier,
                             p.username AS created_by_name,
                             pa.username AS approved_by_name,
                             r.modified_date
                      FROM restok r
                      LEFT JOIN supplier s ON s.id_supplier = r.id_supplier
                      LEFT JOIN pengguna p ON p.id_pengguna = r.created_by
                      LEFT JOIN pengguna pa ON pa.id_pengguna = r.modified_by
                      WHERE r.id_restok = ? AND r.is_deleted = 0";
        $stmtH = sqlsrv_query($conn, $sqlHeader, [$id]);
        $header = sqlsrv_fetch_array($stmtH, SQLSRV_FETCH_ASSOC);
        if (!$header) jsonOut(false, 'Data not found.');

        if ($header['tanggal_restok'] instanceof DateTime)
            $header['tanggal_restok'] = $header['tanggal_restok']->format('d M Y');
        if ($header['modified_date'] instanceof DateTime)
            $header['modified_date'] = $header['modified_date']->format('d M Y H:i');

        // Items
        $sqlItems = "SELECT pr.nama_produk, dr.jumlah, dr.harga_satuan,
                            (dr.jumlah * dr.harga_satuan) AS subtotal
                     FROM detail_restok dr
                     LEFT JOIN produk pr ON pr.id_produk = dr.id_produk
                     WHERE dr.id_restok = ?";
        $stmtI = sqlsrv_query($conn, $sqlItems, [$id]);
        $items = [];
        while ($item = sqlsrv_fetch_array($stmtI, SQLSRV_FETCH_ASSOC)) {
            $items[] = $item;
        }

        jsonOut(true, '', ['header' => $header, 'items' => $items]);

    // ─── APPROVE ─────────────────────────────────────────────────────────
    case 'approve':
        $id      = (int)($_POST['id_restok'] ?? 0);
        $by      = (int)($_SESSION['id_pengguna'] ?? 0);
        if (!$id) jsonOut(false, 'Invalid ID.');

        // Cek status masih pending (0)
        $stmtCek = sqlsrv_query($conn, "SELECT status_restok FROM restok WHERE id_restok = ? AND is_deleted = 0", [$id]);
        $cek = sqlsrv_fetch_array($stmtCek, SQLSRV_FETCH_ASSOC);
        if (!$cek) jsonOut(false, 'PO not found.');
        if ((int)$cek['status_restok'] !== 0) jsonOut(false, 'PO is no longer pending.');

        $sql  = "UPDATE restok SET status_restok = 1, modified_by = ?, modified_date = GETDATE() WHERE id_restok = ?";
        $stmt = sqlsrv_query($conn, $sql, [$by, $id]);
        if (!$stmt) jsonOut(false, 'Failed to approve PO.');

        jsonOut(true, 'PO has been approved successfully.');

    // ─── REJECT ──────────────────────────────────────────────────────────
    case 'reject':
        $id      = (int)($_POST['id_restok'] ?? 0);
        $by      = (int)($_SESSION['id_pengguna'] ?? 0);
        if (!$id) jsonOut(false, 'Invalid ID.');

        $stmtCek = sqlsrv_query($conn, "SELECT status_restok FROM restok WHERE id_restok = ? AND is_deleted = 0", [$id]);
        $cek = sqlsrv_fetch_array($stmtCek, SQLSRV_FETCH_ASSOC);
        if (!$cek) jsonOut(false, 'PO not found.');
        if ((int)$cek['status_restok'] !== 0) jsonOut(false, 'PO is no longer pending.');

        $sql  = "UPDATE restok SET status_restok = 2, modified_by = ?, modified_date = GETDATE() WHERE id_restok = ?";
        $stmt = sqlsrv_query($conn, $sql, [$by, $id]);
        if (!$stmt) jsonOut(false, 'Failed to reject PO.');

        jsonOut(true, 'PO has been rejected.');

    default:
        jsonOut(false, 'Unknown action.');
}