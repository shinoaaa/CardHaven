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

// Anti-spam flags
let isPlacingOrder    = false;
let isSubmittingProof = false;

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
                showAlert('checkout', data.message || 'Gagal memuat info pengguna.', 'error');
            }
        })
        .catch(err => {
            const msg = err.name === 'AbortError'
                ? 'Koneksi timeout saat memuat info pengguna. Coba refresh halaman.'
                : 'Gagal memuat info pengguna. Coba refresh halaman.';
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
                    ${escapeHtml(data.message || 'Gagal memuat item.')}
                    <a href="/cardhaven/interface/cart/" style="color:#1a3a6b;font-weight:700;">Kembali ke keranjang</a>.
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

            items.forEach(item => {
                cartSubtotal   += parseFloat(item.subtotal_harga) || 0;
                cartTotalItems += parseInt(item.jumlah_barang)    || 0;
                list.appendChild(renderCheckoutItem(item));
            });

            document.getElementById('summary-items-label').textContent =
                `Items (${items.length} product${items.length > 1 ? 's' : ''}, ${cartTotalItems} pcs)`;
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
                    ? 'Koneksi timeout. Periksa internet Anda.'
                    : 'Gagal memuat item. Coba refresh halaman.';
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
                    ${escapeHtml(data.message || 'Gagal memuat metode pembayaran.')}
                </p>`;
                return;
            }

            const methods = data.methods || [];

            if (methods.length === 0) {
                list.innerHTML = `<p style="color:#888;font-size:0.88rem;">No payment methods available.</p>`;
                return;
            }

            methods.forEach(m => {
                const div = document.createElement('div');
                div.className   = 'payment-method-option';
                div.dataset.id  = m.id_metode;
                div.dataset.fee = m.biaya_admin;

                const feeText = parseFloat(m.biaya_admin) > 0
                    ? `<span class="payment-method-fee">+${fmt(m.biaya_admin)} fee</span>`
                    : `<span class="payment-method-fee free">No fee</span>`;

                div.innerHTML = `
                    <input type="radio" name="payment_method" value="${m.id_metode}"
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

            checkCanOrder();
        })
        .catch(err => {
            const loading = document.getElementById('payment-method-loading');
            const list    = document.getElementById('payment-method-list');
            if (loading) loading.style.display = 'none';
            if (list) {
                list.style.display = 'block';
                const msg = err.name === 'AbortError'
                    ? 'Koneksi timeout. Periksa internet Anda.'
                    : 'Gagal memuat metode pembayaran. Coba refresh halaman.';
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

function renderCheckoutItem(item) {
    const div = document.createElement('div');
    div.className = 'checkout-item';
    const fotoSrc = item.foto
        ? `${BASE_IMG_URL}/${item.foto}`
        : `${BASE_IMG_URL}/image-profile/defaultProduct.jpg`;
        
    div.innerHTML = `
        <div class="checkout-item-img">
            <img src="${fotoSrc}"
                 alt="${escapeHtml(item.nama_produk)}"
                 onerror="this.src='${BASE_IMG_URL}/image-profile/no-image.png'">
        </div>
        <div class="checkout-item-info">
            <div class="checkout-item-name">${escapeHtml(item.nama_produk)}</div>
            <div class="checkout-item-meta">
                ${fmt(item.harga_produk)} × ${item.jumlah_barang}
            </div>
        </div>
        <div class="checkout-item-subtotal">${fmt(item.subtotal_harga)}</div>
    `;
    return div;
}

function selectPaymentMethod(id, fee, el) {
    document.querySelectorAll('.payment-method-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    selectedMethodId  = id;
    selectedMethodFee = parseFloat(fee) || 0;
    updateSummary();
    checkCanOrder();
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
    const ready  = name.length > 0 && alamat.length > 0 && selectedMethodId !== null && cartSubtotal > 0;
    const btn    = document.getElementById('btn-place-order');
    if (btn) btn.disabled = !ready || isPlacingOrder;
}

document.addEventListener('input', e => {
    if (['field-phone', 'field-alamat'].includes(e.target.id)) checkCanOrder();
});

// ============================================================
// PLACE ORDER (Step 1 → Step 2) — anti-spam
// ============================================================

function placeOrder() {
    if (isPlacingOrder) return;
    const alamat = document.getElementById('field-alamat').value.trim();
    if (!alamat)          { showAlert('checkout', 'Please enter your shipping address.', 'error'); return; }
    if (!selectedMethodId){ showAlert('checkout', 'Please select a payment method.', 'error');     return; }

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
                isPlacingOrder = false;
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
                ? 'Koneksi timeout. Coba lagi.'
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
                ? 'Upload timeout. File mungkin terlalu besar atau koneksi lambat.'
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
               href="/cardhaven/interface/home/"
               style="padding:12px 28px;background:#1a3a6b;color:white;border-radius:6px;
                      font-weight:800;text-decoration:none;font-size:0.85rem;
                      text-transform:uppercase;letter-spacing:1px;">
                🏠 Back to Home
            </a>
            <a href="/cardhaven/interface/cart/"
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