<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once '../../connection.php';

ob_start();

try {
    $id_user = (int)($_POST['id_pengguna_js'] ?? ($_SESSION['id_pengguna'] ?? 1));

    if (isset($_GET['list'])) {
        $limit  = 3;
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $search = trim($_GET['search'] ?? '');
        $status = ($_GET['status'] === '') ? -1 : (int)$_GET['status'];
        $idGame = (int)($_GET['id_game'] ?? 0);
        $sortBy = $_GET['sort_by'] ?? 'id_rarity';
        $sortDir = strtoupper($_GET['sort_order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        $sql = "{CALL dbo.sp_GetRarityList(?, ?, ?, ?, ?, ?, ?)}";
        $params = [$search, $sortBy, $sortDir, $status, $idGame, $page, $limit];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) throw new Exception('Failed to execute sp_GetRarityList');

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

    // [FIX] check_duplicate via GET
    if (isset($_GET['check_duplicate'])) {
        $id_game    = (int)($_GET['id_game'] ?? 0);
        $nama       = $_GET['nama_rarity'] ?? '';
        $kode       = $_GET['kode_rarity'] ?? '';
        $exclude_id = (int)($_GET['exclude_id'] ?? 0);

        $stmt = sqlsrv_query($conn, 'SELECT dbo.udf_CheckDuplicateRarity(?, ?, ?, ?) AS total', [$id_game, $nama, $kode, $exclude_id]);
        if ($stmt === false) throw new Exception('Duplicate check query failed.');
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        ob_clean();
        echo json_encode(['exists' => ($row && $row['total'] > 0)]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action   = $_POST['action'] ?? '';
        $id_game  = (int)($_POST['id_game'] ?? 0);
        $id_rarity = isset($_POST['id_rarity']) && $_POST['id_rarity'] !== '' ? (int)$_POST['id_rarity'] : 0;
        $nama     = trim($_POST['nama_rarity'] ?? '');
        $kode     = trim($_POST['kode_rarity'] ?? '');

        // Cek relasi sebelum delete: rarity tidak boleh dihapus bila masih dipakai produk.
        if ($action === 'delete') {
            $stmt_rel = sqlsrv_query($conn, "SELECT COUNT(*) AS n FROM dbo.produk WHERE id_rarity = ? AND is_deleted = 0", [$id_rarity]);
            $rel = $stmt_rel ? sqlsrv_fetch_array($stmt_rel, SQLSRV_FETCH_ASSOC) : null;
            if ($rel && (int)$rel['n'] > 0) {
                ob_clean();
                echo json_encode(['status' => 'error', 'message' => "Cannot delete: this rarity is still used by {$rel['n']} Product(s)."]);
                exit;
            }
        }

        if ($action === 'add' || $action === 'edit') {
            // Pesan duplikat spesifik (cek code dulu, lalu name) — konsisten dengan Master Set.
            $stmt_code = sqlsrv_query($conn, "SELECT COUNT(*) AS n FROM dbo.rarity WHERE id_game = ? AND kode_rarity = ? AND is_deleted = 0 AND id_rarity <> ?", [$id_game, $kode, $id_rarity]);
            $row_code  = $stmt_code ? sqlsrv_fetch_array($stmt_code, SQLSRV_FETCH_ASSOC) : null;
            if ($row_code && (int)$row_code['n'] > 0) {
                ob_clean();
                echo json_encode(['status' => 'error', 'message' => "Rarity code '$kode' is already in use."]);
                exit;
            }
            $stmt_name = sqlsrv_query($conn, "SELECT COUNT(*) AS n FROM dbo.rarity WHERE id_game = ? AND nama_rarity = ? AND is_deleted = 0 AND id_rarity <> ?", [$id_game, $nama, $id_rarity]);
            $row_name  = $stmt_name ? sqlsrv_fetch_array($stmt_name, SQLSRV_FETCH_ASSOC) : null;
            if ($row_name && (int)$row_name['n'] > 0) {
                ob_clean();
                echo json_encode(['status' => 'error', 'message' => "Rarity name '$nama' is already in use."]);
                exit;
            }
        }

        $params = [$action, $id_rarity, $id_game, $nama, $kode, $id_user];
        $stmt   = sqlsrv_query($conn, '{CALL dbo.sp_ManageRarity(?, ?, ?, ?, ?, ?)}', $params);

        ob_clean();
        if ($stmt === false) {
            $err = sqlsrv_errors();
            echo json_encode(['status' => 'error', 'message' => $err[0]['message'] ?? 'Database error.']);
        } else {
            echo json_encode(['status' => 'success', 'message' => '']);
        }
        exit;
    }

    // [FIX] GET: get_detail
    if (isset($_GET['get_detail'])) {
        $id   = (int)$_GET['get_detail'];
        $stmt = sqlsrv_query($conn, '{CALL dbo.sp_GetRarityDetail(?)}', [$id]);
        if ($stmt === false) throw new Exception('Query sp_GetRarityDetail failed.');

        $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        ob_clean();
        if ($data) {
            // [FIX] Konversi DateTime sebelum json_encode
            $data['created_date']  = ($data['created_date'] instanceof DateTime) ? $data['created_date']->format('d-M-Y H:i') : '-';
            $data['modified_date'] = ($data['modified_date'] instanceof DateTime) ? $data['modified_date']->format('d-M-Y H:i') : '-';
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['error' => 'Data not found.']);
        }
        exit;
    }

} catch (Throwable $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>