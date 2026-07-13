<?php
ob_start();
require_once __DIR__ . '/../../connection.php';
require_once __DIR__ . '/controller/controllerFetch.php';
// Bersihkan output apapun sebelum mulai
if (ob_get_length()) ob_clean(); 

$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';

// ── 1. action: get_events_json (AJAX UNTUK RENDER TABEL) ──────────────────────
if ($action === 'get_events_json') {
    header('Content-Type: application/json');
    
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $search = $_GET['search'] ?? '';
    $status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : -1;
    $type   = $_GET['type'] ?? '';
    $sort   = $_GET['sort'] ?? 'date';
    $dir    = $_GET['dir'] ?? 'desc';

    try {
        $controller = new controllerEvent($conn);
        $controller->autoUpdateStatusEvent();

        $events = $controller->fetchEvent($page, $search, $status, $type, $sort, $dir);
        $total_event = $controller->countEvent();
        $total_pages = max(1, (int)ceil($total_event / 7));

        // Format tanggal ke String agar aman dibaca Javascript
        foreach ($events as &$row) {
            if (isset($row['tanggal_mulai']) && $row['tanggal_mulai'] instanceof DateTime) {
                $row['tanggal_mulai'] = $row['tanggal_mulai']->format('d-m-Y');
            }
            if (isset($row['tanggal_berakhir']) && $row['tanggal_berakhir'] instanceof DateTime) {
                $row['tanggal_berakhir'] = $row['tanggal_berakhir']->format('d-m-Y');
            }
        }
        unset($row);

        echo json_encode([
            'status' => 'success',
            'data' => $events,
            'page' => $page,
            'total_pages' => $total_pages
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit; // STOP EKSEKUSI DI SINI!
}

// ── 2. action: detail atau edit → return JSON ────────────────────────────────────
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

// ── 3. action: search produk → return JSON ───────────────────────────────────────
if ($action === 'search_produk') {
    header('Content-Type: application/json');
    $q          = isset($_GET['q']) ? trim($_GET['q']) : '';
    $controller = new controllerEvent($conn);
    $data       = $controller->searchProduk($q);

    echo json_encode($data);
    exit;
}

// ── 4. action: list (default) → siapkan variabel untuk HALAMAN AWAL HTML ─────────
// Karena semua action AJAX di atas sudah pakai 'exit;', kode di bawah ini 
// HANYA akan tereksekusi saat halaman pertama kali dibuka (di-include oleh index.php)
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// TANGKAP SEMUA FILTER DARI URL
$search = $_GET['search'] ?? '';
$status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : -1;
$type   = $_GET['type'] ?? '';
$sort   = $_GET['sort'] ?? 'date';
$dir    = $_GET['dir'] ?? 'desc';

$controller  = new controllerEvent($conn);
$controller->autoUpdateStatusEvent();

// LEMPAR SEMUA FILTER KE CONTROLLER
$stmt_event  = $controller->fetchEvent($page, $search, $status, $type, $sort, $dir);
$total_event = $controller->countEvent(); 
$total_pages = max(1, (int)ceil($total_event / 7));
?>