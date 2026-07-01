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
    <button id="scrollBottomBtn" class="scroll-bottom-btn" title="Scroll to Bottom">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <polyline points="19 12 12 19 5 12"></polyline>
        </svg>
    </button>
    <div class="main-content">
        <h1 class="coolveticaa" style="color: #a0beff;font-size: 1.5rem;font-weight: 700;">Dashboard / Product</h1>
        <div class="content-card" id="container-produk">
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
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data_produk)): ?>
                        <?php 
                            $no = $offset_produk + 1;
                            foreach ($data_produk as $row): ?>
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
                                    <button class="btn-edit-icon" onclick="openEditProductModal(<?= $row['id_produk'] ?>)"><img src="/cardhaven/assets/image/edit.svg" alt=""></button>
                                    <button class="btn-delete-icon" onclick="confirmDeleteProduct(<?= $row['id_produk'] ?>)"><img src="/cardhaven/assets/image/delete.svg" alt=""></button>
                                    <label class="switch">
                                        <input type="checkbox" 
                                            <?= ($row['status'] == 1) ? 'checked' : '' ?> 
                                            onchange="toggleProductStatus(<?= $row['id_produk'] ?>, this.checked, this)">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; color:#aaa; padding:20px;">No products found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="pagination-container">
                <?php if ($page_produk > 1): ?>
                    <a href="javascript:void(0)" onclick="loadProductPage(<?= $page_produk-1 ?>)" class="page-link">&lt;</a>
                <?php else: ?>
                    <span class="page-link disabled">&lt;</span>
                <?php endif; ?>

                <?php
                $range = 2; 
                
                // Halaman Pertama & Dots
                if ($page_produk > ($range + 2)) {
                    echo '<a href="javascript:void(0)" onclick="loadProductPage(1)" class="page-link">1</a><span class="dots">...</span>';
                } elseif ($page_produk > $range + 1) {
                    echo '<a href="javascript:void(0)" onclick="loadProductPage(1)" class="page-link">1</a>';
                }

                // Loop Angka Halaman
                for ($i = max(1, $page_produk - $range); $i <= min($total_pages_produk, $page_produk + $range); $i++) {
                    $active = ($i == $page_produk) ? 'active' : '';
                    echo '<a href="javascript:void(0)" onclick="loadProductPage('.$i.')" class="page-link '.$active.'">'.$i.'</a>';
                }

                // Dots & Halaman Terakhir
                if ($page_produk < ($total_pages_produk - $range - 1)) {
                    echo '<span class="dots">...</span><a href="javascript:void(0)" onclick="loadProductPage('.$total_pages_produk.')" class="page-link">'.$total_pages_produk.'</a>';
                } elseif ($page_produk < $total_pages_produk - $range) {
                    echo '<a href="javascript:void(0)" onclick="loadProductPage('.$total_pages_produk.')" class="page-link">'.$total_pages_produk.'</a>';
                }
                ?>

                <?php if ($page_produk < $total_pages_produk): ?>
                    <a href="javascript:void(0)" onclick="loadProductPage(<?= $page_produk+1 ?>)" class="page-link">&gt;</a>
                <?php else: ?>
                    <span class="page-link disabled">&gt;</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="master-data-wrapper">
            <div class="master-table-card" id="container-game">
                <?php include 'components/game_card.php'; ?>
            </div>

            <div class="master-table-card" id="container-set">
                <?php include 'components/set_card.php'; ?>
            </div>

            <div class="master-table-card" id="container-rarity">
                <?php include 'components/rarity_card.php'; ?>
            </div>

            <div class="master-table-card" id="container-metode">
                <?php include 'components/metode_card.php'; ?>
            </div>
        </div>
    </div>

    <?php include 'components/modal.php'; ?>

    <script src="/cardhaven/interface/product/produk_script.js?v=<?= time() ?>"></script>
    <script src="/cardhaven/interface/product/set_script.js?v=<?= time() ?>"></script>
    <script src="/cardhaven/interface/product/rarity_script.js?v=<?= time() ?>"></script>
    <script src="/cardhaven/interface/product/game_script.js?v=<?= time() ?>"></script>
    <script src="/cardhaven/interface/product/metode_script.js?v=<?= time() ?>"></script>
</body>
</html>