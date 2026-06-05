<?php
// Pastikan file ini dipanggil SETELAH connection.php

$dummy_products = array_fill(0, 7, [
    'name' => 'Rayquaza V',
    'id' => '#CRD-1003',
    'game' => 'Pokemon',
    'set' => 'Scarlet and Violet Primastic',
    'stock' => 82,
    'condition' => 'NM',
    'price' => '$10.22'
]);

// ==========================================
// 1. LOGIKA PAGINASI & KUERI GAME
// ==========================================
$limit = 3;
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($page - 1) * $limit;

$sql_count = "SELECT COUNT(*) as total FROM dbo.game";
$stmt_count = sqlsrv_query($conn, $sql_count);
if ($stmt_count === false) die(print_r(sqlsrv_errors(), true));
$total_rows = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC)['total'] ?? 0;
$total_pages = max(1, ceil($total_rows / $limit));

$sql_game = "SELECT * FROM dbo.game ORDER BY aktif DESC, id_game ASC OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
$stmt_game = sqlsrv_query($conn, $sql_game);
if ($stmt_game === false) die(print_r(sqlsrv_errors(), true));


// ==========================================
// 2. LOGIKA PAGINASI & KUERI SET
// ==========================================
$limit_s = 3;
$page_s = isset($_GET['ps']) ? max(1, (int)$_GET['ps']) : 1;
$offset_s = ($page_s - 1) * $limit_s;

$sql_count_s = "SELECT COUNT(*) as total FROM dbo.set_kartu";
$stmt_count_s = sqlsrv_query($conn, $sql_count_s);
if ($stmt_count_s === false) die(print_r(sqlsrv_errors(), true));
$total_rows_s = sqlsrv_fetch_array($stmt_count_s, SQLSRV_FETCH_ASSOC)['total'] ?? 0;
$total_pages_s = max(1, ceil($total_rows_s / $limit_s));

$sql_set = "SELECT s.id_set, s.nama_set, s.kode_set, s.tanggal_rilis, s.aktif, g.nama_game
            FROM dbo.set_kartu s
            LEFT JOIN dbo.game g ON s.id_game = g.id_game
            ORDER BY s.aktif DESC, s.id_set ASC
            OFFSET $offset_s ROWS FETCH NEXT $limit_s ROWS ONLY";
$stmt_set = sqlsrv_query($conn, $sql_set);
if ($stmt_set === false) die(print_r(sqlsrv_errors(), true));


// ==========================================
// 3. LOGIKA PAGINASI & KUERI RARITY
// ==========================================
$limit_r = 3;
$page_r = isset($_GET['pr']) ? max(1, (int)$_GET['pr']) : 1;
$offset_r = ($page_r - 1) * $limit_r;

$sql_count_r = "SELECT COUNT(*) as total FROM dbo.rarity";
$stmt_count_r = sqlsrv_query($conn, $sql_count_r);
if ($stmt_count_r === false) die(print_r(sqlsrv_errors(), true));
$total_rows_r = sqlsrv_fetch_array($stmt_count_r, SQLSRV_FETCH_ASSOC)['total'] ?? 0;
$total_pages_r = max(1, ceil($total_rows_r / $limit_r));

$sql_rarity = "SELECT r.id_rarity, r.nama_rarity, r.kode_rarity, r.aktif, g.nama_game
               FROM dbo.rarity r
               LEFT JOIN dbo.game g ON r.id_game = g.id_game
               ORDER BY r.aktif DESC, r.id_rarity ASC
               OFFSET $offset_r ROWS FETCH NEXT $limit_r ROWS ONLY";
$stmt_rarity = sqlsrv_query($conn, $sql_rarity);
if ($stmt_rarity === false) die(print_r(sqlsrv_errors(), true));
?>