/**
 * preorder-transaction/script.js
 * State management dan validasi khusus untuk Pre-Order Event.
 */

var preorderCurrentEventId = null;
var preorderEvent          = null;
var preorderProduct        = null; 
var preorderQty            = 0;
var preorderAlreadyBought  = 0;
var preorderIdPengguna     = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

/* ─────────────────────────────────────────────
   OPEN / CLOSE
───────────────────────────────────────────── */

window.openPreOrderEvent = function(id_event) {
    if (!id_event) {
        console.error("Event ID not found!");
        return;
    }
    
    preorderCurrentEventId = id_event;
    preorderQty            = 0;
    preorderAlreadyBought  = 0;

    document.getElementById('pop-up-preorder-overlay').style.display = 'block';
    document.getElementById('pop-up-preorder').style.display   = 'block';

    preorderSwitchToDetail();
    loadPreOrderData(id_event);
};

window.closePreOrderEvent = function() {
    cardhavenConfirm(
        'Close Event',
        'Are you sure you want to close this pre-order event?',
        'Yes, close',
        function () {
            document.getElementById('pop-up-preorder-overlay').style.display = 'none';
            document.getElementById('pop-up-preorder').style.display   = 'none';
            preorderCurrentEventId = null;
            preorderEvent          = null;
            preorderProduct        = null;
        },
        function () {}
    );
};

/* ─────────────────────────────────────────────
   VIEW SWITCHING
───────────────────────────────────────────── */
window.preorderSwitchToDetail = function() {
    document.getElementById('preorder-view-detail').style.display = 'flex';
    document.getElementById('preorder-view-order').style.display  = 'none';
};

window.preorderSwitchToOrder = function() {
    if (!preorderIdPengguna) { window.location.replace("/CardHaven/interface/login-page"); return; }
    if (!preorderProduct) return;
    
    loadPreorderAlreadyPurchased(function () {
        renderPreOrderControls();
        document.getElementById('preorder-order-img').src = preorderProduct.foto
            ? '/cardhaven/assets/image/products/' + preorderProduct.foto
            : '/cardhaven/image-profile/defaultProduct.jpg';
    });
    
    document.getElementById('preorder-view-detail').style.display = 'none';
    document.getElementById('preorder-view-order').style.display  = 'flex';
};

/* ─────────────────────────────────────────────
   DATA FETCHING & RENDER
───────────────────────────────────────────── */
function loadPreOrderData(id) {
    fetch('/cardhaven/interface/preorder-transaction/controllerPreorderTransaction.php?action=get_event&id_event=' + id)
        .then(r => r.json())
        .then(function (data) {
            if (data.error) {
                Swal.fire('Error', data.error, 'error');
                document.getElementById('pop-up-preorder-overlay').style.display = 'none';
                document.getElementById('pop-up-preorder').style.display   = 'none';
                return;
            }
            preorderEvent   = data.event;
            preorderProduct = data.product;

            if(preorderProduct) {
                renderPreOrderDetail();
            } else {
                Swal.fire('Error', 'No products were found for this event.', 'error');
            }
        })
        .catch(err => console.error('loadPreOrderData error:', err));
}

