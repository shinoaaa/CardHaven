// ════════════════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════════════════

function escHtml(str) {
    if (str == null) return '-';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function showModal(html) {
    const modal = document.getElementById('eventModal');
    const body  = document.getElementById('eventModalBody');
    body.innerHTML = html;
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

async function eePost(action, payload) {
    const res = await fetch(EDIT_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action,
            ...payload
        })
    });
    return await res.json();
}

// ════════════════════════════════════════════════════════════════════════════
// CLOSE — satu fungsi, aware semua mode
// ════════════════════════════════════════════════════════════════════════════

let isEditMode  = false;
let aeIsAddMode = false;

function closeEventModal(e) {
    if (e && e.target !== e.currentTarget) return;

    const modalEl = document.getElementById('eventModal');

    // ── Mode Add ──────────────────────────────────────────────────────────
    if (aeIsAddMode) {
        if (!_aeHasAnyInput()) {
            _aeForceClose();
            return;
        }

        modalEl.classList.remove('show');
        cardhavenConfirm(
            'Cancel Add Event?',
            'Data yang sudah diisi akan hilang.',
            'Yes, Exit',
            () => { _aeForceClose(); },
            () => { modalEl.classList.add('show'); }
        );
        return;
    }

    // ── Mode Edit ─────────────────────────────────────────────────────────
    if (isEditMode) {
        modalEl.classList.remove('show');
        cardhavenConfirm(
            'Cancel Edit?',
            'Confirm to Quit? Your Changes May Not Be Saved.',
            'Yes, Exit',
            () => {
                isEditMode = false;
                document.getElementById('eventModalBody').innerHTML = '';
                document.body.style.overflow = '';
            },
            () => { modalEl.classList.add('show'); }
        );
        return;
    }

    // ── Mode normal (detail / kosong) ─────────────────────────────────────
    modalEl.classList.remove('show');
    document.getElementById('eventModalBody').innerHTML = '';
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeEventModal();
});

// ════════════════════════════════════════════════════════════════════════════
// DETAIL & EDIT
// ════════════════════════════════════════════════════════════════════════════

const VIEW_URL     = '/cardhaven/interface/event/components/detailEvent.php';
const ADD_VIEW_URL = '/cardhaven/interface/event/components/addEvent.php';
const ADD_API_URL  = '/cardhaven/interface/event/controller/controllerAdd.php';
const SEARCH_URL   = '/cardhaven/interface/event/apiFetch.php';
const EDIT_URL     = '/cardhaven/interface/event/controller/controllerEdit.php';
const FINISH_URL   = '/cardhaven/interface/event/controller/controller_complete_event.php';

