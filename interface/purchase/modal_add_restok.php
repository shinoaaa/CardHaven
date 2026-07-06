<div id="addRestokModal" class="modal-overlay">
    <div class="modal-box" style="width: 750px; max-width: 95%;">
        <div class="modal-header">
            <h2>ADD <span class="blue-text">RESTOCK</span></h2>
        </div>

        <div class="modal-form-group">
            <label>Supplier <span style="color: #E74C3C;">*</span></label>
            <div style="position:relative;">
                <input type="text" id="addSupplierSearch" class="modal-input" placeholder="Type supplier name..." autocomplete="off">
                <input type="hidden" id="addIdSupplier">
                <div id="addSupplierSuggest" class="suggestion-box"></div>
            </div>
            <div class="error-message"></div>
        </div>

        <table class="modal-product-table" id="addItemsTable">
            <thead>
                <tr>
                    <th style="width:40%;">Product</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="addItemsBody">
                <!-- rows ditambahkan via JS -->
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: 700; border-top: 2px solid var(--primary-color); color: var(--primary-color);">TOTAL</td>
                    <td style="border-top: 2px solid var(--primary-color); text-align:right; font-weight:700; color: var(--primary-color);" id="addTotalDisplay">Rp0</td>
                    <td style="border-top: 2px solid var(--primary-color);"></td>
                </tr>
            </tfoot>
        </table>

        <button type="button" class="btn-cancel-outline" style="margin-top:10px;" onclick="addItemRow()">+ Add Item</button>

        <div class="modal-footer" style="gap: 0.75rem; margin-top: 20px;">
            <button class="btn-cancel-outline" onclick="closeAddRestokModal()">Cancel</button>
            <button class="btn-confirm" onclick="submitAddRestok()">Save PO</button>
        </div>
    </div>
</div>

<style>
.modal-select {
    width: 100%;
    padding: 9px 14px;
    border: 1.5px solid #D0DAF0;
    border-radius: 10px;
    font-size: 0.9rem;
    outline: none;
    background: white;
}
.item-row select, .item-row input {
    width: 100%;
    padding: 7px 10px;
    border: 1.5px solid #D0DAF0;
    border-radius: 8px;
    font-size: 0.85rem;
    outline: none;
}
.btn-remove-row {
    background: none;
    border: none;
    color: #E74C3C;
    font-weight: 700;
    cursor: pointer;
    font-size: 1rem;
}
</style>
