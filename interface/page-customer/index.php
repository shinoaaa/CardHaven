<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/cardhaven/interface/global_alert.js?v=<?= time() ?>"></script>
    
</head>
<body>
    <div style="width: 100vw; height: 100vh; display: flex; flex-direction: column; justify-content: space-between;">
        <?php include '../CardHaven/interface/page-customer/navBar.php'; ?>
        <div style="height: 85%; width: 100%">
            <?php include '../CardHaven/interface/home/index.php'; ?>
        </div>
    </div>
</body>
</html>