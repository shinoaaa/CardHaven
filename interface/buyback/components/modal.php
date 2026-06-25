
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
