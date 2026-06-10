<?php
session_start();
require_once '../../connection.php';
header('Content-Type: application/json');

$id_user = $_POST['id_pengguna_js'] ?? ($_SESSION['id_pengguna'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $nama = trim($_POST['nama_game'] ?? '');
    $dev = trim($_POST['developer'] ?? '');
    $id_game = $_POST['id_game'] ?? null;

    // 1. Validasi Kosong
    if (($action == 'add' || $action == 'edit') && ($nama == "" || $dev == "")) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required!']); exit;
    }

    // 2. Validasi Duplikat Nama
    if ($action == 'add' || $action == 'edit') {
        $sql_cek = "SELECT COUNT(*) as total FROM dbo.game WHERE nama_game = ? AND is_deleted = 0";
        $params_cek = [$nama];
        if ($action == 'edit') { $sql_cek .= " AND id_game <> ?"; $params_cek[] = $id_game; }
        
        $stmt_cek = sqlsrv_query($conn, $sql_cek, $params_cek);
        if (sqlsrv_fetch_array($stmt_cek, SQLSRV_FETCH_ASSOC)['total'] > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Game name is already registered!']); exit;
        }
    }

    // 3. Eksekusi Action
    if ($action === 'add') {
        $sql = "INSERT INTO dbo.game (nama_game, developer, created_by, created_date, aktif,is_deleted) VALUES (?, ?, ?, GETDATE(), 1,0)";
        $stmt = sqlsrv_query($conn, $sql, [$nama, $dev, $id_user]);
    } else if ($action === 'edit') {
        $sql = "UPDATE dbo.game SET nama_game=?, developer=?, modified_by=?, modified_date=GETDATE() WHERE id_game=?";
        $stmt = sqlsrv_query($conn, $sql, [$nama, $dev, $id_user, $id_game]);
    } else if ($action === 'aktifkan' || $action === 'nonaktifkan') {
        $aktif = $action === 'aktifkan' ? 1 : 0;
        $sql = "UPDATE dbo.game SET aktif=?, modified_by=?, modified_date=GETDATE() WHERE id_game=?";
        $stmt = sqlsrv_query($conn, $sql, [$aktif, $id_user, $id_game]);
    } else if ($action === 'delete') {
        $sql = "UPDATE dbo.game SET is_deleted=1, deleted_by=?, deleted_date=GETDATE() WHERE id_game=?";
        $stmt = sqlsrv_query($conn, $sql, [$id_user, $id_game]);
    } else if ($action === 'restore') {
        $sql = "UPDATE dbo.game SET is_deleted=0, modified_by=?, modified_date=GETDATE() WHERE id_game=?";
        $stmt = sqlsrv_query($conn, $sql, [$id_user, $id_game]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action!']);
        exit;
    }

    echo json_encode(['status' => $stmt ? 'success' : 'error', 'message' => $stmt ? '' : 'Database error']);
    exit;
}

// Fetch Detail
if (isset($_GET['get_detail'])) {
    $id = $_GET['get_detail'];
    
    $sql = "SELECT g.*, u1.username as creator_name, u2.username as modifier_name 
        FROM dbo.game g 
        LEFT JOIN dbo.pengguna u1 ON g.created_by = u1.id_pengguna 
        LEFT JOIN dbo.pengguna u2 ON g.modified_by = u2.id_pengguna 
        WHERE g.id_game = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id]);

    if ($stmt === false) {
        die(json_encode(['error' => sqlsrv_errors()]));
    }

    $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($data) {
        $data['created_date'] = ($data['created_date'] instanceof DateTime) ? $data['created_date']->format('d-M-Y H:i') : '-';
        $data['modified_date'] = ($data['modified_date'] instanceof DateTime) ? $data['modified_date']->format('d-M-Y H:i') : '-';
        $data['creator'] = $data['creator_name'] ?? '-';
        $data['modifier'] = $data['modifier_name'] ?? '-';
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Data not found']);
    }
    exit;
}
?>