<?php
require_once __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/../controller/controllerFetch.php';

$id   = isset($_GET['id'])   ? (int)$_GET['id']   : 0;
$type = isset($_GET['type']) ? trim($_GET['type']) : 'detail';

if ($id <= 0) {
    echo '<p style="text-align:center;color:#E74C3C;padding:20px;">Invalid event ID.</p>';
    exit;
}

$controller = new controllerEvent($conn);
$row = $controller->fetchEventById($id);

if (!$row) {
    echo '<p style="text-align:center;color:#E74C3C;padding:20px;">Event not found.</p>';
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
if ($row['status_event'] === 1) {
    $statusLabel = "Running";
    $statusClass = "status-active";
} elseif ($row['status_event'] === 2) {
    $statusLabel = "Upcoming";
    $statusClass = "status-upcoming";
} else {
    $statusLabel = "Complete";
    $statusClass = "status-complete";
}

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
            <div style="display: flex; justify-content: space-between;">
                <div style="width: 49%;">
                    <label>Discount</label>
                    <div class="modal-pill-row">
                        <span class="pill-left"><?= escHtml($row['persen_diskon']) ?>%</span>
                    </div>
                </div>
                <div style="width: 49%;">
                    <label>Featured Item</label>
                    <div class="modal-pill-row">
                        <span class="pill-right"><?= count($products) ?> items</span>
                    </div>
                </div>
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
    // sp_GetEventDetail tidak mengembalikan foto_banner, jadi ambil lewat query ringan.
    $curBanner = '';
    $stmtBan = sqlsrv_query($conn, "SELECT foto_banner FROM dbo.event WHERE id_event = ?", [$id]);
    if ($stmtBan && ($rowBan = sqlsrv_fetch_array($stmtBan, SQLSRV_FETCH_ASSOC))) {
        $curBanner = trim((string)($rowBan['foto_banner'] ?? ''));
    }
?>
<div class="ee-card">
    <div class="ee-header">
        <span class="ee-title-black">EDIT</span>
        <span class="ee-title-blue"> Event</span>
    </div>

    <div class="ee-code">EVN-<?= (int)$id ?></div>

    <div class="ee-body-split">
        <div class="ee-form-col">
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
                        value="<?= escHtml($event['tanggal_berakhir'] ?? '') ?>" onchange="eeOnEndDateChange()">
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
                <?php 
                    $tglSampai = '';
                    if (!empty($event['tanggal_sampai'])) {
                        if (is_object($event['tanggal_sampai'])) {
                            $tglSampai = $event['tanggal_sampai']->format('Y-m-d');
                        } else {
                            $tglSampai = date('Y-m-d', strtotime($event['tanggal_sampai']));
                        }
                    }
                ?>
                <input id="ee_tanggal_sampai" type="date" class="ee-input" value="<?= escHtml($tglSampai) ?>">
                <span class="ee-error" id="ee_err_tanggal_sampai"></span>
            </div>

            <div class="ee-divider">
                <span>Product</span>
            </div>

            <div class="ee-product-search-wrap">
                <div class="ee-field" style="flex:1; margin-bottom:0;">
                    <label class="ee-label" style="display:flex; justify-content:space-between; align-items:center;">
                        <span>Search Product</span>
                        <button type="button" onclick="eeStartAddProduct(<?= (int)$id ?>)" title="Create a brand-new product, then come back here"
                            style="background:none; border:none; color:var(--primary-color,#173C99); font-weight:700; font-size:0.72rem; cursor:pointer; padding:0;">+ New Product</button>
                    </label>
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

        <div class="ee-table-col">
            <!-- Banner: SELALU tampil, di atas tabel. Kalau event sudah punya banner,
                 langsung ditampilkan sebagai preview. -->
            <div class="ee-banner-wrap">
                <label class="ee-label">Event Banner <small style="font-weight:400;color:#888;">(optional)</small></label>
                <label for="ee_banner_input" class="ee-banner-drop <?= $curBanner ? 'has-img' : '' ?>" id="ee_banner_drop">
                    <img id="ee_banner_preview" alt="banner preview" <?= $curBanner ? 'src="/CardHaven/' . escHtml($curBanner) . '"' : '' ?>>
                    <div class="ee-banner-placeholder" id="ee_banner_placeholder">
                        <span style="font-size:26px; line-height:1;">🖼️</span>
                        <span style="font-weight:700; font-size:13px; color:#333;">Upload Banner</span>
                        <small style="color:#8a97b5;">JPG / PNG / WEBP · max 3 MB</small>
                    </div>
                    <button type="button" class="ee-banner-remove" id="ee_banner_remove" onclick="eeRemoveBanner(event)" title="Remove banner">&times;</button>
                </label>
                <input type="file" id="ee_banner_input" accept="image/png,image/jpeg,image/webp" onchange="eePreviewBanner(this)" style="display:none;">
                <input type="hidden" id="ee_banner_remove_flag" value="0">
                <span class="ee-error" id="ee_err_banner"></span>
            </div>

            <?php if (count($products) > 0): ?>
            <div id="ee_product_table_wrap">
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
                                                    onclick="eeEditStock(<?= (int)($p['id_produk_event'] ?? 0) ?>, <?= (int)($p['stok_event'] ?? 0) ?>, <?= (int)($p['stok'] ?? 0) ?>)" 
                                                    title="Edit Stock">
                                                <img src="/cardhaven/assets/image/edit.svg" alt="Edit">
                                            </button>

                                            <button class="btn-delete-icon"
                                                    onclick="eeRemoveProductFromEvent(<?= (int)($p['id_produk_event'] ?? 0) ?>)">
                                                <img src="/cardhaven/assets/image/delete.svg" alt="">
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
            </div>
            <?php endif; ?>
        </div>
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

/* ── layout 2 kolom: form (kiri) + banner/tabel (kanan) ── */
.ee-body-split { display: flex; gap: 24px; align-items: flex-start; }
.ee-form-col { width: 460px; flex: 0 0 460px; min-width: 0; }
.ee-table-col { flex: 0 0 360px; min-width: 0; margin-top: 40px; }

/* ── Banner upload (dropzone + preview) ── */
.ee-banner-wrap { margin-bottom: 18px; }
.ee-banner-drop {
    position: relative;
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;
    width: 100%; height: 170px; margin-top: 5px;
    border: 1.5px dashed #b9c6de; border-radius: 12px;
    background: #f7faff; cursor: pointer; overflow: hidden;
    transition: border-color .15s, background .15s;
}
.ee-banner-drop:hover { border-color: #1284ff; background: #eef4ff; }
.ee-banner-drop img { display: none; width: 100%; height: 100%; object-fit: cover; }
.ee-banner-drop.has-img img { display: block; }
.ee-banner-drop.has-img .ee-banner-placeholder { display: none; }
.ee-banner-placeholder { display: flex; flex-direction: column; align-items: center; gap: 4px; text-align: center; pointer-events: none; }
.ee-banner-remove {
    display: none; position: absolute; top: 8px; right: 8px;
    width: 26px; height: 26px; border-radius: 50%; border: none;
    background: rgba(231,76,60,.92); color: #fff; font-size: 16px; cursor: pointer;
    align-items: center; justify-content: center; line-height: 1;
}
.ee-banner-drop.has-img .ee-banner-remove { display: flex; }

/* ── Tabel produk: header sticky + scroll setelah ~3 baris ── */
#ee_product_table_wrap {
    max-height: 12rem;
    overflow-y: auto;
    border: 1px solid #eef2f8;
    border-radius: 12px;
}
#ee_product_table_wrap .ee-product-table thead th { position: sticky; top: 0; z-index: 2; }

@media screen and (max-width: 768px) {
    .ee-body-split { flex-direction: column; gap: 12px; }
    .ee-form-col, .ee-table-col { width: 100%; flex: 1 1 auto; margin-top: 0; }
}

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
    text-align: center;
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