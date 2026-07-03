/**
 * preorder-transaction/script.js
 * State management dan validasi khusus untuk Pre-Order Event.
 */

let preorderCurrentEventId = null;
let preorderEvent          = null;
let preorderProduct        = null; 
let preorderQty            = 0;
let preorderAlreadyBought  = 0;
const idPengguna = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

/* ─────────────────────────────────────────────
   OPEN / CLOSE
───────────────────────────────────────────── */

function openPreOrderEvent(id_event) {
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
}

function closePreOrderEvent() {
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
        function () {
            document.getElementById('pop-up-preorder-overlay').style.display = 'block';
            document.getElementById('pop-up-preorder').style.display   = 'block';
        }
    );

    document.getElementById('pop-up-preorder-overlay').style.display = 'none';
    document.getElementById('pop-up-preorder').style.display   = 'none';
}

/* ─────────────────────────────────────────────
   VIEW SWITCHING
───────────────────────────────────────────── */
function preorderSwitchToDetail() {
    document.getElementById('preorder-view-detail').style.display = 'flex';
    document.getElementById('preorder-view-order').style.display  = 'none';
}

function preorderSwitchToOrder() {
    if (!idPengguna) { window.location.replace("login"); }
    if (!preorderProduct) return;
    loadPreorderPaymentMethods();
    loadPreorderAlreadyPurchased(function () {
        renderPreOrderControls();
        document.getElementById('preorder-order-img').src = preorderProduct.foto
            ? '/cardhaven/assets/image/products/' + preorderProduct.foto
            : '/cardhaven/image-profile/defaultProduct.jpg';
    });
    
    document.getElementById('preorder-view-detail').style.display = 'none';
    document.getElementById('preorder-view-order').style.display  = 'flex';
}

