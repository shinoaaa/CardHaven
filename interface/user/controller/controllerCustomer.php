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
    case 'getCustomer':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) jsonOut(false, 'Invalid Customer ID.');

        // Asumsi Role = 3 untuk Customer
        $sql  = "SELECT id_pengguna, username, email, no_telepon, foto_profil, status_akun,
                        CONVERT(varchar, created_date, 105) AS created_date
                 FROM pengguna
                 WHERE id_pengguna = ? AND role = 0 AND is_deleted = 0";
        $stmt = sqlsrv_query($conn, $sql, [$id]);

        if (!$stmt || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
            jsonOut(false, 'Customer not found.');
        }

        jsonOut(true, 'OK', $row);
        break;

    // ----------------------------------------------------
    //  ADD Customer
    // ----------------------------------------------------
    case 'addCustomer':
        $username   = trim($_POST['username'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $no_telepon = trim($_POST['no_telepon'] ?? '');
        $password   = $_POST['password'] ?? '';
        
        if (!$username || !$email || !$password) {
            jsonOut(false, 'Name, Email, and Password are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonOut(false, 'Invalid email format.');
        }

        if (emailExists($conn, $email)) {
            jsonOut(false, 'This email address is already in use.', [], 'EMAIL_DUPLICATE');
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Upload Foto Profil
        $filename = null;
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['foto_profil']['tmp_name'];
            $fileNameOrig = $_FILES['foto_profil']['name'];
            $fileExtension = strtolower(pathinfo($fileNameOrig, PATHINFO_EXTENSION));
            
            // Format penamaan file Customer
            $filename = 'CUST_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/../../../image-profile/';
            $dest_path = $uploadFileDir . $filename;
            
            if (!move_uploaded_file($fileTmpPath, $dest_path)) {
                jsonOut(false, 'Error uploading the profile picture.');
            }
        }

        // Insert dengan role = 3
        $sql  = "INSERT INTO pengguna (username, email, no_telepon, password, foto_profil, role, status_akun, is_deleted, created_date, created_by)
                 VALUES (?, ?, ?, ?, ?, 0, 1, 0, GETDATE(), 1)";
        $params = [$username, $email, $no_telepon, $hashedPassword, $filename];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if (!$stmt) {
            jsonOut(false, 'Database insert error.');
        }

        jsonOut(true, 'Customer added successfully.');
        break;

    // ----------------------------------------------------
    //  UPDATE Customer
    // ----------------------------------------------------
    case 'updateCustomer':
        $id         = (int)($_POST['id_pengguna'] ?? 0);
        $username   = trim($_POST['username'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $no_telepon = trim($_POST['no_telepon'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (!$id || !$username || !$email) {
            jsonOut(false, 'Missing required fields.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonOut(false, 'Invalid email format.');
        }

        if (emailExists($conn, $email, $id)) {
            jsonOut(false, 'This email is already used by another account.', [], 'EMAIL_DUPLICATE');
        }

        $checkSql = "SELECT foto_profil, password FROM pengguna WHERE id_pengguna = ? AND role = 0";
        $checkStmt = sqlsrv_query($conn, $checkSql, [$id]);
        $oldData = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);

        $finalPassword = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : $oldData['password'];
        $filename = $oldData['foto_profil'];

        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['foto_profil']['tmp_name'];
            $fileNameOrig = $_FILES['foto_profil']['name'];
            $fileExtension = strtolower(pathinfo($fileNameOrig, PATHINFO_EXTENSION));
            
            $filename = 'CUST_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/../../../image-profile/';
            $dest_path = $uploadFileDir . $filename;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                if (!empty($oldData['foto_profil']) && file_exists($uploadFileDir . $oldData['foto_profil'])) {
                    @unlink($uploadFileDir . $oldData['foto_profil']);
                }
            } else {
                jsonOut(false, 'Error uploading new profile picture.');
            }
        }

        $sql  = "UPDATE pengguna 
                 SET username = ?, email = ?, no_telepon = ?, password = ?, foto_profil = ?, modified_date = GETDATE()
                 WHERE id_pengguna = ? AND role = 0 AND is_deleted = 0";
        $stmt = sqlsrv_query($conn, $sql, [$username, $email, $no_telepon, $finalPassword, $filename, $id]);

        if (!$stmt) {
            jsonOut(false, 'Failed to update database record.');
        }

        jsonOut(true, 'Customer updated successfully.');
        break;

    // ----------------------------------------------------
    //  DELETE Customer
    // ----------------------------------------------------
    case 'deleteCustomer':
        $id = (int)($_POST['id_pengguna'] ?? 0);
        if (!$id) jsonOut(false, 'Invalid ID.');

        $sql  = "UPDATE pengguna SET is_deleted = 1, deleted_date = GETDATE() WHERE id_pengguna = ? AND role = 0";
        $stmt = sqlsrv_query($conn, $sql, [$id]);

        if (!$stmt) {
            jsonOut(false, 'Failed to delete record.');
        }

        jsonOut(true, 'Customer soft-deleted successfully.');
        break;

    // ----------------------------------------------------
    //  TOGGLE Status
    // ----------------------------------------------------
    case 'toggleCustomer':
        $id          = (int)($_POST['id_pengguna'] ?? 0);
        $status_akun = (int)($_POST['status_akun'] ?? 0);

        if (!$id) jsonOut(false, 'Invalid ID.');
        $status_akun = $status_akun === 1 ? 1 : 0;

        $sql  = "UPDATE pengguna SET status_akun = ? WHERE id_pengguna = ? AND role = 0 AND is_deleted = 0";
        $stmt = sqlsrv_query($conn, $sql, [$status_akun, $id]);

        if (!$stmt) {
            jsonOut(false, 'Failed to update account status.');
        }

        jsonOut(true, 'Account status updated successfully.');
        break;
    case 'verifyCustomer':
        $actorId = $_POST['actor_id'] ?? 0;

        // 1. Cek apakah Aktor adalah Role 3
        $stmtActor = sqlsrv_query($conn, "SELECT role FROM pengguna WHERE id_pengguna = ?", [$actorId]);
        $actor = sqlsrv_fetch_array($stmtActor, SQLSRV_FETCH_ASSOC);

        if (!$actor || $actor['role'] != 3) {
            echo json_encode(["status" => "error", "message" => "Unauthorized: Only Role 3 can do this."]);
            exit;
        }

        // 2. Cari target (Hapus batasan role)
        $email = trim($_POST['email'] ?? '');
        $createdDate = trim($_POST['created_date'] ?? '');
        $sql = "SELECT id_pengguna FROM pengguna WHERE email = ? AND CONVERT(date, created_date) = ?";
        $stmt = sqlsrv_query($conn, $sql, [$email, $createdDate]);

        if ($stmt && sqlsrv_has_rows($stmt)) {
            $_SESSION['cust_reset_verified'] = true;
            $_SESSION['cust_reset_email'] = $email;
            echo json_encode(["status" => "success", "message" => "Verified"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Incorrect date."]);
        }
        break;

    case 'resetCustomerPassword':
        if (!isset($_SESSION['cust_reset_verified']) || !$_SESSION['cust_reset_verified']) {
            echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
            exit;
        }

        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $email    = $_SESSION['cust_reset_email'];

        // Validasi Kompleksitas Sesuai script_register.js
        if (strlen($password) < 8 || strlen($password) > 12 || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            echo json_encode(["status" => "error", "message" => "Password must be 8-12 characters and contain a symbol."]);
            exit;
        }

        if ($password !== $confirm) {
            echo json_encode(["status" => "error", "message" => "Confirm password does not match."]);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE pengguna SET password = ? WHERE email = ?";
        $params = [$hashedPassword, $email];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            unset($_SESSION['cust_reset_verified']);
            unset($_SESSION['cust_reset_email']);
            echo json_encode(["status" => "success", "message" => "Password updated successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Update failed."]);
        }
        break;
    // ----------------------------------------------------
    //  DEFAULT: Render Table List dengan JOIN tabel penjualan
    // ----------------------------------------------------
    default:
        $limit  = 7;
        $page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        // Hitung total customer (role = 3)
        $countSql  = "SELECT COUNT(*) AS total FROM pengguna WHERE role = 0 AND is_deleted = 0";
        $countStmt = sqlsrv_query($conn, $countSql);
        $countRow  = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
        $total_rows = $countRow['total'] ?? 0;
        $total_pages = ceil($total_rows / $limit);

        // Query JOIN pengguna & penjualan
        // Menggunakan SUM dan COUNT berdasarkan status 'Selesai'
        $sql  = "
            SELECT p.id_pengguna, p.username, p.email, p.no_telepon, p.status_akun,
                   COUNT(pj.id_penjualan) AS shopping_amount,
                   COALESCE(SUM(pj.total_harga), 0) AS shopping_total
            FROM pengguna p
            LEFT JOIN penjualan pj ON p.id_pengguna = pj.id_pengguna AND pj.status_penjualan = '4'
            WHERE p.role = 0 AND p.is_deleted = 0
            GROUP BY p.id_pengguna, p.username, p.email, p.no_telepon, p.status_akun
            ORDER BY p.status_akun DESC, p.id_pengguna DESC
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