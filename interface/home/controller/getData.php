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

// ResultSet 3: Promo Event (4 Rows)
sqlsrv_next_result($stmt);
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $response['list_promo'][] = $row;
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