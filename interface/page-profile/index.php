<?php $pageTitle = 'My Profile – CardHaven'; ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="/cardhaven/assets/image/logo.svg">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <link rel="stylesheet" href="/cardhaven/interface/page-profile/assets/css/profile.css">
</head>

<body>

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

    <!-- Buyback detail modal (dipakai tab "Buy Back", perilaku sama seperti customer.php) -->
    <div id="detailModal" class="event-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1000; justify-content: center; align-items: flex-start; padding: 2rem 1rem; overflow-y: auto;" onclick="if(event.target===this) closeDetailModal()">
        <div class="modal-box" style="width: 550px; max-width: 95vw; position: relative;">
            <button class="event-modal-close" onclick="closeDetailModal()" style="background: none; border: none; font-size: 24px; position: absolute; right: 20px; top: 15px; cursor: pointer;">&times;</button>
            <div class="modal-header">
                <h2 style="font-size: 1.5rem; margin-bottom: 5px;">Transaction <span class="blue-text" id="modalTxId"></span></h2>
                <span class="game-id" id="modalStatus" style="font-weight: 600;"></span>
            </div>
            <div id="modalContent" style="margin-top: 15px; max-height: 50vh; overflow-y: auto; padding-right: 10px;"></div>
            <div class="modal-footer" id="modalFooter" style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;"></div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/cardhaven/interface/global_alert.js"></script>
    <script src="/cardhaven/interface/page-profile/assets/js/profile.js"></script>
    <script src="/cardhaven/interface/page-profile/assets/js/mailbox.js"></script>
    <script src="/cardhaven/interface/page-profile/assets/js/transaction.js"></script>
    <script src="/cardhaven/interface/buyback/buyback_customer_script.js"></script>
</body>
</html>