<?php
header('Content-Type: application/json');
require __DIR__ . '/../../../connection.php'; 

// 1. Ambil Semua Parameter Halaman Secara Mandiri
$p_event = isset($_GET['halaman_event']) ? (int)$_GET['halaman_event'] : 1;
if ($p_event < 1) { $p_event = 1; }

$p_game_bar = isset($_GET['halaman_game_bar']) ? (int)$_GET['halaman_game_bar'] : 1;
if ($p_game_bar < 1) { $p_game_bar = 1; }

$p_game_card = isset($_GET['halaman_game_card']) ? (int)$_GET['halaman_game_card'] : 1;
if ($p_game_card < 1) { $p_game_card = 1; }

$p_product = isset($_GET['halaman_product']) ? (int)$_GET['halaman_product'] : 1;
if ($p_product < 1) { $p_product = 1; }

// PARAMETER BARU KHUSUS PROMO
$p_promo = isset($_GET['halaman_promo']) ? (int)$_GET['halaman_promo'] : 1;
if ($p_promo < 1) { $p_promo = 1; }

$response = [
    'halaman_event_aktif' => $p_event,
    'halaman_game_bar_aktif' => $p_game_bar,
    'halaman_game_card_aktif' => $p_game_card,
    'halaman_product_aktif' => $p_product, 
    'halaman_promo_aktif' => $p_promo, // KEMBALIAN STATE PROMO
    'event' => null,
    'promo' => null, // KEMBALIAN DATA PROMO
    'list_game_bar' => [],
    'list_game_card' => [],
    'list_product' => [], 
    'total_event' => 0,
    'total_promo' => 0, // KEMBALIAN TOTAL PROMO
    'total_game_bar' => 0,
    'total_game_card' => 0,
    'total_product' => 0 
];

// --- 2. QUERY EVENT PREORDER (1 Data per Halaman) ---
$offsetEvent = $p_event - 1;
$sqlEvent = "SELECT e.nama_event, e.tanggal_sampai, pe.harga_event, p.nama_produk, p.deskripsi, p.harga_jual, p.foto
             FROM event e
             JOIN produk_event pe ON e.id_event = pe.id_event
             JOIN produk p ON pe.id_produk = p.id_produk
             WHERE e.tipe_event = 'preorder'
             ORDER BY e.id_event DESC
             OFFSET $offsetEvent ROWS
             FETCH NEXT 1 ROWS ONLY";
$stmtEvent = sqlsrv_query($conn, $sqlEvent);

if ($stmtEvent !== false) {
    $event = sqlsrv_fetch_array($stmtEvent, SQLSRV_FETCH_ASSOC);
    if ($event) {
        if ($event['tanggal_sampai'] instanceof DateTime) {
            $event['tanggal_sampai'] = $event['tanggal_sampai']->format('d-m-Y');
        }
        $response['event'] = $event;
    }
}

$sql_total_event = "SELECT COUNT(*) as total FROM event e JOIN produk_event pe ON e.id_event = pe.id_event WHERE e.tipe_event = 'preorder'";
$stmt_total_event = sqlsrv_query($conn, $sql_total_event);
if ($stmt_total_event !== false) $response['total_event'] = sqlsrv_fetch_array($stmt_total_event, SQLSRV_FETCH_ASSOC)['total'] ?? 0;


// --- 3. QUERY EVENT PROMO BARU (1 Data per Halaman, Isolasi dari Preorder) ---
$offsetPromo = ($p_promo - 1) * 4; // Kalkulasi offset per 4 data
$sqlPromo = "SELECT 
                e.id_event, 
                e.nama_event, 
                e.foto_banner, 
                (SELECT TOP 1 g.nama_game 
                 FROM produk_event pe 
                 JOIN produk p ON pe.id_produk = p.id_produk 
                 JOIN game g ON p.id_game = g.id_game 
                 WHERE pe.id_event = e.id_event) AS nama_game
             FROM event e
             WHERE e.tipe_event = 'promo'
             ORDER BY e.id_event DESC
             OFFSET $offsetPromo ROWS
             FETCH NEXT 4 ROWS ONLY"; // Ambil 4 baris sekaligus
