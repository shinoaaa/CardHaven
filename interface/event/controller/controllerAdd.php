<?php
ob_start();
require __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/../../../auth/session.php';
ob_end_clean();
header('Content-Type: application/json');

// Kelola Event: Manager & Owner saja (menu Event disembunyikan untuk Employee).
$eventActor = auth_api_require_role([ROLE_MANAGER, ROLE_OWNER])['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'Method not allowed']); exit; }

// Data dikirim sebagai multipart/form-data (FormData) karena membawa file banner.
// Field skalar ada di $_POST; daftar produk dikirim sebagai string JSON.
$body = $_POST;

$required = ['nama_event', 'tipe_event', 'tanggal_mulai', 'tanggal_berakhir', 'persen_diskon', 'maks_pembelian'];
foreach ($required as $field) {
    if (!isset($body[$field]) || $body[$field] === '') { echo json_encode(['error' => "Field '$field' is required"]); exit; }
}

$products = json_decode($body['products'] ?? '[]', true);
if (!is_array($products) || empty($products)) { echo json_encode(['error' => 'At least 1 product must be added']); exit; }
if ($body['tipe_event'] === 'preorder' && count($products) > 1) { echo json_encode(['error' => 'A preorder event can only have 1 product']); exit; }
if ((int)$body['maks_pembelian'] <= 0) { echo json_encode(['error' => 'Max purchase must be greater than 0']); exit; }
if ($body['tanggal_berakhir'] < $body['tanggal_mulai']) { echo json_encode(['error' => 'End date cannot be earlier than start date']); exit; }

// ── Validasi file banner (opsional) SEBELUM insert, supaya tidak terlanjur
//    membuat event kalau file-nya ternyata tidak valid. ─────────────────────
$bannerTmp = null; $bannerExt = null;
if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
    if ($_FILES['banner']['size'] > 3 * 1024 * 1024) {
        echo json_encode(['error' => 'Banner is too large (max 3 MB)']); exit;
    }
    $info = @getimagesize($_FILES['banner']['tmp_name']); // memastikan benar-benar gambar
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if ($info === false || !isset($allowed[$info[2]])) {
        echo json_encode(['error' => 'Banner must be a JPG, PNG, or WEBP image']); exit;
    }
    $bannerTmp = $_FILES['banner']['tmp_name'];
    $bannerExt = $allowed[$info[2]];
}

$itemsJson    = json_encode($products);
$status_event = isset($body['status_event']) ? (int)$body['status_event'] : 1;

$stmt = sqlsrv_query($conn, "{CALL dbo.sp_AddEvent(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}", [
    trim($body['nama_event']), trim($body['tipe_event']), $body['tanggal_mulai'], $body['tanggal_berakhir'],
    !empty($body['tanggal_sampai']) ? $body['tanggal_sampai'] : null, (float)$body['persen_diskon'],
    (int)$body['maks_pembelian'], $status_event, $eventActor, $itemsJson
]);

if ($stmt === false) { echo json_encode(['error' => sqlsrv_errors()[0]['message'] ?? 'Database Error']); exit; }
$row     = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$idEvent = $row['id_event'] ?? null;

// ── Simpan banner (kalau ada) lalu update kolom foto_banner ─────────────────
$fotoBanner = null;
if ($bannerTmp && $idEvent) {
    $targetDir = __DIR__ . '/../../../assets/image/image-banner/';
    if (!is_dir($targetDir)) @mkdir($targetDir, 0777, true);

    $fileName = 'EVT_' . time() . '_' . uniqid() . '.' . $bannerExt;
    if (move_uploaded_file($bannerTmp, $targetDir . $fileName)) {
        // Disimpan dengan prefix folder (ada '/') mengikuti konvensi game banner,
        // supaya di front-end cukup dipakai '/CardHaven/' + foto_banner.
        $fotoBanner = 'assets/image/image-banner/' . $fileName;
        sqlsrv_query($conn, "UPDATE dbo.event SET foto_banner = ? WHERE id_event = ?", [$fotoBanner, $idEvent]);
    }
}

echo json_encode(['success' => true, 'id_event' => $idEvent, 'foto_banner' => $fotoBanner]);
