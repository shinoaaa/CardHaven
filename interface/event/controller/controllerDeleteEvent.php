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

$modified_by = 1;

// ==========================================
// 1. CEK STATUS EVENT TERLEBIH DAHULU
// ==========================================
$sql_check = "SELECT status_event FROM event WHERE id_event = ?";
$stmt_check = sqlsrv_query($conn, $sql_check, [$id_event]);

// LANGKAH 2: Ambil datanya (Cukup SATU kali fetch saja)
$event = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);

// LANGKAH 3: Cek apakah ID event tersebut terdaftar di DB atau tidak
if (!$event) {
    echo json_encode(['success' => false, 'message' => 'Event not found']);
    exit;
}

if ($event['status_event'] == 1) {
    echo json_encode(['success' => false, 'message' => 'You can not delete running event, please stop it first']);
    exit;
}

// ==========================================
// 2. PROSES UPDATE DENGAN TRANSACTION
// ==========================================

if($event['status_event'] == 1 || $event['status_event'] == 0){
    $sql_update_event = "UPDATE event 
                     SET status_event = 0, modified_by = ?, modified_date = GETDATE(), is_deleted = 1
                     WHERE id_event = ? AND is_deleted = 0";
    $stmt1 = sqlsrv_query($conn, $sql_update_event, [$modified_by, $id_event]);
}

else if($event['status_event'] == 2){
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
}

else {
    echo json_encode(['success' => false, 'message' => 'Status are nor defined!']);
}


echo json_encode([
    'success' => true,
    'message' => 'Event marked as completed and stock restored successfully'
]);