<?php
/**
 * preorder-transaction/controller.php
 * AJAX controller khusus untuk Pre-Order Event.
 */

header('Content-Type: application/json');
require __DIR__ . '/../../connection.php';
require_once __DIR__ . '/../../auth/session.php';

$action = $_GET['action'] ?? '';

// Catatan: 'get_event' sengaja tetap bisa diakses tanpa login (halaman event
// boleh dilihat pengunjung). Aksi yang menyangkut data pribadi / pesanan
// mewajibkan login dan memakai id_pengguna dari session.

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
                   tanggal_sampai, foto_banner, is_hide,maks_pembelian
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
       GET PURCHASE COUNT
    ────────────────────────────────────────────────────── */
    case 'get_purchase_count':
        // Jumlah pembelian milik user sendiri — id dari session.
        $idPengguna = auth_api_require_login()['id'];
        $idEvent    = (int)($_GET['id_event'] ?? 0);

        if (!$idEvent) {
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

        // Pemesan = user yang sedang login (dari session), bukan id kiriman browser.
        $idPengguna    = auth_api_require_login()['id'];
        $idEvent       = (int)($body['id_event']    ?? 0);
        $idMetode      = (int)($body['id_metode']   ?? 0);
        $alamat        = trim($body['alamat']        ?? '');
        
        // TANGKAP TANGGAL ESTIMASI DENGAN AMAN
        $tanggalSampai = isset($body['tanggal_sampai']) && trim($body['tanggal_sampai']) !== '' ? trim($body['tanggal_sampai']) : null;
        
        $items         = $body['items'] ?? [];

        // ── Server-side validation ──────────────────────
        if (!$idPengguna) { echo json_encode(['success' => false, 'message' => 'User not authenticated.']); exit; }
        if (!$idEvent)    { echo json_encode(['success' => false, 'message' => 'Invalid event.']); exit; }
        if (!$idMetode)   { echo json_encode(['success' => false, 'message' => 'Payment method is required.']); exit; }
        if (!$alamat)     { echo json_encode(['success' => false, 'message' => 'Delivery address is required.']); exit; }
        if (empty($items)){ echo json_encode(['success' => false, 'message' => 'No items selected.']); exit; }

        // Fetch event to get maks_pembelian
        $sqlEv = "SELECT id_event, maks_pembelian FROM [CardHaven].[dbo].[event] WHERE id_event = ? AND ISNULL(is_deleted, 0) = 0";
        $stmtEv = sqlsrv_query($conn, $sqlEv, [$idEvent]);
        if (!$stmtEv || !($evRow = sqlsrv_fetch_array($stmtEv, SQLSRV_FETCH_ASSOC))) {
            echo json_encode(['success' => false, 'message' => 'Event not found or inactive.']); exit;
        }
        sqlsrv_free_stmt($stmtEv);
        $maksPembelian = (int)$evRow['maks_pembelian'];

        // Per-product purchase limit check
        $productIds = array_map(function ($i) { return (int)$i['id_produk']; }, $items);
        foreach ($productIds as $idProduk) {
            $sqlCount = "
                SELECT ISNULL(SUM(dp.jumlah_barang), 0) AS total_beli
                FROM   [CardHaven].[dbo].[penjualan]        pj
                INNER JOIN [CardHaven].[dbo].[detail_penjualan] dp ON dp.id_penjualan = pj.id_penjualan
                INNER JOIN [CardHaven].[dbo].[produk_event] pe ON pe.id_produk = dp.id_produk AND pe.id_event = ?
                INNER JOIN [CardHaven].[dbo].[event] ev ON ev.id_event = pe.id_event
                WHERE  pj.id_pengguna = ? 
                  AND  dp.id_produk = ? 
                  AND  pj.status_penjualan NOT IN (7, 8) 
                  AND  dp.harga_produk = pe.harga_event
                  AND  pj.tanggal_penjualan >= ev.tanggal_mulai
                  AND  pj.tanggal_penjualan <= DATEADD(day, 1, ev.tanggal_berakhir)
            ";
            $stmtCount = sqlsrv_query($conn, $sqlCount, [$idEvent, $idPengguna, $idProduk]);
            $countRow  = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmtCount);
            
            $alreadyBought = (int)($countRow['total_beli'] ?? 0);
            $itemQty = 0;
            foreach ($items as $it) {
                if ((int)$it['id_produk'] === $idProduk) { $itemQty = (int)$it['jumlah']; break; }
            }

            // PERBAIKAN: Hanya batasi jika maksPembelian lebih dari 0
            if ($maksPembelian > 0 && ($alreadyBought + $itemQty > $maksPembelian)) {
                echo json_encode(['success' => false, 'message' => 'Purchase limit exceeded for product ' . $idProduk]); exit;
            }

            // Stock check
            $sqlStock = "SELECT stok_event FROM [CardHaven].[dbo].[produk_event] WHERE id_produk = ? AND id_event = ? AND ISNULL(is_deleted, 0) = 0";
            $stmtStock = sqlsrv_query($conn, $sqlStock, [$idProduk, $idEvent]);
            $stockRow = sqlsrv_fetch_array($stmtStock, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmtStock);
            if (!$stockRow || (int)$stockRow['stok_event'] < $itemQty) {
                echo json_encode(['success' => false, 'message' => 'Insufficient stock for this product']); exit;
            }
        }

        // ── Payment method validation ───────────────────
        $sqlMet = "SELECT id_metode, biaya_admin FROM [CardHaven].[dbo].[metode_pembayaran] WHERE id_metode = ? AND aktif = 1 AND is_deleted = 0";
        $stmtMet = sqlsrv_query($conn, $sqlMet, [$idMetode]);
        $metRow = sqlsrv_fetch_array($stmtMet, SQLSRV_FETCH_ASSOC);
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

        // PERBAIKAN PENTING: Tambahkan SET NOCOUNT ON; agar OUTPUT INSERTED berjalan mulus
        $sqlInsertPj = "
            SET NOCOUNT ON;
            INSERT INTO [CardHaven].[dbo].[penjualan]
                (id_pengguna, id_metode, tanggal_penjualan,
                 total_barang, total_harga, alamat, status_penjualan,
                 created_by, created_date, tanggal_sampai)
            OUTPUT INSERTED.id_penjualan
            VALUES (?, ?, GETDATE(), ?, ?, ?, 0, ?, GETDATE(), ?) 
        ";

        $stmtPj = sqlsrv_query($conn, $sqlInsertPj, [
            $idPengguna,
            $idMetode,
            $totalBarang,
            $totalHarga,
            $alamat,
            $idPengguna,   
            $tanggalSampai // Masuk ke tanggal_pengiriman
        ]);

        // JIKA GAGAL, TAMPILKAN ERROR ASLI DARI SQL SERVER KE POPUP!
        if (!$stmtPj) {
            sqlsrv_rollback($conn);
            $dbErrors = sqlsrv_errors();
            $errorMsg = $dbErrors[0]['message'] ?? 'Unknown database error.';
            echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $errorMsg]); 
            exit;
        }

        $pjRow = sqlsrv_fetch_array($stmtPj, SQLSRV_FETCH_ASSOC);
        $idPenjualan = (int)($pjRow['id_penjualan'] ?? 0);
        sqlsrv_free_stmt($stmtPj);

        if (!$idPenjualan) {
            sqlsrv_rollback($conn);
            echo json_encode(['success' => false, 'message' => 'Gagal mendapatkan ID Penjualan baru dari database.']); 
            exit;
        }

        // Insert detail_penjualan WITH id_produk_event so the DB triggers can
        // apply the correct pre-order stock logic (reserve event quota at order,
        // deduct physical stock only at shipment — after goods arrive via restok).
        // Stock is owned entirely by the triggers now — no manual deduction here.
        foreach ($items as $it) {
            $idProduk    = (int)$it['id_produk'];
            $jumlah      = (int)$it['jumlah'];
            $hargaProduk = (float)$it['harga_produk'];
            $subtotal    = $jumlah * $hargaProduk;

            // Resolve the event-product link (authoritative, server-side).
            $stmtPe = sqlsrv_query($conn,
                "SELECT id_produk_event FROM [CardHaven].[dbo].[produk_event]
                 WHERE id_produk = ? AND id_event = ? AND ISNULL(is_deleted, 0) = 0",
                [$idProduk, $idEvent]);
            $peRow = $stmtPe ? sqlsrv_fetch_array($stmtPe, SQLSRV_FETCH_ASSOC) : null;
            if ($stmtPe) sqlsrv_free_stmt($stmtPe);
            if (!$peRow) {
                sqlsrv_rollback($conn);
                echo json_encode(['success' => false, 'message' => 'Event product not found for this order.']); exit;
            }
            $idProdukEvent = (int)$peRow['id_produk_event'];

            $sqlDet = "INSERT INTO [CardHaven].[dbo].[detail_penjualan] (id_penjualan, id_produk, id_produk_event, jumlah_barang, harga_produk, subtotal_harga) VALUES (?, ?, ?, ?, ?, ?)";
            $stmtDet = sqlsrv_query($conn, $sqlDet, [$idPenjualan, $idProduk, $idProdukEvent, $jumlah, $hargaProduk, $subtotal]);
            if (!$stmtDet) {
                sqlsrv_rollback($conn);
                $dbErrors = sqlsrv_errors();
                echo json_encode(['success' => false, 'message' => $dbErrors[0]['message'] ?? 'Failed to insert order detail.']); exit;
            }
            sqlsrv_free_stmt($stmtDet);
        }

        sqlsrv_commit($conn);

        echo json_encode([
            'success'      => true,
            'id_penjualan' => $idPenjualan,
            'message'      => 'Order placed successfully.'
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action.']);
        break;
}