$stmtPromo = sqlsrv_query($conn, $sqlPromo);

if ($stmtPromo !== false) {
    while ($promoRow = sqlsrv_fetch_array($stmtPromo, SQLSRV_FETCH_ASSOC)) {
        $response['list_promo'][] = $promoRow; // Masukkan ke dalam array list_promo
    }
}

$sql_total_promo = "SELECT COUNT(*) as total FROM event WHERE tipe_event = 'promo'";
$stmt_total_promo = sqlsrv_query($conn, $sql_total_promo);
if ($stmt_total_promo !== false) $response['total_promo'] = sqlsrv_fetch_array($stmt_total_promo, SQLSRV_FETCH_ASSOC)['total'] ?? 0;


// --- 4A. QUERY LIST GAME BAR (Text doang) ---
$offsetGameBar = ($p_game_bar - 1) * 4; 
$sqlGameBar = "SELECT id_game, nama_game 
               FROM [CardHaven].[dbo].[game] 
               WHERE is_deleted = 0 AND aktif = 1 
               ORDER BY id_game ASC 
               OFFSET $offsetGameBar ROWS 
               FETCH NEXT 4 ROWS ONLY";
$stmtGameBar = sqlsrv_query($conn, $sqlGameBar);
if ($stmtGameBar !== false) {
    while ($gameRow = sqlsrv_fetch_array($stmtGameBar, SQLSRV_FETCH_ASSOC)) {
        $response['list_game_bar'][] = $gameRow;
    }
}

// --- 4B. QUERY LIST GAME CARD (Card Gambar) ---
$offsetGameCard = ($p_game_card - 1) * 4; 
$sqlGameCard = "SELECT id_game, nama_game, foto_banner 
                FROM [CardHaven].[dbo].[game] 
                WHERE is_deleted = 0 AND aktif = 1 
                ORDER BY id_game ASC 
                OFFSET $offsetGameCard ROWS 
                FETCH NEXT 4 ROWS ONLY";
$stmtGameCard = sqlsrv_query($conn, $sqlGameCard);
if ($stmtGameCard !== false) {
    while ($gameRow = sqlsrv_fetch_array($stmtGameCard, SQLSRV_FETCH_ASSOC)) {
        $response['list_game_card'][] = $gameRow;
    }
}

// Total untuk Game
$sql_total_game = "SELECT COUNT(*) as total FROM [CardHaven].[dbo].[game] WHERE is_deleted = 0 AND aktif = 1";
$stmt_total_game = sqlsrv_query($conn, $sql_total_game);
if ($stmt_total_game !== false) {
    $totalGame = sqlsrv_fetch_array($stmt_total_game, SQLSRV_FETCH_ASSOC)['total'] ?? 0;
    $response['total_game_bar'] = $totalGame;
    $response['total_game_card'] = $totalGame;
}

// --- 5. QUERY PRODUK (4 Data per Halaman) ---
$offsetProduct = ($p_product - 1) * 4; 
$sqlProduct = "SELECT p.id_produk, p.nama_produk, p.stok, g.nama_game, p.deskripsi, p.harga_jual, p.foto 
               FROM [CardHaven].[dbo].[produk] p
               LEFT JOIN [CardHaven].[dbo].[game] g ON p.id_game = g.id_game
               ORDER BY p.id_produk ASC
               OFFSET $offsetProduct ROWS
               FETCH NEXT 4 ROWS ONLY";
$stmtProduct = sqlsrv_query($conn, $sqlProduct);

if ($stmtProduct !== false) {
    while ($prodRow = sqlsrv_fetch_array($stmtProduct, SQLSRV_FETCH_ASSOC)) {
        $response['list_product'][] = $prodRow;
    }
}

$sql_total_product = "SELECT COUNT(*) as total FROM [CardHaven].[dbo].[produk]";
$stmt_total_product = sqlsrv_query($conn, $sql_total_product);
if ($stmt_total_product !== false) $response['total_product'] = sqlsrv_fetch_array($stmt_total_product, SQLSRV_FETCH_ASSOC)['total'] ?? 0;

echo json_encode($response);
exit;