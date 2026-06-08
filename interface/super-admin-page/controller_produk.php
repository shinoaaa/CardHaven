<?php
session_start();
require_once '../../connection.php';
header('Content-Type: application/json');

$id_user = $_POST['id_pengguna_js'] ?? ($_SESSION['id_pengguna'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_produk = isset($_POST['id_produk']) ? (int)$_POST['id_produk'] : null;

    try {
        if ($action === 'add' || $action === 'edit') {
            $nama = trim($_POST['nama_produk'] ?? '');
            $id_game = !empty($_POST['id_game']) ? (int)$_POST['id_game'] : null;
            $tipe = $_POST['tipe_produk'] ?? '';
            $id_set = !empty($_POST['id_set']) ? (int)$_POST['id_set'] : null;
            $id_rarity = !empty($_POST['id_rarity']) ? (int)$_POST['id_rarity'] : null;
            $kondisi = !empty($_POST['kondisi']) ? $_POST['kondisi'] : null;
            
            $harga_jual = (float)($_POST['harga_jual'] ?? 0);
            $harga_beli = (float)($_POST['harga_beli'] ?? 0);
            $stok = (int)($_POST['stok'] ?? 0);
            if ($stok < 1) throw new Exception("Stok minimal 1!");
            $deskripsi = $_POST['deskripsi'] ?? '';

            // --- VALIDASI SERVER-SIDE ---
            if (!$nama || !$id_game || !$tipe) {
                throw new Exception("Field Nama, Game, dan Tipe wajib diisi!");
            }

            // --- VALIDASI DUPLIKAT (Mencegah Nama yang sama di Game & Set yang sama) ---
            $check_sql = "SELECT id_produk FROM dbo.produk WHERE nama_produk = ? AND id_game = ? AND ISNULL(id_set, 0) = ? AND id_produk <> ?";
            $check_stmt = sqlsrv_query($conn, $check_sql, [$nama, $id_game, ($id_set ?? 0), ($id_produk ?? 0)]);
            if (sqlsrv_has_rows($check_stmt)) {
                throw new Exception("Produk '$nama' sudah ada dalam Game dan Set ini!");
            }

            // Logika pembersihan data berdasarkan tipe
            if (!in_array($tipe, ['Single Card', 'Booster Pack', 'Booster Box'])) {
                $id_set = null;
            }
            if ($tipe !== 'Single Card') {
                $id_rarity = null;
                $kondisi = null;
            }

            if ($action === 'add') {
                $sql = "INSERT INTO dbo.produk (id_game, tipe_produk, nama_produk, harga_jual, harga_beli, stok, deskripsi, id_rarity, id_set, kondisi, created_by, created_date, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), 1)";
                $params = [$id_game, $tipe, $nama, $harga_jual, $harga_beli, $stok, $deskripsi, $id_rarity, $id_set, $kondisi, $id_user];
            } else {
                $status = $_POST['status'] ?? 1;
                $sql = "UPDATE dbo.produk SET id_game=?, tipe_produk=?, nama_produk=?, harga_jual=?, harga_beli=?, stok=?, deskripsi=?, id_rarity=?, id_set=?, kondisi=?, modified_by=?, modified_date=GETDATE(), status=? WHERE id_produk=?";
                $params = [$id_game, $tipe, $nama, $harga_jual, $harga_beli, $stok, $deskripsi, $id_rarity, $id_set, $kondisi, $id_user, $status, $id_produk];
            }
        } 
        else if ($action === 'delete' || $action === 'restore') {
            $status = ($action === 'delete') ? 0 : 1;
            $sql = "UPDATE dbo.produk SET status=?, modified_by=?, modified_date=GETDATE() WHERE id_produk=?";
            $params = [$status, $id_user, $id_produk];
        }

        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception("SQL Error: " . $errors[0]['message']);
        }

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
    if ($data) {
        $data['created_date'] = ($data['created_date'] instanceof DateTime) ? $data['created_date']->format('d M Y, H:i') : '-';
        $data['modified_date'] = ($data['modified_date'] instanceof DateTime) ? $data['modified_date']->format('d M Y, H:i') : '-';
        
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Data tidak ditemukan']);
    }
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

