<?php
session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/CardHaven/connection.php';

$raw_id_js = $_POST['id_pengguna_js'] ?? '';
if ($raw_id_js === '' || $raw_id_js === 'undefined' || $raw_id_js === 'null') {
    $id_user = $_SESSION['id_pengguna'] ?? 1;
} else {
    $id_user = $raw_id_js;
}
$id_user = (int)$id_user;

// ================================================================
// GET — list set (pagination)
// ================================================================
if (isset($_GET['get_list'])) {
    $limit  = 3;
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $sql_count  = "SELECT COUNT(*) as total FROM dbo.set_kartu WHERE is_deleted = 0";
    $stmt_count = sqlsrv_query($conn, $sql_count);
    $row_count  = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC);
    $total_rows  = (int)($row_count['total'] ?? 0);
    $total_pages = (int)ceil($total_rows / $limit);

    $sql  = "SELECT s.id_set, s.nama_set, s.kode_set, s.aktif, g.nama_game
             FROM dbo.set_kartu s
             INNER JOIN dbo.game g ON s.id_game = g.id_game
             WHERE s.is_deleted = 0
             ORDER BY s.aktif DESC, s.id_set ASC
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
    $stmt = sqlsrv_query($conn, $sql, [$offset, $limit]);

    $rows = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rows[] = [
            'id_set'    => $row['id_set'],
            'nama_set'  => $row['nama_set'],
            'kode_set'  => $row['kode_set'],
            'aktif'     => $row['aktif'],
            'nama_game' => $row['nama_game'],
        ];
    }

    echo json_encode([
        'status'       => 'success',
        'data'         => $rows,
        'total_pages'  => $total_pages,
        'current_page' => $page,
    ]);
    exit;
}

// ================================================================
// GET — detail satu set
// ================================================================
if (isset($_GET['get_detail'])) {
    $id  = (int)$_GET['get_detail'];
    $sql = "SELECT s.*, g.nama_game,
                k1.username as creator,
                k2.username as modifier
            FROM dbo.set_kartu s
            INNER JOIN dbo.game g ON s.id_game = g.id_game
            LEFT JOIN dbo.pengguna k1 ON s.created_by  = k1.id_pengguna
            LEFT JOIN dbo.pengguna k2 ON s.modified_by = k2.id_pengguna
            WHERE s.id_set = ? AND s.is_deleted = 0";

    $stmt = sqlsrv_query($conn, $sql, [$id]);
    if ($stmt === false) {
        echo json_encode(['error' => 'Query failed.']);
        exit;
    }

    $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($data) {
        if ($data['created_date'])  $data['created_date']  = $data['created_date']->format('d-M-Y H:i');
        if ($data['modified_date']) $data['modified_date'] = $data['modified_date']->format('d-M-Y H:i');
        if ($data['tanggal_rilis']) $data['tanggal_rilis'] = $data['tanggal_rilis']->format('Y-m-d');
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Set not found.']);
    }
    exit;
}

// ================================================================
// GET — list game aktif untuk dropdown
// ================================================================
if (isset($_GET['get_games'])) {
    $sql  = "SELECT id_game, nama_game FROM dbo.game WHERE aktif = 1 ORDER BY nama_game ASC";
    $stmt = sqlsrv_query($conn, $sql);

    $games = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $games[] = ['id_game' => $row['id_game'], 'nama_game' => $row['nama_game']];
    }

    echo json_encode(['status' => 'success', 'data' => $games]);
    exit;
}

