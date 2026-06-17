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

$sql = "
    UPDATE event
    SET 
        status_event = 0,
        modified_by = ?,
        modified_date = GETDATE()
    WHERE id_event = ?
      AND is_deleted = 0;

    UPDATE p
    SET p.stok = p.stok + pe.stok_event
    WHERE pe.id_produk_event = ?
    FROM produk p 
    JOIN produk_event pe
    on pe.id_produk = p.id_produk_event
";

$stmt = sqlsrv_query($conn, $sql, [$modified_by, $id_event]);

if ($stmt === false) {
    echo json_encode([
        'error' => 'Failed to complete event',
        'detail' => sqlsrv_errors()
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Event marked as completed'
]);