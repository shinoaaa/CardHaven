const BUYBACK_CONTROLLER = '/cardhaven/interface/buyback/controller_buyback.php';

const idPengguna = CardHavenAuth.id() || null;
const userRole = CardHavenAuth.role();

// State ala halaman laporan: tarik semua data sekali, filter/sort/paginate di client.
let allBuyback = [];
let filteredBuyback = [];
let currentPage = 1;
const itemsPerPage = 10;
let currentStatusFilter = '';   // '' = All Status
let currentSortBy = 'DATE';     // DATE | PRICE
let currentSortOrder = 'DESC';
let typingTimer;

const STATUS_BUYBACK = [
    { id: 0, label: "Pending Submission", bg: "#fef9c3", color: "#ca8a04" },
    { id: 1, label: "Under Review", bg: "#e0f2fe", color: "#0369a1" },
    { id: 2, label: "Price Negotiation", bg: "#ede9fe", color: "#7c3aed" },
    { id: 3, label: "Offer Accepted", bg: "#d1fae5", color: "#065f46" },
    { id: 4, label: "Card Shipped", bg: "#dbeafe", color: "#1d4ed8" },
    { id: 5, label: "Card Received", bg: "#d1fae5", color: "#065f46" },
    { id: 6, label: "Quality Checked", bg: "#d1fae5", color: "#065f46" },
    { id: 7, label: "Payment Sent", bg: "#ede9fe", color: "#7c3aed" },
    { id: 8, label: "Completed", bg: "#d1fae5", color: "#14532d" },
    { id: 9, label: "Rejected", bg: "#fee2e2", color: "#b91c1c" },
    { id: 10, label: "Cancelled", bg: "#f3f4f6", color: "#6b7280" }
];

if (!idPengguna || (userRole != '2' && userRole != '3')) {
    window.location.href = '/CardHaven/home';
}

// ── Ambil tanggal transaksi (fallback ke created_date) ──
function getRowDate(row) {
    return (row.tanggal_pembelian || row.created_date || '').toString();
}

// ── FETCH SEMUA DATA (limit besar → semua baris untuk difilter di client) ──
function loadDaftar() {
    fetch(`${BUYBACK_CONTROLLER}?action=get_buyback_list&role=2&id_pengguna=${idPengguna}&page=1&search=&status=&limit=100000`)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'error') return;
        allBuyback = data.data || [];
        applyBuybackFilter();
    })
    .catch(err => console.error("Error fetching buyback list:", err));
}

// ── FILTER BAR (search + filter by status + sort by) ──
function handleBuybackSearch() {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(applyBuybackFilter, 250);
}

function setBuybackStatus(val) {
    currentStatusFilter = val;
    applyBuybackFilter();
}

function changeBuybackSort() {
    const el = document.getElementById('buybackSort');
    const val = el ? el.value : 'DATE';
    currentSortBy = (val === '' || val === 'NONE') ? 'DATE' : val;
    applyBuybackFilter();
}

function toggleBuybackSortOrder() {
    currentSortOrder = currentSortOrder === 'DESC' ? 'ASC' : 'DESC';
    const icon = document.getElementById('buybackSortIcon');
    if (icon) icon.innerHTML = currentSortOrder === 'ASC'
        ? '<path d="M12 19V5M5 12l7-7 7 7"/>'
        : '<path d="M12 5v14M19 12l-7 7-7-7"/>';
    applyBuybackFilter();
}

