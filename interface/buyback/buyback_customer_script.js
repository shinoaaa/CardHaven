const BUYBACK_CONTROLLER = '/cardhaven/interface/buyback/controller_buyback.php';

const idPengguna = CardHavenAuth.id() || null;
const userRole = CardHavenAuth.role();
let cardIndexCounter = 1;

// ── Pagination/Filter State (dari buyback.js, untuk profile page) ──
let allBuyback     = [];
let buybackPage    = 1;
const BUYBACK_PER_PAGE = 4;
let bbSearch = '', bbStatus = '', bbSortField = 'date', bbSortDir = 'desc';

if (!idPengguna || userRole != '0') {
    window.location.href = '/CardHaven/login';
}

function fetchBankDetails() {
    fetch(`${BUYBACK_CONTROLLER}?action=get_user_bank&id_pengguna=${idPengguna}`)
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success' && res.data) {
            document.getElementById('bankProvider').value = res.data.provider || '';
            document.getElementById('bankNoRek').value = res.data.no_rekening || '';
        }
    });
}

// ── FILTER/PAGINATION FUNCTIONS (untuk profile page) ──

function getFilteredBuyback() {
    let rows = allBuyback.slice();

    if (bbStatus !== '') {
        rows = rows.filter(r => String(r.status_pembelian) === String(bbStatus));
    }
    if (bbSearch.trim() !== '') {
        const q = bbSearch.trim().toLowerCase();
        rows = rows.filter(r =>
            String(r.id_pembelian).includes(q) ||
            ('#' + r.id_pembelian).includes(q) ||
            parseStatus(r.status_pembelian).toLowerCase().includes(q) ||
            String(r.total_harga || '').includes(q) ||
            (r.tanggal_pembelian || '').toLowerCase().includes(q));
    }
    const dir = bbSortDir === 'asc' ? 1 : -1;
    rows.sort((a, b) => {
        let cmp;
        if (bbSortField === 'price')      cmp = (a.total_harga || 0) - (b.total_harga || 0);
        else if (bbSortField === 'items') cmp = (a.total_barang || 0) - (b.total_barang || 0);
        else cmp = new Date(a.tanggal_pembelian) - new Date(b.tanggal_pembelian);
        return cmp * dir;
    });
    return rows;
}

function renderBuyback() {
    const tbody = document.querySelector('#tableRiwayat tbody');
    if (!tbody) return;

    const rows = getFilteredBuyback();
    if (rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No BuyBack records yet.</td></tr>';
        renderBuybackPagination(0);
        return;
    }

    const totalPages = Math.ceil(rows.length / BUYBACK_PER_PAGE);
    if (buybackPage > totalPages) buybackPage = totalPages;
    const start = (buybackPage - 1) * BUYBACK_PER_PAGE;
    const pageRows = rows.slice(start, start + BUYBACK_PER_PAGE);

    tbody.innerHTML = pageRows.map((row, i) => {
        let tanggal = 'N/A';
        if (row.tanggal_pembelian) {
            const [tahun, bulan, hari] = row.tanggal_pembelian.substring(0, 10).split('-');
            tanggal = `${hari}-${bulan}-${tahun}`;
        }
        const aksi = `<button class="action-dots-btn" title="View detail" onclick="openDetailModal(${row.id_pembelian})">•••</button>`;
        const sc = buybackStatusColor(row.status_pembelian);
        return `<tr>
            <td>${start + i + 1}</td>
            <td>#${row.id_pembelian}</td>
            <td>${tanggal}</td>
            <td style="text-align:right;">${row.total_barang ?? '-'}</td>
            <td style="text-align:right;">Rp ${parseInt(row.total_harga || 0).toLocaleString('id-ID')}</td>
            <td><span class="status-pill" style="background:${sc.bg};color:${sc.color};">${parseStatus(row.status_pembelian)}</span></td>
            <td>${aksi}</td>
        </tr>`;
    }).join('');

    renderBuybackPagination(totalPages);
}

