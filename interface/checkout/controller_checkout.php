<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../connection.php';
require_once __DIR__ . '/../../auth/session.php';

$action = $_REQUEST['action'] ?? '';

// Pembeli = user yang sedang login. Sebelumnya id diambil dari parameter klien
// (idpengguna), jadi siapa pun bisa checkout memakai keranjang & alamat orang lain.
$id_sekarang = auth_api_require_login()['id'];

// Pesan error SQL Server datang dengan prefix driver
// "[Microsoft][ODBC Driver 17 for SQL Server][SQL Server]" yang tidak layak
// ditampilkan ke customer. Ambil kalimat aslinya saja.
function db_error_message($fallback = 'A database error occurred.') {
    $errors = sqlsrv_errors();
    if (!$errors) return $fallback;
    $msg = trim(preg_replace('/^(\[[^\]]*\])+/', '', $errors[0]['message'] ?? ''));
    return $msg !== '' ? $msg : $fallback;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'get_checkout_data') {
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetCheckoutData(?)}", [$id_sekarang]);
            if (!$stmt) throw new Exception(db_error_message('Failed to load checkout data.'));
            
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

            // total_harga & total_barang TIDAK lagi diterima dari browser.
            // Dulu keduanya ditulis apa adanya ke penjualan, jadi header order
            // bisa berisi angka apa pun. Sekarang sp_PlaceOrder menghitungnya
            // sendiri dari keranjang + biaya_admin metode pembayaran.

            if (!$id_metode)     throw new Exception("Please select a payment method.");
            if ($alamat === '')  throw new Exception("Please provide your full address.");

            // penjualan.alamat adalah VARCHAR(100). Tanpa cek ini, alamat yang
            // lebih panjang memunculkan error truncation SQL mentah ke customer.
            if (strlen($alamat) > 100) {
                throw new Exception("Address is too long. Please keep it under 100 characters.");
            }

            // Guard cepat supaya kasus "sudah pernah di-submit" tidak perlu
            // membuka transaksi. sp_PlaceOrder tetap memeriksa ulang secara
            // atomik (UPDLOCK), jadi ini murni demi pesan yang ramah.
            $chk = sqlsrv_query($conn, "{CALL dbo.sp_CheckCartSelection(?)}", [$id_sekarang]);
            $selectedCount = $chk ? (int)(sqlsrv_fetch_array($chk, SQLSRV_FETCH_ASSOC)['n'] ?? 0) : 0;
            if ($selectedCount === 0) {
                throw new Exception("Your cart is empty or this order was already placed.");
            }

            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_PlaceOrder(?, ?, ?)}", [$id_sekarang, $id_metode, $alamat]);
            if (!$stmt) throw new Exception(db_error_message('Failed to place the order.'));

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if (!$row || !isset($row['new_order_id'])) {
                throw new Exception("Failed to place the order.");
            }

            // Total dikembalikan server supaya jumlah yang ditampilkan untuk
            // ditransfer selalu sama dengan yang tercatat di order.
            echo json_encode([
                'success'      => true,
                'id_penjualan' => $row['new_order_id'],
                'total_harga'  => (float)$row['total_harga'],
                'total_barang' => (int)$row['total_barang'],
                'biaya_admin'  => (float)$row['biaya_admin'],
            ]); exit;
        }

        if ($action === 'upload_bukti') {
            $id_penjualan = (int)($_POST['id_penjualan'] ?? 0);
            if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_OK) throw new Exception("Failed to upload the file.");

            // Guard: order harus milik user ini dan masih Pending Payment
            $chk = sqlsrv_query($conn, "{CALL dbo.sp_CheckOrderForPayment(?, ?)}", [$id_penjualan, $id_sekarang]);
            $ord = $chk ? sqlsrv_fetch_array($chk, SQLSRV_FETCH_ASSOC) : null;
            if (!$ord) throw new Exception("Order not found.");
            if ((int)$ord['status_penjualan'] !== 0) {
                throw new Exception("This order has already been paid or can no longer be paid.");
            }

            $upload = $_FILES['bukti_pembayaran'];

            // Bukti pembayaran wajib benar-benar gambar. Sebelumnya nama file
            // asli dipakai apa adanya, jadi bukti.php bisa terupload ke
            // assets/image/receipt/ -- folder yang dilayani Apache langsung
            // (rewrite rule di .htaccess melewatkan file yang benar-benar ada),
            // sehingga file itu ikut dieksekusi.
            if ($upload['size'] > 5 * 1024 * 1024) {
                throw new Exception("The file is too large. Maximum size is 5 MB.");
            }

            $info = @getimagesize($upload['tmp_name']);
            $allowed = [
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG  => 'png',
                IMAGETYPE_WEBP => 'webp',
            ];
            if ($info === false || !isset($allowed[$info[2]])) {
                throw new Exception("Please upload a valid JPG, PNG, or WEBP image.");
            }

            $dir = __DIR__ . '/../../assets/image/receipt/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            // Nama file dibuat sendiri, tidak mengambil apa pun dari nama kiriman.
            $fileName = uniqid('rcpt_') . '.' . $allowed[$info[2]];
            if (!move_uploaded_file($upload['tmp_name'], $dir . $fileName)) throw new Exception("Failed to save the file on the server.");

            $dbPath = 'assets/image/receipt/' . $fileName;
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UploadPaymentProof(?, ?, ?)}", [$id_penjualan, $id_sekarang, $dbPath]);
            if (!$stmt) throw new Exception(db_error_message('Failed to save the payment proof.'));

            echo json_encode(['success' => true]); exit;
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>