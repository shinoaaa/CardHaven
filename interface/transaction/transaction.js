const TRX_API = '/cardhaven/interface/transaction/apifetch.php';

const STATUS_LABEL = {
    0: 'Pending Payment',
    1: 'Paid',
    2: 'Waiting Stock',
    3: 'Processing',
    4: 'Shipped',
    5: 'Delivered',
    6: 'Completed',
    7: 'Returned',
    8: 'Cancelled',
};

const STATUS_COLOR = {
    0: '#ca8a04',
    1: '#15803d',
    2: '#0369a1',
    3: '#7c3aed',
    4: '#1d4ed8',
    5: '#065f46',
    6: '#14532d',
    7: '#b91c1c',
    8: '#6b7280',
};

const STATUS_BG = {
    0: '#fef9c3',
    1: '#dcfce7',
    2: '#e0f2fe',
    3: '#ede9fe',
    4: '#dbeafe',
    5: '#d1fae5',
    6: '#d1fae5',
    7: '#fee2e2',
    8: '#f3f4f6',
};

// ════════════════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════════════════

function getUserId() {
    return CardHavenAuth.id();
}

// Resolusi path foto produk yang konsisten: path lengkap dipakai apa adanya,
// nama file polos diarahkan ke folder produk.
function productImg(foto) {
    if (!foto) return '/CardHaven/assets/image/image-profile/defaultProduct.jpg';
    // Data lama: path tersimpan dgn prefix folder lama → arahkan ke lokasi baru
    if (foto.startsWith('image-profile/')) foto = `assets/image/${foto}`;
    return foto.includes('/') ? `/CardHaven/${foto}` : `/CardHaven/assets/image/products/${foto}`;
}

function statusBadge(status) {
    const s = parseInt(status);
    return `<span style="
        display:inline-block;
        padding:3px 10px;
        border-radius:20px;
        font-size:.72rem;
        font-weight:700;
        background:${STATUS_BG[s] ?? '#f3f4f6'};
        color:${STATUS_COLOR[s] ?? '#555'};
    ">${STATUS_LABEL[s] ?? 'Unknown'}</span>`;
}

// ════════════════════════════════════════════════════════════════════════════
// DETAIL MODAL
// ════════════════════════════════════════════════════════════════════════════

