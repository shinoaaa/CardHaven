<?php
session_start();
require_once '../../connection.php'; 

header('Content-Type: application/json');


$id_user = $_POST['id_karyawan_js'] ?? ($_SESSION['id_karyawan'] ?? 2000);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (($action == 'add' || $action == 'edit') && (empty(trim($_POST['nama_game'])) || empty(trim($_POST['developer'])))) {
        echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi!']);
        exit;
    }

    $nama = trim($_POST['nama_game']);
    $dev = trim($_POST['developer']);

    // --- VALIDASI NAMA DUPLIKAT ---
    if ($action === 'add' || $action === 'edit') {
        $sql_check = "SELECT COUNT(*) as total FROM dbo.game WHERE nama_game = ?";
        $params_check = array($nama);

        if ($action === 'edit') {
            $sql_check .= " AND id_game <> ?";
            $params_check[] = $_POST['id_game'];
        }

        $stmt_check = sqlsrv_query($conn, $sql_check, $params_check);
        $row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);

        if ($row_check['total'] > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Nama game sudah ada di database!']);
            exit;
        }
    }


    if ($action === 'add') {
        $sql = "INSERT INTO dbo.game (nama_game, developer, created_by, created_date, aktif) 
                VALUES (?, ?, ?, GETDATE(), 1)";
        $params = array($nama, $dev, $id_user);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) echo json_encode(['status' => 'success']);
        else echo json_encode(['status' => 'error', 'message' => 'Gagal insert ke database']);
    }


    if ($action === 'edit') {
        $id = $_POST['id_game'];
        $aktif = $_POST['aktif'];

        $sql = "UPDATE dbo.game SET nama_game = ?, developer = ?, modified_by = ?, modified_date = GETDATE(), aktif = ? 
                WHERE id_game = ?";
        $params = array($nama, $dev, $id_user, $aktif, $id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) echo json_encode(['status' => 'success']);
        else echo json_encode(['status' => 'error', 'message' => 'Gagal update database']);
    }


    if ($action === 'delete') {
        $id = $_POST['id_game'];
        $sql = "UPDATE dbo.game SET aktif = 0, modified_by = ?, modified_date = GETDATE() WHERE id_game = ?";
        $stmt = sqlsrv_query($conn, $sql, array($id_user, $id));
        
        if ($stmt) echo json_encode(['status' => 'success']);
        else echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data']);
    }
    exit;
}


if (isset($_GET['get_detail'])) {
    $id = $_GET['get_detail'];
    $sql = "SELECT g.*, k1.nama_karyawan as creator, k2.nama_karyawan as modifier 
            FROM dbo.game g 
            LEFT JOIN dbo.karyawan k1 ON g.created_by = k1.id_karyawan
            LEFT JOIN dbo.karyawan k2 ON g.modified_by = k2.id_karyawan
            WHERE g.id_game = ?";
    $stmt = sqlsrv_query($conn, $sql, array($id));
    $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    if($data){
        if($data['created_date']) $data['created_date'] = $data['created_date']->format('d-M-Y');
        if($data['modified_date']) $data['modified_date'] = $data['modified_date']->format('d-M-Y');
    }
    
    echo json_encode($data);
    exit;
}