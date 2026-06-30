<?php
/**
* interface/page-profile/index.php
* CardHaven – Customer Profile Page
*/
$pageTitle = 'My Profile – CardHaven';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Global CSS -->
    <link rel="stylesheet" href="/cardhaven/interface/global.css">

    <!-- Profile page CSS -->
    <link rel="stylesheet" href="/cardhaven/interface/page-profile/assets/css/profile.css">
</head>
<body>
    <!-- Navbar Placeholder (Jika ada) -->
    <!-- <?php include __DIR__ . '/../page-customer/navBar.php'; ?> -->

    <!-- Placeholder Banner Top (TRUTH NUKE) -->
    <div class="profile-banner">
        <img src="https://i.pinimg.com/1200x/1c/d6/0c/1cd60c5cfdb662b3c117350876f19a2a.jpg">
    </div>

    <!-- Wrapper Content Utama -->
    <div class="profile-page-wrapper">
        <?php include __DIR__ . '/components/profile.php'; ?>
        <?php include __DIR__ . '/components/transaction_activity.php'; ?>
    </div>

    <!-- Modals Overlay & Content -->
    <?php include __DIR__ . '/components/modals.php'; ?>

    <!-- Scripts -->
    <script src="/cardhaven/interface/page-profile/assets/js/profile.js"></script>
    <script src="/cardhaven/interface/page-profile/assets/js/mailbox.js"></script>
    <script src="/cardhaven/interface/page-profile/assets/js/transaction.js"></script>
    <script src="/cardhaven/interface/global_alert.js"></script>
</body>
</html>