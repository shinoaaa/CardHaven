<?php
$baseUrl = '/CardHaven';
?>

<div class="home-container">
    <div class="hero-wrapper scroll-area">

        <div class="hero-section">
            <div class="hero-desc">
                <h1 class="hero-title" id="ui-event-title"></h1>
                
                <div class="hero-subtitle-container">
                    <h3 id="ui-event-product">-</h3>
                    <div class="divider divider-hero"></div>
                    <p>Release Date: <span id="ui-event-date">-</span></p>
                </div>

                <div class="duration-preoder">
                    <p>Event duration: <span id="startDate">Starting from</span> to <span id="endDate">Until</span></p>
                </div>
                
                <div class="hero-text">
                    <h4>Description</h4>
                    <p style="color: var(--paragraf);" id="ui-event-desc"></p>
                </div>

                <div class="action-buttons">
                    <button type="button" class="nav-btn-circle" id="btn-prev-event"><img src="<?= $baseUrl ?>/assets/image/left-arrow.svg"></button>
                    <button type="button" class="nav-btn-circle" id="btn-next-event"><img src="<?= $baseUrl ?>/assets/image/right-arrow.svg"></button>
                    <button class="btn-primary" style="background: var(--bg-gradient);" id="btn-title">See detail</button>
                    <a href="#promo-section">
                        <button class="btn-secondary">Another Event</button>
                    </a>
                </div>
            </div>
            
            <div class="hero-img">
                <!-- <img src="<?= $baseUrl ?>/assets/image/kotak-depan.png" style="position: absolute; z-index: 999; width: 100%; height: 100%;"> -->
                <img id="ui-event-image" src="" style="position: absolute; z-index: 1; transform: translateX(-1.5rem) translateY(2.5rem) scale(0.92);">
                <!-- <img src="<?= $baseUrl ?>/assets/image/garis-belakang.png" style="position: absolute; z-index: 0; width: 65.5%; height: 62%; transform: translateX(-3rem) translateY(-6.75rem);"> -->
            </div>
        </div>
        
        <div class="bottom-bar">
            <button type="button" class="nav-arrow" id="btn-prev-game" style="background:none; border:none; color:inherit; cursor:pointer;">&#9664;</button>

            <div class="nav-links" id="ui-game-list"></div>

            <button type="button" class="nav-arrow" id="btn-next-game" style="background:none; border:none; color:inherit; cursor:pointer;">&#9654;</button>
        </div>
    </div>

    <div class="promo" id="promo-section">
        <div class="promo-header">
            <h3 class="coolveticaa">Promo🔥</h3>
            <div style="width: 25%;">
                <select name="" class="modal-input">
                    <option value="">-- Select Game --</option>
                </select>
            </div>
        </div>
        <div class="promo-content"></div>
        <div class="promo-pagination">
            <button class="nav-btn-circle" id="btn-prev-promo"><img src="<?= $baseUrl ?>/assets/image/left-arrow.svg"></button>
            <div id="promo-page-info">
                <span>1</span>
                <span>of</span>
                <span>1</span>
            </div>
            <button class="nav-btn-circle" id="btn-next-promo"><img src="<?= $baseUrl ?>/assets/image/right-arrow.svg"></button>
        </div>
        <div style="width: 100%; height: 1px; background-color: var(--primary-color); margin: 2rem 0rem 1rem 0rem"></div>
    </div>

    <div class="game">
        <div class="game-button">
            <button class="nav-btn-circle" id="btn-prev-game-card" style="border: 3px solid white;"><img src="<?= $baseUrl ?>/assets/image/left-arrow.svg"></button>
            <button class="nav-btn-circle" id="btn-next-game-card" style="border: 3px solid white;"><img src="<?= $baseUrl ?>/assets/image/right-arrow.svg"></button>
        </div>
        
        <div class="game-list" id="ui-game-card-list"></div>
    </div>

    <div class="product">
        <div class="product-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1 style="font-size: 2.5rem; color: var(--primary-color);">Explore Our <br> Products</h1>
                <!-- <div class="nav-search" style="width: auto; height: 2.5rem;">
                    <input type="text" style="height: 100%; width: 20.5rem; border: 1px solid var(--primary-color); border-radius: 9999px;" placeholder="Type Product Name">
                    <div style="height: 100%; aspect-ratio: 1/1; background-color: var(--primary-color); border-radius: 9999px; display: flex; justify-content: center; align-items: center;">
                        <img src="<?= $baseUrl ?>/assets/image/search.svg" style="object-fit: cover; width: 60%; height: 60%;">
                    </div>
                </div> -->
                <div style="display: flex; flex-direction: column; align-items:  flex-start;">
                    <a href="/CardHaven/home/list" style="text-decoration: underline; color: var(--primary-color); font-size: 1rem;">
                        See all product
                    </a>
                </div>
            </div>
            <div style="gap: 1.75rem; margin-top: 0.75rem; display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr;">
                <div>
                    <label for="">Min Price</label>
                    <input type="text" placeholder="Enter min price">
                </div>
                <div>
                    <label for="">Max Price</label>
                    <input type="text" placeholder="Enter max price">
                </div>
                <div>
                    <label for="">Product Type</label>
                    <input type="text" placeholder="Enter product type">
                </div>
                <div>
                    <label for="">Game Name</label>
                    <input type="text" placeholder="Enter game name">
                </div>
                <div style="display: flex; flex-direction: column; justify-content: flex-end;">
                    <button class="btn-primary" style="background: var(--bg-gradient); display: flex; padding: 1rem 0rem; align-items: center; justify-content: center; font-size: 1rem;">Confirm</button>
                </div>
            </div>
        </div>
        <div class="product-list">
            <div class="product-card"></div>
        </div>
        <div class="promo-pagination">
            <button class="nav-btn-circle" id="btn-prev-product"><img src="<?= $baseUrl ?>/assets/image/left-arrow.svg"></button>
            
            <div id="ui-product-page-info">
                <span>1 of 1</span>
            </div>
            
            <button class="nav-btn-circle" id="btn-next-product"><img src="<?= $baseUrl ?>/assets/image/right-arrow.svg"></button>
        </div>
    </div>

    <div class="buyback">
        <div style="width: 20rem; height: 20rem; border-radius: 9999px; background-color: #1B4AAD; position: absolute; transform: translateX(3rem) translateY(3rem);  filter: blur(75px);"></div>
        <div style="width: 20rem; height: 20rem; border-radius: 9999px; background-color: #1949b1; position: absolute; transform: translateX(65rem) translateY(15rem);  filter: blur(70px); z-index: 0;"></div>

        <div style="display: flex; justify-content: space-between; gap: 27rem;">
            <div style="z-index: 999; position: relative;">
                <div class="buyback-title">
                    <div style="width: 0.75rem; height: 0.75rem; background-color: #FC7812; border-radius: 9999px;"></div>
                    <h3>Buyback Card</h3>
                </div>
                <h2 class="coolveticaa" style="color: white; font-size: 3.25rem; margin-top: 1rem;">
                    Your cards are worth <br>
                    <span class="coolveticaa" style="color: #5e8be3;">
                        more than shelf <br>
                        space.
                    </span>
                </h2>
                <p style="color: #ffffff; opacity: 65%; width: 30rem; margin-top: 1.25rem;">
                    Your cards are worth more than shelf spaGot extra cards collecting dust? Trade them in for fast cash or store credit — we make it simple and fair
                </p>
                <div class="buyback-list-btn">
                    <a href="/CardHaven/home/buyback" style="text-decoration: none;">
                        <div class="btn-buyback" style="padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid white; display: flex; gap: 0.5rem; align-items: center;">
                            <div style="width: 2.25rem; height: 2.25rem; display: flex; align-items: center; justify-content: center;">
                                <img src="<?= $baseUrl ?>/assets/image/cash.svg" style="object-fit: cover; width: 100%; height: 100%;">
                            </div>
                            <h3>Get an offer</h3>
                        </div>
                    </a>
                    
                    <div class="btn-buyback" style="padding: 0.5rem 1.5rem; border-radius: 0.5rem; border: 1px solid white; display: flex; gap: 0.5rem; align-items: center;">
                        <h3>Learn how it work →</h3>
                    </div>
                </div>
            </div>

            <div style="position: relative;">
                <img src="/cardhaven/assets/image/pict1.svg" alt="" style="position: absolute; z-index: 6; transform: translateX(2rem) translateY(3rem);">
                <img src="/cardhaven/assets/image/pict2.svg" alt="" style="position: absolute; z-index: 5; transform: translateX(-0.75rem) translateY(0.75rem);">
                <img src="/cardhaven/assets/image/pict3.svg" alt="" style="position: absolute; z-index: 3; transform: translateX(-2.5rem) translateY(-3.25rem);">
                <img src="/cardhaven/assets/image/pict4.svg" alt="" style="position: absolute; z-index: 2; transform: translateX(-4.5rem) translateY(-1.25rem) scale(1.1);">
            </div>
        </div>
    </div>

    <!-- <div class="footer">
        <div class="foot-top">
            <div class="foot-game">
                <div class="list-header">
                    <div style="width: 2rem; height: 2rem;">
                        <img src="<?= $baseUrl ?>/assets/image/games.svg" style="object-fit: cover; width: 100%; height: 100%;">
                    </div>
                    <h1 style="font-size: 1.25rem;">Games</h1>
                </div>
                <ul class="footer-list" style="margin-left: 1.25rem; margin-top: 0.25rem; color: #6B81B2;">
                    <li>Yu-Gi-Oh! Official DB</li>
                    <li>Pokémon TCG Live</li>
                    <li>Magic: The Gathering</li>
                    <li>One Piece Card Game</li>
                    <li>Cardfight !! Vanguard</li>
                </ul>
            </div>
            <div class="foot-service">
                <div class="list-header">
                    <div style="width: 2rem; height: 2rem;">
                        <img src="<?= $baseUrl ?>/assets/image/service.svg" style="object-fit: cover; width: 100%; height: 100%;">
                    </div>
                    <h1 style="font-size: 1.25rem;">Services</h1>
                </div>
                <ul class="footer-list" style="margin-left: 1.25rem; margin-top: 0.25rem; color: #6B81B2;">
                    <li>Contact Us</li>
                    <li>Privacy And Policy</li>
                    <li>FAQ</li>
                </ul>
            </div>
            <div class="foot-about">
                <div class="list-header">
                    <div style="width: 2rem; height: 2rem;">
                        <img src="<?= $baseUrl ?>/assets/image/product-foot.svg" style="object-fit: cover; width: 100%; height: 100%;">
                    </div>
                    <h1 style="font-size: 1.25rem;">About Us</h1>
                </div>
                <ul class="footer-list" style="margin-left: 1.25rem; margin-top: 0.25rem; color: #6B81B2; gap: 1000px;">
                    <li>Contact Us</li>
                    <li>Privacy And Policy</li>
                    <li>FAQ</li>
                </ul>
            </div>
            <div class="footer-community">
                <h1 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Join Us On</h1>
                <div style="height: 2px; background-color: var(--primary-color); width: 12rem;"></div>
                <div style="display: flex; gap: 1rem;">
                    <div class="community-icon">
                        <img src="<?= $baseUrl ?>/assets/image/instagram.svg" style="object-fit: cover; width: 100%; height: 100%;">
                    </div>
                    <div class="community-icon">
                        <img src="<?= $baseUrl ?>/assets/image/x.svg" style="object-fit: cover; width: 100%; height: 100%;">
                    </div>
                    <div class="community-icon">
                        <img src="<?= $baseUrl ?>/assets/image/discord.svg" style="object-fit: cover; width: 100%; height: 100%;">
                    </div>
                </div>
            </div>
        </div>
        <div class="foot-bottom">
            <div style="margin-top: 0.25rem;">
                <h3>© 2026 www.card-haven.com - All Rights Reserved.</h3>
            </div>
        </div>
    </div> -->
    <?php include __DIR__ . '/../page-customer/footer.php' ?>


    <?php include __DIR__ . '/../../interface/event-transaction/index.php' ?>
    <?php include __DIR__ . '/../../interface/preorder-transaction/index.php' ?>
</div>

<script>
    const BASE_URL = '<?= $baseUrl ?>';
</script>
<script src="<?= $baseUrl ?>/interface/global_alert.js"></script>
<script src="<?= $baseUrl ?>/interface/home/script.js"></script>
<script src="<?= $baseUrl ?>/interface/event-transaction/script.js"></script>
<script src="<?= $baseUrl ?>/interface/preorder-transaction/script.js"></script>
