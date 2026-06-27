<?php
require __DIR__ . '/../../connection.php';

function jsonResponse(array $arr): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

// ==========================================
// MENGAMBIL DATA PENGGUNA
// ==========================================
if ($method === 'GET' && $action === 'get') {
    $id_pengguna = (int)($_GET['id_pengguna'] ?? 0);

    if ($id_pengguna === 0) {
        jsonResponse(["status" => "error", "message" => "User ID not found."]);
    }

    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetAccountSetting(?)}", [$id_pengguna]);

    if ($stmt === false) {
        $errors = sqlsrv_errors();
        jsonResponse(["status" => "error", "message" => $errors[0]['message'] ?? "Failed to retrieve data."]);
    }

    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$user) {
        jsonResponse(["status" => "error", "message" => "Data not found."]);
    }

    jsonResponse([
        "status" => "success",
        "data" => [
            "id_pengguna" => $user['id_pengguna'],
            "username"    => $user['username'],
            "email"       => $user['email'],
            "role"        => $user['role'],
            "foto_profil" => $user['foto_profil'] ?? '/cardhaven/assets/image/default-profile.png',
            "status_akun" => $user['status_akun']
        ]
    ]);
}

// ==========================================
// MENGUBAH DATA ATAU STATUS AKUN
// ==========================================
if ($method === 'POST') {
    $id_pengguna = (int)($_POST['id_pengguna'] ?? 0);

    if ($id_pengguna === 0) {
        jsonResponse(["status" => "error", "message" => "User ID not found."]);
    }

    // --- UBAH KATA SANDI ---
    if ($action === 'change_password') {
        $old_pw = $_POST['old_password'] ?? '';
        $new_pw = $_POST['new_password'] ?? '';

        if ($old_pw === '' || $new_pw === '') {
            jsonResponse(["status" => "error", "message" => "Please fill in all password fields."]);
        }

        // 1. Cek kecocokan password lama
        $stmtCheck = sqlsrv_query($conn, "{CALL dbo.sp_GetAccountPassword(?)}", [$id_pengguna]);
        if ($stmtCheck === false) {
            jsonResponse(["status" => "error", "message" => "Database error checking password."]);
        }

        $row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
        if (!$row || $old_pw !== $row['password']) { 
            jsonResponse(["status" => "error", "message" => "Old password is incorrect!"]);
        }

        // 2. Eksekusi pembaruan password baru
        $stmtUp = sqlsrv_query($conn, "{CALL dbo.sp_UpdateAccountPassword(?, ?)}", [$id_pengguna, $new_pw]);
        if ($stmtUp !== false) {
            jsonResponse(["status" => "success", "message" => "Password updated successfully!"]);
        } else {
            jsonResponse(["status" => "error", "message" => "Failed to update password in database."]);
        }
    }
    
    // --- NONAKTIFKAN ATAU HAPUS AKUN ---
    if ($action === 'deactivate' || $action === 'delete') {
        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageAccountStatus(?, ?)}", [$id_pengguna, $action]);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            jsonResponse(["status" => "error", "message" => $errors[0]['message'] ?? "Failed to update account status."]);
        }

        $msg = ($action === 'delete') ? "Account successfully deleted." : "Account successfully deactivated.";
        jsonResponse(["status" => "success", "message" => $msg]);
    }

    jsonResponse(["status" => "error", "message" => "Invalid POST action."]);
}

jsonResponse(["status" => "error", "message" => "Invalid request method."]);
?>