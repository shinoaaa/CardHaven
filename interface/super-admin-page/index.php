<?php
session_start();
require_once '../cardhaven/connection.php';

$dummy_products = array_fill(0, 7, [
    'name' => 'Rayquaza V',
    'id' => '#CRD-1003',
    'game' => 'Pokemon',
    'set' => 'Scarlet and Violet Primastic',
    'stock' => 82,
    'condition' => 'NM',
    'price' => '$10.22'
]);

// Pagination Logic Game
$limit = 3;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$page = ($page < 1) ? 1 : $page;
$offset = ($page - 1) * $limit;

$sql_count = "SELECT COUNT(*) as total FROM dbo.game";
$stmt_count = sqlsrv_query($conn, $sql_count);
if ($stmt_count === false) {
    die(print_r(sqlsrv_errors(), true));
}
$total_rows = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC)['total'] ?? 0;
$total_pages = max(1, ceil($total_rows / $limit));

$sql_game = "SELECT * FROM dbo.game
            ORDER BY aktif DESC, id_game ASC
            OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
$stmt_game = sqlsrv_query($conn, $sql_game);
if ($stmt_game === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Pagination Logic Set
$limit_s = 3;
$page_s = isset($_GET['ps']) ? (int)$_GET['ps'] : 1;
$page_s = ($page_s < 1) ? 1 : $page_s;
$offset_s = ($page_s - 1) * $limit_s;

$sql_count_s = "SELECT COUNT(*) as total FROM dbo.set_kartu";
$stmt_count_s = sqlsrv_query($conn, $sql_count_s);
if ($stmt_count_s === false) {
    die(print_r(sqlsrv_errors(), true));
}
$total_rows_s = sqlsrv_fetch_array($stmt_count_s, SQLSRV_FETCH_ASSOC)['total'] ?? 0;
$total_pages_s = max(1, ceil($total_rows_s / $limit_s));

$sql_set = "SELECT s.id_set,s.nama_set,s.kode_set,s.tanggal_rilis,s.aktif, g.nama_game
            FROM dbo.set_kartu s
            LEFT JOIN dbo.game g ON s.id_game = g.id_game
            ORDER BY s.aktif DESC, s.id_set ASC
            OFFSET $offset_s ROWS FETCH NEXT $limit_s ROWS ONLY";
$stmt_set = sqlsrv_query($conn, $sql_set);
if ($stmt_set === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Pagination Logic Rarity
$limit_r = 3;
$page_r = isset($_GET['pr']) ? (int)$_GET['pr'] : 1;
$page_r = ($page_r < 1) ? 1 : $page_r;
$offset_r = ($page_r - 1) * $limit_r;

$sql_count_r = "SELECT COUNT(*) as total FROM dbo.rarity";
$stmt_count_r = sqlsrv_query($conn, $sql_count_r);
if ($stmt_count_r === false) {
    die(print_r(sqlsrv_errors(), true));
}
$total_rows_r = sqlsrv_fetch_array($stmt_count_r, SQLSRV_FETCH_ASSOC)['total'] ?? 0;
$total_pages_r = max(1, ceil($total_rows_r / $limit_r));

$sql_rarity = "SELECT r.id_rarity, r.nama_rarity, r.kode_rarity, r.aktif, g.nama_game
                FROM dbo.rarity r
                LEFT JOIN dbo.game g ON r.id_game = g.id_game
                ORDER BY r.aktif DESC, r.id_rarity ASC
                OFFSET $offset_r ROWS FETCH NEXT $limit_r ROWS ONLY";
$stmt_rarity = sqlsrv_query($conn, $sql_rarity);
if ($stmt_rarity === false) {
    die(print_r(sqlsrv_errors(), true));
}
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
            if (!token || role !== "1") {
                window.location.replace("/CardHaven");
            }
        })();
    </script>
