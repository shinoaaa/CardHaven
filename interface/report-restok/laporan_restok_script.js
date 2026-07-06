const REPORT_CONTROLLER = '/cardhaven/interface/report-restok/controller_laporan_restok.php';
const idPengguna = sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna');
const userRole = sessionStorage.getItem('role') || localStorage.getItem('role');

let allData = [];
let filteredData = [];
let currentPage = 1;
const itemsPerPage = 10;
let currentSortBy = 'DATE';
let currentSortOrder = 'DESC';
let typingTimer;

// Laporan Restok cuma buat Owner (role 3)
if (!idPengguna || userRole != '3') window.location.href = 'login';

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
    }).catch(err => console.error(err));
}

function changeSortCriterion() {
    const val = document.getElementById('sortCriterion').value;
    currentSortBy = (val === 'NONE' || val === '') ? 'DATE' : val;
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
        const rawTgl = row.tanggal_restok ? row.tanggal_restok.toString() : '';

        if (rawTgl.length >= 10) {
            const rowYear = parseInt(rawTgl.substring(0, 4));
            const rowMonth = parseInt(rawTgl.substring(5, 7));
            if (targetTahun !== 0 && rowYear !== targetTahun) return false;
            if (targetBulan !== 0 && rowMonth !== targetBulan) return false;
        }

        let tglFormatted = rawTgl.length >= 10 ? rawTgl.substring(0, 10).split('-').reverse().join('-') : rawTgl;

        const supplier = (row.nama_suplier || '').toLowerCase();
        const produk = (row.daftar_produk || '').toLowerCase();
        const dibuatOleh = (row.dibuat_oleh || '').toLowerCase();
        const harga = (row.total_harga || '').toString().toLowerCase();

        if (search !== '') {
            return supplier.includes(search) || produk.includes(search) || dibuatOleh.includes(search) || harga.includes(search) || tglFormatted.includes(search);
        }
        return true;
    });

    filteredData.sort((a, b) => {
        let valA, valB;
        if (currentSortBy === 'DATE') {
            valA = new Date(a.tanggal_restok || 0).getTime();
            valB = new Date(b.tanggal_restok || 0).getTime();
        } else if (currentSortBy === 'PRICE') {
            valA = parseFloat(a.total_harga || 0);
            valB = parseFloat(b.total_harga || 0);
        } else if (currentSortBy === 'QTY') {
            valA = parseInt(a.total_barang || 0);
            valB = parseInt(b.total_barang || 0);
        }
        if (valA === valB) return 0;
        return currentSortOrder === 'DESC' ? (valA < valB ? 1 : -1) : (valA < valB ? -1 : 1);
    });

    let totalItems = 0; let totalPembelian = 0;
    filteredData.forEach(row => {
        totalItems += parseInt(row.total_barang || 0);
        totalPembelian += parseFloat(row.total_harga || 0);
    });

    document.getElementById('summaryTotalItems').innerText = totalItems.toLocaleString('id-ID') + ' Pcs';
    document.getElementById('summaryTotalPaid').innerText = 'Rp ' + totalPembelian.toLocaleString('id-ID');
    currentPage = 1;
    renderTable();
}

