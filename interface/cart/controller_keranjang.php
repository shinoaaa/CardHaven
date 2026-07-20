<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

ob_start();

try {
    require_once '../../connection.php';
    require_once __DIR__ . '/../../auth/session.php';
    if (!isset($conn) || !is_resource($conn)) {
        throw new Exception("Invalid database connection.");
    }

    $id_pengguna = auth_api_require_login()['id'];
    $action      = $_POST['action'] ?? ($_GET['action'] ?? '');

    // =====================================
    // 1. GET ITEMS (Pembacaan Data)
    // =====================================
    if ($action === 'get_items') {
        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetCartItems(?)}", [$id_pengguna]);
        if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
        
        $items = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $row['harga_produk']   = (float)$row['harga_produk'];
            $row['subtotal_harga'] = (float)$row['subtotal_harga'];
            $row['jumlah_barang']  = (int)$row['jumlah_barang'];
            $row['stok']           = (int)$row['stok'];
            
            $items[] = $row;
        }
        
        ob_clean();
        echo json_encode($items);
        exit;
    }

    // =====================================
    // 1b. BUY NOW (Checkout langsung 1 produk)
    // =====================================
    if ($action === 'buy_now') {
        $id_produk = (int)($_POST['id_produk'] ?? 0);
        $harga     = (float)($_POST['harga_produk'] ?? 0);
        $qty       = max(1, (int)($_POST['jumlah'] ?? 1));
        
        if ($id_produk <= 0) throw new Exception("Invalid product.");

        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_BuyNowCart(?, ?, ?, ?)}", 
            [$id_pengguna, $id_produk, $harga, $qty]);
            
        if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
        
        $res = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($res && $res['Status'] === 'ERROR') {
            throw new Exception($res['Message']);
        }

        ob_clean();
        echo json_encode(['success' => true, 'redirect' => '/CardHaven/checkout']);
        exit;
    }

    // =====================================
    // 2. MANIPULASI DATA KERANJANG (DML)
    // =====================================
    $id_detail = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $id_produk = isset($_POST['id_produk']) ? (int)$_POST['id_produk'] : null;
    $harga     = isset($_POST['harga_produk']) ? (float)$_POST['harga_produk'] : 0;
    
    $qty = 0;
    if ($action === 'add_to_cart') $qty = (int)($_POST['jumlah'] ?? 1);
    if ($action === 'update_qty')  $qty = (int)($_POST['change'] ?? 0);
    
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
    
    $sp_action = '';
    if ($action === 'add_to_cart')   $sp_action = 'add';
    if ($action === 'update_qty')    $sp_action = 'update_qty';
    if ($action === 'delete')        $sp_action = 'delete';
    if ($action === 'toggle_select') $sp_action = 'toggle_select';
    if ($action === 'select_all')    $sp_action = 'select_all';

    if ($sp_action !== '') {
        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageCart(?, ?, ?, ?, ?, ?, ?)}", 
            [$sp_action, $id_pengguna, $id_detail, $id_produk, $harga, $qty, $status]);
        
        if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
        
        $res = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($res && $res['Status'] === 'ERROR') {
            throw new Exception($res['Message']);
        }
        
        ob_clean();
        echo json_encode(['success' => true]);
        exit;
    }

    throw new Exception("Unrecognized action.");

} catch (Throwable $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
?>