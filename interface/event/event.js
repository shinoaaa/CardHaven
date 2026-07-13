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

// ── Validasi Real-Time Input Diskon ───────────────────────────────────────────
document.addEventListener('input', function (e) {
    // Cek jika yang sedang diketik adalah input diskon (Add Event atau Edit Event)
    if (e.target && (e.target.id === 'ae_persen_diskon' || e.target.id === 'ee_persen_diskon')) {
        
        // 1. LIMITER REAL-TIME (Cegah angka < 1 dan > 100)
        if (e.target.value !== '') {
            let val = parseFloat(e.target.value);
            if (val > 100) {
                e.target.value = 100; // Mentok di 100
            } else if (val < 1) {
                e.target.value = 1;   // Mentok bawah di 1
            }
        }

        // 2. Kalkulasi ulang harga setelah angka divalidasi
        if (e.target.id === 'ae_persen_diskon') {
            aeRecalcHarga();
            
            const currentDiskon = parseFloat(e.target.value);
            if (!isNaN(currentDiskon)) {
                // Update otomatis harga event semua barang di list
                aeProductList.forEach(p => {
                    p.harga_event = Math.round(((100 - currentDiskon) * p.harga_jual) / 100);
                });
                aeRenderProductTable();
            }
        } else if (e.target.id === 'ee_persen_diskon') {
            eeRecalcHarga();
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
    
    // VALIDASI DISKON TIDAK BOLEH < 1 ATAU > 100
    if (isNaN(diskon) || diskon < 1 || diskon > 100) {
        if (errProduk) errProduk.textContent = 'Discount must be between 1 and 100.';
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

    const hargaEvent = Math.round(((100 - diskon) * hargaJual) / 100);
        
    aeProductList.push({ 
        id_produk: idProduk, 
        nama_produk: namaProduk, 
        harga_jual: hargaJual, 
        harga_event: hargaEvent, 
        stok_event: stok,
        max_stok: aeSelectedMaxStok // <--- SIMPAN BATAS MAKSIMAL STOK PRODUK INI!
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
                <td><button class="btn-edit-icon" onclick="aeEditProductStock(${i})" title="Edit Stock">✏️</button></td>
                <td><button class="btn-delete-icon" onclick="aeRemoveProduct(${i})" title="Delete">🗑</button></td>
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

async function eeEditStock(idProdukEvent, currentStok, sisaStokGudang) {
    const modalEl = document.getElementById('eventModal');
    
    // 1. Sembunyikan Modal Edit Event sementara
    modalEl.classList.remove('show');

    // 2. Hitung batas maksimal yang diizinkan 
    // (Stok yang terpakai di event ini + Sisa stok di gudang)
    const maxStok =(sisaStokGudang || 0);

    // 3. Munculkan Pop-up SweetAlert bergaya CardHaven
    const { value: newStok } = await Swal.fire({
        title: 'Edit Stock',
        html: `<p style="margin:0 0 10px 0; font-size:14px; color:#666;">Available Stock: <b>${maxStok}</b> pcs</p>`,
        input: 'number',
        inputValue: currentStok,
        showCancelButton: true,
        confirmButtonText: 'Save',
        cancelButtonText: 'Cancel',
        iconColor: "#0D47A1",
        customClass: {
            popup: "cardhaven-popup",
            title: "coolveticaa cardhaven-title",
            confirmButton: "btn-confirm",
            cancelButton: "btn-cancel-outline"
        },
        inputValidator: (value) => {
            if (!value) return 'Please enter a number.';
            const valInt = parseInt(value, 10);
            if (valInt < 1) return 'The stock must be greater than 0.';
            if (valInt > maxStok) return `Stock cannot exceed ${maxStok + currentStok}.`;
            return null; // Valid
        }
    });

    if (newStok) {
        // Jika User klik Save dan lolos validasi
        try {
            const data = await eePost('update_stock', {
                id_produk_event: idProdukEvent,
                stok_event: parseInt(newStok, 10)
            });

            if (data.success) {
                // Notifikasi Sukses Kecil (Toast)
                Swal.fire({
                    toast: true, position: 'top-end',
                    icon: 'success', title: 'Stock updated',
                    showConfirmButton: false, timer: 1500
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Failed",
                    text: data.error || "Unable to update stock."
                });
                // Munculkan lagi modal utama jika gagal
                setTimeout(() => { modalEl.classList.add('show'); }, 100);
            }
        } catch (err) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Something went wrong while processing the request."
            });
            setTimeout(() => { modalEl.classList.add('show'); }, 100);
        }
    } else {
        // Jika User klik Cancel, munculkan lagi modal utama
        setTimeout(() => { modalEl.classList.add('show'); }, 100);
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

// ── Fungsi Edit Stok Produk di List ────────────────────────────────────────────
function aeEditProductStock(index) {
    const p = aeProductList[index];
    const modalEl = document.getElementById('eventModal');

    // 1. Sembunyikan Modal Add Event sementara
    modalEl.classList.remove('show');

    // 2. Munculkan Pop-up SweetAlert untuk input angka
    Swal.fire({
        title: 'Edit Stock',
        html: `<p style="margin:0 0 10px 0; font-size:14px; color:#666;">Available Stock: <b>${p.max_stok}</b> pcs</p>`,
        input: 'number',
        inputValue: p.stok_event,
        showCancelButton: true,
        confirmButtonText: 'Save',
        cancelButtonText: 'Cancel',
        iconColor: "#0D47A1",
        customClass: {
            popup: "cardhaven-popup",
            title: "coolveticaa cardhaven-title",
            confirmButton: "btn-confirm",
            cancelButton: "btn-cancel-outline"
        },
        inputValidator: (value) => {
            if (!value) return 'Please enter a number.';
            const valInt = parseInt(value, 10);
            if (valInt < 1) return 'The stock must be greater than 0.';
            if (valInt > p.max_stok) return `Stock cannot exceed ${p.max_stok}.`;
            return null; // Valid
        }
    }).then((result) => {
        // Jika user klik Save dan Lolos Validasi
        if (result.isConfirmed) {
            aeProductList[index].stok_event = parseInt(result.value, 10);
            aeRenderProductTable(); // Render ulang tabel
            
            // Notifikasi Sukses Kecil (Toast)
            Swal.fire({
                toast: true, position: 'top-end',
                icon: 'success', title: 'Stock updated',
                showConfirmButton: false, timer: 1500
            });
        }
        
        // 3. Munculkan kembali Modal Add Event (Delay 100ms mencegah glitch UI)
        setTimeout(() => {
            modalEl.classList.add('show');
        }, 100);
    });
}

// ════════════════════════════════════════════════════════════════════════════
// AJAX SEARCH, FILTER & PAGINATION LOGIC (BERSIH)
// ════════════════════════════════════════════════════════════════════════════

let currentEventSortDir = 'desc';

function parseInitialUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('search')) document.getElementById('filterSearch').value = urlParams.get('search');
    if (urlParams.has('status')) document.getElementById('filterStatus').value = urlParams.get('status');
    if (urlParams.has('type'))   document.getElementById('filterType').value = urlParams.get('type');
    if (urlParams.has('sort'))   document.getElementById('filterSort').value = urlParams.get('sort');
    if (urlParams.has('dir'))    currentEventSortDir = urlParams.get('dir');

    const initialPage = urlParams.has('page') ? parseInt(urlParams.get('page')) : 1;
    applyEventFilters(initialPage);
}

function escapeHTMLStr(str) {
    if (!str) return '-';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function applyEventFilters(page = 1) {
    const search = document.getElementById('filterSearch').value.trim();
    const status = document.getElementById('filterStatus').value;
    const type   = document.getElementById('filterType').value;
    const sort   = document.getElementById('filterSort').value;
    
    const params = new URLSearchParams();
    params.append('action', 'get_events_json');
    params.append('page', page);
    if (search) params.append('search', search);
    if (status !== '-1') params.append('status', status);
    if (type) params.append('type', type);
    params.append('sort', sort);
    params.append('dir', currentEventSortDir);

    const tbody = document.getElementById('event-tbody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="9" style="padding: 30px; text-align: center; color:#173C99; font-weight:bold;">Loading data...</td></tr>';

    fetch('/cardhaven/interface/event/apifetch.php?' + params.toString())
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                // PERBAIKAN: Kirim res.page ke fungsi render agar Nomor Urut bisa dihitung!
                renderEventTable(res.data, res.page);
                renderPaginationUI(res.page, res.total_pages);
                
                // Update URL diam-diam
                const stateParams = new URLSearchParams(params.toString());
                stateParams.delete('action');
                window.history.pushState({}, '', '?' + stateParams.toString());
            } else {
                console.error("Backend Error:", res.msg);
                if (tbody) tbody.innerHTML = `<tr><td colspan="9" style="padding: 30px; text-align: center; color:red;">Gagal memuat data: ${res.msg}</td></tr>`;
            }
        })
        .catch(err => {
            console.error("Fetch Error:", err);
            if (tbody) tbody.innerHTML = '<tr><td colspan="9" style="padding: 30px; text-align: center; color:red;">Terjadi kesalahan sistem. Cek Console.</td></tr>';
        });
}

function renderEventTable(data, currentPage = 1) {
    const tbody = document.getElementById('event-tbody');
    if (!tbody) return;

    if(data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 30px;">No events found.</td></tr>';
        return;
    }

    let html = '';
    // Menghitung Nomor Urut (Limit = 7 per halaman)
    let no = ((currentPage - 1) * 7) + 1;

    data.forEach(row => {
        const estatus = parseInt(row.status_event);
        const isHide = parseInt(row.is_hide);
        
        // ── 1. LOGIKA STATUS & WARNA (Sama Persis Seperti PHP Lama) ──
        let statusHtml = '';
        const hideBadge = (isHide === 1) ? ' <span style="color: #7F8C8D; font-weight: normal; font-size: 0.9em;">(Hidden)</span>' : '';
        
        if (estatus === 1) {
            statusHtml = `<span style="color: #27AE60; font-weight: bold;">Running</span>${hideBadge}`;
        } else if (estatus === 2) {
            statusHtml = `<span style="color: #F39C12; font-weight: bold;">Upcoming</span>${hideBadge}`;
        } else {
            statusHtml = `<span style="color: var(--primary-color); font-weight: bold;">Complete</span>${hideBadge}`;
        }
        
        // ── 2. LOGIKA BUTTON ACTION (Class Asli Milikmu) ──
        let actionHtml = `
            <div class="btn-action-group">
                <button class="btn-view-icon" onclick="openEventModal(${row.id_event})">...</button>
        `;

        // Tombol Edit
        if (estatus === 1 || estatus === 2) {
            actionHtml += `<button class="btn-edit-icon" onclick="openEditModal(${row.id_event})"><img src="/cardhaven/assets/image/edit.svg" alt=""></button>`;
        } else if (estatus === 0) {
            actionHtml += `<button class="btn-complete-icon" style="cursor: default;"><img src="/cardhaven/assets/image/edit.svg" alt=""></button>`;
        }

        // Tombol Complete / Move Up (Start)
        if (estatus === 1) {
            actionHtml += `<button class="btn-delete-icon" onclick="completeEvent(${row.id_event})"><img src="/cardhaven/assets/image/clock-arrow-down.svg" alt=""></button>`;
        } else if (estatus === 0) {
            actionHtml += `<button class="btn-complete-icon" style="cursor: default;"><img src="/cardhaven/assets/image/clock-check.svg" alt=""></button>`;
        } else if (estatus === 2) {
            actionHtml += `<button class="btn-edit-icon" onclick="moveUp(${row.id_event})"><img src="/cardhaven/assets/image/clock-arrow-up.svg" alt=""></button>`;
        }

        // Tombol Delete & Switch Hide
        actionHtml += `
                <button class="btn-delete-icon" onclick="deleteEvent(${row.id_event})"><img src="/cardhaven/assets/image/delete.svg" alt=""></button>
                <label class="switch" title="Hide Event from Customers">
                    <input type="checkbox" ${isHide === 0 ? 'checked="checked"' : ''} onchange="hideEvent(${row.id_event}, this.checked, this)">
                    <span class="slider"></span>
                </label>
            </div>
        `;

        // ── 3. SUSUN HTML BARIS TABEL ──
        html += `<tr>
            <td>${no++}</td>
            <td style="font-weight: 600; text-align: center;">${escapeHTMLStr(row.nama_event)}</td>
            <td>${escapeHTMLStr(row.tipe_event)}</td>
            <td>${row.tanggal_mulai || '-'}</td>
            <td>${row.tanggal_berakhir || '-'}</td>
            <td style="font-weight: bold; text-align: right;">${Number(row.persen_diskon || 0)}%</td>
            <td style="text-align: right;">${Number(row.total_item || 0)}</td>
            <td>${statusHtml}</td>
            <td>${actionHtml}</td>
        </tr>`;
    });

    tbody.innerHTML = html;
}

function toggleEventSortDir() {
    currentEventSortDir = currentEventSortDir === 'desc' ? 'asc' : 'desc';
    applyEventFilters(1);
}

function renderPaginationUI(current, total) {
    const cont = document.getElementById('event-pagination');
    if(!cont) return;
    cont.innerHTML = '';
    if(total <= 1) return;

    let html = '';
    if(current > 1) {
        html += `<button class="page-link" onclick="applyEventFilters(${current - 1})">&lt;</button>`;
    } else {
        html += `<button class="page-link disabled" disabled>&lt;</button>`;
    }

    const start = Math.max(1, current - 1);
    const end = Math.min(total, current + 1);

    if(start > 1) {
        html += `<button class="page-link ${current === 1 ? 'active' : ''}" onclick="applyEventFilters(1)">1</button>`;
        if(start > 2) html += `<span class="page-link disabled">...</span>`;
    }

    for(let i=start; i<=end; i++) {
        html += `<button class="page-link ${current === i ? 'active' : ''}" onclick="applyEventFilters(${i})">${i}</button>`;
    }

    if(end < total) {
        if(end < total - 1) html += `<span class="page-link disabled">...</span>`;
        html += `<button class="page-link ${current === total ? 'active' : ''}" onclick="applyEventFilters(${total})">${total}</button>`;
    }

    if(current < total) {
        html += `<button class="page-link" onclick="applyEventFilters(${current + 1})">&gt;</button>`;
    } else {
        html += `<button class="page-link disabled" disabled>&gt;</button>`;
    }
    cont.innerHTML = html;
}

// Jalankan ketika halaman ter-load
document.addEventListener('DOMContentLoaded', () => {
    parseInitialUrl();

    const searchInput = document.getElementById('filterSearch');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyEventFilters(1);
            }
        });
    }
});