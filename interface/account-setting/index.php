<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Setting</title>
    <link rel="stylesheet" href="/cardhaven/interface/account-setting/account-setting.css">
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
</head>
<body>
<div class="page-shell">

    <main class="content-wrap">
        <section class="account-card">
            <h2 class="account-title coolveticaa">Account Setting</h2>

            <div class="status-badge" id="statusAkun">Status: -</div>

            <div class="profile-row">
                <div class="profile-thumb">
                    <img id="fotoProfil"
                         src="https://i.pinimg.com/736x/e8/2b/43/e82b43056d04e86c577a443485049d9b.jpg"
                         alt="profile">
                </div>
                <div>
                    <div class="coolveticaa" style="font-size: 1rem;">Profile Data</div>
                    <div class="muted" id="profileInfo">-</div>
                </div>
            </div>

            <form id="accountForm">
                <div class="field">
                    <label>Nama</label>
                    <input type="text" id="nama" autocomplete="off">
                </div>

                <div class="field">
                    <label>Email</label>
                    <input type="email" id="email" autocomplete="off">
                </div>

                <div class="field">
                    <label>Password Baru</label>
                    <input type="password" id="password">
                </div>

                <div class="field">
                    <label>Konfirmasi Password</label>
                    <input type="password" id="confirmPassword">
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn btn-save">Save Changes</button>
                    <!-- <button type="button" id="btnDeactivate" class="btn btn-off">Deactivate</button> -->
                    <button type="button" id="btnDelete" class="btn btn-delete">Delete Account</button>
                </div>
            </form>
        </section>
    </main>
</div>

<script src="/cardhaven/interface/account-setting/account-setting.js?v=<?= time() ?>"></script>
</body>
</html>