const CHECKOUT_CONTROLLER = '/cardhaven/interface/checkout/controller_checkout.php';
const BASE_IMG_URL        = '/cardhaven';
const idPengguna          = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

const fmt = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n));

let selectedMethodId   = null;
let selectedMethodFee  = 0;
let currentOrderId     = null;
let cartSubtotal       = 0;
let cartTotalItems     = 0; // Diubah menjadi global agar bisa dibaca oleh placeOrder()
let selectedFile       = null;
let hasStockIssue      = false; // true jika ada item yang stoknya tidak cukup/habis

// Pagination metode pembayaran
let allMethods         = [];
let methodPage         = 1;
const METHODS_PER_PAGE = 4;

// Anti-spam flags
let isPlacingOrder    = false;
let isSubmittingProof = false;
// Sekali order berhasil dibuat, halaman ini tidak boleh membuat order lagi
// (mencegah "bayar lagi" saat user menekan back / restore dari bfcache).
let orderPlaced       = false;

// ============================================================
// FETCH WITH TIMEOUT
// ============================================================
function fetchWithTimeout(url, options = {}, ms = 10000) {
    const ctrl = new AbortController();
    const timer = setTimeout(() => ctrl.abort(), ms);
    return fetch(url, { ...options, signal: ctrl.signal })
        .finally(() => clearTimeout(timer));
}

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    loadUserInfo();
    loadCartItems();
    loadPaymentMethods();
});

// Jika halaman dipulihkan dari bfcache (user menekan tombol back setelah
// meninggalkan halaman), muat ulang agar state order tidak bisa dipakai lagi.
window.addEventListener('pageshow', (e) => {
    if (e.persisted && orderPlaced) {
        window.location.reload();
    }
});

// ============================================================
// STEP 1: Load data
// ============================================================