function renderBuybackPagination(totalPages) {
    const box = document.getElementById('bb-pagination');
    if (!box) return;
    if (totalPages <= 1) { box.innerHTML = ''; return; }

    let html = `<button class="page-arrow" ${buybackPage === 1 ? 'disabled' : ''} onclick="gotoBuybackPage(${buybackPage - 1})">‹</button>`;
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || Math.abs(i - buybackPage) <= 1) {
            html += `<button class="page-num ${i === buybackPage ? 'active' : ''}" onclick="gotoBuybackPage(${i})">${i}</button>`;
        } else if (i === buybackPage - 2 || i === buybackPage + 2) {
            html += `<span class="page-dots">...</span>`;
        }
    }
    html += `<button class="page-arrow" ${buybackPage === totalPages ? 'disabled' : ''} onclick="gotoBuybackPage(${buybackPage + 1})">›</button>`;
    box.innerHTML = html;
}

function gotoBuybackPage(p) { buybackPage = p; renderBuyback(); }

function onBuybackFilterChange() {
    bbSearch    = document.getElementById('bb-search')?.value || '';
    bbStatus    = document.getElementById('bb-status')?.value || '';
    bbSortField = document.getElementById('bb-sortby')?.value || 'date';
    buybackPage = 1;
    renderBuyback();
}

function toggleBuybackDateSort() {
    bbSortDir = bbSortDir === 'desc' ? 'asc' : 'desc';
    const icon = document.getElementById('bb-sort-icon');
    if (icon) icon.textContent = bbSortDir === 'desc' ? '↓' : '↑';
    buybackPage = 1;
    renderBuyback();
}

function buybackStatusColor(status) {
    const map = {
        0:  { bg: '#fef9c3', color: '#ca8a04' },
        1:  { bg: '#e0f2fe', color: '#0369a1' },
        2:  { bg: '#ede9fe', color: '#7c3aed' },
        3:  { bg: '#dcfce7', color: '#15803d' },
        4:  { bg: '#dbeafe', color: '#1d4ed8' },
        5:  { bg: '#d1fae5', color: '#065f46' },
        6:  { bg: '#cffafe', color: '#0e7490' },
        7:  { bg: '#fef3c7', color: '#b45309' },
        8:  { bg: '#d1fae5', color: '#14532d' },
        9:  { bg: '#fee2e2', color: '#b91c1c' },
        10: { bg: '#f3f4f6', color: '#6b7280' },
    };
    return map[parseInt(status)] || { bg: '#f3f4f6', color: '#6b7280' };
}


// Fungsi untuk me-render gambar ke tag <img> saat user memilih file
function previewImage(input, imgId) {
    const imgElement = document.getElementById(imgId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imgElement.src = e.target.result;
            imgElement.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        imgElement.src = '';
        imgElement.style.display = 'none';
    }
}

function resetForm() {
    const form = document.getElementById('formBuyback');
    if (form) {
        form.reset();
        form.querySelectorAll('.error-message').forEach(el => el.innerText = '');
        form.querySelectorAll('.modal-input').forEach(el => el.style.borderColor = '#ccc');
        // Sembunyikan semua preview gambar
        form.querySelectorAll('img[id^="preview"]').forEach(img => {
            img.src = '';
            img.style.display = 'none';
        });
    }
    resetCardFields();
    fetchBankDetails(); // Kembalikan data rekening bawaan
}

