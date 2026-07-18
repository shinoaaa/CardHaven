<?php
require_once '../CardHaven/route/route.php';
require_once __DIR__ . '/../../auth/session.php';

// Halaman dashboard: hanya pegawai (dicek di server).
auth_require_role(auth_staff_roles());
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DashBoard</title>
    <link rel="icon" type="image/svg+xml" href="/cardhaven/assets/image/logo.svg">
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <?php auth_emit_js(); ?>
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
            echo 'Welcome to activity';
        } else if ($segments[1] == 'product') {
            include '../CardHaven/interface/product/index.php';
        } else if ($segments[1] == 'notification'){
            include '../CardHaven/interface/notification/index.php';
        } else if ($segments[1] == 'transaction'){
            include '../CardHaven/interface/transaction/index.php';
        }else if ($segments[1] == 'purchase'){
            echo 'Welcome to ', $segments[1] ;
        }else if ($segments[1] == 'product'){
            echo 'Welcome to ', $segments[1] ;
        }else if ($segments[1] == 'event'){
            include '../CardHaven/interface/event/index.php';
        }else if ($segments[1] == 'sales'){
            echo 'Welcome to ', $segments[1] ;
        }else if ($segments[1] == 'user'){
            include '../CardHaven/interface/user/index.php';
        }else if ($segments[1] == 'settingaccount'){
            include '../CardHaven/interface/account-setting/index.php';
        }
        ?>
    </div>
</body>
</html>