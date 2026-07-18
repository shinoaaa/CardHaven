<?php
ob_start();
require __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/../../../auth/session.php';
ob_end_clean();
header('Content-Type: application/json');

// Kelola Event: Manager & Owner saja.
$eventActor = auth_api_require_role([ROLE_MANAGER, ROLE_OWNER])['id'];

function jsonOut($ok, $data = []) { echo json_encode(array_merge(['success' => $ok], $data)); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(false, ['error' => 'Method not allowed']);

$body = json_decode(file_get_contents('php://input'), true);
$action = trim($body['action'] ?? '');

switch ($action) {
    case 'save_event':
        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_EditEvent(?, ?, ?, ?, ?, ?, ?, ?, ?)}", [
            (int)$body['id_event'], trim($body['nama_event']), trim($body['tipe_event']), $body['tanggal_mulai'], $body['tanggal_berakhir'], 
            ($body['tanggal_sampai'] !== '' ? $body['tanggal_sampai'] : null), (float)$body['persen_diskon'], (int)$body['maks_pembelian'], $eventActor
        ]);
        if ($stmt === false) jsonOut(false, ['error' => sqlsrv_errors()[0]['message']]);
        jsonOut(true, ['message' => 'Event updated']);
        
    case 'add_product':
        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageEventProduct('add', ?, ?, 0, ?, ?)}", [
            (int)$body['id_event'], (int)$body['id_produk'], (float)$body['harga_event'], (int)$body['stok_event']
        ]);
        if ($stmt === false) jsonOut(false, ['error' => sqlsrv_errors()[0]['message']]);
        jsonOut(true, ['message' => 'Product saved']);

    case 'update_stock':
        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageEventProduct('update_stock', 0, 0, ?, 0, ?)}", [
            (int)$body['id_produk_event'], (int)$body['stok_event']
        ]);
        if ($stmt === false) jsonOut(false, ['error' => sqlsrv_errors()[0]['message']]);
        jsonOut(true, ['message' => 'Stock updated']);

    case 'delete_product':
        $stmt = sqlsrv_query($conn, "{CALL dbo.sp_ManageEventProduct('delete', 0, 0, ?, 0, 0)}", [(int)$body['id_produk_event']]);
        if ($stmt === false) jsonOut(false, ['error' => sqlsrv_errors()[0]['message']]);
        jsonOut(true, ['message' => 'Product removed']);

    default: jsonOut(false, ['error' => 'Unknown action']);
}
?>