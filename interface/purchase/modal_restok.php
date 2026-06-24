<div id="restokOverlay" class="supp-overlay" onclick="closeRestokModal()"></div>

<div id="restokDetailModal" class="supp-modal" style="min-width: 38rem; max-width: 44rem;">
    <div class="modal-header">
        <h2>Purchase Order <span class="blue-text" id="modalPOId"></span></h2>
    </div>
    <div class="supp-modal-body" style="padding: 0 4px;">

        <!-- Info Header -->
        <div id="modalHeaderInfo" style="display: flex; gap: 1.5rem; flex-wrap: wrap; margin: 1rem 0 1.2rem 0;">
            <div class="supp-detail-item" style="flex: 1; min-width: 140px;">
                <span class="supp-detail-label">Supplier</span>
                <span class="supp-detail-value" id="modalSupplier">-</span>
            </div>
            <div class="supp-detail-item" style="flex: 1; min-width: 140px;">
                <span class="supp-detail-label">Phone</span>
                <span class="supp-detail-value" id="modalTelp">-</span>
            </div>
            <div class="supp-detail-item" style="flex: 1; min-width: 140px;">
                <span class="supp-detail-label">Date</span>
                <span class="supp-detail-value" id="modalTanggal">-</span>
            </div>
            <div class="supp-detail-item" style="flex: 1; min-width: 140px;">
                <span class="supp-detail-label">Created By</span>
                <span class="supp-detail-value" id="modalCreatedBy">-</span>
            </div>
        </div>

        <!-- Status badge -->
        <div style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.75rem;">
            <span class="supp-detail-label">Status</span>
            <span id="modalStatusBadge"></span>
            <span id="modalApprovedBy" style="font-size: 0.78rem; color: #7A8BA8;"></span>
        </div>

        <!-- Items Table -->
        <table class="styled-table" style="margin-bottom: 0.5rem;">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody id="modalItemsBody">
                <tr><td colspan="4">Loading...</td></tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right; font-weight: 700; padding: 10px; border-top: 2px solid #0D47A1; color: #0D47A1;">TOTAL</td>
                    <td style="border-top: 2px solid #0D47A1;"></td>
                    <td style="text-align: right; font-weight: 700; padding: 10px; border-top: 2px solid #0D47A1; color: #0D47A1;" id="modalTotal">-</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="supp-modal-footer" id="modalFooter">
        <!-- Tombol approve/reject hanya muncul untuk superadmin dan hanya kalau pending -->
        <button class="btn-cancel-outline" onclick="closeRestokModal()">Close</button>
    </div>
</div>

<style>
/* Badge status restok */
.badge-pending  { display:inline-block; padding:5px 18px; background:#FFF3CD; color:#856404; border-radius:9999px; font-weight:700; font-size:0.82rem; }
.badge-approved { display:inline-block; padding:5px 18px; background:#D2FFE5; color:#0E6E36; border-radius:9999px; font-weight:700; font-size:0.82rem; }
.badge-rejected { display:inline-block; padding:5px 18px; background:#FDECEA; color:#E74C3C; border-radius:9999px; font-weight:700; font-size:0.82rem; }
</style>