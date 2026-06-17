<?php require_once 'controller_home.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CardHaven - Homepage</title>
    <link rel="stylesheet" href="/CardHaven/interface/global.css">
    <link rel="stylesheet" href="home.css">
</head>
<body>

    <?php include 'components/header.php'; ?>

    <main class="main-container">
        <?php include 'components/hero.php'; ?>
        <?php include 'components/promo.php'; ?>
        <?php include 'components/games.php'; ?>
        <?php include 'components/products.php'; ?>
        <?php include 'components/buyback.php'; ?>
        <?php include 'components/footer.php'; ?>
    </main>

    <script src="home.js"></script>
</body>
</html>