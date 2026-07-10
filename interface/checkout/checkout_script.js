const CHECKOUT_CONTROLLER = '/cardhaven/interface/checkout/controller_checkout.php';
const BASE_IMG_URL        = '/cardhaven';
const idPengguna          = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

const fmt = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n));

let selectedMethodId   = null;
let selectedMethodFee  = 0;
let currentOrderId     = null;
let cartSubtotal       = 0;
let cartTotalItems     = 0; 
let selectedFile       = null;
let hasStockIssue      = false; 

// Pagination metode pembayaran
let allMethods         = [];
let methodPage         = 1;
const METHODS_PER_PAGE = 4;

// Anti-spam flags
let isPlacingOrder    = false;
let isSubmittingProof = false;
let orderPlaced       = false;

// VARIABEL GLOBAL UNTUK DIRECT CHECKOUT EVENT/PREORDER
let directCheckoutData = null;

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
    // Mode resume (Fitur Lanjutkan Pembayaran Profile)
    const resumeId = parseInt(new URLSearchParams(window.location.search).get('resume'));
    if (resumeId > 0) {
        resumeOrderPayment(resumeId);
        return;
    }

    loadUserInfo();
    loadPaymentMethods();

    // CEK APAKAH INI CHECKOUT DARI EVENT ATAU KERANJANG
    const directDataStr = sessionStorage.getItem('direct_checkout_data');
    if (directDataStr) {
        directCheckoutData = JSON.parse(directDataStr);
        renderDirectCheckoutItems();
    } else {
        loadCartItems();
    }
});

function resumeOrderPayment(orderId) {
    fetchWithTimeout(`/cardhaven/interface/page-profile/controller/ProfileController.php?action=getOrderDetail&id_pengguna=${idPengguna}&id_penjualan=${orderId}`)
        .then(r => r.json())
        .then(res => {
            const o = res.data;
            if (res.status !== 'success' || !o) {
                alert(res.msg || 'Order not found.');
                window.location.replace('/CardHaven/profilepage');
                return;
            }
            if (parseInt(o.status_penjualan) !== 0) {
                alert('This order has already been paid or can no longer be paid.');
                window.location.replace('/CardHaven/profilepage');
                return;
            }

            currentOrderId = o.id_penjualan;
            orderPlaced    = true; 

            const detailParts = [];
            if (o.nama_metode) detailParts.push(escapeHtml(o.nama_metode));
            if (o.rek_tujuan)  detailParts.push(escapeHtml(o.rek_tujuan));
            if (o.atas_nama)   detailParts.push('a/n ' + escapeHtml(o.atas_nama));

            markStepDone(1);
            markStepActive(2);
            document.getElementById('step1-content').style.display = 'none';
            document.getElementById('step2-content').style.display = 'block';

            document.getElementById('step2-order-id').textContent             = '#' + o.id_penjualan;
            document.getElementById('step2-total').textContent                = fmt(o.total_harga);
            document.getElementById('payment-instruction-amount').textContent = fmt(o.total_harga);
            document.getElementById('payment-instruction-detail').innerHTML   = detailParts.join(' · ') || '-';

            window.scrollTo({ top: 0, behavior: 'smooth' });
        })
        .catch(err => {
            console.error(err);
            alert('Failed to load order info. Please try again.');
            window.location.replace('/CardHaven/profilepage');
        });
}

window.addEventListener('pageshow', (e) => {
    if (e.persisted && orderPlaced) {
        window.location.reload();
    }
});

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
        });
}

// ============================================================
// RENDER ITEMS (JALUR EVENT / PREORDER)
// ============================================================
function renderDirectCheckoutItems() {
    const loading = document.getElementById('checkout-items-loading');
    const list    = document.getElementById('checkout-item-list');
    loading.style.display = 'none';
    list.style.display    = 'flex';

    cartSubtotal   = 0;
    cartTotalItems = 0;
    hasStockIssue  = false; 

    directCheckoutData.items.forEach(item => {
        cartSubtotal   += parseFloat(item.subtotal_harga) || 0;
        cartTotalItems += parseInt(item.jumlah_barang) || 0;

        // FITUR HARGA CORET KHUSUS EVENT PROMO
        let hargaCoretHtml = '';
        if (directCheckoutData.checkout_type === 'promo' && directCheckoutData.persen_diskon > 0 && directCheckoutData.persen_diskon < 100) {
            let diskon = parseFloat(directCheckoutData.persen_diskon);
            let hargaDiskon = parseFloat(item.harga_produk);
            // Hitung mundur Harga Asli dari persentase diskon
            let hargaAsli = Math.round(hargaDiskon / (1 - (diskon / 100)));
            hargaCoretHtml = `<span style="text-decoration:line-through; color:#94a3b8; font-size:0.8rem; margin-right:6px;">${fmt(hargaAsli)}</span>`;
        }

        list.appendChild(renderCheckoutItem(item, false, null, hargaCoretHtml));
    });

    const eventLabel = directCheckoutData.checkout_type === 'promo' ? 'Promo Event' : 'Pre-Order';
    document.getElementById('summary-items-label').textContent =
        `Items (${directCheckoutData.items.length} product(s), ${cartTotalItems} pcs) - [${eventLabel}]`;

    renderSummaryItems(directCheckoutData.items, directCheckoutData.checkout_type === 'promo', directCheckoutData.persen_diskon);
    updateSummary();
    renderEventSpecialInfo(); 
    checkCanOrder();
}

