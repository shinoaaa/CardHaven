<!-- 
  Pop-up event container — inject this into your existing page.
  The <div id="pop-up-event"> was already referenced in your code,
  so this replaces / fills that element. Include the script and style
  tags wherever appropriate (e.g. in your main layout head/body).
-->

<!-- ===== OVERLAY BACKDROP ===== -->
<div id="pop-up-overlay" style="
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1040;
    background: rgba(13,71,161,.45);
    backdrop-filter: blur(3px);
" onclick="closePromoEvent()"></div>

<!-- ===== POP-UP WRAPPER ===== -->
<div id="pop-up-event" style="
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1050;
    width: 960px;
    max-width: 96vw;
    border-radius: 25px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(13,71,161,.3);
">

    <!-- ──────────────────────────────────────────── -->
    <!--  VIEW 1 : Detail Event Promo                 -->
    <!-- ──────────────────────────────────────────── -->
    <div id="view-detail" style="
        background: linear-gradient(180deg,#fff 0%,#dae6ff 100%);
        border-radius: 25px;
        padding: 36px 40px 36px 36px;
        position: relative;
        min-height: 550px;
        display: flex;
        gap: 40px;
        align-items: flex-start;
    ">

        <!-- Close Button -->
        <button onclick="closePromoEvent()" title="Close" style="
            position: absolute; top: 16px; right: 18px;
            background: none; border: none; cursor: pointer;
            font-size: 22px; color: #0f3891; line-height:1;
        ">&#x2715;</button>

        <!-- LEFT : card carousel -->
        <div style="flex-shrink:0; width:375px;">
            <div id="detail-card-frame" style="
                background: #0f3891;
                border-radius: 25px;
                width: 375px;
                height: 375px;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            ">
                <!-- card shadow -->
                <div style="
                    position:absolute; bottom:28px; left:50%;
                    transform:translateX(-50%);
                    width:250px; height:5px;
                    background:#011a51;
                    filter:blur(9px);
                    border-radius:50%;
                "></div>
                <!-- main card image -->
                <img id="detail-main-card-img" src="" alt="Card"
                    style="max-height:280px; max-width:200px; object-fit:contain; position:relative; z-index:1;">
            </div>

            <!-- Thumbnails + Pagination -->
            <div style="display:flex; align-items:center; justify-content:center; gap:8px; margin-top:16px;">
                <button class="nav-btn-circle" id="btn-prev-detail-card">
                    <img src="/cardhaven/assets/image/left-arrow.svg">
                </button>

                <div id="detail-thumb-track" style="display:flex; gap:8px; overflow:hidden; max-width:220px;">
                    <!-- thumbnails injected by JS -->
                </div>

                <button class="nav-btn-circle" id="btn-next-detail-card">
                    <img src="/cardhaven/assets/image/right-arrow.svg">
                </button>
            </div>
        </div>

        <!-- RIGHT : event info -->
        <div style="flex:1; padding-top:18px;">
            <h2 id="detail-event-name" class="coolveticaa"
                style="color:#0f3891; font-size:32px; margin:0 0 12px;"></h2>

            <!-- selected product badge -->
            <div id="detail-product-badge" style="
                display:inline-block;
                background:#0f3891; color:#fff;
                border-radius:17px; padding:4px 22px;
                font-weight:700; font-size:15px;
                margin-bottom:18px;
            "></div>

            <!-- meta row -->
            <div style="display:flex; gap:48px; margin-bottom:12px; font-family:Inter,sans-serif;">
                <div>
                    <p style="color:#7e7e7e; font-size:16px; margin:0 0 2px;">Stock:</p>
                    <p id="detail-stok" style="color:#0f3891; font-size:20px; font-weight:700; margin:0;"></p>
                </div>
                <div>
                    <p style="color:#7e7e7e; font-size:20px; margin:0 0 2px;">Game:</p>
                    <p id="detail-game" style="color:#0f3891; font-size:20px; font-weight:700; margin:0;"></p>
                </div>
                <div>
                    <p style="color:#7e7e7e; font-size:16px; margin:0 0 2px;">Type:</p>
                    <p id="detail-type" style="color:#0f3891; font-size:20px; font-weight:700; margin:0;"></p>
                </div>
            </div>

            <!-- kondisi -->
            <p style="font-family:Inter,sans-serif; font-size:16px; margin:0 0 6px;">
                <strong style="color:#0f3891;">Condition :</strong>
                <span id="detail-kondisi" style="color:#7e7e7e;"></span>
            </p>

            <!-- description -->
            <p style="color:#0f3891; font-weight:700; font-size:16px; font-family:Inter,sans-serif; margin:0 0 4px;">Description:</p>
            <p id="detail-deskripsi" style="
                color:#7e7e7e; font-size:12px;
                font-family:Inter,sans-serif;
                text-align:justify;
                letter-spacing:-0.12px;
                line-height:1.5;
                margin:0 0 18px;
            "></p>

            <!-- price -->
            <p id="detail-price" style="
                font-family:'Bell MT',serif;
                font-size:32px;
                color:#0f3891;
                margin: 0 0 28px;
            ">
                <!-- filled by JS: "Price: <s>Rp X</s> Rp Y" -->
            </p>

            <!-- Order button -->
            <button onclick="switchToOrderView()" style="
                background:var(--bg-gradient,#0f3891);
                color:#fff;
                border:none;
                border-radius:25px;
                padding:14px 0;
                width:300px;
                font-size:20px;
                font-family:'Coolvetica',sans-serif;
                cursor:pointer;
                display:block;
            " id="promo-status"></button>
        </div>
    </div><!-- /view-detail -->


    <!-- ──────────────────────────────────────────── -->
    <!--  VIEW 2 : Buy Event Promo (transaction form) -->
    <!-- ──────────────────────────────────────────── -->
    <div id="view-order" style="
        display:none;
        background: linear-gradient(180deg,#fff 0%,#dae6ff 100%);
        border-radius: 25px;
        padding: 36px 40px 36px 36px;
        position: relative;
        min-height: 550px;
        display: none;
        flex-direction: row;
        gap: 40px;
        align-items: flex-start;
    ">
        <!-- Close Button -->
        <button onclick="closePromoEvent()" title="Close" style="
            position: absolute; top: 16px; right: 18px;
            background: none; border: none; cursor: pointer;
            font-size: 22px; color: #0f3891; line-height:1;
        ">&#x2715;</button>

        <!-- Back button -->
        <button onclick="switchToDetailView()" style="
            position:absolute; top:16px; left:18px;
            background:none; border:none; cursor:pointer;
            font-size:13px; color:#0f3891;
            font-family:Inter,sans-serif;
            display:flex; align-items:center; gap:4px;
        ">&#8592; Back</button>

        <!-- LEFT : card display (single, matches selected product) -->
        <div style="flex-shrink:0; width:375px;">
            <div style="
                background:#0f3891;
                border-radius:25px;
                width:375px;
                height:375px;
                display:flex; align-items:center; justify-content:center;
                position:relative; overflow:hidden;
            ">
                <div style="
                    position:absolute; bottom:28px; left:50%;
                    transform:translateX(-50%);
                    width:250px; height:5px;
                    background:#011a51;
                    filter:blur(9px);
                    border-radius:50%;
                "></div>
                <img id="order-card-img" src="" alt="Card"
                    style="max-height:280px; max-width:200px; object-fit:contain; position:relative; z-index:1;">
            </div>
        </div>

        <!-- RIGHT : order form -->
        <div style="flex:1; padding-top:12px;">

            <!-- Address + Payment row -->
            <div style="display:flex; gap:24px; margin-bottom:28px;">
                <!-- Address -->
                <div style="flex:1;">
                    <label style="color:#0f3891; font-size:16px; font-family:Inter,sans-serif; font-weight:700; display:block; margin-bottom:6px;">
                        Address
                    </label>
                    <input id="order-address" type="text" placeholder="Enter city destination..."
                        style="
                            border:1.5px solid #0f3891; border-radius:15px;
                            padding:6px 14px; width:100%; box-sizing:border-box;
                            font-size:12px; color:#7e7e7e; font-family:Inter,sans-serif;
                            outline:none;
                        ">
                </div>
                <!-- Payment Method -->
                <div style="flex:1;">
                    <label style="color:#0f3891; font-size:16px; font-family:Inter,sans-serif; font-weight:700; display:block; margin-bottom:6px;">
                        Payment Method
                    </label>
                    <select id="order-payment" style="
                        border:1.5px solid #0f3891; border-radius:15px;
                        padding:6px 14px; width:100%; box-sizing:border-box;
                        font-size:12px; color:#7e7e7e; font-family:Inter,sans-serif;
                        outline:none; background:#fff; cursor:pointer;
                    ">
                        <option value="">Select Payment Method</option>
                        <!-- filled by JS -->
                    </select>
                </div>
            </div>

            <!-- Divider with label -->
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                <div style="flex:1; height:1px; background:#7e7e7e;"></div>
                <span style="color:#7e7e7e; font-size:12px; font-family:Inter,sans-serif;">Product</span>
                <div style="flex:1; height:1px; background:#7e7e7e;"></div>
            </div>

            <!-- Product list (up to 2 per page, paginated) -->
            <div id="order-product-list" style="display:flex; flex-direction:column; gap:16px; min-height:160px;">
                <!-- items injected by JS -->
            </div>

            <!-- Product pagination -->
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px;">
                <button id="btn-prev-order-product" class="nav-btn-circle">
                    <img src="/cardhaven/assets/image/left-arrow.svg">
                </button>
                <button id="btn-next-order-product" class="nav-btn-circle">
                    <img src="/cardhaven/assets/image/right-arrow.svg">
                </button>
            </div>

            <!-- Buy button -->
            <div style="margin-top:16px;">
                <button onclick="submitOrder()" style="
                    background:var(--bg-gradient,#0f3891);
                    color:#fff; border:none;
                    border-radius:25px;
                    padding:14px 0;
                    width:300px;
                    font-size:20px;
                    font-family:'Coolvetica',sans-serif;
                    cursor:pointer;
                    display:block;
                    margin-left:auto;
                ">Buy This Product</button>
            </div>
        </div>
    </div><!-- /view-order -->

</div><!-- /pop-up-event -->

<script src="/cardhaven/interface/event-transaction/script.js"></script>