function addCardField() {
    cardIndexCounter++; // ID tetap unik (terus bertambah) agar DOM tidak bentrok
    const container = document.getElementById('cardInputsContainer');
    const visualIndex = container.children.length + 1; // Menyesuaikan label visual dengan jumlah kartu riil di layar
    
    const html = `
        <div class="card-input-group" id="cardGroup${cardIndexCounter}" style="border: 2px solid #E1EBFF; padding: 20px; border-radius: 12px; margin-bottom: 15px; background: #fafcff; position: relative;">
            <button type="button" onclick="removeCardField(${cardIndexCounter})" style="position: absolute; right: 15px; top: 15px; background: none; border: none; color: #E74C3C; cursor: pointer; font-weight: bold; font-size: 0.9rem;">&times; Remove</button>
            <h4 style="margin-top: 0; margin-bottom: 15px; color: var(--primary-color); font-size: 1.1rem;">Card ${visualIndex}</h4>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Card Name <span class="required">*</span></label>
                    <input type="text" name="nama_kartu[]" class="modal-input" placeholder="e.g., Charizard Base Set">
                    <div class="error-message" style="color: #E74C3C; font-size: 0.75rem; margin-top: 4px;"></div>
                </div>
                <div class="form-group">
                    <label>Your Offer Price (Rp) <span class="required">*</span></label>
                    <input type="number" name="harga_beli[]" class="modal-input" placeholder="e.g., 500000">
                    <div class="error-message" style="color: #E74C3C; font-size: 0.75rem; margin-top: 4px;"></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                <div class="form-group">
                    <label>Front Photo <span class="required">*</span></label>
                    <input type="file" name="foto_depan[]" class="file-input-custom modal-input" accept="image/*" onchange="previewImage(this, 'previewFront${cardIndexCounter}')">
                    <div class="error-message" style="color: #E74C3C; font-size: 0.75rem; margin-top: 4px;"></div>
                    <img id="previewFront${cardIndexCounter}" src="" style="max-width: 100%; max-height: 200px; display: none; margin-top: 10px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                </div>
                <div class="form-group">
                    <label>Back Photo <span class="required">*</span></label>
                    <input type="file" name="foto_belakang[]" class="file-input-custom modal-input" accept="image/*" onchange="previewImage(this, 'previewBack${cardIndexCounter}')">
                    <div class="error-message" style="color: #E74C3C; font-size: 0.75rem; margin-top: 4px;"></div>
                    <img id="previewBack${cardIndexCounter}" src="" style="max-width: 100%; max-height: 200px; display: none; margin-top: 10px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
}

function closeSubmitModal() {
    document.getElementById('submitModal').style.display = 'none';
    document.getElementById('formBuyback').reset();
    resetCardFields();
}


function removeCardField(id) {
    document.getElementById(`cardGroup${id}`).remove();
    
    // Kalkulasi ulang urutan nomor visual kartu yang tersisa
    const container = document.getElementById('cardInputsContainer');
    const groups = container.querySelectorAll('.card-input-group');
    groups.forEach((group, index) => {
        const header = group.querySelector('h4');
        if (header) {
            header.innerText = `Card ${index + 1}`;
        }
    });
}

function resetCardFields() {
    const container = document.getElementById('cardInputsContainer');
    // Sisakan hanya elemen pertama, hapus sisanya
    while (container.children.length > 1) {
        container.removeChild(container.lastChild);
    }
    cardIndexCounter = 1;
}

function renderBuyback() {
    const tbody = document.querySelector('#tableRiwayat tbody');
    if (!tbody) return;

    const rows = getFilteredBuyback();
    if (rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No BuyBack records yet.</td></tr>';
        renderBuybackPagination(0);
        return;
    }

    const totalPages = Math.ceil(rows.length / BUYBACK_PER_PAGE);
    if (buybackPage > totalPages) buybackPage = totalPages;
    const start = (buybackPage - 1) * BUYBACK_PER_PAGE;
    const pageRows = rows.slice(start, start + BUYBACK_PER_PAGE);

    tbody.innerHTML = pageRows.map((row, i) => {
        let tanggal = 'N/A';
        if (row.tanggal_pembelian) {
            const [tahun, bulan, hari] = row.tanggal_pembelian.substring(0, 10).split('-');
            tanggal = `${hari}-${bulan}-${tahun}`;
        }
        const aksi = `<button class="action-dots-btn" title="View detail" onclick="openDetailModal(${row.id_pembelian})">•••</button>`;
        const sc = buybackStatusColor(row.status_pembelian);
        return `<tr>
            <td>${start + i + 1}</td>
            <td>#${row.id_pembelian}</td>
            <td>${tanggal}</td>
            <td style="text-align:right;">${row.total_barang ?? '-'}</td>
            <td style="text-align:right;">Rp ${parseInt(row.total_harga || 0).toLocaleString('id-ID')}</td>
            <td><span class="status-pill" style="background:${sc.bg};color:${sc.color};">${parseStatus(row.status_pembelian)}</span></td>
            <td>${aksi}</td>
        </tr>`;
    }).join('');

    renderBuybackPagination(totalPages);
}

function loadRiwayat() {
    const tbody = document.querySelector('#tableRiwayat tbody');
    if (!tbody || !idPengguna) return;

    fetch(`${BUYBACK_CONTROLLER}?action=get_buyback_list&role=0&id_pengguna=${idPengguna}`)
    .then(res => res.json())
    .then(data => {
        allBuyback = (data && data.data) ? data.data : [];
        buybackPage = 1;
        renderBuyback();
    })
    .catch(() => {
        if (tbody) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#dc2626;">Failed to load buyback history.</td></tr>';
    });
}
function submitBuyback() {
    const form = document.getElementById('formBuyback');
    let isValid = true;

    // Bersihkan error sebelumnya
    document.querySelectorAll('#formBuyback .error-message').forEach(el => el.innerText = '');
    document.querySelectorAll('#formBuyback .modal-input').forEach(el => el.style.borderColor = '#ccc');

    // Fungsi trigger error ala Master Game
    const showError = (element, message) => {
        element.style.borderColor = '#E74C3C';
        const errorDiv = element.nextElementSibling;
        if (errorDiv && errorDiv.classList.contains('error-message')) {
            errorDiv.innerText = message;
        }
        isValid = false;
    };

    // Validasi Rekening Bank
    const provider = document.getElementById('bankProvider');
    const noRek = document.getElementById('bankNoRek');
    const providerVal = provider.value.trim();
    const noRekVal = noRek.value.trim();

    if (!providerVal || providerVal.length < 2 || !/^[a-zA-Z0-9\s]+$/.test(providerVal)) {
        showError(provider, "Please enter a valid provider.");
    }

    if (!noRekVal || noRekVal.length < 5 || !/^[0-9]+$/.test(noRekVal)) {
        showError(noRek, "Please enter a valid account number.");
    }
    // Validasi Per Kartu
    const cardNames = document.getElementsByName('nama_kartu[]');
    const cardPrices = document.getElementsByName('harga_beli[]');
    const cardFronts = document.getElementsByName('foto_depan[]');
    const cardBacks = document.getElementsByName('foto_belakang[]');

    for (let i = 0; i < cardNames.length; i++) {
        if (!cardNames[i].value.trim()) showError(cardNames[i], "Please enter the card name!");
        if (!cardPrices[i].value) showError(cardPrices[i], "Please enter the price!");
        if (cardPrices[i].value <= 0) showError(cardPrices[i], "Please enter a valid price!");
        if (cardFronts[i].files.length === 0) showError(cardFronts[i], "Please upload the front side photo of the card!");
        if (cardBacks[i].files.length === 0) showError(cardBacks[i], "Please upload the back side photo of the card!");
    }

    if (!isValid) return; // Hentikan proses jika ada error

    const formData = new FormData(form);
    formData.append('action', 'submit_buyback');
    formData.append('id_pengguna', idPengguna);

    fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            resetForm();
            cardhavenToast('success', res.message);
            loadRiwayat();
        } else {
            cardhavenAlert('error', 'Failed', res.message);
        }
    });
}

function inputResi(id_pembelian) {
    closeDetailModal();
    Swal.fire({
        title: 'Input Receipt Number',
        input: 'text',
        inputPlaceholder: 'Enter shipping tracking number',
        showCancelButton: true,
        confirmButtonText: 'Submit',
        customClass: { 
            confirmButton: "btn-confirm", 
            cancelButton: "btn-cancel-outline" 
        },
    
        inputValidator: (value) => {
            if (!value || value.trim() === "") {
                return 'Tracking number is required!'; 
            }
        }
       
    }).then((result) => {
        // Jika validasi lolos dan user menekan Confirm
        if (result.isConfirmed) {
            const formData = new URLSearchParams();
            formData.append('action', 'update_status');
            formData.append('id_pembelian', id_pembelian);
            formData.append('status', 4);
            formData.append('no_resi', result.value.trim()); 
            formData.append('id_pengguna', idPengguna);

            fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
            .then(response => {
                if(response.ok) {
                    Swal.fire('Success', 'Tracking number updated.', 'success');
                    loadRiwayat();
                } else {
                    Swal.fire('Error', 'Failed to update tracking number.', 'error');
                }
            })
            .catch(err => console.error('Error:', err));
        }
    });
}

function parseStatus(status) {
    const statuses = ["Pending Submission", "Under Review", "Price Negotiation", "Offer Accepted", "Card Shipped", "Card Received", "Quality Checked", "Payment Sent", "Completed", "Rejected", "Cancelled"];
    return statuses[status] || "Unknown";
}

function openDetailModal(id_pembelian) {
    // Fix: URL yang benar (sebelumnya URL duplikat/rusak)
    fetch(`${BUYBACK_CONTROLLER}?action=get_detail&id_pembelian=${id_pembelian}&role=${userRole}&id_pengguna=${idPengguna}`)
    .then(res => res.json())
    .then(res => {
        if(res.status === 'success') {
            const data = res.data;
            const pem = data.pembelian;
            document.getElementById('modalTxId').innerText = `#${pem.id_pembelian}`;
            document.getElementById('modalStatus').innerHTML = parseStatus(pem.status_pembelian);
            
            let htmlContent = '';

            // Status final: offer tidak relevan lagi
            const FINAL_STATUSES = [8, 9, 10];
            const isFinal = FINAL_STATUSES.includes(parseInt(pem.status_pembelian));
            const isNegotiating = pem.status_pembelian == 2;

            // Tracking per-kartu untuk logika footer (status Price Negotiation)
            let anyPending      = false; // admin sudah menawar → menunggu keputusan customer (accept/counter)
            let anyWaitingAdmin = false; // customer sudah counter / admin belum menawar → menunggu admin
            let allAgreed       = true;  // semua kartu harganya sudah disepakati
            let hasCounter       = false;

            data.kartu.forEach(k => {
                const adminHasOffer = k.penawaran_admin != null;
                const priceMatch    = adminHasOffer && (parseFloat(k.penawaran_admin) === parseFloat(k.penawaran_customer));
                const isPending     = isNegotiating && adminHasOffer && !priceMatch;
                const isAgreed      = adminHasOffer && priceMatch;
                const isWaitingAdmin = isNegotiating && !adminHasOffer;

                // Menghitung percobaan murni (Submit awal tidak dihitung)
                let actualAttempts = Math.max(0, parseInt(k.percobaan_penawaran) - 1);
                const maxAttempts  = actualAttempts >= 3;

                if (isNegotiating) {
                    if (isPending)      anyPending = true;
                    if (isWaitingAdmin) anyWaitingAdmin = true;
                    if (!isAgreed)      allAgreed = false;
                    if (isWaitingAdmin) hasCounter = true;
                } else {
        // ✅ Kalo bukan negotiating, cek apakah kartu ini udah agreed
        if (!isAgreed) allAgreed = false;
    }

                let adminOfferLabel;
                if (!adminHasOffer) {
                    adminOfferLabel = isFinal
                        ? `<span style="color:#9ca3af;">-</span>`
                        : `<span style="color:#9ca3af;">Waiting for admin...</span>`;
                } else if (isAgreed) {
                    adminOfferLabel = `<span style="color:#27AE60;font-weight:600;">Rp ${parseInt(k.penawaran_admin).toLocaleString('id-ID')}</span>`;
                } else {
                    adminOfferLabel = `<span style="color:#E74C3C;font-weight:600;">Rp ${parseInt(k.penawaran_admin).toLocaleString('id-ID')}</span>`;
                }

                const borderColor = isPending ? '#fbbf24' : '#e5e7eb';

                htmlContent += `
                <div style="border: 1.5px solid ${borderColor}; border-radius: 12px; padding: 15px; margin-bottom: 15px; background: #fff;">
                    <h3 style="margin:0 0 10px 0; color: var(--primary-color);">${k.nama_kartu}</h3>
                    
                    <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                        <div style="flex: 1;">
                            <p style="margin: 0 0 5px 0; font-size: 0.8rem; color: #666;">Front Photo:</p>
                            <a href="/CardHaven/${k.foto_depan}" target="_blank">
                                <img src="/CardHaven/${k.foto_depan}" style="width: 100%; height: auto; object-fit: cover; border-radius: 6px; border: 1px solid #ccc;">
                            </a>
                        </div>
                        <div style="flex: 1;">
                            <p style="margin: 0 0 5px 0; font-size: 0.8rem; color: #666;">Back Photo:</p>
                            <a href="/CardHaven/${k.foto_belakang}" target="_blank">
                                <img src="/CardHaven/${k.foto_belakang}" style="width: 100%; height: auto; object-fit: cover; border-radius: 6px; border: 1px solid #ccc;">
                            </a>
                        </div>
                    </div>

                    <div style="font-size: 0.9rem; margin-bottom: 12px; padding-top: 10px; border-top: 1px dashed #e5e7eb;">
                        <p style="margin: 4px 0;"><strong>Your Ask:</strong> Rp ${parseInt(k.penawaran_customer).toLocaleString('id-ID')}</p>
                        <p style="margin: 4px 0;"><strong>Admin Offer:</strong> ${adminOfferLabel}</p>
                        <p style="margin: 4px 0;"><strong>Attempts:</strong> <span style="color:#E67E22;font-weight:600;">${actualAttempts} / 3</span></p>
                    </div>`;

                if (isPending) {
                    htmlContent += `<div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                        <button onclick="acceptItemOffer(${pem.id_pembelian}, ${k.id_kartu}, ${k.penawaran_admin})"
                            class="btn-confirm" style="width:auto; height:32px; font-size:0.8rem; padding:0 15px; margin:0; background:#27AE60;">
                            ✓ Accept
                        </button>
                        ${!maxAttempts
                            ? `<button onclick="counterItemOffer(${pem.id_pembelian}, ${k.id_kartu})"
                                class="btn-cancel-outline" style="width:auto; height:32px; font-size:0.8rem; padding:0 15px; margin:0; border-width:1.5px; color:#7c3aed; border-color:#7c3aed;">
                                ⟳ Counter Offer
                                </button>`
                            : `<span style="color:#E74C3C; font-weight:bold; font-size:0.8rem;">Max attempts reached. You can only Accept.</span>`
                        }
                    </div>`;
                } else if (isAgreed) {
                    htmlContent += `<p style="color:#27AE60; font-weight:bold; margin:0;">✓ Price Agreed</p>`;
                }

                htmlContent += `</div>`;
            });

            // Hitung ulang hasCounter: ada kartu yang sudah respond tapi pending = 0 dan tidak semua agreed
            // Sederhananya: jika allDecided = true, cek apakah ada yang tidak match awalnya
            // Karena kita tidak ada flag terpisah, kita asumsikan: jika allDecided && ada setidaknya 1 kartu
            // yang penawaran_customer != nilai original (kita tidak track ini), maka hasCounter = true.
            // Solusi pragmatis: jika allDecided = true → selalu tampilkan "Submit Counter Offers"
            // kecuali SEMUA kartu match antara penawaran_customer == penawaran_admin (semua accept murni).
            // Ini sudah benar karena: Accept → update penawaran_customer = penawaran_admin (match)
            //                         Counter → update penawaran_customer = nilai baru != penawaran_admin (tidak match)
            // Tapi saat status = 2, setelah customer counter, status kembali ke 1 (di-handle SP).
            // Jadi saat status == 2, SEMUA kartu yang sudah "respond" akan match (karena counter sudah di-submit round sebelumnya).
            // Yang belum respond = isPending. Jika allDecided, berarti semua sudah accept di round ini.
            // Jadi: allDecided && status == 2 → semua accept di round ini → Proceed to Shipping
                       // ✅ FIX: Recompute allAgreed - cek semua kartu punya penawaran_admin yang match dengan customer
            // Toleransi untuk floating point comparison
            allAgreed = data.kartu.length > 0 && data.kartu.every(k => {
                if (k.penawaran_admin == null) return false;
                const admin = parseFloat(k.penawaran_admin);
                const cust = parseFloat(k.penawaran_customer);
                // Toleransi 1 rupiah untuk handle decimal precision
                return Math.abs(admin - cust) < 1;
            });
            // Tampilkan foto bukti bayar jika ada (Status 7)
            if (pem.bukti_pembayaran) {
                htmlContent += `
                <div style="background: #E1EBFF; padding: 15px; border-radius: 8px; margin-bottom: 15px; text-align: center;">
                    <h4 style="margin: 0 0 10px 0; color: var(--primary-color);">Payment Proof</h4>
                    <a href="/CardHaven/${pem.bukti_pembayaran}" target="_blank">
                        <img src="/CardHaven/${pem.bukti_pembayaran}" style="max-width: 100%; max-height: 250px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    </a>
                </div>`;
            }

            // Fix #2: Alamat retur HANYA muncul saat Rejected (9) yang terjadi SETELAH barang diterima (status >= 5)
            // Jika reject di status 0 atau 1, tidak ada barang yang perlu dikembalikan
            if (pem.status_pembelian == 9 && pem.no_resi) {
                // Barang sudah pernah dikirim (ada resi), berarti perlu alamat retur
                htmlContent += `
                <div style="background: #fff7ed; border: 1px solid #fed7aa; padding: 12px 15px; border-radius: 8px; margin-bottom: 10px; font-size: 0.9rem;">
                    <strong style="color: #c2410c;">📦 Card Return</strong><br>
                    ${pem.alamat 
                        ? `<span style="color: #065f46;">Return address submitted.</span>`
                        : `<span style="color: #9a3412;">Please provide your return address so we can send the card back.</span>`
                    }
                </div>`;
            }

            document.getElementById('modalContent').innerHTML = htmlContent;

            let footerHtml = '';
            
                           if (pem.status_pembelian == 2) {
    if (allAgreed) {
        // Semua kartu harganya sudah disepakati → lanjut ke shipping
        footerHtml += `<button class="btn-confirm" style="width:auto; padding:10px 20px; background:#27AE60;" onclick="proceedToShipping(${pem.id_pembelian})">Proceed to Shipping</button>`;
    } else if (anyPending) {
        // Ada kartu yang masih nunggu keputusan customer (admin udah nawar, belum respond)
        footerHtml += `<button class="btn-confirm" disabled style="width:auto; padding:10px 20px; opacity:0.4; cursor:not-allowed;" title="Respond to all card offers first">Respond to All Cards First</button>`;
    } else if (hasCounter) {
        // ✅ SEMUA KARTU YANG BELUM AGREED UDAH DI-COUNTER → KIRIM KE ADMIN
        footerHtml += `<button class="btn-confirm" style="width:auto; padding:10px 20px; background:#7c3aed;" onclick="submitCounterToAdmin(${pem.id_pembelian})">Send Counter Offer to Admin</button>`;
    } else {
        // Nunggu admin
        footerHtml += `<button class="btn-confirm" disabled style="width:auto; padding:10px 20px; opacity:0.4; cursor:not-allowed;">Waiting for Admin...</button>`;
    }
            } else if (pem.status_pembelian == 3) {
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #27AE60;" onclick="inputResi(${pem.id_pembelian})">Input Receipt</button>`;
            } else if (pem.status_pembelian == 7) {
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #0088FF;" onclick="completeTransaction(${pem.id_pembelian})">Confirm Payment Received</button>`;
            } else if (pem.status_pembelian == 9 && pem.no_resi) {
                // Fix #2: Alamat retur hanya jika barang sudah pernah dikirim (ada no_resi)
                if (!pem.alamat) {
                    footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #E67E22;" onclick="inputAddress(${pem.id_pembelian})">Provide Return Address</button>`;
                } else {
                    footerHtml += `<span style="color: #27AE60; font-weight: bold;">✓ Return Address Submitted</span>`;
                }
            }
            
            // Opsi Cancel untuk status awal (sebelum barang dikirim, status 0-2)
            if (pem.status_pembelian <= 2) {
                footerHtml += `<button class="btn-cancel-outline" style="width: auto; padding: 10px 20px; border-color: #E74C3C; color: #E74C3C; border-width: 2px;" onclick="cancelBuyback(${pem.id_pembelian})">Cancel Submission</button>`;
            }

            document.getElementById('modalFooter').innerHTML = footerHtml;
            document.getElementById('detailModal').style.display = 'flex';
        }
    });
}

