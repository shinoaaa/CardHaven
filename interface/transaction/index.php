<?php 
// 1. PERBAIKAN PATH: Gunakan __DIR__ agar file selalu ditemukan dari mana pun di-include
require_once __DIR__ . '/apifetch.php'; 

// Buyback dipindah ke halaman Purchase; Transaction sekarang khusus Sales.
$type = 'sales';

// Status mapping untuk PHP render (Khusus Sales)
$STATUS_LABEL = [
    0 => 'Pending Payment',
    1 => 'Paid',
    2 => 'Waiting Stock',
    3 => 'Processing',
    4 => 'Shipped',
    5 => 'Delivered',
    6 => 'Completed',
    7 => 'Returned',
    8 => 'Cancelled',
];

$STATUS_COLOR = [
    0 => ['bg' => '#fef9c3', 'color' => '#ca8a04'],
    1 => ['bg' => '#dcfce7', 'color' => '#15803d'],
    2 => ['bg' => '#e0f2fe', 'color' => '#0369a1'],
    3 => ['bg' => '#ede9fe', 'color' => '#7c3aed'],
    4 => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
    5 => ['bg' => '#d1fae5', 'color' => '#065f46'],
    6 => ['bg' => '#d1fae5', 'color' => '#14532d'],
    7 => ['bg' => '#fee2e2', 'color' => '#b91c1c'],
    8 => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
];

// 2. PERBAIKAN VARIABEL: Ambil data dari variabel yang telah disiapkan apifetch.php
$activeStatus = $status ?? null;
$activeSearch = $search ?? '';
$activeSortBy    = $sortBy ?? 'DATE';
$activeSortOrder = $sortOrder ?? 'DESC';

// Sesuaikan nama variabel untuk tabel HTML (karena kontroler baru memakai $data)
$stmt_trx = $data ?? [];

// Ambil jumlah angka per-status langsung dari controller yang sudah aktif di apifetch.php
$count_status = isset($ctrl) ? $ctrl->countPerStatus() : [];

