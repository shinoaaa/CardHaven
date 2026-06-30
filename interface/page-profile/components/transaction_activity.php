<div class="transaction-card">
    <div class="transaction-tabs">
        <button class="tab-btn" onclick="switchTab('preorder')">Pre-Order</button>
        <button class="tab-btn active" onclick="switchTab('buyproduct')">Buy Product</button>
        <button class="tab-btn" onclick="switchTab('buyback')">Buy Back</button>
    </div>

    <div class="transaction-toolbar">
        <div class="search-box">
            <img src="/cardhaven/assets/image/search.svg" alt="Search">
            <input type="text" placeholder="Search Event Name...">
        </div>
        <button class="filter-btn">Status ▼</button>
        <button class="filter-btn">Price ▼</button>
        <button class="sort-btn">
            <span>↑</span> 
            <span>↓</span>
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

    <!-- Buy Product Table (Active default based on design reference) -->
    <div id="tab-buyproduct" class="tab-content active">
        <div class="table-responsive">
            <table class="cardhaven-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Payment Method</th>
                        <th>Order Date</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th>Total Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Row berulang sesuai gambar Figma -->
                    <tr>
                        <td>1</td>
                        <td>Dana</td>
                        <td>20-05-2025</td>
                        <td>Jl. Diponegoro<br>No.5, Medan</td>
                        <td><span class="status-pill status-red">Delivered</span></td>
                        <td>$10.22</td>
                        <td><button class="action-menu-btn"><img src="/cardhaven/assets/icon/dots.svg" alt="..."></button></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Dana</td>
                        <td>20-05-2025</td>
                        <td>Jl. Diponegoro<br>No.5, Medan</td>
                        <td><span class="status-pill status-red">Delivered</span></td>
                        <td>$10.22</td>
                        <td><button class="action-menu-btn"><img src="/cardhaven/assets/icon/dots.svg" alt="..."></button></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Dana</td>
                        <td>20-05-2025</td>
                        <td>Jl. Diponegoro<br>No.5, Medan</td>
                        <td><span class="status-pill status-red">Delivered</span></td>
                        <td>$10.22</td>
                        <td><button class="action-menu-btn"><img src="/cardhaven/assets/icon/dots.svg" alt="..."></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Buyback Table -->
    <div id="tab-buyback" class="tab-content" style="display: none;">
        <div class="table-responsive">
            <table class="cardhaven-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Item Name</th>
                        <th>Deal Date</th>
                        <th>Total Product</th>
                        <th>Status</th>
                        <th>Total Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="7" style="text-align: center;">No Buy Back records yet.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination dummy dari gambar -->
    <div class="pagination">
        <button class="page-arrow">‹</button>
        <button class="page-num">1</button>
        <button class="page-num active">2</button>
        <button class="page-num">3</button>
        <button class="page-num">4</button>
        <button class="page-num">5</button>
        <span class="page-dots">...</span>
        <button class="page-num">82</button>
        <button class="page-arrow">›</button>
    </div>
</div>