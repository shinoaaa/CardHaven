<div class="transaction-card">
    <div class="transaction-tabs">
        <button class="tab-btn" onclick="switchTab('preorder')">Pre-Order</button>
        <button class="tab-btn active" onclick="switchTab('buyproduct')">Buy Product</button>
        <button class="tab-btn" onclick="switchTab('buyback')">Buy Back</button>
    </div>

    <div class="transaction-toolbar" id="bp-toolbar">
        <div class="search-box">
            <img src="/cardhaven/assets/image/search.svg" alt="Search">
            <input type="text" id="bp-search" placeholder="Search order ID, payment, address..." oninput="onOrderFilterChange()">
        </div>
        <select class="filter-btn" id="bp-status" onchange="onOrderFilterChange()">
            <option value="">All Status</option>
            <option value="0">Pending Payment</option>
            <option value="1">Paid</option>
            <option value="2">Waiting Stock</option>
            <option value="3">Processing</option>
            <option value="4">Shipped</option>
            <option value="5">Delivered</option>
            <option value="6">Completed</option>
            <option value="7">Returned</option>
            <option value="8">Cancelled</option>
        </select>
        <select class="filter-btn" id="bp-sortby" onchange="onOrderFilterChange()">
            <option value="date">Sort by Date</option>
            <option value="price">Sort by Price</option>
            <option value="items">Sort by Items</option>
        </select>
        <button class="sort-btn" id="bp-sort" onclick="toggleOrderDateSort()" title="Sort by date">
            <span id="bp-sort-icon">↓</span>
        </button>
    </div>

    <!-- Preorder Table -->
    <div id="tab-preorder" class="tab-content" style="display: none;">
        <div class="table-responsive">
            <table class="cardhaven-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Item Name</th>
                        <th>Estimated Time<br>of Arrival</th>
                        <th>Total Product</th>
                        <th>Status</th>
                        <th>Total Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="7" style="text-align: center;">No Pre-order records yet.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Buy Product Table -->
    <div id="tab-buyproduct" class="tab-content active">
        <div class="table-responsive">
            <table class="cardhaven-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Payment Method</th>
                        <th>Order Date</th>
                        <th>Total Items</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="buyproduct-body">
                    <tr><td colspan="7" style="text-align: center;">Loading orders...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Buyback Table (riwayat asli, sama seperti interface/buyback/customer.php) -->
    <div id="tab-buyback" class="tab-content" style="display: none;">
        <!-- Toolbar khusus Buy Back (search + filter status buyback + sort) -->
        <div class="transaction-toolbar" id="bb-toolbar">
            <div class="search-box">
                <img src="/cardhaven/assets/image/search.svg" alt="Search">
                <input type="text" id="bb-search" placeholder="Search transaction ID or status..." oninput="onBuybackFilterChange()">
            </div>
            <select class="filter-btn" id="bb-status" onchange="onBuybackFilterChange()">
                <option value="">All Status</option>
                <option value="0">Pending Submission</option>
                <option value="1">Under Review</option>
                <option value="2">Price Negotiation</option>
                <option value="3">Offer Accepted</option>
                <option value="4">Card Shipped</option>
                <option value="5">Card Received</option>
                <option value="6">Quality Checked</option>
                <option value="7">Payment Sent</option>
                <option value="8">Completed</option>
                <option value="9">Rejected</option>
                <option value="10">Cancelled</option>
            </select>
            <select class="filter-btn" id="bb-sortby" onchange="onBuybackFilterChange()">
                <option value="date">Sort by Date</option>
                <option value="price">Sort by Price</option>
                <option value="items">Sort by Items</option>
            </select>
            <button class="sort-btn" id="bb-sort" onclick="toggleBuybackDateSort()" title="Sort by date">
                <span id="bb-sort-icon">↓</span>
            </button>
        </div>

        <div class="table-responsive">
            <table class="cardhaven-table" id="tableRiwayat">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Transaction ID</th>
                        <th>Deal Date</th>
                        <th>Total Product</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="buyback-history-body">
                    <tr><td colspan="7" style="text-align: center;">Loading buyback history...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="pagination" id="bb-pagination"></div>
    </div>

    <!-- Pagination (dynamic; driven by the active tab) -->
    <div class="pagination" id="bp-pagination"></div>
</div>

<!-- Order Detail Modal (opened by ••• action button) -->
<div id="orderDetailOverlay" class="order-detail-overlay" onclick="closeOrderDetail(event)">
    <div class="order-detail-box" onclick="event.stopPropagation()">
        <button class="order-detail-close" onclick="closeOrderDetail()">&times;</button>
        <div id="orderDetailContent">
            <div style="text-align:center; padding:2rem; color:#888;">Loading...</div>
        </div>
    </div>
</div>

<style>
    /* Toolbar controls as selects, keep the pill look */
    select.filter-btn { -webkit-appearance:none; appearance:none; cursor:pointer; padding-right:26px;
        background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23fff' stroke-width='1.5' fill='none'/%3E%3C/svg%3E");
        background-repeat:no-repeat; background-position:right 10px center; }

    /* Blue ••• action button */
    .action-dots-btn {
        border: none; background: var(--primary-color, #1a3a6b); color: #fff;
        width: 40px; height: 26px; border-radius: 6px; cursor: pointer;
        font-weight: 800; letter-spacing: 1px; line-height: 1; font-size: 0.9rem;
        display: inline-flex; align-items: center; justify-content: center;
        transition: filter .15s;
    }
    .action-dots-btn:hover { filter: brightness(1.15); }

    /* Order detail modal */
    .order-detail-overlay {
        display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5);
        z-index: 1200; justify-content: center; align-items: flex-start;
        padding: 3rem 1rem; overflow-y: auto;
    }
    .order-detail-overlay.show { display: flex; }
    .order-detail-box {
        background: #fff; border-radius: 14px; padding: 1.75rem; width: min(620px, 96vw);
        position: relative; box-shadow: 0 12px 50px rgba(0,0,0,.35); margin: auto;
    }
    .order-detail-close {
        position: absolute; top: 1rem; right: 1rem; background: none; border: none;
        font-size: 26px; cursor: pointer; opacity: .5; line-height: 1;
    }
    .order-detail-close:hover { opacity: 1; }
    .od-item-row { display: flex; align-items: center; gap: .75rem; padding: .5rem 0; border-bottom: 1px solid #f0f0f0; }
    .od-item-row img { width: 42px; height: 42px; border-radius: 6px; object-fit: cover; flex-shrink: 0; }
</style>
