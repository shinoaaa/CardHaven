<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

ob_start();

try {
    require_once '../../connection.php';
    if (!isset($conn) || !is_resource($conn)) {
        throw new Exception("Koneksi database tidak valid.");
    }

    $id_pengguna = (int)($_POST['id_pengguna_js'] ?? ($_GET['id_pengguna_js'] ?? ($_SESSION['id_pengguna'] ?? 0)));
    $action      = $_POST['action'] ?? ($_GET['action'] ?? '');

    if ($id_pengguna === 0) {
        throw new Exception("Unauthorized access. Sesi tidak ditemukan.");
    }

    // =====================================
    // 1. GET ITEMS (Pembacaan Data)
    // =====================================
    if ($action === 'get_items') {
        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetCartItems(?)}", [$id_pengguna]);
        if ($stmt === false) throw new Exception(sqlsrv_errors()[0]['message']);
        
        $items = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Pastikan format tipe data tepat
            $row['harga_produk'] = (float)$row['harga_produk'];
            $row['subtotal_harga'] = (float)$row['subtotal_harga'];
            $row['jumlah_barang'] = (int)$row['jumlah_barang'];
            $items[] = $row;
        }
        
        ob_clean();
        echo json_encode($items); // Script JS keranjang Anda mengekspektasikan array langsung di sini
        exit;
    }

    // =====================================
    // 2. MANIPULASI DATA KERANJANG (DML)
    // =====================================
    $id_detail = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $id_produk = isset($_POST['id_produk']) ? (int)$_POST['id_produk'] : null;
    $harga     = isset($_POST['harga_produk']) ? (float)$_POST['harga_produk'] : 0;
    
    // Identifikasi QTY berdasarkan jenis action
    $qty = 0;
    if ($action === 'add_to_cart') $qty = (int)($_POST['jumlah'] ?? 1);
    if ($action === 'update_qty')  $qty = (int)($_POST['change'] ?? 0);
    
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
    
    // Penerjemah aksi untuk Stored Procedure
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
        
        ob_clean();
        echo json_encode(['success' => true]);
        exit;
    }

    throw new Exception("Action tidak dikenali.");

} catch (Throwable $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
?>