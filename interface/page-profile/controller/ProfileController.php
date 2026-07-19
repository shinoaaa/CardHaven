<?php
require_once __DIR__ . '/../../../connection.php'; // Asumsi pemanggilan conn DB
require_once __DIR__ . '/../../../auth/session.php';
header('Content-Type: application/json');

// Semua data di halaman profil adalah milik user yang sedang login, jadi
// id_pengguna SELALU dari session. Sebelumnya id dikirim browser, sehingga
// siapa pun bisa melihat profil, pesanan, dan total belanja orang lain.
$user_id = auth_api_require_login()['id'];

$action = $_GET['action'] ?? '';

if ($action === 'getProfile') {

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
        $uploadDir = __DIR__ . '/../../../assets/image/image-profile/';
        $uploadPath = $uploadDir . $filename;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            echo json_encode(['status' => 'error', 'msg' => 'Failed to save photo']);
            exit;
        }

        // Yang disimpan ke DB HANYA nama file. Path folder (assets/image/image-profile/)
        // ditambahkan saat menampilkan, bukan disimpan.
        $foto_path = $filename;
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
    $id_penjualan = (int)($_GET['id_penjualan'] ?? 0);
    if ($id_penjualan <= 0) { echo json_encode(['status' => 'error', 'msg' => 'Invalid request.']); exit; }

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

elseif($action === 'cancelOrder'){
    // 1. Ambil data JSON dari request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $id_penjualan = isset($data['id_penjualan']) ? (int)$data['id_penjualan'] : 0;
    // Pembatal pesanan = user yang sedang login (dari session), bukan id kiriman browser.
    $id_pengguna = $user_id;

    if ($id_penjualan === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak valid.']);
        exit;
    }

    $cek_stmt = sqlsrv_query($conn, "SELECT id_pengguna FROM dbo.penjualan WHERE id_penjualan = ?", [$id_penjualan]);
    $row = sqlsrv_fetch_array($cek_stmt, SQLSRV_FETCH_ASSOC);

    if (!$row || $row['id_pengguna'] != $id_pengguna) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found or access denied.']);
        exit;
    }

    $params = [
        $id_penjualan, 
        8, 
        $id_pengguna, 
        null, 
        null, 
        null
    ];
    
    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UpdateSalesStatus(?, ?, ?, ?, ?, ?)}", $params);

    if ($stmt) {
        echo json_encode(['status' => 'success', 'message' => 'The order was successfully canceled.']);
    } else {
        $errors = sqlsrv_errors();
        $error_msg = 'Failed to cancel the order.';
        if ($errors !== null) {
            // Ambil pesan error SQL Server yang dilempar dari blok THROW
            $error_msg = $errors[0]['message']; 
            
        }
        
        echo json_encode(['status' => 'error', 'message' => $error_msg]);
    }
}
elseif ($action === 'completeOrder') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $id_penjualan = isset($data['id_penjualan']) ? (int)$data['id_penjualan'] : 0;
    // Pelaku aksi = user yang sedang login (dari session), bukan id kiriman browser.
    $id_pengguna = (int)$user_id;

    if ($id_penjualan === 0 || $id_pengguna === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
        exit;
    }

    $cek_stmt = sqlsrv_query($conn, "SELECT id_pengguna FROM dbo.penjualan WHERE id_penjualan = ?", [$id_penjualan]);
    $row = sqlsrv_fetch_array($cek_stmt, SQLSRV_FETCH_ASSOC);

    if (!$row || $row['id_pengguna'] != $id_pengguna) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found or access denied.']);
        exit;
    }

    // Update status menjadi 6 (Completed)
    $params = [
        $id_penjualan,
        6,
        $id_pengguna, 
        null, 
        null, 
        null
    ];
    
    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UpdateSalesStatus(?, ?, ?, ?, ?, ?)}", $params);

    if ($stmt) {
        echo json_encode(['status' => 'success', 'message' => 'The order has been successfully completed.']);
    } else {
        $errors = sqlsrv_errors();
        $error_msg = 'Failed to complete the order.';
        if ($errors !== null) {
            // Ambil pesan error SQL Server yang dilempar dari blok THROW
            $error_msg = $errors[0]['message']; 
            
        }
        
        echo json_encode(['status' => 'error', 'message' => $error_msg]);
    }
}
elseif ($action === 'returnOrder') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $id_penjualan = isset($data['id_penjualan']) ? (int)$data['id_penjualan'] : 0;
    // Pelaku aksi = user yang sedang login (dari session), bukan id kiriman browser.
    $id_pengguna = (int)$user_id;

    if ($id_penjualan === 0 || $id_pengguna === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
        exit;
    }

    $cek_stmt = sqlsrv_query($conn, "SELECT id_pengguna FROM dbo.penjualan WHERE id_penjualan = ?", [$id_penjualan]);
    $row = sqlsrv_fetch_array($cek_stmt, SQLSRV_FETCH_ASSOC);

    if (!$row || $row['id_pengguna'] != $id_pengguna) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found or access denied.']);
        exit;
    }

    // Update status menjadi 7 (Returned)
    $params = [
        $id_penjualan,
        7,
        $id_pengguna, 
        null, 
        null, 
        null
    ];
    
    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_UpdateSalesStatus(?, ?, ?, ?, ?, ?)}", $params);

    if ($stmt) {
        echo json_encode(['status' => 'success', 'message' => 'Return request has been successfully submitted.']);
    } else {
        $errors = sqlsrv_errors();
        $error_msg = 'Failed to submit return request.';
        if ($errors !== null) {
            // Ambil pesan error SQL Server yang dilempar dari blok THROW
            $error_msg = $errors[0]['message']; 
            
        }
        
        echo json_encode(['status' => 'error', 'message' => $error_msg]);
    }
}