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

if (!$body || !isset($body['id_event']) || !isset($body['status_event'])) {
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

$id_event = (int)$body['id_event'];
$status_event = (int)$body['status_event']; // Accepted values: 1 or 3

// Validate the status to prevent unwanted database modifications
if ($status_event !== 1 && $status_event !== 3) {
    echo json_encode(['error' => 'Invalid status value']);
    exit;
}

// Execute the Update query
$sql = "UPDATE event SET status_event = ? WHERE id_event = ? AND is_deleted = 0";
$stmt = sqlsrv_query($conn, $sql, [$status_event, $id_event]);

if ($stmt === false) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update event visibility in the database',
        'detail' => sqlsrv_errors()
    ]);
    exit;
}

// Send back the appropriate success message
$message = ($status_event == 3) ? 'Event is now hidden from customers' : 'Event is visible again';
echo json_encode([
    'success' => true,
    'message' => $message
]);