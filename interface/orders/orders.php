<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: /cardhaven/interface/auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - CardHaven</title>
    <link rel="icon" type="image/svg+xml" href="/cardhaven/assets/image/logo.svg">
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <style>
        /* =========================================
           ORDERS PAGE — CARDHAVEN
           ========================================= */
        .orders-wrapper {
            padding: 2rem 2.75rem 4rem;
            max-width: 1100px;
            margin: 0 auto;
        }

        .orders-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-color, #1a3a6b);
            font-family: 'Coolvetica', sans-serif;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .orders-title .accent { color: #2563EB; }

        /* ---- Tab Filter ---- */
        .order-tabs {
            display: flex;
            gap: 4px;
            border-bottom: 2px solid #eef2ff;
            margin-bottom: 1.5rem;
            overflow-x: auto;
            padding-bottom: 0;
        }

        .order-tab {
            padding: 10px 18px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-gray, #888);
            cursor: pointer;
            border: none;
            background: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            white-space: nowrap;
            transition: all 0.2s;
        }

        .order-tab:hover  { color: #2563EB; }
        .order-tab.active { color: #2563EB; border-bottom-color: #2563EB; }

        /* ---- Order Card ---- */
        .order-card {
            background: white;
            border: 1.5px solid #dde4f8;
            border-radius: 12px;
            margin-bottom: 16px;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }

        .order-card:hover { box-shadow: 0 4px 16px rgba(37,99,235,0.1); }

        .order-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            background: #fafbff;
            border-bottom: 1px solid #eef2ff;
            flex-wrap: wrap;
            gap: 8px;
        }

        .order-id {
            font-size: 0.85rem;
            font-weight: 800;
            color: var(--primary-color, #1a3a6b);
        }

        .order-date {
            font-size: 0.75rem;
            color: var(--text-gray, #888);
        }

        .order-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        /* Status badge colors */
        .badge-0  { background:#fef9c3; color:#ca8a04; }   /* Pending Payment */
        .badge-1  { background:#dcfce7; color:#15803d; }   /* Paid */
        .badge-2  { background:#e0f2fe; color:#0369a1; }   /* Waiting Stock */
        .badge-3  { background:#ede9fe; color:#7c3aed; }   /* Processing */
        .badge-4  { background:#dbeafe; color:#1d4ed8; }   /* Shipped */
        .badge-5  { background:#d1fae5; color:#065f46; }   /* Delivered */
        .badge-6  { background:#d1fae5; color:#14532d; }   /* Completed */
        .badge-7  { background:#fee2e2; color:#b91c1c; }   /* Returned */
        .badge-8  { background:#f3f4f6; color:#6b7280; }   /* Cancelled */

        .order-card-body { padding: 16px 20px; }

        .order-items-preview {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
            overflow: hidden;
        }

        .order-preview-img {
            width: 52px;
            height: 66px;
            border-radius: 5px;
            object-fit: cover;
            border: 1px solid #dde4f8;
            background: #eef2ff;
            flex-shrink: 0;
        }

        .order-items-more {
            width: 52px;
            height: 66px;
            border-radius: 5px;
            border: 1px solid #dde4f8;
            background: #f0f4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            color: #2563EB;
            flex-shrink: 0;
        }

        .order-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 8px;
        }

        .order-total-label { font-size: 0.78rem; color: var(--text-gray, #888); }
        .order-total-val   { font-size: 1.05rem; font-weight: 800; color: var(--primary-color, #1a3a6b); }

        .order-actions { display: flex; gap: 8px; }

        .btn-order-detail {
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary-sm {
            background: var(--primary-color, #1a3a6b);
            color: white;
            border: none;
        }
        .btn-primary-sm:hover { background: #2563EB; }

        .btn-outline-sm {
            background: white;
            color: var(--primary-color, #1a3a6b);
            border: 1.5px solid var(--primary-color, #1a3a6b);
        }
        .btn-outline-sm:hover { background: #eef2ff; }

        .btn-danger-sm {
            background: white;
            color: #dc2626;
            border: 1.5px solid #dc2626;
        }
        .btn-danger-sm:hover { background: #fee2e2; }

        /* ---- Empty / Loading ---- */
        .orders-empty {
            text-align: center;
            padding: 80px 0;
        }

        .orders-empty-icon { font-size: 4rem; margin-bottom: 1rem; }

        .orders-empty p {
            font-size: 0.95rem;
            color: var(--text-gray, #888);
            margin-bottom: 1.5rem;
        }

        .orders-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 60px 0;
        }

        .loading-spinner {
            width: 36px;
            height: 36px;
            border: 3px solid #dde4f8;
            border-top-color: #2563EB;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ---- Complete Button ---- */
        .btn-complete {
            background: #16a34a;
            color: white;
            border: none;
        }
        .btn-complete:hover { background: #15803d; }

        @media (max-width: 640px) {
            .orders-wrapper { padding: 1.5rem 1rem 3rem; }
            .order-card-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <!-- Navbar disini -->

    <main class="main-content">
        <div class="orders-wrapper">
            <h1 class="orders-title">MY <span class="accent">ORDERS</span></h1>

            <!-- Tabs -->
            <div class="order-tabs">
                <button class="order-tab active" onclick="filterOrders('all', this)">All Orders</button>
                <button class="order-tab" onclick="filterOrders('0', this)">Pending Payment</button>
                <button class="order-tab" onclick="filterOrders('1', this)">Paid</button>
                <button class="order-tab" onclick="filterOrders('3', this)">Processing</button>
                <button class="order-tab" onclick="filterOrders('4', this)">Shipped</button>
                <button class="order-tab" onclick="filterOrders('5', this)">Delivered</button>
                <button class="order-tab" onclick="filterOrders('6', this)">Completed</button>
            </div>

            <!-- Loading -->
            <div id="orders-loading" class="orders-loading">
                <div class="loading-spinner"></div>
                <span style="font-size:0.85rem;color:#888;">Loading orders...</span>
            </div>

            <!-- Order List -->
            <div id="order-list" style="display:none;"></div>

            <!-- Empty -->
            <div id="orders-empty" class="orders-empty" style="display:none;">
                <div class="orders-empty-icon">📋</div>
                <p>No orders found.</p>
                <a href="/cardhaven/interface/shop/" class="btn-order-detail btn-primary-sm">Browse Cards</a>
            </div>
        </div>
    </main>

    <!-- Modal Konfirmasi Terima Paket -->
    <div id="modal-confirm-received" style="
        display:none;
        position:fixed;inset:0;
        background:rgba(0,0,0,0.5);
        z-index:9999;
        align-items:center;
        justify-content:center;
    ">
        <div style="
            background:white;
            border-radius:12px;
            padding:32px;
            max-width:420px;
            width:90%;
            text-align:center;
        ">
            <div style="font-size:3rem;margin-bottom:12px;">📬</div>
            <h3 style="font-size:1.2rem;font-weight:800;color:#1a3a6b;margin:0 0 8px;">Confirm Receipt?</h3>
            <p style="font-size:0.88rem;color:#666;margin:0 0 24px;line-height:1.5;">
                Please confirm that you have received your package in good condition.
                This action cannot be undone.
            </p>
            <div style="display:flex;gap:12px;justify-content:center;">
                <button onclick="closeModal()" style="
                    padding:10px 24px;border-radius:6px;font-weight:700;font-size:0.82rem;
                    text-transform:uppercase;letter-spacing:0.8px;cursor:pointer;
                    background:white;border:1.5px solid #dde4f8;color:#666;
                ">Cancel</button>
                <button id="btn-confirm-received" onclick="confirmReceived()" style="
                    padding:10px 24px;border-radius:6px;font-weight:700;font-size:0.82rem;
                    text-transform:uppercase;letter-spacing:0.8px;cursor:pointer;
                    background:#1a3a6b;color:white;border:none;
                ">Yes, Received</button>
            </div>
        </div>
    </div>

    <script>
        const ORDERS_CONTROLLER = '/cardhaven/interface/orders/controller_orders.php';
        let allOrders     = [];
        let currentFilter = 'all';
        let pendingOrderId = null;

        const STATUS_LABEL = {
            0:'Pending Payment', 1:'Paid', 2:'Waiting Stock',
            3:'Processing', 4:'Shipped', 5:'Delivered',
            6:'Completed', 7:'Returned', 8:'Cancelled'
        };
        const STATUS_ICON = {
            0:'⏳',1:'✅',2:'📦',3:'⚙️',4:'🚚',5:'🏠',6:'🎉',7:'↩️',8:'❌'
        };

        document.addEventListener('DOMContentLoaded', loadOrders);

        function loadOrders() {
            fetch(`${ORDERS_CONTROLLER}?action=get_orders`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('orders-loading').style.display = 'none';
                    allOrders = data || [];
                    renderOrders(allOrders);
                })
                .catch(err => {
                    document.getElementById('orders-loading').style.display = 'none';
                    document.getElementById('order-list').innerHTML =
                        '<p style="color:#dc2626;text-align:center;padding:40px 0;">Failed to load orders.</p>';
                    document.getElementById('order-list').style.display = 'block';
                    console.error(err);
                });
        }

        function filterOrders(status, btn) {
            currentFilter = status;
            document.querySelectorAll('.order-tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            const filtered = status === 'all'
                ? allOrders
                : allOrders.filter(o => String(o.status_penjualan) === status);
            renderOrders(filtered);
        }

        function renderOrders(orders) {
            const list  = document.getElementById('order-list');
            const empty = document.getElementById('orders-empty');
            list.innerHTML = '';

            if (!orders || orders.length === 0) {
                list.style.display  = 'none';
                empty.style.display = 'block';
                return;
            }

            list.style.display  = 'block';
            empty.style.display = 'none';

            orders.forEach(o => list.appendChild(buildOrderCard(o)));
        }

        function buildOrderCard(o) {
            const div   = document.createElement('div');
            div.className = 'order-card';
            const st    = parseInt(o.status_penjualan);
            const fmt   = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n));

            // Preview images (max 4)
            const previewItems = (o.items || []).slice(0, 4);
            const extraCount   = (o.items || []).length - 4;
            let previewHtml = previewItems.map(item =>
                `<img class="order-preview-img"
                      src="/CardHaven/${escHtml(item.foto)}"
                      alt="${escHtml(item.nama_produk)}"
                      onerror="this.src='/cardhaven/interface/assets/img/no-image.png'">`
            ).join('');
            if (extraCount > 0) {
                previewHtml += `<div class="order-items-more">+${extraCount}</div>`;
            }

            // Action buttons per status
            let actionBtns = `
                <a href="/cardhaven/interface/orders/detail.php?id=${o.id_penjualan}"
                   class="btn-order-detail btn-primary-sm">View Details</a>
            `;
            // Status 0 (pending): bisa upload bukti jika belum ada
            if (st === 0 && !o.bukti_pembayaran) {
                actionBtns += `
                    <a href="/cardhaven/interface/checkout/?upload=1&id=${o.id_penjualan}"
                       class="btn-order-detail btn-outline-sm">Upload Payment</a>
                `;
            }
            // Status 5 (delivered): bisa konfirmasi terima
            if (st === 5) {
                actionBtns += `
                    <button class="btn-order-detail btn-complete"
                            onclick="openConfirmReceived(${o.id_penjualan})">
                        Order Received
                    </button>
                `;
            }
            // Status 0 atau 1 (belum diproses): bisa cancel
            if (st === 0 || st === 1) {
                actionBtns += `
                    <button class="btn-order-detail btn-danger-sm"
                            onclick="cancelOrder(${o.id_penjualan})">
                        Cancel
                    </button>
                `;
            }

            div.innerHTML = `
                <div class="order-card-header">
                    <div>
                        <div class="order-id">#${o.id_penjualan}</div>
                        <div class="order-date">${formatDate(o.tanggal_penjualan)}</div>
                    </div>
                    <span class="order-status-badge badge-${st}">
                        ${STATUS_ICON[st] || '●'} ${STATUS_LABEL[st] || 'Unknown'}
                    </span>
                </div>
                <div class="order-card-body">
                    <div class="order-items-preview">${previewHtml}</div>
                    <div class="order-card-footer">
                        <div>
                            <div class="order-total-label">
                                ${o.total_barang} item${o.total_barang > 1 ? 's' : ''} · Total
                            </div>
                            <div class="order-total-val">${fmt(o.total_harga)}</div>
                        </div>
                        <div class="order-actions">${actionBtns}</div>
                    </div>
                </div>
            `;
            return div;
        }

        // ---- Konfirmasi Diterima ----
        function openConfirmReceived(id) {
            pendingOrderId = id;
            const modal = document.getElementById('modal-confirm-received');
            modal.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('modal-confirm-received').style.display = 'none';
            pendingOrderId = null;
        }

        function confirmReceived() {
            if (!pendingOrderId) return;
            const btn = document.getElementById('btn-confirm-received');
            btn.disabled    = true;
            btn.textContent = 'Confirming...';

            const fd = new FormData();
            fd.append('action',       'confirm_received');
            fd.append('id_penjualan', pendingOrderId);

            fetch(ORDERS_CONTROLLER, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(json => {
                    closeModal();
                    if (json.success) {
                        loadOrders();
                    } else {
                        alert(json.message || 'Failed to confirm receipt.');
                    }
                })
                .catch(err => { closeModal(); console.error(err); });
        }

        // ---- Cancel Order ----
        function cancelOrder(id) {
            if (!confirm(`Cancel order #${id}? This action cannot be undone.`)) return;
            const fd = new FormData();
            fd.append('action',       'cancel_order');
            fd.append('id_penjualan', id);

            fetch(ORDERS_CONTROLLER, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(json => {
                    if (json.success) loadOrders();
                    else alert(json.message || 'Failed to cancel order.');
                })
                .catch(console.error);
        }

        function formatDate(dtStr) {
            if (!dtStr) return '-';
            const d = new Date(dtStr);
            return d.toLocaleDateString('id-ID', {
                day:'2-digit', month:'long', year:'numeric',
                hour:'2-digit', minute:'2-digit'
            });
        }

        function escHtml(s) {
            if (!s) return '';
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;')
                            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }
    </script>
</body>
</html>
