const REPORT_CONTROLLER = '/cardhaven/interface/report-buyback/controller_laporan_buyback.php';
const idPengguna = sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna');
const userRole = sessionStorage.getItem('role') || localStorage.getItem('role');

let allData = [];         // Menyimpan semua data mentah dari server
let filteredData = [];    // Menyimpan data hasil pencarian & sorting
let currentPage = 1;
const itemsPerPage = 10;
let currentSortBy = 'DATE';
let currentSortOrder = 'DESC';
let typingTimer;

if (!idPengguna || (userRole != '2' && userRole != '3')) {
    window.location.href = 'login';
}

function shiftYear(amount) {
    const yearInput = document.getElementById('filterTahun');
    let currentVal = parseInt(yearInput.value);
    if (isNaN(currentVal)) currentVal = new Date().getFullYear();
    else currentVal += amount;
    
    yearInput.value = currentVal;
    fetchReportData();
}
function fetchReportData() {
    let tahunRaw = document.getElementById('filterTahun').value;
    const tahun = tahunRaw ? parseInt(tahunRaw) : 0;
    const bulan = document.getElementById('filterBulan').value;
    
    fetch(`${REPORT_CONTROLLER}?action=get_data&tahun=${tahun}&bulan=${bulan}&role=${userRole}`)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'error') return alert(data.message);
        allData = data.data || []; 
        applyFilterAndSort(); 
    })
    .catch(err => console.error("Error fetching data:", err));
}

function changeSortCriterion() {
    const val = document.getElementById('sortCriterion').value;
    
    // Jika user memilih 'None', kembalikan sistem ke pengurutan default (DATE)
    if (val === 'NONE' || val === '') {
        currentSortBy = 'DATE';
    } else {
        currentSortBy = val;
    }
    
    applyFilterAndSort();
}

function toggleSortOrder() {
    currentSortOrder = currentSortOrder === 'DESC' ? 'ASC' : 'DESC';
    document.getElementById('btnSortOrder').innerHTML = currentSortOrder === 'DESC' ? 'Descending ↓' : 'Ascending ↑';
    applyFilterAndSort();
}

function debounceSearch() {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(applyFilterAndSort, 250);
}

// BULLETPROOF FILTERING ENGINE
function applyFilterAndSort() {
    const search = document.getElementById('searchReport').value.toLowerCase().trim();
    
    // Tarik nilai bulan & tahun dari UI untuk Double-Check
    const targetTahun = parseInt(document.getElementById('filterTahun').value) || 0;
    const targetBulan = parseInt(document.getElementById('filterBulan').value) || 0;
    
    filteredData = allData.filter(row => {
        const rawTgl = row.tanggal_pembelian ? row.tanggal_pembelian.toString() : '';
        
        // 1. Validasi Ekstra Bulan & Tahun (Jika SQL gagal memfilter)
        if (rawTgl.length >= 10) {
            const rowYear = parseInt(rawTgl.substring(0, 4));
            const rowMonth = parseInt(rawTgl.substring(5, 7));
            if (targetTahun !== 0 && rowYear !== targetTahun) return false;
            if (targetBulan !== 0 && rowMonth !== targetBulan) return false;
        }

        // 2. Logika Pencarian Aman (Anti Error / Null)
        let tglFormatted = rawTgl;
        if (rawTgl.length >= 10) {
            tglFormatted = rawTgl.substring(0, 10).split('-').reverse().join('-');
        }

        const daftarKartu = (row.daftar_kartu || '').toString().toLowerCase();
        const customer = (row.nama_customer || '').toString().toLowerCase();
        const harga = (row.total_harga || '').toString().toLowerCase();
        const resi = (row.no_resi || '').toString().toLowerCase();

        if (search !== '') {
            return customer.includes(search) || 
                   daftarKartu.includes(search) || 
                   harga.includes(search) || 
                   tglFormatted.includes(search) ||
                   resi.includes(search);
        }
        return true;
    });

    filteredData.sort((a, b) => {
        let valA, valB;
        
        if (currentSortBy === 'DATE') {
            valA = new Date(a.tanggal_pembelian || 0).getTime();
            valB = new Date(b.tanggal_pembelian || 0).getTime();
        } else if (currentSortBy === 'PRICE') {
            valA = parseFloat(a.total_harga || 0);
            valB = parseFloat(b.total_harga || 0);
        } else if (currentSortBy === 'QTY') {
            valA = parseInt(a.total_barang || 0);
            valB = parseInt(b.total_barang || 0);
        }

        if (valA === valB) return 0;
        if (currentSortOrder === 'DESC') {
            return valA < valB ? 1 : -1;
        } else {
            return valA < valB ? -1 : 1;
        }
    });
    let totalItems = 0;
    let totalPaid = 0;
    filteredData.forEach(row => {
        totalItems += parseInt(row.total_barang || 0);
        totalPaid += parseFloat(row.total_harga || 0);
    });

    document.getElementById('summaryTotalItems').innerText = totalItems.toLocaleString('id-ID') + ' Pcs';
    document.getElementById('summaryTotalPaid').innerText = 'Rp ' + totalPaid.toLocaleString('id-ID');
    currentPage = 1; 
    renderTable();
}

