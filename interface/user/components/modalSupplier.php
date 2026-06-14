<div id="supplierOverlay" class="supp-overlay" onclick="handleOverlayClick(event)"></div>

<!-- ===================== MODAL DETAIL ===================== -->
<div id="modalSupplierDetail" class="supp-modal">
    <div class="supp-modal-header">
        <h3 class="coolveticaa supp-modal-title">Supplier Detail</h3>
        <button class="supp-close-btn" onclick="closeSupplierModal()">&#x2715;</button>
    </div>
    <div class="supp-modal-body">
        <div class="supp-detail-grid">
            <div class="supp-detail-item">
                <span class="supp-detail-label">Supplier Name</span>
                <span class="supp-detail-value" id="detailNama">-</span>
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
                <span class="supp-detail-label">Address</span>
                <span class="supp-detail-value" id="detailAlamat">-</span>
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
        <button class="btn-cancel-outline" onclick="closeSupplierModal()">Close</button>
    </div>
</div>

<!-- ===================== MODAL ADD ===================== -->
<div id="modalSupplierAdd" class="supp-modal">
    <div class="supp-modal-header">
        <h3 class="coolveticaa supp-modal-title">Add Supplier</h3>
        <button class="supp-close-btn" onclick="closeAddModal()">&#x2715;</button>
    </div>
    <div class="supp-modal-body">
        <form id="suppAddForm" novalidate>
            <div class="supp-form-group">
                <label class="supp-label" for="addSupplierName">Supplier Name <span class="supp-required">*</span></label>
                <input type="text" id="addSupplierName" class="supp-input" placeholder="Enter supplier name" autocomplete="off">
                <span class="supp-err" id="err-add-name"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="addSupplierMail">Email <span class="supp-required">*</span></label>
                <input type="email" id="addSupplierMail" class="supp-input" placeholder="Enter email address" autocomplete="off">
                <span class="supp-err" id="err-add-mail"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="addSupplierNum">Phone Number <span class="supp-required">*</span></label>
                <input type="text" id="addSupplierNum" class="supp-input" placeholder="Enter phone number" autocomplete="off">
                <span class="supp-err" id="err-add-num"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="addSupplierAddress">Address <span class="supp-required">*</span></label>
                <textarea id="addSupplierAddress" class="supp-input supp-textarea" placeholder="Enter address" rows="3"></textarea>
                <span class="supp-err" id="err-add-address"></span>
            </div>
        </form>
    </div>
    <div class="supp-modal-footer">
        <button class="btn-cancel-outline" onclick="closeAddModal()">Cancel</button>
        <button class="btn-confirm" onclick="submitAddSupplier()">Save Supplier</button>
    </div>
</div>

<!-- ===================== MODAL EDIT ===================== -->
<div id="modalSupplierEdit" class="supp-modal">
    <div class="supp-modal-header">
        <h3 class="coolveticaa supp-modal-title">Edit Supplier</h3>
        <button class="supp-close-btn" onclick="closeEditModal()">&#x2715;</button>
    </div>
    <div class="supp-modal-body">
        <form id="suppEditForm" novalidate>
            <input type="hidden" id="editSuppId">
            <div class="supp-form-group">
                <label class="supp-label" for="editSupplierName">Supplier Name <span class="supp-required">*</span></label>
                <input type="text" id="editSupplierName" class="supp-input" placeholder="Enter supplier name" autocomplete="off">
                <span class="supp-err" id="err-edit-name"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="editSupplierMail">Email <span class="supp-required">*</span></label>
                <input type="email" id="editSupplierMail" class="supp-input" placeholder="Enter email address" autocomplete="off">
                <span class="supp-err" id="err-edit-mail"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="editSupplierNum">Phone Number <span class="supp-required">*</span></label>
                <input type="text" id="editSupplierNum" class="supp-input" placeholder="Enter phone number" autocomplete="off">
                <span class="supp-err" id="err-edit-num"></span>
            </div>
            <div class="supp-form-group">
                <label class="supp-label" for="editSupplierAddress">Address <span class="supp-required">*</span></label>
                <textarea id="editSupplierAddress" class="supp-input supp-textarea" placeholder="Enter address" rows="3"></textarea>
                <span class="supp-err" id="err-edit-address"></span>
            </div>
        </form>
    </div>
    <div class="supp-modal-footer">
        <button class="btn-cancel-outline" onclick="closeEditModal()">Cancel</button>
        <button class="btn-confirm" onclick="submitEditSupplier()">Update Supplier</button>
    </div>