async function openDetailModal(id_penjualan) {
    const overlay = document.getElementById('trxModalOverlay');
    const body    = document.getElementById('trxModalBody');
    body.innerHTML = '<div style="text-align:center;padding:2rem;opacity:.5;">Loading...</div>';
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';

    try {
        const res  = await fetch(`${TRX_API}?action=detail&id=${id_penjualan}`);
        const data = await res.json();

        if (data.error) {
            body.innerHTML = `<p style="color:red;">${data.error}</p>`;
            return;
        }

        // apifetch (sp_GetSalesDetail) mengembalikan field header di level teratas,
        // bukan di dalam properti `header`. Ambil langsung dari `data`.
        const h = data;
        const st = parseInt(h.status_penjualan);

        // Tentukan tombol aksi yang tampil. Owner (role 3) view-only — tanpa tombol aksi.
        const USER_ROLE = CardHavenAuth.role();
        let actionBtns = '';

        if (USER_ROLE === 3) {
            // view-only: tidak ada tombol aksi untuk Owner
        } else if (st === 0) {
            actionBtns = `
                <button class="btn-trx-action btn-cancel" onclick="doAction('cancel', ${id_penjualan})">
                    Cancel
                </button>`;
        } else if (st === 1) {
            // Jika sudah paid (1), admin bisa me-reject payment customer (kembali ke 0) 
            actionBtns = `
                <button class="btn-trx-action btn-process" onclick="doAction('proses', ${id_penjualan})">
                    Process Order
                </button>
                <button class="btn-trx-action btn-cancel" onclick="promptRejectPayment(${id_penjualan})">
                    Reject Payment
                </button>`;
        } else if (st === 2) {
            actionBtns = `
                <button class="btn-trx-action btn-process" onclick="doAction('proses', ${id_penjualan})">
                    Process Order
                </button>
                <button class="btn-trx-action btn-cancel" onclick="doAction('cancel', ${id_penjualan})">
                    Cancel
                </button>`;
        } else if (st === 3) {
            actionBtns = `
                <button class="btn-trx-action btn-ship" onclick="openShipModal(${id_penjualan})">
                    Ship Order
                </button>`;
        } else if (st === 4) {
            actionBtns = `
                <button class="btn-trx-action btn-deliver" onclick="doAction('delivered', ${id_penjualan})">
                    Set Delivered
                </button>`;
        }

        // Bukti bayar — nilai DB adalah path relatif lengkap dari web root.
        // Data lama masih memakai folder 'bukti_pembayaran/' → arahkan ke lokasi baru.
        let buktiSrc = h.bukti_pembayaran || '';
        if (buktiSrc.startsWith('bukti_pembayaran/')) buktiSrc = `assets/image/${buktiSrc}`;
        const buktiHtml = h.bukti_pembayaran
            ? `<a href="/CardHaven/${buktiSrc}" target="_blank">
                <img src="/CardHaven/${buktiSrc}"
                    style="max-width:180px;max-height:130px;border-radius:8px;border:1px solid rgba(255,255,255,.15);object-fit:cover;cursor:pointer;">
               </a>`
            : `<span style="opacity:.45;font-size:.8rem;">No proof yet</span>`;

        // Item rows
        const itemsHtml = (data.items || []).map(item => `
            <tr>
                <td style="display:flex;align-items:center;gap:.6rem;padding:.5rem 0;">
                    <img src="${item.foto ? `/CardHaven/assets/image/products/${item.foto}` : '/CardHaven/assets/image/image-profile/defaultProduct.jpg'}"
                        style="width:40px;height:40px;border-radius:6px;object-fit:cover;flex-shrink:0;">
                    <div>
                        <div style="font-weight:600;font-size:.85rem;">${item.nama_produk ?? '-'}</div>
                        <div style="font-size:.72rem;opacity:.55;">${item.tipe_produk ?? ''} · ${item.kondisi ?? ''}</div>
                    </div>
                </td>
                <td style="text-align:right;white-space:nowrap;">Rp ${Number(item.harga_produk).toLocaleString('id-ID')}</td>
                <td style="text-align:center;">${item.jumlah_barang}</td>
                <td style="text-align:right;white-space:nowrap;font-weight:700;">Rp ${Number(item.subtotal_harga).toLocaleString('id-ID')}</td>
            </tr>
        `).join('');

        body.innerHTML = `
            <div class="trx-modal-header">
                <div>
                    <div class="trx-modal-id">Order #${h.id_penjualan}</div>
                    <div class="trx-modal-date">${h.tanggal_penjualan}</div>
                </div>
                ${statusBadge(h.status_penjualan)}
            </div>

            <div class="trx-modal-grid">
                <!-- Customer -->
                <div class="trx-modal-section">
                    <div class="trx-section-title">Customer</div>
                    <div class="trx-info-row"><span>Username</span><b>${h.username ?? '-'}</b></div>
                    <div class="trx-info-row"><span>Email</span><b>${h.email ?? '-'}</b></div>
                    <div class="trx-info-row"><span>Phone Number</span><b>${h.no_telepon ?? '-'}</b></div>
                    <div class="trx-info-row"><span>Address</span><b>${h.alamat ?? '-'}</b></div>
                </div>

                <!-- Payment -->
                <div class="trx-modal-section">
                    <div class="trx-section-title">Payment</div>
                    <div class="trx-info-row"><span>Method</span><b>${h.nama_metode ?? '-'}</b></div>
                    <div class="trx-info-row"><span>Provider</span><b>${h.provider ?? '-'}</b></div>
                    <div class="trx-info-row"><span>Account Number</span><b>${h.rek_tujuan ?? h.no_rekening ?? '-'}</b></div>
                    <div class="trx-info-row"><span>Account Holder</span><b>${h.atas_nama ?? '-'}</b></div>
                    <div class="trx-info-row"><span>Admin Fee</span><b>Rp ${parseInt(h.biaya_admin || 0).toLocaleString('id-ID')}</b></div>
                </div>

                <!-- Shipping -->
                <div class="trx-modal-section">
                    <div class="trx-section-title">Shipping</div>
                    <div class="trx-info-row"><span>Tracking Number</span><b>${h.no_resi ?? '-'}</b></div>
                    <div class="trx-info-row"><span>Shipping Date</span><b>${h.tanggal_pengiriman ?? '-'}</b></div>
                </div>

                <!-- Proof of payment -->
                <div class="trx-modal-section">
                    <div class="trx-section-title">Proof of payment</div>
                    ${buktiHtml}
                </div>
            </div>

            <!-- Items -->
            <div class="trx-section-title" style="margin-top:1.25rem;">Items</div>
            <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
                <thead>
                    <tr style="border-bottom:1px solid rgba(255,255,255,.1);opacity:.55;">
                        <th style="text-align:left;padding-bottom:.4rem;">Product</th>
                        <th style="text-align:right;">Price</th>
                        <th style="text-align:center;">Quantity</th>
                        <th style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>${itemsHtml}</tbody>
            </table>

            <div style="display:flex;justify-content:flex-end;margin-top:.75rem;padding-top:.75rem;border-top:1px solid rgba(255,255,255,.1);">
                <span style="font-size:.85rem;opacity:.65;margin-right:.5rem;">${h.total_barang} item · Total</span>
                <span style="font-size:1rem;font-weight:800;color:var(--primary-color);">Rp ${parseInt(h.total_harga || 0).toLocaleString('id-ID')}</span>
            </div>

            ${actionBtns ? `<div class="trx-action-row">${actionBtns}</div>` : ''}
        `;
    } catch (e) {
        body.innerHTML = `<p style="color:red;">Failed to load data.</p>`;
        console.error(e);
    }
}