function renderTable() {
    const tbody = document.querySelector('#tableLaporan tbody');
    tbody.innerHTML = '';
    
    if (filteredData.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2rem; opacity:0.6;">No data matches your search.</td></tr>`;
        document.getElementById('paginationReport').innerHTML = '';
        return;
    }

    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    const startIdx = (currentPage - 1) * itemsPerPage;
    const endIdx = startIdx + itemsPerPage;
    const pageData = filteredData.slice(startIdx, endIdx);

    let startNo = startIdx + 1;
    pageData.forEach(row => {
        const tanggal = row.tanggal_pembelian ? row.tanggal_pembelian.substring(0, 10).split('-').reverse().join('-') : 'N/A';
        
        let tr = `<tr class="trx-row">
            <td style="text-align:center;">${startNo++}</td>
            <td style="white-space:nowrap;">${tanggal}</td>
            <td style="font-weight:600;">${row.nama_customer}</td>
            <td><div class="card-list-cell">${row.daftar_kartu || '-'}</div></td>
            <td style="text-align:right; font-weight:600; padding-right: 1rem;">${row.total_barang} Pcs</td>
            <td style="text-align:right; font-weight:700; padding-right: 1rem;">Rp ${parseInt(row.total_harga).toLocaleString('id-ID')}</td>
            <td style="text-align:center; padding:0.5rem 0;">
                <div width="100%" style="display:flex; justify-content:center; align-items:center;">
                    <button class="btn-view-icon" onclick="openDetailModal(${row.id_pembelian})">...</button>
                </div>
                
            </td>
        </tr>`;
        tbody.innerHTML += tr;
    });

    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    const pagContainer = document.getElementById('paginationReport');
    if (totalPages <= 1) { pagContainer.innerHTML = ''; return; }

    let html = '';
    html += currentPage > 1 ? `<span onclick="changePage(${currentPage - 1})" class="page-link">&lt; Prev</span>` : `<span class="page-link disabled">&lt; Prev</span>`;
    for(let i = 1; i <= totalPages; i++) {
        html += `<span onclick="changePage(${i})" class="page-link ${i == currentPage ? 'active' : ''}">${i}</span>`;
    }
    html += currentPage < totalPages ? `<span onclick="changePage(${currentPage + 1})" class="page-link">Next &gt;</span>` : `<span class="page-link disabled">Next &gt;</span>`;
    pagContainer.innerHTML = html;
}

function changePage(page) {
    currentPage = page;
    renderTable();
}

function exportReport(type) {
    let tahunRaw = document.getElementById('filterTahun').value;
    const tahun = tahunRaw ? parseInt(tahunRaw) : 0;
    const bulan = document.getElementById('filterBulan').value;
    

    const search = document.getElementById('searchReport').value.trim();
    
    const activeSortOrder = sortBy === 'DATE' ? sortOrderDate : sortOrderPrice;
    
    const url = `${REPORT_CONTROLLER}?action=export_${type}&tahun=${tahun}&bulan=${bulan}&search=${encodeURIComponent(search)}&sort_by=${sortBy}&sort_order=${activeSortOrder}&role=${userRole}`;
    
    window.open(url, '_blank');
}

