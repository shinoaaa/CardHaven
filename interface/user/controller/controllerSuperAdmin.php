<?php
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
    case 'getAdmin':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) jsonOut(false, 'Invalid Admin ID.');

        $sql  = "SELECT id_pengguna, username, email, foto_profil, status_akun,
                        CONVERT(varchar, created_date, 105) AS created_date
                 FROM pengguna
                 WHERE id_pengguna = ? AND role = 2 AND is_deleted = 0";
        $stmt = sqlsrv_query($conn, $sql, [$id]);

        if (!$stmt || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
            jsonOut(false, 'Super Admin not found.');
        }

        jsonOut(true, 'OK', $row);
        break;

    // ----------------------------------------------------
    //  ADD Super Admin
    // ----------------------------------------------------
    case 'addAdmin':
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (!$username || !$email || !$password) {
            jsonOut(false, 'Username, Email, and Password are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonOut(false, 'Invalid email format.');
        }

        if (emailExists($conn, $email)) {
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
            
            // Format penamaan sesuai aset yang lu miliki: SADM_timestamp_uniqid.ext
            $filename = 'SADM_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/../../../image-profile/';
            $dest_path = $uploadFileDir . $filename;
            
            if (!move_uploaded_file($fileTmpPath, $dest_path)) {
                jsonOut(false, 'Error uploading the profile picture.');
            }
        }

        $sql  = "INSERT INTO pengguna (username, email, password, foto_profil, role, status_akun, is_deleted, created_date, created_by)
                 VALUES (?, ?, ?, ?, 2, 1, 0, GETDATE(), 1)";
        $params = [$username, $email, $hashedPassword, $filename];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if (!$stmt) {
            jsonOut(false, 'Database insert error.');
        }

        jsonOut(true, 'Super Admin added successfully.');
        break;

    // ----------------------------------------------------
    //  UPDATE Super Admin
    // ----------------------------------------------------
    case 'updateAdmin':
        $id       = (int)($_POST['id_pengguna'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
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

        // Ambil data lama untuk mempertahankan foto jika tidak diganti
        $checkSql = "SELECT foto_profil, password FROM pengguna WHERE id_pengguna = ? AND role = 2";
        $checkStmt = sqlsrv_query($conn, $checkSql, [$id]);
        $oldData = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);

        $finalPassword = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : $oldData['password'];
        $filename = $oldData['foto_profil'];

        // Cek jika user mengunggah foto baru
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['foto_profil']['tmp_name'];
            $fileNameOrig = $_FILES['foto_profil']['name'];
            $fileExtension = strtolower(pathinfo($fileNameOrig, PATHINFO_EXTENSION));
            
            $filename = 'SADM_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/../../../image-profile/';
            $dest_path = $uploadFileDir . $filename;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Hapus foto lama dari storage fisik jika ada
                if (!empty($oldData['foto_profil']) && file_exists($uploadFileDir . $oldData['foto_profil'])) {
                    @unlink($uploadFileDir . $oldData['foto_profil']);
                }
            } else {
                jsonOut(false, 'Error uploading new profile picture.');
            }
        }

        $sql  = "UPDATE pengguna 
                 SET username = ?, email = ?, password = ?, foto_profil = ?, modified_date = GETDATE()
                 WHERE id_pengguna = ? AND role = 2 AND is_deleted = 0";
        $stmt = sqlsrv_query($conn, $sql, [$username, $email, $finalPassword, $filename, $id]);

        if (!$stmt) {
            jsonOut(false, 'Failed to update database record.');
        }

        jsonOut(true, 'Super Admin updated successfully.');
        break;

    // ----------------------------------------------------
    //  DELETE Super Admin (Soft Delete)
    // ----------------------------------------------------
    case 'deleteAdmin':
        $id = (int)($_POST['id_pengguna'] ?? 0);
        if (!$id) jsonOut(false, 'Invalid ID.');

        $sql  = "UPDATE pengguna SET is_deleted = 1, deleted_date = GETDATE() WHERE id_pengguna = ? AND role = 2";
        $stmt = sqlsrv_query($conn, $sql, [$id]);

        if (!$stmt) {
            jsonOut(false, 'Failed to delete record.');
        }

        jsonOut(true, 'Super Admin soft-deleted successfully.');
        break;

    // ----------------------------------------------------
    //  TOGGLE Status Akun
    // ----------------------------------------------------
    case 'toggleAdmin':
        $id         = (int)($_POST['id_pengguna'] ?? 0);
        $status_akun = (int)($_POST['status_akun'] ?? 0);

        if (!$id) jsonOut(false, 'Invalid ID.');
        $status_akun = $status_akun === 1 ? 1 : 0;

        $sql  = "UPDATE pengguna SET status_akun = ? WHERE id_pengguna = ? AND role = 2 AND is_deleted = 0";
        $stmt = sqlsrv_query($conn, $sql, [$status_akun, $id]);

        if (!$stmt) {
            jsonOut(false, 'Failed to update account status.');
        }

        jsonOut(true, 'Account status updated successfully.');
        break;

    // ----------------------------------------------------
    //  DEFAULT: Render Table List (Pagination Logic)
    // ----------------------------------------------------
    default:
        $limit  = 7;
        $page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        // Hitung total baris data super admin (role = 2)
        $countSql  = "SELECT COUNT(*) AS total FROM pengguna WHERE role = 2 AND is_deleted = 0";
        $countStmt = sqlsrv_query($conn, $countSql);
        $countRow  = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
        $total_rows = $countRow['total'] ?? 0;
        $total_pages = ceil($total_rows / $limit);

        // Ambil data ter-paginasi
        $sql  = "
            SELECT *
            FROM pengguna
            WHERE role = 2 AND is_deleted = 0
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