const REPORT_CONTROLLER = '/cardhaven/interface/report-buyback/controller_laporan_buyback.php';
const idPengguna = sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna');
const userRole = sessionStorage.getItem('role') || localStorage.getItem('role');

let allData = [];         // Menyimpan semua data mentah dari server
let filteredData = [];    // Menyimpan data hasil pencarian & sorting
let currentPage = 1;
const itemsPerPage = 10;
let sortOrder = 'DESC';
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

function toggleSort() {
    sortOrder = sortOrder === 'DESC' ? 'ASC' : 'DESC';
    document.getElementById('btnSort').innerHTML = sortOrder === 'DESC' ? 'Newest' : 'Oldest';
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
        const dateA = new Date(a.tanggal_pembelian || 0).getTime();
        const dateB = new Date(b.tanggal_pembelian || 0).getTime();
        return sortOrder === 'DESC' ? dateB - dateA : dateA - dateB;
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
        
        let tr = `<tr>
            <td style="text-align:center;">${startNo++}</td>
            <td style="white-space:nowrap;">${tanggal}</td>
            <td style="font-weight:600;">${row.nama_customer}</td>
            <td><div class="card-list-cell">${row.daftar_kartu || '-'}</div></td>
            <td style="text-align:right; font-weight:600; padding-right: 1rem">${row.total_barang} Pcs</td>
            <td style="text-align:right; font-weight:700; color:var(--primary-color); padding-right: 1.5rem">Rp ${parseInt(row.total_harga).toLocaleString('id-ID')}</td>
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
    const search = document.getElementById('searchReport').value;
    
    const url = `${REPORT_CONTROLLER}?action=export_${type}&tahun=${tahun}&bulan=${bulan}&search=${encodeURIComponent(search)}&sort=${sortOrder}&role=${userRole}`;
    window.open(url, '_blank');
}

document.addEventListener('DOMContentLoaded', fetchReportData);