function renderEventSpecialInfo() {
    if (!directCheckoutData) return;

    const summaryBox = document.getElementById('summary-items');
    let infoDiv = document.getElementById('checkout-event-info');

    if (!infoDiv) {
        infoDiv = document.createElement('div');
        infoDiv.id = 'checkout-event-info';
        infoDiv.style.cssText = 'margin-top: 15px; padding: 12px; background: #f8fafc; border: 1px dashed #94a3b8; border-radius: 8px;';
        summaryBox.parentNode.insertBefore(infoDiv, summaryBox.nextSibling);
    }

    let html = `<h4 style="margin:0 0 8px 0; color:#1a3a6b; font-size:0.9rem;">🎁 ${escapeHtml(directCheckoutData.nama_event)}</h4>`;

    if (directCheckoutData.persen_diskon && parseFloat(directCheckoutData.persen_diskon) > 0) {
        html += `<div style="display:flex; justify-content:space-between; font-size:0.85rem; color:#059669; font-weight:600; margin-bottom:4px;">
                    <span>Discount Applied:</span>
                    <span>${directCheckoutData.persen_diskon}% OFF</span>
                 </div>`;
    }

    if (directCheckoutData.checkout_type === 'preorder' && directCheckoutData.tanggal_sampai) {
        const dateObj = new Date(directCheckoutData.tanggal_sampai);
        const arrivalDate = isNaN(dateObj) ? '-' : new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(dateObj);
        
        html += `<div style="display:flex; justify-content:space-between; font-size:0.85rem; color:#ea580c; font-weight:600;">
                    <span>Est. Arrival:</span>
                    <span>${arrivalDate}</span>
                 </div>`;
    }

    infoDiv.innerHTML = html;
}

// ============================================================
// RENDER ITEMS (JALUR KERANJANG BIASA)
// ============================================================
function loadCartItems() {
    fetchWithTimeout(`${CHECKOUT_CONTROLLER}?action=get_checkout_data&idpengguna=${idPengguna}`)
        .then(r => r.json())
        .then(data => {
            const loading = document.getElementById('checkout-items-loading');
            const list    = document.getElementById('checkout-item-list');

            loading.style.display = 'none';
            list.style.display    = 'flex';

            if (!data.success || !data.items || data.items.length === 0) {
                list.innerHTML = `<p style="color:#888;font-size:0.88rem;">
                    No items selected. <a href="/cardhaven/interface/cart/">Return to cart</a>.
                </p>`;
                updateSummary(); checkCanOrder(); return;
            }

            cartSubtotal   = 0;
            cartTotalItems = 0;
            hasStockIssue  = false;

            data.items.forEach(item => {
                const qty  = parseInt(item.jumlah_barang) || 0;
                const stok = parseInt(item.stok);
                const outOfStock = !isNaN(stok) && qty > stok;
                if (outOfStock) hasStockIssue = true;

                cartSubtotal   += parseFloat(item.subtotal_harga) || 0;
                cartTotalItems += qty;
                list.appendChild(renderCheckoutItem(item, outOfStock, stok, ''));
            });

            document.getElementById('summary-items-label').textContent =
                `Items (${data.items.length} products, ${cartTotalItems} pcs)`;

            renderSummaryItems(data.items);
            if (hasStockIssue) showAlert('checkout', 'Some products do not have enough stock.', 'error');
            updateSummary(); checkCanOrder();
        });
}

