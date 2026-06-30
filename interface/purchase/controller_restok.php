<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');

ob_start();

try {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/CardHaven/connection.php';
    if (!isset($conn) || !is_resource($conn)) {
        throw new Exception("Invalid database connection.");
    }
} catch (Throwable $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => "Database Error: " . $e->getMessage()]);
    exit;
}

function jsonOut(bool $success, string $message = '', array $data = []): void {
    ob_clean();
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function getSqlError() {
    $errors = sqlsrv_errors();
    return $errors[0]['message'] ?? 'Unknown SQL Server Error';
}

$actor_id = (int)($_POST['actor_id'] ?? $_GET['actor_id'] ?? 0);
if (!$actor_id) {
    http_response_code(401);
    jsonOut(false, 'You must be logged in.');
}

$stmtActor = sqlsrv_query($conn, "SELECT role FROM dbo.pengguna WHERE id_pengguna = ? AND is_deleted = 0 AND status_akun = 1", [$actor_id]);
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
        $status   = trim($_GET['status'] ?? '');
        $search   = trim($_GET['search'] ?? '');

        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetRestokList(?, ?, ?, ?)}", [$search, $status, $limit, $offset]);
        
        if ($stmt === false) jsonOut(false, getSqlError());

        // ResultSet 1: Total Data
        $rowCount = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $totalData = $rowCount['total_data'] ?? 0;

        // ResultSet 2: Rows
        sqlsrv_next_result($stmt);
        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['tanggal_restok'] instanceof DateTime) {
                $row['tanggal_restok'] = $row['tanggal_restok']->format('d-m-Y');
            }
            $rows[] = $row;
        }

        jsonOut(true, '', [
            'rows'        => $rows,
            'total'       => (int)$totalData,
            'total_pages' => (int)ceil($totalData / $limit),
            'current_page'=> $page,
        ]);

    // ─── DETAIL SATU PO ──────────────────────────────────────────────────
    case 'getDetail':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) jsonOut(false, 'Invalid ID.');

        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetRestokDetail(?)}", [$id]);
        if ($stmt === false) jsonOut(false, getSqlError());

        // Header
        $header = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$header) jsonOut(false, 'Data not found.');

        if ($header['tanggal_restok'] instanceof DateTime) $header['tanggal_restok'] = $header['tanggal_restok']->format('d M Y');
        if ($header['modified_date'] instanceof DateTime) $header['modified_date'] = $header['modified_date']->format('d M Y H:i');

        // Items
        sqlsrv_next_result($stmt);
        $items = [];
        while ($item = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
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
        if (!$id) jsonOut(false, 'Invalid ID.');

        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UpdateRestokStatus(?, 1, ?)}", [$id, $id_user]);
        if ($stmt === false) jsonOut(false, getSqlError());

        jsonOut(true, 'PO has been approved successfully.');

    // ─── REJECT ──────────────────────────────────────────────────────────
    case 'reject':
        if ($role !== 3) jsonOut(false, 'Only Superadmin can reject a Purchase Order.');
        $id = (int)($_POST['id_restok'] ?? 0);
        if (!$id) jsonOut(false, 'Invalid ID.');

        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UpdateRestokStatus(?, 2, ?)}", [$id, $id_user]);
        if ($stmt === false) jsonOut(false, getSqlError());

        jsonOut(true, 'PO has been rejected.');

    // ─── SUPPLIER LIST ────────────────────────
    case 'getSuppliers':
        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetDropdownSupplier}");
        $rows = [];
        if ($stmt) while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $r;
        jsonOut(true, '', ['rows' => $rows]);

    // ─── PRODUK LIST ─────────────────────
    case 'getProduk':
        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetDropdownProdukRestok}");
        $rows = [];
        if ($stmt) while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $r;
        jsonOut(true, '', ['rows' => $rows]);

    // ─── CREATE PO BARU ─────────────────────────────────────────────────────
    case 'create':
        if ($role !== 2) jsonOut(false, 'Only Superadmin can create a Purchase Order.');
        $id_supplier = (int)($_POST['id_supplier'] ?? 0);
        $itemsJson   = $_POST['items'] ?? '';
        $items       = json_decode($itemsJson, true);

        if (!$id_supplier) jsonOut(false, 'Supplier is required.');
        if (!is_array($items) || count($items) === 0) jsonOut(false, 'At least one item is required.');

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
            $cleanItems[] = [
                'id_produk' => $id_produk,
                'jumlah_barang' => $jumlah,
                'harga_beli' => $harga,
                'subtotal_harga' => $subtotal
            ];
        }

        $itemsJsonFinal = json_encode($cleanItems);

        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_CreateRestok(?, ?, ?, ?, ?)}", 
            [$id_supplier, $totalBarang, $totalHarga, $id_user, $itemsJsonFinal]);

        if ($stmt === false) jsonOut(false, getSqlError());

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $idRestok = $row['id_restok'] ?? 0;

        jsonOut(true, 'PO created successfully.', ['id_restok' => $idRestok]);

    default:
        jsonOut(false, 'Unknown action.');
}
?>