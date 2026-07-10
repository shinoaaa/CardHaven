
<?php
$type = $_GET['type'] ?? 'sales';
$titles = [
    'sales'   => 'Sales Report',
    'buyback' => 'Buyback Report',
    'restok'  => 'Restock Report',
    'event'   => 'Event Report'
];
// Ambil judul berdasarkan tipe, default ke 'Report' jika tipe tidak ditemukan
$currentTitle = $titles[$type] ?? 'Report';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report - CardHaven</title>
    <link rel="icon" type="image/svg+xml" href="/cardhaven/assets/image/logo.svg">
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    
    <link rel="stylesheet" href="/cardhaven/interface/report-buyback/laporan_buyback.css">
</head>
<body>
    <div class="main-content">
        <div class="content-card">
            
            <div class="card-title-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 class="coolveticaa" style="margin: 0; font-size: 2rem; color: var(--primary-color);">
                    <?= $currentTitle ?>
                </h2>
                
                <div style="display: flex; background: rgba(0,0,0,0.05); padding: 4px; border-radius: 999px;">
                    <?php 
                    $tabs = ['sales' => 'Sales', 'buyback' => 'Buyback', 'restok' => 'Restok', 'event' => 'Event'];
                    foreach ($tabs as $key => $label): 
                        $isActive = ($type === $key);
                    ?>
                        <a href="?type=<?= $key ?>" style="text-decoration: none; padding: 8px 24px; border-radius: 999px; font-weight: 700; font-size: 0.9rem; transition: 0.2s; 
                        <?= $isActive ? 'background: var(--primary-color); color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.1);' : 'color: #555;' ?>">
                        <?= $label ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ANALYTICS: bar chart bulanan + (khusus Sales) top 3 selling items -->
            <div style="display:flex; gap:1.25rem; margin-bottom:1.5rem; flex-wrap:wrap;">
                <div style="flex:2; min-width:320px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem; box-sizing:border-box;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:.75rem; gap:1rem;">
                        <div>
                            <div style="font-weight:700; color:var(--primary-color); font-size:1.05rem;"><?= htmlspecialchars($currentTitle) ?> Overview</div>
                            <div style="font-size:.72rem; color:#94a3b8;">Monthly total (Rp) per selected year</div>
                        </div>
                        <select id="reportChartYear" onchange="reportLoadChart(this.value)" style="height:34px; padding:0 12px; border:1.5px solid #D0DAF0; border-radius:9999px; font-size:.82rem; color:var(--primary-color); background:#fff; cursor:pointer;"></select>
                    </div>
                    <div style="height:260px; position:relative;"><canvas id="reportChart"></canvas></div>
                </div>

                <?php if ($type === 'sales'): ?>
                <div style="flex:1; min-width:270px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem; box-sizing:border-box;">
                    <div style="font-weight:700; color:var(--primary-color); font-size:1.05rem;">Top 5 Selling Items</div>
                    <div style="font-size:.72rem; color:#94a3b8; margin-bottom:.9rem;">By quantity sold</div>
                    <div id="topSellingList"><p style="color:#94a3b8; font-size:.85rem;">Loading…</p></div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($type === 'sales'): ?>
                <div class="filter-container" style="display: flex; flex-direction: column; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 10px;">
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: nowrap;">
                            <div style="display: flex; align-items: center; background: white; border: 1px solid #ccc; border-radius: 8px; overflow: hidden; height: 38px; width: 130px; flex-shrink: 0;">
                                <button onclick="shiftYear(-1)" style="background: #f8fafc; border: none; border-right: 1px solid #ccc; width: 35px; height: 100%; cursor: pointer; font-weight: bold; color: var(--primary-color); font-size: 1.2rem;">-</button>
                                <input type="number" id="filterTahun" placeholder="All Years" onchange="fetchReportData()" style="border: none; outline: none; text-align: center; width: 60px; height: 100%; font-size: 0.85rem; font-weight: 600; color: #333; padding: 0;">
                                <button onclick="shiftYear(1)" style="background: #f8fafc; border: none; border-left: 1px solid #ccc; width: 35px; height: 100%; cursor: pointer; font-weight: bold; color: var(--primary-color); font-size: 1.2rem;">+</button>
                            </div>
                            
                            <select id="filterBulan" class="modal-input" onchange="fetchReportData()" style="height: 38px; width: 130px; padding: 0 12px; border-radius: 8px;">
                                <option value="0">All Months</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>

                            <select id="sortCriterion" class="modal-input" onchange="changeSortCriterion()" style="height: 38px; width: 140px; padding: 0 12px; border-radius: 8px;">
                                <option value="NONE" hidden selected>Sort By...</option>
                                <option value="NONE">None</option>
                                <option value="DATE">Date</option>
                                <option value="PRICE">Price</option>
                                <option value="QTY">Quantity</option>
                            </select>

                            <button id="btnSortOrder" class="btn-sort-small" onclick="toggleSortOrder()" style="margin-left: 5px; width: 120px;">Descending ↓</button>
                        </div>

                        <div class="export-group" style="display: flex; gap: 10px; flex-shrink: 0;">
                            <button class="btn-add-green" onclick="exportReport('excel')" style="background-color: #27AE60; border-radius: 8px; height: 38px; padding: 0 15px; font-size: 0.85rem; display: flex; align-items: center; justify-content: center;">Export Excel</button>
                            <button class="btn-add-green" onclick="exportReport('pdf')" style="background-color: #E74C3C; border-radius: 8px; height: 38px; padding: 0 15px; font-size: 0.85rem; display: flex; align-items: center; justify-content: center;">Export PDF</button>
                        </div>
                    </div>

                    <div style="width: 100%;">
                        <input type="text" id="searchReport" class="modal-input" placeholder="Search customer, card, date, receipt, or price..." onkeyup="debounceSearch()" style="height: 38px; width: 100%; border-radius: 8px; margin: 0; box-sizing: border-box;">
                    </div>
                </div>
                <table class="styled-table" id="tableLaporan">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="12%">Date</th>
                            <th width="18%">Customer</th>
                            <th width="30%">Product Purchased</th>
                            <th width="15%">Payment Method</th>
                            <th width="10%">Quantity</th>
                            <th width="15%">Price</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 10px;">
                    <div id="paginationReport" class="pagination-container" style="margin-top: 0; padding-top: 0;"></div>
                    
                    <div style="background: #f8fafc; padding: 12px 20px; border-radius: 8px; border: 1px solid #e2e8f0; font-weight: 600; display: flex; gap: 20px; align-items: center; white-space: nowrap; width: fit-content;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="color: #64748b; font-size: 0.85rem;">Total Items:</span>
                            <span id="summaryTotalItems" style="color: var(--primary-color); font-size: 1.1rem;">0 Pcs</span>
                        </div>
                        <div style="width: 1px; height: 20px; background: #cbd5e1;"></div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="color: #64748b; font-size: 0.85rem;">Total Sales:</span>
                            <span id="summaryTotalSales" style="color: #27AE60; font-size: 1.1rem;">Rp 0</span>
                        </div>
                    </div>
                </div>
                <div id="detailModal" class="event-modal-overlay" style="display: none;" onclick="closeDetailModal()">
                    <div class="modal-box" style="width: 650px; max-width: 95vw;" onclick="event.stopPropagation()">
                        <button class="event-modal-close" onclick="closeDetailModal()">&times;</button>
                        
                        <div class="modal-header" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px;">
                            <h2 style="font-size: 1.5rem; margin: 0 0 5px 0;">Buyback ID: <span class="blue-text" id="modalTxId"></span></h2>
                            <span style="font-weight: 600; color: #15803d; background: #dcfce7; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem;">Completed</span>
                        </div>
                        
                        <div id="modalContent" style="max-height: 50vh; overflow-y: auto; padding-right: 10px;">
                            </div>
                    </div>
                </div>
                
            <?php elseif ($type === 'buyback'): ?>
                <div class="filter-container" style="display: flex; flex-direction: column; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 10px;">
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: nowrap;">
                            <div style="display: flex; align-items: center; background: white; border: 1px solid #ccc; border-radius: 8px; overflow: hidden; height: 38px; width: 130px; flex-shrink: 0;">
                                <button onclick="shiftYear(-1)" style="background: #f8fafc; border: none; border-right: 1px solid #ccc; width: 35px; height: 100%; cursor: pointer; font-weight: bold; color: var(--primary-color); font-size: 1.2rem;">-</button>
                                <input type="number" id="filterTahun" placeholder="All Years" onchange="fetchReportData()" style="border: none; outline: none; text-align: center; width: 60px; height: 100%; font-size: 0.85rem; font-weight: 600; color: #333; padding: 0;">
                                <button onclick="shiftYear(1)" style="background: #f8fafc; border: none; border-left: 1px solid #ccc; width: 35px; height: 100%; cursor: pointer; font-weight: bold; color: var(--primary-color); font-size: 1.2rem;">+</button>
                            </div>
                            
                            <select id="filterBulan" class="modal-input" onchange="fetchReportData()" style="height: 38px; width: 130px; padding: 0 12px; border-radius: 8px;">
                                <option value="0">All Months</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>

                            <select id="sortCriterion" class="modal-input" onchange="changeSortCriterion()" style="height: 38px; width: 140px; padding: 0 12px; border-radius: 8px;">
                                <option value="NONE" hidden selected>Sort By...</option>
                                <option value="NONE">None</option>
                                <option value="DATE">Date</option>
                                <option value="PRICE">Price</option>
                                <option value="QTY">Quantity</option>
                            </select>

                            <button id="btnSortOrder" class="btn-sort-small" onclick="toggleSortOrder()" style="margin-left: 5px; width: 120px;">Descending ↓</button>
                        </div>

                        <div class="export-group" style="display: flex; gap: 10px; flex-shrink: 0;">
                            <button class="btn-add-green" onclick="exportReport('excel')" style="background-color: #27AE60; border-radius: 8px; height: 38px; padding: 0 15px; font-size: 0.85rem; display: flex; align-items: center; justify-content: center;">Export Excel</button>
                            <button class="btn-add-green" onclick="exportReport('pdf')" style="background-color: #E74C3C; border-radius: 8px; height: 38px; padding: 0 15px; font-size: 0.85rem; display: flex; align-items: center; justify-content: center;">Export PDF</button>
                        </div>
                    </div>

                    <div style="width: 100%;">
                        <input type="text" id="searchReport" class="modal-input" placeholder="Search customer, card, date, receipt, or price..." onkeyup="debounceSearch()" style="height: 38px; width: 100%; border-radius: 8px; margin: 0; box-sizing: border-box;">
                    </div>
                </div>

                <table class="styled-table" id="tableLaporan">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="12%">Date</th>
                            <th width="18%">Customer</th>
                            <th width="30%">Cards Purchased</th>
                            <th width="10%">Items</th>
                            <th width="15%">Paid</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 10px;">
                    <div id="paginationReport" class="pagination-container" style="margin-top: 0; padding-top: 0;"></div>
                    
                    <div style="background: #f8fafc; padding: 12px 20px; border-radius: 8px; border: 1px solid #e2e8f0; font-weight: 600; display: flex; gap: 20px; align-items: center; white-space: nowrap; width: fit-content;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="color: #64748b; font-size: 0.85rem;">Total Items:</span>
                            <span id="summaryTotalItems" style="color: var(--primary-color); font-size: 1.1rem;">0 Pcs</span>
                        </div>
                        <div style="width: 1px; height: 20px; background: #cbd5e1;"></div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="color: #64748b; font-size: 0.85rem;">Total Paid:</span>
                            <span id="summaryTotalPaid" style="color: #27AE60; font-size: 1.1rem;">Rp 0</span>
                        </div>
                    </div>
                </div>
                <div id="detailModal" class="event-modal-overlay" style="display: none;" onclick="closeDetailModal()">
                    <div class="modal-box" style="width: 650px; max-width: 95vw;" onclick="event.stopPropagation()">
                        <button class="event-modal-close" onclick="closeDetailModal()">&times;</button>
                        
                        <div class="modal-header" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px;">
                            <h2 style="font-size: 1.5rem; margin: 0 0 5px 0;">Buyback ID: <span class="blue-text" id="modalTxId"></span></h2>
                            <span style="font-weight: 600; color: #15803d; background: #dcfce7; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem;">Completed</span>
                        </div>
                        
                        <div id="modalContent" style="max-height: 50vh; overflow-y: auto; padding-right: 10px;">
                            </div>
                    </div>
                </div>
                        <?php elseif ($type === 'restok'): ?>
                <div class="filter-container" style="display: flex; flex-direction: column; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #e2e8f0;">

                    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 10px;">
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: nowrap;">
                            <div style="display: flex; align-items: center; background: white; border: 1px solid #ccc; border-radius: 8px; overflow: hidden; height: 38px; width: 130px; flex-shrink: 0;">
                                <button onclick="shiftYear(-1)" style="background: #f8fafc; border: none; border-right: 1px solid #ccc; width: 35px; height: 100%; cursor: pointer; font-weight: bold; color: var(--primary-color); font-size: 1.2rem;">-</button>
                                <input type="number" id="filterTahun" placeholder="All Years" onchange="fetchReportData()" style="border: none; outline: none; text-align: center; width: 60px; height: 100%; font-size: 0.85rem; font-weight: 600; color: #333; padding: 0;">
                                <button onclick="shiftYear(1)" style="background: #f8fafc; border: none; border-left: 1px solid #ccc; width: 35px; height: 100%; cursor: pointer; font-weight: bold; color: var(--primary-color); font-size: 1.2rem;">+</button>
                            </div>

                            <select id="filterBulan" class="modal-input" onchange="fetchReportData()" style="height: 38px; width: 130px; padding: 0 12px; border-radius: 8px;">
                                <option value="0">All Months</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>

                            <select id="sortCriterion" class="modal-input" onchange="changeSortCriterion()" style="height: 38px; width: 140px; padding: 0 12px; border-radius: 8px;">
                                <option value="NONE" hidden selected>Sort By...</option>
                                <option value="NONE">None</option>
                                <option value="DATE">Date</option>
                                <option value="PRICE">Price</option>
                                <option value="QTY">Quantity</option>
                            </select>

                            <button id="btnSortOrder" class="btn-sort-small" onclick="toggleSortOrder()" style="margin-left: 5px; width: 120px;">Descending ↓</button>
                        </div>

                        <div class="export-group" style="display: flex; gap: 10px; flex-shrink: 0;">
                            <button class="btn-add-green" onclick="exportReport('excel')" style="background-color: #27AE60; border-radius: 8px; height: 38px; padding: 0 15px; font-size: 0.85rem; display: flex; align-items: center; justify-content: center;">Export Excel</button>
                            <button class="btn-add-green" onclick="exportReport('pdf')" style="background-color: #E74C3C; border-radius: 8px; height: 38px; padding: 0 15px; font-size: 0.85rem; display: flex; align-items: center; justify-content: center;">Export PDF</button>
                        </div>
                    </div>

                    <div style="width: 100%;">
                        <input type="text" id="searchReport" class="modal-input" placeholder="Search supplier, product, date, or price..." onkeyup="debounceSearch()" style="height: 38px; width: 100%; border-radius: 8px; margin: 0; box-sizing: border-box;">
                    </div>
                </div>

                <table class="styled-table" id="tableLaporan">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="12%">Date</th>
                            <th width="16%">Supplier</th>
                            <th width="27%">Products Purchased</th>
                            <th width="8%">Quantity</th>
                            <th width="13%">Total Price</th>
                            <th width="7%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 10px;">
                    <div id="paginationReport" class="pagination-container" style="margin-top: 0; padding-top: 0;"></div>

                    <div style="background: #f8fafc; padding: 12px 20px; border-radius: 8px; border: 1px solid #e2e8f0; font-weight: 600; display: flex; gap: 20px; align-items: center; white-space: nowrap; width: fit-content;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="color: #64748b; font-size: 0.85rem;">Total Items:</span>
                            <span id="summaryTotalItems" style="color: var(--primary-color); font-size: 1.1rem;">0 Pcs</span>
                        </div>
                        <div style="width: 1px; height: 20px; background: #cbd5e1;"></div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="color: #64748b; font-size: 0.85rem;">Total Sales:</span>
                            <span id="summaryTotalPaid" style="color: #27AE60; font-size: 1.1rem;">Rp 0</span>
                        </div>
                    </div>
                </div>

                <div id="detailModal" class="event-modal-overlay" style="display: none;" onclick="closeDetailModal()">
                    <div class="modal-box" style="width: 650px; max-width: 95vw;" onclick="event.stopPropagation()">
                        <button class="event-modal-close" onclick="closeDetailModal()">&times;</button>

                        <div class="modal-header" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px;">
                            <h2 style="font-size: 1.5rem; margin: 0 0 5px 0;">PO ID: <span class="blue-text" id="modalTxId"></span></h2>
                        </div>

                        <div id="modalContent" style="max-height: 50vh; overflow-y: auto; padding-right: 10px;">
                            </div>
                    </div>
                </div>
            <?php elseif ($type === 'event'): ?>
                <div class="filter-container" style="display: flex; flex-direction: column; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #e2e8f0;">

                    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 10px;">
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: nowrap;">
                            <div style="display: flex; align-items: center; background: white; border: 1px solid #ccc; border-radius: 8px; overflow: hidden; height: 38px; width: 130px; flex-shrink: 0;">
                                <button onclick="shiftYear(-1)" style="background: #f8fafc; border: none; border-right: 1px solid #ccc; width: 35px; height: 100%; cursor: pointer; font-weight: bold; color: var(--primary-color); font-size: 1.2rem;">-</button>
                                <input type="number" id="filterTahun" placeholder="All Years" onchange="fetchReportData()" style="border: none; outline: none; text-align: center; width: 60px; height: 100%; font-size: 0.85rem; font-weight: 600; color: #333; padding: 0;">
                                <button onclick="shiftYear(1)" style="background: #f8fafc; border: none; border-left: 1px solid #ccc; width: 35px; height: 100%; cursor: pointer; font-weight: bold; color: var(--primary-color); font-size: 1.2rem;">+</button>
                            </div>

                            <select id="filterBulan" class="modal-input" onchange="fetchReportData()" style="height: 38px; width: 130px; padding: 0 12px; border-radius: 8px;">
                                <option value="0">All Months</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>

                            <select id="sortCriterion" class="modal-input" onchange="changeSortCriterion()" style="height: 38px; width: 140px; padding: 0 12px; border-radius: 8px;">
                                <option value="NONE" hidden selected>Sort By...</option>
                                <option value="NONE">None</option>
                                <option value="DATE">Date</option>
                                <option value="PRICE">Revenue</option>
                                <option value="QTY">Quantity</option>
                            </select>

                            <button id="btnSortOrder" class="btn-sort-small" onclick="toggleSortOrder()" style="margin-left: 5px; width: 120px;">Descending ↓</button>
                        </div>

                        <div class="export-group" style="display: flex; gap: 10px; flex-shrink: 0;">
                            <button class="btn-add-green" onclick="exportReport('excel')" style="background-color: #27AE60; border-radius: 8px; height: 38px; padding: 0 15px; font-size: 0.85rem; display: flex; align-items: center; justify-content: center;">Export Excel</button>
                            <button class="btn-add-green" onclick="exportReport('pdf')" style="background-color: #E74C3C; border-radius: 8px; height: 38px; padding: 0 15px; font-size: 0.85rem; display: flex; align-items: center; justify-content: center;">Export PDF</button>
                        </div>
                    </div>

                    <div style="width: 100%;">
                        <input type="text" id="searchReport" class="modal-input" placeholder="Search event name, type, product, date, or revenue..." onkeyup="debounceSearch()" style="height: 38px; width: 100%; border-radius: 8px; margin: 0; box-sizing: border-box;">
                    </div>
                </div>

                <table class="styled-table" id="tableLaporan">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="23%">Event Name</th>
                            <th width="17%">Period</th>
                            <th width="13%">Type / Discount</th>
                            <th width="10%">Items Sold</th>
                            <th width="14%">Revenue</th>
                            <th width="8%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 10px;">
                    <div id="paginationReport" class="pagination-container" style="margin-top: 0; padding-top: 0;"></div>

                    <div style="background: #f8fafc; padding: 12px 20px; border-radius: 8px; border: 1px solid #e2e8f0; font-weight: 600; display: flex; gap: 20px; align-items: center; white-space: nowrap; width: fit-content;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="color: #64748b; font-size: 0.85rem;">Total Items:</span>
                            <span id="summaryTotalItems" style="color: var(--primary-color); font-size: 1.1rem;">0 Pcs</span>
                        </div>
                        <div style="width: 1px; height: 20px; background: #cbd5e1;"></div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="color: #64748b; font-size: 0.85rem;">Total Revenue:</span>
                            <span id="summaryTotalRevenue" style="color: #27AE60; font-size: 1.1rem;">Rp 0</span>
                        </div>
                    </div>
                </div>

                <div id="detailModal" class="event-modal-overlay" style="display: none;" onclick="closeDetailModal()">
                    <div class="modal-box" style="width: 650px; max-width: 95vw;" onclick="event.stopPropagation()">
                        <button class="event-modal-close" onclick="closeDetailModal()">&times;</button>

                        <div class="modal-header" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px;">
                            <h2 style="font-size: 1.5rem; margin: 0 0 5px 0;">Event ID: <span class="blue-text" id="modalTxId"></span></h2>
                            <span style="font-weight: 600; color: #15803d; background: #dcfce7; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem;">Event Report</span>
                        </div>

                        <div id="modalContent" style="max-height: 50vh; overflow-y: auto; padding-right: 10px;">
                            </div>
                    </div>
                </div>
            <?php endif; ?>
            

        </div>
    </div>
    <?php
        $scripts = [
            'sales'   => '/cardhaven/interface/report-sales/laporan_sales_script.js',
            'buyback' => '/cardhaven/interface/report-buyback/laporan_buyback_script.js',
            'restok'  => '/cardhaven/interface/report-restok/laporan_restok_script.js',
            'event'   => '/cardhaven/interface/report-event/laporan_event_script.js'
        ];

        if (array_key_exists($type, $scripts)): ?>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script src="/cardhaven/interface/global_alert.js?v=<?= time() ?>"></script>
            <script src="<?= $scripts[$type] ?>?v=<?= time() ?>"></script>

            <!-- Analytics card: bar chart + top selling (Sales) -->
            <script>window.REPORT_TYPE = '<?= $type ?>';</script>
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
            <script src="/cardhaven/interface/report-sales/report_charts.js?v=<?= time() ?>"></script>
    <?php endif; ?>

</body>
</html>