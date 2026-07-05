<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../connection.php';

function jsonOut(bool $success, string $message = '', array $data = [], string $code = ''): void {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data, 'code' => $code]);
    exit;
}

$action = $_REQUEST['action'] ?? '';
$role = 2; 

if ($action !== '') {
    try {
        switch ($action) {
            case 'getAdmin':
                $id = (int)($_GET['id'] ?? 0);
                if (!$id) jsonOut(false, 'Invalid Super Admin ID.');
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetPenggunaDetail(?, ?)}", [$id, $role]);
                if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) jsonOut(true, '', $row);
                jsonOut(false, 'Super Admin not found.');

            case 'addAdmin':
                $pw = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('add', 0, ?, ?, ?, ?, ?)}", 
                    [$role, trim($_POST['username']), trim($_POST['email']), trim($_POST['no_telepon']), $pw]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Super Admin created successfully.');

            case 'updateAdmin':
                $id = (int)$_POST['id_pengguna'];
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('edit', ?, ?, ?, ?, ?, '')}", 
                    [$id, $role, trim($_POST['username']), trim($_POST['email']), trim($_POST['no_telepon'])]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Super Admin updated successfully.');

            case 'deleteAdmin':
                $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
                $id = (int)($body['id_pengguna'] ?? 0);
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('delete', ?, ?, '', '', '', '')}", [$id, $role]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Super Admin deleted successfully.');

            case 'toggleAdmin':
                $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
                $id = (int)($body['id_pengguna'] ?? 0);
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('toggle_status', ?, ?, '', '', '', '')}", [$id, $role]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Status toggled successfully.');

            case 'changePasswordAdmin':
                $id = (int)$_POST['id_pengguna'];
                $pw = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('change_password', ?, ?, '', '', '', ?)}", [$id, $role, $pw]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Password updated successfully.');
        }
    } catch (Throwable $e) {
        jsonOut(false, $e->getMessage());
    }
} else {
    $limit  = 7;
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetPenggunaList(?, ?, ?)}", [$role, $limit, $offset]);
    
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