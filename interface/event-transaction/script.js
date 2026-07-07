/**
 * event-transaction/script.js
 * Handles the promo event popup: detail view, order view, pagination,
 * quantity controls, per-product purchase validation, and form submission.
 */

// Gunakan var dan prefix agar kebal dari crash scope browser
var promoCurrentEventId      = null;
var promoCurrentEvent        = null;          
var promoEventProducts       = [];            
var promoDetailPage          = 0;             
var promoOrderPage           = 0;             
var promoSelectedProductIdx  = 0;             
var promoOrderQuantities     = {};            
var promoAlreadyPurchased    = {};            

const PROMO_DETAIL_PAGE_SIZE  = 3;
const PROMO_ORDER_PAGE_SIZE   = 2;
var promoIdPengguna = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

/* ─────────────────────────────────────────────
   OPEN / CLOSE
───────────────────────────────────────────── */
window.openPromoEvent = function(id) {
    promoCurrentEventId = id;
    promoDetailPage     = 0;
    promoOrderPage      = 0;
    promoOrderQuantities = {};
    promoAlreadyPurchased = {};

    document.getElementById('pop-up-overlay').style.display = 'block';
    document.getElementById('pop-up-event').style.display   = 'block';

    window.switchToPromoDetailView();
    loadPromoEventDetail(id);
};

window.closePromoEvent = function() {
    cardhavenConfirm(
        'Close Event',
        'Are you sure you want to close this event popup?',
        'Yes, close',
        function () {
            document.getElementById('pop-up-overlay').style.display = 'none';
            document.getElementById('pop-up-event').style.display   = 'none';
            promoCurrentEventId = null;
            promoCurrentEvent   = null;
            promoEventProducts  = [];
        },
        function () {}
    );
};

/* ─────────────────────────────────────────────
   VIEW SWITCHING
───────────────────────────────────────────── */
window.switchToPromoDetailView = function() {
    document.getElementById('view-detail').style.display = 'flex';
    document.getElementById('view-order').style.display  = 'none';
};

window.switchToPromoOrderView = function() {
    if(!promoIdPengguna) { window.location.replace("/CardHaven/interface/login-page"); return; }
    if (!promoEventProducts.length) return;
    
    loadPromoAlreadyPurchased(function () {
        renderPromoOrderProducts();
        
        const product = promoEventProducts[promoSelectedProductIdx];
        document.getElementById('order-card-img').src =
            product ? '/cardhaven/assets/image/products/' + product.foto : '/cardhaven/image-profile/defaultProduct.jpg';
    });
    document.getElementById('view-detail').style.display = 'none';
    document.getElementById('view-order').style.display  = 'flex';
};

/* ─────────────────────────────────────────────
   LOAD EVENT DATA
───────────────────────────────────────────── */
function loadPromoEventDetail(id) {
    fetch('/cardhaven/interface/event-transaction/controllerPromoTransaction.php?action=get_event&id_event=' + id)
        .then(r => r.json())
        .then(function (data) {
            if (data.error) {
                Swal.fire('Error', data.error, 'error');
                document.getElementById('pop-up-overlay').style.display = 'none';
                document.getElementById('pop-up-event').style.display   = 'none';
                return;
            }
            promoCurrentEvent  = data.event;
            promoEventProducts = data.products;

            renderPromoDetailView();
        })
        .catch(function (err) {
            console.error('loadPromoEventDetail error:', err);
            Swal.fire('Error', 'Failed to load event data.', 'error');
        });
}

/* ─────────────────────────────────────────────
   DETAIL VIEW RENDER
───────────────────────────────────────────── */
function renderPromoDetailView() {
    document.getElementById('detail-event-name').textContent = promoCurrentEvent.nama_event;
    if (promoEventProducts.length) window.selectPromoDetailProduct(0);
    renderPromoDetailThumbs();
    updatePromoDetailNavButtons();
}