// ── Data Master Payment Method (dipindah ke sini; hanya tampil untuk Owner via JS) ──
$limit_metode       = 3;
$page_metode        = max(1, (int)($_GET['pm'] ?? 1));
$data_metode        = [];
$total_pages_metode = 1;
$offset_metode      = 0;
if (isset($conn) && $conn !== false) {
    $stmt_cm = sqlsrv_query($conn, "SELECT dbo.udf_CountDashboard('metode') AS cm");
    if ($stmt_cm !== false) {
        $cm = sqlsrv_fetch_array($stmt_cm, SQLSRV_FETCH_ASSOC);
        $total_pages_metode = max(1, (int)ceil(($cm['cm'] ?? 0) / $limit_metode));
    }
    $page_metode   = min($page_metode, $total_pages_metode);
    $offset_metode = ($page_metode - 1) * $limit_metode;
    $stmt_m = sqlsrv_query(
        $conn,
        "SELECT * FROM dbo.metode_pembayaran WHERE is_deleted = 0 ORDER BY aktif DESC, id_metode ASC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
        [$offset_metode, $limit_metode]
    );
    if ($stmt_m !== false) while ($rowM = sqlsrv_fetch_array($stmt_m, SQLSRV_FETCH_ASSOC)) $data_metode[] = $rowM;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="/cardhaven/assets/image/logo.svg">
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <style>
        .main-content {
            padding-top: 20px !important; /* Tambahkan jarak dari atas */
            padding-left: 2rem;
            padding-right: 2rem;
            padding-bottom: 2rem;
            box-sizing: border-box;
        }

        .content-card {
            margin-top: 1rem;
            background: var(--card-bg, #ffffff);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        /* Memastikan tombol toggle Sales/Buyback tidak terpotong */
        .card-title-row {
            margin-bottom: 1.5rem;
        }
        /* ── Tab Filter ── */
        .trx-tabs {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .trx-tab {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .85rem;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border: 1.5px solid transparent;
            transition: all .18s;
            color: inherit;
            opacity: .6;
        }

        .trx-tab:hover { opacity: 1; }

        .trx-tab.active {
            opacity: 1;
            border-color: currentColor;
        }

        .trx-tab .tab-count {
            background: rgba(0,0,0,.12);
            border-radius: 999px;
            padding: 0 6px;
            font-size: .68rem;
            line-height: 1.6;
        }

        /* ── Search bar ── */
        .trx-search-wrap {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1rem;
        }

        .trx-search-input {
            flex: 1;
            max-width: 300px;
            padding: .45rem .85rem;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,.18);
            background: rgba(255,255,255,.06);
            color: inherit;
            font-size: .85rem;
            outline: none;
            transition: border-color .18s;
        }

        .trx-search-input:focus {
            border-color: var(--primary-color);
        }

        /* ── Modal ── */
        .trx-modal-overlay, .event-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 900;
            justify-content: center;
            align-items: flex-start;
            padding: 2rem 1rem;
            overflow-y: auto;
        }

        .trx-modal-overlay.show, .event-modal-overlay.show {
            display: flex;
        }

        .trx-modal-box, .modal-box {
            background: var(--card-bg, #ffffff);
            border-radius: 14px;
            padding: 1.75rem;
            width: min(700px, 96vw);
            position: relative;
            color: var(--text-dark, #333);
            box-shadow: 0 12px 50px rgba(0,0,0,.35);
            margin: auto;
        }

        .trx-modal-close, .event-modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            cursor: pointer;
            opacity: .55;
            padding: 0;
            font-size: 24px;
        }

        .trx-modal-close:hover, .event-modal-close:hover { opacity: 1; }

        .trx-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.25rem;
            padding-bottom: .85rem;
            border-bottom: 1px solid rgba(0,0,0,.1);
        }

        .trx-modal-id {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--primary-color);
        }

        .trx-modal-date {
            font-size: .75rem;
            opacity: .5;
            margin-top: .2rem;
        }

        .trx-modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .trx-modal-section {
            background: rgba(0,0,0,.04);
            border-radius: 10px;
            padding: .85rem 1rem;
        }

        .trx-section-title {
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .8px;
            opacity: .5;
            margin-bottom: .6rem;
        }

        .trx-info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: .8rem;
            margin-bottom: .35rem;
            gap: .5rem;
        }

        .trx-info-row span {
            opacity: .55;
            flex-shrink: 0;
        }

        .trx-info-row b {
            text-align: right;
            word-break: break-word;
        }

        .trx-action-row {
            display: flex;
            gap: .6rem;
            flex-wrap: wrap;
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0,0,0,.1);
        }

        .btn-trx-action {
            padding: .5rem 1.1rem;
            border-radius: 8px;
            border: none;
            font-size: .82rem;
            font-weight: 700;
            cursor: pointer;
            transition: filter .18s;
        }

        .btn-trx-action:hover { filter: brightness(1.15); }
        .btn-confirm  { background: #15803d; color: #fff; }
        .btn-process  { background: #7c3aed; color: #fff; }
        .btn-ship     { background: #1d4ed8; color: #fff; }
        .btn-deliver  { background: #065f46; color: #fff; }
        .btn-cancel   { background: #b91c1c; color: #fff; }
        .btn-cancel-outline { border: 2px solid var(--primary-color); color: var(--primary-color); background: transparent; font-weight: bold; border-radius: 8px;}

        /* ── Table clickable row ── */
        .trx-row { cursor: pointer; }
        .trx-row:hover td { background-color: rgba(15, 56, 145, 0.05) !important; }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="content-card">

            <div class="card-title-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 class="coolveticaa" style="margin: 0; font-size: 2rem; color: var(--primary-color);">Transaction</h2>
            </div>

            <?php if ($type === 'sales'): ?>
                <!-- ================== START SALES ================== -->
                <!-- Filter By + Sort By + Asc/Desc satu baris, Search full width di bawah -->
                <?php $trxPill = 'padding:8px 16px; border:1.5px solid #D0DAF0; border-radius:9999px; font-size:0.88rem; outline:none; background:white;'; ?>
                <div style="display:flex; flex-direction:column; gap:0.75rem; margin-bottom:1.25rem;">
                    <div style="display:flex; gap:0.75rem; flex-wrap:wrap; align-items:center;">
                        <select onchange="setTrxStatus(this.value)" style="width:200px; cursor:pointer; <?= $trxPill ?>">
                            <option value="" <?= $activeStatus === null ? 'selected' : '' ?>>All Status (<?= array_sum($count_status) ?>)</option>
                            <?php foreach ($STATUS_LABEL as $s => $label): ?>
                                <?php $cnt = $count_status[$s] ?? 0; ?>
                                <option value="<?= $s ?>" <?= ($activeStatus !== null && $activeStatus == $s) ? 'selected' : '' ?>>
                                    <?= $label ?><?= $cnt > 0 ? " ($cnt)" : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select onchange="setTrxSort(this.value)" style="width:180px; cursor:pointer; <?= $trxPill ?>">
                            <option value="DATE"  <?= $activeSortBy === 'DATE'  ? 'selected' : '' ?>>Sort: Date</option>
                            <option value="PRICE" <?= $activeSortBy === 'PRICE' ? 'selected' : '' ?>>Sort: Total</option>
                            <option value="QTY"   <?= $activeSortBy === 'QTY'   ? 'selected' : '' ?>>Sort: Items</option>
                        </select>

                        <button onclick="toggleTrxOrder('<?= $activeSortOrder ?>')" style="width:150px; cursor:pointer; font-weight:700; color:var(--primary-color); <?= $trxPill ?>">
                            <?= $activeSortOrder === 'ASC' ? 'Ascending ↑' : 'Descending ↓' ?>
                        </button>
                    </div>

                    <input type="text" placeholder="Search username or Order ID..." value="<?= htmlspecialchars($activeSearch) ?>" oninput="onSearchInput(this.value)" style="width:100%; box-sizing:border-box; <?= $trxPill ?>">
                </div>

                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Customer</th>
                            <th>Products</th>
                            <th>Date</th>
                            <th>Payment Method</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($stmt_trx)): ?>
                            <?php
                                $limit = 10;
                                $no    = (($page - 1) * $limit) + 1;
                            ?>
                            <?php foreach ($stmt_trx as $row): ?>
                                <?php $s = (int)$row['status_penjualan']; ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <div style="font-weight:600;font-size:.85rem;"><?= htmlspecialchars($row['username'] ?? '-') ?></div>
                                        <div style="font-size:.73rem;opacity:.5;"><?= htmlspecialchars($row['email'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <div style="max-width:240px; font-size:.78rem; opacity:.85; line-height:1.3; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;" title="<?= htmlspecialchars($row['daftar_produk'] ?? '') ?>">
                                            <?= htmlspecialchars($row['daftar_produk'] ?? '') ?: '-' ?>
                                        </div>
                                    </td>
                                    <td style="white-space:nowrap;font-size:.82rem;"><?= htmlspecialchars($row['tanggal_penjualan'] ?? '-') ?></td>
                                    <td style="font-size:.8rem;">
                                        <?= htmlspecialchars($row['nama_metode'] ?? '-') ?>
                                        <?php if (!empty($row['provider'])): ?>
                                            <span style="opacity:.5;"> · <?= htmlspecialchars($row['provider']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;"><?= (int)$row['total_barang'] ?></td>
                                    <td style="text-align:right;font-weight:700;white-space:nowrap;">Rp <?= htmlspecialchars($row['total_harga']) ?></td>
                                    <td>
                                        <span style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:700; background:<?= $STATUS_COLOR[$s]['bg'] ?? '#f3f4f6' ?>; color:<?= $STATUS_COLOR[$s]['color'] ?? '#555' ?>; white-space:nowrap;">
                                            <?= $STATUS_LABEL[$s] ?? 'Unknown' ?>
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        <div class="btn-action-group" style="justify-content:center;">
                                            <button class="btn-view-icon" title="View detail" onclick="openDetailModal(<?= (int)$row['id_penjualan'] ?>)">...</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="text-align:center;padding:2rem 0;opacity:.5;">No transactions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="pagination-container">
                    <?php
                    $baseUrl = '?type=sales&status=' . urlencode($activeStatus ?? '') . '&search=' . urlencode($activeSearch) . '&sort_by=' . urlencode($activeSortBy) . '&sort_order=' . urlencode($activeSortOrder);
                    ?>
                    <?php if ($page > 1): ?>
                        <a href="<?= $baseUrl ?>&page=<?= $page - 1 ?>" class="page-link">&lt;</a>
                    <?php else: ?>
                        <span class="page-link disabled">&lt;</span>
                    <?php endif; ?>
                    <?php
                    $start = max(1, $page - 1);
                    $end   = min($total_pages, $page + 1);
                    if ($start > 1):
                    ?>
                        <a href="<?= $baseUrl ?>&page=1" class="page-link <?= $page == 1 ? 'active' : '' ?>">1</a>
                        <?php if ($start > 2): ?><span class="dots">...</span><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a href="<?= $baseUrl ?>&page=<?= $i ?>" class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($end < $total_pages): ?>
                        <?php if ($end < $total_pages - 1): ?><span class="dots">...</span><?php endif; ?>
                        <a href="<?= $baseUrl ?>&page=<?= $total_pages ?>" class="page-link <?= $page == $total_pages ? 'active' : '' ?>"><?= $total_pages ?></a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="<?= $baseUrl ?>&page=<?= $page + 1 ?>" class="page-link">&gt;</a>
                    <?php else: ?>
                        <span class="page-link disabled">&gt;</span>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
        </div>

        <!-- ── Master Payment Method (dipindah dari Product; khusus Owner) ── -->
        <div id="ownerMetodeCard" class="content-card" style="display:none; margin-top:1.5rem;">
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/CardHaven/interface/product/components/metode_card.php'; ?>
        </div>
    </div>

    <!-- Modals -->
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/CardHaven/interface/product/components/metode_modal.php'; ?>
    <div id="trxModalOverlay" class="trx-modal-overlay" onclick="closeTrxModal(event)">
        <div class="trx-modal-box" onclick="event.stopPropagation()">
            <button class="trx-modal-close" onclick="closeTrxModal()">&times;</button>
            <div id="trxModalBody"></div>
        </div>
    </div>
    <script src="/cardhaven/interface/transaction/transaction.js?v=<?= time() ?>"></script>

    <script src="/cardhaven/interface/global_alert.js?v=<?= time() ?>"></script>
    <script src="/cardhaven/interface/product/master_filter.js?v=<?= time() ?>"></script>
    <script src="/cardhaven/interface/product/metode_script.js?v=<?= time() ?>"></script>
    <script>
        // Master Payment Method hanya tampil untuk Owner (role 3).
        (function () {
            var role = parseInt(sessionStorage.getItem('role') || localStorage.getItem('role') || 0);
            var card = document.getElementById('ownerMetodeCard');
            if (card && role === 3) card.style.display = '';
        })();
    </script>
</body>
</html>