<div class="master-table-card">
    <div style="flex: 1;">
        <div class="card-title-row">
            <h2 class="coolveticaa" style="font-size: 1.2rem;">Game</h2>
            <button class="btn-add-green" onclick="openAddModal()">+ Add Game</button>
        </div>

        <table class="styled-table">
            <thead>
                <tr>
                    <th>Game Name</th>
                    <th>Developer</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = sqlsrv_fetch_array($stmt_game, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama_game']) ?></td>
                    <td><?= htmlspecialchars($row['developer']) ?></td>
                    <td style="color: <?= $row['aktif'] == 1 ? '#27AE60' : '#E74C3C' ?>; font-weight: bold;">
                        <?= $row['aktif'] == 1 ? 'Active' : 'Inactive' ?>
                    </td>
                    <td>
                        <div class="btn-action-group">
                            <button class="btn-edit-icon" onclick="openEditModal(<?= $row['id_game'] ?>)">✏️</button>
                            <?php if ($row['aktif'] == 1): ?>
                                <button class="btn-delete-icon" onclick="confirmDelete(<?= $row['id_game'] ?>)">🗑️</button>
                            <?php else: ?>
                                <button class="btn-restore-icon"
                                        style="background-color: #27AE60; border:none; padding:5px; border-radius:5px; color:white; cursor:pointer;"
                                        onclick="confirmRestore(<?= $row['id_game'] ?>)">🔄</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-container">
        <a href="?p=<?= max(1, $page - 1) ?>&ps=<?= $page_s ?>&pr=<?= $page_r ?>" class="page-link">&lt;</a>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?p=<?= $i ?>&ps=<?= $page_s ?>&pr=<?= $page_r ?>" class="page-link <?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <a href="?p=<?= min($total_pages, $page + 1) ?>&ps=<?= $page_s ?>&pr=<?= $page_r ?>" class="page-link">&gt;</a>
    </div>
</div>