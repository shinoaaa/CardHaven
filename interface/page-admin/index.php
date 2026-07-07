<?php require_once '../CardHaven/route/route.php' ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="/cardhaven/assets/image/logo.svg">
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <script>
        (function() {
            const token = localStorage.getItem("id_pengguna") || sessionStorage.getItem("id_pengguna");
            const role = localStorage.getItem("role") || sessionStorage.getItem("role");
            if (!token || (role !== "2" && role !== "1" && role !== "3" )) {
                window.location.replace("/CardHaven");
            }
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/cardhaven/interface/global_alert.js?v=<?= time() ?>"></script>
</head>
<body>
    <div class="container" style="justify-content: flex-start; align-items: flex-start;">
        <div class="sideBar">
            <?php include '../CardHaven/interface/page-admin/sideBar.php'; ?>
        </div>

        <?php
        if ($segments[1] == 'activity') {
            include '../CardHaven/interface/page-admin/activity.php';
        } else if ($segments[1] == 'product') {
            include '../CardHaven/interface/product/index.php';
        } else if ($segments[1] == 'transaction'){
            include '../CardHaven/interface/transaction/index.php';
        }else if ($segments[1] == 'purchase'){
            include '../CardHaven/interface/purchase/index.php';
        }else if ($segments[1] == 'product'){
            echo 'Welcome to ', $segments[1] ;
        }else if ($segments[1] == 'event'){
            include '../CardHaven/interface/event/index.php';
        }else if ($segments[1] == 'sales'){
            include '../CardHaven/interface/report-sales/index.php';
        }else if ($segments[1] == 'user'){
            include '../CardHaven/interface/user/index.php';
        }else if ($segments[1] == 'settingaccount'){
            include '../CardHaven/interface/account-setting/index.php';
        }
        ?>
    </div>
</body>
</html>