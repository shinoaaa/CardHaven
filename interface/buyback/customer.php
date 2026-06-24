<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Buyback - Customer</title>
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <link rel="stylesheet" href="buyback_style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/cardhaven/interface/global_alert.js"></script>
</head>
<body>
    <?php include '../page-customer/navBar.php'; ?>
    <div class="main-content">
        <div class="content-card" style="display: flex; flex-direction: row; justify-content: space-between; align-items: center; min-height: auto; padding: 20px 30px;">
            <div>
                <h2 style="color: var(--primary-color); font-weight: 700; font-size: 1.8rem; margin: 0;">Card Buyback</h2>
                <p style="color: #666; margin-top: 5px; font-size: 0.9rem;">Submit your cards for appraisal and get the best price.</p>
            </div>
            <button class="btn-confirm" style="width: auto; margin: 0; padding: 12px 25px;" onclick="openSubmitModal()">+ Sell Cards</button>
        </div>

        <div class="content-card" style="margin-top: 2rem;">
            <div class="card-title-row">
                <h2>Your Buyback History</h2>
            </div>
            <table class="styled-table" id="tableRiwayat">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Date</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    </tbody>
            </table>
        </div>
        <div class="footer">
            <div class="foot-top">
                <div class="foot-game">
                    <div class="list-header">
                        <div style="width: 2rem; height: 2rem;">
                            <img src="/cardhaven/assets/image/games.svg" style="object-fit: cover; width: 100%; height: 100%;">
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
                            <img src="/cardhaven/assets/image/service.svg" style="object-fit: cover; width: 100%; height: 100%;">
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
                            <img src="/cardhaven/assets/image/product-foot.svg" style="object-fit: cover; width: 100%; height: 100%;">
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
                            <img src="/cardhaven/assets/image/instagram.svg" style="object-fit: cover; width: 100%; height: 100%;">
                        </div>
                        <div class="community-icon">
                            <img src="/cardhaven/assets/image/x.svg" style="object-fit: cover; width: 100%; height: 100%;">
                        </div>
                        <div class="community-icon">
                            <img src="/cardhaven/assets/image/discord.svg" style="object-fit: cover; width: 100%; height: 100%;">
                        </div>
                    </div>
                </div>
            </div>
            <div class="foot-bottom">
                <div style="margin-top: 0.25rem;">
                    <h3>© 2026 www.card-haven.com - All Rights Reserved.</h3>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'components/modal.php'; ?>
    <script src="buyback_customer_script.js"></script>
</body>
</html>