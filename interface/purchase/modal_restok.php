<div id="restokDetailModal" class="modal-overlay">
    <div class="modal-box" style="width: 700px; max-width: 95%;">
        <div class="modal-header">
            <h2>PURCHASE <span class="blue-text">ORDER</span></h2>
            <span class="game-id" id="modalPOId"></span>
        </div>

        <!-- Info Header -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
            <div class="modal-form-group" style="margin-bottom: 0;">
                <label>Supplier</label>
                <div class="detail-field" id="modalSupplier">-</div>
            </div>
            <div class="modal-form-group" style="margin-bottom: 0;">
                <label>Phone</label>
                <div class="detail-field" id="modalTelp">-</div>
            </div>
            <div class="modal-form-group" style="margin-bottom: 0;">
                <label>Date</label>
                <div class="detail-field" id="modalTanggal">-</div>
            </div>
            <div class="modal-form-group" style="margin-bottom: 0;">
                <label>Created By</label>
                <div class="detail-field" id="modalCreatedBy">-</div>
            </div>
        </div>

        <!-- Status -->
        <div class="status-text" style="display:flex; align-items:center; justify-content:center; gap:0.6rem;">
            <span>Status:</span>
            <span id="modalStatusBadge"></span>
            <span id="modalApprovedBy" style="font-size: 0.78rem; color: #7A8BA8; font-weight: 400;"></span>
        </div>

        <!-- Items Table -->
        <table class="modal-product-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody id="modalItemsBody">
                <tr><td colspan="4" style="text-align:center;">Loading...</td></tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right; font-weight: 700; border-top: 2px solid var(--primary-color); color: var(--primary-color);">TOTAL</td>
                    <td style="border-top: 2px solid var(--primary-color);"></td>
                    <td style="text-align: right; font-weight: 700; border-top: 2px solid var(--primary-color); color: var(--primary-color);" id="modalTotal">-</td>
                </tr>
            </tfoot>
        </table>

        <div class="modal-footer" id="modalFooter" style="gap: 0.75rem;">
            <button class="btn-cancel-outline" onclick="closeRestokModal()">Close</button>
        </div>
    </div>
</div>

<style>
/* Badge status restok */
.badge-pending  { display:inline-block; padding:5px 18px; background:#FFF3CD; color:#856404; border-radius:9999px; font-weight:700; font-size:0.82rem; }
.badge-approved { display:inline-block; padding:5px 18px; background:#D2FFE5; color:#0E6E36; border-radius:9999px; font-weight:700; font-size:0.82rem; }
.badge-rejected { display:inline-block; padding:5px 18px; background:#FDECEA; color:#E74C3C; border-radius:9999px; font-weight:700; font-size:0.82rem; }
</style>
