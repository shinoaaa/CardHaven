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
    // Tangkap param
    $search_query = !empty($_GET['search_query']) ? $_GET['search_query'] : null; // <--- TAMBAHAN
    $games        = !empty($_GET['games']) ? $_GET['games'] : null;
    $types        = !empty($_GET['types']) ? $_GET['types'] : null;
    $rarities     = !empty($_GET['rarities']) ? $_GET['rarities'] : null;
    $min_price    = !empty($_GET['min_price']) ? $_GET['min_price'] : null;
    $max_price    = !empty($_GET['max_price']) ? $_GET['max_price'] : null;
    $sort_by      = !empty($_GET['sort_by']) ? $_GET['sort_by'] : 'default';
    $page         = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit        = !empty($_GET['limit']) ? (int)$_GET['limit'] : 6;

    // UPDATE PANGGILAN PROCEDURE DENGAN PARAMETER BARU
    $sql = "EXEC dbo.sp_GetCatalogue 
            @Games = ?, @Types = ?, @Rarities = ?, 
            @MinPrice = ?, @MaxPrice = ?, @SortBy = ?, 
            @Page = ?, @Limit = ?, @SearchQuery = ?";
            
    $params = array($games, $types, $rarities, $min_price, $max_price, $sort_by, $page, $limit, $search_query);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    $result = [];
    $total_rows = 0;

    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $total_rows = $row['TotalRows']; 
            unset($row['TotalRows']); 
            $result[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $result, 'total_rows' => $total_rows]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Failed to fetch catalogue']);
    }
}

// ... Kode controller yang lain ...

// TAMBAHKAN BLOK INI DI DALAM CONTROLLER:
elseif ($action === 'search_suggestion') {
    $q = $_GET['q'] ?? '';
    if (strlen($q) < 2) {
        echo json_encode(['status' => 'success', 'data' => []]); 
        exit;
    }
    
    // Cari produk maksimal 5 biji untuk suggestion modal
    $sql = "SELECT TOP 5 id_produk, nama_produk, harga_jual, foto 
            FROM dbo.produk 
            WHERE nama_produk LIKE ? 
              AND is_deleted = 0 
              AND status = 1";
              
    // Format pencarian LIKE '%katakunci%'
    $params = array('%' . $q . '%');
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    $data = [];
    if ($stmt) {
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }
    }
    echo json_encode(['status' => 'success', 'data' => $data]);
}
?>