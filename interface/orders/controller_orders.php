<?php
/**
 * controller_orders.php — CardHaven Orders Controller (sisi Customer)
 *
 * GET actions:
 *   get_orders         → list semua order customer + item preview
 *   get_order_detail   → 1 order + semua detail item + resi
 *
 * POST actions:
 *   confirm_received   → customer konfirmasi paket diterima → status 5 (Delivered) → 6 (Completed)
 *   cancel_order       → customer batalkan order jika status 0 atau 1 → status 8 (Cancelled), stok dikembalikan
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_pengguna'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

require_once '/cardhaven/config/db.php';

$id_pengguna = (int) $_SESSION['id_pengguna'];
$action      = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'get_orders':        getOrders($pdo, $id_pengguna);       break;
        case 'get_order_detail':  getOrderDetail($pdo, $id_pengguna);  break;
        default: echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'confirm_received': confirmReceived($pdo, $id_pengguna); break;
        case 'cancel_order':     cancelOrder($pdo, $id_pengguna);     break;
        default: echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
    exit;
}

// ============================================================
// FUNCTIONS
// ============================================================

/**
 * Ambil semua order customer + preview item (maks 5 per order)
 */
function getOrders(PDO $pdo, int $uid): void {
    // Ambil header penjualan
    $stmt = $pdo->prepare("
        SELECT
            pj.id_penjualan,
            pj.tanggal_penjualan,
            pj.total_barang,
            pj.total_harga,
            pj.status_penjualan,
            pj.bukti_pembayaran,
            pj.no_resi
        FROM penjualan pj
        WHERE pj.id_pengguna = :uid
        ORDER BY pj.tanggal_penjualan DESC
    ");
    $stmt->execute([':uid' => $uid]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($orders)) { echo json_encode([]); return; }

    // Ambil preview item per order (maks 5)
    $ids     = array_column($orders, 'id_penjualan');
    $inClause= implode(',', array_fill(0, count($ids), '?'));

    $stmtItems = $pdo->prepare("
        SELECT
            dp.id_penjualan,
            p.nama_produk,
            p.foto
        FROM detail_penjualan dp
        LEFT JOIN produk p ON p.id_produk = dp.id_produk
        WHERE dp.id_penjualan IN ($inClause)
        ORDER BY dp.id_detail_penjualan
    ");
    $stmtItems->execute($ids);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Kelompokkan per order
    $itemMap = [];
    foreach ($items as $item) {
        $itemMap[$item['id_penjualan']][] = $item;
    }

    foreach ($orders as &$o) {
        $o['items'] = $itemMap[$o['id_penjualan']] ?? [];
    }

    echo json_encode($orders);
}

/**
 * Detail satu order: header + semua item detail + metode pembayaran
 */
function getOrderDetail(PDO $pdo, int $uid): void {
    $id_penjualan = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id_penjualan) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); return; }

    // Header
    $stmt = $pdo->prepare("
        SELECT
            pj.*,
            mp.nama_metode,
            mp.provider,
            mp.no_rekening,
            mp.atas_nama,
            mp.biaya_admin
        FROM penjualan pj
        LEFT JOIN metode_pembayaran mp ON mp.id_metode = pj.id_metode
        WHERE pj.id_penjualan = :id
          AND pj.id_pengguna  = :uid
    ");
    $stmt->execute([':id' => $id_penjualan, ':uid' => $uid]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) { echo json_encode(['success' => false, 'message' => 'Order not found.']); return; }

    // Items
    $stmtD = $pdo->prepare("
        SELECT
            dp.*,
            p.nama_produk,
            p.foto,
            p.kondisi
        FROM detail_penjualan dp
        LEFT JOIN produk p ON p.id_produk = dp.id_produk
        WHERE dp.id_penjualan = :id
        ORDER BY dp.id_detail_penjualan
    ");
    $stmtD->execute([':id' => $id_penjualan]);
    $order['items'] = $stmtD->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'order' => $order]);
}

/**
 * Customer konfirmasi menerima paket
 * Flowchart: status 5 (Delivered) → 6 (Completed)
 * Catatan: berdasarkan flowchart, customer menekan tombol "terima paket"
 * setelah barang datang. Admin set status ke 5 (Delivered) terlebih dulu.
 */
function confirmReceived(PDO $pdo, int $uid): void {
    $id_penjualan = filter_input(INPUT_POST, 'id_penjualan', FILTER_VALIDATE_INT);
    if (!$id_penjualan) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); return; }

    // Pastikan status = 5 (Delivered) dan milik customer ini
    $stmtCheck = $pdo->prepare("
        SELECT id_penjualan FROM penjualan
        WHERE id_penjualan    = :id
          AND id_pengguna     = :uid
          AND status_penjualan = 5
    ");
    $stmtCheck->execute([':id' => $id_penjualan, ':uid' => $uid]);
    if (!$stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Order not eligible for confirmation.']);
        return;
    }

    $now = date('Y-m-d H:i:s');

    try {
        $pdo->beginTransaction();

        // Update status → 6 (Completed)
        $pdo->prepare("
            UPDATE penjualan
            SET status_penjualan = 6,
                modified_by      = :uid,
                modified_date    = :now
            WHERE id_penjualan = :id
        ")->execute([':uid' => $uid, ':now' => $now, ':id' => $id_penjualan]);

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('[orders] confirmReceived error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error.']);
    }
}

/**
 * Customer membatalkan order
 * Hanya boleh jika status = 0 (Pending Payment) atau 1 (Paid, belum diproses)
 * → status 8 (Cancelled)
 * → stok produk dikembalikan
 */
function cancelOrder(PDO $pdo, int $uid): void {
    $id_penjualan = filter_input(INPUT_POST, 'id_penjualan', FILTER_VALIDATE_INT);
    if (!$id_penjualan) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); return; }

    // Cek kepemilikan & status
    $stmtCheck = $pdo->prepare("
        SELECT id_penjualan, status_penjualan FROM penjualan
        WHERE id_penjualan = :id
          AND id_pengguna  = :uid
          AND status_penjualan IN (0, 1)
    ");
    $stmtCheck->execute([':id' => $id_penjualan, ':uid' => $uid]);
    $order = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled.']);
        return;
    }

    $now = date('Y-m-d H:i:s');

    try {
        $pdo->beginTransaction();

        // Update status → 8 (Cancelled)
        $pdo->prepare("
            UPDATE penjualan
            SET status_penjualan = 8,
                modified_by      = :uid,
                modified_date    = :now
            WHERE id_penjualan = :id
        ")->execute([':uid' => $uid, ':now' => $now, ':id' => $id_penjualan]);

        // Kembalikan stok
        $stmtItems = $pdo->prepare("
            SELECT id_produk, jumlah_barang FROM detail_penjualan WHERE id_penjualan = :id
        ");
        $stmtItems->execute([':id' => $id_penjualan]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $stmtRestoreStok = $pdo->prepare("
            UPDATE produk SET stok = stok + :jml WHERE id_produk = :id
        ");
        foreach ($items as $item) {
            $stmtRestoreStok->execute([':jml' => $item['jumlah_barang'], ':id' => $item['id_produk']]);
        }

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('[orders] cancelOrder error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error.']);
    }
}
