const REPORT_CONTROLLER = '/cardhaven/interface/report-event/controller_laporan_event.php';
const idPengguna = sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna');
const userRole = sessionStorage.getItem('role') || localStorage.getItem('role');

let allData = [];         // Menyimpan semua data mentah dari server
let filteredData = [];    // Menyimpan data hasil pencarian & sorting
let currentPage = 1;
const itemsPerPage = 10;
let currentSortBy = 'DATE';
let currentSortOrder = 'DESC';
let typingTimer;

if (!idPengguna || (userRole != '2' && userRole != '3')) window.location.href = 'login';

// Helper: format 'YYYY-MM-DD HH:ii:ss' menjadi 'DD-MM-YYYY'
function formatTanggal(raw) {
    if (!raw) return '-';
    const str = raw.toString();
    if (str.length >= 10) return str.substring(0, 10).split('-').reverse().join('-');
    return str;
}

// Helper: kapitalisasi tipe event (promo / preorder)
function formatTipe(tipe) {
    if (!tipe) return '-';
    return tipe.charAt(0).toUpperCase() + tipe.slice(1);
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
    }).catch(err => console.error("Error fetching data:", err));
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

function applyFilterAndSort() {
    const search = document.getElementById('searchReport').value.toLowerCase().trim();
    const targetTahun = parseInt(document.getElementById('filterTahun').value) || 0;
    const targetBulan = parseInt(document.getElementById('filterBulan').value) || 0;

    filteredData = allData.filter(row => {
        const rawTgl = row.tanggal_mulai ? row.tanggal_mulai.toString() : '';

        // Validasi ekstra bulan & tahun (jika SQL gagal memfilter)
        if (rawTgl.length >= 10) {
            const rowYear = parseInt(rawTgl.substring(0, 4));
            const rowMonth = parseInt(rawTgl.substring(5, 7));
            if (targetTahun !== 0 && rowYear !== targetTahun) return false;
            if (targetBulan !== 0 && rowMonth !== targetBulan) return false;
        }

        let tglFormatted = formatTanggal(rawTgl);

        const namaEvent = (row.nama_event || '').toString().toLowerCase();
        const tipeEvent = (row.tipe_event || '').toString().toLowerCase();
        const produk = (row.daftar_produk || '').toString().toLowerCase();
        const revenue = (row.total_harga || '').toString().toLowerCase();

        if (search !== '') {
            return namaEvent.includes(search) ||
                   tipeEvent.includes(search) ||
                   produk.includes(search) ||
                   revenue.includes(search) ||
                   tglFormatted.includes(search);
        }
        return true;
    });

    filteredData.sort((a, b) => {
        let valA, valB;

        if (currentSortBy === 'DATE') {
            valA = new Date(a.tanggal_mulai || 0).getTime();
            valB = new Date(b.tanggal_mulai || 0).getTime();
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

    let totalItems = 0; let totalRevenue = 0;
    filteredData.forEach(row => {
        totalItems += parseInt(row.total_barang || 0);
        totalRevenue += parseFloat(row.total_harga || 0);
    });

    document.getElementById('summaryTotalItems').innerText = totalItems.toLocaleString('id-ID') + ' Pcs';
    document.getElementById('summaryTotalRevenue').innerText = 'Rp ' + totalRevenue.toLocaleString('id-ID');
    currentPage = 1;
    renderTable();
}

function renderTable() {
    const tbody = document.querySelector('#tableLaporan tbody');
    tbody.innerHTML = '';

    if (filteredData.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:2rem; opacity:0.6;">No data matches your search.</td></tr>`;
        document.getElementById('paginationReport').innerHTML = '';
        return;
    }

    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    const startIdx = (currentPage - 1) * itemsPerPage;
    const pageData = filteredData.slice(startIdx, startIdx + itemsPerPage);

    let startNo = startIdx + 1;
    pageData.forEach(row => {
        const periode = `${formatTanggal(row.tanggal_mulai)} <span style="color:#94a3b8;">&rarr;</span> ${formatTanggal(row.tanggal_berakhir)}`;
        const diskon = parseInt(row.persen_diskon || 0);
        const badgeType = `<span style="background:#e0e7ff; color:#3730a3; padding:2px 8px; border-radius:6px; font-size:0.72rem; font-weight:700;">${formatTipe(row.tipe_event)}</span>`;
        const badgeDiskon = diskon > 0
            ? `<span style="background:#fee2e2; color:#b91c1c; padding:2px 8px; border-radius:6px; font-size:0.72rem; font-weight:700; margin-left:4px;">-${diskon}%</span>`
            : '';

        let tr = `<tr class="trx-row">
            <td style="text-align:center;">${startNo++}</td>
            <td style="font-weight:600;">${row.nama_event || '-'}</td>
            <td style="white-space:nowrap; font-size:0.85rem;">${periode}</td>
            <td style="text-align:center; white-space:nowrap;">${badgeType}${badgeDiskon}</td>
            <td style="text-align:right; font-weight:600; padding-right: 1rem;">${row.total_barang} Pcs</td>
            <td style="text-align:right; font-weight:700; padding-right: 1rem;">Rp ${(parseInt(row.total_harga) || 0).toLocaleString('id-ID')}</td>
            <td style="text-align:center;">
                <div width="100%" style="display:flex; justify-content:center; align-items:center;">
                    <button class="btn-view-icon" onclick="openDetailModal(${row.id_event})">...</button>
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
    for(let i = 1; i <= totalPages; i++) html += `<span onclick="changePage(${i})" class="page-link ${i == currentPage ? 'active' : ''}">${i}</span>`;
    html += currentPage < totalPages ? `<span onclick="changePage(${currentPage + 1})" class="page-link">Next &gt;</span>` : `<span class="page-link disabled">Next &gt;</span>`;
    pagContainer.innerHTML = html;
}

function changePage(page) { currentPage = page; renderTable(); }

function exportReport(type) {
    const tahun = document.getElementById('filterTahun').value || 0;
    const bulan = document.getElementById('filterBulan').value || 0;
    const search = document.getElementById('searchReport').value.trim();

    const url = `${REPORT_CONTROLLER}?action=export_${type}&tahun=${tahun}&bulan=${bulan}&search=${encodeURIComponent(search)}&sort_by=${currentSortBy}&sort_order=${currentSortOrder}&role=${userRole}`;

    window.open(url, '_blank');
}

function openDetailModal(id) {
    const headerTitle = document.querySelector('#detailModal .modal-header h2');
    if (headerTitle) headerTitle.innerHTML = `EVENT ID: <span class="blue-text">#EVT${id}</span>`;

    document.getElementById('detailModal').style.display = 'flex';
    const content = document.getElementById('modalContent');
    content.innerHTML = `<div style="text-align:center; padding: 2rem; color:#888;">Loading event details...</div>`;

    fetch(`${REPORT_CONTROLLER}?action=get_detail&id=${id}&role=${userRole}`)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'error' || !data.data) {
            content.innerHTML = `<div style="color:red; text-align:center; padding:2rem;">Failed to load details.</div>`;
            return;
        }

        // Ambil info global dari allData (UDF detail hanya menarik rincian per produk)
        const evGlobal = allData.find(x => x.id_event == id);
        const eventName = evGlobal ? evGlobal.nama_event : '-';
        const eventType = evGlobal ? formatTipe(evGlobal.tipe_event) : '-';
        const totalItems = evGlobal ? evGlobal.total_barang : '-';
        const totalRevenue = evGlobal ? evGlobal.total_harga : 0;

        let html = `
            <div style="display:flex; justify-content:space-between; margin-bottom:20px; background: #f8fafc; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div>
                    <span style="display:block; color:#64748b; font-size:0.8rem; margin-bottom:3px;">Event</span>
                    <b style="color:var(--primary-color); font-size:1.1rem;">${eventName}</b>
                </div>
                <div>
                    <span style="display:block; color:#64748b; font-size:0.8rem; margin-bottom:3px;">Type</span>
                    <b style="color:#333; font-size:1rem;">${eventType}</b>
                </div>
                <div style="text-align:right;">
                    <span style="display:block; color:#64748b; font-size:0.8rem; margin-bottom:3px;">Total Items Sold</span>
                    <b style="color:#333; font-size:1.1rem;">${totalItems} Pcs</b>
                </div>
            </div>

            <h3 style="font-size: 1rem; color: #475569; margin-bottom: 12px; font-weight: 700; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">Product List</h3>
            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
        `;

        if (data.data.length === 0) {
            html += `<div style="text-align:center; color:#94a3b8; padding:1.5rem;">No products registered for this event.</div>`;
        }

        data.data.forEach(item => {
            let diskon = parseInt(item.diskon);
            let hrgTampilHtml = '';

            // Ekstrak URL Foto Produk (fallback default jika null / tanpa path)
            let prodPath = item.foto;
            if (prodPath && !prodPath.includes('/')) {
                prodPath = `/CardHaven/assets/image/products/${prodPath}`;
            } else {
                prodPath = `/CardHaven/${prodPath}`;
            }

            if (diskon > 0) {
                hrgTampilHtml = `
                    <div style="font-size: 0.75rem; color: #94a3b8; text-decoration: line-through;">Rp ${parseInt(item.harga_produk).toLocaleString('id-ID')}</div>
                    <div style="font-size: 0.95rem; font-weight: 600; color: #E74C3C;">Rp ${parseInt(item.harga_diskon).toLocaleString('id-ID')} <span style="background:#fee2e2; color:#b91c1c; padding:2px 6px; border-radius:4px; font-size:0.7rem; font-weight:bold; margin-left:4px;">-${diskon}%</span></div>
                `;
            } else {
                hrgTampilHtml = `<div style="font-size: 0.95rem; font-weight: 600; color: #333;">Rp ${parseInt(item.harga_diskon).toLocaleString('id-ID')}</div>`;
            }

            html += `
                <div style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">

                    <div style="display: flex; gap: 12px; align-items: center; flex-grow: 1;">
                        <img src="${prodPath}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1; flex-shrink: 0; cursor: pointer; transition: 0.2s;" title="View Photo" onclick="window.open(this.src, '_blank')" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        <div>
                            <div style="font-weight: 700; color: #1e293b; font-size: 1.05rem; margin-bottom: 4px;">${item.nama_produk}</div>
                            <div style="font-size: 0.85rem; color: #64748b;">Qty Sold: <b style="color:#0F3891;">${item.jumlah_barang}</b></div>
                        </div>
                    </div>

                    <div style="text-align: right; padding-right: 15px; border-right: 1px dashed #cbd5e1; margin-right: 15px;">
                        <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 2px;">Event Price</div>
                        ${hrgTampilHtml}
                    </div>
                    <div style="text-align: right; min-width: 120px;">
                        <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 2px;">Subtotal</div>
                        <div style="font-weight: 800; color: var(--primary-color); font-size: 1.1rem;">Rp ${(parseInt(item.subtotal_harga) || 0).toLocaleString('id-ID')}</div>
                    </div>
                </div>
            `;
        });

        html += `</div>
            <div style="background: #dee8fc; border-radius: 12px; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #c6d8f9;">
                <span style="font-weight: 800; color: #0F3891; font-size: 1rem;">TOTAL REVENUE</span>
                <span style="font-weight: 800; color: #27AE60; font-size: 1.25rem;">Rp ${(parseFloat(totalRevenue) || 0).toLocaleString('id-ID')}</span>
            </div>
        `;

        content.innerHTML = html;
    }).catch(err => { content.innerHTML = `<div style="color:red; text-align:center; padding:2rem;">Server error.</div>`; });
}

function closeDetailModal() { document.getElementById('detailModal').style.display = 'none'; }
document.addEventListener('DOMContentLoaded', fetchReportData);
