<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../connection.php';
require_once __DIR__ . '/controllerTransaction.php';

$action = $_REQUEST['action'] ?? '';

// ── LOGIKA API (AJAX JSON) ──────────────────────────────────
if ($action !== '') {
    header('Content-Type: application/json');
    try {
        $ctrl = new controllerTransaction($conn);
        $modified_by = $_SESSION['id_pengguna'] ?? 1;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = (int)($body['id_penjualan'] ?? 0);
            
            switch ($action) {
                case 'proses':
                    $ok = $ctrl->prosesOrder($id, $modified_by);
                    echo json_encode(['status' => $ok ? 'success' : 'error']); exit;
                case 'kirim':
                    $no_resi = trim($body['no_resi'] ?? '');
                    if ($no_resi === '') { 
                        echo json_encode(['status' => 'error', 'message' => 'No resi wajib diisi']); exit; 
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
    
    $ctrl = new controllerTransaction($conn);
    $result = $ctrl->fetchTransaksi($page, $status, $search);
    
    $data = $result['data'];
    $total_pages = $result['total_pages'];
}
?>