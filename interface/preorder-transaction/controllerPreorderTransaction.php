<?php
/**
 * preorder-transaction/controller.php
 * AJAX controller khusus untuk Pre-Order Event.
 */

header('Content-Type: application/json');
require __DIR__ . '/../../connection.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    /* ──────────────────────────────────────────────────────
       GET PREORDER EVENT + PRODUCT (Hanya 1 Produk)
    ────────────────────────────────────────────────────── */
    case 'get_event':
        $idEvent = (int)($_GET['id_event'] ?? 0);
        if (!$idEvent) {
            echo json_encode(['error' => 'Invalid event ID.']);
            exit;
        }

        // Fetch event
        $sqlEvent = "
            SELECT id_event, nama_event, maks_pembelian, persen_diskon,
                   tipe_event, tanggal_mulai, tanggal_berakhir, status_event,
                   tanggal_sampai, foto_banner, is_hide
            FROM   [CardHaven].[dbo].[event]
            WHERE  id_event              = ?
              AND  ISNULL(is_deleted, 0) = 0
              AND  ISNULL(is_hide, 0)    = 0
        ";
        $stmtEvent = sqlsrv_query($conn, $sqlEvent, [$idEvent]);
        if (!$stmtEvent) {
            echo json_encode(['error' => 'Failed to fetch event.']); exit;
        }
        $event = sqlsrv_fetch_array($stmtEvent, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmtEvent);

        if (!$event) {
            echo json_encode(['error' => 'Event not found or not available.']); exit;
        }

        foreach (['tanggal_mulai','tanggal_berakhir','tanggal_sampai'] as $col) {
            if ($event[$col] instanceof DateTime) {
                $event[$col] = $event[$col]->format('Y-m-d');
            }
        }

        // Fetch produk (Limit ke 1 produk saja karena pre-order selalu 1)
        $sqlProducts = "
            SELECT TOP 1
                pe.id_produk_event, pe.id_produk, pe.id_event,
                pe.harga_event, pe.stok_event,
                p.nama_produk, p.harga_jual, p.harga_beli, p.stok,
                p.tipe_produk, p.kondisi, p.deskripsi, p.foto,
                p.id_game, g.nama_game
            FROM   [CardHaven].[dbo].[produk_event] pe
            INNER JOIN [CardHaven].[dbo].[produk]   p ON p.id_produk = pe.id_produk
            LEFT  JOIN [CardHaven].[dbo].[game]     g ON g.id_game   = p.id_game
            WHERE  pe.id_event                      = ?
              AND  ISNULL(pe.is_deleted, 0)         = 0
              AND  ISNULL(pe.is_product_deleted, 0) = 0
              AND  ISNULL(p.is_deleted, 0)          = 0
            ORDER BY pe.id_produk_event
        ";
        $stmtProd = sqlsrv_query($conn, $sqlProducts, [$idEvent]);
        $product = sqlsrv_fetch_array($stmtProd, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmtProd);

        echo json_encode([
            'event'   => $event,
            'product' => $product ?: null
        ]);
        break;

    /* ──────────────────────────────────────────────────────
       GET PAYMENT METHODS
    ────────────────────────────────────────────────────── */
    case 'get_payment_methods':
        $sql  = "
            SELECT id_metode, nama_metode, provider, no_rekening, atas_nama, biaya_admin
            FROM   [CardHaven].[dbo].[metode_pembayaran]
            WHERE  aktif      = 1
              AND  is_deleted = 0
            ORDER BY nama_metode
        ";
        $stmt = sqlsrv_query($conn, $sql);
        $methods = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $methods[] = $row;
        }
        sqlsrv_free_stmt($stmt);
        echo json_encode(['methods' => $methods]);
        break;

    /* ──────────────────────────────────────────────────────
       GET PURCHASE COUNT
    ────────────────────────────────────────────────────── */
    case 'get_purchase_count':
        $idPengguna = (int)($_GET['id_pengguna'] ?? 0);
        $idEvent    = (int)($_GET['id_event']    ?? 0);

        if (!$idPengguna || !$idEvent) {
            echo json_encode(['counts' => []]); exit;
        }

        $sql = "
            SELECT dp.id_produk, SUM(dp.jumlah_barang) AS total_beli
            FROM   [CardHaven].[dbo].[penjualan]        pj
            INNER JOIN [CardHaven].[dbo].[detail_penjualan] dp
                ON dp.id_penjualan = pj.id_penjualan
            INNER JOIN [CardHaven].[dbo].[produk_event] pe
                ON pe.id_produk = dp.id_produk AND pe.id_event = ?
            WHERE  pj.id_pengguna = ?
              AND  pj.status_penjualan NOT IN (7, 8) -- Exclude Returned & Cancelled
            GROUP BY dp.id_produk
        ";
        $stmt = sqlsrv_query($conn, $sql, [$idEvent, $idPengguna]);
        $counts = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $counts[$row['id_produk']] = (int)$row['total_beli'];
            }
            sqlsrv_free_stmt($stmt);
        }
        echo json_encode(['counts' => $counts]);
        break;

    /* ──────────────────────────────────────────────────────
       SUBMIT ORDER (POST)
    ────────────────────────────────────────────────────── */
    case 'submit_order':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']); exit;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        
        $idPengguna = (int)($body['id_pengguna'] ?? 0);
        $idEvent    = (int)($body['id_event']    ?? 0);
        $idMetode   = (int)($body['id_metode']   ?? 0);
        $alamat     = trim($body['alamat']        ?? '');
        $items      = $body['items']              ?? [];

        if (!$idPengguna || !$idEvent || !$idMetode || !$alamat || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Incomplete data submitted.']); exit;
        }

        // Fetch event data (maks pembelian)
        $sqlEv = "SELECT maks_pembelian FROM [CardHaven].[dbo].[event] WHERE id_event = ? AND ISNULL(is_deleted, 0) = 0";
        $stmtEv = sqlsrv_query($conn, $sqlEv, [$idEvent]);
        $evRow = sqlsrv_fetch_array($stmtEv, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmtEv);
        $maksPembelian = (int)$evRow['maks_pembelian'];

        $idProduk = (int)$items[0]['id_produk'];
        $itemQty  = (int)$items[0]['jumlah'];

        // Limit Check
        $sqlCount = "
            SELECT ISNULL(SUM(dp.jumlah_barang), 0) AS total_beli
            FROM   [CardHaven].[dbo].[penjualan] pj
            INNER JOIN [CardHaven].[dbo].[detail_penjualan] dp ON dp.id_penjualan = pj.id_penjualan
            WHERE pj.id_pengguna = ? AND dp.id_produk = ? AND pj.status_penjualan NOT IN (7, 8)
        ";
        $stmtCount = sqlsrv_query($conn, $sqlCount, [$idPengguna, $idProduk]);
        $countRow = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmtCount);
        $alreadyBought = (int)($countRow['total_beli'] ?? 0);

        if ($alreadyBought + $itemQty > $maksPembelian) {
            echo json_encode(['success' => false, 'message' => "Limit reached. Maximum $maksPembelian."]); exit;
        }

        // Metode Pembayaran & Biaya Admin
        $sqlMet = "SELECT biaya_admin FROM [CardHaven].[dbo].[metode_pembayaran] WHERE id_metode = ? AND aktif = 1 AND is_deleted = 0";
        $stmtMet = sqlsrv_query($conn, $sqlMet, [$idMetode]);
        $metRow = sqlsrv_fetch_array($stmtMet, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmtMet);
        $biayaAdmin = (float)$metRow['biaya_admin'];

        $totalHarga = $biayaAdmin + ($itemQty * (float)$items[0]['harga_produk']);

        sqlsrv_begin_transaction($conn);

        // INSERT PENJUALAN (0 = Pending Payment) -> Nanti diupdate jadi 2 (Waiting Stock) setelah dibayar
        $sqlInsertPj = "
            INSERT INTO [CardHaven].[dbo].[penjualan]
                (id_pengguna, id_metode, tanggal_penjualan, total_barang, total_harga, alamat, status_penjualan, created_by, created_date)
            OUTPUT INSERTED.id_penjualan
            VALUES (?, ?, GETDATE(), ?, ?, ?, 0, ?, GETDATE()) 
        ";
        $stmtPj = sqlsrv_query($conn, $sqlInsertPj, [$idPengguna, $idMetode, $itemQty, $totalHarga, $alamat, $idPengguna]);
        if (!$stmtPj) { sqlsrv_rollback($conn); echo json_encode(['success' => false, 'message' => 'Failed to create order.']); exit; }
        
        $pjRow = sqlsrv_fetch_array($stmtPj, SQLSRV_FETCH_ASSOC);
        $idPenjualan = (int)($pjRow['id_penjualan'] ?? 0);
        sqlsrv_free_stmt($stmtPj);

        // INSERT DETAIL PENJUALAN
        $subtotal = $itemQty * (float)$items[0]['harga_produk'];
        $sqlDet = "INSERT INTO [CardHaven].[dbo].[detail_penjualan] (id_penjualan, id_produk, jumlah_barang, harga_produk, subtotal_harga) VALUES (?, ?, ?, ?, ?)";
        $stmtDet = sqlsrv_query($conn, $sqlDet, [$idPenjualan, $idProduk, $itemQty, (float)$items[0]['harga_produk'], $subtotal]);
        if (!$stmtDet) { sqlsrv_rollback($conn); echo json_encode(['success' => false, 'message' => 'Failed to insert detail.']); exit; }
        sqlsrv_free_stmt($stmtDet);

        // UPDATE STOCK
        $sqlDeduct = "UPDATE [CardHaven].[dbo].[produk_event] SET stok_event = stok_event - ? WHERE id_produk = ? AND id_event = ?";
        $stmtDeduct = sqlsrv_query($conn, $sqlDeduct, [$itemQty, $idProduk, $idEvent]);
        if (!$stmtDeduct) { sqlsrv_rollback($conn); echo json_encode(['success' => false, 'message' => 'Failed to update stock.']); exit; }
        sqlsrv_free_stmt($stmtDeduct);

        sqlsrv_commit($conn);
        echo json_encode(['success' => true, 'id_penjualan' => $idPenjualan, 'message' => 'Pre-Order placed successfully.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action.']);
        break;
}