window.selectPromoDetailProduct = function(idx) {
    promoSelectedProductIdx = idx;
    const e = promoCurrentEvent;
    const p = promoEventProducts[idx];
    let promoStatus = document.getElementById('promo-status');
    if (!p) return;

    document.getElementById('detail-main-card-img').src = p.foto
    ? ('/cardhaven/assets/image/products/' + p.foto)
    : '/cardhaven/image-profile/defaultProduct.jpg';

    document.getElementById('detail-product-badge').textContent = p.nama_produk;
    document.getElementById('detail-stok').textContent          = e.maks_pembelian;
    document.getElementById('detail-game').textContent          = p.nama_game || '-';
    document.getElementById('detail-remain').textContent        = p.stok_event || '-';
    document.getElementById('detail-type').textContent          = p.tipe_produk || '-';
    document.getElementById('detail-kondisi').textContent       = p.kondisi || '-';
    document.getElementById('detail-deskripsi').textContent     = p.deskripsi || '-';

    if(!promoIdPengguna){
        promoStatus.textContent = "Login to order product";
        promoStatus.disabled = false;
        promoStatus.setAttribute('onclick', 'window.location.replace("/CardHaven/interface/login-page")');
    }
    else{
        if(p.stok_event > 0){
            if (e.status_event === 1) {
                promoStatus.textContent = "Order product now";
                promoStatus.disabled = false;
                promoStatus.setAttribute('onclick', 'window.switchToPromoOrderView()');
            } else if (e.status_event === 2) {
                promoStatus.textContent = "Event is not begin";
                promoStatus.disabled = true;
            } else {
                promoStatus.textContent = "Event was complete";
                promoStatus.disabled = true;
            }
        } else{
            promoStatus.textContent = "Out of stock";
            promoStatus.disabled = true;
        }
    }

    const hargaAsli  = formatRupiah(p.harga_jual);
    const hargaEvent = formatRupiah(p.harga_event);
    document.getElementById('detail-price').innerHTML =
        'Price: <span style="text-decoration:line-through; color:#7e7e7e; font-size:24px;">'
        + hargaAsli + '</span>&nbsp;&nbsp;'
        + '<span style="color:#7e7e7e;">' + hargaEvent + '</span>';

    const thumbs = document.querySelectorAll('.detail-thumb');
    thumbs.forEach(function (t, i) {
        t.style.border = (i === idx % PROMO_DETAIL_PAGE_SIZE) ? '3px solid #0f3891' : '2px solid #ccc';
    });
};

function renderPromoDetailThumbs() {
    const track   = document.getElementById('detail-thumb-track');
    track.innerHTML = '';

    const start   = promoDetailPage * PROMO_DETAIL_PAGE_SIZE;
    const end     = Math.min(start + PROMO_DETAIL_PAGE_SIZE, promoEventProducts.length);

    for (let i = start; i < end; i++) {
        const p       = promoEventProducts[i];
        const localI  = i;
        const img     = document.createElement('img');
        img.src = p.foto ? '/cardhaven/assets/image/products/' + p.foto : '/cardhaven/image-profile/defaultProduct.jpg';
        img.alt       = p.nama_produk;
        img.className = 'detail-thumb';
        img.style.cssText = `width:60px; height:60px; object-fit:cover; border-radius:7px; cursor:pointer; border: ${i === promoSelectedProductIdx ? '3px solid #0f3891' : '2px solid #ccc'};`;
        img.onclick = function () { window.selectPromoDetailProduct(localI); };
        track.appendChild(img);
    }
}

function updatePromoDetailNavButtons() {
    const totalPages = Math.ceil(promoEventProducts.length / PROMO_DETAIL_PAGE_SIZE);
    document.getElementById('btn-prev-detail-card').disabled = (promoDetailPage === 0);
    document.getElementById('btn-next-detail-card').disabled = (promoDetailPage >= totalPages - 1);
}

window.promoChangeDetailPage = function(delta) {
    const totalPages = Math.ceil(promoEventProducts.length / PROMO_DETAIL_PAGE_SIZE);
    const next = promoDetailPage + delta;
    if (next >= 0 && next < totalPages) {
        promoDetailPage = next;
        renderPromoDetailThumbs();
        updatePromoDetailNavButtons();
        const newIdx = promoDetailPage * PROMO_DETAIL_PAGE_SIZE;
        window.selectPromoDetailProduct(newIdx);
    }
};

