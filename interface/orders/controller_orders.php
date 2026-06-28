<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['id_pengguna'])) { echo json_encode(['success' => false, 'message' => 'Unauthorized.']); exit; }

require_once __DIR__ . '/../../connection.php';
$id_pengguna = (int)$_SESSION['id_pengguna'];
$action = $_REQUEST['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'get_orders') {
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetCustomerOrders(?)}", [$id_pengguna]);
            if (!$stmt) throw new Exception(sqlsrv_errors()[0]['message']);
            
            $orders = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                if ($row['tanggal_penjualan'] instanceof DateTime) $row['tanggal_penjualan'] = $row['tanggal_penjualan']->format('Y-m-d H:i:s');
                $orders[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $orders]); exit;
        }
        
        if ($action === 'get_order_detail') {
            $id = (int)($_GET['id_penjualan'] ?? 0);
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetSalesDetail(?, ?)}", [$id, $id_pengguna]);
            if (!$stmt) throw new Exception(sqlsrv_errors()[0]['message']);
            
            $order = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if (!$order) throw new Exception("Order not found.");
            if ($order['tanggal_penjualan'] instanceof DateTime) $order['tanggal_penjualan'] = $order['tanggal_penjualan']->format('Y-m-d H:i:s');

            sqlsrv_next_result($stmt);
            $items = []; while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $items[] = $row;
            $order['items'] = $items;

            echo json_encode(['success' => true, 'data' => $order]); exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'confirm_received') {
            $id = (int)($_POST['id_penjualan'] ?? 0);
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UpdateSalesStatus(?, 5, ?)}", [$id, $id_pengguna]); 
            if (!$stmt) throw new Exception(sqlsrv_errors()[0]['message']);
            echo json_encode(['success' => true]); exit;
        }
        
        if ($action === 'cancel_order') {
            $id = (int)($_POST['id_penjualan'] ?? 0);
            $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UpdateSalesStatus(?, 8, ?)}", [$id, $id_pengguna]); 
            if (!$stmt) throw new Exception(sqlsrv_errors()[0]['message']);
            echo json_encode(['success' => true]); exit;
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>