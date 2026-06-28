/**
 * event-transaction/script.js
 * Handles the promo event popup: detail view, order view, pagination,
 * quantity controls, purchase validation, and form submission.
 *
 * Depends on: SweetAlert2 (cardhavenConfirm), session/localStorage for id_pengguna.
 */

/* ─────────────────────────────────────────────
   STATE
───────────────────────────────────────────── */
let currentEventId      = null;
let currentEvent        = null;          // event row
let eventProducts       = [];            // produk_event rows merged with produk
let detailPage          = 0;             // 0-indexed, 3 cards per page
let orderPage           = 0;             // 0-indexed, 2 products per page
let selectedProductIdx  = 0;             // index in eventProducts for detail view
let orderQuantities     = {};            // { id_produk: qty }
let alreadyPurchased    = {};            // { id_produk: qty_already_bought }

const DETAIL_PAGE_SIZE  = 3;
const ORDER_PAGE_SIZE   = 2;
const isLogin = sessionStorage.getItem('username') || localStorage.getItem('username');

/* ─────────────────────────────────────────────
   OPEN / CLOSE
───────────────────────────────────────────── */
function openPromoEvent(id) {
    currentEventId = id;
    detailPage     = 0;
    orderPage      = 0;
    orderQuantities = {};
    alreadyPurchased = {};

    // Show overlay + popup
    document.getElementById('pop-up-overlay').style.display = 'block';
    document.getElementById('pop-up-event').style.display   = 'block';

    switchToDetailView();
    loadEventDetail(id);
}

function closePromoEvent() {
    cardhavenConfirm(
        'Close Event',
        'Are you sure you want to close this event popup?',
        'Yes, close',
        function () {
            // Confirmed → close permanently
            _forceClose();
        },
        function () {
            // Cancelled → put popup back (already hidden by swal backdrop)
            document.getElementById('pop-up-overlay').style.display = 'block';
            document.getElementById('pop-up-event').style.display   = 'block';
        }
    );

    // Temporarily hide popup while swal is visible so they don't overlap
    document.getElementById('pop-up-overlay').style.display = 'none';
    document.getElementById('pop-up-event').style.display   = 'none';
}

function _forceClose() {
    document.getElementById('pop-up-overlay').style.display = 'none';
    document.getElementById('pop-up-event').style.display   = 'none';
    currentEventId = null;
    currentEvent   = null;
    eventProducts  = [];
}

/* ─────────────────────────────────────────────
   VIEW SWITCHING
───────────────────────────────────────────── */
function switchToDetailView() {
    document.getElementById('view-detail').style.display = 'flex';
    document.getElementById('view-order').style.display  = 'none';
}

function switchToOrderView() {
    if(!isLogin) window.location.replace("/CardHaven/login");;
    if (!eventProducts.length) return;
    loadPaymentMethods();
    loadAlreadyPurchased(function () {
        renderOrderProducts();
        // Sync the displayed card image with the currently selected product
        const product = eventProducts[selectedProductIdx];
        document.getElementById('order-card-img').src =
            product ? '/cardhaven/' + product.foto : '/cardhaven/image-profile/defaultProduct.jpg';
    });
    document.getElementById('view-detail').style.display = 'none';
    document.getElementById('view-order').style.display  = 'flex';
}

/* ─────────────────────────────────────────────
   LOAD EVENT DATA
───────────────────────────────────────────── */
function loadEventDetail(id) {
    fetch('/cardhaven/interface/event-transaction/controllerPromoTransaction.php?action=get_event&id_event=' + id)
        .then(r => r.json())
        .then(function (data) {
            if (data.error) {
                Swal.fire('Error', data.error, 'error');
                _forceClose();
                return;
            }
            currentEvent  = data.event;
            eventProducts = data.products;

            renderDetailView();
        })
        .catch(function (err) {
            console.error('loadEventDetail error:', err);
            Swal.fire('Error', 'Failed to load event data.', 'error');
        });
}

/* ─────────────────────────────────────────────
   DETAIL VIEW RENDER
───────────────────────────────────────────── */
function renderDetailView() {
    const ev = currentEvent;

    // Event name
    document.getElementById('detail-event-name').textContent = ev.nama_event;
    

    // Select first product
    if (eventProducts.length) {
        selectDetailProduct(0);
    }

    renderDetailThumbs();
    updateDetailNavButtons();
}

