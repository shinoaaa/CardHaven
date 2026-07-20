<?php
header('Content-Type: application/json');

require __DIR__ . '/../../connection.php';
require_once __DIR__ . '/../../auth/session.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'get_event':
        $idEvent = (int)($_GET['id_event'] ?? 0);
        if (!$idEvent) {
            echo json_encode(['error' => 'Invalid event ID.']);
            exit;
        }

        $stmtEvent = sqlsrv_query($conn, "{CALL dbo.sp_GetEventInfo(?)}", [$idEvent]);
        if (!$stmtEvent) {
            echo json_encode(['error' => 'Failed to fetch event.']);
            exit;
        }
        $event = sqlsrv_fetch_array($stmtEvent, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmtEvent);

        if (!$event) {
            echo json_encode(['error' => 'Event not found or not available.']);
            exit;
        }

        foreach (['tanggal_mulai','tanggal_berakhir','tanggal_sampai'] as $col) {
            if ($event[$col] instanceof DateTime) {
                $event[$col] = $event[$col]->format('Y-m-d');
            }
        }

        $stmtProd = sqlsrv_query($conn, "{CALL dbo.sp_GetEventProducts(?)}", [$idEvent]);
        if (!$stmtProd) {
            echo json_encode(['error' => 'Failed to fetch event products.']);
            exit;
        }
        $products = [];
        while ($row = sqlsrv_fetch_array($stmtProd, SQLSRV_FETCH_ASSOC)) {
            $products[] = $row;
        }
        sqlsrv_free_stmt($stmtProd);

        echo json_encode([
            'event'    => $event,
            'products' => $products
        ]);
        break;


    case 'get_purchase_count':
        $idPengguna = auth_api_require_login()['id'];
        $idEvent    = (int)($_GET['id_event'] ?? 0);

        if (!$idEvent) {
            echo json_encode(['counts' => []]);
            exit;
        }

        $sql = "{CALL dbo.sp_GetPurchaseCount(?, ?)}";
        $stmt = sqlsrv_query($conn, $sql, [$idEvent, $idPengguna]);
        $counts = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $counts[$row['id_produk']] = (int)$row['total_beli'];
            }
            sqlsrv_free_stmt($stmt);
        }
        echo json_encode(['counts' => $counts]);
        break;


    case 'submit_order':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            exit;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) {
            echo json_encode(['success' => false, 'message' => 'Invalid request body.']);
            exit;
        }

        $idPengguna = auth_api_require_login()['id'];
        $idEvent    = (int)($body['id_event']    ?? 0);
        $idMetode   = (int)($body['id_metode']   ?? 0);
        $alamat     = trim($body['alamat']        ?? '');
        $items      = $body['items']              ?? [];

        if (!$idPengguna) { echo json_encode(['success' => false, 'message' => 'User not authenticated.']); exit; }
        if (!$idEvent) { echo json_encode(['success' => false, 'message' => 'Invalid event.']); exit; }
        if (!$idMetode) { echo json_encode(['success' => false, 'message' => 'Payment method is required.']); exit; }
        if (!$alamat) { echo json_encode(['success' => false, 'message' => 'Delivery address is required.']); exit; }
        if (empty($items)) { echo json_encode(['success' => false, 'message' => 'No items selected.']); exit; }

        $itemsJson = json_encode($items);

        $sqlSp = "{CALL dbo.sp_CreateEventTransaction(?, ?, ?, ?, ?)}";
        $stmtSp = sqlsrv_query($conn, $sqlSp, [$idPengguna, $idEvent, $idMetode, $alamat, $itemsJson]);
        
        if (!$stmtSp) {
            $err = sqlsrv_errors();
            echo json_encode(['success' => false, 'message' => 'Failed to process transaction: ' . ($err[0]['message'] ?? '')]);
            exit;
        }

        $res = sqlsrv_fetch_array($stmtSp, SQLSRV_FETCH_ASSOC);
        if (!$res) {
            echo json_encode(['success' => false, 'message' => 'Empty response from SP']);
            exit;
        }

        if ($res['Status'] === 'ERROR') {
            echo json_encode(['success' => false, 'message' => $res['Message']]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Order successfully placed!',
            'id_penjualan' => $res['IdPenjualan']
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action.']);
        break;
}