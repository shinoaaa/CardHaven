<?php
session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/CardHaven/connection.php';

$raw_id_js = $_POST['id_pengguna_js'] ?? '';
if ($raw_id_js === '' || $raw_id_js === 'undefined' || $raw_id_js === 'null') {
    $id_user = $_SESSION['id_pengguna'] ?? 1;
} else {
    $id_user = $raw_id_js;
}
$id_user = (int)$id_user;

// ==========================================
// BLOK AKSI (ADD, EDIT, DELETE, RESTORE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $nama       = trim($_POST['nama_metode']  ?? '');
    $provider   = trim($_POST['provider']     ?? '');
    $no_rek     = trim($_POST['no_rekening']  ?? '');
    $atas_nama  = trim($_POST['atas_nama']    ?? '');
    $biaya      = (float)($_POST['biaya_admin'] ?? 0);
    $id_metode  = isset($_POST['id_metode']) ? (int)$_POST['id_metode'] : null;

    // Validasi wajib
    if (($action === 'add' || $action === 'edit') && $nama === '') {
        echo json_encode(['status' => 'error', 'message' => 'Method name is required!']);
        exit;
    }

    // Cek duplikat nama_metode (global, tidak boleh sama)
    if ($action === 'add' || $action === 'edit') {
        $check_sql    = "SELECT COUNT(*) as total FROM dbo.metode_pembayaran WHERE nama_metode = ? AND is_deleted = 0";
        $params_check = [$nama];
        if ($action === 'edit') {
            $check_sql   .= " AND id_metode <> ?";
            $params_check[] = $id_metode;
        }
        $check_stmt = sqlsrv_query($conn, $check_sql, $params_check);
        $check_row  = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC);
        if ((int)$check_row['total'] > 0) {
            echo json_encode(['status' => 'error', 'message' => "Method name '$nama' already exists!"]);
            exit;
        }
    }

    $stmt = false;

    if ($action === 'add') {
        $sql  = "INSERT INTO dbo.metode_pembayaran 
                    (nama_metode, provider, no_rekening, atas_nama, biaya_admin, created_by, created_date, aktif, is_deleted) 
                 VALUES (?, ?, ?, ?, ?, ?, GETDATE(), 1, 0)";
        $stmt = sqlsrv_query($conn, $sql, [$nama, $provider, $no_rek, $atas_nama, $biaya, $id_user]);
    }
    else if ($action === 'edit') {
        $aktif = isset($_POST['aktif']) ? (int)$_POST['aktif'] : 1;
        $sql   = "UPDATE dbo.metode_pembayaran 
                  SET nama_metode=?, provider=?, no_rekening=?, atas_nama=?, biaya_admin=?, 
                      modified_by=?, modified_date=GETDATE(), aktif=? 
                  WHERE id_metode=?";
        $stmt  = sqlsrv_query($conn, $sql, [$nama, $provider, $no_rek, $atas_nama, $biaya, $id_user, $aktif, $id_metode]);
    }
    else if ($action === 'delete') {
        $sql  = "UPDATE dbo.metode_pembayaran SET is_deleted=1, deleted_by=?, deleted_date=GETDATE() WHERE id_metode=?";
        $stmt = sqlsrv_query($conn, $sql, [$id_user, $id_metode]);
    }
    else if ($action === 'restore') {
        $sql  = "UPDATE dbo.metode_pembayaran SET is_deleted=0, deleted_by=?, modified_date=GETDATE() WHERE id_metode=?";
        $stmt = sqlsrv_query($conn, $sql, [$id_user, $id_metode]);
    }

    if ($stmt) {
        echo json_encode(['status' => 'success', 'message' => '']);
    } else {
        $errors    = sqlsrv_errors();
        $error_msg = $errors != null ? $errors[0]['message'] : 'Database query failed.';
        echo json_encode(['status' => 'error', 'message' => 'SQL ERROR: ' . $error_msg]);
    }
    exit;
}

// ==========================================
// BLOK GET DETAIL
// ==========================================
if (isset($_GET['get_detail'])) {
    $sql  = "SELECT 
                m.id_metode, m.nama_metode, m.provider, m.no_rekening, 
                m.atas_nama, m.biaya_admin, m.aktif,
                m.created_date, m.modified_date,
                k1.username AS creator,
                k2.username AS modifier
             FROM dbo.metode_pembayaran m
             LEFT JOIN dbo.pengguna k1 ON m.created_by  = k1.id_pengguna
             LEFT JOIN dbo.pengguna k2 ON m.modified_by = k2.id_pengguna
             WHERE m.id_metode = ? AND m.is_deleted = 0";
    $stmt = sqlsrv_query($conn, $sql, [(int)$_GET['get_detail']]);

    if ($stmt === false) {
        $errors    = sqlsrv_errors();
        $error_msg = $errors != null ? $errors[0]['message'] : 'Failed to fetch detail.';
        echo json_encode(['error' => 'SQL ERROR: ' . $error_msg]);
        exit;
    }

    $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($data) {
        $data['created_date']  = (isset($data['created_date'])  && is_a($data['created_date'],  'DateTime')) ? $data['created_date']->format('d-M-Y H:i')  : '-';
        $data['modified_date'] = (isset($data['modified_date']) && is_a($data['modified_date'], 'DateTime')) ? $data['modified_date']->format('d-M-Y H:i') : '-';
        $data['biaya_admin']   = (float)$data['biaya_admin'];
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Payment method not found.']);
    }
    exit;
}
?>
