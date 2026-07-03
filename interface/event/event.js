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
            'Any data you have already entered will be lost.',
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
const DELETE_URL   = '/cardhaven/interface/event/controller/controllerDeleteEvent.php';
const TOGGLE_URL   = '/cardhaven/interface/event/controller/controllerToggle.php';

async function openEventModal(id) {
    // showModal('<p style="text-align:center;padding:20px;">Loading...</p>');
    try {
        const res = await fetch(`${VIEW_URL}?id=${id}&type=detail`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        showModal(await res.text());
    } catch (err) {
        showModal('<p style="text-align:center;color:#E74C3C;">Failed to load detailed data.</p>');
        console.error('[Event System]', err);
    }
}

async function openEditModal(id) {
    // showModal('<p style="text-align:center;padding:20px;">Loading...</p>'); // 
    try {
        const res = await fetch(`${VIEW_URL}?id=${id}&type=edit`); 
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        showModal(await res.text()); // [cite: 19]
        isEditMode = true; 
        
        // PANGGIL DI SINI JUGA!
        setBatasTanggalMinimal();
        
    } catch (err) {
        showModal('<p style="text-align:center;color:#E74C3C;">Failed to load the edit form.</p>'); // [cite: 19]
        console.error('[Event System]', err); // [cite: 20]
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

    // showModal('<p style="text-align:center;padding:30px;">Loading...</p>');
    try {
        const res = await fetch(ADD_VIEW_URL); // 
        if (!res.ok) throw new Error(`HTTP ${res.status}`); // 
        showModal(await res.text()); // 
        aeIsAddMode = true; // 
        
        // PANGGIL DI SINI! (Setelah form masuk ke HTML)
        setBatasTanggalMinimal(); 
        
    } catch (err) {
        showModal('<p style="text-align:center;color:#e74c3c;">Failed to load the form.</p>'); // 
        console.error('[Add Event]', err); // [cite: 24]
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
        'ae_tanggal_berakhir','ae_tanggal_sampai', 'ae_persen_diskon', 'ae_maks_pembelian'
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

// ==========================================
// 1. SET MINIMAL TANGGAL SAAT HALAMAN DI-LOAD
// ==========================================
// Jadikan fungsi mandiri, jangan pakai DOMContentLoaded lagi
function setBatasTanggalMinimal() {
    const now = new Date();
    const yyyy = now.getFullYear();
    const mm = String(now.getMonth() + 1).padStart(2, '0');
    const dd = String(now.getDate()).padStart(2, '0');
    const hariIni = `${yyyy}-${mm}-${dd}`;

    // === BAGIAN ADD MODAL (ae) ===
    const aeStart = document.getElementById('ae_tanggal_mulai');
    const aeEnd   = document.getElementById('ae_tanggal_berakhir');
    if (aeStart) aeStart.min = hariIni;
    if (aeEnd) aeEnd.min = hariIni; 

    // === BAGIAN EDIT MODAL (ee) ===
    const eeStart = document.getElementById('ee_tanggal_mulai');
    const eeEnd   = document.getElementById('ee_tanggal_berakhir');
    const eeArrive = document.getElementById('ee_tanggal_sampai');

    if (eeStart) {
        // Kunci Start Date biar ga bisa milih tanggal sebelum hari ini
        eeStart.min = hariIni;
        
        // Ambil nilai tanggal bawaan database yang terisi saat modal dibuka
        const currentStartVal = eeStart.value; 
        
        if (currentStartVal) {
            // Pas modal kebuka, End Date & Arrive Date langsung dikunci minimal sesuai Start Date-nya!
            if (eeEnd) eeEnd.min = currentStartVal;
            if (eeArrive) eeArrive.min = currentStartVal;
        } else {
            // Kalau misal tanggal mulainya kosong, default-nya pakai hari ini
            if (eeEnd) eeEnd.min = hariIni;
            if (eeArrive) eeArrive.min = hariIni;
        }
    }
}

function aeOnStartDateChange() {
    const startVal = document.getElementById('ae_tanggal_mulai').value;
    const endInput = document.getElementById('ae_tanggal_berakhir');
    const arriveInput = document.getElementById('ae_tanggal_sampai');
    
    if (startVal) {
        if (endInput) endInput.min = startVal;
        if (arriveInput) arriveInput.min = startVal;
        
        // Reset kalau tanggal berakhir atau sampai malah lebih kecil dari tanggal mulai
        if ((endInput && endInput.value && endInput.value < startVal) || 
            (arriveInput && arriveInput.value && arriveInput.value < startVal)) {
            if (endInput) endInput.value = '';
            if (arriveInput) arriveInput.value = '';
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
                p.harga_event = Math.round(((100 - currentDiskon) * p.harga_jual) / 100);
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

    const hargaEvent = ((100 - diskon) * hargaJual) / 100;
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
        if (errProduk) errProduk.textContent = 'Fill in the "Discount (%)" field in the form above first!';
        return;
    }

    if (!idProduk) {
        if (errProduk) errProduk.textContent = 'Select a product from the search results first.';
        return;
    }
    if (isNaN(stok) || stok < 1) {
        if (errProduk) errProduk.textContent = 'The stock must be greater than 0.';
        return;
    }
    if (stok > aeSelectedMaxStok) {
        if (errProduk) errProduk.textContent = `Stock should not exceed the remaining quantity in the database (${aeSelectedMaxStok}).`;
        return;
    }
    if (aeProductList.some(p => p.id_produk == idProduk)) {
        if (errProduk) errProduk.textContent = 'This product is already on the list.';
        return;
    }
    if (tipe === 'preorder' && aeProductList.length >= 1) {
        if (errProduk) errProduk.textContent = 'A pre-order event may only feature 1 product.';
        return;
    }

    const hargaEvent = Math.round(((100- diskon) * hargaJual) / 100);
        
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
                <td><button class="ae-btn-del-prod" onclick="aeRemoveProduct(${i})" title="Delete">🗑</button></td>
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

    if (!nama)                                  { aeSetError('ae_nama_event',       'err_nama_event',       'Event name is required.'); valid = false; }
    if (!tipe)                                  { aeSetError('ae_tipe_event',       'err_tipe_event',       'Select event type.'); valid = false; }
    if (!mulai)                                 { aeSetError('ae_tanggal_mulai',    'err_tanggal_mulai',    'Start date is required.'); valid = false; }
    if (!berakhir)                              { aeSetError('ae_tanggal_berakhir', 'err_tanggal_berakhir', 'End date is required.'); valid = false; }
    if (mulai && berakhir && berakhir < mulai)  { aeSetError('ae_tanggal_berakhir', 'err_tanggal_berakhir', 'End date can not filled before start date.'); valid = false; }
    if (diskon === '')                          { aeSetError('ae_persen_diskon',    'err_persen_diskon',    'Discount is required.'); valid = false; }
    if (diskon < 1 )                            { aeSetError('ae_persen_diskon',    'err_persen_diskon',    'Discount can not less than 1.'); valid = false; }
    if (!maks || parseInt(maks) <= 0)           { aeSetError('ae_maks_pembelian',   'err_maks_pembelian',   'Max purchase can not less than 0.'); valid = false; }
    if (tipe === 'preorder' && !sampai)         { aeSetError('ae_tanggal_sampai',   'err_tanggal_sampai',   'Estimated arrival time are required for pre order.'); valid = false; }
    if (aeProductList.length === 0) {
        const el = document.getElementById('err_product_list');
        if (el) el.textContent = 'You should add at least 1 item.';
        valid = false;
    }

    if (!valid) return;

    // --- LOGIKA PENENTUAN STATUS ---
    // 1. Cek apakah format mulai adalah DD-MM-YYYY. Jika iya, ubah ke YYYY-MM-DD biar JS paham.
    let formattedMulai = mulai;
    if (mulai.includes('-') && mulai.split('-')[0].length === 2) {
        let parts = mulai.split('-'); // Hasilnya: [DD, MM, YYYY]
        formattedMulai = `${parts[2]}-${parts[1]}-${parts[0]}`; 
    }

    // 2. Buat objek Date untuk hari ini (waktu lokal laptop/hp user)
    const todayDate = new Date();
    todayDate.setHours(0, 0, 0, 0); // Reset jam ke 00:00:00 biar adil

    // 3. Buat objek Date untuk tanggal mulai event
    const eventDate = new Date(formattedMulai);
    eventDate.setHours(0, 0, 0, 0);

    // 4. Bandingkan secara matematis (Date object)
    const statusEvent = (eventDate > todayDate) ? 2 : 1;

    const payload = {
        nama_event:       nama,
        tipe_event:       tipe,
        tanggal_mulai:    mulai,
        tanggal_berakhir: berakhir,
        tanggal_sampai:   sampai || null,
        persen_diskon:    parseFloat(diskon),
        maks_pembelian:   parseInt(maks, 10),
        status_event:     statusEvent, // <-- Lempar status ke PHP
        id_karyawan:      sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna'),
        products: aeProductList.map(p => ({
            id_produk:   p.id_produk,
            harga_event: Math.round(((100 - parseFloat(diskon)) * p.harga_jual) / 100),
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
    const startInput = document.getElementById('ee_tanggal_mulai');
    if (!startInput) return; // Jaga-jaga kalau input start tidak ditemukan

    const startVal    = startInput.value;
    const endInput    = document.getElementById('ee_tanggal_berakhir');
    const arriveInput = document.getElementById('ee_tanggal_sampai');

    if (startVal) {
        // 1. Validasi untuk End Date (Tanggal Berakhir)
        if (endInput) {
            endInput.min = startVal;
            // Reset kalau nilai end date melanggar aturan (lebih kecil dari start date)
            if (endInput.value && endInput.value < startVal) {
                endInput.value = '';
            }
        }
        
        // 2. Validasi untuk Arrive Date (Tanggal Sampai / Estimasi)
        if (arriveInput) {
            arriveInput.min = startVal;
            // Reset juga nilai arrive date kalau melanggar aturan (lebih kecil dari start date)
            if (arriveInput.value && arriveInput.value < startVal) {
                arriveInput.value = '';
            }
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

    const hargaEvent = ((100 - diskon) * hargaJual) / 100;
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
        if (errProduk) errProduk.textContent = 'Fill in the "Discount (%)" field in the form above first!';
        return;
    }
    if (!idProduk) {
        if (errProduk) errProduk.textContent = 'First, select a product from the search results.';
        return;
    }
    if (isNaN(stok) || stok < 1) {
        if (errProduk) errProduk.textContent = 'The stock must be greater than 0.';
        return;
    }
    if (stok > eeSelectedMaxStok) {
        if (errProduk) errProduk.textContent = `Stock should not exceed the remaining quantity in the database (${eeSelectedMaxStok}).`;
        return;
    }
    if (tipe === 'preorder') {
        const rows = document.querySelectorAll('#ee_product_tbody tr');
        if (rows.length >= 1) {
            if (errProduk) errProduk.textContent = 'A pre-order event may only feature 1 product.';
            return;
        }
    }

    const payload = {
        id_event: idEvent,
        id_produk: parseInt(idProduk, 10),
        harga_event: Math.round(((100 - diskon) * hargaJual) / 100),
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
            if (!value || parseInt(value) <= 0) return 'The stock must be greater than 0.';
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

    if (!nama)                                { eeSetError('ee_nama_event',       'ee_err_nama_event',       'Event name is required.'); valid = false; }
    if (!tipe)                                { eeSetError('ee_tipe_event',       'ee_err_tipe_event',       'Select event type.'); valid = false; }
    if (!mulai)                               { eeSetError('ee_tanggal_mulai',    'ee_err_tanggal_mulai',    'Start date is required.'); valid = false; }
    if (!berakhir)                            { eeSetError('ee_tanggal_berakhir', 'ee_err_tanggal_berakhir', 'End date is required.'); valid = false; }
    if (mulai && berakhir && berakhir < mulai) { eeSetError('ee_tanggal_berakhir', 'ee_err_tanggal_berakhir', 'The end date cannot be earlier than the start date.'); valid = false; }
    if (diskon === '')                        { eeSetError('ee_persen_diskon',    'ee_err_persen_diskon',    'The "Discount" field is required.'); valid = false; }
    if (!maks || parseInt(maks) <= 0)         { eeSetError('ee_maks_pembelian',   'ee_err_maks_pembelian',   'Max purchase.'); valid = false; }
    if (tipe === 'preorder' && !sampai)       { eeSetError('ee_tanggal_sampai',   'ee_err_tanggal_sampai',   'The "Estimated Arrival" field must be filled out for pre-orders.'); valid = false; }

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

// ── FUNGSI BARU: MAJUKAN EVENT KE HARI INI ──
function moveUp(idEvent) {
    cardhavenConfirm(
        "Start this event today?",
        "This upcoming event will start running immediately today.",
        "Yes, start it",
        () => {
            fetch(FINISH_URL, { // Pakai URL yang sama
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    id_event: idEvent,
                    action: "move_up" // <--- Ini pembedanya
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Started",
                        text: data.message
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Failed",
                        text: data.error || data.message || "Unable to move up the event."
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

// ── FUNGSI LAMA YANG DI-UPDATE SEDIKIT ──
function completeEvent(idEvent) {
    cardhavenConfirm(
        "Complete this event?",
        "This event will be marked as completed earlier. It will stop running, but it will not be deleted.",
        "Yes, complete it",
        () => {
            fetch(FINISH_URL, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    id_event: idEvent,
                    action: "complete" // <--- Ditambahkan biar rapi
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Completed",
                        text: data.message
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Failed",
                        text: data.error || data.message || "Unable to complete the event."
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

function deleteEvent(idEvent){
    cardhavenConfirm(
        "Delete this event?",
        "This event will be delete and you will not able to see it.",
        "Yes, delete it",
        () => {
            fetch(DELETE_URL, {
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
                        text: "The event has been deleted."
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Failed",
                        // Ditambahkan data.message di tengah-tengah sebagai alternatif
                        text: data.error || data.message || "Unable to complete the event."
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

function hideEvent(idEvent, isHidden, element) {
    // isHidden = true (ON) -> status 3 (Hidden)
    // isHidden = false (OFF) -> status 1 (Visible/Active)
    const newStatus = isHidden ? 0 : 1; 

    fetch(TOGGLE_URL, { 
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            id_event: idEvent,
            is_hide: newStatus
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Unobtrusive toast notification for success
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
            Toast.fire({
                icon: isHidden ? 'info' : 'success', 
                title: data.message
            });
        } else {
            element.checked = !isHidden; 
            Swal.fire({
                icon: "error",
                title: "Failed",
                text: data.error || data.message || "Failed to update event visibility."
            });
        }
    })
    .catch(() => {
        // Revert the switch position if a network error occurs
        element.checked = !isHidden; 
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "A network error or server issue occurred."
        });
    });
}