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

    // Keranjang itu milik pribadi: id_pengguna SELALU dari session.
    // Dulu id dibaca dari POST/GET (id_pengguna_js), jadi siapa pun bisa
    // melihat & mengubah keranjang orang lain dengan mengganti angka id.
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
            // Pastikan format tipe data tepat
            $row['harga_produk'] = (float)$row['harga_produk'];
            $row['subtotal_harga'] = (float)$row['subtotal_harga'];
            $row['jumlah_barang'] = (int)$row['jumlah_barang'];
            
            if (!isset($row['stok'])) {
                $rp = sqlsrv_query($conn, "SELECT stok FROM dbo.produk WHERE id_produk = ?", [$row['id_produk']]);
                if ($rp) {
                    $prod = sqlsrv_fetch_array($rp, SQLSRV_FETCH_ASSOC);
                    $row['stok'] = (int)$prod['stok'];
                } else {
                    $row['stok'] = 0;
                }
            } else {
                $row['stok'] = (int)$row['stok'];
            }
            
            $items[] = $row;
        }
        
        ob_clean();
        echo json_encode($items); // Script JS keranjang Anda mengekspektasikan array langsung di sini
        exit;
    }

    // =====================================
    // 1b. BUY NOW (Checkout langsung 1 produk)
    // =====================================
    // Menyiapkan keranjang agar HANYA produk ini yang terpilih (is_selected=1),
    // lalu front-end mengarahkan ke halaman checkout. Reuse alur checkout yang
    // sudah ada (sp_GetCheckoutData & sp_PlaceOrder membaca item is_selected).
    if ($action === 'buy_now') {
        $id_produk = (int)($_POST['id_produk'] ?? 0);
        $harga     = (float)($_POST['harga_produk'] ?? 0);
        $qty       = max(1, (int)($_POST['jumlah'] ?? 1));
        if ($id_produk <= 0) throw new Exception("Invalid product.");

        // Ambil / buat header keranjang
        $rk = sqlsrv_query($conn, "SELECT id_keranjang FROM dbo.keranjang WHERE id_pengguna = ?", [$id_pengguna]);
        if ($rk === false) throw new Exception(sqlsrv_errors()[0]['message']);
        $rowK = sqlsrv_fetch_array($rk, SQLSRV_FETCH_ASSOC);
        if ($rowK) {
            $id_keranjang = (int)$rowK['id_keranjang'];
        } else {
            sqlsrv_query($conn, "INSERT INTO dbo.keranjang (id_pengguna) VALUES (?)", [$id_pengguna]);
            $rk2 = sqlsrv_query($conn, "SELECT id_keranjang FROM dbo.keranjang WHERE id_pengguna = ?", [$id_pengguna]);
            $id_keranjang = (int)sqlsrv_fetch_array($rk2, SQLSRV_FETCH_ASSOC)['id_keranjang'];
        }

        // Validasi stok & ambil harga acuan
        $rp = sqlsrv_query($conn, "SELECT stok, harga_jual FROM dbo.produk WHERE id_produk = ?", [$id_produk]);
        if ($rp === false) throw new Exception(sqlsrv_errors()[0]['message']);
        $prod = sqlsrv_fetch_array($rp, SQLSRV_FETCH_ASSOC);
        if (!$prod) throw new Exception("Product not found.");
        $stok = (int)$prod['stok'];
        if ($stok <= 0) throw new Exception("This product is out of stock.");
        if ($qty > $stok) $qty = $stok;
        if ($harga <= 0) $harga = (float)$prod['harga_jual'];

        // Upsert qty PERSIS (bukan menambah) untuk produk ini
        $rex = sqlsrv_query($conn, "SELECT id_detail_keranjang FROM dbo.detail_keranjang WHERE id_keranjang = ? AND id_produk = ?", [$id_keranjang, $id_produk]);
        $ex  = $rex ? sqlsrv_fetch_array($rex, SQLSRV_FETCH_ASSOC) : null;
        if ($ex) {
            sqlsrv_query($conn, "UPDATE dbo.detail_keranjang SET jumlah_barang = ?, harga_produk = ?, subtotal_harga = ? * ? WHERE id_detail_keranjang = ?",
                [$qty, $harga, $qty, $harga, (int)$ex['id_detail_keranjang']]);
        } else {
            sqlsrv_query($conn, "INSERT INTO dbo.detail_keranjang (id_keranjang, id_produk, jumlah_barang, harga_produk, subtotal_harga, is_selected) VALUES (?, ?, ?, ?, ? * ?, 1)",
                [$id_keranjang, $id_produk, $qty, $harga, $qty, $harga]);
        }

        // Pilih HANYA produk ini
        sqlsrv_query($conn, "UPDATE dbo.detail_keranjang SET is_selected = 0 WHERE id_keranjang = ?", [$id_keranjang]);
        sqlsrv_query($conn, "UPDATE dbo.detail_keranjang SET is_selected = 1 WHERE id_keranjang = ? AND id_produk = ?", [$id_keranjang, $id_produk]);

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
    
    // Identifikasi QTY berdasarkan jenis action
    $qty = 0;
    if ($action === 'add_to_cart') $qty = (int)($_POST['jumlah'] ?? 1);
    if ($action === 'update_qty')  $qty = (int)($_POST['change'] ?? 0);
    
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
    
    // Validasi stok sebelum update
    if ($action === 'add_to_cart') {
        $rp = sqlsrv_query($conn, "SELECT stok FROM dbo.produk WHERE id_produk = ?", [$id_produk]);
        $prod = sqlsrv_fetch_array($rp, SQLSRV_FETCH_ASSOC);
        if ($prod) {
            $stok = (int)$prod['stok'];
            $rk = sqlsrv_query($conn, "SELECT dk.jumlah_barang FROM dbo.detail_keranjang dk JOIN dbo.keranjang k ON dk.id_keranjang = k.id_keranjang WHERE k.id_pengguna = ? AND dk.id_produk = ?", [$id_pengguna, $id_produk]);
            $ex = sqlsrv_fetch_array($rk, SQLSRV_FETCH_ASSOC);
            $current_qty = $ex ? (int)$ex['jumlah_barang'] : 0;
            if ($current_qty + $qty > $stok) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => "Not enough stock! You already have $current_qty in your cart, remaining stock: $stok."]);
                exit;
            }
        }
    } else if ($action === 'update_qty') {
        $rp = sqlsrv_query($conn, "SELECT p.stok, dk.jumlah_barang FROM dbo.detail_keranjang dk JOIN dbo.produk p ON dk.id_produk = p.id_produk WHERE dk.id_detail_keranjang = ?", [$id_detail]);
        $prod = sqlsrv_fetch_array($rp, SQLSRV_FETCH_ASSOC);
        if ($prod) {
            $stok = (int)$prod['stok'];
            $current_qty = (int)$prod['jumlah_barang'];
            if ($current_qty + $qty > $stok) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => "Exceeds available stock limit ($stok)."]);
                exit;
            }
        }
    }
    
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

    throw new Exception("Unrecognized action.");

} catch (Throwable $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
?>