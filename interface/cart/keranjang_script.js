/**
 * CARDHAVEN CART SCRIPT
 * Konsisten dengan gaya alert & konfirmasi sistem Game
 */

const CART_CONTROLLER = '/cardhaven/interface/cart/controller_keranjang.php';
const BASE_URL = '/cardhaven';

// --- LOGIKA IDENTITAS ---
var getUserId = () => localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

document.addEventListener('DOMContentLoaded', loadCart);
 
// ---- Load semua item ----
function loadCart() {
    const userId = getUserId();
    
    // Jika tidak ada user, hentikan proses (keamanan tambahan)
    if (!userId || userId === "0") return;

    fetch(`${CART_CONTROLLER}?action=get_items&id_pengguna_js=${userId}`)
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
        })
        .then(data => {
            renderCart(data);
        })
        .catch(err => {
            console.error('Gagal memuat keranjang:', err);
            showError();
            cardhavenAlert('error', 'Connection Error', 'Failed to load cart items from server.');
        });
}
 
// ---- Render seluruh cart ----
function renderCart(data) {
    const tbody       = document.getElementById('cart-table-body');
    const table       = document.getElementById('cart-main-table');
    const emptyMsg    = document.getElementById('cart-empty-msg');
    const toolbar     = document.getElementById('cart-toolbar');
    const loadingState = document.getElementById('cart-loading-state');
    const itemCount   = document.getElementById('cart-item-count');
 
    if (loadingState) loadingState.style.display = 'none';
    if (!tbody) return;

    tbody.innerHTML = '';
 
    if (!data || data.length === 0) {
        table.style.display    = 'none';
        toolbar.style.display  = 'none';
        emptyMsg.style.display = 'block';
        setCheckoutState(false);
        updateSummary(0, 0, 0);
        return;
    }
 
    table.style.display    = 'table';
    toolbar.style.display  = 'flex';
    emptyMsg.style.display = 'none';
 
    let totalHarga    = 0;
    let selectedCount = 0;
 
    data.forEach(item => {
        const tr = renderRow(item);
        tbody.appendChild(tr);
 
        if (parseInt(item.is_selected) === 1) {
            totalHarga    += parseFloat(item.subtotal_harga) || 0;
            selectedCount += 1;
        }
    });
 
    itemCount.textContent = `${data.length} item${data.length > 1 ? 's' : ''}`;
 
    const allChecked = data.length > 0 && data.every(item => parseInt(item.is_selected) === 1);
    const selectAllCb = document.getElementById('select-all-checkbox');
    if (selectAllCb) {
        selectAllCb.checked       = allChecked;
        selectAllCb.indeterminate = !allChecked && selectedCount > 0;
    }
 
    updateSummary(totalHarga, selectedCount, data.length);
    setCheckoutState(selectedCount > 0);
}
 
// ---- Render satu baris ----
function renderRow(item) {
    const tr = document.createElement('tr');
    const formatIDR = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n));
    const fotoSrc = item.foto ? `${BASE_URL}/${item.foto}` : `${BASE_URL}/image-profile/defaultProduct.jpg`;
 
    tr.setAttribute('data-id', item.id_detail_keranjang);
 
    tr.innerHTML = `
        <td>
            <input type="checkbox"
                   ${parseInt(item.is_selected) === 1 ? 'checked' : ''}
                   onchange="toggleSelect(${item.id_detail_keranjang}, this.checked)">
        </td>
        <td>
            <div class="cart-product-info">
                <div class="cart-img-wrap">
                    <img src="${fotoSrc}"
                         alt="${escapeHtml(item.nama_produk)}"
                         onerror="this.src='${BASE_URL}/image-profile/no-image.png'">
                </div>
                <div class="cart-product-details">
                    <span class="cart-product-title">${escapeHtml(item.nama_produk)}</span>
                    <span class="cart-product-meta">Official Card</span>
                </div>
            </div>
        </td>
        <td class="cart-price">${formatIDR(item.harga_produk)}</td>
        <td style="text-align:center;">
            <div class="cart-qty-control">
                <button class="cart-qty-btn"
                        onclick="updateQty(${item.id_detail_keranjang}, -1)"
                        title="Kurangi">−</button>
                <span class="cart-qty-val">${item.jumlah_barang}</span>
                <button class="cart-qty-btn"
                        onclick="updateQty(${item.id_detail_keranjang}, 1)"
                        title="Tambah">+</button>
            </div>
        </td>
        <td class="cart-total">${formatIDR(item.subtotal_harga)}</td>
        <td>
            <button class="cart-btn-remove"
                    onclick="deleteItem(${item.id_detail_keranjang})"
                    title="Hapus dari keranjang">✕</button>
        </td>
    `;
    return tr;
}
 
function updateSummary(total, selectedCount, totalItems) {
    const fmt = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n));
    document.getElementById('subtotal-display').textContent    = fmt(total);
    document.getElementById('grand-total-display').textContent = fmt(total);
    const infoEl = document.getElementById('selected-info');
    if (infoEl) {
        infoEl.textContent = selectedCount > 0
            ? `${selectedCount} of ${totalItems} item${totalItems > 1 ? 's' : ''} selected`
            : 'No items selected';
    }
}
 
function setCheckoutState(enabled) {
    const btn = document.getElementById('btn-checkout-main');
    if (!btn) return;
    btn.disabled = !enabled;
}
 
// ---- Aksi POST dengan id_pengguna_js ----
function updateQty(id, change) {
    const fd = new FormData();
    fd.append('action', 'update_qty');
    fd.append('id', id);
    fd.append('change', change);
    fd.append('id_pengguna_js', getUserId());
 
    fetch(CART_CONTROLLER, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(json => { if (json.success) loadCart(); })
        .catch(err => console.error(err));
}
 
function toggleSelect(id, checked) {
    const fd = new FormData();
    fd.append('action', 'toggle_select');
    fd.append('id', id);
    fd.append('status', checked ? 1 : 0);
    fd.append('id_pengguna_js', getUserId());
 
    fetch(CART_CONTROLLER, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(() => loadCart())
        .catch(err => console.error(err));
}
 
function toggleSelectAll(checked) {
    const fd = new FormData();
    fd.append('action', 'select_all');
    fd.append('status', checked ? 1 : 0);
    fd.append('id_pengguna_js', getUserId());
 
    fetch(CART_CONTROLLER, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(() => loadCart())
        .catch(err => console.error(err));
}
 
// ---- Hapus Item dengan cardhavenConfirm ----
function deleteItem(id) {
    cardhavenConfirm(
        "Remove Product?", 
        "Are you sure you want to remove this item from your cart?", 
        "Yes, Remove", 
        () => {
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', id);
            fd.append('id_pengguna_js', getUserId());
         
            fetch(CART_CONTROLLER, { method: 'POST', body: fd })
                .then(res => res.json())
                .then(json => {
                    if (json.success) {
                        const row = document.querySelector(`tr[data-id="${id}"]`);
                        if (row) {
                            row.style.transition = 'opacity 0.25s, transform 0.25s';
                            row.style.opacity    = '0';
                            row.style.transform  = 'translateX(20px)';
                            setTimeout(loadCart, 260);
                        } else {
                            loadCart();
                        }
                    } else {
                        cardhavenAlert('error', 'Failed', 'Could not remove item.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    cardhavenAlert('error', 'Error', 'A connection error occurred.');
                });
        }
    );
}
 
function showError() {
    const tbody = document.getElementById('cart-table-body');
    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 40px; color: #dc2626; font-weight:bold;">Gagal memuat keranjang. Silahkan refresh halaman.</td></tr>`;
    }
}
 
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}