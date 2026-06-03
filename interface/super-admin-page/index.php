<?php

require_once '../../connection.php'; 
/**
 * Fungsi untuk mengambil data dengan sorting:
 * Aktif (1) di atas, Inaktif (0) di bawah, lalu urut ID
 */
function fetchTableData($conn, $tableName, $limit = 7) {
    // Pastikan kolom 'status' dan 'id' ada di tabel Anda
    $query = "SELECT * FROM $tableName ORDER BY status DESC, id ASC LIMIT $limit";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Simulasi Data (Ganti bagian ini dengan hasil fetch asli dari DB)
// $products = fetchTableData($conn, 'products', 7);
// $games = fetchTableData($conn, 'games', 3);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin - Products</title>
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
</head>
<body>
    <div class="container" style="justify-content: flex-start; align-items: flex-start;">
        <div class="sideBar">
            <?php include 'components/sideBar.php' ?>
        </div>
        
        <div class="main-content">
            
            <!-- 1. TABEL PRODUK (7 BARIS) -->
            <div class="content-card">
                <div class="card-title-row">
                    <h2 class="coolveticaa">Products</h2>
                    <button class="btn-add-green">+ Add Product</button>
                </div>
                
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Product ID</th>
                            <th>Game</th>
                            <th>Set</th>
                            <th>Stock</th>
                            <th>Condition</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for($i=0; $i<7; $i++): ?>
                        <tr>
                            <td>Rayquaza V</td>
                            <td>#CRD-1003</td>
                            <td>Pokemon</td>
                            <td>Scarlet and Violet</td>
                            <td>82</td>
                            <td>NM</td>
                            <td>$10.22</td>
                            <td>
                                <div class="btn-action-group">
                                    <button class="btn-edit-icon" style="background-color: #F39C12; border:none; padding:5px; border-radius:5px; color:white;">✏️</button>
                                    <button class="btn-delete-icon" style="background-color: #E74C3C; border:none; padding:5px; border-radius:5px; color:white;">🗑️</button>
                                </div>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>

                <div class="pagination-container">
                    <span class="page-num"> < </span>
                    <span class="page-num">1</span>
                    <span class="page-num active">2</span>
                    <span class="page-num">3</span>
                    <span class="page-num">...</span>
                    <span class="page-num">82</span>
                    <span class="page-num"> > </span>
                </div>
            </div>

            <!-- 2. WRAPPER MASTER DATA (Game, Set, Rarity) -->
            <div class="master-data-wrapper">
                
                <!-- TABEL GAME -->
                <div class="master-table-card">
                    <div>
                        <div class="card-title-row">
                            <h2 class="coolveticaa" style="font-size: 1.2rem;">Game</h2>
                            <button class="btn-add-green">+ Add Game</button>
                        </div>
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Game Name</th>
                                    <th>Game ID</th>
                                    <th>Developer</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for($i=0; $i<3; $i++): ?>
                                <tr>
                                    <td>Pokemon</td>
                                    <td>GM-099</td>
                                    <td>Stepanus</td>
                                    <td>Active</td>
                                    <td>
                                        <div class="btn-action-group">
                                            <button class="btn-edit-icon" style="background-color: #F39C12; border:none; padding:5px; border-radius:5px; color:white;">✏️</button>
                                            <button class="btn-delete-icon" style="background-color: #E74C3C; border:none; padding:5px; border-radius:5px; color:white;">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-container">
                        <span class="page-num"> < </span>
                        <span class="page-num">1</span>
                        <span class="page-num active">2</span>
                        <span class="page-num"> > </span>
                    </div>
                </div>

                <!-- TABEL SET -->
                <div class="master-table-card">
                    <div>
                        <div class="card-title-row">
                            <h2 class="coolveticaa" style="font-size: 1.2rem;">Set</h2>
                            <button class="btn-add-green">+ Add Set</button>
                        </div>
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Set Name</th>
                                    <th>Set ID</th>
                                    <th>Game</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for($i=0; $i<3; $i++): ?>
                                <tr>
                                    <td>Scarlet and Violet Primastic</td>
                                    <td>SET-001</td>
                                    <td>Pokemon</td>
                                    <td>Active</td>
                                    <td>
                                        <div class="btn-action-group">
                                            <button class="btn-edit-icon" style="background-color: #F39C12; border:none; padding:5px; border-radius:5px; color:white;">✏️</button>
                                            <button class="btn-delete-icon" style="background-color: #E74C3C; border:none; padding:5px; border-radius:5px; color:white;">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-container">
                        <span class="page-num"> < </span>
                        <span class="page-num">1</span>
                        <span class="page-num active">2</span>
                        <span class="page-num"> > </span>
                    </div>
                </div>

                <!-- TABEL RARITY -->
                <div class="master-table-card">
                    <div>
                        <div class="card-title-row">
                            <h2 class="coolveticaa" style="font-size: 1.2rem;">Rarity</h2>
                            <button class="btn-add-green">+ Add Rarity</button>
                        </div>
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Rarity Name</th>
                                    <th>Rarity ID</th>
                                    <th>Game</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for($i=0; $i<3; $i++): ?>
                                <tr>
                                    <td>SR</td>
                                    <td>RRT-001</td>
                                    <td>Pokemon</td>
                                    <td>Active</td>
                                    <td>
                                        <div class="btn-action-group">
                                            <button class="btn-edit-icon" style="background-color: #F39C12; border:none; padding:5px; border-radius:5px; color:white;">✏️</button>
                                            <button class="btn-delete-icon" style="background-color: #E74C3C; border:none; padding:5px; border-radius:5px; color:white;">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-container">
                        <span class="page-num"> < </span>
                        <span class="page-num">1</span>
                        <span class="page-num active">2</span>
                        <span class="page-num"> > </span>
                    </div>
                </div>

            </div> <!-- End Master Wrapper -->
        </div> <!-- End Main Content -->
    </div>
</body>
</html>