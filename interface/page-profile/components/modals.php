<div id="modalOverlay" class="ch-overlay" style="display: none;"></div>

<!-- Modal Edit Customer Sesuai Template dari Prompt -->
<div id="modalCustomerEdit" class="supp-modal ch-modal" style="display: none;">
    <div class="modal-header">
        <h2 id="pTitle">Edit <span class="blue-text">Customer</span></h2>
        <button class="btn-close" onclick="closeEditModal()">✕</button>
    </div>
    <div class="supp-modal-body">
        <form id="customerEditForm" novalidate enctype="multipart/form-data">
            <input type="hidden" id="editCustomerId" name="id_pengguna">
            
            <div style="text-align: center; margin-bottom: 15px;">
                <img id="editFotoPreview" src="/cardhaven/assets/image/user.svg" alt="Current Profile" 
                    style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; cursor: pointer;"
                    onclick="document.getElementById('editFoto').click()">
                <p style="font-size: 11px; color: #888; margin-top: 5px;">Click photo to change</p>
            </div>

            <div class="supp-form-group">
                <label class="supp-label" for="editUsername">Username<span class="supp-required">*</span></label>
                <input type="text" id="editUsername" name="editUsername" class="supp-input" placeholder="Enter name" autocomplete="off" style="width: 100%;">
                <span class="supp-err" id="err-edit-username"></span>
            </div>

            <div style="display: flex; gap: 1rem;">
                <div class="supp-form-group" style="width: 100%;">
                    <label class="supp-label" for="editEmail">Email<span class="supp-required">*</span></label>
                    <input type="email" id="editEmail" name="editEmail" class="supp-input" placeholder="Enter email address" autocomplete="off" style="width: 100%;">
                    <span class="supp-err" id="err-edit-email"></span>
                </div>
                <div class="supp-form-group" style="width: 100%;">
                    <label class="supp-label" for="editNoTelp">Phone Number<span class="supp-required">*</span></label>
                    <input type="text" id="editNoTelp" name="editNoTelp" class="supp-input" placeholder="Enter phone number" autocomplete="off" style="width: 100%;">
                    <span class="supp-err" id="err-edit-notelp"></span>
                </div>
            </div>

            <div class="supp-form-group" style="width: 100%;">
                <label class="supp-label" for="editFoto">Change Profile Photo</label>
                <input type="file" id="editFoto" name="editFoto" class="supp-input" accept="image/*" style="width: 100%;">
                <span class="supp-err" id="err-edit-foto"></span>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: -10px; margin-bottom: 15px; padding-right: 5px;">
                <a href="javascript:void(0)" onclick="openCustChangePasswordModal()" class="change-pass-link" style="color:#0D47A1; text-decoration:none; font-size:12px;">
                    Change Password?
                </a>
            </div>
        </form>
    </div>
    <div class="supp-modal-footer">
        <button type="button" class="btn-cancel-outline" onclick="closeEditModal()">Cancel</button>
        <button type="button" class="btn-confirm" onclick="submitEditCustomer()">Update</button>
    </div>
</div>

<!-- Modal Mailbox List -->
<div id="modalMailboxList" class="supp-modal ch-modal" style="display: none;">
    <div class="modal-header" style="display: flex; align-items: center; justify-content: space-between;">
        <h2 style="color: #0D47A1; margin: 0;">Mailbox</h2>
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <button type="button" onclick="markAllAsRead()" style="background: none; border: none; color: #0D47A1; font-size: 0.8rem; font-weight: 600; cursor: pointer;">Mark all as read</button>
            <button class="btn-close" onclick="closeMailboxList()">✕</button>
        </div>
    </div>
    <div class="supp-modal-body" style="padding-top: 10px;">
        <div id="mailListContainer" class="mail-list" style="max-height: 55vh; overflow-y: auto;"></div>
    </div>
</div>

<!-- Modal Mail Content -->
<div id="modalMailContent" class="supp-modal ch-modal" style="display: none;">
    <div class="modal-header">
        <h2 id="mailTitleDetail" style="font-size: 1.2rem; margin-bottom: 0; color: #0D47A1;"></h2>
        <button class="btn-close" onclick="closeMailContent()">✕</button>
    </div>
    <div class="supp-modal-body">
        <p id="mailDateDetail" style="font-size: 0.8rem; color: #888; margin-bottom: 15px;"></p>
        <div id="mailBodyDetail" style="line-height: 1.6; font-size: 0.95rem; color: #333;"></div>
    </div>
    <div class="supp-modal-footer">
        <button class="btn-cancel-outline" onclick="closeMailContent()">Back</button>
        <button id="btnMarkRead" class="btn-confirm">Mark as Read</button>
    </div>
</div>