function renderTable() {
    const tbody = document.querySelector('#tableLaporan tbody');
    tbody.innerHTML = '';

    if (filteredData.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:2rem; opacity:0.6;">No data matches your search.</td></tr>`;
        document.getElementById('paginationReport').innerHTML = '';
        return;
    }

    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    const startIdx = (currentPage - 1) * itemsPerPage;
    const pageData = filteredData.slice(startIdx, startIdx + itemsPerPage);

    let startNo = startIdx + 1;
    pageData.forEach(row => {
        const tanggal = row.tanggal_restok ? row.tanggal_restok.substring(0, 10).split('-').reverse().join('-') : '-';

        let tr = `<tr class="trx-row">
            <td style="text-align:center;">${startNo++}</td>
            <td style="white-space:nowrap;">${tanggal}</td>
            <td style="font-weight:600;">${row.nama_suplier || '-'}</td>
            <td><div class="card-list-cell">${row.daftar_produk || '-'}</div></td>
            <td style="text-align:right; font-weight:600; padding-right: 1rem;">${row.total_barang} Pcs</td>
            <td style="text-align:right; font-weight:700; padding-right: 1rem;">Rp ${parseInt(row.total_harga).toLocaleString('id-ID')}</td>
            <td style="text-align:center;">
                <button class="btn-view-icon" onclick="openDetailModal(${row.id_restok})">...</button>
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
    for (let i = 1; i <= totalPages; i++) html += `<span onclick="changePage(${i})" class="page-link ${i == currentPage ? 'active' : ''}">${i}</span>`;
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
    if (headerTitle) headerTitle.innerHTML = `PO ID: <span class="blue-text">#${id}</span>`;

    document.getElementById('detailModal').style.display = 'flex';
    const content = document.getElementById('modalContent');
    content.innerHTML = `<div style="text-align:center; padding: 2rem; color:#888;">Loading PO details...</div>`;

    fetch(`${REPORT_CONTROLLER}?action=get_detail&id=${id}&role=${userRole}`)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'error' || !data.data || data.data.length === 0) {
            content.innerHTML = `<div style="color:red; text-align:center; padding:2rem;">Failed to load details.</div>`;
            return;
        }

        const trxGlobal = allData.find(x => x.id_restok == id);
        const supplierName = trxGlobal ? trxGlobal.nama_suplier : '-';
        const totalItems = trxGlobal ? trxGlobal.total_barang : '-';
        const totalHarga = trxGlobal ? trxGlobal.total_harga : 0;

        let html = `
            <div style="display:flex; justify-content:space-between; margin-bottom:20px; background: #f8fafc; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div>
                    <span style="display:block; color:#64748b; font-size:0.8rem; margin-bottom:3px;">Supplier</span>
                    <b style="color:var(--primary-color); font-size:1.1rem;">${supplierName}</b>
                </div>
                <div style="text-align:right;">
                    <span style="display:block; color:#64748b; font-size:0.8rem; margin-bottom:3px;">Total Items</span>
                    <b style="color:#333; font-size:1.1rem;">${totalItems} Pcs</b>
                </div>
            </div>

            <h3 style="font-size: 1rem; color: #475569; margin-bottom: 12px; font-weight: 700; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">Product List</h3>
            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
        `;

        data.data.forEach(item => {
            let prodPath = item.foto_produk;
            prodPath = (prodPath && !prodPath.includes('/'))
                ? `/CardHaven/assets/image/products/${prodPath}`
                : `/CardHaven/${prodPath}`;

            html += `
                <div style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    <div style="display: flex; gap: 12px; align-items: center; flex-grow: 1;">
                        <img src="${prodPath}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1; flex-shrink: 0; cursor: pointer;" title="Lihat Foto" onclick="window.open(this.src, '_blank')">
                        <div>
                            <div style="font-weight: 700; color: #1e293b; font-size: 1.05rem; margin-bottom: 4px;">${item.nama_produk}</div>
                            <div style="font-size: 0.85rem; color: #64748b;">Qty: <b style="color:#0F3891;">${item.jumlah_barang}</b></div>
                        </div>
                    </div>
                    <div style="text-align: right; padding-right: 15px; border-right: 1px dashed #cbd5e1; margin-right: 15px;">
                        <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 2px;">Harga Beli / Item</div>
                        <div style="font-size: 0.95rem; font-weight: 600; color: #333;">Rp ${parseInt(item.harga_beli).toLocaleString('id-ID')}</div>
                    </div>
                    <div style="text-align: right; min-width: 120px;">
                        <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 2px;">Subtotal</div>
                        <div style="font-weight: 800; color: var(--primary-color); font-size: 1.1rem;">Rp ${parseInt(item.subtotal_harga).toLocaleString('id-ID')}</div>
                    </div>
                </div>
            `;
        });

        html += `</div>
            <div style="background: #dee8fc; border-radius: 12px; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #c6d8f9;">
                <span style="font-weight: 800; color: #0F3891; font-size: 1rem;">TOTAL PEMBELIAN</span>
                <span style="font-weight: 800; color: #27AE60; font-size: 1.25rem;">Rp ${parseFloat(totalHarga).toLocaleString('id-ID')}</span>
            </div>
        `;

        content.innerHTML = html;
    }).catch(err => { content.innerHTML = `<div style="color:red; text-align:center; padding:2rem;">Server error.</div>`; });
}

function closeDetailModal() { document.getElementById('detailModal').style.display = 'none'; }
document.addEventListener('DOMContentLoaded', fetchReportData);
