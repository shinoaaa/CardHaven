<?php
ob_start();
require __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/../../../auth/session.php';
ob_end_clean();
header('Content-Type: application/json');

// Kelola Event: Manager & Owner saja (menu Event disembunyikan untuk Employee).
$eventActor = auth_api_require_role([ROLE_MANAGER, ROLE_OWNER])['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'Method not allowed']); exit; }

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) { echo json_encode(['error' => 'Invalid JSON body']); exit; }

$required = ['nama_event', 'tipe_event', 'tanggal_mulai', 'tanggal_berakhir', 'persen_diskon', 'maks_pembelian'];
foreach ($required as $field) {
    if (!isset($body[$field]) || $body[$field] === '') { echo json_encode(['error' => "Field '$field' is required"]); exit; }
}

$products = $body['products'] ?? [];
if (empty($products)) { echo json_encode(['error' => 'At least 1 product must be added']); exit; }
if ($body['tipe_event'] === 'preorder' && count($products) > 1) { echo json_encode(['error' => 'A preorder event can only have 1 product']); exit; }
if ($body['maks_pembelian'] <= 0) { echo json_encode(['error' => 'Max purchase must be greater than 0']); exit; }
if ($body['tanggal_berakhir'] < $body['tanggal_mulai']) { echo json_encode(['error' => 'End date cannot be earlier than start date']); exit; }

$itemsJson = json_encode($products);
$status_event = isset($body['status_event']) ? (int)$body['status_event'] : 1;

$stmt = sqlsrv_query($conn, "{CALL dbo.sp_AddEvent(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}", [
    trim($body['nama_event']), trim($body['tipe_event']), $body['tanggal_mulai'], $body['tanggal_berakhir'], 
    !empty($body['tanggal_sampai']) ? $body['tanggal_sampai'] : null, (float)$body['persen_diskon'], 
    (int)$body['maks_pembelian'], $status_event, $eventActor, $itemsJson
]);

if ($stmt === false) { echo json_encode(['error' => sqlsrv_errors()[0]['message'] ?? 'Database Error']); exit; }
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo json_encode(['success' => true, 'id_event' => $row['id_event']]);
?>