async function openEventModal(id) {
    showModal('<p style="text-align:center;padding:20px;">Loading...</p>');
    try {
        const res = await fetch(`${VIEW_URL}?id=${id}&type=detail`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        showModal(await res.text());
    } catch (err) {
        showModal('<p style="text-align:center;color:#E74C3C;">Gagal memuat data detail.</p>');
        console.error('[Event System]', err);
    }
}

async function openEditModal(id) {
    showModal('<p style="text-align:center;padding:20px;">Loading...</p>');
    try {
        const res = await fetch(`${VIEW_URL}?id=${id}&type=edit`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        showModal(await res.text());
        isEditMode = true;
    } catch (err) {
        showModal('<p style="text-align:center;color:#E74C3C;">Gagal memuat form edit.</p>');
        console.error('[Event System]', err);
    }
}

// ════════════════════════════════════════════════════════════════════════════
// ADD EVENT
// ════════════════════════════════════════════════════════════════════════════

let aeProductList   = [];
let aeSearchTimeout = null;

async function openAddEventModal() {
    aeIsAddMode   = false;
    aeProductList = [];

    showModal('<p style="text-align:center;padding:30px;">Loading...</p>');
    try {
        const res = await fetch(ADD_VIEW_URL);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        showModal(await res.text());
        aeIsAddMode = true;
    } catch (err) {
        showModal('<p style="text-align:center;color:#e74c3c;">Gagal memuat form.</p>');
        console.error('[Add Event]', err);
    }
}

function _aeForceClose() {
    aeIsAddMode   = false;
    aeProductList = [];
    document.getElementById('eventModal').classList.remove('show');
    document.getElementById('eventModalBody').innerHTML = '';
    document.body.style.overflow = '';
}

function _aeHasAnyInput() {
    const fields = [
        'ae_nama_event', 'ae_tipe_event', 'ae_tanggal_mulai',
        'ae_tanggal_berakhir', 'ae_persen_diskon', 'ae_maks_pembelian'
    ];
    for (const id of fields) {
        const el = document.getElementById(id);
        if (el && el.value.trim() !== '') return true;
    }
    return aeProductList.length > 0;
}

function aeOnTypeChange() {
    const type      = document.getElementById('ae_tipe_event').value;
    const rowSampai = document.getElementById('ae_row_tanggal_sampai');

    rowSampai.style.display = (type === 'preorder') ? '' : 'none';
    if (type === 'preorder' && aeProductList.length > 1) {
        aeProductList = [aeProductList[0]];
        aeRenderProductTable();
    }
}

function aeOnStartDateChange() {
    const startVal = document.getElementById('ae_tanggal_mulai').value;
    const endInput = document.getElementById('ae_tanggal_berakhir');
    if (startVal) {
        endInput.min = startVal;
        if (endInput.value && endInput.value < startVal) {
            endInput.value = '';
        }
    }
}

function aeDebounceSearch() {
    clearTimeout(aeSearchTimeout);
    aeSearchTimeout = setTimeout(aeDoSearch, 280);
}

async function aeDoSearch() {
    const q   = document.getElementById('ae_search_produk').value.trim();
    const box = document.getElementById('ae_search_results');
    
    if (q.length < 1) {
        box.classList.remove('open');
        box.innerHTML = '';
        return;
    }

    try {
        const res  = await fetch(`${SEARCH_URL}?action=search_produk&q=${encodeURIComponent(q)}`);
        const list = await res.json();

        if (!Array.isArray(list) || list.length === 0) {
            box.innerHTML = '<div class="ae-search-item" style="color:#aaa;">No products found</div>';
            box.classList.add('open');
            return;
        }

        box.innerHTML = list.map(p => {
            const safeName = JSON.stringify(p.nama_produk).replace(/"/g, '&quot;');
            return `
            <div class="ae-search-item"
                 onclick="aeSelectProduct(${p.id_produk}, ${safeName}, ${p.harga_jual}, ${p.stok})">
                <div>
                    <div class="ae-search-item-name">${escHtml(p.nama_produk)}</div>
                    <div class="ae-search-item-type">${escHtml(p.tipe_produk)}</div>
                </div>
                <div class="ae-search-item-price">Rp ${Number(p.harga_jual).toLocaleString('id-ID')}</div>
            </div>
            `;
        }).join('');
        
        box.classList.add('open');
    } catch (err) {
        console.error('[Search Produk]', err);
    }
}

let aeSelectedMaxStok = 0; 

function aeSelectProduct(id, nama, hargaJual, stok) {
    document.getElementById('ae_selected_id_produk').value  = id;
    document.getElementById('ae_selected_harga_jual').value = hargaJual;
    document.getElementById('ae_search_produk').value       = nama;
    document.getElementById('ae_stok_event').value          = '';
    
    aeSelectedMaxStok = parseInt(stok, 10) || 0; 
    
    aeRecalcHarga();
    
    document.getElementById('ae_search_results').classList.remove('open');
    document.getElementById('ae_search_results').innerHTML  = '';
}

document.addEventListener('input', function (e) {
    if (e.target && e.target.id === 'ae_persen_diskon') {
        aeRecalcHarga();
        
        const currentDiskon = parseFloat(e.target.value);
        if (!isNaN(currentDiskon)) {
            aeProductList.forEach(p => {
                p.harga_event = Math.round(((100 + currentDiskon) * p.harga_jual) / 100);
            });
            aeRenderProductTable();
        }
    }
});

function aeRecalcHarga() {
    const hargaJual  = parseFloat(document.getElementById('ae_selected_harga_jual')?.value ?? '');
    const diskon     = parseFloat(document.getElementById('ae_persen_diskon')?.value        ?? '');
    const inputHarga = document.getElementById('ae_harga_event');
    if (!inputHarga) return;

    if (isNaN(hargaJual) || isNaN(diskon)) {
        inputHarga.value = '';
        return;
    }

    const hargaEvent = ((100 + diskon) * hargaJual) / 100;
    inputHarga.value = 'Rp ' + Math.round(hargaEvent).toLocaleString('id-ID');
}

document.addEventListener('click', function (e) {
    const wrap = document.getElementById('ae_search_produk');
    const box  = document.getElementById('ae_search_results');
    if (box && wrap && !wrap.contains(e.target) && !box.contains(e.target)) {
        box.classList.remove('open');
    }
});

// ── Add produk ke list ────────────────────────────────────────────────────────

function aeAddProductToList() {
    const tipe       = document.getElementById('ae_tipe_event')?.value              ?? '';
    const idProduk   = document.getElementById('ae_selected_id_produk')?.value      ?? '';
    const namaProduk = document.getElementById('ae_search_produk')?.value.trim()    ?? '';
    const hargaJual  = parseFloat(document.getElementById('ae_selected_harga_jual')?.value ?? '');
    const diskon     = parseFloat(document.getElementById('ae_persen_diskon')?.value       ?? '');
    const stok       = parseInt(document.getElementById('ae_stok_event')?.value            ?? '');
    const errProduk  = document.getElementById('err_produk');

    if (errProduk) errProduk.textContent = '';
    
    if (isNaN(diskon)) {
        if (errProduk) errProduk.textContent = 'Isi Discount (%) di form atas terlebih dahulu!';
        return;
    }

    if (!idProduk) {
        if (errProduk) errProduk.textContent = 'Pilih produk dari pencarian terlebih dahulu.';
        return;
    }
    if (isNaN(stok) || stok < 1) {
        if (errProduk) errProduk.textContent = 'Stock harus lebih dari 0.';
        return;
    }
    if (stok > aeSelectedMaxStok) {
        if (errProduk) errProduk.textContent = `Stock tidak boleh melebihi sisa di database (${aeSelectedMaxStok}).`;
        return;
    }
    if (aeProductList.some(p => p.id_produk == idProduk)) {
        if (errProduk) errProduk.textContent = 'Produk ini sudah ada di daftar.';
        return;
    }
    if (tipe === 'preorder' && aeProductList.length >= 1) {
        if (errProduk) errProduk.textContent = 'Event Pre-Order hanya boleh memiliki 1 produk.';
        return;
    }

    const hargaEvent = Math.round(((100 + diskon) * hargaJual) / 100);
        
    aeProductList.push({ 
        id_produk: idProduk, 
        nama_produk: namaProduk, 
        harga_jual: hargaJual, 
        harga_event: hargaEvent, 
        stok_event: stok 
    });
    
    aeRenderProductTable();

    document.getElementById('ae_selected_id_produk').value  = '';
    document.getElementById('ae_selected_harga_jual').value = '';
    document.getElementById('ae_search_produk').value       = '';
    document.getElementById('ae_stok_event').value          = '';
    document.getElementById('ae_harga_event').value         = '';
    aeSelectedMaxStok = 0;
}

function aeRemoveProduct(index) {
    aeProductList.splice(index, 1);
    aeRenderProductTable();
}

function aeRenderProductTable() {
    const wrap  = document.getElementById('ae_product_table_wrap');
    const tbody = document.getElementById('ae_product_tbody');
    if (!wrap || !tbody) return;
    
    if (aeProductList.length === 0) {
        wrap.style.display = 'none';
        tbody.innerHTML    = '';
        return;
    }

    wrap.style.display = '';
    wrap.style.marginLeft = '3rem';
    
    tbody.innerHTML = aeProductList.map((p, i) => {
        const subtotal = Math.round(p.harga_event * p.stok_event);
        return `
            <tr>
                <td>${i + 1}</td>
                <td style="font-weight:600;">${escHtml(p.nama_produk)}</td>
                <td>Rp ${Math.round(p.harga_event).toLocaleString('id-ID')}</td>
                <td>${p.stok_event}</td>
                <td><button class="ae-btn-del-prod" onclick="aeRemoveProduct(${i})" title="Hapus">🗑</button></td>
            </tr>
        `;
    }).join('');
}

// ── Validasi & Submit Add ──────────────────────────────────────────────────────

function aeClearErrors() {
    document.querySelectorAll('.ae-error').forEach(el => el.textContent = '');
    document.querySelectorAll('.ae-input').forEach(el => el.classList.remove('ae-error-border'));
}

function aeSetError(fieldId, errId, msg) {
    const input = document.getElementById(fieldId);
    const err   = document.getElementById(errId);
    if (input) input.classList.add('ae-error-border');
    if (err)   err.textContent = msg;
}

async function aeSubmitEvent() {
    aeClearErrors();

    const nama     = document.getElementById('ae_nama_event')?.value.trim()     ?? '';
    const tipe     = document.getElementById('ae_tipe_event')?.value            ?? '';
    const mulai    = document.getElementById('ae_tanggal_mulai')?.value         ?? '';
    const berakhir = document.getElementById('ae_tanggal_berakhir')?.value      ?? '';
    const sampai   = document.getElementById('ae_tanggal_sampai')?.value        ?? '';
    const diskon   = document.getElementById('ae_persen_diskon')?.value.trim()  ?? '';
    const maks     = document.getElementById('ae_maks_pembelian')?.value.trim() ?? '';
    
    let valid = true;

    if (!nama)                                  { aeSetError('ae_nama_event',       'err_nama_event',       'Event name wajib diisi.'); valid = false; }
    if (!tipe)                                  { aeSetError('ae_tipe_event',       'err_tipe_event',       'Event type wajib dipilih.'); valid = false; }
    if (!mulai)                                 { aeSetError('ae_tanggal_mulai',    'err_tanggal_mulai',    'Start date wajib diisi.'); valid = false; }
    if (!berakhir)                              { aeSetError('ae_tanggal_berakhir', 'err_tanggal_berakhir', 'End date wajib diisi.'); valid = false; }
    if (mulai && berakhir && berakhir < mulai)   { aeSetError('ae_tanggal_berakhir', 'err_tanggal_berakhir', 'End date tidak boleh sebelum start date.'); valid = false; }
    if (diskon === '')                          { aeSetError('ae_persen_diskon',    'err_persen_diskon',    'Discount wajib diisi.'); valid = false; }
    if (!maks || parseInt(maks) <= 0)           { aeSetError('ae_maks_pembelian',   'err_maks_pembelian',   'Max purchase harus lebih dari 0.'); valid = false; }
    if (tipe === 'preorder' && !sampai)         { aeSetError('ae_tanggal_sampai',   'err_tanggal_sampai',   'Estimated arrival wajib diisi untuk Pre-Order.'); valid = false; }
    if (aeProductList.length === 0) {
        const el = document.getElementById('err_product_list');
        if (el) el.textContent = 'Minimal 1 produk harus ditambahkan.';
        valid = false;
    }

    if (!valid) return;

    const payload = {
        nama_event:       nama,
        tipe_event:       tipe,
        tanggal_mulai:    mulai,
        tanggal_berakhir: berakhir,
        tanggal_sampai:   sampai || null,
        persen_diskon:    parseFloat(diskon),
        maks_pembelian:   parseInt(maks, 10),
        id_karyawan:      sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna'),
        products: aeProductList.map(p => ({
            id_produk:   p.id_produk,
            harga_event: Math.round(((100 + parseFloat(diskon)) * p.harga_jual) / 100),
            stok_event:  p.stok_event
        }))
    };
    
    try {
        const res = await fetch(ADD_API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            Swal.fire({
                icon: "success",
                title: "Completed",
                text: "The event has been added successfully."
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: "error",
                title: "Failed",
                text: data.error || "Unable to add the event."
            });
        }
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Unable to send Data"
        });
        console.error('[Submit Event]', err);
    }
}

// ════════════════════════════════════════════════════════════════════════════
// EDIT EVENT
// ════════════════════════════════════════════════════════════════════════════

let eeSearchTimeout = null;
let eeSelectedMaxStok = 0;

function eeOnTypeChange() {
    const type      = document.getElementById('ee_tipe_event').value;
    const rowSampai = document.getElementById('ee_row_tanggal_sampai');
    if (rowSampai) {
        rowSampai.style.display = (type === 'preorder') ? '' : 'none';
    }
}

function eeOnStartDateChange() {
    const startVal = document.getElementById('ee_tanggal_mulai').value;
    const endInput = document.getElementById('ee_tanggal_berakhir');

    if (startVal && endInput) {
        endInput.min = startVal;
        if (endInput.value && endInput.value < startVal) {
            endInput.value = '';
        }
    }
}

function eeDebounceSearch() {
    clearTimeout(eeSearchTimeout);
    eeSearchTimeout = setTimeout(eeDoSearch, 280);
}

async function eeDoSearch() {
    const q   = document.getElementById('ee_search_produk').value.trim();
    const box = document.getElementById('ee_search_results');

    if (q.length < 1) {
        box.classList.remove('open');
        box.innerHTML = '';
        return;
    }

    try {
        const res  = await fetch(`${SEARCH_URL}?action=search_produk&q=${encodeURIComponent(q)}`);
        const list = await res.json();

        if (!Array.isArray(list) || list.length === 0) {
            box.innerHTML = '<div class="ee-search-item" style="color:#aaa;">No products found</div>';
            box.classList.add('open');
            return;
        }

        box.innerHTML = list.map(p => {
            const safeName = JSON.stringify(p.nama_produk).replace(/"/g, '&quot;');
            return `
                <div class="ee-search-item"
                     onclick="eeSelectProduct(${p.id_produk}, ${safeName}, ${p.harga_jual}, ${p.stok})">
                    <div>
                        <div class="ee-search-item-name">${escHtml(p.nama_produk)}</div>
                        <div class="ee-search-item-type">${escHtml(p.tipe_produk)}</div>
                    </div>
                    <div class="ee-search-item-price">Rp ${Number(p.harga_jual).toLocaleString('id-ID')}</div>
                </div>
            `;
        }).join('');

        box.classList.add('open');
    } catch (err) {
        console.error('[Edit Search Produk]', err);
    }
}

function eeSelectProduct(id, nama, hargaJual, stok) {
    document.getElementById('ee_selected_id_produk').value  = id;
    document.getElementById('ee_selected_harga_jual').value = hargaJual;
    document.getElementById('ee_search_produk').value       = nama;
    document.getElementById('ee_stok_event').value          = '';
    eeSelectedMaxStok = parseInt(stok, 10) || 0;
    eeRecalcHarga();

    const box = document.getElementById('ee_search_results');
    box.classList.remove('open');
    box.innerHTML = '';
}

function eeRecalcHarga() {
    const hargaJual  = parseFloat(document.getElementById('ee_selected_harga_jual')?.value ?? '');
    const diskon     = parseFloat(document.getElementById('ee_persen_diskon')?.value ?? '');
    const inputHarga = document.getElementById('ee_harga_event');
    if (!inputHarga) return;

    if (isNaN(hargaJual) || isNaN(diskon)) {
        inputHarga.value = '';
        return;
    }

    const hargaEvent = ((100 + diskon) * hargaJual) / 100;
    inputHarga.value = 'Rp ' + Math.round(hargaEvent).toLocaleString('id-ID');
}

function eeClearErrors() {
    document.querySelectorAll('.ee-error').forEach(el => el.textContent = '');
    document.querySelectorAll('.ee-input').forEach(el => el.classList.remove('ee-error-border'));
}

function eeSetError(fieldId, errId, msg) {
    const input = document.getElementById(fieldId);
    const err   = document.getElementById(errId);
    if (input) input.classList.add('ee-error-border');
    if (err)   err.textContent = msg;
}

document.addEventListener('click', function (e) {
    const wrap = document.getElementById('ee_search_produk');
    const box  = document.getElementById('ee_search_results');
    if (box && wrap && !wrap.contains(e.target) && !box.contains(e.target)) {
        box.classList.remove('open');
    }
});

async function eeAddProductToList(idEvent) {
    const tipe       = document.getElementById('ee_tipe_event')?.value           ?? '';
    const idProduk   = document.getElementById('ee_selected_id_produk')?.value   ?? '';
    const hargaJual  = parseFloat(document.getElementById('ee_selected_harga_jual')?.value ?? '');
    const diskon     = parseFloat(document.getElementById('ee_persen_diskon')?.value ?? '');
    const stok       = parseInt(document.getElementById('ee_stok_event')?.value ?? '');
    const errProduk  = document.getElementById('ee_err_produk');

    if (errProduk) errProduk.textContent = '';

    if (isNaN(diskon)) {
        if (errProduk) errProduk.textContent = 'Isi Discount (%) di form atas terlebih dahulu!';
        return;
    }
    if (!idProduk) {
        if (errProduk) errProduk.textContent = 'Pilih produk dari pencarian terlebih dahulu.';
        return;
    }
    if (isNaN(stok) || stok < 1) {
        if (errProduk) errProduk.textContent = 'Stock harus lebih dari 0.';
        return;
    }
    if (stok > eeSelectedMaxStok) {
        if (errProduk) errProduk.textContent = `Stock tidak boleh melebihi sisa di database (${eeSelectedMaxStok}).`;
        return;
    }
    if (tipe === 'preorder') {
        const rows = document.querySelectorAll('#ee_product_tbody tr');
        if (rows.length >= 1) {
            if (errProduk) errProduk.textContent = 'Event Pre-Order hanya boleh memiliki 1 produk.';
            return;
        }
    }

    const payload = {
        id_event: idEvent,
        id_produk: parseInt(idProduk, 10),
        harga_event: Math.round(((100 + diskon) * hargaJual) / 100),
        stok_event: stok
    };

    try {
        const data = await eePost('add_product', payload);

        if (data.success) {
            Swal.fire({
                icon: "success",
                title: "Completed",
                text: "The product has been added to this event."
            }).then(() => location.reload());
        } else {
            Swal.fire({
                icon: "error",
                title: "Failed",
                text: data.error || "Unable to add product."
            });
        }
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Unable to send data"
        });
        console.error('[EE Add Product]', err);
    }
}

