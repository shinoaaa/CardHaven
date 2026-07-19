<?php
/**
 * interface/account-setting/account-setting-controller.php
 * Pengaturan akun milik user yang sedang login (edit profil, ganti password,
 * nonaktif/hapus akun sendiri).
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
require_once __DIR__ . '/../../auth/session.php';
auth_session_start();

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

// Halaman ini HANYA untuk akun milik sendiri, jadi id_pengguna selalu diambil
// dari session. Sebelumnya id dikirim dari browser, sehingga siapa pun bisa
// mengedit/menghapus akun orang lain hanya dengan mengganti angka id.
$authUser    = auth_api_require_login();
$id_pengguna = $authUser['id'];

// ==========================================
// MENGAMBIL DATA PENGGUNA
// ==========================================
if ($method === 'GET' && $action === 'get') {
    $stmt = sqlsrv_query($conn, "SELECT id_pengguna, username, email, role, foto_profil, status_akun, no_telepon FROM dbo.pengguna WHERE id_pengguna = ?", [$id_pengguna]);
    
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
            "no_telepon"  => $user['no_telepon'] ?? '',
            "role"        => $user['role'],
            "foto_profil" => $user['foto_profil'] ?? null,
            "status_akun" => $user['status_akun']
        ]
    ]);
}

if ($method === 'POST') {
    // --- SIMPAN PROFIL ---
    if ($action === 'update') {
        $nama       = trim($_POST['nama'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $no_telepon = trim($_POST['no_telepon'] ?? '');

        if ($nama === '' || $email === '' || $no_telepon === '') {
            jsonResponse(["status" => "error", "message" => "Name, Email, and Phone number are required."]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(["status" => "error", "message" => "Invalid email format."]);
        }
        
        $rawPhone = str_replace([' ', '-'], '', $no_telepon);
        if (strlen($no_telepon) > 20 || !preg_match('/^\+?[0-9]{9,15}$/', $rawPhone)) {
            jsonResponse(["status" => "error", "message" => "Invalid phone number format."]);
        }

        $chk = sqlsrv_query($conn, "SELECT dbo.udf_CheckEmailPengguna(?, ?) AS dup", [$email, $id_pengguna]);
        $crow = $chk ? sqlsrv_fetch_array($chk, SQLSRV_FETCH_ASSOC) : null;
        if (($crow['dup'] ?? 0) > 0) {
            jsonResponse(["status" => "error", "message" => "Email already exists."]);
        }

        $foto_profil = null;
        if (isset($_FILES['fotoFile']) && $_FILES['fotoFile']['error'] === UPLOAD_ERR_OK) {
            
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $fileMime = mime_content_type($_FILES['fotoFile']['tmp_name']);
            if (!in_array($fileMime, $allowedMimeTypes)) {
                jsonResponse(["status" => "error", "message" => "Only JPG and PNG images are allowed."]);
            }

            if ($_FILES['fotoFile']['size'] > 2 * 1024 * 1024) {
                jsonResponse(["status" => "error", "message" => "Image size must be less than 2MB."]);
            }

            $uploadDir = __DIR__ . '/../../assets/image/image-profile/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileExt = strtolower(pathinfo($_FILES['fotoFile']['name'], PATHINFO_EXTENSION));
            $fileName = 'profile_' . $id_pengguna . '_' . time() . '.' . $fileExt;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['fotoFile']['tmp_name'], $targetPath)) {
                // DB hanya menyimpan nama file; prefix folder ditambahkan saat menampilkan.
                $foto_profil = $fileName;
            } else {
                jsonResponse(["status" => "error", "message" => "Failed to upload image."]);
            }
        }

        if ($foto_profil) {
            $up = sqlsrv_query($conn,
                "UPDATE dbo.pengguna SET username = ?, email = ?, no_telepon = ?, foto_profil = ?, modified_by = ?, modified_date = GETDATE() WHERE id_pengguna = ?",
                [$nama, $email, $no_telepon, $foto_profil, (string)$id_pengguna, $id_pengguna]);
        } else {
            $up = sqlsrv_query($conn,
                "UPDATE dbo.pengguna SET username = ?, email = ?, no_telepon = ?, modified_by = ?, modified_date = GETDATE() WHERE id_pengguna = ?",
                [$nama, $email, $no_telepon, (string)$id_pengguna, $id_pengguna]);
        }

        if ($up === false) {
            jsonResponse(["status" => "error", "message" => "Failed to save your profile."]);
        }

        // Session ikut diperbarui supaya nama/email di navbar & sidebar langsung sinkron.
        $_SESSION['username'] = $nama;
        $_SESSION['email']    = $email;

        jsonResponse(["status" => "success", "message" => "Profile updated successfully!"]);
    }

    // --- UBAH KATA SANDI ---
    if ($action === 'change_password') {
        $old_pw = $_POST['current_password'] ?? $_POST['old_password'] ?? '';
        $new_pw = $_POST['new_password'] ?? '';

        if ($old_pw === '' || $new_pw === '') {
            jsonResponse(["status" => "error", "message" => "Please fill in all password fields."]);
        }

        $stmtCheck = sqlsrv_query($conn, "{CALL dbo.sp_GetAccountPassword(?)}", [$id_pengguna]);
        if ($stmtCheck === false) {
            jsonResponse(["status" => "error", "message" => "Database error checking password."]);
        }
        $row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
        if (!$row || !password_verify($old_pw, $row['password'])) {
            jsonResponse(["status" => "error", "message" => "Old password is incorrect!"]);
        }

        if ($err = validateNewPassword($new_pw)) {
            jsonResponse(["status" => "error", "message" => $err]);
        }

        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
        $stmtUp = sqlsrv_query($conn, "{CALL dbo.sp_UpdateAccountPassword(?, ?)}", [$id_pengguna, $hashed]);
        if ($stmtUp === false) {
            jsonResponse(["status" => "error", "message" => "Failed to update password in database."]);
        }
        jsonResponse(["status" => "success", "message" => "Password updated successfully!"]);
    }

    // --- NONAKTIFKAN ATAU HAPUS AKUN ---
    if ($action === 'deactivate' || $action === 'delete') {
        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageAccountStatus(?, ?, ?)}", [$id_pengguna, $action, (string)$id_pengguna]);
        if ($stmt === false) {
            jsonResponse(["status" => "error", "message" => "Failed to update account status."]);
        }

        // Akun sudah tidak aktif/terhapus — session-nya wajib ikut dihapus.
        auth_logout();

        $msg = ($action === 'delete') ? "Account successfully deleted." : "Account successfully deactivated.";
        jsonResponse(["status" => "success", "message" => $msg]);
    }

    jsonResponse(["status" => "error", "message" => "Invalid POST action."]);
}

jsonResponse(["status" => "error", "message" => "Invalid request method."]);
?>