<?php
ob_start();
require __DIR__ . '/../../../connection.php';
ob_end_clean();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

if (!$body || !isset($body['id_event'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$id_event = (int)$body['id_event'];

if ($id_event <= 0) {
    echo json_encode(['error' => 'Invalid event ID']);
    exit;
}

$modified_by = 1; // TODO: ganti dengan session user

// ==========================================
// 1. CEK STATUS EVENT TERLEBIH DAHULU
// ==========================================
$sql_check = "SELECT status_event FROM event WHERE id_event = ? AND is_deleted = 0";
$stmt_check = sqlsrv_query($conn, $sql_check, [$id_event]);

if ($stmt_check === false) {
    echo json_encode(['error' => 'Failed to check event status', 'detail' => sqlsrv_errors()]);
    exit;
}

$event = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);

if (!$event) {
    echo json_encode(['success' => false, 'message' => 'Event not found']);
    exit;
}

// Asumsi: jika status_event = 0 artinya event sudah selesai/tidak aktif
if ($event['status_event'] == 0) {
    echo json_encode(['success' => false, 'message' => 'Can not delete running event']);
    exit;
}

// ==========================================
// 2. PROSES UPDATE DENGAN TRANSACTION
// ==========================================
if (sqlsrv_begin_transaction($conn) === false) {
    echo json_encode(['error' => 'Failed to start transaction', 'detail' => sqlsrv_errors()]);
    exit;
}

// Query A: Update status event jadi 0
$sql_update_event = "UPDATE event 
                     SET status_event = 0, modified_by = ?, modified_date = GETDATE(), is_deleted = 1
                     WHERE id_event = ? AND is_deleted = 0";
$stmt1 = sqlsrv_query($conn, $sql_update_event, [$modified_by, $id_event]);

$sql_update_stock = "UPDATE p
                     SET p.stok = p.stok + pe.stok_event
                     FROM produk p 
                     JOIN produk_event pe ON pe.id_produk = p.id_produk 
                     WHERE pe.id_event = ?";
$stmt2 = sqlsrv_query($conn, $sql_update_stock, [$id_event]);

// Cek apakah semua query update berhasil
if ($stmt1 === false || $stmt2 === false) {
    sqlsrv_rollback($conn); // Batalkan semua jika ada yang gagal
    echo json_encode([
        'error' => 'Failed to complete event',
        'detail' => sqlsrv_errors()
    ]);
    exit;
}

// Jika semua sukses, simpan perubahan permanen ke DB
sqlsrv_commit($conn);

echo json_encode([
    'success' => true,
    'message' => 'Event marked as completed and stock restored successfully'
]);