<div style="flex: 1;">
    <div class="card-title-row">
        <h2 class="coolveticaa" style="font-size: 1.2rem;">Rarity</h2>
        <button class="btn-add-green" onclick="openModalRarity()">+ Add Rarity</button>
    </div>

    <table class="styled-table">
        <thead>
            <tr>
                <th>Rarity Name</th>
                <th>Rarity ID</th>
                <th>Game</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($stmt_rarity): while ($rowRarity = sqlsrv_fetch_array($stmt_rarity, SQLSRV_FETCH_ASSOC)): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($rowRarity['nama_rarity']) ?>
                    <?= !empty($rowRarity['kode_rarity']) ? ' (' . htmlspecialchars($rowRarity['kode_rarity']) . ')' : '' ?>
                </td>
                <td>RAR-<?= str_pad($rowRarity['id_rarity'], 3, '0', STR_PAD_LEFT) ?></td>
                <td style="color: #4A90E2;"><?= htmlspecialchars($rowRarity['nama_game'] ?? 'N/A') ?></td>
                <td style="color: <?= $rowRarity['aktif'] == 1 ? '#27AE60' : '#E74C3C' ?>; font-weight: bold;">
                    <?= $rowRarity['aktif'] == 1 ? 'Active' : 'Inactive' ?>
                </td>
                <td>
                    <div class="btn-action-group">
                        <button class="btn-edit-icon" onclick="openEditRarity(<?= $rowRarity['id_rarity'] ?>)">✏️</button>
                        <?php if ($rowRarity['aktif'] == 1): ?>
                            <button class="btn-delete-icon" onclick="confirmDeleteRarity(<?= $rowRarity['id_rarity'] ?>)">🗑️</button>
                        <?php else: ?>
                            <button class="btn-restore-icon"
                                    style="background-color: #27AE60; border:none; padding:5px; border-radius:5px; color:white; cursor:pointer;"
                                    onclick="confirmRestoreRarity(<?= $rowRarity['id_rarity'] ?>)">🔄</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<div class="pagination-container">
    <a href="?p=<?= $page ?>&pr=<?= max(1, $page_r - 1) ?>" class="page-link">&lt;</a>
    <?php for ($i = 1; $i <= $total_pages_r; $i++): ?>
        <a href="?p=<?= $page ?>&pr=<?= $i ?>" class="page-link <?= ($i == $page_r) ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <a href="?p=<?= $page ?>&pr=<?= min($total_pages_r, $page_r + 1) ?>" class="page-link">&gt;</a>
</div>