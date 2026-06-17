<?php
require_once __DIR__ . '/../../connection.php';

// Ambil Data Game
$games = [];
$sql_games = "SELECT TOP 10 id_game, nama_game FROM dbo.game WHERE is_deleted = 0 ORDER BY id_game ASC";
$stmt_games = sqlsrv_query($conn, $sql_games);
if ($stmt_games) {
    while ($row = sqlsrv_fetch_array($stmt_games, SQLSRV_FETCH_ASSOC)) {
        $row['image_path'] = !empty($row['foto_game']) ? $row['foto_game'] : 'https://placehold.co/20vwx15vh/E2E8F0/475569?text=' . urlencode($row['nama_game']);
        $games[] = $row;
    }
}

// Ambil TOP 10 Produk (Diurutkan dari ID terkecil / ID 1)
$products = [];
$sql_products = "SELECT TOP 10 id_produk, nama_produk, harga_jual, foto FROM dbo.produk WHERE is_deleted = 0 ORDER BY id_produk ASC";
$stmt_products = sqlsrv_query($conn, $sql_products);
if ($stmt_products) {
    while ($row = sqlsrv_fetch_array($stmt_products, SQLSRV_FETCH_ASSOC)) {
        // Cek gambar dari DB, jika tidak ada gunakan dummy yang proporsional
        $row['image_path'] = !empty($row['foto']) ? $row['foto'] : 'https://placehold.co/10vwx15vh/FAFCFF/0F3891?text=Card';
        $products[] = $row;
    }
}
?>