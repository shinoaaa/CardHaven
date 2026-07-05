<?php
if (session_status() === PHP_SESSION_NONE) session_start();
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
        // PERBAIKAN: Langsung ambil dari parameter yang dilempar oleh fetch JS
        $userId = $body['user_id'] ?? $_POST['user_id'] ?? null;
        $userId = $userId ? (int)$userId : null;

        // Validasi mutasi data wajib menyertakan User ID dari JS
        $mutationActions = ['addSupplier', 'updateSupplier', 'deleteSupplier', 'toggleSupplier'];
        if (in_array($action, $mutationActions) && empty($userId)) {
            jsonOut(false, 'Operation denied: Active User ID is missing from the request.');
        }

        switch ($action) {
            case 'getSupplier':
                $id = (int)($_GET['id'] ?? 0);
                if (!$id) jsonOut(false, 'Invalid ID.');
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetSupplierDetail(?)}", [$id]);
                if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) jsonOut(true, '', $row);
                jsonOut(false, 'Supplier not found.');

            case 'addSupplier':
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageSupplier('add', 0, ?, ?, ?, ?, 1, ?)}", 
                    [
                        trim($_POST['nama_suplier']), 
                        trim($_POST['email']), 
                        trim($_POST['no_telp']), 
                        trim($_POST['alamat']),
                        $userId
                    ]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Supplier saved successfully.');

            case 'updateSupplier':
                $id = (int)$_POST['id_supplier'];
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageSupplier('edit', ?, ?, ?, ?, ?, 1, ?)}", 
                    [
                        $id, 
                        trim($_POST['nama_suplier']), 
                        trim($_POST['email']), 
                        trim($_POST['no_telp']), 
                        trim($_POST['alamat']),
                        $userId
                    ]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Supplier updated successfully.');

            case 'deleteSupplier':
                $id = (int)($body['id_supplier'] ?? 0);
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageSupplier('delete', ?, '', '', '', '', 0, ?)}", 
                    [$id, $userId]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Supplier deleted successfully.');

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
} else {
    $limit  = 7;
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetSupplierList(?, ?)}", [$limit, $offset]);
    
    $data = [];
    $total_pages = 1;
    if ($stmt) {
        $rowTotal = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $total_pages = max(1, ceil(($rowTotal['total_data'] ?? 0) / $limit));
        sqlsrv_next_result($stmt);
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $r;
        }
    }
}
?>