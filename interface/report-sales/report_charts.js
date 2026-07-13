/**
 * report_charts.js — kartu analitik di halaman Report.
 * - Grouped bar chart total per bulan (per jenis laporan: sales/buyback/restok/event).
 * - Top 3 selling items (khusus tab Sales).
 * Semua data via UDF di report_chart_controller.php.
 */
const RC_CTRL = '/cardhaven/interface/report-sales/report_chart_controller.php';
const RC_ROLE = sessionStorage.getItem('role') || localStorage.getItem('role') || 0;
const RC_TYPE = window.REPORT_TYPE || 'sales';
const RC_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const RC_COLOR = { sales: '#0088FF', buyback: '#FFC200', restok: '#FF4F00', event: '#27AE60' };
const RC_LABEL = { sales: 'Sales', buyback: 'Buyback', restok: 'Restock', event: 'Event' };

function rcRupiah(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); }
function rcEsc(v) { return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
function rcShortRp(n) {
    n = Number(n) || 0;
    if (n >= 1e9) return (n / 1e9).toFixed(1).replace(/\.0$/, '') + 'M';
    if (n >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'jt';
    if (n >= 1e3) return (n / 1e3).toFixed(0) + 'rb';
    return String(n);
}

// ── Bar chart ─────────────────────────────────────────────────────────────────
let rcChart = null;

function reportLoadChart(year) {
    fetch(`${RC_CTRL}?action=monthly&type=${RC_TYPE}&tahun=${year}&role=${RC_ROLE}`)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') return;
            rcRenderChart(res.data.map(d => d.total_harga), res.data.map(d => d.total_qty));
        })
        .catch(e => console.error('report chart error', e));

    if (RC_TYPE === 'sales') reportLoadTop(year);
}

function rcRenderChart(money, qty) {
    const ctx = document.getElementById('reportChart');
    if (!ctx || typeof Chart === 'undefined') return;
    const color = RC_COLOR[RC_TYPE] || '#0088FF';

    if (rcChart) {
        rcChart.data.datasets[0].data = money;
        rcChart._qty = qty;
        rcChart.update();
        return;
    }

    rcChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: RC_MONTHS,
            datasets: [{ label: `${RC_LABEL[RC_TYPE]} (Rp)`, data: money, backgroundColor: color, borderRadius: 5, maxBarThickness: 26 }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (c) => {
                            const q = (rcChart && rcChart._qty) ? rcChart._qty[c.dataIndex] : null;
                            return `${rcRupiah(c.parsed.y)}` + (q != null ? ` · ${q} item` : '');
                        },
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: { beginAtZero: true, ticks: { callback: (v) => rcShortRp(v), font: { size: 10 } }, grid: { color: '#eef1f6' } },
            },
        },
    });
    rcChart._qty = qty;
}

// ── Top 3 selling items (Sales) ────────────────────────────────────────────────
function rcProductImg(foto) {
    if (!foto) return '/CardHaven/image-profile/defaultProduct.jpg';
    return foto.includes('/') ? `/CardHaven/${foto}` : `/CardHaven/assets/image/products/${foto}`;
}

function reportLoadTop(year) {
    const box = document.getElementById('topSellingList');
    if (!box) return;
    fetch(`${RC_CTRL}?action=top_selling&tahun=${year}&bulan=0&limit=5&role=${RC_ROLE}`)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success' || !res.data.length) {
                box.innerHTML = '<p style="color:#94a3b8;font-size:.85rem;padding:.5rem 0;">No sales in this year.</p>';
                return;
            }
            box.innerHTML = res.data.map(rcTopItem).join('');
        })
        .catch(() => { box.innerHTML = '<p style="color:#e74c3c;font-size:.85rem;">Failed to load.</p>'; });
}

function rcTopItem(p, i) {
    const medal = ['#f59e0b', '#94a3b8', '#b45309'][i] || '#cbd5e1';
    return `<div style="display:flex;align-items:center;gap:.7rem;padding:.55rem 0;border-bottom:1px solid #eef1f6;">
        <div style="width:24px;height:24px;border-radius:50%;background:${medal};color:#fff;font-weight:800;font-size:.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">${i + 1}</div>
        <img src="${rcProductImg(p.foto)}" onerror="this.src='/CardHaven/image-profile/defaultProduct.jpg'" style="width:38px;height:38px;border-radius:8px;object-fit:cover;flex-shrink:0;background:#eef1f6;">
        <div style="flex:1;min-width:0;">
            <div style="font-weight:700;font-size:.83rem;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${rcEsc(p.nama_produk)}</div>
            <div style="font-size:.72rem;color:#94a3b8;">${p.total_qty} sold · ${rcRupiah(p.total_harga)}</div>
        </div>
    </div>`;
}

// ── Init ───────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('reportChartYear');
    if (!sel) return;
    const nowY = new Date().getFullYear();
    for (let y = nowY; y >= nowY - 4; y--) {
        const o = document.createElement('option');
        o.value = y; o.textContent = y;
        sel.appendChild(o);
    }
    sel.value = nowY;
    reportLoadChart(nowY);
});
