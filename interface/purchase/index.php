<?php
session_start();
// NOTE: validasi role untuk fitur Purchase sementara dimatikan (semua role bisa create/approve/reject).
// Nanti aktifkan lagi setelah sistem session/login role-nya jelas.
$role = (int)($_SESSION['role'] ?? 0);

// Toggle jenis purchase: restok (default) atau buyback.
$type = $_GET['type'] ?? 'restok';

// Label status buyback untuk dropdown Filter By.
$BUYBACK_STATUS = [
    0 => 'Pending Submission', 1 => 'Under Review', 2 => 'Price Negotiation',
    3 => 'Offer Accepted', 4 => 'Card Shipped', 5 => 'Card Received',
    6 => 'Quality Checked', 7 => 'Payment Sent', 8 => 'Completed',
    9 => 'Rejected', 10 => 'Cancelled',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase — <?= $type === 'buyback' ? 'Buyback' : 'Restok' ?></title>
    <style>
        .trx-row { cursor: pointer; }
        .trx-row:hover td { background-color: rgba(15, 56, 145, 0.05) !important; }
        .event-modal-close {
            position: absolute; top: 1rem; right: 1rem; background: none; border: none;
            cursor: pointer; opacity: .55; padding: 0; font-size: 24px;
        }
        .event-modal-close:hover { opacity: 1; }
        .purchase-filter-input {
            padding: 8px 16px; border: 1.5px solid #D0DAF0; border-radius: 9999px;
            font-size: 0.88rem; outline: none; background: white;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <h1 class="coolveticaa" style="color:#a0beff; font-size:1.5rem; font-weight:700;">
            Dashboard / Purchase
        </h1>

        <div class="content-card" style="min-height: 540px;">

            <!-- Title row + Toggle Restok | Buyback -->
            <div class="card-title-row" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 class="coolveticaa" style="margin:0;"><?= $type === 'buyback' ? 'Buyback (Card Purchases)' : 'Restock (Purchase Orders)' ?></h2>

                <div style="display:flex; align-items:center; gap:1rem;">
                    <?php if ($type === 'restok'): ?>
                        <button class="btn-add-green" id="btnBuatPO" onclick="openAddRestokModal()" style="display:none;">+ Add PO</button>
                    <?php endif; ?>

                    <div style="display: flex; background: rgba(0,0,0,0.05); padding: 4px; border-radius: 999px;">
                        <a href="?type=restok" style="text-decoration:none; padding:8px 24px; border-radius:999px; font-weight:700; font-size:0.9rem; transition:0.2s; <?= $type === 'restok' ? 'background: var(--primary-color); color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.1);' : 'color: #555;' ?>">Restok</a>
                        <a href="?type=buyback" style="text-decoration:none; padding:8px 24px; border-radius:999px; font-weight:700; font-size:0.9rem; transition:0.2s; <?= $type === 'buyback' ? 'background: var(--primary-color); color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.1);' : 'color: #555;' ?>">Buyback</a>
                    </div>
                </div>
            </div>

            <?php if ($type === 'restok'): ?>
                <!-- ================== RESTOK ================== -->
                <!-- Filter bar: Filter By + Sort By + Asc/Desc satu baris, Search full width di bawah -->
                <div style="display:flex; flex-direction:column; gap:0.75rem; margin-bottom:1.25rem;">
                    <div style="display:flex; gap:0.75rem; flex-wrap:wrap; align-items:center;">
                        <select id="statusFilter" class="purchase-filter-input" onchange="applyRestokFilter(1)" style="width:200px; cursor:pointer;">
                            <option value="">All Status</option>
                            <option value="0">Pending</option>
                            <option value="1">Approved</option>
                            <option value="2">Rejected</option>
                            <option value="3">Received</option>
                            <option value="4">Paid</option>
                        </select>

                        <select id="restokSort" class="purchase-filter-input" onchange="changeRestokSort()" style="width:180px; cursor:pointer;">
                            <option value="DATE">Sort: Date</option>
                            <option value="PRICE">Sort: Total Price</option>
                            <option value="QTY">Sort: Total Items</option>
                        </select>

                        <button id="btnRestokSortOrder" class="purchase-filter-input" onclick="toggleRestokSortOrder()"
                            style="width:150px; cursor:pointer; font-weight:700; color:var(--primary-color);">Descending ↓</button>
                    </div>

                    <input type="text" id="searchInput" class="purchase-filter-input" placeholder="Search supplier or PO ID..."
                        style="width:100%; box-sizing:border-box;" oninput="debounceSearch()">
                </div>

                <!-- Table -->
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th>Total Items</th>
                            <th>Total Price</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="restokTableBody">
                        <tr><td colspan="8" style="color:#999;">Loading...</td></tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination-container" id="restokPagination"></div>

            <?php elseif ($type === 'buyback'): ?>
                <!-- ================== BUYBACK ================== -->
                <!-- Filter bar: Filter By + Sort By + Asc/Desc satu baris, Search full width di bawah -->
                <div style="display:flex; flex-direction:column; gap:0.75rem; margin-bottom:1.25rem;">
                    <div style="display:flex; gap:0.75rem; flex-wrap:wrap; align-items:center;">
                        <select id="buybackStatusFilter" class="purchase-filter-input" onchange="setBuybackStatus(this.value)" style="width:200px; cursor:pointer;">
                            <option value="">All Status</option>
                            <?php foreach ($BUYBACK_STATUS as $sid => $slabel): ?>
                                <option value="<?= $sid ?>"><?= $slabel ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select id="buybackSort" class="purchase-filter-input" onchange="changeBuybackSort()" style="width:180px; cursor:pointer;">
                            <option value="DATE">Sort: Date</option>
                            <option value="PRICE">Sort: Total Offer</option>
                        </select>

                        <button id="btnBuybackSortOrder" class="purchase-filter-input" onclick="toggleBuybackSortOrder()"
                            style="width:150px; cursor:pointer; font-weight:700; color:var(--primary-color);">Descending ↓</button>
                    </div>

                    <input type="text" id="buybackSearch" class="purchase-filter-input" placeholder="Search customer, order ID, date, or offer..."
                        style="width:100%; box-sizing:border-box;" oninput="handleBuybackSearch()">
                </div>

                <!-- Table -->
                <table class="styled-table" id="tableAdmin">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total Offer</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination-container" id="buybackPagination"></div>
            <?php endif; ?>

        </div>
    </div>

    <?php if ($type === 'restok'): ?>
        <?php include 'modal_restok.php'; ?>
        <?php include 'modal_add_restok.php'; ?>
        <script src="/cardhaven/interface/purchase/restok_script.js?v=<?= time() ?>"></script>

    <?php elseif ($type === 'buyback'): ?>
        <!-- Buyback detail modal (dipindahkan dari halaman Transaction) -->
        <div id="detailModal" class="event-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 900; justify-content: center; align-items: flex-start; padding: 2rem 1rem; overflow-y: auto;" onclick="closeDetailModal()">
            <div class="modal-box" style="width: 650px; max-width: 95vw;" onclick="event.stopPropagation()">
                <button class="event-modal-close" onclick="closeDetailModal()">&times;</button>
                <div class="modal-header" style="border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px;">
                    <h2 style="font-size: 1.5rem; margin: 0 0 5px 0;">Transaction <span class="blue-text" id="modalTxId"></span></h2>
                    <span class="game-id" id="modalStatus" style="font-weight: 600;"></span>
                </div>
                <div id="modalContent" style="max-height: 50vh; overflow-y: auto; padding-right: 10px;"></div>
                <div class="modal-footer" id="modalFooter" style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;"></div>
            </div>
        </div>
        <script src="/cardhaven/interface/buyback/buyback_admin_script.js?v=<?= time() ?>"></script>
    <?php endif; ?>

</body>
</html>
