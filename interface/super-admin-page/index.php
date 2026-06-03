<?php
require_once '../cardhaven/connection.php'; 

// --- LOGIC PAGINATION GAME ---
$limit = 3; 
$page_game = isset($_GET['p_game']) ? (int)$_GET['p_game'] : 1;
if ($page_game < 1) $page_game = 1;
$offset = ($page_game - 1) * $limit;

// 1. Hitung total data untuk pagination
$sql_count = "SELECT COUNT(*) as total FROM dbo.game";
$stmt_count = sqlsrv_query($conn, $sql_count);
$row_count = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC);
$total_rows = $row_count['total'];
$total_pages = ceil($total_rows / $limit);

$sql_game = "SELECT * FROM dbo.game 
            ORDER BY aktif DESC, id_game ASC 
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
    <script>
        (function() {
            const token = localStorage.getItem("token") || sessionStorage.getItem("token");
            const role = localStorage.getItem("role") || sessionStorage.getItem("role");
            
            // Jika token kosong ATAU role bukan 1 (Superadmin), tendang!
            if (!token || role !== "1") {
                window.location.replace("/CardHaven");
            }
        })();
    </script>
</head>
<body>
    <div class="container" style="justify-content: flex-start; align-items: flex-start;">
        <div class="sideBar">
            <?php include 'components/sideBar.php' ?>
        </div>
        
        <div class="main-content">
            
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
                        <?php 
                        // Loop data produk (Ganti dengan fetch dari mysqli/sqlsrv nanti)
                        // Catatan: Pastikan variabel $products sudah didefinisikan sebelumnya agar tidak error notice
                        for($i=0; $i<7; $i++): 
                            $item = isset($products[0]) ? $products[0] : ['name'=>'-','pid'=>'-','game'=>'-','set'=>'-','stock'=>'-','cond'=>'-','price'=>'-']; 
                        ?>
                        <tr>
                            <td><?= $item['name'] ?></td>
                            <td><?= $item['pid'] ?></td>
                            <td><?= $item['game'] ?></td>
                            <td><?= $item['set'] ?></td>
                            <td><?= $item['stock'] ?></td>
                            <td><?= $item['cond'] ?></td>
                            <td><?= $item['price'] ?></td>
                            <td>
                                <div class="btn-action-group">
                                    <button class="btn-edit-icon">✏️</button>
                                    <button class="btn-delete-icon">🗑️</button>
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

            <div class="master-data-wrapper">
                
                <div class="master-table-card">
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
                            <?php 
                            if ($stmt_game):
                                while ($row = sqlsrv_fetch_array($stmt_game, SQLSRV_FETCH_ASSOC)): 
                                    $statusText = ($row['aktif'] == 1) ? 'Active' : 'Inactive';
                                    $statusColor = ($row['aktif'] == 1) ? '#27AE60' : '#E74C3C';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nama_game']) ?></td>
                                <td><?= htmlspecialchars($row['id_game']) ?></td>
                                <td><?= htmlspecialchars($row['developer']) ?></td>
                                <td style="color: <?= $statusColor ?>; font-weight: bold;"><?= $statusText ?></td>
                                <td>
                                    <div class="btn-action-group">
                                        <button class="btn-edit-icon" onclick="openEditModal(<?= $row['id_game'] ?>)">✏️</button>
                                        <button class="btn-delete-icon" onclick="confirmDelete(<?= $row['id_game'] ?>)">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            endif; 
                            ?>
                        </tbody>
                    </table>
                    
                    <div class="pagination-container">
                        <a href="?p_game=<?= max(1, $page_game - 1) ?>" class="page-num"> < </a>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?p_game=<?= $i ?>" class="page-num <?= ($i == $page_game) ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        <a href="?p_game=<?= min($total_pages, $page_game + 1) ?>" class="page-num"> > </a>
                    </div>
                </div>

                <div id="gameModal" class="modal-overlay">
                    <div class="modal-box">
                        <div class="modal-header">
                            <h2 id="modalTitle">ADD <span class="blue-text">GAME</span></h2>
                            <span id="displayID" class="game-id"></span>
                        </div>
                        
                        <form id="gameForm">
                            <input type="hidden" name="action" id="formAction" value="add">
                            <input type="hidden" name="id_game" id="formID">

                            <div class="modal-form-group">
                                <label>Game Name</label>
                                <input type="text" name="nama_game" id="nama_game" class="modal-input" placeholder="Enter Game Name..." required>
                            </div>

                            <div class="modal-form-group">
                                <label id="labelDev">Dev Name</label>
                                <input type="text" name="developer" id="developer" class="modal-input" placeholder="Enter Developer Name..." required>
                            </div>

                            <div id="logSection" style="display:none;">
                                <div class="modal-form-group">
                                    <label>Created By</label>
                                    <div class="log-display">
                                        <span id="createdBy"></span>
                                        <span id="createdDate"></span>
                                    </div>
                                </div>
                                <div class="modal-form-group">
                                    <label>Edited By</label>
                                    <div class="log-display">
                                        <span id="editedBy"></span>
                                        <span id="editedDate"></span>
                                    </div>
                                </div>
                                <div class="status-text">
                                    This game status is currently <span id="statusLabel"></span>
                                    <input type="hidden" name="aktif" id="aktifStatus">
                                </div>
                            </div>

                            <button type="submit" class="btn-confirm">Confirm</button>
                        </form>
                    </div>
                </div>

                <div class="master-table-card">
    <div class="card-title-row">
        <h2 class="coolveticaa" style="font-size: 1.2rem;">Set</h2>
        <button class="btn-add-green" onclick="openAddSetModal()">+ Add Set</button>
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
        <tbody id="setTableBody">
            <tr><td colspan="5" style="text-align:center;color:#888;">Loading...</td></tr>
        </tbody>
    </table>
    <div class="pagination-container" id="setPaginationContainer"></div>
