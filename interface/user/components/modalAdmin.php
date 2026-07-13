<div id="adminOverlay" class="supp-overlay" onclick="handleOverlayClick(event)"></div>

<div id="modalAdminDetail" class="supp-modal" style="min-width: 30rem;">
    <div class="modal-header">
        <h2 id="pTitle">Employee <span class="blue-text">Details</span></h2>
        <span id="pDisplayID" class="game-id"></span>
    </div>
    <div class="supp-modal-body">
        <div style="text-align: center; margin-bottom: 20px;">
            <div class="image-upload-wrapper">
                <img id="detailFoto" src="/cardhaven/assets/image/user.svg" alt="Profile Picture" 
                    style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #0D47A1; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            </div>
        </div>
        <div class="supp-detail-grid">
            <div class="supp-detail-item">
                <span class="supp-detail-label">Username</span>
                <span class="supp-detail-value" id="detailUsername">-</span>
            </div>
            <div class="supp-detail-item">
                <span class="supp-detail-label">Email</span>
                <span class="supp-detail-value" id="detailEmail">-</span>
            </div>
            <div class="supp-detail-item">
            <span class="supp-detail-label">Phone Number</span>
            <span class="supp-detail-value" id="detailNoTelp">-</span>
            </div>
            <div class="supp-detail-item">
                <span class="supp-detail-label">Created Date</span>
                <span class="supp-detail-value" id="detailCreated">-</span>
            </div>
            <div class="supp-detail-item" style="align-items: center;">
                <span class="supp-detail-label">Status</span>
                <span class="supp-detail-value" id="detailStatus">-</span>
            </div>
        </div>
    </div>
    <div class="supp-modal-footer">
        <button class="btn-cancel-outline" onclick="closeAdminModal()">Close</button>
    </div>
</div>

<div id="modalAdminAdd" class="supp-modal">
    <div class="modal-header">
        <h2 id="pTitle">Add <span class="blue-text">Admin</span></h2>
        <span id="pDisplayID" class="game-id"></span>
    </div>
    <div class="supp-modal-body">
        <form id="adminAddForm" novalidate enctype="multipart/form-data">
            <div style="text-align: center; margin-bottom: 15px;">
                <div class="image-upload-wrapper">
                    <img id="addFotoPreview" src="/cardhaven/assets/image/user.svg" alt="Preview" 
                        style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px dashed #0D47A1; padding: 3px;">
                </div>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="addUsername">Username<span class="supp-required">*</span></label>
                <input type="text" id="addUsername" class="supp-input" placeholder="Enter username" autocomplete="off" style="width: 100%;">
                <span class="supp-err" id="err-add-username"></span>
            </div>

            <div style="display: flex; gap: 1rem;">
                <div class="supp-form-group">
                    <label class="supp-label" for="addEmail">Email<span class="supp-required">*</span></label>
                    <input type="email" id="addEmail" class="supp-input" placeholder="Enter email address" autocomplete="off">
                    <span class="supp-err" id="err-add-email"></span>
                </div>
                <div class="supp-form-group">
                    <label class="supp-label" for="addNoTelp">Phone Number<span class="supp-required">*</span></label>
                    <input type="text" id="addNoTelp" class="supp-input" placeholder="Enter phone number" autocomplete="off">
                    <span class="supp-err" id="err-add-notelp"></span>
                </div>
            </div>
            <div style="display: flex; gap: 1rem;">
                <div class="supp-form-group">
                    <label class="supp-label" for="addPassword">Password<span class="supp-required">*</span></label>
                    <input type="password" id="addPassword" class="supp-input" placeholder="Enter password">
                    <span class="supp-err" id="err-add-password"></span>
                </div>
                <div class="supp-form-group">
                    <label class="supp-label" for="addConfirmPassword">Confirm Password<span class="supp-required">*</span></label>
                    <input type="password" id="addConfirmPassword" class="supp-input" placeholder="Re-enter password">
                    <span class="supp-err" id="err-add-confirm-password"></span>
                </div>
            </div>
            <div class="supp-form-group" style="width: 100%;">
                <label class="supp-label" for="addFoto">Profile Photo</label>
                <input type="file" id="addFoto" class="supp-input" accept="image/*" style="width: 100%;">
                <span class="supp-err" id="err-add-foto"></span>
            </div>
        </form>
    </div>
    <div class="supp-modal-footer">
        <button class="btn-cancel-outline" onclick="closeAddModal()">Cancel</button>
        <button class="btn-confirm" onclick="submitAddAdmin()">Save</button>
    </div>