function selectDetailProduct(idx) {
    selectedProductIdx = idx;
    const e = currentEvent;
    const p = eventProducts[idx];
    let promoStatus = document.getElementById('promo-status');
    if (!p) return;

    document.getElementById('detail-main-card-img').src = p.foto 
    ? ('/cardhaven/' + p.foto) 
    : '/cardhaven/image-profile/defaultProduct.jpg';

    document.getElementById('detail-product-badge').textContent = p.nama_produk;
    document.getElementById('detail-stok').textContent          = p.stok_event;
    document.getElementById('detail-game').textContent          = p.nama_game || '-';
    document.getElementById('detail-type').textContent          = p.tipe_produk || '-';
    document.getElementById('detail-kondisi').textContent       = p.kondisi || '-';
    document.getElementById('detail-deskripsi').textContent     = p.deskripsi || '-';

    if(!isLogin){
        promoStatus.textContent = "Login to order product";
    }
    else{
        if (e.status_event === 1) {
            console.log(e.status_event);
            promoStatus.textContent = "Order product now";
            promoStatus.disabled = false
        } else if (e.status_event === 2) {
            promoStatus.textContent = "Event is not begin";
            promoStatus.disabled = true
        } else {
            promoStatus.textContent = "Event was complete";
            promoStatus.disabled = true
        }
    }

    // Price: strikethrough original, show event price
    const hargaAsli  = formatRupiah(p.harga_jual);
    const hargaEvent = formatRupiah(p.harga_event);
    document.getElementById('detail-price').innerHTML =
        'Price: <span style="text-decoration:line-through; color:#7e7e7e; font-size:24px;">'
        + hargaAsli + '</span>&nbsp;&nbsp;'
        + '<span style="color:#7e7e7e;">' + hargaEvent + '</span>';

    // Highlight active thumb
    const thumbs = document.querySelectorAll('.detail-thumb');
    thumbs.forEach(function (t, i) {
        t.style.border = (i === idx % DETAIL_PAGE_SIZE)
            ? '3px solid #0f3891'
            : '2px solid #ccc';
    });
}

function renderDetailThumbs() {
    const track   = document.getElementById('detail-thumb-track');
    track.innerHTML = '';

    const start   = detailPage * DETAIL_PAGE_SIZE;
    const end     = Math.min(start + DETAIL_PAGE_SIZE, eventProducts.length);

    for (let i = start; i < end; i++) {
        const p       = eventProducts[i];
        const localI  = i; // capture
        const img     = document.createElement('img');
        if (p.foto) {
            img.src = '/cardhaven/' + p.foto;
        } else {
            img.src = '/cardhaven/image-profile/defaultProduct.jpg';
        }
        img.alt       = p.nama_produk;
        img.className = 'detail-thumb';
        img.style.cssText = `
            width:60px; height:60px; object-fit:cover;
            border-radius:7px; cursor:pointer;
            border: ${i === selectedProductIdx ? '3px solid #0f3891' : '2px solid #ccc'};
        `;
        img.onclick = function () { selectDetailProduct(localI); };
        track.appendChild(img);
    }
}

function updateDetailNavButtons() {
    const totalPages = Math.ceil(eventProducts.length / DETAIL_PAGE_SIZE);
    document.getElementById('btn-prev-detail-card').disabled = (detailPage === 0);
    document.getElementById('btn-next-detail-card').disabled = (detailPage >= totalPages - 1);
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('btn-prev-detail-card').onclick = function () {
        if (detailPage > 0) {
            detailPage--;
            renderDetailThumbs();
            updateDetailNavButtons();
            const newIdx = detailPage * DETAIL_PAGE_SIZE;
            selectDetailProduct(newIdx);
        }
    };

    document.getElementById('btn-next-detail-card').onclick = function () {
        const totalPages = Math.ceil(eventProducts.length / DETAIL_PAGE_SIZE);
        if (detailPage < totalPages - 1) {
            detailPage++;
            renderDetailThumbs();
            updateDetailNavButtons();
            const newIdx = detailPage * DETAIL_PAGE_SIZE;
            selectDetailProduct(newIdx);
        }
    };

    document.getElementById('btn-prev-order-product').onclick = function () {
        if (orderPage > 0) { orderPage--; renderOrderProducts(); }
    };

    document.getElementById('btn-next-order-product').onclick = function () {
        const totalPages = Math.ceil(eventProducts.length / ORDER_PAGE_SIZE);
        if (orderPage < totalPages - 1) { orderPage++; renderOrderProducts(); }
    };
});

