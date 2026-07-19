<?php
/**
 * interface/catalogue/index.php
 */
$pageTitle = 'Catalogue – CardHaven';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="/cardhaven/assets/image/logo.svg">
    
    <!-- SweetAlert2 & Global Alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <script src="/cardhaven/interface/global_alert.js"></script>]

    <!-- Catalogue CSS -->
    <link rel="stylesheet" href="/cardhaven/interface/catalogue/assets/css/style.css">
</head>
<body>
    <div style="overflow-x: hidden;">
        <div class="cat-wrapper">
            <!-- LEFT SIDEBAR: FILTERS -->
            <div class="cat-sidebar">
                <div class="filter-header">
                    <h2>Filter option</h2>
                    <button class="btn-reset" onclick="resetFilters()">Reset Filter</button>
                </div>
                
                <div class="filter-divider"></div>

                <!-- Filter Game -->
                <div class="filter-group">
                    <h3>By Game</h3>
                    <div class="filter-list" id="listGames">
                        <!-- Injected via JS -->
                    </div>
                </div>

                <div class="filter-divider"></div>

                <!-- Filter Product Type -->
                <div class="filter-group">
                    <h3>By Category Item</h3>
                    <div class="filter-list" id="listTypes">
                        <!-- Static tapi dimanage via JS -->
                        <div class="filter-item" onclick="toggleFilter('type', 'Single card',this)">Single card</div>
                        <div class="filter-item" onclick="toggleFilter('type', 'Booster pack',this)">Booster pack</div>
                        <div class="filter-item" onclick="toggleFilter('type', 'Booster box',this)">Booster box</div>
                        <div class="filter-item" onclick="toggleFilter('type', 'Sleeve',this)">Sleeve</div>
                        <div class="filter-item" onclick="toggleFilter('type', 'Playmat',this)">Playmat</div>
                        <div class="filter-item" onclick="toggleFilter('type', 'Binder',this)">Binder</div>
                        <div class="filter-item" onclick="toggleFilter('type', 'Deck box',this)">Deck box</div>
                        <div class="filter-item" onclick="toggleFilter('type', 'Accessory',this)">Accessory</div>
                        <div class="filter-item" onclick="toggleFilter('type', 'Card Protector',this)">Card Protector</div>
                        <div class="filter-item" onclick="toggleFilter('type', 'Toploader',this)">Toploader</div>
                        <div class="filter-item" onclick="toggleFilter('type', 'Storage Box',this)">Storage Box</div>
                        <div class="filter-item" onclick="toggleFilter('type', 'Other',this)">Other</div>
                    </div>
                </div>

                <!-- Filter Rarity (Hanya muncul jika Single Card dipilih) -->
                <div id="wrapperRarity" style="display: none;">
                    <div class="filter-divider"></div>
                    <div class="filter-group">
                        <h3>By Card Rarity</h3>
                        <div class="filter-list" id="listRarities">
                            <!-- Injected via JS -->
                        </div>
                    </div>
                </div>

                <div class="filter-divider"></div>

                <!-- Filter Price -->
                <div class="filter-group">
                    <h3 style="text-align: center;">Price Range</h3>
                    <div class="price-inputs">
                        <input type="number" id="inputMinPrice" placeholder="Min">
                        <span>—</span>
                        <input type="number" id="inputMaxPrice" placeholder="Max">
                    </div>
                    <button class="btn-price-submit" onclick="applyPriceFilter()">Submit</button>
                </div>
            </div>

            <!-- RIGHT MAIN CONTENT: CATALOGUE -->
            <div class="cat-content">
                <div class="cat-topbar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; width: 100%;">
                    <!-- Ini akan diisi oleh Javascript jika ada pencarian -->
                    <div id="search-indicator" style="font-size: 1.25rem; color: #173C99; font-weight: 700; display: none;"></div>
                    
                    <div class="cat-sort" style="display: flex; align-items: center; gap: 10px;">
                        <p style="margin: 0; font-weight: 600; color: #173C99;">SortBy:</p>
                        <select id="sortSelect" onchange="applySort()" style="border: 1px solid #173C99; border-radius: 15px; padding: 5px 10px; color: #173C99; outline: none; cursor: pointer;">
                            <option value="default">Default</option>
                            <option value="lowest">Lowest Price</option>
                            <option value="highest">Highest Price</option>
                        </select>
                    </div>
                </div>

                <div class="cat-grid" id="catalogueGrid">
                    <!-- Cards Injected via JS -->
                </div>

                <div class="cat-pagination">
                    <button class="page-arrow" id="btnCatPrev" onclick="changePage(-1)">‹</button>
                    <span class="page-info" id="catPageInfo">1 of 1</span>
                    <button class="page-arrow" id="btnCatNext" onclick="changePage(1)">›</button>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 35px;">
            <?php include __DIR__ . '/../page-customer/footer.php'?>
        </div>
    </div>

    <script src="/cardhaven/interface/catalogue/assets/js/script.js"></script>
</body>
</html>