function closeDetailModal() {
    document.getElementById('detailModal').style.display = 'none';
}
function counterItemOffer(idP, idK) {
    Swal.fire({
        title: 'Counter Offer for this Card',
        input: 'number',
        inputPlaceholder: 'Enter your counter price (Rp)',
        showCancelButton: true,
        confirmButtonText: 'Save Price',
        inputValidator: (value) => {
            const val = value ? value.toString().trim() : '';
            if (!val || isNaN(val) || Number(val) <= 0) {
                return 'Please enter a valid price!';
            }
        }
    }).then(res => {
        if (res.isConfirmed && res.value) {
            const formData = new URLSearchParams();
            formData.append('action', 'customer_negotiate_item');
            formData.append('id_pembelian', idP);
            formData.append('id_kartu', idK);
            formData.append('penawaran_customer', res.value);
            formData.append('id_pengguna', idPengguna);

            fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(result => {
                if (result.status === 'success') {
                    // Refresh modal dan tabel — status mungkin berubah ke Under Review (1) via SP
                    openDetailModal(idP);
                    loadRiwayat();
                } else {
                    Swal.fire('Error', result.message || 'Failed to submit counter offer.', 'error');
                }
            });
        }
    });
}
function acceptItemOffer(idP, idK, price) {
    const formData = new URLSearchParams();
    formData.append('action', 'customer_accept_item');
    formData.append('id_pembelian', idP);
    formData.append('id_kartu', idK);
    // ✅ Pastikan harga_final dikirim sebagai string tanpa formatting
    formData.append('harga_final', String(price));
    formData.append('id_pengguna', idPengguna);

    fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (result.status === 'success') {
            openDetailModal(idP); // Refresh modal, cek apakah semua sudah decide
        } else {
            Swal.fire('Error', result.message || 'Failed to accept offer.', 'error');
        }
    });
}
function cancelBuyback(id_pembelian) {
    closeDetailModal();
    cardhavenConfirm("Cancel Transaction", "Are you sure you want to cancel this submission?", "Yes, Cancel", () => {
        const formData = new URLSearchParams();
        formData.append('action', 'update_status');
        formData.append('id_pembelian', id_pembelian);
        formData.append('status', 10); // Status 10 = Cancelled
        formData.append('id_pengguna', idPengguna);

        fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
        .then(() => loadRiwayat());
    }, () => {
        // Jika batal dibatalkan, buka kembali modal
        document.getElementById('detailModal').style.display = 'flex';
    });

}function completeTransaction(id_pembelian) {
    closeDetailModal();
    cardhavenConfirm("Confirm Receipt", "Have you verified that the money is in your bank account?", "Yes, Complete", () => {
        const formData = new URLSearchParams();
        formData.append('action', 'update_status');
        formData.append('id_pembelian', id_pembelian);
        formData.append('status', 8); // Status 8 = Completed
        formData.append('id_pengguna', idPengguna);

        fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
        .then(() => loadRiwayat());
    }, () => {
        document.getElementById('detailModal').style.display = 'flex';
    });
}

