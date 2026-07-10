<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Buyback Management - Admin</title>
    <link rel="icon" type="image/svg+xml" href="/cardhaven/assets/image/logo.svg">
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/cardhaven/interface/global_alert.js"></script>
</head>
<body>
    <div class="main-content">
        <div class="content-card">
            <div class="card-title-row">
                <h2>Card Buyback Management</h2>
            </div>
            
            <table class="styled-table" id="tableAdmin">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Transaction ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total Offer</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    </tbody>
            </table>
        </div>
    </div>
    <?php include 'components/modal.php'; ?>
    <script src="buyback_admin_script.js"></script>
</body>
</html>