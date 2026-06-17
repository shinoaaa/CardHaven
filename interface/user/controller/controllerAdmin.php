<?php
session_start(); 
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
    // Cek duplikasi email untuk semua user yang is_deleted = 0
    $sql  = "SELECT COUNT(*) AS cnt FROM pengguna WHERE email = ? AND is_deleted = 0 AND id_pengguna <> ?";
    $stmt = sqlsrv_query($conn, $sql, [$email, $excludeId]);
    if (!$stmt) return false;
    $row  = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return (int)($row['cnt'] ?? 0) > 0;
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ----------------------------------------------------
    //  GET Single Data
    // ----------------------------------------------------
    case 'getAdmin':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) jsonOut(false, 'Invalid Admin ID.');

        // Role = 1 (Admin)
        $sql  = "SELECT id_pengguna, username, email, foto_profil, no_telepon, status_akun,
                        CONVERT(varchar, created_date, 105) AS created_date
                 FROM pengguna
                 WHERE id_pengguna = ? AND role = 1 AND is_deleted = 0";
        $stmt = sqlsrv_query($conn, $sql, [$id]);

        if (!$stmt || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
            jsonOut(false, 'Admin not found.');
        }

        jsonOut(true, 'OK', $row);
        break;

    // ----------------------------------------------------
    //  ADD Admin
    // ----------------------------------------------------
    case 'addAdmin':
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $no_telepon = trim($_POST['no_telepon'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (!$username || !$email || !$password) {
            jsonOut(false, 'Username, Email, and Password are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonOut(false, 'Invalid email format.');
        }

        if (emailExists($conn, $email)) {
            // Melempar code EMAIL_DUPLICATE agar bisa ditangkap JS -> showErr
            jsonOut(false, 'This email address is already in use.', [], 'EMAIL_DUPLICATE');
        }

        // Hashing password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Handling File Upload Foto Profil
        $filename = null;
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['foto_profil']['tmp_name'];
            $fileNameOrig = $_FILES['foto_profil']['name'];
            $fileExtension = strtolower(pathinfo($fileNameOrig, PATHINFO_EXTENSION));
            
            // Format penamaan file Admin
            $filename = 'ADM_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/../../../image-profile/';
            $dest_path = $uploadFileDir . $filename;
            
            if (!move_uploaded_file($fileTmpPath, $dest_path)) {
                jsonOut(false, 'Error uploading the profile picture.');
            }
        }

        // Simpan dengan role = 1
        $sql  = "INSERT INTO pengguna (username, email, no_telepon, password, foto_profil, role, status_akun, is_deleted, created_date, created_by)
                 VALUES (?, ?, ?, ?, ?, 1, 1, 0, GETDATE(), 1)";
        $params = [$username, $email,$no_telepon, $hashedPassword, $filename];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if (!$stmt) {
            jsonOut(false, 'Database insert error.');
        }

        jsonOut(true, 'Admin added successfully.');
        break;

    // ----------------------------------------------------
    //  UPDATE Admin
    // ----------------------------------------------------
    case 'updateAdmin':
        $id       = (int)($_POST['id_pengguna'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $no_telepon = trim($_POST['no_telepon'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$id || !$username || !$email) {
            jsonOut(false, 'Missing required fields.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonOut(false, 'Invalid email format.');
        }

        if (emailExists($conn, $email, $id)) {
            jsonOut(false, 'This email is already used by another account.', [], 'EMAIL_DUPLICATE');
        }

        // Ambil data lama
        $checkSql = "SELECT foto_profil, password FROM pengguna WHERE id_pengguna = ? AND role = 1";
        $checkStmt = sqlsrv_query($conn, $checkSql, [$id]);
        $oldData = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);

        $finalPassword = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : $oldData['password'];
        $filename = $oldData['foto_profil'];

        // Upload foto baru jika ada
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['foto_profil']['tmp_name'];
            $fileNameOrig = $_FILES['foto_profil']['name'];
            $fileExtension = strtolower(pathinfo($fileNameOrig, PATHINFO_EXTENSION));
            
            $filename = 'ADM_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/../../../image-profile/';
            $dest_path = $uploadFileDir . $filename;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Hapus foto lama
                if (!empty($oldData['foto_profil']) && file_exists($uploadFileDir . $oldData['foto_profil'])) {
                    @unlink($uploadFileDir . $oldData['foto_profil']);
                }
            } else {
                jsonOut(false, 'Error uploading new profile picture.');
            }
        }

        $sql  = "UPDATE pengguna 
                 SET username = ?, email = ?, password = ?, foto_profil = ? , no_telepon = ?, modified_date = GETDATE()
                 WHERE id_pengguna = ? AND role = 1 AND is_deleted = 0";
        $stmt = sqlsrv_query($conn, $sql, [$username, $email, $finalPassword, $filename, $no_telepon, $id]);

        if (!$stmt) {
            jsonOut(false, 'Failed to update database record.');
        }

        jsonOut(true, 'Admin updated successfully.');
        break;

    // ----------------------------------------------------
    //  DELETE Admin
    // ----------------------------------------------------
    case 'deleteAdmin':
        $id = (int)($_POST['id_pengguna'] ?? 0);
        if (!$id) jsonOut(false, 'Invalid ID.');

        $sql  = "UPDATE pengguna SET is_deleted = 1, deleted_date = GETDATE() WHERE id_pengguna = ? AND role = 1";
        $stmt = sqlsrv_query($conn, $sql, [$id]);

        if (!$stmt) {
            jsonOut(false, 'Failed to delete record.');
        }

        jsonOut(true, 'Admin soft-deleted successfully.');
        break;

    // ----------------------------------------------------
    //  TOGGLE Status
    // ----------------------------------------------------
    case 'toggleAdmin':
        $id         = (int)($_POST['id_pengguna'] ?? 0);
        $status_akun = (int)($_POST['status_akun'] ?? 0);

        if (!$id) jsonOut(false, 'Invalid ID.');
        $status_akun = $status_akun === 1 ? 1 : 0;

        $sql  = "UPDATE pengguna SET status_akun = ? WHERE id_pengguna = ? AND role = 1 AND is_deleted = 0";
        $stmt = sqlsrv_query($conn, $sql, [$status_akun, $id]);

        if (!$stmt) {
            jsonOut(false, 'Failed to update account status.');
        }

        jsonOut(true, 'Account status updated successfully.');
        break;
    case 'verifyAdmin':
            $actorId = $_POST['actor_id'] ?? 0;

           
            $stmtActor = sqlsrv_query($conn, "SELECT role FROM pengguna WHERE id_pengguna = ?", [$actorId]);
            $actor = sqlsrv_fetch_array($stmtActor, SQLSRV_FETCH_ASSOC);

            if (!$actor || $actor['role'] != 3) {
                echo json_encode(["status" => "error", "message" => "Only Role 3 can modify passwords."]);
                exit;
            }

            // Query Target (Tanpa filter role)
            $email = $_POST['email'];
            $date  = $_POST['created_date'];
            $sql = "SELECT id_pengguna FROM pengguna WHERE email = ? AND CONVERT(date, created_date) = ?";
            $stmt = sqlsrv_query($conn, $sql, [$email, $date]);

            if ($stmt && sqlsrv_has_rows($stmt)) {
                $_SESSION['admin_reset_verified'] = true;
                $_SESSION['admin_reset_email'] = $email;
                echo json_encode(["status" => "success", "message" => "Verified"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Wrong information."]);
            }
            break;
    case 'resetAdminPassword':
            $actorId = $_POST['actor_id'] ?? 0;

            // Cek Role Aktor lagi (Double Security)
            $sqlActor = "SELECT role FROM pengguna WHERE id_pengguna = ?";
            $stmtActor = sqlsrv_query($conn, $sqlActor, [$actorId]);
            $actorData = sqlsrv_fetch_array($stmtActor, SQLSRV_FETCH_ASSOC);

            if (!$actorData || $actorData['role'] != 3) {
                echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
                exit;
            }

            if (!isset($_SESSION['admin_reset_verified']) || !$_SESSION['admin_reset_verified']) {
                echo json_encode(["status" => "error", "message" => "Please verify identity first."]);
                exit;
            }

            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';
            $email    = $_SESSION['admin_reset_email'];

            // 1. Validasi Kosong
            if (!$password || !$confirm) {
                echo json_encode(["status" => "error", "message" => "Password cannot be empty"]);
                exit;
            }

            // 2. Validasi Panjang (8-12) & Karakter Spesial (Sesuai script_register.js)
            if (strlen($password) < 8 || strlen($password) > 12 || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                echo json_encode(["status" => "error", "message" => "Password must be 8-12 characters and contain a special character"]);
                exit;
            }

            // 3. Validasi Kecocokan
            if ($password !== $confirm) {
                echo json_encode(["status" => "error", "message" => "Confirm password does not match"]);
                exit;
            }

            // 4. Hash dan Update
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE pengguna SET password = ? WHERE email = ?";
            $params = [$hashedPassword, $email];
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt) {
                // Hapus session reset setelah berhasil
                unset($_SESSION['admin_reset_verified']);
                unset($_SESSION['admin_reset_email']);
                echo json_encode(["status" => "success", "message" => "Password updated successfully"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update password"]);
            }
            break;
    // ----------------------------------------------------
    //  DEFAULT: Render Table List
    // ----------------------------------------------------
    default:
        $limit  = 7;
        $page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        // Role = 1
        $countSql  = "SELECT COUNT(*) AS total FROM pengguna WHERE role = 1 AND is_deleted = 0";
        $countStmt = sqlsrv_query($conn, $countSql);
        $countRow  = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
        $total_rows = $countRow['total'] ?? 0;
        $total_pages = ceil($total_rows / $limit);

        $sql  = "
            SELECT *
            FROM pengguna
            WHERE role = 1 AND is_deleted = 0
            ORDER BY status_akun DESC, id_pengguna DESC
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