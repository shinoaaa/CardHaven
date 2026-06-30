<?php
/**
 * interface/product-detail/index.php
 * Halaman Product Detail
 */
$pageTitle = 'Product Detail – CardHaven';

// Penyesuaian XAMPP: Folder project utama Anda di htdocs
$baseUrl = '/CardHaven'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Global CSS & JS Alert -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/interface/global.css">
    <script src="<?= $baseUrl ?>/interface/global_alert.js"></script>

    <!-- Product Detail CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/interface/product-detail/assets/css/style.css">
    
    <script>
        const BASE_URL = '<?= $baseUrl ?>';
    </script>
</head>
<body>
    <div style="height: 10rem;"></div>
    <div class="pd-container">
        <div class="pd-main-section">
            <div class="pd-image-box">
                <img id="detailFoto" src="<?= $baseUrl ?>/assets/image/products/placeholder.png" alt="Product Image">
            </div>

            <!-- Right: Details -->
            <div class="pd-info-box">
                <h1 id="detailNama">Loading...</h1>
                
                <div class="pd-specs">
                    <div class="spec-item">
                        <span class="spec-label">Stok:</span>
                        <span class="spec-value" id="detailStok">-</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Game:</span>
                        <span class="spec-value" id="detailGame">-</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Type:</span>
                        <span class="spec-value" id="detailType">-</span>
                    </div>
                </div>

                <div class="pd-condition">
                    <strong>Kondisi :</strong> <span id="detailKondisi">-</span>
                </div>

                <div class="pd-description">
                    <strong>Description:</strong>
                    <p id="detailDeskripsi">Loading description...</p>
                </div>

                <div class="pd-price-row">
                    <div class="pd-price">
                        <span class="price-label">Price:</span> 
                        <span class="price-value" id="detailHarga">-</span>
                    </div>
                    
                    <div class="pd-qty-selector">
                        <button id="subNumber" onclick="updateQty(-1)">−</button>
                        <span id="qtyValue">1</span>
                        <button id="addNumber" onclick="updateQty(1)">+</button>
                    </div>
                </div>

                <div class="pd-actions">
                    <button class="btn-add-cart" onclick="addToCart()">Add To Cart</button>
                    <button class="btn-checkout" onclick="checkoutProduct()">Checkout Product</button>
                </div>
            </div>
        </div>

        <!-- Related Product Section -->
        <div class="pd-related-section">
            <div class="related-header">
                <div class="line"></div>
                <h2>Related Product</h2>
                <div class="line"></div>
            </div>
            <div class="related-link-wrapper">
                <a href="#" class="see-all-link">See All Product</a>
            </div>

            <div class="related-grid" id="relatedGrid">
                <!-- Related products akan di-inject lewat JS -->
            </div>

            <div class="related-pagination">
                <button class="page-arrow" id="btnPrevRelated" onclick="prevRelatedPage()">‹</button>
                <button class="page-arrow" id="btnNextRelated" onclick="nextRelatedPage()">›</button>
            </div>
        </div>
        <?php include __DIR__ . '/../page-customer/footer.php' ?>
    </div>

    <!-- Script -->
    <script src="<?= $baseUrl ?>/interface/product-detail/assets/js/script.js"></script>
</body>
</html>