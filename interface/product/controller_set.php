<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/CardHaven/connection.php';

ob_start();

try {
    $id_user = (int)($_POST['id_pengguna_js'] ?? ($_SESSION['id_pengguna'] ?? 1));

    // GET: list dengan search + sort + filter + pagination (kolom: nama_set, kode_set, game, status)
    if (isset($_GET['list'])) {
        $limit  = 3;
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        $idGame = (int)($_GET['id_game'] ?? 0);
        $sortMap = ['nama_set' => 's.nama_set', 'kode_set' => 's.kode_set', 'aktif' => 's.aktif'];
        $sortCol = $sortMap[$_GET['sort_by'] ?? ''] ?? 's.nama_set';
        $sortDir = strtoupper($_GET['sort_order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        $where = 's.is_deleted = 0';
        $params = [];
        if ($search !== '') { $where .= ' AND (s.nama_set LIKE ? OR s.kode_set LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($status === '0' || $status === '1') { $where .= ' AND s.aktif = ?'; $params[] = (int)$status; }
        if ($idGame > 0) { $where .= ' AND s.id_game = ?'; $params[] = $idGame; }

        $cst = sqlsrv_query($conn, "SELECT COUNT(*) AS n FROM dbo.set_kartu s WHERE $where", $params);
        $total = $cst ? (int)(sqlsrv_fetch_array($cst, SQLSRV_FETCH_ASSOC)['n'] ?? 0) : 0;
        $total_pages = max(1, (int)ceil($total / $limit));
        $page   = min($page, $total_pages);
        $offset = ($page - 1) * $limit;

        $sql = "SELECT s.*, g.nama_game FROM dbo.set_kartu s LEFT JOIN dbo.game g ON s.id_game = g.id_game
                WHERE $where ORDER BY aktif DESC, $sortCol $sortDir, s.id_set DESC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
        $st  = sqlsrv_query($conn, $sql, array_merge($params, [$offset, $limit]));
        $rows = [];
        if ($st) while ($r = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC)) $rows[] = $r;
        ob_clean();
        echo json_encode(['status' => 'success', 'data' => $rows, 'total_pages' => $total_pages, 'current_page' => $page], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (isset($_GET['get_list'])) {
        $limit  = 3;
        $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $limit;

        $stmt_count = sqlsrv_query($conn, "SELECT dbo.udf_CountDashboard('set') AS total");
        if ($stmt_count === false) throw new Exception('Count query failed.');
        $row_count   = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC);
        $total_pages = (int)ceil(($row_count['total'] ?? 0) / $limit);

        $stmt = sqlsrv_query($conn, '{CALL dbo.sp_GetSetList(?, ?)}', [$limit, $offset]);
        if ($stmt === false) throw new Exception('sp_GetSetList query failed.');

        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $row;

        ob_clean();
        echo json_encode(['status' => 'success', 'data' => $rows, 'total_pages' => $total_pages, 'current_page' => $page], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // [FIX] GET: get_detail
    if (isset($_GET['get_detail'])) {
        $id   = (int)$_GET['get_detail'];
        $stmt = sqlsrv_query($conn, '{CALL dbo.sp_GetSetDetail(?)}', [$id]);
        if ($stmt === false) throw new Exception('Query sp_GetSetDetail failed.');

        $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        ob_clean();
        if ($data) {
            // [FIX] Konversi semua DateTime ke string sebelum json_encode
            $data['created_date']  = ($data['created_date'] instanceof DateTime) ? $data['created_date']->format('d-M-Y H:i') : '-';
            $data['modified_date'] = ($data['modified_date'] instanceof DateTime) ? $data['modified_date']->format('d-M-Y H:i') : '-';
            $data['tanggal_rilis'] = ($data['tanggal_rilis'] instanceof DateTime) ? $data['tanggal_rilis']->format('Y-m-d') : null;
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['error' => 'Set not found.']);
        }
        exit;
    }

    if (isset($_GET['get_games'])) {
        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetDropdownGame('')}");
        if ($stmt === false) throw new Exception('sp_GetDropdownGame query failed.');
        $games = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $games[] = $row;
        ob_clean();
        echo json_encode(['status' => 'success', 'data' => $games], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action  = $_POST['action'] ?? '';
        $id_set  = isset($_POST['id_set']) && $_POST['id_set'] !== '' ? (int)$_POST['id_set'] : 0;
        $id_game = (int)($_POST['id_game'] ?? 0);
        $nama    = trim($_POST['nama_set'] ?? '');
        $kode    = trim($_POST['kode_set'] ?? '');

        $tanggal = null;
        $tgl_raw = trim($_POST['tanggal_rilis'] ?? '');
        if ($tgl_raw !== '') {
            $dt = DateTime::createFromFormat('Y-m-d', $tgl_raw);
            $tanggal = $dt ? $dt->format('Y-m-d H:i:s') : null;
        }

        if ($action === 'delete') {
            $stmt_rel = sqlsrv_query($conn, "SELECT COUNT(*) AS n FROM dbo.produk WHERE id_set = ? AND is_deleted = 0", [$id_set]);
            $rel = $stmt_rel ? sqlsrv_fetch_array($stmt_rel, SQLSRV_FETCH_ASSOC) : null;
            if ($rel && (int)$rel['n'] > 0) {
                ob_clean();
                echo json_encode(['status' => 'error', 'message' => "Cannot delete: this set is still used by {$rel['n']} Product(s)."]);
                exit;
            }
        }
        
        if ($action === 'add' || $action === 'edit') {
            $stmt_cek = sqlsrv_query($conn, 'SELECT dbo.udf_CheckDuplicateSet(?, ?) AS total', [$kode, $id_set]);
            if ($stmt_cek === false) throw new Exception('Duplicate check query failed.');
            $row_cek = sqlsrv_fetch_array($stmt_cek, SQLSRV_FETCH_ASSOC);
            if ($row_cek && $row_cek['total'] > 0) {
                ob_clean();
                echo json_encode(['status' => 'error', 'message' => "Set code '$kode' is already in use."]);
                exit;
            }
        }

        $params = [$action, $id_set, $id_game, $nama, $kode, $tanggal, $id_user];
        $stmt   = sqlsrv_query($conn, '{CALL dbo.sp_ManageSet(?, ?, ?, ?, ?, ?, ?)}', $params);

        ob_clean();
        if ($stmt === false) {
            $err = sqlsrv_errors();
            echo json_encode(['status' => 'error', 'message' => $err[0]['message'] ?? 'Database error.']);
        } else {
            echo json_encode(['status' => 'success', 'message' => '']);
        }
        exit;
    }

} catch (Throwable $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>