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

if (!idPengguna || userRole != '2') {
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
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:2rem 0;opacity:.5;">Tidak ada transaksi buyback ditemukan.</td></tr>`;
        } else {
            let startNo = (currentPage - 1) * 10 + 1;
            data.data.forEach((row) => {
                let tr = `<tr class="trx-row" onclick="openDetailModal(${row.id_pembelian})">
                    <td>${startNo++}</td>
                    <td style="font-weight:700;color:var(--primary-color);">#${row.id_pembelian}</td>
                    <td><div style="font-weight:600;font-size:.85rem;">${row.username}</div></td>
                    <td style="white-space:nowrap;font-size:.82rem;">${row.tanggal_pembelian}</td>
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
            
            data.kartu.forEach(k => {
                htmlContent += `
                    <div style="border: 1px solid #e5e7eb; padding: 15px; margin-bottom: 15px; border-radius: 12px; background: #fff;">
                        <h4 style="margin: 0 0 10px 0; color: var(--primary-color);">${k.nama_kartu}</h4>
                        <div style="font-size: 0.9rem; margin-bottom: 12px;">
                            <p style="margin: 4px 0;"><strong>Customer Ask:</strong> Rp ${parseInt(k.penawaran_customer).toLocaleString('id-ID')}</p>
                            <p style="margin: 4px 0;"><strong>Admin Offer:</strong> <span style="color: #E74C3C;">${k.penawaran_admin ? 'Rp ' + parseInt(k.penawaran_admin).toLocaleString('id-ID') : 'Pending'}</span></p>
                            <p style="margin: 4px 0;"><strong>Customer Attempts:</strong> <span style="color: #E67E22; font-weight: 600;">${k.percobaan_penawaran} / 3</span></p>
                        </div>
                        <div id="admin-action-${k.id_kartu}">
                            ${pem.status_pembelian == 1 ? 
                                `<button onclick="adminCounterItem(${pem.id_pembelian}, ${k.id_kartu})" class="btn-cancel-outline" style="width: auto; padding: 6px 15px; font-size: 0.8rem; margin: 0; cursor: pointer;">Set Offer</button>` 
                                : ``}
                        </div>
                    </div>`;
            });
            document.getElementById('modalContent').innerHTML = htmlContent;

            let footerHtml = '';
            const status = pem.status_pembelian;
            
            if (status == 0) {
                footerHtml += `<button class="btn-trx-action btn-cancel" onclick="updateStatus(${pem.id_pembelian}, 9, 'Rejected')">Reject</button>`;
                footerHtml += `<button class="btn-trx-action btn-process" onclick="updateStatus(${pem.id_pembelian}, 1, 'Reviewing started')">Start Review</button>`;
            } else if (status == 1) {
                footerHtml += `<button class="btn-trx-action btn-cancel" onclick="updateStatus(${pem.id_pembelian}, 9, 'Rejected')">Reject All</button>`;
                footerHtml += `<button class="btn-trx-action btn-ship" onclick="updateStatus(${pem.id_pembelian}, 2, 'Sent to Customer')">Send Counter Offers</button>`;
                footerHtml += `<button class="btn-trx-action btn-confirm" onclick="updateStatus(${pem.id_pembelian}, 3, 'Offer Accepted')">Approve All Prices</button>`;
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
        showCancelButton: true
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