function submitCounterToAdmin(id_pembelian) {
    closeDetailModal();
    cardhavenConfirm("Send Counter Offer", "Submit your counter offer to admin for review?", "Yes, Send", () => {
        const formData = new URLSearchParams();
        formData.append('action', 'update_status');
        formData.append('id_pembelian', id_pembelian);
        formData.append('status', 1); // Balik ke Under Review
        formData.append('id_pengguna', idPengguna);

        fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
        .then(() => {
            Swal.fire({ icon: 'success', title: 'Sent!', text: 'Counter offer submitted. Waiting for admin review.', timer: 1500, showConfirmButton: false });
            loadRiwayat();
        });
    }, () => {
        document.getElementById('detailModal').style.display = 'flex';
    });
}

function inputAddress(id_pembelian) {
    closeDetailModal();
    Swal.fire({
        title: 'Input Return Address',
        input: 'textarea',
        inputPlaceholder: 'Enter your full address for card return shipment...',
        inputAttributes: {
            maxlength: '100' // FIX: Membatasi browser agar tidak bisa mengetik lebih dari 100 karakter
        },
        inputValidator: (value) => {
            // Validasi tambahan sebelum form disubmit
            if (!value || !value.trim()) {
                return 'Address cannot be empty!';
            }
            if (value.length > 100) {
                return 'Address cannot exceed 100 characters!';
            }
        },
        showCancelButton: true,
        confirmButtonText: 'Submit Address',
        customClass: { confirmButton: "btn-confirm", cancelButton: "btn-cancel-outline" }
    }).then(res => {
        if(res.isConfirmed && res.value) {
            const formData = new URLSearchParams();
            formData.append('action', 'update_address');
            formData.append('id_pembelian', id_pembelian);
            formData.append('alamat_retur', res.value);
            formData.append('id_pengguna', idPengguna); // INI WAJIB DITAMBAHKAN UNTUK KEAMANAN

            fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(result => {
                if(result.status === 'success') {
                    Swal.fire('Success', result.message, 'success').then(() => {
                        openDetailModal(id_pembelian); // Refresh modal agar muncul pesannya
                    });
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            });
        } else {
            // Jika user batal input, kembalikan modal detail
            document.getElementById('detailModal').style.display = 'flex';
        }
    });
}

function proceedToShipping(id_pembelian) {
    closeDetailModal();

    const formData = new URLSearchParams();
    formData.append('action', 'update_status');
    formData.append('id_pembelian', id_pembelian);
    formData.append('status', 3); // Lanjut ke status Offer Accepted -> Shipping
    formData.append('id_pengguna', idPengguna);

    fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            Swal.fire({
                icon: 'success', 
                title: 'Success', 
                text: 'All prices agreed! Proceed to shipping.', 
                timer: 1500, 
                showConfirmButton: false
            });
            loadRiwayat();
        } else {
            Swal.fire('Error', res.message || 'Failed to proceed.', 'error');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Profile page: ada tabel riwayat
    if (document.querySelector('#tableRiwayat')) {
        loadRiwayat();
    }
    // Customer.php: ada form buyback
    if (document.getElementById('formBuyback')) {
        fetchBankDetails();
    }
});