function closeTrxModal(e) {
    if (e && e.target !== e.currentTarget) return;
    document.getElementById('trxModalOverlay').classList.remove('show');
    document.body.style.overflow = '';
}

// ════════════════════════════════════════════════════════════════════════════
// SHIP MODAL (input no resi + tanggal)
// ════════════════════════════════════════════════════════════════════════════

function openShipModal(id_penjualan) {
    const overlay = document.getElementById('trxModalOverlay');
    const body    = document.getElementById('trxModalBody');

    const today = new Date().toISOString().split('T')[0];

    body.innerHTML = `
        <div class="trx-modal-header">
            <div class="trx-modal-id">Ship Order #${id_penjualan}</div>
        </div>
        <div class="trx-modal-section" style="margin-top:1rem;">
            <div class="trx-section-title">Shipping Details</div>
            <div style="margin-bottom:.85rem;">
                <label style="display:block;font-size:.8rem;opacity:.65;margin-bottom:.3rem;">Tracking Number *</label>
                <input id="inputResi" type="text" placeholder="Example: JNE1234567890"
                    style="width:100%;padding:.6rem .85rem;border-radius:8px;border:1px solid rgba(255,255,255,.2);
                    background:rgba(255,255,255,.07);color:inherit;font-size:.9rem;box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block;font-size:.8rem;opacity:.65;margin-bottom:.3rem;">Shipping Date</label>
                <input id="inputTglKirim" type="date" value="${today}" min="${today}"
                    style="width:100%;padding:.6rem .85rem;border-radius:8px;border:1px solid rgba(255,255,255,.2);
                    background:rgba(255,255,255,.07);color:inherit;font-size:.9rem;box-sizing:border-box;">
            </div>
        </div>
        <div class="trx-action-row">
            <button class="btn-trx-action btn-ship" onclick="submitKirim(${id_penjualan})">
                🚚 Confirm Shipment
            </button>
            <button class="btn-trx-action" style="background:rgba(255,255,255,.08);" onclick="openDetailModal(${id_penjualan})">
                ← Back
            </button>
        </div>
    `;
}

async function submitKirim(id_penjualan) {
    const no_resi   = document.getElementById('inputResi').value.trim();
    const tgl_kirim = document.getElementById('inputTglKirim').value;
    const today     = new Date().toISOString().split('T')[0];

    if (!no_resi) {
        cardhavenAlert('Alert',  'warning', 'Tracking number is required!');
        return;
    }
    const alphanumericRegex = /^[a-zA-Z0-9]+$/;
    if (!alphanumericRegex.test(no_resi)) {
        cardhavenAlert('Alert', 'warning', 'Invalid tracking number!');
        return;
        }

    // 3. Validasi Tanggal
    if (tgl_kirim < today) {
        cardhavenAlert('Alert', 'warning', 'Invalid shipment date!');
        return;
    }

    const res = await postAction('kirim', id_penjualan, { no_resi: no_resi, tgl_kirim: tgl_kirim });
    if (res.status === 'success') {
        cardhavenAlert('Success', 'success', 'Package shipment has been confirmed.');
        closeTrxModal();
        setTimeout(() => location.reload(), 1200);
    } else {
        cardhavenAlert('Error', 'error', res.message ?? 'Failed to update status.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// GENERIC ACTION
// ════════════════════════════════════════════════════════════════════════════

async function postAction(action, id_penjualan, extra = {}) {
    const userId = getUserId();
    const res = await fetch(TRX_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, id_penjualan, modified_by: userId, ...extra }),
    });
    return await res.json();
}

function promptRejectPayment(id_penjualan) {
    Swal.fire({
        title: 'Reject Payment?',
        text: 'Enter the reason why the payment was declined .',
        input: 'textarea',
        inputPlaceholder: 'Examples: Transfer amount is incorrect, receipt is blurry, etc...',
        showCancelButton: true,
        confirmButtonText: 'Reject & Notify',
        confirmButtonColor: '#b91c1c',
        preConfirm: (reason) => {
            if (!reason) {
                Swal.showValidationMessage('please provide a reason for rejecting the payment.');
            }
            return reason;
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            const res = await postAction('reject_payment', id_penjualan, { reason: result.value });
            if (res.status === 'success') {
                cardhavenAlert('Success', 'success', 'Payment rejected. Status reverted to Pending Payment.');
                closeTrxModal();
                setTimeout(() => location.reload(), 1200);
            } else {
                cardhavenAlert('Error', 'error', res.message || 'Failed to reject payment.');
            }
        }
    });
}

