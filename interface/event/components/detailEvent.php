<?php
require_once __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/../controller/controllerFetch.php';

$id   = isset($_GET['id'])   ? (int)$_GET['id']   : 0;
$type = isset($_GET['type']) ? trim($_GET['type']) : 'detail';

if ($id <= 0) {
    echo '<p style="text-align:center;color:#E74C3C;padding:20px;">ID Event tidak valid.</p>';
    exit;
}

$controller = new controllerEvent($conn);
$row = $controller->fetchEventById($id);

if (!$row) {
    echo '<p style="text-align:center;color:#E74C3C;padding:20px;">Event tidak ditemukan.</p>';
    exit;
}

if (isset($row['tanggal_berakhir']) && $row['tanggal_berakhir'] instanceof DateTime) {
    $row['tanggal_berakhir'] = $row['tanggal_berakhir']->format(
        $type === 'detail' ? 'd-M-Y' : 'Y-m-d'
    );
}

if (isset($row['tanggal_mulai']) && $row['tanggal_mulai'] instanceof DateTime) {
    $row['tanggal_mulai'] = $row['tanggal_mulai']->format(
        $type === 'detail' ? 'd-M-Y' : 'Y-m-d'
    );
}

$row['persen_diskon'] = number_format((float)($row['persen_diskon'] ?? 0), 0, ',', '.');
$row['status_event']  = (int)($row['status_event'] ?? 1);
$statusClass = $row['status_event'] === 1 ? 'status-active' : 'status-inactive';
$statusLabel = $row['status_event'] === 1 ? 'Running' : 'Complete';

function escHtml($str) {
    return htmlspecialchars($str ?? '-', ENT_QUOTES, 'UTF-8');
}
?>
<?php
    $products = $controller->fetchDetail($id) ?? [];
?>

