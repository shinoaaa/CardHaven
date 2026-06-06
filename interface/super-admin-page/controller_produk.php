<?php
session_start();
require_once '../../connection.php';
header('Content-Type: application/json');

$id_user = $_POST['id_pengguna_js'] ?? ($_SESSION['id_pengguna'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_produk = $_POST['id_produk'] ?? null;

    try {
        if ($action === 'add' || $action === 'edit') {
            $nama = trim($_POST['nama_produk'] ?? '');
            $id_game = ($_POST['id_game'] != "") ? $_POST['id_game'] : null;
            $tipe = $_POST['tipe_produk'] ?? '';

            if (!$nama || !$id_game || !$tipe) throw new Exception("Field Nama, Game, dan Tipe wajib diisi!");

            // Logika dinamis: Jika bukan kartu, set dan rarity otomatis dipaksa NULL
            if (in_array($tipe, ['Single Card', 'Booster Pack', 'Booster Box'])) {
                $id_set = ($_POST['id_set'] != "") ? $_POST['id_set'] : null;
            } else {
                $id_set = null;
            }

            if ($tipe === 'Single Card') {
                $id_rarity = ($_POST['id_rarity'] != "") ? $_POST['id_rarity'] : null;
                $kondisi = ($_POST['kondisi'] != "") ? $_POST['kondisi'] : null;
            } else {
                $id_rarity = null;
                $kondisi = null;
            }

            if ($action === 'add') {
                $sql = "INSERT INTO dbo.produk (id_game, tipe_produk, nama_produk, harga_jual, harga_beli, stok, deskripsi, id_rarity, id_set, kondisi, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params = [$id_game, $tipe, $nama, $_POST['harga_jual'], $_POST['harga_beli'], $_POST['stok'], $_POST['deskripsi'], $id_rarity, $id_set, $kondisi, $id_user];
            } else {
                $status = $_POST['status'] ?? 1;
                $sql = "UPDATE dbo.produk SET id_game=?, tipe_produk=?, nama_produk=?, harga_jual=?, harga_beli=?, stok=?, deskripsi=?, id_rarity=?, id_set=?, kondisi=?, modified_by=?, modified_date=GETDATE(), status=? WHERE id_produk=?";
                $params = [$id_game, $tipe, $nama, $_POST['harga_jual'], $_POST['harga_beli'], $_POST['stok'], $_POST['deskripsi'], $id_rarity, $id_set, $kondisi, $id_user, $status, $id_produk];
            }
        } else if ($action === 'delete' || $action === 'restore') {
            $status = ($action === 'delete') ? 0 : 1;
            $sql = "UPDATE dbo.produk SET status=?, modified_by=?, modified_date=GETDATE() WHERE id_produk=?";
            $params = [$status, $id_user, $id_produk];
        }

        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) throw new Exception(json_encode(sqlsrv_errors()));
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// --- API UNTUK FORM PRODUK (GET) ---

// 1. Ambil Detail Produk untuk Edit
if (isset($_GET['get_detail'])) {
    $id = $_GET['get_detail'];
    $sql = "SELECT p.*, g.nama_game, s.nama_set, u1.username as creator, u2.username as modifier 
            FROM dbo.produk p 
            LEFT JOIN dbo.game g ON p.id_game = g.id_game
            LEFT JOIN dbo.set_kartu s ON p.id_set = s.id_set
            LEFT JOIN dbo.pengguna u1 ON p.created_by = u1.id_pengguna
            LEFT JOIN dbo.pengguna u2 ON p.modified_by = u2.id_pengguna
            WHERE p.id_produk = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id]);
    $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    echo json_encode($data);
    exit;
}

// 2. Suggestion Game
if (isset($_GET['search_game'])) {
    $key = "%" . $_GET['search_game'] . "%";
    $sql = "SELECT id_game, nama_game FROM dbo.game WHERE nama_game LIKE ? AND aktif = 1";
    $stmt = sqlsrv_query($conn, $sql, [$key]);
    $res = [];
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { $res[] = $row; }
    echo json_encode($res);
    exit;
}

// 3. Suggestion Set (Berdasarkan Game terpilih)
if (isset($_GET['search_set'])) {
    $key = "%" . $_GET['search_set'] . "%";
    $id_game = $_GET['id_game'];
    $sql = "SELECT id_set, nama_set FROM dbo.set_kartu WHERE nama_set LIKE ? AND id_game = ? AND aktif = 1";
    $stmt = sqlsrv_query($conn, $sql, [$key, $id_game]);
    $res = [];
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { $res[] = $row; }
    echo json_encode($res);
    exit;
}

// 4. List Rarity (Dropdown - Berdasarkan Game terpilih)
if (isset($_GET['get_rarity_list'])) {
    $id_game = $_GET['id_game'];
    $sql = "SELECT id_rarity, nama_rarity, kode_rarity FROM dbo.rarity WHERE id_game = ? AND aktif = 1";
    $stmt = sqlsrv_query($conn, $sql, [$id_game]);
    $res = [];
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { $res[] = $row; }
    echo json_encode($res);
    exit;
}