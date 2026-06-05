<?php
session_start();
header('Content-Type: application/json');
require_once 'connection.php'; 

// Proteksi akses biar karyawan doang yg bisa
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['user_type'] !== 'karyawan') {
    echo json_encode(['status' => 'error', 'message' => '403 Forbidden']);
    exit;
}

$action = $_GET['action'] ?? 'read';
$user_id = $_SESSION['user_id'] ?? 1; // Fallback ke 1 untuk uji coba jika sesi kosong

switch ($action) {
    case 'read':
        // ngambilin data rarity ama nama gamenya yg aktif doang
        $sql = "SELECT r.id_rarity, r.nama_rarity, r.kode_rarity, g.nama_game 
                FROM rarity r 
                LEFT JOIN game g ON r.id_game = g.id_game 
                WHERE r.aktif = 1 
                ORDER BY r.id_rarity DESC";
        $stmt = sqlsrv_query($conn, $sql);
        $data = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menarik data']);
        }
        break;

    case 'read_games':
        // ngisi opsi dropdown game di form gitulah
        $sql = "SELECT id_game, nama_game FROM game WHERE aktif = 1 ORDER BY nama_game ASC";
        $stmt = sqlsrv_query($conn, $sql);
        $data = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }
            echo json_encode(['status' => 'success', 'data' => $data]);
        }
        break;

    case 'save':
        $id_rarity = (int)($_POST['id_rarity'] ?? 0);
        $id_game = (int)($_POST['id_game'] ?? 0);
        $nama_rarity = trim($_POST['nama_rarity'] ?? '');
        $kode_rarity = trim($_POST['kode_rarity'] ?? '');

        if (empty($nama_rarity) || $id_game === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Game dan Nama Rarity wajib diisi']);
            exit;
        }

        if ($id_rarity === 0) {
            // Logika Create Baru
            $sql = "INSERT INTO rarity (id_game, nama_rarity, kode_rarity, created_by, created_date, aktif) 
                    VALUES (?, ?, ?, ?, GETDATE(), 1)";
            $params = array($id_game, $nama_rarity, $kode_rarity, $user_id);
        } else {
            // Logika Update
            $sql = "UPDATE rarity SET id_game = ?, nama_rarity = ?, kode_rarity = ?, modified_by = ?, modified_date = GETDATE() 
                    WHERE id_rarity = ?";
            $params = array($id_game, $nama_rarity, $kode_rarity, $user_id, $id_rarity);
        }

        $stmt = sqlsrv_prepare($conn, $sql, $params);
        if (sqlsrv_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'Data Rarity berhasil disimpan']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Kesalahan sistem saat menyimpan']);
        }
        break;

    case 'delete':
        // Logika Soft Delete (aktif = 0)
        $id_rarity = (int)($_POST['id_rarity'] ?? 0);
        if ($id_rarity > 0) {
            $sql = "UPDATE rarity SET aktif = 0, modified_by = ?, modified_date = GETDATE() WHERE id_rarity = ?";
            $params = array($user_id, $id_rarity);
            $stmt = sqlsrv_prepare($conn, $sql, $params);
            if (sqlsrv_execute($stmt)) {
                echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus']);
                exit;
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data']);
        break;
}
?>