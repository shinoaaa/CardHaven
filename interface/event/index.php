<?php 
require 'apifetch.php'; 

// Tangkap parameter filter dari URL untuk initial load di HTML value
$search = $_GET['search'] ?? '';
$status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : -1;
$type   = $_GET['type'] ?? '';
$sort   = $_GET['sort'] ?? 'date';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event List - CardHaven</title>
    <link rel="icon" type="image/svg+xml" href="/cardhaven/assets/image/logo.svg">
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    
    <style>
        /* CSS Minimalis untuk Toolbar agar sejajar dengan desainmu */
        .event-toolbar { display: flex; gap: 12px; margin-bottom: 20px; align-items: center; }
        .filter-input, .filter-select {
            padding: 8px 36px 8px 15px; border: 1px solid var(--primary-color, #173C99);
            border-radius: 9999px; outline: none; color: var(--primary-color, #173C99);
            font-family: inherit; font-size: 13px; background-color: #fff;
        }
        .filter-input { width: 220px; }
        .filter-select { font-weight: 600; cursor: pointer; }
        .sort-btn {
            background: transparent; border: none; cursor: pointer;
            color: var(--primary-color, #173C99); font-weight: bold; font-size: 18px;
            display: flex; align-items: center; justify-content: center;
        }
    </style>
</head>
<body>
    <div class="main-content" style="display: flex; justify-content: center; overflow-y: hidden;">
        <h1 class="coolveticaa" style="color: var(--primary-color); font-size: 1.8rem; font-weight: 700; margin: 0;">Dashboard / Event</h1>
        <div class="content-card">
            
            <div class="card-title-row">
                <h2 class="coolveticaa">Events</h2>
                <button class="btn-add-green" onclick="openAddEventModal()">+ Add Event</button>
            </div>

            <!-- TOOLBAR FILTER & SEARCH -->
            <div class="event-toolbar">
                <input type="text" id="filterSearch" class="filter-input" placeholder="Search Event Name..." value="<?= htmlspecialchars($search) ?>">
                
                <select id="filterStatus" class="filter-select" onchange="applyEventFilters(1)">
                    <option value="-1">All Status</option>
                    <option value="1" <?= $status === 1 ? 'selected' : '' ?>>Running</option>
                    <option value="2" <?= $status === 2 ? 'selected' : '' ?>>Upcoming</option>
                    <option value="0" <?= $status === 0 ? 'selected' : '' ?>>Complete</option>
                </select>

                <select id="filterType" class="filter-select" onchange="applyEventFilters(1)">
                    <option value="">All Types</option>
                    <option value="promo" <?= $type === 'promo' ? 'selected' : '' ?>>Promo</option>
                    <option value="preorder" <?= $type === 'preorder' ? 'selected' : '' ?>>Pre-Order</option>
                </select>

                <select id="filterSort" class="filter-select" onchange="applyEventFilters(1)">
                    <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Sort by Date</option>
                    <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>>Sort by Discount</option>
                </select>

                <button class="sort-btn" onclick="toggleEventSortDir()" title="Change Ascending/Descending">
                    <svg id="sortDirIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M19 12l-7 7-7-7"/>
                    </svg>
                </button>
            </div>

            <!-- TABEL DENGAN CLASS ASLI MILIKMU -->
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Event Name</th>
                        <th>Event Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Discount</th>
                        <th style="max-width: 80px;">Featured Product</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <!-- WADAH TBODY UNTUK JAVASCRIPT -->
                <tbody id="event-tbody">
                    <tr><td colspan="9" style="text-align: center; padding: 20px;">Memuat data...</td></tr>
                </tbody>
            </table>

            <!-- WADAH PAGINATION UNTUK JAVASCRIPT -->
            <div class="pagination-container" id="event-pagination">
            </div>
        </div>
    </div>

    <!-- Modal Base -->
    <div id="eventModal" class="event-modal-overlay" onclick="closeEventModal(event)">
        <div class="event-modal" onclick="event.stopPropagation()">
            <div id="eventModalBody"></div>
        </div>
    </div>

    <!-- MURNI PANGGIL DARI FILE TERPISAH -->
    <script src="/cardhaven/interface/add_product_shortcut.js?v=<?= time() ?>"></script>
    <script src="/cardhaven/interface/event/event.js"></script>
</body>
</html>