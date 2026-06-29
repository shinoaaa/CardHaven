<?php
/**
 * interface/product-detail/index.php
 * Halaman Product Detail
 */
$pageTitle = 'Product Detail – CardHaven';
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
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <script src="/cardhaven/interface/global_alert.js"></script>

    <!-- Product Detail CSS -->
    <link rel="stylesheet" href="/cardhaven/interface/product-detail/assets/css/style.css">
</head>
<body>
    <!-- Navbar Component (Placeholder sesuai permintaan) -->
    <!-- <?php include __DIR__ . '/../components/navBar.php'; ?> -->

    <div class="pd-container">
        <!-- Main Product Section -->
        <div class="pd-main-section">
            <!-- Left: Image -->
            <div class="pd-image-box">
                <img id="detailFoto" src="/cardhaven/assets/image/placeholder.png" alt="Product Image">
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
                        <button onclick="updateQty(-1)">−</button>
                        <span id="qtyValue">1</span>
                        <button onclick="updateQty(1)">+</button>
                    </div>
                </div>

                <div class="pd-actions">
                    <button class="btn-add-cart" onclick="addToCart()">Add To Cart</button>
                    <!-- Tombol checkout product sesuai instruksi -->
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
                <!-- Related products will be injected here via JS -->
            </div>

            <div class="related-pagination">
                <button class="page-arrow" onclick="prevRelatedPage()">‹</button>
                <button class="page-arrow" onclick="nextRelatedPage()">›</button>
            </div>
        </div>
    </div>

    <!-- Script -->
    <script src="/cardhaven/interface/product-detail/assets/js/script.js"></script>
</body>
</html>