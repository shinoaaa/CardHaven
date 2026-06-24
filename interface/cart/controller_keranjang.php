<?php
session_start();
require_once '../../connection.php'; // Pastikan path koneksi benar
header('Content-Type: application/json');

// --- LOGIKA IDENTITAS (Sama dengan Controller Game) ---
// Mengambil ID dari POST (FormData), GET (URL), atau Session sebagai cadangan
$id_pengguna = $_POST['id_pengguna_js'] ?? ($_GET['id_pengguna_js'] ?? ($_SESSION['id_pengguna'] ?? 0));

if ($id_pengguna == 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
if ($action === 'add_to_cart') {
    $id_produk = $_POST['id_produk'];
    $harga = $_POST['harga_produk'];

    // 1. Cari atau Buat Header Keranjang
    $sql_k = "SELECT id_keranjang FROM dbo.keranjang WHERE id_pengguna = ?";
    $stmt_k = sqlsrv_query($conn, $sql_k, [$id_pengguna]);
    $row_k = sqlsrv_fetch_array($stmt_k, SQLSRV_FETCH_ASSOC);

    if (!$row_k) {
        $sql_ins_k = "INSERT INTO dbo.keranjang (id_pengguna) OUTPUT INSERTED.id_keranjang VALUES (?)";
        $stmt_ins_k = sqlsrv_query($conn, $sql_ins_k, [$id_pengguna]);
        $row_new_k = sqlsrv_fetch_array($stmt_ins_k, SQLSRV_FETCH_ASSOC);
        $id_keranjang = $row_new_k['id_keranjang'];
    } else {
        $id_keranjang = $row_k['id_keranjang'];
    }

    // 2. Cek apakah produk sudah ada di detail keranjang
    $sql_check = "SELECT id_detail_keranjang, jumlah_barang FROM dbo.detail_keranjang 
                  WHERE id_keranjang = ? AND id_produk = ?";
    $stmt_check = sqlsrv_query($conn, $sql_check, [$id_keranjang, $id_produk]);
    $row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);

    if ($row_check) {
        // Jika ada, tambahkan qty +1
        $new_qty = $row_check['jumlah_barang'] + 1;
        $sql_upd = "UPDATE dbo.detail_keranjang SET jumlah_barang = ?, subtotal_harga = (? * harga_produk) 
                    WHERE id_detail_keranjang = ?";
        $stmt_final = sqlsrv_query($conn, $sql_upd, [$new_qty, $new_qty, $row_check['id_detail_keranjang']]);
    } else {
        // Jika belum ada, masukkan baru
        $sql_ins_d = "INSERT INTO dbo.detail_keranjang (id_keranjang, id_produk, jumlah_barang, harga_produk, subtotal_harga, is_selected) 
                      VALUES (?, ?, 1, ?, ?, 1)";
        $stmt_final = sqlsrv_query($conn, $sql_ins_d, [$id_keranjang, $id_produk, $harga, $harga]);
    }

    echo json_encode(['success' => $stmt_final ? true : false]);
    exit;
}
// 1. AMBIL DATA KERANJANG
if ($action === 'get_items') {
    $sql = "SELECT dk.*, p.nama_produk, p.foto, p.harga_jual as harga_produk 
            FROM dbo.detail_keranjang dk
            JOIN dbo.keranjang k ON dk.id_keranjang = k.id_keranjang
            JOIN dbo.produk p ON dk.id_produk = p.id_produk
            WHERE k.id_pengguna = ?";
    
    $stmt = sqlsrv_query($conn, $sql, [$id_pengguna]);
    $items = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $items[] = $row;
        }
    }
    echo json_encode($items);
    exit;
}

// 2. UPDATE KUANTITAS
if ($action === 'update_qty') {
    $id_detail = $_POST['id'];
    $change = (int)$_POST['change'];

    // Query update dengan JOIN untuk memastikan item milik user tersebut
    $sql = "UPDATE dk 
            SET dk.jumlah_barang = CASE 
                    WHEN dk.jumlah_barang + ? < 1 THEN 1 
                    ELSE dk.jumlah_barang + ? 
                END,
                dk.subtotal_harga = (CASE WHEN dk.jumlah_barang + ? < 1 THEN 1 ELSE dk.jumlah_barang + ? END) * dk.harga_produk
            FROM dbo.detail_keranjang dk
            JOIN dbo.keranjang k ON dk.id_keranjang = k.id_keranjang
            WHERE dk.id_detail_keranjang = ? AND k.id_pengguna = ?";
            
    $params = [$change, $change, $change, $change, $id_detail, $id_pengguna];
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    echo json_encode(['success' => $stmt ? true : false]);
    exit;
}

// 3. HAPUS ITEM
if ($action === 'delete') {
    $id_detail = $_POST['id'];
    $sql = "DELETE dk FROM dbo.detail_keranjang dk
            JOIN dbo.keranjang k ON dk.id_keranjang = k.id_keranjang
            WHERE dk.id_detail_keranjang = ? AND k.id_pengguna = ?";
            
    $stmt = sqlsrv_query($conn, $sql, [$id_detail, $id_pengguna]);
    echo json_encode(['success' => $stmt ? true : false]);
    exit;
}

// 4. TOGGLE CEKLIST (Satu Item)
if ($action === 'toggle_select') {
    $id_detail = $_POST['id'];
    $status = $_POST['status'];
    $sql = "UPDATE dk SET dk.is_selected = ?
            FROM dbo.detail_keranjang dk
            JOIN dbo.keranjang k ON dk.id_keranjang = k.id_keranjang
            WHERE dk.id_detail_keranjang = ? AND k.id_pengguna = ?";
            
    $stmt = sqlsrv_query($conn, $sql, [$status, $id_detail, $id_pengguna]);
    echo json_encode(['success' => $stmt ? true : false]);
    exit;
}

// 5. CEKLIST SEMUA (Select All)
if ($action === 'select_all') {
    $status = $_POST['status'];
    $sql = "UPDATE dk SET dk.is_selected = ?
            FROM dbo.detail_keranjang dk
            JOIN dbo.keranjang k ON dk.id_keranjang = k.id_keranjang
            WHERE k.id_pengguna = ?";
            
    $stmt = sqlsrv_query($conn, $sql, [$status, $id_pengguna]);
    echo json_encode(['success' => $stmt ? true : false]);
    exit;
}
?>