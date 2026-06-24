<?php
ob_start();
require_once __DIR__ . '/../../connection.php';
require_once __DIR__ . '/controllerTransaction.php';
ob_end_clean();

$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';

// ── action: detail → JSON ─────────────────────────────────────────────────────
if ($action === 'detail') {
    header('Content-Type: application/json');
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID']); exit; }

    $ctrl = new controllerTransaction($conn);
    $data = $ctrl->fetchDetail($id);

    echo $data ? json_encode($data) : json_encode(['error' => 'Not found']);
    exit;
}

// ── action: count_status → JSON ───────────────────────────────────────────────
if ($action === 'count_status') {
    header('Content-Type: application/json');
    $ctrl = new controllerTransaction($conn);
    echo json_encode($ctrl->countPerStatus());
    exit;
}

// ── POST actions (ubah status) ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $body        = json_decode(file_get_contents('php://input'), true) ?? [];
    $postAction  = $body['action'] ?? '';
    $id          = (int)($body['id_penjualan'] ?? 0);
    $modified_by = (int)($body['modified_by'] ?? 0);

    if ($id <= 0) { echo json_encode(['status' => 'error', 'message' => 'Invalid ID']); exit; }

    $ctrl = new controllerTransaction($conn);
    $ok   = false;

    switch ($postAction) {
        case 'konfirmasi_bayar':
            $ok = $ctrl->konfirmasiPembayaran($id, $modified_by);
            break;

        case 'proses':
            $ok = $ctrl->prosesOrder($id, $modified_by);
            break;

        case 'kirim':
            $no_resi   = trim($body['no_resi'] ?? '');
            $tgl_kirim = trim($body['tanggal_pengiriman'] ?? '');
            if ($no_resi === '') {
                echo json_encode(['status' => 'error', 'message' => 'No resi wajib diisi']);
                exit;
            }
            $ok = $ctrl->kirimOrder($id, $modified_by, $no_resi, $tgl_kirim ?: null);
            break;

        case 'delivered':
            $ok = $ctrl->setDelivered($id, $modified_by);
            break;

        case 'cancel':
            $ok = $ctrl->cancelOrder($id, $modified_by);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
            exit;
    }

    echo json_encode(['status' => $ok ? 'success' : 'error']);
    exit;
}

// ── default: list → siapkan var untuk index.php ───────────────────────────────
$page   = isset($_GET['page'])   ? max(1, (int)$_GET['page'])   : 1;
$status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
$search = isset($_GET['search']) ? trim($_GET['search'])         : '';

$ctrl         = new controllerTransaction($conn);
$stmt_trx     = $ctrl->fetchTransaksi($page, $status, $search);
$total_trx    = $ctrl->countTransaksi($status, $search);
$count_status = $ctrl->countPerStatus();
$total_pages  = max(1, (int)ceil($total_trx / 10));
