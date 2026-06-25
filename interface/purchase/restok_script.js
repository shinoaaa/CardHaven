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
    };
    return map[s] ?? '-';
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
            if (!res.success) {
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
            if (!res.success) {
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

            // Tombol approve/reject — hanya Superadmin (role=3) dan hanya kalau status masih pending
            if (parseInt(res.data.can_approve) === 1 && parseInt(h.status_restok) === 0) {
                document.getElementById('modalFooter').innerHTML = `
                    <button class="btn-cancel-outline" onclick="closeRestokModal()">Close</button>
                    <button class="btn-cancel-outline" style="color:#E74C3C; border-color:#E74C3C;"
                        onclick="confirmAction(${h.id_restok}, 'reject')">Reject</button>
                    <button class="btn-confirm" onclick="confirmAction(${h.id_restok}, 'approve')">Approve</button>
                `;
            }
        });
}

function closeRestokModal() {
    document.getElementById('restokDetailModal').style.display = 'none';
}

// ─── APPROVE / REJECT ─────────────────────────────────────────────────────────
function confirmAction(id, action) {
const ACTOR_ID = parseInt(sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna') || 0);
const USER_ROLE = parseInt(sessionStorage.getItem('role') || localStorage.getItem('role') || 0);
    const isApprove = action === 'approve';
    cardhavenConfirm(
        isApprove ? 'Approve this PO?' : 'Reject this PO?',
        isApprove
            ? 'This will mark the PO as approved. Admin will proceed with the purchase.'
            : 'This will reject the PO. The admin will need to create a new one.',
        isApprove ? 'Yes, Approve' : 'Yes, Reject',
        () => {
          const body = new FormData();
            body.append('action', action);
            body.append('id_restok', id);
            body.append('actor_id', ACTOR_ID);
            

            fetch(API, { method: 'POST', body })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
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
            if (!res.success) {
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
            produkList = res.success ? res.data.rows : [];
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
    // Tambahkan di paling atas file, setelah baris const API = ...
const ACTOR_ID = parseInt(sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna') || 0);
const USER_ROLE = parseInt(sessionStorage.getItem('role') || localStorage.getItem('role') || 0);
    const id_supplier = document.getElementById('addSupplierSelect').value;
    if (!id_supplier) {
        cardhavenAlert('error', 'Validation', 'Please select a supplier.');
        return;
    }

    const items = [];
    let valid = true;
    document.querySelectorAll('#addItemsBody tr.item-row').forEach(tr => {
        const idNum = tr.id.replace('itemRow', '');
        const id_produk = document.getElementById(`produk${idNum}`).value;
        const jumlah_barang = parseInt(document.getElementById(`qty${idNum}`).value) || 0;
        const harga_beli = parseFloat(document.getElementById(`harga${idNum}`).value) || 0;

        if (!id_produk || jumlah_barang < 1 || harga_beli <= 0) {
            valid = false;
            return;
        }
        items.push({ id_produk, jumlah_barang, harga_beli });
    });

    if (!valid || items.length === 0) {
        cardhavenAlert('error', 'Validation', 'Please complete all item fields (product, quantity, price).');
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
            if (res.success) {
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