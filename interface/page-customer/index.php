<?php
    $request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $url = str_replace('/CardHaven', '', $request);
    $segments = explode('/', trim($url, '/'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="icon" type="image/svg+xml" href="/cardhaven/assets/image/logo.svg">
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/cardhaven/interface/global_alert.js?v=<?= time() ?>"></script>
    <script>
        (function() {
            const token = localStorage.getItem("id_pengguna") || sessionStorage.getItem("id_pengguna");
            const role = localStorage.getItem("role") || sessionStorage.getItem("role");
            if (token && (role === "2" || role === "1" || role === "3" )) {
                window.location.replace("/CardHaven/dashboard/activity");
            }
        })();
    </script>
</head>
<body>
    <div style="width: 100vw; height: 100vh; display: flex; flex-direction: column; justify-content: space-between;">
        <?php include '../CardHaven/interface/page-customer/navBar.php'; ?>

        <?php if(($segments[0] === 'home' || $segments[0] === '') && count($segments) === 1): ?>
            <div style="height: 85%; width: 100%">
                <?php include '../CardHaven/interface/home/index.php'; ?>
            </div>
        <?php elseif($segments[1] === 'productdetail'): ?>
            <?php include __DIR__ . '/../product-detail/index.php' ?>
        <?php elseif($segments[1] === 'cart'): ?>
            <?php include __DIR__ . '/../cart/index.php' ?>
        <?php elseif($segments[1] === 'list'): ?>
            <?php include __DIR__ . '/../catalogue/index.php' ?>
        <?php elseif($segments[1] === 'buyback'): ?>
            <?php include __DIR__ . '/../buyback/customer.php' ?>
        <?php endif; ?>
    </div>

</body>
</html>