function applyBuybackFilter() {
    const searchEl = document.getElementById('buybackSearch');
    const search = searchEl ? searchEl.value.toLowerCase().trim() : '';

    filteredBuyback = allBuyback.filter(row => {
        // Filter by status
        if (currentStatusFilter !== '' && String(row.status_pembelian) !== String(currentStatusFilter)) return false;

        // Searching (username, order id, harga, tanggal)
        if (search !== '') {
            const uname = (row.username || '').toString().toLowerCase();
            const idPlain = String(row.id_pembelian || '');
            const harga = (row.total_harga || '').toString().toLowerCase();
            const rawTgl = getRowDate(row);
            const tgl = rawTgl.length >= 10 ? rawTgl.substring(0, 10).split('-').reverse().join('-') : rawTgl;
            const match = uname.includes(search) || idPlain.includes(search) ||
                          ('#' + idPlain).includes(search) || harga.includes(search) || tgl.includes(search);
            if (!match) return false;
        }
        return true;
    });

    filteredBuyback.sort((a, b) => {
        let valA, valB;
        if (currentSortBy === 'PRICE') {
            valA = parseFloat(a.total_harga || 0);
            valB = parseFloat(b.total_harga || 0);
        } else { // DATE
            valA = new Date(getRowDate(a) || 0).getTime();
            valB = new Date(getRowDate(b) || 0).getTime();
        }
        if (valA === valB) return 0;
        return currentSortOrder === 'DESC' ? (valA < valB ? 1 : -1) : (valA < valB ? -1 : 1);
    });

    currentPage = 1;
    renderTable();
}

function renderTable() {
    const tbody = document.querySelector('#tableAdmin tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (filteredBuyback.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:2rem 0;opacity:.5;">No buyback transactions were found.</td></tr>`;
        renderPagination(0);
        return;
    }

    const totalPages = Math.ceil(filteredBuyback.length / itemsPerPage);
    const startIdx = (currentPage - 1) * itemsPerPage;
    const pageData = filteredBuyback.slice(startIdx, startIdx + itemsPerPage);

    let startNo = startIdx + 1;
    pageData.forEach((row) => {
        let tanggal = 'N/A';
        const rawTgl = getRowDate(row);
        if (rawTgl) {
            const tglMentah = rawTgl.substring(0, 10);
            const [tahun, bulan, hari] = tglMentah.split('-');
            tanggal = `${hari}-${bulan}-${tahun}`;
        }
        let tr = `<tr class="trx-row" onclick="openDetailModal(${row.id_pembelian})">
            <td>${startNo++}</td>
            <td><div style="font-weight:600;font-size:.85rem;">${row.username}</div></td>
            <td style="white-space:nowrap;font-size:.82rem;">${tanggal}</td>
            <td style="text-align:right;font-weight:700;white-space:nowrap;">Rp ${(parseInt(row.total_harga) || 0).toLocaleString('id-ID')}</td>
            <td>${parseStatus(row.status_pembelian)}</td>
            <td>
                <div class="btn-action-group">
                    <button class="btn-view-icon" onclick="openDetailModal(${row.id_pembelian})">...</button>
                </div>
            </td>
        </tr>`;
        tbody.innerHTML += tr;
    });

    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    const pagContainer = document.getElementById('buybackPagination');
    if (!pagContainer) return;

    if (totalPages <= 1) { pagContainer.innerHTML = ''; return; }

    const page = currentPage;
    let html = '';
    html += page > 1 ? `<a onclick="setBuybackPage(${page - 1})" class="page-link">&lt;</a>` : `<span class="page-link disabled">&lt;</span>`;

    let start = Math.max(1, page - 1);
    let end = Math.min(totalPages, page + 1);

    if (start > 1) {
        html += `<a onclick="setBuybackPage(1)" class="page-link ${page == 1 ? 'active' : ''}">1</a>`;
        if (start > 2) html += `<span class="dots">...</span>`;
    }

    for (let i = start; i <= end; i++) {
        html += `<a onclick="setBuybackPage(${i})" class="page-link ${i == page ? 'active' : ''}">${i}</a>`;
    }

    if (end < totalPages) {
        if (end < totalPages - 1) html += `<span class="dots">...</span>`;
        html += `<a onclick="setBuybackPage(${totalPages})" class="page-link ${page == totalPages ? 'active' : ''}">${totalPages}</a>`;
    }

    html += page < totalPages ? `<a onclick="setBuybackPage(${page + 1})" class="page-link">&gt;</a>` : `<span class="page-link disabled">&gt;</span>`;
    pagContainer.innerHTML = html;
}

function setBuybackPage(page) {
    currentPage = page;
    renderTable();
}

