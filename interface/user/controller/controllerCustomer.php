<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../connection.php';

function jsonOut(bool $success, string $message = '', array $data = [], string $code = ''): void {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data, 'code' => $code]);
    exit;
}

$action = $_REQUEST['action'] ?? '';
$role = 0; 

if ($action !== '') {
    try {
        switch ($action) {
            case 'addCustomer':
                $pw = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('add', 0, ?, ?, ?, ?, ?)}", 
                    [$role, trim($_POST['username']), trim($_POST['email']), trim($_POST['no_telepon']), $pw]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                $successMsg = 'Customer created successfully.';
                break; // biarkan turun ke blok upload foto

            case 'updateCustomer':
                $id = (int)$_POST['id_pengguna'];
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('edit', ?, ?, ?, ?, ?, '')}", 
                    [$id, $role, trim($_POST['username']), trim($_POST['email']), trim($_POST['no_telepon'])]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                
                if (!empty($_POST['password'])) {
                    $pw = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('change_password', ?, ?, '', '', '', ?)}", [$id, $role, $pw]);
                }
                $successMsg = 'Customer updated successfully.';
                break;
            case 'getCustomer':
                $id = (int)($_GET['id'] ?? 0);
                if (!$id) jsonOut(false, 'Invalid Customer ID.');
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetPenggunaDetail(?, ?)}", [$id, $role]);
                if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) jsonOut(true, '', $row);
                jsonOut(false, 'Customer not found.');

            case 'deleteCustomer':
                $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
                $id = (int)($body['id_pengguna'] ?? 0);
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('delete', ?, ?, '', '', '', '')}", [$id, $role]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Customer deleted successfully.');

            case 'toggleCustomer':
                $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
                $id = (int)($body['id_pengguna'] ?? 0);
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('toggle_status', ?, ?, '', '', '', '')}", [$id, $role]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Status toggled successfully.');

            case 'changePasswordCustomer':
                $id = (int)$_POST['id_pengguna'];
                $pw = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('change_password', ?, ?, '', '', '', ?)}", [$id, $role, $pw]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Customer password updated.');
        }
    } catch (Throwable $e) {
        jsonOut(false, $e->getMessage());
    }
} else {
    $limit  = 7;
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetCustomerList(?, ?)}", [$limit, $offset]);
    
    $data = [];
    $total_pages = 1;
    if ($stmt) {
        $rowTotal = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $total_pages = max(1, ceil(($rowTotal['total_data'] ?? 0) / $limit));
        sqlsrv_next_result($stmt);
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $r['shopping_total'] = (float)$r['shopping_total'];
            $r['shopping_amount'] = (int)$r['shopping_amount'];
            $data[] = $r;
        }
    }
}
?>