<?php if ($type === 'detail'): ?>
    <div class="modal-card">
        <div class="modal-title">
            <span class="title-blue">EVENT</span>
            <span class="title-dark">DETAIL</span>
        </div>

        <div class="modal-code" style="margin: 0;">EVN-<?= $id ?></div>

        <div class="modal-field">
            <label>Event Name</label>
            <div class="modal-input-like"><?= escHtml($row['nama_event']) ?></div>
        </div>

        <div class="modal-field">
            <label>Event Type</label>
            <div class="modal-input-like"><?= escHtml($row['tipe_event']) ?></div>
        </div>

        <div style="width: 100%; display: flex; justify-content: space-between;">
            <div class="modal-field" style="width: 49%;">
                <label>Start Date</label>
                <div class="modal-pill-row">
                    <span class="pill-left"><?= escHtml($row['tanggal_mulai']) ?></span>
                </div>
            </div>

            <div class="modal-field" style="width: 49%;">
                <label>End Date</label>
                <div class="modal-pill-row">
                    <span class="pill-left"><?= escHtml($row['tanggal_berakhir']) ?></span>
                </div>
            </div>
        </div>

        <div class="modal-field">
            <label>Discount</label>
            <div class="modal-pill-row">
                <span class="pill-left"><?= escHtml($row['persen_diskon']) ?>%</span>
                <span class="pill-right"><?= count($products) ?> items</span>
            </div>
        </div>

        <?php if (count($products) > 0): $no = 1;?>
        <div style="height: 8.5rem; overflow-y: auto; padding: 0 7px; margin: 0 12px;" class="main-content">
            <table class="modal-product-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= $no++?></td>
                            <td><?= escHtml($p['nama_produk']) ?></td>
                            <td>Rp <?= number_format((float)($p['harga_event'] ?? 0), 0, ',', '.') ?></td>
                            <td><?= number_format((int)($p['stok_event'] ?? 0), 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="modal-status">
            This event status is currently
            <strong class="<?= $statusClass ?>"><?= $statusLabel ?></strong>
        </div>

        <div class="modal-footer">
            <button type="button" class="modal-confirm-btn" onclick="closeEventModal()">
                Confirm
            </button>
        </div>
    </div>



    
<?php elseif ($type === 'edit'): ?>
<?php
    $event = $row['event'] ?? $row;
    $products = $products ?? [];
?>
<div class="ee-card">
    <div class="ee-header">
        <span class="ee-title-black">EDIT</span>
        <span class="ee-title-blue"> Event</span>
    </div>

    <div class="ee-code">EVN-<?= (int)$id ?></div>

    <div style="display: flex; gap: 1rem;">
        <div>
            <div class="ee-grid-2">
                <div class="ee-field">
                    <label class="ee-label">Event Name <span class="ee-required">*</span></label>
                    <input id="ee_nama_event" type="text" class="ee-input" value="<?= escHtml($event['nama_event'] ?? '') ?>">
                    <span class="ee-error" id="ee_err_nama_event"></span>
                </div>

                <div class="ee-field">
                    <label class="ee-label">Event Type <span class="ee-required">*</span></label>
                    <div class="ee-select-wrap">
                        <select id="ee_tipe_event" class="ee-input ee-select" onchange="eeOnTypeChange()">
                            <option value="">— Select type —</option>
                            <option value="preorder" <?= (($event['tipe_event'] ?? '') === 'preorder') ? 'selected' : '' ?>>Pre-Order</option>
                            <option value="promo" <?= (($event['tipe_event'] ?? '') === 'promo') ? 'selected' : '' ?>>Promo</option>
                        </select>
                    </div>
                    <span class="ee-error" id="ee_err_tipe_event"></span>
                </div>

                <div class="ee-field">
                    <label class="ee-label">Start Date <span class="ee-required">*</span></label>
                    <input id="ee_tanggal_mulai" type="date" class="ee-input"
                        value="<?= escHtml($event['tanggal_mulai'] ?? '') ?>"
                        onchange="eeOnStartDateChange()">
                    <span class="ee-error" id="ee_err_tanggal_mulai"></span>
                </div>

                <div class="ee-field">
                    <label class="ee-label">End Date <span class="ee-required">*</span></label>
                    <input id="ee_tanggal_berakhir" type="date" class="ee-input"
                        value="<?= escHtml($event['tanggal_berakhir'] ?? '') ?>">
                    <span class="ee-error" id="ee_err_tanggal_berakhir"></span>
                </div>

                <div class="ee-field">
                    <label class="ee-label">Discount (%) <span class="ee-required">*</span></label>
                    <input id="ee_persen_diskon" type="number" class="ee-input"
                        value="<?= escHtml($event['persen_diskon'] ?? '') ?>"
                        step="0.01" oninput="eeRecalcHarga()">
                    <span class="ee-error" id="ee_err_persen_diskon"></span>
                </div>

                <div class="ee-field">
                    <label class="ee-label">Max Purchase <span class="ee-required">*</span></label>
                    <input id="ee_maks_pembelian" type="number" class="ee-input"
                        value="<?= (int)($event['maks_pembelian'] ?? 0) ?>"
                        min="1">
                    <span class="ee-error" id="ee_err_maks_pembelian"></span>
                </div>
            </div>

            <div class="ee-field" id="ee_row_tanggal_sampai" style="<?= (($event['tipe_event'] ?? '') === 'preorder') ? '' : 'display:none;' ?>">
                <label class="ee-label">Estimated Arrival Date <span class="ee-required">*</span></label>
                <input id="ee_tanggal_sampai" type="date" class="ee-input" value="<?= escHtml($event['tanggal_sampai'] ?? '') ?>">
                <span class="ee-error" id="ee_err_tanggal_sampai"></span>
            </div>

            <div class="ee-divider">
                <span>Product</span>
            </div>

            <div class="ee-product-search-wrap">
                <div class="ee-field" style="flex:1; margin-bottom:0;">
                    <label class="ee-label">Search Product</label>
                    <input id="ee_search_produk" type="text" class="ee-input" placeholder="Type product name..."
                        oninput="eeDebounceSearch()" autocomplete="off">
                    <div id="ee_search_results" class="ee-search-dropdown"></div>
                </div>
            </div>

            <div class="ee-product-form-grid">
                <div class="ee-field">
                    <label class="ee-label">Stock</label>
                    <input id="ee_stok_event" type="number" class="ee-input" placeholder="Stock qty" min="1">
                </div>
                <div class="ee-field ee-field-readonly">
                    <label class="ee-label">Event Price <small style="font-weight:400;color:#888;">(auto)</small></label>
                    <input id="ee_harga_event" type="text" class="ee-input" readonly placeholder="—">
                </div>
            </div>

            <input type="hidden" id="ee_selected_id_produk" value="">
            <input type="hidden" id="ee_selected_harga_jual" value="">
            <span class="ee-error" id="ee_err_produk"></span>

            <div style="text-align:center; margin: 10px 0 6px;">
                <button class="ee-btn-add-prod" onclick="eeAddProductToList(<?= (int)$id ?>)">
                    + Add Product
                </button>
                <span class="ee-error" id="ee_err_product_list"></span>
            </div>
        </div>

        <?php if (count($products) > 0): ?>
            <div style="display: flex; align-items: center; margin-bottom: 75px;">
                <div style="height: 13rem; overflow-y: auto; padding: 0px 10px; margin: 0 12px;" class="main-content">
                    <table class="ee-product-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="ee_product_tbody">
                            <?php $no = 1; foreach ($products as $p): ?>
                                <tr
                                    data-id-produk-event="<?= (int)($p['id_produk_event'] ?? 0) ?>"
                                    data-id-produk="<?= (int)($p['id_produk'] ?? 0) ?>"
                                    data-nama-produk="<?= escHtml($p['nama_produk'] ?? '') ?>"
                                    data-harga-event="<?= (float)($p['harga_event'] ?? 0) ?>"
                                    data-stok-event="<?= (int)($p['stok_event'] ?? 0) ?>"
                                >
                                    <td><?= $no++ ?></td>
                                    <td><?= escHtml($p['nama_produk'] ?? '-') ?></td>
                                    <td>Rp <?= number_format((float)($p['harga_event'] ?? 0), 0, ',', '.') ?></td>
                                    <td><?= number_format((int)($p['stok_event'] ?? 0), 0, ',', '.') ?></td>
                                    <td>
                                        <div class="btn-action-group">
                                            <button class="btn-edit-icon"
                                                    onclick="eeEditStock(<?= (int)($p['id_produk_event'] ?? 0) ?>, <?= (int)($p['stok_event'] ?? 0) ?>)">
                                                ✏️
                                            </button>

                                            <button class="btn-delete-icon"
                                                    onclick="eeRemoveProductFromEvent(<?= (int)($p['id_produk_event'] ?? 0) ?>)">
                                                🗑️
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="ee-footer">
        <button type="button" class="ee-btn-cancel" onclick="closeEventModal()">Cancel</button>
        <button type="button" class="ee-btn-confirm" onclick="eeSubmitEvent(<?= (int)$id ?>)">Save Changes</button>
    </div>
</div>

<style>
.ee-card { font-family: Arial, sans-serif; }
.ee-header {
    text-align: center;
    font-size: 26px;
    font-weight: 900;
    margin-bottom: 1px;
    letter-spacing: -0.5px;
}
.ee-title-black { color: #1a1a1a; }
.ee-title-blue  { color: #1284ff; }
.ee-code {
    text-align: center;
    font-weight: 700;
    margin-bottom: 16px;
    color: #666;
}
.ee-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 0 18px;
}
.ee-field {
    margin-bottom: 14px;
    position: relative;
}
.ee-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}
.ee-required { color: #e74c3c; }
.ee-input {
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
.ee-input:focus { border-color: #1284ff; }
.ee-input.ee-error-border { border-color: #e74c3c !important; }
.ee-input[readonly] { background: #f3f6fb; color: #888; cursor: default; }
.ee-select-wrap { position: relative; }
.ee-select-wrap::after {
    content: '▼';
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 10px;
    color: #888;
    pointer-events: none;
}
.ee-select { padding-right: 32px; cursor: pointer; }
.ee-error {
    display: block;
    font-size: 11px;
    color: #e74c3c;
    min-height: 14px;
    margin-top: 10px;
}
.ee-divider {
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
.ee-divider::before,
.ee-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #dde3ee;
}
.ee-product-search-wrap {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    margin-bottom: 10px;
    position: relative;
}
.ee-search-dropdown {
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
.ee-search-dropdown.open { display: block; }
.ee-search-item {
    padding: 9px 14px;
    font-size: 13px;
    cursor: pointer;
    border-bottom: 1px solid #f0f3f8;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background .12s;
}
.ee-search-item:last-child { border-bottom: none; }
.ee-search-item:hover { background: #eef4ff; }
.ee-search-item-name { font-weight: 600; color: #1a1a1a; }
.ee-search-item-price { color: #1284ff; font-size: 12px; font-weight: 700; }
.ee-search-item-type { font-size: 11px; color: #888; }
.ee-product-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0 18px;
    margin-bottom: 4px;
}
.ee-btn-add-prod {
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
.ee-btn-add-prod:hover { background: #333; }
.ee-product-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    font-size: 12px;
    border-radius: 12px;
    overflow: hidden;
}
.ee-product-table th {
    background: #1284ff;
    color: #fff;
    padding: 8px 10px;
    text-align: left;
    font-weight: 700;
}
.ee-product-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #eef2f8;
    background: #fff;
}
.ee-product-table tr:last-child td { border-bottom: none; }
.ee-footer {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: 20px;
}
.ee-btn-cancel {
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
.ee-btn-cancel:hover { background: #eef4ff; }
.ee-btn-confirm {
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
.ee-btn-confirm:hover { background: #0d6de0; }
</style>
<?php endif; ?>