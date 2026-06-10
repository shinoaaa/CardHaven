<?php
require_once '../../connection.php';

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
            "message" => "ID pengguna tidak ditemukan"
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
            "message" => $errors[0]['message'] ?? "Gagal mengambil data"
        ]);
    }

    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$user) {
        jsonResponse([
            "status" => "error",
            "message" => "Data tidak ditemukan"
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
            "message" => "ID pengguna tidak ditemukan"
        ]);
    }

    if ($action === 'update') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        if ($nama === '' || $email === '') {
            jsonResponse([
                "status" => "error",
                "message" => "Nama dan email wajib diisi"
            ]);
        }

        if ($password !== '' || $confirm_password !== '') {
            if ($password !== $confirm_password) {
                jsonResponse([
                    "status" => "error",
                    "message" => "Password dan konfirmasi password tidak sama"
                ]);
            }
        }

        $sqlCheck = "SELECT id_pengguna
                     FROM pengguna
                     WHERE email = ? AND id_pengguna <> ?";

        $stmtCheck = sqlsrv_prepare($conn, $sqlCheck, [$email, $id_pengguna]);

        if (!$stmtCheck || !sqlsrv_execute($stmtCheck)) {
            $errors = sqlsrv_errors();
            jsonResponse([
                "status" => "error",
                "message" => $errors[0]['message'] ?? "Validasi email gagal"
            ]);
        }

        $emailExists = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);

        if ($emailExists) {
            jsonResponse([
                "status" => "error",
                "message" => "Email sudah dipakai akun lain"
            ]);
        }

        if ($password !== '') {
            $sql = "UPDATE pengguna
                    SET username = ?, email = ?, password = ?
                    WHERE id_pengguna = ?";

            $params = [$nama, $email, $password, $id_pengguna];
        } else {
            $sql = "UPDATE pengguna
                    SET username = ?, email = ?
                    WHERE id_pengguna = ?";

            $params = [$nama, $email, $id_pengguna];
        }

        $stmt = sqlsrv_prepare($conn, $sql, $params);

        if (!$stmt || !sqlsrv_execute($stmt)) {
            $errors = sqlsrv_errors();
            jsonResponse([
                "status" => "error",
                "message" => $errors[0]['message'] ?? "Update gagal"
            ]);
        }

        jsonResponse([
            "status" => "success",
            "message" => "Data berhasil diupdate"
        ]);
    }

    if ($action === 'deactivate' || $action === 'delete') {
        $sql = "UPDATE pengguna
                SET status_akun = 0
                WHERE id_pengguna = ?";

        $stmt = sqlsrv_prepare($conn, $sql, [$id_pengguna]);

        if (!$stmt || !sqlsrv_execute($stmt)) {
            $errors = sqlsrv_errors();
            jsonResponse([
                "status" => "error",
                "message" => $errors[0]['message'] ?? "Gagal menonaktifkan akun"
            ]);
        }

        jsonResponse([
            "status" => "success",
            "message" => "Akun berhasil dinonaktifkan"
        ]);
    }

    jsonResponse([
        "status" => "error",
        "message" => "Action tidak valid"
    ]);
}

jsonResponse([
    "status" => "error",
    "message" => "Request tidak valid"
]);