async function eeEditStock(idProdukEvent, currentStok) {
    const { value: newStok } = await Swal.fire({
        title: 'Edit Stock',
        input: 'number',
        inputValue: currentStok,
        inputAttributes: { min: 1 },
        showCancelButton: true,
        confirmButtonText: 'Save',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value || parseInt(value) <= 0) return 'Stock harus lebih dari 0.';
            return null;
        }
    });

    if (!newStok) return;

    try {
        const data = await eePost('update_stock', {
            id_produk_event: idProdukEvent,
            stok_event: parseInt(newStok, 10)
        });

        if (data.success) {
            Swal.fire({
                icon: "success",
                title: "Completed",
                text: "Stock has been updated."
            }).then(() => location.reload());
        } else {
            Swal.fire({
                icon: "error",
                title: "Failed",
                text: data.error || "Unable to update stock."
            });
        }
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Something went wrong while processing the request."
        });
    }
}

function eeRemoveProductFromEvent(idProdukEvent) {
    cardhavenConfirm(
        "Remove this product?",
        "This product will be removed from this event.",
        "Yes, remove it",
        async () => {
            try {
                const data = await eePost('delete_product', {
                    id_produk_event: idProdukEvent
                });

                if (data.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Completed",
                        text: "The product has been removed from the event."
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Failed",
                        text: data.error || "Unable to remove product."
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Something went wrong while processing the request."
                });
            }
        }
    );
}

