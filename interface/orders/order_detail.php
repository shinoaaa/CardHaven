<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: /cardhaven/interface/auth/login.php");
    exit;
}
$id_penjualan = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_penjualan) {
    header("Location: /cardhaven/interface/orders/");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Detail - CardHaven</title>
    <link rel="icon" type="image/svg+xml" href="/cardhaven/assets/image/logo.svg">
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <style>
        .detail-wrapper {
            padding: 2rem 2.75rem 4rem;
            max-width: 900px;
            margin: 0 auto;
        }

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
        }
        .back-link:hover { color: #2563EB; }

        .detail-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-color, #1a3a6b);
            font-family: 'Coolvetica', sans-serif;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
        }
        .detail-title .accent { color: #2563EB; }

        /* ---- Status Timeline ---- */
        .status-timeline {
            display: flex;
            gap: 0;
            margin-bottom: 2rem;
            background: white;
            border: 1.5px solid #dde4f8;
            border-radius: 10px;
            padding: 20px 24px;
            overflow-x: auto;
        }

        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
            min-width: 80px;
        }

        .timeline-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 16px;
            left: calc(50% + 16px);
            right: calc(-50% + 16px);
            height: 2px;
            background: #e0e7ff;
        }

        .timeline-step.done::after,
        .timeline-step.active::after { background: #2563EB; }

        .timeline-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e0e7ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
        }

        .timeline-step.done   .timeline-dot { background: #16a34a; }
        .timeline-step.active .timeline-dot {
            background: #2563EB;
            box-shadow: 0 0 0 4px rgba(37,99,235,0.15);
        }
        .timeline-step.cancelled .timeline-dot { background: #dc2626; }
        .timeline-step.returned  .timeline-dot { background: #f59e0b; }

        .timeline-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #93a3c4;
            text-align: center;
            line-height: 1.3;
        }

        .timeline-step.done   .timeline-label { color: #15803d; }
        .timeline-step.active .timeline-label { color: #2563EB; }

        /* ---- Info Cards ---- */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .info-card {
            background: white;
            border: 1.5px solid #dde4f8;
            border-radius: 10px;
            overflow: hidden;
        }

        .info-card-header {
            background: var(--primary-color, #1a3a6b);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-card-header h3 {
            font-size: 0.78rem;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin: 0;
        }

        .info-card-body { padding: 16px 20px; }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            gap: 16px;
        }

        .info-row:last-child { margin-bottom: 0; }

        .info-label {
            font-size: 0.75rem;
            color: var(--text-gray, #888);
            font-weight: 600;
            flex-shrink: 0;
        }

        .info-value {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-dark, #111);
            text-align: right;
            word-break: break-word;
        }

        /* ---- Items Table ---- */
        .items-card {
            background: white;
            border: 1.5px solid #dde4f8;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th {
            padding: 12px 20px;
            text-align: left;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-gray, #888);
            background: #fafbff;
            border-bottom: 1px solid #eef2ff;
        }

        .items-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .items-table tbody tr:last-child td { border-bottom: none; }

        .item-img-sm {
            width: 48px;
            height: 60px;
            border-radius: 5px;
            object-fit: cover;
            border: 1px solid #dde4f8;
            background: #eef2ff;
        }

        .item-name {
            font-weight: 700;
            font-size: 0.88rem;
            color: var(--text-dark, #111);
        }

        .item-kondisi {
            font-size: 0.72rem;
            color: #888;
        }

        /* ---- Proof of Payment ---- */
        .bukti-img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            border: 1.5px solid #dde4f8;
            object-fit: contain;
            display: block;
        }

        /* ---- Total Footer ---- */
        .total-footer {
            background: #f0f4ff;
            border: 1.5px solid #dde4f8;
            border-radius: 10px;
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.88rem;
            color: var(--text-gray, #666);
        }

        .total-row.grand {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--primary-color, #1a3a6b);
            padding-top: 10px;
            border-top: 1.5px solid #dde4f8;
        }

        .loading-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 80px 0;
        }

        .spinner {
            width: 36px;
            height: 36px;
            border: 3px solid #dde4f8;
            border-top-color: #2563EB;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 700px) {
            .detail-grid { grid-template-columns: 1fr; }
            .detail-wrapper { padding: 1.5rem 1rem 3rem; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <main class="main-content">
        <div class="detail-wrapper">
            <a href="/cardhaven/interface/orders/" class="back-link">← My Orders</a>

            <div id="detail-loading" class="loading-block">
                <div class="spinner"></div>
                <span style="font-size:0.85rem;color:#888;">Loading order details...</span>
            </div>

            <div id="detail-content" style="display:none;"></div>
        </div>
    </main>

    <script>
        const ORDER_ID = <?= $id_penjualan ?>;
        const ORDERS_CONTROLLER = '/cardhaven/interface/orders/controller_orders.php';

        const STATUS_LABEL = {
            0:'Pending Payment', 1:'Paid', 2:'Waiting Stock',
            3:'Processing', 4:'Shipped', 5:'Delivered',
            6:'Completed', 7:'Returned', 8:'Cancelled'
        };

        // Timeline steps untuk order normal (non-cancelled/returned)
        const TIMELINE_STEPS = [
            { status: 0, icon: '⏳', label: 'Pending Payment' },
            { status: 1, icon: '✅', label: 'Paid' },
            { status: 3, icon: '⚙️', label: 'Processing' },
            { status: 4, icon: '🚚', label: 'Shipped' },
            { status: 5, icon: '🏠', label: 'Delivered' },
            { status: 6, icon: '🎉', label: 'Completed' },
        ];

        document.addEventListener('DOMContentLoaded', loadDetail);

        function loadDetail() {
            fetch(`${ORDERS_CONTROLLER}?action=get_order_detail&id=${ORDER_ID}`)
                .then(r => r.json())
                .then(json => {
                    document.getElementById('detail-loading').style.display = 'none';
                    if (json.success) {
                        renderDetail(json.order);
                        document.getElementById('detail-content').style.display = 'block';
                    } else {
                        document.getElementById('detail-content').innerHTML =
                            `<p style="color:#dc2626;text-align:center;padding:40px 0;">${json.message}</p>`;
                        document.getElementById('detail-content').style.display = 'block';
                    }
                })
                .catch(err => console.error(err));
        }

        function renderDetail(o) {
            const fmt   = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n));
            const st    = parseInt(o.status_penjualan);
            const isCancelled = st === 8;
            const isReturned  = st === 7;

            // ---- Title + Status badge ----
            const badgeClass = {
                0:'background:#fef9c3;color:#ca8a04',
                1:'background:#dcfce7;color:#15803d',
                2:'background:#e0f2fe;color:#0369a1',
                3:'background:#ede9fe;color:#7c3aed',
                4:'background:#dbeafe;color:#1d4ed8',
                5:'background:#d1fae5;color:#065f46',
                6:'background:#d1fae5;color:#14532d',
                7:'background:#fef3c7;color:#92400e',
                8:'background:#fee2e2;color:#b91c1c',
            }[st] || 'background:#f3f4f6;color:#6b7280';

            // ---- Timeline ----
            let timelineHtml = '';
            if (!isCancelled && !isReturned) {
                timelineHtml = '<div class="status-timeline">';
                TIMELINE_STEPS.forEach((step, i) => {
                    let cls = '';
                    if (step.status < st)  cls = 'done';
                    if (step.status === st) cls = 'active';
                    timelineHtml += `
                        <div class="timeline-step ${cls}">
                            <div class="timeline-dot">${step.icon}</div>
                            <div class="timeline-label">${step.label}</div>
                        </div>`;
                });
                timelineHtml += '</div>';
            } else {
                timelineHtml = `<div style="
                    text-align:center;padding:16px;
                    background:${isCancelled ? '#fee2e2' : '#fef3c7'};
                    border-radius:8px;margin-bottom:1.5rem;
                    font-weight:700;color:${isCancelled ? '#dc2626' : '#92400e'};
                    font-size:0.9rem;
                ">
                    ${isCancelled ? '❌ This order has been cancelled.' : '↩️ This order has been returned.'}
                </div>`;
            }

            // ---- Items rows ----
            let itemsHtml = (o.items || []).map(item => `
                <tr>
                    <td>
                        <img class="item-img-sm"
                             src="/CardHaven/${escHtml(item.foto)}"
                             alt="${escHtml(item.nama_produk)}"
                             onerror="this.src='/cardhaven/interface/assets/img/no-image.png'">
                    </td>
                    <td>
                        <div class="item-name">${escHtml(item.nama_produk)}</div>
                        <div class="item-kondisi">${escHtml(item.kondisi || 'Official Card')}</div>
                    </td>
                    <td>${fmt(item.harga_produk)}</td>
                    <td style="text-align:center;">${item.jumlah_barang}</td>
                    <td style="font-weight:800;color:#1a3a6b;">${fmt(item.subtotal_harga)}</td>
                </tr>
            `).join('');

            // ---- Proof of payment ----
            const buktiHtml = o.bukti_pembayaran
                ? `<img src="/CardHaven/${escHtml(o.bukti_pembayaran)}" class="bukti-img" alt="Payment Proof"
                        onerror="this.outerHTML='<p style=color:#888>Preview unavailable</p>'">`
                : `<p style="color:#888;font-size:0.85rem;">No payment proof uploaded yet.</p>`;

            // ---- Resi ----
            const resiHtml = o.no_resi
                ? `<div style="
                    background:#f0f4ff;border:1.5px solid #dde4f8;
                    border-radius:8px;padding:14px 18px;margin-bottom:16px;
                    display:flex;align-items:center;gap:12px;
                   ">
                    <span style="font-size:1.4rem;">🚚</span>
                    <div>
                        <div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.8px;color:#888;font-weight:700;">Tracking Number</div>
                        <div style="font-size:1rem;font-weight:800;color:#1a3a6b;">${escHtml(o.no_resi)}</div>
                    </div>
                </div>` : '';

            // ---- Fee ----
            const feeAmount = parseFloat(o.biaya_admin) || 0;
            const feeRow    = feeAmount > 0
                ? `<div class="total-row"><span>Payment Fee (${escHtml(o.nama_metode)})</span><span>+${fmt(feeAmount)}</span></div>`
                : '';

            const content = document.getElementById('detail-content');
            content.innerHTML = `
                <h1 class="detail-title">
                    ORDER <span class="accent">#${o.id_penjualan}</span>
                    <span style="font-size:0.85rem;font-weight:700;padding:4px 14px;border-radius:20px;
                          vertical-align:middle;margin-left:12px;${badgeClass}">
                        ${STATUS_LABEL[st] || 'Unknown'}
                    </span>
                </h1>

                ${timelineHtml}
                ${resiHtml}

                <div class="detail-grid">
                    <!-- Shipping Info -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <span>📦</span>
                            <h3>Shipping Info</h3>
                        </div>
                        <div class="info-card-body">
                            <div class="info-row">
                                <span class="info-label">Order Date</span>
                                <span class="info-value">${formatDate(o.tanggal_penjualan)}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Ship Date</span>
                                <span class="info-value">${o.tanggal_pengiriman ? formatDate(o.tanggal_pengiriman) : '-'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Address</span>
                                <span class="info-value">${escHtml(o.alamat) || '-'}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Info -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <span>💳</span>
                            <h3>Payment Info</h3>
                        </div>
                        <div class="info-card-body">
                            <div class="info-row">
                                <span class="info-label">Method</span>
                                <span class="info-value">${escHtml(o.nama_metode) || '-'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Account</span>
                                <span class="info-value">
                                    ${o.no_rekening ? escHtml(o.no_rekening) : '-'}
                                    ${o.atas_nama ? '<br>a/n ' + escHtml(o.atas_nama) : ''}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="items-card">
                    <div class="info-card-header">
                        <span>🃏</span>
                        <h3>Order Items</h3>
                    </div>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th width="60"></th>
                                <th>Product</th>
                                <th>Price</th>
                                <th style="text-align:center;">Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>${itemsHtml}</tbody>
                    </table>
                </div>

                <!-- Payment Proof -->
                <div class="info-card" style="margin-bottom:16px;">
                    <div class="info-card-header">
                        <span>🖼️</span>
                        <h3>Payment Proof</h3>
                    </div>
                    <div class="info-card-body">${buktiHtml}</div>
                </div>

                <!-- Total -->
                <div class="total-footer">
                    <div class="total-row">
                        <span>Subtotal (${o.total_barang} items)</span>
                        <span>${fmt(parseFloat(o.total_harga) - feeAmount)}</span>
                    </div>
                    <div class="total-row"><span>Shipping</span><span style="color:#16a34a;">Free</span></div>
                    ${feeRow}
                    <div class="total-row grand">
                        <span>Total Payment</span>
                        <span>${fmt(o.total_harga)}</span>
                    </div>
                </div>
            `;
        }

        function formatDate(s) {
            if (!s) return '-';
            return new Date(s).toLocaleDateString('id-ID', {
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