function loadUserInfo() {
    fetch(`${CHECKOUT_CONTROLLER}?action=get_checkout_data&idpengguna=${idPengguna}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const userData = data.user || {};
                document.getElementById('field-name').value  = userData.username || '';
                document.getElementById('field-phone').value = userData.no_telepon || '';
                checkCanOrder();
            } else {
                showAlert('checkout', data.message || 'Failed to load user info.', 'error');
            }
        })
        .catch(err => {
            const msg = err.name === 'AbortError'
                ? 'Connection timed out while loading user info. Please refresh the page.'
                : 'Failed to load user info. Please refresh the page.';
            showAlert('checkout', msg, 'error');
            console.error(err);
        });
}

function loadCartItems() {
    fetchWithTimeout(`${CHECKOUT_CONTROLLER}?action=get_checkout_data&idpengguna=${idPengguna}`)
        .then(r => r.json())
        .then(data => {
            const loading = document.getElementById('checkout-items-loading');
            const list    = document.getElementById('checkout-item-list');

            loading.style.display = 'none';
            list.style.display    = 'flex';

            if (!data.success) {
                list.innerHTML = `<p style="color:#dc2626;font-size:0.88rem;">
                    ${escapeHtml(data.message || 'Failed to load items.')}
                    <a href="/cardhaven/interface/cart/" style="color:#1a3a6b;font-weight:700;">Return to Card</a>.
                </p>`;
                updateSummary();
                checkCanOrder();
                return;
            }

            const items = data.items || [];

            if (items.length === 0) {
                list.innerHTML = `<p style="color:#888;font-size:0.88rem;">
                    No items selected. <a href="/cardhaven/interface/cart/">Return to cart</a>.
                </p>`;
                updateSummary();
                checkCanOrder();
                return;
            }

            cartSubtotal   = 0;
            cartTotalItems = 0;
            hasStockIssue  = false;

            items.forEach(item => {
                const qty  = parseInt(item.jumlah_barang) || 0;
                const stok = parseInt(item.stok);
                const outOfStock = !isNaN(stok) && qty > stok;
                if (outOfStock) hasStockIssue = true;

                cartSubtotal   += parseFloat(item.subtotal_harga) || 0;
                cartTotalItems += qty;
                list.appendChild(renderCheckoutItem(item, outOfStock, stok));
            });

            document.getElementById('summary-items-label').textContent =
                `Items (${items.length} product${items.length > 1 ? 's' : ''}, ${cartTotalItems} pcs)`;

            renderSummaryItems(items);

            if (hasStockIssue) {
                showAlert('checkout',
                    'Some products do not have enough stock. Reduce the quantity in your cart before paying.',
                    'error');
            }
            updateSummary();
            checkCanOrder();
        })
        .catch(err => {
            const loading = document.getElementById('checkout-items-loading');
            const list    = document.getElementById('checkout-item-list');
            if (loading) loading.style.display = 'none';
            if (list) {
                list.style.display = 'block';
                const msg = err.name === 'AbortError'
                    ? 'Connection Failed. Check your connection.'
                    : 'Failed to load items. Try to refresh the page.';
                list.innerHTML = `
                    <div style="padding:16px;text-align:center;color:#dc2626;">
                        <p style="font-weight:700;margin-bottom:8px;">⚠ ${msg}</p>
                        <button onclick="location.reload()"
                                style="padding:8px 20px;background:#1a3a6b;color:white;
                                border:none;border-radius:6px;cursor:pointer;font-weight:700;">
                            🔄 Refresh
                        </button>
                    </div>`;
            }
            console.error(err);
        });
}

function loadPaymentMethods() {
    fetchWithTimeout(`${CHECKOUT_CONTROLLER}?action=get_checkout_data&idpengguna=${idPengguna}`)
        .then(r => r.json())
        .then(data => {
            const loading = document.getElementById('payment-method-loading');
            const list    = document.getElementById('payment-method-list');

            loading.style.display = 'none';
            list.style.display    = 'flex';

            if (!data.success) {
                list.innerHTML = `<p style="color:#dc2626;font-size:0.88rem;">
                    ${escapeHtml(data.message || 'Failed to load the payment method.')}
                </p>`;
                return;
            }

            allMethods = data.methods || [];

            if (allMethods.length === 0) {
                list.innerHTML = `<p style="color:#888;font-size:0.88rem;">No payment methods available.</p>`;
                return;
            }

            methodPage = 1;
            renderPaymentMethods();
            checkCanOrder();
        })
        .catch(err => {
            const loading = document.getElementById('payment-method-loading');
            const list    = document.getElementById('payment-method-list');
            if (loading) loading.style.display = 'none';
            if (list) {
                list.style.display = 'block';
                const msg = err.name === 'AbortError'
                    ? 'Connection Failed. Check your connection.'
                    : 'Failed to load items. Try to refresh the page.';
                list.innerHTML = `
                    <div style="padding:16px;text-align:center;color:#dc2626;">
                        <p style="font-weight:700;margin-bottom:8px;">⚠ ${msg}</p>
                        <button onclick="location.reload()"
                                style="padding:8px 20px;background:#1a3a6b;color:white;
                                border:none;border-radius:6px;cursor:pointer;font-weight:700;">
                            🔄 Refresh
                        </button>
                    </div>`;
            }
            console.error(err);
        });
}

function renderCheckoutItem(item, outOfStock = false, stok = null) {
    const div = document.createElement('div');
    div.className = 'checkout-item';
    const fotoSrc = item.foto
        ? `${BASE_IMG_URL}/assets/image/products/${item.foto}`
        : `${BASE_IMG_URL}/image-profile/defaultProduct.jpg`;

    const stockWarn = outOfStock
        ? `<div style="color:#dc2626; font-size:0.72rem; font-weight:700; margin-top:2px;">
               ⚠ Only ${stok ?? 0} left in stock, reduce the quantity in your cart.
           </div>`
        : '';

    div.innerHTML = `
        <div class="checkout-item-img">
            <img src="${fotoSrc}"
                 alt="${escapeHtml(item.nama_produk)}"
                 onerror="this.src='${BASE_IMG_URL}/image-profile/no-image.png'"
                 style="${outOfStock ? 'filter: grayscale(1) brightness(0.75);' : ''}">
        </div>
        <div class="checkout-item-info">
            <div class="checkout-item-name">${escapeHtml(item.nama_produk)}</div>
            <div class="checkout-item-meta">
                ${fmt(item.harga_produk)} × ${item.jumlah_barang}
            </div>
            ${stockWarn}
        </div>
        <div class="checkout-item-subtotal">${fmt(item.subtotal_harga)}</div>
    `;
    return div;
}

// Render metode pembayaran per halaman (pagination) supaya list tidak kepanjangan.
function renderPaymentMethods() {
    const list = document.getElementById('payment-method-list');
    if (!list) return;
    list.innerHTML = '';
    list.style.display = 'flex';

    const totalPages = Math.max(1, Math.ceil(allMethods.length / METHODS_PER_PAGE));
    if (methodPage > totalPages) methodPage = totalPages;
    const start = (methodPage - 1) * METHODS_PER_PAGE;
    const pageItems = allMethods.slice(start, start + METHODS_PER_PAGE);

    pageItems.forEach(m => {
        const div = document.createElement('div');
        div.className   = 'payment-method-option';
        div.dataset.id  = m.id_metode;
        div.dataset.fee = m.biaya_admin;
        if (String(selectedMethodId) === String(m.id_metode)) div.classList.add('selected');

        const feeText = parseFloat(m.biaya_admin) > 0
            ? `<span class="payment-method-fee">+${fmt(m.biaya_admin)} fee</span>`
            : `<span class="payment-method-fee free">No fee</span>`;

        div.innerHTML = `
            <input type="radio" name="payment_method" value="${m.id_metode}"
                   ${String(selectedMethodId) === String(m.id_metode) ? 'checked' : ''}
                   onchange="selectPaymentMethod(${m.id_metode}, ${m.biaya_admin}, this.closest('.payment-method-option'))">
            <div class="payment-method-info">
                <div class="payment-method-name">${escapeHtml(m.nama_metode)}</div>
                <div class="payment-method-detail">
                    ${escapeHtml(m.provider || '')}
                    ${m.no_rekening ? '· ' + escapeHtml(m.no_rekening) : ''}
                    ${m.atas_nama   ? '· a/n ' + escapeHtml(m.atas_nama) : ''}
                </div>
            </div>
            ${feeText}
        `;
        list.appendChild(div);
    });

    renderPaymentPagination(totalPages);
}

function renderPaymentPagination(totalPages) {
    let bar = document.getElementById('payment-method-pagination');
    if (!bar) return;
    if (totalPages <= 1) { bar.innerHTML = ''; return; }

    bar.innerHTML = `
        <button type="button" class="pm-page-btn" ${methodPage <= 1 ? 'disabled' : ''}
                onclick="changeMethodPage(-1)">‹ Prev</button>
        <span style="font-size:0.8rem;color:#64748b;font-weight:600;">Page ${methodPage} of ${totalPages}</span>
        <button type="button" class="pm-page-btn" ${methodPage >= totalPages ? 'disabled' : ''}
                onclick="changeMethodPage(1)">Next ›</button>
    `;
}

function changeMethodPage(delta) {
    const totalPages = Math.max(1, Math.ceil(allMethods.length / METHODS_PER_PAGE));
    methodPage = Math.min(totalPages, Math.max(1, methodPage + delta));
    renderPaymentMethods();
}

function selectPaymentMethod(id, fee, el) {
    document.querySelectorAll('.payment-method-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    selectedMethodId  = id;
    selectedMethodFee = parseFloat(fee) || 0;
    updateSummary();
    checkCanOrder();
}

// Rincian total di Order Summary: tampilkan tiap barang beserta subtotalnya
function renderSummaryItems(items) {
    const box = document.getElementById('summary-items');
    if (!box) return;
    box.innerHTML = (items || []).map(it => `
        <div class="summary-line">
            <div>
                <div class="summary-line-name">${escapeHtml(it.nama_produk)}</div>
                <div class="summary-line-qty">${fmt(it.harga_produk)} × ${it.jumlah_barang}</div>
            </div>
            <div class="summary-line-price">${fmt(it.subtotal_harga)}</div>
        </div>
    `).join('');
}

function updateSummary() {
    const grand = cartSubtotal + selectedMethodFee;
    document.getElementById('summary-subtotal').textContent    = fmt(cartSubtotal);
    document.getElementById('summary-grand-total').textContent = fmt(grand);

    const feeRow = document.getElementById('summary-fee-row');
    if (selectedMethodFee > 0) {
        feeRow.style.display = 'flex';
        document.getElementById('summary-fee').textContent = '+' + fmt(selectedMethodFee);
    } else {
        feeRow.style.display = 'none';
    }
}

function checkCanOrder() {
    const name   = (document.getElementById('field-name')?.value   || '').trim();
    const alamat = (document.getElementById('field-alamat')?.value  || '').trim();
    const phone  = (document.getElementById('field-phone')?.value   || '').trim();
    const ready  = name.length > 0 && alamat.length > 0 && isValidPhone(phone) &&
                   selectedMethodId !== null && cartSubtotal > 0 && !hasStockIssue;
    const btn    = document.getElementById('btn-place-order');
    if (btn) btn.disabled = !ready || isPlacingOrder || orderPlaced;
}

// Nomor telepon valid: hanya angka/spasi/+ - ( ), diawali angka atau '+',
// dan 8–15 digit. Menolak input yang hanya karakter unik/spesial.
function isValidPhone(phone) {
    const digits = (phone.match(/\d/g) || []).length;
    return /^\+?[0-9][0-9\s\-()]*$/.test(phone) && digits >= 8 && digits <= 15;
}

document.addEventListener('input', e => {
    if (['field-phone', 'field-alamat'].includes(e.target.id)) checkCanOrder();
});

// ============================================================
// PLACE ORDER (Step 1 → Step 2) — anti-spam
// ============================================================

function placeOrder() {
    if (isPlacingOrder || orderPlaced) return;
    const alamat = document.getElementById('field-alamat').value.trim();
    const phone  = document.getElementById('field-phone').value.trim();
    if (!alamat)          { showAlert('checkout', 'Please enter your shipping address.', 'error'); return; }
    if (!phone)           { showAlert('checkout', 'Please enter your phone number.', 'error'); return; }
    if (!isValidPhone(phone)) {
        showAlert('checkout', 'Invalid phone number. Use digits only (8–15 digits).', 'error');
        document.getElementById('field-phone').focus();
        return;
    }
    if (!selectedMethodId){ showAlert('checkout', 'Please select a payment method.', 'error');     return; }
    if (hasStockIssue)    { showAlert('checkout', 'Some products do not have enough stock. Update your cart first.', 'error'); return; }

    isPlacingOrder = true;
    const btn = document.getElementById('btn-place-order');
    btn.disabled    = true;
    btn.textContent = 'Processing...';
    
    const fd = new FormData();
    fd.append('action',       'place_order');
    fd.append('idpengguna',   idPengguna); // Perbaikan kritis: ID diperlukan oleh PHP
    fd.append('alamat',       alamat);
    fd.append('id_metode',    selectedMethodId);
    fd.append('total_harga',  cartSubtotal + selectedMethodFee); // Perbaikan: Dibutuhkan prosedur DB
    fd.append('total_barang', cartTotalItems); // Perbaikan: Dibutuhkan prosedur DB

    fetchWithTimeout(CHECKOUT_CONTROLLER, { method: 'POST', body: fd }, 15000)
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                currentOrderId = json.id_penjualan;
                orderPlaced    = true; // kunci: order sudah dibuat, tidak boleh submit lagi
                goToStep2(json);
            } else {
                showAlert('checkout', json.message || 'Failed to place order.', 'error');
                isPlacingOrder  = false;
                btn.disabled    = false;
                btn.textContent = 'Place Order →';
                checkCanOrder();
            }
        })
        .catch(err => {
            const msg = err.name === 'AbortError'
                ? 'Connection failed. Please try again.'
                : 'Network error. Please try again.';
            showAlert('checkout', msg, 'error');
            isPlacingOrder  = false;
            btn.disabled    = false;
            btn.textContent = 'Place Order →';
            checkCanOrder();
            console.error(err);
        });
}

// ============================================================
// STEP 2: Upload Bukti Pembayaran
// ============================================================

function goToStep2(orderData) {
    markStepDone(1);
    markStepActive(2);

    document.getElementById('step1-content').style.display = 'none';
    document.getElementById('step2-content').style.display = 'block';

    document.getElementById('step2-order-id').textContent             = '#' + currentOrderId;
    const totalWithFee = cartSubtotal + selectedMethodFee;
    document.getElementById('step2-total').textContent                = fmt(totalWithFee);
    document.getElementById('payment-instruction-amount').textContent = fmt(totalWithFee);
    document.getElementById('payment-instruction-detail').innerHTML   = orderData.payment_detail || '';

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function handleFileSelect(input) {
    if (input.files && input.files[0]) setUploadFile(input.files[0]);
}

function handleFileDrop(event) {
    event.preventDefault();
    const zone = document.getElementById('upload-drop-zone');
    zone.style.borderColor = '#dde4f8';
    zone.style.background  = '';
    const file = event.dataTransfer.files[0];
    if (file) setUploadFile(file);
}

function setUploadFile(file) {
    const maxSize      = 5 * 1024 * 1024;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    if (file.size > maxSize) {
        showAlert('upload', 'File is too large. Maximum 5MB.', 'error');
        return;
    }
    if (!allowedTypes.includes(file.type)) {
        showAlert('upload', 'Invalid file type. Upload JPG, PNG, WEBP, or PDF.', 'error');
        return;
    }

    selectedFile = file;

    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('file-preview-img').src       = e.target.result;
            document.getElementById('file-preview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('file-preview-img').src       = `${BASE_IMG_URL}/assets/image/pdf-icon.svg`;
        document.getElementById('file-preview').style.display = 'block';
    }

    document.getElementById('btn-submit-payment').disabled = false;
    hideAlert('upload');
}

function clearFile() {
    selectedFile = null;
    document.getElementById('bukti-file-input').value        = '';
    document.getElementById('file-preview').style.display    = 'none';
    document.getElementById('btn-submit-payment').disabled   = true;
    isSubmittingProof = false;
}

function submitPayment() {
    if (isSubmittingProof) return;
    if (!selectedFile) {
        showAlert('upload', 'Please upload your payment proof first.', 'error');
        return;
    }

    isSubmittingProof = true;
    const btn = document.getElementById('btn-submit-payment');
    btn.disabled    = true;
    btn.textContent = 'Uploading...';
    
    const fd = new FormData();
    fd.append('action',           'upload_bukti');
    fd.append('idpengguna',       idPengguna); // Perbaikan kritis: Otorisasi klien
    fd.append('id_penjualan',     currentOrderId);
    fd.append('bukti_pembayaran', selectedFile); // Perbaikan: Nama key disesuaikan dengan $_FILES di PHP

    fetchWithTimeout(CHECKOUT_CONTROLLER, { method: 'POST', body: fd }, 20000)
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                isSubmittingProof = false;
                goToStep3();
            } else {
                showAlert('upload', json.message || 'Upload failed. Please try again.', 'error');
                isSubmittingProof = false;
                btn.disabled    = false;
                btn.textContent = 'Submit Payment Proof →';
            }
        })
        .catch(err => {
            const msg = err.name === 'AbortError'
                ? 'Upload timeout. The file may be too large or the network connection may be slow.'
                : 'Network error. Please try again.';
            showAlert('upload', msg, 'error');
            isSubmittingProof = false;
            btn.disabled    = false;
            btn.textContent = 'Submit Payment Proof →';
            console.error(err);
        });
}

// ============================================================
// STEP 3: Confirmation
// ============================================================

function goToStep3() {
    markStepDone(2);
    markStepActive(3);

    document.getElementById('step2-content').style.display = 'none';
    document.getElementById('step3-content').style.display = 'block';
    document.getElementById('confirm-order-id').textContent = '#' + currentOrderId;

    const step3 = document.getElementById('step3-content');
    if (step3 && !document.getElementById('btn-back-home')) {
        const btnWrapper = document.createElement('div');
        btnWrapper.style.cssText = 'display:flex;gap:12px;margin-top:28px;justify-content:center;flex-wrap:wrap;';
        btnWrapper.innerHTML = `
            <a id="btn-back-home"
               href="/CardHaven/home"
               style="padding:12px 28px;background:#1a3a6b;color:white;border-radius:6px;
                      font-weight:800;text-decoration:none;font-size:0.85rem;
                      text-transform:uppercase;letter-spacing:1px;">
                🏠 Back to Home
            </a>
            <a href="/CardHaven/home/cart"
               style="padding:12px 28px;background:white;color:#1a3a6b;border-radius:6px;
                      font-weight:800;text-decoration:none;font-size:0.85rem;
                      text-transform:uppercase;letter-spacing:1px;
                      border:2px solid #1a3a6b;">
                🛒 My Cart
            </a>
        `;
        step3.appendChild(btnWrapper);
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ============================================================
// STEP INDICATOR HELPERS
// ============================================================

function markStepDone(n) {
    const el = document.getElementById(`step${n}-indicator`);
    if (!el) return;
    el.classList.remove('active');
    el.classList.add('done');
    el.querySelector('.step-circle').innerHTML = '✓';
}

function markStepActive(n) {
    const el = document.getElementById(`step${n}-indicator`);
    if (!el) return;
    el.classList.add('active');
}

// ============================================================
// ALERT HELPERS
// ============================================================

function showAlert(context, msg, type) {
    const el = document.getElementById(`alert-${context}`);
    if (!el) return;
    el.textContent = msg;
    el.className   = `alert-box ${type} show`;
}

function hideAlert(context) {
    const el = document.getElementById(`alert-${context}`);
    if (el) el.className = 'alert-box';
}

// ============================================================
// XSS SANITIZE
// ============================================================
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}