function parseStatus(status) {
    const s = STATUS_BUYBACK.find(x => x.id == status) || { label: "Unknown", bg: "#f3f4f6", color: "#555" };
    return `<span style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:700; background:${s.bg}; color:${s.color}; white-space:nowrap;">${s.label}</span>`;
}

function openDetailModal(id_pembelian) {
    fetch(`${BUYBACK_CONTROLLER}?action=get_detail&id_pembelian=${id_pembelian}&role=2&id_pengguna=${idPengguna}`)
    .then(res => res.json())
    .then(res => {
        if(res.status === 'success') {
            const data = res.data;
            const pem = data.pembelian;

            document.getElementById('modalTxId').innerText = `${pem.id_pembelian}`;
            document.getElementById('modalStatus').innerHTML = parseStatus(pem.status_pembelian);

            let proofHtml = '';
            if (pem.bukti_pembayaran) {
                proofHtml = `
                    <div style="margin-top: 15px; padding-top: 12px; border-top: 1px dashed #ccc;">
                        <strong style="display:block; margin-bottom:5px; font-size:0.75rem; text-transform:uppercase; color:#666;">Payment Proof:</strong>
                        <a href="/CardHaven/${pem.bukti_pembayaran}" target="_blank">
                            <img src="/CardHaven/${pem.bukti_pembayaran}" 
                                style="max-width: 150px; max-height: 100px; border-radius: 6px; border: 1px solid #ddd; object-fit: cover; cursor: pointer;"
                                title="Click to enlarge">
                        </a>
                    </div>
                `;
            } else {
                proofHtml = `
                    <div style="margin-top: 15px; padding-top: 12px; border-top: 1px dashed #ccc;">
                        <strong style="display:block; margin-bottom:5px; font-size:0.75rem; text-transform:uppercase; color:#666;">Payment Proof:</strong>
                        <span style="font-size: 0.85rem; color: #999; font-style: italic;">No payment proof uploaded yet</span>
                    </div>
                `;
            }
            let htmlContent = `
                <div style="background: rgba(0,0,0,0.03); padding: 15px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem;">
                    <strong>Customer:</strong> ${pem.username}<br>
                    <strong>Receipt:</strong> ${pem.no_resi || '-'}<br>
                    <strong>Notes / Return Addr:</strong> <span style="color: #E74C3C; font-weight: 600;">${pem.alamat || 'None'}</span>
                    ${proofHtml}
                </div>
            `;

            const FINAL_STATUSES = [8, 9, 10];
            const isFinal = FINAL_STATUSES.includes(parseInt(pem.status_pembelian));

            let allDecided = true;
            let allApproved = true;
            let hasCounter = false;
            let anyDecided = false; // FLAG BARU: Cek apakah admin sudah mulai memberi keputusan
            let customerHasCountered = false;

            data.kartu.forEach(k => {
                const hasDecision = k.penawaran_admin != null;
                const isApproved = hasDecision && (parseFloat(k.penawaran_admin) === parseFloat(k.penawaran_customer));
                const isCountered = hasDecision && !isApproved;

                let actualAttempts = Math.max(0, parseInt(k.percobaan_penawaran) - 1);
                
                // ✅ DETECT CUSTOMER COUNTER: penawaran_admin NULL tapi percobaan > 1
                const customerCountered = !hasDecision && parseInt(k.percobaan_penawaran) > 1;

                if (hasDecision) anyDecided = true; // Set true jika ada minimal 1 kartu yg diklik
                if (!hasDecision) allDecided = false;
                if (!isApproved) allApproved = false;
                if (isCountered) hasCounter = true;
                if (customerCountered) customerHasCountered = true;

                let adminOfferLabel;
                if (!hasDecision) {
                    if (isFinal) {
                        adminOfferLabel = `<span style="color: #9ca3af;">-</span>`;
                    } else {
                        adminOfferLabel = `<span style="color: #E67E22; font-weight: 600;">Not yet decided</span>`;
                    }
                } else if (isApproved) {
                    adminOfferLabel = `<span style="color: #27AE60; font-weight: 600;">✓ Approve — Rp ${parseInt(k.penawaran_admin).toLocaleString('id-ID')}</span>`;
                } else {
                    adminOfferLabel = `<span style="color: #7c3aed; font-weight: 600;">Counter — Rp ${parseInt(k.penawaran_admin).toLocaleString('id-ID')}</span>`;
                }

                let cardActionHtml = '';
                if (userRole === 3) {
                    // Owner: akses hanya-lihat (mirip Sales/Transaction) — tanpa tombol aksi per-kartu.
                    cardActionHtml = '';
                } else if (pem.status_pembelian == 1) {
                    if (!hasDecision) {
                        cardActionHtml = `
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <button onclick="adminApproveCard(${pem.id_pembelian}, ${k.id_kartu}, ${k.penawaran_customer})"
                                    class="btn-confirm" style="width:auto; padding:5px 14px; font-size:0.8rem; margin:0; background:#27AE60;">
                                    ✓ Approve Price
                                </button>
                                <button onclick="adminCounterItem(${pem.id_pembelian}, ${k.id_kartu})"
                                    class="btn-cancel-outline" style="width:auto; padding:5px 14px; font-size:0.8rem; margin:0;">
                                    ✎ Set Counter Offer
                                </button>
                            </div>`;
                    } else {
                        cardActionHtml = `
                            <div style="padding: 6px 10px; background: #f8fafc; border-left: 3px solid #E67E22; border-radius: 4px;">
                                <span style="font-size:0.8rem; color:#E67E22; font-weight:700;">Offer Saved.</span>
                                <span style="font-size:0.75rem; color:#64748b;"> Ready for final submission.</span>
                            </div>`;
                    }
                } else if (pem.status_pembelian == 2) {
                    cardActionHtml = `
                        <div style="padding: 6px 10px; background: #fffbeb; border-left: 3px solid #f59e0b; border-radius: 4px;">
                            <span style="font-size:0.8rem; color:#d97706; font-weight:700;">Waiting for the Customer's Response...</span>
                        </div>`;
                }

                htmlContent += `
                    <div style="border: 1px solid ${!hasDecision && pem.status_pembelian == 1 ? '#fbbf24' : '#e5e7eb'}; padding: 15px; margin-bottom: 15px; border-radius: 12px; background: #fff;">
                        <h4 style="margin: 0 0 10px 0; color: var(--primary-color);">${k.nama_kartu}</h4>

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
                            <p style="margin: 4px 0;"><strong>Customer Ask:</strong> Rp ${parseInt(k.penawaran_customer).toLocaleString('id-ID')}</p>
                            <p style="margin: 4px 0;"><strong>Admin Decision:</strong> ${adminOfferLabel}</p>
                            <p style="margin: 4px 0;"><strong>Customer Attempts:</strong> <span style="color: #E67E22; font-weight: 600;">${actualAttempts} / 3</span></p>
                        </div>
                        <div id="admin-action-${k.id_kartu}">${cardActionHtml}</div>
                    </div>`;
            });

            document.getElementById('modalContent').innerHTML = htmlContent;

            // 3. LOGIKA FOOTER SATU PINTU & KONSISTENSI STYLE TOMBOL
            let footerHtml = '';
            const status = pem.status_pembelian;

            // Variabel Style CSS Tombol
            const btnBase = "border: none; padding: 10px 20px; font-weight: bold; border-radius: 8px; cursor: pointer; font-size: 0.9rem; transition: 0.2s;";
            const btnCancel = `background: #b91c1c; color: white; ${btnBase}`;
            const btnBlue = `background: #1d4ed8; color: white; ${btnBase}`;
            const btnPurple = `background: #7c3aed; color: white; ${btnBase}`;
            const btnGreen = `background: #065f46; color: white; ${btnBase}`;
            const btnDisabled = `background: #7c3aed; color: white; opacity: 0.4; cursor: not-allowed; ${btnBase}`;

            
            if (userRole === 3) {
                // Owner: akses hanya-lihat (mirip Sales/Transaction) — tanpa tombol aksi.
                footerHtml = '';
            }
            else if (status == 0) {
                footerHtml += `<button onclick="updateStatus(${pem.id_pembelian}, 10, 'Submission cancelled')" style="${btnCancel}">Cancel Submission</button>`;
                footerHtml += `<button onclick="updateStatus(${pem.id_pembelian}, 1, 'Reviewing started')" style="${btnBlue}">Start Review</button>`;
            }
            else if (status == 1) {
                footerHtml += `<button onclick="updateStatus(${pem.id_pembelian}, 10, 'Submission cancelled')" style="${btnCancel}">Cancel Submission</button>`;

                if (!anyDecided && !customerHasCountered) {
                    // MODE 1: Belum ada keputusan sama sekali → cuma bisa Approve All
                    footerHtml += `<button onclick="approveAllPrices(${pem.id_pembelian})" style="${btnBlue}">Approve All Prices</button>`;
                } else if (allDecided) {
                    // MODE 2: Semua kartu udah ada keputusan admin
                    if (hasCounter) {
                        // Ada yang di-counter → bisa kirim counter offers
                        footerHtml += `<button onclick="updateStatus(${pem.id_pembelian}, 2, 'Counter offers sent to customer')" style="${btnPurple}">Send Counter Offers</button>`;
                    } else {
                        // Semua approve → Approve All
                        footerHtml += `<button onclick="approveAllPrices(${pem.id_pembelian})" style="${btnBlue}">Approve All Prices</button>`;
                    }
                } else {
                    // MODE 3: Sedang memproses (belum semua kartu diisi) → disabled
                    footerHtml += `<button disabled style="${btnDisabled}">Send Counter Offers</button>`;
                }
            }
            else if (status == 2) {
                footerHtml += `<div style="text-align: center; width: 100%; color: #d97706; font-weight: bold; padding: 12px; background: #fef3c7; border-radius: 8px;">Waiting for the Customer's Response...</div>`;
            }
            else if (status == 4) {
                footerHtml += `<button onclick="updateStatus(${pem.id_pembelian}, 5, 'Received')" style="${btnGreen}">Receive Package</button>`;
            }
            else if (status == 5) {
                footerHtml += `<button onclick="updateStatus(${pem.id_pembelian}, 9, 'Rejected')" style="${btnCancel}">Reject & Return</button>`;
                footerHtml += `<button onclick="updateStatus(${pem.id_pembelian}, 6, 'Verified')" style="${btnGreen}">Quality Match (Proceed)</button>`;
            }
            else if (status == 6) {
                footerHtml += `<button onclick="uploadPayment(${pem.id_pembelian})" style="${btnBlue}">Upload Payment Proof</button>`;
            }

            const modalFooterEl = document.getElementById('modalFooter');
            modalFooterEl.innerHTML = footerHtml;
            // Owner view-only: sembunyikan area tombol biar tidak ada kotak kosong.
            modalFooterEl.style.display = (userRole === 3) ? 'none' : 'flex';
            document.getElementById('detailModal').style.display = 'flex';
        }
    });
}

