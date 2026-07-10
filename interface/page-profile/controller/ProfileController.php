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
            echo json_encode(['status' => 'error', 'msg' => 'Unsupported photo format (jpg/png/webp)']);
            exit;
        }

        if ($file['size'] > 2 * 1024 * 1024) { // Maks 2MB
            echo json_encode(['status' => 'error', 'msg' => 'Maximum photo size is 2MB']);
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
            echo json_encode(['status' => 'error', 'msg' => 'Failed to save photo']);
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
// Daftar pesanan "Buy Product" milik customer (untuk tab di halaman profil)
// ==========================================
// MENGAMBIL DATA ORDER NORMAL (SALES)
// ==========================================
elseif ($action === 'getOrders') {
    $user_id = (int)($_GET['id_pengguna'] ?? 0);
    if ($user_id <= 0) { echo json_encode(['status' => 'error', 'data' => []]); exit; }

    // PERBAIKAN: Tambahkan AND p.tanggal_sampai IS NULL agar Preorder tidak ikut masuk ke sini
    $sql = "SELECT p.id_penjualan, p.tanggal_penjualan, p.alamat, p.total_barang, p.total_harga,
                   p.status_penjualan, p.no_resi, m.nama_metode
            FROM penjualan p
            LEFT JOIN metode_pembayaran m ON p.id_metode = m.id_metode
            WHERE p.id_pengguna = ? AND p.tanggal_sampai IS NULL
            ORDER BY p.tanggal_penjualan DESC, p.id_penjualan DESC";
    $stmt = sqlsrv_query($conn, $sql, array($user_id));

    $orders = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['tanggal_penjualan'] instanceof DateTime) {
                $row['tanggal_penjualan'] = $row['tanggal_penjualan']->format('Y-m-d H:i:s');
            }
            $row['total_harga'] = (float)$row['total_harga'];
            $orders[] = $row;
        }
    }
    echo json_encode(['status' => 'success', 'data' => $orders]);
}

// ==========================================
// MENGAMBIL DATA PREORDER
// ==========================================
elseif ($action === 'getPreorders') {
    $user_id = (int)($_GET['id_pengguna'] ?? 0);
    if ($user_id <= 0) { echo json_encode(['status' => 'error', 'data' => []]); exit; }

    // AMBIL DATA YANG TANGGAL PENGIRIMANNYA TIDAK NULL
    // Serta kita Sub-Query untuk mengambil 1 nama produknya
    $sql = "SELECT 
                p.id_penjualan, 
                p.tanggal_penjualan, 
                p.tanggal_sampai AS tanggal_sampai, 
                p.total_barang, 
                p.total_harga,
                p.status_penjualan, 
                p.no_resi, 
                m.nama_metode,
                (
                    SELECT TOP 1 pr.nama_produk
                    FROM detail_penjualan dp
                    JOIN produk pr ON dp.id_produk = pr.id_produk
                    WHERE dp.id_penjualan = p.id_penjualan
                ) AS nama_produk
            FROM penjualan p
            LEFT JOIN metode_pembayaran m ON p.id_metode = m.id_metode
            WHERE p.id_pengguna = ? AND p.tanggal_sampai IS NOT NULL
            ORDER BY p.tanggal_penjualan DESC, p.id_penjualan DESC";
            
    $stmt = sqlsrv_query($conn, $sql, array($user_id));

    $preorders = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['tanggal_penjualan'] instanceof DateTime) {
                $row['tanggal_penjualan'] = $row['tanggal_penjualan']->format('Y-m-d H:i:s');
            }
            if ($row['tanggal_sampai'] instanceof DateTime) {
                $row['tanggal_sampai'] = $row['tanggal_sampai']->format('Y-m-d');
            }
            $row['total_harga'] = (float)$row['total_harga'];
            $preorders[] = $row;
        }
    }
    echo json_encode(['status' => 'success', 'data' => $preorders]);
}

// Detail satu pesanan (dipakai tombol ••• di tabel profil)
elseif ($action === 'getOrderDetail') {
    $user_id = (int)($_GET['id_pengguna'] ?? 0);
    $id_penjualan = (int)($_GET['id_penjualan'] ?? 0);
    if ($user_id <= 0 || $id_penjualan <= 0) { echo json_encode(['status' => 'error', 'msg' => 'Invalid request.']); exit; }

    // sp_GetSalesDetail(id, id_pengguna) -> hanya milik user tsb
    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetSalesDetail(?, ?)}", array($id_penjualan, $user_id));
    if (!$stmt) { echo json_encode(['status' => 'error', 'msg' => 'Order not found.']); exit; }

    $order = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$order) { echo json_encode(['status' => 'error', 'msg' => 'Order not found.']); exit; }
    if ($order['tanggal_penjualan'] instanceof DateTime) {
        $order['tanggal_penjualan'] = $order['tanggal_penjualan']->format('Y-m-d H:i:s');
    }

    sqlsrv_next_result($stmt);
    $items = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $items[] = $row;
    $order['items'] = $items;

    echo json_encode(['status' => 'success', 'data' => $order]);
}