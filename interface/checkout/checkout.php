<?php
session_start();
// if (!isset($_SESSION['id_pengguna'])) {
//     header("Location: /cardhaven/interface/login-page/index.php?error=login_required");
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - CardHaven</title>
    <link rel="icon" type="image/svg+xml" href="/cardhaven/assets/image/logo.svg">
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <link rel="stylesheet" href="/cardhaven/interface/checkout/style.css">
    
</head>
<body>
    <?php include '../page-customer/navBar.php'; ?>

    <main class="main-content">
        <div class="checkout-page-wrapper">

            <a href="/CardHaven/home/cart" class="back-link">← Back to Cart</a>

            <h1 class="checkout-page-title">CHECK<span class="accent">OUT</span></h1>

            <!-- Step Indicator -->
            <div class="checkout-steps">
                <div class="step-item active" id="step1-indicator">
                    <div class="step-circle">1</div>
                    <span class="step-label">Order Details</span>
                </div>
                <div class="step-item" id="step2-indicator">
                    <div class="step-circle">2</div>
                    <span class="step-label">Upload Payment</span>
                </div>
                <div class="step-item" id="step3-indicator">
                    <div class="step-circle">3</div>
                    <span class="step-label">Confirmation</span>
                </div>
            </div>

            <!-- Step 1: Order Details -->
            <div id="step1-content">
                <div id="alert-checkout" class="alert-box"></div>

                <div class="checkout-layout">

                    <!-- LEFT: Form -->
                    <div class="checkout-form-section">

                        <!-- Order Items (moved to top for better UX) -->
                        <div class="checkout-card">
                            <div class="checkout-card-header">
                                <span class="header-icon">🃏</span>
                                <h2>Order Items</h2>
                            </div>
                            <div class="checkout-card-body">
                                <div id="checkout-items-loading" class="checkout-loading" style="padding:30px 0;">
                                    <div class="loading-spinner"></div>
                                    <span class="loading-text">Loading items...</span>
                                </div>
                                <div id="checkout-item-list" class="checkout-item-list" style="display:none;"></div>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div class="checkout-card">
                            <div class="checkout-card-header">
                                <span class="header-icon">📦</span>
                                <h2>Shipping Address</h2>
                            </div>
                            <div class="checkout-card-body">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Full Name <span class="required">*</span></label>
                                        <input type="text" id="field-name" class="form-input" placeholder="Your full name" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Phone Number <span class="required">*</span></label>
                                        <input type="text" id="field-phone" class="form-input" placeholder="+62...">
                                    </div>
                                </div>
                                <div class="form-row full">
                                    <div class="form-group">
                                        <label class="form-label">Shipping Address <span class="required">*</span></label>
                                        <textarea id="field-alamat" class="form-textarea" placeholder="Full address including city and postal code..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT: Summary + Payment -->
                    <aside class="checkout-aside">
                        <div class="checkout-summary-card">
                            <div class="summary-header">
                                <h2>Order Summary</h2>
                            </div>
                            <div class="summary-body">
                                <div id="summary-items-label" class="summary-items-label">Items</div>
                                <div id="summary-items" class="summary-items"></div>
                                <div class="summary-divider"></div>
                                <div class="summary-row">
                                    <span>Subtotal</span>
                                    <span class="val" id="summary-subtotal">Rp 0</span>
                                </div>
                                <div class="summary-row" id="summary-fee-row" style="display:none;">
                                    <span>Payment Fee</span>
                                    <span class="val fee" id="summary-fee">Rp 0</span>
                                </div>
                            </div>
                            <div class="summary-total-row">
                                <span class="summary-total-label">Total</span>
                                <span class="summary-total-value" id="summary-grand-total">Rp 0</span>
                            </div>
                            <div class="checkout-cta-area">
                                <button class="btn-place-order" id="btn-place-order" disabled onclick="placeOrder()">
                                    Place Order →
                                </button>
                                <p class="checkout-note">
                                    By placing your order, you agree to CardHaven's<br>
                                    terms and conditions.
                                </p>
                            </div>
                        </div>

                        <!-- Payment Method (di kolom kanan, sejajar dengan Shipping Address) -->
                        <div class="checkout-card">
                            <div class="checkout-card-header">
                                <span class="header-icon">💳</span>
                                <h2>Payment Method</h2>
                            </div>
                            <div class="checkout-card-body">
                                <div id="payment-method-loading" class="checkout-loading" style="padding:30px 0;">
                                    <div class="loading-spinner"></div>
                                    <span class="loading-text">Loading payment methods...</span>
                                </div>
                                <div id="payment-method-list" class="payment-method-list" style="display:none;"></div>
                                <div id="payment-method-pagination" class="payment-method-pagination"></div>
                            </div>
                        </div>
                    </aside>

                </div>
            </div>

            <!-- Step 2: Upload Bukti Pembayaran -->
            <div id="step2-content" style="display:none;">
                <div class="checkout-layout">
                    <div class="checkout-form-section">
                        <div class="checkout-card">
                            <div class="checkout-card-header">
                                <span class="header-icon">📤</span>
                                <h2>Upload Payment Proof</h2>
                            </div>
                            <div class="checkout-card-body">
                                <div id="payment-instruction-box" style="
                                    background:#f0f4ff;
                                    border:1.5px solid #dde4f8;
                                    border-radius:8px;
                                    padding:16px 20px;
                                    margin-bottom:20px;
                                ">
                                    <p style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#1a3a6b;margin:0 0 8px;">
                                        Transfer to:
                                    </p>
                                    <p id="payment-instruction-detail" style="font-size:0.95rem;font-weight:700;color:#111;margin:0;line-height:1.6;"></p>
                                    <p style="font-size:0.8rem;color:#666;margin:10px 0 0;">
                                        Please transfer the exact amount: <strong id="payment-instruction-amount" style="color:#1a3a6b;"></strong>
                                    </p>
                                </div>

                                <div class="form-group" style="margin-bottom:20px;">
                                    <label class="form-label">Proof of Payment <span class="required">*</span></label>
                                    <div id="upload-drop-zone" style="
                                        border: 2px dashed #dde4f8;
                                        border-radius: 10px;
                                        padding: 40px 20px;
                                        text-align: center;
                                        cursor: pointer;
                                        transition: border-color 0.2s, background 0.2s;
                                    " onclick="document.getElementById('bukti-file-input').click()"
                                       ondragover="event.preventDefault();this.style.borderColor='var(--highlight)';this.style.background='#eef2ff'"
                                       ondragleave="this.style.borderColor='#dde4f8';this.style.background=''"
                                       ondrop="handleFileDrop(event)">
                                        <div style="font-size:2.5rem;margin-bottom:8px;">🖼️</div>
                                        <p style="font-size:0.88rem;font-weight:700;color:#1a3a6b;margin:0 0 4px;">
                                            Click or drag & drop
                                        </p>
                                        <p style="font-size:0.75rem;color:#888;margin:0;">
                                            JPG, PNG, or PDF — max 5MB
                                        </p>
                                    </div>
                                    <input type="file" id="bukti-file-input" accept="image/*,.pdf" style="display:none;" onchange="handleFileSelect(this)">

                                    <!-- Preview -->
                                    <div id="file-preview" style="display:none;margin-top:12px;position:relative;">
                                        <img id="file-preview-img" style="max-width:100%;max-height:220px;border-radius:8px;border:1.5px solid #dde4f8;object-fit:contain;" alt="Preview">
                                        <button onclick="clearFile()" style="
                                            position:absolute;top:8px;right:8px;
                                            background:#dc2626;color:white;border:none;
                                            border-radius:50%;width:26px;height:26px;
                                            cursor:pointer;font-size:0.75rem;
                                        ">✕</button>
                                    </div>
                                </div>

                                <div id="alert-upload" class="alert-box"></div>

                                <button class="btn-place-order" id="btn-submit-payment" onclick="submitPayment()" disabled>
                                    Submit Payment Proof →
                                </button>
                            </div>
                        </div>
                    </div>

                    <aside>
                        <div class="checkout-summary-card">
                            <div class="summary-header"><h2>Order Summary</h2></div>
                            <div class="summary-body">
                                <div class="summary-row">
                                    <span>Order ID</span>
                                    <span class="val" id="step2-order-id">#-</span>
                                </div>
                                <div class="summary-divider"></div>
                                <div class="summary-row">
                                    <span>Total Payment</span>
                                    <span class="val" id="step2-total">Rp 0</span>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>

            <!-- Step 3: Confirmation -->
            <div id="step3-content" style="display:none;text-align:center;padding:60px 0;">
                <div style="font-size:4rem;margin-bottom:1rem;">✅</div>
                <h2 style="font-size:1.8rem;font-weight:800;color:#1a3a6b;margin-bottom:0.5rem;">
                    Payment Submitted!
                </h2>
                <p style="font-size:0.95rem;color:#666;max-width:500px;margin:0 auto 2rem;">
                    Your payment proof has been uploaded. Our team will verify your payment
                    and update your order status shortly.
                </p>

                <div style="
                    display:inline-block;
                    background:#f0f4ff;
                    border:1.5px solid #dde4f8;
                    border-radius:12px;
                    padding:24px 48px;
                    margin-bottom:2rem;
                ">
                    <p style="font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:#888;margin:0 0 4px;">Order ID</p>
                    <p id="confirm-order-id" style="font-size:1.5rem;font-weight:800;color:#1a3a6b;margin:0;"></p>
                    <p style="font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:#888;margin:12px 0 4px;">Status</p>
                    <p style="font-size:0.9rem;font-weight:700;color:#d97706;margin:0;">⏳ Pending Payment Verification</p>
                </div>

                <br>
                <a href="/cardhaven/interface/page-profile/index.php" style="
                    display:inline-block;
                    padding:14px 32px;
                    background:#1a3a6b;
                    color:white;
                    border-radius:8px;
                    font-weight:800;
                    text-transform:uppercase;
                    letter-spacing:1.5px;
                    font-size:0.85rem;
                    text-decoration:none;
                    transition:all 0.2s;
                    margin-right:12px;
                " onmouseover="this.style.background='var(--highlight)'" onmouseout="this.style.background='#1a3a6b'">
                    View My Orders
                </a>
                <a href="/CardHaven/home" style="
                    display:inline-block;
                    padding:14px 32px;
                    background:transparent;
                    color:#1a3a6b;
                    border:2px solid #1a3a6b;
                    border-radius:8px;
                    font-weight:800;
                    text-transform:uppercase;
                    letter-spacing:1.5px;
                    font-size:0.85rem;
                    text-decoration:none;
                    transition:all 0.2s;
                ">
                    Continue Shopping
                </a>
            </div>

        </div>
    </main>

    <script src="/cardhaven/interface/checkout/checkout_script.js"></script>
</body>
</html>