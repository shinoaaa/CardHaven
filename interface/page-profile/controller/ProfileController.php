<?php
require_once __DIR__ . '/../../../connection.php'; // Asumsi pemanggilan conn DB
header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

if ($action === 'getProfile') {
    $user_id = $_GET['id_pengguna'] ?? '';
    
    // 1. Dapatkan Profil
    $sql = "SELECT username, email, foto_profil, no_telepon, created_date FROM pengguna WHERE id_pengguna = ?";
    $stmt = sqlsrv_query($conn, $sql, array($user_id));
    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if($user && $user['created_date'] instanceof DateTime) {
        // Output -> 25-01-2025 (Format dari Figma)
        $user['created_date'] = $user['created_date']->format('d-m-Y');
    }

    // 2. Dapatkan Nilai di Keranjang (Banyak / Count ID Keranjang, bukan Quantity)
    // Hitung total item di detail_keranjang milik user (via JOIN keranjang)
    $sqlCart = "SELECT COUNT(dk.id_detail_keranjang) as cart_count 
                FROM detail_keranjang dk
                INNER JOIN keranjang k ON dk.id_keranjang = k.id_keranjang
                WHERE k.id_pengguna = ?";
    $stmtCart = sqlsrv_query($conn, $sqlCart, array($user_id));
    $cart = sqlsrv_fetch_array($stmtCart, SQLSRV_FETCH_ASSOC);
    $user['cart_count'] = $cart['cart_count'] ?? 0;

    // 3. Dapatkan Nilai Penjualan Total (Menghiraukan ID = 8 / Canceled)
    $sqlExp = "SELECT SUM(total_harga) as total_exp FROM penjualan WHERE id_pengguna = ? AND status_penjualan != 8";
    $stmtExp = sqlsrv_query($conn, $sqlExp, array($user_id));
    $exp = sqlsrv_fetch_array($stmtExp, SQLSRV_FETCH_ASSOC);
    $user['total_expenditure'] = $exp['total_exp'] ?? 0;

    echo json_encode(['status'=>'success', 'data'=>$user]);
} 
elseif ($action === 'updateProfile') {
    $user_id  = $_POST['id_pengguna'] ?? '';
    $username = $_POST['editUsername'] ?? '';
    $email    = $_POST['editEmail'] ?? '';
    $notelp   = $_POST['editNoTelp'] ?? '';

    $foto_path = null; // null = tidak update foto

    // Handle upload foto jika ada
    if (isset($_FILES['editFoto']) && $_FILES['editFoto']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['editFoto'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            echo json_encode(['status' => 'error', 'msg' => 'Format foto tidak didukung (jpg/png/webp)']);
            exit;
        }

        if ($file['size'] > 2 * 1024 * 1024) { // Maks 2MB
            echo json_encode(['status' => 'error', 'msg' => 'Ukuran foto maksimal 2MB']);
            exit;
        }

        // Nama file unik: profil_{id}_{timestamp}.ext
        $filename  = 'profil_' . $user_id . '_' . time() . '.' . $ext;
        $uploadDir = __DIR__ . '/../../../image-profile/'; // sesuaikan path ke D:\image-profile
        $uploadPath = $uploadDir . $filename;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            echo json_encode(['status' => 'error', 'msg' => 'Gagal menyimpan foto']);
            exit;
        }

        // Path yang disimpan ke DB (relatif dari root cardhaven)
        $foto_path = 'image-profile/' . $filename;
    }

    // Query: update foto hanya kalau ada file baru
    if ($foto_path !== null) {
        $sql  = "UPDATE pengguna SET username=?, email=?, no_telepon=?, foto_profil=?, modified_date=GETDATE(), modified_by=? WHERE id_pengguna=? AND role=0";
        $params = array($username, $email, $notelp, $foto_path, $user_id, $user_id);
    } else {
        $sql  = "UPDATE pengguna SET username=?, email=?, no_telepon=?, modified_date=GETDATE(), modified_by=? WHERE id_pengguna=? AND role=0";
        $params = array($username, $email, $notelp, $user_id, $user_id);
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        echo json_encode(['status' => 'success', 'msg' => 'Profile Updated!']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Failed updating data']);
    }
}