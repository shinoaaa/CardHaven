<?php
ob_start();
require __DIR__ . '/../../../connection.php';
ob_end_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'Method not allowed']); exit; }

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) { echo json_encode(['error' => 'Invalid JSON body']); exit; }

$required = ['nama_event', 'tipe_event', 'tanggal_mulai', 'tanggal_berakhir', 'persen_diskon', 'maks_pembelian'];
foreach ($required as $field) {
    if (!isset($body[$field]) || $body[$field] === '') { echo json_encode(['error' => "Field '$field' wajib diisi"]); exit; }
}

$products = $body['products'] ?? [];
if (empty($products)) { echo json_encode(['error' => 'Minimal 1 produk harus ditambahkan']); exit; }
if ($body['tipe_event'] === 'preorder' && count($products) > 1) { echo json_encode(['error' => 'Event preorder hanya boleh memiliki 1 produk']); exit; }
if ($body['maks_pembelian'] <= 0) { echo json_encode(['error' => 'Maks pembelian harus lebih dari 0']); exit; }
if ($body['tanggal_berakhir'] < $body['tanggal_mulai']) { echo json_encode(['error' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai']); exit; }

$itemsJson = json_encode($products);
$status_event = isset($body['status_event']) ? (int)$body['status_event'] : 1;

$stmt = sqlsrv_query($conn, "{CALL dbo.sp_AddEvent(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}", [
    trim($body['nama_event']), trim($body['tipe_event']), $body['tanggal_mulai'], $body['tanggal_berakhir'], 
    !empty($body['tanggal_sampai']) ? $body['tanggal_sampai'] : null, (float)$body['persen_diskon'], 
    (int)$body['maks_pembelian'], $status_event, (int)($body['id_karyawan'] ?? 0), $itemsJson
]);

if ($stmt === false) { echo json_encode(['error' => sqlsrv_errors()[0]['message'] ?? 'Database Error']); exit; }
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo json_encode(['success' => true, 'id_event' => $row['id_event']]);
?>
