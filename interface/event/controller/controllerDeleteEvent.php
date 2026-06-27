<?php
ob_start();
require __DIR__ . '/../../../connection.php';
ob_end_clean();
header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || !isset($body['id_event'])) { echo json_encode(['error' => 'Invalid request']); exit; }

$stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageEventAction('delete', ?, 1, 0)}", [(int)$body['id_event']]);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => sqlsrv_errors()[0]['message'] ?? 'Database error']);
    exit;
}
echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
?>