</div>

<!-- MODAL SET -->
<div id="setModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2 id="setModalTitle">ADD <span class="blue-text">SET</span></h2>
            <span id="setDisplayID" class="game-id"></span>
        </div>

        <form id="setForm">
            <input type="hidden" name="action"  id="setFormAction" value="add">
            <input type="hidden" name="id_set"  id="setIdInput">

            <div class="modal-form-group">
                <label>Set Name</label>
                <input type="text" name="nama_set" id="setNama" class="modal-input" placeholder="Enter Set Name..." required>
            </div>

            <div class="modal-form-group">
                <label>Set Code</label>
                <input type="text" name="kode_set" id="setKode" class="modal-input" placeholder="e.g. SV-01" required>
            </div>

            <div class="modal-form-group">
                <label>Game</label>
                <select name="id_game" id="setGameId" class="modal-input" required>
                    <option value="">-- Pilih Game --</option>
                </select>
            </div>

            <div class="modal-form-group">
                <label>Release Date <span style="color:#888;font-size:0.85em;">(opsional)</span></label>
                <input type="date" name="tanggal_rilis" id="setTanggal" class="modal-input">
            </div>

            <div id="setLogSection" style="display:none;">
                <div class="modal-form-group">
                    <label>Created By</label>
                    <div class="log-display">
                        <span id="setCreatedBy"></span>
                        <span id="setCreatedDate"></span>
                    </div>
                </div>
                <div class="modal-form-group">
                    <label>Edited By</label>
                    <div class="log-display">
                        <span id="setEditedBy"></span>
                        <span id="setEditedDate"></span>
                    </div>
                </div>
                <div class="status-text">
                    This set status is currently <span id="setStatusLabel"></span>
                    <input type="hidden" name="aktif" id="setAktifStatus">
                </div>
            </div>

            <button type="submit" class="btn-confirm">Confirm</button>
        </form>
    </div>
</div>
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
                                <tr>
                                    <td>Super Rare</td>
                                    <td>RAR-001</td>
                                    <td>Pokemon</td>
                                    <td>Active</td>
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
                        <span class="page-num"> < </span>
                        <span class="page-num">1</span>
                        <span class="page-num active">2</span>
                        <span class="page-num"> > </span>
                    </div>
                </div>

            </div> </div> </div>
    <script src="/cardhaven/interface/super-admin-page/game_script.js"></script>
<script src="/cardhaven/interface/super-admin-page/set_script.js"></script>
</body>
</html>