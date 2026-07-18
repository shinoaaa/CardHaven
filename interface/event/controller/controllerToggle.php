<?php
ob_start();
require __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/../../../auth/session.php';
ob_end_clean();
header('Content-Type: application/json');

// Kelola Event: Manager & Owner saja.
auth_api_require_role([ROLE_MANAGER, ROLE_OWNER]);

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || !isset($body['id_event']) || !isset($body['is_hide'])) { echo json_encode(['error' => 'Invalid request data']); exit; }

$stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageEventAction('toggle_hide', ?, 1, ?)}", [(int)$body['id_event'], (int)$body['is_hide']]);

if ($stmt === false) {
    echo json_encode(['success' => false, 'error' => sqlsrv_errors()[0]['message']]);
    exit;
}
echo json_encode(['success' => true, 'message' => ($body['is_hide'] === 1 ? 'Event is now hidden from customers' : 'Event is visible again')]);
?>