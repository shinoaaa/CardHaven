<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['id_pengguna'])) { echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login first.']); exit; }

require_once __DIR__ . '/../../connection.php';
$id_pengguna = (int)$_SESSION['id_pengguna'];
$action = $_REQUEST['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'get_checkout_data') {
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetCheckoutData(?)}", [$id_pengguna]);
            if (!$stmt) throw new Exception(sqlsrv_errors()[0]['message']);
            
            $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_next_result($stmt);
            $items = []; while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $items[] = $r;
            sqlsrv_next_result($stmt);
            $methods = []; while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $methods[] = $r;

            echo json_encode(['success' => true, 'user' => $user, 'items' => $items, 'methods' => $methods]); exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'place_order') {
            $id_metode = (int)($_POST['id_metode'] ?? 0);
            $alamat = trim($_POST['alamat'] ?? '');
            $total_harga = (float)($_POST['total_harga'] ?? 0);
            $total_barang = (int)($_POST['total_barang'] ?? 0);

            if (!$id_metode || empty($alamat)) throw new Exception("Please select a payment method and provide your full address.");

            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_PlaceOrder(?, ?, ?, ?, ?)}", [$id_pengguna, $id_metode, $alamat, $total_harga, $total_barang]);
            if (!$stmt) throw new Exception(sqlsrv_errors()[0]['message']);
            
            $newId = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)['new_order_id'];
            echo json_encode(['success' => true, 'id_penjualan' => $newId]); exit;
        }

        if ($action === 'upload_bukti') {
            $id_penjualan = (int)($_POST['id_penjualan'] ?? 0);
            if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_OK) throw new Exception("Failed to upload the file.");

            $dir = __DIR__ . '/../../../assets/image/receipt/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            
            $fileName = uniqid('rcpt_') . '_' . basename($_FILES['bukti_pembayaran']['name']);
            if (!move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $dir . $fileName)) throw new Exception("Failed to save the file on the server.");

            $dbPath = 'assets/image/receipt/' . $fileName;
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UploadPaymentProof(?, ?, ?)}", [$id_penjualan, $id_pengguna, $dbPath]);
            if (!$stmt) throw new Exception(sqlsrv_errors()[0]['message']);

            echo json_encode(['success' => true]); exit;
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>