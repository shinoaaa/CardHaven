<?php
session_start();
require_once '../../connection.php';
header('Content-Type: application/json');

// Ambil ID dari POST (JS) atau Session
$id_user = $_POST['id_karyawan_js'] ?? ($_SESSION['id_karyawan'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_game = $_POST['id_game'] ?? '';
    $nama = trim($_POST['nama_rarity'] ?? '');
    $kode = trim($_POST['kode_rarity'] ?? '');
    $id_rarity = $_POST['id_rarity'] ?? null;

    // 1. Validasi Kosong
    if (($action == 'add' || $action == 'edit') && ($nama == "" || empty($id_game))) {
        echo json_encode(['status' => 'error', 'message' => 'Game dan Nama Rarity wajib diisi!']); 
        exit;
    }

    // 2. Validasi Duplikat Nama (Spesifik per Game)
    if ($action == 'add' || $action == 'edit') {
        // Pengecekan ganda: Nama Rarity di dalam Game yang sama
        $sql_cek = "SELECT COUNT(*) as total FROM dbo.rarity WHERE nama_rarity = ? AND id_game = ?";
        $params_cek = [$nama, $id_game];
        
        if ($action == 'edit') { 
            $sql_cek .= " AND id_rarity <> ?"; 
            $params_cek[] = $id_rarity; 
        }
        
        $stmt_cek = sqlsrv_query($conn, $sql_cek, $params_cek);
        if (sqlsrv_fetch_array($stmt_cek, SQLSRV_FETCH_ASSOC)['total'] > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Nama Rarity ini sudah terdaftar di game tersebut!']); 
            exit;
        }
    }

    // 3. Eksekusi Action
    if ($action === 'add') {
        $sql = "INSERT INTO dbo.rarity (id_game, nama_rarity, kode_rarity, created_by, created_date, aktif) VALUES (?, ?, ?, ?, GETDATE(), 1)";
        $stmt = sqlsrv_query($conn, $sql, [$id_game, $nama, $kode, $id_user]);
    } else if ($action === 'edit') {
        $aktif = $_POST['aktif'] ?? 1;
        $sql = "UPDATE dbo.rarity SET id_game=?, nama_rarity=?, kode_rarity=?, modified_by=?, modified_date=GETDATE(), aktif=? WHERE id_rarity=?";
        $stmt = sqlsrv_query($conn, $sql, [$id_game, $nama, $kode, $id_user, $aktif, $id_rarity]);
    } else if ($action === 'delete') {
        $sql = "UPDATE dbo.rarity SET aktif=0, modified_by=?, modified_date=GETDATE() WHERE id_rarity=?";
        $stmt = sqlsrv_query($conn, $sql, [$id_user, $id_rarity]);
    }

    echo json_encode(['status' => $stmt ? 'success' : 'error', 'message' => $stmt ? '' : 'Database error atau kueri gagal dieksekusi.']);
    exit;
}

// 4. Fetch Detail untuk Modal Edit
if (isset($_GET['get_detail'])) {
    $sql = "SELECT r.*, k1.nama_karyawan as creator, k2.nama_karyawan as modifier 
            FROM dbo.rarity r 
            LEFT JOIN dbo.karyawan k1 ON r.created_by = k1.id_karyawan
            LEFT JOIN dbo.karyawan k2 ON r.modified_by = k2.id_karyawan 
            WHERE r.id_rarity = ?";
    $stmt = sqlsrv_query($conn, $sql, [$_GET['get_detail']]);
    $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    if($data) {
        $data['created_date'] = $data['created_date'] ? $data['created_date']->format('d-M-Y') : '-';
        $data['modified_date'] = $data['modified_date'] ? $data['modified_date']->format('d-M-Y') : '-';
    }
    
    echo json_encode($data); 
    exit;
}
?>