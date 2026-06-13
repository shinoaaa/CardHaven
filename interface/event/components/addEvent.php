<div class="add-event-card">
    <div style="display: flex;">
        <div>
            <div class="ae-header">
                <span class="ae-title-black">ADD</span>
                <span class="ae-title-blue"> Event</span>
            </div>
            
            <div class="ae-grid-2">
                <div class="ae-field">
                    <label class="ae-label">Event Name <span class="ae-required">*</span></label>
                    <input id="ae_nama_event" type="text" class="ae-input" placeholder="e.g. Promo Lebaran">
                    <span class="ae-error" id="err_nama_event"></span>
                </div>
                
                <div class="ae-field">
                    <label class="ae-label">Event Type <span class="ae-required">*</span></label>
                    <div class="ae-select-wrap">
                        <select id="ae_tipe_event" class="ae-input ae-select" onchange="aeOnTypeChange()">
                            <option value="">— Select type —</option>
                            <option value="preorder">Pre-Order</option>
                            <option value="promo">Promo</option>
                        </select>
                    </div>
                    <span class="ae-error" id="err_tipe_event"></span>
                </div>
                
                <div class="ae-field">
                    <label class="ae-label">Start Date <span class="ae-required">*</span></label>
                    <input id="ae_tanggal_mulai" type="date" class="ae-input" onchange="aeOnStartDateChange()">
                    <span class="ae-error" id="err_tanggal_mulai"></span>
                </div>
                
                <div class="ae-field">
                    <label class="ae-label">End Date <span class="ae-required">*</span></label>
                    <input id="ae_tanggal_berakhir" type="date" class="ae-input">
                    <span class="ae-error" id="err_tanggal_berakhir"></span>
                </div>
                
                <div class="ae-field">
                    <label class="ae-label">Discount (%) <span class="ae-required">*</span></label>
                    <input id="ae_persen_diskon" type="number" class="ae-input" placeholder="e.g. 10 or 20" step="0.01">
                    <span class="ae-error" id="err_persen_diskon"></span>
                </div>
            
                <div class="ae-field">
                    <label class="ae-label">Max Purchase <span class="ae-required">*</span></label>
                    <input id="ae_maks_pembelian" type="number" class="ae-input" placeholder="e.g. 5" min="1">
                    <span class="ae-error" id="err_maks_pembelian"></span>
                </div>
            </div>
            
            <div class="ae-field" id="ae_row_tanggal_sampai" style="display:none;">
                <label class="ae-label">Estimated Arrival Date <span class="ae-required">*</span></label>
                <input id="ae_tanggal_sampai" type="date" class="ae-input">
                <span class="ae-error" id="err_tanggal_sampai"></span>
            </div>
            
            <div class="ae-divider">
                <span>Product</span>
            </div>
            
            <div class="ae-product-search-wrap">
                <div class="ae-field" style="flex:1; margin-bottom:0;">
                    <label class="ae-label">Search Product</label>
                    <input id="ae_search_produk" type="text" class="ae-input" placeholder="Type product name..."
                    oninput="aeDebounceSearch()" autocomplete="off">
                    <div id="ae_search_results" class="ae-search-dropdown"></div>
                </div>
            </div>
            
            <div class="ae-product-form-grid">
                <div class="ae-field">
                    <label class="ae-label">Stock</label>
                    <input id="ae_stok_event" type="number" class="ae-input" placeholder="Stock qty" min="1">
            </div>
                <div class="ae-field ae-field-readonly">
                    <label class="ae-label">Event Price <small style="font-weight:400;color:#888;">(auto)</small></label>
                    <input id="ae_harga_event" type="text" class="ae-input" readonly placeholder="—">
                </div>
            </div>
            
            <input type="hidden" id="ae_selected_id_produk" value="">
            <input type="hidden" id="ae_selected_harga_jual" value="">
            <span class="ae-error" id="err_produk"></span>
            
            <div style="text-align:center; margin: 10px 0 6px;">
                <button class="ae-btn-add-prod" onclick="aeAddProductToList()">
                    + Add Product
                </button>
                <span class="ae-error" id="err_product_list"></span>
            </div>
        </div>

        <div style=" display: flex; align-items: center;">
            <div id="ae_product_table_wrap" style="display:none; height: 13rem; overflow-y: auto;">
                <table class="ae-product-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="ae_product_tbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="ae-footer">
        <button class="ae-btn-cancel" onclick="closeEventModal()">Cancel</button>
        <button class="ae-btn-confirm" onclick="aeSubmitEvent()">Confirm</button>
    </div>

