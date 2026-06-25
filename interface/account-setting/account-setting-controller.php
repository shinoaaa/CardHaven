<?php
require __DIR__ . '/../../connection.php';

function jsonResponse(array $arr): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get') {
    $id_pengguna = trim($_GET['id_pengguna'] ?? '');

    if ($id_pengguna === '') {
        jsonResponse([
            "status" => "error",
            "message" => "User ID not found."
        ]);
    }

    $sql = "SELECT id_pengguna, username, email, role, foto_profil, status_akun
            FROM pengguna
            WHERE id_pengguna = ?";

    $stmt = sqlsrv_prepare($conn, $sql, [$id_pengguna]);

    if (!$stmt || !sqlsrv_execute($stmt)) {
        $errors = sqlsrv_errors();
        jsonResponse([
            "status" => "error",
            "message" => $errors[0]['message'] ?? "Failed to retrieve data."
        ]);
    }

    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$user) {
        jsonResponse([
            "status" => "error",
            "message" => "Data not found."
        ]);
    }

    jsonResponse([
        "status" => "success",
        "data" => $user
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_pengguna = trim($_POST['id_pengguna'] ?? '');

    if ($id_pengguna === '') {
        jsonResponse([
            "status" => "error",
            "message" => "User ID not found."
        ]);
    }

    if ($action === 'update') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($nama === '' || $email === '') {
            jsonResponse([
                "status" => "error",
                "message" => "Name and email are required."
            ]);
        }

        $sqlCheck = "SELECT id_pengguna
                     FROM pengguna
                     WHERE email = ? AND id_pengguna <> ?";

        $stmtCheck = sqlsrv_prepare($conn, $sqlCheck, [$email, $id_pengguna]);

        if (!$stmtCheck || !sqlsrv_execute($stmtCheck)) {
            $errors = sqlsrv_errors();
            jsonResponse([
                "status" => "error",
                "message" => $errors[0]['message'] ?? "Email validation failed."
            ]);
        }

        $emailExists = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);

        if ($emailExists) {
            jsonResponse([
                "status" => "error",
                "message" => "Email is already in use by another account."
            ]);
        }

        $sql = "UPDATE pengguna SET username = ?, email = ? WHERE id_pengguna = ?";
        $params = [$nama, $email, $id_pengguna];

        $stmt = sqlsrv_prepare($conn, $sql, $params);

        if (!$stmt || !sqlsrv_execute($stmt)) {
            $errors = sqlsrv_errors();
            jsonResponse([
                "status" => "error",
                "message" => $errors[0]['message'] ?? "Failed to update profile."
            ]);
        }

        jsonResponse([
            "status" => "success",
            "message" => "Profile updated successfully."
        ]);
    }

    if ($action === 'change_password') {
        $cur_pw = $_POST['current_password'] ?? '';
        $new_pw = $_POST['new_password'] ?? '';

        // 1. Verifikasi Password Lama
        $sqlCheck = "SELECT password FROM pengguna WHERE id_pengguna = ?";
        $stmtCheck = sqlsrv_prepare($conn, $sqlCheck, [$id_pengguna]);
        sqlsrv_execute($stmtCheck);
        $user = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);

        if (!$user || $user['password'] !== $cur_pw) {
            jsonResponse([
                "status" => "error",
                "message" => "Current password is incorrect!"
            ]);
        }

        // 2. Update ke Password Baru
        $sqlUp = "UPDATE pengguna SET password = ? WHERE id_pengguna = ?";
        $stmtUp = sqlsrv_prepare($conn, $sqlUp, [$new_pw, $id_pengguna]);

        if (sqlsrv_execute($stmtUp)) {
            jsonResponse([
                "status" => "success",
                "message" => "Password updated successfully!"
            ]);
        } else {
            jsonResponse([
                "status" => "error",
                "message" => "Failed to update password in database."
            ]);
        }
    }
    
    if ($action === 'deactivate' || $action === 'delete') {
        $sql = "UPDATE pengguna SET status_akun = 0 WHERE id_pengguna = ?";
        $stmt = sqlsrv_prepare($conn, $sql, [$id_pengguna]);

        if (!$stmt || !sqlsrv_execute($stmt)) {
            $errors = sqlsrv_errors();
            jsonResponse([
                "status" => "error",
                "message" => $errors[0]['message'] ?? "Failed to deactivate account."
            ]);
        }

        $msg = ($action === 'delete') ? "Account successfully deleted." : "Account successfully deactivated.";
        jsonResponse([
            "status" => "success",
            "message" => $msg
        ]);
    }

    jsonResponse([
        "status" => "error",
        "message" => "Invalid action."
    ]);
}

jsonResponse([
    "status" => "error",
    "message" => "Invalid request."
]);