/* ─────────────────────────────────────────────
   ORDER VIEW RENDER
───────────────────────────────────────────── */
function renderOrderProducts() {
    const list  = document.getElementById('order-product-list');
    list.innerHTML = '';

    const start = orderPage * ORDER_PAGE_SIZE;
    const end   = Math.min(start + ORDER_PAGE_SIZE, eventProducts.length);

    for (let i = start; i < end; i++) {
        const p = eventProducts[i];
        if (!orderQuantities[p.id_produk]) orderQuantities[p.id_produk] = 0;

        const alreadyBought  = alreadyPurchased[p.id_produk] || 0;
        const maxAllowed     = (parseInt(currentEvent.maks_pembelian) || 0) - alreadyBought;
        const remaining      = Math.max(0, Math.min(maxAllowed, parseInt(p.stok_event) || 0));

        const item = document.createElement('div');
        item.style.cssText = 'display:flex; flex-direction:column; gap:6px;';
        item.innerHTML = `
            <div style="
                background:#0f3891; color:#fff;
                border-radius:5px; padding:5px 18px;
                font-weight:700; font-size:15px;
                display:inline-block; width:fit-content;
                font-family:Inter,sans-serif;
            ">${escapeHtml(p.nama_produk)}</div>
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-family:'Bell MT',serif; font-size:20px; color:#0f3891;">
                    Price: <span style="color:#7e7e7e;">${formatRupiah(p.harga_event)}</span>
                </span>
                <div style="display:flex; align-items:center; gap:8px; margin-left:auto;">
                    ${remaining > 0 ? `
                    <button onclick="changeQty(${p.id_produk}, -1, ${remaining})" style="
                        width:22px; height:22px; border-radius:50%;
                        border:2px solid #7e7e7e; background:none;
                        cursor:pointer; font-size:16px; line-height:1;
                        display:flex; align-items:center; justify-content:center;
                    ">−</button>
                    <span id="qty-${p.id_produk}"
                        style="font-family:'Bell MT',serif; font-size:20px; color:#2c2c2c; min-width:20px; text-align:center;">
                        ${orderQuantities[p.id_produk]}
                    </span>
                    <button onclick="changeQty(${p.id_produk}, 1, ${remaining})" style="
                        width:22px; height:22px; border-radius:50%;
                        border:2px solid #0f3891; background:none;
                        cursor:pointer; font-size:16px; line-height:1;
                        color:#0f3891;
                        display:flex; align-items:center; justify-content:center;
                    ">+</button>
                    ` : `
                    <span style="color:#e53935; font-size:12px; font-family:Inter,sans-serif;">
                        ${alreadyBought >= parseInt(currentEvent.maks_pembelian)
                            ? 'Purchase limit reached'
                            : 'Out of stock'}
                    </span>
                    `}
                </div>
            </div>
        `;
        list.appendChild(item);
    }

    // Pagination nav visibility
    const totalPages = Math.ceil(eventProducts.length / ORDER_PAGE_SIZE);
    document.getElementById('btn-prev-order-product').style.display =
        (totalPages > 1) ? 'flex' : 'none';
    document.getElementById('btn-next-order-product').style.display =
        (totalPages > 1) ? 'flex' : 'none';
    document.getElementById('btn-prev-order-product').disabled = (orderPage === 0);
    document.getElementById('btn-next-order-product').disabled = (orderPage >= totalPages - 1);
}

function changeQty(idProduk, delta, maxRemaining) {
    const current = orderQuantities[idProduk] || 0;
    const next    = current + delta;

    if (next < 0) return;
    if (next > maxRemaining) {
        Swal.fire({
            title: 'Limit Reached',
            text: 'You cannot purchase more than the allowed limit for this product.',
            icon: 'warning',
            confirmButtonText: 'OK',
            buttonsStyling: false,
            customClass: {
                popup: 'cardhaven-popup',
                title: 'coolveticaa cardhaven-title',
                htmlContainer: 'cardhaven-text',
                confirmButton: 'btn-confirm'
            }
        });
        return;
    }

    orderQuantities[idProduk] = next;
    const el = document.getElementById('qty-' + idProduk);
    if (el) el.textContent = next;
}

/* ─────────────────────────────────────────────
   LOAD PAYMENT METHODS
───────────────────────────────────────────── */
function loadPaymentMethods() {
    const sel = document.getElementById('order-payment');
    sel.innerHTML = '<option value="">Select Payment Method</option>';

    fetch('/cardhaven/interface/event-transaction/controllerPromoTransaction.php?action=get_payment_methods')
        .then(r => r.json())
        .then(function (data) {
            if (!data.methods) return;
            data.methods.forEach(function (m) {
                const opt       = document.createElement('option');
                opt.value       = m.id_metode;
                opt.textContent = m.nama_metode + ' — ' + m.provider +
                    ' (' + m.no_rekening + ')' +
                    (m.biaya_admin > 0 ? ' +' + formatRupiah(m.biaya_admin) : '');
                sel.appendChild(opt);
            });
        })
        .catch(function (err) { console.error('loadPaymentMethods error:', err); });
}

