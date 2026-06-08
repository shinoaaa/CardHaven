<?php
// Pastikan file ini dipanggil SETELAH connection.php


$limit_produk = 7; // Menampilkan 7 baris sesuai desain
$page_produk = isset($_GET['pp']) ? max(1, (int)$_GET['pp']) : 1;
$offset_produk = ($page_produk - 1) * $limit_produk;

// Ambil state halaman dari master lain agar navigasi tidak reset
$page_game = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;
$page_set = isset($_GET['ps']) ? (int)$_GET['ps'] : 1;
$page_rarity = isset($_GET['pr']) ? (int)$_GET['pr'] : 1;

// 1. Hitung Total Baris Produk
$sql_count_produk = "SELECT COUNT(*) as total FROM dbo.produk";
$stmt_count_produk = sqlsrv_query($conn, $sql_count_produk);
if ($stmt_count_produk === false) die(print_r(sqlsrv_errors(), true));
$total_rows_produk = sqlsrv_fetch_array($stmt_count_produk, SQLSRV_FETCH_ASSOC)['total'] ?? 0;
$total_pages_produk = max(1, ceil($total_rows_produk / $limit_produk));

// 2. Query Data Produk dengan JOIN (Game & Set)
$sql_produk = "SELECT p.*, g.nama_game, s.nama_set 
                FROM dbo.produk p
                LEFT JOIN dbo.game g ON p.id_game = g.id_game
                LEFT JOIN dbo.set_kartu s ON p.id_set = s.id_set
                ORDER BY p.status DESC, p.id_produk DESC 
                OFFSET $offset_produk ROWS FETCH NEXT $limit_produk ROWS ONLY";

$stmt_produk = sqlsrv_query($conn, $sql_produk);
if ($stmt_produk === false) die(print_r(sqlsrv_errors(), true));

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

$sql_game = "SELECT * FROM dbo.game ORDER BY aktif DESC, id_game DESC OFFSET $offset_game ROWS FETCH NEXT $limit_game ROWS ONLY";
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
            ORDER BY s.aktif DESC, s.id_set DESC
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
               ORDER BY r.aktif DESC, r.id_rarity DESC
               OFFSET $offset_rarity ROWS FETCH NEXT $limit_rarity ROWS ONLY";
$stmt_rarity = sqlsrv_query($conn, $sql_rarity);
if ($stmt_rarity === false) die(print_r(sqlsrv_errors(), true));
?>