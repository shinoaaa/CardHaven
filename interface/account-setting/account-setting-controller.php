<?php
/**
 * interface/account-setting/account-setting-controller.php
 * Pengaturan akun milik user yang sedang login (edit profil, ganti password,
 * nonaktif/hapus akun sendiri). Hanya dipanggil via fetch → selalu balas JSON.
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (session_status() === PHP_SESSION_NONE) session_start();

ob_start();
register_shutdown_function(function () {
    $buf = ob_get_length() ? ob_get_contents() : '';
    if ($buf !== '' && strpos($buf, '{"status"') === false) {
        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["status" => "error", "message" => "Server error. Please try again."]);
    }
});

require __DIR__ . '/../../connection.php';

function jsonResponse(array $arr): void
{
    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

// Password baru: 8-12 karakter, kombinasi huruf + angka + simbol.
function validateNewPassword(string $pw): ?string
{
    if (strlen($pw) < 8 || strlen($pw) > 12) return 'Password must be 8-12 characters long.';
    if (!preg_match('/[A-Za-z]/', $pw))       return 'Password must contain a letter.';
    if (!preg_match('/[0-9]/', $pw))          return 'Password must contain a number.';
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>_\-]/', $pw)) return 'Password must contain a symbol.';
    return null;
}

if (!isset($conn) || $conn === false) {
    jsonResponse(["status" => "error", "message" => "Database connection failed. Please try again."]);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

// ==========================================
// MENGAMBIL DATA PENGGUNA
// ==========================================
if ($method === 'GET' && $action === 'get') {
    $id_pengguna = (int)($_GET['id_pengguna'] ?? 0);
    if ($id_pengguna === 0) jsonResponse(["status" => "error", "message" => "User ID not found."]);

    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetAccountSetting(?)}", [$id_pengguna]);
    if ($stmt === false) {
        jsonResponse(["status" => "error", "message" => "Failed to retrieve data."]);
    }
    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$user) jsonResponse(["status" => "error", "message" => "Data not found."]);

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

if ($method === 'POST') {
    $id_pengguna = (int)($_POST['id_pengguna'] ?? 0);
    if ($id_pengguna === 0) jsonResponse(["status" => "error", "message" => "User ID not found."]);

    // --- SIMPAN PROFIL (username + email) ---
    if ($action === 'update') {
        $nama  = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($nama === '' || $email === '') {
            jsonResponse(["status" => "error", "message" => "Name and email are required."]);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(["status" => "error", "message" => "Invalid email format."]);
        }

        // Email harus unik (kecuali milik sendiri) — pakai UDF yang sudah ada.
        $chk = sqlsrv_query($conn, "SELECT dbo.udf_CheckEmailPengguna(?, ?) AS dup", [$email, $id_pengguna]);
        $crow = $chk ? sqlsrv_fetch_array($chk, SQLSRV_FETCH_ASSOC) : null;
        if (($crow['dup'] ?? 0) > 0) {
            jsonResponse(["status" => "error", "message" => "Email already exists."]);
        }

        $up = sqlsrv_query($conn,
            "UPDATE dbo.pengguna SET username = ?, email = ?, modified_by = ?, modified_date = GETDATE() WHERE id_pengguna = ?",
            [$nama, $email, (string)$id_pengguna, $id_pengguna]);
        if ($up === false) {
            jsonResponse(["status" => "error", "message" => "Failed to save your profile."]);
        }
        jsonResponse(["status" => "success", "message" => "Profile updated successfully!"]);
    }

    // --- UBAH KATA SANDI ---
    if ($action === 'change_password') {
        // JS mengirim current_password/new_password (dukung old_password sbg fallback).
        $old_pw = $_POST['current_password'] ?? $_POST['old_password'] ?? '';
        $new_pw = $_POST['new_password'] ?? '';

        if ($old_pw === '' || $new_pw === '') {
            jsonResponse(["status" => "error", "message" => "Please fill in all password fields."]);
        }

        // 1. Ambil hash lama & verifikasi (password disimpan ter-hash, bukan plaintext).
        $stmtCheck = sqlsrv_query($conn, "{CALL dbo.sp_GetAccountPassword(?)}", [$id_pengguna]);
        if ($stmtCheck === false) {
            jsonResponse(["status" => "error", "message" => "Database error checking password."]);
        }
        $row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
        if (!$row || !password_verify($old_pw, $row['password'])) {
            jsonResponse(["status" => "error", "message" => "Old password is incorrect!"]);
        }

        // 2. Validasi kekuatan password baru.
        if ($err = validateNewPassword($new_pw)) {
            jsonResponse(["status" => "error", "message" => $err]);
        }

        // 3. Simpan password baru DALAM BENTUK HASH (supaya cocok dgn login).
        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
        $stmtUp = sqlsrv_query($conn, "{CALL dbo.sp_UpdateAccountPassword(?, ?)}", [$id_pengguna, $hashed]);
        if ($stmtUp === false) {
            jsonResponse(["status" => "error", "message" => "Failed to update password in database."]);
        }
        jsonResponse(["status" => "success", "message" => "Password updated successfully!"]);
    }

    // --- NONAKTIFKAN ATAU HAPUS AKUN ---
    if ($action === 'deactivate' || $action === 'delete') {
        // SP membedakan: 'delete' → is_deleted=1 (akun tidak bisa login lagi),
        // 'deactivate' → status_akun=0. ActorId = user sendiri.
        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageAccountStatus(?, ?, ?)}", [$id_pengguna, $action, (string)$id_pengguna]);
        if ($stmt === false) {
            jsonResponse(["status" => "error", "message" => "Failed to update account status."]);
        }
        $msg = ($action === 'delete') ? "Account successfully deleted." : "Account successfully deactivated.";
        jsonResponse(["status" => "success", "message" => $msg]);
    }

    jsonResponse(["status" => "error", "message" => "Invalid POST action."]);
}

jsonResponse(["status" => "error", "message" => "Invalid request method."]);
?>
