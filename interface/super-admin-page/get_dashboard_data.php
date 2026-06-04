<?php
require_once 'connection.php';

function getPagedData($conn, $table, $page, $limit, $joins = "", $where = "WHERE aktif = 1") {
    $offset = ($page - 1) * $limit;
    
    // Hitung Total baris
    $countSql = "SELECT COUNT(*) as total FROM $table $where";
    $countStmt = sqlsrv_query($conn, $countSql);
    $totalRows = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRows / $limit);

    // Ambil Data
    // Catatan: SQL Server butuh ORDER BY untuk OFFSET/FETCH
    $orderBy = "ORDER BY id_" . (strpos($table, ' ') !== false ? explode(' ', $table)[0] : $table) . " DESC";
    if($table == 'dbo.kartu') $orderBy = "ORDER BY id_kartu DESC";

    $sql = "SELECT * FROM $table $joins $where $orderBy 
            OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
            
    $stmt = sqlsrv_query($conn, $sql);
    $data = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
    }

    return [
        'data' => $data,
        'total_pages' => $totalPages,
        'current_page' => $page
    ];
}

// Ambil parameter page dari GET, default 1
$pageGame = isset($_GET['p_game']) ? (int)$_GET['p_game'] : 1;
$pageProduct = isset($_GET['p_prod']) ? (int)$_GET['p_prod'] : 1;

// Contoh ambil data Game (Limit 5 baris per halaman)
$gameResult = getPagedData($conn, "dbo.game", $pageGame, 5);

// Contoh ambil data Produk (Limit 8 baris per halaman)
$productJoins = "LEFT JOIN dbo.game g ON kartu.id_game = g.id_game 
                LEFT JOIN dbo.set_kartu s ON kartu.id_set = s.id_set";
$productResult = getPagedData($conn, "dbo.kartu", $pageProduct, 8, $productJoins, "WHERE kartu.status = 1");
?>