async function doAction(action, id_penjualan) {
    const CONFIRM_MSG = {
        confirm_payment: ['Confirm Payment?', 'The payment will be marked as received.', 'Yes, Confirm'],
        proses:           ['Process Order?', 'The order will be moved to the Processing status.', 'Yes, Process'],
        delivered:        ['Mark as Delivered?', 'The order will be marked as delivered to the customer.', 'Yes, Mark as Delivered'],
        cancel:           ['Cancel Order?', 'The product stock will be restored. This action cannot be undone.', 'Yes, Cancel'],
    };

    const [title, text, btnText] = CONFIRM_MSG[action] ?? ['Konfirmasi?', '', 'Ya'];

    cardhavenConfirm(title, text, btnText, async () => {
        const res = await postAction(action, id_penjualan);
        if (res.status === 'success') {
            cardhavenAlert('Success', 'success', 'Status has been updated successfully.');
            closeTrxModal();
            setTimeout(() => location.reload(), 1200);
        } else {
            cardhavenAlert('Error', 'error', res.message ?? 'Failed to update status.');
        }
    }, null);
}

// ════════════════════════════════════════════════════════════════════════════
// AJAX TABLE UPDATE (Refresh tabel tanpa reload halaman)
// ════════════════════════════════════════════════════════════════════════════

async function updateTableHTML(url) {
    try {
        // Ganti URL di atas browser agar jika direfresh datanya tetap
        window.history.pushState({ path: url }, '', url);
        
        const container = document.getElementById('tableContainer');
        container.style.opacity = '0.4'; // Beri efek loading transparan

        // Ambil HTML baru dari server
        const res = await fetch(url);
        const html = await res.text();

        // Ekstrak hanya bagian <div id="tableContainer"> dari HTML baru
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newContainer = doc.getElementById('tableContainer');

        if (newContainer) {
            container.innerHTML = newContainer.innerHTML;
        }
        
        container.style.opacity = '1'; // Kembalikan opacity
        attachPaginationEvents(); // Pasang event click lagi untuk tombol halaman (pagination)

    } catch (err) {
        console.error("Gagal memuat tabel:", err);
        window.location.href = url; // Fallback jika AJAX error
    }
}

// ════════════════════════════════════════════════════════════════════════════
// SEARCH (Otomatis saat ngetik)
// ════════════════════════════════════════════════════════════════════════════

let _searchTimer = null;
function onSearchInput(val) {
    clearTimeout(_searchTimer);
    _searchTimer = setTimeout(() => {
        const url = new URL(window.location.href);
        url.searchParams.set('search', val);
        url.searchParams.set('page', 1);
        url.searchParams.delete('open_sales'); // Cegah modal terbuka otomatis
        updateTableHTML(url.toString()); // Gunakan AJAX!
    }, 400); // 400ms delay setelah berhenti ngetik
}

// ════════════════════════════════════════════════════════════════════════════
// FILTER BY / SORT BY
// ════════════════════════════════════════════════════════════════════════════

function trxNavigate(params) {
    const url = new URL(window.location.href);
    Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
    if(params.page === undefined) url.searchParams.set('page', 1);
    url.searchParams.delete('open_sales'); // Cegah modal terbuka otomatis
    updateTableHTML(url.toString()); // Gunakan AJAX!
}

function setTrxStatus(val) { trxNavigate({ status: val }); }
function setTrxSort(val)   { trxNavigate({ sort_by: val }); }
function toggleTrxOrder() { 
    const url = new URL(window.location.href);
    const current = url.searchParams.get('sort_order') || 'DESC'; 
    const nextOrder = current === 'ASC' ? 'DESC' : 'ASC';
    
    const icon = document.getElementById('trxSortIcon');
    if(icon) icon.innerHTML = nextOrder === 'ASC'
        ? '<path d="M12 19V5M5 12l7-7 7 7"/>'
        : '<path d="M12 5v14M19 12l-7 7-7-7"/>';

    trxNavigate({ sort_order: nextOrder });
}

// ════════════════════════════════════════════════════════════════════════════
// EVENT LISTENER AWAL
// ════════════════════════════════════════════════════════════════════════════

// Fungsi agar klik nomor halaman (Pagination) pakai AJAX juga
function attachPaginationEvents() {
    const links = document.querySelectorAll('.pagination-container a.page-link');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            updateTableHTML(this.href);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    attachPaginationEvents();

    // Buka modal jika ada param open_sales di URL
    const id = new URLSearchParams(window.location.search).get('open_sales');
    if (id) openDetailModal(parseInt(id));
});