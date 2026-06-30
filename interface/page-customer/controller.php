<?php 
    require_once __DIR__ . '/../../connection.php';
    header('Content-Type: application/json');
    
    $idPengguna = $_GET['id_pengguna'] ?? '';

    $query = "SELECT foto_profil FROM pengguna WHERE id_pengguna = ?";
    $stmt = sqlsrv_query($conn, $query, array($idPengguna)); // ✅ harus array

    if ($stmt) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC); // ✅ fetch dulu baru encode
        echo json_encode(['status' => 'success', 'data' => $row]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Query failed']);
    }
?>