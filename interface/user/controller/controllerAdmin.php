<?php
/**
 * interface/user/controller/controllerAdmin.php
 * Manajemen pengguna role 1 (Employee/Admin).
 * Dipakai dua cara: (1) di-require indexAdmin.php untuk list (mode HTML),
 * (2) dipanggil langsung via fetch untuk aksi AJAX (mode JSON).
 */
error_reporting(E_ALL);
require_once __DIR__ . '/../../../auth/session.php';
auth_session_start();

$action = $_REQUEST['action'] ?? '';
$isAjax = ($action !== '');

// Hanya di mode AJAX: matikan tampilan warning & buffer output supaya respons
// selalu JSON valid (tidak "Network error" gara-gara warning / dump koneksi).
if ($isAjax) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ob_start();
    register_shutdown_function(function () {
        $buf = ob_get_length() ? ob_get_contents() : '';
        if ($buf !== '' && strpos($buf, '{"success"') === false && strpos($buf, '{"status"') === false) {
            if (ob_get_level() > 0) ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
        }
    });
}

require_once __DIR__ . '/../../../connection.php';

function jsonOut(bool $success, string $message = '', array $data = [], string $code = ''): void {
    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data, 'code' => $code]);
    exit;
}

// Endpoint verify/reset password memakai bentuk {status: ...} (dibaca data.status di JS).
function jsonStatus(string $status, string $message = ''): void {
    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// Bersihkan pesan error SQL dari prefix driver "[Microsoft][ODBC ...][SQL Server]".
function cleanSqlMessage(string $raw): string {
    return trim(preg_replace('/\[[^\]]*\]/', '', $raw));
}

// Simpan foto profil yang diupload ke folder assets/image/image-profile/, kembalikan nama file (atau null).
function saveProfilePhoto(?array $file): ?string {
    if (!$file || (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception('Photo upload failed.');
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = @mime_content_type($file['tmp_name']) ?: '';
    if (!isset($allowed[$mime])) throw new Exception('Photo must be JPG, PNG, WEBP, or GIF.');
    if (($file['size'] ?? 0) > 3 * 1024 * 1024) throw new Exception('Photo must be under 3 MB.');
    $dir = __DIR__ . '/../../../assets/image/image-profile/';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $name = 'user_' . uniqid() . '.' . $allowed[$mime];
    $ok = is_uploaded_file($file['tmp_name'])
        ? move_uploaded_file($file['tmp_name'], $dir . $name)
        : rename($file['tmp_name'], $dir . $name);
    if (!$ok) throw new Exception('Failed to save photo.');
    return $name;
}

$role = 1;

// Manajemen pengguna hanya untuk Owner.
if ($isAjax) auth_api_require_role([ROLE_OWNER]);

// Pelaku aksi (jejak audit) diambil dari session, bukan dari actor_id kiriman browser.
$actorId = (string)auth_id();

if ($isAjax) {
    if (!isset($conn) || $conn === false) jsonOut(false, 'Database connection failed. Please try again.');

    try {
        switch ($action) {
            case 'getAdmin':
                $id = (int)($_GET['id'] ?? 0);
                if (!$id) jsonOut(false, 'Invalid Super Admin ID.');
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetPenggunaDetail(?, ?)}", [$id, $role]);
                if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    
                    // FIX: Konversi object DateTime ke String
                    foreach ($row as $key => $val) {
                        if ($val instanceof DateTime) {
                            $row[$key] = $val->format('d M Y, H:i');
                        }
                    }
                    
                    jsonOut(true, '', $row);
                }
                jsonOut(false, 'Super Admin not found.');
                break;

            case 'addAdmin':
                $pw = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('add', 0, ?, ?, ?, ?, ?, ?)}",
                    [$role, trim($_POST['username'] ?? ''), trim($_POST['email'] ?? ''), trim($_POST['no_telepon'] ?? ''), $pw, $actorId]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);

                // Simpan foto (kalau ada). Ambil id user baru via email (unik).
                $foto = saveProfilePhoto($_FILES['foto_profil'] ?? null);
                if ($foto) {
                    $q = sqlsrv_query($conn, "SELECT TOP 1 id_pengguna FROM dbo.pengguna WHERE email = ? AND role = ? ORDER BY id_pengguna DESC",
                        [trim($_POST['email'] ?? ''), $role]);
                    if ($q && ($r = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC))) {
                        sqlsrv_query($conn, "UPDATE dbo.pengguna SET foto_profil = ?, modified_by = ?, modified_date = GETDATE() WHERE id_pengguna = ?",
                            [$foto, $actorId, (int)$r['id_pengguna']]);
                    }
                }
                jsonOut(true, 'Admin created successfully.');

            case 'updateAdmin':
                $id = (int)($_POST['id_pengguna'] ?? 0);
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('edit', ?, ?, ?, ?, ?, '', ?)}",
                    [$id, $role, trim($_POST['username'] ?? ''), trim($_POST['email'] ?? ''), trim($_POST['no_telepon'] ?? ''), $actorId]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);

                $foto = saveProfilePhoto($_FILES['foto_profil'] ?? null);
                if ($foto) {
                    sqlsrv_query($conn, "UPDATE dbo.pengguna SET foto_profil = ?, modified_by = ?, modified_date = GETDATE() WHERE id_pengguna = ? AND role = ?",
                        [$foto, $actorId, $id, $role]);
                }
                jsonOut(true, 'Admin updated successfully.');

            case 'deleteAdmin':
                $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
                $id = (int)($body['id_pengguna'] ?? 0);
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('delete', ?, ?, '', '', '', '', ?)}", [$id, $role, $actorId]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Admin deleted successfully.');

            case 'toggleAdmin':
                $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
                $id = (int)($body['id_pengguna'] ?? 0);
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('toggle_status', ?, ?, '', '', '', '', ?)}", [$id, $role, $actorId]);
                if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
                jsonOut(true, 'Status toggled successfully.');

            // ── Change password: TAHAP 1 verifikasi (email + tanggal dibuat) ──
            case 'verifyAdmin':
                $email = trim($_POST['email'] ?? '');
                $date  = trim($_POST['created_date'] ?? '');
                if ($email === '' || $date === '') jsonStatus('error', 'Email and date are required.');
                $stmt = sqlsrv_query($conn,
                    "SELECT id_pengguna FROM dbo.pengguna WHERE email = ? AND role = ? AND CONVERT(date, created_date) = ? AND is_deleted = 0",
                    [$email, $role, $date]);
                if ($stmt && ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
                    $_SESSION['pwreset_' . $role] = (int)$r['id_pengguna'];
                    jsonStatus('success');
                }
                jsonStatus('error', 'Verification failed. The creation date does not match our records.');

            // ── Change password: TAHAP 2 reset (pakai id hasil verifikasi) ──
            case 'resetAdminPassword':
            case 'changePasswordAdmin':
                $id = (int)($_SESSION['pwreset_' . $role] ?? 0);
                if (!$id) $id = (int)($_POST['id_pengguna'] ?? 0);
                if (!$id) jsonStatus('error', 'Please verify the identity first.');
                $password = $_POST['password'] ?? '';
                if (strlen($password) < 8 || strlen($password) > 12 || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                    jsonStatus('error', 'Password must be 8-12 characters and contain a symbol.');
                }
                $pw = password_hash($password, PASSWORD_DEFAULT);
                $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManagePengguna('change_password', ?, ?, '', '', '', ?, ?)}", [$id, $role, $pw, $actorId]);
                if ($stmt === false) jsonStatus('error', cleanSqlMessage(sqlsrv_errors()[0]['message'] ?? 'Failed to update password.'));
                unset($_SESSION['pwreset_' . $role]);
                jsonStatus('success', 'Password updated successfully.');

            default:
                jsonOut(false, 'Unknown action.');
        }
    } catch (Throwable $e) {
        $clean = cleanSqlMessage($e->getMessage());
        if (stripos($clean, 'Email already exists') !== false) {
            jsonOut(false, 'Email already exists.', [], 'EMAIL_DUPLICATE');
        }
        jsonOut(false, $clean !== '' ? $clean : 'Something went wrong. Please try again.');
    }
} else if (isset($_GET['list'])) {
    $limit  = 7;
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $search = trim($_GET['search'] ?? '');
    $status = ($_GET['status'] === '' || !isset($_GET['status'])) ? -1 : (int)$_GET['status'];
    $sortBy  = $_GET['sort_by'] ?? 'id_pengguna';
    $sortDir = strtoupper($_GET['sort_order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetPenggunaList(?, ?, ?, ?, ?, ?, ?)}", 
        [$role, $search, $sortBy, $sortDir, $status, $page, $limit]);

    $data = [];
    $total_rows = 0;
    if ($stmt) {
        if ($rowTotal = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $total_rows = (int)($rowTotal['total_data'] ?? 0);
        }
        sqlsrv_next_result($stmt);
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($r as $key => $val) {
                if ($val instanceof DateTime) $r[$key] = $val->format('d M Y');
            }
            $data[] = $r;
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'total_pages' => max(1, ceil($total_rows / $limit)),
        'current_page' => $page
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
