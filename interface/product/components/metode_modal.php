<?php /* Payment Method modals (add/edit + detail). Dipakai di halaman Transaction (khusus Owner). */ ?>
<div id="metodeModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2 id="metodeModalTitle">ADD <span class="blue-text">PAYMENT METHOD</span></h2>
            <span id="metodeDisplayID" class="game-id"></span>
        </div>

        <form id="metodeForm">
            <input type="hidden" name="action"    id="metodeFormAction" value="add">
            <input type="hidden" name="id_metode" id="metodeIdInput">
            <input type="hidden" name="aktif"     id="metodeAktifStatus" value="1">

            <div class="modal-form-group">
                <label>Method Name <span style="color:#E74C3C;">*</span></label>
                <input type="text" name="nama_metode" id="metodeNama" class="modal-input"
                 placeholder="e.g. GoPay, QRIS, BCA Transfer"
                 maxlength="50">
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label>Provider <span style="color:#E74C3C;">*</span></label>
               <input type="text" name="provider" id="metodeProvider" class="modal-input"
                placeholder="e.g. GoPay, Bank BCA"
                maxlength="50">
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label>Account Number <span style="color:#E74C3C;">*</span></label>
                <input type="text" name="no_rekening" id="metodeNoRek" class="modal-input"
                 placeholder="e.g. 081234567890"
                 inputmode="numeric"
                 maxlength="20"
                 oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label>Account Name <span style="color:#E74C3C;">*</span></label>
                <input type="text" name="atas_nama" id="metodeAtasNama" class="modal-input"
                placeholder="e.g. CardHaven Store"
                maxlength="50">
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label>Admin Fee (Rp) <span style="color:#E74C3C;">*</span></label>
                <input type="number" name="biaya_admin" id="metodeBiaya" class="modal-input" placeholder="e.g. 2000" min="0" value="0">
                <div class="error-message"></div>
            </div>
            <div class="modal-form-group">
                <label>Method Status</label>
                <input type="text" id="metodeStatusDisplay" value="Active" class="modal-input" disabled style="background-color: #f1f5f9; color: #27AE60; font-weight: 800; text-align: center; border: 1.5px dashed #cbd5e1; cursor: not-allowed;">
            </div>
            <button type="submit" class="btn-confirm">Save Method</button>
        </form>
    </div>
</div>

<div id="metodeDetailModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2>PAYMENT METHOD <span class="blue-text">DETAIL</span></h2>
            <span id="metodeDetailDisplayID" class="game-id"></span>
        </div>

        <div class="modal-form-group">
            <label>Method Name</label>
            <div class="detail-field" id="detailMetodeNama">-</div>
        </div>

        <div class="modal-form-group">
            <label>Provider</label>
            <div class="detail-field" id="detailMetodeProvider">-</div>
        </div>

        <div class="modal-form-group">
            <label>Account Number</label>
            <div class="detail-field" id="detailMetodeNoRek">-</div>
        </div>

        <div class="modal-form-group">
            <label>Account Name</label>
            <div class="detail-field" id="detailMetodeAtasNama">-</div>
        </div>

        <div class="modal-form-group">
            <label>Admin Fee (Rp)</label>
            <div class="detail-field" id="detailMetodeBiaya">-</div>
        </div>

        <div class="modal-form-group">
            <label>Status</label>
            <div class="detail-field" id="detailMetodeStatus">-</div>
        </div>

        <button class="btn-confirm" onclick="document.getElementById('metodeDetailModal').style.display='none'">Close</button>
    </div>
</div>
