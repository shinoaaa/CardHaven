<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../connection.php';
require_once __DIR__ . '/controllerTransaction.php';

$action = $_REQUEST['action'] ?? '';

// Aksi POST dikirim via JSON body (fetch), bukan form field — parse sekali, pakai ulang.
$postBody = ($_SERVER['REQUEST_METHOD'] === 'POST')
    ? (json_decode(file_get_contents('php://input'), true) ?: $_POST)
    : [];
if ($action === '' && isset($postBody['action'])) $action = $postBody['action'];

// ── LOGIKA API (AJAX JSON) ──────────────────────────────────
if ($action !== '') {
    header('Content-Type: application/json');
    try {
        $ctrl = new controllerTransaction($conn);
        $modified_by = $_SESSION['id_pengguna'] ?? 1;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $body = $postBody;
            $id = (int)($body['id_penjualan'] ?? 0);

            // Owner (role 3) view-only: tolak semua aksi mutasi transaksi.
            $actorId = (int)($body['modified_by'] ?? $_SESSION['id_pengguna'] ?? 0);
            if ($actorId) {
                $rq = sqlsrv_query($conn, "SELECT role FROM dbo.pengguna WHERE id_pengguna = ?", [$actorId]);
                $rr = $rq ? sqlsrv_fetch_array($rq, SQLSRV_FETCH_ASSOC) : null;
                if ($rr && (int)$rr['role'] === 3) {
                    echo json_encode(['status' => 'error', 'message' => 'Owner has view-only access to transactions.']); exit;
                }
            }

            switch ($action) {
                case 'proses':
                    $ok = $ctrl->prosesOrder($id, $modified_by);
                    echo json_encode(['status' => $ok ? 'success' : 'error']); exit;
                case 'confirm_payment': // Harus sama persis dengan yang di JS
                    $id = (int)($body['id_penjualan'] ?? 0);
                    $status = (int)($body['status'] ?? 1); // Status 1 = Paid
                    $mod_by = (int)($_SESSION['id_pengguna'] ?? 1);
                    
                    // Panggil fungsi updateStatus dari controllerTransaction
                    if ($ctrl->updateStatus($id, $status, $mod_by)) {
                        echo json_encode(['status' => 'success', 'message' => 'Payment has been confirmed']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Failed to confirm payment']);
                    }
                    exit;
                case 'kirim':
                    $no_resi = trim($body['no_resi'] ?? '');
                    if ($no_resi === '') { 
                        echo json_encode(['status' => 'error', 'message' => 'Tracking number is required']); exit;
                    }
                    $ok = $ctrl->kirimOrder($id, $modified_by, $no_resi);
                    echo json_encode(['status' => $ok ? 'success' : 'error']); exit;
                case 'delivered':
                    $ok = $ctrl->setDelivered($id, $modified_by);
                    echo json_encode(['status' => $ok ? 'success' : 'error']); exit;
                case 'cancel':
                    $ok = $ctrl->cancelOrder($id, $modified_by);
                    echo json_encode(['status' => $ok ? 'success' : 'error']); exit;
                default:
                    echo json_encode(['status' => 'error', 'message' => 'Unknown POST action']); exit;
            }
        } else {
            switch ($action) {
                case 'detail':
                    $id = (int)($_GET['id'] ?? 0);
                    $data = $ctrl->fetchDetail($id);
                    echo json_encode($data ?: ['error' => 'Not found']); exit;
                case 'count_status':
                    echo json_encode($ctrl->countPerStatus()); exit;
                default:
                    echo json_encode(['status' => 'error', 'message' => 'Unknown GET action']); exit;
            }
        }
    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); exit;
    }
} 
// ── LOGIKA VIEW (HTML FETCH) ──────────────────────────────────
else {
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sortBy    = isset($_GET['sort_by']) ? strtoupper($_GET['sort_by']) : 'DATE';
    $sortOrder = isset($_GET['sort_order']) ? strtoupper($_GET['sort_order']) : 'DESC';

    $ctrl = new controllerTransaction($conn);
    $result = $ctrl->fetchTransaksi($page, $status, $search, $sortBy, $sortOrder);

    $data = $result['data'];
    $total_pages = $result['total_pages'];
}
?>