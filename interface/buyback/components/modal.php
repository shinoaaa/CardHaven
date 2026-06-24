
<div id="detailModal" class="event-modal-overlay" style="display: none;">
    <div class="modal-box" style="width: 550px; max-width: 95vw;">
        <button class="event-modal-close" onclick="closeDetailModal()" style="background: none; border: none; font-size: 24px; position: absolute; right: 20px; top: 15px; cursor: pointer;">&times;</button>
        <div class="modal-header">
            <h2 style="font-size: 1.5rem; margin-bottom: 5px;">Transaction <span class="blue-text" id="modalTxId"></span></h2>
            <span class="game-id" id="modalStatus" style="font-weight: 600;"></span>
        </div>
        <div id="modalContent" style="margin-top: 15px; max-height: 50vh; overflow-y: auto; padding-right: 10px;"></div>
        <div class="modal-footer" id="modalFooter" style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;"></div>
    </div>
</div>
<div id="submitModal" class="event-modal-overlay" style="display: none;">
    <div class="modal-box" style="width: 650px; max-width: 95vw; max-height: 90vh; overflow-y: auto; background: #fff;">
        <button class="event-modal-close" onclick="closeSubmitModal()" style="background: none; border: none; font-size: 24px; position: absolute; right: 20px; top: 15px; cursor: pointer;">&times;</button>
        <div class="modal-header">
            <h2 style="font-size: 1.8rem; margin-bottom: 5px;">Sell Your Cards</h2>
            <span style="font-size: 0.9rem; color: #666;">You can add multiple cards.</span>
        </div>
        
        <form id="formBuyback" enctype="multipart/form-data" style="margin-top: 20px;">
            <div id="cardInputsContainer">
                <div class="card-input-group" style="border: 2px solid #E1EBFF; padding: 20px; border-radius: 12px; margin-bottom: 15px; background: #fafcff;">
                    <h4 style="margin-top: 0; margin-bottom: 15px; color: var(--primary-color); font-size: 1.1rem;">Card 1</h4>
                    <div class="form-group">
                        <label>Card Name <span class="required">*</span></label>
                        <input type="text" name="nama_kartu[]" required placeholder="e.g., Pikachu VMAX Secret Rare">
                    </div>
                    <div class="form-group">
                        <label>Your Offer Price (Rp) <span class="required">*</span></label>
                        <input type="number" name="harga_beli[]" required placeholder="e.g., 1500000">
                    </div>
                    <div class="form-group">
                        <label>Front Photo <span class="required">*</span></label>
                        <input type="file" name="foto_depan[]" class="file-input-custom" accept="image/*" required>
                    </div>
                    <div class="form-group">
                        <label>Back Photo <span class="required">*</span></label>
                        <input type="file" name="foto_belakang[]" class="file-input-custom" accept="image/*" required>
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn-cancel-outline" 
                        style="flex: 1; border-style: dashed; border-width: 2px; padding: 12px;" 
                        onclick="addCardField()">
                    + Add Another Card
                </button>
                <button type="button" class="btn-confirm" 
                        style="flex: 1; margin: 0; padding: 12px;" 
                        onclick="submitBuyback()">
                    Submit Transaction
                </button>
            </div>
            
        </form>
    </div>
</div>