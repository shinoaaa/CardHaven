<?php
require_once '../CardHaven/route/route.php';
require_once __DIR__ . '/../../auth/session.php';

// Dashboard hanya untuk pegawai (employee/manager/owner).
// Dicek di server: user yang belum login dilempar ke halaman login,
// customer dilempar ke home. Tidak bisa diakali lewat devtools.
auth_require_role(auth_staff_roles());

// Batasan per-menu, mengikuti aturan role yang sudah dipakai di sideBar.php.
// Sebelumnya menu cuma disembunyikan di tampilan, jadi pegawai masih bisa
// membuka halamannya lewat URL langsung. Sekarang dicek di server.
$menuRoles = [
    'activity'       => [ROLE_EMPLOYEE, ROLE_MANAGER, ROLE_OWNER],
    'product'        => [ROLE_EMPLOYEE, ROLE_MANAGER, ROLE_OWNER],
    'transaction'    => [ROLE_EMPLOYEE, ROLE_MANAGER, ROLE_OWNER],
    'settingaccount' => [ROLE_EMPLOYEE, ROLE_MANAGER, ROLE_OWNER],
    'purchase'       => [ROLE_MANAGER, ROLE_OWNER],   // disembunyikan untuk Employee
    'event'          => [ROLE_MANAGER, ROLE_OWNER],   // disembunyikan untuk Employee
    'sales'          => [ROLE_MANAGER, ROLE_OWNER],   // laporan: Manager & Owner
    'user'           => [ROLE_OWNER],                 // manajemen pengguna: Owner saja
];

$menu = $segments[1] ?? '';
if (isset($menuRoles[$menu])) {
    auth_require_role($menuRoles[$menu], '/CardHaven/dashboard/activity');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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