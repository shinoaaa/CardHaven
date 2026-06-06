<?php
session_start();
require_once '../../connection.php';

header('Content-Type: application/json');

$id_user = $_POST['id_karyawan_js'] ?? ($_SESSION['id_karyawan'] ?? 2000);

// ================================================================
// GET — ambil list set untuk tabel (dengan pagination)
// ================================================================
if (isset($_GET['get_list'])) {
    $limit  = 3;
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $sql_count = "SELECT COUNT(*) as total FROM dbo.set_kartu";
    $stmt_count = sqlsrv_query($conn, $sql_count);
    $row_count  = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC);
    $total_rows  = (int)$row_count['total'];
    $total_pages = (int)ceil($total_rows / $limit);

    $sql = "SELECT s.id_set, s.nama_set, s.kode_set, s.aktif, g.nama_game
            FROM dbo.set_kartu s
            INNER JOIN dbo.game g ON s.id_game = g.id_game
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
        'status'      => 'success',
        'data'        => $rows,
        'total_pages' => $total_pages,
        'current_page'=> $page,
    ]);
    exit;
}

// ================================================================
// GET — ambil detail satu set (untuk modal edit)
// ================================================================
if (isset($_GET['get_detail'])) {
    $id  = (int)$_GET['get_detail'];
    $sql = "SELECT s.*, g.nama_game,
                k1.nama as creator,
                k2.nama as modifier
            FROM dbo.set_kartu s
            INNER JOIN dbo.game g ON s.id_game = g.id_game
            LEFT JOIN dbo.karyawan k1 ON s.created_by  = k1.id_karyawan
            LEFT JOIN dbo.karyawan k2 ON s.modified_by = k2.id_karyawan
            WHERE s.id_set = ?";

    $stmt = sqlsrv_query($conn, $sql, [$id]);
    $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($data) {
        if ($data['created_date'])  $data['created_date']  = $data['created_date']->format('d-M-Y');
        if ($data['modified_date']) $data['modified_date'] = $data['modified_date']->format('d-M-Y');
        if ($data['tanggal_rilis']) $data['tanggal_rilis'] = $data['tanggal_rilis']->format('Y-m-d');
    }

    echo json_encode($data);
    exit;
}

// ================================================================
// GET — ambil list game aktif untuk dropdown di modal
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
// POST — add / edit / delete
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- Validasi field wajib untuk add/edit ---
    if ($action === 'add' || $action === 'edit') {
        $nama   = trim($_POST['nama_set']  ?? '');
        $kode   = trim($_POST['kode_set']  ?? '');
        $id_game = (int)($_POST['id_game'] ?? 0);

        if ($nama === '' || $kode === '' || $id_game <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Nama set, kode set, dan game wajib diisi!']);
            exit;
        }

        // Cek duplikat kode_set
        $sql_check    = "SELECT COUNT(*) as total FROM dbo.set_kartu WHERE kode_set = ?";
        $params_check = [$kode];
        if ($action === 'edit') {
            $sql_check   .= " AND id_set <> ?";
            $params_check[] = (int)$_POST['id_set'];
        }
        $stmt_check = sqlsrv_query($conn, $sql_check, $params_check);
        $row_check  = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
        if ((int)$row_check['total'] > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Kode set sudah digunakan!']);
            exit;
        }

        $tanggal = null;
        $raw = trim($_POST['tanggal_rilis'] ?? '');
        if ($raw !== '') {
            $dt = DateTime::createFromFormat('Y-m-d', $raw);
            $tanggal = $dt ? $dt->format('Y-m-d H:i:s') : null;
        }
    }

    // --- ADD ---
    if ($action === 'add') {
        $sql    = "INSERT INTO dbo.set_kartu (id_game, nama_set, kode_set, tanggal_rilis, created_by, created_date, aktif)
                    VALUES (?, ?, ?, ?, ?, GETDATE(), 1)";
        $params = [$id_game, $nama, $kode, $tanggal, $id_user];
        $stmt   = sqlsrv_query($conn, $sql, $params);

        if ($stmt) echo json_encode(['status' => 'success']);
        else       echo json_encode(['status' => 'error', 'message' => 'Gagal insert ke database']);
        exit;
    }

    // --- EDIT ---
    if ($action === 'edit') {
        $id_set = (int)$_POST['id_set'];
        $aktif  = (int)($_POST['aktif'] ?? 1);

        $sql    = "UPDATE dbo.set_kartu
                    SET id_game = ?, nama_set = ?, kode_set = ?, tanggal_rilis = ?,
                        aktif = ?, modified_by = ?, modified_date = GETDATE()
                    WHERE id_set = ?";
        $params = [$id_game, $nama, $kode, $tanggal, $aktif, $id_user, $id_set];
        $stmt   = sqlsrv_query($conn, $sql, $params);

        if ($stmt) echo json_encode(['status' => 'success']);
        else       echo json_encode(['status' => 'error', 'message' => 'Gagal update database']);
        exit;
    }

    // --- DELETE (soft delete) ---
    if ($action === 'delete') {
        $id_set = (int)$_POST['id_set'];
        $sql    = "UPDATE dbo.set_kartu SET aktif = 0, modified_by = ?, modified_date = GETDATE() WHERE id_set = ?";
        $stmt   = sqlsrv_query($conn, $sql, [$id_user, $id_set]);

        if ($stmt) echo json_encode(['status' => 'success']);
        else       echo json_encode(['status' => 'error', 'message' => 'Gagal menonaktifkan set']);
        exit;
    }
    if($action === 'restore') {
        $id_set = (int)$_POST['id_set'];
        $sql    = "UPDATE dbo.set_kartu SET aktif = 1, modified_by = ?, modified_date = GETDATE() WHERE id_set = ?";
        $stmt   = sqlsrv_query($conn, $sql, [$id_user, $id_set]);

        if ($stmt) echo json_encode(['status' => 'success']);
        else       echo json_encode(['status' => 'error', 'message' => 'Gagal mengaktifkan set']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
    exit;
}
?>