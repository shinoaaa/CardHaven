<?php
/**
 * interface/catalogue/controller/CatalogueController.php
 */
require_once __DIR__ . '/../../../connection.php'; 

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

if ($action === 'get_filters') {
    $sql = "EXEC dbo.sp_GetCatalogueFilters";
    $stmt = sqlsrv_query($conn, $sql);
    
    $games = [];
    $rarities = [];

    if ($stmt) {
        // Result Set 1: Games
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $games[] = $row;
        }
        // Maju ke Result Set 2: Rarities
        if (sqlsrv_next_result($stmt)) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rarities[] = $row;
            }
        }
        echo json_encode(['status' => 'success', 'games' => $games, 'rarities' => $rarities]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Failed to fetch filters']);
    }
}
elseif ($action === 'get_products') {
    // Tangkap param (jika tidak ada/kosong, set ke null)
    $games = !empty($_GET['games']) ? $_GET['games'] : null;
    $types = !empty($_GET['types']) ? $_GET['types'] : null;
    $rarities = !empty($_GET['rarities']) ? $_GET['rarities'] : null;
    $min_price = !empty($_GET['min_price']) ? $_GET['min_price'] : null;
    $max_price = !empty($_GET['max_price']) ? $_GET['max_price'] : null;
    $sort_by = !empty($_GET['sort_by']) ? $_GET['sort_by'] : 'default';
    $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 6;

    $sql = "EXEC dbo.sp_GetCatalogue 
            @Games = ?, @Types = ?, @Rarities = ?, 
            @MinPrice = ?, @MaxPrice = ?, @SortBy = ?, 
            @Page = ?, @Limit = ?";
            
    $params = array($games, $types, $rarities, $min_price, $max_price, $sort_by, $page, $limit);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    $result = [];
    $total_rows = 0;

    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $total_rows = $row['TotalRows']; // Ambil count dari kolom OVER()
            unset($row['TotalRows']); // Hapus supaya tidak ikut dikirim ke front-end card
            $result[] = $row;
        }
        echo json_encode([
            'status' => 'success', 
            'data' => $result, 
            'total_rows' => $total_rows
        ]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Failed to fetch catalogue']);
    }
}
?>