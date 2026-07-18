<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../connection.php';
require_once __DIR__ . '/controllerTransaction.php';

// Seluruh data transaksi khusus pegawai (employee/manager/owner).
auth_api_require_role(auth_staff_roles());

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
        // Pelaku aksi (jejak audit) diambil dari session, bukan dari body request.
        $modified_by = auth_id();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $body = $postBody;
            $id = (int)($body['id_penjualan'] ?? 0);

            // Owner (role 3) view-only: tolak semua aksi mutasi transaksi.
            // Role dibaca dari session, jadi tidak perlu lagi query role
            // berdasarkan id kiriman browser (yang bisa dipalsukan).
            if (auth_role() === ROLE_OWNER) {
                echo json_encode(['status' => 'error', 'message' => 'Owner has view-only access to transactions.']); exit;
            }

            switch ($action) {
                case 'proses':
                    $ok = $ctrl->prosesOrder($id, $modified_by);
                    echo json_encode(['status' => $ok ? 'success' : 'error']); exit;
                case 'confirm_payment': // Harus sama persis dengan yang di JS
                    $id = (int)($body['id_penjualan'] ?? 0);
                    $status = (int)($body['status'] ?? 1); // Status 1 = Paid
                    $mod_by = $modified_by;
                    
                    // Panggil fungsi updateStatus dari controllerTransaction
                    if ($ctrl->updateStatus($id, $status, $mod_by)) {
                        echo json_encode(['status' => 'success', 'message' => 'Payment has been confirmed']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Failed to confirm payment']);
                    }
                    exit;
                case 'reject_payment':
                    $id = (int)($body['id_penjualan'] ?? 0);
                    $reason = trim($body['reason'] ?? '');
                    $mod_by = $modified_by;
                    
                    if ($reason === '') {
                        echo json_encode(['status' => 'error', 'message' => 'Alasan penolakan harus diisi.']); exit;
                    }
                    if ($ctrl->rejectPayment($id, $mod_by, $reason)) {
                        echo json_encode(['status' => 'success', 'message' => 'Payment ditolak dan notifikasi berhasil dikirim.']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Gagal menolak payment.']);
                    }
                    exit;
                case 'kirim':
                   $no_resi = trim($body['no_resi'] ?? '');
                    $tgl_kirim = trim($body['tgl_kirim'] ?? '');
                    $today = date('Y-m-d');

                    if ($no_resi === '') { 
                        echo json_encode(['status' => 'error', 'message' => 'Tracking number is required']); exit;
                    }
                    // Validasi: Resi hanya boleh kombinasi huruf dan angka tanpa spasi atau karakter unik
                    if (!preg_match('/^[a-zA-Z0-9]+$/', $no_resi)) {
                        echo json_encode(['status' => 'error', 'message' => 'Tracking number hanya boleh kombinasi huruf dan angka tanpa spasi.']); exit;
                    }

                    if (strtotime($tgl_kirim) < strtotime($today)) {
                        echo json_encode(['status' => 'error', 'message' => 'Tanggal pengiriman tidak boleh di masa lalu.']); exit;
                    }
                    
                    $ok = $ctrl->updateStatus($id, 4, $modified_by, $no_resi, $tgl_kirim);
                    echo json_encode(['status' => $ok ? 'success' : 'error']); exit;
                case 'delivered':
                    $ok = $ctrl->setDelivered($id, $modified_by);
                    echo json_encode(['status' => $ok ? 'success' : 'error']); exit;
                case 'completed': // Status 6 (Selesai)
                    $ok = $ctrl->updateStatus($id, 6, $modified_by);
                    echo json_encode(['status' => $ok ? 'success' : 'error']); exit;

                case 'returned': // Status 7 (Dikembalikan/Restorasi Stok)
                    $ok = $ctrl->updateStatus($id, 7, $modified_by);
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