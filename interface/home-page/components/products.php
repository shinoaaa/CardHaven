<section class="section-padding">
    <h2 class="coolveticaa section-title" style="color: #0F3891;">Explore Our Products</h2>
    <div class="filter-bar">
        <div class="filter-item">Min Price</div>
        <div class="filter-item">Max Price</div>
        <div class="filter-item">Category Item</div>
        <div class="filter-item">Game</div>
        <div class="filter-btn">Confirm</div>
    </div>
    
    <div class="horizontal-slider draggable-slider" id="productSlider">
        <?php 
        $productPages = array_chunk($products, 4); 
        foreach ($productPages as $pageData): 
        ?>
        <div class="product-page-grid">
            <?php foreach ($pageData as $prod): ?>
            <div class="product-card">
                <div class="product-img-wrap">
                    <img src="<?= $prod['image_path'] ?>" class="product-img" alt="Product">
                </div>
                
                <div class="product-details">
                    <h3 class="coolveticaa"><?= htmlspecialchars($prod['nama_produk']) ?></h3>
                    <p class="price">Rp <?= number_format($prod['harga_jual'], 0, ',', '.') ?></p>
                    
                    <div class="product-meta">
                        <span class="stok">Stok: <?= htmlspecialchars($prod['stok'] ?? '0') ?></span>
                        <span class="game-tag">Game: <?= htmlspecialchars($prod['nama_game'] ?? 'N/A') ?></span>
                    </div>
                    
                    <p class="desc-micro">Deskripsi singkat produk akan dirender di sini untuk memberikan konteks kepada pengguna.</p>
                    
                    <div class="product-actions">
                        <button class="btn-check coolveticaa">Check Detail</button>
                        <button class="btn-addcart coolveticaa">Add to Cart</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>