async function eeSubmitEvent(idEvent) {
    eeClearErrors();

    const nama     = document.getElementById('ee_nama_event')?.value.trim()     ?? '';
    const tipe     = document.getElementById('ee_tipe_event')?.value            ?? '';
    const mulai    = document.getElementById('ee_tanggal_mulai')?.value         ?? '';
    const berakhir = document.getElementById('ee_tanggal_berakhir')?.value      ?? '';
    const sampai   = document.getElementById('ee_tanggal_sampai')?.value        ?? '';
    const diskon   = document.getElementById('ee_persen_diskon')?.value.trim()  ?? '';
    const maks     = document.getElementById('ee_maks_pembelian')?.value.trim() ?? '';

    let valid = true;

    if (!nama)                                { eeSetError('ee_nama_event',       'ee_err_nama_event',       'Event name wajib diisi.'); valid = false; }
    if (!tipe)                                { eeSetError('ee_tipe_event',       'ee_err_tipe_event',       'Event type wajib dipilih.'); valid = false; }
    if (!mulai)                               { eeSetError('ee_tanggal_mulai',    'ee_err_tanggal_mulai',    'Start date wajib diisi.'); valid = false; }
    if (!berakhir)                            { eeSetError('ee_tanggal_berakhir', 'ee_err_tanggal_berakhir', 'End date wajib diisi.'); valid = false; }
    if (mulai && berakhir && berakhir < mulai) { eeSetError('ee_tanggal_berakhir', 'ee_err_tanggal_berakhir', 'End date tidak boleh sebelum start date.'); valid = false; }
    if (diskon === '')                        { eeSetError('ee_persen_diskon',    'ee_err_persen_diskon',    'Discount wajib diisi.'); valid = false; }
    if (!maks || parseInt(maks) <= 0)         { eeSetError('ee_maks_pembelian',   'ee_err_maks_pembelian',   'Max purchase harus lebih dari 0.'); valid = false; }
    if (tipe === 'preorder' && !sampai)       { eeSetError('ee_tanggal_sampai',   'ee_err_tanggal_sampai',   'Estimated arrival wajib diisi untuk Pre-Order.'); valid = false; }

    if (!valid) return;

    const payload = {
        id_event: idEvent,
        nama_event: nama,
        tipe_event: tipe,
        tanggal_mulai: mulai,
        tanggal_berakhir: berakhir,
        tanggal_sampai: sampai || null,
        persen_diskon: parseFloat(diskon),
        maks_pembelian: parseInt(maks, 10)
    };

    try {
        const data = await eePost('save_event', payload);

        if (data.success) {
            Swal.fire({
                icon: "success",
                title: "Completed",
                text: "The event has been updated successfully."
            }).then(() => location.reload());
        } else {
            Swal.fire({
                icon: "error",
                title: "Failed",
                text: data.error || "Unable to update the event."
            });
        }
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Unable to send data"
        });
        console.error('[EE Submit Event]', err);
    }
}

// ════════════════════════════════════════════════════════════════════════════
// COMPLETE EVENT
// ════════════════════════════════════════════════════════════════════════════

function completeEvent(idEvent) {
    cardhavenConfirm(
        "Complete this event?",
        "This event will be marked as completed earlier. It will stop running, but it will not be deleted.",
        "Yes, complete it",
        () => {
            fetch(FINISH_URL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    id_event: idEvent
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Completed",
                        text: "The event has been marked as completed."
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Failed",
                        text: data.error || "Unable to complete the event."
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Something went wrong while processing the request."
                });
            });
        }
    );
}