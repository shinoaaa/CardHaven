<?php
ob_start();
require_once __DIR__ . '/../../connection.php';
require_once __DIR__ . '/controller/controllerFetch.php';
ob_end_clean();

$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';

// ── action: detail atau edit → return JSON ────────────────────────────────────
if ($action === 'detail' || $action === 'edit') {
    header('Content-Type: application/json');
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid ID']);
        exit;
    }

    $controller = new controllerEvent($conn);
    $row        = $controller->fetchEventById($id);
    
    if (!$row) {
        echo json_encode(['error' => 'Event not found']);
        exit;
    }

    $dateFormat = ($action === 'edit') ? 'Y-m-d' : 'd-M-Y';
    foreach (['tanggal_mulai', 'tanggal_berakhir', 'tanggal_sampai'] as $field) {
        if (isset($row[$field]) && $row[$field] instanceof DateTime) {
            $row[$field] = $row[$field]->format($dateFormat);
        } else {
            $row[$field] = ($action === 'edit') ? '' : '-';
        }
    }

    $row['persen_diskon']  = (float)($row['persen_diskon'] ?? 0);
    $row['maks_pembelian'] = (int)($row['maks_pembelian'] ?? 0);
    $row['status_event']   = (int)($row['status_event'] ?? 0);

    $payload = ['event' => $row];

    $detail = $controller->fetchDetail($id);

    if ($action === 'detail') {
        foreach ($detail as &$prod) {
            $prod['harga_event'] = number_format((float)($prod['harga_event'] ?? 0), 0, ',', '.');
            $prod['stok_event']  = number_format((int)($prod['stok_event'] ?? 0), 0, ',', '.');
        }
        unset($prod);
    }

    $payload['products'] = $detail;

    echo json_encode($payload);
    exit;
}

// ── action: search produk → return JSON ───────────────────────────────────────
if ($action === 'search_produk') {
    header('Content-Type: application/json');
    $q          = isset($_GET['q']) ? trim($_GET['q']) : '';
    $controller = new controllerEvent($conn);
    $data       = $controller->searchProduk($q);

    echo json_encode($data);
    exit;
}

// ── action: list (default) → siapkan variabel untuk index.php ────────────────
$page        = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$controller  = new controllerEvent($conn);
$stmt_event  = $controller->fetchEvent($page);
$total_event = $controller->countEvent();
$total_pages = max(1, (int)ceil($total_event / 7));