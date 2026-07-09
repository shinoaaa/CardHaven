const API       = '/cardhaven/interface/purchase/controller_restok.php';
const ACTOR_ID  = parseInt(sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna') || 0);
const USER_ROLE = parseInt(sessionStorage.getItem('role') || localStorage.getItem('role') || 0);
const RESTOK_PER_PAGE = 7;
let currentPage = 1;
let searchTimer = null;
let allRestok = [];        // semua data (di-fetch sekali)
let filteredRestok = [];   // hasil filter + sort
let restokSortBy = 'DATE'; // DATE | PRICE | QTY
let restokSortOrder = 'DESC';

// ─── STATUS HELPER ────────────────────────────────────────────────────────────
function statusLabel(s) {
    const map = {
        0: '<span class="badge-pending">Pending</span>',
        1: '<span class="badge-approved">Approved</span>',
        2: '<span class="badge-rejected">Rejected</span>',
        3: '<span class="badge-received">Received</span>',
        4: '<span class="badge-paid">Paid</span>',
    };
    return map[parseInt(s)] ?? '-';
}

function formatRupiah(n) {
    return 'Rp' + Number(n).toLocaleString('id-ID');
}

// ─── LOAD TABLE (fetch semua data sekali, filter/sort/paginate di client) ──────
function loadRestok(page = 1) {
    const params = new URLSearchParams({
        action: 'getList',
        page: 1,
        search: '',
        status: '',
        limit: 100000,
        actor_id: ACTOR_ID,
    });

    const tbody = document.getElementById('restokTableBody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="8" style="color:#999;">Loading...</td></tr>';

    fetch(`${API}?${params}`)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') {
                if (tbody) tbody.innerHTML = `<tr><td colspan="8" style="color:#E74C3C;">${res.message}</td></tr>`;
                return;
            }
            allRestok = res.data.rows || [];
            applyRestokFilter(page);
        })
        .catch(() => {
            if (tbody) tbody.innerHTML = '<tr><td colspan="8" style="color:#E74C3C;">Failed to load data.</td></tr>';
        });
}

// Parse tanggal 'd-m-Y' menjadi timestamp untuk sorting.
function parseRestokDate(str) {
    if (!str) return 0;
    const [d, m, y] = str.toString().split('-');
    return new Date(`${y}-${m}-${d}`).getTime() || 0;
}

function changeRestokSort() {
    const el = document.getElementById('restokSort');
    restokSortBy = el ? el.value : 'DATE';
    applyRestokFilter(1);
}

function toggleRestokSortOrder() {
    restokSortOrder = restokSortOrder === 'DESC' ? 'ASC' : 'DESC';
    const btn = document.getElementById('btnRestokSortOrder');
    if (btn) btn.innerHTML = restokSortOrder === 'DESC' ? 'Descending ↓' : 'Ascending ↑';
    applyRestokFilter(1);
}

function applyRestokFilter(page = 1) {
    const searchEl = document.getElementById('searchInput');
    const statusEl = document.getElementById('statusFilter');
    const search = searchEl ? searchEl.value.trim().toLowerCase() : '';
    const status = statusEl ? statusEl.value : '';

    filteredRestok = allRestok.filter(row => {
        if (status !== '' && String(row.status_restok) !== String(status)) return false;
        if (search !== '') {
            const supplier = (row.nama_suplier || '').toString().toLowerCase();
            const id = String(row.id_restok || '');
            const by = (row.created_by_name || '').toString().toLowerCase();
            const harga = String(row.total_harga || '');
            const tgl = (row.tanggal_restok || '').toString().toLowerCase();
            const match = supplier.includes(search) || id.includes(search) ||
                          ('#' + id).includes(search) || by.includes(search) ||
                          harga.includes(search) || tgl.includes(search);
            if (!match) return false;
        }
        return true;
    });

    filteredRestok.sort((a, b) => {
        let x, y;
        if (restokSortBy === 'PRICE') { x = parseFloat(a.total_harga || 0); y = parseFloat(b.total_harga || 0); }
        else if (restokSortBy === 'QTY') { x = parseInt(a.total_barang || 0); y = parseInt(b.total_barang || 0); }
        else { x = parseRestokDate(a.tanggal_restok); y = parseRestokDate(b.tanggal_restok); }
        if (x === y) return 0;
        return restokSortOrder === 'DESC' ? (x < y ? 1 : -1) : (x < y ? -1 : 1);
    });

    const totalPages = Math.max(1, Math.ceil(filteredRestok.length / RESTOK_PER_PAGE));
    currentPage = Math.min(Math.max(1, page), totalPages);
    renderRestokTable();
}

