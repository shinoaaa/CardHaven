<div style="flex: 1;" id="container-rarity">
    <div class="card-title-row">
        <h2 class="coolveticaa" style="font-size: 1.2rem;">Rarity</h2>
        <button class="btn-add-green" onclick="openModalRarity()">+ Add Rarity</button>
    </div>

    <table class="styled-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Rarity Name</th>
                <th>Game</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = $offset_rarity + 1;
            if (!empty($data_rarity)):
                foreach ($data_rarity as $rowRarity): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td>
                    <?= htmlspecialchars($rowRarity['nama_rarity']) ?>
                    <?= !empty($rowRarity['kode_rarity']) ? ' (' . htmlspecialchars($rowRarity['kode_rarity']) . ')' : '' ?>
                </td>
                <td><?= htmlspecialchars($rowRarity['nama_game'] ?? 'N/A') ?></td>
                <td>
                    <?php if ($rowRarity['aktif'] == 1): ?>
                        <span style="color: #27AE60; font-weight: bold;">Active</span>
                    <?php else: ?>
                        <span style="color: #E74C3C; font-weight: bold;">Inactive</span>
                    <?php endif; ?>
                </td> 
                <td> 
                    <div class="btn-action-group">
                        <button class="btn-view-icon" onclick="openDetailRarity(<?= $rowRarity['id_rarity'] ?>)">...</button>
                        <button class="btn-edit-icon" onclick="openEditRarity(<?= $rowRarity['id_rarity'] ?>)"><img src="/cardhaven/assets/image/edit.svg" alt=""></button>
                        <button class="btn-delete-icon" onclick="confirmDeleteRarity(<?= $rowRarity['id_rarity'] ?>)"><img src="/cardhaven/assets/image/delete.svg" alt=""></button>
                        <label class="switch">
                            <input type="checkbox" 
                                <?= $rowRarity['aktif'] == 1 ? 'checked' : '' ?> 
                                onchange="toggleRarityStatus(<?= $rowRarity['id_rarity'] ?>, this.checked, this)">
                            <span class="slider"></span>
                        </label>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="5" style="text-align:center; color:#aaa; padding:20px;">No rarities found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pagination-container">
    <?php if ($page_rarity > 1): ?>
        <a href="javascript:void(0)" onclick="loadRarityPage(<?= $page_rarity - 1 ?>)" class="page-link">&lt;</a>
    <?php else: ?>
        <span class="page-link disabled">&lt;</span>
    <?php endif; ?>

    <?php
    $range = 3;
    if ($page_rarity > ($range + 2)) {
        echo '<a href="javascript:void(0)" onclick="loadRarityPage(1)" class="page-link">1</a><span class="dots">...</span>';
    } elseif ($page_rarity > $range + 1) {
        echo '<a href="javascript:void(0)" onclick="loadRarityPage(1)" class="page-link">1</a>';
    }

    for ($i = max(1, $page_rarity - $range); $i <= min($total_pages_rarity, $page_rarity + $range); $i++) {
        $activeClass = ($i == $page_rarity) ? 'active' : '';
        echo '<a href="javascript:void(0)" onclick="loadRarityPage('.$i.')" class="page-link '.$activeClass.'">'.$i.'</a>';
    }

    if ($page_rarity < ($total_pages_rarity - $range - 1)) {
        echo '<span class="dots">...</span><a href="javascript:void(0)" onclick="loadRarityPage('.$total_pages_rarity.')" class="page-link">'.$total_pages_rarity.'</a>';
    } elseif ($page_rarity < $total_pages_rarity - $range) {
        echo '<a href="javascript:void(0)" onclick="loadRarityPage('.$total_pages_rarity.')" class="page-link">'.$total_pages_rarity.'</a>';
    }
    ?>

    <?php if ($page_rarity < $total_pages_rarity): ?>
        <a href="javascript:void(0)" onclick="loadRarityPage(<?= $page_rarity + 1 ?>)" class="page-link">&gt;</a>
    <?php else: ?>
        <span class="page-link disabled">&gt;</span>
    <?php endif; ?>
</div>