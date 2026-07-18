<?php
ob_start();
require __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/../../../auth/session.php';
ob_end_clean();
header('Content-Type: application/json');

// Kelola Event: Manager & Owner saja.
auth_api_require_role([ROLE_MANAGER, ROLE_OWNER]);

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || !isset($body['id_event'])) { echo json_encode(['error' => 'Invalid request']); exit; }

$action = ($body['action'] ?? 'complete') === 'move_up' ? 'move_up' : 'complete';
$stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageEventAction(?, ?, 1, 0)}", [$action, (int)$body['id_event']]);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => sqlsrv_errors()[0]['message'] ?? 'Database error']);
    exit;
}
echo json_encode(['success' => true, 'message' => 'Event status updated successfully']);
?>