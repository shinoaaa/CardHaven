// Tab Profit di halaman Report (Owner only).
// Ambil ringkasan bulanan dari profit_controller.php lalu render
// stat cards + tabel bulanan + bar chart (Revenue vs Gross Profit).

const PROFIT_API = '/cardhaven/interface/report-sales/profit_controller.php';
const PROFIT_MONTH_NAMES = ['January','February','March','April','May','June',
                            'July','August','September','October','November','December'];
let profitChartInstance = null;

function profitFormatRp(n) {
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}

async function profitLoad(tahun) {
    try {
        const res  = await fetch(`${PROFIT_API}?action=summary&tahun=${encodeURIComponent(tahun)}`);
        const data = await res.json();
        if (data.status !== 'success') throw new Error(data.message || 'Failed to load profit data.');

        profitFillYearSelect(data.years, data.tahun);
        profitRenderCards(data.total);
        profitRenderTable(data.months, data.total);
        profitRenderChart(data.months);
    } catch (err) {
        const tbody = document.getElementById('profitTableBody');
        if (tbody) tbody.innerHTML = `<tr><td colspan="6" style="color:#e74c3c;">${err.message}</td></tr>`;
    }
}

function profitFillYearSelect(years, active) {
    const sel = document.getElementById('profitYear');
    if (!sel || sel.options.length > 0) {
        // dropdown sudah terisi — cukup sinkronkan nilai aktif
        if (sel) sel.value = String(active);
        return;
    }
    if (!years || years.length === 0) years = [new Date().getFullYear()];
    years.forEach(y => {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        sel.appendChild(opt);
    });
    // Kalau tahun aktif tidak ada di daftar (mis. default tahun ini belum ada data),
    // pilih tahun pertama yang tersedia supaya dropdown & data selalu sinkron.
    sel.value = String(active);
    if (sel.selectedIndex < 0) sel.value = String(years[0]);
}

function profitRenderCards(total) {
    const margin = total.revenue > 0 ? (total.profit / total.revenue) * 100 : 0;
    const set = (id, text, color) => {
        const el = document.getElementById(id);
        if (el) { el.textContent = text; if (color) el.style.color = color; }
    };
    // Warna default ikut --pair-color di CSS; profit/margin dipaksa merah kalau minus.
    set('profitCardRevenue', profitFormatRp(total.revenue));
    set('profitCardCogs',    profitFormatRp(total.cogs));
    set('profitCardProfit',  profitFormatRp(total.profit), total.profit < 0 ? '#e74c3c' : '');
    set('profitCardMargin',  margin.toFixed(1) + ' %',     total.profit < 0 ? '#e74c3c' : '');
    set('profitCardRestok',  profitFormatRp(total.restok_spend));
    set('profitCardBuyback', profitFormatRp(total.buyback_spend));
    set('profitCardSold',    (total.items_sold || 0).toLocaleString('id-ID') + ' Pcs');
    set('profitCardOrders',  (total.orders || 0).toLocaleString('id-ID') + ' Orders');
}

function profitRenderTable(months, total) {
    const tbody = document.getElementById('profitTableBody');
    if (!tbody) return;

    tbody.innerHTML = months.map(m => {
        const profitColor = m.profit > 0 ? '#27AE60' : (m.profit < 0 ? '#e74c3c' : '#333');
        return `
            <tr>
                <td style="text-align:left; font-weight:600;">${PROFIT_MONTH_NAMES[m.bulan - 1]}</td>
                <td>${profitFormatRp(m.revenue)}</td>
                <td>${profitFormatRp(m.cogs)}</td>
                <td style="color:${profitColor}; font-weight:700;">${profitFormatRp(m.profit)}</td>
                <td>${profitFormatRp(m.restok_spend)}</td>
                <td>${profitFormatRp(m.buyback_spend)}</td>
            </tr>`;
    }).join('');

    const profitColor = total.profit > 0 ? '#27AE60' : (total.profit < 0 ? '#e74c3c' : '#333');
    tbody.innerHTML += `
        <tr style="border-top:2px solid var(--primary-color);">
            <td style="text-align:left; font-weight:800; color:var(--primary-color);">Total</td>
            <td style="font-weight:800;">${profitFormatRp(total.revenue)}</td>
            <td style="font-weight:800;">${profitFormatRp(total.cogs)}</td>
            <td style="font-weight:800; color:${profitColor};">${profitFormatRp(total.profit)}</td>
            <td style="font-weight:800;">${profitFormatRp(total.restok_spend)}</td>
            <td style="font-weight:800;">${profitFormatRp(total.buyback_spend)}</td>
        </tr>`;
}

function profitRenderChart(months) {
    const canvas = document.getElementById('profitChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const labels  = months.map(m => PROFIT_MONTH_NAMES[m.bulan - 1].slice(0, 3));
    const revenue = months.map(m => m.revenue);
    const profit  = months.map(m => m.profit);

    if (profitChartInstance) profitChartInstance.destroy();
    profitChartInstance = new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Revenue',      data: revenue, backgroundColor: 'rgba(53, 113, 234, 0.75)', borderRadius: 5 },
                { label: 'Gross Profit', data: profit,  backgroundColor: 'rgba(39, 174, 96, 0.75)',  borderRadius: 5 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 14, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: ${profitFormatRp(ctx.parsed.y)}` } }
            },
            scales: {
                y: { ticks: { callback: v => 'Rp ' + Number(v).toLocaleString('id-ID') , font: { size: 10 } } },
                x: { ticks: { font: { size: 10 } } }
            }
        }
    });
}

// Export Excel/PDF mengikuti tahun yang sedang dipilih. Nama file & style
// mengikuti report lain (Laporan_Profit_<tanggal>.xls/.pdf, kop CardHaven).
function profitExport(type) {
    const sel = document.getElementById('profitYear');
    const tahun = sel ? (sel.value || 0) : 0;
    const url = `${PROFIT_API}?action=export_${type}&tahun=${encodeURIComponent(tahun)}`;
    window.open(url, '_blank');
}

document.addEventListener('DOMContentLoaded', () => {
    profitLoad(new Date().getFullYear());
});
