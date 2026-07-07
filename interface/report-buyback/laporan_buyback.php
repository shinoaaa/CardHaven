<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Buyback Report</title>
    <link rel="icon" type="image/svg+xml" href="/cardhaven/assets/image/logo.svg">
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <link rel="stylesheet" href="laporan_buyback.css">
</head>
<body>
    <div class="main-content">
        <div class="content-card">
            <div class="card-title-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 class="coolveticaa" style="margin: 0; font-size: 2rem; color: var(--primary-color);">Buyback Report</h2>
                
                <div style="display: flex; background: rgba(0,0,0,0.05); padding: 4px; border-radius: 999px;">
                    <a href="?type=sales" style="text-decoration: none; padding: 8px 24px; border-radius: 999px; font-weight: 700; font-size: 0.9rem; transition: 0.2s; color: #555;">Sales</a>
                    <a href="?type=buyback" style="text-decoration: none; padding: 8px 24px; border-radius: 999px; font-weight: 700; font-size: 0.9rem; transition: 0.2s; background: var(--primary-color); color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">Buyback</a>
                </div>
            </div>

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

                        <button id="btnSort" class="btn-sort-small" onclick="toggleSort()">Newest</button>
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
                        <th width="15%">Total Paid</th>
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
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/cardhaven/interface/global_alert.js"></script>
    <script src="/cardhaven/interface/report-buyback/laporan_buyback_script.js"></script>
</body>
</html>