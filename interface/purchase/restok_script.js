const API       = '/cardhaven/interface/purchase/controller_restok.php';
const ACTOR_ID  = parseInt(sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna') || 0);
const USER_ROLE = parseInt(sessionStorage.getItem('role') || localStorage.getItem('role') || 0);
let currentPage = 1;
let searchTimer = null;

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

// ─── LOAD TABLE ───────────────────────────────────────────────────────────────
function loadRestok(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('statusFilter').value;

    const params = new URLSearchParams({
        action: 'getList',
        page,
        search,
        status,
        actor_id: ACTOR_ID,
    });

    const tbody = document.getElementById('restokTableBody');
    tbody.innerHTML = '<tr><td colspan="8" style="color:#999;">Loading...</td></tr>';

    fetch(`${API}?${params}`)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') {
                tbody.innerHTML = `<tr><td colspan="8" style="color:#E74C3C;">${res.message}</td></tr>`;
                return;
            }

            const { rows, total, total_pages } = res.data;

            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="8" style="color:#999;">No data found.</td></tr>';
                renderPagination(0, 0);
                return;
            }

            let html = '';
            rows.forEach((row, i) => {
                const no = ((page - 1) * 7) + i + 1;
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
            renderPagination(total_pages, page);
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="8" style="color:#E74C3C;">Failed to load data.</td></tr>';
        });
}

// ─── PAGINATION ───────────────────────────────────────────────────────────────
function renderPagination(totalPages, current) {
    const el = document.getElementById('restokPagination');
    if (totalPages <= 1) { el.innerHTML = ''; return; }

    let html = '';
    html += current > 1
        ? `<a href="javascript:void(0)" onclick="loadRestok(${current - 1})" class="page-link">&lt;</a>`
        : `<span class="page-link disabled">&lt;</span>`;

    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || Math.abs(i - current) <= 1) {
            html += `<a href="javascript:void(0)" onclick="loadRestok(${i})" class="page-link ${i === current ? 'active' : ''}">${i}</a>`;
        } else if (Math.abs(i - current) === 2) {
            html += `<span class="dots">...</span>`;
        }
    }

    html += current < totalPages
        ? `<a href="javascript:void(0)" onclick="loadRestok(${current + 1})" class="page-link">&gt;</a>`
        : `<span class="page-link disabled">&gt;</span>`;

    el.innerHTML = html;
}

// ─── DEBOUNCE SEARCH ──────────────────────────────────────────────────────────
function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadRestok(1), 400);
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
    document.getElementById('addSupplierSelect').innerHTML = '<option value="">Loading...</option>';
    document.getElementById('addItemsBody').innerHTML = '';
    itemRowCount = 0;

    // Load supplier dropdown
    fetch(`${API}?action=getSuppliers&actor_id=${ACTOR_ID}`)
        .then(r => r.json())
        .then(res => {
            const sel = document.getElementById('addSupplierSelect');
            if (res.status !== 'success') {
                sel.innerHTML = '<option value="">Failed to load suppliers</option>';
                return;
            }
            sel.innerHTML = '<option value="">-- Select Supplier --</option>' +
                res.data.rows.map(s => `<option value="${s.id_supplier}">${s.nama_suplier}</option>`).join('');
        })
        .catch(() => {
            document.getElementById('addSupplierSelect').innerHTML = '<option value="">Failed to load suppliers</option>';
        });

    // Load produk list (dipakai berkali-kali tiap nambah baris, jadi di-cache di produkList)
    fetch(`${API}?action=getProduk&actor_id=${ACTOR_ID}`)
        .then(r => r.json())
        .then(res => {
            produkList = res.status === 'success' ? res.data.rows : [];
            addItemRow(); // langsung kasih 1 baris kosong waktu modal kebuka
        })
        .catch(() => { produkList = []; addItemRow(); });
}

function closeAddRestokModal() {
    document.getElementById('addRestokModal').style.display = 'none';
}

// ─── ADD PO: baris item dinamis ──────────────────────────────────────────────
function addItemRow() {
    itemRowCount++;
    const rowId = itemRowCount;

    const produkOptions = '<option value="">-- Select Product --</option>' +
        produkList.map(p => `<option value="${p.id_produk}" data-harga="${p.harga_beli}">${p.nama_produk}</option>`).join('');

    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.id = `itemRow${rowId}`;
    tr.innerHTML = `
        <td>
            <select onchange="onProdukChange(${rowId})" id="produk${rowId}">
                ${produkOptions}
            </select>
        </td>
        <td><input type="number" min="1" value="1" id="qty${rowId}" oninput="recalcRow(${rowId})"></td>
        <td><input type="number" min="0" step="0.01" value="0" id="harga${rowId}" oninput="recalcRow(${rowId})"></td>
        <td style="text-align:right; font-weight:600;" id="subtotal${rowId}">Rp0</td>
        <td><button type="button" class="btn-remove-row" onclick="removeItemRow(${rowId})">&times;</button></td>
    `;
    document.getElementById('addItemsBody').appendChild(tr);
}

function removeItemRow(rowId) {
    const row = document.getElementById(`itemRow${rowId}`);
    if (row) row.remove();
    recalcAddTotal();
}

// Auto-fill harga_beli ketika produk dipilih
function onProdukChange(rowId) {
    const sel = document.getElementById(`produk${rowId}`);
    const opt = sel.options[sel.selectedIndex];
    const harga = opt?.dataset?.harga ?? 0;
    document.getElementById(`harga${rowId}`).value = harga;
    recalcRow(rowId);
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
   const suppSel = document.getElementById('addSupplierSelect');
const id_supplier = suppSel.value;
let errors = [];

// Reset semua border dulu
suppSel.style.border = '';
document.querySelectorAll('#addItemsBody tr.item-row').forEach(tr => {
    tr.querySelectorAll('select, input').forEach(el => el.style.border = '');
});

if (!id_supplier) {
    suppSel.style.border = '2px solid #E74C3C';
    errors.push('Supplier is required.');
}

const rows = document.querySelectorAll('#addItemsBody tr.item-row');
if (rows.length === 0) {
    errors.push('Add at least one item.');
}

const items = [];
rows.forEach((tr, idx) => {
    const n = tr.id.replace('itemRow', '');
    const selProduk  = document.getElementById(`produk${n}`);
    const inputQty   = document.getElementById(`qty${n}`);
    const inputHarga = document.getElementById(`harga${n}`);

    const id_produk     = selProduk.value;
    const jumlah_barang = parseInt(inputQty.value) || 0;
    const harga_beli    = parseFloat(inputHarga.value) || 0;

    let rowErrors = [];
    if (!id_produk)        { selProduk.style.border  = '2px solid #E74C3C'; rowErrors.push('product'); }
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