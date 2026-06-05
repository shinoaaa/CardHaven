<div style="flex: 1;">
    <div class="card-title-row">
        <h2 class="coolveticaa" style="font-size: 1.2rem;">Set</h2>
        <button class="btn-add-green" onclick="openAddSetModal()">+ Add Set</button>
    </div>

    <table class="styled-table">
        <thead>
            <tr>
                <th>Set Name</th>
                <th>Game</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($stmt_set): while ($rowSet = sqlsrv_fetch_array($stmt_set, SQLSRV_FETCH_ASSOC)): ?>
            <tr>
                <td><?= htmlspecialchars($rowSet['nama_set']) ?></td>
                <td style="color: #4A90E2;"><?= htmlspecialchars($rowSet['nama_game'] ?? 'N/A') ?></td>
                <td style="color: <?= $rowSet['aktif'] == 1 ? '#27AE60' : '#E74C3C' ?>; font-weight: bold;">
                    <?= $rowSet['aktif'] == 1 ? 'Active' : 'Inactive' ?>
                </td>
                <td>
                    <div class="btn-action-group">
                        <button class="btn-edit-icon" onclick="openEditSetModal(<?= $rowSet['id_set'] ?>)">✏️</button>
                        <?php if ($rowSet['aktif'] == 1): ?>
                            <button class="btn-delete-icon" onclick="confirmDeleteSet(<?= $rowSet['id_set'] ?>)">🗑️</button>
                        <?php else: ?>
                            <button class="btn-restore-icon"
                                    style="background-color: #27AE60; border:none; padding:5px; border-radius:5px; color:white; cursor:pointer;"
                                    onclick="confirmRestoreSet(<?= $rowSet['id_set'] ?>)">🔄</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<div class="pagination-container">
    <a href="?p=<?= $page ?>&ps=<?= max(1, $page_s - 1) ?>&pr=<?= $page_r ?>" class="page-link">&lt;</a>
    <?php for ($i = 1; $i <= $total_pages_s; $i++): ?>
        <a href="?p=<?= $page ?>&ps=<?= $i ?>&pr=<?= $page_r ?>" class="page-link <?= ($i == $page_s) ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <a href="?p=<?= $page ?>&ps=<?= min($total_pages_s, $page_s + 1) ?>&pr=<?= $page_r ?>" class="page-link">&gt;</a>
</div>