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
    <div class="modal-card">
        <div class="modal-title">
            <span class="title-blue">EVENT</span>
            <span class="title-dark">EDIT</span>
        </div>

        <div class="modal-code" style="margin: 0;">EVN-<?= $id ?></div>

        <div class="modal-field">
            <label>Event Name</label>
            <input class="modal-input-like" placeholder="<?= escHtml($row['nama_event']) ?>"></input>
        </div>

        <div class="modal-field">
            <label>Event Type</label>
            <input class="modal-input-like" placeholder="<?= escHtml($row['tipe_event']) ?>"></input>
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
                <input
                    type="date"
                    class="pill-left"
                    value="<?= escHtml($row['tanggal_berakhir']) ?>">
            </div>
        </div>

        <div class="modal-field">
            <label>Discount</label>
            <input class="pill-left" type="number" placeholder="<?= escHtml($row['persen_diskon']) ?>%"></input>
        </div>

        <?php if (count($products) > 0): $no = 1;?>
            <div style="height: 8.5rem; overflow-y: auto; padding: 0 7px; margin: 0 12px;" class="main-content">
                <table class="modal-product-table">
                    <thead>
                        <tr >
                            <th>No</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td><?= $no++?></td>
                                <td><?= escHtml($p['nama_produk']) ?></td>
                                <td>Rp <?= number_format((float)($p['harga_event'] ?? 0), 0, ',', '.') ?></td>
                                <td><?= number_format((int)($p['stok_event'] ?? 0), 0, ',', '.') ?></td>
                                <td>
                                    <div class="btn-action-group">

                                        <button class="btn-edit-icon">
                                            ✏️
                                        </button>

                                        <button class="btn-delete-icon">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="modal-footer">
            <button type="button" class="modal-confirm-btn" onclick="closeEventModal()">
                Confirm
            </button>
        </div>
    </div>
<?php endif; ?>