</div>

<style>
/* ── Add Event modal card ───────────────────────────────────────────── */
.add-event-card {
    font-family: Arial, sans-serif;
}

.ae-header {
    text-align: center;
    font-size: 26px;
    font-weight: 900;
    margin-bottom: 22px;
    letter-spacing: -0.5px;
}

.ae-title-black { color: #1a1a1a; }
.ae-title-blue  { color: #1284ff; }

.ae-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 0 18px;
}

.ae-field {
    margin-bottom: 14px;
    position: relative;
}

.ae-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.ae-required { color: #e74c3c; }

.ae-input {
    width: 100%;
    box-sizing: border-box;
    height: 36px;
    border-radius: 999px;
    border: 1.5px solid #d0d7e3;
    padding: 0 14px;
    font-size: 13px;
    color: #222;
    background: #fff;
    outline: none;
    transition: border-color .18s;
    appearance: none;
    -webkit-appearance: none;
}

.ae-input:focus { border-color: #1284ff; }
.ae-input.ae-error-border { border-color: #e74c3c !important; }
.ae-input[readonly] { background: #f3f6fb; color: #888; cursor: default; }

.ae-select-wrap { position: relative; }
.ae-select-wrap::after {
    content: '▼';
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 10px;
    color: #888;
    pointer-events: none;
}
.ae-select { padding-right: 32px; cursor: pointer; }

.ae-error {
    display: block;
    font-size: 11px;
    color: #e74c3c;
    min-height: 14px;
    margin-top: 10px;

}

.ae-divider {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 18px 0 14px;
    color: #888;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.ae-divider::before,
.ae-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #dde3ee;
}

.ae-product-search-wrap {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    margin-bottom: 10px;
    position: relative;
}

.ae-search-dropdown {
    position: absolute;
    top: calc(100% + 2px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1.5px solid #d0d7e3;
    border-radius: 14px;
    max-height: 180px;
    overflow-y: auto;
    z-index: 999;
    display: none;
    box-shadow: 0 6px 18px rgba(0,0,0,.10);
}

.ae-search-dropdown.open { display: block; }

.ae-search-item {
    padding: 9px 14px;
    font-size: 13px;
    cursor: pointer;
    border-bottom: 1px solid #f0f3f8;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background .12s;
}

.ae-search-item:last-child { border-bottom: none; }
.ae-search-item:hover { background: #eef4ff; }

.ae-search-item-name { font-weight: 600; color: #1a1a1a; }
.ae-search-item-price { color: #1284ff; font-size: 12px; font-weight: 700; }
.ae-search-item-type { font-size: 11px; color: #888; }

.ae-product-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0 18px;
    margin-bottom: 4px;
}

.ae-btn-add-prod {
    height: 34px;
    padding: 0 28px;
    border-radius: 999px;
    border: none;
    background: #1a1a1a;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s;
}
.ae-btn-add-prod:hover { background: #333; }

/* ── product table ── */
.ae-product-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    font-size: 12px;
    border-radius: 12px;
    overflow: hidden;
}

.ae-product-table th {
    background: #1284ff;
    color: #fff;
    padding: 8px 10px;
    text-align: left;
    font-weight: 700;
}

.ae-product-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #eef2f8;
    background: #fff;
}

.ae-product-table tr:last-child td { border-bottom: none; }

.ae-btn-del-prod {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: none;
    background: #e74c3c;
    color: #fff;
    font-size: 14px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background .15s;
}
.ae-btn-del-prod:hover { background: #c0392b; }

/* ── footer ── */
.ae-footer {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: 20px;
}

.ae-btn-cancel {
    min-width: 110px;
    height: 36px;
    border-radius: 999px;
    border: 2px solid #1284ff;
    background: transparent;
    color: #1284ff;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    transition: all .15s;
}
.ae-btn-cancel:hover { background: #eef4ff; }

.ae-btn-confirm {
    min-width: 110px;
    height: 36px;
    border-radius: 999px;
    border: none;
    background: #1284ff;
    color: #fff;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    transition: background .15s;
}
.ae-btn-confirm:hover { background: #0d6de0; }
</style>