function openDetailModal(id) {
    const headerTitle = document.querySelector('#detailModal .modal-header h2');
    if (headerTitle) {
        headerTitle.innerHTML = `BUYBACK ID: <span class="blue-text">#${id}</span>`;
    }
    
    const modalStatus = document.getElementById('modalStatus');
    if (modalStatus) {
        modalStatus.innerHTML = `<span style="background: #dcfce7; color: #15803d; padding: 4px 15px; border-radius: 20px; font-size: 0.85rem; display: inline-block;">Completed</span>`;
    }

    document.getElementById('detailModal').style.display = 'flex';
    
    const content = document.getElementById('modalContent');
    content.innerHTML = `<div style="text-align:center; padding: 2rem; color:#888;">Loading transaction details...</div>`;

    fetch(`${REPORT_CONTROLLER}?action=get_detail&id=${id}&role=${userRole}`)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'error' || !data.data || data.data.length === 0) {
            content.innerHTML = `<div style="color:red; text-align:center; padding:2rem;">Failed to load details or data is empty.</div>`;
            return;
        }

        const info = data.data[0];
        let calcTotal = 0;

        let html = `
            <div style="display:flex; justify-content:space-between; margin-bottom:20px; background: #f8fafc; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div>
                    <span style="display:block; color:#64748b; font-size:0.8rem; margin-bottom:3px;">Customer</span>
                    <b style="color:var(--primary-color); font-size:1.1rem;">${info.nama_customer}</b>
                </div>
                <div style="text-align:right;">
                    <span style="display:block; color:#64748b; font-size:0.8rem; margin-bottom:3px;">Total Items</span>
                    <b style="color:#333; font-size:1.1rem;">${info.total_barang} Pcs</b>
                </div>
            </div>
            
            <h3 style="font-size: 1rem; color: #475569; margin-bottom: 12px; font-weight: 700; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">Card List</h3>
            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
        `;

        data.data.forEach(item => {
            let price = parseFloat(item.penawaran_admin) > 0 ? parseFloat(item.penawaran_admin) : parseFloat(item.penawaran_customer);
            calcTotal += price;

            let imgDepan = `/CardHaven/${item.foto_depan}`;
            let imgBelakang = `/CardHaven/${item.foto_belakang}`;

            html += `
                <div style="display: flex; gap: 15px; align-items: center; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    
                    <div style="display: flex; gap: 8px; flex-shrink: 0;">
                        <img src="${imgDepan}" style="width: 55px; height: 75px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; cursor: pointer; transition: 0.2s;" title="Front Photo" onclick="window.open(this.src, '_blank')" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <img src="${imgBelakang}" style="width: 55px; height: 75px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; cursor: pointer; transition: 0.2s;" title="Back Photo" onclick="window.open(this.src, '_blank')" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    
                    <div style="flex-grow: 1;">
                        <div style="font-weight: 700; color: #1e293b; font-size: 1.05rem; margin-bottom: 4px;">${item.nama_kartu}</div>
                        <div style="font-size: 0.8rem; color: #64748b;">Final Approved Price</div>
                    </div>
                    
                    <div style="text-align: right; flex-shrink: 0;">
                        <div style="font-weight: 800; color: var(--primary-color); font-size: 1rem;">Rp ${price.toLocaleString('id-ID')}</div>
                    </div>

                </div>
            `;
        });

        html += `</div>`; 

        html += `
            <div style="background: #dee8fc; border-radius: 12px; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #c6d8f9;">
                <span style="font-weight: 800; color: #0F3891; font-size: 1rem;">Total Paid</span>
                <span style="font-weight: 800; color: #27AE60; font-size: 1rem;">Rp ${calcTotal.toLocaleString('id-ID')}</span>
            </div>
        `;

        let imgBukti = info.bukti_pembayaran ? `/CardHaven/${info.bukti_pembayaran}` : '';
        if (imgBukti) {
            html += `
                <div style="margin-top:20px; text-align:center; background:#f8fafc; padding:20px; border-radius:12px; border:1px solid #e2e8f0;">
                    <h4 style="margin: 0 0 15px 0; color:#475569; font-size:0.95rem;">Payment Proof</h4>
                    <img src="${imgBukti}" onerror="this.style.display='none'" style="max-width: 100%; max-height: 250px; border-radius:8px; border:2px solid #cbd5e1; cursor:pointer; transition:0.2s;" onclick="window.open(this.src, '_blank')" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                </div>
            `;
        } else {
            html += `
                <div style="margin-top:20px; text-align:center; background:#f8fafc; padding:15px; border-radius:12px; border:1px solid #e2e8f0; color:#94a3b8; font-size:0.85rem;">
                    No payment proof available for this transaction.
                </div>
            `;
        }

        content.innerHTML = html;
    })
    .catch(err => {
        content.innerHTML = `<div style="color:red; text-align:center; padding:2rem;">Connection lost. Server error.</div>`;
    });
}

// Fungsi menutup modal
function closeDetailModal() {
    document.getElementById('detailModal').style.display = 'none';
}
document.addEventListener('DOMContentLoaded', fetchReportData);