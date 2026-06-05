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
$limit_game = 3;
$page_game = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;
$page_game = ($page_game < 1) ? 1 : $page_game;
$offset_game = ($page_game - 1) * $limit_game;

$sql_count_game = "SELECT COUNT(*) as total FROM dbo.game";
$stmt_count_game = sqlsrv_query($conn, $sql_count_game);
if ($stmt_count_game === false) {
    die(print_r(sqlsrv_errors(), true));
}
$total_rows_game = sqlsrv_fetch_array($stmt_count_game, SQLSRV_FETCH_ASSOC)['total'] ?? 0;
$total_pages_game = max(1, ceil($total_rows_game / $limit_game));

$sql_game = "SELECT * FROM dbo.game
            ORDER BY aktif DESC, id_game ASC
            OFFSET $offset_game ROWS FETCH NEXT $limit_game ROWS ONLY";
$stmt_game = sqlsrv_query($conn, $sql_game);
if ($stmt_game === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Pagination Logic Set
$limit_set = 3;
$page_set = isset($_GET['ps']) ? (int)$_GET['ps'] : 1;
$page_set = ($page_set < 1) ? 1 : $page_set;
$offset_set = ($page_set - 1) * $limit_set;

$sql_count_set = "SELECT COUNT(*) as total FROM dbo.set_kartu";
$stmt_count_set = sqlsrv_query($conn, $sql_count_set);
if ($stmt_count_set === false) {
    die(print_r(sqlsrv_errors(), true));
}
$total_rows_set = sqlsrv_fetch_array($stmt_count_set, SQLSRV_FETCH_ASSOC)['total'] ?? 0;
$total_pages_set = max(1, ceil($total_rows_set / $limit_set));

$sql_set = "SELECT s.id_set,s.nama_set,s.kode_set,s.tanggal_rilis,s.aktif, g.nama_game
            FROM dbo.set_kartu s
            LEFT JOIN dbo.game g ON s.id_game = g.id_game
            ORDER BY s.aktif DESC, s.id_set ASC
            OFFSET $offset_set ROWS FETCH NEXT $limit_set ROWS ONLY";
$stmt_set = sqlsrv_query($conn, $sql_set);
if ($stmt_set === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Pagination Logic Rarity
$limit_rarity = 3;
$page_rarity = isset($_GET['pr']) ? (int)$_GET['pr'] : 1;
$page_rarity = ($page_rarity < 1) ? 1 : $page_rarity;
$offset_rarity = ($page_rarity - 1) * $limit_rarity;

$sql_count_rarity = "SELECT COUNT(*) as total FROM dbo.rarity";
$stmt_count_rarity = sqlsrv_query($conn, $sql_count_rarity);
if ($stmt_count_rarity === false) {
    die(print_r(sqlsrv_errors(), true));
}
$total_rows_rarity = sqlsrv_fetch_array($stmt_count_rarity, SQLSRV_FETCH_ASSOC)['total'] ?? 0;
$total_pages_rarity = max(1, ceil($total_rows_rarity / $limit_rarity));

$sql_rarity = "SELECT r.id_rarity, r.nama_rarity, r.kode_rarity, r.aktif, g.nama_game
                FROM dbo.rarity r
                LEFT JOIN dbo.game g ON r.id_game = g.id_game
                ORDER BY r.aktif DESC, r.id_rarity ASC
                OFFSET $offset_rarity ROWS FETCH NEXT $limit_rarity ROWS ONLY";
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
                <div class="master-table-card">
                    <?php include 'components/game_card.php'; ?>
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

    <?php include 'components/modal.php'; ?>

    <script src="/cardhaven/interface/super-admin-page/set_script.js"></script>
    <script src="/cardhaven/interface/super-admin-page/rarity_script.js"></script>
    <script src="/cardhaven/interface/super-admin-page/game_script.js"></script>
</body>
</html>