function renderRestokTable() {
    const tbody = document.getElementById('restokTableBody');
    if (!tbody) return;

    if (!filteredRestok.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="color:#999;">No data found.</td></tr>';
        renderPagination(0, 0);
        return;
    }

    const totalPages = Math.ceil(filteredRestok.length / RESTOK_PER_PAGE);
    const startIdx = (currentPage - 1) * RESTOK_PER_PAGE;
    const pageRows = filteredRestok.slice(startIdx, startIdx + RESTOK_PER_PAGE);

    let html = '';
    pageRows.forEach((row, i) => {
        const no = startIdx + i + 1;
        html += `
        <tr>
            <td>${no}</td>
            <td style="text-align:left;">${row.nama_suplier ?? '-'}</td>
            <td>${row.tanggal_restok}</td>
            <td style="text-align:right;">${row.total_barang}</td>
            <td style="text-align:right; font-weight:600;">${formatRupiah(row.total_harga)}</td>
            <td>${row.created_by_name ?? '-'}</td>
            <td>${statusLabel(row.status_restok)}</td>
            <td>
                <div class="btn-action-group">
                    <button class="btn-view-icon" onclick="openRestokModal(${row.id_restok})">...</button>
                </div>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;
    renderPagination(totalPages, currentPage);
}

function gotoRestokPage(page) {
    currentPage = page;
    renderRestokTable();
}

// ─── PAGINATION ───────────────────────────────────────────────────────────────
function renderPagination(totalPages, current) {
    const el = document.getElementById('restokPagination');
    if (totalPages <= 1) { el.innerHTML = ''; return; }

    let html = '';
    html += current > 1
        ? `<a href="javascript:void(0)" onclick="gotoRestokPage(${current - 1})" class="page-link">&lt;</a>`
        : `<span class="page-link disabled">&lt;</span>`;

    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || Math.abs(i - current) <= 1) {
            html += `<a href="javascript:void(0)" onclick="gotoRestokPage(${i})" class="page-link ${i === current ? 'active' : ''}">${i}</a>`;
        } else if (Math.abs(i - current) === 2) {
            html += `<span class="dots">...</span>`;
        }
    }

    html += current < totalPages
        ? `<a href="javascript:void(0)" onclick="gotoRestokPage(${current + 1})" class="page-link">&gt;</a>`
        : `<span class="page-link disabled">&gt;</span>`;

    el.innerHTML = html;
}

// ─── DEBOUNCE SEARCH ──────────────────────────────────────────────────────────
function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyRestokFilter(1), 400);
}

// ─── MODAL DETAIL ─────────────────────────────────────────────────────────────
function openRestokModal(id) {
    document.getElementById('restokDetailModal').style.display = 'flex';
    document.getElementById('modalItemsBody').innerHTML = '<tr><td colspan="4" style="text-align:center;">Loading...</td></tr>';
    document.getElementById('modalFooter').innerHTML = '<button class="btn-cancel-outline" onclick="closeRestokModal()">Close</button>';

    fetch(`${API}?action=getDetail&id=${id}&actor_id=${ACTOR_ID}`)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') {
                cardhavenAlert('error', 'Error', res.message);
                closeRestokModal();
                return;
            }

            const h = res.data.header;
            const items = res.data.items;

            document.getElementById('modalPOId').textContent = `#${h.id_restok}`;
            document.getElementById('modalSupplier').textContent = h.nama_suplier ?? '-';
            document.getElementById('modalTelp').textContent = h.telp_suplier ?? '-';
            document.getElementById('modalTanggal').textContent = h.tanggal_restok;
            document.getElementById('modalCreatedBy').textContent = h.created_by_name ?? '-';
            document.getElementById('modalStatusBadge').innerHTML = statusLabel(h.status_restok);

            // Approved/rejected by
            const byEl = document.getElementById('modalApprovedBy');
            if (h.approved_by_name && h.modified_date) {
                const action = h.status_restok == 1 ? 'Approved' : 'Rejected';
                byEl.textContent = `— ${action} by ${h.approved_by_name} on ${h.modified_date}`;
            } else {
                byEl.textContent = '';
            }

            // Items
            let itemHtml = '';
            items.forEach(item => {
                itemHtml += `
                <tr>
                    <td style="text-align:left;">${item.nama_produk ?? '-'}</td>
                    <td>${item.jumlah_barang}</td>
                    <td style="text-align:right;">${formatRupiah(item.harga_beli)}</td>
                    <td style="text-align:right; font-weight:600;">${formatRupiah(item.subtotal_harga)}</td>
                </tr>`;
            });
            document.getElementById('modalItemsBody').innerHTML = itemHtml || '<tr><td colspan="4">No items.</td></tr>';
            document.getElementById('modalTotal').textContent = formatRupiah(h.total_harga);

          // Tombol approve/reject — hanya Owner (role=3) dan hanya kalau status masih pending
            if (parseInt(res.data.can_approve) === 1 && parseInt(h.status_restok) === 0) {
                document.getElementById('modalFooter').innerHTML = `
                    <button class="btn-cancel-outline" onclick="closeRestokModal()">Close</button>
                    <button class="btn-cancel-outline" style="color:#E74C3C; border-color:#E74C3C;"
                        onclick="confirmAction(${h.id_restok}, 'reject')">Reject</button>
                    <button class="btn-confirm" onclick="confirmAction(${h.id_restok}, 'approve')">Approve</button>
                `;
            }

            // Tombol Received — Owner (role=3), hanya kalau status sudah Approved
            if (parseInt(res.data.can_receive) === 1 && parseInt(h.status_restok) === 1) {
                document.getElementById('modalFooter').innerHTML = `
                    <button class="btn-cancel-outline" onclick="closeRestokModal()">Close</button>
                    <button class="btn-confirm" onclick="confirmAction(${h.id_restok}, 'receive')">Mark as Received</button>
                `;
            }

            // Tombol Paid — Owner (role=3), hanya kalau status sudah Received
            if (parseInt(res.data.can_pay) === 1 && parseInt(h.status_restok) === 3) {
                document.getElementById('modalFooter').innerHTML = `
                    <button class="btn-cancel-outline" onclick="closeRestokModal()">Close</button>
                    <button class="btn-confirm" onclick="confirmAction(${h.id_restok}, 'pay')">Mark as Paid</button>
                `;
            }
        });
}

