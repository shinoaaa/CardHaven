<?php
/**
 * fetch_dashboard.php
 * Di-include oleh halaman utama product dashboard.
 * Mengambil semua data paginasi untuk ditampilkan di card-card.
 */

$limit_produk = 7; $limit_game = 3; $limit_set = 3; $limit_rarity = 3; $limit_metode = 5;

$page_produk = max(1, (int)($_GET['pp'] ?? 1));
$page_game   = max(1, (int)($_GET['pg'] ?? 1));
$page_set    = max(1, (int)($_GET['ps'] ?? 1));
$page_rarity = max(1, (int)($_GET['pr'] ?? 1));
$page_metode = max(1, (int)($_GET['pm'] ?? 1));

$offset_produk = ($page_produk - 1) * $limit_produk;
$offset_game   = ($page_game - 1) * $limit_game;
$offset_set    = ($page_set - 1) * $limit_set;
$offset_rarity = ($page_rarity - 1) * $limit_rarity;
$offset_metode = ($page_metode - 1) * $limit_metode;

// [FIX] Inisialisasi default semua variabel agar card tidak crash jika query gagal
$data_produk = []; $data_game = []; $data_set = []; $data_rarity = []; $data_metode = [];
$total_pages_produk = 1; $total_pages_game = 1; $total_pages_set = 1;
$total_pages_rarity = 1; $total_pages_metode = 1;

// [FIX] Guard: pastikan $conn tersedia sebelum query
if (!isset($conn) || $conn === false) {
    // Koneksi belum siap, biarkan variabel default di atas
    return;
}

// [FIX] COUNT: bungkus dengan error check, jangan langsung chain sqlsrv_fetch_array(sqlsrv_query())
$sql_count  = "SELECT dbo.udf_CountDashboard('produk') AS cp,
                        dbo.udf_CountDashboard('game')   AS cg,
                        dbo.udf_CountDashboard('set')    AS cs,
                        dbo.udf_CountDashboard('rarity') AS cr,
                        dbo.udf_CountDashboard('metode') AS cm";

$stmt_count = sqlsrv_query($conn, $sql_count);
if ($stmt_count !== false) {
    $counts = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC);
    if ($counts) {
        $total_pages_produk = max(1, (int)ceil(($counts['cp'] ?? 0) / $limit_produk));
        $total_pages_game   = max(1, (int)ceil(($counts['cg'] ?? 0) / $limit_game));
        $total_pages_set    = max(1, (int)ceil(($counts['cs'] ?? 0) / $limit_set));
        $total_pages_rarity = max(1, (int)ceil(($counts['cr'] ?? 0) / $limit_rarity));
        $total_pages_metode = max(1, (int)ceil(($counts['cm'] ?? 0) / $limit_metode));
    }
}

// [FIX] Clamp halaman aktif agar tidak melebihi total halaman
$page_produk = min($page_produk, $total_pages_produk);
$page_game   = min($page_game,   $total_pages_game);
$page_set    = min($page_set,    $total_pages_set);
$page_rarity = min($page_rarity, $total_pages_rarity);
$page_metode = min($page_metode, $total_pages_metode);

// Hitung ulang offset setelah clamp
$offset_produk = ($page_produk - 1) * $limit_produk;
$offset_game   = ($page_game - 1) * $limit_game;
$offset_set    = ($page_set - 1) * $limit_set;
$offset_rarity = ($page_rarity - 1) * $limit_rarity;
$offset_metode = ($page_metode - 1) * $limit_metode;

// [FIX] Eksekusi sp_FetchDashboard dengan error check
$stmt_all = sqlsrv_query(
    $conn,
    '{CALL dbo.sp_FetchDashboard(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}',
    [
        $limit_produk, $offset_produk,
        $limit_game,   $offset_game,
        $limit_set,    $offset_set,
        $limit_rarity, $offset_rarity,
        $limit_metode, $offset_metode,
    ]
);

// [FIX] Jika SP gagal, log error tapi JANGAN die() agar halaman tetap tampil
if ($stmt_all === false) {
    // Log ke PHP error log, jangan tampil ke user
    error_log('[CardHaven] sp_FetchDashboard error: ' . print_r(sqlsrv_errors(), true));
    // Variabel default (array kosong) sudah di-set di atas, cukup return
    return;
}

// Ekstraksi ResultSet 1: Produk
while ($row = sqlsrv_fetch_array($stmt_all, SQLSRV_FETCH_ASSOC)) {
    $data_produk[] = $row;
}

// ResultSet 2: Game
if (sqlsrv_next_result($stmt_all) !== false) {
    while ($row = sqlsrv_fetch_array($stmt_all, SQLSRV_FETCH_ASSOC)) {
        $data_game[] = $row;
    }
}

// ResultSet 3: Set
if (sqlsrv_next_result($stmt_all) !== false) {
    while ($row = sqlsrv_fetch_array($stmt_all, SQLSRV_FETCH_ASSOC)) {
        $data_set[] = $row;
    }
}

// ResultSet 4: Rarity
if (sqlsrv_next_result($stmt_all) !== false) {
    while ($row = sqlsrv_fetch_array($stmt_all, SQLSRV_FETCH_ASSOC)) {
        $data_rarity[] = $row;
    }
}

// ResultSet 5: Metode
if (sqlsrv_next_result($stmt_all) !== false) {
    while ($row = sqlsrv_fetch_array($stmt_all, SQLSRV_FETCH_ASSOC)) {
        $data_metode[] = $row;
    }
}
?>