</head>
<body>
    <div class="container" style="justify-content: flex-start; align-items: flex-start;">
        <div class="sideBar">
            <?php include 'components/sideBar.php'; ?>
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
                        <?php foreach ($dummy_products as $item): ?>
                        <tr>
                            <td style="color: #4A90E2;"><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= htmlspecialchars($item['id']) ?></td>
                            <td style="color: #4A90E2;"><?= htmlspecialchars($item['game']) ?></td>
                            <td><?= htmlspecialchars($item['set']) ?></td>
                            <td><?= htmlspecialchars($item['stock']) ?></td>
                            <td><?= htmlspecialchars($item['condition']) ?></td>
                            <td style="color: #4A90E2; font-weight: bold;"><?= htmlspecialchars($item['price']) ?></td>
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
                    <span class="page-num">&lt;</span>
                    <span class="page-num">1</span>
                    <span class="page-num active">2</span>
                    <span class="page-num">3</span>
                    <span class="page-num">...</span>
                    <span class="page-num">82</span>
                    <span class="page-num">&gt;</span>
                </div>
            </div>

            <div class="master-data-wrapper">
                <?php include 'components/game_card.php'; ?>

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
                    <?php include 'components/set_card.php'; ?>
                </div>

                <div class="master-table-card">
                    <?php include 'components/rarity_card.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="rarityModal" class="rarity-modal-overlay">
        <div class="rarity-modal-box">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2 id="modalTitleRarity" class="coolveticaa" style="margin: 0; font-size: 24px;">ADD <span class="blue-text">RARITY</span></h2>
                <span id="displayIDRarity" class="game-id" style="font-weight: bold; color: #7F8C8D;"></span>
            </div>

            <form id="rarityForm">
                <input type="hidden" name="action" id="formActionRarity">
                <input type="hidden" name="id_rarity" id="inputIdRarity">

                <div class="rarity-form-group">
                    <label class="rarity-form-label">Game</label>
                    <select id="inputGameRarity" name="id_game" required class="rarity-form-input">
                        <option value="">-- Select Game --</option>
                        <?php
                        $sql_dropdown = "SELECT id_game, nama_game FROM dbo.game WHERE aktif = 1 ORDER BY nama_game ASC";
                        $stmt_dropdown = sqlsrv_query($conn, $sql_dropdown);
                        if ($stmt_dropdown) {
                            while ($rowGame = sqlsrv_fetch_array($stmt_dropdown, SQLSRV_FETCH_ASSOC)) {
                                echo '<option value="' . htmlspecialchars($rowGame['id_game']) . '">' . htmlspecialchars($rowGame['nama_game']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="rarity-form-group">
                    <label class="rarity-form-label">Rarity Name</label>
                    <input type="text" id="inputNamaRarity" name="nama_rarity" class="rarity-form-input" required>
                </div>

                <div class="rarity-form-group">
                    <label class="rarity-form-label">Rarity Code</label>
                    <input type="text" id="inputKodeRarity" name="kode_rarity" class="rarity-form-input">
                </div>

                <div id="logSectionRarity" style="display:none; margin-top: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <label style="font-size: 13px; font-weight: 600;">Created By</label>
                        <div style="font-size: 13px;">
                            <span id="createdByRarity" style="color:#2980b9;"></span>
                            <span id="createdDateRarity"></span>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <label style="font-size: 13px; font-weight: 600;">Edited By</label>
                        <div style="font-size: 13px;">
                            <span id="editedByRarity" style="color:#2980b9;"></span>
                            <span id="editedDateRarity"></span>
                        </div>
                    </div>
                    <div style="text-align: right; font-size: 13px; font-weight: 600;">
                        Status: <span id="statusLabelRarity">Active</span>
                        <input type="hidden" name="aktif" id="aktifStatusRarity">
                    </div>
                </div>

                <div class="rarity-action-group">
                    <button type="submit" class="rarity-btn-save">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <div id="setModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="setModalTitle">ADD <span class="blue-text">SET</span></h2>
                <span id="setDisplayID" class="game-id"></span>
            </div>

            <form id="setForm">
                <input type="hidden" name="action" id="setFormAction" value="add">
                <input type="hidden" name="id_set" id="setIdInput">

                <div class="modal-form-group">
                    <label>Game</label>
                    <select name="id_game" id="setGameId" class="modal-input" required>
                        <option value="">-- Pilih Game --</option>
                        <?php
                        $sql_g = "SELECT id_game, nama_game FROM dbo.game WHERE aktif = 1 ORDER BY nama_game ASC";
                        $res_g = sqlsrv_query($conn, $sql_g);
                        if ($res_g) {
                            while ($g = sqlsrv_fetch_array($res_g, SQLSRV_FETCH_ASSOC)) {
                                echo "<option value='" . htmlspecialchars($g['id_game']) . "'>" . htmlspecialchars($g['nama_game']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="modal-form-group">
                    <label>Set Name</label>
                    <input type="text" name="nama_set" id="setNama" class="modal-input" placeholder="Enter Set Name..." required>
                </div>

                <div class="modal-form-group">
                    <label>Set Code</label>
                    <input type="text" name="kode_set" id="setKode" class="modal-input" placeholder="e.g. SV-01" required>
                </div>
                <div class="modal-form-group">
                    <label>Release Date</label>
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
                        Status: <span id="setStatusLabel"></span>
                        <input type="hidden" name="aktif" id="setAktifStatus">
                    </div>
                </div>

                <button type="submit" class="btn-confirm">Confirm</button>
            </form>
        </div>
    </div>

    <script src="/cardhaven/interface/super-admin-page/set_script.js"></script>
    <script src="/cardhaven/interface/super-admin-page/rarity_script.js"></script>
    <script src="/cardhaven/interface/super-admin-page/game_script.js"></script>
</body>
</html>