</div>

<div id="modalAdminEdit" class="supp-modal">
    <div class="modal-header">
        <h2 id="pTitle">Edit <span class="blue-text">Admin</span></h2>
        <span id="pDisplayID" class="game-id"></span>
    </div>
    <div class="supp-modal-body">
        <form id="adminEditForm" novalidate enctype="multipart/form-data">
            <input type="hidden" id="editAdminId">
            <div style="text-align: center; margin-bottom: 15px;">
                <div class="image-upload-wrapper">
                    <img id="editFotoPreview" src="/cardhaven/assets/image/user.svg" alt="Current Profile" 
                        style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #0D47A1;">
                </div>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="editUsername">Username<span class="supp-required">*</span></label>
                <input type="text" id="editUsername" class="supp-input" placeholder="Enter username" autocomplete="off" style="width: 100%;">
                <span class="supp-err" id="err-edit-username"></span>
            </div>
            <div style="display: flex; gap: 1rem;">
                <div class="supp-form-group">
                    <label class="supp-label" for="editEmail">Email<span class="supp-required">*</span></label>
                    <input type="email" id="editEmail" class="supp-input" placeholder="Enter email address" autocomplete="off">
                    <span class="supp-err" id="err-edit-email"></span>
                </div>
                <div class="supp-form-group">
                    <label class="supp-label" for="editNoTelp">Phone Number<span class="supp-required">*</span></label>
                    <input type="text" id="editNoTelp" class="supp-input" placeholder="Enter phone number" autocomplete="off">
                    <span class="supp-err" id="err-edit-notelp"></span>
                </div>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="editFoto">Change Profile Photo</label>
                <input type="file" id="editFoto" class="supp-input" accept="image/*" style="width: 100%;">
                <span class="supp-err" id="err-edit-foto"></span>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: -10px; margin-bottom: 15px; padding-right: 5px;">
                <a href="javascript:void(0)" onclick="openAdminChangePasswordModal()" class="change-pass-link">
                    Change Password?
                </a>
            </div>
        </form>
    </div>
    <div class="supp-modal-footer">
        <button class="btn-cancel-outline" onclick="closeEditModal()">Cancel</button>
        <button class="btn-confirm" onclick="submitEditAdmin()">Update</button>
    </div>
</div>

<div id="modalAdminChangePassword" class="supp-modal" style="min-width: 25rem;">
    <div class="modal-header">
        <h2>Change <span class="blue-text">Password</span></h2>
    </div>
    <div class="supp-modal-body">
        <form id="adminChangePasswordForm" novalidate>
            <input type="hidden" id="adminChangeEmail">
            
            <!-- TAHAP 1: VERIFIKASI -->
            <div id="admin-verify-section">
                <p style="font-size: 0.85rem; color: #666; margin-bottom: 15px;">Verify identity by entering the account creation date.</p>
                <div class="supp-form-group">
                    <label class="supp-label">Created Date<span class="supp-required">*</span></label>
                    <input type="date" id="adminChangeCreatedDate" class="supp-input" style="width: 100%;">
                    <span class="supp-err" id="err-admin-change-date"></span>
                </div>
            </div>

            <!-- TAHAP 2: RESET -->
            <div id="admin-reset-section" style="display: none;">
                <div class="supp-form-group">
                    <label class="supp-label">New Password<span class="supp-required">*</span></label>
                    <input type="password" id="adminChangeNewPassword" class="supp-input" style="width: 100%;" placeholder="8-12 characters + symbol">
                    <span class="supp-err" id="err-admin-change-pass"></span>
                </div>
                <div class="supp-form-group">
                    <label class="supp-label">Confirm New Password<span class="supp-required">*</span></label>
                    <input type="password" id="adminChangeConfirmPassword" class="supp-input" style="width: 100%;">
                    <span class="supp-err" id="err-admin-change-confirm"></span>
                </div>
            </div>
        </form>
    </div>
    <div class="supp-modal-footer">
        <button class="btn-cancel-outline" onclick="closeAdminChangePasswordModal()">Cancel</button>
        <button type="button" id="btn-admin-submit-change" class="btn-confirm" onclick="handleAdminPasswordStep()">Verify</button>
    </div>