function closeRestokModal() {
    document.getElementById('restokDetailModal').style.display = 'none';
}

// ─── APPROVE / REJECT / RECEIVE / PAY ──────────────────────────────────────────
function confirmAction(id, action) {
    const config = {
        approve: { title: 'Approve this PO?', desc: 'This will mark the PO as approved.', btn: 'Yes, Approve' },
        reject:  { title: 'Reject this PO?', desc: 'This will reject the PO. Admin will need to create a new one.', btn: 'Yes, Reject' },
        receive: { title: 'Mark this PO as Received?', desc: 'Confirm the goods have been physically checked and match the PO.', btn: 'Yes, Received' },
        pay:     { title: 'Mark this PO as Paid?', desc: 'This confirms payment to the supplier and will automatically add items to stock.', btn: 'Yes, Paid' },
    };
    const c = config[action];
    cardhavenConfirm(
        c.title,
        c.desc,
        c.btn,
        () => {
          const body = new FormData();
            body.append('action', action);
            body.append('id_restok', id);
            body.append('actor_id', ACTOR_ID);
            

            fetch(API, { method: 'POST', body })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        cardhavenAlert('success', 'Done!', res.message, () => {
                            closeRestokModal();
                            loadRestok(currentPage);
                        });
                    } else {
                        cardhavenAlert('error', 'Failed', res.message);
                    }
                })
                .catch(() => cardhavenAlert('error', 'Error', 'Request failed.'));
        }
    );
}

// ─── INIT ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Owner (role=2) read-only — tombol Buat PO disembunyikan
    const btn = document.getElementById('btnBuatPO');
    if (btn) btn.style.display = (USER_ROLE === 2) ? '' : 'none';
    loadRestok(1);

    // Shortcut dari dashboard Activity: buka modal PO langsung via ?open_restok=<id>
    const openId = new URLSearchParams(window.location.search).get('open_restok');
    if (openId) openRestokModal(parseInt(openId));
});

// Klik di luar modal box (di area overlay) untuk menutup, sama seperti modul Product
window.addEventListener('click', (e) => {
    const m = document.getElementById('restokDetailModal');
    if (m && e.target === m) m.style.display = 'none';
    const a = document.getElementById('addRestokModal');
    if (a && e.target === a) a.style.display = 'none';
});