function renderPreOrderDetail() {
    const p = preorderProduct;
    const e = preorderEvent;
    let preorderStatus = document.getElementById('preorder-title');
    
    document.getElementById('preorder-event-name').textContent = e.nama_event;
    document.getElementById('preorder-detail-img').src = p.foto
        ? ('/cardhaven/assets/image/products/' + p.foto)
        : '/cardhaven/image-profile/defaultProduct.jpg';

    document.getElementById('preorder-product-badge').textContent = p.nama_produk;
    document.getElementById('preorder-stok').textContent          = e.maks_pembelian;
    document.getElementById('preorder-remain').textContent        = p.stok_event;
    document.getElementById('preorder-game').textContent          = p.nama_game || '-';
    document.getElementById('preorder-type').textContent          = p.tipe_produk || '-';
    document.getElementById('preorder-kondisi').textContent       = p.kondisi || '-';
    document.getElementById('preorder-deskripsi').textContent     = p.deskripsi || '-';

    if(!preorderIdPengguna){
        preorderStatus.textContent = "Login to order product";
        preorderStatus.disabled = false;
        preorderStatus.setAttribute('onclick', 'window.location.replace("/CardHaven/interface/login-page")');
    }
    else{
        if(p.stok_event > 0){
            if (e.status_event === 1) {
                preorderStatus.textContent = "Order product now";
                preorderStatus.disabled = false;
                preorderStatus.style.cursor = 'pointer';
                preorderStatus.style.background = 'var(--bg-gradient,#0f3891)';
                preorderStatus.setAttribute('onclick', 'preorderSwitchToOrder()');
            } else if (e.status_event === 2) {
                preorderStatus.textContent = "Event is not begin";
                preorderStatus.disabled = true;
                preorderStatus.style.cursor = 'default';
                preorderStatus.style.background = '#FF8E24';
            } else {
                preorderStatus.textContent = "Event was complete";
                preorderStatus.disabled = true;
                preorderStatus.style.cursor = 'default';
                preorderStatus.style.background = '#FF8E24';
            }
        } else {
            preorderStatus.textContent = "Out of stock";
            preorderStatus.disabled = true;
            preorderStatus.style.cursor = 'default';
            preorderStatus.style.background = '#FF8E24';
        }
    }

    const hargaAsli = 'Rp ' + parseInt(p.harga_jual || 0).toLocaleString('id-ID');
    const hargaPO   = 'Rp ' + parseInt(p.harga_event || 0).toLocaleString('id-ID');

    document.getElementById('preorder-price').innerHTML =
        'Price: <span style="text-decoration:line-through; color:#7e7e7e; font-size:24px;">'
        + hargaAsli + '</span>&nbsp;&nbsp;'
        + '<span style="color:#7e7e7e;">' + hargaPO + '</span>';
}

// PERBAIKAN LOGIKA MATH DAN UI DI SINI!
function renderPreOrderControls() {
    const container = document.getElementById('preorder-product-control');
    const p = preorderProduct;
    const e = preorderEvent;

    // Hitung berapa sisa kuota user
    const maxAllowed = (parseInt(e.maks_pembelian) || 0) - preorderAlreadyBought;
    
    // Hitung berapa stok asli di database
    const currentStock = parseInt(p.stok_event) || 0;
    
    // Angka final yang boleh dibeli adalah nilai TERKECIL antara kuota dan stok.
    const absoluteMax = Math.max(0, Math.min(maxAllowed, currentStock));

    // Kalau sebelumnya user sudah terlanjur pilih banyak, reset jika melebihi batas baru
    if (preorderQty > absoluteMax) preorderQty = absoluteMax;
    // Beri minimal angka 1 kalau masih bisa beli
    if (preorderQty === 0 && absoluteMax > 0) preorderQty = 1;

    container.innerHTML = `
        <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:12px; padding:20px; width:100%; box-sizing:border-box;">
            
            <div style="background:#0f3891; color:#fff; border-radius:6px; padding:6px 16px; font-weight:700; font-size:16px; display:inline-block; margin-bottom:16px;">
                ${p.nama_produk}
            </div>
            
            <!-- Info Limit & Stok -->
            <div style="display:flex; justify-content:space-between; margin-bottom:16px; font-family:Inter,sans-serif; font-size:14px;">
                <div style="background:#e0f2fe; color:#0369a1; padding:10px; border-radius:8px; width:48%;">
                    <span style="display:block; font-weight:700; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Available Stock</span>
                    <span style="font-size:18px; font-weight:800;">${currentStock} pcs</span>
                </div>
                <div style="background:#fef3c7; color:#b45309; padding:10px; border-radius:8px; width:48%;">
                    <span style="display:block; font-weight:700; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Your Quota</span>
                    <span style="font-size:18px; font-weight:800;">${maxAllowed} <span style="font-size:12px; font-weight:600;">more</span></span>
                </div>
            </div>

            <div style="height:1px; background:#e2e8f0; margin-bottom:16px;"></div>

            <!-- Price & Input -->
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <span style="font-family:'Bell MT',serif; font-size:26px; color:#0f3891; font-weight:bold;">
                    Rp ${parseInt(p.harga_event).toLocaleString('id-ID')}
                </span>
                
                <div style="display:flex; align-items:center; gap:8px;">
                    ${absoluteMax > 0 ? `
                    <button onclick="window.changePreorderQty(-1, ${absoluteMax}, ${currentStock})" style="width:30px; height:30px; border-radius:50%; border:2px solid #94a3b8; background:none; cursor:pointer; font-size:18px; line-height:1; display:flex; align-items:center; justify-content:center; color:#64748b;">−</button>
                    
                    <span id="preorder-qty-display" style="font-family:'Bell MT',serif; font-size:22px; font-weight:bold; color:#0f3891; min-width:30px; text-align:center;">
                        ${preorderQty}
                    </span>
                    
                    <button onclick="window.changePreorderQty(1, ${absoluteMax}, ${currentStock})" style="width:30px; height:30px; border-radius:50%; border:2px solid #0f3891; background:#0f3891; cursor:pointer; font-size:18px; line-height:1; color:#fff; display:flex; align-items:center; justify-content:center;">+</button>
                    ` : `
                    <span style="background:#fee2e2; color:#dc2626; padding:6px 12px; border-radius:6px; font-size:13px; font-weight:700;">
                        ${maxAllowed <= 0 ? 'Purchase limit reached' : 'Out of stock'}
                    </span>
                    `}
                </div>
            </div>
        </div>
    `;
}

