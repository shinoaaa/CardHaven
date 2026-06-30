<?php
header('Content-Type: application/json');
require __DIR__ . '/../../../connection.php'; 

// 1. Ambil Parameter & Validasi Minimal 1
$p_event     = max(1, (int)($_GET['halaman_event'] ?? 1));
$p_promo     = max(1, (int)($_GET['halaman_promo'] ?? 1));
$p_game_bar  = max(1, (int)($_GET['halaman_game_bar'] ?? 1));
$p_game_card = max(1, (int)($_GET['halaman_game_card'] ?? 1));
$p_product   = max(1, (int)($_GET['halaman_product'] ?? 1));

// 2. Kalkulasi Offset
$offsetEvent    = $p_event - 1;
$offsetPromo    = ($p_promo - 1) * 4;
$offsetGameBar  = ($p_game_bar - 1) * 4;
$offsetGameCard = ($p_game_card - 1) * 4;
$offsetProduct  = ($p_product - 1) * 4;

$response = [
    'halaman_event_aktif'     => $p_event,
    'halaman_promo_aktif'     => $p_promo,
    'halaman_game_bar_aktif'  => $p_game_bar,
    'halaman_game_card_aktif' => $p_game_card,
    'halaman_product_aktif'   => $p_product, 
    'event'           => null,
    'list_promo'      => [],
    'list_game_bar'   => [],
    'list_game_card'  => [],
    'list_product'    => [], 
    'total_event'     => 0,
    'total_promo'     => 0,
    'total_game_bar'  => 0,
    'total_game_card' => 0,
    'total_product'   => 0 
];

// 3. Eksekusi Stored Procedure Tunggal
$stmt = sqlsrv_query($conn, "{CALL dbo.sp_GetHomePageData(?, ?, ?, ?, ?)}", [
    $offsetEvent, $offsetPromo, $offsetGameBar, $offsetGameCard, $offsetProduct
]);

if ($stmt === false) {
    echo json_encode(['error' => sqlsrv_errors()[0]['message'] ?? 'Database execution failed.']);
    exit;
}

// 4. Ekstrak ResultSet Secara Berurutan
// ResultSet 1: Aggregated Counts
$counts = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if ($counts) {
    $response['total_event']     = $counts['TotalEvent'];
    $response['total_promo']     = $counts['TotalPromo'];
    $response['total_game_bar']  = $counts['TotalGame'];
    $response['total_game_card'] = $counts['TotalGame'];
    $response['total_product']   = $counts['TotalProduct'];
}

// ResultSet 2: Preorder Event (1 Row)
sqlsrv_next_result($stmt);
$event = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if ($event) {
    if ($event['tanggal_sampai'] instanceof DateTime) {
        $event['tanggal_sampai'] = $event['tanggal_sampai']->format('d-m-Y');
    }
    $response['event'] = $event;
}

<<<<<<< HEAD
$sql_total_event = "SELECT COUNT(*) as total FROM event e JOIN produk_event pe ON e.id_event = pe.id_event WHERE e.tipe_event = 'preorder'";
$stmt_total_event = sqlsrv_query($conn, $sql_total_event);
if ($stmt_total_event !== false) $response['total_event'] = sqlsrv_fetch_array($stmt_total_event, SQLSRV_FETCH_ASSOC)['total'] ?? 0;


// --- 3. QUERY EVENT PROMO BARU (1 Data per Halaman, Isolasi dari Preorder) ---
$offsetPromo = ($p_promo - 1) * 4; // Kalkulasi offset per 4 data
$sqlPromo = "SELECT 
                e.id_event, 
                e.nama_event, 
                e.foto_banner, 
                e.status_event,
                (SELECT TOP 1 g.nama_game 
                 FROM produk_event pe 
                 JOIN produk p ON pe.id_produk = p.id_produk 
                 JOIN game g ON p.id_game = g.id_game 
                 WHERE pe.id_event = e.id_event) AS nama_game
             FROM event e
             WHERE e.tipe_event = 'promo' AND e.is_hide = 0
             ORDER BY e.id_event DESC
             OFFSET $offsetPromo ROWS
             FETCH NEXT 4 ROWS ONLY";
$stmtPromo = sqlsrv_query($conn, $sqlPromo);

if ($stmtPromo !== false) {
    while ($promoRow = sqlsrv_fetch_array($stmtPromo, SQLSRV_FETCH_ASSOC)) {
        $response['list_promo'][] = $promoRow; // Masukkan ke dalam array list_promo
    }
=======
// ResultSet 3: Promo Event (4 Rows)
sqlsrv_next_result($stmt);
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $response['list_promo'][] = $row;
>>>>>>> 6d09495ca60c0fa58ed4c79bb2a79655713dc299
}

// ResultSet 4: Game Bar (4 Rows)
sqlsrv_next_result($stmt);
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $response['list_game_bar'][] = $row;
}

// ResultSet 5: Game Card (4 Rows)
sqlsrv_next_result($stmt);
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $response['list_game_card'][] = $row;
}

// ResultSet 6: Product (4 Rows)
sqlsrv_next_result($stmt);
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $response['list_product'][] = $row;
}

echo json_encode($response);
exit;
?>