function closeDetailModal() {
    document.getElementById('detailModal').style.display = 'none';
}

function updateStatus(id_pembelian, statusBaru, message) {
    closeDetailModal();
    const formData = new URLSearchParams();
    formData.append('action', 'update_status');
    formData.append('id_pembelian', id_pembelian);
    formData.append('status', statusBaru);
    formData.append('id_pengguna', idPengguna);

    fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
    .then(() => {
        Swal.fire({icon: 'success', title: 'Success', text: message, timer: 1500, showConfirmButton: false});
        loadDaftar();
    });
}
function adminCounterItem(idP, idK) {
    Swal.fire({
        title: 'Input Offer for this Card',
        input: 'number',
        confirmButtonText: 'Save Price',
        showCancelButton: true,
        inputValidator: (value) => {

            const val = value ? value.toString().trim() : '';

            if (!val || isNaN(val) || Number(val) <= 0) {
                return 'Invalid price!';
            }
        }
    }).then(result => {
        if (result.isConfirmed && result.value) {
            const formData = new URLSearchParams();
            formData.append('action', 'admin_negotiate');
            formData.append('id_pembelian', idP);
            formData.append('id_kartu', idK);
            formData.append('penawaran_admin', result.value);
            formData.append('id_pengguna', idPengguna);

            fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    // JANGAN panggil loadDaftar() di sini agar modal tidak menutup.
                    // Cukup panggil openDetailModal agar data harga yang baru tersimpan langsung ter-refresh di dalam modal.
                    openDetailModal(idP);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        }
    });
}

