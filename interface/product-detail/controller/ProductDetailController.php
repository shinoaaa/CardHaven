<?php
/**
 * interface/product-detail/controller/ProductDetailController.php
 */
// Sesuai instruksi: Jika controller di dalam folder, pathnya /../../../connection.php
require_once __DIR__ . '/../../../connection.php'; 

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

if ($action === 'get_detail') {
    $id_produk = $_GET['id_produk'] ?? 0;
    
    // Panggil Stored Procedure yang telah kita buat
    $sql = "EXEC dbo.sp_GetProductDetail @IdProduk = ?";
    $stmt = sqlsrv_query($conn, $sql, array($id_produk));
    
    if ($stmt) {
        $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($data) {
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Product not found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Query Failed']);
    }
}
elseif ($action === 'get_related') {
    // Tangkap parameter, ubah teks 'null' dari fetch API jadi benar-benar nilai null/0
    $id_game = $_GET['id_game'] ?? null;
    if ($id_game === 'null' || $id_game === '') {
        $id_game = null;
    }
    
    $id_produk = $_GET['id_produk'] ?? 0;
    
    // Panggil Stored Procedure
    $sql = "EXEC dbo.sp_GetRelatedProducts @IdGame = ?, @IdProduk = ?";
    $stmt = sqlsrv_query($conn, $sql, array($id_game, $id_produk));
    
    $result = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $result]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Failed to fetch related']);
    }
}
elseif ($action === 'add_to_cart') {
    // Dipanggil melalui POST request
    $id_pengguna = $_POST['id_pengguna'] ?? 0;
    $id_produk = $_POST['id_produk'] ?? 0;
    $harga = $_POST['harga'] ?? 0;
    $qty = $_POST['qty'] ?? 1;

    if (!$id_pengguna || !$id_produk) {
        echo json_encode(['status' => 'error', 'msg' => 'Invalid parameters.']);
        exit;
    }

    // Panggil Stored Procedure sp_ManageCart sesuai yang Anda kirimkan di prompt
    $sql = "EXEC dbo.sp_ManageCart 
            @Action = 'add', 
            @IdPengguna = ?, 
            @IdProduk = ?, 
            @Harga = ?, 
            @Qty = ?";
    
    $params = array($id_pengguna, $id_produk, $harga, $qty);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt) {
        echo json_encode(['status' => 'success', 'msg' => 'Added to cart']);
    } else {
        $errors = sqlsrv_errors();
        echo json_encode(['status' => 'error', 'msg' => 'Failed executing SP', 'db_error' => $errors]);
    }
}
?>