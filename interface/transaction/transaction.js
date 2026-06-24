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
    return sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna') || 0;
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

        const h = data.header;
        const st = parseInt(h.status_penjualan);

        // Tentukan tombol aksi yang tampil
        let actionBtns = '';

        if (st === 0) {
            actionBtns = `
                <button class="btn-trx-action btn-confirm" onclick="doAction('konfirmasi_bayar', ${id_penjualan})">
                    ✅ Konfirmasi Pembayaran
                </button>
                <button class="btn-trx-action btn-cancel" onclick="doAction('cancel', ${id_penjualan})">
                    ❌ Tolak / Cancel
                </button>`;
        } else if (st === 1 || st === 2) {
            actionBtns = `
                <button class="btn-trx-action btn-process" onclick="doAction('proses', ${id_penjualan})">
                    ⚙️ Proses Order
                </button>
                <button class="btn-trx-action btn-cancel" onclick="doAction('cancel', ${id_penjualan})">
                    ❌ Cancel
                </button>`;
        } else if (st === 3) {
            actionBtns = `
                <button class="btn-trx-action btn-ship" onclick="openShipModal(${id_penjualan})">
                    🚚 Kirim Paket
                </button>`;
        } else if (st === 4) {
            actionBtns = `
                <button class="btn-trx-action btn-deliver" onclick="doAction('delivered', ${id_penjualan})">
                    🏠 Set Delivered
                </button>`;
        }

        // Bukti bayar
        const buktiHtml = h.bukti_pembayaran
            ? `<a href="/CardHaven/image-profile/${h.bukti_pembayaran}" target="_blank">
                <img src="/CardHaven/image-profile/${h.bukti_pembayaran}"
                    style="max-width:180px;max-height:130px;border-radius:8px;border:1px solid rgba(255,255,255,.15);object-fit:cover;cursor:pointer;">
               </a>`
            : `<span style="opacity:.45;font-size:.8rem;">Belum ada bukti</span>`;

        // Item rows
        const itemsHtml = (data.items || []).map(item => `
            <tr>
                <td style="display:flex;align-items:center;gap:.6rem;padding:.5rem 0;">
                    <img src="/CardHaven/image-profile/${item.foto || 'defaultProduct.jpg'}"
                        style="width:40px;height:40px;border-radius:6px;object-fit:cover;flex-shrink:0;">
                    <div>
                        <div style="font-weight:600;font-size:.85rem;">${item.nama_produk ?? '-'}</div>
                        <div style="font-size:.72rem;opacity:.55;">${item.tipe_produk ?? ''} · ${item.kondisi ?? ''}</div>
                    </div>
                </td>
                <td style="text-align:right;white-space:nowrap;">Rp ${item.harga_produk}</td>
                <td style="text-align:center;">${item.jumlah_barang}</td>
                <td style="text-align:right;white-space:nowrap;font-weight:700;">Rp ${item.subtotal_harga}</td>
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
                    <div class="trx-info-row"><span>No. Telepon</span><b>${h.no_telepon ?? '-'}</b></div>
                    <div class="trx-info-row"><span>Alamat</span><b>${h.alamat ?? '-'}</b></div>
                </div>

                <!-- Pembayaran -->
                <div class="trx-modal-section">
                    <div class="trx-section-title">Pembayaran</div>
                    <div class="trx-info-row"><span>Metode</span><b>${h.nama_metode ?? '-'}</b></div>
                    <div class="trx-info-row"><span>Provider</span><b>${h.provider ?? '-'}</b></div>
                    <div class="trx-info-row"><span>No. Rekening</span><b>${h.no_rekening ?? '-'}</b></div>
                    <div class="trx-info-row"><span>Atas Nama</span><b>${h.atas_nama ?? '-'}</b></div>
                    <div class="trx-info-row"><span>Biaya Admin</span><b>Rp ${h.biaya_admin}</b></div>
                </div>

                <!-- Pengiriman -->
                <div class="trx-modal-section">
                    <div class="trx-section-title">Pengiriman</div>
                    <div class="trx-info-row"><span>No. Resi</span><b>${h.no_resi ?? '-'}</b></div>
                    <div class="trx-info-row"><span>Tgl Kirim</span><b>${h.tanggal_pengiriman ?? '-'}</b></div>
                </div>

                <!-- Bukti Bayar -->
                <div class="trx-modal-section">
                    <div class="trx-section-title">Bukti Pembayaran</div>
                    ${buktiHtml}
                </div>
            </div>

            <!-- Items -->
            <div class="trx-section-title" style="margin-top:1.25rem;">Items</div>
            <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
                <thead>
                    <tr style="border-bottom:1px solid rgba(255,255,255,.1);opacity:.55;">
                        <th style="text-align:left;padding-bottom:.4rem;">Produk</th>
                        <th style="text-align:right;">Harga</th>
                        <th style="text-align:center;">Qty</th>
                        <th style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>${itemsHtml}</tbody>
            </table>

            <div style="display:flex;justify-content:flex-end;margin-top:.75rem;padding-top:.75rem;border-top:1px solid rgba(255,255,255,.1);">
                <span style="font-size:.85rem;opacity:.65;margin-right:.5rem;">${h.total_barang} item · Total</span>
                <span style="font-size:1rem;font-weight:800;color:var(--primary-color);">Rp ${h.total_harga}</span>
            </div>

            ${actionBtns ? `<div class="trx-action-row">${actionBtns}</div>` : ''}
        `;
    } catch (e) {
        body.innerHTML = `<p style="color:red;">Gagal memuat data.</p>`;
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
            <div class="trx-modal-id">Kirim Order #${id_penjualan}</div>
        </div>
        <div class="trx-modal-section" style="margin-top:1rem;">
            <div class="trx-section-title">Detail Pengiriman</div>
            <div style="margin-bottom:.85rem;">
                <label style="display:block;font-size:.8rem;opacity:.65;margin-bottom:.3rem;">No. Resi *</label>
                <input id="inputResi" type="text" placeholder="Contoh: JNE1234567890"
                    style="width:100%;padding:.6rem .85rem;border-radius:8px;border:1px solid rgba(255,255,255,.2);
                    background:rgba(255,255,255,.07);color:inherit;font-size:.9rem;box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block;font-size:.8rem;opacity:.65;margin-bottom:.3rem;">Tanggal Pengiriman</label>
                <input id="inputTglKirim" type="date" value="${today}"
                    style="width:100%;padding:.6rem .85rem;border-radius:8px;border:1px solid rgba(255,255,255,.2);
                    background:rgba(255,255,255,.07);color:inherit;font-size:.9rem;box-sizing:border-box;">
            </div>
        </div>
        <div class="trx-action-row">
            <button class="btn-trx-action btn-ship" onclick="submitKirim(${id_penjualan})">
                🚚 Konfirmasi Kirim
            </button>
            <button class="btn-trx-action" style="background:rgba(255,255,255,.08);" onclick="openDetailModal(${id_penjualan})">
                ← Kembali
            </button>
        </div>
    `;
}

async function submitKirim(id_penjualan) {
    const no_resi   = document.getElementById('inputResi').value.trim();
    const tgl_kirim = document.getElementById('inputTglKirim').value;

    if (!no_resi) {
        cardhavenAlert('Peringatan', 'No. Resi wajib diisi!', 'warning');
        return;
    }

    const res  = await postAction('kirim', id_penjualan, { no_resi, tanggal_pengiriman: tgl_kirim });
    if (res.status === 'success') {
        cardhavenAlert('Berhasil', 'Paket berhasil dikonfirmasi dikirim.', 'success');
        closeTrxModal();
        setTimeout(() => location.reload(), 1200);
    } else {
        cardhavenAlert('Error', res.message ?? 'Gagal update status.', 'error');
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

async function doAction(action, id_penjualan) {
    const CONFIRM_MSG = {
        konfirmasi_bayar: ['Konfirmasi Pembayaran?', 'Pembayaran akan ditandai sudah diterima.', 'Ya, Konfirmasi'],
        proses:           ['Proses Order?', 'Order akan dipindahkan ke status Processing.', 'Ya, Proses'],
        delivered:        ['Set Delivered?', 'Order akan ditandai sebagai sudah tiba di customer.', 'Ya, Set'],
        cancel:           ['Cancel Order?', 'Stok produk akan dikembalikan. Tindakan ini tidak bisa diubah.', 'Ya, Cancel'],
    };

    const [title, text, btnText] = CONFIRM_MSG[action] ?? ['Konfirmasi?', '', 'Ya'];

    cardhavenConfirm(title, text, btnText, async () => {
        const res = await postAction(action, id_penjualan);
        if (res.status === 'success') {
            cardhavenAlert('Berhasil', 'Status berhasil diperbarui.', 'success');
            closeTrxModal();
            setTimeout(() => location.reload(), 1200);
        } else {
            cardhavenAlert('Error', res.message ?? 'Gagal update status.', 'error');
        }
    }, null);
}

// ════════════════════════════════════════════════════════════════════════════
// SEARCH (debounce)
// ════════════════════════════════════════════════════════════════════════════

let _searchTimer = null;

function onSearchInput(val) {
    clearTimeout(_searchTimer);
    _searchTimer = setTimeout(() => {
        const url = new URL(window.location.href);
        url.searchParams.set('search', val);
        url.searchParams.set('page', 1);
        window.location.href = url.toString();
    }, 500);
}
