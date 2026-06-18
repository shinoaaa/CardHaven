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

// Sesuaikan pengecekan dengan is_hide
if (!$body || !isset($body['id_event']) || !isset($body['is_hide'])) {
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

$id_event = (int)$body['id_event'];
$is_hide = (int)$body['is_hide']; // Pastikan yang ditangkap adalah is_hide (0 atau 1)

// Validasi hanya boleh 0 (Visible) atau 1 (Hidden)
if ($is_hide !== 0 && $is_hide !== 1) {
    echo json_encode(['error' => 'Invalid status value']);
    exit;
}

// Execute the Update query (Targetkan kolom is_hide)
$sql = "UPDATE event SET is_hide = ? WHERE id_event = ? AND ISNULL(is_deleted, 0) = 0";
$stmt = sqlsrv_query($conn, $sql, [$is_hide, $id_event]);

if ($stmt === false) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update event visibility in the database',
        'detail' => sqlsrv_errors()
    ]);
    exit;
}

// Send back the appropriate success message
$message = ($is_hide === 1) ? 'Event is now hidden from customers' : 'Event is visible again';
echo json_encode([
    'success' => true,
    'message' => $message
]);