// Approve harga customer: set penawaran_admin = penawaran_customer
function adminApproveCard(idP, idK, hargaCustomer) {
    const formData = new URLSearchParams();
    formData.append('action', 'admin_negotiate');
    formData.append('id_pembelian', idP);
    formData.append('id_kartu', idK);
    formData.append('penawaran_admin', hargaCustomer); // sama dengan harga customer = approve
    formData.append('id_pengguna', idPengguna);

    fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            openDetailModal(idP); // Refresh modal tanpa tutup
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    });
}

function uploadPayment(id_pembelian) {
    closeDetailModal();
    Swal.fire({
        title: 'Upload Payment Proof',
        input: 'file',
        inputAttributes: {
            'accept': 'image/*',
            'aria-label': 'Upload payment proof'
        },
        showCancelButton: true,
        confirmButtonText: 'Send Payment',
        customClass: { confirmButton: "btn-confirm", cancelButton: "btn-cancel-outline" }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const formData = new FormData();
            formData.append('action', 'admin_send_payment');
            formData.append('id_pembelian', id_pembelian);
            formData.append('id_pengguna', idPengguna);
            formData.append('bukti_pembayaran', result.value);

            fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if(res.status === 'success') {
                    Swal.fire('Success', res.message, 'success');
                    loadDaftar();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        } else if (result.isDismissed) {
            document.getElementById('detailModal').style.display = 'flex';
        }
    });
}