/* ─────────────────────────────────────────────
   ORDER VIEW RENDER & PAGINATION
───────────────────────────────────────────── */
function renderPromoOrderProducts() {
    const list  = document.getElementById('order-product-list');
    list.innerHTML = '';

    const start = promoOrderPage * PROMO_ORDER_PAGE_SIZE;
    const end   = Math.min(start + PROMO_ORDER_PAGE_SIZE, promoEventProducts.length);

    for (let i = start; i < end; i++) {
        const p = promoEventProducts[i];
        if (!promoOrderQuantities[p.id_produk]) promoOrderQuantities[p.id_produk] = 0;

        // MATEMATIKA KUOTA PER-PRODUK
        const eventMax       = parseInt(promoCurrentEvent.maks_pembelian) || 0;
        const alreadyBought  = promoAlreadyPurchased[p.id_produk] || 0;
        const maxAllowed     = eventMax - alreadyBought; // Sisa Kuota Aktual
        const currentStock   = parseInt(p.stok_event) || 0;
        
        // Batas absolut: Terkecil antara sisa kuota dan sisa stok
        const absoluteMax    = Math.max(0, Math.min(maxAllowed, currentStock));

        // Reset kalau quantity nyangkut di atas batas
        if (promoOrderQuantities[p.id_produk] > absoluteMax) promoOrderQuantities[p.id_produk] = absoluteMax;
        
        const currentQty = promoOrderQuantities[p.id_produk];

        const item = document.createElement('div');
        item.style.cssText = 'background:#f8fafc; border:1px solid #cbd5e1; border-radius:12px; padding:14px; width:100%; box-sizing:border-box; margin-bottom:8px;';
        
        let controlHTML = '';
        if (currentStock <= 0) {
            controlHTML = `<span style="background:#fee2e2; color:#dc2626; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700;">Out of stock</span>`;
        } else if (maxAllowed <= 0) {
            controlHTML = `<span style="background:#fef08a; color:#92400e; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700;">Quota Reached</span>`;
        } else {
            controlHTML = `
                <button onclick="window.changePromoQty(${p.id_produk}, -1, ${absoluteMax})" style="width:26px; height:26px; border-radius:50%; border:2px solid #94a3b8; background:none; cursor:pointer; font-size:14px; line-height:1; display:flex; align-items:center; justify-content:center; color:#64748b;">−</button>

                <span id="qty-${p.id_produk}" style="font-family:'Bell MT',serif; font-size:18px; font-weight:bold; color:#0f3891; min-width:24px; text-align:center;">
                    ${currentQty}
                </span>

                <button onclick="window.changePromoQty(${p.id_produk}, 1, ${absoluteMax})" style="width:26px; height:26px; border-radius:50%; border:2px solid #0f3891; background:#0f3891; cursor:pointer; font-size:14px; line-height:1; color:#fff; display:flex; align-items:center; justify-content:center;">+</button>
            `;
        }

        item.innerHTML = `
            <div style="background:#0f3891; color:#fff; border-radius:6px; padding:4px 12px; font-weight:700; font-size:13px; display:inline-block; margin-bottom:10px;">
                ${escapeHtml(p.nama_produk)}
            </div>

            <!-- Info Limit & Stok -->
            <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-family:Inter,sans-serif; font-size:12px;">
                <div style="background:#e0f2fe; color:#0369a1; padding:6px 10px; border-radius:8px; width:48%;">
                    <span style="display:block; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:2px;">Available Stock</span>
                    <span style="font-size:16px; font-weight:800;">${currentStock} pcs</span>
                </div>
                <div style="background:#fef3c7; color:#b45309; padding:6px 10px; border-radius:8px; width:48%;">
                    <span style="display:block; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:2px;">Your Quota</span>
                    <span style="font-size:16px; font-weight:800;">${maxAllowed} <span style="font-size:10px; font-weight:600;">more</span></span>
                </div>
            </div>

            <div style="height:1px; background:#e2e8f0; margin-bottom:10px;"></div>

            <!-- Price & Input -->
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <span style="font-family:'Bell MT',serif; font-size:20px; color:#0f3891; font-weight:bold;">
                    ${formatRupiah(p.harga_event)}
                </span>
                <div style="display:flex; align-items:center; gap:8px;">
                    ${controlHTML}
                </div>
            </div>
        `;
        list.appendChild(item);
    }

    const totalPages = Math.ceil(promoEventProducts.length / PROMO_ORDER_PAGE_SIZE);
    document.getElementById('btn-prev-order-product').style.display = (totalPages > 1) ? 'flex' : 'none';
    document.getElementById('btn-next-order-product').style.display = (totalPages > 1) ? 'flex' : 'none';
    document.getElementById('btn-prev-order-product').disabled = (promoOrderPage === 0);
    document.getElementById('btn-next-order-product').disabled = (promoOrderPage >= totalPages - 1);
}