function renderCheckoutItem(item, outOfStock = false, stok = null, hargaCoretHtml = '') {
    const div = document.createElement('div');
    div.className = 'checkout-item';
    const fotoSrc = item.foto ? `${BASE_IMG_URL}/assets/image/products/${item.foto}` : `${BASE_IMG_URL}/image-profile/defaultProduct.jpg`;
    const stockWarn = outOfStock ? `<div style="color:#dc2626; font-size:0.72rem; font-weight:700; margin-top:2px;">⚠ Only ${stok ?? 0} left in stock.</div>` : '';

    div.innerHTML = `
        <div class="checkout-item-img">
            <img src="${fotoSrc}" alt="${escapeHtml(item.nama_produk)}" onerror="this.src='${BASE_IMG_URL}/image-profile/no-image.png'" style="${outOfStock ? 'filter: grayscale(1) brightness(0.75);' : ''}">
        </div>
        <div class="checkout-item-info">
            <div class="checkout-item-name">${escapeHtml(item.nama_produk)}</div>
            <div class="checkout-item-meta">${hargaCoretHtml}${fmt(item.harga_produk)} × ${item.jumlah_barang}</div>
            ${stockWarn}
        </div>
        <div class="checkout-item-subtotal">${fmt(item.subtotal_harga)}</div>
    `;
    return div;
}

