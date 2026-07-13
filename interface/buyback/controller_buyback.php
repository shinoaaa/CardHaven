<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

ob_start();

try {
    require_once '../../connection.php';
    if (!isset($conn) || !is_resource($conn)) {
        throw new Exception("Invalid database connection.");
    }
} catch (Throwable $e) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
    exit;
}

function getSqlError() {
    $errors = sqlsrv_errors();
    return $errors[0]['message'] ?? 'Unknown SQL Server Error';
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'submit_buyback':
        try {
            if (!isset($_POST['nama_kartu']) || !is_array($_POST['nama_kartu'])) throw new Exception("Form data is invalid or empty.");
            
            $id_customer = (int)($_POST['id_pengguna'] ?? 0);
            if ($id_customer === 0) throw new Exception("User session expired.");
            
            $provider    = $_POST['provider'] ?? '';
            $no_rekening = $_POST['no_rekening'] ?? '';
            $total_barang= count($_POST['nama_kartu']);
            $total_harga = isset($_POST['harga_beli']) && is_array($_POST['harga_beli']) ? array_sum($_POST['harga_beli']) : 0;
            
            $uploadDir = '../../assets/image/buyback/';
            $dbPath    = 'assets/image/buyback/'; 
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $itemsData = [];

            for ($i = 0; $i < $total_barang; $i++) {
                if ($_FILES['foto_depan']['error'][$i] !== UPLOAD_ERR_OK || $_FILES['foto_belakang']['error'][$i] !== UPLOAD_ERR_OK) {
                    throw new Exception("Image file is corrupted or exceeds upload size limit.");
                }

                $fileNameDepan = uniqid('front_') . '_' . basename($_FILES['foto_depan']['name'][$i]);
                $fileNameBelakang = uniqid('back_') . '_' . basename($_FILES['foto_belakang']['name'][$i]);
                
                if (!move_uploaded_file($_FILES['foto_depan']['tmp_name'][$i], $uploadDir . $fileNameDepan) || 
                    !move_uploaded_file($_FILES['foto_belakang']['tmp_name'][$i], $uploadDir . $fileNameBelakang)) {
                    throw new Exception("Server failed to save the photo file.");
                }

                $itemsData[] = [
                    'nama_kartu'    => $_POST['nama_kartu'][$i],
                    'foto_depan'    => $dbPath . $fileNameDepan,
                    'foto_belakang' => $dbPath . $fileNameBelakang,
                    'harga_beli'    => (float)$_POST['harga_beli'][$i]
                ];
            }

            $jsonItems = json_encode($itemsData);
            
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_SubmitBuyback(?, ?, ?, ?, ?, ?)}", 
                [$id_customer, $provider, $no_rekening, $total_barang, $total_harga, $jsonItems]);
            
            if ($stmt === false) throw new Exception(getSqlError());

            ob_clean();
            echo json_encode(["status" => "success", "message" => "Submission sent successfully!"]);
        } catch (Throwable $e) {
            ob_clean();
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case 'get_buyback_list':
        try {
            $role        = (int)($_GET['role'] ?? 0);
            $id_pengguna = (int)($_GET['id_pengguna'] ?? 0);
            $page        = max(1, intval($_GET['page'] ?? 1));
            // Limit bisa di-override lewat query (dipakai admin untuk menarik semua data
            // lalu difilter/sort/paginate di sisi client, sama seperti halaman laporan).
            $limit       = max(1, (int)($_GET['limit'] ?? 10));
            $offset      = ($page - 1) * $limit;
            $search      = trim($_GET['search'] ?? '');
            $status      = trim($_GET['status'] ?? ''); 
            
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetBuybackList(?, ?, ?, ?, ?, ?)}", 
                [$role, $id_pengguna, $search, $status, $limit, $offset]);
            
            if ($stmt === false) throw new Exception(getSqlError());

            $statusCounts = [];
            while ($rowG = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $statusCounts[$rowG['status_pembelian']] = $rowG['cnt'];
            }

            sqlsrv_next_result($stmt);
            $rowCount = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $totalData = $rowCount['total_data'] ?? 0;
            $totalPages = ceil($totalData / $limit);

            sqlsrv_next_result($stmt);
            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                foreach ($row as $key => $val) { if ($val instanceof DateTime) $row[$key] = $val->format('Y-m-d H:i:s'); }
                $data[] = $row;
            }
            
            ob_clean();
            echo json_encode(["status" => "success", "data" => $data, "pagination" => ["current_page" => $page, "total_pages" => $totalPages, "total_data" => $totalData], "status_counts" => $statusCounts]);
        } catch (Throwable $e) {
            ob_clean(); echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case 'update_status':
        try {
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UpdateBuybackStatus(?, ?, ?, ?)}", 
                [$_POST['id_pembelian'], $_POST['status'], $_POST['no_resi'] ?? null, $_POST['id_pengguna']]);
            if ($stmt === false) throw new Exception(getSqlError());
            
            ob_clean(); echo json_encode(["status" => "success", "message" => "Status updated successfully."]);
        } catch (Throwable $e) { ob_clean(); echo json_encode(["status" => "error", "message" => $e->getMessage()]); }
        break;

    case 'admin_negotiate':
        try {
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_AdminNegotiateCard(?, ?)}", [$_POST['id_kartu'], $_POST['penawaran_admin']]);
            if ($stmt === false) throw new Exception(getSqlError());
            ob_clean(); echo json_encode(["status" => "success"]);
        } catch (Throwable $e) { ob_clean(); echo json_encode(["status" => "error", "message" => $e->getMessage()]); }
        break;

    case 'customer_negotiate_item': 
        try {
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_CustomerNegotiateCard(?, ?, ?, ?)}", 
                [$_POST['id_kartu'], $_POST['id_pembelian'], $_POST['id_pengguna'], $_POST['penawaran_customer']]);
            if ($stmt === false) throw new Exception(getSqlError());
            ob_clean(); echo json_encode(["status" => "success"]);
        } catch (Throwable $e) { ob_clean(); echo json_encode(["status" => "error", "message" => $e->getMessage()]); }
        break;

    case 'customer_accept_item':
        try {
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_CustomerAcceptCard(?, ?, ?, ?)}", 
                [$_POST['id_kartu'], $_POST['id_pembelian'], $_POST['id_pengguna'], $_POST['harga_final']]);
            if ($stmt === false) throw new Exception(getSqlError());
            ob_clean(); echo json_encode(["status" => "success"]);
        } catch (Throwable $e) { ob_clean(); echo json_encode(["status" => "error", "message" => $e->getMessage()]); }
        break;

    case 'update_address':
        try {
        // FIX: Tukar posisi alamat_retur dan id_pengguna menyesuaikan struktur SP standar
        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UpdateBuybackAddress(?, ?, ?)}", 
            [$_POST['id_pembelian'], $_POST['alamat_retur'], $_POST['id_pengguna']]);
            if ($stmt === false) throw new Exception(getSqlError());
            ob_clean(); echo json_encode(["status" => "success", "message" => "Address saved successfully."]);
        } catch (Throwable $e) { ob_clean(); echo json_encode(["status" => "error", "message" => $e->getMessage()]); }
        break;
    case 'customer_negotiate':
        try {
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_CustomerNegotiateTransaction(?, ?, ?)}", 
                [$_POST['id_pembelian'], $_POST['id_pengguna'], $_POST['penawaran_customer']]);
            if ($stmt === false) throw new Exception(getSqlError());
            ob_clean(); echo json_encode(["status" => "success", "message" => "Counter-offer submitted."]);
        } catch (Throwable $e) { ob_clean(); echo json_encode(["status" => "error", "message" => $e->getMessage()]); }
        break;

    case 'get_detail':
        try {
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetBuybackDetail(?, ?, ?)}", [$_GET['id_pembelian'], $_GET['role'] ?? 0, $_GET['id_pengguna'] ?? 0]);
            if ($stmt === false) throw new Exception(getSqlError());
            
            $pembelian = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if (!$pembelian) throw new Exception("Access Denied or Transaction not found.");
            foreach ($pembelian as $key => $val) {
                if ($val instanceof DateTime) {
                    $pembelian[$key] = $val->format('Y-m-d H:i:s');
                }
            }
            sqlsrv_next_result($stmt);
            $kartu = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $kartu[] = $row;
            
            ob_clean();
            echo json_encode(["status" => "success", "data" => ["pembelian" => $pembelian, "kartu" => $kartu]]);
        } catch (Throwable $e) { ob_clean(); echo json_encode(["status" => "error", "message" => $e->getMessage()]); }
        break;

    case 'admin_send_payment':
        try {
            if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_OK) throw new Exception("Payment proof file is invalid or missing.");

            $uploadDir = '../../assets/image/buyback/payment/';
            $dbPath = 'assets/image/buyback/payment/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileName = uniqid('pay_') . '.' . pathinfo($_FILES['bukti_pembayaran']['name'], PATHINFO_EXTENSION);
            if (!move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $uploadDir . $fileName)) throw new Exception("Failed to save the payment proof file.");

            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_AdminSendPayment(?, ?, ?)}", [$_POST['id_pembelian'], $_POST['id_pengguna'], $dbPath . $fileName]);
            if ($stmt === false) throw new Exception(getSqlError());

            ob_clean(); echo json_encode(["status" => "success", "message" => "Payment sent and proof uploaded."]);
        } catch (Throwable $e) { ob_clean(); echo json_encode(["status" => "error", "message" => $e->getMessage()]); }
        break;

    case 'get_user_bank':
        try {
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetUserBank(?)}", [$_GET['id_pengguna'] ?? 0]);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            ob_clean(); echo json_encode(["status" => "success", "data" => $row]);
        } catch (Throwable $e) { ob_clean(); echo json_encode(["status" => "error", "message" => $e->getMessage()]); }
        break;
}
?>