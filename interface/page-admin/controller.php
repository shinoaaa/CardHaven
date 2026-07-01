<?php
// Pastikan path ini bener-bener ngarah ke file connection.php lu
// Kalau controller ini ada di interface/dashboard/, naik 2 level ke root: ../../connection.php
include '../../connection.php'; 

// Cek apakah ini request minta gambar profil
if (isset($_GET['action']) && $_GET['action'] == 'getProfileImage') {
    $id_pengguna = $_GET['id'] ?? '';

    if (!empty($id_pengguna)) {
        // Nama tabel diubah jadi 'pengguna'
        $query = "SELECT foto_profil FROM pengguna WHERE id_pengguna = ?";
        
        // Parameter untuk sqlsrv
        $params = array($id_pengguna);
        
        // Eksekusi query pakai fungsi sqlsrv
        $stmt = sqlsrv_query($conn, $query, $params);
        
        if ($stmt) {
            // Ambil datanya
            if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                echo json_encode([
                    'status' => 'success',
                    'image' => $row['foto_profil']
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'User not found.']);
            }
            sqlsrv_free_stmt($stmt);
        } else {
            // Kalau query gagal, tangkap error dari SQL Server
            $errors = sqlsrv_errors();
            echo json_encode(['status' => 'error', 'message' => 'Failed to retrieve profile image: ' . $errors[0]['message']]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
    }
    
    exit(); 
}
?>