// PERBAIKAN LOGIKA ALERT LIMIT DI SINI
window.changePreorderQty = function(delta, absoluteMax, currentStock) {
    const next = preorderQty + delta;
    if (next < 1 && absoluteMax > 0) return; // Minimal qty = 1
    
    if (next > absoluteMax) {
        // Tentukan pesan error mana yang lebih relevan
        let msg = '';
        if (next > currentStock) {
            msg = 'Not enough stock available.';
        } else {
            msg = 'You have reached your purchase limit for this event.';
        }

        Swal.fire({
            title: 'Limit Reached',
            text: msg,
            icon: 'warning'
        });
        return;
    }
    
    preorderQty = next;
    const el = document.getElementById('preorder-qty-display');
    if (el) el.textContent = next;
};

function loadPreorderAlreadyPurchased(callback) {
    if (!preorderIdPengguna || !preorderCurrentEventId || !preorderProduct) {
        if (callback) callback();
        return;
    }

    fetch(`/cardhaven/interface/preorder-transaction/controllerPreorderTransaction.php?action=get_purchase_count&id_event=${preorderCurrentEventId}&id_pengguna=${preorderIdPengguna}`)
        .then(r => r.json())
        .then(data => {
            preorderAlreadyBought = (data.counts && data.counts[preorderProduct.id_produk]) || 0;
            if (callback) callback();
        });
}

/* ─────────────────────────────────────────────
   SUBMIT ORDER (PREORDER) - REDIRECT KE CHECKOUT
───────────────────────────────────────────── */
window.submitPreOrder = function() {    
    if (preorderQty <= 0) { Swal.fire('Warning', 'Specify the number of products to be pre-ordered.', 'warning'); return; }

    const payload = {
        checkout_type: 'preorder',
        id_event: preorderCurrentEventId,
        nama_event: preorderEvent.nama_event,
        persen_diskon: preorderEvent.persen_diskon,
        tanggal_sampai: preorderEvent.tanggal_sampai, // INI PENTING! (Format YYYY-MM-DD)
        items: [{
            id_produk: preorderProduct.id_produk,
            nama_produk: preorderProduct.nama_produk,
            foto: preorderProduct.foto,
            jumlah_barang: preorderQty, 
            harga_produk: preorderProduct.harga_event,
            subtotal_harga: preorderQty * preorderProduct.harga_event
        }]
    };

    document.getElementById('pop-up-preorder-overlay').style.display = 'none';
    document.getElementById('pop-up-preorder').style.display   = 'none';

    sessionStorage.setItem('direct_checkout_data', JSON.stringify(payload));
    window.location.href = '/CardHaven/checkout';
};