// ================================================================
// POST — add / edit / toggle / delete / restore
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Validasi field wajib untuk add/edit
    if ($action === 'add' || $action === 'edit') {
        $nama    = trim($_POST['nama_set']  ?? '');
        $kode    = trim($_POST['kode_set']  ?? '');
        $id_game = (int)($_POST['id_game']  ?? 0);

        if ($nama === '' || $kode === '' || $id_game <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Set name, set code, and game are required!']);
            exit;
        }

        // Cek duplikat kode_set
        $sql_check    = "SELECT COUNT(*) as total FROM dbo.set_kartu WHERE kode_set = ? AND is_deleted = 0";
        $params_check = [$kode];
        if ($action === 'edit') {
            $sql_check   .= " AND id_set <> ?";
            $params_check[] = (int)$_POST['id_set'];
        }
        $stmt_check = sqlsrv_query($conn, $sql_check, $params_check);
        $row_check  = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
        if ((int)($row_check['total'] ?? 0) > 0) {
            echo json_encode(['status' => 'error', 'message' => "Set code '$kode' is already in use!"]);
            exit;
        }

        $tanggal = null;
        $raw = trim($_POST['tanggal_rilis'] ?? '');
        if ($raw !== '') {
            $dt = DateTime::createFromFormat('Y-m-d', $raw);
            $tanggal = $dt ? $dt->format('Y-m-d H:i:s') : null;
        }
    }

    // ADD
    if ($action === 'add') {
        $sql    = "INSERT INTO dbo.set_kartu (id_game, nama_set, kode_set, tanggal_rilis, created_by, created_date, aktif,is_deleted)
                   VALUES (?, ?, ?, ?, ?, GETDATE(), 1,0)";
        $stmt   = sqlsrv_query($conn, $sql, [$id_game, $nama, $kode, $tanggal, $id_user]);

        if ($stmt) echo json_encode(['status' => 'success']);
        else {
            $err = sqlsrv_errors();
            echo json_encode(['status' => 'error', 'message' => $err[0]['message'] ?? 'Failed to insert data.']);
        }
        exit;
    }

    // EDIT
    if ($action === 'edit') {
        $id_set = (int)$_POST['id_set'];
        $sql    = "UPDATE dbo.set_kartu
                   SET id_game = ?, nama_set = ?, kode_set = ?, tanggal_rilis = ?,
                       modified_by = ?, modified_date = GETDATE()
                   WHERE id_set = ?";
        $stmt   = sqlsrv_query($conn, $sql, [$id_game, $nama, $kode, $tanggal, $id_user, $id_set]);

        if ($stmt) echo json_encode(['status' => 'success']);
        else {
            $err = sqlsrv_errors();
            echo json_encode(['status' => 'error', 'message' => $err[0]['message'] ?? 'Failed to update data.']);
        }
        exit;
    }

    // TOGGLE (aktifkan / nonaktifkan)
    if ($action === 'aktifkan' || $action === 'nonaktifkan') {
        $id_set = (int)$_POST['id_set'];  // ← FIX: sebelumnya tidak ada baris ini
        $aktif  = $action === 'aktifkan' ? 1 : 0;
        $sql    = "UPDATE dbo.set_kartu SET aktif = ?, modified_by = ?, modified_date = GETDATE() WHERE id_set = ?";
        $stmt   = sqlsrv_query($conn, $sql, [$aktif, $id_user, $id_set]);

        if ($stmt) echo json_encode(['status' => 'success']);
        else {
            $err = sqlsrv_errors();
            echo json_encode(['status' => 'error', 'message' => $err[0]['message'] ?? 'Failed to update status.']);
        }
        exit;
    }

    // DELETE (soft delete)
    if ($action === 'delete') {
        $id_set = (int)$_POST['id_set'];
        $sql    = "UPDATE dbo.set_kartu SET is_deleted = 1, deleted_by = ?, deleted_date = GETDATE() WHERE id_set = ?";
        $stmt   = sqlsrv_query($conn, $sql, [$id_user, $id_set]);

        if ($stmt) echo json_encode(['status' => 'success']);
        else {
            $err = sqlsrv_errors();
            echo json_encode(['status' => 'error', 'message' => $err[0]['message'] ?? 'Failed to delete set.']);
        }
        exit;
    }

    // RESTORE
    if ($action === 'restore') {
        $id_set = (int)$_POST['id_set'];
        $sql    = "UPDATE dbo.set_kartu SET is_deleted = 0, modified_by = ?, modified_date = GETDATE() WHERE id_set = ?";
        $stmt   = sqlsrv_query($conn, $sql, [$id_user, $id_set]);

        if ($stmt) echo json_encode(['status' => 'success']);
        else {
            $err = sqlsrv_errors();
            echo json_encode(['status' => 'error', 'message' => $err[0]['message'] ?? 'Failed to restore set.']);
        }
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Action not recognized.']);
    exit;
}
?>