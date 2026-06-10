<div style="flex: 1;">
    <div class="card-title-row">
        <h2 class="coolveticaa" style="font-size: 1.2rem;">Payment Method</h2>
        <button class="btn-add-green" onclick="openAddMetode()">+ Add Method</button>
    </div>

    <table class="styled-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Method Name</th>
                <th>Provider</th>
                <th>Admin Fee</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no_m = $offset_metode + 1;
            if ($stmt_metode): while ($rowMetode = sqlsrv_fetch_array($stmt_metode, SQLSRV_FETCH_ASSOC)): ?>
            <tr>
                <td><?= $no_m++ ?></td>
                <td><?= htmlspecialchars($rowMetode['nama_metode']) ?></td>
                <td><?= htmlspecialchars($rowMetode['provider'] ?? '-') ?></td>
                <td>Rp. <?= number_format($rowMetode['biaya_admin'], 0, ',', '.') ?></td>
                <td>
                    <?php if ($rowMetode['aktif'] == 1): ?>
                        <span style="color: #27AE60; font-weight: bold;">Active</span>
                    <?php else: ?>
                        <span style="color: #E74C3C; font-weight: bold;">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
    <div class="btn-action-group">
        <button class="btn-view-icon" onclick="openDetailMetode(<?= $rowMetode['id_metode'] ?>)">...</button>
        <button class="btn-edit-icon" onclick="openEditMetode(<?= $rowMetode['id_metode'] ?>)">✏️</button>
        <label class="switch">
            <input type="checkbox"
                <?= $rowMetode['aktif'] == 1 ? 'checked' : '' ?>
                onchange="toggleMetode(<?= $rowMetode['id_metode'] ?>, this.checked, this)">
            <span class="slider"></span>
        </label>
        <button class="btn-delete-icon" onclick="confirmDeleteMetode(<?= $rowMetode['id_metode'] ?>)">🗑️</button>
    </div>
</td>
            </tr>
            <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<div class="pagination-container">
    <?php if ($page_metode > 1): ?>
        <a href="?pp=<?= $page_produk ?>&pg=<?= $page_game ?>&ps=<?= $page_set ?>&pr=<?= $page_rarity ?>&pm=<?= $page_metode-1 ?>" class="page-link">&lt;</a>
    <?php else: ?>
        <span class="page-link disabled">&lt;</span>
    <?php endif; ?>

    <?php
    $range = 3;
    if ($page_metode > ($range + 2)) {
        echo '<a href="?pp='.$page_produk.'&pg='.$page_game.'&ps='.$page_set.'&pr='.$page_rarity.'&pm=1" class="page-link">1</a><span class="dots">...</span>';
    } elseif ($page_metode > $range + 1) {
        echo '<a href="?pp='.$page_produk.'&pg='.$page_game.'&ps='.$page_set.'&pr='.$page_rarity.'&pm=1" class="page-link">1</a>';
    }

    for ($i = max(1, $page_metode - $range); $i <= min($total_pages_metode, $page_metode + $range); $i++) {
        echo '<a href="?pp='.$page_produk.'&pg='.$page_game.'&ps='.$page_set.'&pr='.$page_rarity.'&pm='.$i.'" class="page-link '.($i == $page_metode ? 'active' : '').'">'.$i.'</a>';
    }

    if ($page_metode < ($total_pages_metode - $range - 1)) {
        echo '<span class="dots">...</span><a href="?pp='.$page_produk.'&pg='.$page_game.'&ps='.$page_set.'&pr='.$page_rarity.'&pm='.$total_pages_metode.'" class="page-link">'.$total_pages_metode.'</a>';
    } elseif ($page_metode < $total_pages_metode - $range) {
        echo '<a href="?pp='.$page_produk.'&pg='.$page_game.'&ps='.$page_set.'&pr='.$page_rarity.'&pm='.$total_pages_metode.'" class="page-link">'.$total_pages_metode.'</a>';
    }
    ?>

    <?php if ($page_metode < $total_pages_metode): ?>
        <a href="?pp=<?= $page_produk ?>&pg=<?= $page_game ?>&ps=<?= $page_set ?>&pr=<?= $page_rarity ?>&pm=<?= $page_metode+1 ?>" class="page-link">&gt;</a>
    <?php else: ?>
        <span class="page-link disabled">&gt;</span>
    <?php endif; ?>
</div>
