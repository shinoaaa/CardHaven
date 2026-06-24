<?php
session_start();
$role = (int)($_SESSION['role'] ?? 0);
// role: 1=Admin, 2=Owner/Manager, 3=Superadmin
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase — Restok</title>
</head>
<body>
    <div class="main-content">
        <h1 class="coolveticaa" style="color:#a0beff; font-size:1.5rem; font-weight:700;">
            Dashboard / Purchase
        </h1>

        <div class="content-card" style="min-height: 540px;">

            <!-- Title row -->
            <div class="card-title-row">
                <h2 class="coolveticaa">Restock (Purchase Orders)</h2>
            </div>

            <!-- Filter bar -->
            <div style="display:flex; gap:0.75rem; margin-bottom:1.25rem; flex-wrap:wrap; align-items:center;">
                <input type="text" id="searchInput" placeholder="Search supplier or PO ID..."
                    style="padding:8px 16px; border:1.5px solid #D0DAF0; border-radius:9999px; font-size:0.88rem; outline:none; min-width:220px;"
                    oninput="debounceSearch()">

                <select id="statusFilter" onchange="loadRestok(1)"
                    style="padding:8px 16px; border:1.5px solid #D0DAF0; border-radius:9999px; font-size:0.88rem; outline:none; background:white; cursor:pointer;">
                    <option value="">All Status</option>
                    <option value="0">Pending</option>
                    <option value="1">Approved</option>
                    <option value="2">Rejected</option>
                </select>
            </div>

            <!-- Table -->
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>PO ID</th>
                        <th>Supplier</th>
                        <th>Date</th>
                        <th>Total Items</th>
                        <th>Total Price</th>
                        <th>Created By</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="restokTableBody">
                    <tr><td colspan="9" style="color:#999;">Loading...</td></tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination-container" id="restokPagination"></div>
        </div>
    </div>

    <?php include 'components/modal_restok.php'; ?>

    <script>
        // Inject role dari PHP ke JS supaya JS tahu boleh approve/reject atau tidak
        const USER_ROLE = <?= $role ?>;
    </script>
    <script src="/cardhaven/interface/purchase/restok_script.js?v=<?= time() ?>"></script>
</body>
</html>