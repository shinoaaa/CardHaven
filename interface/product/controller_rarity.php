<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once '../../connection.php';

ob_start();

try {
    $id_user = (int)($_POST['id_pengguna_js'] ?? ($_SESSION['id_pengguna'] ?? 1));

    // GET: list dengan search + sort + filter + pagination (kolom: nama_rarity, kode_rarity, game, status)
    if (isset($_GET['list'])) {
        $limit  = 3;
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        $idGame = (int)($_GET['id_game'] ?? 0);
        $sortMap = ['nama_rarity' => 'r.nama_rarity', 'kode_rarity' => 'r.kode_rarity', 'aktif' => 'r.aktif'];
        $sortCol = $sortMap[$_GET['sort_by'] ?? ''] ?? 'r.nama_rarity';
        $sortDir = strtoupper($_GET['sort_order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        $where = 'r.is_deleted = 0';
        $params = [];
        if ($search !== '') { $where .= ' AND (r.nama_rarity LIKE ? OR r.kode_rarity LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($status === '0' || $status === '1') { $where .= ' AND r.aktif = ?'; $params[] = (int)$status; }
        if ($idGame > 0) { $where .= ' AND r.id_game = ?'; $params[] = $idGame; }

        $cst = sqlsrv_query($conn, "SELECT COUNT(*) AS n FROM dbo.rarity r WHERE $where", $params);
        $total = $cst ? (int)(sqlsrv_fetch_array($cst, SQLSRV_FETCH_ASSOC)['n'] ?? 0) : 0;
        $total_pages = max(1, (int)ceil($total / $limit));
        $page   = min($page, $total_pages);
        $offset = ($page - 1) * $limit;

        $sql = "SELECT r.*, g.nama_game FROM dbo.rarity r LEFT JOIN dbo.game g ON r.id_game = g.id_game
                WHERE $where ORDER BY aktif DESC, $sortCol $sortDir, r.id_rarity DESC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
        $st  = sqlsrv_query($conn, $sql, array_merge($params, [$offset, $limit]));
        $rows = [];
        if ($st) while ($r = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC)) $rows[] = $r;
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