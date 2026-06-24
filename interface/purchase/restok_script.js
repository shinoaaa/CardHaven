const API = '/cardhaven/interface/purchase/controller_restok.php';
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
    });

    const tbody = document.getElementById('restokTableBody');
    tbody.innerHTML = '<tr><td colspan="9" style="color:#999;">Loading...</td></tr>';

    fetch(`${API}?${params}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                tbody.innerHTML = `<tr><td colspan="9" style="color:#E74C3C;">${res.message}</td></tr>`;
                return;
            }

            const { rows, total, total_pages } = res.data;

            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="9" style="color:#999;">No data found.</td></tr>';
                renderPagination(0, 0);
                return;
            }

            let html = '';
            rows.forEach((row, i) => {
                const no = ((page - 1) * 7) + i + 1;
                html += `
                <tr>
                    <td>${no}</td>
                    <td style="font-weight:600;">#${row.id_restok}</td>
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
            tbody.innerHTML = '<tr><td colspan="9" style="color:#E74C3C;">Failed to load data.</td></tr>';
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
    document.getElementById('restokOverlay').classList.add('active');
    document.getElementById('restokDetailModal').classList.add('active');
    document.getElementById('modalItemsBody').innerHTML = '<tr><td colspan="4">Loading...</td></tr>';
    document.getElementById('modalFooter').innerHTML = '<button class="btn-cancel-outline" onclick="closeRestokModal()">Close</button>';

    fetch(`${API}?action=getDetail&id=${id}`)
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
                    <td>${item.jumlah}</td>
                    <td style="text-align:right;">${formatRupiah(item.harga_satuan)}</td>
                    <td style="text-align:right; font-weight:600;">${formatRupiah(item.subtotal)}</td>
                </tr>`;
            });
            document.getElementById('modalItemsBody').innerHTML = itemHtml || '<tr><td colspan="4">No items.</td></tr>';
            document.getElementById('modalTotal').textContent = formatRupiah(h.total_harga);

            // Tombol approve/reject — hanya superadmin (role=3) dan hanya kalau pending
            if (USER_ROLE === 3 && parseInt(h.status_restok) === 0) {
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
    document.getElementById('restokOverlay').classList.remove('active');
    document.getElementById('restokDetailModal').classList.remove('active');
}

// ─── APPROVE / REJECT ─────────────────────────────────────────────────────────
function confirmAction(id, action) {
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
document.addEventListener('DOMContentLoaded', () => loadRestok(1));