// ============================================================
// PAYMENT METHODS & SUMMARY
// ============================================================
function loadPaymentMethods() {
    fetchWithTimeout(`${CHECKOUT_CONTROLLER}?action=get_checkout_data&idpengguna=${idPengguna}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('payment-method-loading').style.display = 'none';
            document.getElementById('payment-method-list').style.display = 'flex';
            if (data.success) {
                allMethods = data.methods || [];
                renderPaymentMethods(); checkCanOrder();
            }
        });
}

function renderPaymentMethods() {
    const list = document.getElementById('payment-method-list');
    list.innerHTML = '';
    const start = (methodPage - 1) * METHODS_PER_PAGE;
    allMethods.slice(start, start + METHODS_PER_PAGE).forEach(m => {
        const div = document.createElement('div');
        div.className = 'payment-method-option';
        if (String(selectedMethodId) === String(m.id_metode)) div.classList.add('selected');

        const feeText = parseFloat(m.biaya_admin) > 0 ? `<span class="payment-method-fee">+${fmt(m.biaya_admin)}</span>` : `<span class="payment-method-fee free">No fee</span>`;

        div.innerHTML = `
            <input type="radio" name="payment_method" value="${m.id_metode}" ${String(selectedMethodId) === String(m.id_metode) ? 'checked' : ''} onchange="selectPaymentMethod(${m.id_metode}, ${m.biaya_admin}, this.closest('.payment-method-option'))">
            <div class="payment-method-info">
                <div class="payment-method-name">${escapeHtml(m.nama_metode)}</div>
                <div class="payment-method-detail">${escapeHtml(m.provider || '')} ${m.no_rekening ? '· ' + escapeHtml(m.no_rekening) : ''}</div>
            </div>
            ${feeText}
        `;
        list.appendChild(div);
    });
    renderPaymentPagination(Math.max(1, Math.ceil(allMethods.length / METHODS_PER_PAGE)));
}

function renderPaymentPagination(totalPages) {
    let bar = document.getElementById('payment-method-pagination');
    if (totalPages <= 1) { bar.innerHTML = ''; return; }
    bar.innerHTML = `
        <button type="button" class="pm-page-btn" ${methodPage <= 1 ? 'disabled' : ''} onclick="changeMethodPage(-1)">‹ Prev</button>
        <span style="font-size:0.8rem;color:#64748b;font-weight:600;">Page ${methodPage} of ${totalPages}</span>
        <button type="button" class="pm-page-btn" ${methodPage >= totalPages ? 'disabled' : ''} onclick="changeMethodPage(1)">Next ›</button>
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
    updateSummary(); checkCanOrder();
}

// Diubah agar menerima diskon dan merender Strikethrough pada Order Summary di kanan
function renderSummaryItems(items, isPromo = false, persenDiskon = 0) {
    document.getElementById('summary-items').innerHTML = (items || []).map(it => {
        let hargaCoretHtml = '';
        if (isPromo && persenDiskon > 0 && persenDiskon < 100) {
            let diskon = parseFloat(persenDiskon);
            let hargaDiskon = parseFloat(it.harga_produk);
            let hargaAsli = Math.round(hargaDiskon / (1 - (diskon / 100)));
            hargaCoretHtml = `<span style="text-decoration:line-through; color:#94a3b8; font-size:0.75rem; margin-right:4px;">${fmt(hargaAsli)}</span>`;
        }

        return `
        <div class="summary-line">
            <div>
                <div class="summary-line-name">${escapeHtml(it.nama_produk)}</div>
                <div class="summary-line-qty">${hargaCoretHtml}${fmt(it.harga_produk)} × ${it.jumlah_barang}</div>
            </div>
            <div class="summary-line-price">${fmt(it.subtotal_harga)}</div>
        </div>
        `;
    }).join('');
}

function updateSummary() {
    document.getElementById('summary-subtotal').textContent    = fmt(cartSubtotal);
    document.getElementById('summary-grand-total').textContent = fmt(cartSubtotal + selectedMethodFee);
    const feeRow = document.getElementById('summary-fee-row');
    if (selectedMethodFee > 0) {
        feeRow.style.display = 'flex';
        document.getElementById('summary-fee').textContent = '+' + fmt(selectedMethodFee);
    } else {
        feeRow.style.display = 'none';
    }
}

function checkCanOrder() {
    const alamat = (document.getElementById('field-alamat')?.value  || '').trim();
    const phone  = (document.getElementById('field-phone')?.value   || '').trim();
    const ready  = alamat.length > 0 && isValidPhone(phone) && selectedMethodId !== null && cartSubtotal > 0 && !hasStockIssue;
    const btn    = document.getElementById('btn-place-order');
    if (btn) btn.disabled = !ready || isPlacingOrder || orderPlaced;
}

function isValidPhone(phone) {
    const digits = (phone.match(/\d/g) || []).length;
    return /^\+?[0-9][0-9\s\-()]*$/.test(phone) && digits >= 8 && digits <= 15;
}

document.addEventListener('input', e => {
    if (['field-phone', 'field-alamat'].includes(e.target.id)) checkCanOrder();
});

// ============================================================
// PLACE ORDER
// ============================================================
function placeOrder() {
    if (isPlacingOrder || orderPlaced) return;
    const alamat = document.getElementById('field-alamat').value.trim();
    const phone  = document.getElementById('field-phone').value.trim();
    if (!alamat)          { showAlert('checkout', 'Please enter your shipping address.', 'error'); return; }
    if (!isValidPhone(phone)) { showAlert('checkout', 'Invalid phone number.', 'error'); return; }
    if (!selectedMethodId){ showAlert('checkout', 'Please select a payment method.', 'error'); return; }
    if (hasStockIssue)    { showAlert('checkout', 'Some products do not have enough stock.', 'error'); return; }

    isPlacingOrder = true;
    const btn = document.getElementById('btn-place-order');
    btn.disabled    = true;
    btn.textContent = 'Processing...';

    if (directCheckoutData) {
        const payload = {
            id_pengguna: idPengguna,
            id_event: directCheckoutData.id_event,
            id_metode: selectedMethodId,
            tanggal_sampai: directCheckoutData.tanggal_sampai,
            alamat: alamat,
            items: directCheckoutData.items.map(i => ({
                id_produk: i.id_produk,
                jumlah: i.jumlah_barang,
                harga_produk: i.harga_produk
            }))
        };

        const endpoint = directCheckoutData.checkout_type === 'promo' 
            ? '/cardhaven/interface/event-transaction/controllerPromoTransaction.php?action=submit_order'
            : '/cardhaven/interface/preorder-transaction/controllerPreorderTransaction.php?action=submit_order';

        fetchWithTimeout(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }, 15000)
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                currentOrderId = json.id_penjualan;
                orderPlaced    = true;
                sessionStorage.removeItem('direct_checkout_data'); 
                goToStep2(json);
            } else {
                throw new Error(json.message || 'Failed to place order ma.');
            }
        })
        .catch(err => {
            showAlert('checkout', err.message || 'Network error.', 'error');
            isPlacingOrder = false; btn.disabled = false; btn.textContent = 'Place Order →';
        });
    } 
    else {
        const fd = new FormData();
        fd.append('action',       'place_order');
        fd.append('idpengguna',   idPengguna); 
        fd.append('alamat',       alamat);
        fd.append('id_metode',    selectedMethodId);
        fd.append('total_harga',  cartSubtotal + selectedMethodFee); 
        fd.append('total_barang', cartTotalItems); 

        fetchWithTimeout(CHECKOUT_CONTROLLER, { method: 'POST', body: fd }, 15000)
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    currentOrderId = json.id_penjualan;
                    orderPlaced    = true; 
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
                const msg = err.name === 'AbortError' ? 'Connection failed. Please try again.' : 'Network error. Please try again.';
                showAlert('checkout', msg, 'error');
                isPlacingOrder  = false;
                btn.disabled    = false;
                btn.textContent = 'Place Order →';
                checkCanOrder();
            });
    }
}

