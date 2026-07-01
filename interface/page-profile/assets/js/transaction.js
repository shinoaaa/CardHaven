const BUYBACK_CONTROLLER = '/cardhaven/interface/buyback/controller_buyback.php';
const profileUserId = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');

// Start default behavior ke Buy Product persis seperti di layout
document.addEventListener('DOMContentLoaded', () => {
    switchTab('buyproduct');
    // Muat riwayat buyback (dipindahkan ke halaman profil customer)
    loadBuybackHistory();
});

function switchTab(tabName) {
    // Matikan semua tanda aktif di tombol
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));

    // Sembunyikan semua section tabel
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => content.style.display = 'none');

    // Nyalakan tanda pada tombol yang dipilih
    const targetBtn = document.querySelector(`.tab-btn[onclick="switchTab('${tabName}')"]`);
    if(targetBtn) targetBtn.classList.add('active');

    // Tampilkan data/tabel yang bersangkutan
    const targetContent = document.getElementById(`tab-${tabName}`);
    if(targetContent) targetContent.style.display = 'block';

    if (tabName === 'buyback') loadBuybackHistory();
}

// ── Riwayat Buyback di halaman profil ────────────────────────────────
function buybackStatusLabel(status) {
    const statuses = ["Pending Submission", "Under Review", "Price Negotiation", "Offer Accepted",
        "Card Shipped", "Card Received", "Quality Checked", "Payment Sent", "Completed", "Rejected", "Cancelled"];
    return statuses[status] || "Unknown";
}

function loadBuybackHistory() {
    const tbody = document.getElementById('buyback-history-body');
    if (!tbody) return;

    if (!profileUserId) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;">Please login to see your buyback history.</td></tr>`;
        return;
    }

    fetch(`${BUYBACK_CONTROLLER}?action=get_buyback_list&role=0&id_pengguna=${profileUserId}`)
        .then(res => res.json())
        .then(res => {
            const rows = (res && res.data) ? res.data : [];
            if (rows.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No BuyBack records yet.</td></tr>`;
                return;
            }

            tbody.innerHTML = '';
            rows.forEach((row, index) => {
                let tanggal = 'N/A';
                if (row.tanggal_pembelian) {
                    const [tahun, bulan, hari] = row.tanggal_pembelian.substring(0, 10).split('-');
                    tanggal = `${hari}-${bulan}-${tahun}`;
                }
                const total = parseInt(row.total_harga || 0).toLocaleString('id-ID');
                const aksi = `<a href="/cardhaven/interface/buyback/customer.php" class="filter-btn" style="text-decoration:none;">View</a>`;

                tbody.innerHTML += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>#${row.id_pembelian}</td>
                        <td>${tanggal}</td>
                        <td>${row.total_barang ?? '-'}</td>
                        <td><span class="status-pill">${buybackStatusLabel(row.status_pembelian)}</span></td>
                        <td>Rp ${total}</td>
                        <td>${aksi}</td>
                    </tr>`;
            });
        })
        .catch(err => {
            console.error('Failed to load buyback history:', err);
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#dc2626;">Failed to load buyback history.</td></tr>`;
        });
}
