<?php
session_start();
require_once '../../connection.php';
header('Content-Type: application/json');

// Ambil ID dari POST (JS) atau Session
$id_user = $_POST['id_karyawan_js'] ?? ($_SESSION['id_karyawan'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $nama = trim($_POST['nama_game'] ?? '');
    $dev = trim($_POST['developer'] ?? '');
    $id_game = $_POST['id_game'] ?? null;

    // 1. Validasi Kosong
    if (($action == 'add' || $action == 'edit') && ($nama == "" || $dev == "")) {
        echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi!']); exit;
    }

    // 2. Validasi Duplikat Nama
    if ($action == 'add' || $action == 'edit') {
        $sql_cek = "SELECT COUNT(*) as total FROM dbo.game WHERE nama_game = ?";
        $params_cek = [$nama];
        if ($action == 'edit') { $sql_cek .= " AND id_game <> ?"; $params_cek[] = $id_game; }
        
        $stmt_cek = sqlsrv_query($conn, $sql_cek, $params_cek);
        if (sqlsrv_fetch_array($stmt_cek, SQLSRV_FETCH_ASSOC)['total'] > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Nama game sudah terdaftar!']); exit;
        }
    }

    // 3. Eksekusi Action
    if ($action === 'add') {
        $sql = "INSERT INTO dbo.game (nama_game, developer, created_by, created_date, aktif) VALUES (?, ?, ?, GETDATE(), 1)";
        $stmt = sqlsrv_query($conn, $sql, [$nama, $dev, $id_user]);
    } else if ($action === 'edit') {
        $aktif = $_POST['aktif'];
        $sql = "UPDATE dbo.game SET nama_game=?, developer=?, modified_by=?, modified_date=GETDATE(), aktif=? WHERE id_game=?";
        $stmt = sqlsrv_query($conn, $sql, [$nama, $dev, $id_user, $aktif, $id_game]);
    } else if ($action === 'delete') {
        $sql = "UPDATE dbo.game SET aktif=0, modified_by=?, modified_date=GETDATE() WHERE id_game=?";
        $stmt = sqlsrv_query($conn, $sql, [$id_user, $id_game]);
    }else if ($action === 'restore') {
        $sql = "UPDATE dbo.game SET aktif=1, modified_by=?, modified_date=GETDATE() WHERE id_game=?";
        $stmt = sqlsrv_query($conn, $sql, [$id_user, $id_game]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Action tidak valid!']);
        exit;
    }

    echo json_encode(['status' => $stmt ? 'success' : 'error', 'message' => $stmt ? '' : 'Database error']);
    exit;
}

// Fetch Detail
if (isset($_GET['get_detail'])) {
    $id = $_GET['get_detail'];
    
    // Query hanya ke tabel game
    $sql = "SELECT * FROM dbo.game WHERE id_game = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id]);

    if ($stmt === false) {
        die(json_encode(['error' => sqlsrv_errors()]));
    }

    $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($data) {
        // Format Tanggal (Sangat penting agar tidak error di JS)
        $data['created_date'] = ($data['created_date'] instanceof DateTime) ? $data['created_date']->format('d-M-Y H:i') : '-';
        $data['modified_date'] = ($data['modified_date'] instanceof DateTime) ? $data['modified_date']->format('d-M-Y H:i') : '-';
        
        // Karena tidak pakai JOIN, kita kirim ID karyawannya saja sebagai teks
        $data['creator'] = "User ID: " . ($data['created_by'] ?? '-');
        $data['modifier'] = ($data['modified_by']) ? "User ID: " . $data['modified_by'] : '-';

        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Data tidak ditemukan']);
    }
    exit;
}