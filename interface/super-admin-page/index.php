<?php
session_start();
require_once '../../connection.php'; 

$dummy_products = array_fill(0, 7, [
    'name' => 'Rayquaza V',
    'id' => '#CRD-1003',
    'game' => 'Pokemon',
    'set' => 'Scarlet and Violet Primastic',
    'stock' => 82,
    'condition' => 'NM',
    'price' => '$10.22'
]);

// Pagination Logic
$limit = 3; 
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$page = ($page < 1) ? 1 : $page;
$offset = ($page - 1) * $limit;

// Count Total
$sql_count = "SELECT COUNT(*) as total FROM dbo.game";
$stmt_count = sqlsrv_query($conn, $sql_count);
$total_rows = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch Data
$sql_game = "SELECT * FROM dbo.game ORDER BY aktif DESC, id_game ASC 
            OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
$stmt_game = sqlsrv_query($conn, $sql_game);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Super Admin</title>
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
</head>
<body>
    <div class="container" style="justify-content: flex-start; align-items: flex-start;">
        <div class="sideBar">
            <?php include 'components/sideBar.php' ?>
        </div>
        
        <div class="main-content">
            
            <!-- 1. TABEL PRODUK (Fixed Height for 7 Rows) -->
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
                        <?php foreach ($dummy_products as $item): ?>
                        <tr>
                            <td style="color: #4A90E2;"><?= $item['name'] ?></td>
                            <td><?= $item['id'] ?></td>
                            <td style="color: #4A90E2;"><?= $item['game'] ?></td>
                            <td><?= $item['set'] ?></td>
                            <td><?= $item['stock'] ?></td>
                            <td><?= $item['condition'] ?></td>
                            <td style="color: #4A90E2; font-weight: bold;"><?= $item['price'] ?></td>
                            <td>
                                <div class="btn-action-group">
                                    <button class="btn-edit-icon" style="background-color: #F39C12; border:none; padding:5px; border-radius:5px; color:white;">✏️</button>
                                    <button class="btn-delete-icon" style="background-color: #E74C3C; border:none; padding:5px; border-radius:5px; color:white;">🗑️</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
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

            <!-- 2. WRAPPER MASTER DATA (Pastikan Class Ini Ada) -->
            <div class="master-data-wrapper">
                
                <!-- TABEL GAME -->
                <div class="master-table-card">
                    <div style="flex: 1;">
                        <div class="card-title-row">
                            <h2 class="coolveticaa" style="font-size: 1.2rem;">Game</h2>
                            <button class="btn-add-green" onclick="openAddModal()">+ Add Game</button>
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
                                <?php while ($row = sqlsrv_fetch_array($stmt_game, SQLSRV_FETCH_ASSOC)): ?>
                                <tr>
                                    <td style="color: #4A90E2;"><?= htmlspecialchars($row['nama_game']) ?></td>
                                    <td>GAM-<?= str_pad($row['id_game'], 3, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= htmlspecialchars($row['developer']) ?></td>
                                    <td style="color: #4A90E2; font-weight: bold;">
                                        <?= $row['aktif'] == 1 ? 'Active' : 'Inactive' ?>
                                    </td>
                                    <td>
                                        <div class="btn-action-group">
                                            <button class="btn-edit-icon" onclick="openEditModal(<?= $row['id_game'] ?>)">✏️</button>
                                            <button class="btn-delete-icon" onclick="confirmDelete(<?= $row['id_game'] ?>)">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-container">
                        <a href="?p=<?= max(1, $page-1) ?>" class="page-link"> < </a>
                        <?php for($i=1; $i<=$total_pages; $i++): ?>
                            <a href="?p=<?= $i ?>" class="page-link <?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <a href="?p=<?= min($total_pages, $page+1) ?>" class="page-link"> > </a>
                    </div>
                </div>

                <!-- TABEL SET -->
                <div class="master-table-card">
                    <div style="flex: 1;">
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
                                <tr>
                                    <td>Scarlet & Violet Primastic</td>
                                    <td>SET-001</td>
                                    <td style="color: #4A90E2;">Pokemon</td>
                                    <td style="color: #4A90E2; font-weight: bold;">Active</td>
                                    <td>
                                        <div class="btn-action-group">
                                            <button class="btn-edit-icon">✏️</button>
                                            <button class="btn-delete-icon">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-container">
                        <span class="page-num"><</span> <span class="page-num active">1</span> <span class="page-num">></span>
                    </div>
                </div>

                <!-- TABEL RARITY -->
                <div class="master-table-card">
                    <div style="flex: 1;">
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
                                <tr>
                                    <td>Super Rare</td>
                                    <td>RAR-001</td>
                                    <td style="color: #4A90E2;">Pokemon</td>
                                    <td style="color: #4A90E2; font-weight: bold;">Active</td>
                                    <td>
                                        <div class="btn-action-group">
                                            <button class="btn-edit-icon">✏️</button>
                                            <button class="btn-delete-icon">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-container">
                        <span class="page-num"><</span> <span class="page-num active">1</span> <span class="page-num">></span>
                    </div>
                </div>

            </div> <!-- END OF master-data-wrapper -->

        </div> <!-- End Main Content -->
    </div>
    <div id="gameModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="modalTitle">ADD <span class="blue-text">GAME</span></h2>
                <span id="displayID" class="game-id"></span>
            </div>
            <form id="gameForm">
                <input type="hidden" name="action" id="formAction">
                <input type="hidden" name="id_game" id="formID">
                
                <div class="modal-form-group">
                    <label>Game Name</label>
                    <input type="text" name="nama_game" id="nama_game" class="modal-input" placeholder="Enter Game Name..." required>
                </div>
                <div class="modal-form-group">
                    <label>Dev Name</label>
                    <input type="text" name="developer" id="developer" class="modal-input" placeholder="Enter Developer Name..." required>
                </div>

                <div id="logSection" style="display:none;">
                    <div class="modal-form-group"><label>Created By</label>
                        <div class="log-display"><span id="createdBy"></span><span id="createdDate"></span></div>
                    </div>
                    <div class="modal-form-group"><label>Edited By</label>
                        <div class="log-display"><span id="editedBy"></span><span id="editedDate"></span></div>
                    </div>
                    <div class="status-text">
                        This game status is currently <span id="statusLabel">Active</span>
                        <input type="hidden" name="aktif" id="aktifStatus">
                    </div>
                </div>
                <button type="submit" class="btn-confirm">Confirm</button>
            </form>
        </div>
    </div>
    <script src="game_script.js"></script>
</body>
</html>