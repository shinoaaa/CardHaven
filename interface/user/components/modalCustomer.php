<div id="customerOverlay" class="supp-overlay" onclick="handleOverlayClick(event)"></div>

<div id="modalCustomerDetail" class="supp-modal">
    <div class="supp-modal-header">
        <h3 class="coolveticaa supp-modal-title">Customer Detail</h3>
        <button class="supp-close-btn" onclick="closeCustomerModal()">&#x2715;</button>
    </div>
    <div class="supp-modal-body">
        <div style="text-align: center; margin-bottom: 20px;">
            <img id="detailFoto" src="/cardhaven/assets/image/user.svg" alt="Profile Picture" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #0D47A1;">
        </div>
        <div class="supp-detail-grid">
            <div class="supp-detail-item">
                <span class="supp-detail-label">Nama</span>
                <span class="supp-detail-value" id="detailUsername">-</span>
            </div>
            <div class="supp-detail-item">
                <span class="supp-detail-label">Email</span>
                <span class="supp-detail-value" id="detailEmail">-</span>
            </div>
            <div class="supp-detail-item">
                <span class="supp-detail-label">No Telepon</span>
                <span class="supp-detail-value" id="detailNoTelp">-</span>
            </div>
            <div class="supp-detail-item">
                <span class="supp-detail-label">Created Date</span>
                <span class="supp-detail-value" id="detailCreated">-</span>
            </div>
            <div class="supp-detail-item">
                <span class="supp-detail-label">Status</span>
                <span class="supp-detail-value" id="detailStatus">-</span>
            </div>
        </div>
    </div>
    <div class="supp-modal-footer">
        <button class="btn-cancel-outline" onclick="closeCustomerModal()">Close</button>
    </div>
</div>

<div id="modalCustomerAdd" class="supp-modal">
    <div class="supp-modal-header">
        <h3 class="coolveticaa supp-modal-title">Add Customer</h3>
        <button class="supp-close-btn" onclick="closeAddModal()">&#x2715;</button>
    </div>
    <div class="supp-modal-body">
        <form id="customerAddForm" novalidate enctype="multipart/form-data">
            <div class="supp-form-group">
                <label class="supp-label" for="addUsername">Nama <span class="supp-required">*</span></label>
                <input type="text" id="addUsername" class="supp-input" placeholder="Enter name" autocomplete="off">
                <span class="supp-err" id="err-add-username"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="addEmail">Email <span class="supp-required">*</span></label>
                <input type="email" id="addEmail" class="supp-input" placeholder="Enter email address" autocomplete="off">
                <span class="supp-err" id="err-add-email"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="addNoTelp">No Telepon</label>
                <input type="text" id="addNoTelp" class="supp-input" placeholder="Enter phone number" autocomplete="off">
                <span class="supp-err" id="err-add-notelp"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="addPassword">Password <span class="supp-required">*</span></label>
                <input type="password" id="addPassword" class="supp-input" placeholder="Enter password">
                <span class="supp-err" id="err-add-password"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="addConfirmPassword">Confirm Password <span class="supp-required">*</span></label>
                <input type="password" id="addConfirmPassword" class="supp-input" placeholder="Re-enter password">
                <span class="supp-err" id="err-add-confirm-password"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="addFoto">Profile Photo</label>
                <input type="file" id="addFoto" class="supp-input" accept="image/*">
                <span class="supp-err" id="err-add-foto"></span>
            </div>
        </form>
    </div>
    <div class="supp-modal-footer">
        <button class="btn-cancel-outline" onclick="closeAddModal()">Cancel</button>
        <button class="btn-confirm" onclick="submitAddCustomer()">Save Customer</button>
    </div>
</div>

