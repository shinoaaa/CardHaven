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
// Tangkap tipe aksinya. Jika tidak dikirim, anggap sebagai 'complete' biar fungsi lama gak rusak
$action = isset($body['action']) ? $body['action'] : 'complete'; 

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
    echo json_encode(['success' => false, 'message' => 'Event not found or deleted']);
    exit;
}


// ==========================================
// 2. CABANG LOGIKA BERDASARKAN 'ACTION'
// ==========================================

if ($action === 'move_up') {
    // ── AKSI: MAJUKAN EVENT (MOVE UP) ──
    
    // Pastikan hanya event Upcoming (status 2) yang bisa dimajukan
    if ($event['status_event'] != 2) {
        echo json_encode(['success' => false, 'message' => 'Hanya event Upcoming (Upcoming) yang bisa dimajukan.']);
        exit;
    }

    // Ubah tanggal mulai jadi hari ini, dan set status ke 1 (Running)
    $sql_move_up = "UPDATE event 
                    SET tanggal_mulai = CAST(GETDATE() AS DATE), 
                        status_event = 1, 
                        modified_by = ?, 
                        modified_date = GETDATE()
                    WHERE id_event = ?";
    $stmt_move = sqlsrv_query($conn, $sql_move_up, [$modified_by, $id_event]);

    if ($stmt_move === false) {
        echo json_encode(['error' => 'Gagal memajukan event', 'detail' => sqlsrv_errors()]);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Event berhasil dimajukan dan sekarang sedang berjalan!']);
    exit;

} else {
    // ── AKSI: SELESAIKAN EVENT (COMPLETE) ──

    // Asumsi: jika status_event = 0 artinya event sudah selesai
    if ($event['status_event'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Event ini sudah selesai sebelumnya.']);
        exit;
    }

    if (sqlsrv_begin_transaction($conn) === false) {
        echo json_encode(['error' => 'Failed to start transaction', 'detail' => sqlsrv_errors()]);
        exit;
    }

    // Query A: Update status event jadi 0
    $sql_update_event = "UPDATE event 
                         SET status_event = 0, modified_by = ?, modified_date = GETDATE()
                         WHERE id_event = ? AND is_deleted = 0";
    $stmt1 = sqlsrv_query($conn, $sql_update_event, [$modified_by, $id_event]);

    // Query B: Kembalikan stok
    $sql_update_stock = "UPDATE p
                         SET p.stok = p.stok + pe.stok_event
                         FROM produk p 
                         JOIN produk_event pe ON pe.id_produk = p.id_produk 
                         WHERE pe.id_event = ?";
    $stmt2 = sqlsrv_query($conn, $sql_update_stock, [$id_event]);

    if ($stmt1 === false || $stmt2 === false) {
        sqlsrv_rollback($conn);
        echo json_encode(['error' => 'Failed to complete event', 'detail' => sqlsrv_errors()]);
        exit;
    }

    sqlsrv_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Event marked as completed and stock restored successfully']);
    exit;
}