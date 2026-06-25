<?php
session_start();
header('Content-Type: application/json');

// ── Cek session ──────────────────────────────────────────────
if (!isset($_SESSION['id_pengguna'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Silakan login terlebih dahulu.']);
    exit;
}

require_once __DIR__ . '/../../connection.php';

$id_pengguna = (int) $_SESSION['id_pengguna'];
$action      = $_REQUEST['action'] ?? '';

// ── Router ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'get_user_info':       actionGetUserInfo($conn, $id_pengguna);      break;
        case 'get_selected_items':  actionGetSelectedItems($conn, $id_pengguna); break;
        case 'get_payment_methods': actionGetPaymentMethods($conn);              break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'place_order':   actionPlaceOrder($conn, $id_pengguna);   break;
        case 'upload_bukti':  actionUploadBukti($conn, $id_pengguna);  break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
exit;


// ════════════════════════════════════════════════════════════════
// FUNGSI-FUNGSI
// ════════════════════════════════════════════════════════════════

/**
 * GET: Ambil info user (username & no_telepon) dari sesi
 */
function actionGetUserInfo($conn, $id_pengguna) {
    $sql  = "SELECT username, no_telepon FROM dbo.pengguna WHERE id_pengguna = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id_pengguna]);

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil data user.']);
        return;
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($row) {
        echo json_encode([
            'success'    => true,
            'username'   => $row['username']   ?? '',
            'no_telepon' => $row['no_telepon'] ?? '',
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan.']);
    }
}

/**
 * GET: Ambil item keranjang yang is_selected = 1 milik user
 */
function actionGetSelectedItems($conn, $id_pengguna) {
    $sql = "
        SELECT
            dk.id_detail_keranjang,
            dk.id_produk,
            dk.jumlah_barang,
            dk.harga_produk,
            dk.subtotal_harga,
            p.nama_produk,
            p.foto,
            p.stok
        FROM dbo.detail_keranjang dk
        JOIN dbo.keranjang k        ON dk.id_keranjang = k.id_keranjang
        JOIN dbo.produk p           ON dk.id_produk    = p.id_produk
        WHERE k.id_pengguna = ?
          AND dk.is_selected = 1
    ";

    $stmt = sqlsrv_query($conn, $sql, [$id_pengguna]);
    $items = [];

    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Konversi Decimal agar JSON-serializable
            $row['harga_produk']   = (float) $row['harga_produk'];
            $row['subtotal_harga'] = (float) $row['subtotal_harga'];
            $row['jumlah_barang']  = (int)   $row['jumlah_barang'];
            $row['stok']           = (int)   $row['stok'];
            $items[] = $row;
        }
    }

    echo json_encode($items);
}

/**
 * GET: Ambil daftar metode pembayaran yang aktif dan tidak dihapus
 */
function actionGetPaymentMethods($conn) {
    $sql = "
        SELECT
            id_metode,
            nama_metode,
            provider,
            no_rekening,
            atas_nama,
            biaya_admin
        FROM dbo.metode_pembayaran
        WHERE aktif      = 1
          AND is_deleted = 0
        ORDER BY nama_metode ASC
    ";

    $stmt    = sqlsrv_query($conn, $sql);
    $methods = [];

    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $row['biaya_admin'] = (float) $row['biaya_admin'];
            $methods[] = $row;
        }
    }

    echo json_encode($methods);
}

/**
 * POST: Buat order baru
 *
 * Flow:
 * 1. Validasi input
 * 2. Ambil item is_selected dari keranjang (re-query agar tidak bisa dimanipulasi dari JS)
 * 3. Cek stok tiap produk
 * 4. Hitung total
 * 5. INSERT dbo.penjualan  → dapat id_penjualan
 * 6. INSERT dbo.detail_penjualan (INSTEAD OF INSERT trigger TRG_Penjualan_CegahMinus aktif)
 * 7. Hapus item terpilih dari keranjang
 * 8. Return id_penjualan + payment_detail untuk tampilan Step 2
 */
