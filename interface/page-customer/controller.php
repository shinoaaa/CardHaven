<?php
    require_once __DIR__ . '/../../connection.php';
    require_once __DIR__ . '/../../auth/session.php';
    header('Content-Type: application/json');

    // id_pengguna diambil dari session, bukan dari query string, supaya user
    // tidak bisa mengintip data profil orang lain dengan mengganti URL.
    $user = auth_api_require_login();
    $idPengguna = $user['id'];

    $query = "SELECT foto_profil FROM pengguna WHERE id_pengguna = ?";
    $stmt = sqlsrv_query($conn, $query, array($idPengguna)); // ✅ harus array

    if ($stmt) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC); // ✅ fetch dulu baru encode
        echo json_encode(['status' => 'success', 'data' => $row]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Query failed']);
    }
?>
