<?php
session_start();
require_once '../cardhaven/connection.php';
require_once 'components/fetch_dashboard.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Super Admin</title>
</head>
<body>
    <!-- <div class="container" style="justify-content: flex-start; align-items: flex-start;"> -->
        <!-- <div class="sideBar">
            </* ?php  include '../CardHaven/interface/dashboard/sideBar.php';  ? */> 
        </div> -->

        <div class="main-content">
            <h1 class="coolveticaa" style="color: var(--primary-color);font-size: 1.5rem;font-weight: 700;">Dashboard / Product</h1>
            <div class="content-card">
            <div class="card-title-row">
                <h2 class="coolveticaa">Products</h2>
                <button class="btn-add-green" onclick="openAddProductModal()">+ Add Product</button>
            </div>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Product Name</th>
                        <th>Game</th>
                        <th>Product Type</th>
                        <th>Stock</th>
                        <th>Price</th>
                        <th>status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (sqlsrv_has_rows($stmt_produk)): ?>
                        <?php 
                            $no = $offset_produk + 1;
                            while ($row = sqlsrv_fetch_array($stmt_produk, SQLSRV_FETCH_ASSOC)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td style="font-weight: 600; text-align: left;">
                                <?= htmlspecialchars($row['nama_produk']) ?>
                            </td>
                            <td><?= htmlspecialchars($row['nama_game'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['tipe_produk'] ?? '-') ?></td>
                            <td style="text-align: right;"><?= (int)$row['stok'] ?></td>
                            <td style="font-weight: bold; text-align: right;">
                                Rp<?= number_format($row['harga_jual'], 2, ',', '.') ?>
                            </td>
                            <td>
                                <?php if ($row['status'] == 1): ?>
                                    <span style="color: #27AE60; font-weight: bold;">Active</span>
                                <?php else: ?>
                                    <span style="color: #E74C3C; font-weight: bold;">Inactive</span>
                                <?php endif; ?>
                            </td>
                                <td>
                                    <div class="btn-action-group">
                                        <button class="btn-view-icon" onclick="openDetailProductModal(<?= $row['id_produk'] ?>)">...</button>
                                        <button class="btn-edit-icon" onclick="openEditProductModal(<?= $row['id_produk'] ?>)">✏️</button>
                                        <label class="switch">
                                            <input type="checkbox" 
                                                <?= ($row['status'] == 1) ? 'checked' : '' ?> 
                                                onchange="toggleProductStatus(<?= $row['id_produk'] ?>, this.checked, this)">
                                            <span class="slider"></span>
                                        </label>
                                        <button class="btn-delete-icon" onclick="confirmDeleteProduct(<?= $row['id_produk'] ?>)">🗑️</button>
                                    </div>
                                </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8">No products found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- PAGINATION PRODUK -->
            <div class="pagination-container">
                <!-- Arrow Back -->
                <?php if ($page_produk > 1): ?>
                    <a href="?pp=<?= $page_produk-1 ?>&pg=<?= $page_game ?>&ps=<?= $page_set ?>&pr=<?= $page_rarity ?>" class="page-link">&lt;</a>
                <?php else: ?>
                    <span class="page-link disabled">&lt;</span>
                <?php endif; ?>

                <?php
                $range = 2; // Jumlah angka di kiri & kanan halaman aktif
                
                // Halaman Pertama & Dots
                if ($page_produk > ($range + 2)) {
                    echo '<a href="?pp=1&pg='.$page_game.'&ps='.$page_set.'&pr='.$page_rarity.'" class="page-link">1</a><span class="dots">...</span>';
                } elseif ($page_produk > $range + 1) {
                    echo '<a href="?pp=1&pg='.$page_game.'&ps='.$page_set.'&pr='.$page_rarity.'" class="page-link">1</a>';
                }

                // Loop Angka Halaman
                for ($i = max(1, $page_produk - $range); $i <= min($total_pages_produk, $page_produk + $range); $i++) {
                    $active = ($i == $page_produk) ? 'active' : '';
                    echo '<a href="?pp='.$i.'&pg='.$page_game.'&ps='.$page_set.'&pr='.$page_rarity.'" class="page-link '.$active.'">'.$i.'</a>';
                }

                // Dots & Halaman Terakhir
                if ($page_produk < ($total_pages_produk - $range - 1)) {
                    echo '<span class="dots">...</span><a href="?pp='.$total_pages_produk.'&pg='.$page_game.'&ps='.$page_set.'&pr='.$page_rarity.'" class="page-link">'.$total_pages_produk.'</a>';
                } elseif ($page_produk < $total_pages_produk - $range) {
                    echo '<a href="?pp='.$total_pages_produk.'&pg='.$page_game.'&ps='.$page_set.'&pr='.$page_rarity.'" class="page-link">'.$total_pages_produk.'</a>';
                }
                ?>

                <!-- Arrow Next -->
                <?php if ($page_produk < $total_pages_produk): ?>
                    <a href="?pp=<?= $page_produk+1 ?>&pg=<?= $page_game ?>&ps=<?= $page_set ?>&pr=<?= $page_rarity ?>" class="page-link">&gt;</a>
                <?php else: ?>
                    <span class="page-link disabled">&gt;</span>
                <?php endif; ?>
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

                <div class="master-table-card">
                    <?php include 'components/metode_card.php'; ?>
                    </div>
            </div>
        </div>
    <!-- </div> -->

    <?php include 'components/modal.php'; ?>

    <!-- PENGGUNAAN TRIK CACHE BUSTING (?v=waktu_saat_ini) -->
    <script src="/cardhaven/interface/product/produk_script.js?v=<?= time() ?>"></script>
    <script src="/cardhaven/interface/product/set_script.js?v=<?= time() ?>"></script>
    <script src="/cardhaven/interface/product/rarity_script.js?v=<?= time() ?>"></script>
    <script src="/cardhaven/interface/product/game_script.js?v=<?= time() ?>"></script>
    <script src="/cardhaven/interface/product/metode_script.js?v=<?= time() ?>"></script>
</body>
</html>