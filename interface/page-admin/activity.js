/**
 * activity.js — Activity Dashboard
 * Kartu statistik + grouped bar chart (Sales/Buyback/Restok per bulan) +
 * Recent Activity (10 transaksi terbaru gabungan, klik → buka modal di halamannya).
 */
const DASH_API = '/cardhaven/interface/page-admin/dashboard_controller.php';

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// ── Meta per jenis transaksi: ikon, warna, url tujuan, & label status ──────────
const ACT_META = {
    sales: {
        icon: '🛒', tagBg: '#e7f0ff', tagColor: '#1d4ed8', badgeBg: '#e7f0ff', label: 'Sales',
        url: (id) => `/CardHaven/dashboard/transaction?open_sales=${id}`,
        status: {
            0: ['Pending Payment', '#fef9c3', '#ca8a04'], 1: ['Paid', '#dcfce7', '#15803d'],
            2: ['Waiting Stock', '#e0f2fe', '#0369a1'], 3: ['Processing', '#ede9fe', '#7c3aed'],
            4: ['Shipped', '#dbeafe', '#1d4ed8'], 5: ['Delivered', '#d1fae5', '#065f46'],
            6: ['Completed', '#d1fae5', '#14532d'], 7: ['Returned', '#fee2e2', '#b91c1c'],
            8: ['Cancelled', '#f3f4f6', '#6b7280'],
        },
    },
    buyback: {
        icon: '🔄', tagBg: '#f1ecff', tagColor: '#7c3aed', badgeBg: '#f1ecff', label: 'Buyback',
        url: (id) => `/CardHaven/dashboard/purchase?type=buyback&open_buyback=${id}`,
        status: {
            0: ['Pending Submission', '#fef9c3', '#ca8a04'], 1: ['Under Review', '#e0f2fe', '#0369a1'],
            2: ['Price Negotiation', '#ede9fe', '#7c3aed'], 3: ['Offer Accepted', '#d1fae5', '#065f46'],
            4: ['Card Shipped', '#dbeafe', '#1d4ed8'], 5: ['Card Received', '#d1fae5', '#065f46'],
            6: ['Quality Checked', '#d1fae5', '#065f46'], 7: ['Payment Sent', '#ede9fe', '#7c3aed'],
            8: ['Completed', '#d1fae5', '#14532d'], 9: ['Rejected', '#fee2e2', '#b91c1c'],
            10: ['Cancelled', '#f3f4f6', '#6b7280'],
        },
    },
    restok: {
        icon: '📦', tagBg: '#e9fbf0', tagColor: '#15803d', badgeBg: '#e9fbf0', label: 'Restock',
        url: (id) => `/CardHaven/dashboard/purchase?type=restok&open_restok=${id}`,
        status: {
            0: ['Pending', '#fef9c3', '#ca8a04'], 1: ['Approved', '#dbeafe', '#1d4ed8'],
            2: ['Rejected', '#fee2e2', '#b91c1c'], 3: ['Received', '#d1fae5', '#065f46'],
            4: ['Paid', '#d1fae5', '#14532d'],
        },
    },
};

function dashRupiah(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

// Rp singkat untuk sumbu chart (mis. 88jt, 1.2rb).
function dashShortRp(n) {
    n = Number(n) || 0;
    if (n >= 1e9) return (n / 1e9).toFixed(1).replace(/\.0$/, '') + 'M';
    if (n >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'jt';
    if (n >= 1e3) return (n / 1e3).toFixed(0) + 'rb';
    return String(n);
}

function dashEsc(v) {
    return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// ── Kartu statistik ───────────────────────────────────────────────────────────
function dashLoadStats() {
    fetch(`${DASH_API}?action=stats`)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') return;
            const d = res.data;
            document.getElementById('statSales').textContent     = dashRupiah(d.total_sales);
            document.getElementById('statOrders').textContent    = Number(d.total_orders).toLocaleString('id-ID');
            document.getElementById('statCustomers').textContent = Number(d.total_customers).toLocaleString('id-ID');
            document.getElementById('statOOS').textContent       = Number(d.out_of_stock).toLocaleString('id-ID');
        })
        .catch(e => console.error('stats error', e));
}

// ── Grouped bar chart ──────────────────────────────────────────────────────────
let dashChartInstance = null;

function dashLoadChart(year) {
    fetch(`${DASH_API}?action=chart&year=${year}`)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') return;
            const rows    = res.data || [];
            const sales   = rows.map(r => r.sales);
            const buyback = rows.map(r => r.buyback);
            const restok  = rows.map(r => r.restok);
            dashRenderChart(sales, buyback, restok);
        })
        .catch(e => console.error('chart error', e));
}

