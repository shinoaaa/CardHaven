/**
 * CARDHAVEN CART SCRIPT
 * Konsisten dengan gaya alert & konfirmasi sistem Game
 */

const CART_CONTROLLER = '/cardhaven/interface/cart/controller_keranjang.php';
const BASE_URL = '/cardhaven';

// --- LOGIKA IDENTITAS ---
var getUserId = () => CardHavenAuth.id() || null;

document.addEventListener('DOMContentLoaded', loadCart);
 
// ---- Load semua item ----
function loadCart() {
    const userId = getUserId();
    
    // Jika tidak ada user, hentikan proses (keamanan tambahan)
    if (!userId || userId === "0") return;

    fetch(`${CART_CONTROLLER}?action=get_items`)
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
        })
        .then(data => {
            renderCart(data);
        })
        .catch(err => {
            console.error('Failed to load cart:', err);
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
        renderSummaryBreakdown([]);
        return;
    }
 
    table.style.display    = 'table';
    toolbar.style.display  = 'flex';
    emptyMsg.style.display = 'none';
 
    let totalHarga    = 0;
    let selectedCount = 0;
    const selectedItems = [];

    data.forEach(item => {
        const tr = renderRow(item);
        tbody.appendChild(tr);

        if (parseInt(item.is_selected) === 1) {
            totalHarga    += parseFloat(item.subtotal_harga) || 0;
            selectedCount += 1;
            selectedItems.push(item);
        }
    });

    renderSummaryBreakdown(selectedItems);
 
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
    const fotoSrc = item.foto ? `${BASE_URL}/assets/image/products/${item.foto}` : `${BASE_URL}/assets/image/image-profile/defaultProduct.jpg`;
 
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
                         onerror="this.src='${BASE_URL}/assets/image/image-profile/no-image.png'">
                </div>
                <div class="cart-product-details">
                    <span class="cart-product-title">${escapeHtml(item.nama_produk)}</span>
                    <span style="font-size: 0.75rem; color: var(--primary-color, #173C99); font-weight: 600; display: block; margin-bottom: 3px;">Stock: ${item.stok}</span>
                    <span class="cart-product-meta">Official Card</span>
                </div>
            </div>
        </td>
        <td class="cart-price">${formatIDR(item.harga_produk)}</td>
        <td style="text-align:center;">
            <div class="cart-qty-control">
                <button class="cart-qty-btn"
                        onclick="updateQty(${item.id_detail_keranjang}, -1)"
                        title="Subtract"
                        ${item.jumlah_barang <= 1 ? 'disabled style="opacity:0.3; cursor:default;"' : ''}>−</button>
                <input type="number"
                       class="cart-qty-val"
                       min="1"
                       max="${item.stok}"
                       value="${item.jumlah_barang}"
                       data-qty="${item.jumlah_barang}"
                       onchange="handleCartQtyTyped(${item.id_detail_keranjang}, this, ${item.stok})">
                <button class="cart-qty-btn"
                        onclick="updateQty(${item.id_detail_keranjang}, 1)"
                        title="Add"
                        ${item.jumlah_barang >= item.stok ? 'disabled style="opacity:0.3; cursor:default;"' : ''}>+</button>
            </div>
        </td>
        <td class="cart-total">${formatIDR(item.subtotal_harga)}</td>
        <td>
            <button class="cart-btn-remove"
                    onclick="deleteItem(${item.id_detail_keranjang})"
                    title="Delete form cart">✕</button>
        </td>
    `;
    return tr;
}
 
// Rincian total: tampilkan tiap item terpilih beserta subtotalnya
function renderSummaryBreakdown(items) {
    const box = document.getElementById('summary-items');
    if (!box) return;
    const fmt = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n));

    if (!items || items.length === 0) {
        box.innerHTML = '<div class="summary-items-empty">No items selected.</div>';
        return;
    }

    box.innerHTML = items.map(it => `
        <div class="summary-item">
            <div>
                <div class="summary-item-name">${escapeHtml(it.nama_produk)}</div>
                <div class="summary-item-qty">${fmt(it.harga_produk)} × ${it.jumlah_barang}</div>
            </div>
            <div class="summary-item-price">${fmt(it.subtotal_harga)}</div>
        </div>
    `).join('');
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
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('btn-checkout-main');
    if (btn) {
        btn.addEventListener('click', () => {
            if (!btn.disabled) {
                window.location.href = '/CardHaven/checkout';
            }
        });
    }
});
function setCheckoutState(enabled) {
    const btn = document.getElementById('btn-checkout-main');
    if (btn) btn.disabled = !enabled;
}
 
// ---- Aksi POST (id_pengguna diambil server dari session) ----
function updateQty(id, change) {
    const fd = new FormData();
    fd.append('action', 'update_qty');
    fd.append('id', id);
    fd.append('change', change);
 
    fetch(CART_CONTROLLER, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(json => { 
            if (json.success) {
                loadCart(); 
            } else {
                cardhavenToast('error', json.message || 'Cannot change quantity');
                loadCart(); // reset value to what's in DB
            }
        })
        .catch(err => console.error(err));
}

// ---- Dipanggil saat user mengetik langsung jumlah quantity lalu keluar dari kolom (blur / Enter) ----
function handleCartQtyTyped(id, inputEl, stok) {
    const oldQty = parseInt(inputEl.dataset.qty) || 1;
    let newQty   = parseInt(inputEl.value);

    // Kalau kosong atau tidak valid, kembalikan ke jumlah sebelumnya
    if (isNaN(newQty) || newQty < 1) {
        newQty = 1;
    }
    if (newQty > stok) {
        newQty = stok;
    }

    if (newQty === oldQty) {
        inputEl.value = oldQty; // rapikan tampilan, tidak perlu request ke server
        return;
    }

    // Backend cuma terima perubahan (delta), jadi dihitung selisihnya dulu
    const change = newQty - oldQty;
    updateQty(id, change);
}
 
function toggleSelect(id, checked) {
    const fd = new FormData();
    fd.append('action', 'toggle_select');
    fd.append('id', id);
    fd.append('status', checked ? 1 : 0);
 
    fetch(CART_CONTROLLER, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(() => loadCart())
        .catch(err => console.error(err));
}
 
function toggleSelectAll(checked) {
    const fd = new FormData();
    fd.append('action', 'select_all');
    fd.append('status', checked ? 1 : 0);
 
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
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 40px; color: #dc2626; font-weight:bold;">Unable to load the basket. Please refresh the page.</td></tr>`;
    }
}
 
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}