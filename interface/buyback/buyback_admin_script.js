const BUYBACK_CONTROLLER = '/cardhaven/interface/buyback/controller_buyback.php';

const idPengguna = sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna');
const userRole = sessionStorage.getItem('role') || localStorage.getItem('role');
let currentPage = 1;
let currentSearch = '';
let currentStatus = '';
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

function handleBuybackSearch(val) {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => {
        currentSearch = val;
        currentPage = 1; // Reset page saat mencari
        loadDaftar();
    }, 400); // Debounce untuk mengurangi beban server
}

function setBuybackStatus(statusId) {
    currentStatus = statusId;
    currentPage = 1;
    loadDaftar();
}

function setBuybackPage(page) {
    currentPage = page;
    loadDaftar();
}

if (!idPengguna || (userRole != '2' && userRole != '3')) {
    window.location.href = '../login-page/index.php';
}
function loadDaftar() {
    fetch(`${BUYBACK_CONTROLLER}?action=get_buyback_list&role=2&id_pengguna=${idPengguna}&page=${currentPage}&search=${encodeURIComponent(currentSearch)}&status=${currentStatus}`)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'error') return;

        // 1. Render Table
        const tbody = document.querySelector('#tableAdmin tbody');
        tbody.innerHTML = '';
        
        if(data.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:2rem 0;opacity:.5;">No buyback transactions were found.</td></tr>`;
        } else {
            let startNo = (currentPage - 1) * 10 + 1;
            data.data.forEach((row) => {
                let tanggal = 'N/A';
                if (row.tanggal_pembelian) {
                    // Mengambil 10 karakter pertama (YYYY-MM-DD)
                    const tglMentah = row.tanggal_pembelian.substring(0, 10); 
                    
                    // Opsional: Jika ingin mengubah format menjadi DD-MM-YYYY (e.g., 30-06-2026)
                    const [tahun, bulan, hari] = tglMentah.split('-');
                    tanggal = `${hari}-${bulan}-${tahun}`;
                }
                let tr = `<tr class="trx-row" onclick="openDetailModal(${row.id_pembelian})">
                    <td>${startNo++}</td>
                    <td style="font-weight:700;color:var(--primary-color);">#${row.id_pembelian}</td>
                    <td><div style="font-weight:600;font-size:.85rem;">${row.username}</div></td>
                    <td style="white-space:nowrap;font-size:.82rem;">${tanggal}</td>
                    <td style="text-align:right;font-weight:700;white-space:nowrap;">Rp ${parseInt(row.total_harga).toLocaleString('id-ID')}</td>
                    <td>${parseStatus(row.status_pembelian)}</td>
                </tr>`;
                tbody.innerHTML += tr;
            });
        }

        // 2. Render Tabs
        renderTabs(data.status_counts);

        // 3. Render Pagination
        renderPagination(data.pagination);
    });
}
function renderTabs(counts) {
    const tabsContainer = document.getElementById('buybackTabs');
    if (!tabsContainer) return;
    
    let totalAll = 0;
    for (let key in counts) { totalAll += parseInt(counts[key]); }

    let html = `<a onclick="setBuybackStatus('')" class="trx-tab ${currentStatus === '' ? 'active' : ''}" style="color:#555;">
                    All <span class="tab-count">${totalAll}</span>
                </a>`;
    
    STATUS_BUYBACK.forEach(s => {
        const cnt = counts[s.id] || 0;
        html += `<a onclick="setBuybackStatus(${s.id})" class="trx-tab ${currentStatus === s.id ? 'active' : ''}" style="color:${s.color};">
                    ${s.label} ${cnt > 0 ? `<span class="tab-count">${cnt}</span>` : ''}
                </a>`;
    });
    
    tabsContainer.innerHTML = html;
}

