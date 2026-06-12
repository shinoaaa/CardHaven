<?php
session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../../connection.php';

$raw_id_js = $_POST['id_pengguna_js'] ?? '';

if ($raw_id_js === '' || $raw_id_js === 'undefined' || $raw_id_js === 'null') {
    $id_user = $_SESSION['id_pengguna'] ?? 1;
} else {
    $id_user = $raw_id_js;
}

$id_user = (int)$id_user; 

if (isset($_GET['check_duplicate'])) {
    $id_game = (int)$_GET['id_game'];
    $nama = $_GET['nama_rarity'] ?? '';
    $kode = $_GET['kode_rarity'] ?? '';
    $id_rarity = (int)($_GET['exclude_id'] ?? 0);

    $sql = "SELECT COUNT(*) as total FROM dbo.rarity 
            WHERE id_game = ? AND (nama_rarity = ? OR kode_rarity = ?) AND id_rarity <> ? AND is_deleted = 0";
    $stmt = sqlsrv_query($conn, $sql, [$id_game, $nama, $kode, $id_rarity]);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    echo json_encode(['exists' => $row['total'] > 0]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_game = isset($_POST['id_game']) ? (int)$_POST['id_game'] : null;
    $nama = trim($_POST['nama_rarity'] ?? '');
    $kode = trim($_POST['kode_rarity'] ?? '');
    $id_rarity = isset($_POST['id_rarity']) ? (int)$_POST['id_rarity'] : null;

    if ($action === 'add' || $action === 'edit') {
    if (empty($id_game)) {
        echo json_encode(['status' => 'error', 'message' => 'Game is required!']); exit;
    }
    if ($nama === '') {
        echo json_encode(['status' => 'error', 'message' => 'Rarity name is required!']); exit;
    }
    if (strlen($nama) > 20) {
        echo json_encode(['status' => 'error', 'message' => 'Rarity name must not exceed 20 characters!']); exit;
    }
    if ($kode === '') {
        echo json_encode(['status' => 'error', 'message' => 'Rarity code is required!']); exit;
    }
    if (strlen($kode) > 20) {
        echo json_encode(['status' => 'error', 'message' => 'Rarity code must not exceed 20 characters!']); exit;
    }
}
    if ($action === 'add' || $action === 'edit') {
        $check_sql = "SELECT nama_rarity, kode_rarity FROM dbo.rarity 
                    WHERE id_game = ? AND (nama_rarity = ? OR kode_rarity = ?) AND is_deleted = 0";
        $params = [$id_game, $nama, $kode];

        if ($action === 'edit') {
            $check_sql .= " AND id_rarity <> ?";
            $params[] = $id_rarity;
        }

        $check_stmt = sqlsrv_query($conn, $check_sql, $params);
        $duplicate = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC);

        if ($duplicate) {
            $pesan = "";
            if (strcasecmp($duplicate['nama_rarity'], $nama) == 0) {
                $pesan = "Rarity Name '$nama' is already registered in this game!";
            } else {
                $pesan = "Rarity Code '$kode' is already registered in this game!";
            }
            echo json_encode(['status' => 'error', 'message' => $pesan]);
            exit;
        }
    }

    $stmt = false;
    
    if ($action === 'add') {
        $sql = "INSERT INTO dbo.rarity (id_game, nama_rarity, kode_rarity, created_by, created_date, aktif,is_deleted) VALUES (?, ?, ?, ?, GETDATE(), 1,0)";
        $stmt = sqlsrv_query($conn, $sql, [$id_game, $nama, $kode, $id_user]);
    } 
    else if ($action === 'edit') {
        $sql = "UPDATE dbo.rarity SET id_game=?, nama_rarity=?, kode_rarity=?, modified_by=?, modified_date=GETDATE() WHERE id_rarity=?";
        $stmt = sqlsrv_query($conn, $sql, [$id_game, $nama, $kode, $id_user, $id_rarity]);
    } 
    else if ($action === 'delete') {
        $sql = "UPDATE dbo.rarity SET is_deleted=1, deleted_by=?, deleted_date=GETDATE() WHERE id_rarity=?";
        $stmt = sqlsrv_query($conn, $sql, [$id_user, $id_rarity]);
    }
    else if($action === 'restore'){
        $sql = "UPDATE dbo.rarity SET is_deleted=0, modified_by=?, modified_date=GETDATE() WHERE id_rarity=?";
        $stmt = sqlsrv_query($conn, $sql, [$id_user, $id_rarity]);
    }
    else if ($action === 'aktifkan' || $action === 'nonaktifkan') {
        $aktif = $action === 'aktifkan' ? 1 : 0;
        $sql = "UPDATE dbo.rarity SET aktif=?, modified_by=?, modified_date=GETDATE() WHERE id_rarity=?";
        $stmt = sqlsrv_query($conn, $sql, [$aktif, $id_user, $id_rarity]);
    }

    if ($stmt) {
        echo json_encode(['status' => 'success', 'message' => '']);
    } else {
        $errors = sqlsrv_errors();
        $error_msg = $errors != null ? $errors[0]['message'] : 'Failed to execute database query.';
        echo json_encode(['status' => 'error', 'message' => 'SQL ERROR: ' . $error_msg]);
    }
    exit;
}

if (isset($_GET['get_detail'])) {
$sql = "SELECT 
            r.id_rarity, r.id_game, r.nama_rarity, r.kode_rarity, r.aktif,
            r.created_date, r.modified_date,
            g.nama_game,
            k1.username AS creator, k2.username AS modifier
        FROM dbo.rarity r
        LEFT JOIN dbo.game g ON r.id_game = g.id_game
        LEFT JOIN dbo.pengguna k1 ON r.created_by = k1.id_pengguna
        LEFT JOIN dbo.pengguna k2 ON r.modified_by = k2.id_pengguna
        WHERE r.id_rarity = ? AND r.is_deleted = 0";
            
    $stmt = sqlsrv_query($conn, $sql, [(int)$_GET['get_detail']]);

    if ($stmt === false) {
        $errors = sqlsrv_errors();
        $error_msg = $errors != null ? $errors[0]['message'] : 'Failed to fetch detail data.';
        echo json_encode(['error' => 'SQL ERROR: ' . $error_msg]);
        exit;
    }

    $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if($data) {
        $data['created_date'] = (isset($data['created_date']) && is_a($data['created_date'], 'DateTime')) ? $data['created_date']->format('d-M-Y H:i') : '-';
        $data['modified_date'] = (isset($data['modified_date']) && is_a($data['modified_date'], 'DateTime')) ? $data['modified_date']->format('d-M-Y H:i') : '-';
        echo json_encode($data); 
    } else {
        echo json_encode(['error' => 'Rarity ID not found in the database.']);
    }
    exit;
}
?>