// ─── ADD PO: state ──────────────────────────────────────────────────────────
let produkList = [];
let itemRowCount = 0;

// ─── ADD PO: open / close ────────────────────────────────────────────────────
function openAddRestokModal() {
    document.getElementById('addRestokModal').style.display = 'flex';
    document.getElementById('addSupplierSearch').value = '';
    document.getElementById('addIdSupplier').value = '';
    document.getElementById('addItemsBody').innerHTML = '';
    itemRowCount = 0;
    addItemRow(); // langsung kasih 1 baris kosong waktu modal kebuka
}

// Helper error style, dipakai buat field Supplier (di dalam .modal-form-group)
function showError(el, msg) {
    el.style.border = "2px solid #E74C3C";
    const err = el.closest('.modal-form-group')?.querySelector('.error-message');
    if (err) { err.innerText = msg; err.style.display = "block"; err.style.color = "#E74C3C"; }
}
function clearError(el) {
    el.style.border = "";
    const err = el.closest('.modal-form-group')?.querySelector('.error-message');
    if (err) err.innerText = "";
}

// ─── Autocomplete Supplier ───────────────────────────────────────────────────
const suppInput   = document.getElementById('addSupplierSearch');
const suppHidden  = document.getElementById('addIdSupplier');
const suppBox     = document.getElementById('addSupplierSuggest');

suppInput.oninput = function () {
    clearError(suppInput);
    suppHidden.value = ''; // kalau lagi ngetik ulang, anggap belum valid sampai pilih dari suggestion
    if (this.value.length < 1) { suppBox.style.display = 'none'; return; }

    fetch(`${API}?action=search_supplier&search_supplier=${encodeURIComponent(this.value)}&actor_id=${ACTOR_ID}`)
        .then(r => r.json())
        .then(data => {
            suppBox.innerHTML = '';
            if (data.length > 0) {
                suppBox.style.display = 'block';
                data.forEach(item => {
                    const div = document.createElement('div');
                    div.innerHTML = item.nama_suplier;
                    div.onclick = () => {
                        suppInput.value = item.nama_suplier;
                        suppHidden.value = item.id_supplier;
                        suppBox.style.display = 'none';
                        clearError(suppInput);
                    };
                    suppBox.appendChild(div);
                });
            } else {
                suppBox.style.display = 'none';
            }
        });
};

// ─── Autocomplete Produk (per baris, wajib supplier dipilih dulu) ────────────
function setupProdukSuggest(rowId) {
    const input  = document.getElementById(`produkSearch${rowId}`);
    const hidden = document.getElementById(`produkId${rowId}`);
    const box    = document.getElementById(`produkSuggest${rowId}`);

    input.oninput = function () {
        hidden.value = '';
        if (this.value.length < 1) { box.style.display = 'none'; return; }

        if (!suppHidden.value) {
            box.innerHTML = '<div style="color:#E74C3C; cursor:default;">⚠ Please select a Supplier first!</div>';
            box.style.display = 'block';
            return;
        }

        fetch(`${API}?action=search_produk&search_produk=${encodeURIComponent(this.value)}&id_supplier=${suppHidden.value}&actor_id=${ACTOR_ID}`)
            .then(r => r.json())
            .then(data => {
                box.innerHTML = '';
                if (data.length > 0) {
                    box.style.display = 'block';
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.innerHTML = item.nama_produk;
                        div.onclick = () => {
                            input.value = item.nama_produk;
                            hidden.value = item.id_produk;
                            box.style.display = 'none';
                            document.getElementById(`harga${rowId}`).value = item.harga_beli;
                            recalcRow(rowId);
                        };
                        box.appendChild(div);
                    });
                } else {
                    box.innerHTML = '<div style="color:#888; cursor:default;">No product found.</div>';
                    box.style.display = 'block';
                }
            });
    };
}

function closeAddRestokModal() {
    document.getElementById('addRestokModal').style.display = 'none';
}