/* ─────────────────────────────────────────────
   DATA FETCHING & RENDER
───────────────────────────────────────────── */
function loadPreOrderData(id) {
    fetch('/cardhaven/interface/preorder-transaction/controllerPreorderTransaction.php?action=get_event&id_event=' + id)
        .then(r => r.json())
        .then(function (data) {
            if (data.error) {
                Swal.fire('Error', data.error, 'error');
                closePreOrderEvent();
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
    
    document.getElementById('preorder-event-name').textContent = preorderEvent.nama_event;
    document.getElementById('preorder-detail-img').src = p.foto
        ? ('/cardhaven/assets/image/products/' + p.foto)
        : '/cardhaven/image-profile/defaultProduct.jpg';

    document.getElementById('preorder-product-badge').textContent = p.nama_produk;
    document.getElementById('preorder-stok').textContent          = p.stok_event;
    document.getElementById('preorder-game').textContent          = p.nama_game || '-';
    document.getElementById('preorder-type').textContent          = p.tipe_produk || '-';
    document.getElementById('preorder-kondisi').textContent       = p.kondisi || '-';
    document.getElementById('preorder-deskripsi').textContent     = p.deskripsi || '-';

    if(!idPengguna){
        preorderStatus.textContent = "Login to order product";
    }
    else{
        if (e.status_event === 1) {
            console.log(e.status_event);
            preorderStatus.textContent = "Order product now";
            preorderStatus.disabled = false
        } else if (e.status_event === 2) {
            console.log(e.status_event);
            preorderStatus.textContent = "Event is not begin";
            preorderStatus.disabled = true
            preorderStatus.style.cursor = 'default';
        } else {
            console.log(e.status_event);
            preorderStatus.textContent = "Event was complete";
            preorderStatus.disabled = true
        }
    }

    const hargaAsli = 'Rp ' + parseInt(p.harga_jual || 0).toLocaleString('id-ID');
    const hargaPO   = 'Rp ' + parseInt(p.harga_event || 0).toLocaleString('id-ID');

    document.getElementById('preorder-price').innerHTML =
        'Price: <span style="text-decoration:line-through; color:#7e7e7e; font-size:24px;">'
        + hargaAsli + '</span>&nbsp;&nbsp;'
        + '<span style="color:#7e7e7e;">' + hargaPO + '</span>';
}

function renderPreOrderControls() {
    const container = document.getElementById('preorder-product-control');
    const p = preorderProduct;

    const maxAllowed = (parseInt(preorderEvent.maks_pembelian) || 0) - preorderAlreadyBought;
    const remaining  = Math.max(0, Math.min(maxAllowed, parseInt(p.stok_event) || 0));

    container.innerHTML = `
        <div style="
            background:#0f3891; color:#fff; border-radius:5px; padding:5px 18px;
            font-weight:700; font-size:15px; display:inline-block; width:fit-content;
            font-family:Inter,sans-serif;
        ">${p.nama_produk}</div>
        <div style="display:flex; align-items:center; gap:12px;">
            <span style="font-family:'Bell MT',serif; font-size:20px; color:#0f3891;">
                Price: <span style="color:#7e7e7e;">Rp ${parseInt(p.harga_event).toLocaleString('id-ID')}</span>
            </span>
            <div style="display:flex; align-items:center; gap:8px; margin-left:auto;">
                ${remaining > 0 ? `
                <button onclick="changePreorderQty(-1, ${remaining})" style="
                    width:22px; height:22px; border-radius:50%; border:2px solid #7e7e7e; background:none;
                    cursor:pointer; font-size:16px; line-height:1; display:flex; align-items:center; justify-content:center;
                ">−</button>
                <span id="preorder-qty-display" style="font-family:'Bell MT',serif; font-size:20px; color:#2c2c2c; min-width:20px; text-align:center;">
                    ${preorderQty}
                </span>
                <button onclick="changePreorderQty(1, ${remaining})" style="
                    width:22px; height:22px; border-radius:50%; border:2px solid #0f3891; background:none;
                    cursor:pointer; font-size:16px; line-height:1; color:#0f3891; display:flex; align-items:center; justify-content:center;
                ">+</button>
                ` : `
                <span style="color:#e53935; font-size:12px; font-family:Inter,sans-serif;">
                    ${preorderAlreadyBought >= parseInt(preorderEvent.maks_pembelian) ? 'Purchase limit reached' : 'Out of stock'}
                </span>
                `}
            </div>
        </div>
    `;
}

function changePreorderQty(delta, maxRemaining) {
    const next = preorderQty + delta;
    if (next < 0) return;
    if (next > maxRemaining) {
        Swal.fire({
            title: 'Limit Reached',
            text: 'The purchase limit for this event has been reached.',
            icon: 'warning'
        });
        return;
    }
    preorderQty = next;
    const el = document.getElementById('preorder-qty-display');
    if (el) el.textContent = next;
}

function loadPreorderPaymentMethods() {
    const sel = document.getElementById('preorder-payment');
    sel.innerHTML = '<option value="">Select Payment Method</option>';

    fetch('/cardhaven/interface/preorder-transaction/controllerPreorderTransaction.php?action=get_payment_methods')
        .then(r => r.json())
        .then(data => {
            if (!data.methods) return;
            data.methods.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.id_metode;
                const admin = m.biaya_admin > 0 ? ' +Rp ' + parseInt(m.biaya_admin).toLocaleString('id-ID') : '';
                opt.textContent = `${m.nama_metode} — ${m.provider} (${m.no_rekening})${admin}`;
                sel.appendChild(opt);
            });
        });
}

function loadPreorderAlreadyPurchased(callback) {
    const idPengguna = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');
    if (!idPengguna || !preorderCurrentEventId || !preorderProduct) {
        if (callback) callback();
        return;
    }

    fetch(`/cardhaven/interface/preorder-transaction/controllerPreorderTransaction.php?action=get_purchase_count&id_event=${preorderCurrentEventId}&id_pengguna=${idPengguna}`)
        .then(r => r.json())
        .then(data => {
            preorderAlreadyBought = (data.counts && data.counts[preorderProduct.id_produk]) || 0;
            if (callback) callback();
        });
}

/* ─────────────────────────────────────────────
   SUBMIT ORDER
───────────────────────────────────────────── */
function submitPreOrder() {    
    const address    = document.getElementById('preorder-address').value.trim();
    const idMetode   = document.getElementById('preorder-payment').value;

    if (!address)    { Swal.fire('Warning', 'The shipping address must be filled in.', 'warning'); return; }
    if (!idMetode)   { Swal.fire('Warning', 'You must select a payment method.', 'warning'); return; }
    if (preorderQty <= 0) { Swal.fire('Warning', 'Specify the number of products to be pre-ordered.', 'warning'); return; }

    cardhavenConfirm(
        'Confirm Pre-Order',
        'Lanjutkan proses pre-order?',
        'Yes, Checkout',
        function () {
            document.getElementById('pop-up-preorder-overlay').style.display = 'none';
            document.getElementById('pop-up-preorder').style.display   = 'none';

            const payload = {
                id_pengguna: idPengguna,
                id_event:    preorderCurrentEventId,
                id_metode:   idMetode,
                alamat:      address,
                items: [{
                    id_produk: preorderProduct.id_produk,
                    jumlah: preorderQty,
                    harga_produk: preorderProduct.harga_event
                }]
            };

            fetch('/cardhaven/interface/preorder-transaction/controllerPreorderTransaction.php?action=submit_order', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success!', 'Your pre-order has been successfully placed. Please proceed to checkout.', 'success');
                } else {
                    Swal.fire('Error', data.message, 'error');
                    document.getElementById('pop-up-preorder-overlay').style.display = 'block';
                    document.getElementById('pop-up-preorder').style.display   = 'block';
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Failed to connect to the server.', 'error');
                document.getElementById('pop-up-preorder-overlay').style.display = 'block';
                document.getElementById('pop-up-preorder').style.display   = 'block';
            });
        },
        function () {
            document.getElementById('pop-up-preorder-overlay').style.display = 'block';
            document.getElementById('pop-up-preorder').style.display   = 'block';
        }
    );

    document.getElementById('pop-up-preorder-overlay').style.display = 'none';
    document.getElementById('pop-up-preorder').style.display   = 'none';
}