window.promoChangeOrderPage = function(delta) {
    const totalPages = Math.ceil(promoEventProducts.length / PROMO_ORDER_PAGE_SIZE);
    const next = promoOrderPage + delta;
    if (next >= 0 && next < totalPages) {
        promoOrderPage = next;
        renderPromoOrderProducts();
    }
};

window.changePromoQty = function(idProduk, delta, absoluteMax) {
    const current = promoOrderQuantities[idProduk] || 0;
    const next    = current + delta;

    if (next < 0) return;
    if (next > absoluteMax) {
        Swal.fire({ title: 'Limit Reached', text: 'You cannot add more due to stock or purchase limits.', icon: 'warning' });
        return;
    }

    promoOrderQuantities[idProduk] = next;
    renderPromoOrderProducts(); // Re-render untuk mengupdate angka span
};

/* ─────────────────────────────────────────────
   LOAD ALREADY PURCHASED
───────────────────────────────────────────── */
function loadPromoAlreadyPurchased(callback) {
    if (!promoIdPengguna || !promoCurrentEventId) {
        if (callback) callback();
        return;
    }

    fetch('/cardhaven/interface/event-transaction/controllerPromoTransaction.php?action=get_purchase_count&id_event=' + promoCurrentEventId + '&id_pengguna=' + promoIdPengguna)
        .then(r => r.json())
        .then(function (data) {
            promoAlreadyPurchased = data.counts || {};
            if (callback) callback();
        })
        .catch(function (err) {
            console.error('loadAlreadyPurchased error:', err);
            if (callback) callback();
        });
}

/* ─────────────────────────────────────────────
   SUBMIT ORDER (PROMO) - REDIRECT KE CHECKOUT
───────────────────────────────────────────── */
window.submitPromoOrder = function() {
    if (!promoIdPengguna) {
        Swal.fire('Not Logged In', 'Please log in before purchasing.', 'warning'); return;
    }

    const selectedItems = promoEventProducts
        .filter(function (p) { return (promoOrderQuantities[p.id_produk] || 0) > 0; })
        .map(function (p) {
            const qty = promoOrderQuantities[p.id_produk];
            return {
                id_produk:    p.id_produk,
                nama_produk:  p.nama_produk,
                foto:         p.foto,
                jumlah_barang: qty, 
                harga_produk: p.harga_event,
                subtotal_harga: qty * p.harga_event
            };
        });

    if (!selectedItems.length) {
        Swal.fire('No Items Selected', 'Please add at least one item to your order.', 'warning'); return;
    }

    // FINAL CHECK: Validasi Limit Individu
    const eventMax = parseInt(promoCurrentEvent.maks_pembelian) || 0;
    for (let item of selectedItems) {
        const alreadyBought = promoAlreadyPurchased[item.id_produk] || 0;
        if (alreadyBought + item.jumlah_barang > eventMax) {
            Swal.fire('Purchase Limit Exceeded', `You exceeded the limit for one of the products.`, 'warning');
            return;
        }
    }

    // Tutup popup
    document.getElementById('pop-up-overlay').style.display = 'none';
    document.getElementById('pop-up-event').style.display   = 'none';

    // BUNGKUS DATA DAN LEMPAR KE HALAMAN CHECKOUT
    const payload = {
        checkout_type: 'promo',
        id_event: promoCurrentEventId,
        nama_event: promoCurrentEvent.nama_event,
        persen_diskon: promoCurrentEvent.persen_diskon,
        items: selectedItems
    };

    sessionStorage.setItem('direct_checkout_data', JSON.stringify(payload));
    window.location.href = '/CardHaven/checkout';
};

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