function renderPagination(pageInfo) {
    const pagContainer = document.getElementById('buybackPagination');
    if (!pagContainer) return;

    const totalPages = pageInfo.total_pages;
    const page = pageInfo.current_page;
    
    if (totalPages <= 1) { pagContainer.innerHTML = ''; return; }

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
            
            let htmlContent = `
                <div style="background: rgba(0,0,0,0.03); padding: 15px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem;">
                    <strong>Customer:</strong> ${pem.username}<br>
                    <strong>Receipt:</strong> ${pem.no_resi || '-'}<br>
                    <strong>Notes / Return Addr:</strong> <span style="color: #E74C3C; font-weight: 600;">${pem.alamat || 'None'}</span>
                </div>
            `;
            
            // Status final: offer admin tidak relevan lagi
            const FINAL_STATUSES = [8, 9, 10]; // Completed, Rejected, Cancelled
            const isFinal = FINAL_STATUSES.includes(parseInt(pem.status_pembelian));

            // Setiap kartu harus punya keputusan: counter offer (penawaran_admin diisi)
            // ATAU approve harga customer (ditandai dengan flag approved_by_admin di data kartu).
            // Karena tidak ada kolom khusus, kita pakai konvensi:
            // penawaran_admin == penawaran_customer → admin approve harga customer
            // penawaran_admin != null && != penawaran_customer → admin counter
            // penawaran_admin == null → belum ada keputusan
            let allDecided = true; // untuk enable/disable Send Counter Offers
            let allApproved = true;
            let hasCounter = false;

            data.kartu.forEach(k => {
                const hasDecision = k.penawaran_admin != null; 
                const isApproved = hasDecision && (parseFloat(k.penawaran_admin) === parseFloat(k.penawaran_customer));
                const isCountered = hasDecision && !isApproved;

                if (!hasDecision) allDecided = false;
                if (!isApproved) allApproved = false;
                if (isCountered) hasCounter = true;

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

                // 2. Kunci tombol edit. Jika sudah memutuskan, tampilkan label terkunci.
                let cardActionHtml = '';
                if (pem.status_pembelian == 1) {
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
                        // MENGUNCI INPUT AGAR ADMIN TIDAK BISA EDIT LAGI
                        cardActionHtml = `
                            <div style="padding: 6px 10px; background: #f8fafc; border-left: 3px solid #E67E22; border-radius: 4px;">
                                <span style="font-size:0.8rem; color:#E67E22; font-weight:700;">Offer Saved.</span>
                                <span style="font-size:0.75rem; color:#64748b;"> Click 'Send Counter' / 'Approve' below when done.</span>
                            </div>`;
                    }
                } else if (pem.status_pembelian == 2) {
                    // Tampilan khusus saat menunggu customer
                    cardActionHtml = `
                        <div style="padding: 6px 10px; background: #fffbeb; border-left: 3px solid #f59e0b; border-radius: 4px;">
                            <span style="font-size:0.8rem; color:#d97706; font-weight:700;">Waiting for the Customer's Response...</span>
                        </div>`;
                }

                // Append HTML template seperti biasa (pastikan tag gambar depan/belakang Anda tetap ada)
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
                            <p style="margin: 4px 0;"><strong>Customer Attempts:</strong> <span style="color: #E67E22; font-weight: 600;">${k.percobaan_penawaran} / 3</span></p>
                        </div>
                        <div id="admin-action-${k.id_kartu}">${cardActionHtml}</div>
                    </div>`;
            });

            document.getElementById('modalContent').innerHTML = htmlContent;

            // 3. Perbaikan Logika Footer & Status Button 
            let footerHtml = '';
            const status = pem.status_pembelian;
            
            if (status == 0) {
                footerHtml += `<button class="btn-trx-action btn-cancel" onclick="updateStatus(${pem.id_pembelian}, 10, 'Submission cancelled')">Cancel Submission</button>`;
                footerHtml += `<button class="btn-trx-action btn-process" onclick="updateStatus(${pem.id_pembelian}, 1, 'Reviewing started')">Start Review</button>`;
            } else if (status == 1) {
                footerHtml += `<button class="btn-trx-action btn-cancel" onclick="updateStatus(${pem.id_pembelian}, 10, 'Submission cancelled')">Cancel Submission</button>`;
                
                if (allDecided) {
                    if (hasCounter) {
                        // HANYA muncul jika admin melakukan setidaknya 1 Counter
                        footerHtml += `<button class="btn-trx-action btn-ship" onclick="updateStatus(${pem.id_pembelian}, 2, 'Counter offers sent to customer')" style="background: #7c3aed; color: #fff; border:none; padding:10px 20px; font-weight:bold; border-radius:8px;">Send Counter Offers</button>`;
                    } else if (allApproved) {
                        // HANYA muncul jika admin Murni Approve seluruh kartu
                        footerHtml += `<button class="btn-trx-action btn-confirm" onclick="updateStatus(${pem.id_pembelian}, 3, 'All prices approved')">Approve All Prices</button>`;
                    }
                } else {
                    // Terkunci jika belum menentukan keputusan pada setiap kartu
                    footerHtml += `<button class="btn-trx-action btn-ship" disabled style="opacity: 0.4; cursor: not-allowed; background: #7c3aed; color: #fff; border:none; padding:10px 20px; font-weight:bold; border-radius:8px;">Send Counter Offers</button>`;
                }
            } else if (status == 2) {
                // Notifikasi visual Status 2 agar admin sadar bahwa proses terkunci menunggu customer
                footerHtml += `<div style="text-align: center; width: 100%; color: #d97706; font-weight: bold; padding: 12px; background: #fef3c7; border-radius: 8px;">Waiting for the Customer's Response...</div>`;
            } else if (status == 4) {
                footerHtml += `<button class="btn-trx-action btn-deliver" onclick="updateStatus(${pem.id_pembelian}, 5, 'Received')">Receive Package</button>`;
            } else if (status == 5) {
                footerHtml += `<button class="btn-trx-action btn-cancel" onclick="updateStatus(${pem.id_pembelian}, 9, 'Rejected')">Reject & Return</button>`;
                footerHtml += `<button class="btn-trx-action btn-confirm" onclick="updateStatus(${pem.id_pembelian}, 6, 'Verified')">Quality Match (Proceed)</button>`;
            } else if (status == 6) {
                footerHtml += `<button class="btn-trx-action btn-process" onclick="uploadPayment(${pem.id_pembelian})">Upload Payment Proof</button>`;
            } 

            document.getElementById('modalFooter').innerHTML = footerHtml;
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

document.addEventListener('DOMContentLoaded', loadDaftar);