// ============================================================
// UPLOAD BUKTI PEMBAYARAN (STEP 2 & 3)
// ============================================================
function goToStep2(orderData) {
    markStepDone(1); markStepActive(2);
    document.getElementById('step1-content').style.display = 'none';
    document.getElementById('step2-content').style.display = 'block';

    document.getElementById('step2-order-id').textContent = '#' + currentOrderId;
    const totalWithFee = cartSubtotal + selectedMethodFee;
    document.getElementById('step2-total').textContent = fmt(totalWithFee);
    
    const activeMethod = allMethods.find(m => String(m.id_metode) === String(selectedMethodId));
    document.getElementById('payment-instruction-amount').textContent = fmt(totalWithFee);
    document.getElementById('payment-instruction-detail').innerHTML = orderData.payment_detail || `Please transfer to <b>${activeMethod.provider}</b><br>No. Rekening: <b>${activeMethod.no_rekening}</b>`;

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function handleFileSelect(input) { if (input.files && input.files[0]) setUploadFile(input.files[0]); }
function handleFileDrop(event) {
    event.preventDefault();
    document.getElementById('upload-drop-zone').style.borderColor = '#dde4f8';
    const file = event.dataTransfer.files[0];
    if (file) setUploadFile(file);
}

function setUploadFile(file) {
    const maxSize = 5 * 1024 * 1024;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    if (file.size > maxSize) { showAlert('upload', 'File is too large. Maximum 5MB.', 'error'); return; }
    if (!allowedTypes.includes(file.type)) { showAlert('upload', 'Invalid file type. Upload JPG, PNG, WEBP, or PDF.', 'error'); return; }

    selectedFile = file;
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('file-preview-img').src = e.target.result;
            document.getElementById('file-preview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('file-preview-img').src = `${BASE_IMG_URL}/assets/image/pdf-icon.svg`;
        document.getElementById('file-preview').style.display = 'block';
    }
    document.getElementById('btn-submit-payment').disabled = false;
    hideAlert('upload');
}

function clearFile() {
    selectedFile = null;
    document.getElementById('bukti-file-input').value = '';
    document.getElementById('file-preview').style.display = 'none';
    document.getElementById('btn-submit-payment').disabled = true;
    isSubmittingProof = false;
}

function submitPayment() {
    if (isSubmittingProof) return;
    if (!selectedFile) { showAlert('upload', 'Please upload your payment proof first.', 'error'); return; }

    isSubmittingProof = true;
    const btn = document.getElementById('btn-submit-payment');
    btn.disabled = true; btn.textContent = 'Uploading...';
    
    const fd = new FormData();
    fd.append('action', 'upload_bukti');
    fd.append('idpengguna', idPengguna); 
    fd.append('id_penjualan', currentOrderId);
    fd.append('bukti_pembayaran', selectedFile);

    fetchWithTimeout(CHECKOUT_CONTROLLER, { method: 'POST', body: fd }, 20000)
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                history.replaceState(null, '', window.location.pathname);
                goToStep3();
            }
            else throw new Error(json.message || 'Upload failed. Please try again.');
        })
        .catch(err => {
            showAlert('upload', err.message || 'Network error.', 'error');
            isSubmittingProof = false; btn.disabled = false; btn.textContent = 'Submit Payment Proof →';
        });
}

function goToStep3() {
    markStepDone(2); markStepActive(3);
    document.getElementById('step2-content').style.display = 'none';
    document.getElementById('step3-content').style.display = 'block';
    document.getElementById('confirm-order-id').textContent = '#' + currentOrderId;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function markStepDone(n) {
    const el = document.getElementById(`step${n}-indicator`);
    if (el) { el.classList.remove('active'); el.classList.add('done'); el.querySelector('.step-circle').innerHTML = '✓'; }
}
function markStepActive(n) {
    const el = document.getElementById(`step${n}-indicator`);
    if (el) el.classList.add('active');
}
function showAlert(context, msg, type) {
    const el = document.getElementById(`alert-${context}`);
    if (el) { el.textContent = msg; el.className = `alert-box ${type} show`; }
}
function hideAlert(context) {
    const el = document.getElementById(`alert-${context}`);
    if (el) el.className = 'alert-box';
}
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}