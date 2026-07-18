<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once '../../connection.php';
require_once __DIR__ . '/../../auth/session.php';

ob_start();

try {
    // Master data produk: khusus pegawai. id_user dipakai sebagai jejak audit
    // (created_by/modified_by), jadi WAJIB dari session — kalau dari browser,
    // user bisa mengaku sebagai orang lain di catatan audit.
    $id_user = auth_api_require_role(auth_staff_roles())['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action   = $_POST['action'] ?? '';
        $id_produk = isset($_POST['id_produk']) && $_POST['id_produk'] !== '' ? (int)$_POST['id_produk'] : 0;
        $path_foto_simpan = null;

        // Inisialisasi variabel agar tidak undefined saat dipakai di luar if-block
        $nama      = '';
        $id_game   = null;
        $tipe      = '';
        $id_set    = null;
        $id_rarity = null;
        $kondisi   = null;
        $harga_jual = 0;
        $harga_beli = 0;
        $stok       = 0;
        $deskripsi  = '';

        if ($action === 'add' || $action === 'edit') {
            $nama       = trim($_POST['nama_produk'] ?? '');
            $id_game    = !empty($_POST['id_game']) ? (int)$_POST['id_game'] : null;
            $id_supplier = !empty($_POST['id_supplier']) ? (int)$_POST['id_supplier'] : null;
            $tipe       = $_POST['tipe_produk'] ?? '';
            $id_set     = !empty($_POST['id_set']) ? (int)$_POST['id_set'] : null;
            $id_rarity  = !empty($_POST['id_rarity']) ? (int)$_POST['id_rarity'] : null;
            $kondisi    = !empty($_POST['kondisi']) ? $_POST['kondisi'] : null;
            $harga_jual = (float)($_POST['harga_jual'] ?? 0);
            $harga_beli = (float)($_POST['harga_beli'] ?? 0);
            $stok       = (int)($_POST['stok'] ?? 0);
            $deskripsi  = $_POST['deskripsi'] ?? '';

            if (!$nama || !$tipe) throw new Exception('Name and Type fields are required!');

            $stmt_cek = sqlsrv_query($conn, 'SELECT dbo.udf_CheckDuplicateProduk(?, ?, ?, ?) AS total', [$nama, $id_game, $id_set, $id_produk]);
            if ($stmt_cek === false) throw new Exception('Duplicate check query failed.');
            $row_cek = sqlsrv_fetch_array($stmt_cek, SQLSRV_FETCH_ASSOC);
            if ($row_cek && $row_cek['total'] > 0) throw new Exception("Product '$nama' already exists in this Game and Set!");

            if (isset($_FILES['foto_produk']) && $_FILES['foto_produk']['error'] === UPLOAD_ERR_OK) {
                $ext           = strtolower(pathinfo($_FILES['foto_produk']['name'], PATHINFO_EXTENSION));
                $new_file_name = 'PROD_' . time() . '_' . uniqid() . '.' . $ext;
                $target_dir    = '../../assets/image/products/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

                if (move_uploaded_file($_FILES['foto_produk']['tmp_name'], $target_dir . $new_file_name)) {
                    $path_foto_simpan = $new_file_name; // simpan nama file saja
                    if ($action === 'edit') {
                        $stmt_old = sqlsrv_query($conn, 'SELECT dbo.udf_GetProdukPhoto(?) AS foto', [$id_produk]);
                        if ($stmt_old) {
                            $row_old = sqlsrv_fetch_array($stmt_old, SQLSRV_FETCH_ASSOC);
                            // basename() agar kompatibel dgn data lama yg mungkin masih ada prefix folder
                            if ($row_old && !empty($row_old['foto'])) @unlink('../../assets/image/products/' . basename($row_old['foto']));
                        }
                    }
                }
            }

            // Null-kan field yang tidak relevan
            if (!in_array($tipe, ['Single Card', 'Booster Pack', 'Booster Box'])) $id_set = null;
            if ($tipe !== 'Single Card') { $id_rarity = null; $kondisi = null; }
        }

        $params = [$action, $id_produk, $id_game, $id_supplier, $tipe, $nama, $harga_jual, $harga_beli, $stok, $deskripsi, $id_rarity, $id_set, $kondisi, $path_foto_simpan, $id_user];
        $stmt   = sqlsrv_query($conn, '{CALL dbo.sp_ManageProduk(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}', $params);
        if ($stmt === false) throw new Exception('Query Failed: ' . (sqlsrv_errors()[0]['message'] ?? 'Unknown error'));

        ob_clean();
        echo json_encode(['status' => 'success']);
        exit;
    }
    if (isset($_GET['list'])) {
        // 7 baris per halaman — samakan dengan render awal (fetch_dashboard limit_produk=7)
        // agar tidak flicker 7 -> 5 saat MasterFilter memuat ulang.
        $limit  = 7;
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        
        $statusParam = ($status === '') ? -1 : (int)$status;
        $sortBy      = $_GET['sort_by'] ?? 'nama_produk';
        $sortOrder   = strtoupper($_GET['sort_order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $tipeProduk  = trim($_GET['tipe_produk'] ?? ''); // filter tipe produk (kosong = semua)

        $sql  = "{CALL dbo.sp_GetProductList(?, ?, ?, ?, ?, ?, ?)}";
        $params = [$search, $sortBy, $sortOrder, $statusParam, $page, $limit, $tipeProduk];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Failed to execute SP']);
            exit;
        }

        $total_rows = 0;
        if ($rCount = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $total_rows = (int)$rCount['total_rows'];
        }

        sqlsrv_next_result($stmt);
        $rows = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $r;
        }

        $total_pages = max(1, (int)ceil($total_rows / $limit));
        $offset      = ($page - 1) * $limit;

        ob_clean();
        echo json_encode(['status' => 'success', 'data' => $rows, 'total_pages' => $total_pages, 'current_page' => $page, 'offset' => $offset], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // [FIX] GET: get_detail
    if (isset($_GET['get_detail'])) {
        $id   = (int)$_GET['get_detail'];
        $stmt = sqlsrv_query($conn, '{CALL dbo.sp_GetProdukDetail(?)}', [$id]);
        if ($stmt === false) throw new Exception('Failed to fetch product details.');

        $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        ob_clean();
        if ($data) {
            // [FIX] Konversi DateTime sebelum json_encode
            $data['created_date']  = ($data['created_date'] instanceof DateTime) ? $data['created_date']->format('d M Y, H:i') : '-';
            $data['modified_date'] = ($data['modified_date'] instanceof DateTime) ? $data['modified_date']->format('d M Y, H:i') : '-';
            // [FIX] Cast tipe numerik agar tidak jadi string
            $data['harga_jual'] = (float)($data['harga_jual'] ?? 0);
            $data['harga_beli'] = (float)($data['harga_beli'] ?? 0);
            $data['stok']       = (int)($data['stok'] ?? 0);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('Product data not found.');
        }
        exit;
    }

    if (isset($_GET['search_game'])) {
        $keyword = trim($_GET['search_game']);
        $stmt = sqlsrv_query($conn, '{CALL dbo.sp_GetDropdownGame(?)}', [$keyword]);
        $res  = [];
        if ($stmt) while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $res[] = $row;
        ob_clean();
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (isset($_GET['search_set'])) {
        $id_game = (int)$_GET['id_game'];
        $keyword = trim($_GET['search_set']);
        $stmt = sqlsrv_query($conn, '{CALL dbo.sp_GetDropdownSet(?, ?)}', [$id_game, $keyword]);
        $res  = [];
        if ($stmt) while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $res[] = $row;
        ob_clean();
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (isset($_GET['get_rarity_list'])) {
        $id_game = (int)$_GET['id_game'];
        $stmt = sqlsrv_query($conn, '{CALL dbo.sp_GetDropdownRarity(?)}', [$id_game]);
        $res  = [];
        if ($stmt) while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $res[] = $row;
        ob_clean();
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (isset($_GET['search_supplier'])) {
        $keyword = trim($_GET['search_supplier']);
        $stmt = sqlsrv_query($conn, '{CALL dbo.sp_GetSearchSupplier(?)}', [$keyword]);
        $res  = [];
        if ($stmt) while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $res[] = $row;
        ob_clean();
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }

} catch (Throwable $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>