function approveAllPrices(idP) {
    closeDetailModal();
    Swal.fire({
        title: 'Approving all prices...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    // Ambil data kartu terbaru
    fetch(`${BUYBACK_CONTROLLER}?action=get_detail&id_pembelian=${idP}&role=2&id_pengguna=${idPengguna}`)
    .then(res => res.json())
    .then(res => {
        const cards = res.data.kartu;

        // Looping persetujuan (set harga admin = harga customer)
        const promises = cards.map(k => {
            const formData = new URLSearchParams();
            formData.append('action', 'admin_negotiate');
            formData.append('id_pembelian', idP);
            formData.append('id_kartu', k.id_kartu);
            formData.append('penawaran_admin', k.penawaran_customer); 
            formData.append('id_pengguna', idPengguna);
            return fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData });
        });

        // Setelah semua kartu berhasil di-approve, update status transaksi ke 3 (Offer Accepted)
        Promise.all(promises)
            .then(responses => Promise.all(responses.map(r => r.json()))) // Konversi semua respon ke JSON
            .then(results => {
                // Cek apakah ada proses penawaran harga kartu yang gagal di backend
                const anyError = results.some(res => res.status === 'error');
                
                if (anyError) {
                    Swal.fire('Error', 'Gagal memproses persetujuan harga pada beberapa kartu.', 'error');
                    return; // Hentikan proses, jangan update status transaksi ke 3
                }

                // Jika semua kartu aman, baru tembak update_status ke 3
                const statusData = new URLSearchParams();
                statusData.append('action', 'update_status');
                statusData.append('id_pembelian', idP);
                statusData.append('status', 3);
                statusData.append('id_pengguna', idPengguna);
                
                return fetch(BUYBACK_CONTROLLER, { method: 'POST', body: statusData });
            })
            .then(res => res && res.json())
            .then(res => {
                if(res && res.status === 'success') {
                    Swal.fire('Success', 'All prices approved successfully!', 'success');
                    loadDaftar();
                }
            });
    });
}

document.addEventListener('DOMContentLoaded', loadDaftar);

// Shortcut dari dashboard Activity: buka modal detail langsung via ?open_buyback=<id>
document.addEventListener('DOMContentLoaded', () => {
    const id = new URLSearchParams(window.location.search).get('open_buyback');
    if (id) openDetailModal(parseInt(id));
});
