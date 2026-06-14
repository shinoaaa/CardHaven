<?php
require __DIR__ . '/../../../connection.php';

header('Content-Type: application/json');

function jsonOut(bool $success, string $message = '', array $data = [], string $code = ''): void
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'code'    => $code,
        'data'    => $data,
    ]);
    exit;
}

function emailExists($conn, string $email, int $excludeId = 0): bool
{
    $sql    = "SELECT COUNT(*) AS cnt FROM supplier WHERE email = ? AND is_deleted = 0 AND id_supplier <> ?";
    $stmt   = sqlsrv_query($conn, $sql, [$email, $excludeId]);
    if (!$stmt) return false;
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return (int)($row['cnt'] ?? 0) > 0;
}

// -------------------------------------------------------
//  Route
// -------------------------------------------------------

$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ----------------------------------------------------
    //  GET single supplier (for detail & edit modals)
    // ----------------------------------------------------
    case 'getSupplier':
        $id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) jsonOut(false, 'Invalid supplier ID.');

        $sql  = "SELECT id_supplier, nama_suplier, email, no_telp, alamat, aktif,
                        CONVERT(varchar, created_date, 105) AS created_date
                 FROM supplier
                 WHERE id_supplier = ? AND is_deleted = 0";
        $stmt = sqlsrv_query($conn, $sql, [$id]);

        if (!$stmt || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
            jsonOut(false, 'Supplier not found.');
        }

        jsonOut(true, 'OK', $row);
        break;

    // ----------------------------------------------------
    //  ADD supplier
    // ----------------------------------------------------
    case 'addSupplier':
        $nama  = trim($_POST['nama_suplier'] ?? '');
        $email = trim($_POST['email']        ?? '');
        $telp  = trim($_POST['no_telp']      ?? '');
        $alamat= trim($_POST['alamat']        ?? '');

        // Server-side required check
        if (!$nama || !$email || !$telp || !$alamat) {
            jsonOut(false, 'All fields are required.');
        }

        // Email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonOut(false, 'Invalid email address format.');
        }

        // Email uniqueness
        if (emailExists($conn, $email)) {
            jsonOut(false, 'Email address is already in use by another supplier.', [], 'EMAIL_DUPLICATE');
        }

        $sql  = "INSERT INTO supplier (nama_suplier, email, no_telp, alamat, aktif,created_by, is_deleted, created_date)
                 VALUES (?, ?, ?, ?,1, 1, 0, GETDATE())";
        $stmt = sqlsrv_query($conn, $sql, [$nama, $email, $telp, $alamat]);

        if (!$stmt) {
            die(print_r(sqlsrv_errors(), true));
        }

        jsonOut(true, 'Supplier added successfully.');
        break;

    // ----------------------------------------------------
    //  UPDATE supplier
    // ----------------------------------------------------
    case 'updateSupplier':
        $id    = (int)($_POST['id_supplier']  ?? 0);
        $nama  = trim($_POST['nama_suplier']  ?? '');
        $email = trim($_POST['email']          ?? '');
        $telp  = trim($_POST['no_telp']        ?? '');
        $alamat= trim($_POST['alamat']         ?? '');

        if (!$id || !$nama || !$email || !$telp || !$alamat) {
            jsonOut(false, 'All fields are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonOut(false, 'Invalid email address format.');
        }

        // Email uniqueness — exclude current supplier from check
        if (emailExists($conn, $email, $id)) {
            jsonOut(false, 'This email is already used by another supplier.', [], 'EMAIL_DUPLICATE');
        }

        $sql  = "UPDATE supplier
                 SET nama_suplier = ?, email = ?, no_telp = ?, alamat = ?
                 WHERE id_supplier = ? AND is_deleted = 0";
        $stmt = sqlsrv_query($conn, $sql, [$nama, $email, $telp, $alamat, $id]);

        if (!$stmt) {
            jsonOut(false, 'Failed to update supplier. Please try again.');
        }

        jsonOut(true, 'Supplier updated successfully.');
        break;

    // ----------------------------------------------------
    //  DELETE supplier (soft delete)
    // ----------------------------------------------------
    case 'deleteSupplier':
        $id = (int)($_POST['id_supplier'] ?? 0);
        if (!$id) jsonOut(false, 'Invalid supplier ID.');

        $sql  = "UPDATE supplier SET is_deleted = 1 WHERE id_supplier = ?";
        $stmt = sqlsrv_query($conn, $sql, [$id]);

        if (!$stmt) {
            jsonOut(false, 'Failed to delete supplier. Please try again.');
        }

        jsonOut(true, 'Supplier deleted successfully.');
        break;

    // ----------------------------------------------------
    //  TOGGLE active / inactive
    // ----------------------------------------------------
    case 'toggleSupplier':
        $id    = (int)($_POST['id_supplier'] ?? 0);
        $aktif = (int)($_POST['aktif']       ?? 0);

        if (!$id) jsonOut(false, 'Invalid supplier ID.');
        $aktif = $aktif === 1 ? 1 : 0;

        $sql  = "UPDATE supplier SET aktif = ? WHERE id_supplier = ? AND is_deleted = 0";
        $stmt = sqlsrv_query($conn, $sql, [$aktif, $id]);

        if (!$stmt) {
            jsonOut(false, 'Failed to update supplier status.');
        }

        jsonOut(true, 'Status updated successfully.');
        break;

    // ----------------------------------------------------
    //  Pagination list (original controller logic)
    // ----------------------------------------------------
    default:
        $limit  = 7;
        $page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        $countSql  = "SELECT COUNT(*) AS total FROM supplier WHERE is_deleted = 0";
        $countStmt = sqlsrv_query($conn, $countSql);
        $countRow  = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
        $total_rows = $countRow['total'];
        $total_pages = ceil($total_rows / $limit);

        $sql  = "
            SELECT *
            FROM supplier
            WHERE is_deleted = 0
            ORDER BY id_supplier DESC
            OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
        ";
        $stmt = sqlsrv_query($conn, $sql, [$offset, $limit]);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $data = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }
        break;
}