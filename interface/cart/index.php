<?php
session_start();

// Proteksi Halaman
// if (!isset($_SESSION['id_pengguna'])) {
//     header("Location: /cardhaven/interface/login-page/index.php?error=login_required");
//     exit;
// }

// $session_id_pengguna = $_SESSION['id_pengguna'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - CardHaven</title>
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <style>
        .main-content {
            margin-top: 80px; /* Sesuaikan dengan tinggi navbar Anda */
        }
        /* CSS Anda tetap sama seperti sebelumnya */
        .cart-page-wrapper {
            padding: 0 2rem 4rem;
            max-width: 1300px;
            margin: 0 auto;
            overflow-x: hidden; /* Mencegah scroll horizontal pada container utama */
        }
        .cart-page-title { font-size: 2.5rem; font-weight: 800; margin-bottom: 2rem; color: var(--primary-color); font-family: 'Coolvetica', sans-serif; letter-spacing: 1px; text-transform: uppercase; }
        .cart-page-title .accent { color: #2563EB; }
        .cart-wrapper {
            display: flex;
            gap: 40px;
            align-items: flex-start;
            width: 100%;
        }
        .cart-items-section {
            flex: 1; /* Biarkan sisi kiri mengambil sisa ruang yang ada */
            min-width: 0; /* Penting agar flex child bisa mengecil di bawah lebar kontennya */
        }
        .cart-toolbar { display: flex; align-items: center; gap: 12px; padding: 14px 0; border-bottom: 2px solid var(--primary-color, #1a3a6b); margin-bottom: 0; }
        .cart-toolbar label { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-gray, #666); cursor: pointer; user-select: none; }
        #select-all-checkbox { width: 16px; height: 16px; accent-color: var(--primary-color, #1a3a6b); cursor: pointer; }
        .cart-item-count { margin-left: auto; font-size: 0.8rem; color: var(--text-gray, #666); font-weight: 600; }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto; /* Biarkan kolom menyesuaikan isi */
        }
        .cart-table thead th {
            text-align: left;
            padding: 15px 10px;
            color: #888;
            font-size: 0.75rem;
            border-bottom: 2px solid #eee;
            white-space: nowrap;
        }

        /* Atur lebar spesifik agar tidak bertabrakan */
        .cart-table th:nth-child(3), .cart-table td:nth-child(3) { width: 140px; } /* Price */
        .cart-table th:nth-child(4), .cart-table td:nth-child(4) { width: 120px; text-align: center; } /* Qty */
        .cart-table th:nth-child(5), .cart-table td:nth-child(5) { width: 140px; text-align: right; } /* Subtotal */

        .cart-table tbody td {
            padding: 20px 10px;
            border-bottom: 1px solid #f5f5f5;
            vertical-align: middle;
        }
        .cart-table tbody tr:hover { background: #fafbff; }
        .cart-table tbody td input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--primary-color, #1a3a6b); cursor: pointer; }
        .cart-product-info { display: flex; align-items: center; gap: 18px; }
        .cart-img-wrap { width: 80px; height: 100px; background: #eef2ff; border-radius: 6px; display: flex; justify-content: center; align-items: center; flex-shrink: 0; overflow: hidden; border: 1px solid #dde4f8; }
        .cart-img-wrap img { width: 100%; height: 100%; object-fit: cover; border-radius: 5px; }
        .cart-product-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
            max-width: 300px; /* Membatasi lebar teks produk */
        }
        .cart-product-title {
            font-weight: 700;
            font-size: 0.95rem;
            line-height: 1.4;
            word-wrap: break-word; /* Bungkus teks jika terlalu panjang */
        }
        
        .cart-product-meta { font-size: 0.75rem; color: var(--text-gray, #888); background: #eef2ff; display: inline-block; padding: 2px 8px; border-radius: 20px; font-weight: 600; }
        .cart-price { font-weight: 700; font-size: 0.9rem; color: var(--text-dark, #111); white-space: nowrap; }
        .cart-total { font-weight: 800; font-size: 0.95rem; color: var(--primary-color, #1a3a6b); white-space: nowrap; }
        .cart-qty-control { display: inline-flex; align-items: center; border: 1.5px solid #dde4f8; border-radius: 6px; overflow: hidden; background: white; }
        .cart-qty-btn { background: none; border: none; width: 32px; height: 32px; font-size: 1.1rem; cursor: pointer; color: var(--primary-color, #1a3a6b); font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .cart-qty-btn:hover { background: #eef2ff; }
        .cart-qty-val { min-width: 36px; text-align: center; font-weight: 700; font-size: 0.9rem; color: var(--text-dark, #111); border-left: 1px solid #dde4f8; border-right: 1px solid #dde4f8; padding: 0 4px; height: 32px; display: flex; align-items: center; justify-content: center; }
        .cart-btn-remove { width: 28px; height: 28px; border-radius: 50%; background: #f5f5f5; border: none; color: #aaa; cursor: pointer; font-size: 0.7rem; display: flex; justify-content: center; align-items: center; }
        .cart-btn-remove:hover { background: #fee2e2; color: #dc2626; }
        #cart-empty-msg { padding: 80px 0; text-align: center; }
        .empty-cart-icon { font-size: 4rem; margin-bottom: 1rem; opacity: 0.3; }
        .btn-shop-now { display: inline-block; padding: 12px 32px; background: var(--primary-color, #1a3a6b); color: white; border-radius: 6px; font-weight: 700; text-decoration: none; font-size: 0.85rem; text-transform: uppercase; }
        .cart-summary-section {
            width: 350px; /* Lebar tetap untuk sidebar */
            flex-shrink: 0; /* Mencegah sidebar mengecil */
            position: sticky;
            top: 120px;
        }
        .order-summary-box { background: white; border: 1.5px solid #dde4f8; border-radius: 10px; overflow: hidden; }
        .summary-header { background: var(--primary-color, #1a3a6b); padding: 18px 24px; }
        .summary-header h2 { font-size: 1rem; font-weight: 800; color: white; text-transform: uppercase; margin: 0; }
        .summary-body { padding: 20px 24px; display: flex; flex-direction: column; gap: 14px; }
        .summary-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.88rem; color: var(--text-gray, #666); }
        .summary-row .summary-value { font-weight: 700; color: var(--text-dark, #111); }
        .summary-row .summary-value.free { color: #16a34a; }
        /* Itemized breakdown in order summary */
        .summary-items { display: flex; flex-direction: column; gap: 10px; max-height: 260px; overflow-y: auto; }
        .summary-item { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; font-size: 0.82rem; }
        .summary-item-name { color: var(--text-dark, #111); font-weight: 600; line-height: 1.3; }
        .summary-item-qty { color: var(--text-gray, #888); font-weight: 500; }
        .summary-item-price { font-weight: 700; color: var(--primary-color, #1a3a6b); white-space: nowrap; }
        .summary-items-empty { font-size: 0.82rem; color: #888; text-align: center; padding: 6px 0; }
        .summary-divider { height: 1px; background: #f0f0f0; margin: 4px 0; }
        .summary-total-row { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: #f0f4ff; border-top: 1.5px solid #dde4f8; }
        .summary-total-label { font-size: 0.9rem; font-weight: 800; color: var(--text-dark, #111); text-transform: uppercase; }
        .summary-total-value { font-size: 1.15rem; font-weight: 800; color: var(--primary-color, #1a3a6b); }
        .btn-cart-checkout { width: calc(100% - 48px); margin: 16px 24px 24px; padding: 14px; background: var(--primary-color, #1a3a6b); color: white; border: none; border-radius: 6px; font-weight: 800; text-transform: uppercase; font-size: 0.85rem; cursor: pointer; }
        .btn-cart-checkout:disabled { background: #d1d5db; cursor: not-allowed; }
        .cart-loading { display: flex; flex-direction: column; gap: 16px; padding: 20px 0; }
        .skeleton-row { height: 90px; background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%); background-size: 200% 100%; border-radius: 8px; animation: shimmer 1.2s infinite; }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
        @media (max-width: 1100px) {
            .cart-wrapper {
                flex-direction: column;
            }
            .cart-summary-section {
                width: 100%;
                position: static;
            }
            .cart-product-details {
                max-width: 100%;
            }
        }
    </style>
    <script>
        const isLogin = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

        if(!isLogin){
            window.location.replace("/CardHaven/login")
        }
    </script>
</head>
<body>
    <!-- Simpan ID Session agar bisa dibaca JS -->
    <input type="hidden" id="session-id-pengguna" value="<?php echo $session_id_pengguna; ?>">

    <!-- Path NavBar diperbaiki -->
    <?php include '../page-customer/navBar.php'; ?>

    <main class="main-content">
        <div class="cart-page-wrapper">
            <h1 class="cart-page-title">MY <span class="accent">CART</span></h1>

            <div class="cart-wrapper">
                <div class="cart-items-section">
                    <div id="cart-loading-state">
                        <div class="cart-loading">
                            <div class="skeleton-row"></div>
                            <div class="skeleton-row"></div>
                        </div>
                    </div>

                    <div class="cart-toolbar" id="cart-toolbar" style="display:none;">
                        <input type="checkbox" id="select-all-checkbox" onchange="toggleSelectAll(this.checked)">
                        <label for="select-all-checkbox">Select All</label>
                        <span class="cart-item-count" id="cart-item-count"></span>
                    </div>

                    <table class="cart-table" id="cart-main-table" style="display:none;">
                        <thead>
                            <tr>
                                <th width="36"></th>
                                <th>Product</th>
                                <th>Price</th>
                                <th style="text-align:center;">Qty</th>
                                <th>Subtotal</th>
                                <th width="40"></th>
                            </tr>
                        </thead>
                        <tbody id="cart-table-body"></tbody>
                    </table>

                    <div id="cart-empty-msg" style="display:none;">
                        <div class="empty-cart-icon">🛒</div>
                        <p>Your cart is empty. Time to add some cards!</p>
                        <a href="/cardhaven/interface/home/" class="btn-shop-now">Browse Cards</a>
                    </div>
                </div>

                <aside class="cart-summary-section">
                    <div class="order-summary-box">
                        <div class="summary-header"><h2>Order Summary</h2></div>
                        <div class="summary-body">
                            <div id="summary-items" class="summary-items"></div>
                            <div class="summary-divider"></div>
                            <div class="summary-row"><span>Subtotal</span><span class="summary-value" id="subtotal-display">Rp 0</span></div>
                        </div>
                        <div class="summary-total-row">
                            <span class="summary-total-label">Total</span>
                            <span class="summary-total-value" id="grand-total-display">Rp 0</span>
                        </div>
                        <p class="summary-selected-info" id="selected-info" style="text-align:center; font-size:0.8rem; margin:10px 0; color:#888;"></p>
                        <button class="btn-cart-checkout" id="btn-checkout-main" disabled>Proceed to Checkout</button>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <!-- <script>
        // Logika Sinkronisasi Storage
        const sessionIdEl = document.getElementById('session-id-pengguna');
        if (sessionIdEl) {
            const sessionId = sessionIdEl.value;
            if (!localStorage.getItem('id_pengguna')) {
                localStorage.setItem('id_pengguna', sessionId);
            }
            if (localStorage.getItem('id_pengguna') !== sessionId) {
                localStorage.setItem('id_pengguna', sessionId);
            }
        }
    </script> -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/cardhaven/interface/global_alert.js"></script>
    <script src="/cardhaven/interface/cart/keranjang_script.js"></script>
    
</body>
</html>