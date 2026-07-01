<?php
// Session start dihapus jika memang murni tidak digunakan di server
header('Content-Type: application/json');

require_once __DIR__ . '/../../connection.php';

$action = $_REQUEST['action'] ?? '';
// Mengambil ID secara eksplisit dari parameter klien
$id_sekarang = (int)($_REQUEST['idpengguna'] ?? 0);

if ($id_sekarang === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing user ID.']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'get_checkout_data') {
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetCheckoutData(?)}", [$id_sekarang]);
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

            // Guard: pastikan keranjang punya item terpilih. Tanpa ini, menekan
            // "Place Order" ulang (mis. setelah back/undo) akan membuat order kosong
            // ("bisa bayar lagi") karena sp_PlaceOrder tetap meng-INSERT header penjualan.
            $chk = sqlsrv_query(
                $conn,
                "SELECT COUNT(*) AS n
                   FROM dbo.detail_keranjang dk
                   INNER JOIN dbo.keranjang k ON dk.id_keranjang = k.id_keranjang
                  WHERE k.id_pengguna = ? AND dk.is_selected = 1",
                [$id_sekarang]
            );
            $selectedCount = $chk ? (int)(sqlsrv_fetch_array($chk, SQLSRV_FETCH_ASSOC)['n'] ?? 0) : 0;
            if ($selectedCount === 0) {
                throw new Exception("Your cart is empty or this order was already placed.");
            }

            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_PlaceOrder(?, ?, ?, ?, ?)}", [$id_sekarang, $id_metode, $alamat, $total_harga, $total_barang]);
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
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UploadPaymentProof(?, ?, ?)}", [$id_penjualan, $id_sekarang, $dbPath]);
            if (!$stmt) throw new Exception(sqlsrv_errors()[0]['message']);

            echo json_encode(['success' => true]); exit;
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>