<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);   // jangan tampilkan error ke output (biar JSON gak rusak)
ini_set('log_errors', 1);       // tapi catat ke error log PHP/Apache, bisa dicek manual kalau perlu
require_once $_SERVER['DOCUMENT_ROOT'] . '/CardHaven/connection.php';
header('Content-Type: application/json');

function jsonOut(bool $success, string $message = '', array $data = []): void {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

$actor_id = (int)($_POST['actor_id'] ?? $_GET['actor_id'] ?? 0);
if (!$actor_id) {
    http_response_code(401);
    jsonOut(false, 'You must be logged in.');
}
$stmtActor = sqlsrv_query($conn, "SELECT role FROM pengguna WHERE id_pengguna = ? AND is_deleted = 0 AND status_akun = 1", [$actor_id]);
$actor = sqlsrv_fetch_array($stmtActor, SQLSRV_FETCH_ASSOC);
if (!$actor) {
    http_response_code(403);
    jsonOut(false, 'Invalid user or account inactive.');
}
$role    = (int)$actor['role'];
$id_user = $actor_id;
if ($role === 0) {
    http_response_code(403);
    jsonOut(false, 'Access denied.');
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

        $where  = "WHERE 1=1";
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
                      WHERE r.id_restok = ?";
        $stmtH = sqlsrv_query($conn, $sqlHeader, [$id]);
        $header = sqlsrv_fetch_array($stmtH, SQLSRV_FETCH_ASSOC);
        if (!$header) jsonOut(false, 'Data not found.');

        if ($header['tanggal_restok'] instanceof DateTime)
            $header['tanggal_restok'] = $header['tanggal_restok']->format('d M Y');
        if ($header['modified_date'] instanceof DateTime)
            $header['modified_date'] = $header['modified_date']->format('d M Y H:i');

        // Items
        $sqlItems = "SELECT pr.nama_produk, dr.jumlah_barang, dr.harga_beli, dr.subtotal_harga
                     FROM detail_restok dr
                     LEFT JOIN produk pr ON pr.id_produk = dr.id_produk
                     WHERE dr.id_restok = ?";
        $stmtI = sqlsrv_query($conn, $sqlItems, [$id]);
        $items = [];
        while ($item = sqlsrv_fetch_array($stmtI, SQLSRV_FETCH_ASSOC)) {
            $items[] = $item;
        }

        jsonOut(true, '', [
            'header'      => $header,
            'items'       => $items,
            'can_approve' => ($role === 3) ? 1 : 0,
        ]);

    // ─── APPROVE ─────────────────────────────────────────────────────────
    case 'approve':
        if ($role !== 3) jsonOut(false, 'Only Superadmin can approve a Purchase Order.');
        $id = (int)($_POST['id_restok'] ?? 0);
        $by = $id_user;
        if (!$id) jsonOut(false, 'Invalid ID.');

        // Cek status masih pending (0)
        $stmtCek = sqlsrv_query($conn, "SELECT status_restok FROM restok WHERE id_restok = ?", [$id]);
        $cek = sqlsrv_fetch_array($stmtCek, SQLSRV_FETCH_ASSOC);
        if (!$cek) jsonOut(false, 'PO not found.');
        if ((int)$cek['status_restok'] !== 0) jsonOut(false, 'PO is no longer pending.');

        $sql  = "UPDATE restok SET status_restok = 1, modified_by = ?, modified_date = GETDATE() WHERE id_restok = ?";
        $stmt = sqlsrv_query($conn, $sql, [$by, $id]);
        if (!$stmt) jsonOut(false, 'Failed to approve PO.');

        jsonOut(true, 'PO has been approved successfully.');

    // ─── REJECT ──────────────────────────────────────────────────────────
    case 'reject':
        if ($role !== 3) jsonOut(false, 'Only Superadmin can reject a Purchase Order.');
        $id = (int)($_POST['id_restok'] ?? 0);
        $by = $id_user;
        if (!$id) jsonOut(false, 'Invalid ID.');

        $stmtCek = sqlsrv_query($conn, "SELECT status_restok FROM restok WHERE id_restok = ?", [$id]);
        $cek = sqlsrv_fetch_array($stmtCek, SQLSRV_FETCH_ASSOC);
        if (!$cek) jsonOut(false, 'PO not found.');
        if ((int)$cek['status_restok'] !== 0) jsonOut(false, 'PO is no longer pending.');

        $sql  = "UPDATE restok SET status_restok = 2, modified_by = ?, modified_date = GETDATE() WHERE id_restok = ?";
        $stmt = sqlsrv_query($conn, $sql, [$by, $id]);
        if (!$stmt) jsonOut(false, 'Failed to reject PO.');

        jsonOut(true, 'PO has been rejected.');

    // ─── SUPPLIER LIST (untuk dropdown form Add PO) ────────────────────────
    case 'getSuppliers':
        $sql  = "SELECT id_supplier, nama_suplier, no_telp FROM supplier WHERE is_deleted = 0 ORDER BY nama_suplier ASC";
        $stmt = sqlsrv_query($conn, $sql, []);
        $rows = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $r;
        jsonOut(true, '', ['rows' => $rows]);

    // ─── PRODUK LIST (untuk dropdown item form Add PO) ─────────────────────
    case 'getProduk':
        $sql  = "SELECT id_produk, nama_produk, harga_beli FROM produk WHERE is_deleted = 0 ORDER BY nama_produk ASC";
        $stmt = sqlsrv_query($conn, $sql, []);
        $rows = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $r;
        jsonOut(true, '', ['rows' => $rows]);

    // ─── CREATE PO BARU ─────────────────────────────────────────────────────
    case 'create':
        if ($role !== 2) jsonOut(false, 'Only Superadmin can create a Purchase Order.');
        $id_supplier = (int)($_POST['id_supplier'] ?? 0);
        $by          = $id_user;
        $itemsJson   = $_POST['items'] ?? '';
        $items       = json_decode($itemsJson, true);

        if (!$id_supplier) jsonOut(false, 'Supplier is required.');
        if (!is_array($items) || count($items) === 0) jsonOut(false, 'At least one item is required.');

        // Validasi & hitung total
        $totalBarang = 0;
        $totalHarga  = 0;
        $cleanItems  = [];
        foreach ($items as $it) {
            $id_produk = (int)($it['id_produk'] ?? 0);
            $jumlah    = (int)($it['jumlah_barang'] ?? 0);
            $harga     = (float)($it['harga_beli'] ?? 0);
            if (!$id_produk || $jumlah < 1 || $harga <= 0) {
                jsonOut(false, 'Each item must have a valid product, quantity (min 1), and price.');
            }
            $subtotal     = $jumlah * $harga;
            $totalBarang += $jumlah;
            $totalHarga  += $subtotal;
            $cleanItems[] = [$id_produk, $jumlah, $harga, $subtotal];
        }

        sqlsrv_begin_transaction($conn);

        try {
            // Insert header — pakai OUTPUT supaya id_restok langsung didapat dari statement yang sama
            // (lebih reliable daripada SCOPE_IDENTITY() di query terpisah, apalagi kalau ada trigger di tabel restok)
            $sqlHeader = "INSERT INTO restok (id_supplier, tanggal_restok, total_barang, total_harga, status_restok, created_by, created_date)
                          OUTPUT INSERTED.id_restok
                          VALUES (?, GETDATE(), ?, ?, 0, ?, GETDATE())";
            $stmtHeader = sqlsrv_query($conn, $sqlHeader, [$id_supplier, $totalBarang, $totalHarga, $by]);
            if (!$stmtHeader) {
                $errors = sqlsrv_errors();
                $detail = $errors ? $errors[0]['message'] : 'unknown error';
                throw new Exception('Failed to create PO header: ' . $detail);
            }

            $idRestok = (int)(sqlsrv_fetch_array($stmtHeader, SQLSRV_FETCH_ASSOC)['id_restok'] ?? 0);
            if (!$idRestok) {
                $errors = sqlsrv_errors();
                $detail = $errors ? $errors[0]['message'] : 'no rows returned from OUTPUT';
                throw new Exception('Failed to retrieve new PO ID: ' . $detail);
            }

            // Insert detail items
            $sqlDetail = "INSERT INTO detail_restok (id_restok, id_produk, jumlah_barang, harga_beli, subtotal_harga)
                          VALUES (?, ?, ?, ?, ?)";
            foreach ($cleanItems as $ci) {
                $stmtDetail = sqlsrv_query($conn, $sqlDetail, [$idRestok, $ci[0], $ci[1], $ci[2], $ci[3]]);
                if (!$stmtDetail) {
                    $errors = sqlsrv_errors();
                    $detail = $errors ? $errors[0]['message'] : 'unknown error';
                    throw new Exception('Failed to add PO item: ' . $detail);
                }
            }

            sqlsrv_commit($conn);
            jsonOut(true, 'PO created successfully.', ['id_restok' => $idRestok]);

        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            jsonOut(false, $e->getMessage());
        }

    default:
        jsonOut(false, 'Unknown action.');
}