// ─── ADD PO: baris item dinamis ──────────────────────────────────────────────
function addItemRow() {
    itemRowCount++;
    const rowId = itemRowCount;

    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.id = `itemRow${rowId}`;
    tr.innerHTML = `
        <td>
            <div style="position:relative;">
                <input type="text" id="produkSearch${rowId}" class="modal-input" placeholder="Type product name..." autocomplete="off" style="padding:7px 10px; font-size:0.85rem;">
                <input type="hidden" id="produkId${rowId}">
                <div id="produkSuggest${rowId}" class="suggestion-box"></div>
            </div>
        </td>
        <td><input type="number" min="1" value="1" id="qty${rowId}" oninput="recalcRow(${rowId})"></td>
        <td><input type="number" min="0" step="0.01" value="0" id="harga${rowId}" oninput="recalcRow(${rowId})"></td>
        <td style="text-align:right; font-weight:600;" id="subtotal${rowId}">Rp0</td>
        <td><button type="button" class="btn-remove-row" onclick="removeItemRow(${rowId})">&times;</button></td>
    `;
    document.getElementById('addItemsBody').appendChild(tr);
    setupProdukSuggest(rowId);
}

function removeItemRow(rowId) {
    const row = document.getElementById(`itemRow${rowId}`);
    if (row) row.remove();
    recalcAddTotal();
}


function recalcRow(rowId) {
    const qty   = parseFloat(document.getElementById(`qty${rowId}`).value) || 0;
    const harga = parseFloat(document.getElementById(`harga${rowId}`).value) || 0;
    const subtotal = qty * harga;
    document.getElementById(`subtotal${rowId}`).textContent = formatRupiah(subtotal);
    recalcAddTotal();
}

function recalcAddTotal() {
    let total = 0;
    document.querySelectorAll('#addItemsBody tr.item-row').forEach(tr => {
        const idNum = tr.id.replace('itemRow', '');
        const qty   = parseFloat(document.getElementById(`qty${idNum}`)?.value) || 0;
        const harga = parseFloat(document.getElementById(`harga${idNum}`)?.value) || 0;
        total += qty * harga;
    });
    document.getElementById('addTotalDisplay').textContent = formatRupiah(total);
}

// ─── ADD PO: submit ──────────────────────────────────────────────────────────
function submitAddRestok() {
   const suppInputEl = document.getElementById('addSupplierSearch');
const id_supplier = document.getElementById('addIdSupplier').value;
let errors = [];

// Reset semua border dulu
suppInputEl.style.border = '';
document.querySelectorAll('#addItemsBody tr.item-row').forEach(tr => {
    tr.querySelectorAll('input').forEach(el => el.style.border = '');
});

if (!id_supplier) {
    showError(suppInputEl, 'Supplier is required.');
    errors.push('Supplier is required.');
}

const rows = document.querySelectorAll('#addItemsBody tr.item-row');
if (rows.length === 0) {
    errors.push('Add at least one item.');
}

const items = [];
rows.forEach((tr, idx) => {
    const n = tr.id.replace('itemRow', '');
    const selProduk  = document.getElementById(`produkId${n}`);
    const inputQty   = document.getElementById(`qty${n}`);
    const inputHarga = document.getElementById(`harga${n}`);

    const id_produk     = selProduk.value;
    const jumlah_barang = parseInt(inputQty.value) || 0;
    const harga_beli    = parseFloat(inputHarga.value) || 0;

    let rowErrors = [];
    if (!id_produk)        { document.getElementById(`produkSearch${n}`).style.border = '2px solid #E74C3C'; rowErrors.push('product'); }
    if (jumlah_barang < 1) { inputQty.style.border   = '2px solid #E74C3C'; rowErrors.push('quantity'); }
    if (harga_beli <= 0)   { inputHarga.style.border = '2px solid #E74C3C'; rowErrors.push('price'); }

    if (rowErrors.length > 0) {
        errors.push(`Row ${idx + 1}: ${rowErrors.join(', ')} is required.`);
    } else {
        items.push({ id_produk, jumlah_barang, harga_beli });
    }
});

if (errors.length > 0) {
    cardhavenAlert('error', 'Please fix the following:', errors.join('\n'));
    return;
}

    const body = new FormData();
    body.append('action', 'create');
    body.append('id_supplier', id_supplier);
    body.append('items', JSON.stringify(items));
    body.append('actor_id', ACTOR_ID);

    fetch(API, { method: 'POST', body })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                cardhavenAlert('success', 'Done!', res.message, () => {
                    closeAddRestokModal();
                    loadRestok(1);
                });
            } else {
                cardhavenAlert('error', 'Failed', res.message);
            }
        })
        .catch(() => cardhavenAlert('error', 'Error', 'Request failed.'));
}