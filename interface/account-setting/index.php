<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Setting</title>
    <link rel="icon" type="image/svg+xml" href="/cardhaven/assets/image/logo.svg">
    <link rel="stylesheet" href="/cardhaven/interface/account-setting/account-setting.css">
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/cardhaven/interface/global_alert.js"></script>
</head>
<body>
<div class="page-shell">

    <main class="content-wrap">
        <div class="particles">
            <span class="particle p1"></span>
            <span class="particle p2 gold"></span>
            <span class="particle p3"></span>
            <span class="particle p4 pink"></span>
            <span class="particle p5"></span>
            <span class="particle p6 gold"></span>
            <span class="particle p7 pink"></span>
            <span class="particle p8"></span>
        </div>

        <section class="settings-layout">
            <div class="account-card">
                <h2 class="account-title coolveticaa">Account Setting</h2>

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
                        <label>Name</label>
                        <input type="text" id="nama" name="nama" autocomplete="off" readonly>
                        <small class="error-message" id="namaError"></small>
                    </div>

                    <div class="field">
                        <label>Email</label>
                        <input type="email" id="email" name="email" autocomplete="off" readonly>
                        <small class="error-message" id="emailError"></small>
                    </div>

                    <div class="field">
                        <label>Phone Number</label>
                        <input type="text" id="no_telepon" name="no_telepon" autocomplete="off" maxlength="20" readonly>
                        <small class="error-message" id="noTeleponError"></small>
                    </div>

                    <div class="field" id="fotoField" style="margin-top: 10px; display: none;">
                        <label>Profile Picture</label>
                        <input type="file" id="fotoFile" name="fotoFile" class="file-input-custom" accept="image/*">
                        <small class="error-message" id="fotoError"></small>
                    </div>

                    <div class="field" style="margin-top: 1.5rem;">
                        <button type="button" id="btnOpenPwModal" class="btn-link" style="background:none; border:none; color:var(--primary-color); cursor:pointer; font-weight:700; padding:0; text-decoration:underline;">
                            Change Password?
                        </button>
                    </div>

                    <div class="btn-row">
                        <button type="submit" class="btn btn-save" id="btnEditSave">Change Detail</button>
                        <button type="button" class="btn btn-cancel" id="btnCancel" style="display: none;">Cancel</button>
                        <button type="button" id="btnDelete" class="btn btn-delete">Delete Account</button>
                    </div>
                </form>
            </div>
            
            <div class="modal-overlay" id="pwModal">
                <div class="event-modal" style="width: 450px;"> 
                    <button class="event-modal-close" id="btnClosePwModal">&times;</button>
                    
                    <div class="modal-card" style="width: 100%;">
                        <div class="modal-title">
                            <span class="title-blue">CHANGE</span> <span class="title-dark">PASSWORD</span>
                        </div>
                        <div class="modal-code">Secure your account access</div>

                        <form id="pwForm" style="margin-top: 20px;">
                            <div class="modal-field">
                                <label class="modal-label-dark">Current Password</label>
                                <input type="password" id="current_password" class="modal-input-pill" required>
                            </div>

                            <div class="modal-field">
                                <label class="modal-label-dark">New Password</label>
                                <input type="password" id="new_password" class="modal-input-pill" required>
                            </div>

                            <div class="modal-field">
                                <label class="modal-label-dark">Confirm New Password</label>
                                <input type="password" id="confirm_new_password" class="modal-input-pill" required>
                            </div>

                            <div class="modal-footer" style="margin-top: 30px;">
                                <button type="submit" class="modal-confirm-btn" style="width: 100%; height: 45px; font-size: 16px;">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <aside class="card-showcase">
                <div class="cards-wrapper">
                    
                    <div class="card-anchor anchor-1">
                        <div class="card-float">
                            <img src="/CardHaven/assets/image/card-sylveon.jpg" alt="Sylveon EX">
                        </div>
                    </div>
                    
                    <div class="card-anchor anchor-2">
                        <div class="card-float">
                            <img src="/CardHaven/assets/image/card-mimikyu.jpg" alt="Mimikyu VMAX">
                        </div>
                    </div>
                    
                    <div class="card-anchor anchor-3">
                        <div class="card-float">
                            <img src="/CardHaven/assets/image/card-umbreon.jpg" alt="Umbreon EX">
                        </div>
                    </div>

                </div>
            </aside>
        </section>
    </main>
</div>

<script src="/cardhaven/interface/account-setting/account-setting.js?v=<?= time() ?>"></script>
</body>
</html>