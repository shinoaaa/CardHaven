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
    echo json_encode(['status' => 'error', 'message' => "Database Error: " . $e->getMessage()]);
    exit;
}

function jsonOut(string $status, string $message = '', array $data = []): void {
    ob_clean();
    $payload = ['status' => $status, 'message' => $message];
    if (!empty($data)) {
        $payload['data'] = $data;
    }
    echo json_encode($payload);
    exit;
}

function getSqlError() {
    $errors = sqlsrv_errors();
    return $errors[0]['message'] ?? 'Unknown SQL Server Error';
}

$actor_id = (int)($_POST['actor_id'] ?? $_GET['actor_id'] ?? 0);
if (!$actor_id) {
    http_response_code(401);
    jsonOut('error', 'You must be logged in.');
}

$stmtActor = sqlsrv_query($conn, "SELECT role FROM dbo.pengguna WHERE id_pengguna = ? AND is_deleted = 0 AND status_akun = 1", [$actor_id]);
$actor = sqlsrv_fetch_array($stmtActor, SQLSRV_FETCH_ASSOC);
if (!$actor) {
    http_response_code(403);
    jsonOut('error', 'Invalid user or account inactive.');
}

$role    = (int)$actor['role'];
$id_user = $actor_id;

if ($role === 0 || $role === 1) {
    http_response_code(403);
    jsonOut('error', 'Access denied.');
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ─── LIST (tabel utama, dengan pagination & filter) ───────────────────
    case 'getList':
        $page     = max(1, (int)($_GET['page'] ?? 1));
        // Limit bisa di-override (admin menarik semua data untuk difilter/sort/paginate di client).
        $limit    = max(1, (int)($_GET['limit'] ?? 7));
        $offset   = ($page - 1) * $limit;
        $status   = trim($_GET['status'] ?? '');
        $search   = trim($_GET['search'] ?? '');

        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetRestokList(?, ?, ?, ?)}", [$search, $status, $limit, $offset]);
        
        if ($stmt === false) jsonOut('error', getSqlError());

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

        jsonOut('success', '', [
            'rows'        => $rows,
            'total'       => (int)$totalData,
            'total_pages' => (int)ceil($totalData / $limit),
            'current_page'=> $page,
        ]);

    // ─── DETAIL SATU PO ──────────────────────────────────────────────────
    case 'getDetail':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) jsonOut('error', 'Invalid ID.');

        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetRestokDetail(?)}", [$id]);
        if ($stmt === false) jsonOut('error', getSqlError());

        // Header
        $header = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$header) jsonOut('error', 'Data not found.');

        if ($header['tanggal_restok'] instanceof DateTime) $header['tanggal_restok'] = $header['tanggal_restok']->format('d M Y');
        if ($header['modified_date'] instanceof DateTime) $header['modified_date'] = $header['modified_date']->format('d M Y H:i');

        // Items
        sqlsrv_next_result($stmt);
        $items = [];
        while ($item = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $items[] = $item;
        }

        jsonOut('success', '', [
            'header'      => $header,
            'items'       => $items,
            // Manager (role 2) menjalankan lifecycle PO; Owner (role 3) view-only.
            'can_approve' => ($role === 2) ? 1 : 0,
            'can_receive' => ($role === 2) ? 1 : 0,
            'can_pay'     => ($role === 2) ? 1 : 0,
        ]);

    // ─── APPROVE ─────────────────────────────────────────────────────────
    case 'approve':
        if ($role !== 2) jsonOut('error', 'Only Manager can approve a Purchase Order.');
        $id = (int)($_POST['id_restok'] ?? 0);
        if (!$id) jsonOut('error', 'Invalid ID.');

        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UpdateRestokStatus(?, 1, ?)}", [$id, $id_user]);
        if ($stmt === false) jsonOut('error', getSqlError());

        jsonOut('success', 'PO has been approved successfully.');

    // ─── REJECT ──────────────────────────────────────────────────────────
    case 'reject':
        if ($role !== 2) jsonOut('error', 'Only Manager can reject a Purchase Order.');
        $id = (int)($_POST['id_restok'] ?? 0);
        if (!$id) jsonOut('error', 'Invalid ID.');

        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UpdateRestokStatus(?, 2, ?)}", [$id, $id_user]);
        if ($stmt === false) jsonOut('error', getSqlError());

        jsonOut('success', 'PO has been rejected.');

    // ─── RECEIVE (barang sudah dicek fisik) ────────────────────────────────
    case 'receive':
        if ($role !== 2) jsonOut('error', 'Only Manager can mark a PO as received.');
        $id = (int)($_POST['id_restok'] ?? 0);
        if (!$id) jsonOut('error', 'Invalid ID.');

        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UpdateRestokStatus(?, 3, ?)}", [$id, $id_user]);
        if ($stmt === false) jsonOut('error', getSqlError());

        jsonOut('success', 'PO marked as received.');

    // ─── PAY (stok bertambah otomatis via trigger) ──────────────────────────
    case 'pay':
        if ($role !== 2) jsonOut('error', 'Only Manager can mark a PO as paid.');
        $id = (int)($_POST['id_restok'] ?? 0);
        if (!$id) jsonOut('error', 'Invalid ID.');

        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UpdateRestokStatus(?, 4, ?)}", [$id, $id_user]);
        if ($stmt === false) jsonOut('error', getSqlError());

        jsonOut('success', 'PO marked as paid. Stock has been updated.');

    // ─── SEARCH SUPPLIER (autocomplete, mirip search_game) ──────────────────
    case 'search_supplier':
        $keyword = trim($_GET['search_supplier'] ?? '');
        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetDropdownSupplier(?)}", [$keyword]);
        $res = [];
        if ($stmt) while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $res[] = $r;
        ob_clean();
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    // ─── SEARCH PRODUK (autocomplete, mirip search_set) ──────────────────────
    case 'search_produk':
    $keyword     = trim($_GET['search_produk'] ?? '');
    $id_supplier = (int)($_GET['id_supplier'] ?? 0);
    if (!$id_supplier) {
        ob_clean();
        echo json_encode([]);
        exit;
    }
    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetDropdownProdukRestok(?, ?)}", [$keyword, $id_supplier]);
    $res = [];
    if ($stmt) while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $res[] = $r;
    ob_clean();
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;

    // ─── CREATE PO BARU ─────────────────────────────────────────────────────
    case 'create':
        if ($role !== 2) jsonOut('error', 'Only Superadmin can create a Purchase Order.');
        $id_supplier = (int)($_POST['id_supplier'] ?? 0);
        $itemsJson   = $_POST['items'] ?? '';
        $items       = json_decode($itemsJson, true);

        if (!$id_supplier) jsonOut('error', 'Supplier is required.');
        if (!is_array($items) || count($items) === 0) jsonOut('error', 'At least one item is required.');

        $totalBarang = 0;
        $totalHarga  = 0;
        $cleanItems  = [];

        foreach ($items as $it) {
            $id_produk = (int)($it['id_produk'] ?? 0);
            $jumlah    = (int)($it['jumlah_barang'] ?? 0);
            $harga     = (float)($it['harga_beli'] ?? 0);

            if (!$id_produk || $jumlah < 1 || $harga <= 0) {
                jsonOut('error', 'Each item must have a valid product, quantity (min 1), and price.');
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

        if ($stmt === false) jsonOut('error', getSqlError());

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $idRestok = $row['id_restok'] ?? 0;

        jsonOut('success', 'PO created successfully.', ['id_restok' => $idRestok]);

    default:
        jsonOut('error', 'Unknown action.');
}
?>