<div id="modalCustomerEdit" class="supp-modal">
    <div class="supp-modal-header">
        <h3 class="coolveticaa supp-modal-title">Edit Customer</h3>
        <button class="supp-close-btn" onclick="closeEditModal()">&#x2715;</button>
    </div>
    <div class="supp-modal-body">
        <form id="customerEditForm" novalidate enctype="multipart/form-data">
            <input type="hidden" id="editCustomerId">
            <div style="text-align: center; margin-bottom: 15px;">
                <img id="editFotoPreview" src="/cardhaven/assets/image/user.svg" alt="Current Profile" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="editUsername">Nama <span class="supp-required">*</span></label>
                <input type="text" id="editUsername" class="supp-input" placeholder="Enter name" autocomplete="off">
                <span class="supp-err" id="err-edit-username"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="editEmail">Email <span class="supp-required">*</span></label>
                <input type="email" id="editEmail" class="supp-input" placeholder="Enter email address" autocomplete="off">
                <span class="supp-err" id="err-edit-email"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="editNoTelp">No Telepon</label>
                <input type="text" id="editNoTelp" class="supp-input" placeholder="Enter phone number" autocomplete="off">
                <span class="supp-err" id="err-edit-notelp"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="editPassword">New Password <span style="font-size: 0.75rem; color: #7A8BA8;">(Leave blank to keep current)</span></label>
                <input type="password" id="editPassword" class="supp-input" placeholder="Enter new password">
                <span class="supp-err" id="err-edit-password"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="editConfirmPassword">Confirm New Password</label>
                <input type="password" id="editConfirmPassword" class="supp-input" placeholder="Re-enter new password">
                <span class="supp-err" id="err-edit-confirm-password"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="editFoto">Change Profile Photo</label>
                <input type="file" id="editFoto" class="supp-input" accept="image/*">
                <span class="supp-err" id="err-edit-foto"></span>
            </div>
        </form>
    </div>
    <div class="supp-modal-footer">
        <button class="btn-cancel-outline" onclick="closeEditModal()">Cancel</button>
        <button class="btn-confirm" onclick="submitEditCustomer()">Update Customer</button>
    </div>
</div>

<style>
/* Masukkan semua style CSS bawaan dari referensi supplier lu di sini agar desainnya konsisten */
.supp-overlay { display: none; position: fixed; inset: 0; background: rgba(13, 71, 161, 0.25); z-index: 900; backdrop-filter: blur(2px); }
.supp-overlay.active { display: block; }
.supp-modal { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.96); z-index: 1000; width: 100%; max-width: 480px; background: #fff; border-radius: 14px; box-shadow: 0 8px 40px rgba(13, 71, 161, 0.18); opacity: 0; transition: opacity 0.18s ease, transform 0.18s ease; }
.supp-modal.active { display: block; opacity: 1; transform: translate(-50%, -50%) scale(1); }
.supp-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px 16px; border-bottom: 1.5px solid #E8EEF8; }
.supp-modal-title { margin: 0; font-size: 1.15rem; color: #0D47A1; }
.supp-close-btn { background: none; border: none; font-size: 1.1rem; color: #7A8BA8; cursor: pointer; padding: 4px 8px; border-radius: 6px; line-height: 1; transition: background 0.15s, color 0.15s; }
.supp-close-btn:hover { background: #F0F4FF; color: #0D47A1; }
.supp-modal-body { padding: 20px 24px; max-height: 60vh; overflow-y: auto; }
.supp-detail-grid { display: flex; flex-direction: column; gap: 14px; }
.supp-detail-item { display: flex; flex-direction: column; gap: 3px; padding-bottom: 12px; border-bottom: 1px solid #F0F4FF; }
.supp-detail-item:last-child { border-bottom: none; }
.supp-detail-label { font-size: 0.75rem; font-weight: 600; color: #7A8BA8; text-transform: uppercase; letter-spacing: 0.05em; }
.supp-detail-value { font-size: 0.95rem; color: #1A2340; font-weight: 500; }
.supp-form-group { display: flex; flex-direction: column; gap: 5px; margin-bottom: 16px; }
.supp-label { font-size: 0.82rem; font-weight: 600; color: #3A4A6B; }
.supp-required { color: #E74C3C; }
.supp-input { width: 100%; padding: 9px 13px; border: 1.5px solid #D0DAF0; border-radius: 8px; font-size: 0.92rem; color: #1A2340; background: #F8FAFF; outline: none; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; font-family: inherit; }
.supp-input:focus { border-color: #0D47A1; box-shadow: 0 0 0 3px rgba(13,71,161,0.1); background: #fff; }
.supp-input.input-error { border-color: #E74C3C; box-shadow: 0 0 0 3px rgba(231,76,60,0.1); }
.supp-err { font-size: 0.78rem; color: #E74C3C; min-height: 16px; display: block; }
.supp-modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 14px 24px 20px; border-top: 1.5px solid #E8EEF8; }
.badge-active { display: inline-block; padding: 2px 10px; background: #E8F8EF; color: #27AE60; border-radius: 20px; font-weight: 700; font-size: 0.85rem; }
.badge-inactive { display: inline-block; padding: 2px 10px; background: #FDECEA; color: #E74C3C; border-radius: 20px; font-weight: 700; font-size: 0.85rem; }
</style>