</div>

<!-- ===================== STYLES ===================== -->
<style>
/* ---------- Overlay ---------- */
.supp-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(13, 71, 161, 0.25);
    z-index: 900;
    backdrop-filter: blur(2px);
}
.supp-overlay.active { display: block; }

/* ---------- Modal base ---------- */
.supp-modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.96);
    z-index: 1000;
    width: 100%;
    max-width: 480px;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 8px 40px rgba(13, 71, 161, 0.18);
    opacity: 0;
    transition: opacity 0.18s ease, transform 0.18s ease;
}
.supp-modal.active {
    display: block;
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
}

/* ---------- Header ---------- */
.supp-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px 16px;
    border-bottom: 1.5px solid #E8EEF8;
}
.supp-modal-title {
    margin: 0;
    font-size: 1.15rem;
    color: #0D47A1;
}
.supp-close-btn {
    background: none;
    border: none;
    font-size: 1.1rem;
    color: #7A8BA8;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    line-height: 1;
    transition: background 0.15s, color 0.15s;
}
.supp-close-btn:hover {
    background: #F0F4FF;
    color: #0D47A1;
}

/* ---------- Body ---------- */
.supp-modal-body {
    padding: 20px 24px;
    max-height: 60vh;
    overflow-y: auto;
}

/* ---------- Detail grid ---------- */
.supp-detail-grid {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.supp-detail-item {
    display: flex;
    flex-direction: column;
    gap: 3px;
    padding-bottom: 12px;
    border-bottom: 1px solid #F0F4FF;
}
.supp-detail-item:last-child { border-bottom: none; }
.supp-detail-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #7A8BA8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.supp-detail-value {
    font-size: 0.95rem;
    color: #1A2340;
    font-weight: 500;
}

/* ---------- Form ---------- */
.supp-form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 16px;
}
.supp-form-group:last-child { margin-bottom: 0; }
.supp-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #3A4A6B;
}
.supp-required { color: #E74C3C; }
.supp-input {
    width: 100%;
    padding: 9px 13px;
    border: 1.5px solid #D0DAF0;
    border-radius: 8px;
    font-size: 0.92rem;
    color: #1A2340;
    background: #F8FAFF;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
    box-sizing: border-box;
    font-family: inherit;
}
.supp-input:focus {
    border-color: #0D47A1;
    box-shadow: 0 0 0 3px rgba(13,71,161,0.1);
    background: #fff;
}
.supp-input.input-error {
    border-color: #E74C3C;
    box-shadow: 0 0 0 3px rgba(231,76,60,0.1);
}
.supp-textarea {
    resize: vertical;
    min-height: 72px;
}
.supp-err {
    font-size: 0.78rem;
    color: #E74C3C;
    min-height: 16px;
    display: block;
}

/* ---------- Footer ---------- */
.supp-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 24px 20px;
    border-top: 1.5px solid #E8EEF8;
}

/* ---------- Status badge ---------- */
.badge-active {
    display: inline-block;
    padding: 2px 10px;
    background: #E8F8EF;
    color: #27AE60;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.85rem;
}
.badge-inactive {
    display: inline-block;
    padding: 2px 10px;
    background: #FDECEA;
    color: #E74C3C;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.85rem;
}
</style>