function actionPlaceOrder($conn, $id_pengguna) {
    // ── 1. Validasi input ──────────────────────────────────────
    $alamat    = trim($_POST['alamat']    ?? '');
    $id_metode = (int) ($_POST['id_metode'] ?? 0);

    if (empty($alamat)) {
        echo json_encode(['success' => false, 'message' => 'Alamat pengiriman tidak boleh kosong.']);
        return;
    }
    if ($id_metode <= 0) {
        echo json_encode(['success' => false, 'message' => 'Metode pembayaran tidak valid.']);
        return;
    }

    // ── 2. Ambil item terpilih dari DB (bukan dari JS) ─────────
    $sqlItems = "
        SELECT
            dk.id_detail_keranjang,
            dk.id_produk,
            dk.jumlah_barang,
            dk.harga_produk,
            dk.subtotal_harga,
            p.stok,
            p.nama_produk
        FROM dbo.detail_keranjang dk
        JOIN dbo.keranjang k  ON dk.id_keranjang = k.id_keranjang
        JOIN dbo.produk    p  ON dk.id_produk    = p.id_produk
        WHERE k.id_pengguna = ?
          AND dk.is_selected = 1
    ";

    $stmtItems = sqlsrv_query($conn, $sqlItems, [$id_pengguna]);
    if (!$stmtItems) {
        echo json_encode(['success' => false, 'message' => 'Gagal membaca keranjang.']);
        return;
    }

    $items = [];
    while ($row = sqlsrv_fetch_array($stmtItems, SQLSRV_FETCH_ASSOC)) {
        $items[] = $row;
    }

    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Tidak ada item yang dipilih di keranjang.']);
        return;
    }

    // ── 3. Cek stok ────────────────────────────────────────────
    foreach ($items as $item) {
        if ((int)$item['stok'] < (int)$item['jumlah_barang']) {
            echo json_encode([
                'success' => false,
                'message' => "Stok tidak mencukupi untuk produk: {$item['nama_produk']} (tersisa {$item['stok']})"
            ]);
            return;
        }
    }

    // ── 4. Hitung total ─────────────────────────────────────────
    $total_barang = 0;
    $total_produk = 0.0;   // subtotal sebelum biaya admin
    foreach ($items as $item) {
        $total_barang += (int)   $item['jumlah_barang'];
        $total_produk += (float) $item['subtotal_harga'];
    }

    // Ambil biaya_admin dari metode_pembayaran
    $sqlMetode = "SELECT biaya_admin, nama_metode, provider, no_rekening, atas_nama
                  FROM dbo.metode_pembayaran
                  WHERE id_metode = ? AND aktif = 1 AND is_deleted = 0";
    $stmtMetode = sqlsrv_query($conn, $sqlMetode, [$id_metode]);
    if (!$stmtMetode) {
        echo json_encode(['success' => false, 'message' => 'Metode pembayaran tidak ditemukan.']);
        return;
    }
    $metode = sqlsrv_fetch_array($stmtMetode, SQLSRV_FETCH_ASSOC);
    if (!$metode) {
        echo json_encode(['success' => false, 'message' => 'Metode pembayaran tidak aktif atau tidak ditemukan.']);
        return;
    }

    $biaya_admin = (float) $metode['biaya_admin'];
    $total_harga = $total_produk + $biaya_admin;
    $now         = date('Y-m-d H:i:s');

    // ── 5. BEGIN TRANSACTION ────────────────────────────────────
    sqlsrv_begin_transaction($conn);

    try {
        // INSERT dbo.penjualan
        // status_penjualan = 0 (Pending Payment)
        $sqlInsertPenjualan = "
            INSERT INTO dbo.penjualan
                (id_pengguna, id_metode, tanggal_penjualan, total_barang, total_harga,
                 alamat, status_penjualan, created_by, created_date)
            OUTPUT INSERTED.id_penjualan
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)
        ";
        $paramsP = [
            $id_pengguna,
            $id_metode,
            $now,
            $total_barang,
            $total_harga,
            $alamat,
            $id_pengguna,
            $now,
        ];
        $stmtP = sqlsrv_query($conn, $sqlInsertPenjualan, $paramsP);

        if (!$stmtP) {
            $errors = sqlsrv_errors();
            throw new Exception('Gagal membuat pesanan: ' . ($errors[0]['message'] ?? 'unknown error'));
        }

        $rowP = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC);
        if (!$rowP || !isset($rowP['id_penjualan'])) {
            throw new Exception('Gagal mendapatkan ID pesanan baru.');
        }
        $id_penjualan = (int) $rowP['id_penjualan'];

        // ── 6. INSERT dbo.detail_penjualan ──────────────────────
        // Trigger TRG_Penjualan_CegahMinus (INSTEAD OF INSERT) akan menangkap dan
        // melakukan cek stok ulang di level DB, serta forward insert yang sebenarnya.
        
        $sqlInsertDetail = "
            INSERT INTO dbo.detail_penjualan
                (id_penjualan, id_produk,
                 jumlah_barang, harga_produk, subtotal_harga)
            VALUES (?, ?, ?, ?, ?)
        ";

        foreach ($items as $item) {
            $paramsD = [
                $id_penjualan,
                (int)   $item['id_produk'],
                (int)   $item['jumlah_barang'],
                (float) $item['harga_produk'],
                (float) $item['subtotal_harga'],
            ];
            $stmtD = sqlsrv_query($conn, $sqlInsertDetail, $paramsD);

            if (!$stmtD) {
                $errors = sqlsrv_errors();
                // Trigger RAISERROR akan masuk ke sini jika stok tidak cukup
                $msg = $errors[0]['message'] ?? 'Gagal menyimpan detail pesanan.';
                throw new Exception($msg);
            }
        }

        // ── 7. Hapus item terpilih dari keranjang ───────────────
        // Ambil id_keranjang dulu
        $sqlKeranjang = "SELECT id_keranjang FROM dbo.keranjang WHERE id_pengguna = ?";
        $stmtK        = sqlsrv_query($conn, $sqlKeranjang, [$id_pengguna]);
        $rowK         = sqlsrv_fetch_array($stmtK, SQLSRV_FETCH_ASSOC);

        if ($rowK) {
            $id_keranjang = (int) $rowK['id_keranjang'];
            $sqlDelDetail = "
                DELETE FROM dbo.detail_keranjang
                WHERE id_keranjang = ?
                  AND is_selected  = 1
            ";
            sqlsrv_query($conn, $sqlDelDetail, [$id_keranjang]);
        }

        // ── COMMIT ──────────────────────────────────────────────
        sqlsrv_commit($conn);

        // ── 8. Bangun payment_detail HTML untuk Step 2 ──────────
        $payment_detail = buildPaymentDetail($metode);

        echo json_encode([
            'success'        => true,
            'id_penjualan'   => $id_penjualan,
            'payment_detail' => $payment_detail,
        ]);

    } catch (Exception $e) {
        sqlsrv_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * POST: Upload bukti pembayaran
 *
 * Flow:
 * 1. Validasi id_penjualan & kepemilikan
 * 2. Validasi file
 * 3. Simpan file ke disk
 * 4. UPDATE kolom bukti_pembayaran di dbo.penjualan
 */
function actionUploadBukti($conn, $id_pengguna) {
    // ── 1. Validasi id_penjualan ───────────────────────────────
    $id_penjualan = (int) ($_POST['id_penjualan'] ?? 0);
    if ($id_penjualan <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID pesanan tidak valid.']);
        return;
    }

    // Pastikan pesanan milik user ini dan statusnya masih 0 (Pending Payment)
    $sqlCek = "
        SELECT id_penjualan
        FROM dbo.penjualan
        WHERE id_penjualan   = ?
          AND id_pengguna    = ?
          AND status_penjualan = 0
    ";
    $stmtCek = sqlsrv_query($conn, $sqlCek, [$id_penjualan, $id_pengguna]);
    if (!$stmtCek || !sqlsrv_fetch_array($stmtCek, SQLSRV_FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan atau sudah diproses.']);
        return;
    }

    // ── 2. Validasi file upload ────────────────────────────────
    if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'File terlalu besar (melebihi batas server).',
            UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar.',
            UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian.',
            UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dikirim.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara tidak ditemukan.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
        ];
        $errCode = $_FILES['bukti']['error'] ?? UPLOAD_ERR_NO_FILE;
        $errMsg  = $uploadErrors[$errCode] ?? 'Gagal upload file.';
        echo json_encode(['success' => false, 'message' => $errMsg]);
        return;
    }

    $file     = $_FILES['bukti'];
    $maxSize  = 5 * 1024 * 1024; // 5 MB

    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 5MB.']);
        return;
    }

    // Validasi tipe file via finfo (lebih aman dari MIME yang dikirim browser)
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    if (!in_array($mimeType, $allowedMimes)) {
        echo json_encode(['success' => false, 'message' => 'Tipe file tidak valid. Gunakan JPG, PNG, WEBP, atau PDF.']);
        return;
    }

    // Tentukan ekstensi
    $extMap = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'application/pdf' => 'pdf',
    ];
    $ext = $extMap[$mimeType];

    // ── 3. Simpan file ─────────────────────────────────────────
    // Folder: /CardHaven/bukti_pembayaran/
    $uploadDir = __DIR__ . '/../../bukti_pembayaran/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = 'BUKTI_' . $id_penjualan . '_' . time() . '.' . $ext;
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file ke server.']);
        return;
    }

    // Path yang disimpan di DB (relatif dari root CardHaven)
    $dbPath = 'bukti_pembayaran/' . $fileName;

    // ── 4. UPDATE dbo.penjualan ────────────────────────────────
    $now    = date('Y-m-d H:i:s');
    $sqlUpd = "
        UPDATE dbo.penjualan
        SET bukti_pembayaran = ?,
            modified_by      = ?,
            modified_date    = ?
        WHERE id_penjualan = ?
          AND id_pengguna  = ?
    ";
    $stmtUpd = sqlsrv_query($conn, $sqlUpd, [
        $dbPath,
        $id_pengguna,
        $now,
        $id_penjualan,
        $id_pengguna,
    ]);

    if ($stmtUpd) {
        echo json_encode(['success' => true]);
    } else {
        // Upload berhasil tapi DB gagal - hapus file agar tidak orphan
        @unlink($filePath);
        $errors = sqlsrv_errors();
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database: ' . ($errors[0]['message'] ?? 'unknown')]);
    }
}


// ════════════════════════════════════════════════════════════════
// HELPER
// ════════════════════════════════════════════════════════════════

/**
 * Bangun HTML instruksi pembayaran berdasarkan metode yang dipilih
 * Hasil ini dikirim ke JS untuk ditampilkan di Step 2
 */
function buildPaymentDetail(array $metode): string {
    $nama     = htmlspecialchars($metode['nama_metode'] ?? '', ENT_QUOTES);
    $provider = htmlspecialchars($metode['provider']    ?? '', ENT_QUOTES);
    $norek    = htmlspecialchars($metode['no_rekening'] ?? '', ENT_QUOTES);
    $atas     = htmlspecialchars($metode['atas_nama']   ?? '', ENT_QUOTES);

    $parts = [];
    if ($nama)     $parts[] = "<strong>{$nama}</strong>";
    if ($provider) $parts[] = $provider;
    if ($norek)    $parts[] = "No. Rekening/Akun: <strong>{$norek}</strong>";
    if ($atas)     $parts[] = "a.n. <strong>{$atas}</strong>";

    return implode('<br>', $parts);
}