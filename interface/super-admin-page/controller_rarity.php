<?php
session_start();
ini_set('display_errors', 0); // Sembunyikan error HTML yang merusak JSON
header('Content-Type: application/json');

require_once '../../connection.php';


$raw_id_js = $_POST['id_pengguna_js'] ?? '';


if ($raw_id_js === '' || $raw_id_js === 'undefined' || $raw_id_js === 'null') {
    $id_user = $_SESSION['id_pengguna'] ?? 1;
} else {
    $id_user = $raw_id_js;
}


$id_user = (int)$id_user; 

// ==========================================
// BLOK AKSI (ADD, EDIT, DELETE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_game = isset($_POST['id_game']) ? (int)$_POST['id_game'] : null;
    $nama = trim($_POST['nama_rarity'] ?? '');
    $kode = trim($_POST['kode_rarity'] ?? '');
    $id_rarity = isset($_POST['id_rarity']) ? (int)$_POST['id_rarity'] : null;

    if (($action == 'add' || $action == 'edit') && ($nama == "" || empty($id_game))) {
        echo json_encode(['status' => 'error', 'message' => 'Game dan Nama Rarity wajib diisi!']); 
        exit;
    }

    $stmt = false;
    
    // Proses Tambah
    if ($action === 'add') {
        $sql = "INSERT INTO dbo.rarity (id_game, nama_rarity, kode_rarity, created_by, created_date, aktif) VALUES (?, ?, ?, ?, GETDATE(), 1)";
        $stmt = sqlsrv_query($conn, $sql, [$id_game, $nama, $kode, $id_user]);
    } 
    // Proses Ubah
    else if ($action === 'edit') {
        $aktif = isset($_POST['aktif']) ? (int)$_POST['aktif'] : 1;
        $sql = "UPDATE dbo.rarity SET id_game=?, nama_rarity=?, kode_rarity=?, modified_by=?, modified_date=GETDATE(), aktif=? WHERE id_rarity=?";
        $stmt = sqlsrv_query($conn, $sql, [$id_game, $nama, $kode, $id_user, $aktif, $id_rarity]);
    } 
    // Proses Hapus (Nonaktifkan)
    else if ($action === 'delete') {
        $sql = "UPDATE dbo.rarity SET aktif=0, modified_by=?, modified_date=GETDATE() WHERE id_rarity=?";
        $stmt = sqlsrv_query($conn, $sql, [$id_user, $id_rarity]);
    }
    //proses restore
    else if($action === 'restore'){
        $sql = "UPDATE dbo.rarity SET aktif=1, modified_by=?, modified_date=GETDATE() WHERE id_rarity=?";
        $stmt = sqlsrv_query($conn, $sql, [$id_user, $id_rarity]);
    }


    if ($stmt) {
        echo json_encode(['status' => 'success', 'message' => '']);
    } else {
        $errors = sqlsrv_errors();
        $error_msg = $errors != null ? $errors[0]['message'] : 'Gagal mengeksekusi kueri pangkalan data.';
        echo json_encode(['status' => 'error', 'message' => 'KESALAHAN SQL: ' . $error_msg]);
    }
    exit;
}

// ==========================================
// BLOK PENARIKAN DATA DETAIL (GET)
// ==========================================
if (isset($_GET['get_detail'])) {
    $sql = "SELECT 
                r.id_rarity AS id_rarity, 
                r.id_game AS id_game, 
                r.nama_rarity AS nama_rarity, 
                r.kode_rarity AS kode_rarity, 
                r.aktif AS aktif, 
                r.created_date AS created_date, 
                r.modified_date AS modified_date, 
                k1.username AS creator, 
                k2.username AS modifier  
            FROM dbo.rarity r 
            LEFT JOIN dbo.pengguna k1 ON r.created_by = k1.id_pengguna
            LEFT JOIN dbo.pengguna k2 ON r.modified_by = k2.id_pengguna 
            WHERE r.id_rarity = ?";
            
    $stmt = sqlsrv_query($conn, $sql, [(int)$_GET['get_detail']]);

    if ($stmt === false) {
        $errors = sqlsrv_errors();
        $error_msg = $errors != null ? $errors[0]['message'] : 'Gagal menarik data detail.';
        echo json_encode(['error' => 'KESALAHAN SQL: ' . $error_msg]);
        exit;
    }

    $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if($data) {
        $data['created_date'] = (isset($data['created_date']) && is_a($data['created_date'], 'DateTime')) ? $data['created_date']->format('d-M-Y H:i') : '-';
        $data['modified_date'] = (isset($data['modified_date']) && is_a($data['modified_date'], 'DateTime')) ? $data['modified_date']->format('d-M-Y H:i') : '-';
        
        echo json_encode($data); 
    } else {
        echo json_encode(['error' => 'ID Rarity tidak ditemukan di pangkalan data.']);
    }
    exit;
}
?>