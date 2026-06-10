<?php require_once '../CardHaven/route/route.php' ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DashBoard</title>
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
            <?php include '../CardHaven/interface/dashboard/sideBar.php'; ?>
        </div>

        <?php
        if ($segments[1] == 'activity') {
            echo 'Welcome to activity';
        } else if ($segments[1] == 'product') {
            include '../CardHaven/interface/product/index.php';
        } else if ($segments[1] == 'transaction'){
            echo 'Welcome to ', $segments[1] ;
        }else if ($segments[1] == 'purchase'){
            echo 'Welcome to ', $segments[1] ;
        }else if ($segments[1] == 'product'){
            echo 'Welcome to ', $segments[1] ;
        }else if ($segments[1] == 'event'){
            echo 'Welcome to ', $segments[1] ;
        }else if ($segments[1] == 'sales'){
            echo 'Welcome to ', $segments[1] ;
        }else if ($segments[1] == 'user'){
            echo 'Welcome to ', $segments[1] ;
        }else if ($segments[1] == 'settingaccount'){
            include '../CardHaven/interface/account-setting/index.php';
        }
        ?>
    </div>
</body>
</html>