function dashRenderChart(sales, buyback, restok) {
    const ctx = document.getElementById('dashChart');
    if (!ctx || typeof Chart === 'undefined') return;

    const mk = (label, data, color) => ({
        label, data, backgroundColor: color, borderRadius: 5, maxBarThickness: 16,
    });

    const datasets = [
        mk('Sales', sales, '#0088FF'),
        mk('Buyback', buyback, '#fcdc4e'),
        mk('Restock', restok, '#e39037'),
    ];

    if (dashChartInstance) {
        dashChartInstance.data.datasets.forEach((ds, i) => { ds.data = datasets[i].data; });
        dashChartInstance.update();
        return;
    }

    dashChartInstance = new Chart(ctx, {
        type: 'bar',
        data: { labels: MONTHS, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, font: { size: 11 } } },
                tooltip: { callbacks: { label: (c) => `${c.dataset.label}: ${dashRupiah(c.parsed.y)}` } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: { beginAtZero: true, ticks: { callback: (v) => dashShortRp(v), font: { size: 10 } }, grid: { color: '#eef1f6' } },
            },
        },
    });
}

// ── Recent Activity ─────────────────────────────────────────────────────────────
function dashLoadRecent() {
    const box = document.getElementById('dashActivityList');
    fetch(`${DASH_API}?action=recent&limit=15`)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') {
                box.innerHTML = '<p style="text-align:center;color:#e74c3c;padding:2rem 0;">Failed to load.</p>';
                return;
            }
            const rows = res.data || [];
            if (!rows.length) {
                box.innerHTML = '<p style="text-align:center;color:#9aa4b2;padding:2rem 0;">No recent activity.</p>';
                return;
            }
            box.innerHTML = rows.map(dashActivityItem).join('');
        })
        .catch(() => { box.innerHTML = '<p style="text-align:center;color:#e74c3c;padding:2rem 0;">Failed to load.</p>'; });
}

function dashActivityItem(row) {
    const meta = ACT_META[row.jenis] || ACT_META.sales;
    const st   = meta.status[row.status_code] || ['Unknown', '#f3f4f6', '#6b7280'];
    const url  = meta.url(row.ref_id);
    const tgl  = row.tanggal ? dashEsc(row.tanggal) : '';

    return `<div class="dash-act-item" onclick="window.location.href='${url}'">
        <div class="dash-act-badge" style="background:${meta.badgeBg};">${meta.icon}</div>
        <div class="dash-act-main">
            <div class="dash-act-title">
                <span class="dash-type-tag" style="background:${meta.tagBg};color:${meta.tagColor};">${meta.label}</span>
                #${row.ref_id}
            </div>
            <div class="dash-act-sub">${dashEsc(row.pihak)} · ${tgl}</div>
        </div>
        <div class="dash-act-right">
            <div class="dash-act-amount">${dashRupiah(row.total_harga)}</div>
            <span class="dash-act-pill" style="background:${st[1]};color:${st[2]};">${st[0]}</span>
        </div>
    </div>`;
}

// ── Init ─────────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    // Dropdown tahun: tahun berjalan mundur 4 tahun.
    const sel = document.getElementById('dashYear');
    const nowY = new Date().getFullYear();
    for (let y = nowY; y >= nowY - 4; y--) {
        const o = document.createElement('option');
        o.value = y; o.textContent = y;
        sel.appendChild(o);
    }
    sel.value = nowY;

    dashLoadStats();
    dashLoadChart(nowY);
    dashLoadRecent();
});
