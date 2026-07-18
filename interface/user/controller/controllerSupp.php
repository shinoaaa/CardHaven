<?php
require_once __DIR__ . '/../../../auth/session.php';
auth_session_start();
require_once __DIR__ . '/../../../connection.php';

function jsonOut(bool $success, string $message = '', array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $_REQUEST['action'] ?? $body['action'] ?? '';

if ($action !== '') {
    try {
        // Manajemen supplier ada di menu User — hanya untuk Owner.
        auth_api_require_role([ROLE_OWNER]);

        // Pelaku aksi (jejak audit) diambil dari session, bukan dari user_id
        // kiriman JS yang bisa diganti.
        $userId = auth_id();

        switch ($action) {
            case 'getSupplier':
                $id = (int)($_GET['id'] ?? 0);
                if (!$id) jsonOut(false, 'Invalid ID.');
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetSupplierDetail(?)}", [$id]);
                if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    foreach ($row as $key => $val) {
                        if ($val instanceof DateTime) {
                            $row[$key] = $val->format('d M Y, H:i');
                        }
                    }
                    jsonOut(true, '', $row);
                }
                jsonOut(false, 'Supplier not found.');
                break;

            case 'addSupplier':
                $nama   = trim($_POST['nama_suplier'] ?? '');
                $email  = trim($_POST['email'] ?? '');
                $telp   = trim($_POST['no_telp'] ?? '');
                $alamat = trim($_POST['alamat'] ?? '');

                $resName = sqlsrv_query($conn, "SELECT 1 FROM dbo.supplier WHERE nama_suplier = ? AND is_deleted = 0", [$nama]);
                if (sqlsrv_has_rows($resName)) {
                    jsonOut(false, "Supplier name '$nama' is already registered.");
                }

                $resEmail = sqlsrv_query($conn, "SELECT 1 FROM dbo.supplier WHERE email = ? AND is_deleted = 0", [$email]);
                if (sqlsrv_has_rows($resEmail)) {
                    jsonOut(false, "Email address '$email' is already used by another supplier.");
                }

                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageSupplier('add', 0, ?, ?, ?, ?, 1, ?)}", 
                    [$nama, $email, $telp, $alamat, $userId]);
                
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'New supplier added successfully.');
                break;

            case 'updateSupplier':
                $id     = (int)$_POST['id_supplier'];
                $nama   = trim($_POST['nama_suplier'] ?? '');
                $email  = trim($_POST['email'] ?? '');
                $telp   = trim($_POST['no_telp'] ?? '');
                $alamat = trim($_POST['alamat'] ?? '');

                // Validasi Nama Unik (Update - Kecuali diri sendiri)
                $resName = sqlsrv_query($conn, "SELECT 1 FROM dbo.supplier WHERE nama_suplier = ? AND is_deleted = 0 AND id_supplier <> ?", [$nama, $id]);
                if (sqlsrv_has_rows($resName)) {
                    jsonOut(false, "Another supplier is already using the name '$nama'.");
                }

                // Validasi Email Unik (Update - Kecuali diri sendiri)
                $resEmail = sqlsrv_query($conn, "SELECT 1 FROM dbo.supplier WHERE email = ? AND is_deleted = 0 AND id_supplier <> ?", [$email, $id]);
                if (sqlsrv_has_rows($resEmail)) {
                    jsonOut(false, "Email '$email' is already used by another supplier.");
                }

                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageSupplier('edit', ?, ?, ?, ?, ?, 1, ?)}", 
                    [$id, $nama, $email, $telp, $alamat, $userId]);
                
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Supplier updated successfully.');
                break;

            case 'deleteSupplier':
                $id = (int)($body['id_supplier'] ?? $_POST['id_supplier'] ?? 0);
                
                // ISSUE 4: Cek apakah supplier masih digunakan di data produk
                $sqlRelasi = "SELECT COUNT(*) as total FROM dbo.produk WHERE id_supplier = ? AND is_deleted = 0";
                $stmtRel = sqlsrv_query($conn, $sqlRelasi, [$id]);
                $rowRel = sqlsrv_fetch_array($stmtRel, SQLSRV_FETCH_ASSOC);
                
                if ($rowRel && (int)$rowRel['total'] > 0) {
                    jsonOut(false, "Cannot delete: This supplier is still used by " . $rowRel['total'] . " active product(s).");
                }

                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageSupplier('delete', ?, '', '', '', '', 0, ?)}", [$id, $userId]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Supplier deleted successfully.');
                break;

            case 'toggleSupplier':
                $id = (int)($body['id_supplier'] ?? 0);
                $aktif = (int)($body['aktif'] ?? 0);
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageSupplier('toggle_status', ?, '', '', '', '', ?, ?)}", 
                    [$id, $aktif, $userId]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Status updated successfully.');
        }
    } catch (Throwable $e) {
        jsonOut(false, $e->getMessage());
    }
}else if (isset($_GET['list'])) {
    $limit  = 7;
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $search = trim($_GET['search'] ?? '');
    $status = ($_GET['status'] === '' || !isset($_GET['status'])) ? -1 : (int)$_GET['status'];
    $sortBy  = $_GET['sort_by'] ?? 'id_supplier';
    $sortDir = strtoupper($_GET['sort_order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetSupplierList(?, ?, ?, ?, ?, ?)}", 
        [$search, $sortBy, $sortDir, $status, $page, $limit]);
    
    $data = [];
    $total_rows = 0;

    if ($stmt) {
        if ($rowTotal = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $total_rows = (int)($rowTotal['total_data'] ?? 0);
        }
        sqlsrv_next_result($stmt);
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($r as $key => $val) {
                if ($val instanceof DateTime) $r[$key] = $val->format('d M Y');
            }
            $data[] = $r;
        }
    }

    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status'       => 'success',
        'data'         => $data,
        'total_pages'  => max(1, ceil($total_rows / $limit)),
        'current_page' => $page
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>