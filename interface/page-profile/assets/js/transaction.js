// NOTE: tab "Buy Back" ditangani oleh buyback.js (punya detail modal seperti
// buyback_customer_script.js). File ini fokus ke Buy Product / orders saja.
const PROFILE_CONTROLLER = '/cardhaven/interface/page-profile/controller/ProfileController.php';
const profileUserId = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

const ORDER_STATUS = {
    0: { label: 'Pending Payment', bg: '#fef9c3', color: '#ca8a04' },
    1: { label: 'Paid',            bg: '#dcfce7', color: '#15803d' },
    2: { label: 'Waiting Stock',   bg: '#e0f2fe', color: '#0369a1' },
    3: { label: 'Processing',      bg: '#ede9fe', color: '#7c3aed' },
    4: { label: 'Shipped',         bg: '#dbeafe', color: '#1d4ed8' },
    5: { label: 'Delivered',       bg: '#d1fae5', color: '#065f46' },
    6: { label: 'Completed',       bg: '#d1fae5', color: '#14532d' },
    7: { label: 'Returned',        bg: '#fee2e2', color: '#b91c1c' },
    8: { label: 'Cancelled',       bg: '#f3f4f6', color: '#6b7280' },
};

const fmtRp = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n || 0));
const escHtml = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

// ── Buy Product state ────────────────────────────────────────────────
let allOrders       = [];
let currentTab      = 'buyproduct';
let orderPage       = 1;
const ORDERS_PER_PAGE = 5;
let orderSearch     = '';
let orderStatus     = '';
let orderSortField  = 'date';   // 'date' | 'price' | 'items'
let orderSortDir    = 'desc';   // 'asc' | 'desc'

document.addEventListener('DOMContentLoaded', () => {
    switchTab('buyproduct');
    loadOrders();
    // Buy Back di-load oleh buyback.js (loadRiwayat) saat DOMContentLoaded.
});

function switchTab(tabName) {
    currentTab = tabName;

    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');

    const targetBtn = document.querySelector(`.tab-btn[onclick="switchTab('${tabName}')"]`);
    if (targetBtn) targetBtn.classList.add('active');
    const targetContent = document.getElementById(`tab-${tabName}`);
    if (targetContent) targetContent.style.display = 'block';

    // Toolbar & pagination hanya relevan untuk Buy Product
    const toolbar = document.getElementById('bp-toolbar');
    const pag     = document.getElementById('bp-pagination');
    const showTools = tabName === 'buyproduct';
    if (toolbar) toolbar.style.display = showTools ? 'flex' : 'none';
    if (pag)     pag.style.display = showTools ? '' : 'none';

    if (tabName === 'buyproduct') renderOrders();
    if (tabName === 'buyback' && typeof loadRiwayat === 'function') loadRiwayat();
}

// ── Buy Product: load + filter + sort + paginate ─────────────────────
function loadOrders() {
    const tbody = document.getElementById('buyproduct-body');
    if (!tbody) return;
    if (!profileUserId) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;">Please login to see your orders.</td></tr>`;
        return;
    }

    fetch(`${PROFILE_CONTROLLER}?action=getOrders&id_pengguna=${profileUserId}`)
        .then(res => res.json())
        .then(res => {
            allOrders = (res && res.data) ? res.data : [];
            orderPage = 1;
            renderOrders();
        })
        .catch(err => {
            console.error('Failed to load orders:', err);
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#dc2626;">Failed to load orders.</td></tr>`;
        });
}