</div>

<style>
/* Base Style (Bisa di-include di satu file css global sebenarnya biar nggak numpuk, tp gw masukkin buat contoh lengkap) */
.supp-overlay { display: none; position: fixed; inset: 0; background: rgba(13, 71, 161, 0.25); z-index: 900; backdrop-filter: blur(2px); }
.supp-overlay.active { display: block; }
.supp-modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.96);

    z-index: 1000;
    padding: 30px;

    background: linear-gradient(180deg, #FFFFFF 0%, #E1EBFF 100%);
    border-radius: 40px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);

    opacity: 0;
    transition: opacity 0.18s ease, transform 0.18s ease;
}
.supp-modal.active { display: block; opacity: 1; transform: translate(-50%, -50%) scale(1); }
.supp-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px 16px; border-bottom: 1.5px solid #E8EEF8; }
.supp-modal-title { margin: 0; font-size: 1.15rem; color: #0D47A1; }
.supp-close-btn { background: none; border: none; font-size: 1.1rem; color: #7A8BA8; cursor: pointer; padding: 4px 8px; border-radius: 6px; line-height: 1; transition: background 0.15s, color 0.15s; }
.supp-close-btn:hover { background: #F0F4FF; color: #0D47A1; }
.supp-modal-body { padding: 0px 5px }
.supp-detail-grid { display: flex; flex-direction: column; gap: 14px; }
.supp-detail-item { display: flex; flex-direction: column; gap: 3px; padding-bottom: 12px; border-bottom: 1px solid #F0F4FF; }
.supp-detail-item:last-child { border-bottom: none; }
.supp-detail-label { font-size: 0.75rem; font-weight: 600; color: #7A8BA8; text-transform: uppercase; letter-spacing: 0.05em; }
.supp-detail-value { font-size: 0.95rem; color: #1A2340; font-weight: 500; padding: ;}
.supp-form-group { display: flex; flex-direction: column; gap: 5px; margin-bottom: 16px; }
.supp-label { font-size: 0.82rem; font-weight: 600; color: #3A4A6B; }
.supp-required { color: #E74C3C; }
.supp-input {padding: 10px 20px; width: 20rem; border: 1.5px solid #D0DAF0; border-radius: 9999px; font-size: 0.92rem; color: #1A2340; background: transparent; outline: none; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; font-family: inherit; }
.supp-input:focus { border-color: #0D47A1; box-shadow: 0 0 0 3px rgba(13,71,161,0.1); background: #fff; }
.supp-input.input-error { border-color: #E74C3C; box-shadow: 0 0 0 3px rgba(231,76,60,0.1); }
.supp-err { font-size: 0.78rem; color: #E74C3C; min-height: 16px; display: block; }
.supp-modal-footer { display: flex; justify-content: flex-end; gap: 4px; padding: 6px 15px; border-top: 1.5px solid #E8EEF8; }
.badge-active { display: inline-block; padding: 7px 75px; background: #d2ffe5; color: #0e6e36; border-radius: 9999px; font-weight: 700; font-size: 0.85rem; }
.badge-inactive { display: inline-block; padding: 7px 75px; background: #FDECEA; color: #E74C3C; border-radius: 9999px; font-weight: 700; font-size: 0.85rem; }
.change-pass-link {
    color: #0088FF;
    font-size: 13px;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.change-pass-link:hover {
    color: #0056b3;
    text-decoration: underline;
}

/* Pastikan input modal juga mengikuti style rounded di gambar */
.supp-input {
    padding: 12px 25px; /* Sedikit lebih gemuk seperti gambar */
    border: 1.5px solid #0F3891; /* Biru gelap seperti border di gambar */
}
</style>