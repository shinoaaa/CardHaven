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
$limit_game = 3;
$page_game = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
$offset_game = ($page_game - 1) * $limit_game;

$sql_count_game = "SELECT COUNT(*) as total FROM dbo.game";
$stmt_count_game = sqlsrv_query($conn, $sql_count_game);
if ($stmt_count_game === false) die(print_r(sqlsrv_errors(), true));
$total_rows_game = sqlsrv_fetch_array($stmt_count_game, SQLSRV_FETCH_ASSOC)['total'] ?? 0;
$total_pages_game = max(1, ceil($total_rows_game / $limit_game));

$sql_game = "SELECT * FROM dbo.game ORDER BY aktif DESC, id_game ASC OFFSET $offset_game ROWS FETCH NEXT $limit_game ROWS ONLY";
$stmt_game = sqlsrv_query($conn, $sql_game);
if ($stmt_game === false) die(print_r(sqlsrv_errors(), true));


// ==========================================
// 2. LOGIKA PAGINASI & KUERI SET
// ==========================================
$limit_set = 3;
$page_set = isset($_GET['ps']) ? max(1, (int)$_GET['ps']) : 1;
$offset_set = ($page_set - 1) * $limit_set;

$sql_count_set = "SELECT COUNT(*) as total FROM dbo.set_kartu";
$stmt_count_set = sqlsrv_query($conn, $sql_count_set);
if ($stmt_count_set === false) die(print_r(sqlsrv_errors(), true));
$total_rows_set = sqlsrv_fetch_array($stmt_count_set, SQLSRV_FETCH_ASSOC)['total'] ?? 0;
$total_pages_set = max(1, ceil($total_rows_set / $limit_set));

$sql_set = "SELECT s.id_set, s.nama_set, s.kode_set, s.tanggal_rilis, s.aktif, g.nama_game
            FROM dbo.set_kartu s
            LEFT JOIN dbo.game g ON s.id_game = g.id_game
            ORDER BY s.aktif DESC, s.id_set ASC
            OFFSET $offset_set ROWS FETCH NEXT $limit_set ROWS ONLY";
$stmt_set = sqlsrv_query($conn, $sql_set);
if ($stmt_set === false) die(print_r(sqlsrv_errors(), true));


// ==========================================
// 3. LOGIKA PAGINASI & KUERI RARITY
// ==========================================
$limit_rarity = 3;
$page_rarity = isset($_GET['pr']) ? max(1, (int)$_GET['pr']) : 1;
$offset_rarity = ($page_rarity - 1) * $limit_rarity;

$sql_count_rarity = "SELECT COUNT(*) as total FROM dbo.rarity";
$stmt_count_rarity = sqlsrv_query($conn, $sql_count_rarity);
if ($stmt_count_rarity === false) die(print_r(sqlsrv_errors(), true));
$total_rows_rarity = sqlsrv_fetch_array($stmt_count_rarity, SQLSRV_FETCH_ASSOC)['total'] ?? 0;
$total_pages_rarity = max(1, ceil($total_rows_rarity / $limit_rarity));

$sql_rarity = "SELECT r.id_rarity, r.nama_rarity, r.kode_rarity, r.aktif, g.nama_game
               FROM dbo.rarity r
               LEFT JOIN dbo.game g ON r.id_game = g.id_game
               ORDER BY r.aktif DESC, r.id_rarity ASC
               OFFSET $offset_rarity ROWS FETCH NEXT $limit_rarity ROWS ONLY";
$stmt_rarity = sqlsrv_query($conn, $sql_rarity);
if ($stmt_rarity === false) die(print_r(sqlsrv_errors(), true));
?>