function getFilteredOrders() {
    let rows = allOrders.slice();

    if (orderStatus !== '') {
        rows = rows.filter(r => String(r.status_penjualan) === String(orderStatus));
    }
    if (orderSearch.trim() !== '') {
        const q = orderSearch.trim().toLowerCase();
        rows = rows.filter(r =>
            String(r.id_penjualan).includes(q) ||
            (r.nama_metode || '').toLowerCase().includes(q) ||
            (r.alamat || '').toLowerCase().includes(q));
    }
    // Sort berdasarkan field terpilih (tanggal/harga/jumlah item) + arah asc/desc.
    const dir = orderSortDir === 'asc' ? 1 : -1;
    rows.sort((a, b) => {
        let cmp;
        if (orderSortField === 'price')      cmp = (a.total_harga || 0) - (b.total_harga || 0);
        else if (orderSortField === 'items') cmp = (a.total_barang || 0) - (b.total_barang || 0);
        else cmp = new Date(a.tanggal_penjualan) - new Date(b.tanggal_penjualan);
        return cmp * dir;
    });
    return rows;
}

function renderOrders() {
    const tbody = document.getElementById('buyproduct-body');
    if (!tbody) return;

    const rows = getFilteredOrders();

    if (rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No orders found.</td></tr>`;
        renderOrderPagination(0);
        return;
    }

    const totalPages = Math.ceil(rows.length / ORDERS_PER_PAGE);
    if (orderPage > totalPages) orderPage = totalPages;
    const start = (orderPage - 1) * ORDERS_PER_PAGE;
    const pageRows = rows.slice(start, start + ORDERS_PER_PAGE);

    tbody.innerHTML = pageRows.map((row, i) => {
        const st = ORDER_STATUS[parseInt(row.status_penjualan)] || { label: 'Unknown', bg: '#f3f4f6', color: '#555' };
        let tgl = '-';
        if (row.tanggal_penjualan) {
            const [y, m, d] = row.tanggal_penjualan.substring(0, 10).split('-');
            tgl = `${d}-${m}-${y}`;
        }
        return `
            <tr>
                <td>${start + i + 1}</td>
                <td>${escHtml(row.nama_metode || '-')}</td>
                <td>${tgl}</td>
                <td style="text-align:right; padding-right: 5px">${row.total_barang ?? '-'}</td>
                <td style="text-align:right;">${fmtRp(row.total_harga)}</td>
                <td><span class="status-pill" style="background:${st.bg};color:${st.color};">${st.label}</span></td>
                <td><button class="action-dots-btn" title="View detail" onclick="openOrderDetail(${row.id_penjualan})">•••</button></td>
            </tr>`;
    }).join('');

    renderOrderPagination(totalPages);
}

function renderOrderPagination(totalPages) {
    const box = document.getElementById('bp-pagination');
    if (!box) return;
    if (totalPages <= 1) { box.innerHTML = ''; return; }

    let html = `<button class="page-arrow" ${orderPage === 1 ? 'disabled' : ''} onclick="gotoOrderPage(${orderPage - 1})">‹</button>`;
    for (let i = 1; i <= totalPages; i++) {
        // compact: first, last, current ±1
        if (i === 1 || i === totalPages || Math.abs(i - orderPage) <= 1) {
            html += `<button class="page-num ${i === orderPage ? 'active' : ''}" onclick="gotoOrderPage(${i})">${i}</button>`;
        } else if (i === orderPage - 2 || i === orderPage + 2) {
            html += `<span class="page-dots">...</span>`;
        }
    }
    html += `<button class="page-arrow" ${orderPage === totalPages ? 'disabled' : ''} onclick="gotoOrderPage(${orderPage + 1})">›</button>`;
    box.innerHTML = html;
}

function gotoOrderPage(p) { orderPage = p; renderOrders(); }

function onOrderFilterChange() {
    orderSearch    = document.getElementById('bp-search')?.value || '';
    orderStatus    = document.getElementById('bp-status')?.value || '';
    orderSortField = document.getElementById('bp-sortby')?.value || 'date';
    orderPage = 1;
    renderOrders();
}

function toggleOrderDateSort() {
    orderSortDir = orderSortDir === 'desc' ? 'asc' : 'desc';
    const icon = document.getElementById('bp-sort-icon');
    if (icon) icon.textContent = orderSortDir === 'desc' ? '↓' : '↑';
    orderPage = 1;
    renderOrders();
}

// ── Order detail modal ───────────────────────────────────────────────
function openOrderDetail(idPenjualan) {
    const overlay = document.getElementById('orderDetailOverlay');
    const content = document.getElementById('orderDetailContent');
    overlay.classList.add('show');
    content.innerHTML = `<div style="text-align:center;padding:2rem;color:#888;">Loading...</div>`;

    fetch(`${PROFILE_CONTROLLER}?action=getOrderDetail&id_pengguna=${profileUserId}&id_penjualan=${idPenjualan}`)
        .then(res => res.json())
        .then(res => {
            if (res.status !== 'success') {
                content.innerHTML = `<p style="color:#dc2626;text-align:center;padding:1.5rem;">${escHtml(res.msg || 'Failed to load order.')}</p>`;
                return;
            }
            const o = res.data;
            const st = ORDER_STATUS[parseInt(o.status_penjualan)] || { label: 'Unknown', bg: '#f3f4f6', color: '#555' };
            const itemsHtml = (o.items || []).map(it => `
                <div class="od-item-row">
                    <img src="/cardhaven/${it.foto || 'image-profile/defaultProduct.jpg'}" onerror="this.src='/cardhaven/image-profile/defaultProduct.jpg'">
                    <div style="flex:1;">
                        <div style="font-weight:600;font-size:.9rem;">${escHtml(it.nama_produk || '-')}</div>
                        <div style="font-size:.78rem;color:#888;">${fmtRp(it.harga_produk)} × ${it.jumlah_barang}</div>
                    </div>
                    <div style="font-weight:700;color:var(--primary-color,#1a3a6b);">${fmtRp(it.subtotal_harga)}</div>
                </div>`).join('');

            content.innerHTML = `
                <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #eee;padding-bottom:.85rem;margin-bottom:1rem;">
                    <div>
                        <div style="font-size:1.15rem;font-weight:800;color:var(--primary-color,#1a3a6b);">Order #${o.id_penjualan}</div>
                        <div style="font-size:.78rem;color:#888;margin-top:.2rem;">${escHtml((o.tanggal_penjualan || '').replace('T', ' '))}</div>
                    </div>
                    <span class="status-pill" style="background:${st.bg};color:${st.color};">${st.label}</span>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem;font-size:.82rem;margin-bottom:1rem;">
                    <div><span style="color:#888;">Payment</span><br><b>${escHtml(o.nama_metode || '-')}</b></div>
                    <div><span style="color:#888;">Tracking No.</span><br><b>${escHtml(o.no_resi || '-')}</b></div>
                    <div style="grid-column:1/-1;"><span style="color:#888;">Address</span><br><b>${escHtml(o.alamat || '-')}</b></div>
                </div>
                <div style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.8px;color:#888;margin-bottom:.4rem;">Items</div>
                ${itemsHtml || '<div style="color:#888;font-size:.85rem;">No items.</div>'}
                <div style="display:flex;justify-content:space-between;margin-top:1rem;padding-top:.75rem;border-top:1px solid #eee;">
                    <span style="font-weight:700;">Total (${o.total_barang || 0} pcs)</span>
                    <span style="font-weight:800;color:var(--primary-color,#1a3a6b);">${fmtRp(o.total_harga)}</span>
                </div>`;
        })
        .catch(err => {
            console.error('Failed to load order detail:', err);
            content.innerHTML = `<p style="color:#dc2626;text-align:center;padding:1.5rem;">Failed to load order detail.</p>`;
        });
}

function closeOrderDetail(e) {
    if (e && e.target !== e.currentTarget) return;
    document.getElementById('orderDetailOverlay').classList.remove('show');
}

// ── Buy Back: lihat buyback.js (loadRiwayat + openDetailModal) ────────
