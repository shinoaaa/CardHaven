<?php
ob_start();
require __DIR__ . '/../../../connection.php';
ob_end_clean();

header('Content-Type: application/json');

function jsonOut($ok, $data = []) {
    echo json_encode(array_merge(['success' => $ok], $data));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, ['error' => 'Method not allowed']);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    jsonOut(false, ['error' => 'Invalid JSON body']);
}

$action = trim($body['action'] ?? '');
if ($action === '') {
    jsonOut(false, ['error' => 'Action is required']);
}

switch ($action) {
    case 'save_event': {
        $id_event        = (int)($body['id_event'] ?? 0);
        $nama_event      = trim($body['nama_event'] ?? '');
        $tipe_event      = trim($body['tipe_event'] ?? '');
        $tanggal_mulai   = trim($body['tanggal_mulai'] ?? '');
        $tanggal_berakhir = trim($body['tanggal_berakhir'] ?? '');
        $tanggal_sampai  = trim($body['tanggal_sampai'] ?? '');
        $persen_diskon   = $body['persen_diskon'] ?? null;
        $maks_pembelian  = (int)($body['maks_pembelian'] ?? 0);
        $modified_by     = (int)($body['id_karyawan'] ?? 0);

        if ($id_event <= 0) {
            jsonOut(false, ['error' => 'Invalid event ID']);
        }
        if ($nama_event === '' || $tipe_event === '' || $tanggal_mulai === '' || $tanggal_berakhir === '' || $persen_diskon === null) {
            jsonOut(false, ['error' => 'Required fields are missing']);
        }
        if ($maks_pembelian <= 0) {
            jsonOut(false, ['error' => 'Max purchase must be greater than 0']);
        }
        if ($tanggal_berakhir < $tanggal_mulai) {
            jsonOut(false, ['error' => 'End date cannot be before start date']);
        }

        $sql = "
            UPDATE event
            SET
                nama_event = ?,
                tipe_event = ?,
                tanggal_mulai = ?,
                tanggal_berakhir = ?,
                tanggal_sampai = ?,
                persen_diskon = ?,
                maks_pembelian = ?,
                modified_by = ?,
                modified_date = GETDATE()
            WHERE id_event = ? AND ISNULL(is_deleted, 0) = 0
        ";

        $params = [
            $nama_event,
            $tipe_event,
            $tanggal_mulai,
            $tanggal_berakhir,
            ($tanggal_sampai !== '' ? $tanggal_sampai : null),
            (float)$persen_diskon,
            $maks_pembelian,
            $modified_by > 0 ? $modified_by : null,
            $id_event
        ];

        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            jsonOut(false, ['error' => 'Failed to update event', 'detail' => sqlsrv_errors()]);
        }

        jsonOut(true, ['message' => 'Event updated']);
    }

    case 'add_product': {
        $id_event    = (int)($body['id_event'] ?? 0);
        $id_produk   = (int)($body['id_produk'] ?? 0);
        $harga_event = (float)($body['harga_event'] ?? 0);
        $stok_event  = (int)($body['stok_event'] ?? 0);

        if ($id_event <= 0 || $id_produk <= 0) {
            jsonOut(false, ['error' => 'Invalid event/product ID']);
        }
        if ($stok_event <= 0) {
            jsonOut(false, ['error' => 'Stock must be greater than 0']);
        }

        $sqlEventType = "
            SELECT tipe_event
            FROM event
            WHERE id_event = ? AND ISNULL(is_deleted, 0) = 0
        ";
        $stmtEvent = sqlsrv_query($conn, $sqlEventType, [$id_event]);
        if ($stmtEvent === false) {
            jsonOut(false, ['error' => 'Failed to check event type', 'detail' => sqlsrv_errors()]);
        }
        $eventRow = sqlsrv_fetch_array($stmtEvent, SQLSRV_FETCH_ASSOC);
        if (!$eventRow) {
            jsonOut(false, ['error' => 'Event not found']);
        }

        $sqlActiveCount = "
            SELECT COUNT(*) AS total
            FROM produk_event
            WHERE id_event = ?
              AND ISNULL(is_deleted, 0) = 0
              AND ISNULL(is_product_deleted, 0) = 0
        ";
        $stmtCount = sqlsrv_query($conn, $sqlActiveCount, [$id_event]);
        if ($stmtCount === false) {
            jsonOut(false, ['error' => 'Failed to check product count', 'detail' => sqlsrv_errors()]);
        }
        $countRow = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC);
        $activeCount = (int)($countRow['total'] ?? 0);

        if (($eventRow['tipe_event'] ?? '') === 'preorder' && $activeCount >= 1) {
            jsonOut(false, ['error' => 'Event Pre-Order hanya boleh memiliki 1 produk']);
        }

        $sqlExists = "
            SELECT id_produk_event, is_deleted, is_product_deleted
            FROM produk_event
            WHERE id_event = ? AND id_produk = ?
        ";
        $stmtExists = sqlsrv_query($conn, $sqlExists, [$id_event, $id_produk]);
        if ($stmtExists === false) {
            jsonOut(false, ['error' => 'Failed to check existing product', 'detail' => sqlsrv_errors()]);
        }
        $exists = sqlsrv_fetch_array($stmtExists, SQLSRV_FETCH_ASSOC);

        if ($exists) {
            $sqlUpdate = "
                UPDATE produk_event
                SET
                    harga_event = ?,
                    stok_event = ?,
                    is_deleted = 0,
                    is_product_deleted = 0
                WHERE id_produk_event = ?
            ";
            $stmt = sqlsrv_query($conn, $sqlUpdate, [$harga_event, $stok_event, (int)$exists['id_produk_event']]);
        } else {
            $sqlInsert = "
                INSERT INTO produk_event (
                    id_produk, id_event, harga_event, stok_event, is_deleted, is_product_deleted
                )
                VALUES (?, ?, ?, ?, 0, 0)
            ";
            $stmt = sqlsrv_query($conn, $sqlInsert, [$id_produk, $id_event, $harga_event, $stok_event]);
        }

        if ($stmt === false) {
            jsonOut(false, ['error' => 'Failed to save product', 'detail' => sqlsrv_errors()]);
        }

        jsonOut(true, ['message' => 'Product saved']);
    }

    case 'update_stock': {
        $id_produk_event = (int)($body['id_produk_event'] ?? 0);
        $stok_event      = (int)($body['stok_event'] ?? 0);

        if ($id_produk_event <= 0) {
            jsonOut(false, ['error' => 'Invalid product-event ID']);
        }
        if ($stok_event <= 0) {
            jsonOut(false, ['error' => 'Stock must be greater than 0']);
        }

        $sql = "
            UPDATE produk_event
            SET stok_event = ?
            WHERE id_produk_event = ?
              AND ISNULL(is_deleted, 0) = 0
              AND ISNULL(is_product_deleted, 0) = 0
        ";

        $stmt = sqlsrv_query($conn, $sql, [$stok_event, $id_produk_event]);
        if ($stmt === false) {
            jsonOut(false, ['error' => 'Failed to update stock', 'detail' => sqlsrv_errors()]);
        }

        jsonOut(true, ['message' => 'Stock updated']);
    }

    case 'delete_product': {
        $id_produk_event = (int)($body['id_produk_event'] ?? 0);

        if ($id_produk_event <= 0) {
            jsonOut(false, ['error' => 'Invalid product-event ID']);
        }

        $sql = "
            UPDATE produk_event
            SET
                is_deleted = 1,
                is_product_deleted = 1
            WHERE id_produk_event = ?
        ";

        $stmt = sqlsrv_query($conn, $sql, [$id_produk_event]);
        if ($stmt === false) {
            jsonOut(false, ['error' => 'Failed to remove product', 'detail' => sqlsrv_errors()]);
        }

        jsonOut(true, ['message' => 'Product removed']);
    }

    default:
        jsonOut(false, ['error' => 'Unknown action']);
}