/* ─────────────────────────────────────────────
   LOAD ALREADY PURCHASED (per-product per-user)
───────────────────────────────────────────── */
function loadAlreadyPurchased(callback) {
    const idPengguna = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');
    if (!idPengguna || !currentEventId) {
        if (callback) callback();
        return;
    }

    fetch('/cardhaven/interface/event-transaction/controllerPromoTransaction.php?action=get_purchase_count'
        + '&id_event=' + currentEventId
        + '&id_pengguna=' + idPengguna)
        .then(r => r.json())
        .then(function (data) {
            alreadyPurchased = data.counts || {};
            if (callback) callback();
        })
        .catch(function (err) {
            console.error('loadAlreadyPurchased error:', err);
            if (callback) callback();
        });
}

/* ─────────────────────────────────────────────
   SUBMIT ORDER
───────────────────────────────────────────── */
function submitOrder() {
    const idPengguna = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');
    const address    = document.getElementById('order-address').value.trim();
    const idMetode   = document.getElementById('order-payment').value;

    // ── Validation ──
    if (!idPengguna) {
        Swal.fire('Not Logged In', 'Please log in before purchasing.', 'warning'); return;
    }
    if (!address) {
        Swal.fire('Address Required', 'Please enter your delivery address.', 'warning'); return;
    }
    if (!idMetode) {
        Swal.fire('Payment Method Required', 'Please select a payment method.', 'warning'); return;
    }

    const selectedItems = eventProducts
        .filter(function (p) { return (orderQuantities[p.id_produk] || 0) > 0; })
        .map(function (p) {
            return {
                id_produk:    p.id_produk,
                jumlah:       orderQuantities[p.id_produk],
                harga_produk: p.harga_event
            };
        });

    if (!selectedItems.length) {
        Swal.fire('No Items Selected', 'Please add at least one item to your order.', 'warning'); return;
    }

    // Per-product purchase limit check (re-validated client-side before submit)
    for (const item of selectedItems) {
        const alreadyBought = alreadyPurchased[item.id_produk] || 0;
        const maxAllowed    = parseInt(currentEvent.maks_pembelian) || 0;
        if (alreadyBought + item.jumlah > maxAllowed) {
            const prod = eventProducts.find(function (p) { return p.id_produk == item.id_produk; });
            Swal.fire(
                'Purchase Limit Exceeded',
                'You can only buy ' + maxAllowed + ' of "' + (prod ? prod.nama_produk : 'this product') + '" total (including previous purchases).',
                'warning'
            );
            return;
        }
    }

    cardhavenConfirm(
        'Confirm Purchase',
        'Are you sure you want to place this order?',
        'Yes, buy now',
        function () {
            // Hide popup while swal is up
            document.getElementById('pop-up-overlay').style.display = 'none';
            document.getElementById('pop-up-event').style.display   = 'none';

            const payload = {
                id_pengguna:  idPengguna,
                id_event:     currentEventId,
                id_metode:    idMetode,
                alamat:       address,
                items:        selectedItems
            };

            fetch('/cardhaven/interface/event-transaction/controllerPromoTransaction.php?action=submit_order', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(function (data) {
                if (data.success) {
                    Swal.fire('Order Placed!', 'Your order has been submitted successfully.', 'success');
                    // Popup stays closed after a successful purchase
                } else {
                    Swal.fire('Order Failed', data.message || 'An unexpected error occurred.', 'error');
                    // Reopen popup on failure so user can fix it
                    document.getElementById('pop-up-overlay').style.display = 'block';
                    document.getElementById('pop-up-event').style.display   = 'block';
                }
            })
            .catch(function (err) {
                console.error('submitOrder error:', err);
                Swal.fire('Network Error', 'Could not reach the server. Please try again.', 'error');
                document.getElementById('pop-up-overlay').style.display = 'block';
                document.getElementById('pop-up-event').style.display   = 'block';
            });
        },
        function () {
            // User cancelled → put popup back
            document.getElementById('pop-up-overlay').style.display = 'block';
            document.getElementById('pop-up-event').style.display   = 'block';
        }
    );

    // Hide while swal is shown (cancelCallback will reopen it)
    document.getElementById('pop-up-overlay').style.display = 'none';
    document.getElementById('pop-up-event').style.display   = 'none';
}

/* ─────────────────────────────────────────────
   HELPERS
───────────────────────────────────────────── */
function formatRupiah(amount) {
    return 'Rp ' + parseInt(amount || 0).toLocaleString('id-ID');
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}