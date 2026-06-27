<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once '../../connection.php';

ob_start();

try {
    $id_user = (int)($_POST['id_pengguna_js'] ?? ($_SESSION['id_pengguna'] ?? 1));

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

        if ($action === 'add' || $action === 'edit') {
            $stmt_cek = sqlsrv_query($conn, 'SELECT dbo.udf_CheckDuplicateRarity(?, ?, ?, ?) AS total', [$id_game, $nama, $kode, $id_rarity]);
            if ($stmt_cek === false) throw new Exception('Duplicate check query failed.');
            $row_cek = sqlsrv_fetch_array($stmt_cek, SQLSRV_FETCH_ASSOC);
            if ($row_cek && $row_cek['total'] > 0) {
                ob_clean();
                echo json_encode(['status' => 'error', 'message' => 'Rarity Name or Code is already registered.']);
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