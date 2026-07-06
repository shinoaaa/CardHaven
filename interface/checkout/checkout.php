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
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <style>
        /* =========================================
           CHECKOUT PAGE — CARDHAVEN THEME
           ========================================= */

        .checkout-page-wrapper {
            padding: 2rem 2.75rem 4rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .checkout-page-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: var(--primary-color, #1a3a6b);
            font-family: coolvetica, sans-serif;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .checkout-page-title .accent { color: var(--highlight); }

        /* ---- Step Indicator ---- */
        .checkout-steps {
            display: flex;
            align-items: flex-start;
            gap: 0;
            margin-bottom: 2.5rem;
            padding: 1.25rem 0;
            border-bottom: 2px solid #eef2ff;
        }

        /* Circle di atas, label di bawah, dan garis penghubung sejajar dengan
           tengah lingkaran (lewat di BAWAH lingkaran) supaya tidak menembus teks. */
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            flex: 1;
            position: relative;
            text-align: center;
        }

        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 18px;               /* tengah lingkaran 36px */
            left: 50%;
            width: 100%;             /* dari tengah circle ini ke tengah circle berikutnya */
            height: 2px;
            background: #e0e7ff;
            z-index: 0;
        }

        .step-item.active::after { background: var(--highlight); }
        .step-item.done::after   { background: #16a34a; }

        .step-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e0e7ff;
            color: #93a3c4;
            font-weight: 800;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.3s;
            position: relative;
            z-index: 1;              /* di atas garis penghubung */
        }

        .step-item.active .step-circle {
            background: var(--highlight);
            color: white;
            box-shadow: 0 0 0 4px rgba(37,99,235,0.15);
        }

        .step-item.done .step-circle {
            background: #16a34a;
            color: white;
        }

        .step-label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #93a3c4;
            white-space: nowrap;
        }

        .step-item.active .step-label { color: var(--highlight); }
        .step-item.done  .step-label  { color: #16a34a; }

        /* ---- Layout ---- */
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 32px;
            align-items: flex-start;
        }

        /* ---- Form Sections ---- */
        .checkout-form-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .checkout-card {
            background: white;
            border: 1.5px solid #dde4f8;
            border-radius: 12px;
            overflow: hidden;
        }

        .checkout-card-header {
            background: var(--primary-color, #1a3a6b);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .checkout-card-header h2 {
            font-size: 0.85rem;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 0;
        }

        .header-icon {
            font-size: 1.1rem;
        }

        .checkout-card-body {
            padding: 24px;
        }

        /* ---- Form Fields ---- */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .form-row.full { grid-template-columns: 1fr; }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-gray, #666);
        }

        .form-label .required { color: #dc2626; }

        .form-input,
        .form-select,
        .form-textarea {
            border: 1.5px solid #dde4f8;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.9rem;
            color: var(--text-dark, #111);
            background: white;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            width: 100%;
            box-sizing: border-box;
            font-family: inherit;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: var(--highlight);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-input.readonly-input {
            background: #f8faff;
            color: #555;
            cursor: not-allowed;
        }

        .form-textarea { resize: vertical; min-height: 80px; }

        /* ---- Item list in checkout ---- */
        .checkout-item-list { display: flex; flex-direction: column; gap: 12px; }

        .checkout-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .checkout-item:last-child { border-bottom: none; }

        .checkout-item-img {
            width: 60px;
            height: 76px;
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            border-radius: 6px;
            overflow: hidden;
            flex-shrink: 0;
            border: 1px solid #dde4f8;
        }

        .checkout-item-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .checkout-item-info { flex: 1; min-width: 0; }

        .checkout-item-name {
            font-weight: 700;
            font-size: 0.88rem;
            color: var(--text-dark, #111);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .checkout-item-meta {
            font-size: 0.75rem;
            color: var(--text-gray, #888);
        }

        .checkout-item-subtotal {
            font-weight: 800;
            font-size: 0.9rem;
            color: var(--primary-color, #1a3a6b);
            white-space: nowrap;
        }

        /* ---- Payment Methods ---- */
        .payment-method-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .payment-method-option {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border: 1.5px solid #dde4f8;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .payment-method-option:hover { border-color: var(--highlight); background: #fafbff; }

        .payment-method-option.selected {
            border-color: var(--highlight);
            background: #eef2ff;
        }

        .payment-method-option input[type="radio"] {
            accent-color: var(--highlight);
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .payment-method-info { flex: 1; }

        .payment-method-name {
            font-weight: 700;
            font-size: 0.88rem;
            color: var(--text-dark, #111);
        }

        .payment-method-detail {
            font-size: 0.75rem;
            color: var(--text-gray, #888);
            margin-top: 2px;
        }

        .payment-method-fee {
            font-size: 0.78rem;
            font-weight: 700;
            color: #dc2626;
        }

        .payment-method-fee.free { color: #16a34a; }

        /* ---- Payment Method Pagination ---- */
        .payment-method-pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin-top: 14px;
        }

        .pm-page-btn {
            padding: 7px 16px;
            border: 1.5px solid #dde4f8;
            background: white;
            color: var(--primary-color, #0F3891);
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .pm-page-btn:hover:not(:disabled) {
            border-color: var(--highlight);
            background: #f0f4ff;
        }

        .pm-page-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        /* ---- Order Summary (Sidebar) ---- */
        /* Kolom kanan: Order Summary di atas, Payment Method di bawahnya */
        .checkout-aside {
            display: flex;
            flex-direction: column;
            gap: 20px;
            position: sticky;
            top: 100px;
        }

        .checkout-summary-card {
            background: white;
            border: 1.5px solid #dde4f8;
            border-radius: 12px;
            overflow: hidden;
        }

        .summary-header {
            background: var(--primary-color, #1a3a6b);
            padding: 18px 24px;
        }

        .summary-header h2 {
            font-size: 1rem;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 0;
        }

        .summary-body { padding: 20px 24px; display: flex; flex-direction: column; gap: 12px; }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.88rem;
            color: var(--text-gray, #666);
        }

        .summary-row .val { font-weight: 700; color: var(--text-dark, #111); }
        .summary-row .val.free { color: #16a34a; }
        .summary-row .val.fee  { color: #dc2626; }

        .summary-divider { height: 1px; background: #f0f0f0; }

        .summary-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            background: #f0f4ff;
            border-top: 1.5px solid #dde4f8;
        }

        .summary-total-label {
            font-size: 0.9rem;
            font-weight: 800;
            color: var(--text-dark, #111);
            text-transform: uppercase;
        }

        .summary-total-value {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--primary-color, #1a3a6b);
        }

        /* ---- Checkout CTA ---- */
        .checkout-cta-area { padding: 16px 24px 24px; }

        .btn-place-order {
            width: 100%;
            padding: 15px;
            background: var(--primary-color, #1a3a6b);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-place-order:hover:not(:disabled) {
            background: var(--highlight);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37,99,235,0.28);
        }

        .btn-place-order:disabled {
            background: #d1d5db;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .checkout-note {
            font-size: 0.72rem;
            color: var(--text-gray, #888);
            text-align: center;
            margin-top: 10px;
            line-height: 1.5;
        }

        /* ---- Loading State ---- */
        .checkout-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 0;
            gap: 12px;
        }

        .loading-spinner {
            width: 36px;
            height: 36px;
            border: 3px solid #dde4f8;
            border-top-color: var(--highlight);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .loading-text {
            font-size: 0.85rem;
            color: var(--text-gray, #888);
        }

        /* ---- Alert ---- */
        .alert-box {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 16px;
            display: none;
        }

        .alert-box.error   { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; }
        .alert-box.success { background: #dcfce7; color: #16a34a; border: 1px solid #86efac; }
        .alert-box.show    { display: block; }

        /* ---- Back link ---- */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--primary-color, #1a3a6b);
            text-decoration: none;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            transition: color 0.2s;
        }

        .back-link:hover { color: var(--highlight); }

        @media (max-width: 900px) {
            .checkout-layout { grid-template-columns: 1fr; }
            .checkout-aside { position: static; }
            .checkout-page-wrapper { padding: 1.5rem 1rem 3rem; }
            .form-row { grid-template-columns: 1fr; }
            .step-label { font-size: 0.68rem; }
        }

        /* ---- Offset navbar ---- */
        .main-content {
            margin-top: 80px;
        }

        /* ---- Itemized order summary ---- */
        .summary-items-label {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-gray, #888);
        }
        .summary-items { display: flex; flex-direction: column; gap: 10px; max-height: 260px; overflow-y: auto; }
        .summary-line { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; font-size: 0.82rem; }
        .summary-line-name { color: var(--text-dark, #111); font-weight: 600; line-height: 1.3; }
        .summary-line-qty { color: var(--text-gray, #888); font-weight: 500; font-size: 0.76rem; }
        .summary-line-price { font-weight: 700; color: var(--primary-color, #1a3a6b); white-space: nowrap; }
    </style>
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