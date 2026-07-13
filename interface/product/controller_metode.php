<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/CardHaven/connection.php';

ob_start();

try {
    $id_user = (int)($_POST['id_pengguna_js'] ?? ($_SESSION['id_pengguna'] ?? 1));

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action    = $_POST['action'] ?? '';
        $id_metode = isset($_POST['id_metode']) && $_POST['id_metode'] !== '' ? (int)$_POST['id_metode'] : null;
        $nama      = trim($_POST['nama_metode'] ?? '');
        $provider  = trim($_POST['provider'] ?? '');
        $no_rek    = trim($_POST['no_rekening'] ?? '');
        $atas_nama = trim($_POST['atas_nama'] ?? '');
        $biaya     = (float)($_POST['biaya_admin'] ?? 0);

        if ($action === 'add' || $action === 'edit') {
            if (!preg_match('/^[A-Za-z ]+$/', $nama)) {
                ob_clean();
                echo json_encode(['status' => 'error', 'message' => 'Method name must contain letters only (no numbers or symbols).']);
                exit;
            }
            if (!preg_match('/^[A-Za-z0-9 .]+$/', $provider) || !preg_match('/[A-Za-z]/', $provider)) {
                ob_clean();
                echo json_encode(['status' => 'error', 'message' => 'Provider must contain letters (not only numbers or symbols).']);
                exit;
            }
            if (!preg_match('/^[A-Za-z ]+$/', $atas_nama)) {
                ob_clean();
                echo json_encode(['status' => 'error', 'message' => 'Account name must contain letters only (no numbers or symbols).']);
                exit;
            }
        }

        if ($action === 'add' || $action === 'edit') {
            $stmt_cek = sqlsrv_query($conn, 'SELECT dbo.udf_CheckDuplicateMetode(?, ?) AS total', [$nama, $id_metode]);
            if ($stmt_cek === false) throw new Exception('Duplicate check query failed.');
            $row_cek = sqlsrv_fetch_array($stmt_cek, SQLSRV_FETCH_ASSOC);
            if ($row_cek && $row_cek['total'] > 0) {
                ob_clean();
                echo json_encode(['status' => 'error', 'message' => "Method '$nama' already exists!"]);
                exit;
            }
        }

        $params = [$action, $id_metode, $nama, $provider, $no_rek, $atas_nama, $biaya, $id_user];
        $stmt   = sqlsrv_query($conn, '{CALL dbo.sp_ManageMetode(?, ?, ?, ?, ?, ?, ?, ?)}', $params);

        ob_clean();
        if ($stmt === false) {
            $err = sqlsrv_errors();
            echo json_encode(['status' => 'error', 'message' => $err[0]['message'] ?? 'Database error.']);
        } else {
            echo json_encode(['status' => 'success', 'message' => '']);
        }
        exit;
    }

    // GET: list dengan search + sort + filter + pagination (kolom: nama_metode, provider, biaya_admin, status)
    if (isset($_GET['list'])) {
        $limit  = 5;
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $search = trim($_GET['search'] ?? '');
        $status = ($_GET['status'] === '' || !isset($_GET['status'])) ? -1 : (int)$_GET['status'];
        
        $sortBy  = $_GET['sort_by'] ?? 'id_metode';
        $sortDir = strtoupper($_GET['sort_order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        // Panggil SP
        $sql = "{CALL dbo.sp_GetMetodeList(?, ?, ?, ?, ?, ?)}";
        $params = [$search, $sortBy, $sortDir, $status, $page, $limit];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) throw new Exception('Query sp_GetMetodeList failed.');

        // Result 1: Total Count
        $total_rows = 0;
        if ($rCount = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $total_rows = (int)$rCount['total_rows'];
        }

        // Result 2: Data Rows
        sqlsrv_next_result($stmt);
        $rows = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $r;
        }

        $total_pages = max(1, (int)ceil($total_rows / $limit));

        ob_clean();
        echo json_encode([
            'status' => 'success', 
            'data' => $rows, 
            'total_pages' => $total_pages, 
            'current_page' => $page
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // [FIX] GET: get_detail
    if (isset($_GET['get_detail'])) {
        $id   = (int)$_GET['get_detail'];
        $stmt = sqlsrv_query($conn, '{CALL dbo.sp_GetMetodeDetail(?)}', [$id]);
        if ($stmt === false) throw new Exception('Query sp_GetMetodeDetail failed.');

        $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        ob_clean();
        if ($data) {
            // [FIX] Konversi DateTime sebelum json_encode
            $data['created_date']  = ($data['created_date'] instanceof DateTime) ? $data['created_date']->format('d-M-Y H:i') : '-';
            $data['modified_date'] = ($data['modified_date'] instanceof DateTime) ? $data['modified_date']->format('d-M-Y H:i') : '-';
            // [FIX] biaya_admin harus float bukan string
            $data['biaya_admin']   = (float)($data['biaya_admin'] ?? 0);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['error' => 'Not found.']);
        }
        exit;
    }

} catch (Throwable $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>