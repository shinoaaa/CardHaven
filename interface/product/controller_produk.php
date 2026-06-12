<?php
session_start();
error_reporting(0); // Matikan pemaparan galat standar
ini_set('display_errors', 0); // Kunci agar HTML tidak bocor ke JSON
header('Content-Type: application/json');

require_once '../../connection.php';

try {
    $id_user = $_POST['id_pengguna_js'] ?? ($_SESSION['id_pengguna'] ?? 0);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $id_produk = isset($_POST['id_produk']) && $_POST['id_produk'] !== '' ? (int)$_POST['id_produk'] : 0;

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
            
            if ($stok < 1) throw new Exception("Stock must be at least 1!");
            $deskripsi = $_POST['deskripsi'] ?? '';
            
            if (!$nama || !$id_game || !$tipe) throw new Exception("Name, Game, and Type fields are required!");

            // 1. PENGAMANAN CHECK DUPLICATE
            $check_sql = "SELECT id_produk FROM dbo.produk WHERE nama_produk = ? AND id_game = ? AND ISNULL(id_set, 0) = ? AND id_produk <> ? AND is_deleted = 0";
            $check_stmt = sqlsrv_query($conn, $check_sql, [$nama, $id_game, ($id_set ?? 0), $id_produk]);
            if ($check_stmt === false) {
                $err = sqlsrv_errors();
                throw new Exception("Duplicate Check Query Failed: " . ($err[0]['message'] ?? 'Unknown Error'));
            }
            if (sqlsrv_has_rows($check_stmt)) {
                throw new Exception("Product '$nama' already exists in this Game and Set!");
            }

            // --- BAGIAN PENARIKAN FOTO LAMA DAN UPLOAD FOTO DIHAPUS SEMENTARA ---

            $path_foto_simpan = null;
            if (isset($_FILES['foto_produk']) && $_FILES['foto_produk']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['foto_produk']['tmp_name'];
                $file_name = $_FILES['foto_produk']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Buat nama file unik: PROD_Waktu_Random.ext
                $new_file_name = "PROD_" . time() . "_" . uniqid() . "." . $file_ext;
                $target_dir = "../../image-profile/"; // Path folder tujuan
                
                // Buat folder jika belum ada
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

                if (move_uploaded_file($file_tmp, $target_dir . $new_file_name)) {
                    $path_foto_simpan = "image-profile/" . $new_file_name;

                    // Jika sedang EDIT, hapus file foto lama dari folder image-profile
                    if ($action === 'edit') {
                        $sql_old = "SELECT foto FROM dbo.produk WHERE id_produk = ?";
                        $stmt_old = sqlsrv_query($conn, $sql_old, [$id_produk]);
                        $row_old = sqlsrv_fetch_array($stmt_old, SQLSRV_FETCH_ASSOC);
                        if ($row_old && $row_old['foto_produk']) {
                            $old_file_path = "../../" . $row_old['foto_produk'];
                            if (file_exists($old_file_path)) unlink($old_file_path);
                        }
                    }
                }
            }

            if (!in_array($tipe, ['Single Card', 'Booster Pack', 'Booster Box'])) $id_set = null;
            if ($tipe !== 'Single Card') { $id_rarity = null; $kondisi = null; }

            // 4. PENENTUAN KUERI UTAMA (Tanpa kolom foto_produk)
            if ($action === 'add') {
                $sql = "INSERT INTO dbo.produk (id_game, tipe_produk, nama_produk, harga_jual, harga_beli, stok, deskripsi, id_rarity, id_set, kondisi, created_by, created_date, status, foto, is_deleted) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), 1, ?, 0)";
                $params = [$id_game, $tipe, $nama, $harga_jual, $harga_beli, $stok, $deskripsi, $id_rarity, $id_set, $kondisi, $id_user, $path_foto_simpan];
            } else if ($action === 'edit') {
                $sql = "UPDATE dbo.produk SET id_game=?, tipe_produk=?, nama_produk=?, harga_jual=?, harga_beli=?, stok=?, deskripsi=?, id_rarity=?, id_set=?, kondisi=?, modified_by=?, modified_date=GETDATE()";
                $params = [$id_game, $tipe, $nama, $harga_jual, $harga_beli, $stok, $deskripsi, $id_rarity, $id_set, $kondisi, $id_user];
                
    
                if ($path_foto_simpan) {
                    $sql .= ", foto = ?";
                    $params[] = $path_foto_simpan;
                }
                
                $sql .= " WHERE id_produk = ?";
                $params[] = $id_produk;
            }
        }
        else if ($action === 'aktifkan' || $action === 'nonaktifkan') {
            $status = ($action === 'aktifkan') ? 1 : 0;
            $sql = "UPDATE dbo.produk SET status=?, modified_by=?, modified_date=GETDATE() WHERE id_produk=?";
            $params = [$status, $id_user, $id_produk];
        } 
        else if ($action === 'delete' || $action === 'restore') {
            $status = ($action === 'delete') ? 1 : 0;
            $sql = "UPDATE dbo.produk SET is_deleted=?, deleted_by=?, deleted_date=GETDATE() WHERE id_produk=?";
            $params = [$status, $id_user, $id_produk];
        } else {
            throw new Exception("Action '$action' is not recognized by the system.");
        }

        // 5. PENGAMANAN EKSEKUSI KUERI UTAMA
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $msg = $errors ? $errors[0]['message'] : "Unknown SQL error";
            throw new Exception("Query Execution Failed: " . $msg);
        }

        echo json_encode(['status' => 'success']);
        exit;
    }

    // ==========================================
    // BLOK FETCH DATA
    // ==========================================
    if (isset($_GET['get_detail'])) {
        $id = $_GET['get_detail'];
        $sql = "SELECT p.*, g.nama_game, s.nama_set,r.nama_rarity, r.kode_rarity, u1.username as creator, u2.username as modifier 
                FROM dbo.produk p 
                LEFT JOIN dbo.game g ON p.id_game = g.id_game
                LEFT JOIN dbo.set_kartu s ON p.id_set = s.id_set
                LEFT JOIN dbo.rarity r ON p.id_rarity = r.id_rarity
                LEFT JOIN dbo.pengguna u1 ON p.created_by = u1.id_pengguna
                LEFT JOIN dbo.pengguna u2 ON p.modified_by = u2.id_pengguna
                WHERE p.id_produk = ? AND p.is_deleted = 0";
        $stmt = sqlsrv_query($conn, $sql, [$id]);
        
        if ($stmt === false) throw new Exception("Failed to fetch product details.");
        
        $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($data) {
            $data['created_date'] = ($data['created_date'] instanceof DateTime) ? $data['created_date']->format('d M Y, H:i') : '-';
            $data['modified_date'] = ($data['modified_date'] instanceof DateTime) ? $data['modified_date']->format('d M Y, H:i') : '-';
            echo json_encode($data);
        } else {
            throw new Exception("Product data not found.");
        }
        exit;
    }

    if (isset($_GET['search_game'])) {
        $key = "%" . $_GET['search_game'] . "%";
        $sql = "SELECT id_game, nama_game FROM dbo.game WHERE nama_game LIKE ? AND aktif = 1 AND is_deleted = 0 ORDER BY nama_game ASC";
        $stmt = sqlsrv_query($conn, $sql, [$key]);
        $res = [];
        if ($stmt !== false) {
            while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { $res[] = $row; }
        }
        echo json_encode($res); exit;
    }

    if (isset($_GET['search_set'])) {
        $key = "%" . $_GET['search_set'] . "%";
        $id_game = $_GET['id_game'];
        $sql = "SELECT id_set, nama_set FROM dbo.set_kartu WHERE nama_set LIKE ? AND id_game = ? AND aktif = 1 AND is_deleted = 0 ORDER BY nama_set ASC";
        $stmt = sqlsrv_query($conn, $sql, [$key, $id_game]);
        $res = [];
        if ($stmt !== false) {
            while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { $res[] = $row; }
        }
        echo json_encode($res); exit;
    }

    if (isset($_GET['get_rarity_list'])) {
        $id_game = $_GET['id_game'];
        $sql = "SELECT id_rarity, nama_rarity, kode_rarity FROM dbo.rarity WHERE id_game = ? AND aktif = 1 AND is_deleted = 0";
        $stmt = sqlsrv_query($conn, $sql, [$id_game]);
        $res = [];
        if ($stmt !== false) {
            while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { $res[] = $row; }
        }
        echo json_encode($res); exit;
    }

} catch (Throwable $e) {
    ob_clean(); // Bersihkan sisa tag HTML/Warning sebelum mencetak JSON
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}