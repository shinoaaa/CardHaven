<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);
require_once '../../connection.php';
header('Content-Type: application/json');

// [FIX] Pastikan tidak ada output PHP error yang merusak JSON
ob_start();

try {
    $id_user = (int)($_POST['id_pengguna_js'] ?? ($_SESSION['id_pengguna'] ?? 0));

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action   = $_POST['action'] ?? '';
        $nama     = trim($_POST['nama_game'] ?? '');
        $dev      = trim($_POST['developer'] ?? '');
        $id_game  = isset($_POST['id_game']) && $_POST['id_game'] !== '' ? (int)$_POST['id_game'] : null;

        if (($action === 'add' || $action === 'edit') && ($nama === '' || $dev === '')) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
            exit;
        }

        // Cek relasi sebelum delete: game tidak boleh dihapus bila masih dipakai set, produk, atau rarity.
        if ($action === 'delete') {
            $cSet = sqlsrv_query($conn, "SELECT COUNT(*) AS n FROM dbo.set_kartu WHERE id_game = ? AND is_deleted = 0", [$id_game]);
            $cPrd = sqlsrv_query($conn, "SELECT COUNT(*) AS n FROM dbo.produk    WHERE id_game = ? AND is_deleted = 0", [$id_game]);
            $cRar = sqlsrv_query($conn, "SELECT COUNT(*) AS n FROM dbo.rarity     WHERE id_game = ? AND is_deleted = 0", [$id_game]);
            $nSet = $cSet ? (int)(sqlsrv_fetch_array($cSet, SQLSRV_FETCH_ASSOC)['n'] ?? 0) : 0;
            $nPrd = $cPrd ? (int)(sqlsrv_fetch_array($cPrd, SQLSRV_FETCH_ASSOC)['n'] ?? 0) : 0;
            $nRar = $cRar ? (int)(sqlsrv_fetch_array($cRar, SQLSRV_FETCH_ASSOC)['n'] ?? 0) : 0;
            if ($nSet > 0 || $nPrd > 0 || $nRar > 0) {
                ob_clean();
                echo json_encode(['status' => 'error', 'message' => "Cannot delete: this game is still used by {$nSet} set(s), {$nPrd} Product(s) and {$nRar} rarity(s)."]);
                exit;
            }
        }

        if ($action === 'add' || $action === 'edit') {
            $stmt_cek = sqlsrv_query($conn, "SELECT dbo.udf_CheckDuplicateGame(?, ?) AS total", [$nama, $id_game]);
            if ($stmt_cek === false) throw new Exception('Duplicate check query failed.');
            $row_cek = sqlsrv_fetch_array($stmt_cek, SQLSRV_FETCH_ASSOC);
            if ($row_cek && $row_cek['total'] > 0) {
                ob_clean();
                echo json_encode(['status' => 'error', 'message' => 'Game name is already registered.']);
                exit;
            }
        }

        $path_foto_simpan = null;
        if (isset($_FILES['foto_banner']) && $_FILES['foto_banner']['error'] === UPLOAD_ERR_OK) {
            $ext          = strtolower(pathinfo($_FILES['foto_banner']['name'], PATHINFO_EXTENSION));
            $new_file_name = 'GAME_' . time() . '_' . uniqid() . '.' . $ext;
            $target_dir   = '../../image-profile/';
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            if (move_uploaded_file($_FILES['foto_banner']['tmp_name'], $target_dir . $new_file_name)) {
                $path_foto_simpan = 'image-profile/' . $new_file_name;
                if ($action === 'edit' && $id_game) {
                    $stmt_old = sqlsrv_query($conn, "SELECT dbo.udf_GetGamePhoto(?) AS foto", [$id_game]);
                    if ($stmt_old) {
                        $row_old = sqlsrv_fetch_array($stmt_old, SQLSRV_FETCH_ASSOC);
                        if ($row_old && !empty($row_old['foto'])) @unlink('../../' . $row_old['foto']);
                    }
                }
            }
        }

        $params = [$action, $id_game, $nama, $dev, $path_foto_simpan, $id_user];
        $stmt   = sqlsrv_query($conn, '{CALL dbo.sp_ManageGame(?, ?, ?, ?, ?, ?)}', $params);

        ob_clean();
        if ($stmt === false) {
            $err = sqlsrv_errors();
            echo json_encode(['status' => 'error', 'message' => $err[0]['message'] ?? 'An unexpected database error occurred.']);
        } else {
            echo json_encode(['status' => 'success', 'message' => '']);
        }
        exit;
    }

    // GET: list dengan search + sort + filter + pagination (kolom: nama_game, developer, aktif)
    if (isset($_GET['list'])) {
        $limit  = 3; 
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        
        $statusParam = ($status === '') ? -1 : (int)$status;
        $sortBy      = $_GET['sort_by'] ?? 'nama_game';
        $sortOrder   = strtoupper($_GET['sort_order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        $sql  = "{CALL dbo.sp_GetGameList(?, ?, ?, ?, ?, ?)}";
        $params = [$search, $sortBy, $sortOrder, $statusParam, $page, $limit];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Failed to execute SP']); exit;
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

        ob_clean();
        echo json_encode(['status' => 'success', 'data' => $rows, 'total_pages' => $total_pages, 'current_page' => $page], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // [FIX] GET: get_detail
    if (isset($_GET['get_detail'])) {
        $id   = (int)$_GET['get_detail'];
        $stmt = sqlsrv_query($conn, '{CALL dbo.sp_GetGameDetail(?)}', [$id]);
        if ($stmt === false) throw new Exception('Query sp_GetGameDetail failed.');

        $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        ob_clean();
        if ($data) {
            // [FIX] Konversi DateTime ke string sebelum json_encode
            $data['created_date']  = ($data['created_date'] instanceof DateTime) ? $data['created_date']->format('d-M-Y H:i') : '-';
            $data['modified_date'] = ($data['modified_date'] instanceof DateTime) ? $data['modified_date']->format('d-M-Y H:i') : '-';
            // [FIX] Tambahkan JSON_UNESCAPED_UNICODE agar karakter tidak rusak
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['error' => 'Data not found']);
        }
        exit;
    }

} catch (Throwable $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>