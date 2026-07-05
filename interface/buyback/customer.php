<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Buyback - Customer</title>
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <link rel="stylesheet" href="/cardhaven/interface/buyback/buyback_style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/cardhaven/interface/global_alert.js"></script>
    
</head>
<body>
    <div class="main-content">
        
        <div class="content-card" style="margin-bottom: 2rem; padding: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #E1EBFF;">
                <div>
                    <h2 style="color: var(--primary-color); font-weight: 700; font-size: 1.8rem; margin: 0;">Submit Card Buyback</h2>
                    <p style="color: #666; margin-top: 5px; font-size: 0.9rem;">Fill the form below to sell your cards and get the best offer.</p>
                </div>
                <div>
                    <a href="/CardHaven/home" class="btn-cancel-outline" style="width: auto !important; text-decoration: none; padding: 10px 20px; border-width: 2px; border-radius: 8px;">Back to Home</a>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 3px; align-items: start;">
                
                <div style="grid-column: span 2;">
                    <form id="formBuyback" enctype="multipart/form-data">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px; border-bottom: 2px dashed #E1EBFF; padding-bottom: 20px;">
                            <div class="form-group">
                                <label>Provider<span class="required">*</span></label>
                                <input type="text" name="provider" id="bankProvider" class="modal-input" placeholder="e.g., BCA, GoPay, DANA">
                                <div class="error-message" style="color: #E74C3C; font-size: 0.75rem; margin-top: 4px;"></div>
                            </div>
                            <div class="form-group">
                                <label>account number<span class="required">*</span></label>
                                <input type="text" name="no_rekening" id="bankNoRek" class="modal-input" placeholder="e.g., 08123456789">
                                <div class="error-message" style="color: #E74C3C; font-size: 0.75rem; margin-top: 4px;"></div>
                            </div>
                        </div>
                        <div id="cardInputsContainer">
                            <div class="card-input-group" id="cardGroup1" style="border: 2px solid #E1EBFF; padding: 20px; border-radius: 12px; margin-bottom: 7px; background: #fafcff;">
                                <h4 style="margin-top: 0; margin-bottom: 15px; color: var(--primary-color); font-size: 1.1rem;">Card 1</h4>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div class="form-group">
                                        <label>Card Name <span class="required">*</span></label>
                                        <input type="text" name="nama_kartu[]" class="modal-input" placeholder="e.g., Pikachu VMAX Secret Rare">
                                        <div class="error-message" style="color: #E74C3C; font-size: 0.75rem; margin-top: 4px;"></div>
                                    </div>
                                    <div class="form-group">
                                        <label>Your Offer Price (Rp) <span class="required">*</span></label>
                                        <input type="number" name="harga_beli[]" class="modal-input" placeholder="e.g., 1500000">
                                        <div class="error-message" style="color: #E74C3C; font-size: 0.75rem; margin-top: 4px;"></div>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 7px;">
                                    <div class="form-group">
                                        <label>Front Photo <span class="required">*</span></label>
                                        <input type="file" name="foto_depan[]" class="file-input-custom modal-input" accept="image/*" onchange="previewImage(this, 'previewFront1')">
                                        <div class="error-message" style="color: #E74C3C; font-size: 0.75rem; margin-top: 4px;"></div>
                                        <img id="previewFront1" src="" style="max-width: 100%; max-height: 200px; display: none; margin-top: 10px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                    </div>
                                    <div class="form-group">
                                        <label>Back Photo <span class="required">*</span></label>
                                        <input type="file" name="foto_belakang[]" class="file-input-custom modal-input" accept="image/*" onchange="previewImage(this, 'previewBack1')">
                                        <div class="error-message" style="color: #E74C3C; font-size: 0.75rem; margin-top: 4px;"></div>
                                        <img id="previewBack1" src="" style="max-width: 100%; max-height: 200px; display: none; margin-top: 10px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="button" class="btn-cancel-outline" style="flex: 1; border-style: dashed; border-width: 2px; padding: 12px;" onclick="addCardField()">+ Add Another Card</button>
                            <button type="button" class="btn-cancel" style="width: auto; padding: 12px 30px; border-radius: 8px; background: #E74C3C; color: white; font-weight: bold; border: none; cursor: pointer;" onclick="resetForm()">Reset Form</button>
                            <button type="button" class="btn-confirm" style="flex: 1; margin: 0; padding: 12px;" onclick="submitBuyback()">Submit Transaction</button>
                        </div>
                    </form>
                </div>

                <div style="grid-column: span 1; top: 20px;">
                    <div style="background: #fafcff; border: 2px solid #E1EBFF; border-radius: 12px; padding: 25px;">
                        <h3 style="color: var(--primary-color); margin-top: 0; font-size: 1.2rem; border-bottom: 2px dashed #E1EBFF; padding-bottom: 10px; margin-bottom: 5px;">Terms & Conditions</h3>
                        <ul style="padding-left: 20px; font-size: 0.85rem; color: #555; line-height: 1.8; margin: 0;">
                            <li style="margin-bottom: 10px;"><strong>Originality:</strong> Ensure your cards are 100% genuine. Counterfeit cards will be automatically rejected.</li>
                            <li style="margin-bottom: 10px;"><strong>Clear Photos:</strong> Photos must be clear, well-lit, and clearly show all four edges of the card.</li>
                            <li style="margin-bottom: 10px;"><strong>Condition Match:</strong> Any physical damage (scratches, dents) not visible in the uploaded photos may result in rejection during the final physical check.</li>
                            <li style="margin-bottom: 10px;"><strong>Negotiation:</strong> You have a maximum of 3 attempts to counter-offer per card.</li>
                            <li style="margin-bottom: 10px;"><strong>Shipping:</strong> Once the price is agreed upon, you must ship the card securely to our store address.</li>
                            <li style="margin-bottom: 10px;"><strong>Payment:</strong> Payment will be transferred strictly after the package is received and the physical quality is verified by our admin.</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

        <?php include __DIR__ . '/../page-customer/footer.php'; ?>
        <!-- <div class="footer">
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
        </div> -->
    </div>
    
    <?php include 'components/modal.php'; ?>
    <script src="/cardhaven/interface/buyback/buyback_customer_script.js"></script>
</body>
</html>