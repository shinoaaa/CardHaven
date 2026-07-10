<?php
/**
 * event-transaction/controller.php
 * AJAX controller — all business logic lives here.
 * Connected to SQL Server via the shared connection file.
 *
 * Endpoints (GET ?action=):
 *   get_event            → event info + product list
 *   get_payment_methods  → active payment methods
 *   get_purchase_count   → per-product purchase count for a user in an event
 *
 * Endpoint (POST ?action=):
 *   submit_order         → validates and inserts penjualan + detail_penjualan
 */

header('Content-Type: application/json');

require __DIR__ . '/../../connection.php';
// $conn is expected to be a valid sqlsrv connection resource.

$action = $_GET['action'] ?? '';

switch ($action) {

    /* ──────────────────────────────────────────────────────
       GET EVENT + PRODUCTS
    ────────────────────────────────────────────────────── */
    case 'get_event':
        $idEvent = (int)($_GET['id_event'] ?? 0);
        if (!$idEvent) {
            echo json_encode(['error' => 'Invalid event ID.']);
            exit;
        }

        // Fetch event
        // status_event is stored as integer (1 = active, 2 = ...), not a string.
        // is_deleted / is_hide may be NULL in older rows — treat NULL as 0 via ISNULL.
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
            echo json_encode(['error' => 'Failed to fetch event.']);
            exit;
        }
        $event = sqlsrv_fetch_array($stmtEvent, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmtEvent);

        if (!$event) {
            echo json_encode(['error' => 'Event not found or not available.']);
            exit;
        }

        // Serialize DateTime objects
        foreach (['tanggal_mulai','tanggal_berakhir','tanggal_sampai'] as $col) {
            if ($event[$col] instanceof DateTime) {
                $event[$col] = $event[$col]->format('Y-m-d');
            }
        }

        // Fetch products for this event, merged with produk + game name.
        // is_deleted / is_product_deleted in produk_event can be NULL → use ISNULL.
        // p.status and p.is_display: only filter if your produk table actually uses them;
        // if those columns can also be NULL, guard with ISNULL too.
        $sqlProducts = "
            SELECT
                pe.id_produk_event,
                pe.id_produk,
                pe.id_event,
                pe.harga_event,
                pe.stok_event,
                p.nama_produk,
                p.harga_jual,
                p.harga_beli,
                p.stok,
                p.tipe_produk,
                p.kondisi,
                p.deskripsi,
                p.foto,
                p.id_game,
                g.nama_game
            FROM   [CardHaven].[dbo].[produk_event] pe
            INNER JOIN [CardHaven].[dbo].[produk]   p ON p.id_produk = pe.id_produk
            LEFT  JOIN [CardHaven].[dbo].[game]     g ON g.id_game   = p.id_game
            WHERE  pe.id_event                    = ?
              AND  ISNULL(pe.is_deleted, 0)        = 0
              AND  ISNULL(pe.is_product_deleted, 0) = 0
              AND  ISNULL(p.is_deleted, 0)          = 0
            ORDER BY pe.id_produk_event
        ";
        $stmtProd = sqlsrv_query($conn, $sqlProducts, [$idEvent]);
        if (!$stmtProd) {
            echo json_encode(['error' => 'Failed to fetch event products.']);
            exit;
        }
        $products = [];
        while ($row = sqlsrv_fetch_array($stmtProd, SQLSRV_FETCH_ASSOC)) {
            $products[] = $row;
        }
        sqlsrv_free_stmt($stmtProd);

        echo json_encode([
            'event'    => $event,
            'products' => $products
        ]);
        break;


    /* ──────────────────────────────────────────────────────
       GET PURCHASE COUNT (per-product, per-user, per-event)
    ────────────────────────────────────────────────────── */
    case 'get_purchase_count':
        $idPengguna = (int)($_GET['id_pengguna'] ?? 0);
        $idEvent    = (int)($_GET['id_event']    ?? 0);

        if (!$idPengguna || !$idEvent) {
            echo json_encode(['counts' => []]);
            exit;
        }

        // PERBAIKAN: Gunakan angka (7, 8) bukan string ('cancelled')
        $sql = "
            SELECT dp.id_produk, SUM(dp.jumlah_barang) AS total_beli
            FROM   [CardHaven].[dbo].[penjualan]        pj
            INNER JOIN [CardHaven].[dbo].[detail_penjualan] dp
                ON dp.id_penjualan = pj.id_penjualan
            INNER JOIN [CardHaven].[dbo].[produk_event] pe
                ON pe.id_produk = dp.id_produk AND pe.id_event = ?
            WHERE  pj.id_pengguna = ?
              AND  pj.status_penjualan NOT IN (7, 8)
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
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            exit;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) {
            echo json_encode(['success' => false, 'message' => 'Invalid request body.']);
            exit;
        }

        $idPengguna = (int)($body['id_pengguna'] ?? 0);
        $idEvent    = (int)($body['id_event']    ?? 0);
        $idMetode   = (int)($body['id_metode']   ?? 0);
        $alamat     = trim($body['alamat']        ?? '');
        $items      = $body['items']              ?? [];

        // ── Server-side validation ──────────────────────
        if (!$idPengguna) {
            echo json_encode(['success' => false, 'message' => 'User not authenticated.']); exit;
        }
        if (!$idEvent) {
            echo json_encode(['success' => false, 'message' => 'Invalid event.']); exit;
        }
        if (!$idMetode) {
            echo json_encode(['success' => false, 'message' => 'Payment method is required.']); exit;
        }
        if (!$alamat) {
            echo json_encode(['success' => false, 'message' => 'Delivery address is required.']); exit;
        }
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'No items selected.']); exit;
        }

        // Fetch event to get maks_pembelian
        $sqlEv = "
            SELECT id_event, maks_pembelian
            FROM   [CardHaven].[dbo].[event]
            WHERE  id_event              = ?
              AND  ISNULL(is_deleted, 0) = 0
        ";
        $stmtEv = sqlsrv_query($conn, $sqlEv, [$idEvent]);
        if (!$stmtEv || !($evRow = sqlsrv_fetch_array($stmtEv, SQLSRV_FETCH_ASSOC))) {
            echo json_encode(['success' => false, 'message' => 'Event not found or inactive.']); exit;
        }
        sqlsrv_free_stmt($stmtEv);
        $maksPembelian = (int)$evRow['maks_pembelian'];

        // Per-product purchase limit check (server-side)
        $productIds = array_map(function ($i) { return (int)$i['id_produk']; }, $items);

        foreach ($productIds as $idProduk) {
            // How many has this user already bought of this product via this event?
            $sqlCount = "
                SELECT ISNULL(SUM(dp.jumlah_barang), 0) AS total_beli
                FROM   [CardHaven].[dbo].[penjualan]        pj
                INNER JOIN [CardHaven].[dbo].[detail_penjualan] dp
                    ON dp.id_penjualan = pj.id_penjualan
                INNER JOIN [CardHaven].[dbo].[produk_event] pe
                    ON pe.id_produk = dp.id_produk AND pe.id_event = ?
                WHERE  pj.id_pengguna = ?
                AND  dp.id_produk   = ?
                AND  pj.status_penjualan NOT IN (7, 8) 
            ";
            $stmtCount = sqlsrv_query($conn, $sqlCount, [$idEvent, $idPengguna, $idProduk]);
            if (!$stmtCount) {
                echo json_encode(['success' => false, 'message' => 'Validation query failed.']); exit;
            }
            $countRow    = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmtCount);
            $alreadyBought = (int)($countRow['total_beli'] ?? 0);

            $itemQty = 0;
            foreach ($items as $it) {
                if ((int)$it['id_produk'] === $idProduk) {
                    $itemQty = (int)$it['jumlah'];
                    break;
                }
            }

            if ($alreadyBought + $itemQty > $maksPembelian) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Purchase limit exceeded for product ' . $idProduk .
                                 '. You have already purchased ' . $alreadyBought .
                                 ' and the maximum allowed is ' . $maksPembelian . '.'
                ]);
                exit;
            }

            // Stock check for this event product
            $sqlStock = "
                SELECT stok_event FROM [CardHaven].[dbo].[produk_event]
                WHERE id_produk = ? AND id_event = ?
                  AND ISNULL(is_deleted, 0) = 0
                  AND ISNULL(is_product_deleted, 0) = 0
            ";
            $stmtStock = sqlsrv_query($conn, $sqlStock, [$idProduk, $idEvent]);
            if (!$stmtStock) {
                echo json_encode(['success' => false, 'message' => 'Stock validation failed.']); exit;
            }
            $stockRow = sqlsrv_fetch_array($stmtStock, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmtStock);
            if (!$stockRow || (int)$stockRow['stok_event'] < $itemQty) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Insufficient stock for this product'
                ]);
                exit;
            }
        }

        // ── Payment method validation ───────────────────
        $sqlMet = "SELECT id_metode, biaya_admin FROM [CardHaven].[dbo].[metode_pembayaran]
                   WHERE id_metode = ? AND aktif = 1 AND is_deleted = 0";
        $stmtMet = sqlsrv_query($conn, $sqlMet, [$idMetode]);
        if (!$stmtMet || !($metRow = sqlsrv_fetch_array($stmtMet, SQLSRV_FETCH_ASSOC))) {
            echo json_encode(['success' => false, 'message' => 'Invalid or inactive payment method.']); exit;
        }
        sqlsrv_free_stmt($stmtMet);
        $biayaAdmin = (float)$metRow['biaya_admin'];

        // ── Compute totals ──────────────────────────────
        $totalBarang = 0;
        $totalHarga  = $biayaAdmin;

        foreach ($items as $it) {
            $qty          = (int)$it['jumlah'];
            $harga        = (float)$it['harga_produk'];
            $totalBarang += $qty;
            $totalHarga  += $qty * $harga;
        }

        // ── Begin transaction ───────────────────────────
        sqlsrv_begin_transaction($conn);

        // Insert penjualan (Tidak mengirim id_penjualan karena IDENTITY, tapi ditangkap via OUTPUT)
        $sqlInsertPj = "
            INSERT INTO [CardHaven].[dbo].[penjualan]
                (id_pengguna, id_metode, tanggal_penjualan,
                 total_barang, total_harga, alamat, status_penjualan,
                 created_by, created_date)
            OUTPUT INSERTED.id_penjualan
            VALUES (?, ?, GETDATE(), ?, ?, ?, 0, ?, GETDATE()) 
        ";
        // Catatan: status_penjualan aku ubah jadi 0 (tanpa kutip) agar berupa integer.

        $stmtPj = sqlsrv_query($conn, $sqlInsertPj, [
            $idPengguna,
            $idMetode,
            $totalBarang,
            $totalHarga,
            $alamat,
            $idPengguna   // created_by
        ]);

        if (!$stmtPj) {
            sqlsrv_rollback($conn);
            echo json_encode(['success' => false, 'message' => 'Failed to create order record.']); 
            exit;
        }

        // Menangkap ID Identity yang baru saja dibuat
        $pjRow = sqlsrv_fetch_array($stmtPj, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmtPj);
        
        $idPenjualan = (int)($pjRow['id_penjualan'] ?? 0);

        if (!$idPenjualan) {
            sqlsrv_rollback($conn);
            echo json_encode(['success' => false, 'message' => 'Failed to retrieve new order ID.']); 
            exit;
        }

        // ... Lanjut ke proses INSERT detail_penjualan ...

        // Insert detail_penjualan + deduct stok_event
        foreach ($items as $it) {
            $idProduk    = (int)$it['id_produk'];
            $jumlah      = (int)$it['jumlah'];
            $hargaProduk = (float)$it['harga_produk'];
            $subtotal    = $jumlah * $hargaProduk;

            $sqlDet = "
                INSERT INTO [CardHaven].[dbo].[detail_penjualan]
                    (id_penjualan, id_produk, jumlah_barang, harga_produk, subtotal_harga)
                VALUES (?, ?, ?, ?, ?)
            ";
            $stmtDet = sqlsrv_query($conn, $sqlDet, [
                $idPenjualan, $idProduk, $jumlah, $hargaProduk, $subtotal
            ]);
            if (!$stmtDet) {
                sqlsrv_rollback($conn);
                echo json_encode(['success' => false, 'message' => 'Failed to insert order detail.']); exit;
            }
            sqlsrv_free_stmt($stmtDet);

            // Deduct event stock
            $sqlDeduct = "
                UPDATE [CardHaven].[dbo].[produk_event]
                SET stok_event = stok_event - ?
                WHERE id_produk = ? AND id_event = ?
                  AND ISNULL(is_deleted, 0) = 0
            ";
            $stmtDeduct = sqlsrv_query($conn, $sqlDeduct, [$jumlah, $idProduk, $idEvent]);
            if (!$stmtDeduct) {
                sqlsrv_rollback($conn);
                echo json_encode(['success' => false, 'message' => 'Failed to update stock.']); exit;
            }
            sqlsrv_free_stmt($stmtDeduct);
        }

        sqlsrv_commit($conn);

        echo json_encode([
            'success'      => true,
            'id_penjualan' => $idPenjualan,
            'message'      => 'Order placed successfully.'
        ]);
        break;


    /* ──────────────────────────────────────────────────────
       FALLBACK
    ────────────────────────────────────────────────────── */
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action.']);
        break;
}