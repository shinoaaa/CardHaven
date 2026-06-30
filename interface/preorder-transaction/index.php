<!-- ===== OVERLAY BACKDROP ===== -->
<div id="pop-up-preorder-overlay" style="
    display: none; position: fixed; inset: 0; z-index: 1040;
    background: rgba(13,71,161,.45); backdrop-filter: blur(3px);
" onclick="closePreOrderEvent()"></div>

<!-- ===== POP-UP WRAPPER ===== -->
<div id="pop-up-preorder" style="
    display: none; position: fixed; top: 50%; left: 50%;
    transform: translate(-50%, -50%); z-index: 1050;
    width: 960px; max-width: 96vw; border-radius: 25px;
    overflow: hidden; box-shadow: 0 20px 60px rgba(13,71,161,.3);
">

    <!-- ──────────────────────────────────────────── -->
    <!--  VIEW 1 : Detail Pre-Order                   -->
    <!-- ──────────────────────────────────────────── -->
    <div id="preorder-view-detail" style="
        background: linear-gradient(180deg,#fff 0%,#dae6ff 100%);
        border-radius: 25px; padding: 36px 40px 36px 36px;
        position: relative; min-height: 550px; display: flex;
        gap: 40px; align-items: flex-start;
    ">
        <button onclick="closePreOrderEvent()" title="Close" style="
            position: absolute; top: 16px; right: 18px;
            background: none; border: none; cursor: pointer;
            font-size: 22px; color: #0f3891; line-height:1;
        ">&#x2715;</button>

        <!-- LEFT : Main Card (No Carousel needed) -->
        <div style="flex-shrink:0; width:375px;">
            <div style="
                background: #0f3891; border-radius: 25px;
                width: 375px; height: 375px; position: relative;
                display: flex; align-items: center; justify-content: center;
                overflow: hidden; margin-top: 15px;
            ">
                <div style="
                    position:absolute; bottom:28px; left:50%;
                    transform:translateX(-50%); width:250px; height:5px;
                    background:#011a51; filter:blur(9px); border-radius:50%;
                "></div>
                <img id="preorder-detail-img" src="" alt="Preorder Card"
                    style="max-height:280px; max-width:200px; object-fit:contain; position:relative; z-index:1;">
            </div>
        </div>

        <!-- RIGHT : Event Info -->
        <div style="flex:1; padding-top:18px;">
            <h2 id="preorder-event-name" class="coolveticaa" style="color:#0f3891; font-size:32px; margin:0 0 12px;"></h2>

            <div id="preorder-product-badge" style="
                display:inline-block; background:#0f3891; color:#fff;
                border-radius:17px; padding:4px 22px; font-weight:700;
                font-size:15px; margin-bottom:18px;
            "></div>

            <div style="display:flex; gap:48px; margin-bottom:12px; font-family:Inter,sans-serif;">
                <div>
                    <p style="color:#7e7e7e; font-size:16px; margin:0 0 2px;">PO Quota:</p>
                    <p id="preorder-stok" style="color:#0f3891; font-size:20px; font-weight:700; margin:0;"></p>
                </div>
                <div>
                    <p style="color:#7e7e7e; font-size:20px; margin:0 0 2px;">Game:</p>
                    <p id="preorder-game" style="color:#0f3891; font-size:20px; font-weight:700; margin:0;"></p>
                </div>
                <div>
                    <p style="color:#7e7e7e; font-size:16px; margin:0 0 2px;">Type:</p>
                    <p id="preorder-type" style="color:#0f3891; font-size:20px; font-weight:700; margin:0;"></p>
                </div>
            </div>

            <p style="font-family:Inter,sans-serif; font-size:16px; margin:0 0 6px;">
                <strong style="color:#0f3891;">Kondisi :</strong> <span id="preorder-kondisi" style="color:#7e7e7e;"></span>
            </p>

            <p style="color:#0f3891; font-weight:700; font-size:16px; font-family:Inter,sans-serif; margin:0 0 4px;">Description:</p>
            <p id="preorder-deskripsi" style="
                color:#7e7e7e; font-size:12px; font-family:Inter,sans-serif;
                text-align:justify; line-height:1.5; margin:0 0 18px;
            "></p>

            <p id="preorder-price" style="font-family:'Bell MT',serif; font-size:32px; color:#0f3891; margin: 0 0 28px;"></p>

            <button onclick="preorderSwitchToOrder()" style="
                background:var(--bg-gradient,#0f3891); color:#fff; border:none;
                border-radius:25px; padding:14px 0; width:300px;
                font-size:20px; font-family:'Coolvetica',sans-serif;
                cursor:pointer; display:block;
            " id="preorder-title">Pre-Order This Product</button>
        </div>
    </div>


    <!-- ──────────────────────────────────────────── -->
    <!--  VIEW 2 : Transaction Form                   -->
    <!-- ──────────────────────────────────────────── -->
    <div id="preorder-view-order" style="
        display:none; background: linear-gradient(180deg,#fff 0%,#dae6ff 100%);
        border-radius: 25px; padding: 36px 40px 36px 36px;
        position: relative; min-height: 550px; flex-direction: row;
        gap: 40px; align-items: flex-start;
    ">
        <button onclick="closePreOrderEvent()" title="Close" style="
            position: absolute; top: 16px; right: 18px;
            background: none; border: none; cursor: pointer;
            font-size: 22px; color: #0f3891; line-height:1;
        ">&#x2715;</button>

        <button onclick="preorderSwitchToDetail()" style="
            position:absolute; top:16px; left:18px;
            background:none; border:none; cursor:pointer;
            font-size:13px; color:#0f3891; font-family:Inter,sans-serif;
            display:flex; align-items:center; gap:4px;
        ">&#8592; Back</button>

        <div style="flex-shrink:0; width:375px;">
            <div style="
                background:#0f3891; border-radius:25px; width:375px; height:375px;
                display:flex; align-items:center; justify-content:center;
                position:relative; overflow:hidden; margin-top: 15px;
            ">
                <div style="
                    position:absolute; bottom:28px; left:50%; transform:translateX(-50%);
                    width:250px; height:5px; background:#011a51; filter:blur(9px); border-radius:50%;
                "></div>
                <img id="preorder-order-img" src="" alt="Preorder Card"
                    style="max-height:280px; max-width:200px; object-fit:contain; position:relative; z-index:1;">
            </div>
        </div>

        <div style="flex:1; padding-top:12px;">
            <!-- Address + Payment row -->
            <div style="display:flex; gap:24px; margin-bottom:28px;">
                <div style="flex:1;">
                    <label style="color:#0f3891; font-size:16px; font-family:Inter,sans-serif; font-weight:700; display:block; margin-bottom:6px;">Address</label>
                    <input id="preorder-address" type="text" placeholder="Enter city destination..." style="
                        border:1.5px solid #0f3891; border-radius:15px; padding:6px 14px; width:100%; box-sizing:border-box;
                        font-size:12px; color:#7e7e7e; font-family:Inter,sans-serif; outline:none;
                    ">
                </div>
                <div style="flex:1;">
                    <label style="color:#0f3891; font-size:16px; font-family:Inter,sans-serif; font-weight:700; display:block; margin-bottom:6px;">Payment Method</label>
                    <select id="preorder-payment" style="
                        border:1.5px solid #0f3891; border-radius:15px; padding:6px 14px; width:100%; box-sizing:border-box;
                        font-size:12px; color:#7e7e7e; font-family:Inter,sans-serif; outline:none; background:#fff; cursor:pointer;
                    ">
                        <option value="">Select Payment Method</option>
                    </select>
                </div>
            </div>

            <!-- Single Product Control -->
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                <div style="flex:1; height:1px; background:#7e7e7e;"></div>
                <span style="color:#7e7e7e; font-size:12px; font-family:Inter,sans-serif;">Product Quantity</span>
                <div style="flex:1; height:1px; background:#7e7e7e;"></div>
            </div>

            <div id="preorder-product-control" style="display:flex; flex-direction:column; gap:6px; min-height:80px;">
                <!-- Diisi via JS -->
            </div>

            <div style="margin-top:24px;">
                <button onclick="submitPreOrder()" style="
                    background:var(--bg-gradient,#0f3891); color:#fff; border:none;
                    border-radius:25px; padding:14px 0; width:300px;
                    font-size:20px; font-family:'Coolvetica',sans-serif;
                    cursor:pointer; display:block; margin-left:auto;
                ">Buy This Product</button>
            </div>
        </div>
    </div>
</div>

<script src="/cardhaven/interface/global_alert.js"></script>
<script src="/cardhaven/interface/home/script.js"></script>
<script src="/cardhaven/interface/preorder-transaction/script.js"></script>