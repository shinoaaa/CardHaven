<?php // Activity Dashboard — di-include dari page-admin/index.php (route: dashboard/activity) ?>
<link rel="stylesheet" href="/cardhaven/interface/global.css">
<style>
    .dash-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.25rem;
        flex-shrink: 0;
    }
    .dash-stat-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 15px rgba(0,0,0,.05);
        padding: 1.25rem 1.4rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .dash-stat-icon {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .dash-stat-label { font-size: .78rem; color: #8a95a5; margin: 0 0 .15rem 0; }
    .dash-stat-value { font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1.1; }

    .dash-main-grid {
        display: grid;
        grid-template-columns: 1.6fr 1fr;
        gap: 1.5rem;
        flex-shrink: 0;
    }
    @media (max-width: 1100px) { .dash-main-grid { grid-template-columns: 1fr; } .dash-stats { grid-template-columns: repeat(2, 1fr); } }

    .dash-panel {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,.05);
        padding: 1.5rem;
        min-height: 460px;
        display: flex;
        flex-direction: column;
    }
    .dash-panel-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 1.25rem;
    }
    .dash-panel-title { font-size: 1.15rem; font-weight: 700; color: var(--primary-color); margin: 0; }
    .dash-panel-sub   { font-size: .75rem; color: #9aa4b2; margin: .15rem 0 0 0; }

    .dash-year-select {
        padding: 6px 12px; border: 1.5px solid #D0DAF0; border-radius: 9999px;
        font-size: .82rem; color: var(--primary-color); background: #fff; cursor: pointer; outline: none;
    }

    /* ── Recent Activity list ── */
    .dash-activity-list {
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: .55rem;
        max-height: 420px;
        padding-right: .35rem;
    }
    .dash-activity-list::-webkit-scrollbar { width: 7px; }
    .dash-activity-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
    .dash-activity-list::-webkit-scrollbar-thumb:hover { background: var(--primary-color); }

    .dash-act-item {
        display: flex; align-items: center; gap: .85rem;
        padding: .7rem .8rem;
        border: 1px solid #eef1f6;
        border-radius: 12px;
        cursor: pointer;
        transition: background .15s, border-color .15s, transform .1s;
    }
    .dash-act-item:hover { background: #f6f9ff; border-color: #d5e2ff; transform: translateY(-1px); }
    .dash-act-badge {
        width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
    }
    .dash-act-main { flex: 1; min-width: 0; }
    .dash-act-title { font-weight: 700; font-size: .86rem; color: #1f2937; display: flex; align-items: center; gap: .4rem; }
    .dash-act-sub   { font-size: .74rem; color: #8a95a5; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .dash-act-right { text-align: right; flex-shrink: 0; }
    .dash-act-amount { font-weight: 800; font-size: .85rem; color: #1f2937; white-space: nowrap; }
    .dash-act-pill {
        display: inline-block; margin-top: .2rem;
        padding: 2px 8px; border-radius: 999px; font-size: .64rem; font-weight: 700; white-space: nowrap;
    }
    .dash-type-tag {
        font-size: .6rem; font-weight: 800; text-transform: uppercase; letter-spacing: .5px;
        padding: 1px 6px; border-radius: 5px;
    }
</style>

<div class="main-content">
    <h1 class="coolveticaa" style="color: var(--primary-color); font-size: 2rem; font-weight: 700; margin: 0;">Dashboard</h1>

    <!-- Kartu statistik -->
    <div class="dash-stats">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:#e7f0ff;">🛒</div>
            <div>
                <p class="dash-stat-label">Total Sales</p>
                <p class="dash-stat-value" id="statSales">—</p>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:#e9fbf0;">📄</div>
            <div>
                <p class="dash-stat-label">Orders</p>
                <p class="dash-stat-value" id="statOrders">—</p>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:#f1ecff;">👥</div>
            <div>
                <p class="dash-stat-label">Customers</p>
                <p class="dash-stat-value" id="statCustomers">—</p>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:#ffecec;">📦</div>
            <div>
                <p class="dash-stat-label">Out Of Stock</p>
                <p class="dash-stat-value" id="statOOS">—</p>
            </div>
        </div>
    </div>

    <!-- Chart + Recent Activity -->
    <div class="dash-main-grid">
        <!-- Bar chart -->
        <div class="dash-panel">
            <div class="dash-panel-head">
                <div>
                    <p class="dash-panel-title">Transaction Overview</p>
                    <p class="dash-panel-sub">Monthly total (Rp) for Sales, Buyback &amp; Restock</p>
                </div>
                <select id="dashYear" class="dash-year-select" onchange="dashLoadChart(this.value)"></select>
            </div>
            <div style="flex:1; position:relative; min-height:300px;">
                <canvas id="dashChart"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="dash-panel">
            <div class="dash-panel-head">
                <div>
                    <p class="dash-panel-title">Recent Activity</p>
                    <p class="dash-panel-sub">Latest 15 · click to open</p>
                </div>
            </div>
            <div class="dash-activity-list" id="dashActivityList">
                <p style="text-align:center; color:#9aa4b2; padding:2rem 0;">Loading…</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/cardhaven/interface/page-admin/activity.js?v=<?= time() ?>"></script>
