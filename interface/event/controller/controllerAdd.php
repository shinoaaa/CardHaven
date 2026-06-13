<?php
ob_start();
require __DIR__ . '/../../../connection.php';
ob_end_clean();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

if (!$body) {
    echo json_encode(['error' => 'Invalid JSON body']);
    exit;
}

// ── Validasi field wajib ──────────────────────────────────────────────────────
$required = ['nama_event', 'tipe_event', 'tanggal_mulai', 'tanggal_berakhir', 'persen_diskon', 'maks_pembelian'];
foreach ($required as $field) {
    if (!isset($body[$field]) || $body[$field] === '' || $body[$field] === null) {
        echo json_encode(['error' => "Field '$field' wajib diisi"]);
        exit;
    }
}

$products = $body['products'] ?? [];
if (empty($products)) {
    echo json_encode(['error' => 'Minimal 1 produk harus ditambahkan']);
    exit;
}

$nama_event      = trim($body['nama_event']);
$tipe_event      = trim($body['tipe_event']);
$tanggal_mulai   = $body['tanggal_mulai'];
$tanggal_berakhir = $body['tanggal_berakhir'];
$tanggal_sampai  = !empty($body['tanggal_sampai']) ? $body['tanggal_sampai'] : null;
$persen_diskon   = (float)$body['persen_diskon'];
$maks_pembelian  = (int)$body['maks_pembelian'];
$created_by      = (int)$body['id_karyawan']; 

// Validasi preorder max 1 produk
if ($tipe_event === 'preorder' && count($products) > 1) {
    echo json_encode(['error' => 'Event preorder hanya boleh memiliki 1 produk']);
    exit;
}

// Validasi maks pembelian
if ($maks_pembelian <= 0) {
    echo json_encode(['error' => 'Maks pembelian harus lebih dari 0']);
    exit;
}

// Validasi tanggal
if ($tanggal_berakhir < $tanggal_mulai) {
    echo json_encode(['error' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai']);
    exit;
}

// ── Insert event ─────────────────────────────────────────────────────────────
$sqlEvent = "
    INSERT INTO event (
        nama_event, tipe_event, tanggal_mulai, tanggal_berakhir,
        tanggal_sampai, persen_diskon, maks_pembelian,
        status_event, is_deleted, created_by, created_date
    )
    OUTPUT INSERTED.id_event
    VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0, ?, GETDATE())
";

$paramsEvent = [
    $nama_event,
    $tipe_event,
    $tanggal_mulai,
    $tanggal_berakhir,
    $tanggal_sampai,
    $persen_diskon,
    $maks_pembelian,
    $created_by
];

$stmtEvent = sqlsrv_query($conn, $sqlEvent, $paramsEvent);

if ($stmtEvent === false) {
    echo json_encode(['error' => 'Gagal insert event', 'detail' => sqlsrv_errors()]);
    exit;
}

$row = sqlsrv_fetch_array($stmtEvent, SQLSRV_FETCH_ASSOC);
$id_event = (int)$row['id_event'];

// ── Insert produk_event ───────────────────────────────────────────────────────
$sqlProd = "
    INSERT INTO produk_event (id_produk, id_event, harga_event, stok_event)
    VALUES (?, ?, ?, ?)
";

foreach ($products as $prod) {
    $id_produk   = (int)$prod['id_produk'];
    $harga_event = (float)$prod['harga_event'];
    $stok_event  = (int)$prod['stok_event'];
    
    $stmtProd = sqlsrv_query($conn, $sqlProd, [$id_produk, $id_event, $harga_event, $stok_event]);

    if ($stmtProd === false) {
        echo json_encode(['error' => "Gagal insert produk id $id_produk", 'detail' => sqlsrv_errors()]);
        exit;
    }
}

echo json_encode(['success' => true, 'id_event' => $id_event]);