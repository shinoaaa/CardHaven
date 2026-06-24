/**
 * checkout_script.js — CardHaven Checkout Flow
 * Menangani 3 tahap: (1) Detail Order → (2) Upload Bukti → (3) Konfirmasi
 */

const CHECKOUT_CONTROLLER = '/cardhaven/interface/checkout/controller_checkout.php';

const fmt = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n));

let selectedMethodId   = null;
let selectedMethodFee  = 0;
let currentOrderId     = null;
let cartSubtotal       = 0;
let selectedFile       = null;

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
    fetch(`${CHECKOUT_CONTROLLER}?action=get_user_info`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('field-name').value  = data.username  || '';
                document.getElementById('field-phone').value = data.no_telepon || '';
            }
        })
        .catch(console.error);
}

function loadCartItems() {
    fetch(`${CHECKOUT_CONTROLLER}?action=get_selected_items`)
        .then(r => r.json())
        .then(data => {
            const loading = document.getElementById('checkout-items-loading');
            const list    = document.getElementById('checkout-item-list');

            loading.style.display = 'none';
            list.style.display    = 'flex';

            if (!data || data.length === 0) {
                list.innerHTML = `<p style="color:#888;font-size:0.88rem;">
                    No items selected. <a href="/cardhaven/interface/cart/">Return to cart</a>.
                </p>`;
                return;
            }

            cartSubtotal = 0;
            let totalItems = 0;
            data.forEach(item => {
                cartSubtotal += parseFloat(item.subtotal_harga) || 0;
                totalItems   += parseInt(item.jumlah_barang)    || 0;
                list.appendChild(renderCheckoutItem(item));
            });

            document.getElementById('summary-items-label').textContent =
                `Items (${data.length} product${data.length > 1 ? 's' : ''}, ${totalItems} pcs)`;

            updateSummary();
            checkCanOrder();
        })
        .catch(console.error);
}

function renderCheckoutItem(item) {
    const div = document.createElement('div');
    div.className = 'checkout-item';
    div.innerHTML = `
        <div class="checkout-item-img">
            <img src="/CardHaven/${escapeHtml(item.foto)}"
                 alt="${escapeHtml(item.nama_produk)}"
                 onerror="this.src='/cardhaven/interface/assets/img/no-image.png'">
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

function loadPaymentMethods() {
    fetch(`${CHECKOUT_CONTROLLER}?action=get_payment_methods`)
        .then(r => r.json())
        .then(methods => {
            const loading = document.getElementById('payment-method-loading');
            const list    = document.getElementById('payment-method-list');

            loading.style.display = 'none';
            list.style.display    = 'flex';

            if (!methods || methods.length === 0) {
                list.innerHTML = `<p style="color:#888;font-size:0.88rem;">No payment methods available.</p>`;
                return;
            }

            methods.forEach(m => {
                const div = document.createElement('div');
                div.className = 'payment-method-option';
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
        .catch(console.error);
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

    document.getElementById('summary-subtotal').textContent   = fmt(cartSubtotal);
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
    const name   = (document.getElementById('field-name')?.value  || '').trim();
    const alamat = (document.getElementById('field-alamat')?.value || '').trim();
    const ready  = name && alamat && selectedMethodId && cartSubtotal > 0;
    document.getElementById('btn-place-order').disabled = !ready;
}

document.addEventListener('input', e => {
    if (['field-name','field-phone','field-alamat'].includes(e.target.id)) checkCanOrder();
});

// ============================================================
// PLACE ORDER (Step 1 → Step 2)
// ============================================================

function placeOrder() {
    const alamat = document.getElementById('field-alamat').value.trim();
    if (!alamat) { showAlert('checkout', 'Please enter your shipping address.', 'error'); return; }
    if (!selectedMethodId) { showAlert('checkout', 'Please select a payment method.', 'error'); return; }

    const btn = document.getElementById('btn-place-order');
    btn.disabled      = true;
    btn.textContent   = 'Processing...';

    const fd = new FormData();
    fd.append('action',    'place_order');
    fd.append('alamat',    alamat);
    fd.append('id_metode', selectedMethodId);

    fetch(CHECKOUT_CONTROLLER, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                currentOrderId = json.id_penjualan;
                goToStep2(json);
            } else {
                showAlert('checkout', json.message || 'Failed to place order.', 'error');
                btn.disabled    = false;
                btn.textContent = 'Place Order →';
            }
        })
        .catch(err => {
            showAlert('checkout', 'Network error. Please try again.', 'error');
            btn.disabled    = false;
            btn.textContent = 'Place Order →';
            console.error(err);
        });
}

// ============================================================
// STEP 2: Upload Bukti Pembayaran
// ============================================================

function goToStep2(orderData) {
    // Update step indicators
    markStepDone(1);
    markStepActive(2);

    document.getElementById('step1-content').style.display = 'none';
    document.getElementById('step2-content').style.display = 'block';

    // Populate payment instruction
    document.getElementById('step2-order-id').textContent = '#' + currentOrderId;
    const totalWithFee = cartSubtotal + selectedMethodFee;
    document.getElementById('step2-total').textContent = fmt(totalWithFee);
    document.getElementById('payment-instruction-amount').textContent = fmt(totalWithFee);
    document.getElementById('payment-instruction-detail').innerHTML   = orderData.payment_detail || '';
}

function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        setUploadFile(input.files[0]);
    }
}

function handleFileDrop(event) {
    event.preventDefault();
    document.getElementById('upload-drop-zone').style.borderColor = '#dde4f8';
    document.getElementById('upload-drop-zone').style.background  = '';
    const file = event.dataTransfer.files[0];
    if (file) setUploadFile(file);
}

function setUploadFile(file) {
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        showAlert('upload', 'File is too large. Maximum 5MB.', 'error');
        return;
    }

    const allowedTypes = ['image/jpeg','image/png','image/webp','application/pdf'];
    if (!allowedTypes.includes(file.type)) {
        showAlert('upload', 'Invalid file type. Upload JPG, PNG, or PDF.', 'error');
        return;
    }

    selectedFile = file;

    // Preview
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('file-preview-img').src = e.target.result;
            document.getElementById('file-preview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        // PDF: tampilkan nama file saja
        document.getElementById('file-preview-img').src = '/cardhaven/interface/assets/img/pdf-icon.png';
        document.getElementById('file-preview').style.display = 'block';
    }

    document.getElementById('btn-submit-payment').disabled = false;
    hideAlert('upload');
}

function clearFile() {
    selectedFile = null;
    document.getElementById('bukti-file-input').value = '';
    document.getElementById('file-preview').style.display  = 'none';
    document.getElementById('btn-submit-payment').disabled = true;
}

function submitPayment() {
    if (!selectedFile) {
        showAlert('upload', 'Please upload your payment proof first.', 'error');
        return;
    }

    const btn    = document.getElementById('btn-submit-payment');
    btn.disabled    = true;
    btn.textContent = 'Uploading...';

    const fd = new FormData();
    fd.append('action',       'upload_bukti');
    fd.append('id_penjualan', currentOrderId);
    fd.append('bukti',        selectedFile);

    fetch(CHECKOUT_CONTROLLER, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                goToStep3();
            } else {
                showAlert('upload', json.message || 'Upload failed. Please try again.', 'error');
                btn.disabled    = false;
                btn.textContent = 'Submit Payment Proof →';
            }
        })
        .catch(err => {
            showAlert('upload', 'Network error. Please try again.', 'error');
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
    el.textContent  = msg;
    el.className    = `alert-box ${type} show`;
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
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#39;');
}
